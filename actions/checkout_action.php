<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

require_login('views/checkout.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/checkout.php'));

$date   = $_POST['date'] ?? '';
$window = $_POST['window'] ?? '';
$phone  = trim($_POST['phone'] ?? '');
$zone   = (int)($_POST['zone'] ?? 0);

$validDates = array_column(get_slots(), 'value');
$errors = [];
if (!in_array($date, $validDates)) $errors[] = 'Choose a delivery date.';
if (!in_array($window, time_windows())) $errors[] = 'Choose a time window.';
if (!delivery_quote($zone)) $errors[] = 'Choose your delivery area.';
if (!preg_match('/^[0-9 +]{9,15}$/', $phone)) $errors[] = 'Enter a valid mobile number.';

if ($errors) { $_SESSION['checkout_errors'] = $errors; redirect(url('views/checkout.php')); }

$ref = place_order($date, $window, 'paystack', $phone, $zone);   // email comes from the account, not the form
if (!$ref) { $_SESSION['checkout_errors'] = ['We could not place your order. Check the minimum order of ' . money(MIN_ORDER) . ' and try again.']; redirect(url('views/checkout.php')); }

redirect(url('views/order_summary.php?ref=' . $ref));