<?php
namespace App\Models;

class SocialAccount {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM social_accounts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByUser(int $userId, ?string $platform = null) {
        $db = \Database::getConnection();
        
        $sql = 'SELECT * FROM social_accounts WHERE user_id = ?';
        $params = [$userId];
        
        if ($platform) {
            $sql .= ' AND platform = ?';
            $params[] = $platform;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getByAgency(int $agencyId, ?string $platform = null) {
        $db = \Database::getConnection();
        
        $sql = 'SELECT * FROM social_accounts WHERE (agency_id = ? OR agency_id IS NULL)';
        $params = [$agencyId];
        
        if ($platform) {
            $sql .= ' AND platform = ?';
            $params[] = $platform;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, $agencyId, string $platform, string $accountId, string $accountName, ?string $accessToken = null, ?string $refreshToken = null): int {
        $db = \Database::getConnection();
        $stmt = $db->prepare('INSERT INTO social_accounts (user_id, agency_id, platform, account_id, account_name, access_token, refresh_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $agencyId, $platform, $accountId, $accountName, $accessToken, $refreshToken, 'active']);
        return $db->lastInsertId();
    }

    public static function updateToken(int $id, string $accessToken, ?string $refreshToken = null): bool {
        $db = \Database::getConnection();
        
        if ($refreshToken) {
            $stmt = $db->prepare('UPDATE social_accounts SET access_token = ?, refresh_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 60 DAY) WHERE id = ?');
            return $stmt->execute([$accessToken, $refreshToken, $id]);
        } else {
            $stmt = $db->prepare('UPDATE social_accounts SET access_token = ? WHERE id = ?');
            return $stmt->execute([$accessToken, $id]);
        }
    }

    public static function disconnect(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE social_accounts SET status = ?, access_token = NULL, refresh_token = NULL WHERE id = ?');
        return $stmt->execute(['disconnected', $id]);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('DELETE FROM social_accounts WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function isConnected(int $userId, int $agencyId, string $platform): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM social_accounts WHERE user_id = ? AND agency_id = ? AND platform = ? AND status = ?');
        $stmt->execute([$userId, $agencyId, $platform, 'active']);
        return $stmt->fetchColumn() > 0;
    }
    
    public static function findByAccountId(string $accountId, string $platform, ?int $agencyId): ?array {
        $db = \Database::getConnection();
        
        if ($agencyId) {
            $stmt = $db->prepare('SELECT * FROM social_accounts WHERE account_id = ? AND platform = ? AND agency_id = ? AND status = ?');
            $stmt->execute([$accountId, $platform, $agencyId, 'active']);
        } else {
            $stmt = $db->prepare('SELECT * FROM social_accounts WHERE account_id = ? AND platform = ? AND status = ?');
            $stmt->execute([$accountId, $platform, 'active']);
        }
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
