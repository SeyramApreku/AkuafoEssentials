<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/invoice_controller.php';
require_once __DIR__ . '/../../classes/rating_class.php';

// !! Use YOUR existing admin guard here. This fails closed until you do.
if (function_exists('require_admin')) { require_admin(); }
else { http_response_code(403); exit('Add your admin check to views/admin/ratings.php'); }

$ratings = new RatingClass();
$rows  = $ratings->all();
$stats = $ratings->stats();

$pageTitle = 'Customer ratings | Admin';
include __DIR__ . '/../layout/header.php';
?>
<div class="max-w-4xl mx-auto px-6 py-12">
  <h1 class="font-display text-3xl font-light mb-2">Customer ratings</h1>
  <p class="text-sm mb-8" style="color:var(--muted-foreground)">
    <?= (int)$stats['c'] ?> rating<?= (int)$stats['c'] === 1 ? '' : 's' ?>
    <?php if ($stats['c']): ?> · average <b><?= number_format((float)$stats['a'], 1) ?></b> / 5<?php endif; ?>
  </p>

  <?php if (!$rows): ?>
    <p class="text-sm" style="color:var(--muted-foreground)">No ratings yet.</p>
  <?php else: ?>
  <table class="w-full text-sm">
    <thead>
      <tr class="text-xs uppercase tracking-widest text-left" style="color:var(--muted-foreground)">
        <th class="py-3 font-normal">Date</th><th class="py-3 font-normal">Order</th><th class="py-3 font-normal">Customer</th>
        <th class="py-3 font-normal">Rating</th><th class="py-3 font-normal">Comment</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="border-t align-top" style="border-color:var(--border)">
        <td class="py-3 whitespace-nowrap"><?= e(date('j M Y', strtotime($r['updated_at']))) ?></td>
        <td class="py-3"><?= e($r['ref']) ?><span class="block text-xs" style="color:var(--muted-foreground)"><?= money($r['total']) ?></span></td>
        <td class="py-3"><?= e($r['email']) ?></td>
        <td class="py-3 whitespace-nowrap" style="color:#d9a21b"><?= str_repeat('★', (int)$r['rating']) ?><span style="color:#cfcabc"><?= str_repeat('★', 5 - (int)$r['rating']) ?></span></td>
        <td class="py-3"><?= e($r['comment'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>