<?php
// Admin: entertaining orders, sorted by event date, with their own status flow.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/order_class.php';
require_admin();

$db       = new Database();
$statuses = ent_order_statuses();
$nice     = fn($s) => ucwords(str_replace('_', ' ', (string)$s));
$when     = in_array($_GET['when'] ?? '', ['upcoming', 'past', 'all'], true) ? $_GET['when'] : 'upcoming';
$filter   = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$viewId   = (int)($_GET['id'] ?? 0);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);

$where = "o.order_type = 'entertaining'";
$p = [];
if ($when === 'upcoming') $where .= ' AND o.delivery_date >= CURDATE()';
if ($when === 'past')     $where .= ' AND o.delivery_date < CURDATE()';
if ($filter) { $where .= ' AND o.status = ?'; $p[] = $filter; }
$order = $when === 'past' ? 'o.delivery_date DESC, o.time_window' : 'o.delivery_date ASC, o.time_window';
$rows = $db->fetchAll("SELECT o.*, c.customer_name FROM aku_orders o LEFT JOIN aku_customer c ON c.customer_id = o.user_id WHERE $where ORDER BY $order", $p);

$o = $viewId ? $db->fetchOne("SELECT o.*, c.customer_name, c.customer_contact FROM aku_orders o LEFT JOIN aku_customer c ON c.customer_id = o.user_id WHERE o.id = ? AND o.order_type = 'entertaining'", [$viewId]) : null;
$items = $o ? (new OrderClass())->items($o['id']) : [];

$pageTitle = 'Entertaining orders | Admin';
include __DIR__ . '/../layout/admin_header.php';
$qs = fn($extra) => '?' . http_build_query(array_filter(['when' => $when, 'status' => $filter] + $extra));
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin · Entertaining</p>
  <h1 class="font-display text-4xl font-light mb-6">Entertaining orders</h1>

  <?php if ($o): ?>
    <a href="<?= $qs([]) ?>" class="text-xs uppercase tracking-widest mb-6 inline-block">&larr; All event orders</a>
    <div class="border p-6 mb-8" style="border-color:var(--border)">
      <h2 class="font-display text-2xl mb-1">Order <?= e($o['ref']) ?></h2>
      <p class="text-sm mb-5" style="color:var(--accent)">Event: <?= e(date('l j F Y', strtotime($o['delivery_date']))) ?>, <?= e($o['time_window']) ?></p>
      <div class="grid md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
        <p><span style="color:var(--muted-foreground)">Customer:</span> <?php if ($o['user_id']): ?><a class="underline" href="admin_customers.php?id=<?= (int)$o['user_id'] ?>"><?= e($o['customer_name'] ?? '#' . $o['user_id']) ?></a><?php else: ?>-<?php endif; ?></p>
        <p><span style="color:var(--muted-foreground)">Phone:</span> <?= e($o['phone'] ?: ($o['customer_contact'] ?? '-')) ?></p>
        <p><span style="color:var(--muted-foreground)">Area:</span> <?= e($o['delivery_zone'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Event address:</span> <?= e($o['event_address'] ?: 'Usual address in area') ?></p>
        <p><span style="color:var(--muted-foreground)">Status:</span> <?= e($nice($o['status'])) ?></p>
        <p><span style="color:var(--muted-foreground)">Payment:</span> <?= e($nice($o['payment_status'])) ?> · <?= e($o['paystack_reference'] ?? '-') ?></p>
        <?php if ($o['refund_status']): ?><p><span style="color:var(--muted-foreground)">Refund:</span> <strong><?= e($nice($o['refund_status'])) ?></strong><?= $o['refund_status'] === 'failed' ? ' (refund by hand in Paystack)' : '' ?></p><?php endif; ?>
        <p class="font-medium"><span style="color:var(--muted-foreground)">Total:</span> <?= money($o['total']) ?></p>
      </div>
      <table class="w-full text-sm mt-6">
        <thead><tr class="text-xs uppercase tracking-widest text-left border-b" style="color:var(--muted-foreground);border-color:var(--border)"><th class="py-2 font-normal">Item</th><th class="py-2 font-normal text-right">Qty</th><th class="py-2 font-normal text-right">Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $i): ?>
          <tr class="border-b" style="border-color:var(--border)"><td class="py-2"><?= e($i['name']) ?><span class="block text-xs" style="color:var(--muted-foreground)"><?= e($i['detail'] ?? '') ?></span></td><td class="py-2 text-right"><?= (int)$i['qty'] ?></td><td class="py-2 text-right"><?= money($i['price'] * $i['qty']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <form method="post" action="<?= url('actions/admin_ent_order_action.php') ?>" class="flex flex-wrap gap-3 mt-6 items-center">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
        <select name="status" class="px-4 py-3 text-sm border bg-transparent" style="border-color:var(--border)">
          <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= e($nice($s)) ?></option><?php endforeach; ?>
        </select>
        <button class="px-6 py-3 text-sm font-medium btn-primary">Update status</button>
        <span class="text-xs" style="color:var(--muted-foreground)">The customer is emailed when you set Confirmed, Out for delivery or Delivered. Cancelling here does not refund: refund in Paystack.</span>
      </form>
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap gap-2 mb-4 text-xs uppercase tracking-widest">
    <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $k => $label): ?>
      <a href="?<?= http_build_query(array_filter(['when' => $k, 'status' => $filter])) ?>" class="px-3 py-2 border <?= $when === $k ? 'pill-on' : '' ?>" style="border-color:var(--border)"><?= $label ?></a>
    <?php endforeach; ?>
    <span class="mx-2"></span>
    <a href="?when=<?= $when ?>" class="px-3 py-2 border" style="border-color:var(--border)"><?= !$filter ? '<strong>Any status</strong>' : 'Any status' ?></a>
    <?php foreach ($statuses as $s): ?>
      <a href="?when=<?= $when ?>&status=<?= e($s) ?>" class="px-3 py-2 border" style="border-color:var(--border)"><?= $filter === $s ? '<strong>' . e($nice($s)) . '</strong>' : e($nice($s)) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="border" style="border-color:var(--border)">
    <?php $lastDate = null; foreach ($rows as $r): ?>
      <?php if ($r['delivery_date'] !== $lastDate): $lastDate = $r['delivery_date']; ?>
        <div class="px-4 py-2 text-xs uppercase tracking-widest" style="background:var(--muted)"><?= e(date('l j F Y', strtotime($r['delivery_date']))) ?></div>
      <?php endif; ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($r['ref']) ?> &middot; <?= money($r['total']) ?> &middot; <?= e($r['customer_name'] ?? '') ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['time_window']) ?> &middot; <?= e($r['delivery_zone']) ?><?= $r['event_address'] ? ' &middot; ' . e(mb_strimwidth($r['event_address'], 0, 50, '…')) : '' ?> &middot; Payment: <?= e($nice($r['payment_status'])) ?></p>
        </div>
        <span class="text-xs uppercase tracking-widest"><?= e($nice($r['status'])) ?></span>
        <a href="<?= $qs(['id' => (int)$r['id']]) ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">View</a>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No entertaining orders here.</p><?php endif; ?>
  </div>
</div>
</body>
</html>
