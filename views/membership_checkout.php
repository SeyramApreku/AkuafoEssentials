<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/membership_class.php';

$done = $_SESSION['membership_done'] ?? null; unset($_SESSION['membership_done']);
$tier = tier_info($_GET['tier'] ?? '');
if (!$tier && !$done) redirect(url('views/membership.php'));
require_login($tier ? 'views/membership_checkout.php?tier=' . strtolower($tier['name']) : 'views/membership.php');

$active = (new MembershipClass())->activeFor(customer_id());
if ($done && !$active) $done = null;
if (!$done && !$tier) redirect(url('views/membership.php'));
$errors = $_SESSION['membership_errors'] ?? []; unset($_SESSION['membership_errors']);
$pageTitle = 'Join Membership | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-2xl mx-auto px-6 py-12">
<?php if ($done && $active): $t = tier_info($active['tier']); ?>
  <div class="text-center py-12">
    <div class="flex justify-center mb-4"><?= tier_star($active['tier'], 40) ?></div>
    <p class="font-display text-3xl font-light mb-3">Welcome to <?= e($done) ?></p>
    <p class="text-sm mb-6" style="color:var(--muted-foreground)">Payment received. Your membership is active until <strong><?= e(date('j M Y', strtotime($active['expires_at']))) ?></strong>.</p>
    <ul class="text-sm text-left inline-block space-y-2 mb-8">
      <li>✓ <?= money($t['delivery']) ?> delivery credit this month, applied automatically at checkout</li>
      <li>✓ <?= money($t['voucher']) ?> monthly voucher from partner restaurants</li>
      <li>✓ Early access to new stock, <?= e($t['early']) ?> before everyone else, with <?= (int)$t['discount'] ?>% off</li>
      <li>✓ <?= (int)$t['recipes'] ?> exclusive chef recipes a month</li>
    </ul><br>
    <a href="<?= url('views/shop.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">Start shopping</a>
  </div>
<?php else: ?>
  <a href="<?= url('views/membership.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← All memberships</a>
  <h1 class="font-display text-3xl font-light mb-8"><?= $active && membership_upgrade_price($active['tier'], $tier['name']) !== null ? 'Upgrade to' : 'Join' ?> <?= e($tier['name']) ?></h1>
  <?php foreach ($errors as $err): ?><p class="text-sm mb-3 p-3" style="background:var(--muted);color:var(--accent)"><?= e($err) ?></p><?php endforeach; ?>

  <div class="mb-8 p-4 border text-sm space-y-1.5" style="border-color:var(--border)">
    <div class="flex justify-between"><span><?= e($tier['name']) ?> membership</span><span class="font-display"><?= money($tier['price']) ?>/month</span></div>
    <p class="text-xs" style="color:var(--muted-foreground)"><?= money($tier['voucher']) ?> voucher, <?= money($tier['delivery']) ?> delivery credit, <?= e($tier['early']) ?> early access, <?= (int)$tier['discount'] ?>% off early-access stock</p>
    <p class="text-xs" style="color:var(--muted-foreground)">Lasts one month from payment.</p>
  </div>

  <?php $upPrice = $active ? membership_upgrade_price($active['tier'], $tier['name']) : null;
  if ($active && $upPrice !== null): $was = tier_info($active['tier']); ?>
    <div class="mb-6 p-4 border text-sm space-y-1.5" style="border-color:var(--primary);background:var(--card)">
      <p class="font-medium">Upgrade from <?= e($was['name']) ?> to <?= e($tier['name']) ?></p>
      <div class="flex justify-between gap-4"><span><?= e($tier['name']) ?> <?= money($tier['price']) ?> − <?= e($was['name']) ?> <?= money($was['price']) ?> already paid</span><span class="font-display"><?= money($upPrice) ?></span></div>
      <p class="text-xs" style="color:var(--muted-foreground)">Starts as soon as you pay and still ends on <?= e(date('j M Y', strtotime($active['expires_at']))) ?>. Your voucher goes up by <?= money($tier['voucher'] - $was['voucher']) ?> (what you have already spent stays spent) and you get a new voucher code.</p>
    </div>
    <form method="post" action="<?= url('actions/membership_action.php') ?>">
      <input type="hidden" name="tier" value="<?= e(strtolower($tier['name'])) ?>">
      <button class="w-full py-3 text-sm font-medium btn-primary">Pay <?= money($upPrice) ?> and upgrade &rarr;</button>
      <p class="text-xs mt-3 text-center" style="color:var(--muted-foreground)">You will be taken to Paystack to pay securely by card or mobile money.</p>
    </form>
  <?php elseif ($active): ?>
    <div class="p-4 border text-sm" style="border-color:var(--secondary);background:var(--muted)">
      <p class="font-medium mb-1"><?= tier_star($active['tier']) ?> You're a <?= e(ucfirst($active['tier'])) ?> member until <?= e(date('j M Y', strtotime($active['expires_at']))) ?></p>
      <p class="text-xs" style="color:var(--muted-foreground)">You can choose a lower plan after your current membership ends.</p>
    </div>
  <?php else: ?>
  <form method="post" action="<?= url('actions/membership_action.php') ?>">
    <input type="hidden" name="tier" value="<?= e(strtolower($tier['name'])) ?>">
    <button class="w-full py-3 text-sm font-medium btn-primary">Pay <?= money($tier['price']) ?> with Paystack &rarr;</button>
    <p class="text-xs mt-3 text-center" style="color:var(--muted-foreground)">You will be taken to Paystack to pay securely by card or mobile money.</p>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
