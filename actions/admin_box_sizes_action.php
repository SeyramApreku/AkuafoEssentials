<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/admin/admin_boxes.php'));

$boxId = $_POST['box_id'] ?? '';
$db = new Database();

// Every size needs a label and a price above 0 (checked before anything is saved).
$problems = [];
foreach (($_POST['sizes'] ?? []) as $sid => $s) {
    $label = trim($s['label'] ?? '') ?: 'A size';
    if (!validate_field('product_name', $s['label'] ?? '', 'Size label')[0]) $problems[] = 'Each size needs a label, e.g. "Small".';
    [$okPrice, $msg] = validate_field('money', $s['price'] ?? '', $label . ' price');
    if (!$okPrice) $problems[] = $msg;
}
if ($problems) {
    $_SESSION['admin_msg'] = implode(' ', array_unique($problems));
    $_SESSION['admin_restore'] = 1;
    redirect(url('views/admin/admin_boxes.php?edit=' . urlencode($boxId)));
}

foreach (($_POST['sizes'] ?? []) as $sid => $s) {
    $picked = array_values(array_filter(array_map('trim', (array)($s['contents'] ?? []))));
    $db->execute(
        "UPDATE aku_box_sizes SET label = ?, description = ?, price = ?, items = ?, contents = ? WHERE id = ? AND box_id = ?",
        [trim($s['label'] ?? ''), trim($s['description'] ?? ''), (float)($s['price'] ?? 0), trim($s['items'] ?? ''), json_encode($picked, JSON_UNESCAPED_UNICODE), (int)$sid, $boxId]
    );
}

$_SESSION['admin_msg'] = 'Sizes updated.';
redirect(url('views/admin/admin_boxes.php?edit=' . urlencode($boxId)));