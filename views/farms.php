<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$farmer = isset($_GET['id']) ? catalog()->farmer((int)$_GET['id']) : null;
$pageTitle = ($farmer ? $farmer['name'] : 'Our Farms') . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
if ($farmer): ?>
<div class="max-w-5xl mx-auto px-6 py-10">
  <a href="<?= url('views/farms.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← All farmers</a>
  <div class="grid md:grid-cols-2 gap-12">
    <div>
      <div class="overflow-hidden mb-4" style="aspect-ratio:3/4;background:var(--muted)"><img src="<?= img($farmer['image'], 600, 800) ?>" alt="<?= e($farmer['name']) ?>" class="w-full h-full object-cover"></div>
      <div class="flex flex-wrap gap-2"><?php foreach ($farmer['certifications'] as $c): ?><span class="text-xs px-2.5 py-1 border" style="border-color:var(--primary);color:var(--primary)"><?= e($c) ?></span><?php endforeach; ?></div>
    </div>
    <div class="pt-4">
      <p class="text-xs uppercase tracking-widest mb-3" style="color:var(--accent)"><?= e($farmer['region']) ?></p>
      <h1 class="font-display text-4xl font-light mb-1"><?= e($farmer['name']) ?></h1>
      <p class="text-sm mb-6" style="color:var(--muted-foreground)"><?= e($farmer['location']) ?></p>
      <div class="flex gap-8 mb-8 pb-8 border-b" style="border-color:var(--border)">
        <?php if ($farmer['hectares']): ?><div><p class="font-display text-3xl font-light"><?= rtrim(rtrim($farmer['hectares'], '0'), '.') ?></p><p class="text-xs" style="color:var(--muted-foreground)">hectares</p></div><?php endif; ?>
        <div><p class="font-display text-base font-light"><?= e($farmer['season']) ?></p><p class="text-xs" style="color:var(--muted-foreground)">season</p></div>
      </div>
      <p class="text-sm leading-relaxed mb-8" style="color:var(--muted-foreground)"><?= e($farmer['story']) ?></p>
      <p class="text-xs uppercase tracking-widest mb-3" style="color:var(--muted-foreground)">Grows for us</p>
      <div class="flex flex-wrap gap-2 mb-8"><?php foreach ($farmer['produce'] as $p): ?><span class="text-xs px-3 py-1.5 font-medium" style="background:var(--muted)"><?= e($p) ?></span><?php endforeach; ?></div>
      <a href="<?= url('views/shop.php') ?>" class="text-sm font-medium px-5 py-2.5 inline-block btn-primary">Shop their produce →</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Provenance</p>
  <h1 class="font-display text-4xl font-light mb-2">Our Farms &amp; Farmers</h1>
  <p class="text-sm mb-10 max-w-xl" style="color:var(--muted-foreground)">Every item on Akuafo &amp; Essentials is traceable to a named farmer and region. We visit every partner farm before onboarding and annually thereafter.</p>
  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach (catalog()->farmers() as $f): ?>
    <a href="?id=<?= $f['id'] ?>" class="group block">
      <div class="aspect-[3/2] overflow-hidden mb-4" style="background:var(--muted)"><img src="<?= img($f['image'], 600, 700) ?>" alt="<?= e($f['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"></div>
      <p class="text-xs mb-0.5" style="color:var(--accent)"><?= e($f['region']) ?></p>
      <h3 class="font-display text-xl font-light mb-1"><?= e($f['name']) ?></h3>
      <p class="text-xs mb-2" style="color:var(--muted-foreground)"><?= e($f['location']) ?> · <?= e(implode(', ', $f['produce'])) ?></p>
      <p class="text-xs leading-relaxed line-clamp-3" style="color:var(--muted-foreground)"><?= e($f['story']) ?></p>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif;
include __DIR__ . '/layout/footer.php'; ?>
