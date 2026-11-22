<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/order_class.php';
require_once __DIR__ . '/cart_controller.php';

// The order's email is always the account's signup email, read from the database.
function place_order($date, $window, $method, $phone, $zoneId)
{
    $cart = cart();
    if (!$cart->items() || $cart->subtotal() < MIN_ORDER) return false;
    $q = delivery_quote($zoneId);
    if (!$q) return false;
    $account = (new OrderClass())->customer(customer_id());
    if (!$account) return false;
    $email = $account['customer_email'];

    $data = ['subtotal' => $cart->subtotal(), 'delivery' => $q['pay'], 'pass_fee' => 0,
             'total' => $cart->subtotal() + $q['pay'],
             'date' => $date, 'window' => $window, 'method' => $method, 'phone' => $phone,
             'email' => $email, 'user_id' => $_SESSION['customer_id'], 'zone' => $q['zone'], 'credit' => $q['credit']];
    return (new OrderClass())->create($data, $cart->items());
}

// Empties the cart once the order it was checked out as has been paid.
// Called from every page that can learn about a payment (callback, invoice, My Orders).
// $justConfirmed: the payment was confirmed this very request, so clear even if no order is recorded
// for the cart (payments started before cart_order_ref existed).
function clear_cart_for_paid_order($ref, $justConfirmed = false)
{
    $cartRef = $_SESSION['cart_order_ref'] ?? '';
    if ($cartRef !== $ref && !($justConfirmed && $cartRef === '')) return;
    cart()->clear();
    unset($_SESSION['cart_order_ref']);
}

// Asks Paystack (server to server) whether the order's latest payment attempt succeeded.
// Returns 'paid', 'failed', or 'pending' when Paystack can't say yet (not paid / network error).
// Used by the Paystack callback AND by My Orders, so a customer who closed the Paystack tab
// before being sent back still gets their order marked as paid.
function verify_order_payment($order)
{
    require_once __DIR__ . '/../settings/paystack_config.php';
    if ($order['payment_status'] === 'paid') return 'paid';
    if (empty($order['paystack_reference'])) return 'pending';

    $res = paystack_request('GET', '/transaction/verify/' . rawurlencode($order['paystack_reference']));
    $d = $res['data'] ?? [];
    if (empty($res['status']) || !$d) return 'pending';

    $ok = ($d['status'] ?? '') === 'success'
        && (int)($d['amount'] ?? 0) === (int)round($order['total'] * 100)
        && ($d['currency'] ?? '') === 'GHS';
    $orders = new OrderClass();
    if ($ok) { $orders->markPaid($order['id']); return 'paid'; }
    if (($d['status'] ?? '') === 'success' || ($d['status'] ?? '') === 'failed') {   // wrong amount/currency, or declined
        $orders->markFailed($order['id']);
        return 'failed';
    }
    return 'pending';   // e.g. 'abandoned': the customer never finished paying
}