<?php

require_once __DIR__ . '/../controllers/catalog_controller.php';
require_once __DIR__ . '/../classes/membership_class.php';
$active = customer_id() ? (new MembershipClass())->activeFor(customer_id()) : null;
$mine = $active ? tier_info($active['tier']) : null;
$pageTitle = 'Membership | Akuafo & Essentials';
$memberMsg = $_SESSION['membership_msg'] ?? null; unset($_SESSION['membership_msg']);
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
  <?php if ($memberMsg): ?><p class="text-sm mb-8 p-3" style="background:var(--muted)"><?= e($memberMsg) ?></p><?php endif; ?>
  <?php if ($mine):
    $mc = new MembershipClass();
    $voucher = $mc->voucherFor($active);
    $uses = $voucher ? $mc->redemptions($voucher['id']) : [];
    $creditLeft = delivery_credit_left();
    [$memberAll, $memberOpen] = member_recipe_access();
    $myRecipes = array_values(array_filter(catalog()->recipes(), fn($r) => in_array((int)$r['id'], $memberOpen, true)));
    $daysLeft = max(0, (int)ceil((strtotime($active['expires_at']) - time()) / 86400));
    $bar = fn($left, $of) => $of > 0 ? round(max(0, min(1, $left / $of)) * 100) : 0;
  ?>
  <!-- ===== Member dashboard ===== -->
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">My membership</p>
  <h1 class="font-display text-4xl font-light mb-8">Welcome back, <?= e(explode(' ', (string)($_SESSION['customer_name'] ?? ''))[0]) ?></h1>

  <div class="grid lg:grid-cols-3 gap-6 mb-6">
    <!-- Member card -->
    <div class="p-7 flex flex-col justify-between gap-8" style="background:<?= e($mine['color']) ?>;color:#fff;min-height:210px">
      <div class="flex items-start justify-between">
        <div><p class="text-xs uppercase tracking-widest opacity-80">Akuafo &amp; Essentials</p>
          <p class="font-display text-3xl font-light mt-1 flex items-center gap-2"><?= tier_star($mine['name'], 24) ?><?= e($mine['name']) ?> Member</p></div>
        <span class="text-xs px-2.5 py-1 uppercase tracking-widest" style="background:rgba(255,255,255,.2)">Active</span>
      </div>
      <div class="flex items-end justify-between text-sm">
        <div><p class="text-xs opacity-80">Member</p><p class="font-medium"><?= e($_SESSION['customer_name'] ?? '') ?></p><p class="text-xs opacity-80 mt-1">No. AKM-<?= str_pad((string)(int)$active['id'], 5, '0', STR_PAD_LEFT) ?></p></div>
        <div class="text-right"><p class="text-xs opacity-80">Valid until</p><p class="font-medium"><?= e(date('j M Y', strtotime($active['expires_at']))) ?></p><p class="text-xs opacity-80 mt-1"><?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> left</p></div>
      </div>
    </div>

    <!-- Restaurant voucher -->
    <div class="p-7 border flex flex-col gap-4" style="border-color:var(--border);background:var(--card)">
      <p class="text-xs uppercase tracking-widest" style="color:var(--accent)">Partner restaurant voucher</p>
      <?php if ($voucher): ?>
        <div class="flex items-center gap-3">
          <p id="voucher-code" class="font-display text-2xl tracking-wider px-3 py-1.5 border border-dashed" style="border-color:var(--primary)"><?= e($voucher['code']) ?></p>
          <button type="button" class="text-xs underline" onclick="if (navigator.clipboard) navigator.clipboard.writeText(document.getElementById('voucher-code').textContent.trim()); this.textContent = 'Copied';">Copy</button>
        </div>
        <div><p class="font-display text-2xl"><?= money($voucher['balance']) ?> <span class="text-xs" style="color:var(--muted-foreground)">left of <?= money($voucher['amount']) ?></span></p>
          <div class="h-1.5 mt-2" style="background:var(--muted)"><div class="h-full" style="width:<?= $bar($voucher['balance'], $voucher['amount']) ?>%;background:var(--primary)"></div></div></div>
        <p class="text-xs" style="color:var(--muted-foreground)">Show this code when you pay at any of our <a href="<?= url('views/testimonials.php') ?>" class="underline">partner restaurants</a>. Valid until <?= e(date('j M', strtotime($voucher['expires_at']))) ?>.</p>
      <?php else: ?>
        <p class="text-sm" style="color:var(--muted-foreground)">Your voucher of <?= money($mine['voucher']) ?> is being prepared. Please check back shortly.</p>
      <?php endif; ?>
    </div>

    <!-- Delivery pass -->
    <div class="p-7 border flex flex-col gap-4" style="border-color:var(--border);background:var(--card)">
      <p class="text-xs uppercase tracking-widest" style="color:var(--accent)">Delivery pass</p>
      <div><p class="font-display text-2xl"><?= money($creditLeft) ?> <span class="text-xs" style="color:var(--muted-foreground)">left of <?= money($mine['delivery']) ?> this month</span></p>
        <div class="h-1.5 mt-2" style="background:var(--muted)"><div class="h-full" style="width:<?= $bar($creditLeft, $mine['delivery']) ?>%;background:var(--primary)"></div></div></div>
      <p class="text-xs" style="color:var(--muted-foreground)">Taken off your delivery fee automatically at checkout. Resets on the 1st of each month.</p>
      <a href="<?= url('views/shop.php') ?>" class="mt-auto text-sm font-medium" style="color:var(--primary)">Shop now →</a>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6 mb-6">
    <!-- Exclusive recipes -->
    <div class="lg:col-span-2 p-7 border" style="border-color:var(--border);background:var(--card)">
      <div class="flex flex-wrap items-baseline justify-between gap-2 mb-5"><p class="text-xs uppercase tracking-widest" style="color:var(--accent)">Your exclusive recipes</p>
        <p class="text-xs" style="color:var(--muted-foreground)"><?= (int)$mine['recipes'] ?> a month from our partner restaurants · <a href="<?= url('views/member_recipes.php') ?>" class="underline">My recipes →</a></p></div>
      <?php if ($myRecipes): ?>
        <div class="grid sm:grid-cols-2 gap-4">
          <?php foreach ($myRecipes as $r): ?>
            <a href="<?= url('views/member_recipes.php?id=' . (int)$r['id']) ?>" class="flex gap-3 items-center group">
              <img src="<?= img($r['image'], 120, 120) ?>" alt="" class="w-16 h-16 object-cover shrink-0" style="background:var(--muted)">
              <span><span class="block text-sm font-medium group-hover:underline"><?= e($r['name']) ?></span><span class="block text-xs" style="color:var(--muted-foreground)"><?= !empty($r['partner']) ? 'From ' . e($r['partner']) . ' · ' : '' ?><?= e($r['time']) ?></span></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-sm" style="color:var(--muted-foreground)">This month's exclusive recipes from our partner restaurants are on their way. Check back soon.</p>
      <?php endif; ?>
    </div>

    <!-- Early access + voucher history -->
    <div class="p-7 border flex flex-col gap-5" style="border-color:var(--border);background:var(--card)">
      <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Early access</p>
        <p class="text-sm">New stock <?= e($mine['early']) ?> before everyone else, with <?= (int)$mine['discount'] ?>% off early-access stock.</p></div>
      <div><p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Voucher history</p>
        <?php if ($uses): ?>
          <ul class="text-xs space-y-1.5"><?php foreach (array_slice($uses, 0, 5) as $u): ?><li class="flex justify-between gap-3"><span><?= e($u['restaurant']) ?> · <?= e(date('j M', strtotime($u['created_at']))) ?></span><span><?= money($u['amount']) ?></span></li><?php endforeach; ?></ul>
        <?php else: ?><p class="text-xs" style="color:var(--muted-foreground)">Not used yet this month.</p><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Manage membership -->
  <div id="manage" class="flex flex-wrap items-center justify-between gap-4 p-6 mb-16 border" style="border-color:var(--border);background:var(--card)">
    <div>
      <p class="text-xs uppercase tracking-widest mb-1" style="color:var(--accent)">Manage membership</p>
      <p class="text-sm" style="color:var(--muted-foreground)"><?= e($mine['name']) ?> · <?= money($mine['price']) ?> paid · ends <?= e(date('j M Y', strtotime($active['expires_at']))) ?>. Memberships don't renew automatically.<?= membership_upgrades($active['tier']) ? ' Upgrade any time: you only pay the difference and keep the same end date.' : '' ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
    <?php foreach (membership_upgrades($active['tier']) as $up): ?>
      <a href="<?= url('views/membership_checkout.php?tier=' . strtolower($up['name'])) ?>" class="px-5 py-2.5 text-sm font-medium btn-primary inline-flex items-center gap-1.5"><?= tier_star($up['name'], 14) ?>Upgrade to <?= e($up['name']) ?> · <?= money($up['upgrade_price']) ?></a>
    <?php endforeach; ?>
    <form method="post" action="<?= url('actions/membership_cancel_action.php') ?>"
          data-confirm="Cancel your <?= e($mine['name']) ?> membership? Your voucher, delivery credit and exclusive recipes stop today, and the rest of this month is not refunded." data-confirm-yes="Yes, cancel it">
      <button class="px-5 py-2.5 text-sm font-medium border" style="border-color:#b3261e;color:#b3261e">Cancel membership</button>
    </form>
    </div>
  </div>
  <?php if (isset($_GET['manage'])): ?><script>document.getElementById('manage').scrollIntoView({ block: 'center' });</script><?php endif; ?>
  <?php endif; ?>

  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Akuafo Membership</p>
  <h2 class="font-display text-4xl font-light mb-2"><?= $mine ? 'All plans' : 'Choose your membership' ?></h2>
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
            money($t['voucher']) . ' monthly voucher to spend at our partner restaurants',
            money($t['delivery']) . ' monthly delivery credit',
            'Early access to new stock, ' . $t['early'] . ' before everyone else',
            $t['discount'] . '% off early-access stock',
            $t['recipes'] . ' exclusive recipe' . ($t['recipes'] === 1 ? '' : 's') . ' a month from our partner restaurants',
          ] as $perk): ?>
            <li class="flex items-start gap-2.5"><span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--accent)"></span><span><?= e($perk) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php $upP = $mine ? membership_upgrade_price($mine['name'], $t['name']) : null; ?>
        <?php if ($mine && $mine['name'] === $t['name']): ?>
          <p class="mt-auto w-full py-3 text-sm font-medium text-center border" style="border-color:var(--primary);color:var(--primary)">Your current plan</p>
        <?php elseif ($mine && $upP !== null): ?>
          <a href="<?= url('views/membership_checkout.php?tier=' . strtolower($t['name'])) ?>" class="mt-auto w-full py-3 text-sm font-medium btn-primary text-center block">Upgrade for <?= money($upP) ?></a>
        <?php elseif ($mine): ?>
          <p class="mt-auto w-full py-3 text-xs text-center" style="color:var(--muted-foreground)">Available after your <?= e($mine['name']) ?> plan ends</p>
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