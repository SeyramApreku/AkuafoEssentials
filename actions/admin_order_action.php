<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';   // OrderClass + status emails
require_admin();   // also checks the CSRF token

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$back   = url('views/admin/admin_orders.php?id=' . $id);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id || !in_array($status, order_statuses(), true)) redirect($back);

$orders = new OrderClass();
$order  = $orders->fetchOne('SELECT * FROM aku_orders WHERE id = ?', [$id]);
if (!$order) redirect(url('views/admin/admin_orders.php'));
if (($order['order_type'] ?? '') === 'entertaining') redirect(url('views/admin/admin_ent_orders.php?id=' . $id));   // own status flow
if ($order['status'] === $status) redirect($back);

// An order can only be paid, shipped or delivered once Paystack has confirmed the payment.
if (in_array($status, ['paid', 'shipped', 'delivered'], true) && $order['payment_status'] !== 'paid') {
    $_SESSION['admin_msg'] = 'This order has not been paid yet, so it can only be Pending or Cancelled.';
    redirect($back);
}

$orders->setStatus($id, $status);
if ($status === 'cancelled') { require_once __DIR__ . '/../classes/inventory_class.php'; (new InventoryClass())->restoreForOrder($id); }   // stock goes back on the shelf
$emailed = send_status_email($order, $status);   // shipped / delivered / cancelled
$_SESSION['admin_msg'] = 'Order status updated to ' . ucfirst($status) . '.' . ($emailed ? ' The customer has been emailed.' : '');
redirect($back);
