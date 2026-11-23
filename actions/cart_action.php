<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('index.php'));

$act = $_POST['action'] ?? '';
$id = $_POST['id'] ?? '';
// Only go back to a page on this site (never to another website).
$return = (string)($_POST['return'] ?? '');
if (strpos($return, BASE_URL . '/') !== 0 || strpos($return, '//') !== false) $return = url('index.php');

// Entertaining orders are checked out on their own (event date, notice, capacity),
// so the cart never mixes them with groceries or weekly boxes.
function cart_add_checked($item)
{
    $kind = cart()->kind();
    if ($kind !== null && $kind !== CartClass::kindOf($item)) {
        $_SESSION['cart_notice'] = $kind === 'entertaining'
            ? 'Your cart has Entertaining items. Please check those out first, or empty your cart, before adding groceries or boxes.'
            : 'Entertaining orders are checked out separately. Please check out or empty your current cart first.';
        $_SESSION['cart_open'] = true;
        return false;
    }
    cart()->add($item);
    $_SESSION['cart_open'] = true;
    return true;
}

switch ($act) {
    case 'add':
        $item = build_cart_item($_POST['type'] ?? '', $_POST['key'] ?? '', $_POST['qty'] ?? 1, $_POST['size'] ?? 0, $_POST['frequency'] ?? '', $_POST['day'] ?? '', $_POST['window'] ?? '');
        if ($item) cart_add_checked($item);
        break;
    case 'ent_many':   // "Add all to cart" from the guest calculator: qty[itemId] => quantity
        foreach ((array)($_POST['qty'] ?? []) as $key => $q) {
            if ((int)$q < 1) continue;
            $item = build_cart_item('ent', (int)$key, min(99, (int)$q));
            if ($item && !cart_add_checked($item)) break;
        }
        break;
    case 'remove': cart()->remove($id); $_SESSION['cart_open'] = true; break;
    case 'inc':   // produce goes through the stock check; everything else just goes up by one
        if (strpos($id, 'p-') === 0) { $item = build_cart_item('produce', substr($id, 2), 1); if ($item) cart()->add($item); }
        else cart()->changeQty($id, 1);
        $_SESSION['cart_open'] = true;
        break;
    case 'dec': cart()->changeQty($id, -1); $_SESSION['cart_open'] = true; break;
    case 'clear': cart()->clear(); unset($_SESSION['cart_order_ref']); $_SESSION['cart_open'] = true; break;
    case 'pass': cart()->setPass(true); break;
    case 'custom':
        $item = build_custom_box($_POST['qty'] ?? [], $_POST['frequency'] ?? 'weekly', $_POST['day'] ?? '', $_POST['window'] ?? '');
        if ($item) cart_add_checked($item);
        break;
}

// Updated in the background by js/app.js (no page reload): send back the re-drawn drawer and the item count.
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
    unset($_SESSION['cart_open']);
    $cartObj = cart(); $here = $return; $autoOpen = false;
    ob_start();
    include __DIR__ . '/../views/layout/cart_drawer.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => cart()->count(), 'html' => $html]);
    exit;
}
redirect($return);   // without JavaScript: the normal form post + redirect still works
