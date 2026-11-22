<?php
$pageTitle = 'Payment not completed | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$ref = $_GET['ref'] ?? '';
?>
<div class="max-w-md mx-auto px-6 py-20 text-center">
  <h1 class="font-display text-3xl font-light mb-3">Payment not completed</h1>
  <p class="text-sm mb-8 leading-relaxed" style="color:var(--muted-foreground)">Your payment did not go through and you have not been charged. Your items are still in your cart.</p>
  <?php if ($ref): ?><a href="<?= url('actions/process_payment_action.php?ref=' . urlencode($ref)) ?>" class="px-8 py-3 text-sm font-medium btn-primary inline-block mb-3">Try payment again</a><br><?php endif; ?>
  <a href="<?= url('views/checkout.php') ?>" class="text-sm" style="color:var(--primary)">Back to checkout</a>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>