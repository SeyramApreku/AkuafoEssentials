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
checkTest('3 tries allowed', LOGIN_MAX_FAILS, 3);
checkTest('Lock lasts 10 minutes', LOGIN_LOCK_MINUTES, 10);
checkTest('1st failure: 2 attempts left', login_fail_message(1), 'Incorrect email or password. You have 2 attempts left.');
checkTest('2nd failure: 1 attempt left', login_fail_message(2), 'Incorrect email or password. You have 1 attempt left.');
checkTest('3rd failure: locked message', login_fail_message(3), "You've been locked out for 10 minutes due to too many failed login attempts. Please try again after 10 minutes.");
checkTest('Wait text rounds up (7 min)', login_wait_text(401), 'Try again in 7 minutes.');
checkTest('Under a minute says 1 minute', login_wait_text(20), 'Try again in 1 minute.');

section('Password rule (registration)');
checkTest('Hyphen counts as a symbol (Stepby-step123)', password_problem('Stepby-step123'), '');
checkTest('Underscore, dot, hash, space all count', [password_problem('Abc_defg1'), password_problem('Abc.defg1'), password_problem('Abc#defg1'), password_problem('Abc defg1')], ['', '', '', '']);
checkTest('The old special characters still work', password_problem('Passw0rd!'), '');
checkTest('Accented letters are allowed', password_problem('Ébène-2026x'), '');
checkTest('Too short', password_problem('Ab1-'), 'Your password needs at least 8 characters.');
checkTest('Too long (over 72)', password_problem(str_repeat('Ab1-', 19)), 'Your password can be at most 72 characters.');
checkTest('Says exactly what is missing (symbol)', password_problem('Password123'), 'Your password needs a symbol (for example - _ ! @ #).');
checkTest('Lists several missing parts', password_problem('password'), 'Your password needs an uppercase letter, a number and a symbol (for example - _ ! @ #).');
checkTest('Confirmation matches', password_problem('Stepby-step123', 'Stepby-step123'), '');
checkTest('Confirmation differs', password_problem('Stepby-step123', 'Stepby-step124'), 'The two passwords do not match.');
checkTest('Empty confirmation is a mismatch', password_problem('Stepby-step123', ''), 'The two passwords do not match.');

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
