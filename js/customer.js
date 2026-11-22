function showMessage(msg, ok) {
  const m = document.getElementById('formMessage');
  m.textContent = msg;
  m.style.color = ok ? 'var(--primary)' : '#b3261e';
}

function togglePassword() {
  const p = document.getElementById('customer_pass');
  p.type = p.type === 'password' ? 'text' : 'password';
}

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

    if (data.success) {
      onSuccess(data);
    }

  } catch (e) {
    console.error('Request failed:', e);
    showMessage('Could not communicate with the server. Please try again.', false);
  }
}

function loginCustomer() {
  const email = document.getElementById('customer_email').value.trim();
  const pass = document.getElementById('customer_pass').value;
  if (!email || !pass) return showMessage('Email and password are required.', false);
  submitForm('loginForm', '../actions/login_action.php', d => window.location.href = d.redirect);
}

function registerCustomer() {
  const ids = ['customer_name', 'customer_email', 'customer_pass', 'customer_country', 'customer_city', 'customer_contact'];
  if (ids.some(id => !document.getElementById(id).value.trim())) return showMessage('All fields are required.', false);
  if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(document.getElementById('customer_pass').value))
    return showMessage('Password must be 8+ characters with uppercase, lowercase, a number and a special character.', false);
  // keep ?next=... so the customer returns to checkout after logging in
  submitForm('registerForm', '../actions/register_action.php', () => setTimeout(() => window.location.href = 'login.php' + window.location.search, 1500));
}