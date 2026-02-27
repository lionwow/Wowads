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
    <title>Crear Cuenta - WowAds</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
    <style>
        .type-selector { display: flex; gap: 12px; margin-bottom: 8px; }
        .type-option { flex: 1; }
        .type-option input { display: none; }
        .type-option label { 
            display: block; padding: 14px 16px; background: var(--bg); border: 1px solid var(--border); 
            border-radius: var(--radius-sm); cursor: pointer; text-align: center; transition: all 0.2s;
        }
        .type-option label:hover { border-color: var(--border-light); }
        .type-option input:checked + label { 
            border-color: var(--primary); background: var(--primary-dim); color: var(--primary); 
        }
        .type-option .type-title { font-weight: 600; display: block; margin-bottom: 4px; }
        .type-option .type-desc { font-size: 12px; opacity: 0.7; }
        .agency-fields { display: none; }
        .agency-fields.active { display: block; }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="logo">W</div>
            <h1>WowAds</h1>
            <p>Crea tu cuenta gratis</p>
        </div>
        
        <div class="auth-card">
            <div class="auth-header">
                <h2>Registro</h2>
                <p>Comienza a crear campañas con IA</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo $baseUrl; ?>auth/register" class="auth-form" autocomplete="on">
                <div class="form-group">
                    <label>Tipo de cuenta</label>
                    <div class="type-selector">
                        <div class="type-option">
                            <input type="radio" id="type_client" name="type" value="client" checked>
                            <label for="type_client">
                                <span class="type-title">Personal</span>
                                <span class="type-desc">Para uso propio</span>
                            </label>
                        </div>
                        <div class="type-option">
                            <input type="radio" id="type_agency" name="type" value="agency">
                            <label for="type_agency">
                                <span class="type-title">Agencia</span>
                                <span class="type-desc">Multi-clientes</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">Nombre completo</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        placeholder="Juan Pérez" 
                        required 
                        autofocus
                        autocomplete="name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="tu@email.com" 
                        required 
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="phone">Teléfono (opcional)</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="+51 999 999 999" 
                        autocomplete="tel"
                    >
                </div>
                
                <div class="agency-fields" id="agencyFields">
                    <div class="form-group">
                        <label for="agency_name">Nombre de la Agencia *</label>
                        <input 
                            type="text" 
                            id="agency_name" 
                            name="agency_name" 
                            placeholder="Mi Agencia Digital"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="agency_website">Página web de la Agencia</label>
                        <input 
                            type="url" 
                            id="agency_website" 
                            name="agency_website" 
                            placeholder="https://miagencia.com"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Mínimo 6 caracteres" 
                        required
                        autocomplete="new-password"
                        minlength="6"
                    >
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_register_token'] ?? ''); ?>">
                
                <button type="submit" class="btn-primary">
                    Crear Cuenta
                </button>
            </form>
            
            <div class="auth-footer">
                ¿Ya tienes cuenta? <a href="<?php echo $baseUrl; ?>">Iniciar sesión</a>
            </div>
        </div>
    </div>
    
    <script>
        const typeClient = document.getElementById('type_client');
        const typeAgency = document.getElementById('type_agency');
        const agencyFields = document.getElementById('agencyFields');
        const agencyNameInput = document.getElementById('agency_name');
        
        function toggleAgencyFields() {
            if (typeAgency.checked) {
                agencyFields.classList.add('active');
                agencyNameInput.setAttribute('required', 'required');
            } else {
                agencyFields.classList.remove('active');
                agencyNameInput.removeAttribute('required');
            }
        }
        
        typeClient.addEventListener('change', toggleAgencyFields);
        typeAgency.addEventListener('change', toggleAgencyFields);
    </script>
</body>
</html>
