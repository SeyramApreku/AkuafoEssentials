<?php
// Admin: partners and their testimonials (logo for the home page, portrait + quote for the Testimonials page).
// Its own page because a partner has two pictures (logo and portrait), which the generic editor can't handle.
require_once __DIR__ . '/../../core/core.php';
require_admin();

$db = new Database();
try { $rows = $db->fetchAll('SELECT * FROM aku_partners ORDER BY sort_order, id'); $ready = true; }
catch (PDOException $e) { $rows = []; $ready = false; }
$ready = $ready && $db->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_partners' AND column_name = 'quote'");
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? $db->fetchOne('SELECT * FROM aku_partners WHERE id = ?', [$editId]) : null;
$isNew = isset($_GET['new']);
$f = $edit ?: ['id' => 0, 'name' => '', 'type' => 'Restaurant', 'image' => '', 'photo' => '', 'role' => '', 'restaurant_name' => '', 'quote' => '', 'sort_order' => count($rows) + 1];
$types = ['Restaurant', 'Hotel', 'Catering', 'Cafe', 'Bar', 'Lodge'];

$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Partners | Admin';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
$act = url('actions/admin_partner_action.php');
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-2">Partners &amp; testimonials</h1>
  <p class="text-sm mb-6" style="color:var(--muted-foreground)">Partners with a quote appear on the <a class="underline" href="<?= url('views/testimonials.php') ?>" target="_blank">Testimonials page</a>. All partners appear in "Our Partners" on the home page.</p>

  <?php if (!$ready): ?>
    <p class="p-4 mb-6 text-sm" style="background:var(--secondary)">Run <a class="underline" href="<?= url('views/admin/admin_setup.php') ?>">Database Update</a> first to add photos, roles and quotes.</p>
  <?php else: ?>
  <a href="?new=1" class="inline-block mb-6 px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">+ Add partner</a>

  <?php if ($edit || $isNew): ?>
  <form method="post" action="<?= $act ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2"><?= $edit ? 'Edit ' . e($edit['name']) : 'Add a partner' ?></h2>
    <input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Name (person or business)</label>
      <input type="text" name="name" required maxlength="100" value="<?= e($f['name']) ?>" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Restaurant / business name</label>
      <input type="text" name="restaurant_name" maxlength="120" value="<?= e($f['restaurant_name']) ?>" placeholder="e.g. Adom Kitchen" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Role</label>
      <input type="text" name="role" maxlength="80" value="<?= e($f['role']) ?>" placeholder="e.g. Head Chef" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Type</label>
      <select name="type" class="<?= $box ?>" style="border-color:var(--border)">
        <?php foreach (array_unique(array_merge($types, [$f['type']])) as $t): ?><option <?= $t === $f['type'] ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select></div>
    <div class="md:col-span-2"><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Testimonial quote (leave empty to hide from the Testimonials page)</label>
      <textarea name="quote" rows="3" maxlength="600" class="<?= $box ?>" style="border-color:var(--border)"><?= e($f['quote']) ?></textarea></div>
    <div>
      <span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Portrait photo (Testimonials page)</span>
      <img src="<?= e(img($f['photo'] ?: PARTNER_PLACEHOLDER, 160, 160)) ?>" alt="" class="w-20 h-20 object-cover mb-2" style="filter:grayscale(1)">
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="text-sm">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Square works best. JPG, PNG or WebP, up to 2 MB. Saved in uploads/partners/.</p>
      <?php if (!empty($f['photo'])): ?><label class="flex items-center gap-2 text-xs mt-2"><input type="checkbox" name="remove_photo" value="1"> Remove photo (use the placeholder)</label><?php endif; ?>
    </div>
    <div>
      <span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Logo (home page, optional)</span>
      <?php if (!empty($f['image'])): ?><img src="<?= e(img($f['image'], 160, 160)) ?>" alt="" class="w-20 h-20 object-contain p-1 mb-2" style="background:#fff"><?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">JPG, PNG or WebP, up to 2 MB.</p>
    </div>
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Display order</label>
      <input type="number" name="sort_order" min="0" max="999" value="<?= (int)$f['sort_order'] ?>" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Lower numbers show first.</p></div>
    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $edit ? 'Save changes' : 'Add partner' ?></button>
      <a href="<?= url('views/admin/admin_partners.php') ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a>
    </div>
  </form>
  <?php endif; ?>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($rows as $r): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <img src="<?= e(img($r['photo'] ?: PARTNER_PLACEHOLDER, 120, 120)) ?>" alt="" class="w-14 h-14 object-cover shrink-0" style="filter:grayscale(1)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($r['name']) ?><?= $r['restaurant_name'] ? ' · ' . e($r['restaurant_name']) : '' ?></p>
          <p class="text-xs truncate" style="color:var(--muted-foreground)"><?= e(implode(' · ', array_filter([$r['role'], $r['type'], 'Order ' . (int)$r['sort_order']]))) ?> · <?= $r['quote'] ? 'On Testimonials page' : 'No quote' ?></p>
        </div>
        <a href="?edit=<?= (int)$r['id'] ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit</a>
        <form method="post" action="<?= $act ?>" data-confirm="Delete <?= e('"' . $r['name'] . '"') ?>?">
          <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No partners yet.</p><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
