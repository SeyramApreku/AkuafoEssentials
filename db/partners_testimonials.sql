-- =====================================================================
-- Akuafo & Essentials: Partners / Testimonials page (portrait photo, role, restaurant, quote)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
--
-- The partners table already has `image` = the partner's LOGO (used on the home page).
-- The portrait for the testimonials page goes in the new `photo` column (uploads/partners/).
-- =====================================================================

SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_partners' AND column_name = 'photo') = 0,
  'ALTER TABLE aku_partners ADD COLUMN photo VARCHAR(255) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_partners' AND column_name = 'role') = 0,
  'ALTER TABLE aku_partners ADD COLUMN role VARCHAR(80) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_partners' AND column_name = 'restaurant_name') = 0,
  'ALTER TABLE aku_partners ADD COLUMN restaurant_name VARCHAR(120) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_partners' AND column_name = 'quote') = 0,
  'ALTER TABLE aku_partners ADD COLUMN quote TEXT NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Move the three testimonials that used to be written into the code into the table
-- (only if no partner has a testimonial yet, so re-running never duplicates them).
SET @has_quotes = (SELECT COUNT(*) FROM aku_partners WHERE quote IS NOT NULL AND quote <> '');
INSERT INTO aku_partners (name, type, image, sort_order, role, restaurant_name, quote)
SELECT t.name, t.type, NULL, t.sort_order, t.role, t.restaurant_name, t.quote FROM (
            SELECT 'Kwame Mensah' AS name, 'Restaurant' AS type, 10 AS sort_order, 'Head Chef' AS role, 'Adom Kitchen' AS restaurant_name,
                   'Our kontomire arrives the morning it was picked, with the farm named on the crate. Guests taste the difference.' AS quote
  UNION ALL SELECT 'Efua Boateng', 'Restaurant', 11, 'Owner', 'Sankofa Table',
                   'Weekly delivery slots let me plan the menu around what is in season, not what the market happens to have.'
  UNION ALL SELECT 'Yaw Darko', 'Lodge', 12, 'Executive Chef', 'Volta Bay Lodge',
                   'Consistent quality for a full hotel kitchen was the hard part. Akuafo made it routine.'
) t WHERE @has_quotes = 0;
