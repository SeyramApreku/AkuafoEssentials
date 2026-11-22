<?php
// Admin: restock a product, or set its exact stock count and low-stock warning level.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/inventory_class.php';
require_admin();   // also checks the CSRF token

$back = url('views/admin/admin_inventory.php' . (($_POST['back'] ?? '') === 'low' ? '?low=1' : ''));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
$inv = new InventoryClass();
$id  = (int)($_POST['id'] ?? 0);
$p   = $inv->product($id);
if (!$p) { $_SESSION['admin_msg'] = 'Product not found.'; redirect($back); }

if (($_POST['act'] ?? '') === 'restock') {
    $qty = (int)($_POST['qty'] ?? 0);
    if ($qty < 1 || $qty > 10000) { $_SESSION['admin_msg'] = 'Enter how many arrived (1 to 10,000).'; redirect($back); }
    $inv->restock($id, $qty, 'Restocked by admin');
    $_SESSION['admin_msg'] = 'Added ' . $qty . ' to ' . $p['name'] . '.';
}

if (($_POST['act'] ?? '') === 'set') {
    $raw = trim((string)($_POST['qty'] ?? ''));
    $low = (int)($_POST['low_at'] ?? 5);
    if (($raw !== '' && (!ctype_digit($raw) || (int)$raw > 100000)) || $low < 0 || $low > 10000) {
        $_SESSION['admin_msg'] = 'Enter a stock count of 0 or more (or leave it empty for no limit).';
        redirect($back);
    }
    $inv->setStock($id, $raw === '' ? null : (int)$raw, $low, 'Stock count by admin');
    $_SESSION['admin_msg'] = $raw === '' ? $p['name'] . ' is no longer stock-tracked.' : $p['name'] . ' set to ' . (int)$raw . ' in stock.';
}
redirect($back);
