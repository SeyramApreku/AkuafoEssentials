<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/invoice_controller.php';
require_once __DIR__ . '/../classes/rating_class.php';

$ref = $_GET['ref'] ?? '';
$t   = $_GET['t'] ?? '';
if (!rating_token_ok($ref, $t)) { http_response_code(403); exit('This rating link is not valid.'); }

$orders = new OrderClass();
$order  = $orders->byRef($ref);
if (!$order || $order['payment_status'] !== 'paid') { http_response_code(404); exit('Order not found.'); }

$existing = (new RatingClass())->find((int)$order['id']) ?: null;

$done    = isset($_GET['done']);
$current = $existing ? (int)$existing['rating'] : (int)($_GET['r'] ?? 0);
if ($current < 1 || $current > 5) $current = 0;

$pageTitle = 'Rate your experience | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
?>
<style>
  .stars { display:flex; flex-direction:row-reverse; justify-content:center; gap:6px; }
  .stars input { position:absolute; opacity:0; pointer-events:none; }
  .stars label { font-size:44px; line-height:1; cursor:pointer; color:#cfcabc; }
  .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color:#d9a21b; }
</style>
<div class="max-w-md mx-auto px-6 py-16 text-center">
<?php if ($done): ?>
  <h1 class="font-display text-3xl font-light mb-3">Thank you!</h1>
  <p class="text-sm mb-8" style="color:var(--muted-foreground)">Your feedback has been shared with our team.</p>
  <a href="<?= url('index.php') ?>" class="text-sm" style="color:var(--primary)">← Back to the shop</a>
<?php else: ?>
  <h1 class="font-display text-3xl font-light mb-2">How was your experience?</h1>
  <p class="text-xs mb-8" style="color:var(--muted-foreground)">Order <?= e($order['ref']) ?></p>

  <form method="post" action="<?= url('actions/rate_action.php') ?>">
    <input type="hidden" name="ref" value="<?= e($ref) ?>">
    <input type="hidden" name="t" value="<?= e($t) ?>">

    <div class="stars mb-6">
      <?php for ($n = 5; $n >= 1; $n--): ?>
        <input type="radio" id="s<?= $n ?>" name="rating" value="<?= $n ?>" <?= $current === $n ? 'checked' : '' ?> required>
        <label for="s<?= $n ?>" title="<?= $n ?> star<?= $n > 1 ? 's' : '' ?>">★</label>
      <?php endfor; ?>
    </div>

    <textarea name="comment" rows="4" maxlength="1000" placeholder="Anything you'd like to tell us? (optional)"
      class="w-full px-4 py-3 text-sm border outline-none bg-transparent mb-6" style="border-color:var(--border)"><?= e($existing['comment'] ?? '') ?></textarea>

    <button class="w-full py-3 text-sm font-medium btn-primary"><?= $existing ? 'Update my rating' : 'Submit rating' ?></button>
  </form>
<?php endif; ?>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>