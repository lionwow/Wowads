<?php
// Load config first
require_once __DIR__ . '/../config/config.php';

class Session {
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on',
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function login(int $user_id): void {
        $_SESSION['user_id'] = $user_id;
    }

    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function logout(): void {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    // Masquerade / Login As functionality
    public static function masqueradeAs(int $userId): void {
        // Store the real admin ID before masquerading
        if (!isset($_SESSION['masquerade_from'])) {
            $_SESSION['masquerade_from'] = $_SESSION['user_id'];
        }
        $_SESSION['user_id'] = $userId;
    }
    
    public static function isMasquerading(): bool {
        return isset($_SESSION['masquerade_from']);
    }
    
    public static function getRealAdminId(): ?int {
        return $_SESSION['masquerade_from'] ?? null;
    }
    
    public static function stopMasquerading(): void {
        if (isset($_SESSION['masquerade_from'])) {
            $_SESSION['user_id'] = $_SESSION['masquerade_from'];
            unset($_SESSION['masquerade_from']);
        }
    }
    
    public static function redirect(string $path): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $base = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/' : rtrim($scriptDir, '/') . '/';
        header('Location: ' . $base . ltrim($path, '/'));
        exit;
    }
    
    public static function setFlash(string $type, string $message): void {
        $_SESSION['flash'][$type] = $message;
    }
    
    public static function getFlash(string $type): ?string {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
}
