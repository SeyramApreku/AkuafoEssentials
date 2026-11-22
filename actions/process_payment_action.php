<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/order_class.php';
require_once __DIR__ . '/../settings/paystack_config.php';

require_login();
$orders = new OrderClass();
$order = $orders->byRef($_GET['ref'] ?? '');
if (!owns_order($order) || $order['payment_status'] === 'paid') redirect(url('index.php'));

$psRef = $order['ref'] . 'T' . time();   // new reference per attempt so retries work
$orders->setPaystackRef($order['ref'], $psRef);
$_SESSION['cart_order_ref'] = $order['ref'];   // the cart is emptied once THIS order is confirmed paid

$res = paystack_request('POST', '/transaction/initialize', [
    'email'        => $orders->customer($order['user_id'])['customer_email'] ?? $order['email'],   // signup email
    'amount'       => (int)round($order['total'] * 100),   // pesewas
    'currency'     => 'GHS',
    'reference'    => $psRef,
    'callback_url' => site_url('actions/paystack_callback.php'),   // must be a FULL url, or Paystack never redirects back
   // 'channels'     => ['card', 'mobile_money'],
    'metadata'     => ['order_ref' => $order['ref']],
]);

if (empty($res['status']) || empty($res['data']['authorization_url'])) {
    error_log('Paystack init failed: ' . json_encode($res));
    $_SESSION['checkout_errors'] = ['We could not start the payment. Please try again.'];
    redirect(url('views/checkout.php'));
}
redirect($res['data']['authorization_url']);