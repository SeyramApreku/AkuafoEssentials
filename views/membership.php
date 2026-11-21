<?php

require_once __DIR__ . '/../controllers/catalog_controller.php';
$pageTitle = 'Membership | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-7xl mx-auto px-6 py-12">
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
        <form method="get" action="<?= url('views/membership_checkout.php') ?>" class="mt-auto">
  <input type="hidden" name="tier" value="<?= e($t['key'] ?? strtolower($t['name'])) ?>">
  <button type="submit" class="w-full py-3 text-sm font-medium btn-primary">Join <?= e($t['name']) ?></button>
</form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>