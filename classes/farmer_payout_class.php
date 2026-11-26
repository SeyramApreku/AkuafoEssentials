<?php
require_once __DIR__ . '/../settings/db_class.php';

// Farmer payouts, fully automatic.
//   When a customer pays, each Farm Shop item is tagged with its farmer and that farmer's commission rate
//   (OrderClass::stampFarmers). The farmer gets the sale price minus the commission; Akuafo keeps the commission.
//   Weeks run Saturday to Friday and are worked out by themselves once the week is over. The plan's monthly fee
//   comes off the first payout of the month; if a week ends up negative, the rest is carried to the next week.
//   The admin's only step: send the MoMo and click "Mark as paid".
// The maths itself is farmer_payout_calc() in core/core.php.
class FarmerPayoutClass extends Database
{
    private const PAID = "o.payment_status = 'paid' AND o.status <> 'cancelled'";

    // Paid orders whose items were never tagged (e.g. paid before this existed): tag them now.
    public function catchUp()
    {
        $this->execute("UPDATE aku_order_items i
                          JOIN aku_orders o ON o.id = i.order_id AND o.payment_status = 'paid'
                          JOIN aku_products p ON i.item_key = CONCAT('p-', p.id)
                          JOIN aku_farmers f ON f.id = COALESCE(p.farmer_id, (SELECT f2.id FROM aku_farmers f2 WHERE LOWER(TRIM(f2.name)) = LOWER(TRIM(p.farmer)) LIMIT 1))
                           SET i.farmer_id = f.id, i.source_channel = 'shop', i.commission_rate = f.commission_rate
                         WHERE i.type = 'produce' AND i.farmer_id IS NULL");
    }

    public function farmers() { return $this->fetchAll('SELECT * FROM aku_farmers ORDER BY name'); }

    // Farm Shop items paid for in the week (by the day the order was placed, same as the Revenue page).
    public function sales($farmerId, $start, $end)
    {
        return $this->fetchAll(
            'SELECT i.name, i.qty, i.price, i.price * i.qty AS amount, COALESCE(i.commission_rate, f.commission_rate) AS rate, o.ref, o.created_at
               FROM aku_order_items i JOIN aku_orders o ON o.id = i.order_id JOIN aku_farmers f ON f.id = i.farmer_id
              WHERE i.farmer_id = ? AND ' . self::PAID . ' AND DATE(o.created_at) BETWEEN ? AND ? ORDER BY o.created_at',
            [$farmerId, $start, $end]
        );
    }

    // What the farmer still owes from the last payout before this week.
    public function carriedIn($farmerId, $start)
    {
        $r = $this->fetchOne('SELECT carried_out FROM aku_farmer_payouts WHERE farmer_id = ? AND period_start < ? ORDER BY period_start DESC LIMIT 1', [$farmerId, $start]);
        return $r ? (float)$r['carried_out'] : 0.0;
    }

    // The plan fee comes off once a month: the first payout of that month that has sales.
    public function planFee($farmer, $end, $payoutId = null)
    {
        $plan = farmer_plans()[$farmer['plan'] ?? 'basic'] ?? null;
        if (!$plan || (float)$plan['monthly_fee'] <= 0) return 0.0;
        $taken = $this->fetchOne("SELECT id FROM aku_farmer_payouts WHERE farmer_id = ? AND plan_fee > 0 AND DATE_FORMAT(period_end, '%Y-%m') = ? AND id <> ?",
            [$farmer['id'], date('Y-m', strtotime($end)), (int)$payoutId]);
        return $taken ? 0.0 : (float)$plan['monthly_fee'];
    }

    // One farmer's week: the sales plus the totals.
    public function preview($farmer, $start, $end, $payoutId = null)
    {
        $sales = $this->sales($farmer['id'], $start, $end);
        $fee = $sales ? $this->planFee($farmer, $end, $payoutId) : 0.0;   // no sales, no fee that week
        $calc = farmer_payout_calc($sales, 0, 0, 0, $fee, $this->carriedIn($farmer['id'], $start));
        return ['farmer' => $farmer, 'sales' => $sales, 'calc' => $calc];
    }

    public function forWeek($start)
    {
        return $this->fetchAll('SELECT p.*, f.name FROM aku_farmer_payouts p JOIN aku_farmers f ON f.id = p.farmer_id WHERE p.period_start = ? ORDER BY f.name', [$start]);
    }

    // Saves a finished week's payouts. Paid ones are never touched; unpaid ones are refreshed
    // (e.g. an order was cancelled and refunded). Returns how many were saved.
    public function generate($start, $end)
    {
        $saved = 0;
        $this->begin();
        try {
            foreach ($this->farmers() as $f) {
                $old = $this->fetchOne('SELECT * FROM aku_farmer_payouts WHERE farmer_id = ? AND period_start = ?', [$f['id'], $start]);
                if ($old && $old['status'] === 'paid') continue;
                $w = $this->preview($f, $start, $end, $old['id'] ?? null);
                $c = $w['calc'];
                if ($old) $this->execute('DELETE FROM aku_farmer_payouts WHERE id = ?', [$old['id']]);
                if (!$w['sales'] && $c['carried_in'] == 0) continue;   // nothing happened for this farmer
                $this->execute('INSERT INTO aku_farmer_payouts (farmer_id, period_start, period_end, gross_sales, commission, shop_earnings, box_supply_value, pickup_fees, qc_deductions, plan_fee, carried_in, net_payout, carried_out)
                                VALUES (?,?,?,?,?,?,0,0,0,?,?,?,?)',
                    [$f['id'], $start, $end, $c['gross_sales'], $c['commission'], $c['shop_earnings'], $c['plan_fee'], $c['carried_in'], $c['net_payout'], $c['carried_out']]);
                $saved++;
            }
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
        return $saved;
    }

    // Works out the last few finished weeks by itself (oldest first, so carry-forwards chain correctly).
    public function autoGenerate($weeks = 4)
    {
        [$thisStart] = payout_week();
        for ($i = $weeks; $i >= 1; $i--) {
            $s = date('Y-m-d', strtotime("$thisStart -" . (7 * $i) . ' days'));
            $this->generate($s, date('Y-m-d', strtotime("$s +6 days")));
        }
    }

    public function markPaid($id, $momoRef)
    {
        return $this->execute("UPDATE aku_farmer_payouts SET status = 'paid', paid_at = NOW(), momo_reference = ? WHERE id = ? AND status = 'pending'", [$momoRef !== '' ? $momoRef : null, $id]);
    }

    // Dashboard cards for a month (Y-m).
    public function monthTotals($month)
    {
        $first = $month . '-01'; $last = date('Y-m-t', strtotime($first));
        $split = farmer_split_totals($this, $first, $last);
        $paid = $this->fetchOne("SELECT COALESCE(SUM(net_payout), 0) AS v FROM aku_farmer_payouts WHERE status = 'paid' AND DATE(paid_at) BETWEEN ? AND ?", [$first, $last]);
        $due = $this->fetchOne("SELECT COALESCE(SUM(net_payout), 0) AS v, COUNT(*) AS n FROM aku_farmer_payouts WHERE status = 'pending' AND net_payout > 0");
        return $split + ['paid' => (float)$paid['v'], 'due' => (float)$due['v'], 'due_n' => (int)$due['n']];
    }
}
