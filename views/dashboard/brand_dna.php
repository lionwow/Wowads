<?php
// Ensure BASE_URL is defined (same logic as layout.php)
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'ADNs de Marca';
$currentPage = 'brand-dna';

$facebookAccounts = array_filter($socialAccounts, fn($a) => $a['platform'] === 'facebook');

ob_start();
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .page-title { font-size: 24px; font-weight: 600; }
    
    .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
    
    .brand-list { display: grid; gap: 16px; }
    .brand-item { 
        background: var(--bg-lighter); 
        border: 1px solid var(--border); 
        border-radius: var(--radius-sm); 
        padding: 16px; 
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand-info { flex: 1; }
    .brand-name { font-weight: 600; font-size: 16px; margin-bottom: 4px; }
    .brand-meta { font-size: 12px; color: var(--text-muted); }
    .brand-preview { 
        font-size: 13px; 
        color: var(--text-muted); 
        margin-top: 8px; 
        max-height: 60px; 
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .brand-actions { display: flex; gap: 8px; }
    .btn { 
        padding: 8px 16px; 
        border-radius: var(--radius-sm); 
        font-size: 13px; 
        cursor: pointer; 
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary { background: var(--primary); color: #000; border: none; font-weight: 600; }
    .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
    .btn-danger { background: rgba(255, 71, 87, 0.1); color: var(--danger); border: 1px solid rgba(255, 71, 87, 0.3); }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    
    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    .empty-icon { font-size: 48px; margin-bottom: 16px; }
</style>

<div class="page-header">
    <h1 class="page-title">ADNs de Marca</h1>
    <a href="<?php echo $baseUrl; ?>brand-dna/create" class="btn btn-primary">+ Nuevo ADN</a>
</div>

<?php if (empty($brandDNAs)): ?>
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">🎨</div>
        <h3>No hay ADNs de Marca</h3>
        <p>Crea el primer ADN de marca para tu negocio</p>
        <a href="<?php echo $baseUrl; ?>brand-dna/create" class="btn btn-primary" style="margin-top: 16px;">Crear ADN</a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="brand-list">
        <?php foreach ($brandDNAs as $dna): ?>
        <div class="brand-item">
            <div class="brand-info">
                <div class="brand-name"><?php echo htmlspecialchars($dna['name']); ?></div>
                <div class="brand-meta">
                    <?php if (!empty($dna['fanpage_name'])): ?>
                    Fanpage: <?php echo htmlspecialchars($dna['fanpage_name']); ?> | 
                    <?php endif; ?>
                    Creado: <?php echo date('d/m/Y', strtotime($dna['created_at'])); ?>
                </div>
                <?php if (!empty($dna['description'])): ?>
                <div class="brand-preview"><?php echo htmlspecialchars(substr($dna['description'], 0, 200)); ?>...</div>
                <?php endif; ?>
            </div>
            <div class="brand-actions">
                <a href="<?php echo $baseUrl; ?>brand-dna/edit?id=<?php echo $dna['id']; ?>" class="btn btn-secondary btn-sm">Editar</a>
                <button type="button" class="btn btn-danger btn-sm" onclick="deleteBrandDNA(<?php echo $dna['id']; ?>)">Eliminar</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function deleteBrandDNA(id) {
    if (!confirm('¿Estás seguro de eliminar este ADN de marca?')) return;
    
    fetch('<?php echo $baseUrl; ?>brand-dna/delete', {
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
