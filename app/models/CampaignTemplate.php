<?php
namespace App\Models;

class CampaignTemplate {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM campaign_templates WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getAll() {
        $db = \Database::getConnection();
        $stmt = $db->query('SELECT * FROM campaign_templates ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function getActive() {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM campaign_templates WHERE is_active = 1 ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $db = \Database::getConnection();
        
        $stmt = $db->prepare('INSERT INTO campaign_templates (name, category, description, campaign_config, adset_config, creative_config, ad_config, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $data['category'] ?? null,
            $data['description'] ?? '',
            $data['campaign_config'],
            $data['adset_config'] ?? null,
            $data['creative_config'] ?? null,
            $data['ad_config'] ?? null,
            $data['is_active'] ?? 1
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE campaign_templates SET name = ?, category = ?, description = ?, campaign_config = ?, adset_config = ?, creative_config = ?, ad_config = ?, is_active = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $data['category'] ?? null,
            $data['description'] ?? '',
            $data['campaign_config'],
            $data['adset_config'] ?? null,
            $data['creative_config'] ?? null,
            $data['ad_config'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM campaign_templates WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function toggleActive(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE campaign_templates SET is_active = NOT is_active WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
