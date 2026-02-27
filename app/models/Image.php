<?php
namespace App\Models;

class Image {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM images WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByUser(int $userId, ?string $platform = null, string $search = '') {
        $db = \Database::getConnection();
        
        $sql = 'SELECT i.*, s.account_name, s.platform as social_platform 
                FROM images i 
                LEFT JOIN social_accounts s ON i.social_account_id = s.id
                WHERE i.user_id = ?';
        $params = [$userId];
        
        if ($platform) {
            $sql .= ' AND s.platform = ?';
            $params[] = $platform;
        }
        
        if ($search) {
            $sql .= ' AND (i.name LIKE ? OR i.prompt LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY i.created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getByAgency(int $agencyId, ?string $platform = null, string $search = '') {
        $db = \Database::getConnection();
        
        $sql = 'SELECT i.*, s.account_name, s.platform as social_platform 
                FROM images i 
                LEFT JOIN social_accounts s ON i.social_account_id = s.id
                WHERE i.agency_id = ?';
        $params = [$agencyId];
        
        if ($platform) {
            $sql .= ' AND s.platform = ?';
            $params[] = $platform;
        }
        
        if ($search) {
            $sql .= ' AND (i.name LIKE ? OR i.prompt LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY i.created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, int $agencyId, string $name, string $filePath, string $prompt, ?int $socialAccountId = null): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare('INSERT INTO images (user_id, agency_id, social_account_id, name, file_path, prompt, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $agencyId, $socialAccountId, $name, $filePath, $prompt, 'active']);
        return $db->lastInsertId();
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM images WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function deleteOld(): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM images WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
