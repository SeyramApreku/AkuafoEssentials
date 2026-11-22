<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/order_class.php';
require_admin();

$statuses = order_statuses();
$db = new Database();
$filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$viewId = (int)($_GET['id'] ?? 0);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$nice = fn($s) => ucwords(str_replace('_', ' ', (string)$s));

$order = $viewId ? $db->fetchOne("SELECT * FROM aku_orders WHERE id = ?", [$viewId]) : null;
$counts = [];
$credit = null;
$items = $order ? (new OrderClass())->items($order['id']) : [];
$cust  = ($order && $order['user_id']) ? $db->fetchOne('SELECT customer_id, customer_name FROM aku_customer WHERE customer_id = ?', [$order['user_id']]) : null;

if ($order && !empty($order['user_id'])) {
    $mem = $db->fetchOne("SELECT tier FROM aku_memberships WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1", [$order['user_id']]);
    if ($mem) {
        $allow = 0;
        foreach (membership_tiers() as $m) if (strtolower($m['name']) === strtolower($mem['tier'])) $allow = $m['delivery'];
        $u = $db->fetchOne("SELECT COALESCE(SUM(delivery_credit), 0) AS used FROM aku_orders WHERE user_id = ? AND status <> 'cancelled' AND payment_status = 'paid' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", [$order['user_id']]);
        $used = (float)$u['used'];
        $credit = ['tier' => $mem['tier'], 'allow' => $allow, 'used' => $used, 'left' => max(0, $allow - $used)];
    }
}
foreach ($db->fetchAll("SELECT status, COUNT(*) AS n FROM aku_orders WHERE order_type <> 'entertaining' GROUP BY status") as $r) $counts[$r['status']] = $r['n'];
$rows = $filter
    ? $db->fetchAll("SELECT * FROM aku_orders WHERE order_type <> 'entertaining' AND status = ? ORDER BY id DESC", [$filter])
    : $db->fetchAll("SELECT * FROM aku_orders WHERE order_type <> 'entertaining' ORDER BY id DESC");

$pageTitle = 'Orders | Akuafo & Essentials';
include __DIR__ . '/../layout/admin_header.php';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Manage orders</h1>

  <?php if ($order): ?>
    <a href="admin_orders.php<?= $filter ? '?status=' . e($filter) : '' ?>" class="text-xs uppercase tracking-widest mb-6 inline-block">&larr; All orders</a>
    <div class="border p-6 mb-8" style="border-color:var(--border)">
      <h2 class="font-display text-2xl mb-4">Order <?= e($order['ref'] ?? ('#' . $order['id'])) ?></h2>

      <?php if ($order['status'] !== 'cancelled'): $at = array_search($order['status'], ['pending', 'paid', 'shipped', 'delivered'], true); ?>
      <div class="grid grid-cols-4 gap-1 mb-6">
        <?php foreach (['pending', 'paid', 'shipped', 'delivered'] as $k => $s): ?>
          <div>
            <div class="h-1.5 mb-1.5" style="background:<?= $at !== false && $k <= $at ? 'var(--primary)' : 'var(--border)' ?>"></div>
            <p class="text-xs uppercase tracking-widest" style="color:<?= $k === $at ? 'var(--foreground)' : 'var(--muted-foreground)' ?>"><?= e($nice($s)) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="grid md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
        <p><span style="color:var(--muted-foreground)">Placed:</span> <?= e($order['created_at']) ?></p>
        <p><span style="color:var(--muted-foreground)">Status:</span> <?= e($nice($order['status'])) ?></p>
        <p><span style="color:var(--muted-foreground)">Customer:</span> <?php if ($cust): ?><a href="admin_customers.php?id=<?= (int)$cust['customer_id'] ?>" class="underline"><?= e($cust['customer_name']) ?></a><?php else: ?>Guest<?php endif; ?></p>
        <p><span style="color:var(--muted-foreground)">Paystack reference:</span> <?= e($order['paystack_reference'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Invoice emailed:</span> <?= !empty($order['invoice_sent_at']) ? e($order['invoice_sent_at']) : 'Not yet' ?></p>
        <p><span style="color:var(--muted-foreground)">Email:</span> <?= e($order['email'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Phone:</span> <?= e($order['phone'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Delivery date:</span> <?= e($order['delivery_date'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Time window:</span> <?= e($order['time_window'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Payment method:</span> <?= e($order['pay_method'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Payment status:</span> <?= e($nice($order['payment_status'] ?? '')) ?></p>
        <p><span style="color:var(--muted-foreground)">Subtotal:</span> <?= money($order['subtotal'] ?? 0) ?></p>
        <?php if ($a = order_address_line($order)): ?><p class="md:col-span-2"><span style="color:var(--muted-foreground)">Deliver to:</span> <?= e($a) ?><?= !empty($order['delivery_notes']) ? ' · <em>' . e($order['delivery_notes']) . '</em>' : '' ?></p><?php endif; ?>
        <p><span style="color:var(--muted-foreground)">Delivery area:</span> <?= e($order['delivery_zone'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Delivery fee:</span> <?= money(($order['delivery'] ?? 0) + ($order['delivery_credit'] ?? 0)) ?></p>
        <p><span style="color:var(--muted-foreground)">Covered by membership:</span> <?= money($order['delivery_credit'] ?? 0) ?></p>
        <p><span style="color:var(--muted-foreground)">Delivery paid by customer:</span> <?= money($order['delivery'] ?? 0) ?></p>
        <p class="font-medium"><span style="color:var(--muted-foreground)">Total:</span> <?= money($order['total'] ?? 0) ?></p>
        <?php if ($credit): ?>
        <p class="md:col-span-2"><span style="color:var(--muted-foreground)"><?= e(ucfirst($credit['tier'])) ?> delivery credit this month:</span> <?= money($credit['used']) ?> used of <?= money($credit['allow']) ?>, <strong><?= money($credit['left']) ?> left</strong></p>
        <?php endif; ?>
      </div>

      <table class="w-full text-sm mt-6">
        <thead>
          <tr class="text-xs uppercase tracking-widest text-left border-b" style="color:var(--muted-foreground);border-color:var(--border)">
            <th class="py-2 font-normal">Item</th><th class="py-2 font-normal text-right">Qty</th><th class="py-2 font-normal text-right">Unit price</th><th class="py-2 font-normal text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i): ?>
          <tr class="border-b" style="border-color:var(--border)">
            <td class="py-2"><?= e($i['name']) ?><?php if (!empty($i['detail'])): ?><span class="block text-xs" style="color:var(--muted-foreground)"><?= e($i['detail']) ?></span><?php endif; ?></td>
            <td class="py-2 text-right"><?= (int)$i['qty'] ?></td>
            <td class="py-2 text-right whitespace-nowrap"><?= money($i['price']) ?></td>
            <td class="py-2 text-right whitespace-nowrap"><?= money($i['price'] * $i['qty']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$items): ?><tr><td colspan="4" class="py-3" style="color:var(--muted-foreground)">No items recorded.</td></tr><?php endif; ?>
        </tbody>
      </table>

      <form method="post" action="<?= url('actions/admin_order_action.php') ?>" class="flex flex-wrap gap-3 mt-6 items-center">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <select name="status" class="px-4 py-3 text-sm border bg-transparent" style="border-color:var(--border)">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= e($nice($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="px-6 py-3 text-sm font-medium btn-primary">Update status</button>
        <?php if ($order['payment_status'] !== 'paid'): ?><span class="text-xs" style="color:var(--accent)">Not paid yet: only Pending or Cancelled can be saved.</span><?php endif; ?>
      </form>
    </div>
  <?php endif; ?>

  <div class="flex flex-wrap gap-2 mb-6 text-xs uppercase tracking-widest">
    <a href="admin_orders.php" class="px-3 py-2 border" style="border-color:var(--border)"><?= !$filter ? '<strong>All</strong>' : 'All' ?> (<?= array_sum($counts) ?>)</a>
    <?php foreach ($statuses as $s): ?>
      <a href="?status=<?= e($s) ?>" class="px-3 py-2 border" style="border-color:var(--border)"><?= $filter === $s ? '<strong>' . e($nice($s)) . '</strong>' : e($nice($s)) ?> (<?= (int)($counts[$s] ?? 0) ?>)</a>
    <?php endforeach; ?>
  </div>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($rows as $r): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($r['ref'] ?? ('#' . $r['id'])) ?> &middot; <?= money($r['total'] ?? 0) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['email'] ?? $r['phone'] ?? '') ?> &middot; <?= e($r['created_at']) ?> &middot; Payment: <?= e($nice($r['payment_status'] ?? '')) ?></p>
        </div>
        <span class="text-xs uppercase tracking-widest"><?= e($nice($r['status'])) ?></span>
        <a href="?id=<?= (int)$r['id'] ?><?= $filter ? '&status=' . e($filter) : '' ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">View</a>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No orders yet.</p><?php endif; ?>
  </div>
</div>
</body>
</html>