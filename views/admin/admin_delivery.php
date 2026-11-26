<?php
// Admin: delivery zones (fee, days, on/off) with their areas, and delivery settings.
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../classes/delivery_class.php';
require_admin();

$dc    = new DeliveryClass();
$tab   = ($_GET['tab'] ?? 'zones') === 'settings' ? 'settings' : 'zones';
$zones = $dc->zones();
$zoneId = (int)($_GET['zone'] ?? 0);
$zone   = $zoneId ? $dc->zone($zoneId) : null;
$isNew  = isset($_GET['new']);
$areas  = $zone ? $dc->areas($zone['id']) : [];
$msg = $_SESSION['admin_msg'] ?? null; unset($_SESSION['admin_msg']);
$pageTitle = 'Delivery | Admin';
include __DIR__ . '/../layout/admin_header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
$act = url('actions/admin_delivery_action.php');
$days = zone_day_names();
?>
<div class="max-w-6xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Admin · Delivery</p>
  <h1 class="font-display text-4xl font-light mb-6"><?= $tab === 'settings' ? 'Delivery settings' : 'Delivery zones' ?></h1>
  <div class="flex gap-2 mb-8 text-xs uppercase tracking-widest">
    <a href="?tab=zones" class="px-3 py-2 border <?= $tab === 'zones' ? 'pill-on' : '' ?>" style="border-color:var(--border)">Zones &amp; areas</a>
    <a href="?tab=settings" class="px-3 py-2 border <?= $tab === 'settings' ? 'pill-on' : '' ?>" style="border-color:var(--border)">Settings</a>
  </div>

<?php if ($tab === 'settings'): ?>
  <form method="post" action="<?= $act ?>" class="grid md:grid-cols-3 gap-4 p-6 border max-w-3xl" style="border-color:var(--border)">
    <input type="hidden" name="act" value="settings">
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Minimum order (GHS)</label>
      <input type="number" name="min_order" min="0" step="0.01" required value="<?= e(min_order()) ?>" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Checkout is blocked below this.</p>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Free delivery from (GHS)</label>
      <input type="number" name="free_delivery_threshold" min="0" step="0.01" required value="<?= e(free_delivery_threshold()) ?>" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Orders at or above this get free delivery. 0 turns it off.</p>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Next-day cutoff time</label>
      <input type="time" name="order_cutoff_time" required value="<?= e(order_cutoff_time()) ?>" class="<?= $box ?>" style="border-color:var(--border)">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Orders after this time can't get next-day delivery.</p>
    </div>
    <div class="md:col-span-3"><button class="px-6 py-3 text-sm font-medium btn-primary">Save settings</button></div>
  </form>

<?php else: ?>
  <a href="?new=1" class="inline-block mb-6 px-4 py-2 text-xs font-medium border" style="border-color:var(--border)">+ Add zone</a>

  <?php if ($zone || $isNew): $zDays = $zone ? explode(',', $zone['delivery_days']) : array_slice($days, 0, 6); ?>
  <form method="post" action="<?= $act ?>" class="grid md:grid-cols-2 gap-4 mb-8 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl md:col-span-2"><?= $zone ? 'Edit ' . e($zone['name']) : 'Add a zone' ?></h2>
    <input type="hidden" name="act" value="save_zone"><input type="hidden" name="id" value="<?= (int)($zone['id'] ?? 0) ?>">
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Zone name</label>
      <input type="text" name="name" required maxlength="100" value="<?= e($zone['name'] ?? '') ?>" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div><label class="<?= $lbl ?>" style="color:var(--muted-foreground)">Delivery fee (GHS)</label>
      <input type="number" name="fee" required min="0" step="0.01" value="<?= e($zone['fee'] ?? '') ?>" class="<?= $box ?>" style="border-color:var(--border)"></div>
    <div class="md:col-span-2"><span class="<?= $lbl ?>" style="color:var(--muted-foreground)">Delivery days</span>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($days as $d): ?>
          <label class="pick relative text-xs px-3 py-2 border cursor-pointer" style="border-color:var(--border)"><input type="checkbox" name="days[]" value="<?= $d ?>" <?= in_array($d, $zDays, true) ? 'checked' : '' ?>><?= $d ?></label>
        <?php endforeach; ?>
      </div></div>
    <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="is_active" value="1" <?= (int)($zone['is_active'] ?? 1) ? 'checked' : '' ?>> Active (customers in this zone can order)</label>
    <div class="md:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $zone ? 'Save zone' : 'Add zone' ?></button>
      <a href="?tab=zones" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Close</a>
    </div>
  </form>

  <?php if ($zone): ?>
  <div class="mb-12 p-6 border" style="border-color:var(--border)">
    <h2 class="font-display text-xl mb-4">Areas in <?= e($zone['name']) ?> (<?= count($areas) ?>)</h2>
    <?php foreach ($areas as $a): ?>
      <div class="flex flex-wrap items-center gap-3 py-2 border-b" style="border-color:var(--border)">
        <form method="post" action="<?= $act ?>" class="flex flex-wrap items-center gap-3 flex-1">
          <input type="hidden" name="act" value="save_area"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <input type="text" name="name" required maxlength="100" value="<?= e($a['name']) ?>" class="px-3 py-2 text-sm border bg-transparent flex-1 min-w-[10rem]" style="border-color:var(--border)">
          <select name="zone_id" class="px-3 py-2 text-sm border bg-transparent" style="border-color:var(--border)" aria-label="Zone">
            <?php foreach ($zones as $z): ?><option value="<?= (int)$z['id'] ?>" <?= (int)$z['id'] === (int)$a['zone_id'] ? 'selected' : '' ?>><?= e($z['name']) ?></option><?php endforeach; ?>
          </select>
          <label class="flex items-center gap-1.5 text-xs"><input type="checkbox" name="is_active" value="1" <?= (int)$a['is_active'] ? 'checked' : '' ?>> Active</label>
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Save</button>
        </form>
        <form method="post" action="<?= $act ?>" data-confirm="Delete the area <?= e('"' . $a['name'] . '"') ?>?">
          <input type="hidden" name="act" value="delete_area"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="zone" value="<?= (int)$zone['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
    <form method="post" action="<?= $act ?>" class="flex gap-3 mt-4">
      <input type="hidden" name="act" value="save_area"><input type="hidden" name="id" value="0"><input type="hidden" name="zone_id" value="<?= (int)$zone['id'] ?>"><input type="hidden" name="is_active" value="1">
      <input type="text" name="name" required maxlength="100" placeholder="New area, e.g. Teshie" class="<?= $box ?>" style="border-color:var(--border)">
      <button class="px-6 py-3 text-sm font-medium btn-primary whitespace-nowrap">Add area</button>
    </form>
  </div>
  <?php endif; endif; ?>

  <div class="border" style="border-color:var(--border)">
    <?php foreach ($zones as $z): ?>
      <div class="flex items-center gap-4 p-4 border-b last:border-b-0" style="border-color:var(--border);<?= (int)$z['is_active'] ? '' : 'opacity:.55' ?>">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium"><?= e($z['name']) ?> · <?= money($z['fee']) ?>
            <?php if (!(int)$z['is_active']): ?><span class="text-xs px-1.5 py-0.5 ml-1" style="background:var(--muted);color:var(--accent)">Inactive</span><?php endif; ?></p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e(str_replace(',', ', ', $z['delivery_days'])) ?> · <?= (int)$z['area_count'] ?> area<?= (int)$z['area_count'] === 1 ? '' : 's' ?></p>
        </div>
        <a href="?zone=<?= (int)$z['id'] ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit &amp; areas</a>
        <form method="post" action="<?= $act ?>" data-confirm="Delete the zone <?= e('"' . $z['name'] . '"') ?>?">
          <input type="hidden" name="act" value="delete_zone"><input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
          <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button>
        </form>
      </div>
    <?php endforeach; ?>
    <?php if (!$zones): ?><p class="p-6 text-sm" style="color:var(--muted-foreground)">No zones yet.</p><?php endif; ?>
  </div>

  <?php $wait = []; try { $wait = $dc->waitlist(); } catch (PDOException $e) { /* table created in D2 */ } ?>
  <h2 class="font-display text-2xl mt-12 mb-2">Delivery waitlist (<?= count($wait) ?>)</h2>
  <p class="text-xs mb-4" style="color:var(--muted-foreground)">Customers asking us to deliver to areas we don't cover yet. Pick a zone and click <strong>Add</strong>: the area starts getting deliveries and everyone who asked for it is emailed automatically.</p>
  <div class="border" style="border-color:var(--border)">
    <?php foreach ($wait as $w): ?>
      <div class="flex flex-wrap items-center gap-4 p-3 border-b last:border-b-0 text-sm" style="border-color:var(--border)">
        <span class="font-medium w-40 truncate"><?= e($w['area_name']) ?></span>
        <a href="mailto:<?= e($w['email']) ?>" class="flex-1 truncate underline"><?= e($w['email']) ?></a>
        <span class="text-xs" style="color:var(--muted-foreground)"><?= e(date('j M Y', strtotime($w['created_at']))) ?></span>
        <?php if ($zones): ?>
        <form method="post" action="<?= $act ?>" class="flex flex-wrap items-center gap-2" data-confirm="Start delivering to <?= e('"' . $w['area_name'] . '"') ?> and email everyone who asked for it?" data-confirm-yes="Add area">
          <input type="hidden" name="act" value="add_waitlist"><input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
          <input type="text" name="area_name" value="<?= e($w['area_name']) ?>" maxlength="100" aria-label="Area name" title="You can tidy the name before adding it" class="px-2 py-1.5 text-xs border bg-transparent w-36" style="border-color:var(--border)">
          <select name="zone_id" required class="px-2 py-1.5 text-xs border bg-transparent" style="border-color:var(--border)" aria-label="Zone">
            <option value="">Zone…</option>
            <?php foreach ($zones as $z): ?><option value="<?= (int)$z['id'] ?>"><?= e($z['name']) ?> · <?= money($z['fee']) ?></option><?php endforeach; ?>
          </select>
          <button class="text-xs font-medium px-3 py-1.5 btn-primary">Add</button>
        </form>
        <?php endif; ?>
        <form method="post" action="<?= $act ?>" data-confirm="Remove <?= e($w['email']) ?> from the waitlist without adding the area?"><input type="hidden" name="act" value="delete_waitlist"><input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
          <button class="text-xs font-medium px-3 py-1.5 border" style="border-color:var(--border);color:#b3261e">Remove</button></form>
      </div>
    <?php endforeach; ?>
    <?php if (!$wait): ?><p class="p-4 text-sm" style="color:var(--muted-foreground)">Nobody on the waitlist.</p><?php endif; ?>
  </div>
<?php endif; ?>
</div>
</body>
</html>
