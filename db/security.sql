-- =====================================================================
-- Akuafo & Essentials: Security (login lockout after repeated failures)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- One row per failed login. 5 failures in 15 minutes for the same email OR the same
-- IP address locks further attempts for 15 minutes. Old rows are cleaned up automatically.
CREATE TABLE IF NOT EXISTS aku_login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempt_email (email, created_at),
  KEY idx_attempt_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
