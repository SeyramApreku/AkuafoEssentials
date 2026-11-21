<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'The Produce | Akuafo & Essentials';
$cat = $_GET['category'] ?? 'All'; $reg = $_GET['region'] ?? 'All Regions';
$products = catalog()->products($cat, $reg);
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Directly from Ghanaian farms</p>
  <h1 class="font-display text-4xl font-light mb-8">The Produce</h1>
  <form method="get" class="flex flex-col sm:flex-row gap-4 mb-10">
    <div class="flex flex-wrap gap-2">
      <?php foreach (catalog()->categories() as $c): ?>
        <a href="?category=<?= urlencode($c) ?>&region=<?= urlencode($reg) ?>" class="text-xs px-3 py-1.5 border <?= $cat === $c ? 'pill-on' : '' ?>" style="border-color:var(--border)"><?= e($c) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="category" value="<?= e($cat) ?>">
    <select name="region" onchange="this.form.submit()" class="text-xs px-3 py-1.5 border bg-transparent ml-auto" style="border-color:var(--border)">
      <?php foreach (catalog()->regions() as $r): ?><option <?= $r === $reg ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
    </select>
  </form>
  <?php if (!$products): ?>
    <p class="text-sm text-center py-20" style="color:var(--muted-foreground)">No produce matches your filter.</p>
  <?php else: ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
    <?php foreach ($products as $p): ?>
    <a href="<?= url('views/product.php?id=' . $p['id']) ?>" class="group block">
      <div class="aspect-square overflow-hidden mb-3 relative" style="background:var(--muted)">
        <img src="<?= img($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        <?php if ($p['badge']): ?><span class="absolute top-2 left-2 text-xs px-2 py-0.5 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($p['badge']) ?></span><?php endif; ?>
      </div>
      <p class="text-xs mb-0.5" style="color:var(--muted-foreground)"><?= e($p['region']) ?> · <?= e($p['farmer']) ?></p>
      <p class="font-medium text-sm mb-0.5"><?= e($p['name']) ?></p>
      <p class="text-xs mb-1 italic" style="color:var(--muted-foreground)"><?= e($p['twi']) ?></p>
      <div class="flex items-center justify-between"><p class="text-sm font-display"><?= money($p['price']) ?></p><span class="text-xs" style="color:var(--muted-foreground)"><?= e($p['unit']) ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
