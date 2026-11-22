<?php
require_once __DIR__ . '/../settings/db_class.php';

// Stock levels for produce (aku_products.stock_qty; NULL = not tracked) and their history (aku_stock_moves).
// Weekly boxes, hampers and entertaining dishes are made to order and are not stock-tracked.
class InventoryClass extends Database
{
    // Products with stock and units sold in the last 30 days.
    public function products($onlyLow = false)
    {
        $rows = $this->fetchAll(
            "SELECT p.id, p.name, p.category, p.price, p.unit, p.image, p.stock_qty, p.low_stock_at,
                    COALESCE((SELECT SUM(oi.qty) FROM aku_order_items oi JOIN aku_orders o ON o.id = oi.order_id
                               WHERE oi.item_key = CONCAT('p-', p.id) AND o.payment_status = 'paid' AND o.status <> 'cancelled'
                                 AND o.created_at >= NOW() - INTERVAL 30 DAY), 0) AS sold_30d
               FROM aku_products p ORDER BY (p.stock_qty IS NULL), p.stock_qty, p.name");
        return $onlyLow ? array_values(array_filter($rows, fn($r) => $r['stock_qty'] !== null && (int)$r['stock_qty'] <= (int)$r['low_stock_at'])) : $rows;
    }

    public function product($id) { return $this->fetchOne('SELECT id, name, stock_qty, low_stock_at FROM aku_products WHERE id = ?', [(int)$id]); }

    private function log($productId, $change, $reason, $note = null, $orderId = null)
    {
        if ((int)$change === 0) return;
        $this->execute('INSERT INTO aku_stock_moves (product_id, change_qty, reason, note, order_id) VALUES (?, ?, ?, ?, ?)',
            [(int)$productId, (int)$change, $reason, $note, $orderId]);
    }

    // Set the exact count (stock take). $qty = null stops tracking (always available).
    public function setStock($id, $qty, $lowAt, $note)
    {
        $p = $this->product($id);
        if (!$p) return false;
        $this->execute('UPDATE aku_products SET stock_qty = ?, low_stock_at = ? WHERE id = ?', [$qty, (int)$lowAt, (int)$id]);
        if ($qty !== null) $this->log($id, (int)$qty - (int)($p['stock_qty'] ?? 0), 'adjustment', $note ?: 'Stock count');
        return true;
    }

    // Add delivered stock (e.g. a harvest came in).
    public function restock($id, $qty, $note)
    {
        $n = $this->execute('UPDATE aku_products SET stock_qty = COALESCE(stock_qty, 0) + ? WHERE id = ?', [(int)$qty, (int)$id]);
        if ($n) $this->log($id, (int)$qty, 'restock', $note ?: null);
        return $n > 0;
    }

    // Take stock off for a paid order, exactly once (claimed with stock_deducted_at).
    public function deductForOrder($orderId)
    {
        try {
            if ($this->execute('UPDATE aku_orders SET stock_deducted_at = NOW() WHERE id = ? AND stock_deducted_at IS NULL', [(int)$orderId]) !== 1) return;
            foreach ($this->fetchAll("SELECT item_key, qty FROM aku_order_items WHERE order_id = ? AND item_key LIKE 'p-%'", [(int)$orderId]) as $i) {
                $pid = (int)substr($i['item_key'], 2);
                if ($this->execute('UPDATE aku_products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty IS NOT NULL', [(int)$i['qty'], $pid]))
                    $this->log($pid, -(int)$i['qty'], 'sale', null, (int)$orderId);
            }
        } catch (PDOException $e) {
            error_log('Stock not updated for order ' . $orderId . ' (run Database Update): ' . $e->getMessage());   // never block a payment
        }
    }

    // Put stock back when a paid order is cancelled.
    public function restoreForOrder($orderId)
    {
        try {
            if ($this->execute('UPDATE aku_orders SET stock_deducted_at = NULL WHERE id = ? AND stock_deducted_at IS NOT NULL', [(int)$orderId]) !== 1) return;
            foreach ($this->fetchAll("SELECT item_key, qty FROM aku_order_items WHERE order_id = ? AND item_key LIKE 'p-%'", [(int)$orderId]) as $i) {
                $pid = (int)substr($i['item_key'], 2);
                if ($this->execute('UPDATE aku_products SET stock_qty = stock_qty + ? WHERE id = ? AND stock_qty IS NOT NULL', [(int)$i['qty'], $pid]))
                    $this->log($pid, (int)$i['qty'], 'cancelled', null, (int)$orderId);
            }
        } catch (PDOException $e) {
            error_log('Stock not restored for order ' . $orderId . ': ' . $e->getMessage());
        }
    }

    public function moves($limit = 30)
    {
        return $this->fetchAll('SELECT m.*, p.name, o.ref FROM aku_stock_moves m JOIN aku_products p ON p.id = m.product_id
                                 LEFT JOIN aku_orders o ON o.id = m.order_id ORDER BY m.id DESC LIMIT ' . (int)$limit);
    }
}
