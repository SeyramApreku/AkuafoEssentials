// ---------- Cart drawer ----------
// Looked up each time: the drawer is replaced when the cart changes in the background.
function openCart() { document.getElementById('cart-drawer')?.classList.add('open'); document.getElementById('cart-backdrop')?.classList.add('open'); }
function closeCart() { document.getElementById('cart-drawer')?.classList.remove('open'); document.getElementById('cart-backdrop')?.classList.remove('open'); }
document.addEventListener('click', e => {
  if (e.target.closest('[data-open-cart]')) openCart();
  else if (e.target.closest('[data-close-cart]')) closeCart();
});

// Shows a short message at the top of the cart drawer (and opens it).
function cartMessage(text) {
  const list = document.querySelector('#cart-drawer .overflow-y-auto');
  if (!list) return alert(text);
  let box = document.getElementById('cart-message');
  if (!box) { box = document.createElement('p'); box.id = 'cart-message'; box.className = 'text-xs mb-4 p-3'; box.style.cssText = 'background:var(--muted);color:var(--accent)'; list.prepend(box); }
  box.textContent = text;
  openCart();
}

// Add to cart / + / − / Remove / Empty without reloading the page.
// Any form posting to actions/cart_action.php is sent in the background; the server replies with the
// re-drawn drawer and the item count. If anything goes wrong, the form is submitted normally instead.
document.addEventListener('submit', async e => {
  const form = e.target;
  if (e.defaultPrevented || !/actions\/cart_action\.php$/.test(form.getAttribute('action') || '') || (form.method || '').toLowerCase() !== 'post') return;
  e.preventDefault();
  const button = form.querySelector('button[type=submit], button:not([type])');
  if (button) { if (button.disabled) return; button.disabled = true; button.style.opacity = '.6'; }
  const body = new FormData(form);
  // Buttons inside a re-drawn drawer may not carry the security token: borrow it from the page.
  if (!body.get('csrf')) { const t = document.querySelector('input[name="csrf"]'); if (t) body.set('csrf', t.value); }
  let data;
  try {
    const res = await fetch(form.action, { method: 'POST', body, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
    data = await res.json();
  } catch (err) {
    form.submit();   // network or server problem: fall back to the normal post + redirect
    return;
  }
  try {
    if (!data.success) {   // refused (e.g. session expired): say so in the cart, don't reload
      cartMessage(data.message || 'We could not update your cart. Please refresh the page and try again.');
      return;
    }
    // Swap in the new drawer, already open so it doesn't flash closed.
    const tmp = document.createElement('div');
    tmp.innerHTML = data.html;
    ['cart-backdrop', 'cart-drawer'].forEach(id => {
      const fresh = tmp.querySelector('#' + id), old = document.getElementById(id);
      if (fresh && old) { fresh.classList.add('open'); old.replaceWith(fresh); }
    });
    const badge = document.getElementById('cart-count');
    if (badge) { badge.textContent = data.count; badge.style.display = data.count > 0 ? 'flex' : 'none'; }
    // Checkout shows totals from the cart: refresh just those, without reloading the page.
    if (/\/checkout\.php$/.test(location.pathname)) refreshCheckoutTotals();
  } finally {
    if (button && document.body.contains(button)) { button.disabled = false; button.style.opacity = ''; }
  }
});
// Fetches checkout again in the background and swaps in the new totals box, keeping the chosen address
// and everything typed. Only if checkout no longer applies (cart emptied, sent elsewhere) does the page change.
async function refreshCheckoutTotals() {
  try {
    const res = await fetch(location.href, { credentials: 'same-origin' });
    const page = new DOMParser().parseFromString(await res.text(), 'text/html');
    const fresh = page.getElementById('grand-total'), old = document.getElementById('grand-total');
    if (res.redirected || !fresh || !old) { location.href = res.url || location.href; return; }
    const freshBox = fresh.closest('.border'), oldBox = old.closest('.border');
    if (!freshBox || !oldBox) return;
    oldBox.replaceWith(document.importNode(freshBox, true));
    // Re-apply the chosen address's delivery fee with the new subtotal (same sums as checkout's own script).
    const src = Array.from(page.scripts).map(s => s.textContent).join('\n');
    const sub = parseFloat((src.match(/var sub = ([\d.]+)/) || [])[1]);
    const credit = parseFloat((src.match(/credit = ([\d.]+)/) || [])[1]) || 0;
    const pick = document.querySelector('input[name=address_id]:checked');
    if (isNaN(sub) || !pick || !pick.dataset.fee) return;
    const m = n => 'GH₵ ' + (n % 1 === 0 ? n : n.toFixed(2));
    const fee = parseFloat(pick.dataset.fee), use = Math.min(fee, credit);
    document.getElementById('del-fee').textContent = m(fee);
    const row = document.getElementById('credit-row');
    if (row) row.style.display = use > 0 ? 'flex' : 'none';
    const cr = document.getElementById('del-credit'); if (cr) cr.textContent = '- ' + m(use);
    document.getElementById('grand-total').textContent = m(sub + fee - use);
    // Checkout's own script still holds the old subtotal: keep it in step when the address is changed.
    document.querySelectorAll('input[name=address_id]').forEach(r => r.addEventListener('change', () => {
      const f = parseFloat(r.dataset.fee), u = Math.min(f, credit);
      document.getElementById('del-fee').textContent = m(f);
      if (row) row.style.display = u > 0 ? 'flex' : 'none';
      if (cr) cr.textContent = '- ' + m(u);
      document.getElementById('grand-total').textContent = m(sub + f - u);
    }));
  } catch (e) { /* totals stay as they were; they are recalculated on the server at payment anyway */ }
}
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
