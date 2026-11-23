// ---------- Cart drawer ----------
// Looked up each time: the drawer is replaced when the cart changes in the background.
function openCart() { document.getElementById('cart-drawer')?.classList.add('open'); document.getElementById('cart-backdrop')?.classList.add('open'); }
function closeCart() { document.getElementById('cart-drawer')?.classList.remove('open'); document.getElementById('cart-backdrop')?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.closest('[data-open-cart]')) openCart();
  else if (e.target.closest('[data-close-cart]')) closeCart();
});

// Add to cart / + / − / Remove / Empty without reloading the page.
// Any form posting to actions/cart_action.php is sent in the background; the server replies with the
// re-drawn drawer and the item count. If anything goes wrong, the form is submitted normally instead.
document.addEventListener('submit', async e => {
  const form = e.target;
  if (e.defaultPrevented || !/actions\/cart_action\.php$/.test(form.getAttribute('action') || '') || (form.method || '').toLowerCase() !== 'post') return;
  e.preventDefault();
  const button = form.querySelector('button[type=submit], button:not([type])');
  if (button) { if (button.disabled) return; button.disabled = true; button.style.opacity = '.6'; }
  try {
    const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Cart update failed');
    // Swap in the new drawer, already open so it doesn't flash closed.
    const tmp = document.createElement('div');
    tmp.innerHTML = data.html;
    ['cart-backdrop', 'cart-drawer'].forEach(id => {
      const fresh = tmp.querySelector('#' + id), old = document.getElementById(id);
      if (fresh && old) { fresh.classList.add('open'); old.replaceWith(fresh); }
    });
    const badge = document.getElementById('cart-count');
    if (badge) { badge.textContent = data.count; badge.style.display = data.count > 0 ? 'flex' : 'none'; }
    // Checkout shows totals from the cart, so refresh it there once the cart has been updated.
    if (/\/checkout\.php$/.test(location.pathname)) location.reload();
  } catch (err) {
    form.submit();   // fall back to the normal post + redirect
  } finally {
    if (button && document.body.contains(button)) { button.disabled = false; button.style.opacity = ''; }
  }
});
document.getElementById('menu-btn')?.addEventListener('click', () => document.getElementById('mobile-menu').classList.toggle('hidden'));
document.querySelectorAll('[data-toggle]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.toggle).classList.toggle('hidden')));

// Box size picker: updates the price shown and the hidden size field
document.querySelectorAll('[data-box]').forEach(card => {
  const sizeInput = card.querySelector('input[name=size]');
  const priceEl = card.querySelector('[data-price]');
  const metaEl = card.querySelector('[data-meta]');
  card.querySelectorAll('[data-size]').forEach(btn => btn.addEventListener('click', () => {
    card.querySelectorAll('[data-size]').forEach(b => b.classList.remove('pill-on'));
    btn.classList.add('pill-on');
    sizeInput.value = btn.dataset.size;
    priceEl.textContent = 'GH₵ ' + btn.dataset.sizePrice;
metaEl.textContent = '/week · ' + btn.dataset.sizeItems + ' · Free delivery';
  }));
});

// Quantity stepper on product page
const qty = document.getElementById('qty');
document.querySelectorAll('[data-qty]').forEach(b => b.addEventListener('click', () => {
  qty.value = Math.max(1, parseInt(qty.value || 1) + parseInt(b.dataset.qty));
  document.getElementById('qty-label').textContent = qty.value;
}));

// Checkout: payment fields + live total
document.querySelectorAll('input[name=method]').forEach(r => r.addEventListener('change', () => {
  const card = r.value === 'card' && r.checked;
  document.getElementById('mobile-fields')?.classList.toggle('hidden', card);
  document.getElementById('card-fields')?.classList.toggle('hidden', !card);
}));
const passBox = document.getElementById('add-pass');
if (passBox) passBox.addEventListener('change', () => {
  const t = document.getElementById('grand-total');
  t.textContent = 'GH₵ ' + (parseFloat(t.dataset.base) + (passBox.checked ? parseFloat(t.dataset.pass) : 0));
  document.getElementById('pass-row').classList.toggle('hidden', !passBox.checked);
});
