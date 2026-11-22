<?php
require_once __DIR__ . '/../settings/db_class.php';

class CustomerClass extends Database
{
    public function getByEmail($email)
    {
        return $this->fetchOne('SELECT * FROM aku_customer WHERE customer_email = ?', [$email]);
    }

    public function insert($name, $email, $hash, $country, $city, $contact, $role)
    {
        return $this->execute(
            'INSERT INTO aku_customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role) VALUES (?,?,?,?,?,?,?)',
            [$name, $email, $hash, $country, $city, $contact, $role]
        ) > 0;
    }

    /* ---------- Login lockout (table from db/security.sql; quietly off until it exists) ---------- */

    // Failed logins in the lock window: [for this email, from this IP].
    public function recentFailures($email, $ip, $minutes)
    {
        try {
            $e = $this->fetchOne('SELECT COUNT(*) AS n FROM aku_login_attempts WHERE email = ? AND created_at > NOW() - INTERVAL ? MINUTE', [strtolower($email), (int)$minutes]);
            $i = $this->fetchOne('SELECT COUNT(*) AS n FROM aku_login_attempts WHERE ip = ? AND created_at > NOW() - INTERVAL ? MINUTE', [$ip, (int)$minutes]);
            return [(int)$e['n'], (int)$i['n']];
        } catch (PDOException $ex) { return [0, 0]; }
    }

    public function recordFailure($email, $ip)
    {
        try {
            $this->execute('INSERT INTO aku_login_attempts (email, ip) VALUES (?, ?)', [mb_substr(strtolower($email), 0, 150), $ip]);
            $this->execute('DELETE FROM aku_login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY');   // keep the table small
        } catch (PDOException $ex) { /* table not created yet */ }
    }

    public function clearFailures($email)
    {
        try { $this->execute('DELETE FROM aku_login_attempts WHERE email = ?', [strtolower($email)]); } catch (PDOException $ex) {}
    }
}