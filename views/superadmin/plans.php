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
    <title>Planes - Super Admin - WowAds</title>
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
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .plan-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: relative; }
        .plan-card.featured { border-color: var(--primary); box-shadow: 0 0 20px rgba(168, 255, 53, 0.1); }
        .plan-card .badge { position: absolute; top: -12px; right: 20px; background: var(--primary); color: #000; padding: 4px 12px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 600; }
        .plan-card h3 { font-size: 20px; margin-bottom: 8px; }
        .plan-card .price { font-size: 36px; font-weight: 700; margin-bottom: 4px; }
        .plan-card .price span { font-size: 14px; color: var(--text-muted); font-weight: 400; }
        .plan-card .description { color: var(--text-muted); margin-bottom: 20px; font-size: 14px; }
        .plan-features { list-style: none; margin-bottom: 24px; }
        .plan-features li { padding: 8px 0; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .plan-features li::before { content: "✓"; color: var(--primary); font-weight: bold; }
        .plan-card .actions { display: flex; gap: 12px; }
        .btn-primary-admin { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-secondary-admin { padding: 12px 24px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; cursor: pointer; }
        .plan-stats { display: flex; gap: 16px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
        .plan-stats .stat { text-align: center; flex: 1; }
        .plan-stats .stat-value { font-size: 18px; font-weight: 600; }
        .plan-stats .stat-label { font-size: 12px; color: var(--text-muted); }
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
                    <a href="<?php echo $baseUrl; ?>superadmin/plans" class="nav-item active">Planes</a>
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
                <h1>Gestión de Planes</h1>
                <button class="btn-primary-admin">+ Nuevo Plan</button>
            </div>
            
            <div class="plans-grid">
                <div class="plan-card">
                    <h3>Básico</h3>
                    <div class="price">$29<span>/mes</span></div>
                    <div class="description">Ideal para individuos que inician</div>
                    <ul class="plan-features">
                        <li>100 créditos/mes</li>
                        <li>5 campañas activas</li>
                        <li>Generación de imágenes</li>
                        <li>Copies básicos</li>
                        <li>Soporte por email</li>
                    </ul>
                    <div class="plan-stats">
                        <div class="stat">
                            <div class="stat-value">450</div>
                            <div class="stat-label">Usuarios</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">$13,050</div>
                            <div class="stat-label">Ingresos</div>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-primary-admin">Editar</button>
                        <button class="btn-secondary-admin">Eliminar</button>
                    </div>
                </div>
                
                <div class="plan-card featured">
                    <span class="badge">Más Popular</span>
                    <h3>Pro</h3>
                    <div class="price">$79<span>/mes</span></div>
                    <div class="description">Para profesionales y pequeñas agencias</div>
                    <ul class="plan-features">
                        <li>500 créditos/mes</li>
                        <li>20 campañas activas</li>
                        <li>Generación de imágenes + videos</li>
                        <li>Copies avanzados</li>
                        <li>Analytics completo</li>
                        <li>Soporte prioritario</li>
                    </ul>
                    <div class="plan-stats">
                        <div class="stat">
                            <div class="stat-value">320</div>
                            <div class="stat-label">Usuarios</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">$25,280</div>
                            <div class="stat-label">Ingresos</div>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-primary-admin">Editar</button>
                        <button class="btn-secondary-admin">Eliminar</button>
                    </div>
                </div>
                
                <div class="plan-card">
                    <h3>Enterprise</h3>
                    <div class="price">$199<span>/mes</span></div>
                    <div class="description">Para agencias y grandes equipos</div>
                    <ul class="plan-features">
                        <li>Créditos ilimitados</li>
                        <li>Campañas ilimitadas</li>
                        <li>Generación de imágenes + videos</li>
                        <li>Copies ilimitados</li>
                        <li>API access</li>
                        <li>Gestión de clientes</li>
                        <li>Soporte 24/7</li>
                    </ul>
                    <div class="plan-stats">
                        <div class="stat">
                            <div class="stat-value">89</div>
                            <div class="stat-label">Usuarios</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">$17,711</div>
                            <div class="stat-label">Ingresos</div>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-primary-admin">Editar</button>
                        <button class="btn-secondary-admin">Eliminar</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
