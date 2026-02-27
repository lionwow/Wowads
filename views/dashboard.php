<?php
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

$isMasquerading = \Session::isMasquerading();
$masqueradeUser = null;
if ($isMasquerading) {
    $masqueradeUser = \App\Models\User::findById(\Session::getUserId());
}

ob_start();
?>

<?php if (isset($_GET['welcome']) && $_GET['welcome'] === '1'): ?>
<div class="modal-overlay" id="welcomeModal">
    <div class="modal-content">
        <div class="modal-icon">🎉</div>
        <h2>¡Bienvenido a WowAds!</h2>
        <p>Tu cuenta ha sido creada exitosamente.</p>
        <p class="credits-info">Tienes <strong>0 créditos</strong> disponibles.</p>
        <p class="credits-hint">¡Compra créditos para empezar a generar campañas con IA!</p>
        <a href="<?php echo $baseUrl; ?>dashboard" class="btn-primary" onclick="document.getElementById('welcomeModal').remove(); return true;">Comenzar</a>
    </div>
</div>
<style>
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 40px; text-align: center; max-width: 400px; animation: fadeInUp 0.3s ease-out; }
    .modal-icon { font-size: 48px; margin-bottom: 16px; }
    .modal-content h2 { margin-bottom: 12px; }
    .modal-content p { color: var(--text-muted); margin-bottom: 8px; }
    .credits-info { font-size: 18px; color: var(--primary) !important; font-weight: 600; }
    .credits-hint { font-size: 14px; margin-top: 16px !important; }
    .modal-content .btn-primary { display: inline-block; margin-top: 24px; text-decoration: none; }
</style>
<?php endif; ?>

<?php if ($isMasquerading && $masqueradeUser): ?>
<style>
    .app-layout { margin-top: 40px !important; }
</style>
<?php endif; ?>



<div class="content">
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">📊</div>
                <span class="stat-trend up">+12%</span>
            </div>
            <div class="stat-value">24</div>
            <div class="stat-label">Campañas Activas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">👁</div>
                <span class="stat-trend up">+8%</span>
            </div>
            <div class="stat-value">1.2M</div>
            <div class="stat-label">Impresiones</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">💰</div>
                <span class="stat-trend down">-3%</span>
            </div>
            <div class="stat-value">$4.2K</div>
            <div class="stat-label">Gasto Total</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">🎯</div>
                <span class="stat-trend up">+15%</span>
            </div>
            <div class="stat-value">3.4%</div>
            <div class="stat-label">CTR Promedio</div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#" class="quick-action">
            <div class="icon">✨</div>
            <h3>Nueva Campaña</h3>
            <p>Crear campaña con IA</p>
        </a>
        
        <a href="#" class="quick-action">
            <div class="icon">🖼</div>
            <h3>Generar Imagen</h3>
            <p>Crear creativo visual</p>
        </a>
        
        <a href="#" class="quick-action">
            <div class="icon">📝</div>
            <h3>Generar Copy</h3>
            <p>Escribir texto publicitario</p>
        </a>
        
        <a href="#" class="quick-action">
            <div class="icon">🔗</div>
            <h3>Conectar Meta</h3>
            <p>Vincular cuenta publicitaria</p>
        </a>
    </div>
    
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Actividad Reciente</h3>
            <a href="#" class="badge badge-primary">Ver todo</a>
        </div>
        <div class="card-body">
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon" style="background: rgba(168, 255, 53, 0.1); color: #A8FF35;">✨</div>
                    <div class="activity-content">
                        <div class="activity-text">Nueva imagen generada para "Campaña Verano"</div>
                        <div class="activity-time">Hace 2 horas</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon" style="background: rgba(46, 213, 115, 0.1); color: #2ED573;">✓</div>
                    <div class="activity-content">
                        <div class="activity-text">Campaña "Promo Invierno" publicada exitosamente</div>
                        <div class="activity-time">Hace 5 horas</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon" style="background: rgba(255, 165, 2, 0.1); color: #FFA502;">⚡</div>
                    <div class="activity-content">
                        <div class="activity-text">Copy generado para "Nuevo Producto"</div>
                        <div class="activity-time">Ayer</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon" style="background: rgba(255, 71, 87, 0.1); color: #FF4757;">!</div>
                    <div class="activity-content">
                        <div class="activity-text">Alerta: Presupuesto bajo en "Campaña Navidad"</div>
                        <div class="activity-time">Ayer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
