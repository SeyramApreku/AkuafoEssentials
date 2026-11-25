<?php
// Admin: choose which exclusive recipes each membership tier gets this month
// (Silver 1, Gold 2, Platinum 4), based on what the partner restaurants are highlighting.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/catalog_controller.php';
require_admin();

[$memberAll] = member_recipe_access();
$pool  = array_values(array_filter(catalog()->recipes(), fn($r) => in_array((int)$r['id'], $memberAll, true)));
$picks = member_recipe_picks();
$updated = app_setting('member_recipes_updated', '');
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);

$pageTitle = "Member Recipes | Akuafo & Essentials";
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-2">Member recipes</h1>
  <p class="text-sm mb-8 max-w-2xl" style="color:var(--muted-foreground)">Choose this month's exclusive recipes for each plan, from what our partner restaurants are highlighting. Members see them on their Members' recipes page straight away.<?= $updated ? ' Last changed ' . e(date('j M Y', strtotime($updated))) . '.' : '' ?></p>
  <?php if ($msg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted)"><?= e(is_array($msg) ? implode(' ', $msg) : $msg) ?></p><?php endif; ?>

  <?php if (!$pool): ?>
    <div class="p-6 border text-sm" style="border-color:var(--border)">
      There are no members-only recipes yet. In <a href="<?= url('views/admin/admin_manage.php?t=recipes') ?>" class="underline">Recipes</a>, add a recipe, set <strong>Members only</strong> to 1 and choose the <strong>Partner</strong> restaurant. It will then appear here.
    </div>
  <?php else: ?>
  <form method="post" action="<?= url('actions/admin_member_recipes_action.php') ?>">
    <div class="grid md:grid-cols-3 gap-6 mb-8">
      <?php foreach (membership_tiers() as $t): $key = strtolower($t['name']); ?>
        <div class="p-6 border" style="border-color:var(--border);background:var(--card)">
          <div class="h-1 mb-4" style="background:<?= e($t['color']) ?>"></div>
          <p class="font-display text-2xl font-light mb-1"><?= e($t['name']) ?></p>
          <p class="text-xs mb-5" style="color:var(--muted-foreground)"><?= (int)$t['recipes'] ?> recipe<?= $t['recipes'] === 1 ? '' : 's' ?> this month</p>
          <?php for ($slot = 0; $slot < (int)$t['recipes']; $slot++): $cur = $picks[$key][$slot] ?? 0; ?>
            <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Recipe <?= $slot + 1 ?></label>
            <select name="picks[<?= e($key) ?>][]" class="<?= $box ?> mb-4" style="border-color:var(--border)">
              <option value="0">Choose a recipe</option>
              <?php foreach ($pool as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (int)$cur === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?><?= !empty($r['partner']) ? ' · ' . e($r['partner']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          <?php endfor; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="px-6 py-3 text-sm font-medium btn-primary">Save this month's recipes</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
