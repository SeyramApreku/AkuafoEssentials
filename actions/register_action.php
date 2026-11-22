<?php
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
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass)) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be 8+ characters with uppercase, lowercase, a number and a special character.'
    ]);
    exit;
}

$result = (new CustomerController())->register($name, $email, password_hash($pass, PASSWORD_DEFAULT), $country, $city, $contact, 2);

if ($result['success']) {

    require_once __DIR__ . '/../core/mailer.php';
    $sent = sendWelcomeEmail($email, $name);   // never throws; failures go to the error log

    echo json_encode([
        'success' => true,
        'message' => $sent
            ? 'Registration successful. We sent a welcome email to ' . $email . '.'
            : 'Registration successful.'
    ]);

} else {
    echo json_encode([
        'success' => false,
        'message' => $result['error']
    ]);
}