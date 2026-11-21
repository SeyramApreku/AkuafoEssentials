<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();

$db = new Database();
$boxes = $db->fetchAll("SELECT * FROM boxes ORDER BY name");
$editId = $_GET['edit'] ?? '';
$edit = $editId ? $db->fetchOne("SELECT * FROM boxes WHERE id = ?", [$editId]) : null;
$sizes = $edit ? $db->fetchAll("SELECT * FROM box_sizes WHERE box_id = ? ORDER BY idx", [$edit['id']]) : [];
$produce = array_column($db->fetchAll("SELECT name FROM products WHERE name IS NOT NULL AND name <> '' ORDER BY name"), 'name');
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Weekly Boxes | Admin';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Manage weekly boxes</h1>
  <a href="?new=1" class="inline-block mb-6 px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">+ Add box</a>

  <?php if ($edit): ?>
  <form method="post" action="<?= url('actions/admin_box_sizes_action.php') ?>" class="mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl mb-1">Sizes, prices and contents</h2>
    <p class="text-xs mb-6" style="color:var(--muted-foreground)"><?= e($edit['name']) ?></p>
    <input type="hidden" name="box_id" value="<?= e($edit['id']) ?>">
    <?php foreach ($sizes as $s):
      $sid = (int)$s['id'];
      $picked = json_list($s['contents'] ?? null);
      $extra = array_values(array_diff($picked, $produce));
      $options = array_merge($produce, $extra);
    ?>
      <div class="grid md:grid-cols-4 gap-4 mb-6 pb-6 border-b" style="border-color:var(--border)">
        <div>
          <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Label</label>
          <input type="text" name="sizes[<?= $sid ?>][label]" value="<?= e($s['label']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
        </div>
        <div>
          <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">People</label>
          <input type="text" name="sizes[<?= $sid ?>][description]" value="<?= e($s['description']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
        </div>
        <div>
          <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Price (GHS)</label>
          <input type="number" step="any" name="sizes[<?= $sid ?>][price]" value="<?= e($s['price']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
        </div>
        <div>
          <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Items per box</label>
          <input type="text" name="sizes[<?= $sid ?>][items]" value="<?= e($s['items']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
        </div>
        <details class="md:col-span-4 border" style="border-color:var(--border)">
          <summary class="px-4 py-3 text-sm cursor-pointer">What's inside the <?= e($s['label']) ?> box (<?= count($picked) ?> selected)</summary>
          <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2 p-4 max-h-64 overflow-y-auto">
            <?php foreach ($options as $o): ?>
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="sizes[<?= $sid ?>][contents][]" value="<?= e($o) ?>" <?= in_array($o, $picked, true) ? 'checked' : '' ?>>
                <?= e($o) ?><?= in_array($o, $extra, true) ? ' (not in store)' : '' ?>
              </label>
            <?php endforeach; ?>
            <?php if (!$options): ?><p class="text-sm" style="color:var(--muted-foreground)">No products in the store yet.</p><?php endif; ?>
          </div>
        </details>
      </div>
    <?php endforeach; ?>
    <?php if (!$sizes): ?><p class="text-sm mb-4" style="color:var(--muted-foreground)">No sizes found for this box.</p><?php endif; ?>
    <button class="px-6 py-3 text-sm font-medium btn-primary">Save sizes</button>
  </form>

  <form method="post" action="<?= url('actions/admin_box_save_action.php') ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2">Box details</h2>
    <input type="hidden" name="id" value="<?= e($edit['id']) ?>">
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Name</label>
      <input type="text" name="name" value="<?= e($edit['name']) ?>" required class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Color</label>
      <input type="text" name="color" value="<?= e($edit['color']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Tagline</label>
      <input type="text" name="tagline" value="<?= e($edit['tagline']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Image</label>
      <?php if (!empty($edit['image'])): ?><img src="<?= img($edit['image'], 160, 160) ?>" alt="" class="w-20 h-20 object-cover mb-2"><?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">JPG, PNG or WebP, up to 2 MB. Leave empty to keep the current image.</p>
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Note</label>
      <textarea name="note" rows="3" class="<?= $box ?>" style="border-color:var(--border)"><?= e($edit['note']) ?></textarea>
    </div>
    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary">Save changes</button>
      <a href="<?= url('views/admin/admin_boxes.php') ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a>
    </div>
  </form>
  <?php endif; ?>

  <?php if (isset($_GET['new'])): ?>
  <form method="post" action="<?= url('actions/admin_box_save_action.php') ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2">Add a box</h2>
    <input type="hidden" name="id" value="">
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Name</label>
      <input type="text" name="name" required class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Color</label>
      <input type="text" name="color" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Tagline</label>
      <input type="text" name="tagline" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Image</label>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">JPG, PNG or WebP, up to 2 MB.</p>
    </div>
    <div class="md:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Note</label>
      <textarea name="note" rows="3" class="<?= $box ?>" style="border-color:var(--border)"></textarea>
    </div>
    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary">Create box</button>
      <a href="<?= url('views/admin/admin_boxes.php') ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a>
    </div>
  </form>
  <?php endif; ?>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($boxes as $b): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="w-14 h-14 shrink-0" style="background:var(--muted)"><?php if (!empty($b['image'])): ?><img src="<?= img($b['image'], 120, 120) ?>" alt="" class="w-full h-full object-cover"><?php endif; ?></div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($b['name']) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($b['tagline']) ?></p>
        </div>
        <a href="?edit=<?= e(urlencode($b['id'])) ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit</a>
        <form method="post" action="<?= url('actions/admin_box_delete_action.php') ?>" data-confirm="Delete <?= e('"' . $b['name'] . '"') ?>?">
          <input type="hidden" name="id" value="<?= e($b['id']) ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>