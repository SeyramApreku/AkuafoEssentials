function showMessage(msg, ok) {
  const m = document.getElementById('formMessage');
  m.textContent = msg;
  m.style.color = ok ? 'var(--primary)' : '#b3261e';
}

// Shows/hides the password box (and the confirm box on the register page).
function togglePassword() {
  ['customer_pass', 'customer_pass_confirm'].forEach(id => {
    const p = document.getElementById(id);
    if (p) p.type = p.type === 'password' ? 'text' : 'password';
  });
}

// Same rule as password_problem() in core/core.php: 8-72 characters, an uppercase letter, a lowercase
// letter, a number and ANY symbol. Returns '' when fine, otherwise what's missing.
function passwordProblem(pass, confirm) {
  const len = [...pass].length;
  if (len < 8) return 'Your password needs at least 8 characters.';
  if (len > 72) return 'Your password can be at most 72 characters.';
  const missing = [];
  if (!/\p{Lu}/u.test(pass)) missing.push('an uppercase letter');
  if (!/\p{Ll}/u.test(pass)) missing.push('a lowercase letter');
  if (!/\p{N}/u.test(pass)) missing.push('a number');
  if (!/[^\p{L}\p{N}]/u.test(pass)) missing.push('a symbol (for example - _ ! @ #)');
  if (missing.length) {
    const last = missing.pop();
    return 'Your password needs ' + (missing.length ? missing.join(', ') + ' and ' : '') + last + '.';
  }
  if (confirm !== undefined && confirm !== pass) return 'The two passwords do not match.';
  return '';
}

// Live "passwords match" hint under the confirm box.
document.addEventListener('input', e => {
  if (e.target.id !== 'customer_pass' && e.target.id !== 'customer_pass_confirm') return;
  const hint = document.getElementById('pass-match'), confirm = document.getElementById('customer_pass_confirm');
  if (!hint || !confirm) return;
  if (!confirm.value) { hint.textContent = ''; return; }
  const same = confirm.value === document.getElementById('customer_pass').value;
  hint.textContent = same ? '✓ Passwords match' : 'Passwords don\'t match yet';
  hint.style.color = same ? 'var(--primary)' : '#b3261e';
});

// Login lockout: disable the button and count down the minutes; re-enable when the lock is over.
let loginLockTimer = null;
function loginLockCountdown(seconds) {
  const btn = document.getElementById('loginBtn'), out = document.getElementById('lockTimer');
  if (!btn || !out) return;
  const end = Date.now() + seconds * 1000;
  clearInterval(loginLockTimer);
  btn.disabled = true; btn.style.opacity = '.5'; btn.style.cursor = 'not-allowed';
  const tick = () => {
    const left = Math.ceil((end - Date.now()) / 1000);
    if (left <= 0) {
      clearInterval(loginLockTimer);
      btn.disabled = false; btn.style.opacity = ''; btn.style.cursor = '';
      out.textContent = '';
      showMessage('You can try logging in again now.', true);
      return;
    }
    const m = Math.ceil(left / 60);
    out.textContent = 'Try again in ' + m + ' minute' + (m === 1 ? '' : 's') + '.';
  };
  tick();
  loginLockTimer = setInterval(tick, 1000);
}
document.addEventListener('DOMContentLoaded', () => {
  const out = document.getElementById('lockTimer');
  if (out && Number(out.dataset.seconds) > 0) loginLockCountdown(Number(out.dataset.seconds));
});

async function submitForm(formId, action, onSuccess) {
  try {
    const res = await fetch(action, {
      method: 'POST',
      body: new FormData(document.getElementById(formId))
    });

    const raw = await res.text();

    let data;

    try {
      data = JSON.parse(raw);
    } catch (e) {
      console.error('Invalid JSON response:', e);
      showMessage('The server returned an invalid response. Check the console.', false);
      return;
    }

    showMessage(data.message, data.success);
    if (data.lock_seconds) loginLockCountdown(data.lock_seconds);

    if (data.success) {
      onSuccess(data);
    }

  } catch (e) {
    console.error('Request failed:', e);
    showMessage('Could not communicate with the server. Please try again.', false);
  }
}

function loginCustomer() {
  const btn = document.getElementById('loginBtn');
  if (btn && btn.disabled) return;   // locked out: the countdown re-enables it
  const email = document.getElementById('customer_email').value.trim();
  const pass = document.getElementById('customer_pass').value;
  if (!email || !pass) return showMessage('Email and password are required.', false);
  submitForm('loginForm', '../actions/login_action.php', d => window.location.href = d.redirect);
}

function registerCustomer() {
  const ids = ['customer_name', 'customer_email', 'customer_pass', 'customer_country', 'customer_city', 'customer_contact'];
  if (ids.some(id => !document.getElementById(id).value.trim())) return showMessage('All fields are required.', false);
  const problem = passwordProblem(document.getElementById('customer_pass').value, document.getElementById('customer_pass_confirm').value);
  if (problem) return showMessage(problem, false);
  // keep ?next=... so the customer returns to checkout after logging in
  submitForm('registerForm', '../actions/register_action.php', () => setTimeout(() => window.location.href = 'login.php' + window.location.search, 1500));
}