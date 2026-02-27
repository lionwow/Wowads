<?php
namespace App\Controllers;

use App\Models\User;

class AuthController {
    private function redirect(string $path): void {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $base = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/' : rtrim($scriptDir, '/') . '/';
        header('Location: ' . $base . ltrim($path, '/'));
        exit;
    }

    public function loginForm(): void {
        $_SESSION['csrf_login_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function loginPost(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !isset($_SESSION['csrf_login_token']) || hash_equals($_SESSION['csrf_login_token'], $token) === false) {
            $this->redirect('/');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check user status
            $status = $user['status'] ?? 'active';
            
            if ($status === 'suspended') {
                $error = 'Tu cuenta ha sido suspendida. Contacta al administrador.';
            } elseif ($status === 'inactive') {
                $error = 'Tu cuenta está inactiva. Contacta al administrador.';
            } else {
                \Session::login((int)$user['id']);
                $this->redirect('/dashboard');
                exit;
            }
        } else {
            $error = 'Credenciales inválidas';
        }
        
        $_SESSION['csrf_login_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function registerForm(): void {
        $_SESSION['csrf_register_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/register.php';
    }

    public function registerPost(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !isset($_SESSION['csrf_register_token']) || hash_equals($_SESSION['csrf_register_token'], $token) === false) {
            $this->redirect('/auth/register');
            exit;
        }

        $type = $_POST['type'] ?? 'client';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $agencyName = trim($_POST['agency_name'] ?? '');
        $agencyWebsite = trim($_POST['agency_website'] ?? '');

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
        if (strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        if (empty($name)) $errors[] = 'El nombre es requerido';
        
        if ($type === 'agency') {
            if (empty($agencyName)) $errors[] = 'El nombre de la agencia es requerido';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            
            try {
                if ($type === 'agency') {
                    $id = User::createAgency($name, $email, $hash, $phone, $agencyName, $agencyWebsite);
                } else {
                    $id = User::createClient($name, $email, $hash, $phone);
                }
                
                if ($id) {
                    \Session::login((int)$id);
                    $this->redirect('/dashboard?welcome=1');
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = 'El email ya está registrado';
                } else {
                    $errors[] = 'Error al registrar: ' . $e->getMessage();
                }
            }
        }
        
        $error = implode('<br>', $errors);
        $_SESSION['csrf_register_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/register.php';
    }
    
    // Admin Login
    public function adminLoginForm(): void {
        // If already logged in as admin, redirect to superadmin
        if (\Session::isLoggedIn()) {
            $userId = \Session::getUserId();
            $user = User::findById($userId);
            if ($user && $user['role'] === 'admin') {
                $this->redirect('/superadmin');
            }
        }
        
        $_SESSION['csrf_admin_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/admin_login.php';
    }

    public function adminLoginPost(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !isset($_SESSION['csrf_admin_token']) || hash_equals($_SESSION['csrf_admin_token'], $token) === false) {
            $this->redirect('/admin');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $status = $user['status'] ?? 'active';
            
            if ($status === 'suspended') {
                $error = 'Tu cuenta ha sido suspendida.';
            } elseif ($status === 'inactive') {
                $error = 'Tu cuenta está inactiva.';
            } elseif ($user['role'] !== 'admin') {
                $error = 'No tienes acceso al panel de administrador.';
            } else {
                \Session::login((int)$user['id']);
                $this->redirect('/superadmin');
                exit;
            }
        } else {
            $error = 'Credenciales inválidas';
        }
        
        $_SESSION['csrf_admin_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/admin_login.php';
    }

    public function forgotPasswordForm(): void {
        $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/forgot_password.php';
    }

    public function forgotPasswordPost(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !isset($_SESSION['csrf_forgot_token']) || hash_equals($_SESSION['csrf_forgot_token'], $token) === false) {
            $this->redirect('/auth/forgot-password');
            exit;
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor ingresa un correo electrónico válido';
            $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(16));
            require __DIR__ . '/../../views/auth/forgot_password.php';
            return;
        }

        $user = User::findByEmail($email);

        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            User::setResetToken((int)$user['id'], $resetToken, $expires);

            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            $scriptDir = str_replace('\\', '/', $scriptDir);
            $base = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/' : rtrim($scriptDir, '/') . '/';
            $resetLink = $base . 'auth/reset-password?token=' . $resetToken;
            $this->sendResetEmail($email, $user['name'], $resetLink);
        }

        $success = 'Si el correo existe en nuestro sistema, recibirás un enlace de recuperación en breve.';
        $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/forgot_password.php';
    }

    public function resetPasswordForm(): void {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->redirect('/auth/forgot-password');
            return;
        }

        $user = User::findByResetToken($token);
        
        if (!$user) {
            $error = 'El enlace de recuperación ha expirado o es inválido';
            $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(16));
            require __DIR__ . '/../../views/auth/forgot_password.php';
            return;
        }

        $_SESSION['csrf_reset_token'] = bin2hex(random_bytes(16));
        require __DIR__ . '/../../views/auth/reset_password.php';
    }

    public function resetPasswordPost(): void {
        $token = $_POST['token'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (empty($csrfToken) || !isset($_SESSION['csrf_reset_token']) || hash_equals($_SESSION['csrf_reset_token'], $csrfToken) === false) {
            $this->redirect('/');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
            $_SESSION['csrf_reset_token'] = bin2hex(random_bytes(16));
            require __DIR__ . '/../../views/auth/reset_password.php';
            return;
        }

        if ($password !== $confirmPassword) {
            $error = 'Las contraseñas no coinciden';
            $_SESSION['csrf_reset_token'] = bin2hex(random_bytes(16));
            require __DIR__ . '/../../views/auth/reset_password.php';
            return;
        }

        $user = User::findByResetToken($token);

        if (!$user) {
            $error = 'El enlace de recuperación ha expirado o es inválido';
            $_SESSION['csrf_forgot_token'] = bin2hex(random_bytes(16));
            require __DIR__ . '/../../views/auth/forgot_password.php';
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        User::updatePassword((int)$user['id'], $passwordHash);

        $success = 'Tu contraseña ha sido cambiada exitosamente. Ya puedes iniciar sesión.';
        require __DIR__ . '/../../views/auth/reset_password.php';
    }

    private function sendResetEmail(string $email, string $name, string $resetLink): void {
        $subject = 'Recuperar tu contraseña - WowAds';
        $message = "
        <html>
        <head>
            <title>Recuperar Contraseña</title>
        </head>
        <body>
            <h2>Hola {$name},</h2>
            <p>Has solicitado recuperar tu contraseña en WowAds.</p>
            <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
            <p><a href='{$resetLink}' style='background-color: #A8FF35; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>Recuperar Contraseña</a></p>
            <p>Este enlace expirará en 1 hora.</p>
            <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
            <br>
            <p>Saludos,<br>El equipo de WowAds</p>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type:text/html;charset=UTF-8\r\n";
        $headers .= "From: " . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'WowAds') . " <" . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@wowads.com') . ">\r\n";

        @mail($email, $subject, $message, $headers);
    }
}
