<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/order_controller.php';

require_login('views/checkout.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('views/checkout.php'));

$date   = $_POST['date'] ?? '';
$window = $_POST['window'] ?? '';

// The saved address (must be this customer's, in an active area and zone) decides zone, fee and phone.
require_once __DIR__ . '/../classes/delivery_class.php';
$addr  = (new DeliveryClass())->address((int)($_POST['address_id'] ?? 0), customer_id());
$zone  = ($addr && (int)$addr['deliverable']) ? (int)$addr['zone_id'] : 0;
$phone = $addr['phone'] ?? '';
$_SESSION['checkout_address'] = (int)($addr['id'] ?? 0);   // keep the choice if we bounce back with errors

$isEvent = cart()->kind() === 'entertaining';
$address = mb_substr(trim($_POST['event_address'] ?? ''), 0, 255);
$errors = [];
if ($isEvent) {
    // Entertaining: any date that respects notice, blocked dates and capacity (checked on the server).
    require_once __DIR__ . '/../controllers/entertaining_controller.php';
    [$ok, $why] = ent_slot_check($date, $window, ent_cart_notice_hours());
    if (!$ok) $errors[] = $why;
    $_SESSION['checkout_old'] = ['date' => $date, 'window' => $window, 'event_address' => $address];
} else {
    $validDates = array_column(get_slots(), 'value');
    if (!in_array($date, $validDates)) $errors[] = 'Choose a delivery date.';
    if (!in_array($window, time_windows())) $errors[] = 'Choose a time window.';
}
foreach (cart_stock_problems() as $p) $errors[] = $p;   // someone else may have bought the last ones
if (!$addr) $errors[] = 'Choose a delivery address.';
elseif (!$zone || !delivery_quote($zone)) $errors[] = "We don't deliver to " . $addr['area_name'] . ' at the moment. Please choose another address.';

if ($errors) { $_SESSION['checkout_errors'] = $errors; redirect(url('views/checkout.php')); }
unset($_SESSION['checkout_old'], $_SESSION['checkout_address']);

$ref = place_order($date, $window, 'paystack', $phone, $zone,   // email comes from the account, not the form
                   ['order_type' => $isEvent ? 'entertaining' : 'standard', 'event_address' => $isEvent && $address !== '' ? $address : null,
                    // a copy of the address, so later edits to the saved address don't change this order
                    'zone_id' => $zone, 'address_id' => (int)$addr['id'], 'gps_address' => $addr['gps_address'], 'area_name' => $addr['area_name'],
                    'landmark' => $addr['landmark'], 'delivery_notes' => $addr['notes']]);
if (!$ref) { $_SESSION['checkout_errors'] = ['We could not place your order. Check the minimum order of ' . money(min_order()) . ' and try again.']; redirect(url('views/checkout.php')); }

redirect(url('views/order_summary.php?ref=' . $ref));