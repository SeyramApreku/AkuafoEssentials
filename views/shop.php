<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'The Produce | Akuafo & Essentials';
$cat = $_GET['category'] ?? 'All'; $reg = $_GET['region'] ?? 'All Regions';
$farmer = mb_substr(trim((string)($_GET['farmer'] ?? '')), 0, 80);
$products = catalog()->products($cat, $reg, $farmer);
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Directly from Ghanaian farms</p>
  <h1 class="font-display text-4xl font-light mb-8">The Produce</h1>
  <?php if ($farmer !== ''): ?>
    <p class="text-sm mb-6 p-3 flex flex-wrap items-center gap-3" style="background:var(--muted)">Showing produce grown by <strong><?= e($farmer) ?></strong>
      <a href="<?= url('views/shop.php') ?>" class="text-xs underline ml-auto">Show all produce</a></p>
  <?php endif; ?>
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
    <?php foreach ($products as $p) include __DIR__ . '/layout/product_card.php'; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
