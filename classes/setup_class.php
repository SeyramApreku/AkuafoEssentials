<?php
require_once __DIR__ . '/../settings/db_class.php';

// Admin "Database update": applies the SQL files in db/ from the website, so no phpMyAdmin is needed.
// Every file is written to be safe to run more than once.
class SetupClass extends Database
{
    // In the order they must run, with a check that tells whether each is already applied.
    public function steps()
    {
        return [
            ['file' => 'fix_ids.sql',      'label' => 'Products & farmers number themselves (needed to add new ones)', 'check' => ['autoinc', 'aku_products', 'aku_farmers']],
            ['file' => 'entertaining.sql', 'label' => 'Entertaining section',            'check' => ['table', 'aku_ent_items']],
            ['file' => 'delivery_d1.sql',  'label' => 'Delivery zones, areas, settings', 'check' => ['column', 'aku_delivery_zones', 'delivery_days']],
            ['file' => 'delivery_d2.sql',  'label' => 'Saved addresses and waitlist',    'check' => ['column', 'aku_orders', 'gps_address']],
            ['file' => 'inventory.sql',    'label' => 'Inventory (stock levels)',        'check' => ['table', 'aku_stock_moves']],
            ['file' => 'security.sql',     'label' => 'Security (login lockout)',        'check' => ['table', 'aku_login_attempts']],
            ['file' => 'login_lockout.sql', 'label' => 'Login lockout (3 tries, then 10 minutes)', 'check' => ['column', 'aku_login_attempts', 'attempt_count']],
            ['file' => 'box_subscriptions.sql', 'label' => 'Weekly box subscriptions (length, renewal reminders)', 'check' => ['table', 'aku_box_subscriptions']],
            ['file' => 'farmer_payouts.sql', 'label' => 'Farmer revenue: plans, rates, pickups, payouts', 'check' => ['column', 'aku_farmer_payouts', 'net_payout']],
            ['file' => 'farmer_revenue.sql', 'label' => 'Farmer revenue: sales by farmer, box supply, QC, carry-forward, contacts', 'check' => ['column', 'aku_farmer_payouts', 'carried_out']],
            ['file' => 'membership_benefits.sql', 'label' => 'Membership benefits (vouchers, members-only recipes)', 'check' => ['table', 'aku_voucher_redemptions']],
            ['file' => 'partner_recipes.sql', 'label' => 'Recipes credited to partner restaurants', 'check' => ['column', 'aku_recipes', 'partner']],
            ['file' => 'exclusive_recipes.sql', 'label' => "Exclusive members' recipes (4 from partner kitchens)", 'check' => ['recipe', 'Abenkwan with Smoked Mackerel']],
            ['file' => 'partners_testimonials.sql', 'label' => 'Partner testimonials (photo, role, quote)', 'check' => ['column', 'aku_partners', 'quote']],
            ['file' => 'farmer_produce.sql', 'label' => "Farmers' produce creates draft products", 'check' => ['column', 'aku_products', 'is_active']],
        ];
    }

    public function isApplied($check)
    {
        if ($check[0] === 'autoinc') {   // every listed table's id numbers itself
            foreach (array_slice($check, 1) as $t)
                if (!$this->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'id' AND extra LIKE '%auto_increment%'", [$t])) return false;
            return true;
        }
        if ($check[0] === 'recipe') {
            try { return (bool)$this->fetchOne('SELECT 1 FROM aku_recipes WHERE name = ?', [$check[1]]); } catch (PDOException $e) { return false; }
        }
        if ($check[0] === 'table') {
            return (bool)$this->fetchOne('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$check[1]]);
        }
        return (bool)$this->fetchOne('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$check[1], $check[2]]);
    }

    // Splits a .sql file into statements on ";" that are not inside quotes or comments.
    public static function splitSql($sql)
    {
        $out = []; $buf = ''; $len = strlen($sql); $q = null;
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($q) {                                   // inside '...' or "..."
                $buf .= $c;
                if ($c === '\\') { $buf .= $sql[++$i] ?? ''; continue; }
                if ($c === $q) { if (($sql[$i + 1] ?? '') === $q) { $buf .= $sql[++$i]; continue; } $q = null; }
                continue;
            }
            if ($c === '-' && ($sql[$i + 1] ?? '') === '-') { while ($i < $len && $sql[$i] !== "\n") $i++; $buf .= "\n"; continue; }   // -- comment
            if ($c === '#') { while ($i < $len && $sql[$i] !== "\n") $i++; $buf .= "\n"; continue; }
            if ($c === "'" || $c === '"') { $q = $c; $buf .= $c; continue; }
            if ($c === ';') { if (trim($buf) !== '') $out[] = trim($buf); $buf = ''; continue; }
            $buf .= $c;
        }
        if (trim($buf) !== '') $out[] = trim($buf);
        return $out;
    }

    // Runs one file. Returns [ok, statements run, error message or ''].
    public function runFile($file)
    {
        $path = __DIR__ . '/../db/' . basename($file);
        if (!is_file($path)) return [false, 0, "File db/$file was not found on the server. Upload the db folder."];
        $n = 0;
        foreach (self::splitSql(file_get_contents($path)) as $stmt) {
            try {
                // query() + closeCursor(): a statement that returns rows (e.g. EXECUTE of a SELECT) can't block the next one.
                $st = $this->db()->query($stmt);
                if ($st) { try { $st->fetchAll(); } catch (PDOException $ignored) {} $st->closeCursor(); }
                $n++;
            }
            catch (PDOException $e) { return [false, $n, $e->getMessage() . ' — in: ' . mb_strimwidth(preg_replace('/\s+/', ' ', $stmt), 0, 160, '…')]; }
        }
        return [true, $n, ''];
    }
}
