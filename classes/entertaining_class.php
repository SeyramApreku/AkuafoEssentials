<?php
require_once __DIR__ . '/../settings/db_class.php';

// Entertaining section: occasions, event categories and pre-order items.
class EntertainingClass extends Database
{
    public function occasions()  { return $this->fetchAll('SELECT * FROM aku_ent_occasions ORDER BY sort_order, id'); }
    public function categories() { return $this->fetchAll('SELECT * FROM aku_ent_categories ORDER BY sort_order, id'); }

    // All items with their category name and occasion ids (admin list; storefront filters come in E3).
    public function items($onlyAvailable = false)
    {
        $rows = $this->fetchAll(
            'SELECT i.*, c.name AS category_name,
                    (SELECT GROUP_CONCAT(occasion_id) FROM aku_ent_item_occasions WHERE item_id = i.id) AS occasion_ids
               FROM aku_ent_items i JOIN aku_ent_categories c ON c.id = i.category_id'
            . ($onlyAvailable ? ' WHERE i.is_available = 1' : '')
            . ' ORDER BY c.sort_order, c.id, i.name'
        );
        return array_map([$this, 'decode'], $rows);
    }

    public function item($id)
    {
        $r = $this->fetchOne(
            'SELECT i.*, c.name AS category_name,
                    (SELECT GROUP_CONCAT(occasion_id) FROM aku_ent_item_occasions WHERE item_id = i.id) AS occasion_ids
               FROM aku_ent_items i JOIN aku_ent_categories c ON c.id = i.category_id WHERE i.id = ?',
            [(int)$id]
        );
        return $r ? $this->decode($r) : null;
    }

    private function decode($r)
    {
        $r['dietary']      = $r['dietary'] === '' ? [] : explode(',', $r['dietary']);
        $r['occasion_ids'] = $r['occasion_ids'] ? array_map('intval', explode(',', $r['occasion_ids'])) : [];
        $r['contents']     = json_list($r['contents']);
        return $r;
    }

    // Insert ($id = 0) or update an item and replace its occasions, in one transaction. Returns the id.
    public function save($id, array $data, array $occasionIds)
    {
        $this->begin();
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
                $this->execute("UPDATE aku_ent_items SET $set WHERE id = ?", [...array_values($data), $id]);
            } else {
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
                $marks = implode(', ', array_fill(0, count($data), '?'));
                $this->execute("INSERT INTO aku_ent_items ($cols) VALUES ($marks)", array_values($data));
                $id = (int)$this->lastId();
            }
            $this->execute('DELETE FROM aku_ent_item_occasions WHERE item_id = ?', [$id]);
            foreach (array_unique($occasionIds) as $oid) {
                $this->execute('INSERT INTO aku_ent_item_occasions (item_id, occasion_id) VALUES (?, ?)', [$id, (int)$oid]);
            }
            $this->commit();
            return $id;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function delete($id) { return $this->execute('DELETE FROM aku_ent_items WHERE id = ?', [(int)$id]); }   // occasions cascade

    /* ---------- Storefront ---------- */

    // Available items matching the filters. $f: occasion (id), category (id), diet (list of tags, all required), guests (int).
    public function browse(array $f)
    {
        $sql = 'SELECT i.*, c.name AS category_name,
                       (SELECT GROUP_CONCAT(occasion_id) FROM aku_ent_item_occasions WHERE item_id = i.id) AS occasion_ids
                  FROM aku_ent_items i JOIN aku_ent_categories c ON c.id = i.category_id
                 WHERE i.is_available = 1';
        $p = [];
        if (!empty($f['occasion'])) { $sql .= ' AND EXISTS (SELECT 1 FROM aku_ent_item_occasions o WHERE o.item_id = i.id AND o.occasion_id = ?)'; $p[] = (int)$f['occasion']; }
        if (!empty($f['category'])) { $sql .= ' AND i.category_id = ?'; $p[] = (int)$f['category']; }
        foreach ($f['diet'] ?? [] as $d) { $sql .= ' AND FIND_IN_SET(?, i.dietary)'; $p[] = $d; }
        if (!empty($f['guests']))   { $sql .= ' AND i.serves IS NOT NULL'; }   // only portioned food can be sized for guests
        $sql .= ' ORDER BY i.featured DESC, c.sort_order, c.id, i.name';
        return array_map([$this, 'decode'], $this->fetchAll($sql, $p));
    }

    public function featured($limit = 8)
    {
        return array_map([$this, 'decode'], $this->fetchAll(
            'SELECT i.*, c.name AS category_name, NULL AS occasion_ids FROM aku_ent_items i JOIN aku_ent_categories c ON c.id = i.category_id
              WHERE i.is_available = 1 AND i.featured = 1 ORDER BY c.sort_order, i.name LIMIT ' . (int)$limit));
    }

    public function related($item, $limit = 4)
    {
        return array_map([$this, 'decode'], $this->fetchAll(
            'SELECT i.*, c.name AS category_name, NULL AS occasion_ids FROM aku_ent_items i JOIN aku_ent_categories c ON c.id = i.category_id
              WHERE i.is_available = 1 AND i.category_id = ? AND i.id <> ? ORDER BY i.featured DESC, i.name LIMIT ' . (int)$limit,
            [$item['category_id'], $item['id']]));
    }

    public function occasion($id) { return $this->fetchOne('SELECT * FROM aku_ent_occasions WHERE id = ?', [(int)$id]); }
    public function category($id) { return $this->fetchOne('SELECT * FROM aku_ent_categories WHERE id = ?', [(int)$id]); }

    /* ---------- Settings, capacity and blocked dates ---------- */

    public function setting($name, $default)
    {
        $r = $this->fetchOne('SELECT value FROM aku_settings WHERE name = ?', [$name]);
        return $r ? $r['value'] : $default;
    }
    public function setSetting($name, $value)
    {
        $this->execute('INSERT INTO aku_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, (string)$value]);
    }

    // Rules for one date, keyed by time window ('' = whole day).
    public function rulesFor($date)
    {
        $rows = $this->fetchAll('SELECT * FROM aku_ent_date_rules WHERE rule_date = ?', [$date]);
        return array_column($rows, null, 'time_window');
    }
    public function rulesBetween($from, $to)
    {
        return $this->fetchAll('SELECT * FROM aku_ent_date_rules WHERE rule_date BETWEEN ? AND ? ORDER BY rule_date, time_window', [$from, $to]);
    }

    // Entertaining orders that take up a slot: paid ones, plus unpaid ones started in the last
    // 30 minutes (someone is on the Paystack page right now). $window = null counts the whole day.
    public function bookedCount($date, $window = null, $exceptOrderId = 0)
    {
        $sql = "SELECT COUNT(*) AS n FROM aku_orders WHERE order_type = 'entertaining' AND delivery_date = ? AND status <> 'cancelled'
                  AND (payment_status = 'paid' OR created_at > NOW() - INTERVAL 30 MINUTE) AND id <> ?";
        $p = [$date, (int)$exceptOrderId];
        if ($window !== null) { $sql .= ' AND time_window = ?'; $p[] = $window; }
        return (int)$this->fetchOne($sql, $p)['n'];
    }
}
