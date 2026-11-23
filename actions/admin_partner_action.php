<?php
// Admin: save or delete a partner (portrait in uploads/partners/, logo in images/partners/).
require_once __DIR__ . '/../core/core.php';
require_admin();   // also checks the CSRF token

$list = url('views/admin/admin_partners.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($list);
$db  = new Database();
$id  = (int)($_POST['id'] ?? 0);
$old = $id ? $db->fetchOne('SELECT * FROM aku_partners WHERE id = ?', [$id]) : null;
if ($id && !$old) redirect($list);

if (($_POST['act'] ?? '') === 'delete' && $old) {
    $db->execute('DELETE FROM aku_partners WHERE id = ?', [$id]);
    delete_image($old['image']);
    delete_image($old['photo'] ?? '');
    $_SESSION['admin_msg'] = 'Partner deleted.';
    redirect($list);
}
if (($_POST['act'] ?? '') !== 'save') redirect($list);

$back = $list . ($id ? '?edit=' . $id : '?new=1');
$name = trim($_POST['name'] ?? '');
if ($name === '' || mb_strlen($name) > 100) { $_SESSION['admin_msg'] = 'Enter a name (up to 100 characters).'; redirect($back); }

$data = [
    'name'            => $name,
    'type'            => mb_substr(trim($_POST['type'] ?? 'Restaurant'), 0, 50) ?: 'Restaurant',
    'role'            => mb_substr(trim($_POST['role'] ?? ''), 0, 80) ?: null,
    'restaurant_name' => mb_substr(trim($_POST['restaurant_name'] ?? ''), 0, 120) ?: null,
    'quote'           => mb_substr(trim($_POST['quote'] ?? ''), 0, 600) ?: null,
    'sort_order'      => max(0, min(999, (int)($_POST['sort_order'] ?? 0))),
];

// Portrait (uploads/partners/) and logo (images/partners/): JPG/PNG/WebP up to 2 MB, checked by save_image().
$photo = save_image($_FILES['photo'] ?? [], 'partners', 'uploads');
$logo  = save_image($_FILES['image'] ?? [], 'partners');
if ($photo === false || $logo === false) { $_SESSION['admin_msg'] = 'Pictures must be JPG, PNG or WebP, up to 2 MB.'; redirect($back); }
if ($photo) $data['photo'] = $photo;
elseif (!empty($_POST['remove_photo'])) $data['photo'] = null;
if ($logo) $data['image'] = $logo;

if ($old) {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $db->execute("UPDATE aku_partners SET $set WHERE id = ?", [...array_values($data), $id]);
    if (array_key_exists('photo', $data)) delete_image($old['photo'] ?? '');   // replaced or removed
    if ($logo) delete_image($old['image']);
    $_SESSION['admin_msg'] = 'Partner saved.';
} else {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $db->execute("INSERT INTO aku_partners ($cols) VALUES (" . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
    $_SESSION['admin_msg'] = 'Partner added.';
}
redirect($list);
