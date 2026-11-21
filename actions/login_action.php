<?php
require_once __DIR__ . '/../controllers/customer_controller.php';
header('Content-Type: application/json');

$email = trim($_POST['customer_email'] ?? '');
$pass  = $_POST['customer_pass'] ?? '';

if ($email === '' || $pass === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']); exit;
}

$result = (new CustomerController())->login($email, $pass);

if ($result['success']) {
    session_regenerate_id(true);
    $c = $result['customer'];
    $_SESSION['customer_id']    = $c['customer_id'];
    $_SESSION['customer_name']  = $c['customer_name'];
    $_SESSION['customer_email'] = $c['customer_email'];
    $_SESSION['user_role']      = $c['user_role'];
    echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => ((int)$c['user_role'] === 1) ? url('views/admin/manage.php') : url('index.php')]);
} else {
    echo json_encode(['success' => false, 'message' => $result['error']]);
}