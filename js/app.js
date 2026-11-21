const drawer = document.getElementById('cart-drawer');
const backdrop = document.getElementById('cart-backdrop');
function openCart() { drawer.classList.add('open'); backdrop.classList.add('open'); }
function closeCart() { drawer.classList.remove('open'); backdrop.classList.remove('open'); }
document.querySelectorAll('[data-open-cart]').forEach(b => b.addEventListener('click', openCart));
document.querySelectorAll('[data-close-cart]').forEach(b => b.addEventListener('click', closeCart));
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
