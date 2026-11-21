<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$recipe = isset($_GET['id']) ? catalog()->recipe((int)$_GET['id']) : null;
$pageTitle = ($recipe ? $recipe['name'] : 'Recipes') . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
if ($recipe): ?>
<div class="max-w-4xl mx-auto px-6 py-10">
  <a href="<?= url('views/recipes.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← All recipes</a>
  <div class="relative mb-10 overflow-hidden" style="background:var(--muted);aspect-ratio:16/7">
    <img src="<?= img($recipe['image'], 1000, 440) ?>" alt="<?= e($recipe['name']) ?>" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
    <div class="absolute bottom-6 left-6"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--secondary)"><?= e($recipe['subtitle']) ?></p><h1 class="font-display text-4xl font-light text-white"><?= e($recipe['name']) ?></h1></div>
  </div>
  <div class="flex flex-wrap gap-4 mb-10 text-sm border-b pb-8" style="border-color:var(--border);color:var(--muted-foreground)">
    <span>⏱ <?= e($recipe['time']) ?></span><span>·</span><span><?= e($recipe['difficulty']) ?></span><span>·</span><span>Serves <?= $recipe['serves'] ?></span>
    <div class="flex gap-2 flex-wrap ml-auto"><?php foreach ($recipe['tags'] as $t): ?><span class="text-xs px-2 py-0.5" style="background:var(--muted)"><?= e($t) ?></span><?php endforeach; ?></div>
  </div>
  <div class="grid md:grid-cols-3 gap-12">
    <div>
      <h2 class="font-display text-xl mb-4">Ingredients</h2>
      <ul class="space-y-2 mb-8"><?php foreach ($recipe['ingredients'] as $ing): ?><li class="text-sm flex items-start gap-2" style="color:var(--muted-foreground)"><span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--accent)"></span><?= e($ing) ?></li><?php endforeach; ?></ul>
      <h3 class="text-xs uppercase tracking-widest mb-3" style="color:var(--muted-foreground)">Produce used</h3>
      <div class="flex flex-wrap gap-2"><?php foreach ($recipe['produce'] as $p): ?><span class="text-xs px-2.5 py-1 border" style="border-color:var(--border)"><?= e($p) ?></span><?php endforeach; ?></div>
    </div>
    <div class="md:col-span-2">
      <h2 class="font-display text-xl mb-6">Method</h2>
      <ol class="space-y-6"><?php foreach ($recipe['method'] as $i => $step): ?><li class="flex gap-4"><span class="font-display text-2xl font-light shrink-0 w-8 pt-0.5" style="color:var(--accent)"><?= $i + 1 ?></span><p class="text-sm leading-relaxed" style="color:var(--muted-foreground)"><?= e($step) ?></p></li><?php endforeach; ?></ol>
    </div>
  </div>
</div>
<?php else: ?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Tested in our kitchen</p>
  <h1 class="font-display text-4xl font-light mb-2">Recipes</h1>
  <p class="text-sm mb-12" style="color:var(--muted-foreground)">Traditional Ghanaian recipes, made with the exact produce we sell.</p>
  <div class="grid md:grid-cols-2 gap-8">
    <?php foreach (catalog()->recipes() as $r): ?>
    <a href="?id=<?= $r['id'] ?>" class="group block">
      <div class="relative overflow-hidden mb-4" style="aspect-ratio:16/9;background:var(--muted)">
        <img src="<?= img($r['image'], 800, 600) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
        <div class="absolute bottom-0 left-0 right-0 p-4 flex gap-2 flex-wrap"><?php foreach ($r['tags'] as $t): ?><span class="text-xs px-2 py-0.5 font-medium" style="background:var(--card)"><?= e($t) ?></span><?php endforeach; ?></div>
      </div>
      <p class="text-xs mb-1" style="color:var(--muted-foreground)"><?= e($r['subtitle']) ?></p>
      <h3 class="font-display text-2xl font-light mb-2"><?= e($r['name']) ?></h3>
      <div class="flex gap-4 text-xs" style="color:var(--muted-foreground)"><span><?= e($r['time']) ?></span><span>·</span><span><?= e($r['difficulty']) ?></span><span>·</span><span>Serves <?= $r['serves'] ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif;
include __DIR__ . '/layout/footer.php'; ?>
