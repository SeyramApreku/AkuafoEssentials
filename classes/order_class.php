<?php
require_once __DIR__ . '/../settings/db_class.php';

class OrderClass extends Database
{
    public function create($data, $items)
    {
        $ref = 'AK' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        try {
            $this->begin();
            $this->execute('INSERT INTO aku_orders (ref,subtotal,delivery,pass_fee,total,delivery_date,time_window,pay_method,phone,email,user_id,delivery_zone,delivery_credit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$ref, $data['subtotal'], $data['delivery'], $data['pass_fee'], $data['total'], $data['date'], $data['window'], $data['method'], $data['phone'], $data['email'], $data['user_id'], $data['zone'], $data['credit']]);
            $orderId = $this->lastId();
            foreach ($items as $i) {
                $this->execute('INSERT INTO aku_order_items (order_id,item_key,name,type,price,qty,detail) VALUES (?,?,?,?,?,?,?)',
                    [$orderId, $i['id'], $i['name'], $i['type'], $i['price'], $i['qty'], $i['detail'] ?? null]);
            }
            $this->commit();
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
    public function markPaid($id) { $this->execute("UPDATE aku_orders SET payment_status = 'paid', status = IF(status = 'pending', 'paid', status) WHERE id = ?", [$id]); }
    public function setStatus($id, $status) { return $this->execute('UPDATE aku_orders SET status = ? WHERE id = ?', [$status, $id]); }
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