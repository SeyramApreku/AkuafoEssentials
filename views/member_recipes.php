<?php
// Members' recipes: this month's exclusive recipes from partner restaurants, picked by the admin for each tier
// (Silver 1, Gold 2, Platinum 4; Admin > Member Recipes). Non-members see what membership includes.
require_once __DIR__ . '/../controllers/catalog_controller.php';
[$memberAll, $memberOpen] = member_recipe_access();
$tierName = member_tier() ? ucfirst(member_tier()) : null;
$recipe = isset($_GET['id']) ? catalog()->recipe((int)$_GET['id']) : null;
if ($recipe && !in_array((int)$recipe['id'], $memberAll, true)) redirect(url('views/recipes.php?id=' . (int)$recipe['id']));   // a public recipe
$canOpen = $recipe && in_array((int)$recipe['id'], $memberOpen, true);
$listName = $tierName ? 'My recipes' : "Members' recipes";
$pageTitle = ($recipe && $canOpen ? $recipe['name'] : $listName) . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';

if ($recipe && $canOpen):
    $backUrl = url('views/member_recipes.php'); $backLabel = $listName;
    $credit = "Exclusive members' recipe" . (!empty($recipe['partner']) ? ' from ' . $recipe['partner'] : '');
    include __DIR__ . '/layout/recipe_detail.php';
else:
    $mine = array_values(array_filter(catalog()->recipes(), fn($r) => in_array((int)$r['id'], $memberOpen, true)));
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">★ Members only · <?= e(date('F Y')) ?></p>
  <h1 class="font-display text-4xl font-light mb-2"><?= e($listName) ?></h1>
  <p class="text-sm mb-10 max-w-2xl" style="color:var(--muted-foreground)">Each month our partner restaurants share dishes from their kitchens with Akuafo members: 1 recipe for Silver, 2 for Gold and 4 for Platinum.</p>

  <?php if ($recipe && !$canOpen): ?>
    <p class="text-sm mb-8 p-3" style="background:var(--muted)">"<?= e($recipe['name']) ?>" isn't in your <?= $tierName ? e($tierName) . ' ' : '' ?>recipes this month.</p>
  <?php endif; ?>

  <?php if ($mine): ?>
    <div class="grid md:grid-cols-2 gap-8">
      <?php foreach ($mine as $r): ?>
      <a href="?id=<?= (int)$r['id'] ?>" class="group block">
        <div class="relative overflow-hidden mb-4" style="aspect-ratio:16/9;background:var(--muted)">
          <img src="<?= img($r['image'], 800, 600) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
          <div class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors"></div>
          <?php if (!empty($r['partner'])): ?><span class="absolute top-3 left-3 text-xs px-2.5 py-1 font-medium" style="background:var(--primary);color:var(--primary-foreground)">From <?= e($r['partner']) ?></span><?php endif; ?>
        </div>
        <p class="text-xs mb-1" style="color:var(--muted-foreground)"><?= e($r['subtitle']) ?></p>
        <h3 class="font-display text-2xl font-light mb-2"><?= e($r['name']) ?></h3>
        <div class="flex gap-4 text-xs" style="color:var(--muted-foreground)"><span><?= e($r['time']) ?></span><span>·</span><span><?= e($r['difficulty']) ?></span><span>·</span><span>Serves <?= (int)$r['serves'] ?></span></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if ($tierName && $tierName !== 'Platinum'): ?>
      <p class="text-sm mt-12" style="color:var(--muted-foreground)">Want more each month? <a href="<?= url('views/membership.php') ?>" class="underline" style="color:var(--primary)">Gold gets 2 and Platinum gets 4.</a></p>
    <?php endif; ?>
  <?php elseif ($tierName): ?>
    <p class="text-sm py-12 text-center border" style="border-color:var(--border);color:var(--muted-foreground)">This month's recipes from our partner restaurants are on their way. Check back soon.</p>
  <?php else: ?>
    <!-- Not a member: what they'd get -->
    <div class="grid md:grid-cols-3 gap-6 mb-10">
      <?php foreach (membership_tiers() as $t): ?>
        <div class="p-6 border text-center" style="border-color:var(--border);background:var(--card)">
          <div class="h-1 mb-5" style="background:<?= e($t['color']) ?>"></div>
          <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)"><?= e($t['name']) ?></p>
          <p class="font-display text-4xl font-light">🔒 <?= (int)$t['recipes'] ?></p>
          <p class="text-xs mt-1" style="color:var(--muted-foreground)">exclusive recipe<?= $t['recipes'] === 1 ? '' : 's' ?> a month</p>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center"><a href="<?= url('views/membership.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">Become a member</a></div>
  <?php endif; ?>
</div>
<?php endif;
include __DIR__ . '/layout/footer.php'; ?>
