<?php
require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'Weekly Boxes | Akuafo & Essentials';
$boxes = catalog()->boxes();
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Weekly Subscriptions</p>
  <h1 class="font-display text-4xl font-light mb-2">Weekly Produce Boxes</h1>
  <p class="text-sm mb-3 max-w-xl" style="color:var(--muted-foreground)">A curated selection of Ghanaian produce, delivered weekly on a schedule you choose. Pause or cancel anytime. Free delivery included on all box subscriptions.</p>
  <div class="flex items-center gap-3 mb-10 p-3 border text-xs" style="border-color:var(--border);background:var(--muted)">
    <!--<span class="font-medium">✦ Akuafo Delivery Pass</span> -->
    <a href="<?= url('views/custom_box.php') ?>" class="block mb-10 p-5 border text-sm font-medium" style="border-color:var(--primary);color:var(--primary)">Want something different? Build your own box →</a>
     <!--<span style="color:var(--muted-foreground)">Box subscribers get the Delivery Pass at 20% off, <?= money(round(PASS_PRICE * 0.8)) ?>/month for unlimited free delivery on all orders.</span> -->
  </div>


  <div class="grid md:grid-cols-3 gap-8">
    <?php foreach ($boxes as $b): $s0 = $b['sizes'][0]; ?>
    <div data-box class="border flex flex-col" style="border-color:var(--border)">
      <div class="relative overflow-hidden" style="aspect-ratio:4/3;background:var(--muted)">
        <img src="<?= img($b['image'], 800, 600) ?>" alt="<?= e($b['name']) ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0" style="background:linear-gradient(to top,<?= e($b['color']) ?>CC 0%,transparent 55%)"></div>
        <p class="absolute bottom-4 left-4 font-display text-xl text-white font-light"><?= e($b['name']) ?></p>
      </div>


      <div class="p-5 flex flex-col flex-1 gap-4">
        <p class="text-xs leading-relaxed" style="color:var(--muted-foreground)"><?= e($b['tagline']) ?></p>
        <div>


          <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--muted-foreground)">Size</p>

          <div class="flex gap-2">
            <?php foreach ($b['sizes'] as $i => $s): ?>
      <button type="button" data-size="<?= $i ?>" data-size-price="<?= (int)$s['price'] ?>" data-size-items="<?= e($s['items']) ?>" data-size-contents="<?= e(json_encode($s['contents'], JSON_UNESCAPED_UNICODE)) ?>" class="flex-1 py-2 text-xs border <?= $i === 0 ? 'pill-on' : '' ?>" style="border-color:var(--border)">
              <div class="font-medium"><?= e($s['label']) ?></div><div class="text-xs opacity-70"><?= e($s['description']) ?></div>
            </button>
            <?php endforeach; ?>
          </div>


        </div>
        <div>
          <button type="button" data-toggle="inside-<?= e($b['id']) ?>" class="text-xs mb-2" style="color:var(--primary)">▼ What's inside this week</button>
                    <ul id="inside-<?= e($b['id']) ?>" data-contents-list class="hidden space-y-1">
            <?php foreach ($s0['contents'] as $c): ?><li data-item class="text-xs flex items-center gap-2" style="color:var(--muted-foreground)"><span class="w-1 h-1 rounded-full shrink-0" style="background:var(--accent)"></span><?= e($c) ?></li><?php endforeach; ?>
            <li data-note class="text-xs italic mt-2" style="color:var(--muted-foreground)"><?= e($b['note']) ?></li>
          </ul>
        </div>


        <form method="post" action="<?= url('actions/cart_action.php') ?>" class="mt-auto pt-2 border-t flex items-center justify-between" style="border-color:var(--border)">
          <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="box"><input type="hidden" name="key" value="<?= e($b['id']) ?>">
          <input type="hidden" name="size" value="0"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
          <div><p data-price class="font-display text-2xl font-light"><?= money($s0['price']) ?></p><p data-meta class="text-xs" style="color:var(--muted-foreground)">/week · <?= e($s0['items']) ?> · Free delivery</p></div>
          <button type="button" data-open-schedule class="px-4 py-2.5 text-xs font-medium btn-primary">Subscribe</button>
          <div data-schedule style="display:none;position:fixed;inset:0;z-index:80;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
            <div class="p-6 border w-full max-w-md" style="background:var(--background);border-color:var(--border);max-height:90vh;overflow-y:auto">
              <h2 class="font-display text-xl font-light mb-1">Set up your delivery</h2>
              <p class="text-xs mb-5" style="color:var(--muted-foreground)"><?= e($b['name']) ?></p>
              <h3 class="font-display text-base font-light mb-3">How often?</h3>
              <div class="flex flex-col gap-2 mb-5">
                <?php foreach (frequencies() as $key => $label): ?>
                <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)">
                  <input type="radio" name="frequency" value="<?= $key ?>" <?= $key === 'weekly' ? 'checked' : '' ?>><?= e($label) ?>
                </label>
                <?php endforeach; ?>
              </div>
              <h3 class="font-display text-base font-light mb-3">Which day?</h3>
              <div class="grid grid-cols-3 gap-2 mb-5">
                <?php foreach (delivery_days() as $k => $d): ?>
                <label class="pill relative py-2.5 text-xs border text-center cursor-pointer" style="border-color:var(--border)">
                  <input type="radio" name="day" value="<?= $d ?>" <?= $k === 0 ? 'checked' : '' ?>><?= substr($d, 0, 3) ?>
                </label>
                <?php endforeach; ?>
              </div>
              <h3 class="font-display text-base font-light mb-3">What time?</h3>
              <div class="flex flex-col gap-2 mb-6">
                <?php foreach (time_windows() as $k => $t): ?>
                <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)">
                  <input type="radio" name="window" value="<?= e($t) ?>" <?= $k === 0 ? 'checked' : '' ?>><?= e($t) ?>
                </label>
                <?php endforeach; ?>
              </div>
              <div class="flex gap-3">
                <button class="px-6 py-3 text-sm font-medium btn-primary">Add to order</button>
                <button type="button" data-close-schedule class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</button>
              </div>
            </div>
          </div>
        </form>


      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="mt-20 border-t pt-16" style="border-color:var(--border)">
    <h2 class="font-display text-2xl font-light mb-8">How weekly boxes work</h2>
    <div class="grid md:grid-cols-4 gap-8">
      <?php foreach ([['01','Choose your box','Pick from Veg, Fruit, Ghana Staples, or Build Your Own Box and select the size for your household.'],['02','Book your delivery slot',"Choose a day and time window that works for you."],['03','Pay via mobile money or card','MTN MoMo, Vodafone Cash, AirtelTigo, or debit card. Billed with the frequency of your subscription.'],['04','Pause or cancel anytime',"Going away? Pause the subscription from your account with 48 hours' notice."]] as [$n,$t,$d]): ?>
        <div class="flex flex-col gap-2"><p class="font-display text-3xl font-light" style="color:var(--accent)"><?= $n ?></p><p class="font-medium text-sm"><?= $t ?></p><p class="text-xs leading-relaxed" style="color:var(--muted-foreground)"><?= $d ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>


</div>
<script>

document.querySelectorAll('[data-box]').forEach(function (box) {
  var list = box.querySelector('[data-contents-list]');
  var note = list.querySelector('[data-note]');
  box.querySelectorAll('[data-size]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var items = [];
      try { items = JSON.parse(btn.getAttribute('data-size-contents')) || []; } catch (e) {}
      list.querySelectorAll('[data-item]').forEach(function (li) { li.remove(); });
      items.forEach(function (text) {
        var li = document.createElement('li');
        li.setAttribute('data-item', '');
        li.className = 'text-xs flex items-center gap-2';
        li.style.color = 'var(--muted-foreground)';
        var dot = document.createElement('span');
        dot.className = 'w-1 h-1 rounded-full shrink-0';
        dot.style.background = 'var(--accent)';
        li.appendChild(dot);
        li.appendChild(document.createTextNode(text));
        list.insertBefore(li, note);
      });
    });
  });
});
</script>

<script>
document.querySelectorAll('[data-box]').forEach(function (box) {
  var modal = box.querySelector('[data-schedule]');
  box.querySelector('[data-open-schedule]').addEventListener('click', function () { modal.style.display = 'flex'; });
  modal.querySelector('[data-close-schedule]').addEventListener('click', function () { modal.style.display = 'none'; });
  modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') document.querySelectorAll('[data-schedule]').forEach(function (m) { m.style.display = 'none'; });
});
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
