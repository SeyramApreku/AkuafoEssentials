<?php
require_once __DIR__ . '/../core/core.php';
require_login('views/checkout.php');
require_once __DIR__ . '/../classes/order_class.php';
$accountEmail = (new OrderClass())->customer(customer_id())['customer_email'] ?? '';
$pageTitle = 'Checkout | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$items = $cartObj->items(); $sub = $cartObj->subtotal();
$zones = delivery_zones(); $tier = member_tier(); $creditLeft = delivery_credit_left();
$errors = $_SESSION['checkout_errors'] ?? []; unset($_SESSION['checkout_errors']);
$hasBox = (bool)array_filter($items, fn($i) => $i['type'] === 'box');

// Entertaining orders: event date picker limited by the longest notice period in the cart.
$isEvent = $cartObj->kind() === 'entertaining';
if ($isEvent) {
    require_once __DIR__ . '/../controllers/entertaining_controller.php';
    $noticeH  = ent_cart_notice_hours();
    $earliest = ent_earliest_date($noticeH)->format('Y-m-d');
    $latest   = (new DateTime('today'))->modify('+' . ENT_BOOK_AHEAD_DAYS . ' days')->format('Y-m-d');
    $blocked  = ent_unavailable_dates($earliest, $latest);
    $old      = $_SESSION['checkout_old'] ?? []; unset($_SESSION['checkout_old']);
}
?>
<div class="max-w-2xl mx-auto px-6 py-12">
<?php if (!$items || $sub < min_order()): ?>
  <div class="text-center py-12">
    <p class="font-display text-2xl font-light mb-4"><?= $items ? 'Minimum order is ' . money(min_order()) : 'Your order is empty' ?></p>
    <a href="<?= url('views/shop.php') ?>" class="text-sm" style="color:var(--primary)">← Back to shop</a>
  </div>
<?php else: ?>
  <a href="<?= url($isEvent ? 'views/preorder.php' : 'views/shop.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← Continue shopping</a>
  <h1 class="font-display text-3xl font-light mb-8"><?= $isEvent ? 'Entertaining checkout' : 'Checkout' ?></h1>
  <?php foreach ($errors as $err): ?><p class="text-sm mb-3 p-3" style="background:var(--muted);color:var(--accent)"><?= e($err) ?></p><?php endforeach; ?>

  <form method="post" action="<?= url('actions/checkout_action.php') ?>">
    <h2 class="font-display text-xl mb-4">Review your order</h2>
    <ul class="mb-6">
      <?php foreach ($items as $i): ?>
      <li class="flex gap-4 items-center py-3 border-b" style="border-color:var(--border)">
        <img src="<?= e($i['image']) ?>" alt="" class="w-12 h-12 object-cover">
        <div class="flex-1"><p class="text-sm font-medium"><?= e($i['name']) ?></p><?php if (!empty($i['detail'])): ?><p class="text-xs" style="color:var(--muted-foreground)"><?= e($i['detail']) ?></p><?php endif; ?></div>
        <p class="text-sm">× <?= $i['qty'] ?></p><p class="text-sm font-display w-20 text-right"><?= money($i['price'] * $i['qty']) ?></p>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!$tier): ?>
    <a href="<?= url('views/membership.php') ?>" class="mb-6 p-4 border block" style="border-color:var(--secondary);background:var(--muted)">
      <span class="block text-sm font-medium mb-1">✦ Become a member for free delivery benefits</span>
      <span class="text-xs" style="color:var(--muted-foreground)">Members get a monthly delivery allowance, discounts and more. View membership →</span>
    </a>
    <?php else: ?>
    <div class="mb-6 p-4 border" style="border-color:var(--secondary);background:var(--muted)">
      <span class="block text-sm font-medium mb-1">✦ <?= e(ucfirst($tier)) ?> member</span>
      <span class="text-xs" style="color:var(--muted-foreground)">Delivery credit left this month: <?= money($creditLeft) ?>. Unused credit does not roll over.</span>
    </div>
    <?php endif; ?>

    <h2 class="font-display text-xl mb-2">Delivery area</h2>
    <select name="zone" id="zone" required class="w-full px-4 py-3 text-sm border bg-transparent mb-6" style="border-color:var(--border)">
      <option value="">Select your area</option>
      <?php foreach ($zones as $z): ?>
        <option value="<?= (int)$z['id'] ?>" data-fee="<?= (float)$z['fee'] ?>"><?= e($z['name']) ?> (<?= money($z['fee']) ?>)</option>
      <?php endforeach; ?>
    </select>

    <div class="space-y-1.5 text-sm mb-10 p-4 border" style="border-color:var(--border)">
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Subtotal</span><span><?= money($sub) ?></span></div>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Delivery</span><span id="del-fee">Select your area</span></div>
      <div id="credit-row" class="justify-between" style="display:none"><span style="color:var(--muted-foreground)">Membership delivery credit</span><span id="del-credit"></span></div>
      <div class="flex justify-between font-medium border-t pt-2" style="border-color:var(--border)"><span>Total</span><span id="grand-total"><?= money($sub) ?></span></div>
      <p class="text-xs pt-1" style="color:var(--muted-foreground)"><?= $hasBox ? 'Box subscriptions are billed weekly.' : 'One-off payment.' ?></p>
    </div>

    <?php if ($isEvent): ?>
    <h2 class="font-display text-xl mb-2">Your event</h2>
    <p class="text-xs mb-4" style="color:var(--muted-foreground)">Your cart needs at least <strong><?= e(notice_label($noticeH)) ?></strong> notice, so the earliest event date is <strong><?= e(date('D j M', strtotime($earliest))) ?></strong>.</p>
    <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)" for="event-date">Event date</label>
    <input type="date" id="event-date" name="date" required min="<?= $earliest ?>" max="<?= $latest ?>" value="<?= e($old['date'] ?? '') ?>" class="w-full px-4 py-3 text-sm border bg-transparent mb-1" style="border-color:var(--border)">
    <p id="date-msg" class="text-xs mb-5" style="color:var(--accent)"></p>
    <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--muted-foreground)">Delivery time</p>
    <div class="flex flex-col gap-2 mb-6">
      <?php foreach (time_windows() as $k => $t): ?>
        <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)"><input type="radio" name="window" value="<?= e($t) ?>" <?= ($old['window'] ?? time_windows()[0]) === $t ? 'checked' : '' ?>><?= e($t) ?></label>
      <?php endforeach; ?>
    </div>
    <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)" for="event-address">Event address (optional)</label>
    <textarea id="event-address" name="event_address" rows="2" maxlength="255" placeholder="Venue or house address, landmark, gate colour…" class="w-full px-4 py-3 text-sm border outline-none bg-transparent mb-1" style="border-color:var(--border)"><?= e($old['event_address'] ?? '') ?></textarea>
    <p class="text-xs mb-10" style="color:var(--muted-foreground)">Leave empty if we're delivering to your usual address in the area above. You can change the date, time or address until <?= ent_cutoff_hours() ?> hours before the event.</p>
    <script>
    (function () {
      var blocked = <?= json_encode($blocked) ?>, input = document.getElementById('event-date'), msg = document.getElementById('date-msg');
      input.addEventListener('change', function () {
        if (blocked.indexOf(input.value) !== -1) { msg.textContent = 'We are fully booked on that date. Please choose another.'; input.value = ''; }
        else if (input.value && input.value < input.min) { msg.textContent = 'That date is too soon for the items in your cart.'; input.value = ''; }
        else msg.textContent = '';
      });
    })();
    </script>
    <?php else: ?>
    <h2 class="font-display text-xl mb-2">Book your delivery slot</h2>
    <p class="text-xs mb-4" style="color:var(--muted-foreground)">Select a date and time window. We'll confirm your slot by SMS after payment.</p>
    <div class="grid grid-cols-4 gap-2 mb-6">
      <?php foreach (get_slots() as $k => $s): ?>
        <label class="pill relative py-2.5 text-xs border text-center cursor-pointer" style="border-color:var(--border)"><input type="radio" name="date" value="<?= $s['value'] ?>" <?= $k === 0 ? 'checked' : '' ?>><?= $s['date'] ?></label>
      <?php endforeach; ?>
    </div>
    <div class="flex flex-col gap-2 mb-10">
      <?php foreach (time_windows() as $k => $t): ?>
        <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)"><input type="radio" name="window" value="<?= e($t) ?>" <?= $k === 0 ? 'checked' : '' ?>><?= e($t) ?></label>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h2 class="font-display text-xl mb-4">Contact details</h2>

    <div class="mb-6">
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Email address</label>
      <p class="w-full px-4 py-3 text-sm border" style="border-color:var(--border);background:var(--muted)"><?= e($accountEmail) ?></p>
      <p class="text-xs mt-2" style="color:var(--muted-foreground)">This is your account email. Your payment confirmation and invoice will be sent here.</p>
    </div>

    <div class="mb-10">
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Mobile number</label>
      <input type="tel" name="phone" required placeholder="e.g. 0244 000 000" class="w-full px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
      <p class="text-xs mt-2" style="color:var(--muted-foreground)">We use this to confirm your delivery slot. You will choose how to pay on the next step.</p>
    </div>

    <button class="w-full py-3 text-sm font-medium btn-primary">Continue to order summary →</button>
  </form>

  <script>
  (function () {
    var sub = <?= (float)$sub ?>, credit = <?= (float)$creditLeft ?>;
    var zone = document.getElementById('zone');
    function m(n) { return 'GH₵ ' + (n % 1 === 0 ? n : n.toFixed(2)); }
    function update() {
      var o = zone.options[zone.selectedIndex];
      var fee = o && o.dataset.fee ? parseFloat(o.dataset.fee) : null;
      var row = document.getElementById('credit-row');
      if (fee === null) {
        document.getElementById('del-fee').textContent = 'Select your area';
        row.style.display = 'none';
        document.getElementById('grand-total').textContent = m(sub);
        return;
      }
      var use = Math.min(fee, credit);
      document.getElementById('del-fee').textContent = m(fee);
      row.style.display = use > 0 ? 'flex' : 'none';
      document.getElementById('del-credit').textContent = '- ' + m(use);
      document.getElementById('grand-total').textContent = m(sub + fee - use);
    }
    zone.addEventListener('change', update);
    update();
  })();
  </script>
<?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>