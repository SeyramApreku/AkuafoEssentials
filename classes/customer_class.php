<?php
require_once __DIR__ . '/../settings/db_class.php';

class CustomerClass extends Database
{
    public function getByEmail($email)
    {
        return $this->fetchOne('SELECT * FROM customer WHERE customer_email = ?', [$email]);
    }

    public function insert($name, $email, $hash, $country, $city, $contact, $role)
    {
        return $this->execute(
            'INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role) VALUES (?,?,?,?,?,?,?)',
            [$name, $email, $hash, $country, $city, $contact, $role]
        ) > 0;
    }
}