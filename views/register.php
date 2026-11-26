<?php
require_once __DIR__ . '/../core/core.php';
$next = safe_next($_GET['next'] ?? '');
if (!empty($_SESSION['customer_id'])) redirect(url($next ?: 'index.php'));
$nextQs = $next ? '?next=' . urlencode($next) : '';
$inputStyle = 'px-4 py-3 text-sm border outline-none bg-transparent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create account | Akuafo &amp; Essentials</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="min-h-screen flex items-center justify-center px-5">
  <div class="w-full max-w-sm py-12">
    <div class="flex items-center gap-2.5 mb-8">
      <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:var(--primary);color:var(--primary-foreground)">A</div>
      <span class="font-display text-base font-medium">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</span>
    </div>
    <h1 class="font-display text-3xl font-light mb-6">Create your account</h1>
    <form id="registerForm" onsubmit="event.preventDefault(); registerCustomer()" class="flex flex-col gap-3">
      <?= csrf_field() ?>
      <input type="text" id="customer_name" name="customer_name" placeholder="Full name" autocomplete="name" minlength="2" maxlength="100" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <input type="email" id="customer_email" name="customer_email" placeholder="Email" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <input type="password" id="customer_pass" name="customer_pass" placeholder="Password" autocomplete="new-password" maxlength="72" aria-describedby="pass-hint" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <p id="pass-hint" class="text-xs -mt-1" style="color:var(--muted-foreground)">At least 8 characters, with an uppercase letter, a lowercase letter, a number and any symbol (for example - _ ! @ #).</p>
      <input type="password" id="customer_pass_confirm" name="customer_pass_confirm" placeholder="Confirm password" autocomplete="new-password" maxlength="72" aria-describedby="pass-match" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <p id="pass-match" class="text-xs -mt-1" aria-live="polite"></p>
      <label class="text-xs flex items-center gap-2 cursor-pointer" style="color:var(--muted-foreground)"><input type="checkbox" onclick="togglePassword()"> Show passwords</label>
      <input type="text" id="customer_country" name="customer_country" placeholder="Country" autocomplete="country-name" maxlength="100" value="Ghana" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <input type="text" id="customer_city" name="customer_city" placeholder="City" autocomplete="address-level2" maxlength="100" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <input type="tel" id="customer_contact" name="customer_contact" placeholder="Phone number, e.g. 024 123 4567" inputmode="tel" autocomplete="tel" maxlength="16" class="<?= $inputStyle ?>" style="border-color:var(--border)">
      <button type="submit" class="px-5 py-3 text-sm font-medium btn-primary">Register</button>
    </form>
    <p id="formMessage" class="text-sm mt-4"></p>
    <p class="text-sm mt-6" style="color:var(--muted-foreground)">Already have an account? <a href="login.php<?= e($nextQs) ?>" class="font-medium" style="color:var(--primary)">Log in</a></p>
  </div>
  <script src="<?= asset('js/customer.js') ?>"></script>
</body>
</html>