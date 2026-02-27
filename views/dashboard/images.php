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

// Get images
$platform = $_GET['platform'] ?? null;
$search = $_GET['search'] ?? '';
$images = \App\Models\Image::getByAgency($agencyId, $platform, $search);

$pageTitle = 'Imágenes';
$currentPage = 'images';

ob_start();
?>

<style>
    .images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
    .image-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: all 0.2s; }
    .image-card:hover { border-color: var(--border-light); transform: translateY(-2px); }
    .image-card .image-preview { width: 100%; height: 200px; object-fit: cover; background: var(--bg-lighter); display: flex; align-items: center; justify-content: center; }
    .image-card .image-preview img { width: 100%; height: 200px; object-fit: cover; }
    .image-card .image-info { padding: 16px; }
    .image-card .image-name { font-size: 14px; font-weight: 600; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .image-card .image-meta { font-size: 12px; color: var(--text-muted); margin-bottom: 12px; }
    .image-card .image-platform { display: inline-block; padding: 4px 8px; border-radius: var(--radius-xs); font-size: 11px; font-weight: 500; margin-bottom: 12px; }
    .image-card .image-platform.facebook { background: rgba(24, 119, 242, 0.2); color: #1877F2; }
    .image-card .image-platform.tiktok { background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid #25F4EE; }
    .image-card .image-platform.google { background: rgba(66, 133, 244, 0.2); color: #4285F4; }
    .image-card .actions { display: flex; gap: 8px; }
    .image-card .btn-action { padding: 8px 12px; border-radius: var(--radius-xs); font-size: 12px; cursor: pointer; border: none; text-decoration: none; flex: 1; text-align: center; }
    .image-card .btn-view { background: var(--primary-dim); color: var(--primary); }
    .image-card .btn-delete { background: rgba(255, 71, 87, 0.1); color: var(--danger); }
    .image-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; }
    .image-modal.active { display: flex; }
    .image-modal img { max-width: 90%; max-height: 90%; border-radius: var(--radius); }
    .image-modal .close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 30px; cursor: pointer; }
    .empty-state { text-align: center; padding: 60px 20px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); }
    .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
    .empty-state h3 { font-size: 18px; margin-bottom: 8px; }
    .empty-state p { color: var(--text-muted); }
</style>

<?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="alert alert-success">✅ Imagen eliminada exitosamente</div>
<?php endif; ?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h1 style="font-size: 24px;">Imágenes Creadas</h1>
</div>

<form method="GET" action="<?php echo $baseUrl; ?>images" class="filters" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <input type="text" name="search" placeholder="Buscar por nombre o prompt..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); width: 250px;">
    <select name="platform" style="padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
        <option value="">Todas las plataformas</option>
        <option value="facebook" <?php echo $platform === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
        <option value="tiktok" <?php echo $platform === 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
        <option value="google" <?php echo $platform === 'google' ? 'selected' : ''; ?>>Google</option>
    </select>
    <button type="submit" style="padding: 10px 20px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer;">Filtrar</button>
</form>

<?php if (empty($images)): ?>
<div class="empty-state">
    <div class="icon">🖼️</div>
    <h3>No hay imágenes</h3>
    <p>Crea tu primera imagen con IA desde la sección de generación</p>
</div>
<?php else: ?>
<div class="images-grid">
    <?php foreach ($images as $img): ?>
    <div class="image-card">
        <div class="image-preview" onclick="openImageModal('<?php echo $baseUrl . htmlspecialchars($img['file_path']); ?>')">
            <?php if ($img['file_path'] && file_exists(__DIR__ . '/../../' . $img['file_path'])): ?>
                <img src="<?php echo $baseUrl . htmlspecialchars($img['file_path']); ?>" alt="<?php echo htmlspecialchars($img['name']); ?>">
            <?php else: ?>
                <span style="font-size: 40px;">🖼️</span>
            <?php endif; ?>
        </div>
        <div class="image-info">
            <div class="image-name"><?php echo htmlspecialchars($img['name']); ?></div>
            <?php if ($img['social_platform']): ?>
            <span class="image-platform <?php echo htmlspecialchars($img['social_platform']); ?>"><?php echo ucfirst($img['social_platform']); ?></span>
            <?php endif; ?>
            <div class="image-meta">
                <?php echo date('d/m/Y H:i', strtotime($img['created_at'])); ?>
            </div>
            <div class="actions">
                <button class="btn-action btn-view" onclick="openImageModal('<?php echo $baseUrl . htmlspecialchars($img['file_path']); ?>')">Ver</button>
                <a href="<?php echo $baseUrl; ?>images/delete?id=<?php echo $img['id']; ?>" class="btn-action btn-delete" onclick="return confirm('¿Eliminar imagen?')">Eliminar</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Image Modal -->
<div class="image-modal" id="imageModal" onclick="closeImageModal()">
    <span class="close" onclick="closeImageModal()">&times;</span>
    <img id="modalImage" src="" alt="Imagen">
</div>

<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.add('active');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
}
</script>

<?php
$content = ob_get_clean();

require_once __DIR__ . '/../layout.php';
