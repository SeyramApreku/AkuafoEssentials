<?php
require_once __DIR__ . '/../core/core.php';

$done = $_SESSION['membership_done'] ?? null; unset($_SESSION['membership_done']);
$tier = null;
foreach (membership_tiers() as $t) if (strtolower($t['name']) === strtolower($_GET['tier'] ?? '')) $tier = $t;
if (!$tier && !$done) redirect(url('views/membership.php'));

$pageTitle = 'Join Membership | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$errors = $_SESSION['membership_errors'] ?? []; unset($_SESSION['membership_errors']);
?>
<div class="max-w-2xl mx-auto px-6 py-12">
<?php if ($done): ?>
  <div class="text-center py-12">
    <p class="font-display text-3xl font-light mb-3">Welcome to <?= e($done) ?></p>
    <p class="text-sm mb-6" style="color:var(--muted-foreground)">Your membership is active.</p>
    <a href="<?= url('views/shop.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">Start shopping</a>
  </div>
<?php else: ?>
  <a href="<?= url('views/membership.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">← All memberships</a>
  <h1 class="font-display text-3xl font-light mb-8">Join <?= e($tier['name']) ?></h1>
  <?php foreach ($errors as $err): ?><p class="text-sm mb-3 p-3" style="background:var(--muted);color:var(--accent)"><?= e($err) ?></p><?php endforeach; ?>

  <div class="mb-8 p-4 border text-sm space-y-1.5" style="border-color:var(--border)">
    <div class="flex justify-between"><span><?= e($tier['name']) ?> membership</span><span class="font-display"><?= money($tier['price']) ?>/month</span></div>
    <p class="text-xs" style="color:var(--muted-foreground)"><?= money($tier['voucher']) ?> voucher, <?= money($tier['delivery']) ?> delivery credit, <?= e($tier['early']) ?> early access</p>
  </div>

  <form method="post" action="<?= url('actions/membership_action.php') ?>">
    <input type="hidden" name="tier" value="<?= e(strtolower($tier['name'])) ?>">
    <h2 class="font-display text-xl mb-4">Payment</h2>
    <div class="grid grid-cols-2 gap-2 mb-6">
      <?php foreach ([['mtn','MTN Mobile Money'],['vodafone','Vodafone Cash'],['airteltigo','AirtelTigo Money'],['card','Debit / Credit Card']] as $k => [$id,$label]): ?>
        <label class="pick relative py-3 px-3 text-xs border flex items-center gap-2 cursor-pointer" style="border-color:var(--border)">
          <input type="radio" name="method" value="<?= $id ?>" <?= $k === 0 ? 'checked' : '' ?>><span><?= $label ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="mb-6">
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Mobile number</label>
      <input type="tel" name="phone" placeholder="e.g. 0244 000 000" class="w-full px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
    </div>
    <button class="w-full py-3 text-sm font-medium btn-primary">Pay <?= money($tier['price']) ?> and join →</button>
  </form>
<?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>