<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WowAds - Inteligencia Artificial para Publicidad</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="logo">W</div>
            <h1>WowAds</h1>
            <p>Inteligencia Artificial para Publicidad</p>
        </div>
        
        <div class="auth-card">
            <div class="auth-header">
                <h2>Bienvenido de nuevo</h2>
                <p>Inicia sesión para continuar</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
                <div class="alert alert-success">Cuenta creada exitosamente. Por favor inicia sesión.</div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $baseUrl; ?>" class="auth-form" autocomplete="on">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="tu@email.com" 
                        required 
                        autofocus
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required
                        autocomplete="current-password"
                    >
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_login_token'] ?? ''); ?>">
                
                <button type="submit" class="btn-primary">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="<?php echo $baseUrl; ?>auth/forgot-password">¿Olvidaste tu contraseña?</a>
                <br><br>
                ¿No tienes cuenta? <a href="<?php echo $baseUrl; ?>auth/register">Crear cuenta</a>
            </div>
        </div>
    </div>
</body>
</html>
