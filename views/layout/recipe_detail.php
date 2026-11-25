<?php
// One recipe, full page. Shared by views/recipes.php (everyone) and views/member_recipes.php (members).
// Needs: $recipe (decoded), $backUrl, $backLabel; optional $credit (e.g. "Exclusive members' recipe from Adom Kitchen").
?>
<div class="max-w-4xl mx-auto px-6 py-10">
  <a href="<?= e($backUrl) ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← <?= e($backLabel) ?></a>
  <div class="relative mb-10 overflow-hidden" style="background:var(--muted);aspect-ratio:16/7">
    <img src="<?= img($recipe['image'], 1000, 440) ?>" alt="<?= e($recipe['name']) ?>" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
    <div class="absolute bottom-6 left-6"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--secondary)"><?= e($recipe['subtitle']) ?></p><h1 class="font-display text-4xl font-light text-white"><?= e($recipe['name']) ?></h1></div>
  </div>
  <div class="flex flex-wrap gap-4 mb-10 text-sm border-b pb-8" style="border-color:var(--border);color:var(--muted-foreground)">
    <span>⏱ <?= e($recipe['time']) ?></span><span>·</span><span><?= e($recipe['difficulty']) ?></span><span>·</span><span>Serves <?= (int)$recipe['serves'] ?></span>
    <div class="flex gap-2 flex-wrap ml-auto"><?php foreach ($recipe['tags'] as $t): ?><span class="text-xs px-2 py-0.5" style="background:var(--muted)"><?= e($t) ?></span><?php endforeach; ?></div>
  </div>
  <?php if (!empty($credit)): ?><p class="text-xs uppercase tracking-widest mb-6" style="color:var(--accent)">★ <?= e($credit) ?></p><?php endif; ?>
  <div class="grid md:grid-cols-3 gap-12">
    <div>
      <h2 class="font-display text-xl mb-4">Ingredients</h2>
      <ul class="space-y-2 mb-8"><?php foreach ($recipe['ingredients'] as $ing): ?><li class="text-sm flex items-start gap-2" style="color:var(--muted-foreground)"><span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--accent)"></span><?= e($ing) ?></li><?php endforeach; ?></ul>
      <?php if ($recipe['produce']): ?>
      <h3 class="text-xs uppercase tracking-widest mb-3" style="color:var(--muted-foreground)">Produce used</h3>
      <div class="flex flex-wrap gap-2"><?php foreach ($recipe['produce'] as $p): ?><a href="<?= e(produce_url($p)) ?>" class="text-xs px-2.5 py-1 border hover:underline underline-offset-2" style="border-color:var(--border)" title="Shop <?= e($p) ?>"><?= e($p) ?> →</a><?php endforeach; ?></div>
      <?php endif; ?>
    </div>
    <div class="md:col-span-2">
      <h2 class="font-display text-xl mb-6">Method</h2>
      <ol class="space-y-6"><?php foreach ($recipe['method'] as $i => $step): ?><li class="flex gap-4"><span class="font-display text-2xl font-light shrink-0 w-8 pt-0.5" style="color:var(--accent)"><?= $i + 1 ?></span><p class="text-sm leading-relaxed" style="color:var(--muted-foreground)"><?= e($step) ?></p></li><?php endforeach; ?></ol>
    </div>
  </div>
</div>
