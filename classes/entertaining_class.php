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
}
