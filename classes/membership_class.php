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
        $this->begin();
        try {
            $this->execute("UPDATE aku_memberships SET status = 'replaced' WHERE user_id = ? AND status = 'active'", [$m['user_id']]);
            $this->execute(
                "UPDATE aku_memberships SET status = 'active', starts_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                 WHERE order_ref = ? AND status = 'pending'",
                [$ref]
            );
            $this->commit();
            return true;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function markFailed($ref)
    {
        $this->execute("UPDATE aku_memberships SET status = 'failed' WHERE order_ref = ? AND status = 'pending'", [$ref]);
    }
}
