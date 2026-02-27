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
    <title>Informes - Super Admin - WowAds</title>
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
        .report-filters { display: flex; gap: 16px; margin-bottom: 24px; }
        .report-filters select, .report-filters input { padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
        .stat-card .label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
        .stat-card .value { font-size: 28px; font-weight: 700; }
        .stat-card .value.primary { color: var(--primary); }
        .stat-card .trend { font-size: 12px; margin-top: 8px; }
        .stat-card .trend.up { color: var(--success); }
        .stat-card .trend.down { color: var(--danger); }
        .chart-container { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; }
        .chart-container h3 { font-size: 16px; margin-bottom: 20px; }
        .chart-placeholder { height: 250px; background: var(--bg); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; color: var(--text-muted); }
        .report-table { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .report-table table { width: 100%; border-collapse: collapse; }
        .report-table th { text-align: left; padding: 16px; background: var(--bg-light); font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
        .report-table td { padding: 16px; border-bottom: 1px solid var(--border); }
        .report-table tr:last-child td { border-bottom: none; }
        .progress-bar { height: 8px; background: var(--bg); border-radius: 4px; overflow: hidden; }
        .progress-bar .fill { height: 100%; background: var(--primary); }
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
                    <a href="<?php echo $baseUrl; ?>superadmin/reports" class="nav-item active">Informes</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Gestión</div>
                    <a href="<?php echo $baseUrl; ?>superadmin/users" class="nav-item">Usuarios</a>
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
            <div class="admin-header">
                <h1>Informes y Estadísticas</h1>
                <button class="btn-primary" style="padding: 10px 20px;">Exportar PDF</button>
            </div>
            
            <div class="report-filters">
                <select>
                    <option>Este mes</option>
                    <option>Último mes</option>
                    <option>Últimos 3 meses</option>
                    <option>Último año</option>
                    <option>Todo el tiempo</option>
                </select>
                <select>
                    <option>Todos los planes</option>
                    <option>Básico</option>
                    <option>Pro</option>
                    <option>Enterprise</option>
                </select>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Ingresos Totales</div>
                    <div class="value primary">$55,041</div>
                    <div class="trend up">↑ 12% vs mes anterior</div>
                </div>
                <div class="stat-card">
                    <div class="label">Nuevos Usuarios</div>
                    <div class="value">234</div>
                    <div class="trend up">↑ 8% vs mes anterior</div>
                </div>
                <div class="stat-card">
                    <div class="label">Créditos Consumidos</div>
                    <div class="value">45,230</div>
                    <div class="trend up">↑ 15% vs mes anterior</div>
                </div>
                <div class="stat-card">
                    <div class="label">Campañas Creadas</div>
                    <div class="value">1,567</div>
                    <div class="trend down">↓ 3% vs mes anterior</div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>📈 Ingresos por Mes</h3>
                <div class="chart-placeholder">[Gráfico de ingresos - Este mes: $12,450]</div>
            </div>
            
            <div class="chart-container">
                <h3>👥 Usuarios por Plan</h3>
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Usuarios</th>
                                <th>Ingresos</th>
                                <th>% del Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Básico</td>
                                <td>450</td>
                                <td>$13,050</td>
                                <td>
                                    <div class="progress-bar" style="width: 100px;">
                                        <div class="fill" style="width: 40%;"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Pro</td>
                                <td>320</td>
                                <td>$25,280</td>
                                <td>
                                    <div class="progress-bar" style="width: 100px;">
                                        <div class="fill" style="width: 60%;"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Enterprise</td>
                                <td>89</td>
                                <td>$17,711</td>
                                <td>
                                    <div class="progress-bar" style="width: 100px;">
                                        <div class="fill" style="width: 25%;"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>🔝 Top Agencias por Ingresos</h3>
                <div class="report-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Agencia</th>
                                <th>Usuarios</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Agencia Digital Pro</td>
                                <td>12</td>
                                <td>$2,388</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Marketing Solutions</td>
                                <td>8</td>
                                <td>$1,592</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Brand Studio</td>
                                <td>5</td>
                                <td>$995</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
