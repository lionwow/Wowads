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
    <title>Admin - WowAds</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
    <style>
        .auth-page { min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; background: radial-gradient(ellipse at 20% 20%, rgba(168, 255, 53, 0.03) 0%, transparent 50%), radial-gradient(ellipse at 80% 80%, rgba(168, 255, 53, 0.02) 0%, transparent 50%), var(--bg); padding: 40px 20px; overflow-y: auto; }
        .auth-container { width: 100%; max-width: 420px; animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .auth-brand { text-align: center; margin-bottom: 40px; }
        .auth-brand .logo { width: 56px; height: 56px; background: var(--primary); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px; font-weight: 700; color: #000; box-shadow: 0 8px 32px rgba(168, 255, 53, 0.25); }
        .auth-brand h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 8px; }
        .auth-brand p { color: var(--text-muted); font-size: 14px; }
        .auth-brand .badge { display: inline-block; background: var(--primary); color: #000; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-top: 8px; text-transform: uppercase; }
        .auth-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; box-shadow: var(--shadow); }
        .auth-header { margin-bottom: 32px; }
        .auth-header h2 { font-size: 22px; font-weight: 600; margin-bottom: 8px; }
        .auth-header p { color: var(--text-muted); font-size: 14px; }
        .auth-form { display: flex; flex-direction: column; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input { padding: 14px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 15px; transition: all 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-dim); }
        .form-group input::placeholder { color: var(--text-dim); }
        .btn-primary { padding: 14px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit; margin-top: 8px; }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(168, 255, 53, 0.3); }
        .auth-footer { text-align: center; margin-top: 24px; color: var(--text-muted); font-size: 14px; }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .auth-footer a:hover { color: var(--primary-hover); }
        .alert { padding: 14px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 20px; }
        .alert-error { background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.3); color: var(--danger); }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="logo">W</div>
            <h1>WowAds</h1>
            <p>Panel de Administración</p>
            <span class="badge">Admin</span>
        </div>
        
        <div class="auth-card">
            <div class="auth-header">
                <h2>Iniciar Sesión</h2>
                <p>Accede como administrador</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $baseUrl; ?>admin" class="auth-form" autocomplete="on">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@wowads.com" required autofocus autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_admin_token'] ?? ''); ?>">
                
                <button type="submit" class="btn-primary">
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="<?php echo $baseUrl; ?>">← Volver al login principal</a>
            </div>
        </div>
    </div>
</body>
</html>
