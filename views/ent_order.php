<?php
// Customer: change the date / time / address of an entertaining order, or cancel it, until the cutoff.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/entertaining_controller.php';
require_once __DIR__ . '/../classes/order_class.php';

$ref = $_GET['ref'] ?? '';
require_login('views/ent_order.php?ref=' . urlencode($ref));
$orders = new OrderClass();
$order  = $orders->byRef($ref);
if (!owns_order($order) || ($order['order_type'] ?? '') !== 'entertaining') redirect(url('views/orders.php'));

[$canChange, $info] = ent_can_change($order);
$noticeH  = ent_order_notice_hours($order['id']);
$earliest = ent_earliest_date($noticeH)->format('Y-m-d');
$latest   = (new DateTime('today'))->modify('+' . ENT_BOOK_AHEAD_DAYS . ' days')->format('Y-m-d');
$items    = $orders->items($order['id']);
$pageMsg      = $_SESSION['ent_order_msg'] ?? null; unset($_SESSION['ent_order_msg']);
$pageTitle = 'Your event order ' . $order['ref'] . ' | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
?>
<div class="max-w-2xl mx-auto px-6 py-12">
  <a href="<?= url('views/orders.php') ?>" class="text-xs mb-8 inline-block" style="color:var(--muted-foreground)">&larr; My orders</a>
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Entertaining order</p>
  <h1 class="font-display text-3xl font-light mb-6"><?= e($order['ref']) ?></h1>
  <?php if ($pageMsg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted);color:<?= $pageMsg[0] ? 'var(--primary)' : 'var(--accent)' ?>"><?= e($pageMsg[1]) ?></p><?php endif; ?>

  <div class="text-sm mb-8 p-4 border space-y-1" style="border-color:var(--border)">
    <p><span style="color:var(--muted-foreground)">Event:</span> <?= e(date('D j M Y', strtotime($order['delivery_date']))) ?>, <?= e($order['time_window']) ?></p>
    <p><span style="color:var(--muted-foreground)">Area:</span> <?= e($order['delivery_zone']) ?><?= $order['event_address'] ? ' · ' . e($order['event_address']) : '' ?></p>
    <p><span style="color:var(--muted-foreground)">Status:</span> <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?><?= $order['refund_status'] ? ' · Refund ' . e($order['refund_status']) : '' ?></p>
    <p><span style="color:var(--muted-foreground)">Items:</span> <?= e(implode(', ', array_map(fn($i) => $i['name'] . ' ×' . $i['qty'], $items))) ?></p>
    <p><span style="color:var(--muted-foreground)">Total paid:</span> <?= money($order['total']) ?></p>
  </div>

  <?php if (!$canChange): ?>
    <p class="text-sm p-4" style="background:var(--muted)"><?= e($info) ?></p>
  <?php else: ?>
    <p class="text-xs mb-6" style="color:var(--muted-foreground)">You can make changes until <strong><?= e($info) ?></strong>. To change the items, cancel and place a new order.</p>

    <form method="post" action="<?= url('actions/ent_order_action.php') ?>" class="mb-10 p-5 border" style="border-color:var(--border)">
      <input type="hidden" name="act" value="change"><input type="hidden" name="ref" value="<?= e($order['ref']) ?>">
      <h2 class="font-display text-xl mb-4">Change date, time or address</h2>
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)" for="d">Event date</label>
      <input type="date" id="d" name="date" required min="<?= $earliest ?>" max="<?= $latest ?>" value="<?= e($order['delivery_date']) ?>" class="<?= $box ?> mb-4" style="border-color:var(--border)">
      <div class="flex flex-col gap-2 mb-4">
        <?php foreach (time_windows() as $t): ?>
          <label class="pick relative py-3 px-4 text-sm border cursor-pointer" style="border-color:var(--border)"><input type="radio" name="window" value="<?= e($t) ?>" <?= $order['time_window'] === $t ? 'checked' : '' ?>><?= e($t) ?></label>
        <?php endforeach; ?>
      </div>
      <label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)" for="a">Event address (optional)</label>
      <textarea id="a" name="event_address" rows="2" maxlength="255" class="<?= $box ?> mb-4" style="border-color:var(--border)"><?= e($order['event_address'] ?? '') ?></textarea>
      <button class="px-6 py-3 text-sm font-medium btn-primary">Save changes</button>
    </form>

    <form method="post" action="<?= url('actions/ent_order_action.php') ?>" class="p-5 border" style="border-color:var(--border)"
          data-confirm="Cancel this order? We will refund <?= e(money($order['total'])) ?> to your original payment method." data-confirm-yes="Yes, cancel and refund">
      <input type="hidden" name="act" value="cancel"><input type="hidden" name="ref" value="<?= e($order['ref']) ?>">
      <h2 class="font-display text-xl mb-2">Cancel order</h2>
      <p class="text-xs mb-4" style="color:var(--muted-foreground)">We'll refund <?= money($order['total']) ?> through Paystack to the card or mobile money account you paid with. Refunds usually arrive within 5 to 10 working days.</p>
      <button class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border);color:#b3261e">Cancel and refund</button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
