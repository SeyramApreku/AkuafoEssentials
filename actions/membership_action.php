<?php
// Starts a membership payment: creates a pending membership, then sends the customer to Paystack.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/membership_class.php';
require_once __DIR__ . '/../settings/paystack_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/membership.php'));

$tier = null;
foreach (membership_tiers() as $t) {
    if (strtolower($t['name']) === strtolower($_POST['tier'] ?? '')) $tier = $t;
}
if (!$tier) redirect(url('views/membership.php'));

$key  = strtolower($tier['name']);
$back = url('views/membership_checkout.php?tier=' . $key);
require_login('views/membership_checkout.php?tier=' . $key);

$memberships = new MembershipClass();
if ($active = $memberships->activeFor(customer_id())) {
    $_SESSION['membership_errors'] = ['You already have an active ' . ucfirst($active['tier']) . ' membership until '
        . date('j M Y', strtotime($active['expires_at'])) . '. You can choose a new plan after it ends.'];
    redirect($back);
}

$customer = (new Database())->fetchOne('SELECT customer_email FROM aku_customer WHERE customer_id = ?', [customer_id()]);
if (!$customer) redirect(url('actions/logout_action.php'));

$reference = 'MEM' . strtoupper(bin2hex(random_bytes(5)));   // 13 chars, fits order_ref
$memberships->createPending(customer_id(), $key, $tier['price'], $reference);

$res = paystack_request('POST', '/transaction/initialize', [
    'email'        => $customer['customer_email'],
    'amount'       => (int)round($tier['price'] * 100),   // pesewas
    'currency'     => 'GHS',
    'reference'    => $reference,
    'callback_url' => site_url('actions/membership_callback.php'),
    'metadata'     => ['type' => 'membership', 'tier' => $key, 'customer_id' => customer_id()],
]);

if (empty($res['status']) || empty($res['data']['authorization_url'])) {
    error_log('Paystack membership init failed: ' . json_encode($res));
    $memberships->markFailed($reference);
    $_SESSION['membership_errors'] = ['We could not start the payment. Please try again.'];
    redirect($back);
}
redirect($res['data']['authorization_url']);
