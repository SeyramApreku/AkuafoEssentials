<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/order_class.php';

require_login('views/order_summary.php?ref=' . urlencode($_GET['ref'] ?? ''));
$orders = new OrderClass();
$order = $orders->byRef($_GET['ref'] ?? '');
if (!owns_order($order)) redirect(url('index.php'));
if ($order['payment_status'] === 'paid') redirect(url('views/invoice.php?ref=' . urlencode($order['ref'])));

$items = $orders->items($order['id']);
$fee = (float)$order['delivery'] + (float)$order['delivery_credit'];
$pageTitle = 'Order summary | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<div class="max-w-2xl mx-auto px-6 py-12">
  <a href="<?= url('views/checkout.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">&larr; Back to checkout</a>
  <h1 class="font-display text-3xl font-light mb-2">Order summary</h1>
  <p class="text-xs mb-8" style="color:var(--muted-foreground)">Please check everything below before you pay.</p>

  <div class="text-sm mb-8 space-y-1">
    <p><span style="color:var(--muted-foreground)">Reference:</span> <?= e($order['ref']) ?></p>
    <?php if (($order["order_type"] ?? "") === "entertaining"): ?><p class="text-xs uppercase tracking-widest" style="color:var(--accent)">Entertaining order</p><?php endif; ?>
    <p><span style="color:var(--muted-foreground)"><?= ($order["order_type"] ?? "") === "entertaining" ? "Event delivery" : "Delivery" ?>:</span> <?= e(date('D, j M', strtotime($order['delivery_date']))) ?>, <?= e($order['time_window']) ?></p>
    <p><span style="color:var(--muted-foreground)">Area:</span> <?= e($order['delivery_zone']) ?></p>
    <?php if (!empty($order['event_address'])): ?><p><span style="color:var(--muted-foreground)">Event address:</span> <?= e($order['event_address']) ?></p><?php endif; ?>
    <p><span style="color:var(--muted-foreground)">Email:</span> <?= e($orders->customer($order['user_id'])['customer_email'] ?? $order['email']) ?></p>
    <?php if (!empty($order['phone'])): ?><p><span style="color:var(--muted-foreground)">Mobile number:</span> <?= e($order['phone']) ?></p><?php endif; ?>
  </div>

  <table class="w-full text-sm mb-8">
    <thead>
      <tr class="text-xs uppercase tracking-widest text-left" style="color:var(--muted-foreground)">
        <th class="py-3 font-normal">Product</th><th class="py-3 font-normal text-right">Qty</th><th class="py-3 font-normal text-right">Unit price</th><th class="py-3 font-normal text-right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i): ?>
      <tr class="border-t" style="border-color:var(--border)">
        <td class="py-3"><?= e($i['name']) ?><?php if (!empty($i['detail'])): ?><span class="block text-xs" style="color:var(--muted-foreground)"><?= e($i['detail']) ?></span><?php endif; ?></td>
        <td class="py-3 text-right"><?= (int)$i['qty'] ?></td>
        <td class="py-3 text-right"><?= money($i['price']) ?></td>
        <td class="py-3 text-right"><?= money($i['price'] * $i['qty']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="space-y-1.5 text-sm mb-8 p-4 border" style="border-color:var(--border)">
    <div class="flex justify-between"><span style="color:var(--muted-foreground)">Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
    <div class="flex justify-between"><span style="color:var(--muted-foreground)">Delivery</span><span><?= money($fee) ?></span></div>
    <?php if ((float)$order['delivery_credit'] > 0): ?>
    <div class="flex justify-between"><span style="color:var(--muted-foreground)">Membership delivery credit</span><span>- <?= money($order['delivery_credit']) ?></span></div>
    <?php endif; ?>
    <div class="flex justify-between font-medium border-t pt-2" style="border-color:var(--border)"><span>Total to pay</span><span><?= money($order['total']) ?></span></div>
  </div>

  <a href="<?= url('actions/process_payment_action.php?ref=' . urlencode($order['ref'])) ?>" class="block w-full py-3 text-sm font-medium btn-primary text-center">Pay <?= money($order['total']) ?> with Paystack &rarr;</a>
  <p class="text-xs mt-3 text-center" style="color:var(--muted-foreground)">You will be taken to Paystack to complete your payment securely.</p>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>