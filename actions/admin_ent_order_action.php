<?php
// Admin: update the status of an entertaining order (and email the customer for the key steps).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';
require_admin();   // also checks the CSRF token

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$back   = url('views/admin/admin_ent_orders.php?id=' . $id);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id || !in_array($status, ent_order_statuses(), true)) redirect($back);

$orders = new OrderClass();
$order  = $orders->fetchOne("SELECT * FROM aku_orders WHERE id = ? AND order_type = 'entertaining'", [$id]);
if (!$order) redirect(url('views/admin/admin_ent_orders.php'));
if ($order['status'] === $status) redirect($back);

if (!in_array($status, ['pending', 'cancelled'], true) && $order['payment_status'] !== 'paid') {
    $_SESSION['admin_msg'] = 'This order has not been paid yet, so it can only be Pending or Cancelled.';
    redirect($back);
}

$orders->setStatus($id, $status);
if ($status === 'cancelled') $orders->execute('UPDATE aku_orders SET cancelled_at = NOW() WHERE id = ?', [$id]);
$emailed = send_status_email($order, $status);
$_SESSION['admin_msg'] = 'Status updated to ' . ucwords(str_replace('_', ' ', $status)) . '.' . ($emailed ? ' The customer has been emailed.' : '');
redirect($back);
