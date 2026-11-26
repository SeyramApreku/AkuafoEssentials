<?php
require_once __DIR__ . '/../settings/db_class.php';

class OrderClass extends Database
{
    public function create($data, $items)
    {
        $ref = 'AK' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        try {
            $this->begin();
            $this->execute('INSERT INTO aku_orders (ref,subtotal,delivery,pass_fee,total,delivery_date,time_window,pay_method,phone,email,user_id,delivery_zone,delivery_credit,order_type,event_address,
                                zone_id,address_id,gps_address,area_name,landmark,delivery_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$ref, $data['subtotal'], $data['delivery'], $data['pass_fee'], $data['total'], $data['date'], $data['window'], $data['method'], $data['phone'], $data['email'], $data['user_id'], $data['zone'], $data['credit'],
                 $data['order_type'] ?? 'standard', $data['event_address'] ?? null,
                 $data['zone_id'] ?? null, $data['address_id'] ?? null, $data['gps_address'] ?? null, $data['area_name'] ?? null, $data['landmark'] ?? null, $data['delivery_notes'] ?? null]);
            $orderId = $this->lastId();
            foreach ($items as $i) {
                $this->execute('INSERT INTO aku_order_items (order_id,item_key,name,type,price,qty,detail) VALUES (?,?,?,?,?,?,?)',
                    [$orderId, $i['id'], $i['name'], $i['type'], $i['price'], $i['qty'], $i['detail'] ?? null]);
            }
            $this->commit();
            $this->stampFarmers($orderId);
            return $ref;
        } catch (Throwable $ex) {
            $this->rollback();
            error_log($ex);
            return false;
        }
    }

    // A customer's orders, newest first, with a short item summary for the list.
    public function forCustomer($customerId)
    {
        return $this->fetchAll(
            "SELECT o.*, (SELECT COALESCE(SUM(qty), 0) FROM aku_order_items WHERE order_id = o.id) AS item_count,
                    (SELECT GROUP_CONCAT(name ORDER BY id SEPARATOR ', ') FROM aku_order_items WHERE order_id = o.id) AS item_names
               FROM aku_orders o WHERE o.user_id = ? ORDER BY o.id DESC",
            [$customerId]
        );
    }

    // Unpaid orders from the last 7 days that went to Paystack: worth asking Paystack about.
    public function unconfirmedFor($customerId, $limit = 5)
    {
        return $this->fetchAll(
            "SELECT * FROM aku_orders WHERE user_id = ? AND payment_status <> 'paid' AND paystack_reference IS NOT NULL
               AND created_at >= NOW() - INTERVAL 7 DAY ORDER BY id DESC LIMIT " . (int)$limit,
            [$customerId]
        );
    }

    public function byRef($ref) { return $this->fetchOne('SELECT * FROM aku_orders WHERE ref = ?', [$ref]); }
    public function items($orderId) { return $this->fetchAll('SELECT * FROM aku_order_items WHERE order_id = ?', [$orderId]); }
    public function byPaystackRef($p) { return $this->fetchOne('SELECT * FROM aku_orders WHERE paystack_reference = ?', [$p]); }
    public function setPaystackRef($ref, $p) { $this->execute('UPDATE aku_orders SET paystack_reference = ? WHERE ref = ?', [$p, $ref]); }
    // Payment confirmed: grocery orders become 'paid', entertaining orders become 'received' (their own status flow).
    public function markPaid($id)
    {
        $this->markPaidOnly($id);
        $this->stampFarmers($id);   // payment in: each Farm Shop item now knows its farmer and commission (farmer's share vs ours)
        require_once __DIR__ . '/inventory_class.php';
        (new InventoryClass())->deductForOrder($id);   // stock comes off once the money is in
        require_once __DIR__ . '/subscription_class.php';
        (new SubscriptionClass())->createFromOrder($id);   // boxes in the order become subscriptions
    }
    private function markPaidOnly($id) { $this->execute("UPDATE aku_orders SET payment_status = 'paid', status = IF(status = 'pending', IF(order_type = 'entertaining', 'received', 'paid'), status) WHERE id = ?", [$id]); }
    // Entertaining orders: customer changes and cancellations.
    public function updateEvent($id, $date, $window, $address)
    {
        return $this->execute('UPDATE aku_orders SET delivery_date = ?, time_window = ?, event_address = ? WHERE id = ?', [$date, $window, $address, $id]);
    }
    public function cancel($id, $refundStatus)
    {
        return $this->execute("UPDATE aku_orders SET status = 'cancelled', cancelled_at = NOW(), refund_status = ? WHERE id = ? AND status <> 'cancelled'", [$refundStatus, $id]);
    }
    public function setStatus($id, $status)
    {
        $n = $this->execute('UPDATE aku_orders SET status = ? WHERE id = ?', [$status, $id]);
        if ($status === 'delivered') {   // farmer payouts count an order in the week it was delivered
            try { $this->execute('UPDATE aku_orders SET delivered_at = NOW() WHERE id = ? AND delivered_at IS NULL', [$id]); } catch (Throwable $e) { error_log($e->getMessage()); }
        }
        return $n;
    }
    // Farm Shop lines: save which farmer grew it and their commission rate today (a later rate change doesn't rewrite old sales).
    public function stampFarmers($orderId)
    {
        try {
            $this->execute("UPDATE aku_order_items i
                              JOIN aku_products p ON i.item_key = CONCAT('p-', p.id)
                              JOIN aku_farmers f ON f.id = COALESCE(p.farmer_id, (SELECT f2.id FROM aku_farmers f2 WHERE LOWER(TRIM(f2.name)) = LOWER(TRIM(p.farmer)) LIMIT 1))
                               SET i.farmer_id = f.id, i.source_channel = 'shop', i.commission_rate = f.commission_rate
                             WHERE i.order_id = ? AND i.type = 'produce' AND i.farmer_id IS NULL", [$orderId]);
        } catch (Throwable $e) { error_log('Farmer stamp: ' . $e->getMessage()); }   // before Database Update: the order still goes through
    }
    public function markFailed($id) { $this->execute("UPDATE aku_orders SET payment_status = 'failed' WHERE id = ? AND payment_status <> 'paid'", [$id]); }
    // Atomically "claims" the invoice email so two callbacks can never send it twice.
    // Returns true only for the one request that should send it.
    public function claimInvoice($id) { return $this->execute('UPDATE aku_orders SET invoice_sent_at = NOW() WHERE id = ? AND invoice_sent_at IS NULL', [$id]) === 1; }
    public function releaseInvoice($id) { $this->execute('UPDATE aku_orders SET invoice_sent_at = NULL WHERE id = ?', [$id]); }   // sending failed: allow a retry
    public function customerName($userId) { return $this->customer($userId)['customer_name'] ?? ''; }

    // The account an order belongs to. Its customer_email (the signup email) is where ALL
    // customer communication goes, never an address typed or autofilled at checkout.
    public function customer($userId)
    {
        return $this->fetchOne('SELECT customer_id, customer_name, customer_email FROM aku_customer WHERE customer_id = ?', [(int)$userId]);
    }
}