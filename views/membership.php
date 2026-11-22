<?php

require_once __DIR__ . '/../controllers/catalog_controller.php';
require_once __DIR__ . '/../classes/membership_class.php';
$active = customer_id() ? (new MembershipClass())->activeFor(customer_id()) : null;
$mine = $active ? tier_info($active['tier']) : null;
$pageTitle = 'Membership | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <?php if ($mine): ?>
  <div class="mb-12 p-6 border grid md:grid-cols-4 gap-6 items-center" style="border-color:var(--border);background:var(--card)">
    <div class="md:col-span-1">
      <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Your membership</p>
      <p class="font-display text-3xl font-light flex items-center gap-2"><?= tier_star($mine['name'], 24) ?><?= e($mine['name']) ?></p>
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Active until <?= e(date('j M Y', strtotime($active['expires_at']))) ?></p>
    </div>
    <div><p class="text-xs" style="color:var(--muted-foreground)">Delivery credit left this month</p>
      <p class="font-display text-2xl"><?= money(delivery_credit_left()) ?> <span class="text-xs" style="color:var(--muted-foreground)">of <?= money($mine['delivery']) ?></span></p>
      <p class="text-xs" style="color:var(--muted-foreground)">Applied automatically at checkout</p></div>
    <div><p class="text-xs" style="color:var(--muted-foreground)">Partner restaurant voucher</p>
      <p class="font-display text-2xl"><?= money($mine['voucher']) ?><span class="text-xs" style="color:var(--muted-foreground)"> /month</span></p></div>
    <div><p class="text-xs" style="color:var(--muted-foreground)">Early access &amp; recipes</p>
      <p class="text-sm"><?= e($mine['early']) ?> early, <?= (int)$mine['discount'] ?>% off · <?= (int)$mine['recipes'] ?> recipes/month</p></div>
  </div>
  <?php endif; ?>

  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Akuafo Membership</p>
  <h1 class="font-display text-4xl font-light mb-2">Choose your membership</h1>
  <p class="text-sm mb-12 max-w-xl" style="color:var(--muted-foreground)">Monthly restaurant vouchers, delivery credit, first access to new stock and recipes from our partner chefs. Every tier builds on the last.</p>

  <div class="grid md:grid-cols-3 gap-8">
    <?php foreach (membership_tiers() as $t): ?>
    <div class="border flex flex-col" style="border-color:var(--border);background:var(--card)">
      <div class="h-1.5" style="background:<?= e($t['color']) ?>"></div>
      <div class="p-7 flex flex-col flex-1 gap-6">
        <div>
          <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)"><?= e($t['name']) ?></p>
          <p class="font-display text-4xl font-light"><?= money($t['price']) ?><span class="text-sm" style="color:var(--muted-foreground)"> /month</span></p>
        </div>
        <ul class="space-y-3 text-sm">
          <?php foreach ([
            money($t['voucher']) . ' monthly voucher from partner restaurants',
            money($t['delivery']) . ' monthly delivery credit',
            'Early access to new stock, ' . $t['early'] . ' before everyone else',
            $t['discount'] . '% off early-access stock',
            $t['recipes'] . ' exclusive recipes a month from our featured partner chefs',
          ] as $perk): ?>
            <li class="flex items-start gap-2.5"><span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--accent)"></span><span><?= e($perk) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($mine && $mine['name'] === $t['name']): ?>
          <p class="mt-auto w-full py-3 text-sm font-medium text-center border" style="border-color:var(--primary);color:var(--primary)">Your current plan</p>
        <?php else: ?>
        <form method="get" action="<?= url('views/membership_checkout.php') ?>" class="mt-auto">
          <input type="hidden" name="tier" value="<?= e(strtolower($t['name'])) ?>">
          <button type="submit" class="w-full py-3 text-sm font-medium btn-primary">Join <?= e($t['name']) ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>