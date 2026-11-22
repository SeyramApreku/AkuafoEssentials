-- =====================================================================
-- Akuafo & Essentials: Entertaining section (E1)
-- In phpMyAdmin: FIRST click your database in the left list, THEN open the SQL tab,
-- paste this whole file and click Go. Safe to run more than once.
-- Prices, serves and notice periods below are placeholders: edit them in
-- Admin > Menu > Entertaining items. Images can be replaced there too.
-- =====================================================================

-- ---------- Tables ----------
CREATE TABLE IF NOT EXISTS aku_ent_occasions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  image VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aku_ent_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  image VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aku_ent_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  contents TEXT NULL,                           -- JSON list, e.g. what is inside a hamper
  price DECIMAL(10,2) NOT NULL,
  serves INT NULL,                              -- people one unit feeds; NULL = not portioned (e.g. bulk crates)
  notice_hours INT NOT NULL DEFAULT 48,         -- minimum notice before the event
  dietary SET('vegetarian','vegan','contains_nuts','spicy') NOT NULL DEFAULT '',
  badge VARCHAR(40) NULL,
  image VARCHAR(255) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cat (category_id),
  CONSTRAINT fk_ent_item_cat FOREIGN KEY (category_id) REFERENCES aku_ent_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aku_ent_item_occasions (
  item_id INT NOT NULL,
  occasion_id INT NOT NULL,
  PRIMARY KEY (item_id, occasion_id),
  CONSTRAINT fk_ent_io_item FOREIGN KEY (item_id) REFERENCES aku_ent_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_ent_io_occ  FOREIGN KEY (occasion_id) REFERENCES aku_ent_occasions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Capacity overrides and blocked-out dates (used from E4/E5)
CREATE TABLE IF NOT EXISTS aku_ent_date_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rule_date DATE NOT NULL,
  time_window VARCHAR(40) NOT NULL DEFAULT '',  -- '' = the whole day
  max_orders INT NULL,                          -- NULL = use the default capacity
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(120) NULL,
  UNIQUE KEY uq_rule (rule_date, time_window)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aku_settings (
  name VARCHAR(50) PRIMARY KEY,
  value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO aku_settings (name, value) VALUES ('ent_slot_capacity', '5'), ('ent_change_cutoff_hours', '48');

-- Orders: entertaining orders sit apart from grocery/box orders (used from E4)
-- (each column is only added if it is not there yet, so re-running is safe)
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'order_type') = 0,
  'ALTER TABLE aku_orders ADD COLUMN order_type VARCHAR(20) NOT NULL DEFAULT ''standard''', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'event_address') = 0,
  'ALTER TABLE aku_orders ADD COLUMN event_address VARCHAR(255) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'cancelled_at') = 0,
  'ALTER TABLE aku_orders ADD COLUMN cancelled_at DATETIME NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND column_name = 'refund_status') = 0,
  'ALTER TABLE aku_orders ADD COLUMN refund_status VARCHAR(20) NULL', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @q = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'aku_orders' AND index_name = 'idx_type_date') = 0,
  'ALTER TABLE aku_orders ADD INDEX idx_type_date (order_type, delivery_date)', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------- Occasions & categories ----------
INSERT IGNORE INTO aku_ent_occasions (id, name, image, sort_order) VALUES
 (1, 'Christmas',      'images/entertaining/placeholder.svg', 1),
 (2, 'Weddings',       'images/entertaining/placeholder.svg', 2),
 (3, 'Funerals',       'images/entertaining/placeholder.svg', 3),
 (4, 'Birthdays',      'images/entertaining/placeholder.svg', 4),
 (5, 'Outdoorings',    'images/entertaining/placeholder.svg', 5),
 (6, 'Office parties', 'images/entertaining/placeholder.svg', 6),
 (7, 'Gatherings',     'images/entertaining/placeholder.svg', 7);

INSERT IGNORE INTO aku_ent_categories (id, name, image, sort_order) VALUES
 (1, 'Platters',              'images/entertaining/placeholder.svg', 1),
 (2, 'Small chops',           'images/entertaining/placeholder.svg', 2),
 (3, 'Jollof & rice trays',   'images/entertaining/placeholder.svg', 3),
 (4, 'Grills',                'images/entertaining/placeholder.svg', 4),
 (5, 'Soups & stews',         'images/entertaining/placeholder.svg', 5),
 (6, 'Desserts & cakes',      'images/entertaining/placeholder.svg', 6),
 (7, 'Drinks',                'images/entertaining/placeholder.svg', 7),
 (8, 'Hampers',               'photo-1705088293220-c1a22b5d214a',    8),
 (9, 'Bulk produce',          'photo-1607305387299-a3d9611cd469',    9);

-- ---------- Items (placeholder prices: edit in the admin) ----------
INSERT IGNORE INTO aku_ent_items (id, category_id, name, description, contents, price, serves, notice_hours, dietary, badge, image, featured) VALUES
 (1,  1, 'Grand Party Platter', 'A generous sharing platter of grilled chicken wings, kebabs, spring rolls, plantain chips and dips, arranged ready to serve.', NULL, 450, 15, 48, 'spicy', 'Bestseller', 'images/entertaining/placeholder.svg', 1),
 (2,  1, 'Garden Crudites & Dips Platter', 'Crisp seasonal vegetables from our farmers with a groundnut dip and a pepper-and-tomato salsa.', NULL, 220, 10, 48, 'vegetarian,vegan,contains_nuts', NULL, 'images/entertaining/placeholder.svg', 0),
 (3,  2, 'Small Chops Box', 'The party classic: spring rolls, samosas, meat pies, puff-puff and peppered chicken. Packed in a presentation box.', NULL, 380, 20, 48, 'spicy', 'Party favourite', 'images/entertaining/placeholder.svg', 1),
 (4,  2, 'Kelewele & Groundnuts Tray', 'Spiced fried ripe plantain with roasted groundnuts, made with plantain from Ejisu.', NULL, 150, 10, 24, 'vegetarian,vegan,contains_nuts,spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (5,  2, 'Veggie Small Chops Box', 'Vegetable spring rolls, veggie samosas, puff-puff and yam balls.', NULL, 300, 20, 48, 'vegetarian', NULL, 'images/entertaining/placeholder.svg', 0),
 (6,  3, 'Party Jollof Tray with Chicken', 'Smoky party jollof cooked over firewood, with fried chicken, coleslaw and fried plantain.', NULL, 420, 15, 48, 'spicy', 'Bestseller', 'images/entertaining/placeholder.svg', 1),
 (7,  3, 'Waakye Tray', 'Waakye with shito, gari, spaghetti, boiled eggs, wele and stew.', NULL, 350, 15, 48, 'spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (8,  3, 'Vegetable Fried Rice Tray', 'Fried rice with garden vegetables and spring onions.', NULL, 320, 15, 48, 'vegetarian', NULL, 'images/entertaining/placeholder.svg', 0),
 (9,  4, 'Chichinga Grill Platter', 'Beef and chicken suya skewers with onions, tomatoes and suya spice.', NULL, 400, 15, 48, 'contains_nuts,spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (10, 4, 'Grilled Tilapia Tray', 'Whole grilled tilapia with banku or kenkey, pepper sauce and onions.', NULL, 480, 10, 48, 'spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (11, 5, 'Goat Light Soup Pot', 'Light soup with goat meat, garden eggs and tomatoes. Served with fufu or rice balls on request.', NULL, 380, 15, 72, 'spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (12, 5, 'Groundnut Soup Pot', 'Rich groundnut soup with chicken and smoked fish.', NULL, 380, 15, 72, 'contains_nuts,spicy', NULL, 'images/entertaining/placeholder.svg', 0),
 (13, 5, 'Palava Sauce with Yam & Plantain', 'Kontomire stew with egusi and smoked fish, with boiled yam and ripe plantain.', NULL, 300, 12, 48, '', NULL, 'images/entertaining/placeholder.svg', 0),
 (14, 6, 'Celebration Cake', 'A three-tier vanilla and coconut cake, decorated to your theme. Tell us your colours after ordering.', NULL, 650, 30, 120, 'vegetarian', 'Made to order', 'images/entertaining/placeholder.svg', 1),
 (15, 6, 'Bofrot & Coconut Treats Tray', 'Bofrot, coconut toffee and chin-chin.', NULL, 180, 20, 24, 'vegetarian', NULL, 'images/entertaining/placeholder.svg', 0),
 (16, 7, 'Sobolo (5 litres)', 'Chilled hibiscus drink with ginger and pineapple.', NULL, 120, 15, 24, 'vegetarian,vegan', NULL, 'images/entertaining/placeholder.svg', 0),
 (17, 7, 'Asaana (5 litres)', 'Caramelised corn drink, served chilled.', NULL, 120, 15, 24, 'vegetarian,vegan', NULL, 'images/entertaining/placeholder.svg', 0),
 -- Your existing hampers, moved from aku_hampers (same photos and contents)
 (18, 8, 'Christmas Harvest Hamper', 'A full festive box for the Ghanaian Christmas table. Everything you need from dawn kelewele to evening light soup.', '["White Yam (3kg)","Ripe Plantain (2 hands)","Cocoyam (2kg)","Kontomire (3 bunches)","Fresh Tomatoes (2kg)","Scotch Bonnets (500g)","Garden Eggs (1kg)","Dawadawa (400g)","Onions (1kg)","Spring Onions & Ginger"]', 320, 8, 168, 'vegetarian,vegan', 'Festive', 'photo-1705088293220-c1a22b5d214a', 1),
 (19, 8, 'Wedding & Events Pack', 'Bulk-packed produce for wedding receptions, outdoorings, and large family gatherings. Coordinated delivery to your venue.', '["White Yam (10kg)","Plantain (4 hands)","Cassava (5kg)","Fresh Tomatoes (5kg)","Peppers (1.5kg)","Garden Eggs (3kg)","Kontomire (6 bunches)","Onions (3kg)","Dawadawa (500g)","Palm Nuts (2kg)"]', 480, 30, 336, 'vegetarian,vegan', 'Bulk', 'photo-1649551895001-85125e4dd5bd', 0),
 (20, 8, 'Family Gathering Box', 'For Sunday gatherings, naming ceremonies, and homecomings. A curated selection sized for a big household table.', '["White Yam (2kg)","Ripe Plantain (1 hand)","Kontomire (2 bunches)","Garden Eggs (1kg)","Fresh Tomatoes (1.5kg)","Scotch Bonnets (300g)","Onions (500g)","Spring Onions","Dawadawa (200g)"]', 210, 10, 120, 'vegetarian,vegan', NULL, 'photo-1628690570318-9d1e75e4f590', 0),
 -- Your existing bulk produce, moved from aku_bulk_items (serves NULL = not in the guest calculator)
 (21, 9, 'Tomato Crate', '10kg, Techiman', NULL, 95,  NULL, 72, 'vegetarian,vegan', NULL, 'photo-1607305387299-a3d9611cd469', 0),
 (22, 9, 'Plantain Crate', '20 hands, Ejisu', NULL, 130, NULL, 72, 'vegetarian,vegan', NULL, 'photo-1636992226967-d5d83d9ccde2', 0),
 (23, 9, 'Yam Sack', '25kg, Techiman', NULL, 280, NULL, 72, 'vegetarian,vegan', NULL, 'photo-1567306226416-28f0efdc88ce', 0),
 (24, 9, 'Pepper Sack', '5kg, Bolgatanga', NULL, 75,  NULL, 72, 'vegetarian,vegan,spicy', NULL, 'photo-1583119022894-919a68a3d0e3', 0);

-- Which occasions each item appears under (1 Christmas, 2 Weddings, 3 Funerals, 4 Birthdays, 5 Outdoorings, 6 Office parties, 7 Gatherings)
INSERT IGNORE INTO aku_ent_item_occasions (item_id, occasion_id) VALUES
 (1,2),(1,4),(1,6),(1,7),
 (2,4),(2,6),(2,7),
 (3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),
 (4,4),(4,5),(4,7),
 (5,4),(5,6),
 (6,1),(6,2),(6,4),(6,5),(6,6),(6,7),
 (7,3),(7,5),(7,7),
 (8,1),(8,2),(8,6),
 (9,4),(9,6),(9,7),
 (10,1),(10,3),(10,7),
 (11,3),(11,5),(11,7),
 (12,1),(12,3),(12,7),
 (13,5),(13,7),
 (14,2),(14,4),
 (15,4),(15,5),(15,6),
 (16,1),(16,2),(16,3),(16,4),(16,5),(16,6),(16,7),
 (17,1),(17,2),(17,3),(17,4),(17,5),(17,6),(17,7),
 (18,1),
 (19,2),(19,3),(19,5),
 (20,5),(20,7),
 (21,2),(21,3),(21,7),(22,2),(22,3),(22,7),(23,2),(23,3),(23,7),(24,2),(24,3),(24,7);
