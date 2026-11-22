<?php
// Paystack sends the customer back here after a membership payment.
// The payment is verified on the server before the membership is switched on.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/membership_class.php';
require_once __DIR__ . '/../settings/paystack_config.php';

$reference = trim($_GET['reference'] ?? $_GET['trxref'] ?? '');
require_login();

$memberships = new MembershipClass();
$m = $reference !== '' ? $memberships->byRef($reference) : null;
if (!$m || (int)$m['user_id'] !== customer_id()) redirect(url('views/membership.php'));

if ($m['status'] === 'pending') {
    $res = paystack_request('GET', '/transaction/verify/' . rawurlencode($reference));
    $d = $res['data'] ?? [];
    $ok = !empty($res['status'])
        && ($d['status'] ?? '') === 'success'
        && ($d['currency'] ?? '') === 'GHS'
        && (int)($d['amount'] ?? 0) === (int)round($m['price'] * 100);

    if (!$ok) {
        $memberships->markFailed($reference);
        $_SESSION['membership_errors'] = ['Your payment was not completed, so your membership was not started. You have not been charged.'];
        redirect(url('views/membership_checkout.php?tier=' . urlencode($m['tier'])));
    }
    $memberships->activate($reference);
} elseif ($m['status'] !== 'active') {
    redirect(url('views/membership_checkout.php?tier=' . urlencode($m['tier'])));
}

$_SESSION['membership_done'] = ucfirst($m['tier']);
redirect(url('views/membership_checkout.php'));
