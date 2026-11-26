<?php
// Cart drawer. Rendered by the header, and re-rendered on its own by actions/cart_action.php
// when the cart changes in the background (no page reload; see js/app.js).
// Delivery is not charged here: the fee depends on the address, so it is added at checkout.
$items = $cartObj->items(); $sub = $cartObj->subtotal();
$belowMin = $items && $sub < min_order();
$isEvent = $cartObj->kind() === 'entertaining';
$cartNotice = $_SESSION['cart_notice'] ?? null; unset($_SESSION['cart_notice']);
if (!function_exists('cart_btn')) {
    function cart_btn($act, $id, $label, $here, $cls = '') {
        return '<form method="post" action="' . url('actions/cart_action.php') . '" class="inline" data-cart-form>' . csrf_field()
             . '<input type="hidden" name="action" value="' . e($act) . '"><input type="hidden" name="id" value="' . e($id) . '">'
             . '<input type="hidden" name="return" value="' . e($here) . '"><button class="' . $cls . '">' . $label . '</button></form>';
    }
}
?>
<div id="cart-backdrop" data-close-cart class="fixed inset-0 z-40 bg-black/30"></div>
<aside id="cart-drawer" class="fixed top-0 right-0 h-full w-full max-w-sm z-50 flex flex-col shadow-2xl" style="background:var(--card);border-left:1px solid var(--border)" aria-label="Your order">
  <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--border)">
    <h2 class="font-display text-lg font-light">Your order</h2>
    <button data-close-cart class="text-xl leading-none" style="color:var(--muted-foreground)" aria-label="Close cart">×</button>
  </div>
  <div class="flex-1 overflow-y-auto px-5 py-4" aria-live="polite">
    <?php if ($cartNotice): ?>
      <div class="text-xs mb-4 p-3" style="background:var(--muted);color:var(--accent)">
        <?= e($cartNotice) ?>
        <div class="mt-2"><?= cart_btn('clear', '', 'Empty my cart', $here, 'underline font-medium') ?></div>
      </div>
    <?php endif; ?>
    <?php if ($isEvent): ?><p class="text-xs uppercase tracking-widest mb-3" style="color:var(--accent)">Entertaining order</p><?php endif; ?>
    <?php if (!$items): ?>
      <p class="text-sm text-center py-12" style="color:var(--muted-foreground)">Your order is empty.</p>
    <?php else: ?>
      <ul class="space-y-4">
        <?php foreach ($items as $i): ?>
        <li class="flex gap-3 items-start">
          <img src="<?= e($i['image']) ?>" alt="<?= e($i['name']) ?>" class="w-14 h-14 object-cover shrink-0">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium leading-tight"><?= e($i['name']) ?></p>
            <?php if (!empty($i['detail'])): ?><p class="text-xs mt-0.5" style="color:var(--muted-foreground)"><?= e($i['detail']) ?></p><?php endif; ?>
            <div class="flex items-center gap-2 mt-2">
              <div class="flex items-center border text-xs" style="border-color:var(--border)">
                <?= cart_btn('dec', $i['id'], '−', $here, 'px-2 py-1') ?>
                <span class="px-2 py-1 border-l border-r" style="border-color:var(--border)"><?= (int)$i['qty'] ?></span>
                <?= cart_btn('inc', $i['id'], '+', $here, 'px-2 py-1') ?>
              </div>
              <?= cart_btn('remove', $i['id'], 'Remove', $here, 'text-xs') ?>
            </div>
          </div>
          <p class="text-sm font-display shrink-0"><?= money($i['price'] * $i['qty']) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <?php if ($items): ?>
  <div class="px-5 py-4 border-t" style="border-color:var(--border)">
    <?php if ($belowMin): ?><p class="text-xs mb-3 p-2.5" style="background:var(--muted);color:var(--accent)">Minimum order is <?= money(min_order()) ?>. Add <?= money(min_order() - $sub) ?> more to unlock delivery.</p><?php endif; ?>
    <div class="space-y-1.5 mb-4 text-sm">
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Subtotal</span><span><?= money($sub) ?></span></div>
      <div class="flex justify-between"><span style="color:var(--muted-foreground)">Delivery</span><span style="color:var(--muted-foreground)">Calculated at checkout</span></div>
      <div class="flex justify-between font-medium border-t pt-2" style="border-color:var(--border)"><span>Total so far</span><span><?= money($sub) ?></span></div>
    </div>
    <?php if ($belowMin): ?><button disabled class="w-full py-3 text-sm font-medium btn-primary opacity-40">Book delivery &amp; pay</button>
    <?php else: ?><a href="<?= url('views/checkout.php') ?>" class="block text-center w-full py-3 text-sm font-medium btn-primary"><?= $isEvent ? 'Choose event date &amp; pay' : 'Book delivery &amp; pay' ?></a><?php endif; ?>
  </div>
  <?php endif; ?>
</aside>
<?php if (!empty($autoOpen)): ?><script>document.addEventListener('DOMContentLoaded', () => openCart());</script><?php endif; ?>
