<?php
// Customer: saved delivery addresses (GhanaPostGPS) and the "we don't deliver here yet" waitlist.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/delivery_class.php';

$back = safe_next($_GET['next'] ?? '');                 // e.g. views/checkout.php when coming from checkout
require_login('views/addresses.php' . ($back ? '?next=' . urlencode($back) : ''));

$dc        = new DeliveryClass();
$addresses = $dc->addresses(customer_id());
$areas     = $dc->areasForSelect();
$editId    = (int)($_GET['edit'] ?? 0);
$edit      = $editId ? $dc->address($editId, customer_id()) : null;
$showForm  = $edit || isset($_GET['new']) || !$addresses;
$old       = $_SESSION['address_old'] ?? []; unset($_SESSION['address_old']);
$f         = $old ?: ($edit ?: ['label' => '', 'gps_address' => '', 'area_id' => 0, 'landmark' => '', 'notes' => '', 'phone' => '']);
$pageMsg       = $_SESSION['address_msg'] ?? null; unset($_SESSION['address_msg']);
$nextQs    = $back ? '&next=' . urlencode($back) : '';
$pageTitle = 'My addresses | Akuafo & Essentials';
include __DIR__ . '/layout/header.php';
$box = 'w-full px-4 py-3 text-sm border outline-none bg-transparent';
$lbl = 'text-xs uppercase tracking-widest mb-2 block';
$act = url('actions/address_action.php');
?>
<div class="max-w-3xl mx-auto px-6 py-12">
  <p class="text-xs uppercase tracking-widest mb-2" style="color:var(--accent)">Your account</p>
  <h1 class="font-display text-4xl font-light mb-2">My addresses</h1>
  <p class="text-sm mb-8" style="color:var(--muted-foreground)">Where should we deliver? Save as many addresses as you like and choose one at checkout.</p>
  <?php if ($back === 'views/checkout.php'): ?><a href="<?= url('views/checkout.php') ?>" class="text-xs mb-6 inline-block" style="color:var(--primary)">&larr; Back to checkout</a><?php endif; ?>
  <?php if ($pageMsg): ?><p class="text-sm mb-6 p-3" style="background:var(--muted);color:<?= $pageMsg[0] ? 'var(--primary)' : 'var(--accent)' ?>"><?= e($pageMsg[1]) ?></p><?php endif; ?>

  <?php if ($addresses): ?>
  <div class="border mb-8" style="border-color:var(--border)">
    <?php foreach ($addresses as $a): ?>
      <div class="p-5 border-b last:border-b-0 flex flex-col sm:flex-row sm:items-center gap-4" style="border-color:var(--border)">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium">
            <?= e($a['label'] ?: 'Address') ?> · <?= e($a['gps_address']) ?>
            <?php if ((int)$a['is_default']): ?><span class="text-xs px-1.5 py-0.5 ml-1" style="background:var(--secondary)">Default</span><?php endif; ?>
            <?php if (!(int)$a['deliverable']): ?><span class="text-xs px-1.5 py-0.5 ml-1" style="background:var(--muted);color:var(--accent)">We can't deliver here right now</span><?php endif; ?>
          </p>
          <p class="text-xs" style="color:var(--muted-foreground)"><?= e($a['area_name']) ?> (<?= e($a['zone_name']) ?>, delivery <?= money($a['fee']) ?>)<?= $a['landmark'] ? ' · Near ' . e($a['landmark']) : '' ?> · <?= e($a['phone']) ?></p>
          <?php if ($a['notes']): ?><p class="text-xs mt-0.5 italic" style="color:var(--muted-foreground)">"<?= e($a['notes']) ?>"</p><?php endif; ?>
        </div>
        <div class="flex gap-2 shrink-0">
          <?php if (!(int)$a['is_default']): ?>
          <form method="post" action="<?= $act ?>"><input type="hidden" name="act" value="default"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="next" value="<?= e($back) ?>">
            <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Make default</button></form>
          <?php endif; ?>
          <a href="?edit=<?= (int)$a['id'] ?><?= $nextQs ?>" class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border)">Edit</a>
          <form method="post" action="<?= $act ?>" onsubmit="return confirm('Delete this address?')"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="next" value="<?= e($back) ?>">
            <button class="text-xs font-medium px-3 py-2 border" style="border-color:var(--border);color:#b3261e">Delete</button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($showForm): ?>
  <form method="post" action="<?= $act ?>" class="grid sm:grid-cols-2 gap-4 p-6 border mb-8" style="border-color:var(--border)">
    <h2 class="font-display text-xl sm:col-span-2"><?= $edit ? 'Edit address' : 'Add an address' ?></h2>
    <input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>"><input type="hidden" name="next" value="<?= e($back) ?>">
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="gps">GhanaPostGPS address</label>
      <input id="gps" type="text" name="gps_address" required maxlength="15" placeholder="GA-123-4567" value="<?= e($f['gps_address']) ?>" class="<?= $box ?> uppercase" style="border-color:var(--border)" autocomplete="off">
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">The short code from the GhanaPostGPS app, like <strong>GA-123-4567</strong> (not your street name). Put the street and house number under landmark or notes.</p>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="area">Area</label>
      <select id="area" name="area_id" required class="<?= $box ?>" style="border-color:var(--border)">
        <option value="">Choose your area</option>
        <?php foreach ($areas as $zone => $list): ?>
          <optgroup label="<?= e($zone) ?> (delivery <?= e(money($list[0]['fee'])) ?>)">
            <?php foreach ($list as $ar): ?><option value="<?= (int)$ar['id'] ?>" <?= (int)$f['area_id'] === (int)$ar['id'] ? 'selected' : '' ?>><?= e($ar['name']) ?></option><?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
      <p class="text-xs mt-1" style="color:var(--muted-foreground)">Not listed? <a href="#waitlist" class="underline">Tell us your area</a>.</p>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="landmark">Nearest landmark</label>
      <input id="landmark" type="text" name="landmark" maxlength="150" placeholder="e.g. Opposite Shell, Oxford Street" value="<?= e($f['landmark']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="phone">Phone for delivery</label>
      <input id="phone" type="tel" name="phone" required maxlength="20" placeholder="0244 000 000" value="<?= e($f['phone'] ?: ($_SESSION['customer_contact'] ?? '')) ?>" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <div class="sm:col-span-2">
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="notes">Delivery notes (optional)</label>
      <textarea id="notes" name="notes" rows="2" maxlength="255" placeholder="Gate colour, which floor, call on arrival…" class="<?= $box ?>" style="border-color:var(--border)"><?= e($f['notes']) ?></textarea>
    </div>
    <div>
      <label class="<?= $lbl ?>" style="color:var(--muted-foreground)" for="label">Name this address (optional)</label>
      <input id="label" type="text" name="label" maxlength="40" placeholder="Home, Office…" value="<?= e($f['label']) ?>" class="<?= $box ?>" style="border-color:var(--border)">
    </div>
    <label class="flex items-center gap-2 text-sm self-end pb-3"><input type="checkbox" name="make_default" value="1" <?= !$addresses || (int)($edit['is_default'] ?? 0) ? 'checked' : '' ?>> Use as my default address</label>
    <div class="sm:col-span-2 flex gap-3">
      <button class="px-6 py-3 text-sm font-medium btn-primary"><?= $edit ? 'Save address' : 'Add address' ?></button>
      <?php if ($addresses): ?><a href="?<?= ltrim($nextQs, '&') ?>" class="px-6 py-3 text-sm font-medium border" style="border-color:var(--border)">Cancel</a><?php endif; ?>
    </div>
  </form>
  <?php else: ?>
    <a href="?new=1<?= $nextQs ?>" class="inline-block mb-8 px-5 py-3 text-sm font-medium btn-primary">+ Add an address</a>
  <?php endif; ?>

  <div id="waitlist" class="p-6" style="background:var(--muted)">
    <h2 class="font-display text-xl mb-1">Don't see your area?</h2>
    <p class="text-sm mb-4" style="color:var(--muted-foreground)">We don't deliver there yet. Tell us where you are and we'll email you when we do.</p>
    <form method="post" action="<?= $act ?>" class="flex flex-col sm:flex-row gap-3">
      <input type="hidden" name="act" value="waitlist"><input type="hidden" name="next" value="<?= e($back) ?>">
      <input type="text" name="area_name" required maxlength="100" placeholder="Your area, e.g. Aburi" class="<?= $box ?>" style="border-color:var(--border);background:var(--card)">
      <button class="px-6 py-3 text-sm font-medium btn-primary whitespace-nowrap">Notify me</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/layout/footer.php'; ?>
