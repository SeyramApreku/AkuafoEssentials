<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/customer_class.php';

class CustomerController
{
    private $model;
    public function __construct() { $this->model = new CustomerClass(); }

    public function register($name, $email, $hash, $country, $city, $contact, $role)
    {
        if ($this->model->getByEmail($email)) return ['success' => false, 'error' => 'An account with this email already exists.'];
        $ok = $this->model->insert($name, $email, $hash, $country, $city, $contact, $role);
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Registration failed.'];
    }

    public function login($email, $pass)
    {
        $c = $this->model->getByEmail($email);
        if (!$c || !password_verify($pass, $c['customer_pass'])) return ['success' => false, 'error' => 'Invalid email or password.'];
        return ['success' => true, 'customer' => $c];
    }
}