<?php
$script = basename($_SERVER['SCRIPT_NAME']);
$tCur = $script === 'admin_manage.php' ? ($_GET['t'] ?? 'products') : (['admin_boxes.php' => 'boxes', 'admin_orders.php' => 'orders', 'admin_customers.php' => 'customers', 'admin_ent_items.php' => 'ent_items'][$script] ?? null);
$navName = $_SESSION['customer_name'] ?? 'Admin';
$links = [
    ['Products',     'views/admin/admin_manage.php?t=products',   'products'],
    ['Categories',   'views/admin/admin_manage.php?t=categories', 'categories'],
    ['Regions',      'views/admin/admin_manage.php?t=regions',    'regions'],
    ['Farmers',      'views/admin/admin_manage.php?t=farmers',    'farmers'],
    ['Recipes',      'views/admin/admin_manage.php?t=recipes',    'recipes'],
    ['Partners',     'views/admin/admin_manage.php?t=partners',   'partners'],
    ['Weekly Boxes', 'views/admin/admin_boxes.php',         'boxes'],
    ['Entertaining', 'views/admin/admin_ent_items.php',  'ent_items'],
    ['Occasions',    'views/admin/admin_manage.php?t=ent_occasions',  'ent_occasions'],
    ['Event Categories', 'views/admin/admin_manage.php?t=ent_categories', 'ent_categories'],
    ['Orders',       'views/admin/admin_orders.php',        'orders'],
    ['Customers',    'views/admin/admin_customers.php',     'customers'],
    ['Delivery Zones', 'views/admin/admin_manage.php?t=delivery_zones', 'delivery_zones'],
    ['View Store',   'index.php',                           null],
];
?>

<style>
  #adminOverlay { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.5); opacity: 0; pointer-events: none; transition: opacity .3s; }
  #adminOverlay.open { opacity: 1; pointer-events: auto; }
  #adminDrawer { position: fixed; top: 0; left: 0; z-index: 70; width: 320px; max-width: 85vw; height: 100vh; padding: 32px; display: flex; flex-direction: column; overflow-y: auto; background: var(--primary); color: var(--primary-foreground); transform: translateX(-100%); transition: transform .3s ease; }
  #adminDrawer.open { transform: translateX(0); }
  #adminDrawer .a-link { padding: 6px 0; font-size: 26px; line-height: 1.2; opacity: .75; }
  #adminDrawer .a-link:hover, #adminDrawer .a-link.active { opacity: 1; color: var(--accent); }
</style>
<div id="adminOverlay" onclick="toggleAdminNav()"></div>
<aside id="adminDrawer">
  <button type="button" onclick="toggleAdminNav()" class="self-start mb-10 text-xs font-medium uppercase tracking-widest">Close</button>
  <nav class="flex flex-col gap-1 flex-1">
    <?php foreach ($links as [$label, $path, $key]): ?>
      <a href="<?= url($path) ?>" class="a-link font-display <?= $key !== null && $key === $tCur ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="pt-5 border-t" style="border-color:rgba(255,255,255,.2)">
    <p class="text-xs uppercase tracking-widest mb-3 opacity-70"><?= e($navName) ?></p>
    <a href="<?= url('actions/logout_action.php') ?>" class="text-xs font-medium uppercase tracking-widest">Logout</a>
  </div>
</aside>
<script>
// Adds the CSRF token to every POST form on admin pages (checked by require_admin()).
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (f) {
    if (f.querySelector('input[name="csrf"]')) return;
    var i = document.createElement('input');
    i.type = 'hidden'; i.name = 'csrf'; i.value = <?= json_encode(csrf_token()) ?>;
    f.appendChild(i);
  });
});
function toggleAdminNav() {
  document.getElementById('adminDrawer').classList.toggle('open');
  document.getElementById('adminOverlay').classList.toggle('open');
}
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    document.getElementById('adminDrawer').classList.remove('open');
    document.getElementById('adminOverlay').classList.remove('open');
  }
});
</script>

<?php if (!empty($msg)): ?>
<div id="noticeModal" onclick="if (event.target === this) this.style.display='none'" style="display:flex;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:var(--background);border:1px solid var(--border);padding:32px;max-width:440px;width:90%">
    <p class="font-display text-2xl mb-6"><?= e($msg) ?></p>
    <button type="button" onclick="document.getElementById('noticeModal').style.display='none'" class="px-6 py-3 text-xs font-medium uppercase tracking-widest btn-primary">OK</button>
  </div>
</div>
<?php endif; ?>

<div id="confirmModal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:var(--background);border:1px solid var(--border);padding:32px;max-width:440px;width:90%">
    <p id="confirmText" class="font-display text-2xl mb-6"></p>
    <div class="flex gap-3">
      <button type="button" id="confirmYes" class="px-6 py-3 text-xs font-medium uppercase tracking-widest btn-primary">Yes, delete</button>
      <button type="button" id="confirmNo" class="px-6 py-3 text-xs font-medium uppercase tracking-widest border" style="border-color:var(--border)">No</button>
    </div>
  </div>
</div>
<script>
(function () {
  var modal = document.getElementById('confirmModal');
  var text = document.getElementById('confirmText');
  var pending = null;
  function close() { modal.style.display = 'none'; pending = null; }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute('data-confirm')) return;
    e.preventDefault();
    pending = f;
    text.textContent = f.getAttribute('data-confirm');
    modal.style.display = 'flex';
  });
  document.getElementById('confirmYes').addEventListener('click', function () { if (pending) pending.submit(); });
  document.getElementById('confirmNo').addEventListener('click', close);
  modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>