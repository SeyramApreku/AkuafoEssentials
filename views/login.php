<?php
require_once __DIR__ . '/../core/core.php';
$next = safe_next($_GET['next'] ?? '');

// Arrived from a link in an email: sign out the account on this browser (the cart is kept)
// and ask for the password, then continue to $next.
$fromEmail = !empty($_GET['force']);
if ($fromEmail && !empty($_SESSION['customer_id'])) {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email'],
          $_SESSION['user_role'], $_SESSION['csrf'], $_SESSION['cart_order_ref']);
    session_regenerate_id(true);
}
if (!empty($_SESSION['customer_id'])) redirect(url($next ?: 'index.php'));
$nextQs = $next ? '?next=' . urlencode($next) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in | Akuafo &amp; Essentials</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="min-h-screen flex items-center justify-center px-5">
  <div class="w-full max-w-sm py-12">
    <div class="flex items-center gap-2.5 mb-8">
      <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:var(--primary);color:var(--primary-foreground)">A</div>
      <span class="font-display text-base font-medium">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</span>
    </div>
    <h1 class="font-display text-3xl font-light mb-6">Welcome</h1>
    <?php if ($fromEmail): ?><p class="text-sm mb-4 p-3" style="background:var(--muted)">For your security, please log in to continue.</p><?php elseif ($next === 'views/checkout.php'): ?><p class="text-sm mb-4 p-3" style="background:var(--muted)">Log in or create an account to complete your order. Your cart is saved.</p><?php endif; ?>
    <form id="loginForm" onsubmit="event.preventDefault(); loginCustomer()" class="flex flex-col gap-3">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <input type="email" id="customer_email" name="customer_email" placeholder="Email" class="px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
      <input type="password" id="customer_pass" name="customer_pass" placeholder="Password" class="px-4 py-3 text-sm border outline-none bg-transparent" style="border-color:var(--border)">
      <label class="text-xs flex items-center gap-2 cursor-pointer" style="color:var(--muted-foreground)"><input type="checkbox" onclick="togglePassword()"> Show password</label>
      <button type="submit" class="px-5 py-3 text-sm font-medium btn-primary">Log in</button>
    </form>
    <p id="formMessage" class="text-sm mt-4"></p>
    <p class="text-sm mt-6" style="color:var(--muted-foreground)">New here? <a href="register.php<?= e($nextQs) ?>" class="font-medium" style="color:var(--primary)">Create an account</a></p>
  </div>
  <script src="<?= url('js/customer.js') ?>"></script>
</body>
</html>