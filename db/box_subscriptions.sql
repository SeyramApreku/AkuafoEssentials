-- =====================================================================
-- Akuafo & Essentials: Weekly box subscriptions (duration, renewal reminders)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- One row per paid box. The customer pays upfront for the weeks they chose (4, 8 or 12);
-- a reminder email goes out 3 days before the last delivery, with a one-click renew.
CREATE TABLE IF NOT EXISTS aku_box_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  order_id INT NOT NULL,
  item_key VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  detail TEXT NULL,
  frequency VARCHAR(20) NOT NULL,
  weeks INT NOT NULL,
  deliveries INT NOT NULL,
  price_paid DECIMAL(10,2) NOT NULL,
  starts_on DATE NOT NULL,
  ends_on DATE NOT NULL,
  reminder_sent_at DATETIME NULL,
  renewed_by_order_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sub_order_item (order_id, item_key),
  KEY idx_sub_user (user_id),
  KEY idx_sub_ends (ends_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
