<?php
// Paystack sends the customer back here after paying for an order.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

$psRef = trim($_GET['reference'] ?? $_GET['trxref'] ?? '');
$order = $psRef ? (new OrderClass())->byPaystackRef($psRef) : null;
if (!$order) redirect(url('index.php'));
require_login('views/orders.php');           // session expired while on Paystack: log in, My Orders will confirm the payment
if (!owns_order($order)) redirect(url('index.php'));

if (verify_order_payment($order) !== 'paid') {
    (new OrderClass())->markFailed($order['id']);
    redirect(url('views/payment_failed.php?ref=' . urlencode($order['ref'])));
}

cart()->clear();                       // they just paid in this browser
unset($_SESSION['cart_order_ref']);
// The invoice email is sent by views/invoice.php AFTER the invoice is on screen.
redirect(url('views/invoice.php?paid=1&ref=' . urlencode($order['ref'])));
