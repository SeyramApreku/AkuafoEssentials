<?php
require_once __DIR__ . '/../settings/db_class.php';

// Delivery: zones (fee + delivery days) and the areas inside them.
class DeliveryClass extends Database
{
    public function zones()
    {
        return $this->fetchAll(
            'SELECT z.*, (SELECT COUNT(*) FROM aku_delivery_areas a WHERE a.zone_id = z.id) AS area_count
               FROM aku_delivery_zones z ORDER BY z.fee, z.name');
    }
    public function zone($id) { return $this->fetchOne('SELECT * FROM aku_delivery_zones WHERE id = ?', [(int)$id]); }

    public function areas($zoneId = null)
    {
        $sql = 'SELECT a.*, z.name AS zone_name, z.fee, z.is_active AS zone_active
                  FROM aku_delivery_areas a JOIN aku_delivery_zones z ON z.id = a.zone_id';
        return $zoneId ? $this->fetchAll($sql . ' WHERE a.zone_id = ? ORDER BY a.name', [(int)$zoneId])
                       : $this->fetchAll($sql . ' ORDER BY a.name');
    }
    public function area($id) { return $this->fetchOne('SELECT * FROM aku_delivery_areas WHERE id = ?', [(int)$id]); }

    public function saveZone($id, $name, $fee, $days, $active)
    {
        if ($id) {
            $this->execute('UPDATE aku_delivery_zones SET name = ?, fee = ?, delivery_days = ?, is_active = ? WHERE id = ?', [$name, $fee, $days, $active, $id]);
            return (int)$id;
        }
        $this->execute('INSERT INTO aku_delivery_zones (name, fee, delivery_days, is_active) VALUES (?, ?, ?, ?)', [$name, $fee, $days, $active]);
        return (int)$this->lastId();
    }

    // A zone with areas can't be deleted (switch it off instead).
    public function deleteZone($id)
    {
        if ($this->fetchOne('SELECT 1 FROM aku_delivery_areas WHERE zone_id = ? LIMIT 1', [(int)$id])) return false;
        return $this->execute('DELETE FROM aku_delivery_zones WHERE id = ?', [(int)$id]) > 0;
    }

    public function saveArea($id, $zoneId, $name, $active)
    {
        if ($id) return $this->execute('UPDATE aku_delivery_areas SET zone_id = ?, name = ?, is_active = ? WHERE id = ?', [$zoneId, $name, $active, $id]);
        return $this->execute('INSERT INTO aku_delivery_areas (zone_id, name, is_active) VALUES (?, ?, ?)', [$zoneId, $name, $active]);
    }

    // Areas used by saved addresses (from D2) can't be deleted, only switched off.
    public function deleteArea($id)
    {
        try { return $this->execute('DELETE FROM aku_delivery_areas WHERE id = ?', [(int)$id]) > 0; }
        catch (PDOException $e) { return false; }
    }

    public function setSetting($name, $value)
    {
        $this->execute('INSERT INTO aku_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, (string)$value]);
    }
}
