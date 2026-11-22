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

    // Areas used by saved addresses can't be deleted, only switched off.
    public function deleteArea($id)
    {
        try {
            if ($this->areaInUse($id)) return false;
        } catch (PDOException $e) { /* aku_addresses not created yet (before D2): nothing uses the area */ }
        return $this->execute('DELETE FROM aku_delivery_areas WHERE id = ?', [(int)$id]) > 0;
    }

    /* ---------- Customer addresses (D2) ---------- */

    // Active areas grouped for a <select>: [zone name => [area rows]] (inactive zones/areas left out).
    public function areasForSelect()
    {
        $out = [];
        foreach ($this->fetchAll('SELECT a.id, a.name, z.name AS zone_name, z.fee FROM aku_delivery_areas a JOIN aku_delivery_zones z ON z.id = a.zone_id
                                   WHERE a.is_active = 1 AND z.is_active = 1 ORDER BY z.fee, z.name, a.name') as $r) $out[$r['zone_name']][] = $r;
        return $out;
    }

    // A customer's addresses with their area/zone; 'deliverable' is false if the area or zone was switched off.
    public function addresses($customerId)
    {
        return $this->fetchAll(
            'SELECT ad.*, a.name AS area_name, z.id AS zone_id, z.name AS zone_name, z.fee, z.delivery_days,
                    (a.is_active = 1 AND z.is_active = 1) AS deliverable
               FROM aku_addresses ad JOIN aku_delivery_areas a ON a.id = ad.area_id JOIN aku_delivery_zones z ON z.id = a.zone_id
              WHERE ad.customer_id = ? ORDER BY ad.is_default DESC, ad.id DESC', [(int)$customerId]);
    }

    // One address, only if it belongs to this customer.
    public function address($id, $customerId)
    {
        foreach ($this->addresses($customerId) as $a) if ((int)$a['id'] === (int)$id) return $a;
        return null;
    }

    public function saveAddress($id, $customerId, array $d)
    {
        $first = !$this->fetchOne('SELECT 1 FROM aku_addresses WHERE customer_id = ? LIMIT 1', [(int)$customerId]);
        if ($id) {
            $this->execute('UPDATE aku_addresses SET label = ?, gps_address = ?, area_id = ?, landmark = ?, notes = ?, phone = ? WHERE id = ? AND customer_id = ?',
                [$d['label'], $d['gps_address'], $d['area_id'], $d['landmark'], $d['notes'], $d['phone'], (int)$id, (int)$customerId]);
        } else {
            $this->execute('INSERT INTO aku_addresses (customer_id, label, gps_address, area_id, landmark, notes, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [(int)$customerId, $d['label'], $d['gps_address'], $d['area_id'], $d['landmark'], $d['notes'], $d['phone'], $first ? 1 : 0]);
            $id = (int)$this->lastId();
        }
        if (!empty($d['make_default'])) $this->setDefaultAddress($id, $customerId);
        return (int)$id;
    }

    public function setDefaultAddress($id, $customerId)
    {
        $this->execute('UPDATE aku_addresses SET is_default = (id = ?) WHERE customer_id = ?', [(int)$id, (int)$customerId]);
    }

    public function deleteAddress($id, $customerId)
    {
        $wasDefault = $this->fetchOne('SELECT is_default FROM aku_addresses WHERE id = ? AND customer_id = ?', [(int)$id, (int)$customerId]);
        $n = $this->execute('DELETE FROM aku_addresses WHERE id = ? AND customer_id = ?', [(int)$id, (int)$customerId]);
        if ($n && $wasDefault && (int)$wasDefault['is_default']) {   // promote the newest remaining address
            $next = $this->fetchOne('SELECT id FROM aku_addresses WHERE customer_id = ? ORDER BY id DESC LIMIT 1', [(int)$customerId]);
            if ($next) $this->setDefaultAddress($next['id'], $customerId);
        }
        return $n > 0;
    }

    // An area can't be deleted while saved addresses use it (no FK, so check here).
    public function areaInUse($areaId) { return (bool)$this->fetchOne('SELECT 1 FROM aku_addresses WHERE area_id = ? LIMIT 1', [(int)$areaId]); }

    /* ---------- Waitlist ---------- */
    public function joinWaitlist($customerId, $email, $area)
    {
        return $this->execute('INSERT IGNORE INTO aku_delivery_waitlist (customer_id, email, area_name) VALUES (?, ?, ?)', [$customerId ?: null, $email, $area]);
    }
    public function waitlist() { return $this->fetchAll('SELECT * FROM aku_delivery_waitlist ORDER BY area_name, created_at'); }
    public function deleteWaitlist($id) { return $this->execute('DELETE FROM aku_delivery_waitlist WHERE id = ?', [(int)$id]); }

    public function setSetting($name, $value)
    {
        $this->execute('INSERT INTO aku_settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, (string)$value]);
    }
}
