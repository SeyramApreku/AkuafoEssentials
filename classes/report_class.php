<?php
require_once __DIR__ . '/../settings/db_class.php';

// Revenue reporting. "Revenue" = paid orders that weren't cancelled, by the day they were placed.
// Dates are inclusive Y-m-d strings.
class ReportClass extends Database
{
    private const PAID = "o.payment_status = 'paid' AND o.status <> 'cancelled'";

    public function summary($from, $to)
    {
        $r = $this->fetchOne(
            'SELECT COUNT(*) AS orders, COALESCE(SUM(o.total), 0) AS revenue, COALESCE(SUM(o.subtotal), 0) AS goods,
                    COALESCE(SUM(o.delivery), 0) AS delivery, COALESCE(AVG(o.total), 0) AS avg_order
               FROM aku_orders o WHERE ' . self::PAID . ' AND DATE(o.created_at) BETWEEN ? AND ?', [$from, $to]);
        $refund = $this->fetchOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(o.total), 0) AS amount FROM aku_orders o
              WHERE o.payment_status = 'paid' AND o.status = 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?", [$from, $to]);
        $mem = $this->fetchOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(price), 0) AS amount FROM aku_memberships
              WHERE status IN ('active', 'replaced', 'expired') AND DATE(COALESCE(starts_at, created_at)) BETWEEN ? AND ?", [$from, $to]);
        return $r + ['refunds' => (int)$refund['n'], 'refund_amount' => (float)$refund['amount'],
                     'memberships' => (int)$mem['n'], 'membership_amount' => (float)$mem['amount']];
    }

    // One row per day in the range (days without sales included as 0).
    public function daily($from, $to)
    {
        $got = [];
        foreach ($this->fetchAll('SELECT DATE(o.created_at) AS d, COUNT(*) AS orders, SUM(o.total) AS revenue FROM aku_orders o
                                   WHERE ' . self::PAID . ' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY DATE(o.created_at)', [$from, $to]) as $r) $got[$r['d']] = $r;
        $out = [];
        for ($d = new DateTime($from), $end = new DateTime($to); $d <= $end; $d->modify('+1 day')) {
            $k = $d->format('Y-m-d');
            $out[] = ['d' => $k, 'orders' => (int)($got[$k]['orders'] ?? 0), 'revenue' => (float)($got[$k]['revenue'] ?? 0)];
        }
        return $out;
    }

    // Revenue by what was sold: produce, weekly boxes, hampers/bulk, entertaining.
    public function byItemType($from, $to)
    {
        return $this->fetchAll('SELECT oi.type, COUNT(DISTINCT o.id) AS orders, SUM(oi.qty) AS units, SUM(oi.price * oi.qty) AS revenue
                                  FROM aku_order_items oi JOIN aku_orders o ON o.id = oi.order_id
                                 WHERE ' . self::PAID . ' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY oi.type ORDER BY revenue DESC', [$from, $to]);
    }

    public function topItems($from, $to, $limit = 10)
    {
        return $this->fetchAll('SELECT oi.name, oi.type, SUM(oi.qty) AS units, SUM(oi.price * oi.qty) AS revenue
                                  FROM aku_order_items oi JOIN aku_orders o ON o.id = oi.order_id
                                 WHERE ' . self::PAID . ' AND DATE(o.created_at) BETWEEN ? AND ?
                                 GROUP BY oi.name, oi.type ORDER BY revenue DESC LIMIT ' . (int)$limit, [$from, $to]);   // by name: the same box on different schedules counts once
    }
}
