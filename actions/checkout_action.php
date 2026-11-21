<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/checkout.php'));

$date = $_POST['date'] ?? '';
$window = $_POST['window'] ?? '';
$method = $_POST['method'] ?? '';
$phone = trim($_POST['phone'] ?? '');

$validDates = array_column(get_slots(), 'value');
$errors = [];
if (!in_array($date, $validDates)) $errors[] = 'Choose a delivery date.';
if (!in_array($window, time_windows())) $errors[] = 'Choose a time window.';
if (!in_array($method, ['mtn', 'vodafone', 'airteltigo', 'card'])) $errors[] = 'Choose a payment method.';
if ($method !== 'card' && !preg_match('/^[0-9 +]{9,15}$/', $phone)) $errors[] = 'Enter a valid mobile number.';

if ($errors) { $_SESSION['checkout_errors'] = $errors; redirect(url('views/checkout.php')); }

$ref = place_order($date, $window, $method, $method === 'card' ? null : $phone, !empty($_POST['add_pass']));
if (!$ref) { $_SESSION['checkout_errors'] = ['We could not place your order. Check the minimum order of ' . money(MIN_ORDER) . ' and try again.']; redirect(url('views/checkout.php')); }

redirect(url('views/order_success.php?ref=' . $ref));
