<?php
require_once __DIR__ . '/../settings/db_class.php';

class OrderClass extends Database
{
    public function create($data, $items)
    {
        $ref = 'AK' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        try {
            $this->begin();
            $this->execute('INSERT INTO orders (ref,subtotal,delivery,pass_fee,total,delivery_date,time_window,pay_method,phone) VALUES (?,?,?,?,?,?,?,?,?)',
                [$ref, $data['subtotal'], $data['delivery'], $data['pass_fee'], $data['total'], $data['date'], $data['window'], $data['method'], $data['phone']]);
            $orderId = $this->lastId();
            foreach ($items as $i) {
                $this->execute('INSERT INTO order_items (order_id,item_key,name,type,price,qty,detail) VALUES (?,?,?,?,?,?,?)',
    [$orderId, $i['id'], $i['name'], $i['type'], $i['price'], $i['qty'], $i['detail'] ?? null]);
            }
            $this->commit();
            return $ref;
        } catch (Throwable $ex) {
            $this->rollback();
            return false;
        }
    }
    public function byRef($ref) { return $this->fetchOne('SELECT * FROM orders WHERE ref = ?', [$ref]); }
}
