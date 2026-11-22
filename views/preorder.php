<?php
// Entertaining landing page: hero, how it works, shop by occasion/category, featured,
// "How much do I need?" calculator, and the filterable list of everything.
require_once __DIR__ . '/../controllers/entertaining_controller.php';
$pageTitle = 'Entertaining | Akuafo & Essentials';

$ent        = entertaining();
$occasions  = $ent->occasions();
$categories = $ent->categories();
$dietTags   = ent_dietary_tags();

// ---------- Filters (#browse) ----------
$f = [
    'occasion' => (int)($_GET['occasion'] ?? 0),
    'category' => (int)($_GET['category'] ?? 0),
    'diet'     => array_values(array_intersect(array_keys($dietTags), (array)($_GET['diet'] ?? []))),
    'guests'   => max(0, min(1000, (int)($_GET['guests'] ?? 0))),
];
$filtering = $f['occasion'] || $f['category'] || $f['diet'] || $f['guests'];
$results   = $ent->browse($f);
$guests    = $f['guests'];                      // used by the cards to suggest quantities
$occName   = array_column($occasions, 'name', 'id');
$catName   = array_column($categories, 'name', 'id');

// ---------- Calculator (#calculator) ----------
$calcGuests = max(0, min(1000, (int)($_GET['calc_guests'] ?? 0)));
$portioned  = array_values(array_filter($ent->browse([]), fn($i) => $i['serves']));
$calcCatIds = array_values(array_unique(array_map(fn($i) => (int)$i['category_id'], $portioned)));   // categories the calculator can size
$defaultCats = array_values(array_intersect($calcCatIds, array_map('intval', array_keys(array_intersect($catName, ['Small chops', 'Jollof & rice trays', 'Desserts & cakes', 'Drinks'])))));
$chosenCats = isset($_GET['calc_cats']) ? array_values(array_intersect($calcCatIds, array_map('intval', (array)$_GET['calc_cats']))) : ($defaultCats ?: array_slice($calcCatIds, 0, 3));
$suggestions = [];
if ($calcGuests) {
    foreach ($chosenCats as $cid) {
        foreach ($portioned as $i) {   // first portioned item in the category (featured items come first)
            if ((int)$i['category_id'] === $cid) { $suggestions[] = ['item' => $i, 'qty' => ent_suggest_qty($i['serves'], $calcGuests)]; break; }
        }
    }
}
$calcTotal = array_sum(array_map(fn($s) => $s['item']['price'] * $s['qty'], $suggestions));

$enquiry = $_SESSION['enquiry_msg'] ?? null; unset($_SESSION['enquiry_msg']);
include __DIR__ . '/layout/header.php';
$self = url('views/preorder.php');
$box  = 'px-4 py-3 text-sm border outline-none bg-transparent';
?>
<main>
  <!-- Hero -->
  <section class="relative overflow-hidden" style="min-height:62vh">
    <div class="absolute inset-0">
      <img src="<?= img('photo-1705088293220-c1a22b5d214a', 1800, 1000) ?>" alt="A table spread with Ghanaian dishes" class="w-full h-full object-cover">
      <div class="absolute inset-0" style="background:linear-gradient(to right,rgba(26,25,22,.78) 40%,rgba(26,25,22,.2) 100%)"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-6 flex items-end pb-16" style="min-height:62vh">
      <div class="max-w-xl">
        <p class="text-xs uppercase tracking-widest mb-4 font-medium" style="color:var(--secondary)">Akuafo Entertaining</p>
        <h1 class="font-display text-5xl md:text-6xl font-light text-white leading-none mb-5">Feeding a crowd?<br><em class="not-italic" style="color:var(--secondary)">We've got the table.</em></h1>
        <p class="text-sm mb-8 leading-relaxed max-w-md" style="color:rgba(244,239,228,.85)">Platters, small chops, jollof trays, grills, soups, cakes and hampers for Christmas, weddings, funerals, birthdays, outdoorings and office parties. Pre-order, pick your date, and we deliver.</p>
        <div class="flex flex-wrap gap-3">
          <a href="#occasions" class="px-6 py-3 text-sm font-medium" style="background:var(--accent);color:var(--accent-foreground)">Shop by occasion</a>
          <a href="#calculator" class="px-6 py-3 text-sm font-medium border" style="border-color:rgba(244,239,228,.45);color:var(--primary-foreground)">How much do I need?</a>
        </div>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section class="border-b" style="border-color:var(--border);background:var(--muted)">
    <div class="max-w-7xl mx-auto px-6 py-8 grid md:grid-cols-3 gap-6">
      <?php foreach ([['1', 'Choose', 'Pick dishes, drinks and hampers for your event. Each item shows how many it serves.'],
                      ['2', 'Pick a date', 'Choose your event date and delivery slot. Most items need 24 to 72 hours\' notice.'],
                      ['3', 'We deliver', 'Freshly prepared and delivered to your home or venue, on time.']] as [$n, $t, $d]): ?>
        <div class="flex gap-4 items-start">
          <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center font-display text-lg btn-primary"><?= $n ?></span>
          <div><p class="text-sm font-medium mb-0.5"><?= $t ?></p><p class="text-xs leading-relaxed" style="color:var(--muted-foreground)"><?= $d ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Shop by occasion -->
  <section id="occasions" class="max-w-7xl mx-auto px-6 pt-16">
    <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Shop by occasion</p>
    <h2 class="font-display text-4xl font-light mb-8">What are you celebrating?</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
      <?php foreach ($occasions as $o): ?>
        <a href="<?= $self ?>?occasion=<?= (int)$o['id'] ?>#browse" class="group block">
          <div class="aspect-square overflow-hidden mb-2 relative" style="background:var(--muted)">
            <img src="<?= img($o['image'] ?: 'images/entertaining/placeholder.svg', 400, 400) ?>" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
          </div>
          <p class="text-sm font-medium <?= $f['occasion'] === (int)$o['id'] ? 'underline' : '' ?>"><?= e($o['name']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Shop by category -->
  <section class="max-w-7xl mx-auto px-6 pt-16">
    <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Shop by category</p>
    <h2 class="font-display text-4xl font-light mb-8">Everything for the table</h2>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($categories as $c): ?>
        <a href="<?= $self ?>?category=<?= (int)$c['id'] ?>#browse" class="text-sm px-4 py-2.5 border <?= $f['category'] === (int)$c['id'] ? 'pill-on' : '' ?>" style="border-color:var(--border)"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if (!$filtering && ($featured = $ent->featured())): ?>
  <!-- Featured -->
  <section class="max-w-7xl mx-auto px-6 pt-16">
    <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Featured</p>
    <h2 class="font-display text-4xl font-light mb-8">Party favourites</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ($featured as $i) include __DIR__ . '/layout/ent_card.php'; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- How much do I need? -->
  <section id="calculator" class="max-w-7xl mx-auto px-6 pt-16">
    <div class="p-8 md:p-10" style="background:var(--secondary)">
      <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Guest calculator</p>
      <h2 class="font-display text-3xl md:text-4xl font-light mb-2">How much do I need?</h2>
      <p class="text-sm mb-6 max-w-xl" style="color:var(--muted-foreground)">Tell us how many guests you're expecting and what you'd like to serve. We'll suggest quantities you can adjust before adding to your cart.</p>
      <form method="get" action="<?= $self ?>#calculator" class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end gap-4">
          <label class="flex flex-col gap-1 text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Number of guests
            <input type="number" name="calc_guests" min="1" max="1000" required value="<?= $calcGuests ?: '' ?>" placeholder="e.g. 40" class="<?= $box ?> w-40" style="border-color:var(--foreground);background:var(--card)">
          </label>
          <button class="px-6 py-3 text-sm font-medium btn-primary">Suggest quantities</button>
        </div>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($calcCatIds as $cid): ?>
            <label class="pick relative text-xs px-3 py-2 border cursor-pointer" style="border-color:var(--foreground);background:var(--card)">
              <input type="checkbox" name="calc_cats[]" value="<?= $cid ?>" <?= in_array($cid, $chosenCats, true) ? 'checked' : '' ?>><?= e($catName[$cid] ?? '') ?>
            </label>
          <?php endforeach; ?>
        </div>
      </form>

      <?php if ($calcGuests && $suggestions): ?>
      <form method="post" action="<?= url('actions/cart_action.php') ?>" class="mt-8" style="background:var(--card)">
        <input type="hidden" name="action" value="ent_many"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
        <div class="p-5 border-b" style="border-color:var(--border)"><p class="font-display text-xl">Suggested for <?= $calcGuests ?> guests</p></div>
        <?php foreach ($suggestions as ['item' => $i, 'qty' => $q]): ?>
          <div class="flex items-center gap-4 p-4 border-b" style="border-color:var(--border)">
            <img src="<?= img($i['image'], 160, 120) ?>" alt="" class="w-16 h-12 object-cover shrink-0">
            <div class="flex-1 min-w-0">
              <a href="<?= url('views/entertaining_item.php?id=' . $i['id'] . '&guests=' . $calcGuests) ?>" class="text-sm font-medium"><?= e($i['name']) ?></a>
              <p class="text-xs" style="color:var(--muted-foreground)"><?= e($i['category_name']) ?> · serves <?= (int)$i['serves'] ?> each · <?= money($i['price']) ?></p>
            </div>
            <input type="number" name="qty[<?= (int)$i['id'] ?>]" min="0" max="99" value="<?= $q ?>" aria-label="Quantity of <?= e($i['name']) ?>" class="w-16 px-2 py-2 text-sm border bg-transparent text-center" style="border-color:var(--border)">
          </div>
        <?php endforeach; ?>
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
          <p class="text-sm">Estimated total <strong class="font-display text-xl ml-1"><?= money($calcTotal) ?></strong> <span class="text-xs" style="color:var(--muted-foreground)">(before delivery)</span></p>
          <button class="px-6 py-3 text-sm font-medium btn-primary">Add all to cart</button>
        </div>
      </form>
      <?php elseif ($calcGuests): ?>
        <p class="text-sm mt-6">Tick at least one category above.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Browse + filters -->
  <section id="browse" class="max-w-7xl mx-auto px-6 py-16">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
      <div>
        <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">The full menu</p>
        <h2 class="font-display text-4xl font-light">
          <?= $f['occasion'] ? e($occName[$f['occasion']] ?? 'Occasion') : ($f['category'] ? e($catName[$f['category']] ?? 'Category') : 'Everything') ?>
          <span class="text-base" style="color:var(--muted-foreground)">(<?= count($results) ?>)</span>
        </h2>
      </div>
      <?php if ($filtering): ?><a href="<?= $self ?>#browse" class="text-xs underline">Clear filters</a><?php endif; ?>
    </div>

    <form method="get" action="<?= $self ?>#browse" class="grid md:grid-cols-5 gap-3 mb-10 p-4 border items-end" style="border-color:var(--border)">
      <label class="flex flex-col gap-1 text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Occasion
        <select name="occasion" class="<?= $box ?>" style="border-color:var(--border)"><option value="0">Any occasion</option>
          <?php foreach ($occasions as $o): ?><option value="<?= (int)$o['id'] ?>" <?= $f['occasion'] === (int)$o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="flex flex-col gap-1 text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Category
        <select name="category" class="<?= $box ?>" style="border-color:var(--border)"><option value="0">Any category</option>
          <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $f['category'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="flex flex-col gap-1 text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Guests
        <input type="number" name="guests" min="1" max="1000" value="<?= $f['guests'] ?: '' ?>" placeholder="e.g. 40" class="<?= $box ?>" style="border-color:var(--border)">
      </label>
      <fieldset class="flex flex-col gap-1">
        <legend class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Dietary</legend>
        <div class="flex flex-wrap gap-x-3 gap-y-1">
          <?php foreach ($dietTags as $k => $label): ?>
            <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" name="diet[]" value="<?= e($k) ?>" <?= in_array($k, $f['diet'], true) ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <button class="px-6 py-3 text-sm font-medium btn-primary">Show results</button>
    </form>

    <?php if ($f['guests']): ?><p class="text-xs mb-6" style="color:var(--muted-foreground)">Showing food that can be sized for <?= $f['guests'] ?> guests, with a suggested quantity on each item.</p><?php endif; ?>
    <?php if ($results): ?>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($results as $i) include __DIR__ . '/layout/ent_card.php'; ?>
      </div>
    <?php else: ?>
      <p class="text-sm text-center py-16 border" style="border-color:var(--border);color:var(--muted-foreground)">Nothing matches those filters. <a href="<?= $self ?>#browse" class="underline">Clear filters</a></p>
    <?php endif; ?>
  </section>

  <!-- Custom enquiry -->
  <section id="enquiry" class="max-w-7xl mx-auto px-6 pb-16">
    <div class="p-8 border" style="border-color:var(--border);background:var(--card)">
      <div class="grid md:grid-cols-2 gap-6 items-start">
        <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Custom orders</p><h3 class="font-display text-2xl font-light mb-2">Feeding a larger event?</h3>
          <p class="text-sm leading-relaxed" style="color:var(--muted-foreground)">For events over 100 people, a special menu or produce we don't list, tell us what you need. We'll reply by email.</p></div>
        <form method="post" action="<?= url('actions/enquiry_action.php') ?>" class="flex flex-col gap-3">
          <?php if ($enquiry): ?><p class="text-sm p-3" style="background:var(--muted);color:<?= $enquiry[0] ? 'var(--primary)' : 'var(--accent)' ?>"><?= e($enquiry[1]) ?></p><?php endif; ?>
          <input type="text" name="name" required maxlength="100" placeholder="Your name" value="<?= e($_SESSION['customer_name'] ?? '') ?>" class="<?= $box ?>" style="border-color:var(--border)">
          <input type="email" name="email" required maxlength="150" placeholder="Email address" value="<?= e($_SESSION['customer_email'] ?? '') ?>" class="<?= $box ?>" style="border-color:var(--border)">
          <textarea name="message" required rows="3" maxlength="2000" placeholder="Tell us about your event: date, number of guests and what you need" class="<?= $box ?> resize-none" style="border-color:var(--border)"></textarea>
          <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
          <button class="py-3 text-sm font-medium btn-primary">Send enquiry</button>
        </form>
      </div>
    </div>
  </section>
</main>
<?php include __DIR__ . '/layout/footer.php'; ?>
