<?php
// Deletes one row from a table managed by views/admin/admin_manage.php.
require_once __DIR__ . '/../core/core.php';
require_admin();   // also checks the CSRF token

$tables = admin_tables();
$t = $_POST['t'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($tables[$t]) || !$id) redirect(url('views/admin/admin_manage.php'));

$db = new Database();
$table = tbl($t);   // real table name, e.g. products => aku_products
$row = $db->fetchOne("SELECT * FROM `$table` WHERE id = ?", [$id]);
if ($row) {
    try {
        $db->execute("DELETE FROM `$table` WHERE id = ?", [$id]);
        delete_image($row['image'] ?? '');   // only once the row is really gone
        $_SESSION['admin_msg'] = 'Deleted.';
    } catch (PDOException $e) {
        // e.g. an event category that entertaining items still use
        $_SESSION['admin_msg'] = 'This is still in use, so it cannot be deleted. Move or delete the items that use it first.';
    }
}
redirect(url('views/admin/admin_manage.php?t=' . $t));
