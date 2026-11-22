<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/order_class.php';
require_admin();   // also checks the CSRF token

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$back   = url('views/admin/admin_orders.php?id=' . $id);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id || !in_array($status, order_statuses(), true)) redirect($back);

$orders = new OrderClass();
$order  = $orders->fetchOne('SELECT id, payment_status FROM aku_orders WHERE id = ?', [$id]);
if (!$order) redirect(url('views/admin/admin_orders.php'));

// An order can only be paid, shipped or delivered once Paystack has confirmed the payment.
if (in_array($status, ['paid', 'shipped', 'delivered'], true) && $order['payment_status'] !== 'paid') {
    $_SESSION['admin_msg'] = 'This order has not been paid yet, so it can only be Pending or Cancelled.';
    redirect($back);
}

$orders->setStatus($id, $status);
$_SESSION['admin_msg'] = 'Order status updated to ' . ucfirst($status) . '.';
redirect($back);
