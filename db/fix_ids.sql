-- =====================================================================
-- Akuafo & Essentials: make product and farmer ids number themselves (AUTO_INCREMENT)
-- Without this, adding a NEW product or farmer in the admin fails.
-- Easiest: Admin > Menu > Database Update > Update database. Safe to run more than once.
-- =====================================================================

-- A failed earlier "Add" may have left a row stuck at id 0: give it a proper number first.
SET @n = (SELECT COALESCE(MAX(id), 0) + 1 FROM aku_farmers);
UPDATE aku_farmers SET id = @n WHERE id = 0;
SET @n = (SELECT COALESCE(MAX(id), 0) + 1 FROM aku_products);
UPDATE aku_products SET id = @n WHERE id = 0;

-- Turn on automatic numbering (only where it's still off).
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_products'
             AND column_name = 'id' AND extra LIKE '%auto_increment%') = 0,
  'ALTER TABLE aku_products MODIFY id INT NOT NULL AUTO_INCREMENT', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers'
             AND column_name = 'id' AND extra LIKE '%auto_increment%') = 0,
  'ALTER TABLE aku_farmers MODIFY id INT NOT NULL AUTO_INCREMENT', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
