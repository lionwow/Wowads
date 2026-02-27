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
    <title>Detalle de Usuario - Super Admin - WowAds</title>
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
        .sidebar-admin .nav-section { margin-bottom: 20px; }
        .sidebar-admin .nav-section-title { font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; padding: 8px 16px; }
        .main-admin { flex: 1; margin-left: 260px; padding: 32px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .admin-header h1 { font-size: 24px; font-weight: 600; }
        .back-link { color: var(--text-muted); text-decoration: none; display: flex; align-items: center; gap: 8px; margin-bottom: 24px; }
        .back-link:hover { color: var(--primary); }
        .profile-header { display: flex; gap: 24px; margin-bottom: 32px; }
        .profile-avatar { width: 100px; height: 100px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 700; color: #000; flex-shrink: 0; }
        .profile-info h2 { font-size: 24px; margin-bottom: 4px; }
        .profile-info .email { color: var(--text-muted); margin-bottom: 12px; }
        .profile-info .badges { display: flex; gap: 8px; }
        .status-badge { display: inline-flex; padding: 4px 12px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 500; }
        .status-active { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        .status-suspended { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .profile-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
        .profile-card h3 { font-size: 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: var(--text-muted); font-size: 14px; }
        .info-row .value { font-weight: 500; }
        .activity-list { display: flex; flex-direction: column; }
        .activity-item { display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--border); }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 40px; height: 40px; background: var(--bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .activity-content { flex: 1; }
        .activity-content .text { font-size: 14px; margin-bottom: 4px; }
        .activity-content .time { font-size: 12px; color: var(--text-muted); }
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-danger-admin { padding: 12px 24px; background: rgba(255, 71, 87, 0.1); color: var(--danger); border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
        .action-buttons { display: flex; gap: 12px; margin-top: 24px; }
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
                    <a href="<?php echo $baseUrl; ?>superadmin/users" class="nav-item active">Usuarios</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/agencies" class="nav-item">Agencias</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Configuración</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/plans" class="nav-item">Planes</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/settings" class="nav-item">API & Conexiones</a>
                </div>
            </nav>
            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item">← Volver al Panel</a>
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="color: var(--danger);">Cerrar Sesión</a>
            </div>
        </aside>
        
        <main class="main-admin">
            <a href="<?php echo $baseUrl; ?>superadmin/users" class="back-link">← Volver a Usuarios</a>
            
            <div class="profile-header">
                <div class="profile-avatar">JP</div>
                <div class="profile-info">
                    <h2>Juan Pérez</h2>
                    <div class="email">juan@email.com</div>
                    <div class="badges">
                        <span class="status-badge status-active">Activo</span>
                        <span class="status-badge" style="background: var(--primary-dim); color: var(--primary);">Cliente</span>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button class="btn-primary-admin">Editar Usuario</button>
                <button class="btn-danger-admin">Suspender Usuario</button>
            </div>
            
            <div class="profile-grid" style="margin-top: 32px;">
                <div class="profile-card">
                    <h3>📋 Información General</h3>
                    <div class="info-row">
                        <span class="label">ID</span>
                        <span class="value">#001</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Nombre completo</span>
                        <span class="value">Juan Pérez</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email</span>
                        <span class="value">juan@email.com</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Teléfono</span>
                        <span class="value">+51 999 999 999</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Fecha de registro</span>
                        <span class="value">15 Ene 2024</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Último acceso</span>
                        <span class="value">Hace 2 horas</span>
                    </div>
                </div>
                
                <div class="profile-card">
                    <h3>💰 Créditos y Facturación</h3>
                    <div class="info-row">
                        <span class="label">Plan actual</span>
                        <span class="value">Pro</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Créditos disponibles</span>
                        <span class="value" style="color: var(--primary);">150</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Créditos consumidos (mes)</span>
                        <span class="value">350</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Gasto del mes</span>
                        <span class="value">$79.00</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Total gastado</span>
                        <span class="value">$1,580.00</span>
                    </div>
                </div>
                
                <div class="profile-card">
                    <h3>📊 Estadísticas</h3>
                    <div class="info-row">
                        <span class="label">Campañas creadas</span>
                        <span class="value">12</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Anuncios activos</span>
                        <span class="value">8</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Imágenes generadas</span>
                        <span class="value">245</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Copies generados</span>
                        <span class="value">89</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Videos generados</span>
                        <span class="value">15</span>
                    </div>
                </div>
                
                <div class="profile-card" style="grid-column: 1 / -1;">
                    <h3>📝 Actividad Reciente</h3>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(168, 255, 53, 0.1); color: var(--primary);">🖼</div>
                            <div class="activity-content">
                                <div class="text">Generó una imagen para "Campaña Verano"</div>
                                <div class="time">Hace 2 horas</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(46, 213, 115, 0.1); color: var(--success);">📝</div>
                            <div class="activity-content">
                                <div class="text">Creó copia publicitaria para "Promo Invierno"</div>
                                <div class="time">Hace 5 horas</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(255, 165, 2, 0.1); color: var(--warning);">📺</div>
                            <div class="activity-content">
                                <div class="text">Generó video para "Nuevo Producto"</div>
                                <div class="time">Ayer</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(255, 71, 87, 0.1); color: var(--danger);">💳</div>
                            <div class="activity-content">
                                <div class="text">Compró 500 créditos - Plan Pro</div>
                                <div class="time">Hace 3 días</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
