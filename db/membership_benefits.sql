-- =====================================================================
-- Akuafo & Essentials: Membership benefits (restaurant vouchers, members-only recipes)
-- Easiest: Admin > Menu > Database Update > Update database.
-- Or in phpMyAdmin: click your database first, then SQL tab, paste, Go. Safe to run more than once.
-- =====================================================================

-- One voucher per paid membership month: a code the member shows at a partner restaurant.
-- balance goes down each time the admin records a use (aku_voucher_redemptions).
CREATE TABLE IF NOT EXISTS aku_member_vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  membership_id INT NOT NULL,
  user_id INT NOT NULL,
  code VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  balance DECIMAL(10,2) NOT NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voucher_code (code),
  UNIQUE KEY uq_voucher_membership (membership_id),
  KEY idx_voucher_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aku_voucher_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  voucher_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  restaurant VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_redemption_voucher (voucher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recipes can be members-only (exclusive recipes from partner chefs). Tick it in Admin > Recipes.
SET @q = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_recipes' AND column_name = 'members_only') = 0,
  'ALTER TABLE aku_recipes ADD COLUMN members_only TINYINT(1) NOT NULL DEFAULT 0', 'DO 0');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
