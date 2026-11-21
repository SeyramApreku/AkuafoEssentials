<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'Entertaining | Akuafo & Essentials';
$hampers = catalog()->hampers(); $bulk = catalog()->bulkItems();
include __DIR__ . '/layout/header.php';
$ret = e($_SERVER['REQUEST_URI']); $act = url('actions/cart_action.php');
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Akuafo Entertaining</p>
  <h1 class="font-display text-4xl font-light mb-2">Pre-order &amp; Events</h1>
  <p class="text-sm mb-10 max-w-xl" style="color:var(--muted-foreground)">Hampers and bulk produce for celebrations, holidays, and large gatherings. All orders require advance notice, see each item for lead times. Delivery coordinated to your door or venue.</p>
  <h2 class="font-display text-2xl font-light mb-6">Hampers &amp; Occasion Boxes</h2>
  <div class="grid md:grid-cols-3 gap-6 mb-16">
    <?php foreach ($hampers as $h): ?>
    <div class="border flex flex-col" style="border-color:var(--border)">
      <div class="relative overflow-hidden" style="aspect-ratio:4/3;background:var(--muted)">
        <img src="<?= img($h['image'], 800, 600) ?>" alt="<?= e($h['name']) ?>" class="w-full h-full object-cover">
        <?php if ($h['badge']): ?><span class="absolute top-3 left-3 text-xs px-2 py-0.5 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($h['badge']) ?></span><?php endif; ?>
        <span class="absolute top-3 right-3 text-xs px-2 py-0.5" style="background:var(--card)"><?= e($h['occasion']) ?></span>
      </div>
      <div class="p-5 flex flex-col flex-1 gap-3">
        <div><h3 class="font-display text-xl font-light mb-1"><?= e($h['name']) ?></h3><p class="text-xs leading-relaxed" style="color:var(--muted-foreground)"><?= e($h['description']) ?></p></div>
        <div class="flex gap-4 text-xs" style="color:var(--muted-foreground)"><span>Serves <?= e($h['serves']) ?></span><span>· Min <?= e($h['min_notice']) ?> notice</span></div>
        <button type="button" data-toggle="h-<?= e($h['id']) ?>" class="text-xs text-left" style="color:var(--primary)">▼ View contents</button>
        <ul id="h-<?= e($h['id']) ?>" class="hidden space-y-1"><?php foreach ($h['contents'] as $c): ?><li class="text-xs flex items-center gap-2" style="color:var(--muted-foreground)"><span class="w-1 h-1 rounded-full shrink-0" style="background:var(--accent)"></span><?= e($c) ?></li><?php endforeach; ?></ul>
        <form method="post" action="<?= $act ?>" class="mt-auto pt-3 border-t flex items-center justify-between" style="border-color:var(--border)">
          <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="hamper"><input type="hidden" name="key" value="<?= e($h['id']) ?>"><input type="hidden" name="return" value="<?= $ret ?>">
          <div><p class="font-display text-2xl font-light"><?= money($h['price']) ?></p><p class="text-xs" style="color:var(--muted-foreground)">Delivery included</p></div>
          <button class="px-4 py-2.5 text-xs font-medium btn-primary">Pre-order</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="border-t pt-12" style="border-color:var(--border)">
    <h2 class="font-display text-2xl font-light mb-2">Bulk Produce</h2>
    <p class="text-sm mb-8" style="color:var(--muted-foreground)">Crates and sacks for restaurants, caterers, and large households. Minimum 3 days notice.</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ($bulk as $b): ?>
      <div class="border" style="border-color:var(--border)">
        <div class="aspect-square overflow-hidden" style="background:var(--muted)"><img src="<?= img($b['image'], 400, 400) ?>" alt="<?= e($b['name']) ?>" class="w-full h-full object-cover"></div>
        <form method="post" action="<?= $act ?>" class="p-4">
          <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="bulk"><input type="hidden" name="key" value="<?= e($b['id']) ?>"><input type="hidden" name="return" value="<?= $ret ?>">
          <p class="font-medium text-sm mb-0.5"><?= e($b['name']) ?></p><p class="text-xs mb-3" style="color:var(--muted-foreground)"><?= e($b['detail']) ?></p>
          <div class="flex items-center justify-between"><p class="font-display text-lg"><?= money($b['price']) ?></p><button class="text-xs px-3 py-1.5 font-medium" style="background:var(--muted)">Add</button></div>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mt-14 p-8 border" style="border-color:var(--border);background:var(--card)">
    <div class="grid md:grid-cols-2 gap-6 items-start">
      <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Custom orders</p><h3 class="font-display text-2xl font-light mb-2">Feeding a larger event?</h3>
        <p class="text-sm leading-relaxed" style="color:var(--muted-foreground)">For events over 100 people or unusual produce requirements, get in touch directly. We coordinate with our farmers for larger harvests and can source almost anything grown in Ghana.</p></div>
      <form onsubmit="event.preventDefault()" class="flex flex-col gap-3">
        <input type="text" placeholder="Your name" class="px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
        <input type="email" placeholder="Email address" class="px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
        <textarea rows="3" placeholder="Tell us about your event and what you need" class="px-4 py-3 text-sm border outline-none bg-transparent resize-none" style="border-color:var(--border)"></textarea>
        <button class="py-3 text-sm font-medium btn-primary">Send enquiry</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
