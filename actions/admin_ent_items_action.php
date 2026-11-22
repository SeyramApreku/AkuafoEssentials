<?php
// Save / delete / show-hide an entertaining item (views/admin/admin_ent_items.php).
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/catalog_controller.php';
require_admin();   // also checks the CSRF token

$list = url('views/admin/admin_ent_items.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($list);

$ent = entertaining();
$act = $_POST['act'] ?? '';
$id  = (int)($_POST['id'] ?? 0);
$old = $id ? $ent->item($id) : null;
if ($id && !$old) { $_SESSION['admin_msg'] = 'Item not found.'; redirect($list); }

if ($act === 'toggle' && $old) {
    $ent->execute('UPDATE aku_ent_items SET is_available = 1 - is_available WHERE id = ?', [$id]);
    $_SESSION['admin_msg'] = $old['is_available'] ? 'Hidden from the website.' : 'Now showing on the website.';
    redirect($list);
}

if ($act === 'delete' && $old) {
    $ent->delete($id);
    delete_image($old['image']);
    $_SESSION['admin_msg'] = 'Item deleted.';
    redirect($list);
}

if ($act !== 'save') redirect($list);

// ---------- Validate ----------
$back   = $list . ($id ? '?edit=' . $id : '?new=1');
$name   = trim($_POST['name'] ?? '');
$catId  = (int)($_POST['category_id'] ?? 0);
$price  = $_POST['price'] ?? '';
$serves = trim($_POST['serves'] ?? '');
$notice = $_POST['notice_hours'] ?? '';
$catIds = array_map('intval', array_column($ent->categories(), 'id'));
$occIds = array_map('intval', array_column($ent->occasions(), 'id'));

$errors = [];
if ($name === '' || mb_strlen($name) > 120)                       $errors[] = 'Enter a name (up to 120 characters).';
if (!in_array($catId, $catIds, true))                             $errors[] = 'Choose a category.';
if (!is_numeric($price) || $price < 0)                            $errors[] = 'Enter a valid price.';
if ($serves !== '' && (!ctype_digit($serves) || (int)$serves < 1)) $errors[] = 'Serves must be a whole number of people (or empty).';
if (!ctype_digit((string)$notice) || (int)$notice > 720)          $errors[] = 'Notice must be between 0 and 720 hours.';
if ($errors) { $_SESSION['admin_msg'] = implode(' ', $errors); redirect($back); }

$dietary  = array_values(array_intersect(array_keys(ent_dietary_tags()), (array)($_POST['dietary'] ?? [])));
$occasions = array_values(array_intersect($occIds, array_map('intval', (array)($_POST['occasions'] ?? []))));
$contents = array_values(array_filter(array_map('trim', preg_split('/\R/', $_POST['contents'] ?? ''))));

$data = [
    'name'         => $name,
    'category_id'  => $catId,
    'description'  => trim($_POST['description'] ?? '') ?: null,
    'contents'     => $contents ? json_encode($contents, JSON_UNESCAPED_UNICODE) : null,
    'price'        => round((float)$price, 2),
    'serves'       => $serves === '' ? null : (int)$serves,
    'notice_hours' => (int)$notice,
    'dietary'      => implode(',', $dietary),
    'badge'        => mb_substr(trim($_POST['badge'] ?? ''), 0, 40) ?: null,
    'featured'     => empty($_POST['featured']) ? 0 : 1,
    'is_available' => empty($_POST['is_available']) ? 0 : 1,
];

$img = save_image($_FILES['image'] ?? [], 'entertaining');
if ($img === false) { $_SESSION['admin_msg'] = 'Image must be JPG, PNG or WebP, up to 2 MB.'; redirect($back); }
if ($img !== null) $data['image'] = $img;
elseif (!$old)     $data['image'] = 'images/entertaining/placeholder.svg';

$newId = $ent->save($id, $data, $occasions);
if ($img !== null && $old) delete_image($old['image']);   // old upload removed only after the save worked (placeholder is protected)

$_SESSION['admin_msg'] = $old ? 'Changes saved.' : 'Item added.';
redirect($list . '?edit=' . $newId);
