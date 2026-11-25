<?php
require_once __DIR__ . '/../settings/db_class.php';

// Membership rows: pending (waiting for Paystack) -> active (paid) -> replaced/expired.
// order_ref holds the Paystack reference for that membership payment.
class MembershipClass extends Database
{
    public function activeFor($userId)
    {
        return $this->fetchOne(
            "SELECT * FROM aku_memberships WHERE user_id = ? AND status = 'active'
             AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1",
            [$userId]
        );
    }

    public function byRef($ref)
    {
        return $this->fetchOne('SELECT * FROM aku_memberships WHERE order_ref = ?', [$ref]);
    }

    // Created just before the customer goes to Paystack. Stays 'pending' until payment is verified.
    public function createPending($userId, $tier, $price, $ref)
    {
        $this->execute("UPDATE aku_memberships SET status = 'cancelled' WHERE user_id = ? AND status = 'pending'", [$userId]);
        $this->execute(
            "INSERT INTO aku_memberships (user_id, tier, price, status, order_ref) VALUES (?, ?, ?, 'pending', ?)",
            [$userId, $tier, $price, $ref]
        );
        return (int)$this->lastId();
    }

    // Call ONLY after Paystack has verified the payment on the server.
    // Starts a 1-month membership and retires any older active one. Safe to call twice.
    public function activate($ref)
    {
        $m = $this->byRef($ref);
        if (!$m || $m['status'] !== 'pending') return false;
        $old = $this->activeFor($m['user_id']);
        $isUpgrade = $old && membership_upgrade_price($old['tier'], $m['tier']) !== null;   // paid the difference: same end date
        $oldVoucher = $isUpgrade ? $this->voucherFor($old) : null;
        $this->begin();
        try {
            $this->execute("UPDATE aku_memberships SET status = 'replaced' WHERE user_id = ? AND status = 'active'", [$m['user_id']]);
            if ($isUpgrade) {
                $this->execute("UPDATE aku_memberships SET status = 'active', starts_at = NOW(), expires_at = ? WHERE order_ref = ? AND status = 'pending'", [$old['expires_at'], $ref]);
            } else {
                $this->execute(
                    "UPDATE aku_memberships SET status = 'active', starts_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                     WHERE order_ref = ? AND status = 'pending'",
                    [$ref]
                );
            }
            $this->commit();
            $new = $this->voucherFor($this->byRef($ref));   // this month's restaurant voucher
            if ($isUpgrade && $new && $oldVoucher) {
                // Upgrade: what was already spent stays spent; the balance grows by the difference. The old code stops working.
                try {
                    $balance = max(0, (float)$oldVoucher['balance'] + (float)$new['amount'] - (float)$oldVoucher['amount']);
                    $this->execute('UPDATE aku_member_vouchers SET balance = ? WHERE id = ?', [$balance, $new['id']]);
                    $this->execute('UPDATE aku_member_vouchers SET expires_at = NOW() WHERE id = ?', [$oldVoucher['id']]);
                } catch (PDOException $e) { error_log('Voucher upgrade: ' . $e->getMessage()); }
            }
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /* ---------- Benefits (tables from db/membership_benefits.sql; quietly off until they exist) ---------- */

    // The restaurant voucher for one membership month. Made on first need, so older memberships get one too.
    public function voucherFor($membership)
    {
        if (!$membership || $membership['status'] !== 'active') return null;
        try {
            $v = $this->fetchOne('SELECT * FROM aku_member_vouchers WHERE membership_id = ?', [$membership['id']]);
            if ($v) return $v;
            $tier = tier_info($membership['tier']);
            if (!$tier) return null;
            for ($try = 0; $try < 5; $try++) {   // unique, easy to read aloud (no 0/O or 1/I)
                $code = 'AKU-' . strtoupper(substr($tier['name'], 0, 1)) . '-' . substr(str_shuffle(str_repeat('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', 3)), 0, 6);
                try {
                    $this->execute('INSERT INTO aku_member_vouchers (membership_id, user_id, code, amount, balance, expires_at) VALUES (?, ?, ?, ?, ?, ?)',
                                   [$membership['id'], $membership['user_id'], $code, $tier['voucher'], $tier['voucher'], $membership['expires_at']]);
                    break;
                } catch (PDOException $dup) { if ($try === 4) throw $dup; }
            }
            return $this->fetchOne('SELECT * FROM aku_member_vouchers WHERE membership_id = ?', [$membership['id']]);
        } catch (PDOException $e) { return null; }
    }

    public function redemptions($voucherId)
    {
        try { return $this->fetchAll('SELECT * FROM aku_voucher_redemptions WHERE voucher_id = ? ORDER BY id DESC', [(int)$voucherId]); }
        catch (PDOException $e) { return []; }
    }

    // Admin records a meal paid with a voucher. Returns [ok, message].
    public function redeem($code, $amount, $restaurant)
    {
        $code = strtoupper(trim((string)$code));
        $v = $this->fetchOne('SELECT v.*, (v.expires_at IS NOT NULL AND v.expires_at < NOW()) AS expired FROM aku_member_vouchers v WHERE v.code = ?', [$code]);
        if (!$v) return [false, "No voucher has the code $code."];
        if ((int)$v['expired']) return [false, "Voucher $code expired on " . date('j M Y', strtotime($v['expires_at'])) . '.'];
        if ((float)$amount > (float)$v['balance']) return [false, "Voucher $code only has " . money($v['balance']) . ' left.'];
        $this->begin();
        try {
            // Only takes the money off if the balance is still enough (two admins at once can't overspend it).
            if ($this->execute('UPDATE aku_member_vouchers SET balance = balance - ? WHERE id = ? AND balance >= ?', [$amount, $v['id'], $amount]) !== 1) {
                $this->rollback(); return [false, "Voucher $code no longer has enough left."];
            }
            $this->execute('INSERT INTO aku_voucher_redemptions (voucher_id, amount, restaurant) VALUES (?, ?, ?)', [$v['id'], $amount, $restaurant]);
            $this->commit();
        } catch (Throwable $e) { $this->rollback(); throw $e; }
        return [true, money($amount) . " used on voucher $code at $restaurant. " . money((float)$v['balance'] - (float)$amount) . ' left.'];
    }

    // Delivery credit used by a member this calendar month (same rule as checkout).
    public function deliveryCreditUsed($userId)
    {
        $r = $this->fetchOne("SELECT COALESCE(SUM(delivery_credit), 0) AS used FROM aku_orders WHERE user_id = ? AND status <> 'cancelled' AND payment_status = 'paid' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", [(int)$userId]);
        return (float)$r['used'];
    }

    // Admin: everyone who has paid for a membership, newest first, with their voucher.
    public function adminList($q = '')
    {
        $sql = "SELECT m.*, c.customer_name, c.customer_email, c.customer_contact,
                       (m.status = 'active' AND (m.expires_at IS NULL OR m.expires_at > NOW())) AS is_live
                FROM aku_memberships m JOIN aku_customer c ON c.customer_id = m.user_id
                WHERE (m.status IN ('active', 'replaced', 'expired') OR (m.status = 'cancelled' AND m.starts_at IS NOT NULL))";
        $p = [];
        if ($q !== '') { $sql .= ' AND (c.customer_name LIKE ? OR c.customer_email LIKE ? OR m.tier LIKE ?)'; $like = like_pattern($q); array_push($p, $like, $like, $like); }
        $rows = $this->fetchAll($sql . ' ORDER BY is_live DESC, m.id DESC', $p);
        foreach ($rows as &$r) {
            try { $r['voucher'] = $this->fetchOne('SELECT * FROM aku_member_vouchers WHERE membership_id = ?', [$r['id']]); }
            catch (PDOException $e) { $r['voucher'] = null; }
            if (!$r['voucher'] && (int)$r['is_live']) $r['voucher'] = $this->voucherFor($r);
        }
        return $rows;
    }

    // Ends an active membership now: benefits stop and the voucher can no longer be used. No automatic refund.
    // $userId limits it to the member's own membership (null = admin). Returns the membership row, or null.
    public function cancel($membershipId, $userId = null)
    {
        $m = $this->fetchOne("SELECT * FROM aku_memberships WHERE id = ? AND status = 'active'" . ($userId !== null ? ' AND user_id = ?' : ''),
                             $userId !== null ? [(int)$membershipId, (int)$userId] : [(int)$membershipId]);
        if (!$m) return null;
        $this->execute("UPDATE aku_memberships SET status = 'cancelled', expires_at = NOW() WHERE id = ? AND status = 'active'", [$m['id']]);
        try { $this->execute('UPDATE aku_member_vouchers SET expires_at = NOW() WHERE membership_id = ?', [$m['id']]); } catch (PDOException $e) {}
        return $m;
    }

    public function markFailed($ref)
    {
        $this->execute("UPDATE aku_memberships SET status = 'failed' WHERE order_ref = ? AND status = 'pending'", [$ref]);
    }
}
