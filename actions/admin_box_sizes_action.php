<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/admin/admin_boxes.php'));

$boxId = $_POST['box_id'] ?? '';
$db = new Database();

foreach (($_POST['sizes'] ?? []) as $sid => $s) {
    $picked = array_values(array_filter(array_map('trim', (array)($s['contents'] ?? []))));
    $db->execute(
        "UPDATE box_sizes SET label = ?, description = ?, price = ?, items = ?, contents = ? WHERE id = ? AND box_id = ?",
        [trim($s['label'] ?? ''), trim($s['description'] ?? ''), (float)($s['price'] ?? 0), trim($s['items'] ?? ''), json_encode($picked, JSON_UNESCAPED_UNICODE), (int)$sid, $boxId]
    );
}

$_SESSION['admin_msg'] = 'Sizes updated.';
redirect(url('views/admin/admin_boxes.php?edit=' . urlencode($boxId)));