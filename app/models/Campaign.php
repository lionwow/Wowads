<?php
namespace App\Models;

class Campaign {
    private static $table = 'campaigns';

    public static function create(array $data): int {
        $db = \Database::getConnection();
        $fields = array_keys($data);
        $values = array_values($data);
        
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = "INSERT INTO " . self::$table . " (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM " . self::$table . " WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function getByAgency(int $agencyId, ?string $status = null): array {
        $db = \Database::getConnection();
        $sql = "SELECT c.*, cl.name as client_name FROM " . self::$table . " c 
                LEFT JOIN clients cl ON c.client_id = cl.id 
                WHERE c.agency_id = ?";
        $params = [$agencyId];

        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getByUser(int $userId, ?string $status = null): array {
        $db = \Database::getConnection();
        $sql = "SELECT c.*, cl.name as client_name FROM " . self::$table . " c 
                LEFT JOIN clients cl ON c.client_id = cl.id 
                WHERE c.user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        $sets = [];
        foreach (array_keys($data) as $field) {
            $sets[] = "$field = ?";
        }
        
        $sql = "UPDATE " . self::$table . " SET " . implode(', ', $sets) . " WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare("DELETE FROM " . self::$table . " WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getStats(int $agencyId): array {
        $db = \Database::getConnection();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . self::$table . " WHERE agency_id = ?");
        $stmt->execute([$agencyId]);
        $total = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . self::$table . " WHERE agency_id = ? AND status = 'active'");
        $stmt->execute([$agencyId]);
        $active = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . self::$table . " WHERE agency_id = ? AND status = 'draft'");
        $stmt->execute([$agencyId]);
        $draft = $stmt->fetch()['count'] ?? 0;
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . self::$table . " WHERE agency_id = ? AND status = 'paused'");
        $stmt->execute([$agencyId]);
        $paused = $stmt->fetch()['count'] ?? 0;
        
        return [
            'total' => $total,
            'active' => $active,
            'draft' => $draft,
            'paused' => $paused
        ];
    }
}
