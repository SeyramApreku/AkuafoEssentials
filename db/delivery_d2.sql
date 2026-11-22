-- =====================================================================
-- Akuafo & Essentials: Delivery system, step D2 (saved addresses, waitlist)
-- In phpMyAdmin: FIRST click your database in the left list, THEN open the SQL tab,
-- paste this whole file and click Go. Safe to run more than once.
-- Run db/delivery_d1.sql before this file.
-- =====================================================================

-- Customers' saved delivery addresses
CREATE TABLE IF NOT EXISTS aku_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  label VARCHAR(40) NULL,                -- e.g. Home, Office
  gps_address VARCHAR(15) NOT NULL,      -- GhanaPostGPS, e.g. GA-123-4567
  area_id INT NOT NULL,
  landmark VARCHAR(150) NULL,
  notes VARCHAR(255) NULL,               -- delivery notes for the rider
  phone VARCHAR(20) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_addr_customer (customer_id),
  KEY idx_addr_area (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- "We don't deliver here yet" signups
CREATE TABLE IF NOT EXISTS aku_delivery_waitlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  email VARCHAR(150) NOT NULL,
  area_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wait (email, area_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Each order keeps a copy of where it goes (so editing an address later doesn't change past orders)
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'zone_id') = 0,
  'ALTER TABLE aku_orders ADD COLUMN zone_id INT NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'address_id') = 0,
  'ALTER TABLE aku_orders ADD COLUMN address_id INT NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'gps_address') = 0,
  'ALTER TABLE aku_orders ADD COLUMN gps_address VARCHAR(15) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'area_name') = 0,
  'ALTER TABLE aku_orders ADD COLUMN area_name VARCHAR(100) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'landmark') = 0,
  'ALTER TABLE aku_orders ADD COLUMN landmark VARCHAR(150) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'delivery_notes') = 0,
  'ALTER TABLE aku_orders ADD COLUMN delivery_notes VARCHAR(255) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
