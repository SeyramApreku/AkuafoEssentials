<?php
// Saves which exclusive recipes each membership tier gets this month (stored in aku_settings).
require_once __DIR__ . '/../core/core.php';
require_admin();

$back = url('views/admin/admin_member_recipes.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect($back);

[$memberAll] = member_recipe_access();
$posted = (array)($_POST['picks'] ?? []);
$db = new Database();
$problems = [];
foreach (membership_tiers() as $t) {
    $key = strtolower($t['name']);
    // Only real members-only recipes, no repeats, no more than the plan allows.
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($posted[$key] ?? [])), fn($id) => in_array($id, $memberAll, true))));
    $ids = array_slice($ids, 0, (int)$t['recipes']);
    if (count($ids) < (int)$t['recipes']) $problems[] = $t['name'] . ' has ' . count($ids) . ' of ' . (int)$t['recipes'];
    $db->execute('INSERT INTO aku_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', ['member_recipes_' . $key, json_encode($ids)]);
}
$db->execute('INSERT INTO aku_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', ['member_recipes_updated', date('Y-m-d H:i:s')]);
$_SESSION['admin_msg'] = "This month's member recipes are saved." . ($problems ? ' Still to choose: ' . implode(', ', $problems) . ' (the same recipe can only be picked once per plan).' : '');
redirect($back);
