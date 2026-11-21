<?php
require_once __DIR__ . '/db_cred.php';

class Database
{
    private static $pdo;

    protected function db()
    {
        if (!self::$pdo) {
            self::$pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
        return self::$pdo;
    }
    public function fetchAll($sql, $p = []) { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    public function fetchOne($sql, $p = []) { $s = $this->db()->prepare($sql); $s->execute($p); return $s->fetch() ?: null; }
    public function execute($sql, $p = []) { $s = $this->db()->prepare($sql); $s->execute($p); return $s->rowCount(); }
    public function lastId() { return $this->db()->lastInsertId(); }
    public function begin() { $this->db()->beginTransaction(); }
    public function commit() { $this->db()->commit(); }
    public function rollback() { $this->db()->rollBack(); }
}
