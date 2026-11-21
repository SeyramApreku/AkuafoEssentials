<?php

require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'Build Your Box | Akuafo & Essentials';
$products = catalog()->products();
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <a href="<?= url('views/boxes.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← Weekly boxes</a>
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Custom Subscription</p>
  <h1 class="font-display text-4xl font-light mb-2">Build your own box</h1>
  <p class="text-sm mb-10 max-w-xl" style="color:var(--muted-foreground)">Choose the produce and quantities you want, then pick how often it arrives. Free delivery on every box subscription.</p>

  <form method="post" action="<?= url('actions/cart_action.php') ?>" class="grid lg:grid-cols-3 gap-10 items-start">
    <input type="hidden" name="action" value="custom">
    <input type="hidden" name="return" value="<?= e(url('views/custom_box.php')) ?>">

    <div class="lg:col-span-2 grid sm:grid-cols-2 gap-4">
      <?php foreach ($products as $p): ?>
      <div data-row data-price="<?= (float)$p['price'] ?>" class="border flex gap-4 p-3" style="border-color:var(--border)">
        <img src="<?= img($p['image'], 200, 200) ?>" alt="<?= e($p['name']) ?>" class="w-20 h-20 object-cover shrink-0">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium leading-tight"><?= e($p['name']) ?></p>
          <p class="text-xs mb-2" style="color:var(--muted-foreground)"><?= e($p['unit']) ?> · <?= e($p['farmer']) ?></p>
          <div class="flex items-center justify-between">
            <p class="text-sm font-display"><?= money($p['price']) ?></p>
            <div class="flex items-center border text-xs" style="border-color:var(--border)">
              <button type="button" data-step="-1" class="px-2.5 py-1">−</button>
              <span data-qty-label class="px-2.5 py-1 border-l border-r w-8 text-center" style="border-color:var(--border)">0</span>
              <button type="button" data-step="1" class="px-2.5 py-1">+</button>
            </div>
          </div>
          <input type="hidden" name="qty[<?= $p['id'] ?>]" value="0" data-qty>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <aside class="border p-6 lg:sticky lg:top-24" style="border-color:var(--border);background:var(--card)">
      <h2 class="font-display text-xl font-light mb-4">How often?</h2>
      <div class="flex flex-col gap-2 mb-6">
        <?php foreach (frequencies() as $key => $label): ?>
        <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)">
          <input type="radio" name="frequency" value="<?= $key ?>" <?= $key === 'weekly' ? 'checked' : '' ?>><?= e($label) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <h2 class="font-display text-xl font-light mb-4">Which day?</h2>
      <div class="grid grid-cols-3 gap-2 mb-6">
        <?php foreach (delivery_days() as $k => $d): ?>
        <label class="pill relative py-2.5 text-xs border text-center cursor-pointer" style="border-color:var(--border)">
          <input type="radio" name="day" value="<?= $d ?>" <?= $k === 0 ? 'checked' : '' ?>><?= substr($d, 0, 3) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <h2 class="font-display text-xl font-light mb-4">What time?</h2>
      <div class="flex flex-col gap-2 mb-6">
        <?php foreach (time_windows() as $k => $t): ?>
        <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)">
          <input type="radio" name="window" value="<?= e($t) ?>" <?= $k === 0 ? 'checked' : '' ?>><?= e($t) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="border-t pt-4 mb-4 text-sm space-y-1.5" style="border-color:var(--border)">
        <div class="flex justify-between"><span style="color:var(--muted-foreground)">Items</span><span id="item-count">0</span></div>
        <div class="flex justify-between font-medium"><span>Per delivery</span><span id="box-total" class="font-display">GH₵ 0</span></div>
      </div>
      <button id="box-submit" disabled class="w-full py-3 text-sm font-medium btn-primary disabled:opacity-40">Add custom box</button>
    </aside>
  </form>
</div>

<script>
const rows = document.querySelectorAll('[data-row]');
function recalc() {
  let total = 0, count = 0;
  rows.forEach(r => {
    const q = parseInt(r.querySelector('[data-qty]').value);
    total += q * parseFloat(r.dataset.price); count += q;
  });
  document.getElementById('box-total').textContent = 'GH₵ ' + total;
  document.getElementById('item-count').textContent = count;
  document.getElementById('box-submit').disabled = count === 0;
}
rows.forEach(r => r.querySelectorAll('[data-step]').forEach(b => b.addEventListener('click', () => {
  const input = r.querySelector('[data-qty]');
  input.value = Math.max(0, parseInt(input.value) + parseInt(b.dataset.step));
  r.querySelector('[data-qty-label]').textContent = input.value;
  recalc();
})));
</script>
<?php include __DIR__ . '/layout/footer.php'; ?>