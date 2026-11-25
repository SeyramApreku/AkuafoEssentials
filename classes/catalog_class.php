<?php
require_once __DIR__ . '/../settings/db_class.php';

class CatalogClass extends Database
{
    public function products($category = 'All', $region = 'All Regions', $farmer = '')
    {
        $sql = 'SELECT * FROM aku_products WHERE 1=1' . $this->live();
        $p = [];
        if ($category !== 'All') { $sql .= ' AND category = ?'; $p[] = $category; }
        if ($region !== 'All Regions') { $sql .= ' AND region = ?'; $p[] = $region; }
        if ($farmer !== '') { $sql .= ' AND TRIM(farmer) = TRIM(?)'; $p[] = $farmer; }   // "Shop their produce" from a farmer's page
        return $this->fetchAll($sql . ' ORDER BY id', $p);
    }
    // Farmers who have at least one product (for the Shop's farmer filter), A to Z.
    public function productFarmers()
    {
        return array_column($this->fetchAll("SELECT DISTINCT TRIM(farmer) AS farmer FROM aku_products WHERE farmer IS NOT NULL AND TRIM(farmer) <> ''" . $this->live() . ' ORDER BY farmer'), 'farmer');
    }

    // Shop search + filters. $f: q (words), category, region, farmer, sort (featured|price_asc|price_desc|name), in_stock (bool).
    // Every word in q must appear in the name, Twi name, description, farmer, region or category.
    public function search(array $f)
    {
        $sql = 'SELECT * FROM aku_products WHERE 1=1' . $this->live();
        $p = [];
        foreach (array_slice(preg_split('/\s+/u', trim((string)($f['q'] ?? '')), -1, PREG_SPLIT_NO_EMPTY), 0, 6) as $word) {
            $like = like_pattern($word);
            $sql .= " AND (name LIKE ? OR twi LIKE ? OR description LIKE ? OR farmer LIKE ? OR region LIKE ? OR category LIKE ?)";
            array_push($p, $like, $like, $like, $like, $like, $like);
        }
        if (($f['category'] ?? 'All') !== 'All')         { $sql .= ' AND category = ?'; $p[] = $f['category']; }
        if (($f['region'] ?? 'All Regions') !== 'All Regions') { $sql .= ' AND region = ?'; $p[] = $f['region']; }
        if (($f['farmer'] ?? '') !== '')                 { $sql .= ' AND TRIM(farmer) = TRIM(?)'; $p[] = $f['farmer']; }
        $stockSql = !empty($f['in_stock']) ? ' AND (stock_qty IS NULL OR stock_qty > 0)' : '';
        $order = ['price_asc' => 'price ASC, name', 'price_desc' => 'price DESC, name', 'name' => 'name'][$f['sort'] ?? ''] ?? 'id';
        try {
            return $this->fetchAll($sql . $stockSql . ' ORDER BY ' . $order, $p);
        } catch (PDOException $e) {
            if ($stockSql === '') throw $e;
            return $this->fetchAll($sql . ' ORDER BY ' . $order, $p);   // stock tracking not set up yet: ignore "in stock"
        }
    }

    public function product($id) { return $this->fetchOne('SELECT * FROM aku_products WHERE id = ?' . $this->live(), [$id]); }
    public function related($region, $exceptId) { return $this->fetchAll('SELECT * FROM aku_products WHERE region = ? AND id <> ?' . $this->live() . ' LIMIT 3', [$region, $exceptId]); }
    public function categories() { return array_merge(['All'], array_column($this->fetchAll('SELECT DISTINCT category FROM aku_products WHERE category IS NOT NULL' . $this->live() . ' ORDER BY category'), 'category')); }
    public function regions() { return array_merge(['All Regions'], array_column($this->fetchAll('SELECT DISTINCT region FROM aku_products WHERE region IS NOT NULL' . $this->live() . ' ORDER BY region'), 'region')); }

    public function boxes()
    {
        $boxes = $this->fetchAll('SELECT * FROM aku_boxes');
        foreach ($boxes as &$b) {
            $b['contents'] = json_list($b['contents']);
                        $b['sizes'] = $this->fetchAll('SELECT * FROM aku_box_sizes WHERE box_id = ? ORDER BY idx', [$b['id']]);
            foreach ($b['sizes'] as &$s) {
                $s['contents'] = json_list($s['contents'] ?? null) ?: $b['contents'];
            }
            unset($s);
        }
        return $boxes;
    }
    public function hampers()
    {
        $rows = $this->fetchAll('SELECT * FROM aku_hampers');
        foreach ($rows as &$h) $h['contents'] = json_list($h['contents']);
        return $rows;
    }
    public function bulkItems() { return $this->fetchAll('SELECT * FROM aku_bulk_items'); }

    public function recipes()
    {
        return array_map([$this, 'decodeRecipe'], $this->fetchAll('SELECT * FROM aku_recipes ORDER BY id'));
    }
    public function recipe($id) { $r = $this->fetchOne('SELECT * FROM aku_recipes WHERE id = ?', [$id]); return $r ? $this->decodeRecipe($r) : null; }
    public function recipeForProduce($name)
    {
        foreach ($this->recipes() as $r) if (in_array($name, $r['produce'])) return $r;
        return null;
    }
    // Members-only recipes, newest first (db/membership_benefits.sql). [] until that column exists.
    public function memberRecipeIds()
    {
        try { return array_map('intval', array_column($this->fetchAll('SELECT id FROM aku_recipes WHERE members_only = 1 ORDER BY id DESC'), 'id')); }
        catch (PDOException $e) { return []; }
    }
    private function decodeRecipe($r)
    {
        foreach (['ingredients', 'method', 'produce', 'tags'] as $k) $r[$k] = json_list($r[$k]);
        return $r;
    }

    public function farmers() { return array_map([$this, 'decodeFarmer'], $this->fetchAll('SELECT * FROM aku_farmers ORDER BY id')); }
    public function farmer($id) { $f = $this->fetchOne('SELECT * FROM aku_farmers WHERE id = ?', [$id]); return $f ? $this->decodeFarmer($f) : null; }
    private function decodeFarmer($f)
    {
        $f['produce'] = json_list($f['produce']);
        $f['certifications'] = json_list($f['certifications']);
        return $f;
    }

    // ---- Farmers decide the product list (db/farmer_produce.sql) ----
    // Customers only see live products. Drafts (made from a farmer's produce) wait for a price, unit and category.
    private static $hasActive = null;
    public function hasDraftColumn()
    {
        if (self::$hasActive === null) {
            self::$hasActive = (bool)$this->fetchOne("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'aku_products' AND column_name = 'is_active'");
        }
        return self::$hasActive;
    }
    private function live() { return $this->hasDraftColumn() ? ' AND is_active = 1' : ''; }

    // Ids of products still waiting to be completed (shown as "Draft" in admin).
    public function draftIds()
    {
        return $this->hasDraftColumn() ? array_map('intval', array_column($this->fetchAll('SELECT id FROM aku_products WHERE is_active = 0'), 'id')) : [];
    }

    // Does this farmer already have a product with this name? (case and spaces don't matter)
    public function farmerHasProduct($farmer, $name)
    {
        return (bool)$this->fetchOne('SELECT 1 FROM aku_products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND LOWER(TRIM(farmer)) = LOWER(TRIM(?))', [$name, $farmer]);
    }

    // A farmer was saved: every item of their produce that isn't a product for them yet becomes a draft product.
    // Returns the names of the drafts made. Nothing is ever deleted.
    public function syncFarmerProduce($farmer, $region, array $produce)
    {
        if (!$this->hasDraftColumn() || trim((string)$farmer) === '') return [];   // without drafts they'd go live with no price
        $made = [];
        foreach ($produce as $name) {
            $name = trim((string)$name);
            if ($name === '' || in_array(mb_strtolower($name), array_map('mb_strtolower', $made), true) || $this->farmerHasProduct($farmer, $name)) continue;
            $this->execute('INSERT INTO aku_products (name, farmer, region, is_active) VALUES (?, ?, ?, 0)', [$name, trim($farmer), $region ?: null]);
            $made[] = $name;
        }
        return $made;
    }

    // A product was saved with a farmer: make sure it's on that farmer's produce list.
    public function addToFarmerProduce($farmer, $name)
    {
        $f = $this->fetchOne('SELECT id, produce FROM aku_farmers WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))', [$farmer]);
        $name = trim((string)$name);
        if (!$f || $name === '') return false;
        $list = json_list($f['produce']);
        foreach ($list as $item) if (mb_strtolower(trim($item)) === mb_strtolower($name)) return false;
        $list[] = $name;
        $this->execute('UPDATE aku_farmers SET produce = ? WHERE id = ?', [json_encode(array_values($list), JSON_UNESCAPED_UNICODE), $f['id']]);
        return true;
    }

    // A farmer was renamed: their products follow the new name.
    public function renameFarmer($old, $new)
    {
        if (trim((string)$old) === '' || trim($old) === trim($new)) return;
        $this->execute('UPDATE aku_products SET farmer = ? WHERE TRIM(farmer) = TRIM(?)', [trim($new), $old]);
    }
    // Renaming a category (or region) moves its products too, so the shop never shows the old and new name side by side.
    public function renameCategory($old, $new) { return $this->renameColumnValue('category', $old, $new); }
    public function renameRegion($old, $new) { return $this->renameColumnValue('region', $old, $new); }
    private function renameColumnValue($col, $old, $new)
    {
        if (trim((string)$old) === '' || trim($old) === trim($new)) return 0;
        return $this->execute("UPDATE aku_products SET `$col` = ? WHERE TRIM(`$col`) = TRIM(?)", [trim($new), $old]);
    }
}
