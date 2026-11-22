<?php $navName = $_SESSION['customer_name'] ?? 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Admin | Akuafo & Essentials') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="min-h-screen">
<header class="sticky top-0 z-30 border-b" style="background:var(--background);border-color:var(--border)">
  <div class="max-w-7xl mx-auto px-5 h-16 grid grid-cols-3 items-center">
    <button type="button" onclick="toggleAdminNav()" class="flex items-center gap-3 text-xs font-medium uppercase tracking-widest justify-self-start">
      <span class="flex flex-col gap-[5px]"><i class="block h-px w-5 bg-current"></i><i class="block h-px w-5 bg-current"></i><i class="block h-px w-5 bg-current"></i></span>
      Menu
    </button>
    <a href="<?= url('views/admin/admin_manage.php') ?>" class="font-display text-base font-medium tracking-tight justify-self-center">Akuafo <span style="color:var(--accent)">&amp;</span> Essentials</a>
    <span class="text-xs uppercase tracking-widest justify-self-end" style="color:var(--muted-foreground)"><?= e($navName) ?></span>
  </div>
</header>
<?php include __DIR__ . '/admin_menu.php'; ?>