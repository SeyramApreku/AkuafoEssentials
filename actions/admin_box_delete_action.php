<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$back = url('views/admin/admin_boxes.php');
$id = $_POST['id'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id === '') redirect($back);

$db = new Database();
$box = $db->fetchOne("SELECT * FROM aku_boxes WHERE id = ?", [$id]);
if ($box) {
    delete_image($box['image']);
    $db->execute("DELETE FROM aku_box_sizes WHERE box_id = ?", [$id]);
    $db->execute("DELETE FROM aku_boxes WHERE id = ?", [$id]);
    $_SESSION['admin_msg'] = 'Box deleted.';
}
redirect($back);