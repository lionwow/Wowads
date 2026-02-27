<?php
// Ensure BASE_URL is defined (same logic as layout.php)
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Editar ADN de Marca';
$currentPage = 'brand-dna';

ob_start();
?>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .page-title { font-size: 24px; font-weight: 600; }
    
    .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
    .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
    .form-control { 
        width: 100%; 
        padding: 12px 16px; 
        background: var(--bg); 
        border: 1px solid var(--border); 
        border-radius: var(--radius-sm); 
        color: var(--text); 
        font-size: 14px; 
        box-sizing: border-box;
    }
    .form-control:focus { outline: none; border-color: var(--primary); }
    textarea.form-control { resize: vertical; min-height: 120px; }
    
    .btn { 
        padding: 12px 24px; 
        border-radius: var(--radius-sm); 
        font-size: 14px; 
        cursor: pointer; 
        text-decoration: none;
        display: inline-block;
        border: none;
    }
    .btn-primary { background: var(--primary); color: #000; font-weight: 600; }
    .btn-primary:hover { background: #96E82E; }
    .btn-secondary { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
    .btn-back { background: var(--bg-lighter); color: var(--text); border: 1px solid var(--border); }
    
    .form-actions { display: flex; gap: 12px; margin-top: 24px; }
    
    #briefingResult { 
        background: var(--bg-lighter); 
        border: 1px solid var(--border); 
        border-radius: var(--radius-sm); 
        padding: 20px; 
        margin-top: 20px;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
        font-size: 13px;
        line-height: 1.6;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Editar ADN de Marca</h1>
    <a href="<?php echo $baseUrl; ?>brand-dna" class="btn btn-back">← Volver</a>
</div>

<div class="card">
    <form id="brandDNAForm">
        <input type="hidden" name="id" value="<?php echo $brandDNA['id']; ?>">
        
        <div class="form-group">
            <label>Nombre del ADN de Marca *</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($brandDNA['name']); ?>" required>
        </div>
        
        <?php if (!empty($brandDNA['fanpage_name'])): ?>
        <div class="form-group">
            <label>Fan Page</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($brandDNA['fanpage_name']); ?>" disabled>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Descripción del negocio</label>
            <textarea name="business_description" id="businessDescription" class="form-control" rows="4"><?php echo htmlspecialchars($brandDNA['business_description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Briefing de Marca (Generado con IA)</label>
            <textarea name="description" id="description" class="form-control" rows="12"><?php echo htmlspecialchars($brandDNA['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Valores de Marca</label>
            <textarea name="brand_values" class="form-control" rows="4"><?php echo htmlspecialchars($brandDNA['brand_values'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="<?php echo $baseUrl; ?>brand-dna" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.getElementById('brandDNAForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?php echo $baseUrl; ?>brand-dna/update', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?php echo $baseUrl; ?>brand-dna';
        } else {
            alert(data.error || 'Error al guardar');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
