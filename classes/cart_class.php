<?php
// Session-based cart. Item keys: p-{id} (produce), box-{id}-{sizeIdx}, hamper/bulk ids.
class CartClass
{
    public function items() { return $_SESSION['cart'] ?? []; }
    public function count() { return array_sum(array_column($this->items(), 'qty')); }
    public function subtotal() { return array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $this->items())); }
    public function hasPass() { return !empty($_SESSION['has_pass']); }
    public function deliveryCost()
    {
        if ($this->hasPass()) return 0;
        return $this->subtotal() >= MIN_ORDER ? DELIVERY_FEE : 0;
    }
    public function add($item)
    {
        $c = $this->items();
        if (isset($c[$item['id']])) $c[$item['id']]['qty'] += $item['qty'];
        else $c[$item['id']] = $item;
        $_SESSION['cart'] = $c;
    }
    public function remove($id) { unset($_SESSION['cart'][$id]); }
    public function changeQty($id, $delta)
    {
        if (!isset($_SESSION['cart'][$id])) return;
        $_SESSION['cart'][$id]['qty'] = max(1, $_SESSION['cart'][$id]['qty'] + $delta);
    }
    public function setPass($on) { $_SESSION['has_pass'] = (bool)$on; }
    public function clear() { unset($_SESSION['cart']); }
}
