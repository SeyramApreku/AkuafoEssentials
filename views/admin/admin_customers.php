<?php
require_once __DIR__ . '/../../core/core.php';
require_admin();

$db = new Database();
$q = trim($_GET['q'] ?? '');
$viewId = (int)($_GET['id'] ?? 0);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$nice = fn($s) => ucwords(str_replace('_', ' ', (string)$s));

$cust = $viewId ? $db->fetchOne("SELECT * FROM aku_customer WHERE customer_id = ? AND user_role = 2", [$viewId]) : null;
$history = $cust ? $db->fetchAll("SELECT * FROM aku_orders WHERE user_id = ? ORDER BY id DESC", [$viewId]) : [];
$spent = 0;
foreach ($history as $h) if (($h['status'] ?? '') !== 'cancelled') $spent += (float)($h['total'] ?? 0);

if ($q !== '') {
    $like = '%' . $q . '%';
    $rows = $db->fetchAll("SELECT * FROM aku_customer WHERE user_role = 2 AND (customer_name LIKE ? OR customer_email LIKE ? OR customer_contact LIKE ?) ORDER BY customer_id DESC", [$like, $like, $like]);
} else {
    $rows = $db->fetchAll("SELECT * FROM aku_customer WHERE user_role = 2 ORDER BY customer_id DESC");
}

$pageTitle = 'Customers | Akuafo & Essentials';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Manage customers</h1>

  <?php if ($cust): ?>
    <a href="admin_customers.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="text-xs uppercase tracking-widest mb-6 inline-block">&larr; All customers</a>
    <div class="border p-6 mb-8" style="border-color:var(--border)">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-display text-2xl"><?= e($cust['customer_name']) ?></h2>
        <span class="text-xs uppercase tracking-widest" style="color:<?= (int)($cust['is_active'] ?? 1) ? 'inherit' : '#b3261e' ?>"><?= (int)($cust['is_active'] ?? 1) ? 'Active' : 'Deactivated' ?></span>
      </div>
      <p class="text-sm mb-6" style="color:var(--muted-foreground)"><?= count($history) ?> orders &middot; <?= money($spent) ?> spent (cancelled orders excluded)</p>

      <form method="post" action="<?= url('actions/admin_customer_action.php') ?>" class="grid md:grid-cols-2 gap-4">
        <input type="hidden" name="act" value="save">
        <input type="hidden" name="id" value="<?= (int)$cust['customer_id'] ?>">
        <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Name</label>
          <input type="text" name="customer_name" value="<?= e($cust['customer_name']) ?>" required class="<?= $box ?>" style="border-color:var(--border)"></div>
        <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Email</label>
          <input type="email" name="customer_email" value="<?= e($cust['customer_email']) ?>" required class="<?= $box ?>" style="border-color:var(--border)"></div>
        <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Country</label>
          <input type="text" name="customer_country" value="<?= e($cust['customer_country']) ?>" required class="<?= $box ?>" style="border-color:var(--border)"></div>
        <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">City</label>
          <input type="text" name="customer_city" value="<?= e($cust['customer_city']) ?>" required class="<?= $box ?>" style="border-color:var(--border)"></div>
        <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Contact</label>
          <input type="text" name="customer_contact" value="<?= e($cust['customer_contact']) ?>" required class="<?= $box ?>" style="border-color:var(--border)"></div>
        <div class="md:col-span-2 flex gap-3">
          <button class="px-6 py-3 text-sm font-medium btn-primary">Save changes</button>
        </div>
      </form>

      <form method="post" action="<?= url('actions/admin_customer_action.php') ?>" class="mt-4">
        <input type="hidden" name="act" value="toggle">
        <input type="hidden" name="id" value="<?= (int)$cust['customer_id'] ?>">
        <button class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border);color:<?= (int)($cust['is_active'] ?? 1) ? '#b3261e' : 'inherit' ?>">
          <?= (int)($cust['is_active'] ?? 1) ? 'Deactivate account' : 'Reactivate account' ?>
        </button>
      </form>

      <h3 class="font-display text-xl mt-8 mb-3">Order history</h3>
      <div class="border" style="border-color:var(--border)">
        <?php foreach ($history as $o): ?>
          <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium"><?= e($o['ref'] ?? ('#' . $o['id'])) ?> &middot; <?= money($o['total'] ?? 0) ?></p>
              <p class="text-xs" style="color:var(--muted-foreground)"><?= e($o['created_at']) ?> &middot; Payment: <?= e($nice($o['payment_status'] ?? '')) ?></p>
            </div>
            <span class="text-xs uppercase tracking-widest"><?= e($nice($o['status'])) ?></span>
            <a href="admin_orders.php?id=<?= (int)$o['id'] ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">View</a>
          </div>
        <?php endforeach; ?>
        <?php if (!$history): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No orders yet.</p><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="get" class="flex gap-3 mb-6">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone" class="<?= $box ?>" style="border-color:var(--border)">
    <button class="px-6 py-3 text-sm font-medium btn-primary">Search</button>
    <?php if ($q !== ''): ?><a href="admin_customers.php" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Clear</a><?php endif; ?>
  </form>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($rows as $r): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate"><?= e($r['customer_name']) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['customer_email']) ?> &middot; <?= e($r['customer_contact']) ?> &middot; <?= e($r['customer_city']) ?></p>
        </div>
        <span class="text-xs uppercase tracking-widest" style="color:<?= (int)($r['is_active'] ?? 1) ? 'inherit' : '#b3261e' ?>"><?= (int)($r['is_active'] ?? 1) ? 'Active' : 'Deactivated' ?></span>
        <a href="?id=<?= (int)$r['customer_id'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">View</a>
      </div>
    <?php endforeach; ?>
    <?php if (!$rows): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No customers found.</p><?php endif; ?>
  </div>
</div>
</body>
</html>