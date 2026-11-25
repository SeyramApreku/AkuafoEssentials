<?php
// Recipes for everyone. Members-only recipes live on their own page (views/member_recipes.php).
require_once __DIR__ . '/../controllers/catalog_controller.php';
[$memberAll] = member_recipe_access();
$recipe = isset($_GET['id']) ? catalog()->recipe((int)$_GET['id']) : null;
if ($recipe && in_array((int)$recipe['id'], $memberAll, true)) redirect(url('views/member_recipes.php?id=' . (int)$recipe['id']));   // old links
$pageTitle = ($recipe ? $recipe['name'] : 'Recipes') . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';

if ($recipe):
    $backUrl = url('views/recipes.php'); $backLabel = 'All recipes';
    include __DIR__ . '/layout/recipe_detail.php';
else: ?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Tested in our kitchen</p>
  <h1 class="font-display text-4xl font-light mb-2">Recipes</h1>
  <p class="text-sm mb-8" style="color:var(--muted-foreground)">Traditional Ghanaian recipes, made with the exact produce we sell.</p>

  <a href="<?= url('views/member_recipes.php') ?>" class="flex flex-wrap items-center justify-between gap-3 p-5 mb-12 border" style="border-color:var(--primary);background:var(--card)">
    <span><span class="block text-xs uppercase tracking-widest mb-1" style="color:var(--accent)">★ Members' recipes</span>
      <span class="text-sm"><?= member_tier() ? 'Open this month\'s exclusive recipes from our partner restaurants.' : 'Exclusive recipes from our partner restaurants, chosen every month for Akuafo members.' ?></span></span>
    <span class="text-sm font-medium" style="color:var(--primary)"><?= member_tier() ? 'My recipes →' : 'See what members get →' ?></span>
  </a>

  <div class="grid md:grid-cols-2 gap-8">
    <?php foreach (catalog()->recipes() as $r): if (in_array((int)$r['id'], $memberAll, true)) continue; ?>
    <a href="?id=<?= (int)$r['id'] ?>" class="group block">
      <div class="relative overflow-hidden mb-4" style="aspect-ratio:16/9;background:var(--muted)">
        <img src="<?= img($r['image'], 800, 600) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
        <div class="absolute bottom-0 left-0 right-0 p-4 flex gap-2 flex-wrap"><?php foreach ($r['tags'] as $t): ?><span class="text-xs px-2 py-0.5 font-medium" style="background:var(--card)"><?= e($t) ?></span><?php endforeach; ?></div>
      </div>
      <p class="text-xs mb-1" style="color:var(--muted-foreground)"><?= e($r['subtitle']) ?></p>
      <h3 class="font-display text-2xl font-light mb-2"><?= e($r['name']) ?></h3>
      <div class="flex gap-4 text-xs" style="color:var(--muted-foreground)"><span><?= e($r['time']) ?></span><span>·</span><span><?= e($r['difficulty']) ?></span><span>·</span><span>Serves <?= (int)$r['serves'] ?></span></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif;
include __DIR__ . '/layout/footer.php'; ?>
