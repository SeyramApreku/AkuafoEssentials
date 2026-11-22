<?php
ob_start();   // lets finish_response() send the page before any invoice emails go out
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';
require_login('views/orders.php');

$orders = new OrderClass();

// Payments that never came back from Paystack (tab closed, connection lost): ask Paystack now.
$newlyPaid = [];
foreach ($orders->unconfirmedFor(customer_id()) as $o) {
    if (verify_order_payment($o) === 'paid') $newlyPaid[] = $o['ref'];
}
foreach ($newlyPaid as $ref) clear_cart_for_paid_order($ref, true);

$rows = $orders->forCustomer(customer_id());
$nice = fn($s) => ucwords(str_replace('_', ' ', (string)$s));
$pageTitle = 'My orders | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-4xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Your account</p>
  <h1 class="font-display text-4xl font-light mb-8">My orders</h1>

  <?php if ($newlyPaid): ?>
    <p class="text-sm mb-6 p-3" style="background:var(--muted)">We confirmed your payment for <?= e(implode(', ', $newlyPaid)) ?>. Your invoice is on its way to your email.</p>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <div class="text-center py-16 border" style="border-color:var(--border)">
      <p class="font-display text-2xl font-light mb-4">You haven't placed an order yet</p>
      <a href="<?= url('views/shop.php') ?>" class="px-6 py-3 text-sm font-medium btn-primary inline-block">Start shopping</a>
    </div>
  <?php else: ?>
  <div class="border" style="border-color:var(--border)">
    <?php foreach ($rows as $r): $paid = $r['payment_status'] === 'paid'; ?>
    <div class="p-5 border-b last:border-b-0 flex flex-col md:flex-row md:items-center gap-4" style="border-color:var(--border)">
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mb-1">
          <p class="font-display text-lg"><?= e($r['ref']) ?></p>
          <span class="text-xs uppercase tracking-widest px-2 py-0.5" style="<?= $paid ? 'background:var(--primary);color:var(--primary-foreground)' : 'background:var(--muted);color:var(--accent)' ?>"><?= $paid ? 'Paid' : ($r['payment_status'] === 'failed' ? 'Payment failed' : 'Awaiting payment') ?></span>
          <?php if ($paid): ?><span class="text-xs uppercase tracking-widest" style="color:var(--muted-foreground)"><?= e($nice($r['status'])) ?></span><?php endif; ?>
        </div>
        <p class="text-xs mb-1" style="color:var(--muted-foreground)">Ordered <?= e(date('j M Y', strtotime($r['created_at']))) ?> · Delivery <?= e(date('D, j M', strtotime($r['delivery_date']))) ?>, <?= e($r['time_window']) ?></p>
        <p class="text-sm truncate"><?= (int)$r['item_count'] ?> item<?= (int)$r['item_count'] === 1 ? '' : 's' ?>: <?= e($r['item_names']) ?></p>
      </div>
      <p class="font-display text-xl md:w-28 md:text-right"><?= money($r['total']) ?></p>
      <div class="flex gap-2 md:w-36 md:justify-end">
        <?php if ($paid): ?>
          <a href="<?= url('views/invoice.php?ref=' . urlencode($r['ref'])) ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">View invoice</a>
        <?php else: ?>
          <a href="<?= url('views/order_summary.php?ref=' . urlencode($r['ref'])) ?>" class="text-xs font-medium px-3 py-2 btn-primary">Pay now</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php
include __DIR__ . '/layout/footer.php';

if ($newlyPaid) {
    finish_response();                                         // page first...
    foreach ($newlyPaid as $ref) send_invoice_email_once($ref);  // ...then the invoice emails
}
