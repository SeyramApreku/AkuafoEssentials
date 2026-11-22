-- =====================================================================
-- Akuafo & Essentials: Inventory (stock levels and stock history)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- Was stock tracking already set up? (decides whether the starting stock below is applied)
SET @new_stock = (SELECT COUNT(*) = 0 FROM information_schema.columns
                  WHERE table_schema = DATABASE() AND table_name = 'aku_products' AND column_name = 'stock_qty');

-- Products: how many are in stock (NULL = not tracked, always available) and when to warn
SET @q = IF(@new_stock, 'ALTER TABLE aku_products ADD COLUMN stock_qty INT NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_products' AND column_name = 'low_stock_at') = 0,
  'ALTER TABLE aku_products ADD COLUMN low_stock_at INT NOT NULL DEFAULT 5', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Starting stock of 50 for every product, only the first time (edit it in Admin > Inventory)
UPDATE aku_products SET stock_qty = 50 WHERE @new_stock = 1 AND stock_qty IS NULL;

-- Orders: when their stock was taken off (so it happens exactly once, and can be put back on cancel)
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'stock_deducted_at') = 0,
  'ALTER TABLE aku_orders ADD COLUMN stock_deducted_at DATETIME NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Every stock change, with the reason (sale, restock, adjustment, cancelled order)
CREATE TABLE IF NOT EXISTS aku_stock_moves (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  change_qty INT NOT NULL,              -- negative = stock went out
  reason VARCHAR(20) NOT NULL,          -- sale, restock, adjustment, cancelled
  note VARCHAR(150) NULL,
  order_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_move_product (product_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
