<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$allowed = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && in_array($status, $allowed, true)) {
    (new Database())->execute("UPDATE orders SET status = ? WHERE id = ?", [$status, $id]);
    $_SESSION['admin_msg'] = 'Order status updated.';
}
redirect(url('views/admin/admin_orders.php?id=' . $id));