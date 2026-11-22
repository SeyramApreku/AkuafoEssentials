<?php
require_once __DIR__ . '/../controllers/entertaining_controller.php';

$i = entertaining()->item((int)($_GET['id'] ?? 0));
if (!$i || !(int)$i['is_available']) redirect(url('views/preorder.php'));

$guests  = max(0, min(1000, (int)($_GET['guests'] ?? 0)));
$suggest = ent_suggest_qty($i['serves'], $guests);
$diet    = ent_dietary_tags();
$occName = array_column(entertaining()->occasions(), 'name', 'id');
$related = entertaining()->related($i);
$pageTitle = $i['name'] . ' | Entertaining | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-10">
  <a href="<?= url('views/preorder.php?category=' . (int)$i['category_id']) ?>#browse" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← <?= e($i['category_name']) ?></a>
  <div class="grid md:grid-cols-2 gap-12">
    <div class="relative" style="background:var(--muted)">
      <img src="<?= img($i['image'], 1000, 750) ?>" alt="<?= e($i['name']) ?>" class="w-full object-cover" style="aspect-ratio:4/3">
      <?php if (!empty($i['badge'])): ?><span class="absolute top-4 left-4 text-xs px-2 py-1 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($i['badge']) ?></span><?php endif; ?>
    </div>

    <div>
      <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Entertaining · <?= e($i['category_name']) ?></p>
      <h1 class="font-display text-4xl font-light mb-3"><?= e($i['name']) ?></h1>
      <p class="text-3xl font-display mb-5"><?= money($i['price']) ?></p>

      <div class="grid grid-cols-2 gap-3 mb-6 text-sm">
        <div class="p-3 border" style="border-color:var(--border)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Serves</p><?= $i['serves'] ? (int)$i['serves'] . ' people' : 'Bulk, not portioned' ?></div>
        <div class="p-3 border" style="border-color:var(--border)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Minimum notice</p>Order <?= e(notice_label($i['notice_hours'])) ?> ahead</div>
      </div>

      <?php if ($i['dietary']): ?>
        <div class="flex flex-wrap gap-1.5 mb-5">
          <?php foreach ($i['dietary'] as $d): ?><span class="text-xs px-2 py-1 border" style="border-color:var(--border)"><?= e($diet[$d] ?? $d) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($i['description'])): ?><p class="text-sm leading-relaxed mb-5" style="color:var(--muted-foreground)"><?= nl2br(e($i['description'])) ?></p><?php endif; ?>

      <?php if ($i['contents']): ?>
        <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--muted-foreground)">What's included</p>
        <ul class="space-y-1 mb-5"><?php foreach ($i['contents'] as $c): ?><li class="text-sm flex items-center gap-2"><span class="w-1 h-1 rounded-full shrink-0" style="background:var(--accent)"></span><?= e($c) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>

      <?php if ($i['occasion_ids']): ?>
        <p class="text-xs mb-6" style="color:var(--muted-foreground)">Popular for:
          <?php foreach ($i['occasion_ids'] as $k => $oid): ?><a href="<?= url('views/preorder.php?occasion=' . $oid) ?>#browse" class="underline"><?= e($occName[$oid] ?? '') ?></a><?= $k < count($i['occasion_ids']) - 1 ? ', ' : '' ?><?php endforeach; ?>
        </p>
      <?php endif; ?>

      <form method="post" action="<?= url('actions/cart_action.php') ?>" class="p-5 border mb-4" style="border-color:var(--border);background:var(--card)">
        <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="ent"><input type="hidden" name="key" value="<?= (int)$i['id'] ?>">
        <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
        <?php if ($i['serves']): ?>
          <label class="flex items-center justify-between gap-3 text-sm mb-4">
            <span>How many guests?</span>
            <input type="number" id="guests" min="1" max="1000" value="<?= $guests ?: '' ?>" placeholder="e.g. 40" class="w-24 px-3 py-2 text-sm border bg-transparent" style="border-color:var(--border)">
          </label>
          <p id="suggestion" class="text-xs mb-4 p-2" style="background:var(--secondary);<?= $suggest ? '' : 'display:none' ?>">For <span id="sg-guests"><?= $guests ?></span> guests we suggest <strong id="sg-qty"><?= (int)$suggest ?></strong> (each serves <?= (int)$i['serves'] ?>).</p>
        <?php endif; ?>
        <div class="flex items-center gap-4">
          <label class="text-sm" for="qty">Quantity</label>
          <input type="number" id="qty" name="qty" min="1" max="99" value="<?= $suggest ?: 1 ?>" class="w-20 px-3 py-2 text-sm border bg-transparent text-center" style="border-color:var(--border)">
          <button class="flex-1 py-3 text-sm font-medium btn-primary">Add to cart · <span id="line-total"><?= money($i['price'] * ($suggest ?: 1)) ?></span></button>
        </div>
      </form>
      <p class="text-xs" style="color:var(--muted-foreground)">You'll choose your event date and delivery slot at checkout. Entertaining orders are checked out separately from groceries.</p>
    </div>
  </div>

  <?php if ($related): ?>
  <div class="mt-16 border-t pt-10" style="border-color:var(--border)">
    <h2 class="font-display text-2xl font-light mb-6">More <?= e(strtolower($i['category_name'])) ?></h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php $main = $i; foreach ($related as $i) include __DIR__ . '/layout/ent_card.php'; $i = $main; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
(function () {
  var price = <?= json_encode((float)$i['price']) ?>, serves = <?= json_encode((int)$i['serves']) ?>;
  var qty = document.getElementById('qty'), guests = document.getElementById('guests');
  function money(n) { return 'GH₵ ' + (n % 1 === 0 ? n : n.toFixed(2)); }
  function total() { document.getElementById('line-total').textContent = money(price * Math.max(1, parseInt(qty.value || 1))); }
  qty.addEventListener('input', total);
  if (guests) guests.addEventListener('input', function () {
    var g = parseInt(guests.value || 0), box = document.getElementById('suggestion');
    if (g > 0 && serves > 0) {
      var q = Math.ceil(g / serves);
      document.getElementById('sg-guests').textContent = g; document.getElementById('sg-qty').textContent = q;
      box.style.display = ''; qty.value = Math.min(99, q); total();
    } else { box.style.display = 'none'; }
  });
})();
</script>
<?php include __DIR__ . '/layout/footer.php'; ?>
