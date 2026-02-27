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

// Get all accounts
$accounts = \App\Models\SocialAccount::getByAgency($agencyId);

$platform = $_GET['platform'] ?? null;
$search = $_GET['search'] ?? '';

// Filter accounts
if ($platform) {
    $accounts = array_filter($accounts, fn($a) => $a['platform'] === $platform);
}
if ($search) {
    $accounts = array_filter($accounts, fn($a) => stripos($a['account_name'], $search) !== false);
}

$pageTitle = 'Cuentas de Clientes';
$currentPage = 'client-accounts';

ob_start();
?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success">✅ Cuenta agregada exitosamente</div>
<?php endif; ?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h1 style="font-size: 24px;">Cuentas Conectadas</h1>
    <button class="btn-primary" onclick="openAddModal()">+ Agregar Cuenta</button>
</div>

<form method="GET" action="<?php echo $baseUrl; ?>client-accounts" class="filters" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <input type="text" name="search" placeholder="Buscar cuenta..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); width: 250px;">
    <select name="platform" style="padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
        <option value="">Todas las plataformas</option>
        <option value="facebook" <?php echo $platform === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
        <option value="tiktok" <?php echo $platform === 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
        <option value="google" <?php echo $platform === 'google' ? 'selected' : ''; ?>>Google</option>
    </select>
    <button type="submit" style="padding: 10px 20px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer;">Filtrar</button>
</form>

<?php if (empty($accounts)): ?>
<div class="empty-state" style="text-align: center; padding: 60px 20px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);">
    <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
    <h3 style="font-size: 18px; margin-bottom: 8px;">No hay cuentas registradas</h3>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Conecta una cuenta desde la sección de Conexiones para comenzar</p>
    <a href="<?php echo $baseUrl; ?>connections" class="btn-primary" style="padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;">Ir a Conexiones</a>
</div>
<?php else: ?>
<div class="accounts-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
    <?php foreach ($accounts as $account): ?>
    <div class="account-card" style="background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; transition: all 0.2s;">
        <div class="header" style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
            <div class="platform-icon" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: #fff; <?php echo $account['platform'] === 'facebook' ? 'background: #1877F2;' : ($account['platform'] === 'tiktok' ? 'background: #000; border: 1px solid #25F4EE;' : 'background: #4285F4;'); ?>">
                <?php echo strtoupper($account['platform'][0]); ?>
            </div>
            <div>
                <h3 style="font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($account['account_name']); ?></h3>
                <span style="font-size: 13px; color: var(--text-muted); text-transform: capitalize;"><?php echo htmlspecialchars($account['platform']); ?></span>
            </div>
        </div>
        
        <span class="status-badge" style="display: inline-flex; padding: 4px 10px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 500; <?php echo $account['status'] === 'active' ? 'background: rgba(46, 213, 115, 0.1); color: var(--success);' : 'background: rgba(255, 71, 87, 0.1); color: var(--danger);'; ?>">
            <?php echo $account['status'] === 'active' ? 'Activa' : 'Desconectada'; ?>
        </span>
        
        <div style="display: flex; gap: 16px; padding: 16px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin: 16px 0;">
            <div style="flex: 1;">
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">ID de Cuenta</div>
                <div style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($account['account_id']); ?></div>
            </div>
            <div style="flex: 1;">
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Conectado</div>
                <div style="font-size: 14px; font-weight: 500;"><?php echo date('d/m/Y', strtotime($account['created_at'])); ?></div>
            </div>
        </div>
        
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="btn-action btn-brand" style="padding: 8px 16px; border-radius: var(--radius-xs); font-size: 13px; cursor: pointer; border: none; background: var(--primary-dim); color: var(--primary);">🎨 ADN de Marca</button>
            <button class="btn-action btn-edit" style="padding: 8px 16px; border-radius: var(--radius-xs); font-size: 13px; cursor: pointer; border: none; background: rgba(255, 165, 2, 0.1); color: var(--warning);">✏️ Editar</button>
            <a href="<?php echo $baseUrl; ?>client-accounts/delete?id=<?php echo $account['id']; ?>" class="btn-action btn-delete" style="padding: 8px 16px; border-radius: var(--radius-xs); font-size: 13px; cursor: pointer; border: none; background: rgba(255, 71, 87, 0.1); color: var(--danger); text-decoration: none;" onclick="return confirm('¿Eliminar cuenta?')">🗑 Eliminar</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Add Account Modal -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-bottom: 24px;">Agregar Cuenta</h3>
        
        <!-- Step 1: Select Platform -->
        <div id="step1">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Seleccionar Plataforma</label>
                <select id="platformSelect" onchange="platformChanged()" style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                    <option value="">Seleccionar...</option>
                    <option value="facebook">Facebook (Fan Pages)</option>
                    <option value="tiktok">TikTok</option>
                    <option value="google">Google Ads</option>
                </select>
            </div>
            
            <div id="manualInput" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">ID de Cuenta</label>
                    <input type="text" id="accountId" placeholder="ID de la cuenta publicitaria" style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px;">Nombre de Cuenta</label>
                    <input type="text" id="accountName" placeholder="Nombre para identificar" style="width: 100%; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                </div>
            </div>
            
            <div id="facebookPagesSection" style="display: none;">
                <div style="margin-bottom: 20px;">
                    <button type="button" onclick="loadFacebookPages()" id="loadPagesBtn" style="padding: 12px 24px; background: #1877F2; color: #fff; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; width: 100%;">🔗 Cargar mis Fan Pages</button>
                </div>
                <div id="pagesLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                    Cargando páginas...
                </div>
                <div id="pagesList" style="max-height: 300px; overflow-y: auto;">
                    <!-- Pages will be loaded here -->
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeAddModal()" style="padding: 12px 24px; background: transparent; color: var(--text); border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer;">Cancelar</button>
                <button type="button" onclick="submitAccount()" id="submitBtn" style="padding: 12px 24px; background: #ccc; color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: not-allowed;" disabled>Agregar</button>
            </div>
        </div>
    </div>
</div>

<form id="addAccountForm" method="POST" action="<?php echo $baseUrl; ?>client-accounts/add" style="display: none;">
    <input type="hidden" name="platform" id="formPlatform">
    <input type="hidden" name="account_id" id="formAccountId">
    <input type="hidden" name="account_name" id="formAccountName">
</form>

<script>
let selectedPlatform = '';
let selectedPageId = '';
let selectedPageName = '';

function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
    resetModal();
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    resetModal();
}

function resetModal() {
    document.getElementById('platformSelect').value = '';
    document.getElementById('manualInput').style.display = 'none';
    document.getElementById('facebookPagesSection').style.display = 'none';
    document.getElementById('pagesList').innerHTML = '';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').style.background = '#ccc';
    document.getElementById('submitBtn').style.cursor = 'not-allowed';
    selectedPlatform = '';
    selectedPageId = '';
    selectedPageName = '';
}

function platformChanged() {
    const platform = document.getElementById('platformSelect').value;
    selectedPlatform = platform;
    
    document.getElementById('manualInput').style.display = 'none';
    document.getElementById('facebookPagesSection').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').style.background = '#ccc';
    document.getElementById('submitBtn').style.cursor = 'not-allowed';
    
    if (platform === 'facebook') {
        document.getElementById('facebookPagesSection').style.display = 'block';
    } else if (platform === 'tiktok' || platform === 'google') {
        document.getElementById('manualInput').style.display = 'block';
        checkManualInput();
    }
}

function checkManualInput() {
    const accountId = document.getElementById('accountId').value;
    const accountName = document.getElementById('accountName').value;
    
    if (accountId && accountName) {
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').style.background = 'var(--primary)';
        document.getElementById('submitBtn').style.cursor = 'pointer';
    } else {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').style.background = '#ccc';
        document.getElementById('submitBtn').style.cursor = 'not-allowed';
    }
}

document.getElementById('accountId').addEventListener('input', checkManualInput);
document.getElementById('accountName').addEventListener('input', checkManualInput);

function loadFacebookPages() {
    const btn = document.getElementById('loadPagesBtn');
    const loading = document.getElementById('pagesLoading');
    const pagesList = document.getElementById('pagesList');
    
    btn.style.display = 'none';
    loading.style.display = 'block';
    
    fetch('<?php echo $baseUrl; ?>connections/pages?platform=facebook')
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success && data.pages && data.pages.length > 0) {
                let html = '<div style="margin-bottom: 12px; font-size: 13px; color: var(--text-muted);">Selecciona una Fan Page:</div>';
                
                data.pages.forEach(page => {
                    html += `
                        <div onclick="selectPage('${page.id}', '${page.name}', this)" 
                             style="padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 8px; cursor: pointer; transition: all 0.2s;">
                            <div style="font-weight: 600;">${page.name}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">${page.category}</div>
                        </div>
                    `;
                });
                
                pagesList.innerHTML = html;
            } else {
                pagesList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-muted);">No se encontraron páginas. Asegúrate de tener páginas vinculadas a tu cuenta de Facebook.</div>';
                btn.style.display = 'block';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            pagesList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger);">Error al cargar páginas. Intenta de nuevo.</div>';
            btn.style.display = 'block';
        });
}

function selectPage(pageId, pageName, element) {
    // Remove previous selection
    document.querySelectorAll('#pagesList > div').forEach(div => {
        div.style.borderColor = 'var(--border)';
        div.style.background = 'var(--bg)';
    });
    
    // Highlight selected
    element.style.borderColor = 'var(--primary)';
    element.style.background = 'rgba(168, 255, 53, 0.1)';
    
    selectedPageId = pageId;
    selectedPageName = pageName;
    
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('submitBtn').style.background = '#A8FF35';
    document.getElementById('submitBtn').style.color = '#000';
    document.getElementById('submitBtn').style.cursor = 'pointer';
}

function submitAccount() {
    const platform = selectedPlatform;
    
    if (platform === 'facebook' && selectedPageId) {
        document.getElementById('formPlatform').value = platform;
        document.getElementById('formAccountId').value = selectedPageId;
        document.getElementById('formAccountName').value = selectedPageName;
    } else if ((platform === 'tiktok' || platform === 'google')) {
        document.getElementById('formPlatform').value = platform;
        document.getElementById('formAccountId').value = document.getElementById('accountId').value;
        document.getElementById('formAccountName').value = document.getElementById('accountName').value;
    } else {
        alert('Por favor completa todos los campos');
        return;
    }
    
    document.getElementById('addAccountForm').submit();
}
</script>

<?php
$content = ob_get_clean();

require_once __DIR__ . '/../layout.php';
