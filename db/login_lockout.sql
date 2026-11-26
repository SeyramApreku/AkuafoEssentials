-- =====================================================================
-- Akuafo & Essentials: Login lockout (3 failed tries = locked for 10 minutes)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- One row per email + IP address. Replaces the older one-row-per-failure table from security.sql
-- (it only held temporary counts). The old table is only dropped if it is still the old shape.
SET @old := (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'aku_login_attempts')
          - (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_login_attempts' AND column_name = 'attempt_count');
SET @sql := IF(@old > 0, 'DROP TABLE aku_login_attempts', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS aku_login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempt_count INT NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL,
  locked_until DATETIME NULL,
  UNIQUE KEY uq_email_ip (email, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
