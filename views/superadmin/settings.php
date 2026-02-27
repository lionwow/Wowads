<?php
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
    <title>API y Conexiones - Super Admin - WowAds</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
    <style>
        .superadmin-layout { display: flex; min-height: 100vh; }
        .sidebar-admin { width: 260px; background: var(--bg-light); border-right: 1px solid var(--border); position: fixed; top: 0; left: 0; bottom: 0; display: flex; flex-direction: column; }
        .sidebar-admin .brand { padding: 24px; border-bottom: 1px solid var(--border); }
        .sidebar-admin .brand h2 { font-size: 18px; color: var(--primary); }
        .sidebar-admin .brand span { font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px; }
        .sidebar-admin .nav { flex: 1; padding: 16px 12px; }
        .sidebar-admin .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-muted); text-decoration: none; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 4px; transition: all 0.15s; }
        .sidebar-admin .nav-item:hover, .sidebar-admin .nav-item.active { background: var(--bg-lighter); color: var(--text); }
        .sidebar-admin .nav-item.active { border-left: 3px solid var(--primary); }
        .sidebar-admin .nav-section { margin-bottom: 20px; }
        .sidebar-admin .nav-section-title { font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; padding: 8px 16px; }
        .main-admin { flex: 1; margin-left: 260px; padding: 32px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .admin-header h1 { font-size: 24px; font-weight: 600; }
        .settings-section { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; }
        .settings-section h3 { font-size: 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .settings-section .status { margin-left: auto; padding: 4px 12px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 500; }
        .status-connected { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        .status-disconnected { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .form-group-admin { margin-bottom: 20px; }
        .form-group-admin label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px; }
        .form-group-admin input, .form-group-admin textarea { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; box-sizing: border-box; }
        .form-group-admin input:focus, .form-group-admin textarea:focus { outline: none; border-color: var(--primary); }
        .form-group-admin textarea { min-height: 100px; font-family: monospace; }
        .form-group-admin small { display: block; margin-top: 6px; font-size: 12px; color: var(--text-muted); }
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; margin-right: 12px; }
        .btn-secondary-admin { padding: 12px 24px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-primary-admin:hover { background: #96E82E; }
        .btn-secondary-admin:hover { border-color: var(--primary); }
        .connection-test { display: flex; align-items: center; gap: 12px; margin-top: 16px; padding: 12px; background: var(--bg); border-radius: var(--radius-sm); }
        .connection-test .indicator { width: 10px; height: 10px; border-radius: 50%; }
        .connection-test .indicator.ok { background: var(--success); }
        .connection-test .indicator.error { background: var(--danger); }
        .alert { padding: 14px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 20px; }
        .alert-success { background: rgba(46, 213, 115, 0.1); border: 1px solid rgba(46, 213, 115, 0.3); color: var(--success); }
        .alert-error { background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.3); color: var(--danger); }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="superadmin-layout">
        <aside class="sidebar-admin">
            <div class="brand">
                <h2>WowAds</h2>
                <span>Super Admin</span>
            </div>
            <nav class="nav">
                <div class="nav-section">
                    <div class="nav-section-title">General</div>
                    <a href="<?php echo $baseUrl; ?>superadmin" class="nav-item">Dashboard</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/reports" class="nav-item">Informes</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Gestión</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/users" class="nav-item">Usuarios</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/agencies" class="nav-item">Agencias</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Configuración</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/plans" class="nav-item">Planes</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/settings" class="nav-item active">API & Conexiones</a>
                </div>
            </nav>
            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item">← Volver al Panel</a>
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="color: var(--danger);">Cerrar Sesión</a>
            </div>
        </aside>
        
        <main class="main-admin">
            <div class="admin-header">
                <h1>API y Conexiones</h1>
            </div>
            
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?php echo $flashSuccess; ?></div>
            <?php endif; ?>
            
            <?php if ($flashError): ?>
                <div class="alert alert-error"><?php echo $flashError; ?></div>
            <?php endif; ?>
            
            <!-- OpenAI -->
            <div class="settings-section">
                <h3>
                    🤖 OpenAI (DALL-E / GPT / Whisper)
                    <span class="status <?php echo $openai['status'] === 'connected' ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $openai['status'] === 'connected' ? 'Conectado' : 'Desconectado'; ?>
                    </span>
                </h3>
                <form method="POST" action="<?php echo $baseUrl; ?>superadmin/settings-save">
                    <input type="hidden" name="type" value="openai">
                    <div class="form-group-admin">
                        <label>API Key</label>
                        <input type="password" name="api_key" value="<?php echo htmlspecialchars($openai['api_key']); ?>" placeholder="sk-..." style="width: 100%;">
                        <small>Obtén tu API key en platform.openai.com</small>
                    </div>
                    <div class="form-group-admin">
                        <label>Modelo para Imágenes (DALL-E)</label>
                        <select name="image_model" style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 4px; color: var(--text);">
                            <option value="dall-e-3" <?php echo ($openai['image_model'] ?? 'dall-e-3') === 'dall-e-3' ? 'selected' : ''; ?>>DALL-E 3 (Recomendado)</option>
                            <option value="dall-e-2" <?php echo ($openai['image_model'] ?? '') === 'dall-e-2' ? 'selected' : ''; ?>>DALL-E 2</option>
                        </select>
                    </div>
                    <div class="form-group-admin">
                        <label>Modelo para Texto (GPT)</label>
                        <select name="gpt_model" style="width: 100%; padding: 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 4px; color: var(--text);">
                            <option value="gpt-4o" <?php echo ($openai['gpt_model'] ?? 'gpt-4o') === 'gpt-4o' ? 'selected' : ''; ?>>GPT-4o (Recomendado)</option>
                            <option value="gpt-4-turbo" <?php echo ($openai['gpt_model'] ?? '') === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                            <option value="gpt-4" <?php echo ($openai['gpt_model'] ?? '') === 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                            <option value="gpt-3.5-turbo" <?php echo ($openai['gpt_model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                        </select>
                    </div>
                    <div class="connection-test" id="openai-test">
                        <div class="indicator <?php echo $openai['status'] === 'connected' ? 'ok' : 'error'; ?>"></div>
                        <span>
                            <?php if ($openai['status'] === 'connected' && $openai['last_test']): ?>
                                Última conexión: <?php echo $openai['last_test']; ?>
                            <?php else: ?>
                                No configurado
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary-admin">Guardar</button>
                        <button type="button" class="btn-secondary-admin" onclick="testConnection('openai')">Probar Conexión</button>
                    </div>
                </form>
            </div>
            
            <!-- Briefing Prompt -->
            <div class="settings-section">
                <h3>
                    📝 Prompt para Generación de ADN de Marca
                </h3>
                <form method="POST" action="<?php echo $baseUrl; ?>superadmin/settings-save">
                    <input type="hidden" name="type" value="briefing_prompt">
                    <div class="form-group-admin">
                        <label>Prompt para generar briefing de marca</label>
                        <textarea name="briefing_prompt" rows="12" style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 13px; font-family: monospace;"><?php echo htmlspecialchars($briefingPrompt); ?></textarea>
                        <small>Usa {description} para insertar la descripción del negocio. Este prompt se usará con GPT-4 para generar el briefing.</small>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary-admin">Guardar Prompt</button>
                    </div>
                </form>
            </div>
            
            <!-- Meta Ads -->
            <div class="settings-section">
                <h3>
                    📘 Meta Ads API (Facebook/Instagram)
                    <span class="status <?php echo $metaApi['status'] === 'connected' ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $metaApi['status'] === 'connected' ? 'Conectado' : 'Desconectado'; ?>
                    </span>
                </h3>
                <form method="POST" action="<?php echo $baseUrl; ?>superadmin/settings-save">
                    <input type="hidden" name="type" value="meta">
                    <div class="form-group-admin">
                        <label>App ID</label>
                        <input type="text" name="app_id" value="<?php echo htmlspecialchars($metaApi['app_id']); ?>" placeholder="Ingresa tu App ID de Meta">
                    </div>
                    <div class="form-group-admin">
                        <label>App Secret</label>
                        <input type="password" name="app_secret" value="<?php echo htmlspecialchars($metaApi['app_secret']); ?>" placeholder="Ingresa tu App Secret">
                    </div>
                    <div class="form-group-admin">
                        <label>Access Token</label>
                        <input type="password" name="access_token" value="<?php echo htmlspecialchars($metaApi['access_token']); ?>" placeholder="Ingresa tu Access Token">
                        <small>Token de acceso de larga duración para la API de Meta Ads</small>
                    </div>
                    <div class="form-group-admin">
                        <label>Ad Account ID</label>
                        <input type="text" name="ad_account_id" value="<?php echo htmlspecialchars($metaApi['ad_account_id']); ?>" placeholder="act_123456789">
                        <small>ID de la cuenta publicitaria de WowAds</small>
                    </div>
                    <div class="connection-test" id="meta-test">
                        <div class="indicator <?php echo $metaApi['status'] === 'connected' ? 'ok' : 'error'; ?>"></div>
                        <span>
                            <?php if ($metaApi['status'] === 'connected' && $metaApi['last_test']): ?>
                                Última conexión: <?php echo $metaApi['last_test']; ?>
                            <?php else: ?>
                                No configurado
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary-admin">Guardar</button>
                        <button type="button" class="btn-secondary-admin" onclick="testConnection('meta')">Probar Conexión</button>
                    </div>
                </form>
            </div>
            
            <!-- Stripe -->
            <div class="settings-section">
                <h3>
                    💳 Pasarela de Pago (Stripe)
                    <span class="status <?php echo $stripe['status'] === 'connected' ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $stripe['status'] === 'connected' ? 'Conectado' : 'Desconectado'; ?>
                    </span>
                </h3>
                <form method="POST" action="<?php echo $baseUrl; ?>superadmin/settings-save">
                    <input type="hidden" name="type" value="stripe">
                    <div class="form-group-admin">
                        <label>Publishable Key</label>
                        <input type="text" name="publishable_key" value="<?php echo htmlspecialchars($stripe['publishable_key']); ?>" placeholder="pk_live_...">
                    </div>
                    <div class="form-group-admin">
                        <label>Secret Key</label>
                        <input type="password" name="secret_key" value="<?php echo htmlspecialchars($stripe['secret_key']); ?>" placeholder="sk_live_...">
                    </div>
                    <div class="form-group-admin">
                        <label>Webhook Secret</label>
                        <input type="password" name="webhook_secret" value="<?php echo htmlspecialchars($stripe['webhook_secret']); ?>" placeholder="whsec_...">
                    </div>
                    <div class="connection-test">
                        <div class="indicator <?php echo !empty($stripe['secret_key']) ? 'ok' : 'error'; ?>"></div>
                        <span><?php echo !empty($stripe['secret_key']) ? 'Configurado' : 'No configurado'; ?></span>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary-admin">Guardar</button>
                    </div>
                </form>
            </div>
            
            <!-- Unsplash -->
            <div class="settings-section">
                <h3>
                    🖼️ Proveedor de Imágenes (Unsplash)
                    <span class="status <?php echo $unsplash['status'] === 'connected' ? 'status-connected' : 'status-disconnected'; ?>">
                        <?php echo $unsplash['status'] === 'connected' ? 'Conectado' : 'Desconectado'; ?>
                    </span>
                </h3>
                <form method="POST" action="<?php echo $baseUrl; ?>superadmin/settings-save">
                    <input type="hidden" name="type" value="unsplash">
                    <div class="form-group-admin">
                        <label>API Key</label>
                        <input type="password" name="api_key" value="<?php echo htmlspecialchars($unsplash['api_key']); ?>" placeholder="Ingresa tu API Key de Unsplash">
                    </div>
                    <div class="connection-test" id="unsplash-test">
                        <div class="indicator <?php echo $unsplash['status'] === 'connected' ? 'ok' : 'error'; ?>"></div>
                        <span>
                            <?php if ($unsplash['status'] === 'connected' && $unsplash['last_test']): ?>
                                Última conexión: <?php echo $unsplash['last_test']; ?>
                            <?php else: ?>
                                No configurado
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary-admin">Guardar</button>
                        <button type="button" class="btn-secondary-admin" onclick="testConnection('unsplash')">Probar Conexión</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <style>
        .popup-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
        .popup-overlay.active { display: flex; }
        .popup-box { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; text-align: center; max-width: 400px; }
        .popup-box .icon { font-size: 48px; margin-bottom: 16px; }
        .popup-box h3 { font-size: 20px; margin-bottom: 12px; }
        .popup-box p { color: var(--text-muted); margin-bottom: 24px; }
        .popup-box button { padding: 12px 32px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
    </style>
    
    <div class="popup-overlay" id="popup">
        <div class="popup-box">
            <div class="icon" id="popupIcon">✅</div>
            <h3 id="popupTitle">Guardado</h3>
            <p id="popupMessage">Los cambios se han guardado correctamente.</p>
            <button onclick="closePopup()">Aceptar</button>
        </div>
    </div>
    
    <script>
        function showPopup(success, message) {
            const popup = document.getElementById('popup');
            const icon = document.getElementById('popupIcon');
            const title = document.getElementById('popupTitle');
            const msg = document.getElementById('popupMessage');
            
            if (success) {
                icon.textContent = '✅';
                title.textContent = 'Guardado';
            } else {
                icon.textContent = '❌';
                title.textContent = 'Error';
            }
            
            msg.textContent = message || (success ? 'Los cambios se han guardado correctamente.' : 'Ha ocurrido un error.');
            popup.classList.add('active');
        }
        
        function closePopup() {
            document.getElementById('popup').classList.remove('active');
            location.reload();
        }
        
        function testConnection(type) {
            const btn = event.target;
            btn.textContent = 'Probando...';
            btn.disabled = true;
            
            fetch('<?php echo $baseUrl; ?>superadmin/settings-test?type=' + type, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    showPopup(data.success, data.message);
                })
                .catch(error => {
                    showPopup(false, 'Error al probar conexión: ' + error.message);
                })
                .finally(() => {
                    btn.textContent = 'Probar Conexión';
                    btn.disabled = false;
                });
        }
        
        // Handle form submissions via AJAX
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.textContent;
                btn.textContent = 'Guardando...';
                btn.disabled = true;
                
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    showPopup(true, 'Las credenciales se han guardado correctamente.');
                })
                .catch(error => {
                    showPopup(false, 'Error al guardar: ' + error.message);
                })
                .finally(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
