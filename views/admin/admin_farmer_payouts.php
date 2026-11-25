<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/farmer_payout_class.php';
require_admin();

// Farmer Payouts: fully automatic. Every paid Farm Shop item is split between the farmer and Akuafo using the
// farmer's plan commission; finished weeks (Saturday to Friday) are worked out by themselves.
// The admin only sends the MoMo and clicks "Mark as paid".
$fp = new FarmerPayoutClass();
$week = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['week'] ?? '') ? $_GET['week'] : date('Y-m-d');
[$start, $end] = payout_week($week);
[$thisStart] = payout_week();
$prevWeek = date('Y-m-d', strtotime($start . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($start . ' +7 days'));
$isFinished = $end < date('Y-m-d') || ($end === date('Y-m-d') && $start !== $thisStart);
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);

$ready = true;
try {
    $fp->catchUp();                          // tag any paid orders from before this existed
    $fp->autoGenerate(4);                    // last 4 finished weeks, by themselves
    if ($start < $thisStart) $fp->generate($start, $end);   // an older finished week the admin is looking at
    $saved = [];
    foreach ($fp->forWeek($start) as $p) $saved[(int)$p['farmer_id']] = $p;
    $rows = [];
    foreach ($fp->farmers() as $f) {
        $w = $fp->preview($f, $start, $end, $saved[(int)$f['id']]['id'] ?? null);
        $p = $saved[(int)$f['id']] ?? null;
        if ($p && $p['status'] === 'paid') $w['calc'] = array_map('floatval', array_intersect_key($p, $w['calc'])) + $w['calc'];   // paid: exactly what was paid
        if ($p || $w['sales'] || $w['calc']['carried_in'] > 0) $rows[] = $w + ['payout' => $p];
    }
    $totals = $fp->monthTotals(date('Y-m'));
} catch (PDOException $e) {
    error_log('Farmer payouts page: ' . $e->getMessage());
    $ready = false;
}

// Download this week's payouts as a spreadsheet (CSV)
if ($ready && isset($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="farmer-payouts-' . $start . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // so Excel shows GH₵ properly
    fputcsv($out, ['Farmer', 'MoMo number', 'Plan', 'Week', 'Customers paid', 'Akuafo keeps', 'Farmer share', 'Plan fee', 'Owed from last week', 'To pay', 'Carried to next week', 'Status', 'Paid on', 'MoMo reference']);
    foreach ($rows as $r) {
        $c = $r['calc']; $p = $r['payout'];
        fputcsv($out, [$r['farmer']['name'], $r['farmer']['payout_momo_number'], $r['farmer']['plan'] ?? 'basic', "$start to $end", $c['gross_sales'], $c['commission'], $c['shop_earnings'],
                       $c['plan_fee'], $c['carried_in'], $c['net_payout'], $c['carried_out'],
                       $p ? $p['status'] : 'week still running', $p && $p['paid_at'] ? date('Y-m-d', strtotime($p['paid_at'])) : '', $p['momo_reference'] ?? '']);
    }
    exit;
}

$pageTitle = 'Farmer Payouts | Akuafo & Essentials';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-3 py-2 text-sm border outline-none bg-transparent';
$muted = 'color:var(--muted-foreground)';
$weekPay = array_sum(array_map(fn($r) => $r['calc']['net_payout'], $rows ?? []));
$weekKeep = array_sum(array_map(fn($r) => $r['calc']['commission'], $rows ?? []));
$weekGross = array_sum(array_map(fn($r) => $r['calc']['gross_sales'], $rows ?? []));
$tile = function ($label, $value, $sub, $strong = false) use ($muted) {
    return '<div class="p-5 border" style="border-color:' . ($strong ? 'var(--primary)' : 'var(--border)') . ';background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="' . ($strong ? 'color:var(--primary)' : $muted) . '">' . $label . '</p>'
         . '<p class="font-display text-3xl">' . $value . '</p><p class="text-xs mt-1" style="' . $muted . '">' . $sub . '</p></div>';
};
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-8">Farmer Payouts</h1>
  <?php if ($msg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted)"><?= e(is_array($msg) ? implode(' ', $msg) : $msg) ?></p><?php endif; ?>

  <?php if (!$ready): ?>
    <p class="text-sm p-6 border" style="border-color:var(--border)">Farmer payouts need the database update first. Go to <a class="underline" href="<?= url('views/admin/admin_setup.php') ?>">Menu → Database Update</a> and run both <strong>Farmer revenue</strong> rows.</p>
  <?php else: ?>

  <p class="text-xs uppercase tracking-widest mb-3" style="<?= $muted ?>"><?= e(date('F Y')) ?> so far</p>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    <?= $tile('Customers paid', money($totals['gross']), "for farmers' produce") ?>
    <?= $tile('Farmers\' share', money($totals['to_farmers']), 'after commission' . ($totals['plan_fees'] > 0 ? ' and ' . money($totals['plan_fees']) . ' plan fees' : '')) ?>
    <?= $tile('Akuafo keeps', money($totals['commission'] + $totals['plan_fees']), 'commission' . ($totals['plan_fees'] > 0 ? ' + plan fees' : ''), true) ?>
    <?= $tile('Waiting to be paid', money($totals['due']), $totals['due_n'] . ' payout' . ($totals['due_n'] === 1 ? '' : 's') . ' · ' . money($totals['paid']) . ' paid this month') ?>
  </div>

  <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div class="flex items-center gap-3">
      <a href="?week=<?= $prevWeek ?>" class="px-3 py-2 border text-sm" style="border-color:var(--border)" aria-label="Previous week">←</a>
      <div><p class="font-display text-2xl"><?= e(date('D j M', strtotime($start))) ?> – <?= e(date('D j M Y', strtotime($end))) ?></p>
        <p class="text-xs" style="<?= $muted ?>"><?= $start === $thisStart ? 'This week (still running) · payday Friday ' . e(date('j M', strtotime($end))) : 'Payday was Friday ' . e(date('j M', strtotime($end))) ?>
          · customers paid <?= money($weekGross) ?> · farmers get <?= money($weekPay) ?> · Akuafo keeps <?= money($weekKeep) ?></p></div>
      <?php if ($start < $thisStart): ?><a href="?week=<?= $nextWeek ?>" class="px-3 py-2 border text-sm" style="border-color:var(--border)" aria-label="Next week">→</a><?php endif; ?>
    </div>
    <a href="?week=<?= $start ?>&amp;csv=1" class="px-5 py-2.5 text-sm font-medium border" style="border-color:var(--border)">Download spreadsheet</a>
  </div>

  <div class="border overflow-x-auto mb-12" style="border-color:var(--border)">
    <table class="w-full text-sm">
      <thead><tr class="text-left text-xs uppercase tracking-widest" style="<?= $muted ?>">
        <th class="p-3">Farmer</th><th class="p-3 text-right">Customers paid</th><th class="p-3 text-right">Akuafo keeps</th><th class="p-3 text-right">Farmer share</th><th class="p-3 text-right">To pay</th><th class="p-3">Status</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $f = $r['farmer']; $c = $r['calc']; $p = $r['payout'];
        $plan = farmer_plans()[$f['plan'] ?? 'basic'] ?? null;
        $tel = preg_replace('/\D/', '', (string)(($f['phone'] ?? '') ?: ($f['payout_momo_number'] ?? ''))); $wa = $tel ? '233' . substr($tel, 1) : ''; ?>
        <tr class="border-t align-top" style="border-color:var(--border)">
          <td class="p-3">
            <p class="font-medium"><?= e($f['name']) ?></p>
            <p class="text-xs" style="<?= $muted ?>"><?= e($plan['name'] ?? ucfirst($f['plan'] ?? 'basic')) ?> plan · <?= (float)$f['commission_rate'] ?>% commission · MoMo <?= !empty($f['payout_momo_number']) ? e($f['payout_momo_number']) : '<span style="color:#b3261e">not set (Farmers → Edit)</span>' ?></p>
            <p class="text-xs mt-1 flex flex-wrap gap-3">
              <?php if ($tel): ?><a class="underline" href="tel:<?= e($tel) ?>">Call</a><a class="underline" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>">WhatsApp</a><?php endif; ?>
              <?php if (!empty($f['email'])): ?><a class="underline" href="mailto:<?= e($f['email']) ?>">Email</a><?php endif; ?>
              <button type="button" class="underline" onclick="var d=document.getElementById('why-<?= (int)$f['id'] ?>');d.hidden=!d.hidden">See the sales</button>
            </p>
          </td>
          <td class="p-3 text-right"><?= money($c['gross_sales']) ?></td>
          <td class="p-3 text-right" style="color:var(--primary)"><?= money($c['commission']) ?></td>
          <td class="p-3 text-right"><?= money($c['shop_earnings']) ?>
            <?php if ($c['plan_fee'] > 0): ?><p class="text-xs" style="<?= $muted ?>">− <?= money($c['plan_fee']) ?> plan fee</p><?php endif; ?>
            <?php if ($c['carried_in'] > 0): ?><p class="text-xs" style="<?= $muted ?>">− <?= money($c['carried_in']) ?> from last week</p><?php endif; ?></td>
          <td class="p-3 text-right font-medium"><?= money($c['net_payout']) ?><?php if ($c['carried_out'] > 0): ?><p class="text-xs font-normal" style="color:#b3261e"><?= money($c['carried_out']) ?> carried to next week</p><?php endif; ?></td>
          <td class="p-3">
            <?php if ($p && $p['status'] === 'paid'): ?>
              <p class="text-xs font-medium" style="color:var(--primary)">✓ Paid <?= e(date('j M', strtotime($p['paid_at']))) ?></p><p class="text-xs" style="<?= $muted ?>"><?= e($p['momo_reference'] ?? '') ?></p>
            <?php elseif ($p && (float)$p['net_payout'] > 0): ?>
              <form method="post" action="<?= url('actions/admin_farmer_payout_action.php') ?>" class="flex flex-col gap-1.5 min-w-[160px]"
                    data-confirm="Mark <?= e(money($p['net_payout'])) ?> to <?= e($f['name']) ?> as sent by MoMo?" data-confirm-yes="Mark as paid">
                <input type="hidden" name="act" value="paid"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="week" value="<?= $start ?>">
                <input name="momo_reference" maxlength="80" placeholder="MoMo ID (optional)" class="<?= $box ?>" style="border-color:var(--border)">
                <button class="px-3 py-2 text-xs font-medium btn-primary">Mark as paid</button>
              </form>
            <?php elseif ($p): ?><p class="text-xs" style="<?= $muted ?>">Nothing to pay</p>
            <?php else: ?><p class="text-xs" style="<?= $muted ?>">Week still running</p><?php endif; ?>
          </td>
        </tr>
        <tr id="why-<?= (int)$f['id'] ?>" hidden><td colspan="6" class="p-5 text-xs" style="background:var(--muted)">
          <?php foreach ($r['sales'] as $s): ?>
            <p class="flex justify-between gap-3 py-0.5"><span><?= e(date('D j M', strtotime($s['created_at']))) ?> · order <?= e($s['ref']) ?> · <?= (int)$s['qty'] ?> × <?= e($s['name']) ?> @ <?= money($s['price']) ?></span>
              <span><?= money($s['amount']) ?> → farmer <?= money(round($s['amount'] * (1 - $s['rate'] / 100), 2)) ?>, Akuafo <?= money(round($s['amount'] * $s['rate'] / 100, 2)) ?> (<?= (float)$s['rate'] ?>%)</span></p>
          <?php endforeach; ?>
          <?php if (!$r['sales']): ?><p style="<?= $muted ?>">No sales this week.</p><?php endif; ?>
        </td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="p-6 text-sm" style="<?= $muted ?>">No paid Farm Shop orders this week. Sales appear here the moment a customer pays.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <details class="p-6 border mb-16" style="border-color:var(--border);background:var(--card)">
    <summary class="font-display text-xl cursor-pointer">How the split works: an example</summary>
    <div class="grid md:grid-cols-2 gap-8 mt-5 text-sm">
      <div class="space-y-2" style="<?= $muted ?>">
        <p>A customer pays <strong>GH₵ 200</strong> for Kofi's yams. The money is split the moment they pay, using Kofi's plan:</p>
        <?php foreach (farmer_plans() as $code => $pl): ?>
          <p><strong><?= e($pl['name']) ?></strong> (<?= (float)$pl['monthly_fee'] > 0 ? money($pl['monthly_fee']) . '/month' : 'free' ?>, <?= (float)$pl['commission_rate'] ?>%): Kofi gets <?= money(200 * (1 - $pl['commission_rate'] / 100)) ?>, Akuafo keeps <?= money(200 * $pl['commission_rate'] / 100) ?>.</p>
        <?php endforeach; ?>
      </div>
      <div class="space-y-1.5">
        <p>On Friday Kofi's week is added up by itself. On a paid plan, the monthly fee comes off his first payout of the month (and counts as Akuafo's income).</p>
        <p style="<?= $muted ?>">If a customer's order is cancelled before you pay, it drops out of the payout automatically. If a week ends below zero (e.g. the plan fee is bigger than that week's sales), Kofi gets GH₵ 0 and the rest comes off next week.</p>
        <p style="<?= $muted ?>">Change a farmer's plan in <a class="underline" href="<?= url('views/admin/admin_manage.php?t=farmers') ?>">Farmers → Edit</a>. New sales use the new rate; past sales keep the rate they were sold at.</p>
      </div>
    </div>
  </details>
  <?php endif; ?>
</div>
</body>
</html>
