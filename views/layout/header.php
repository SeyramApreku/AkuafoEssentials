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
  <script>
  // Security: add this session's CSRF token to every POST form (checked in core/core.php).
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (f) {
      if (f.querySelector('input[name="csrf"]')) return;
      var i = document.createElement('input'); i.type = 'hidden'; i.name = 'csrf'; i.value = <?= json_encode(csrf_token()) ?>; f.appendChild(i);
    });
  });
  </script>
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
      <?php $loggedIn = customer_id() > 0; $mt = $loggedIn && !is_admin() ? member_tier() : null;
            $first = $loggedIn ? explode(' ', trim($_SESSION['customer_name'] ?? 'there'))[0] : ''; ?>
      <div class="relative" id="account">
        <button type="button" id="account-btn" aria-haspopup="true" aria-expanded="false" aria-controls="account-menu"
                class="account-btn relative w-9 h-9 rounded-full flex items-center justify-center border" style="border-color:var(--border);background:var(--card)"
                title="<?= $loggedIn ? 'Your account' : 'Log in' ?>">
          <span class="sr-only">Account</span>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <circle class="account-head" cx="12" cy="8" r="4"/>
            <path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
          </svg>
          <?php if ($mt): ?><span class="absolute -bottom-1 -right-1" title="<?= e(ucfirst($mt)) ?> member"><?= tier_star($mt, 14) ?></span><?php endif; ?>
          <?php if ($loggedIn): ?><span class="absolute top-0 right-0 w-2 h-2 rounded-full" style="background:var(--primary);box-shadow:0 0 0 2px var(--background)"></span><?php endif; ?>
        </button>
        <div id="account-menu" class="account-menu absolute right-0 mt-2 w-56 border shadow-lg py-2 z-40" style="background:var(--card);border-color:var(--border)" role="menu" hidden>
          <?php if ($loggedIn): ?>
            <p class="px-4 pt-1 pb-2 text-xs border-b mb-1" style="color:var(--muted-foreground);border-color:var(--border)">Hi, <strong style="color:var(--foreground)"><?= e($first) ?></strong><?= $mt ? ' · ' . e(ucfirst($mt)) . ' member' : '' ?></p>
            <?php if (is_admin()): ?><button type="button" onclick="toggleAdminNav()" class="account-item w-full text-left" role="menuitem">Admin menu</button><?php endif; ?>
            <a href="<?= url('views/orders.php') ?>" class="account-item" role="menuitem">My orders</a>
            <a href="<?= url('views/addresses.php') ?>" class="account-item" role="menuitem">My addresses</a>
            <a href="<?= url('views/membership.php') ?>" class="account-item" role="menuitem"><?= $mt ? 'My membership' : 'Membership' ?></a>
            <form method="post" action="<?= url('actions/logout_action.php') ?>" class="border-t mt-1 pt-1" style="border-color:var(--border)"><?= csrf_field() ?><button class="account-item w-full text-left" style="color:var(--accent)" role="menuitem">Log out</button></form>
          <?php else: ?>
            <a href="<?= url('views/login.php') ?>" class="account-item" role="menuitem">Log in</a>
            <a href="<?= url('views/register.php') ?>" class="account-item" role="menuitem">Create an account</a>
          <?php endif; ?>
        </div>
      </div>
      <style>
        .account-head { transform-origin: 12px 12px; transform-box: view-box; animation: head-nod .9s ease-in-out .4s 1; }
        .account-btn:hover .account-head, .account-btn:focus-visible .account-head { animation: head-nod .9s ease-in-out infinite; }
        @keyframes head-nod { 0%, 100% { transform: translateY(0) rotate(0); } 30% { transform: translateY(-1.5px) rotate(-8deg); } 60% { transform: translateY(-1.5px) rotate(8deg); } }
        @media (prefers-reduced-motion: reduce) { .account-head { animation: none !important; } }
        .account-item { display: block; padding: 8px 16px; font-size: 13px; }
        .account-item:hover, .account-item:focus-visible { background: var(--muted); outline: none; }
      </style>
      <script>
      (function () {
        var btn = document.getElementById('account-btn'), menu = document.getElementById('account-menu');
        function set(open) { menu.hidden = !open; btn.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open) { var f = menu.querySelector('a,button'); if (f) f.focus(); } }
        btn.addEventListener('click', function (e) { e.stopPropagation(); set(menu.hidden); });
        document.addEventListener('click', function (e) { if (!menu.hidden && !document.getElementById('account').contains(e.target)) set(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { set(false); btn.focus(); } });
      })();
      </script>

      <button data-open-cart aria-label="Open cart" class="flex items-center gap-1.5 text-xs font-medium px-3.5 py-2 btn-primary">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a1 1 0 0 0 1 .8h9.2a1 1 0 0 0 1-.8L21 7H6"/></svg>
  <span id="cart-count" class="w-4 h-4 rounded-full text-xs items-center justify-center font-bold" style="background:var(--accent);color:var(--accent-foreground);display:<?= $cartObj->count() > 0 ? 'flex' : 'none' ?>"><?= $cartObj->count() ?></span>
</button>
      <button id="menu-btn" class="lg:hidden p-1"><div class="w-5 flex flex-col gap-[5px]"><span class="block h-px w-full bg-current"></span><span class="block h-px w-3/4 bg-current"></span><span class="block h-px w-full bg-current"></span></div></button>
    </div>
  </div>
  <div id="mobile-menu" class="hidden lg:hidden border-t px-5 py-3" style="border-color:var(--border)">
    <?php foreach (nav_items() as $path => $label): ?><a href="<?= url($path) ?>" class="block py-2 text-sm font-medium"><?= $label ?></a><?php endforeach; ?>
  </div>
</header>
<!-- Site-wide confirmation dialog (replaces the browser's "this site says" box).
     Use: <form data-confirm="Delete this address?" data-confirm-yes="Delete">. Old onsubmit="return confirm('…')" forms are upgraded automatically. -->
<div id="aku-confirm" role="dialog" aria-modal="true" aria-labelledby="aku-confirm-text" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(26,25,22,.55);align-items:center;justify-content:center;padding:16px">
  <div style="background:var(--card);border:1px solid var(--border);padding:28px;max-width:440px;width:100%">
    <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Please confirm</p>
    <p id="aku-confirm-text" class="font-display text-2xl font-light leading-snug mb-6"></p>
    <div class="flex flex-wrap gap-3">
      <button type="button" id="aku-confirm-yes" class="px-6 py-3 text-sm font-medium btn-primary">Yes</button>
      <button type="button" id="aku-confirm-no" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">No, go back</button>
    </div>
  </div>
</div>
<script>
(function () {
  window.akuConfirm = true;   // admin_menu.php uses this dialog instead of its own when both are on a page
  var box = document.getElementById('aku-confirm'), text = document.getElementById('aku-confirm-text'),
      yes = document.getElementById('aku-confirm-yes'), no = document.getElementById('aku-confirm-no'), pending = null, opener = null;
  function close() { box.style.display = 'none'; pending = null; if (opener) opener.focus(); }
  // Upgrade old forms: onsubmit="return confirm('message')"  ->  data-confirm="message"
  document.addEventListener('DOMContentLoaded', function () {   // forms further down the page exist only now
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function (f) {
      var m = f.getAttribute('onsubmit').match(/confirm\((['"])([\s\S]*?)\1\)/);
      if (m) { f.setAttribute('data-confirm', m[2]); f.removeAttribute('onsubmit'); }
    });
  });
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.dataset.confirmed === '1') return;
    e.preventDefault(); e.stopImmediatePropagation();
    pending = f; opener = document.activeElement;
    text.textContent = f.getAttribute('data-confirm');
    yes.textContent = f.getAttribute('data-confirm-yes') || 'Yes, continue';
    box.style.display = 'flex'; no.focus();
  }, true);
  yes.addEventListener('click', function () { if (!pending) return; var f = pending; f.dataset.confirmed = '1'; box.style.display = 'none'; f.submit(); });
  no.addEventListener('click', close);
  box.addEventListener('click', function (e) { if (e.target === box) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && box.style.display === 'flex') close(); });
})();
</script>
<?php if (is_admin()) include __DIR__ . '/admin_menu.php'; ?>

<?php include __DIR__ . '/cart_drawer.php'; ?>
