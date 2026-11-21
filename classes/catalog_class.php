<?php
require_once __DIR__ . '/../settings/db_class.php';

class CatalogClass extends Database
{
    public function products($category = 'All', $region = 'All Regions')
    {
        $sql = 'SELECT * FROM products WHERE 1=1';
        $p = [];
        if ($category !== 'All') { $sql .= ' AND category = ?'; $p[] = $category; }
        if ($region !== 'All Regions') { $sql .= ' AND region = ?'; $p[] = $region; }
        return $this->fetchAll($sql . ' ORDER BY id', $p);
    }
    public function product($id) { return $this->fetchOne('SELECT * FROM products WHERE id = ?', [$id]); }
    public function related($region, $exceptId) { return $this->fetchAll('SELECT * FROM products WHERE region = ? AND id <> ? LIMIT 3', [$region, $exceptId]); }
    public function categories() { return array_merge(['All'], array_column($this->fetchAll('SELECT DISTINCT category FROM products ORDER BY category'), 'category')); }
    public function regions() { return array_merge(['All Regions'], array_column($this->fetchAll('SELECT DISTINCT region FROM products ORDER BY region'), 'region')); }

    public function boxes()
    {
        $boxes = $this->fetchAll('SELECT * FROM boxes');
        foreach ($boxes as &$b) {
            $b['contents'] = json_list($b['contents']);
                        $b['sizes'] = $this->fetchAll('SELECT * FROM box_sizes WHERE box_id = ? ORDER BY idx', [$b['id']]);
            foreach ($b['sizes'] as &$s) {
                $s['contents'] = json_list($s['contents'] ?? null) ?: $b['contents'];
            }
            unset($s);
        }
        return $boxes;
    }
    public function hampers()
    {
        $rows = $this->fetchAll('SELECT * FROM hampers');
        foreach ($rows as &$h) $h['contents'] = json_list($h['contents']);
        return $rows;
    }
    public function bulkItems() { return $this->fetchAll('SELECT * FROM bulk_items'); }

    public function recipes()
    {
        return array_map([$this, 'decodeRecipe'], $this->fetchAll('SELECT * FROM recipes ORDER BY id'));
    }
    public function recipe($id) { $r = $this->fetchOne('SELECT * FROM recipes WHERE id = ?', [$id]); return $r ? $this->decodeRecipe($r) : null; }
    public function recipeForProduce($name)
    {
        foreach ($this->recipes() as $r) if (in_array($name, $r['produce'])) return $r;
        return null;
    }
    private function decodeRecipe($r)
    {
        foreach (['ingredients', 'method', 'produce', 'tags'] as $k) $r[$k] = json_list($r[$k]);
        return $r;
    }

    public function farmers() { return array_map([$this, 'decodeFarmer'], $this->fetchAll('SELECT * FROM farmers ORDER BY id')); }
    public function farmer($id) { $f = $this->fetchOne('SELECT * FROM farmers WHERE id = ?', [$id]); return $f ? $this->decodeFarmer($f) : null; }
    private function decodeFarmer($f)
    {
        $f['produce'] = json_list($f['produce']);
        $f['certifications'] = json_list($f['certifications']);
        return $f;
    }
}
