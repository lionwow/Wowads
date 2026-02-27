<?php
namespace App\Models;

class BrandDNA {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM brand_dna WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByAgency(int $agencyId) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM brand_dna WHERE agency_id = ? ORDER BY created_at DESC');
        $stmt->execute([$agencyId]);
        return $stmt->fetchAll();
    }

    public static function getActive(int $agencyId) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM brand_dna WHERE agency_id = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$agencyId]);
        return $stmt->fetch();
    }

    public static function create(int $agencyId, array $data): int {
        $db = \Database::getConnection();
        
        $socialAccountId = isset($data['social_account_id']) ? intval($data['social_account_id']) : null;
        $userId = isset($data['user_id']) ? intval($data['user_id']) : null;
        
        $stmt = $db->prepare('INSERT INTO brand_dna (agency_id, user_id, social_account_id, fanpage_id, fanpage_name, name, description, brand_values, business_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $agencyId,
            $userId,
            $socialAccountId,
            $data['fanpage_id'] ?? null,
            $data['fanpage_name'] ?? null,
            $data['name'],
            $data['description'] ?? '',
            $data['brand_values'] ?? '',
            $data['business_description'] ?? ''
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE brand_dna SET name = ?, description = ?, brand_values = ?, business_description = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['brand_values'] ?? '',
            $data['business_description'] ?? '',
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM brand_dna WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
