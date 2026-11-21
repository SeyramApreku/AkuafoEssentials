<?php
require_once __DIR__ . '/../settings/db_class.php';

class UserClass extends Database
{
    public function findById($id)
    {
        return $this->fetchOne('SELECT id, name, email, phone FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail($email)
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    }

    public function create($name, $email, $phone, $password)
    {
        $this->execute(
            'INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)',
            [$name, strtolower($email), $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]
        );
        return (int)$this->lastId();
    }

    // Attach an order to the logged-in account and store the contact email.
    public function linkOrder($ref, $userId, $email)
    {
        return $this->execute('UPDATE orders SET user_id = ?, email = ? WHERE ref = ?', [$userId, $email, $ref]);
    }
}