<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = isset($template) ? 'Editar Plantilla' : 'Crear Plantilla';
$currentPage = 'superadmin';

$isEditing = isset($template) && !empty($template);

$campaignConfig = $isEditing ? htmlspecialchars($template['campaign_config']) : '{
  "status": "PAUSED",
  "bid_strategy": "LOWEST_COST_WITHOUT_CAP"
}';
$adsetConfig = $isEditing && !empty($template['adset_config']) ? htmlspecialchars($template['adset_config']) : '{
  "billing_event": "IMPRESSIONS",
  "optimization_goal": "LINK_CLICKS",
  "bid_strategy": "LOWEST_COST_WITHOUT_CAP",
  "status": "PAUSED"
}';
$creativeConfig = $isEditing && !empty($template['creative_config']) ? htmlspecialchars($template['creative_config']) : '
"call_to_action": {
  "type": "LEARN_MORE"
}

';
$adConfig = $isEditing && !empty($template['ad_config']) ? htmlspecialchars($template['ad_config']) : '{
  "status": "PAUSED"
}';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Super Admin - WowAds</title>
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
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 24px; font-weight: 600; }
        
        .form-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; }
        .form-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-group label span { color: var(--text-muted); font-weight: 400; }
        .form-control { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { font-family: monospace; font-size: 12px; min-height: 200px; resize: vertical; }
        
        .json-editor { position: relative; }
        .json-editor textarea { min-height: 280px; }
        .json-hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; }
        .json-valid { color: var(--success); }
        .json-invalid { color: var(--danger); }
        
        .form-row { display: flex; gap: 16px; }
        .form-row .form-group { flex: 1; }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input { width: 18px; height: 18px; }
        .checkbox-group label { margin: 0; font-weight: 400; }
        
        .section-tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 0; overflow-x: auto; }
        .section-tab { padding: 10px 16px; background: transparent; border: none; color: var(--text-muted); cursor: pointer; font-size: 13px; font-weight: 500; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all 0.2s; white-space: nowrap; }
        .section-tab:hover { color: var(--text); }
        .section-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary-admin:hover { background: #96E82E; }
        .btn-primary-admin:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-secondary-admin { padding: 12px 24px; background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary-admin:hover { border-color: var(--primary); }
        
        .actions { display: flex; gap: 12px; margin-top: 24px; }
        
        @media (max-width: 768px) {
            .main-admin { padding: 16px; margin-left: 0; }
            .superadmin-layout { flex-direction: column; }
            .sidebar-admin { position: relative; width: 100%; }
        }
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
                    <a href="<?php echo $baseUrl; ?>superadmin/templates" class="nav-item active">Plantillas Campañas</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/settings" class="nav-item">API & Conexiones</a>
                </div>
            </nav>
            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item">← Volver al Panel</a>
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="color: var(--danger);">Cerrar Sesión</a>
            </div>
        </aside>
        
        <main class="main-admin">
            <div class="page-header">
                <h1><?php echo $isEditing ? '✏️ Editar Plantilla' : '➕ Crear Plantilla'; ?></h1>
                <a href="<?php echo $baseUrl; ?>superadmin/templates" class="btn-secondary-admin">← Volver</a>
            </div>

            <form id="templateForm">
                <?php if ($isEditing): ?>
                <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                <?php endif; ?>
                
                <div class="form-card">
                    <div class="form-title">Información General</div>
                    
                    <div class="form-group">
                        <label>Nombre de la Plantilla *</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej: Campaña Conversiones - Prospección" value="<?php echo $isEditing ? htmlspecialchars($template['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="category" class="form-control">
                            <option value="">Seleccionar categoría...</option>
                            <option value="sales" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'sales') ? 'selected' : ''; ?>>💰 Ventas</option>
                            <option value="awareness" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'awareness') ? 'selected' : ''; ?>>👁️ Reconocimiento de Marca</option>
                            <option value="traffic" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'traffic') ? 'selected' : ''; ?>>🚀 Tráfico</option>
                            <option value="engagement" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'engagement') ? 'selected' : ''; ?>>💬 Interacción</option>
                            <option value="leads" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'leads') ? 'selected' : ''; ?>>📝 Captación de Leads</option>
                            <option value="retargeting" <?php echo ($isEditing && isset($template['category']) && $template['category'] === 'retargeting') ? 'selected' : ''; ?>>🔄 Retargeting</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" name="description" class="form-control" placeholder="Descripción breve de la plantilla" value="<?php echo $isEditing ? htmlspecialchars($template['description'] ?? '') : ''; ?>">
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="isActive" <?php echo ($isEditing && $template['is_active']) || !$isEditing ? 'checked' : ''; ?>>
                        <label for="isActive">Plantilla activa (disponible para usuarios)</label>
                    </div>
                </div>
                
                <div class="form-card">
                    <div class="form-title">Configuración JSON</div>
                    
                    <div class="section-tabs">
                        <button type="button" class="section-tab active" onclick="switchTab('campaign')">Campaña</button>
                        <button type="button" class="section-tab" onclick="switchTab('adset')">AdSet</button>
                        <button type="button" class="section-tab" onclick="switchTab('creative')">Creativo</button>
                        <button type="button" class="section-tab" onclick="switchTab('ad')">Anuncio</button>
                    </div>
                    
                    <div id="tab-campaign" class="tab-content active">
                        <div class="form-group">
                            <label>Configuración de Campaña (JSON) *</label>
                            <div class="json-editor">
                                <textarea name="campaign_config" id="campaignConfig" required><?php echo $campaignConfig; ?></textarea>
                            </div>
                            <div class="json-hint" id="campaignHint">
                                <strong>Valores válidos para "objective":</strong> OUTCOME_CONVERSIONS, OUTCOME_TRAFFIC, OUTCOME_LEADS, OUTCOME_SALES, OUTCOME_AWARENESS, OUTCOME_ENGAGEMENT<br>
                                <strong>Valores válidos para "bid_strategy":</strong> LOWEST_COST_WITHOUT_CAP, LOWEST_COST_WITH_BID_CAP, COST_CAP, LOWEST_COST_WITH_MIN_ROAS<br>
                                <strong>Ejemplo Campaign:</strong> {"objective": "OUTCOME_CONVERSIONS", "bid_strategy": "LOWEST_COST_WITHOUT_CAP", "special_ad_categories": []}
                            </div>
                        </div>
                    </div>
                    
                    <div id="tab-adset" class="tab-content">
                        <div class="form-group">
                            <label>Configuración de AdSet (JSON)</label>
                            <div class="json-editor">
                                <textarea name="adset_config" id="adsetConfig"><?php echo $adsetConfig; ?></textarea>
                            </div>
                            <div class="json-hint" id="adsetHint">
                                <strong>Valores válidos para "bid_strategy":</strong> LOWEST_COST_WITHOUT_CAP, LOWEST_COST_WITH_BID_CAP, COST_CAP, LOWEST_COST_WITH_MIN_ROAS<br>
                                <strong>Valores válidos para "billing_event":</strong> IMPRESSIONS, CLICKS, LINK_CLICKS, VIDEO_VIEWS, APP_INSTALLS, LANDING_PAGE_VIEWS, PURCHASES<br>
                                <strong>Valores válidos para "optimization_goal":</strong> NONE, APP_INSTALLS, AD_RECALL_LIFT, ENGAGED_USERS, EVENT_RESPONSES, IMPRESSIONS, LEAD_GENERATION, QUALITY_LEAD, LINK_CLICKS, OFFSITE_CONVERSIONS, PAGE_LIKES, POST_ENGAGEMENT, QUALITY_CALL, REACH, LANDING_PAGE_VIEWS, VISIT_INSTAGRAM_PROFILE, ENGAGED_PAGE_VIEWS, VALUE, THRUPLAY, DERIVED_EVENTS, APP_INSTALLS_AND_OFFSITE_CONVERSIONS, CONVERSATIONS, IN_APP_VALUE, MESSAGING_PURCHASE_CONVERSION, SUBSCRIBERS, REMINDERS_SET, MEANINGFUL_CALL_ATTEMPT, PROFILE_VISIT, PROFILE_AND_PAGE_ENGAGEMENT, ADVERTISER_SILOED_VALUE, AUTOMATIC_OBJECTIVE, MESSAGING_APPOINTMENT_CONVERSION<br>
                                <strong>Valores válidos para "destination_type":</strong> WEBSITE, APP, MESSAGING, WHATSAPP, FACEBOOK_MESSAGES, ONECLINE_SHOP, SHOP<br>
                                <strong>Ejemplo AdSet:</strong> {"daily_budget": "2000", "billing_event": "IMPRESSIONS", "optimization_goal": "CONVERSATIONS", "bid_strategy": "LOWEST_COST_WITHOUT_CAP", "destination_type": "WEBSITE", "targeting": {"age_min": 25, "age_max": 45}}
                            </div>
                        </div>
                    </div>
                    
                    <div id="tab-creative" class="tab-content">
                        <div class="form-group">
                            <label>Configuración de Creativo (JSON)</label>
                            <div class="json-editor">
                                <textarea name="creative_config" id="creativeConfig"><?php echo $creativeConfig; ?></textarea>
                            </div>
                            <div class="json-hint" id="creativeHint">
                                <strong>Parámetros de Creative:</strong> page_id, message, link, caption, call_to_action_type<br>
                                <strong>Valores para "call_to_action_type":</strong> SHOP_NOW, LEARN_MORE, SIGN_UP, GET_OFFER, BOOK_NOW, WHATSAPP_MESSAGE, MESSAGE_PAGE<br>
                                <strong>Ejemplo Creative:</strong> {"page_id": "123456789", "message": "Descubre nuestros productos", "link": "https://tusitio.com", "caption": "wowads.com", "call_to_action_type": "SHOP_NOW"}
                            </div>
                        </div>
                    </div>
                    
                    <div id="tab-ad" class="tab-content">
                        <div class="form-group">
                            <label>Configuración de Anuncio (JSON)</label>
                            <div class="json-editor">
                                <textarea name="ad_config" id="adConfig"><?php echo $adConfig; ?></textarea>
                            </div>
                            <div class="json-hint" id="adHint">
                                <strong>Parámetros de Ad:</strong> status (PAUSED, ACTIVE)<br>
                                <strong>Ejemplo Ad:</strong> {"status": "PAUSED"}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn-primary-admin" id="submitBtn"><?php echo $isEditing ? 'Actualizar' : 'Crear'; ?> Plantilla</button>
                    <a href="<?php echo $baseUrl; ?>superadmin/templates" class="btn-secondary-admin">Cancelar</a>
                </div>
            </form>
        </main>
    </div>

    <script>
    const BASE_URL = '<?php echo $baseUrl; ?>';
    const isEditing = <?php echo $isEditing ? 'true' : 'false'; ?>;
    const btnText = isEditing ? 'Actualizar Plantilla' : 'Crear Plantilla';
    
    function switchTab(tab) {
        document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        document.querySelector('.section-tab[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function validateJson(textarea, hintId) {
        const hint = document.getElementById(hintId);
        try {
            JSON.parse(textarea.value);
            hint.textContent = '✓ JSON válido';
            hint.className = 'json-hint json-valid';
            return true;
        } catch (e) {
            hint.textContent = '✗ JSON inválido: ' + e.message;
            hint.className = 'json-hint json-invalid';
            return false;
        }
    }

    document.getElementById('campaignConfig').addEventListener('blur', function() {
        validateJson(this, 'campaignHint');
    });

    document.getElementById('adsetConfig').addEventListener('blur', function() {
        if (this.value.trim()) validateJson(this, 'adsetHint');
    });

    document.getElementById('creativeConfig').addEventListener('blur', function() {
        if (this.value.trim()) validateJson(this, 'creativeHint');
    });

    document.getElementById('adConfig').addEventListener('blur', function() {
        if (this.value.trim()) validateJson(this, 'adHint');
    });

    document.getElementById('templateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Guardando...';
        
        const campaignJson = document.getElementById('campaignConfig').value;
        try {
            JSON.parse(campaignJson);
        } catch (e) {
            alert('La configuración de campaña tiene JSON inválido');
            submitBtn.disabled = false;
            submitBtn.textContent = btnText;
            return;
        }
        
        const formData = new FormData(this);
        
        const url = BASE_URL + 'superadmin/templates/' + (isEditing ? 'update' : 'store');
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = BASE_URL + 'superadmin/templates';
            } else {
                alert(data.error || 'Error al guardar');
                submitBtn.disabled = false;
                submitBtn.textContent = btnText;
            }
        })
        .catch(err => {
            alert('Error al guardar');
            submitBtn.disabled = false;
            submitBtn.textContent = btnText;
        });
    });
    </script>
</body>
</html>
