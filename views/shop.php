<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'The Produce | Akuafo & Essentials';

// Search + filters (all can be combined; each choice keeps the others)
$sorts = ['featured' => 'Featured', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name: A to Z'];
$f = [
    'q'        => mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60),
    'category' => (string)($_GET['category'] ?? 'All'),
    'region'   => (string)($_GET['region'] ?? 'All Regions'),
    'farmer'   => mb_substr(trim((string)($_GET['farmer'] ?? '')), 0, 80),
    'sort'     => isset($sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'featured',
    'in_stock' => !empty($_GET['in_stock']),
];
// search() lives in classes/catalog_class.php; if an older copy of that file is on the server,
// fall back to the plain list instead of breaking the shop.
$products  = method_exists(catalog(), 'search') ? catalog()->search($f) : catalog()->products($f['category'], $f['region'], $f['farmer']);
$filtering = $f['q'] !== '' || $f['category'] !== 'All' || $f['region'] !== 'All Regions' || $f['farmer'] !== '' || $f['in_stock'];
// Link that changes one filter and keeps the rest
$link = function (array $change) use ($f) {
    $q = array_filter(array_merge($f, $change), fn($v, $k) => !($v === '' || $v === false || ($k === 'category' && $v === 'All')
        || ($k === 'region' && $v === 'All Regions') || ($k === 'sort' && $v === 'featured')), ARRAY_FILTER_USE_BOTH);
    if (!empty($q['in_stock'])) $q['in_stock'] = 1;
    return url('views/shop.php') . ($q ? '?' . http_build_query($q) : '');
};
$box = 'text-xs px-3 py-2 border bg-transparent';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Directly from Ghanaian farms</p>
  <h1 class="font-display text-4xl font-light mb-8">The Produce</h1>

  <form method="get" action="<?= url('views/shop.php') ?>" id="shop-filters" class="mb-10">
    <!-- Search bar -->
    <div class="flex mb-5 border" style="border-color:var(--foreground);background:var(--card)">
      <label for="shop-q" class="sr-only">Search produce</label>
      <span class="flex items-center pl-4" aria-hidden="true" style="color:var(--muted-foreground)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </span>
      <input id="shop-q" type="search" name="q" value="<?= e($f['q']) ?>" maxlength="60" autocomplete="off"
             placeholder="Search produce, farmer or region, e.g. yam, Kofi Asante, Volta"
             class="flex-1 px-3 py-3.5 text-sm outline-none bg-transparent">
      <button class="px-6 text-sm font-medium btn-primary">Search</button>
    </div>

    <!-- Category pills (keep the other filters) -->
    <div class="flex flex-wrap gap-2 mb-4">
      <?php foreach (catalog()->categories() as $c): ?>
        <a href="<?= e($link(['category' => $c])) ?>" class="text-xs px-3 py-1.5 border <?= $f['category'] === $c ? 'pill-on' : '' ?>" style="border-color:var(--border)"><?= e($c) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Region, farmer, sort, stock -->
    <div class="flex flex-wrap items-center gap-3">
      <input type="hidden" name="category" value="<?= e($f['category']) ?>">
      <?php $farmerList = method_exists(catalog(), 'productFarmers') ? catalog()->productFarmers() : [];
            if ($f['farmer'] !== '' && !in_array($f['farmer'], $farmerList, true)) $farmerList[] = $f['farmer']; ?>
      <label class="sr-only" for="shop-farmer">Farmer</label>
      <select id="shop-farmer" name="farmer" class="<?= $box ?>" style="border-color:var(--border)" data-autosubmit>
        <option value="">All farmers</option>
        <?php foreach ($farmerList as $fm): ?><option value="<?= e($fm) ?>" <?= $fm === $f['farmer'] ? 'selected' : '' ?>><?= e($fm) ?></option><?php endforeach; ?>
      </select>
      <label class="sr-only" for="shop-region">Region</label>
      <select id="shop-region" name="region" class="<?= $box ?>" style="border-color:var(--border)" data-autosubmit>
        <?php foreach (catalog()->regions() as $r): ?><option <?= $r === $f['region'] ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
      </select>
      <label class="sr-only" for="shop-sort">Sort by</label>
      <select id="shop-sort" name="sort" class="<?= $box ?>" style="border-color:var(--border)" data-autosubmit>
        <?php foreach ($sorts as $k => $label): ?><option value="<?= $k ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
      <label class="flex items-center gap-2 text-xs cursor-pointer"><input type="checkbox" name="in_stock" value="1" <?= $f['in_stock'] ? 'checked' : '' ?> data-autosubmit> In stock only</label>
      <span class="text-xs ml-auto" style="color:var(--muted-foreground)" aria-live="polite">
        <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?>
        <?php if ($filtering): ?> · <a href="<?= url('views/shop.php') ?>" class="underline">Clear all</a><?php endif; ?>
      </span>
    </div>
  </form>

  <?php if ($f['farmer'] !== '' || $f['q'] !== ''): ?>
    <p class="text-sm mb-6 p-3 flex flex-wrap items-center gap-3" style="background:var(--muted)">
      <?php if ($f['q'] !== ''): ?>Results for <strong>"<?= e($f['q']) ?>"</strong><?php endif; ?>
      <?php if ($f['farmer'] !== ''): ?><?= $f['q'] !== '' ? ' · ' : '' ?>Grown by <strong><?= e($f['farmer']) ?></strong> <a href="<?= e($link(['farmer' => ''])) ?>" class="text-xs underline">(any farmer)</a><?php endif; ?>
    </p>
  <?php endif; ?>

  <?php if (!$products): ?>
    <div class="text-center py-20 border" style="border-color:var(--border)">
      <p class="font-display text-2xl font-light mb-2">No produce matches<?= $f['q'] !== '' ? ' "' . e($f['q']) . '"' : ' your filters' ?></p>
      <p class="text-sm mb-6" style="color:var(--muted-foreground)">Try a shorter word, another category, or clear the filters.</p>
      <a href="<?= url('views/shop.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">Show all produce</a>
    </div>
  <?php else: ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
    <?php foreach ($products as $p) include __DIR__ . '/layout/product_card.php'; ?>
  </div>
  <?php endif; ?>
</div>
<script>
  // Region, sort and "in stock" apply straight away.
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { document.getElementById('shop-filters').submit(); });
  });
</script>
<?php include __DIR__ . '/layout/footer.php'; ?>
