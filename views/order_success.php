<?php
require_once __DIR__ . '/../classes/order_class.php';
$pageTitle = 'Order placed | Akuafo & Essentials';
$order = (new OrderClass())->byRef($_GET['ref'] ?? '');
if (!$order) { header('Location: ' . '../index.php'); exit; }
if (($order['payment_status'] ?? '') !== 'paid') { header('Location: payment_failed.php?ref=' . urlencode($order['ref'])); exit; }
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-md mx-auto px-6 py-20 text-center">
  <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl btn-primary">✓</div>
  <h1 class="font-display text-3xl font-light mb-3">Order placed!</h1>
  <p class="text-xs mb-4" style="color:var(--muted-foreground)">Reference <strong><?= e($order['ref']) ?></strong></p>
  <p class="text-sm mb-6 leading-relaxed" style="color:var(--muted-foreground)">Your order is confirmed. Delivery booked for <strong><?= e(date('D, j M', strtotime($order['delivery_date']))) ?></strong>, <?= e($order['time_window']) ?>. Total <?= money($order['total']) ?>.</p>
  <p class="text-xs mb-8" style="color:var(--muted-foreground)">You'll receive an SMS confirmation shortly. Your produce is being harvested to order.</p>
  <a href="<?= url('index.php') ?>" class="px-8 py-3 text-sm font-medium btn-primary inline-block">Back to home</a>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
