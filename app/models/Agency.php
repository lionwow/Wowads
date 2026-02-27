<?php
namespace App\Models;

class Agency {
    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT a.*, u.name as owner_name, u.email as owner_email 
            FROM agencies a 
            LEFT JOIN users u ON a.owner_user_id = u.id 
            WHERE a.id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getAll(?string $status = null, string $search = '') {
        $db = \Database::getConnection();
        
        $sql = 'SELECT a.*, u.name as owner_name, u.email as owner_email,
            (SELECT COUNT(*) FROM clients WHERE agency_id = a.id) as clients_count
            FROM agencies a 
            LEFT JOIN users u ON a.owner_user_id = u.id 
            WHERE 1=1';
        $params = [];
        
        if ($status) {
            $sql .= ' AND a.status = ?';
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= ' AND (a.name LIKE ? OR u.name LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY a.created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getCount(?string $status = null): int {
        $db = \Database::getConnection();
        $sql = 'SELECT COUNT(*) as total FROM agencies';
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        
        $allowedFields = ['name', 'website', 'status'];
        $updates = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $id;
        $sql = 'UPDATE agencies SET ' . implode(', ', $updates) . ' WHERE id = ?';
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function suspend(int $id): bool {
        return self::update($id, ['status' => 'suspended']);
    }

    public static function activate(int $id): bool {
        return self::update($id, ['status' => 'active']);
    }

    public static function delete(int $id): bool {
        $db = \Database::getConnection();
        
        // First update users to remove agency reference
        $stmt = $db->prepare('UPDATE users SET agency_id = NULL WHERE agency_id = ?');
        $stmt->execute([$id]);
        
        // Then delete agency
        $stmt = $db->prepare('DELETE FROM agencies WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
