<?php
namespace App\Models;

class Client {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByAgency(int $agencyId) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM clients WHERE agency_id = ? ORDER BY name ASC');
        $stmt->execute([$agencyId]);
        return $stmt->fetchAll();
    }

    public static function create(int $agencyId, string $name, ?string $email = null): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare('INSERT INTO clients (agency_id, name, email) VALUES (?, ?, ?)');
        $stmt->execute([$agencyId, $name, $email]);
        return $db->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $email = null): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE clients SET name = ?, email = ? WHERE id = ?');
        return $stmt->execute([$name, $email, $id]);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM clients WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
