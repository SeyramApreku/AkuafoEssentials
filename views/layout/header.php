<?php
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/cart_controller.php';
$cartObj = cart();
$current = basename($_SERVER['SCRIPT_NAME']);
$autoOpen = !empty($_SESSION['cart_open']); unset($_SESSION['cart_open']);
$here = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Akuafo & Essentials') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-30 border-b" style="background:var(--background);border-color:var(--border)">
  <div class="max-w-7xl mx-auto px-5 h-16 flex items-center justify-between gap-4">
    <a href="<?= url('index.php') ?>" class="flex items-center gap-2.5 shrink-0">
      <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:var(--primary);color:var(--primary-foreground)">A</div>
      <span class="font-display text-base font-medium tracking-tight">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</span>
    </a>
    <nav class="hidden lg:flex items-center gap-6">
      <?php foreach (nav_items() as $path => $label): ?>
        <a href="<?= url($path) ?>" class="text-xs font-medium" style="color:<?= basename($path) === $current ? 'var(--primary)' : 'var(--muted-foreground)' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="flex items-center gap-3">
      <?php if (is_admin()): ?><button type="button" onclick="toggleAdminNav()" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Admin menu</button><?php endif; ?>

        <?php if (!is_admin() && ($mt = member_tier())): ?><span class="inline-flex items-center gap-1 text-xs font-medium" title="<?= e(ucfirst($mt)) ?> member"><?= tier_star($mt) ?><span class="hidden md:inline"><?= e(ucfirst($mt)) ?></span></span><?php endif; ?>
          <a href="<?= url('actions/logout_action.php') ?>" class="text-xs font-medium" style="color:var(--muted-foreground)">Log out</a>
          
      <button data-open-cart aria-label="Open cart" class="flex items-center gap-1.5 text-xs font-medium px-3.5 py-2 btn-primary">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 7H6"/></svg>
  <?php if ($cartObj->count() > 0): ?><span class="w-4 h-4 rounded-full text-xs flex items-center justify-center font-bold" style="background:var(--accent);color:var(--accent-foreground)"><?= $cartObj->count() ?></span><?php endif; ?>
</button>
      <button id="menu-btn" class="lg:hidden p-1"><div class="w-5 flex flex-col gap-[5px]"><span class="block h-px w-full bg-current"></span><span class="block h-px w-3/4 bg-current"></span><span class="block h-px w-full bg-current"></span></div></button>
    </div>
  </div>
  <div id="mobile-menu" class="hidden lg:hidden border-t px-5 py-3" style="border-color:var(--border)">
    <?php foreach (nav_items() as $path => $label): ?><a href="<?= url($path) ?>" class="block py-2 text-sm font-medium"><?= $label ?></a><?php endforeach; ?>
  </div>
</header>
<?php if (is_admin()) include __DIR__ . '/admin_menu.php'; ?>

<?php include __DIR__ . '/cart_drawer.php'; ?>
