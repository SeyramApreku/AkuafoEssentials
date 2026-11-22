<?php
// One Entertaining item card. Expects $i (item row). Optional $guests (int) to suggest a quantity.
$guests  = (int)($guests ?? 0);
$suggest = ent_suggest_qty($i['serves'], $guests);
$cardUrl = url('views/entertaining_item.php?id=' . $i['id'] . ($guests ? '&guests=' . $guests : ''));
$diet    = ent_dietary_tags();
?>
<div class="group border flex flex-col" style="border-color:var(--border);background:var(--card)">
  <a href="<?= $cardUrl ?>" class="block relative overflow-hidden" style="aspect-ratio:4/3;background:var(--muted)">
    <img src="<?= img($i['image'], 800, 600) ?>" alt="<?= e($i['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
    <?php if (!empty($i['badge'])): ?><span class="absolute top-3 left-3 text-xs px-2 py-0.5 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($i['badge']) ?></span><?php endif; ?>
    <span class="absolute top-3 right-3 text-xs px-2 py-0.5" style="background:var(--card)"><?= e($i['category_name']) ?></span>
  </a>
  <div class="p-5 flex flex-col flex-1 gap-3">
    <div>
      <a href="<?= $cardUrl ?>"><h3 class="font-display text-xl font-light leading-snug mb-1"><?= e($i['name']) ?></h3></a>
      <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs" style="color:var(--muted-foreground)">
        <?php if ($i['serves']): ?><span>Serves <?= (int)$i['serves'] ?></span><?php endif; ?>
        <span>Order <?= e(notice_label($i['notice_hours'])) ?> ahead</span>
      </div>
    </div>
    <?php if ($i['dietary']): ?>
      <div class="flex flex-wrap gap-1.5">
        <?php foreach ($i['dietary'] as $d): ?><span class="text-xs px-2 py-0.5 border" style="border-color:var(--border)"><?= e($diet[$d] ?? $d) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($suggest): ?>
      <p class="text-xs p-2" style="background:var(--secondary)">For <?= $guests ?> guests we suggest <strong><?= $suggest ?></strong></p>
    <?php endif; ?>
    <form method="post" action="<?= url('actions/cart_action.php') ?>" class="mt-auto pt-3 border-t flex items-center justify-between gap-3" style="border-color:var(--border)">
      <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="ent"><input type="hidden" name="key" value="<?= (int)$i['id'] ?>">
      <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
      <p class="font-display text-2xl font-light"><?= money($i['price']) ?></p>
      <div class="flex items-center gap-2">
        <label class="sr-only" for="q<?= (int)$i['id'] ?>">Quantity</label>
        <input id="q<?= (int)$i['id'] ?>" type="number" name="qty" min="1" max="99" value="<?= $suggest ?: 1 ?>" class="w-14 px-2 py-2 text-sm border bg-transparent text-center" style="border-color:var(--border)">
        <button class="px-4 py-2.5 text-xs font-medium btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>
