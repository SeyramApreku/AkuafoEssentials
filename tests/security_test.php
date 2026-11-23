<?php
// Security helpers: CSRF tokens, safe redirects, HTML escaping, login lockout.
require __DIR__ . '/bootstrap.php';

section('CSRF tokens');
$_SESSION = [];
$t = csrf_token();
checkTest('Token is 64 hex characters', (bool)preg_match('/^[0-9a-f]{64}$/', $t), true);
checkTest('Token stays the same within a session', csrf_token(), $t);
$_POST = ['csrf' => $t];
checkTest('Correct token is accepted', csrf_valid(), true);
$_POST = ['csrf' => str_repeat('0', 64)];
checkTest('Wrong token is rejected', csrf_valid(), false);
$_POST = [];
checkTest('Missing token is rejected', csrf_valid(), false);
$_POST = ['csrf' => [$t]];
checkTest('Token sent as an array is rejected', csrf_valid(), false);
$_SESSION = []; $_POST = ['csrf' => $t];
checkTest('Token from an old session is rejected', csrf_valid(), false);
checkTest('csrf_field() writes a hidden input', strpos(csrf_field(), 'type="hidden" name="csrf"') !== false, true);

section('Safe redirects after login (no open redirects)');
checkTest('Checkout is allowed', safe_next('views/checkout.php'), 'views/checkout.php');
checkTest('Page with a query is allowed', safe_next('views/invoice.php?ref=AK12AB34'), 'views/invoice.php?ref=AK12AB34');
checkTest('Home page is allowed', safe_next('index.php'), 'index.php');
checkTest('Another website is refused', safe_next('https://evil.example/x'), '');
checkTest('Protocol-relative URL is refused', safe_next('//evil.example'), '');
checkTest('Path traversal is refused', safe_next('views/../settings/db_cred.php'), '');
checkTest('Actions are refused', safe_next('actions/logout_action.php'), '');
checkTest('Script in the query is refused', safe_next('views/shop.php?x=<script>'), '');

section('HTML escaping');
checkTest('Tags are escaped', e('<script>alert(1)</script>'), '&lt;script&gt;alert(1)&lt;/script&gt;');
checkTest('Quotes are escaped', e('"x" \'y\''), '&quot;x&quot; &#039;y&#039;');
checkTest('null becomes empty text', e(null), '');

section('Login lockout');
checkTest('No failures: not locked', login_locked(0, 0), false);
checkTest('4 failures for an email: not locked', login_locked(LOGIN_MAX_FAILS_EMAIL - 1, 0), false);
checkTest('5 failures for an email: locked', login_locked(LOGIN_MAX_FAILS_EMAIL, 0), true);
checkTest('19 failures from one IP: not locked', login_locked(0, LOGIN_MAX_FAILS_IP - 1), false);
checkTest('20 failures from one IP: locked', login_locked(0, LOGIN_MAX_FAILS_IP), true);
checkTest('Lock lasts 15 minutes', LOGIN_LOCK_MINUTES, 15);

section('Order ownership');
$_SESSION = ['customer_id' => 7];
checkTest('Own order is visible', owns_order(['user_id' => 7]), true);
checkTest("Someone else's order is hidden", owns_order(['user_id' => 8]), false);
checkTest('Guest order (no owner) is hidden', owns_order(['user_id' => null]), false);
$_SESSION = [];
checkTest('Logged-out visitor sees no orders', owns_order(['user_id' => 0]), false);

section('Email links point only to this site');
$_SERVER['HTTP_HOST'] = 'evil.example';
checkTest('Forged host falls back to localhost', strpos(site_url('views/shop.php'), 'http://localhost/') === 0, true);
$_SERVER['HTTP_HOST'] = '169.239.251.102:442';
checkTest('School server host is kept', strpos(site_url('views/shop.php'), 'http://169.239.251.102:442/') === 0, true);

finish();
