<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$tables = admin_tables();
$t = $_POST['t'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($tables[$t]) || !$id) redirect(url('views/admin/manage.php'));

$db = new Database();
$row = $db->fetchOne("SELECT * FROM `$t` WHERE id = ?", [$id]);
if ($row) {
    delete_image($row['image'] ?? '');
    $db->execute("DELETE FROM `$t` WHERE id = ?", [$id]);
    $_SESSION['admin_msg'] = 'Deleted.';
}
redirect(url('views/admin/manage.php?t=' . $t));