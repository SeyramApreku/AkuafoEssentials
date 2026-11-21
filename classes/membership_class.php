<?php
require_once __DIR__ . '/../settings/db_class.php';

class MembershipClass extends Database
{
    public function activeFor($userId)
    {
        return $this->fetchOne(
            "SELECT * FROM memberships WHERE user_id = ? AND status = 'active'
             AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1",
            [$userId]
        );
    }

    public function pendingFor($userId)
    {
        return $this->fetchOne(
            "SELECT * FROM memberships WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1",
            [$userId]
        );
    }

    // Created when the order is placed. It stays 'pending' until payment is confirmed.
    public function createPending($userId, $tier, $price, $orderRef)
    {
        $this->execute("UPDATE memberships SET status = 'cancelled' WHERE user_id = ? AND status = 'pending'", [$userId]);
        $this->execute(
            "INSERT INTO memberships (user_id, tier, price, status, order_ref) VALUES (?, ?, ?, 'pending', ?)",
            [$userId, $tier, $price, $orderRef]
        );
        return (int)$this->lastId();
    }

    // Call this ONLY after payment for the order has been confirmed
    // (payment gateway webhook or server-side verification).
    public function activateByOrderRef($ref)
    {
        return $this->execute(
            "UPDATE memberships SET status = 'active', starts_at = NOW(),
             expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH)
             WHERE order_ref = ? AND status = 'pending'",
            [$ref]
        );
    }
}