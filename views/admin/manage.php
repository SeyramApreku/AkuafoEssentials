<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();

$tables = admin_tables();
$t = $_GET['t'] ?? 'products';
if (!isset($tables[$t])) $t = 'products';
$db = new Database();
$cols = $db->fetchAll("SHOW COLUMNS FROM `$t`");
$rows = $db->fetchAll("SELECT * FROM `$t` ORDER BY id");
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? $db->fetchOne("SELECT * FROM `$t` WHERE id = ?", [$editId]) : null;
$commaCols = $tables[$t]['commas'] ?? [];
$lineCols  = $tables[$t]['lines'] ?? [];
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);

$lookups = [
  'category' => 'categories', 'category_id' => 'categories',
  'region'   => 'regions',    'region_id'   => 'regions',
  'farmer'   => 'farmers',    'farmer_id'   => 'farmers',
];
$opts = [];
foreach ($lookups as $field => $lt) {
    if ($lt === $t) continue;
    if (!$db->fetchAll("SHOW TABLES LIKE '$lt'")) continue;
    $opts[$field] = $db->fetchAll("SELECT id, name FROM `$lt` ORDER BY name");
}


$pageTitle = 'Admin | Akuafo & Essentials';
include __DIR__ . '/../layout/admin_header.php';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Manage <?= e(strtolower($tables[$t]['label'])) ?></h1>



  <?php if ($msg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted)"><?= e($msg) ?></p><?php endif; ?>

  <form method="post" action="<?= url('actions/admin_save_action.php') ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2"><?= $edit ? 'Edit item' : 'Add new' ?></h2>
    <input type="hidden" name="t" value="<?= e($t) ?>">
    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
    <?php foreach ($cols as $c):
      $f = $c['Field'];
      if (in_array($f, ['id', 'created_at', 'updated_at'], true) || strpos($c['Extra'], 'auto_increment') !== false) continue;
      $label = ucwords(str_replace('_', ' ', $f));
      $type = strtolower($c['Type']);
      $val = $edit[$f] ?? '';
      if ($edit && in_array($f, $commaCols, true)) $val = implode(', ', json_list($val));
if ($edit && in_array($f, $lineCols, true))  $val = implode("\n", json_list($val));
      $req = $c['Null'] === 'NO' && $c['Default'] === null && $f !== 'image' ? 'required' : '';
      $box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
    ?>
      <div class="<?= strpos($type, 'text') !== false ? 'md:col-span-2' : '' ?>">
        <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)"><?= e($label) ?><?= in_array($f, $commaCols, true) ? ' (comma separated)' : (in_array($f, $lineCols, true) ? ' (one per line)' : '') ?></label>

                <?php if ($f === 'image'): ?>
          <?php if ($edit && !empty($edit['image'])): ?><img src="<?= img($edit['image'], 160, 160) ?>" alt="" class="w-20 h-20 object-cover mb-2"><?php endif; ?>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
          <p class="text-xs mt-1" style="color:var(--muted-foreground)">JPG, PNG or WebP, up to 2 MB.<?= $edit ? ' Leave empty to keep the current image.' : '' ?></p>
        <?php elseif (isset($opts[$f])):
          $byId = substr($f, -3) === '_id';
          $known = false; ?>
          <select name="<?= e($f) ?>" <?= $req ?> class="<?= $box ?>" style="border-color:var(--border)">
            <option value="">Select <?= e(strtolower($label)) ?></option>
            <?php foreach ($opts[$f] as $o):
              $ov = $byId ? $o['id'] : $o['name'];
              $sel = (string)$val === (string)$ov;
              if ($sel) $known = true; ?>
              <option value="<?= e($ov) ?>" <?= $sel ? 'selected' : '' ?>><?= e($o['name']) ?></option>
            <?php endforeach; ?>
            <?php if ($val !== '' && !$known): ?><option value="<?= e($val) ?>" selected><?= e($val) ?></option><?php endif; ?>
          </select>
          <a href="?t=<?= e($lookups[$f]) ?>" class="text-xs mt-1 inline-block" style="color:var(--accent)">+ Add new <?= e(strtolower($label)) ?></a>
        <?php elseif (strpos($type, 'text') !== false): ?>



          <textarea name="<?= e($f) ?>" rows="4" <?= $req ?> class="<?= $box ?>" style="border-color:var(--border)"><?= e($val) ?></textarea>
        <?php elseif (preg_match('/int|decimal|float|double/', $type)): ?>
          <input type="number" step="any" name="<?= e($f) ?>" value="<?= e($val) ?>" <?= $req ?> class="<?= $box ?>" style="border-color:var(--border)">
        <?php else: ?>
          <input type="text" name="<?= e($f) ?>" value="<?= e($val) ?>" <?= $req ?> class="<?= $box ?>" style="border-color:var(--border)">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $edit ? 'Save changes' : 'Add' ?></button>
      <?php if ($edit): ?><a href="?t=<?= e($t) ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a><?php endif; ?>
    </div>
  </form>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($rows as $r): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="w-14 h-14 shrink-0" style="background:var(--muted)"><?php if (!empty($r['image'])): ?><img src="<?= img($r['image'], 120, 120) ?>" alt="" class="w-full h-full object-cover"><?php endif; ?></div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($r['name'] ?? ('#' . $r['id'])) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['category'] ?? $r['type'] ?? $r['region'] ?? '') ?></p>
        </div>
        <a href="?t=<?= e($t) ?>&edit=<?= (int)$r['id'] ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit</a>
        <form method="post" action="<?= url('actions/admin_delete_action.php') ?>" data-confirm="Delete <?= e('"' . ($r['name'] ?? ('#' . $r['id'])) . '"') ?>?">
          <input type="hidden" name="t" value="<?= e($t) ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">Nothing here yet.</p><?php endif; ?>
  </div>
</div>
</body>
</html>