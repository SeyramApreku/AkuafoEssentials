<?php
ob_start();   // lets finish_response() send the whole page before the email goes out
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';
require_once __DIR__ . '/../controllers/order_controller.php';

require_login('views/invoice.php?ref=' . urlencode($_GET['ref'] ?? ''));
$order = (new OrderClass())->byRef($_GET['ref'] ?? '');
if (!owns_order($order)) redirect(url('index.php'));
if ($order['payment_status'] !== 'paid') redirect(url('views/payment_failed.php?ref=' . urlencode($order['ref'])));
clear_cart_for_paid_order($order['ref']);   // before the header renders, so the cart badge shows empty

// Not emailed yet (just paid, or an earlier send failed): send it after the page is shown.
$sendEmail = empty($order['invoice_sent_at']);

$d = invoice_data($order);
$justPaid = isset($_GET['paid']);
$pageTitle = 'Invoice ' . $order['ref'] . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<style>
  @media print {
    body * { visibility: hidden; }
    #invoice, #invoice * { visibility: visible; }
    #invoice { position: absolute; left: 0; top: 0; width: 100%; padding: 0; }
    .no-print { display: none !important; }
  }
</style>
<div class="max-w-3xl mx-auto px-6 py-12">
  <?php if ($justPaid): ?>
  <div class="no-print mb-10 p-5 border" style="border-color:var(--primary);background:var(--muted)">
    <p class="font-display text-2xl font-light mb-1">Payment successful</p>
    <p class="text-sm" style="color:var(--muted-foreground)">Thank you for your order. Delivery is booked for <?= e($d['delivery']) ?>.
      <?= $sendEmail ? 'A copy of this invoice is on its way to ' . e($d['email']) . '.' : 'A copy of this invoice was emailed to ' . e($d['email']) . '.' ?></p>
  </div>
  <?php endif; ?>

  <div class="no-print flex items-center justify-between mb-8">
    <a href="<?= url('views/orders.php') ?>" class="text-xs" style="color:var(--muted-foreground)">&larr; My orders</a>
    <button type="button" onclick="window.print()" class="px-5 py-2.5 text-xs font-medium border" style="border-color:var(--border)">Print / save as PDF</button>
  </div>

  <div id="invoice" class="p-8 border" style="border-color:var(--border);background:var(--card)">
    <div class="flex items-start justify-between gap-6 mb-8">
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:var(--primary);color:var(--primary-foreground)">A</div>
        <span class="font-display text-base font-medium">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</span>
      </div>
      <div class="text-right">
        <p class="text-xs uppercase tracking-widest mb-1" style="color:var(--accent)">Invoice</p>
        <p class="font-display text-2xl font-light"><?= e($order['ref']) ?></p>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1.5 text-sm mb-8">
      <p><span style="color:var(--muted-foreground)">Order number:</span> <?= e($order['ref']) ?></p>
      <p><span style="color:var(--muted-foreground)">Order date:</span> <?= e($d['date']) ?></p>
      <p><span style="color:var(--muted-foreground)">Payment reference:</span> <?= e($d['pay_ref']) ?></p>
      <p><span style="color:var(--muted-foreground)">Payment:</span> Paid via Paystack</p>
      <p><span style="color:var(--muted-foreground)">Billed to:</span> <?= e($d['name'] ? $d['name'] . ', ' : '') ?><?= e($d['email']) ?></p>
      <?php if (!empty($order['phone'])): ?><p><span style="color:var(--muted-foreground)">Phone:</span> <?= e($order['phone']) ?></p><?php endif; ?>
      <p class="sm:col-span-2"><span style="color:var(--muted-foreground)"><?= ($order['order_type'] ?? '') === 'entertaining' ? 'Event delivery' : 'Delivery' ?>:</span> <?= e($d['delivery']) ?> (<?= e($order['delivery_zone']) ?>)</p>
      <?php if ($a = order_address_line($order)): ?><p class="sm:col-span-2"><span style="color:var(--muted-foreground)">Deliver to:</span> <?= e($a) ?><?= !empty($order['delivery_notes']) ? ' · <em>' . e($order['delivery_notes']) . '</em>' : '' ?></p><?php endif; ?>
      <?php if (!empty($order['event_address'])): ?><p class="sm:col-span-2"><span style="color:var(--muted-foreground)">Event address:</span> <?= e($order['event_address']) ?></p><?php endif; ?>
    </div>

    <table class="w-full text-sm mb-6">
      <thead>
        <tr class="text-xs uppercase tracking-widest text-left border-b" style="color:var(--muted-foreground);border-color:var(--foreground)">
          <th class="py-3 font-normal">Item</th><th class="py-3 font-normal text-right">Qty</th><th class="py-3 font-normal text-right">Unit price</th><th class="py-3 font-normal text-right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($d['items'] as $i): ?>
        <tr class="border-b" style="border-color:var(--border)">
          <td class="py-4"><?= e($i['name']) ?><?php if (!empty($i['detail'])): ?><span class="block text-xs" style="color:var(--muted-foreground)"><?= e($i['detail']) ?></span><?php endif; ?></td>
          <td class="py-4 text-right"><?= (int)$i['qty'] ?></td>
          <td class="py-4 text-right whitespace-nowrap"><?= money($i['price']) ?></td>
          <td class="py-4 text-right whitespace-nowrap"><?= money($i['price'] * $i['qty']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="text-sm space-y-1.5 mb-4 sm:w-2/3 sm:ml-auto">
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Shipping &amp; delivery</span><span><?= money($d['fee']) ?></span></div>
      <?php if ($d['credit'] > 0): ?>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Membership delivery credit</span><span>- <?= money($d['credit']) ?></span></div>
      <?php endif; ?>
      <?php if ($d['pass_fee'] > 0): ?>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Delivery pass</span><span><?= money($d['pass_fee']) ?></span></div>
      <?php endif; ?>
      <div class="flex justify-between font-medium text-lg border-t pt-3" style="border-color:var(--foreground)"><span>Total paid (GHS)</span><span><?= money($order['total']) ?></span></div>
    </div>
  </div>
</div>
<?php
include __DIR__ . '/layout/footer.php';

if ($sendEmail) {
    finish_response();                        // the customer now has the full invoice on screen
    send_invoice_email_once($order['ref']);   // never throws; sent once only, retried on the next visit if it fails
}
