<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

// Check if masquerading
$isMasquerading = \Session::isMasquerading();
$masqueradeUser = null;
if ($isMasquerading) {
    $masqueradeUser = \App\Models\User::findById(\Session::getUserId());
}

// Get current page for active state
$currentPage = $currentPage ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'WowAds'; ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%23A8FF35' rx='20' width='100' height='100'/><text y='.9em' font-size='80' x='50%' text-anchor='middle' fill='%23000'>W</text></svg>">
    <style>
        .alert { padding: 14px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 20px; }
        .alert-success { background: rgba(46, 213, 115, 0.1); border: 1px solid rgba(46, 213, 115, 0.3); color: var(--success); }
        .alert-error { background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.3); color: var(--danger); }
        .btn-primary { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        <?php if ($isMasquerading): ?>
        .masquerade-banner {
            position: fixed; top: 0; left: 0; right: 0;
            background: linear-gradient(90deg, #A8FF35, #96E82E);
            color: #000; padding: 8px 20px;
            display: flex; justify-content: space-between; align-items: center;
            z-index: 9999; font-size: 13px;
        }
        .masquerade-btn {
            background: #000; color: #A8FF35;
            padding: 6px 14px; border-radius: 4px;
            text-decoration: none; font-weight: 600; font-size: 12px;
        }
        .app-layout { margin-top: 40px !important; }
        <?php endif; ?>
        .mobile-logo {
            display: none;
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 8px;
            font-size: 20px;
            font-weight: 800;
            align-items: center;
            justify-content: center;
            color: #000;
        }
        @media (max-width: 768px) {
            .mobile-logo { display: flex; }
        }
    </style>
</head>
<body>
    <?php if ($isMasquerading && $masqueradeUser): ?>
    <div class="masquerade-banner">
        <span>👤 Vista del usuario <strong><?php echo htmlspecialchars($masqueradeUser['name']); ?></strong> desde SuperAdmin</span>
        <a href="<?php echo $baseUrl; ?>superadmin/stop-masquerade" class="masquerade-btn">← Volver al Admin</a>
    </div>
    <?php endif; ?>
    
    <div class="app-layout" style="<?php echo $isMasquerading ? 'margin-top: 40px;' : ''; ?>">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="logo">W</div>
                <h2>WowAds</h2>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="<?php echo $baseUrl; ?>dashboard" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <span class="icon">◉</span> Dashboard
                    </a>
                    <a href="<?php echo $baseUrl; ?>campaigns" class="nav-item <?php echo $currentPage === 'campaigns' ? 'active' : ''; ?>">
                        <span class="icon">◈</span> Campañas
                    </a>
                    <a href="#" class="nav-item">
                        <span class="icon">◐</span> Anuncios
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Marca</div>
                    <a href="<?php echo $baseUrl; ?>brand-dna" class="nav-item <?php echo $currentPage === 'brand-dna' ? 'active' : ''; ?>">
                        <span class="icon">🎨</span> ADNs de Marcas
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Conexiones</div>
                    <a href="<?php echo $baseUrl; ?>connections" class="nav-item <?php echo $currentPage === 'connections' ? 'active' : ''; ?>">
                        <span class="icon">🔗</span> Conexión
                    </a>
                    <a href="<?php echo $baseUrl; ?>client-accounts" class="nav-item <?php echo $currentPage === 'client-accounts' ? 'active' : ''; ?>">
                        <span class="icon">👥</span> Cuentas Clientes
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Creativos IA</div>
                    <a href="<?php echo $baseUrl; ?>images" class="nav-item <?php echo $currentPage === 'images' ? 'active' : ''; ?>">
                        <span class="icon">▣</span> Imágenes
                    </a>
                    <a href="#" class="nav-item">
                        <span class="icon">▶</span> Videos
                    </a>
                    <a href="#" class="nav-item">
                        <span class="icon">✎</span> Copies
                    </a>
                </div>
                
                
                
                <div class="nav-section">
                    <div class="nav-section-title">Gestión</div>
                    <a href="#" class="nav-item">
                        <span class="icon">⚙</span> Estrategias
                    </a>
                    <a href="#" class="nav-item">
                        <span class="icon">⚑</span> Configuración
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="<?php echo $baseUrl; ?>auth/logout" class="nav-item" style="margin-top: 8px; color: var(--danger);">
                    <span class="icon">⏻</span> Cerrar Sesión
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <div class="mobile-logo">W</div>
                </div>
                <div class="topbar-right">
                    <button class="btn-icon" title="Notificaciones">🔔</button>
                    <button class="btn-icon" title="Ayuda">?</button>

                    <div class="user-menu">
                    <div class="user-avatar">U</div>
                    <div class="user-info">
                        <div class="user-name">Usuario</div>
                        <div class="user-role">Agencia</div>
                    </div>
                </div>
                <button class="menu-toggle" id="menuToggle">☰</button>

                </div>
            </header>
            
            <div class="content">
                <?php echo $content; ?>
            </div>
        </main>
    </div>
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('active');"></div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });
    </script>
</body>
</html>
