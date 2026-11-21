<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('index.php'));

$act = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';
$return = $_POST['return'] ?? url('index.php');

switch ($act) {
        case 'add':
        $item = build_cart_item($_POST['type'] ?? '', $_POST['key'] ?? '', $_POST['qty'] ?? 1, $_POST['size'] ?? 0, $_POST['frequency'] ?? '', $_POST['day'] ?? '', $_POST['window'] ?? '');
        if ($item) { cart()->add($item); $_SESSION['cart_open'] = true; }
        break;
    case 'remove': cart()->remove($id); $_SESSION['cart_open'] = true; break;
    case 'inc': cart()->changeQty($id, 1); $_SESSION['cart_open'] = true; break;
    case 'dec': cart()->changeQty($id, -1); $_SESSION['cart_open'] = true; break;
    case 'pass': cart()->setPass(true); break;
    case 'custom':
    $item = build_custom_box($_POST['qty'] ?? [], $_POST['frequency'] ?? 'weekly', $_POST['day'] ?? '', $_POST['window'] ?? '');
    if ($item) { cart()->add($item); $_SESSION['cart_open'] = true; }
    break;
}
redirect($return);
