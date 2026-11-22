<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/catalog_controller.php';
require_admin();

$ent        = entertaining();
$categories = $ent->categories();
$occasions  = $ent->occasions();
$items      = $ent->items();
$catFilter  = (int)($_GET['category'] ?? 0);
if ($catFilter) $items = array_values(array_filter($items, fn($i) => (int)$i['category_id'] === $catFilter));

$editId = (int)($_GET['edit'] ?? 0);
$edit   = $editId ? $ent->item($editId) : null;
$isNew  = isset($_GET['new']);
$f      = $edit ?: ['id' => 0, 'name' => '', 'category_id' => $catFilter, 'description' => '', 'contents' => [], 'price' => '', 'serves' => '',
                    'notice_hours' => 48, 'dietary' => [], 'badge' => '', 'image' => '', 'featured' => 0, 'is_available' => 1, 'occasion_ids' => []];
$occName = array_column($occasions, 'name', 'id');

$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Entertaining items | Admin';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
$act = url('actions/admin_ent_items_action.php');
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin · Entertaining</p>
  <h1 class="font-display text-4xl font-light mb-6">Manage entertaining items</h1>

  <div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="?new=1<?= $catFilter ? '&category=' . $catFilter : '' ?>" class="px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">+ Add item</a>
    <a href="<?= url('views/admin/admin_manage.php?t=ent_occasions') ?>" class="px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">Occasions</a>
    <a href="<?= url('views/admin/admin_manage.php?t=ent_categories') ?>" class="px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">Categories</a>
    <form method="get" class="ml-auto">
      <select name="category" onchange="this.form.submit()" class="px-3 py-2 text-xs border bg-transparent" style="border-color:var(--border)">
        <option value="0">All categories (<?= count($ent->items()) ?>)</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($edit || $isNew): ?>
  <form method="post" action="<?= $act ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2"><?= $edit ? 'Edit ' . e($edit['name']) : 'Add an entertaining item' ?></h2>
    <input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">

    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Name</label>
      <input type="text" name="name" value="<?= e($f['name']) ?>" required maxlength="120" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Category</label>
      <select name="category_id" required class="<?= $box ?>" style="border-color:var(--border)">
        <option value="">Select category</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$f['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Price (GHS)</label>
      <input type="number" name="price" value="<?= e($f['price']) ?>" required min="0" step="0.01" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Serves (people per unit)</label>
      <input type="number" name="serves" value="<?= e($f['serves']) ?>" min="1" step="1" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Used by the guest calculator. Leave empty for things that aren't portions (e.g. a produce crate).</p>
    </div>

    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Minimum notice (hours)</label>
      <input type="number" name="notice_hours" value="<?= (int)$f['notice_hours'] ?>" required min="0" max="720" step="1" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">24 = 1 day, 48 = 2 days, 72 = 3 days, 168 = 1 week.</p>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Badge (optional)</label>
      <input type="text" name="badge" value="<?= e($f['badge']) ?>" maxlength="40" placeholder="e.g. Bestseller" class="<?= $box ?>" style="border-color:var(--border)">
    </div>

    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Description</label>
      <textarea name="description" rows="3" class="<?= $box ?>" style="border-color:var(--border)"><?= e($f['description']) ?></textarea>
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">What's included (optional, one per line)</label>
      <textarea name="contents" rows="3" class="<?= $box ?>" style="border-color:var(--border)"><?= e(implode("\n", $f['contents'])) ?></textarea>
    </div>

    <div>
      <span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Dietary tags</span>
      <div class="grid grid-cols-2 gap-2">
        <?php foreach (ent_dietary_tags() as $k => $label): ?>
          <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="dietary[]" value="<?= e($k) ?>" <?= in_array($k, $f['dietary'], true) ? 'checked' : '' ?>> <?= e($label) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Occasions</span>
      <div class="grid grid-cols-2 gap-2">
        <?php foreach ($occasions as $o): ?>
          <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="occasions[]" value="<?= (int)$o['id'] ?>" <?= in_array((int)$o['id'], $f['occasion_ids'], true) ? 'checked' : '' ?>> <?= e($o['name']) ?></label>
        <?php endforeach; ?>
        <?php if (!$occasions): ?><p class="text-sm" style="color:var(--muted-foreground)">No occasions yet.</p><?php endif; ?>
      </div>
    </div>

    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Image</label>
      <?php if (!empty($f['image'])): ?><img src="<?= img($f['image'], 240, 180) ?>" alt="" class="w-28 h-20 object-cover mb-2"><?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">JPG, PNG or WebP, up to 2 MB.<?= $edit ? ' Leave empty to keep the current image.' : ' Leave empty to use the placeholder for now.' ?></p>
    </div>

    <div class="md:col-span-2 flex flex-wrap gap-6">
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_available" value="1" <?= (int)$f['is_available'] ? 'checked' : '' ?>> Available to order</label>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="featured" value="1" <?= (int)$f['featured'] ? 'checked' : '' ?>> Featured on the Entertaining page</label>
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $edit ? 'Save changes' : 'Add item' ?></button>
      <a href="<?= url('views/admin/admin_ent_items.php' . ($catFilter ? '?category=' . $catFilter : '')) ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a>
    </div>
  </form>
  <?php endif; ?>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($items as $i): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border);<?= (int)$i['is_available'] ? '' : 'opacity:.55' ?>">
        <div class="w-16 h-14 shrink-0" style="background:var(--muted)"><?php if (!empty($i['image'])): ?><img src="<?= img($i['image'], 160, 120) ?>" alt="" class="w-full h-full object-cover"><?php endif; ?></div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">
            <?= e($i['name']) ?>
            <?php if ((int)$i['featured']): ?><span class="text-xs px-1.5 py-0.5 ml-1" style="background:var(--secondary)">Featured</span><?php endif; ?>
            <?php if (!(int)$i['is_available']): ?><span class="text-xs px-1.5 py-0.5 ml-1" style="background:var(--muted);color:var(--accent)">Hidden</span><?php endif; ?>
          </p>
          <p class="text-xs" style="color:var(--muted-foreground)">
            <?= e($i['category_name']) ?> · <?= money($i['price']) ?><?= $i['serves'] ? ' · serves ' . (int)$i['serves'] : '' ?> · <?= e(notice_label($i['notice_hours'])) ?> notice
            <?php if ($i['dietary']): ?> · <?= e(implode(', ', array_map(fn($d) => ent_dietary_tags()[$d] ?? $d, $i['dietary']))) ?><?php endif; ?>
          </p>
          <p class="text-xs truncate" style="color:var(--muted-foreground)"><?= $i['occasion_ids'] ? e(implode(', ', array_map(fn($o) => $occName[$o] ?? '', $i['occasion_ids']))) : 'No occasions' ?></p>
        </div>
        <form method="post" action="<?= $act ?>">
          <input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)"><?= (int)$i['is_available'] ? 'Hide' : 'Show' ?></button>
        </form>
        <a href="?edit=<?= (int)$i['id'] ?><?= $catFilter ? '&category=' . $catFilter : '' ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit</a>
        <form method="post" action="<?= $act ?>" data-confirm="Delete <?= e('"' . $i['name'] . '"') ?>? Past orders keep their copy of the item.">
          <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No items<?= $catFilter ? ' in this category' : '' ?> yet.</p><?php endif; ?>
  </div>
</div>
</body>
</html>
