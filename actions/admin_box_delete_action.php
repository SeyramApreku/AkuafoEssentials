<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$back = url('views/admin/admin_boxes.php');
$id = $_POST['id'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id === '') redirect($back);

$db = new Database();
$box = $db->fetchOne("SELECT * FROM boxes WHERE id = ?", [$id]);
if ($box) {
    delete_image($box['image']);
    $db->execute("DELETE FROM box_sizes WHERE box_id = ?", [$id]);
    $db->execute("DELETE FROM boxes WHERE id = ?", [$id]);
    $_SESSION['admin_msg'] = 'Box deleted.';
}
redirect($back);