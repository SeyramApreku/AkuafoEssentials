<?php
// Admin: revenue for a date range, compared with the period before it.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/report_class.php';
require_admin();

// ---------- Date range ----------
$range = $_GET['range'] ?? '30';
$today = new DateTime('today');
$valid = fn($s) => ($d = DateTime::createFromFormat('!Y-m-d', (string)$s)) && $d->format('Y-m-d') === $s ? $d : null;
if ($range === 'custom' && ($f = $valid($_GET['from'] ?? '')) && ($t = $valid($_GET['to'] ?? '')) && $f <= $t) {
    $from = $f; $to = $t;
} elseif ($range === 'month') {
    $from = new DateTime('first day of this month'); $to = clone $today;
} else {
    $range = in_array($range, ['7', '30', '90'], true) ? $range : '30';
    $to = clone $today; $from = (clone $today)->modify('-' . ((int)$range - 1) . ' days');
}
$days = (int)$from->diff($to)->days + 1;
if ($days > 366) { $from = (clone $to)->modify('-365 days'); $days = 366; }   // keep the chart readable
$prevTo = (clone $from)->modify('-1 day'); $prevFrom = (clone $prevTo)->modify('-' . ($days - 1) . ' days');
$F = $from->format('Y-m-d'); $T = $to->format('Y-m-d');

$rep   = new ReportClass();
$s     = $rep->summary($F, $T);
$p     = $rep->summary($prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d'));
$daily = $rep->daily($F, $T);
$types = $rep->byItemType($F, $T);
$top   = $rep->topItems($F, $T);
// Farmers' produce: split automatically by each farmer's plan when the customer paid (see Farmer Payouts)
$fs  = farmer_split_totals($rep, $F, $T);
$fsP = farmer_split_totals($rep, $prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d'));
$income  = $s['revenue'] + $s['membership_amount'];
$incomeP = $p['revenue'] + $p['membership_amount'];
$typeNames = ['produce' => 'Produce', 'box' => 'Weekly boxes', 'preorder' => 'Hampers & bulk (old)', 'entertaining' => 'Entertaining', 'membership' => 'Memberships (in cart)'];

// "+12% vs previous 30 days" (null when there's nothing to compare with)
$change = function ($now, $before) { return $before > 0 ? round(($now - $before) / $before * 100) : null; };

$pageTitle = 'Revenue | Admin';
include __DIR__ . '/../layout/admin_header.php';
$tile = function ($label, $value, $sub = '', $delta = null) {
    $d = $delta === null ? '' : '<span style="color:' . ($delta >= 0 ? 'var(--primary)' : '#b3261e') . '">' . ($delta >= 0 ? '▲ ' : '▼ ') . abs($delta) . '%</span> vs previous period';
    return '<div class="p-5 border" style="border-color:var(--border);background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--muted-foreground)">' . $label . '</p>'
         . '<p class="font-display text-3xl">' . $value . '</p><p class="text-xs mt-1" style="color:var(--muted-foreground)">' . $d . ($sub && $d ? ' · ' : '') . $sub . '</p></div>';
};

// ---------- Chart geometry (server-rendered SVG, one series) ----------
$W = 900; $H = 260; $padL = 56; $padB = 28; $padT = 12; $plotW = $W - $padL - 8; $plotH = $H - $padB - $padT;
$max = max(1, max(array_column($daily, 'revenue')));
$step = pow(10, floor(log10($max))); foreach ([1, 2, 2.5, 5, 10] as $m) if ($max / ($step * $m) <= 4) { $step *= $m; break; }
$top_ = ceil($max / $step) * $step;
$bw = $plotW / count($daily); $gap = $bw > 6 ? 2 : 0;
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-6">Revenue</h1>

  <!-- Filters: one row above everything -->
  <form method="get" class="flex flex-wrap items-end gap-2 mb-8 text-xs">
    <?php foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'month' => 'This month'] as $k => $label): ?>
      <a href="?range=<?= $k ?>" class="px-3 py-2 border uppercase tracking-widest <?= $range === (string)$k ? 'pill-on' : '' ?>" style="border-color:var(--border)"><?= $label ?></a>
    <?php endforeach; ?>
    <span class="mx-1"></span>
    <input type="hidden" name="range" value="custom">
    <label class="flex flex-col gap-1" style="color:var(--muted-foreground)">From<input type="date" name="from" value="<?= $F ?>" max="<?= $today->format('Y-m-d') ?>" class="px-2 py-1.5 border bg-transparent" style="border-color:var(--border)"></label>
    <label class="flex flex-col gap-1" style="color:var(--muted-foreground)">To<input type="date" name="to" value="<?= $T ?>" max="<?= $today->format('Y-m-d') ?>" class="px-2 py-1.5 border bg-transparent" style="border-color:var(--border)"></label>
    <button class="px-4 py-2 border uppercase tracking-widest <?= $range === 'custom' ? 'pill-on' : '' ?>" style="border-color:var(--border)">Apply</button>
  </form>
  <p class="text-sm mb-6" style="color:var(--muted-foreground)"><?= e($from->format('j M Y')) ?> to <?= e($to->format('j M Y')) ?> (<?= $days ?> day<?= $days === 1 ? '' : 's' ?>), compared with <?= e($prevFrom->format('j M')) ?> to <?= e($prevTo->format('j M Y')) ?>.</p>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
    <?= $tile('Order revenue', money($s['revenue']), (int)$s['orders'] . ' paid orders', $change($s['revenue'], $p['revenue'])) ?>
    <?= $tile('Average order', money(round($s['avg_order'], 2)), '', $change($s['avg_order'], $p['avg_order'])) ?>
    <?= $tile('Delivery fees collected', money($s['delivery']), 'included in order revenue', $change($s['delivery'], $p['delivery'])) ?>
    <?= $tile('Membership sales', money($s['membership_amount']), (int)$s['memberships'] . ' memberships started', $change($s['membership_amount'], $p['membership_amount'])) ?>
    <?= $tile('Refunded / cancelled', money($s['refund_amount']), (int)$s['refunds'] . ' paid orders cancelled') ?>
    <?= $tile('Total income', money($income), 'everything customers paid: orders + memberships', $change($income, $incomeP)) ?>
  </div>

  <!-- Farmers' share vs what Akuafo keeps -->
  <h2 class="font-display text-2xl mb-1">After paying farmers</h2>
  <p class="text-sm mb-4" style="color:var(--muted-foreground)">Farm Shop produce belongs to the farmer: the moment a customer pays, it's split by the farmer's plan. <a class="underline" href="<?= url('views/admin/admin_farmer_payouts.php') ?>">Farmer Payouts →</a></p>
  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
    <?= $tile("Farmers' share", money($fs['to_farmers']), 'of ' . money($fs['gross']) . " farmers' produce sold" . ($fs['plan_fees'] > 0 ? ', after ' . money($fs['plan_fees']) . ' plan fees' : ''), $change($fs['to_farmers'], $fsP['to_farmers'])) ?>
    <?= $tile('Akuafo keeps from farmers', money($fs['commission'] + $fs['plan_fees']), 'commission' . ($fs['plan_fees'] > 0 ? ' + plan fees' : ''), $change($fs['commission'] + $fs['plan_fees'], $fsP['commission'] + $fsP['plan_fees'])) ?>
    <div class="p-5 border" style="border-color:var(--primary);background:var(--card)"><p class="text-xs uppercase tracking-widest mb-1" style="color:var(--primary)">Our revenue</p>
      <p class="font-display text-3xl"><?= money(round($income - $fs['to_farmers'], 2)) ?></p>
      <p class="text-xs mt-1" style="color:var(--muted-foreground)"><?= money($income) ?> total income − <?= money($fs['to_farmers']) ?> to farmers<?php $d = $change($income - $fs['to_farmers'], $incomeP - $fsP['to_farmers']); if ($d !== null): ?> · <span style="color:<?= $d >= 0 ? 'var(--primary)' : '#b3261e' ?>"><?= $d >= 0 ? '▲' : '▼' ?> <?= abs($d) ?>%</span><?php endif; ?></p></div>
  </div>

  <!-- Daily revenue: single series, so no legend; the title names it -->
  <div class="p-5 border mb-10" style="border-color:var(--border);background:var(--card)">
    <div class="flex items-baseline justify-between mb-3">
      <h2 class="font-display text-xl">Order revenue per day</h2>
      <span class="text-xs" style="color:var(--muted-foreground)">Hover a bar for the day's figures</span>
    </div>
    <div class="relative">
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto" role="img" aria-label="Order revenue per day, <?= e($from->format('j M')) ?> to <?= e($to->format('j M')) ?>. Highest day <?= e(money($max)) ?>.">
        <?php for ($v = 0; $v <= $top_; $v += $step): $y = $padT + $plotH - $v / $top_ * $plotH; ?>
          <line x1="<?= $padL ?>" x2="<?= $W - 8 ?>" y1="<?= round($y, 1) ?>" y2="<?= round($y, 1) ?>" stroke="#D5CBBA" stroke-width="1" <?= $v ? 'stroke-dasharray="2 4"' : '' ?>/>
          <text x="<?= $padL - 8 ?>" y="<?= round($y + 4, 1) ?>" text-anchor="end" font-size="11" fill="#6B6050">GH₵<?= number_format($v) ?></text>
        <?php endfor; ?>
        <?php $labelEvery = max(1, (int)ceil(count($daily) / 10));
        foreach ($daily as $k => $d):
          $x = $padL + $k * $bw; $h = $d['revenue'] / $top_ * $plotH; $y = $padT + $plotH - $h;
          $tip = date('D j M', strtotime($d['d'])) . ': ' . money($d['revenue']) . ' from ' . $d['orders'] . ' order' . ($d['orders'] === 1 ? '' : 's'); ?>
          <g class="bar" data-tip="<?= e($tip) ?>">
            <rect x="<?= round($x, 1) ?>" y="<?= $padT ?>" width="<?= round($bw, 1) ?>" height="<?= $plotH ?>" fill="transparent"/>
            <?php if ($h > 0): $w = max(1, $bw - $gap); $r = min(4, $w / 2, $h); ?>
              <path d="M<?= round($x + $gap / 2, 1) ?>,<?= round($padT + $plotH, 1) ?> v<?= -round($h - $r, 1) ?> q0,-<?= round($r, 1) ?> <?= round($r, 1) ?>,-<?= round($r, 1) ?> h<?= round($w - 2 * $r, 1) ?> q<?= round($r, 1) ?>,0 <?= round($r, 1) ?>,<?= round($r, 1) ?> v<?= round($h - $r, 1) ?> z" fill="#2B4A12"/>
            <?php endif; ?>
            <?php if ($k % $labelEvery === 0): ?><text x="<?= round($x + $bw / 2, 1) ?>" y="<?= $H - 8 ?>" text-anchor="middle" font-size="11" fill="#6B6050"><?= e(date('j M', strtotime($d['d']))) ?></text><?php endif; ?>
          </g>
        <?php endforeach; ?>
      </svg>
      <div id="tip" class="absolute pointer-events-none text-xs px-2.5 py-1.5 border shadow" style="display:none;background:var(--background);border-color:var(--border)"></div>
    </div>
    <details class="mt-3 text-sm"><summary class="cursor-pointer text-xs" style="color:var(--muted-foreground)">Show as a table</summary>
      <table class="w-full mt-2"><thead><tr class="text-xs text-left" style="color:var(--muted-foreground)"><th class="py-1 font-normal">Day</th><th class="py-1 font-normal text-right">Orders</th><th class="py-1 font-normal text-right">Revenue</th></tr></thead>
        <tbody><?php foreach (array_reverse($daily) as $d): if (!$d['orders']) continue; ?><tr class="border-t" style="border-color:var(--border)"><td class="py-1"><?= e(date('D j M Y', strtotime($d['d']))) ?></td><td class="py-1 text-right"><?= $d['orders'] ?></td><td class="py-1 text-right"><?= money($d['revenue']) ?></td></tr><?php endforeach; ?></tbody></table>
    </details>
  </div>

  <div class="grid md:grid-cols-2 gap-6">
    <div class="p-5 border" style="border-color:var(--border)">
      <h2 class="font-display text-xl mb-3">By what was sold</h2>
      <table class="w-full text-sm"><thead><tr class="text-xs text-left" style="color:var(--muted-foreground)"><th class="py-1 font-normal">Type</th><th class="py-1 font-normal text-right">Units</th><th class="py-1 font-normal text-right">Revenue</th></tr></thead>
        <tbody><?php foreach ($types as $t): ?><tr class="border-t" style="border-color:var(--border)"><td class="py-2"><?= e($typeNames[$t['type']] ?? ucfirst($t['type'])) ?></td><td class="py-2 text-right"><?= (int)$t['units'] ?></td><td class="py-2 text-right"><?= money($t['revenue']) ?></td></tr><?php endforeach; ?>
        <?php if (!$types): ?><tr><td colspan="3" class="py-3" style="color:var(--muted-foreground)">No sales in this period.</td></tr><?php endif; ?></tbody></table>
      <p class="text-xs mt-2" style="color:var(--muted-foreground)">Item totals, before delivery fees and membership credit.</p>
    </div>
    <div class="p-5 border" style="border-color:var(--border)">
      <h2 class="font-display text-xl mb-3">Best sellers</h2>
      <table class="w-full text-sm"><thead><tr class="text-xs text-left" style="color:var(--muted-foreground)"><th class="py-1 font-normal">Item</th><th class="py-1 font-normal text-right">Units</th><th class="py-1 font-normal text-right">Revenue</th></tr></thead>
        <tbody><?php foreach ($top as $t): ?><tr class="border-t" style="border-color:var(--border)"><td class="py-2"><?= e($t['name']) ?></td><td class="py-2 text-right"><?= (int)$t['units'] ?></td><td class="py-2 text-right"><?= money($t['revenue']) ?></td></tr><?php endforeach; ?>
        <?php if (!$top): ?><tr><td colspan="3" class="py-3" style="color:var(--muted-foreground)">No sales in this period.</td></tr><?php endif; ?></tbody></table>
    </div>
  </div>
</div>
<script>
(function () {
  var tip = document.getElementById('tip'), box = tip.parentNode;
  document.querySelectorAll('.bar').forEach(function (g) {
    g.addEventListener('mousemove', function (e) {
      var r = box.getBoundingClientRect();
      tip.textContent = g.dataset.tip; tip.style.display = 'block';
      var x = e.clientX - r.left + 12; if (x + tip.offsetWidth > r.width) x = e.clientX - r.left - tip.offsetWidth - 12;
      tip.style.left = x + 'px'; tip.style.top = (e.clientY - r.top - 36) + 'px';
    });
    g.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
  });
})();
</script>
</body>
</html>
