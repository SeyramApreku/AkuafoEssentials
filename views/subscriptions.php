<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/cart_controller.php';
require_once __DIR__ . '/../classes/subscription_class.php';
require_login('views/subscriptions.php');
$subs = (new SubscriptionClass())->forCustomer((int)$_SESSION['customer_id']);
$pageTitle = 'My subscriptions | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-4xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">My account</p>
  <h1 class="font-display text-4xl font-light mb-3">My subscriptions</h1>
  <p class="text-sm mb-10" style="color:var(--muted-foreground)">Your weekly boxes, how many weeks are left, and one-click renewal. We'll also email you <?= BOX_REMIND_DAYS ?> days before your last box.</p>

  <?php if (!$subs): ?>
    <div class="text-center py-16 border" style="border-color:var(--border)">
      <p class="font-display text-2xl font-light mb-2">No box subscriptions yet</p>
      <p class="text-sm mb-6" style="color:var(--muted-foreground)">Choose a box, how often it comes and for how long. You pay once for the whole plan.</p>
      <a href="<?= url('views/boxes.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">See weekly boxes</a>
    </div>
  <?php else: ?>
  <div class="flex flex-col gap-4">
    <?php foreach ($subs as $s):
      $days = (int)$s['days_left'];
      $renewed = !empty($s['renewed_by_order_id']);
      $started = strtotime($s['starts_on']) <= strtotime(date('Y-m-d'));
      if ($renewed)        [$tag, $bg] = ['Renewed', 'var(--muted)'];
      elseif ($days < 0)   [$tag, $bg] = ['Finished', 'var(--muted)'];
      elseif ($days <= BOX_REMIND_DAYS) [$tag, $bg] = [$days === 0 ? 'Last box today' : 'Ends in ' . $days . ' day' . ($days === 1 ? '' : 's'), '#f3e3b5'];
      else                 [$tag, $bg] = [$started ? 'Active' : 'Starts ' . date('D j M', strtotime($s['starts_on'])), '#dbe8cf'];
      // Rough progress through the plan, for the bar.
      $total = max(1, (strtotime($s['ends_on']) - strtotime($s['starts_on'])) / 86400);
      $done  = min(1, max(0, (time() - strtotime($s['starts_on'])) / 86400 / $total));
    ?>
      <div class="p-6 border" style="border-color:var(--border);background:var(--card)">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
          <div>
            <p class="font-display text-xl font-light"><?= e($s['name']) ?></p>
            <p class="text-xs mt-1" style="color:var(--muted-foreground)"><?= e($s['detail'] ?? '') ?></p>
          </div>
          <span class="text-xs px-2.5 py-1 uppercase tracking-widest" style="background:<?= $bg ?>"><?= e($tag) ?></span>
        </div>
        <div class="h-1.5 mb-3" style="background:var(--muted)"><div class="h-full" style="width:<?= round($done * 100) ?>%;background:var(--primary)"></div></div>
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs" style="color:var(--muted-foreground)">
          <span><?= e(frequencies()[$s['frequency']] ?? '') ?> for <?= (int)$s['weeks'] ?> weeks = <?= (int)$s['deliveries'] ?> deliver<?= (int)$s['deliveries'] === 1 ? 'y' : 'ies' ?> · <?= date('j M', strtotime($s['starts_on'])) ?> to <?= date('j M Y', strtotime($s['ends_on'])) ?> · Paid <?= money($s['price_paid']) ?> · Order <?= e($s['ref']) ?></span>
          <?php if (!$renewed): ?>
            <form method="post" action="<?= url('actions/cart_action.php') ?>">
              <input type="hidden" name="action" value="renew"><input type="hidden" name="sub" value="<?= (int)$s['id'] ?>">
              <input type="hidden" name="return" value="<?= e(url('views/subscriptions.php')) ?>">
              <button class="px-5 py-2.5 text-xs font-medium <?= $days <= BOX_REMIND_DAYS ? 'btn-primary' : 'border' ?>" style="border-color:var(--primary)"><?= $days < 0 ? 'Renew' : ($days <= BOX_REMIND_DAYS ? 'Renew now' : 'Renew early') ?> for <?= (int)$s['weeks'] ?> weeks</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
