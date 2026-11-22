<?php
// Admin: save capacity settings, add or remove a date rule.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/catalog_controller.php';   // entertaining()
require_admin();   // also checks the CSRF token

$back = url('views/admin/admin_ent_capacity.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);
$ent = entertaining();
$act = $_POST['act'] ?? '';

if ($act === 'settings') {
    $cap = (int)($_POST['capacity'] ?? 0);
    $cut = (int)($_POST['cutoff'] ?? -1);
    if ($cap < 1 || $cap > 500 || $cut < 0 || $cut > 720) { $_SESSION['admin_msg'] = 'Capacity must be 1 to 500 and the cutoff 0 to 720 hours.'; redirect($back); }
    $ent->setSetting('ent_slot_capacity', $cap);
    $ent->setSetting('ent_change_cutoff_hours', $cut);
    $_SESSION['admin_msg'] = 'Settings saved.';
}

if ($act === 'add_rule') {
    $date   = $_POST['date'] ?? '';
    $window = $_POST['window'] ?? '';
    $block  = ($_POST['kind'] ?? 'block') === 'block';
    $max    = $_POST['max'] ?? '';
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date || $date < date('Y-m-d')) { $_SESSION['admin_msg'] = 'Choose a date from today onwards.'; redirect($back); }
    if ($window !== '' && !in_array($window, time_windows(), true)) redirect($back);
    if (!$block && (!ctype_digit((string)$max) || (int)$max > 500)) { $_SESSION['admin_msg'] = 'Enter how many orders to allow (0 to 500).'; redirect($back); }
    $ent->execute(
        'INSERT INTO aku_ent_date_rules (rule_date, time_window, max_orders, is_blocked, note) VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE max_orders = VALUES(max_orders), is_blocked = VALUES(is_blocked), note = VALUES(note)',
        [$date, $window, $block ? null : (int)$max, $block ? 1 : 0, mb_substr(trim($_POST['note'] ?? ''), 0, 120) ?: null]);
    $_SESSION['admin_msg'] = 'Rule saved for ' . date('D j M', strtotime($date)) . '. Existing orders are not affected.';
}

if ($act === 'delete_rule') {
    $ent->execute('DELETE FROM aku_ent_date_rules WHERE id = ?', [(int)($_POST['id'] ?? 0)]);
    $_SESSION['admin_msg'] = 'Rule removed.';
}
redirect($back);
