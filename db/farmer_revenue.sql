-- =====================================================================
-- Akuafo & Essentials: Farmer revenue, step 1 of the revised spec (database)
-- Runs AFTER farmer_payouts.sql (plans, rates, pickups, payouts). Adds what that file was missing:
--   which farmer each sale belongs to, the commission saved on each sale, box supply deliveries,
--   QC rejections, carry-forward of negative weeks, the MoMo reference, and farmer contact details.
-- Easiest: Admin > Menu > Database Update > Update database. Safe to run more than once.
-- Money is DECIMAL(10,2); rates are percentages DECIMAL(5,2) (15.00 = 15%).
-- =====================================================================

-- ---------- Getting in touch with farmers ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'phone') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN phone VARCHAR(20) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmers' AND column_name = 'email') = 0,
  'ALTER TABLE aku_farmers ADD COLUMN email VARCHAR(150) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------- Products know their farmer by id (the name stays for display) ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_products' AND column_name = 'farmer_id') = 0,
  'ALTER TABLE aku_products ADD COLUMN farmer_id INT NULL, ADD KEY idx_product_farmer (farmer_id)', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE aku_products p JOIN aku_farmers f ON LOWER(TRIM(f.name)) = LOWER(TRIM(p.farmer))
   SET p.farmer_id = f.id WHERE p.farmer_id IS NULL;

-- ---------- Each order line keeps its farmer, channel and the commission rate at the time of sale ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_order_items' AND column_name = 'farmer_id') = 0,
  'ALTER TABLE aku_order_items ADD COLUMN farmer_id INT NULL, ADD KEY idx_item_farmer (farmer_id)', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_order_items' AND column_name = 'source_channel') = 0,
  'ALTER TABLE aku_order_items ADD COLUMN source_channel VARCHAR(10) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_order_items' AND column_name = 'commission_rate') = 0,
  'ALTER TABLE aku_order_items ADD COLUMN commission_rate DECIMAL(5,2) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
-- Past Farm Shop sales: link them to the farmer and use the farmer's current rate (the best we know).
UPDATE aku_order_items i
  JOIN aku_products p ON i.item_key = CONCAT('p-', p.id)
  JOIN aku_farmers f ON f.id = p.farmer_id
   SET i.farmer_id = f.id, i.source_channel = 'shop', i.commission_rate = f.commission_rate
 WHERE i.type = 'produce' AND i.farmer_id IS NULL;

-- ---------- When an order was delivered (payouts count delivered orders in their week) ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'delivered_at') = 0,
  'ALTER TABLE aku_orders ADD COLUMN delivered_at DATETIME NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE aku_orders SET delivered_at = COALESCE(delivery_date, created_at) WHERE status = 'delivered' AND delivered_at IS NULL;

-- ---------- Box supply: what Akuafo bought from a farmer for boxes/hampers ----------
-- agreed_price = MAX(farmgate_price x (1 + premium_rate/100), min_price). Rates and prices are saved as they were that day.
CREATE TABLE IF NOT EXISTS aku_farmer_supplies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(120) NOT NULL,
  supply_date DATE NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'kg',
  farmgate_price DECIMAL(10,2) NOT NULL,
  premium_rate DECIMAL(5,2) NOT NULL,
  min_price DECIMAL(10,2) NULL,
  agreed_price DECIMAL(10,2) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,                    -- quantity x agreed_price: what the farmer earns
  payout_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_supply_farmer_date (farmer_id, supply_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Quality control: produce rejected, not paid for ----------
CREATE TABLE IF NOT EXISTS aku_farmer_qc_rejections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT NOT NULL,
  reject_date DATE NOT NULL,
  product_name VARCHAR(120) NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,                    -- value taken off the payout
  reason VARCHAR(255) NULL,
  payout_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_qc_farmer_date (farmer_id, reject_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Payouts: earnings split by channel, carry-forward, MoMo reference ----------
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmer_payouts' AND column_name = 'shop_earnings') = 0,
  'ALTER TABLE aku_farmer_payouts ADD COLUMN shop_earnings DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER commission', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmer_payouts' AND column_name = 'carried_in') = 0,
  'ALTER TABLE aku_farmer_payouts ADD COLUMN carried_in DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER plan_fee', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmer_payouts' AND column_name = 'carried_out') = 0,
  'ALTER TABLE aku_farmer_payouts ADD COLUMN carried_out DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER net_payout', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_farmer_payouts' AND column_name = 'momo_reference') = 0,
  'ALTER TABLE aku_farmer_payouts ADD COLUMN momo_reference VARCHAR(80) NULL AFTER paid_at', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------- Box margin: packaging + delivery cost per box (admin can change it) ----------
INSERT IGNORE INTO aku_settings (name, value) VALUES ('box_packaging_cost', '15.00');
