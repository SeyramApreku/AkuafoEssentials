-- =====================================================================
-- Akuafo & Essentials: Exclusive recipes come from partner restaurants
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- Which partner restaurant/hotel created the recipe (chosen from Partners in Admin > Recipes).
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_recipes' AND column_name = 'partner') = 0,
  'ALTER TABLE aku_recipes ADD COLUMN partner VARCHAR(120) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
