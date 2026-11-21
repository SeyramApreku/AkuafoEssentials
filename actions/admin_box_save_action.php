<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$back = url('views/admin/admin_boxes.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);

$id = $_POST['id'] ?? '';
$db = new Database();
$old = $id ? $db->fetchOne("SELECT * FROM boxes WHERE id = ?", [$id]) : null;
if ($id && !$old) redirect($back);

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    $_SESSION['admin_msg'] = 'A box needs a name.';
    redirect($back);
}

$data = [
    'name'    => $name,
    'tagline' => trim($_POST['tagline'] ?? ''),
    'color'   => trim($_POST['color'] ?? ''),
    'note'    => trim($_POST['note'] ?? ''),
];
if (isset($_POST['contents'])) {
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $_POST['contents']))));
    $data['contents'] = json_encode($lines, JSON_UNESCAPED_UNICODE);
}

$img = save_image($_FILES['image'] ?? [], 'boxes');
if ($img === false) {
    $_SESSION['admin_msg'] = 'Image must be JPG, PNG or WebP, up to 2 MB.';
    redirect($old ? $back . '?edit=' . urlencode($id) : $back . '?new=1');
}
if ($img !== null) {
    if ($old) delete_image($old['image']);
    $data['image'] = $img;
}

if ($old) {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $db->execute("UPDATE `boxes` SET $set WHERE id = ?", [...array_values($data), $id]);
    $_SESSION['admin_msg'] = 'Box updated.';
    redirect($back);
}

// New box: make a unique slug id, then default sizes
$slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'box';
$base = $slug; $n = 2;
while ($db->fetchOne("SELECT id FROM boxes WHERE id = ?", [$slug])) $slug = $base . '-' . $n++;

$data['id'] = $slug;
$data['contents'] = '[]';
$names = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
$marks = implode(', ', array_fill(0, count($data), '?'));
$db->execute("INSERT INTO `boxes` ($names) VALUES ($marks)", array_values($data));

$start = (int)($db->fetchOne("SELECT MIN(idx) AS m FROM box_sizes")['m'] ?? 0);
foreach ([['Small', '1-2 people'], ['Medium', '3-4 people'], ['Large', '5+ people']] as $i => [$label, $people]) {
    $db->execute("INSERT INTO box_sizes (box_id, idx, label, description, price, items) VALUES (?, ?, ?, ?, 0, '')", [$slug, $start + $i, $label, $people]);
}

$_SESSION['admin_msg'] = 'Box added. Set its sizes and prices below.';
redirect($back . '?edit=' . urlencode($slug));