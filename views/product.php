<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$p = catalog()->product((int)($_GET['id'] ?? 0));
if (!$p) { header('Location: ' . url('views/shop.php')); exit; }
$pageTitle = $p['name'] . ' | Akuafo & Essentials';
$related = catalog()->related($p['region'], $p['id']);
$recipe = catalog()->recipeForProduce($p['name']);
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-10">
  <a href="<?= url('views/shop.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← Back to produce</a>
  <div class="grid md:grid-cols-2 gap-12">
    <div class="relative" style="background:var(--muted)">
      <img src="<?= img($p['image'], 800, 800) ?>" alt="<?= e($p['name']) ?>" class="w-full object-cover" style="aspect-ratio:1">
      <?php if ($p['badge']): ?><span class="absolute top-4 left-4 text-xs px-2 py-1 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($p['badge']) ?></span><?php endif; ?>
    </div>
    <div class="flex flex-col justify-center">
      <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)"><?= e($p['category']) ?></p>
      <h1 class="font-display text-4xl font-light mb-1"><?= e($p['name']) ?></h1>
      <p class="text-sm mb-4 italic" style="color:var(--muted-foreground)"><?= e($p['twi']) ?></p>
      <p class="text-2xl font-display mb-1"><?= money($p['price']) ?></p>
      <p class="text-xs mb-6" style="color:var(--muted-foreground)"><?= e($p['unit']) ?></p>
      <p class="text-sm leading-relaxed mb-6" style="color:var(--muted-foreground)"><?= e($p['description']) ?></p>
      <div class="flex gap-4 text-xs mb-8" style="color:var(--muted-foreground)"><span>Farmer: <strong style="color:var(--foreground)"><?= e($p['farmer']) ?></strong></span><span>·</span><span><?= e($p['region']) ?></span></div>
      <form method="post" action="<?= url('actions/cart_action.php') ?>" class="flex items-center gap-4 mb-4">
        <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="produce"><input type="hidden" name="key" value="<?= $p['id'] ?>">
        <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>"><input type="hidden" name="qty" id="qty" value="1">
        <div class="flex items-center border" style="border-color:var(--border)">
          <button type="button" data-qty="-1" class="px-3 py-2 text-sm">−</button>
          <span id="qty-label" class="px-3 py-2 text-sm border-l border-r" style="border-color:var(--border)">1</span>
          <button type="button" data-qty="1" class="px-3 py-2 text-sm">+</button>
        </div>
        <button class="flex-1 py-2.5 text-sm font-medium btn-primary">Add to order</button>
      </form>
      <?php if ($recipe): ?><a href="<?= url('views/recipes.php?id=' . $recipe['id']) ?>" class="text-sm underline underline-offset-4" style="color:var(--accent)">See recipe using <?= e($p['name']) ?> →</a><?php endif; ?>
    </div>
  </div>
  <?php if ($related): ?>
  <div class="mt-16 border-t pt-10" style="border-color:var(--border)">
    <p class="text-sm font-medium mb-6">Also from <?= e($p['region']) ?></p>
    <div class="grid grid-cols-3 gap-4">
      <?php foreach ($related as $r): ?>
      <a href="<?= url('views/product.php?id=' . $r['id']) ?>"><div class="aspect-square overflow-hidden mb-2" style="background:var(--muted)"><img src="<?= img($r['image'], 400, 400) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover"></div>
        <p class="text-xs font-medium"><?= e($r['name']) ?></p><p class="text-xs font-display"><?= money($r['price']) ?></p></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
