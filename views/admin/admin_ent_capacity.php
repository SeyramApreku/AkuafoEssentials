<?php
// Admin: how many entertaining orders we take per slot, blocked-out dates, and the change cutoff.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/entertaining_controller.php';
require_admin();

$ent      = entertaining();
$capacity = ent_slot_capacity();
$cutoff   = ent_cutoff_hours();
$today    = date('Y-m-d');
$rules    = $ent->rulesBetween($today, date('Y-m-d', strtotime('+1 year')));
$windows  = time_windows();
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Event capacity | Admin';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
$act = url('actions/admin_ent_capacity_action.php');
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin · Entertaining</p>
  <h1 class="font-display text-4xl font-light mb-8">Capacity &amp; blocked dates</h1>

  <div class="grid md:grid-cols-2 gap-6 mb-12">
    <form method="post" action="<?= $act ?>" class="p-6 border" style="border-color:var(--border)">
      <input type="hidden" name="act" value="settings">
      <h2 class="font-display text-xl mb-4">Default settings</h2>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Max event orders per time slot</label>
      <input type="number" name="capacity" min="1" max="500" value="<?= $capacity ?>" required class="<?= $box ?> mb-4" style="border-color:var(--border)">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Customers can change or cancel until (hours before the event)</label>
      <input type="number" name="cutoff" min="0" max="720" value="<?= $cutoff ?>" required class="<?= $box ?> mb-4" style="border-color:var(--border)">
      <button class="px-6 py-3 text-sm font-medium btn-primary">Save settings</button>
    </form>

    <form method="post" action="<?= $act ?>" class="p-6 border" style="border-color:var(--border)">
      <input type="hidden" name="act" value="add_rule">
      <h2 class="font-display text-xl mb-4">Block a date or set its limit</h2>
      <div class="grid grid-cols-2 gap-3 mb-3">
        <label><span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Date</span><input type="date" name="date" min="<?= $today ?>" required class="<?= $box ?>" style="border-color:var(--border)"></label>
        <label><span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Time slot</span>
          <select name="window" class="<?= $box ?>" style="border-color:var(--border)"><option value="">Whole day</option><?php foreach ($windows as $w): ?><option value="<?= e($w) ?>"><?= e($w) ?></option><?php endforeach; ?></select>
        </label>
      </div>
      <div class="flex flex-wrap gap-4 mb-3 text-sm">
        <label class="flex items-center gap-2"><input type="radio" name="kind" value="block" checked> Block it (no orders)</label>
        <label class="flex items-center gap-2"><input type="radio" name="kind" value="limit"> Limit to <input type="number" name="max" min="0" max="500" class="w-20 px-2 py-1 border bg-transparent" style="border-color:var(--border)"> orders</label>
      </div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Note (optional, admin only)</label>
      <input type="text" name="note" maxlength="120" placeholder="e.g. Public holiday" class="<?= $box ?> mb-4" style="border-color:var(--border)">
      <button class="px-6 py-3 text-sm font-medium btn-primary">Save rule</button>
    </form>
  </div>

  <h2 class="font-display text-2xl mb-4">Upcoming rules</h2>
  <div class="border mb-12" style="border-color:var(--border)">
    <?php foreach ($rules as $r): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium"><?= e(date('D j M Y', strtotime($r['rule_date']))) ?> · <?= $r['time_window'] === '' ? 'Whole day' : e($r['time_window']) ?></p>
          <p class="text-xs" style="color:<?= $r['is_blocked'] ? 'var(--accent)' : 'var(--muted-foreground)' ?>"><?= $r['is_blocked'] ? 'Blocked' : 'Limited to ' . (int)$r['max_orders'] . ' orders' ?><?= $r['note'] ? ' · ' . e($r['note']) : '' ?></p>
        </div>
        <form method="post" action="<?= $act ?>" data-confirm="Remove this rule?">
          <input type="hidden" name="act" value="delete_rule"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Remove</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$rules): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No rules. Every slot takes up to <?= $capacity ?> orders.</p><?php endif; ?>
  </div>

  <h2 class="font-display text-2xl mb-4">Next 14 days</h2>
  <div class="overflow-x-auto border" style="border-color:var(--border)">
    <table class="w-full text-sm">
      <thead><tr class="text-xs uppercase tracking-widest text-left" style="color:var(--muted-foreground);background:var(--muted)">
        <th class="p-3 font-normal">Date</th><?php foreach ($windows as $w): ?><th class="p-3 font-normal"><?= e(preg_replace('/\s*\(.*$/', '', $w)) ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php for ($d = 0; $d < 14; $d++): $date = date('Y-m-d', strtotime("+$d day")); $dayRules = $ent->rulesFor($date); ?>
        <tr class="border-t" style="border-color:var(--border)">
          <td class="p-3 whitespace-nowrap"><?= e(date('D j M', strtotime($date))) ?></td>
          <?php foreach ($windows as $w):
            $blocked = !empty($dayRules['']['is_blocked']) || !empty($dayRules[$w]['is_blocked']);
            $max = isset($dayRules[$w]) && $dayRules[$w]['max_orders'] !== null ? (int)$dayRules[$w]['max_orders'] : $capacity;
            $n = $ent->bookedCount($date, $w); ?>
            <td class="p-3" style="<?= $blocked ? 'color:var(--accent)' : ($n >= $max ? 'color:#b3261e;font-weight:600' : '') ?>"><?= $blocked ? 'Blocked' : $n . ' / ' . $max ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>
  </div>
  <p class="text-xs mt-2" style="color:var(--muted-foreground)">Counts paid event orders plus anyone paying right now (held for 30 minutes).</p>
</div>
</body>
</html>
