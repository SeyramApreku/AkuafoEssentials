<?php
// Run: php run_cart_tests.php
error_reporting(E_ALL);
const MIN_ORDER = 100;
const DELIVERY_FEE = 15;
require_once __DIR__ . '/../classes/cart_class.php';
$_SESSION = [];
$cart = new CartClass();
$passed = 0; $failed = 0;
function checkTest($name, $actual, $expected) {
    global $passed, $failed;
    if ($actual === $expected) { echo "PASS: $name\n"; $passed++; }
    else { echo "FAIL: $name (expected " . var_export($expected,true) . ", got " . var_export($actual,true) . ")\n"; $failed++; }
}
$cart->add(['id'=>'p-1','name'=>'Tomatoes','price'=>20,'qty'=>3]);
$cart->add(['id'=>'p-2','name'=>'Onions','price'=>10,'qty'=>2]);
checkTest('Subtotal of two products', $cart->subtotal(), 80);
checkTest('Total cart quantity', $cart->count(), 5);
$cart->changeQty('p-1', -10);
checkTest('Quantity cannot fall below one', $cart->items()['p-1']['qty'], 1);
$cart->setPass(true);
checkTest('Delivery Pass waives fee', $cart->deliveryCost(), 0);
$cart->setPass(false);
checkTest('No delivery fee below minimum order', $cart->deliveryCost(), 0);
$cart->add(['id'=>'p-3','name'=>'Yam','price'=>100,'qty'=>1]);
checkTest('Delivery fee applies at minimum order', $cart->deliveryCost(), DELIVERY_FEE);
$cart->remove('p-2');
checkTest('Remove selected product', isset($cart->items()['p-2']), false);
$cart->clear();
checkTest('Clear shopping cart', $cart->items(), []);
echo "\nResults: $passed passed, $failed failed\n";
exit($failed ? 1 : 0);
