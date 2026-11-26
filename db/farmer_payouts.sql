-- =====================================================================
-- Akuafo & Essentials: Farmer revenue model, step 1 (database)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
--
-- Two ways farmers earn:
--   box  = Akuafo buys their produce for weekly boxes/hampers at farmgate price + a premium (box_premium_rate %)
--   shop = the farmer lists produce in the Farm Shop at their own price; Akuafo keeps a commission per sale
-- Money is DECIMAL(10,2); rates are percentages, DECIMAL(5,2) (e.g. 12.50 = 12.5%).
-- =====================================================================

-- ---------- Farmer plans (Farm Shop only). No joining fee. ----------
CREATE TABLE IF NOT EXISTS aku_farmer_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(40) NOT NULL,
  monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  commission_rate DECIMAL(5,2) NOT NULL,
  featured_listings TINYINT(1) NOT NULL DEFAULT 0,
  homepage_feature TINYINT(1) NOT NULL DEFAULT 0,
  box_priority TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_plan_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO aku_farmer_plans (code, name, monthly_fee, commission_rate, featured_listings, homepage_feature, box_priority, sort_order) VALUES
  ('basic',   'Basic',          0.00, 15.00, 0, 0, 0, 1),
  ('grower',  'Grower',       100.00, 10.00, 1, 0, 0, 2),
  ('partner', 'Partner Farm', 250.00,  7.00, 1, 1, 1, 3);

-- ---------- Farmers: plan, rates, payout number, channel ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'plan') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN plan VARCHAR(20) NOT NULL DEFAULT ''basic''', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'commission_rate') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN commission_rate DECIMAL(5,2) NOT NULL DEFAULT 15.00', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'box_premium_rate') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN box_premium_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'payout_momo_number') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN payout_momo_number VARCHAR(20) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'supply_channel') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN supply_channel ENUM(''box'',''shop'',''both'') NOT NULL DEFAULT ''both''', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------- Box suppliers: seasonal minimum price per product ----------
CREATE TABLE IF NOT EXISTS aku_farmer_min_prices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  product_id INT NOT NULL,
  season VARCHAR(40) NOT NULL,                      -- e.g. "Major season (Apr-Jul)"
  min_price DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_farmer_product_season (farmer_id, product_id, season),
  KEY idx_min_farmer (farmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Farm pickups: fee per trip, deducted from the payout (hub drop-off is free, so not logged) ----------
CREATE TABLE IF NOT EXISTS aku_farmer_pickups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  pickup_date DATE NOT NULL,
  fee DECIMAL(10,2) NOT NULL,
  payout_id INT NULL,                               -- the payout it was deducted in (so it is never deducted twice)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pickup_farmer_date (farmer_id, pickup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Weekly payouts: Saturday to Friday, paid Fridays by mobile money ----------
-- net_payout = gross_sales - commission + box_supply_value - pickup_fees - qc_deductions - plan_fee
CREATE TABLE IF NOT EXISTS aku_farmer_payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  period_start DATE NOT NULL,                       -- a Saturday
  period_end DATE NOT NULL,                         -- the Friday after
  gross_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,  -- Farm Shop sales (delivered orders only)
  commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  box_supply_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  pickup_fees DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  qc_deductions DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  plan_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,     -- charged on the first payout of each month
  net_payout DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_farmer_period (farmer_id, period_start),   -- generating a week twice never doubles it
  KEY idx_payout_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Admin-set farm pickup fee (GHS 30-50 per trip) ----------
CREATE TABLE IF NOT EXISTS aku_settings (
  name VARCHAR(60) PRIMARY KEY,
  value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO aku_settings (name, value) VALUES ('farm_pickup_fee', '40.00');
