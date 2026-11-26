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
    if ($f === 'is_active') { if ($t === 'products') $data['is_active'] = 1; continue; }   // a completed product goes live
    if ($f === 'farmer_id') {   // products: linked to the farmer picked by name
        $fid = $db->fetchOne('SELECT id FROM aku_farmers WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))', [$_POST['farmer'] ?? '']);
        $data['farmer_id'] = $fid ? (int)$fid['id'] : null;
        continue;
    }

    if ($f === 'image') {
        $img = save_image($_FILES['image'] ?? [], $tables[$t]['dir']);
        if ($img === false) {
            $_SESSION['admin_msg'] = save_image_error(); $_SESSION['admin_restore'] = 1;
            redirect($back);
        }
        if ($img !== null) {
            if ($old) delete_image($old['image']);
            $data['image'] = $img;
        }
        continue;
    }

    $v = trim($_POST[$f] ?? '');
    // Farmers: an empty commission rate, or a plan change, uses the plan's rate.
    if ($t === 'farmers' && $f === 'commission_rate') {
        $plans = farmer_plans();
        $plan = $_POST['plan'] ?? ($old['plan'] ?? 'basic');
        $planChanged = $old && ($old['plan'] ?? '') !== $plan;
        $typedSame = $old && $v !== '' && (float)$v === (float)$old['commission_rate'];
        if (isset($plans[$plan]) && ($v === '' || ($planChanged && $typedSame))) $v = (string)$plans[$plan]['commission_rate'];
    }
        if (in_array($f, $commaCols, true)) {
        $v = json_encode(array_values(array_filter(array_map('trim', explode(',', $v)))));
    } elseif (in_array($f, $lineCols, true)) {
        $v = json_encode(array_values(array_filter(array_map('trim', preg_split('/\R/', $v)))));
    }
    if ($v === '' && $c['Null'] === 'NO' && $c['Default'] !== null) { if (!$old) continue; $v = $old[$f]; }   // left empty: keep the default / current value
    if ($v === '' && $c['Null'] === 'YES') $v = null;
    $data[$f] = $v;
}

require_once __DIR__ . '/../classes/catalog_class.php';
$cat = new CatalogClass();
if ($t === 'farmers' && $old && trim((string)$old['name']) !== trim((string)$data['name'])) $cat->renameFarmer($old['name'], $data['name']);
$movedProducts = 0;
if ($t === 'categories' && $old) $movedProducts = $cat->renameCategory($old['name'], $data['name']);
if ($t === 'regions' && $old) $movedProducts = $cat->renameRegion($old['name'], $data['name']);

if ($old) {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $db->execute("UPDATE `$table` SET $set WHERE id = ?", [...array_values($data), $id]);
    $_SESSION['admin_msg'] = 'Changes saved.' . ($movedProducts ? " $movedProducts product" . ($movedProducts === 1 ? '' : 's') . ' moved to the new name.' : '');
} else {
    $names = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $marks = implode(', ', array_fill(0, count($data), '?'));
    $db->execute("INSERT INTO `$table` ($names) VALUES ($marks)", array_values($data));
    $_SESSION['admin_msg'] = 'Added.';
}
// Farmers decide the product list: their produce becomes (draft) products, and a product's farmer lists it.
if ($t === 'farmers') {
    try {
        $made = $cat->syncFarmerProduce($data['name'], $data['region'] ?? '', json_list($data['produce'] ?? null));
        if ($made) $_SESSION['admin_msg'] .= ' New draft products (hidden from the shop until you add a price, unit and category in Products): ' . implode(', ', $made) . '.';
        elseif (!$cat->hasDraftColumn()) $_SESSION['admin_msg'] .= " Run \"Farmers' produce creates draft products\" in Database Update so produce becomes products.";
    } catch (PDOException $e) {
        error_log('Farmer produce sync: ' . $e->getMessage());
        $_SESSION['admin_msg'] .= ' The produce could not be turned into products yet. Run Database Update, then save this farmer again.';
    }
} elseif ($t === 'products' && trim((string)($data['farmer'] ?? '')) !== '') {
    if ($cat->addToFarmerProduce($data['farmer'], $data['name'])) $_SESSION['admin_msg'] .= ' Added to ' . $data['farmer'] . "'s produce.";
}
redirect($back);