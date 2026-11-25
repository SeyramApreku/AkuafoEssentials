<?php
require_once __DIR__ . '/../settings/db_class.php';

// Weekly box subscriptions (table from db/box_subscriptions.sql; quietly off until it exists).
// A box is paid upfront for 4, 8 or 12 weeks. When the order is paid, each box in it becomes a
// subscription; 3 days before its last delivery the customer gets a reminder to renew.
class SubscriptionClass extends Database
{
    // Called when an order is confirmed paid. Safe to call twice (one row per order + box).
    public function createFromOrder($orderId)
    {
        try {
            $o = $this->fetchOne('SELECT id, user_id, delivery_date FROM aku_orders WHERE id = ?', [(int)$orderId]);
            if (!$o) return;
            foreach ($this->fetchAll("SELECT * FROM aku_order_items WHERE order_id = ? AND type = 'box'", [(int)$orderId]) as $i) {
                [$freq, $weeks] = box_plan_from_key($i['item_key']);
                $count  = box_delivery_count($freq, $weeks);
                $start  = $o['delivery_date'] && strtotime($o['delivery_date']) ? $o['delivery_date'] : date('Y-m-d');
                // First box on the weekday they chose ("…, Fridays, …"), on or after the delivery date.
                if (preg_match('/\b(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)s\b/', (string)$i['detail'], $d) && date('l', strtotime($start)) !== $d[1])
                    $start = date('Y-m-d', strtotime('next ' . $d[1], strtotime($start)));
                $end    = date('Y-m-d', strtotime($start . ' +' . (($count - 1) * box_step_weeks($freq) * 7) . ' days'));   // last delivery
                $this->execute('INSERT IGNORE INTO aku_box_subscriptions (user_id, order_id, item_key, name, detail, frequency, weeks, deliveries, price_paid, starts_on, ends_on)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                               [$o['user_id'], $o['id'], $i['item_key'], $i['name'], $i['detail'] ?? null, $freq, $weeks, $count, (float)$i['price'] * (int)$i['qty'], $start, $end]);
                // This order renews an older box with the same key: link them so the old one stops reminding.
                $this->execute('UPDATE aku_box_subscriptions SET renewed_by_order_id = ? WHERE user_id = ? AND item_key = ? AND order_id <> ? AND renewed_by_order_id IS NULL',
                               [$o['id'], $o['user_id'], $i['item_key'], $o['id']]);
            }
        } catch (PDOException $e) { error_log('Box subscription: ' . $e->getMessage()); }
    }

    // A customer's boxes, newest first, with days left.
    public function forCustomer($userId)
    {
        try {
            return $this->fetchAll('SELECT s.*, DATEDIFF(s.ends_on, CURDATE()) AS days_left, o.ref FROM aku_box_subscriptions s
                                    JOIN aku_orders o ON o.id = s.order_id WHERE s.user_id = ? ORDER BY s.ends_on DESC, s.id DESC', [(int)$userId]);
        } catch (PDOException $e) { return []; }
    }

    public function one($id, $userId)
    {
        try { return $this->fetchOne('SELECT * FROM aku_box_subscriptions WHERE id = ? AND user_id = ?', [(int)$id, (int)$userId]); }
        catch (PDOException $e) { return null; }
    }

    // Boxes ending within 3 days (or just ended) that haven't been reminded or renewed yet.
    public function dueReminders($limit = 10)
    {
        try {
            return $this->fetchAll('SELECT s.*, c.customer_email, c.customer_name FROM aku_box_subscriptions s
                                    JOIN aku_customer c ON c.customer_id = s.user_id
                                    WHERE s.reminder_sent_at IS NULL AND s.renewed_by_order_id IS NULL
                                      AND s.ends_on <= CURDATE() + INTERVAL ? DAY AND s.ends_on >= CURDATE() - INTERVAL 7 DAY
                                    ORDER BY s.ends_on LIMIT ' . (int)$limit, [BOX_REMIND_DAYS]);
        } catch (PDOException $e) { return []; }
    }
    // Takes the reminder for one box (true only for the request that got it, so it's never sent twice).
    public function claimReminder($id) { return $this->execute('UPDATE aku_box_subscriptions SET reminder_sent_at = NOW() WHERE id = ? AND reminder_sent_at IS NULL', [(int)$id]) === 1; }
    public function releaseReminder($id) { $this->execute('UPDATE aku_box_subscriptions SET reminder_sent_at = NULL WHERE id = ?', [(int)$id]); }
}
