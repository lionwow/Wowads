<?php
namespace App\Models;

class User {
    public static function findByEmail(string $email) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function findById(int $id) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getAll(?string $role = null, ?string $status = null, string $search = '') {
        $db = \Database::getConnection();
        
        $sql = 'SELECT u.*, a.name as agency_name FROM users u LEFT JOIN agencies a ON u.agency_id = a.id WHERE 1=1';
        $params = [];
        
        if ($role) {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
        }
        
        if ($status) {
            $sql .= ' AND u.status = ?';
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY u.created_at DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getCount(?string $role = null): int {
        $db = \Database::getConnection();
        $sql = 'SELECT COUNT(*) as total FROM users';
        $params = [];
        
        if ($role) {
            $sql .= ' WHERE role = ?';
            $params[] = $role;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public static function createClient(string $name, string $email, string $passwordHash, ?string $phone = null) {
        $db = \Database::getConnection();
        $stmt = $db->prepare('INSERT INTO users (name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $passwordHash, 'client', 'active']);
        return $db->lastInsertId();
    }

    public static function createAgency(string $name, string $email, string $passwordHash, ?string $phone, string $agencyName, ?string $agencyWebsite) {
        $db = \Database::getConnection();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare('INSERT INTO agencies (name, website, status) VALUES (?, ?, ?)');
            $stmt->execute([$agencyName, $agencyWebsite, 'active']);
            $agencyId = $db->lastInsertId();
            
            $stmt = $db->prepare('INSERT INTO users (name, email, phone, password_hash, role, agency_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $passwordHash, 'agency', $agencyId, 'active']);
            $userId = $db->lastInsertId();
            
            $stmt = $db->prepare('UPDATE agencies SET owner_user_id = ? WHERE id = ?');
            $stmt->execute([$userId, $agencyId]);
            
            $db->commit();
            return $userId;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $data): bool {
        $db = \Database::getConnection();
        
        $allowedFields = ['name', 'email', 'phone', 'role', 'status', 'credits'];
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
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        
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
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function addCredits(int $userId, float $credits): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE users SET credits = credits + ? WHERE id = ?');
        return $stmt->execute([$credits, $userId]);
    }

    public static function setResetToken(int $userId, string $token, string $expires): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
        return $stmt->execute([$token, $expires, $userId]);
    }

    public static function findByResetToken(string $token): ?array {
        $db = \Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function updatePassword(int $userId, string $passwordHash): bool {
        $db = \Database::getConnection();
        $stmt = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
        return $stmt->execute([$passwordHash, $userId]);
    }
}
