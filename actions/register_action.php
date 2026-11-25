<?php
ob_start();   // lets finish_response() send the reply before the (slow) welcome email goes out
require_once __DIR__ . '/../controllers/customer_controller.php';
header('Content-Type: application/json');

$name    = trim($_POST['customer_name'] ?? '');
$email   = trim($_POST['customer_email'] ?? '');
$pass    = $_POST['customer_pass'] ?? '';
$country = trim($_POST['customer_country'] ?? '');
$city    = trim($_POST['customer_city'] ?? '');
$contact = trim($_POST['customer_contact'] ?? '');

if ($name === '' || $email === '' || $pass === '' || $country === '' || $city === '' || $contact === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid email address.']); exit;
}
// Any symbol is allowed; the two password boxes must match (rule lives in core/core.php).
if ($problem = password_problem($pass, $_POST['customer_pass_confirm'] ?? '')) {
    echo json_encode(['success' => false, 'message' => $problem]); exit;
}

$result = (new CustomerController())->register($name, $email, password_hash($pass, PASSWORD_DEFAULT), $country, $city, $contact, 2);

if ($result['success']) {
    // Reply straight away; sending through Gmail can take several seconds, so it happens afterwards.
    echo json_encode(['success' => true, 'message' => 'Registration successful. A welcome email is on its way to ' . $email . '.']);
    finish_response();
    require_once __DIR__ . '/../core/mailer.php';
    sendWelcomeEmail($email, $name);   // never throws; failures go to the error log
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['error']
    ]);
}