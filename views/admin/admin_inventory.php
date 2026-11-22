<?php
// Admin: stock levels for produce, restocking, stock counts and the stock history.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/inventory_class.php';
require_admin();

$inv     = new InventoryClass();
$onlyLow = isset($_GET['low']);
$rows    = $inv->products($onlyLow);
$all     = $onlyLow ? $inv->products() : $rows;
$moves   = $inv->moves(30);
$counts  = ['out' => 0, 'low' => 0, 'value' => 0.0];
foreach ($all as $r) {
    if ($r['stock_qty'] === null) continue;
    if ((int)$r['stock_qty'] <= 0) $counts['out']++; elseif ((int)$r['stock_qty'] <= (int)$r['low_stock_at']) $counts['low']++;
    $counts['value'] += max(0, (int)$r['stock_qty']) * (float)$r['price'];
}
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Inventory | Admin';
include __DIR__ . '/../layout/admin_header.php';
$act = url('actions/admin_inventory_action.php');
$in  = 'px-2 py-1.5 text-sm border bg-transparent w-20';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Inventory</h1>

  <div class="grid sm:grid-cols-3 gap-4 mb-8">
    <div class="p-5 border" style="border-color:var(--border)"><p class="text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Sold out</p><p class="font-display text-3xl" style="color:<?= $counts['out'] ? 'var(--accent)' : 'inherit' ?>"><?= $counts['out'] ?></p></div>
    <div class="p-5 border" style="border-color:var(--border)"><p class="text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Running low</p><p class="font-display text-3xl"><?= $counts['low'] ?></p></div>
    <div class="p-5 border" style="border-color:var(--border)"><p class="text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Stock value (at selling price)</p><p class="font-display text-3xl"><?= money($counts['value']) ?></p></div>
  </div>

  <div class="flex gap-2 mb-4 text-xs uppercase tracking-widest">
    <a href="?" class="px-3 py-2 border <?= !$onlyLow ? 'pill-on' : '' ?>" style="border-color:var(--border)">All products</a>
    <a href="?low=1" class="px-3 py-2 border <?= $onlyLow ? 'pill-on' : '' ?>" style="border-color:var(--border)">Low or sold out (<?= $counts['out'] + $counts['low'] ?>)</a>
  </div>

  <div class="border overflow-x-auto mb-12" style="border-color:var(--border)">
    <table class="w-full text-sm">
      <thead><tr class="text-xs uppercase tracking-widest text-left" style="color:var(--muted-foreground);background:var(--muted)">
        <th class="p-3 font-normal">Product</th><th class="p-3 font-normal text-right">In stock</th><th class="p-3 font-normal text-right">Sold (30 days)</th>
        <th class="p-3 font-normal">Restock</th><th class="p-3 font-normal">Set count / warn at</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $tracked = $r['stock_qty'] !== null; $q = (int)$r['stock_qty'];
        [$label, $color] = !$tracked ? ['Not tracked', 'var(--muted-foreground)'] : ($q <= 0 ? ['Sold out', '#b3261e'] : ($q <= (int)$r['low_stock_at'] ? ['Low', 'var(--accent)'] : ['In stock', 'var(--primary)'])); ?>
        <tr class="border-t align-middle" style="border-color:var(--border)">
          <td class="p-3"><p class="font-medium"><?= e($r['name']) ?></p><p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['category']) ?> · <?= money($r['price']) ?> <?= e($r['unit']) ?></p></td>
          <td class="p-3 text-right whitespace-nowrap"><span class="font-display text-xl"><?= $tracked ? $q : '–' ?></span><span class="block text-xs" style="color:<?= $color ?>"><?= $label ?></span></td>
          <td class="p-3 text-right"><?= (int)$r['sold_30d'] ?></td>
          <td class="p-3">
            <form method="post" action="<?= $act ?>" class="flex items-center gap-2">
              <input type="hidden" name="act" value="restock"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= $onlyLow ? 'low' : '' ?>">
              <label class="sr-only" for="r<?= (int)$r['id'] ?>">Add quantity</label>
              <input id="r<?= (int)$r['id'] ?>" type="number" name="qty" min="1" max="10000" required placeholder="+ qty" class="<?= $in ?>" style="border-color:var(--border)">
              <button class="text-xs font-medium px-3 py-1.5 btn-primary">Add</button>
            </form>
          </td>
          <td class="p-3">
            <form method="post" action="<?= $act ?>" class="flex items-center gap-2">
              <input type="hidden" name="act" value="set"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= $onlyLow ? 'low' : '' ?>">
              <input type="number" name="qty" min="0" max="100000" value="<?= $tracked ? $q : '' ?>" placeholder="no limit" aria-label="Stock count" class="<?= $in ?>" style="border-color:var(--border)">
              <input type="number" name="low_at" min="0" max="10000" value="<?= (int)$r['low_stock_at'] ?>" aria-label="Warn when at or below" title="Warn when at or below" class="<?= $in ?> w-16" style="border-color:var(--border)">
              <button class="text-xs font-medium px-3 py-1.5 border" style="border-color:var(--border)">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="p-6" style="color:var(--muted-foreground)"><?= $onlyLow ? 'Nothing is low. Well stocked!' : 'No products yet.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="text-xs -mt-10 mb-12" style="color:var(--muted-foreground)">Leave "Set count" empty to stop tracking a product (it will always be available). Stock comes off when an order is paid and goes back if you cancel the order. Boxes, hampers and entertaining dishes are made to order and aren't counted here.</p>

  <h2 class="font-display text-2xl mb-4">Recent stock changes</h2>
  <div class="border" style="border-color:var(--border)">
    <?php foreach ($moves as $m): ?>
      <div class="flex items-center gap-4 px-4 py-2.5 border-b last:border-b-0 text-sm" style="border-color:var(--border)">
        <span class="w-14 text-right font-medium" style="color:<?= $m['change_qty'] < 0 ? '#b3261e' : 'var(--primary)' ?>"><?= $m['change_qty'] > 0 ? '+' : '' ?><?= (int)$m['change_qty'] ?></span>
        <span class="flex-1 truncate"><?= e($m['name']) ?> · <?= e(ucfirst($m['reason'])) ?><?= $m['ref'] ? ' (order ' . e($m['ref']) . ')' : '' ?><?= $m['note'] ? ' · ' . e($m['note']) : '' ?></span>
        <span class="text-xs whitespace-nowrap" style="color:var(--muted-foreground)"><?= e(date('j M, H:i', strtotime($m['created_at']))) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$moves): ?><p class="p-4 text-sm" style="color:var(--muted-foreground)">No stock changes yet.</p><?php endif; ?>
  </div>
</div>
</body>
</html>
