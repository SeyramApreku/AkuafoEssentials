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

    /* ---------- Login lockout (table from db/login_lockout.sql; quietly off until it exists) ---------- */
    // One row per email + IP. Times use the database clock (NOW()), so PHP and MySQL time zones never clash.

    // Seconds left on a lock for this email + IP (0 = not locked). An expired lock is cleared, so counting starts again.
    public function lockSecondsLeft($email, $ip)
    {
        try {
            $r = $this->fetchOne('SELECT TIMESTAMPDIFF(SECOND, NOW(), locked_until) AS secs FROM aku_login_attempts WHERE email = ? AND ip_address = ? AND locked_until IS NOT NULL',
                                 [$this->lockEmail($email), $ip]);
            if (!$r) return 0;
            if ((int)$r['secs'] > 0) return (int)$r['secs'];
            $this->clearFailures($email, $ip);   // lock is over
            return 0;
        } catch (PDOException $ex) { return 0; }
    }

    // Counts one more failure (unknown emails too) and locks on the 3rd. Returns the new count (0 if the table is missing).
    public function recordFailure($email, $ip)
    {
        try {
            $this->execute('INSERT INTO aku_login_attempts (email, ip_address, attempt_count, last_attempt_at, locked_until)
                            VALUES (?, ?, 1, NOW(), IF(1 >= ?, NOW() + INTERVAL ? MINUTE, NULL))
                            ON DUPLICATE KEY UPDATE locked_until = IF(attempt_count + 1 >= ?, NOW() + INTERVAL ? MINUTE, NULL),
                              attempt_count = attempt_count + 1, last_attempt_at = NOW()',
                           [$this->lockEmail($email), $ip, LOGIN_MAX_FAILS, LOGIN_LOCK_MINUTES, LOGIN_MAX_FAILS, LOGIN_LOCK_MINUTES]);
            $r = $this->fetchOne('SELECT attempt_count FROM aku_login_attempts WHERE email = ? AND ip_address = ?', [$this->lockEmail($email), $ip]);
            return $r ? (int)$r['attempt_count'] : 0;
        } catch (PDOException $ex) { return 0; }
    }

    // After a successful login (or when a lock runs out): start counting from zero.
    public function clearFailures($email, $ip)
    {
        try { $this->execute('DELETE FROM aku_login_attempts WHERE email = ? AND ip_address = ?', [$this->lockEmail($email), $ip]); } catch (PDOException $ex) {}
    }

    private function lockEmail($email) { return mb_substr(mb_strtolower(trim((string)$email)), 0, 150); }
}
