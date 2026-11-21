<?php
require_once __DIR__ . '/../core/core.php';

$tier = null;
foreach (membership_tiers() as $t) if (strtolower($t['name']) === strtolower($_POST['tier'] ?? '')) $tier = $t;
if (!$tier) redirect(url('views/membership.php'));

$back = url('views/membership_checkout.php?tier=' . strtolower($tier['name']));
$method = $_POST['method'] ?? '';
if (!in_array($method, ['mtn', 'vodafone', 'airteltigo', 'card'], true)) redirect($back);

$phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
if ($method !== 'card' && !preg_match('/^0\d{9}$/', $phone)) {
    $_SESSION['membership_errors'] = ['Enter a valid 10-digit mobile number.'];
    redirect($back);
}

// PLACEHOLDER: no real payment happens here. Swap in Paystack before going live.
$db = new Database();
$db->execute("UPDATE memberships SET status = 'replaced' WHERE user_id = ? AND status = 'active'", [$_SESSION['customer_id']]);
$db->execute(
    'INSERT INTO memberships (user_id, tier, price, status, starts_at, expires_at) VALUES (?,?,?,?,NOW(),DATE_ADD(NOW(), INTERVAL 1 MONTH))',
    [$_SESSION['customer_id'], strtolower($tier['name']), $tier['price'], 'active']
);

$_SESSION['membership_done'] = $tier['name'];
redirect(url('views/membership_checkout.php'));