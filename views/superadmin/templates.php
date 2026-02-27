<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Plantillas de Campañas';
$currentPage = 'superadmin';

$flashSuccess = \Session::getFlash('success');
$flashError = \Session::getFlash('error');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantillas de Campañas - Super Admin - WowAds</title>
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
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .admin-header h1 { font-size: 24px; font-weight: 600; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .page-header h1 { font-size: 24px; font-weight: 600; }
        
        .templates-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        
        .template-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; transition: all 0.2s; }
        .template-card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .template-card.inactive { opacity: 0.6; }
        
        .template-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .template-name { font-size: 16px; font-weight: 600; }
        .template-status { font-size: 11px; padding: 4px 8px; border-radius: 12px; font-weight: 600; }
        .template-status.active { background: rgba(46, 213, 115, 0.15); color: var(--success); }
        .template-status.inactive { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }
        
        .template-desc { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5; }
        
        .template-configs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
        .config-badge { font-size: 10px; padding: 4px 8px; background: var(--bg-lighter); border-radius: 4px; color: var(--text-muted); }
        .config-badge.has-data { background: rgba(168, 255, 53, 0.15); color: var(--primary); }
        
        .template-actions { display: flex; gap: 8px; border-top: 1px solid var(--border); padding-top: 12px; }
        .template-actions .btn { flex: 1; padding: 8px 12px; font-size: 12px; text-align: center; border-radius: var(--radius-sm); cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-edit { background: var(--bg-lighter); border: 1px solid var(--border); color: var(--text); }
        .btn-edit:hover { border-color: var(--primary); }
        .btn-delete { background: transparent; border: 1px solid var(--danger); color: var(--danger); }
        .btn-delete:hover { background: rgba(255, 71, 87, 0.1); }
        
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 18px; margin-bottom: 8px; }
        .empty-state p { color: var(--text-muted); margin-bottom: 20px; }
        
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary-admin { padding: 12px 24px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        
        @media (max-width: 900px) {
            .templates-grid { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 600px) {
            .templates-grid { grid-template-columns: 1fr; }
            .main-admin { padding: 16px; }
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
                <h1>📋 Plantillas de Campañas</h1>
                <a href="<?php echo $baseUrl; ?>superadmin/templates/create" class="btn-primary-admin">+ Nueva Plantilla</a>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?php echo $flashSuccess; ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="alert alert-error"><?php echo $flashError; ?></div>
            <?php endif; ?>

            <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <h3>No hay plantillas</h3>
                    <p>Crea tu primera plantilla de campaña para los usuarios.</p>
                    <a href="<?php echo $baseUrl; ?>superadmin/templates/create" class="btn-primary-admin">Crear Plantilla</a>
                </div>
            <?php else: ?>
                <div class="templates-grid">
                    <?php foreach ($templates as $template): ?>
                    <div class="template-card <?php echo $template['is_active'] ? '' : 'inactive'; ?>">
                        <div class="template-header">
                            <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                            <div class="template-status <?php echo $template['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $template['is_active'] ? 'Activa' : 'Inactiva'; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($template['category'])): ?>
                        <div style="margin-bottom: 8px;">
                            <?php 
                            $catLabels = [
                                'sales' => '💰 Ventas',
                                'awareness' => '👁️ Reconocimiento',
                                'traffic' => '🚀 Tráfico',
                                'engagement' => '💬 Interacción',
                                'leads' => '📝 Leads',
                                'retargeting' => '🔄 Retargeting'
                            ];
                            $catLabel = $catLabels[$template['category']] ?? $template['category'];
                            ?>
                            <span style="font-size: 11px; padding: 3px 8px; background: var(--bg-lighter); border-radius: 4px; color: var(--primary);"><?php echo $catLabel; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($template['description']): ?>
                        <div class="template-desc"><?php echo htmlspecialchars($template['description']); ?></div>
                        <?php endif; ?>
                        
                        <div class="template-configs">
                            <div class="config-badge <?php echo !empty($template['campaign_config']) ? 'has-data' : ''; ?>">Campaña</div>
                            <div class="config-badge <?php echo !empty($template['adset_config']) ? 'has-data' : ''; ?>">AdSet</div>
                            <div class="config-badge <?php echo !empty($template['creative_config']) ? 'has-data' : ''; ?>">Creativo</div>
                            <div class="config-badge <?php echo !empty($template['ad_config']) ? 'has-data' : ''; ?>">Anuncio</div>
                        </div>
                        
                        <div class="template-actions">
                            <a href="<?php echo $baseUrl; ?>superadmin/templates/edit?id=<?php echo $template['id']; ?>" class="btn btn-edit">Editar</a>
                            <button class="btn btn-delete" onclick="toggleTemplate(<?php echo $template['id']; ?>)">
                                <?php echo $template['is_active'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
                            <button class="btn btn-delete" onclick="deleteTemplate(<?php echo $template['id']; ?>)">Eliminar</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function toggleTemplate(id) {
        if (!confirm('¿Cambiar estado de esta plantilla?')) return;
        
        fetch(BASE_URL + 'superadmin/templates/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error al cambiar estado');
            }
        });
    }

    function deleteTemplate(id) {
        if (!confirm('¿Eliminar esta plantilla?')) return;
        
        fetch(BASE_URL + 'superadmin/templates/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error al eliminar');
            }
        });
    }
    </script>
</body>
</html>
