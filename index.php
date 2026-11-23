<?php
require_once __DIR__ . '/controllers/catalog_controller.php';
$pageTitle = 'Akuafo & Essentials';
$boxes = catalog()->boxes(); $products = array_slice(catalog()->products(), 0, 4);
$recipes = catalog()->recipes(); $farmers = array_slice(catalog()->farmers(), 0, 3);
include __DIR__ . '/views/layout/header.php';
?>
<main>
  <section class="relative overflow-hidden" style="min-height:88vh">
    <div class="absolute inset-0">
      <img src="<?= img('photo-1665904285523-47c0a6fdfc0e', 1800, 1100) ?>" alt="Baskets of fresh produce at a Ghanaian market" class="w-full h-full object-cover">
      <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(26,25,22,.74) 42%,rgba(26,25,22,.18) 100%)"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-6 flex items-end pb-20" style="min-height:88vh">
      <div class="max-w-xl">
        <p class="text-xs uppercase tracking-widest mb-4 font-medium" style="color:var(--secondary)">Freshness You Can Trace, From Source to Table</p>
        <h1 class="font-display text-5xl md:text-7xl font-light text-white leading-none mb-6">Straight from<br><em class="not-italic font-normal" style="color:var(--secondary)">the field</em><br>to your pot.</h1>
        <p class="text-sm mb-8 leading-relaxed max-w-md" style="color:rgba(244,239,228,.8)">Akuafo &amp; Essentials connects Ghanaian farmers directly with households across the country. Weekly boxes. Pre-orders for celebrations. Delivery booked on your schedule.</p>
        <div class="flex flex-wrap gap-3">
          <a href="<?= url('views/boxes.php') ?>" class="px-6 py-3 text-sm font-medium" style="background:var(--accent);color:var(--accent-foreground)">Subscribe to a Box</a>
          <a href="<?= url('views/shop.php') ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:rgba(244,239,228,.4);color:var(--primary-foreground)">Shop Produce</a>
        </div>
      </div>
    </div>
  </section>

  <section class="border-b border-t" style="border-color:var(--border);background:var(--muted)">
    <div class="max-w-7xl mx-auto px-6 py-8 grid grid-cols-2 md:grid-cols-4 gap-6">
      <?php foreach ([['Direct from Local Farmers','No market chain markup'],['Weekly box delivery','Recurring mobile money or card'],['Fully traceable','Farm, region, and farmer named'],['Pre-order for events','Weddings, Christmas, gatherings']] as [$l,$d]): ?>
        <div class="flex flex-col gap-1"><span class="text-sm font-medium"><?= $l ?></span><span class="text-xs" style="color:var(--muted-foreground)"><?= $d ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-20">
    <div class="flex items-end justify-between mb-10">
      <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Weekly Subscriptions</p><h2 class="font-display text-4xl font-light">Choose your box</h2></div>
      <a href="<?= url('views/boxes.php') ?>" class="text-sm font-medium hidden md:block" style="color:var(--primary)">See all boxes →</a>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
      <?php foreach ($boxes as $b): ?>
      <a href="<?= url('views/boxes.php') ?>" class="group border block" style="border-color:var(--border)">
        <div class="aspect-[4/3] overflow-hidden relative" style="background:var(--muted)">
          <img src="<?= img($b['image'], 800, 600) ?>" alt="<?= e($b['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
          <div class="absolute inset-0" style="background:linear-gradient(to top,<?= e($b['color']) ?>CC 0%,transparent 60%)"></div>
          <div class="absolute bottom-4 left-4"><p class="font-display text-xl font-light text-white"><?= e($b['name']) ?></p><p class="text-xs text-white/70 mt-0.5"><?= e($b['tagline']) ?></p></div>
        </div>
        <div class="p-4">
          <p class="text-xs mb-3" style="color:var(--muted-foreground)">From <?= money($b['sizes'][0]['price']) ?>/week · Free delivery</p>
          <div class="flex gap-2"><?php foreach ($b['sizes'] as $s): ?><span class="text-xs px-2 py-1" style="background:var(--muted);color:var(--muted-foreground)"><?= e($s['label']) ?></span><?php endforeach; ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mx-6 mb-12 md:mx-auto max-w-7xl">
  <div class="p-8 md:p-10 grid md:grid-cols-2 gap-8 items-center" style="background:var(--secondary)">
    <div>
      <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Akuafo Membership</p>
      <h3 class="font-display text-3xl font-light mb-2">Monthly vouchers, delivery credit and first access</h3>
      <p class="text-sm leading-relaxed mb-5" style="color:var(--muted-foreground)">Restaurant vouchers from our partners, early access to new stock and exclusive chef recipes. Choose Silver, Gold or Platinum.</p>
      <a href="<?= url('views/membership.php') ?>" class="inline-block px-6 py-3 text-sm font-medium btn-primary">Explore membership</a>
    </div>
    <div class="grid grid-cols-3 gap-3">
      <?php foreach (membership_tiers() as $t): ?>
        <div class="p-3" style="background:rgba(255,255,255,.5)">
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($t['name']) ?></p>
          <p class="text-sm font-medium mt-0.5"><?= money($t['price']) ?>/mo</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

  <section class="border-t" style="border-color:var(--border)">
    <div class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-12 items-center">
      <div class="max-w-sm">
        <p class="text-xs uppercase tracking-widest mb-4" style="color:var(--accent)">Pre-order</p>
        <h2 class="font-display text-4xl font-light leading-snug mb-4">Feeding a celebration?</h2>
        <p class="text-sm leading-relaxed mb-6" style="color:var(--muted-foreground)">Christmas hampers, wedding packs, family gathering boxes, curated in bulk, delivered to your venue or door. Book 5-14 days ahead.</p>
        <div class="flex flex-wrap gap-2 mb-8"><?php foreach (['Christmas','Weddings & Events','Family Gatherings','Bulk Produce'] as $t): ?><span class="text-xs px-3 py-1.5 border" style="border-color:var(--border)"><?= e($t) ?></span><?php endforeach; ?></div>
        <a href="<?= url('views/preorder.php') ?>" class="text-sm font-medium px-5 py-2.5 border inline-block" style="border-color:var(--primary);color:var(--primary)">Browse pre-orders</a>
      </div>
      <img src="<?= img('photo-1705088293220-c1a22b5d214a', 900, 700) ?>" alt="A table spread with Ghanaian dishes" class="w-full object-cover" style="aspect-ratio:4/3">
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-16 border-t" style="border-color:var(--border)">
    <div class="flex items-end justify-between mb-10">
      <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Currently in season</p><h2 class="font-display text-4xl font-light">What's fresh this week</h2></div>
      <a href="<?= url('views/shop.php') ?>" class="text-sm font-medium hidden md:block" style="color:var(--primary)">View all →</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ($products as $p) include __DIR__ . '/views/layout/product_card.php'; ?>
    </div>
  </section>

  <section class="border-t" style="border-color:var(--border)">
    <div class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-12 items-center">
      <img src="<?= img('photo-1665332195309-9d75071138f0', 900, 700) ?>" alt="A Ghanaian stew with fresh vegetables" class="w-full object-cover" style="aspect-ratio:4/3">
      <div class="max-w-sm">
        <p class="text-xs uppercase tracking-widest mb-4" style="color:var(--accent)">Recipes</p>
        <h2 class="font-display text-4xl font-light leading-snug mb-4">Cook with what you've grown to love.</h2>
        <p class="text-sm leading-relaxed mb-6" style="color:var(--muted-foreground)">Every product comes with at least one recipe, traditional Ghanaian cooking tested in our kitchen using the exact varieties we sell.</p>
        <div class="flex flex-wrap gap-2 mb-8"><?php foreach ($recipes as $r): ?><a href="<?= url('views/recipes.php?id=' . $r['id']) ?>" class="text-xs px-3 py-1.5 border" style="border-color:var(--border)"><?= e($r['name']) ?></a><?php endforeach; ?></div>
        <a href="<?= url('views/recipes.php') ?>" class="text-sm font-medium px-5 py-2.5 border inline-block" style="border-color:var(--primary);color:var(--primary)">Browse all recipes</a>
      </div>
    </div>
  </section>

  <section class="py-20" style="background:var(--primary)">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex items-end justify-between mb-12">
        <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--secondary)">Provenance</p><h2 class="font-display text-4xl font-light" style="color:var(--primary-foreground)">The farmers behind every delivery</h2></div>
        <a href="<?= url('views/farms.php') ?>" class="text-sm hidden md:block" style="color:var(--secondary)">All farmers →</a>
      </div>
      <div class="grid md:grid-cols-3 gap-6">
        <?php foreach ($farmers as $f): ?>
        <a href="<?= url('views/farms.php?id=' . $f['id']) ?>" class="group block">
          <div class="aspect-[4/3] overflow-hidden mb-4"><img src="<?= img($f['image'], 600, 700) ?>" alt="<?= e($f['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"></div>
          <p class="text-xs mb-1" style="color:var(--secondary)"><?= e($f['region']) ?></p>
          <p class="font-medium text-base mb-1" style="color:var(--primary-foreground)"><?= e($f['name']) ?></p>
          <p class="text-xs" style="color:rgba(244,239,228,.65)"><?= e(mb_substr($f['story'], 0, 100)) ?>…</p>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="mx-6 my-12 md:mx-auto max-w-7xl">
    <div class="p-8 md:p-12" style="background:var(--muted)">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-10">
        <div>
          <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Our Partners</p>
          <h2 class="font-display text-3xl md:text-4xl font-light mb-2">Cooked in the kitchens you love</h2>
          <p class="text-sm max-w-md" style="color:var(--muted-foreground)">Restaurants, hotels and caterers across Ghana buy straight from our farmers.</p>
        </div>
        <a href="<?= url('views/testimonials.php') ?>" class="inline-block px-6 py-3 text-sm font-medium btn-primary whitespace-nowrap self-start md:self-auto">Read Their Testimonials</a>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-px" style="background:var(--border)">
        <?php foreach (partners() as $p): ?>
<div class="p-5" style="background:var(--background)">
  <?php if (!empty($p['image'])): ?><img src="<?= img($p['image']) ?>" alt="<?= e($p['name']) ?>" class="h-12 object-contain mb-3"><?php endif; ?>
  <p class="font-display text-lg font-light leading-snug mb-1"><?= e(($p['restaurant_name'] ?? '') ?: $p['name']) ?></p>
  <p class="text-xs uppercase tracking-widest" style="color:var(--accent)"><?= e($p['type']) ?></p>
</div>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-20">
    <div class="grid md:grid-cols-2 gap-10 items-center border-t pt-16" style="border-color:var(--border)">
      <div>
          

        <p class="text-xs uppercase tracking-widest mb-3" style="color:var(--accent)">Stay in the loop</p>
        <h2 class="font-display text-3xl font-light mb-3">Harvest updates. Recipe drops. Farm stories.</h2>
        <p class="text-sm leading-relaxed" style="color:var(--muted-foreground)">A short weekly note on what's in season, which farmer we're visiting, and what the test kitchen has been cooking.</p>
      </div>
      <form onsubmit="event.preventDefault()" class="flex flex-col sm:flex-row gap-3">
        <input type="email" placeholder="Your email address" class="flex-1 px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
        <button class="px-5 py-3 text-sm font-medium btn-primary whitespace-nowrap">Sign up</button>
      </form>
    </div>
  </section>
</main>
<?php include __DIR__ . '/views/layout/footer.php'; ?>