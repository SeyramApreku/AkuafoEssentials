<?php
// Admin: apply database updates (db/*.sql) with one click.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/setup_class.php';
require_admin();   // on POST this also checks the CSRF token

$setup   = new SetupClass();
$steps   = $setup->steps();

// The button posts back to this same page (one file to upload), runs every update file in order,
// stops at the first that fails, then shows the result.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    @set_time_limit(120);
    $results = [];
    foreach ($steps as $s) {
        [$ok, $n, $err] = $setup->runFile($s['file']);
        if (!$ok) error_log('Database update failed in ' . $s['file'] . ': ' . $err);
        $results[] = [$s['file'], $ok, $n, $err];
        if (!$ok) break;   // later files depend on earlier ones
    }
    $_SESSION['setup_results'] = $results;
    redirect(url('views/admin/admin_setup.php'));
}
$results = $_SESSION['setup_results'] ?? null; unset($_SESSION['setup_results']);
$pending = count(array_filter($steps, fn($s) => !$setup->isApplied($s['check'])));
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Database update | Admin';
include __DIR__ . '/../layout/admin_header.php';
?>
<div class="max-w-3xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin</p>
  <h1 class="font-display text-4xl font-light mb-2">Database update</h1>
  <p class="text-sm mb-8" style="color:var(--muted-foreground)">New features need new tables and columns. Click the button to add whatever is missing. It never deletes anything and is safe to click more than once.</p>

  <div class="border mb-8" style="border-color:var(--border)">
    <?php foreach ($steps as $s): $done = $setup->isApplied($s['check']); ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border)">
        <span class="w-6 h-6 shrink-0 rounded-full flex items-center justify-center text-xs" style="<?= $done ? 'background:var(--primary);color:var(--primary-foreground)' : 'border:1px solid var(--accent);color:var(--accent)' ?>"><?= $done ? '✓' : '!' ?></span>
        <div class="flex-1">
          <p class="text-sm font-medium"><?= e($s['label']) ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)">db/<?= e($s['file']) ?> · <?= $done ? 'Up to date' : 'Needs updating' ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="post" action="<?= url('views/admin/admin_setup.php') ?>">
    <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $pending ? 'Update database (' . $pending . ' to apply)' : 'Run again (everything is up to date)' ?></button>
  </form>

  <?php if ($results): ?>
    <h2 class="font-display text-xl mt-10 mb-3">Result</h2>
    <div class="border text-sm" style="border-color:var(--border)">
      <?php foreach ($results as [$file, $ok, $n, $err]): ?>
        <div class="p-4 border-b last:border-b-0" style="border-color:var(--border)">
          <p class="font-medium" style="color:<?= $ok ? 'var(--primary)' : '#b3261e' ?>"><?= $ok ? '✓' : '✗' ?> db/<?= e($file) ?> — <?= (int)$n ?> statement<?= $n === 1 ? '' : 's' ?> run</p>
          <?php if (!$ok): ?><p class="text-xs mt-1 break-words" style="color:#b3261e"><?= e($err) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="text-xs mt-2" style="color:var(--muted-foreground)">Error details are shown to admins only.</p>
  <?php endif; ?>
</div>
</body>
</html>
