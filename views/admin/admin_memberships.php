<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/membership_class.php';
require_admin();

// Everyone who has paid for a membership: tier, dates, restaurant voucher (code + balance), delivery credit.
$mc = new MembershipClass();
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60);
$rows = $mc->adminList($q);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);

$live = array_filter($rows, fn($r) => (int)$r['is_live']);
$byTier = [];
foreach ($live as $r) $byTier[ucfirst($r['tier'])] = ($byTier[ucfirst($r['tier'])] ?? 0) + 1;
$voucherLeft = array_sum(array_map(fn($r) => $r['voucher'] ? (float)$r['voucher']['balance'] : 0, $live));
$monthRevenue = array_sum(array_map(fn($r) => strtotime($r['starts_at'] ?? '') >= strtotime(date('Y-m-01')) ? (float)$r['price'] : 0, $rows));
$restaurants = [];
try { $restaurants = array_column($mc->fetchAll("SELECT DISTINCT COALESCE(NULLIF(restaurant_name, ''), name) AS r FROM aku_partners ORDER BY r"), 'r'); } catch (Throwable $e) {}

$pageTitle = 'Memberships | Akuafo & Essentials';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Memberships</h1>
  <?php if ($msg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted)"><?= e(is_array($msg) ? implode(' ', $msg) : $msg) ?></p><?php endif; ?>

  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <div class="p-5 border" style="border-color:var(--border);background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Active members</p><p class="font-display text-3xl"><?= count($live) ?></p>
      <p class="text-xs mt-1" style="color:var(--muted-foreground)"><?= $byTier ? e(implode(' · ', array_map(fn($t, $n) => "$n $t", array_keys($byTier), $byTier))) : 'None yet' ?></p></div>
    <div class="p-5 border" style="border-color:var(--border);background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Membership sales this month</p><p class="font-display text-3xl"><?= money($monthRevenue) ?></p></div>
    <div class="p-5 border" style="border-color:var(--border);background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">Voucher balance outstanding</p><p class="font-display text-3xl"><?= money($voucherLeft) ?></p>
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">What active members can still spend at restaurants</p></div>
    <form method="get" class="p-5 border flex flex-col gap-2" style="border-color:var(--border);background:var(--card)">
      <label for="mq" class="text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">Find a member</label>
      <input id="mq" name="q" value="<?= e($q) ?>" placeholder="Name, email or tier" class="<?= $box ?>" style="border-color:var(--border)">
    </form>
  </div>

  <!-- Record a voucher use (the restaurant tells us the code and the bill) -->
  <form method="post" action="<?= url('actions/admin_membership_action.php') ?>" class="grid md:grid-cols-4 gap-4 items-end mb-10 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-4">Record a voucher use</h2>
    <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Voucher code</label>
      <input name="code" required maxlength="20" placeholder="e.g. AKU-G-7KQ2MX" class="<?= $box ?>" style="border-color:var(--border);text-transform:uppercase"></div>
    <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Amount used (GH₵)</label>
      <input name="amount" type="number" required min="0.01" step="0.01" max="100000" placeholder="e.g. 120.00" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div><label class="text-xs uppercase tracking-widest mb-2 block" style="color:var(--muted-foreground)">Restaurant</label>
      <input name="restaurant" required minlength="2" maxlength="120" list="restaurants" placeholder="e.g. Adom Kitchen" class="<?= $box ?>" style="border-color:var(--border)">
      <datalist id="restaurants"><?php foreach ($restaurants as $r): ?><option value="<?= e($r) ?>"><?php endforeach; ?></datalist></div>
    <button class="px-6 py-3 text-sm font-medium btn-primary">Take off voucher</button>
  </form>

  <div class="border overflow-x-auto" style="border-color:var(--border)">
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs uppercase tracking-widest" style="color:var(--muted-foreground)">
        <th class="p-3">Member</th><th class="p-3">Plan</th><th class="p-3">Dates</th><th class="p-3">Voucher</th><th class="p-3">Voucher left</th><th class="p-3">Delivery credit used</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $t = tier_info($r['tier']); $v = $r['voucher']; $isLive = (int)$r['is_live']; ?>
        <tr class="border-t align-top" style="border-color:var(--border);<?= $isLive ? '' : 'opacity:.55' ?>">
          <td class="p-3"><p class="font-medium"><?= e($r['customer_name']) ?></p><p class="text-xs" style="color:var(--muted-foreground)"><?= e($r['customer_email']) ?><?= $r['customer_contact'] ? ' · ' . e($r['customer_contact']) : '' ?></p></td>
          <td class="p-3"><p class="flex items-center gap-1.5"><?= tier_star($r['tier'], 14) ?><?= e(ucfirst($r['tier'])) ?></p><p class="text-xs" style="color:var(--muted-foreground)"><?= money($r['price']) ?> · <?= $isLive ? 'Active' : ($r['status'] === 'cancelled' ? 'Cancelled' : 'Ended') ?></p>
            <?php if ($isLive): ?>
              <form method="post" action="<?= url('actions/admin_membership_action.php') ?>" class="mt-1" data-confirm="Cancel <?= e($r['customer_name']) ?>'s <?= e(ucfirst($r['tier'])) ?> membership now? Their benefits stop immediately." data-confirm-yes="Cancel membership">
                <input type="hidden" name="act" value="cancel"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="text-xs underline" style="color:#b3261e">Cancel</button>
              </form>
            <?php endif; ?></td>
          <td class="p-3 text-xs"><?= $r['starts_at'] ? e(date('j M Y', strtotime($r['starts_at']))) : '–' ?><br>to <?= $r['expires_at'] ? e(date('j M Y', strtotime($r['expires_at']))) : '–' ?></td>
          <td class="p-3 font-mono text-xs"><?= $v ? e($v['code']) : '–' ?></td>
          <td class="p-3"><?php if ($v): ?><?= money($v['balance']) ?> <span class="text-xs" style="color:var(--muted-foreground)">of <?= money($v['amount']) ?></span><?php else: ?>–<?php endif; ?></td>
          <td class="p-3"><?php if ($isLive && $t): $used = $mc->deliveryCreditUsed($r['user_id']); ?><?= money($used) ?> <span class="text-xs" style="color:var(--muted-foreground)">of <?= money($t['delivery']) ?></span><?php else: ?>–<?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="p-6 text-sm" style="color:var(--muted-foreground)"><?= $q !== '' ? 'No member matches "' . e($q) . '".' : 'No paid memberships yet.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
