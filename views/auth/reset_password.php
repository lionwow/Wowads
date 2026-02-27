<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '/wowads/';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - WowAds</title>
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
                <h2>Nueva Contraseña</h2>
                <p>Ingresa tu nueva contraseña</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <div class="auth-footer">
                    <a href="<?php echo $baseUrl; ?>">Ir al login</a>
                </div>
            <?php else: ?>
            
            <form method="POST" action="<?php echo $baseUrl; ?>auth/reset-password" class="auth-form">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                
                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••" 
                        required
                        minlength="6"
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="••••••••" 
                        required
                        minlength="6"
                    >
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_reset_token'] ?? ''); ?>">
                
                <button type="submit" class="btn-primary">
                    Cambiar Contraseña
                </button>
            </form>
            
            <?php endif; ?>
            
            <?php if (!isset($success)): ?>
            <div class="auth-footer">
                <a href="<?php echo $baseUrl; ?>">&larr; Volver al login</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
