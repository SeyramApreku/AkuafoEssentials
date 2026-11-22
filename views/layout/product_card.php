<?php
// One produce card with a quick "Add to cart" button over the image.
// Desktop: the button appears on hover (or keyboard focus). Phones: always visible (no hover).
// Expects: $p (product row). Used by views/shop.php and index.php.
$cardUrl = url('views/product.php?id=' . $p['id']);
$stock   = isset($p['stock_qty']) && $p['stock_qty'] !== null ? (int)$p['stock_qty'] : null;   // null = not tracked
$soldOut = $stock !== null && $stock <= 0;
$fewLeft = $stock !== null && !$soldOut && $stock <= (int)($p['low_stock_at'] ?? 5);
?>
<div class="group">
  <div class="relative mb-3">
    <a href="<?= $cardUrl ?>" class="block aspect-square overflow-hidden relative" style="background:var(--muted)">
      <img src="<?= img($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
      <?php if ($p['badge']): ?><span class="absolute top-2 left-2 text-xs px-2 py-0.5 font-medium" style="background:var(--accent);color:var(--accent-foreground)"><?= e($p['badge']) ?></span><?php endif; ?>
      <?php if ($soldOut): ?><span class="absolute inset-0 flex items-center justify-center text-sm font-medium" style="background:rgba(244,239,228,.7)">Sold out</span>
      <?php elseif ($fewLeft): ?><span class="absolute top-2 right-2 text-xs px-2 py-0.5" style="background:var(--card)">Only <?= $stock ?> left</span><?php endif; ?>
    </a>
    <form method="post" action="<?= url('actions/cart_action.php') ?>"
          class="absolute bottom-2 left-2 right-2 transition-opacity duration-200 md:opacity-0 md:group-hover:opacity-100 md:group-focus-within:opacity-100">
      <input type="hidden" name="action" value="add"><input type="hidden" name="type" value="produce">
      <input type="hidden" name="key" value="<?= (int)$p['id'] ?>"><input type="hidden" name="qty" value="1">
      <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
      <button class="w-full py-2.5 text-xs font-medium btn-primary flex items-center justify-center gap-1.5" aria-label="Add <?= e($p['name']) ?> to cart" <?= $soldOut ? 'disabled style="opacity:.4;cursor:not-allowed"' : '' ?>>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Add to cart
      </button>
    </form>
  </div>
  <a href="<?= $cardUrl ?>" class="block" tabindex="-1">
    <p class="text-xs mb-0.5" style="color:var(--muted-foreground)"><?= e($p['region']) ?> · <?= e($p['farmer']) ?></p>
    <p class="font-medium text-sm mb-0.5"><?= e($p['name']) ?></p>
    <?php if (!empty($p['twi'])): ?><p class="text-xs mb-1 italic" style="color:var(--muted-foreground)"><?= e($p['twi']) ?></p><?php endif; ?>
    <div class="flex items-center justify-between"><p class="text-sm font-display"><?= money($p['price']) ?></p><span class="text-xs" style="color:var(--muted-foreground)"><?= e($p['unit']) ?></span></div>
  </a>
</div>
