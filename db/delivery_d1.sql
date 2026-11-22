-- =====================================================================
-- Akuafo & Essentials: Delivery system, step D1 (zones, areas, settings)
-- In phpMyAdmin: FIRST click your database in the left list, THEN open the SQL tab,
-- paste this whole file and click Go. Safe to run more than once.
-- Everything here is sample data you can edit in Admin > Delivery Zones.
-- =====================================================================

-- Zones: delivery days and an on/off switch (only added if missing)
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_delivery_zones' AND column_name = 'delivery_days') = 0,
  'ALTER TABLE aku_delivery_zones ADD COLUMN delivery_days VARCHAR(60) NOT NULL DEFAULT ''Mon,Tue,Wed,Thu,Fri,Sat''', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_delivery_zones' AND column_name = 'is_active') = 0,
  'ALTER TABLE aku_delivery_zones ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Sample delivery days for the outer zones (only if still on the default days)
UPDATE aku_delivery_zones SET delivery_days = 'Mon,Wed,Fri,Sat' WHERE name = 'Tema'  AND delivery_days = 'Mon,Tue,Wed,Thu,Fri,Sat';
UPDATE aku_delivery_zones SET delivery_days = 'Tue,Thu,Sat'     WHERE name = 'Kasoa' AND delivery_days = 'Mon,Tue,Wed,Thu,Fri,Sat';

-- Areas: each belongs to one zone (the zone sets the fee and the days)
CREATE TABLE IF NOT EXISTS aku_delivery_areas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  name VARCHAR(100) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_area_zone (zone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Accra areas, matched to your zones by name.
-- CONVERT(... USING utf8mb4) on both sides avoids "Illegal mix of collations" on MySQL 8.
-- INSERT IGNORE skips areas that already exist.
INSERT IGNORE INTO aku_delivery_areas (zone_id, name)
SELECT z.id, a.name FROM aku_delivery_zones z JOIN (
            SELECT 'Accra Central' AS zone, 'Osu' AS name
  UNION ALL SELECT 'Accra Central', 'Adabraka'
  UNION ALL SELECT 'Accra Central', 'Asylum Down'
  UNION ALL SELECT 'Accra Central', 'Ridge'
  UNION ALL SELECT 'Accra Central', 'Kokomlemle'
  UNION ALL SELECT 'Accra Central', 'Labone'
  UNION ALL SELECT 'Accra Central', 'Cantonments'
  UNION ALL SELECT 'Accra Central', 'Dansoman'
  UNION ALL SELECT 'Accra Central', 'Achimota'
  UNION ALL SELECT 'East Legon and Airport', 'East Legon'
  UNION ALL SELECT 'East Legon and Airport', 'Airport Residential'
  UNION ALL SELECT 'East Legon and Airport', 'Airport City'
  UNION ALL SELECT 'East Legon and Airport', 'Shiashie'
  UNION ALL SELECT 'East Legon and Airport', 'Dzorwulu'
  UNION ALL SELECT 'East Legon and Airport', 'Roman Ridge'
  UNION ALL SELECT 'East Legon and Airport', 'Trasacco'
  UNION ALL SELECT 'Madina and Adenta', 'Madina'
  UNION ALL SELECT 'Madina and Adenta', 'Adenta'
  UNION ALL SELECT 'Madina and Adenta', 'Haatso'
  UNION ALL SELECT 'Madina and Adenta', 'Dome'
  UNION ALL SELECT 'Madina and Adenta', 'Kwabenya'
  UNION ALL SELECT 'Madina and Adenta', 'Ashongman'
  UNION ALL SELECT 'Tema', 'Tema Community 1'
  UNION ALL SELECT 'Tema', 'Tema Community 25'
  UNION ALL SELECT 'Tema', 'Sakumono'
  UNION ALL SELECT 'Tema', 'Spintex'
  UNION ALL SELECT 'Tema', 'Nungua'
  UNION ALL SELECT 'Kasoa', 'Kasoa'
  UNION ALL SELECT 'Kasoa', 'Weija'
  UNION ALL SELECT 'Kasoa', 'McCarthy Hill'
) a ON CONVERT(a.zone USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(z.name USING utf8mb4) COLLATE utf8mb4_unicode_ci;

-- Delivery settings (aku_settings normally comes from db/entertaining.sql; created here if missing)
CREATE TABLE IF NOT EXISTS aku_settings (
  name VARCHAR(50) PRIMARY KEY,
  value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO aku_settings (name, value) VALUES
  ('min_order', '80'),
  ('free_delivery_threshold', '500'),
  ('order_cutoff_time', '20:00');
