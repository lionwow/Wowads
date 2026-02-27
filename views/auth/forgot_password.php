<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '/wowads/';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - WowAds</title>
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
                <h2>Recuperar Contraseña</h2>
                <p>Ingresa tu correo electrónico</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $baseUrl; ?>auth/forgot-password" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="tu@email.com" 
                        required 
                        autofocus
                    >
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_forgot_token'] ?? ''); ?>">
                
                <button type="submit" class="btn-primary">
                    Enviar Enlace de Recuperación
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="<?php echo $baseUrl; ?>">&larr; Volver al login</a>
            </div>
        </div>
    </div>
</body>
</html>
