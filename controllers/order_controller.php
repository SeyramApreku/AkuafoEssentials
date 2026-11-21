<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/order_class.php';
require_once __DIR__ . '/cart_controller.php';

function place_order($date, $window, $method, $phone, $addPass)
{
    $cart = cart();
    if (!$cart->items()) return false;
    if ($cart->subtotal() < MIN_ORDER) return false;
    if ($addPass) $cart->setPass(true);

    $passFee = ($addPass && empty($_SESSION['pass_paid'])) ? PASS_PRICE : 0;
    $data = ['subtotal' => $cart->subtotal(), 'delivery' => $cart->deliveryCost(), 'pass_fee' => $passFee,
             'total' => $cart->subtotal() + $cart->deliveryCost() + $passFee,
             'date' => $date, 'window' => $window, 'method' => $method, 'phone' => $phone];
    $ref = (new OrderClass())->create($data, $cart->items());
    if ($ref) { $cart->clear(); if ($passFee) $_SESSION['pass_paid'] = true; }
    return $ref;
}
