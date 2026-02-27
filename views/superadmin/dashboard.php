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
    <title>Super Admin - WowAds</title>
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
        .admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .admin-stat { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
        .admin-stat .label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
        .admin-stat .value { font-size: 28px; font-weight: 700; }
        .admin-stat .value.primary { color: var(--primary); }
        .admin-table { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .admin-table table { width: 100%; border-collapse: collapse; }
        .admin-table th { text-align: left; padding: 16px; background: var(--bg-light); font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
        .admin-table td { padding: 16px; border-bottom: 1px solid var(--border); }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background: var(--bg-lighter); }
        .admin-table .actions { display: flex; gap: 8px; }
        .admin-table .btn-action { padding: 6px 12px; border-radius: var(--radius-xs); font-size: 12px; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .admin-table .btn-view { background: var(--primary-dim); color: var(--primary); }
        .admin-table .btn-edit { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
        .admin-table .btn-delete { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .admin-table .btn-suspend { background: rgba(136, 136, 136, 0.1); color: var(--text-muted); }
        .admin-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 20px; }
        .admin-card h3 { font-size: 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .form-group-admin { margin-bottom: 20px; }
        .form-group-admin label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px; }
        .form-group-admin input, .form-group-admin select, .form-group-admin textarea { width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; }
        .form-group-admin input:focus, .form-group-admin select:focus, .form-group-admin textarea:focus { outline: none; border-color: var(--primary); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .status-badge { display: inline-flex; padding: 4px 10px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 500; }
        .status-active { background: rgba(46, 213, 115, 0.1); color: var(--success); }
        .status-suspended { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
        .status-inactive { background: rgba(136, 136, 136, 0.1); color: var(--text-muted); }
        .status-pending { background: rgba(255, 165, 2, 0.1); color: var(--warning); }
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
                    <a href="<?php echo $baseUrl; ?>superadmin" class="nav-item active">Dashboard</a>
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
                    <a href="<?php echo $baseUrl; ?>superadmin/templates" class="nav-item">Plantillas Campañas</a>
                    <a href="<?php echo $baseUrl; ?>superadmin/settings" class="nav-item">API & Conexiones</a>
                </div>
            </nav>
            <div style="padding: 16px; border-top: 1px solid var(--border);">
                <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item">← Volver al Panel</a>
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="color: var(--danger);">Cerrar Sesión</a>
            </div>
        </aside>
        
        <main class="main-admin">
            <div class="admin-header">
                <h1>Dashboard Super Admin</h1>
            </div>
            
            <div class="admin-stats">
                <div class="admin-stat">
                    <div class="label">Total Usuarios</div>
                    <div class="value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                </div>
                <div class="admin-stat">
                    <div class="label">Agencias Activas</div>
                    <div class="value"><?php echo number_format($stats['total_agencies_active'] ?? 0); ?></div>
                </div>
                <div class="admin-stat">
                    <div class="label">Total Agencias</div>
                    <div class="value primary"><?php echo number_format($stats['total_agencies'] ?? 0); ?></div>
                </div>
                <div class="admin-stat">
                    <div class="label">Usuarios Activos</div>
                    <div class="value"><?php echo number_format($stats['active_users'] ?? 0); ?></div>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>📊 Actividad Reciente</h3>
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Juan Pérez</td>
                                <td>Creación de campaña</td>
                                <td>Hace 2 horas</td>
                                <td><span class="status-badge status-active">Completado</span></td>
                            </tr>
                            <tr>
                                <td>Agencia Digital</td>
                                <td>Compra de créditos</td>
                                <td>Hace 5 horas</td>
                                <td><span class="status-badge status-active">Completado</span></td>
                            </tr>
                            <tr>
                                <td>María García</td>
                                <td>Generación de imagen</td>
                                <td>Hace 8 horas</td>
                                <td><span class="status-badge status-active">Completado</span></td>
                            </tr>
                            <tr>
                                <td>Carlos López</td>
                                <td>Registro</td>
                                <td>Ayer</td>
                                <td><span class="status-badge status-pending">Pendiente</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="form-row">
                <div class="admin-card">
                    <h3>🔗 Conexiones API</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>OpenAI</span>
                            <span class="status-badge status-active">Conectado</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>Meta Ads API</span>
                            <span class="status-badge status-active">Conectado</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>Stripe</span>
                            <span class="status-badge status-suspended">Desconectado</span>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h3>📈 Planes Activos</h3>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>Básico</span>
                            <span>450 usuarios</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>Pro</span>
                            <span>320 usuarios</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg); border-radius: var(--radius-sm);">
                            <span>Enterprise</span>
                            <span>89 usuarios</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
