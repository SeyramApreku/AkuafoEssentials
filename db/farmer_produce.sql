-- Farmers decide the product list: produce typed on a farmer creates draft products.
-- A draft (is_active = 0) is hidden from customers until it has a price, unit and category.
-- Safe to run more than once.
SET @has := (SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'aku_products' AND column_name = 'is_active');
SET @sql := IF(@has = 0, 'ALTER TABLE aku_products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1', 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
