<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

// Get user info
$user = \App\Models\User::findById(\Session::getUserId());
$agencyId = $user['agency_id'] ?? $user['id'];

// Get connected accounts
$accounts = \App\Models\SocialAccount::getByAgency($agencyId);

// Group accounts by platform
$facebookAccounts = array_filter($accounts, fn($a) => $a['platform'] === 'facebook' && $a['status'] === 'active');
$tiktokAccounts = array_filter($accounts, fn($a) => $a['platform'] === 'tiktok' && $a['status'] === 'active');
$googleAccounts = array_filter($accounts, fn($a) => $a['platform'] === 'google' && $a['status'] === 'active');

$pageTitle = 'Conexiones';
$currentPage = 'connections';

ob_start();
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'connected'): ?>
    <div class="alert alert-success">✅ Cuenta conectada exitosamente</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <?php $errorMsg = [
        'no_app_configured' => '⚠️ La API de Meta no está configurada. Contacta al administrador.',
        'connection_failed' => '❌ Error al conectar cuenta. Intenta de nuevo.',
        'no_code' => '❌ No se recibió código de autorización.',
        'already_connected' => '⚠️ Esta cuenta ya está conectada.'
    ][$_GET['error']] ?? '❌ Error al conectar cuenta. Intenta de nuevo.'; ?>
    <div class="alert alert-error"><?php echo $errorMsg; ?></div>
<?php endif; ?>

<div class="connection-grid">
    <!-- Facebook -->
    <div class="connection-card <?php echo !empty($facebookAccounts) ? 'connected' : ''; ?>">
        <div class="icon facebook">f</div>
        <h3>Facebook</h3>
        <p>Conecta tu cuenta de Facebook Ads para crear campañas en Facebook e Instagram</p>
        
        <?php if (!empty($facebookAccounts)): ?>
            <div class="connected-accounts">
                <div class="status connected">
                    ✓ Conectado (<?php echo count($facebookAccounts); ?>)
                </div>
                <div class="account-list">
                    <?php foreach ($facebookAccounts as $acc): ?>
                        <div class="account-item">
                            <span class="account-name"><?php echo htmlspecialchars($acc['account_name']); ?></span>
                            <a href="<?php echo $baseUrl; ?>connections/disconnect?id=<?php echo $acc['id']; ?>" class="btn-disconnect" onclick="return confirm('¿Desconectar esta cuenta?')">✕</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo $baseUrl; ?>connections/connect?platform=facebook" class="btn-connect primary">+ Añadir otra cuenta</a>
            </div>
        <?php else: ?>
            <div class="status disconnected">○ No conectado</div>
            <a href="<?php echo $baseUrl; ?>connections/connect?platform=facebook" class="btn-connect primary">Conectar Facebook</a>
        <?php endif; ?>
    </div>
    
    <!-- TikTok -->
    <div class="connection-card <?php echo !empty($tiktokAccounts) ? 'connected' : ''; ?>">
        <div class="icon tiktok">♪</div>
        <h3>TikTok</h3>
        <p>Conecta tu cuenta de TikTok Ads para llegar a millones de usuarios</p>
        
        <?php if (!empty($tiktokAccounts)): ?>
            <div class="connected-accounts">
                <div class="status connected">
                    ✓ Conectado (<?php echo count($tiktokAccounts); ?>)
                </div>
                <div class="account-list">
                    <?php foreach ($tiktokAccounts as $acc): ?>
                        <div class="account-item">
                            <span class="account-name"><?php echo htmlspecialchars($acc['account_name']); ?></span>
                            <a href="<?php echo $baseUrl; ?>connections/disconnect?id=<?php echo $acc['id']; ?>" class="btn-disconnect" onclick="return confirm('¿Desconectar esta cuenta?')">✕</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo $baseUrl; ?>connections/connect?platform=tiktok" class="btn-connect primary">+ Añadir otra cuenta</a>
            </div>
        <?php else: ?>
            <div class="status disconnected">○ No conectado</div>
            <a href="<?php echo $baseUrl; ?>connections/connect?platform=tiktok" class="btn-connect primary">Conectar TikTok</a>
        <?php endif; ?>
    </div>
    
    <!-- Google -->
    <div class="connection-card <?php echo !empty($googleAccounts) ? 'connected' : ''; ?>">
        <div class="icon google">G</div>
        <h3>Google Ads</h3>
        <p>Conecta tu cuenta de Google Ads para campañas en Search, Display y YouTube</p>
        
        <?php if (!empty($googleAccounts)): ?>
            <div class="connected-accounts">
                <div class="status connected">
                    ✓ Conectado (<?php echo count($googleAccounts); ?>)
                </div>
                <div class="account-list">
                    <?php foreach ($googleAccounts as $acc): ?>
                        <div class="account-item">
                            <span class="account-name"><?php echo htmlspecialchars($acc['account_name']); ?></span>
                            <a href="<?php echo $baseUrl; ?>connections/disconnect?id=<?php echo $acc['id']; ?>" class="btn-disconnect" onclick="return confirm('¿Desconectar esta cuenta?')">✕</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo $baseUrl; ?>connections/connect?platform=google" class="btn-connect primary">+ Añadir otra cuenta</a>
            </div>
        <?php else: ?>
            <div class="status disconnected">○ No conectado</div>
            <a href="<?php echo $baseUrl; ?>connections/connect?platform=google" class="btn-connect primary">Conectar Google</a>
        <?php endif; ?>
    </div>
</div>

<div class="info-box">
    <h4>ℹ️ ¿Qué puedes hacer con estas conexiones?</h4>
    <ul>
        <li>Crear campañas publicitarias en múltiples plataformas</li>
        <li>Gestionar tus fanpages y cuentas de Ads desde un solo lugar</li>
        <li>Visualizar estadísticas y métricas de tus campañas</li>
        <li>Gestionar presupuestos y pujas</li>
    </ul>
</div>

<style>
    .connection-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px; }
    .connection-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; text-align: center; transition: all 0.2s; }
    .connection-card:hover { border-color: var(--border-light); transform: translateY(-2px); }
    .connection-card.connected { border-color: var(--primary); }
    .connection-card .icon { width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; }
    .connection-card .icon.facebook { background: linear-gradient(135deg, #1877F2, #0d5ecb); }
    .connection-card .icon.tiktok { background: linear-gradient(135deg, #000, #25F4EE); }
    .connection-card .icon.google { background: linear-gradient(135deg, #4285F4, #EA4335); }
    .connection-card h3 { font-size: 20px; margin-bottom: 8px; }
    .connection-card p { color: var(--text-muted); font-size: 14px; margin-bottom: 20px; }
    .connection-card .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: var(--radius-sm); font-size: 14px; margin-bottom: 16px; }
    .connection-card .status.connected { background: rgba(46, 213, 115, 0.1); color: var(--success); }
    .connection-card .status.disconnected { background: rgba(136, 136, 136, 0.1); color: var(--text-muted); }
    .connection-card .connected-accounts { margin-bottom: 16px; }
    .connection-card .account-list { text-align: left; margin-bottom: 16px; background: var(--bg-lighter); border-radius: var(--radius-sm); padding: 8px; }
    .connection-card .account-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-bottom: 1px solid var(--border); }
    .connection-card .account-item:last-child { border-bottom: none; }
    .connection-card .account-name { font-size: 14px; color: var(--text); }
    .connection-card .btn-disconnect { color: var(--danger); font-size: 16px; text-decoration: none; padding: 4px 8px; }
    .connection-card .btn-disconnect:hover { background: rgba(255, 71, 87, 0.1); border-radius: 4px; }
    .connection-card .btn-connect { padding: 14px 32px; border-radius: var(--radius-sm); font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; border: none; width: 100%; box-sizing: border-box; }
    .connection-card .btn-connect.primary { background: var(--primary); color: #000; }
    .connection-card .btn-connect.primary:hover { background: #96E82E; }
    .info-box { background: var(--bg-lighter); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
    .info-box h4 { font-size: 14px; margin-bottom: 12px; color: var(--text-muted); }
    .info-box ul { list-style: none; padding: 0; }
    .info-box li { padding: 8px 0; font-size: 14px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
    .info-box li::before { content: "✓"; color: var(--primary); }
</style>

<?php
$content = ob_get_clean();

require_once __DIR__ . '/../layout.php';
