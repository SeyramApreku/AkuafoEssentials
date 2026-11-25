<?php
$script = basename($_SERVER['SCRIPT_NAME']);
$tCur = $script === 'admin_manage.php' ? ($_GET['t'] ?? 'products') : (['admin_boxes.php' => 'boxes', 'admin_orders.php' => 'orders', 'admin_customers.php' => 'customers', 'admin_ent_items.php' => 'ent_items', 'admin_ent_orders.php' => 'ent_orders', 'admin_ent_capacity.php' => 'ent_capacity', 'admin_ratings.php' => 'ratings', 'admin_setup.php' => 'setup', 'admin_revenue.php' => 'revenue', 'admin_inventory.php' => 'inventory', 'admin_partners.php' => 'partners', 'admin_memberships.php' => 'memberships', 'admin_farmer_payouts.php' => 'farmer_payouts', 'admin_member_recipes.php' => 'member_recipes'][$script] ?? null);
if ($script === 'admin_delivery.php') $tCur = ($_GET['tab'] ?? '') === 'settings' ? 'delivery_settings' : 'delivery_zones';
$navName = $_SESSION['customer_name'] ?? 'Admin';
$links = [
    ['Products',     'views/admin/admin_manage.php?t=products',   'products'],
    ['Categories',   'views/admin/admin_manage.php?t=categories', 'categories'],
    ['Regions',      'views/admin/admin_manage.php?t=regions',    'regions'],
    ['Farmers',      'views/admin/admin_manage.php?t=farmers',    'farmers'],
    ['Recipes',      'views/admin/admin_manage.php?t=recipes',    'recipes'],
    ['Partners',     'views/admin/admin_partners.php',      'partners'],
    ['Weekly Boxes', 'views/admin/admin_boxes.php',         'boxes'],
    ['Entertaining', 'views/admin/admin_ent_items.php',  'ent_items'],
    ['Occasions',    'views/admin/admin_manage.php?t=ent_occasions',  'ent_occasions'],
    ['Event Categories', 'views/admin/admin_manage.php?t=ent_categories', 'ent_categories'],
    ['Event Orders', 'views/admin/admin_ent_orders.php',    'ent_orders'],
    ['Event Capacity', 'views/admin/admin_ent_capacity.php', 'ent_capacity'],
    ['Orders',       'views/admin/admin_orders.php',        'orders'],
    ['Customers',    'views/admin/admin_customers.php',     'customers'],
    ['Memberships',  'views/admin/admin_memberships.php',   'memberships'],
    ['Farmer Payouts', 'views/admin/admin_farmer_payouts.php', 'farmer_payouts'],
    ['Member Recipes', 'views/admin/admin_member_recipes.php', 'member_recipes'],
    ['Delivery Zones', 'views/admin/admin_delivery.php?tab=zones', 'delivery_zones'],
    ['Delivery Settings', 'views/admin/admin_delivery.php?tab=settings', 'delivery_settings'],
    ['Revenue',      'views/admin/admin_revenue.php',       'revenue'],
    ['Inventory',    'views/admin/admin_inventory.php',     'inventory'],
    ['Database Update', 'views/admin/admin_setup.php',      'setup'],
];
// Alphabetical order (new pages slot in automatically); "View Store" always last since it leaves the admin.
usort($links, fn($a, $b) => strcasecmp($a[0], $b[0]));
$links[] = ['View Store', 'index.php', null];
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
    <form method="post" action="<?= url('actions/logout_action.php') ?>"><?= csrf_field() ?><button class="text-xs font-medium uppercase tracking-widest">Logout</button></form>
  </div>
</aside>
<?php $restoreForm = !empty($_SESSION['admin_restore']); unset($_SESSION['admin_restore']); ?>
<?php
// Products made from a farmer's produce wait as drafts: tag them in the list, and hide the internal
// "is active" box (saving a product with its price, unit and category makes it live).
$draftIds = [];
if (($_GET['t'] ?? '') === 'products' && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'admin_manage.php') {
    try { require_once __DIR__ . '/../../classes/catalog_class.php'; $draftIds = (new CatalogClass())->draftIds(); } catch (Throwable $e) { $draftIds = []; }
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (<?= json_encode(basename($_SERVER['SCRIPT_NAME'] ?? '') === 'admin_manage.php') ?>) document.querySelectorAll('[name="farmer_id"]').forEach(function (el) { var box = el.closest('div'); (box || el).style.display = 'none'; el.disabled = true; });
  document.querySelectorAll('[name="is_active"]').forEach(function (el) { var box = el.closest('div'); (box || el).style.display = 'none'; el.disabled = true; });
  var DRAFTS = <?= json_encode($draftIds) ?>;
  DRAFTS.forEach(function (id) {
    document.querySelectorAll('a[href*="edit=' + id + '"]').forEach(function (a) {
      if (!/[?&]edit=\d+$/.test(a.getAttribute('href')) || a.getAttribute('href').split('edit=')[1] !== String(id)) return;
      var name = a.parentNode.querySelector('p.text-sm');
      if (name && !name.querySelector('.draft-tag')) {
        var tag = document.createElement('span'); tag.className = 'draft-tag';
        tag.textContent = 'Draft · add price, unit & category';
        tag.style.cssText = 'margin-left:8px;font-size:10px;padding:2px 6px;background:#f3e3b5;color:#7a5a00;text-transform:uppercase;letter-spacing:.05em';
        name.appendChild(tag);
      }
      a.textContent = 'Complete';
    });
  });
  if (DRAFTS.length) {
    var h = document.querySelector('h1');
    if (h) { var p = document.createElement('p'); p.className = 'text-sm mb-6 p-3'; p.style.background = 'var(--muted)';
      p.textContent = DRAFTS.length + ' product' + (DRAFTS.length === 1 ? '' : 's') + " from farmers' produce " + (DRAFTS.length === 1 ? 'is' : 'are') + ' waiting for a price, unit and category. Drafts are hidden from the shop.';
      h.insertAdjacentElement('afterend', p); }
  }
});
</script>
<style>.field-hint { font-size: 12px; margin-top: 4px; color: var(--muted-foreground); }</style>
<script>
// Typing help on admin forms (example text, a hint under the box, browser limits) from admin_field_help()
// in core/core.php, and: when a save is refused, the form is refilled with what was typed.
(function () {
  var HELP = <?= json_encode(admin_field_help(), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>, RESTORE = <?= $restoreForm ? 'true' : 'false' ?>;
  function formKey(f) {
    var t = f.querySelector('[name="t"]'), id = f.querySelector('[name="id"],[name="box_id"]'), act = f.querySelector('[name="act"]');
    return 'aku-form:' + (f.getAttribute('action') || '').split('/').pop() + ':' + (t ? t.value : '') + ':' + (act ? act.value : '') + ':' + (id ? id.value : '');
  }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (f) {
      var file = (f.getAttribute('action') || '').split('/').pop(), t = f.querySelector('[name="t"]');
      var maps = [t ? HELP['t:' + t.value] : null, HELP[file]].filter(Boolean);
      Array.prototype.forEach.call(f.elements, function (el) {
        if (!el.name || el.type === 'hidden' || el.type === 'file') return;
        var entry = null;
        maps.forEach(function (m) { Object.keys(m).forEach(function (k) { if (!entry && (k === el.name || (k.charAt(0) === '[' && el.name.slice(-k.length) === k))) entry = m[k]; }); });
        if (!entry) return;
        if (entry[0] && el.tagName !== 'SELECT' && !el.placeholder) el.placeholder = entry[0];
        if (entry[1] && !(el.nextElementSibling && el.nextElementSibling.classList.contains('field-hint'))) {
          var p = document.createElement('p'); p.className = 'field-hint'; p.textContent = entry[1]; el.insertAdjacentElement('afterend', p);
        }
        Object.keys(entry[2] || {}).forEach(function (a) {
          if (a === 'required') el.required = true;
          else if (a === 'step' || a === 'min' || !el.hasAttribute(a)) el.setAttribute(a, entry[2][a]);
        });
      });
      // Remember what was typed, so a refused save can be refilled.
      f.addEventListener('submit', function () {
        var data = {};
        Array.prototype.forEach.call(f.elements, function (el) {
          if (!el.name || el.type === 'file' || el.name === 'csrf') return;
          data[el.name + (el.type === 'checkbox' || el.type === 'radio' ? '=' + el.value : '')] = (el.type === 'checkbox' || el.type === 'radio') ? el.checked : el.value;
        });
        try { sessionStorage.setItem(formKey(f), JSON.stringify(data)); } catch (e) {}
      });
      var key = formKey(f), saved = null;
      try { saved = sessionStorage.getItem(key); sessionStorage.removeItem(key); } catch (e) {}
      if (RESTORE && saved) {
        var data = JSON.parse(saved);
        Array.prototype.forEach.call(f.elements, function (el) {
          if (!el.name || el.type === 'file' || el.name === 'csrf' || el.type === 'hidden') return;
          if (el.type === 'checkbox' || el.type === 'radio') { var k = el.name + '=' + el.value; if (k in data) el.checked = data[k]; }
          else if (el.name in data) el.value = data[el.name];
        });
      }
    });
  });
})();

// Photo uploads: big pictures (phone/camera photos) are resized to max 1600px and compressed in the
// browser before upload, so they stay well under the server's size limit. HEIC (iPhone) can't be
// read by most browsers, so it's caught here with a clear message.
(function () {
  var MAX_SIDE = 1600, TARGET = 900 * 1024;   // resize anything larger than 1600px or ~900 KB
  function note(input, text, bad) {
    var p = input.parentNode.querySelector('.img-note');
    if (!p) { p = document.createElement('p'); p.className = 'img-note text-xs mt-1'; input.insertAdjacentElement('afterend', p); }
    p.textContent = text; p.style.color = bad ? '#b3261e' : 'var(--primary)';
  }
  function busy(form, on) { form.querySelectorAll('button:not([type=button])').forEach(function (b) { b.disabled = on; b.style.opacity = on ? '.5' : ''; }); }
  document.addEventListener('change', function (e) {
    var input = e.target, file = input.files && input.files[0];
    if (input.type !== 'file' || !file || !/image/.test(input.accept || 'image')) return;
    if (/\.(heic|heif)$/i.test(file.name) || /hei[cf]/i.test(file.type)) {
      input.value = ''; note(input, 'This is an iPhone HEIC photo, which websites can\'t use. Please export it as JPG first.', true); return;
    }
    if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) { input.value = ''; note(input, 'Please choose a JPG, PNG or WebP picture.', true); return; }
    var url = URL.createObjectURL(file), img = new Image(), form = input.form;
    img.onload = function () {
      URL.revokeObjectURL(url);
      var w = img.naturalWidth, h = img.naturalHeight, scale = Math.min(1, MAX_SIDE / Math.max(w, h));
      if (scale === 1 && file.size <= TARGET) { note(input, 'Ready to upload (' + Math.round(file.size / 1024) + ' KB).'); return; }
      var c = document.createElement('canvas'); c.width = Math.round(w * scale); c.height = Math.round(h * scale);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      var type = file.type === 'image/png' ? 'image/webp' : 'image/jpeg';   // webp keeps transparent logos
      if (form) busy(form, true);
      c.toBlob(function (blob) {
        if (form) busy(form, false);
        if (!blob || blob.size >= file.size && scale === 1) { note(input, 'Ready to upload (' + Math.round(file.size / 1024) + ' KB).'); return; }
        var ext = blob.type === 'image/webp' ? '.webp' : blob.type === 'image/png' ? '.png' : '.jpg';
        var dt = new DataTransfer(); dt.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + ext, { type: blob.type }));
        input.files = dt.files;
        note(input, 'Resized for the web: ' + (file.size / 1048576).toFixed(1) + ' MB → ' + Math.round(blob.size / 1024) + ' KB.');
      }, type, 0.85);
    };
    img.onerror = function () { URL.revokeObjectURL(url); input.value = ''; note(input, 'That picture couldn\'t be opened. Please try a JPG or PNG.', true); };
    img.src = url;
  });
})();

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
  if (window.akuConfirm) return;   // on public pages the site-wide dialog in header.php already handles data-confirm
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