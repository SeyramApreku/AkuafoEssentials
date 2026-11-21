<?php
$pageTitle = 'Checkout | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$items = $cartObj->items(); $sub = $cartObj->subtotal(); $del = $cartObj->deliveryCost(); $hasPass = $cartObj->hasPass();
$errors = $_SESSION['checkout_errors'] ?? []; unset($_SESSION['checkout_errors']);
$hasBox = (bool)array_filter($items, fn($i) => $i['type'] === 'box');
$base = $sub + $del;
?>
<div class="max-w-2xl mx-auto px-6 py-12">
<?php if (!$items || $sub < MIN_ORDER): ?>
  <div class="text-center py-12">
    <p class="font-display text-2xl font-light mb-4"><?= $items ? 'Minimum order is ' . money(MIN_ORDER) : 'Your order is empty' ?></p>
    <a href="<?= url('views/shop.php') ?>" class="text-sm" style="color:var(--primary)">← Back to shop</a>
  </div>
<?php else: ?>
  <a href="<?= url('views/shop.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← Continue shopping</a>
  <h1 class="font-display text-3xl font-light mb-8">Checkout</h1>
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

    <?php if (!$hasPass): ?>
    <label class="mb-6 p-4 border flex items-start justify-between gap-4 cursor-pointer" style="border-color:var(--secondary);background:var(--muted)">
      <span><span class="block text-sm font-medium mb-1">✦ Add Akuafo Delivery Pass, <?= money(PASS_PRICE) ?>/month</span><span class="text-xs" style="color:var(--muted-foreground)">Unlimited free delivery on all future orders. Cancel anytime.</span></span>
      <input type="checkbox" name="add_pass" value="1" id="add-pass" class="mt-1">
    </label>
    <?php endif; ?>

    <div class="space-y-1.5 text-sm mb-10 p-4 border" style="border-color:var(--border)">
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Subtotal</span><span><?= money($sub) ?></span></div>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Delivery</span><span><?= $hasPass ? 'Free (Pass)' : money($del) ?></span></div>
      <div id="pass-row" class="hidden flex justify-between"><span style="color:var(--muted-foreground)">Delivery Pass (1st month)</span><span><?= money(PASS_PRICE) ?></span></div>
      <div class="flex justify-between font-medium border-t pt-2" style="border-color:var(--border)"><span>Total</span><span id="grand-total" data-base="<?= $base ?>" data-pass="<?= PASS_PRICE ?>"><?= money($base) ?></span></div>
      <p class="text-xs pt-1" style="color:var(--muted-foreground)"><?= $hasBox ? 'Box subscriptions are billed weekly.' : 'One-off payment.' ?></p>
    </div>

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


    <h2 class="font-display text-xl mb-4">Contact details</h2>

<div class="mb-6">
    <label class="text-xs uppercase tracking-widest mb-2 block"
           style="color:var(--muted-foreground)">
        Email address
    </label>

    <input
        type="email"
        name="email"
        required
        placeholder="you@example.com"
        class="w-full px-4 py-3 text-sm border outline-none bg-transparent"
        style="border-color:var(--border)"
    >
    <p class="text-xs mt-2" style="color:var(--muted-foreground)">
        Your email will be used for payment confirmation and your receipt.
    </p>
</div>


    <h2 class="font-display text-xl mb-4">Payment</h2>
    <div class="grid grid-cols-2 gap-2 mb-6">
      <?php foreach ([['mtn','MTN Mobile Money','#FFCC00','#1A1916'],['vodafone','Vodafone Cash','#E60014','#fff'],['airteltigo','AirtelTigo Money','#DC143C','#fff'],['card','Debit / Credit Card','var(--muted)','var(--foreground)']] as $k => [$id,$label,$bg,$fg]): ?>
        <label class="pick relative py-3 px-3 text-xs border flex items-center gap-2 cursor-pointer" style="border-color:var(--border)">
          <input type="radio" name="method" value="<?= $id ?>" <?= $k === 0 ? 'checked' : '' ?>>
          <span class="w-6 h-6 rounded flex items-center justify-center font-bold shrink-0" style="background:<?= $bg ?>;color:<?= $fg ?>"><?= $label[0] ?></span><span><?= $label ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <div id="mobile-fields" class="mb-6">
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Mobile number</label>
      <input type="tel" name="phone" placeholder="e.g. 0244 000 000" class="w-full px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
      <p class="text-xs mt-2" style="color:var(--muted-foreground)">You'll receive a prompt on this number to approve the payment.</p>
    </div>
    <div id="card-fields" class="hidden mb-6 p-4 text-xs" style="background:var(--muted);color:var(--muted-foreground)">Card payments are handled by a payment gateway (e.g. Paystack). Card details are never stored on this site.</div>

    <button class="w-full py-3 text-sm font-medium btn-primary">Place order →</button>
  </form>
<?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
