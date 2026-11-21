<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();

$statuses = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled'];
$db = new Database();
$filter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$viewId = (int)($_GET['id'] ?? 0);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$nice = fn($s) => ucwords(str_replace('_', ' ', (string)$s));

$order = $viewId ? $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$viewId]) : null;
$counts = [];
foreach ($db->fetchAll("SELECT status, COUNT(*) AS n FROM orders GROUP BY status") as $r) $counts[$r['status']] = $r['n'];
$rows = $filter
    ? $db->fetchAll("SELECT * FROM orders WHERE status = ? ORDER BY id DESC", [$filter])
    : $db->fetchAll("SELECT * FROM orders ORDER BY id DESC");

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
      <div class="grid md:grid-cols-2 gap-x-8 gap-y-2 text-sm">
        <p><span style="color:var(--muted-foreground)">Placed:</span> <?= e($order['created_at']) ?></p>
        <p><span style="color:var(--muted-foreground)">Status:</span> <?= e($nice($order['status'])) ?></p>
        <p><span style="color:var(--muted-foreground)">Email:</span> <?= e($order['email'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Phone:</span> <?= e($order['phone'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Delivery date:</span> <?= e($order['delivery_date'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Time window:</span> <?= e($order['time_window'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Payment method:</span> <?= e($order['pay_method'] ?? '-') ?></p>
        <p><span style="color:var(--muted-foreground)">Payment status:</span> <?= e($nice($order['payment_status'] ?? '')) ?></p>
        <p><span style="color:var(--muted-foreground)">Subtotal:</span> <?= money($order['subtotal'] ?? 0) ?></p>
        <p><span style="color:var(--muted-foreground)">Delivery:</span> <?= money($order['delivery'] ?? 0) ?></p>
        <p><span style="color:var(--muted-foreground)">Pass fee:</span> <?= money($order['pass_fee'] ?? 0) ?></p>
        <p class="font-medium"><span style="color:var(--muted-foreground)">Total:</span> <?= money($order['total'] ?? 0) ?></p>
      </div>
      <form method="post" action="<?= url('actions/admin_order_action.php') ?>" class="flex gap-3 mt-6">
        <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
        <select name="status" class="px-4 py-3 text-sm border bg-transparent" style="border-color:var(--border)">
          <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $order['status'] ? 'selected' : '' ?>><?= e($nice($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="px-6 py-3 text-sm font-medium btn-primary">Update status</button>
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