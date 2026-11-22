<?php
require_once __DIR__ . '/../core/core.php';
require_admin();

$tables = admin_tables();
$t = $_POST['t'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($tables[$t])) redirect(url('views/admin/admin_manage.php'));
$back = url('views/admin/admin_manage.php?t=' . $t);

$db = new Database();
$table = tbl($t);   // real table name, e.g. products => aku_products
$cols = $db->fetchAll("SHOW COLUMNS FROM `$table`");
$id = (int)($_POST['id'] ?? 0);
$old = $id ? $db->fetchOne("SELECT * FROM `$table` WHERE id = ?", [$id]) : null;
$commaCols = $tables[$t]['commas'] ?? [];
$lineCols  = $tables[$t]['lines'] ?? [];

$data = [];
foreach ($cols as $c) {
    $f = $c['Field'];
    if (in_array($f, ['id', 'created_at', 'updated_at'], true) || strpos($c['Extra'], 'auto_increment') !== false) continue;

    if ($f === 'image') {
        $img = save_image($_FILES['image'] ?? [], $tables[$t]['dir']);
        if ($img === false) {
            $_SESSION['admin_msg'] = 'Image must be JPG, PNG or WebP, up to 2 MB.';
            redirect($back);
        }
        if ($img !== null) {
            if ($old) delete_image($old['image']);
            $data['image'] = $img;
        }
        continue;
    }

    $v = trim($_POST[$f] ?? '');
        if (in_array($f, $commaCols, true)) {
        $v = json_encode(array_values(array_filter(array_map('trim', explode(',', $v)))));
    } elseif (in_array($f, $lineCols, true)) {
        $v = json_encode(array_values(array_filter(array_map('trim', preg_split('/\R/', $v)))));
    }
    if ($v === '' && $c['Null'] === 'YES') $v = null;
    $data[$f] = $v;
}

if ($old) {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $db->execute("UPDATE `$table` SET $set WHERE id = ?", [...array_values($data), $id]);
    $_SESSION['admin_msg'] = 'Changes saved.';
} else {
    $names = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $marks = implode(', ', array_fill(0, count($data), '?'));
    $db->execute("INSERT INTO `$table` ($names) VALUES ($marks)", array_values($data));
    $_SESSION['admin_msg'] = 'Added.';
}
redirect($back);