<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

// Decode campaign configuration if editing
$campaignConfig = [];
if (!empty($editCampaign) && !empty($editCampaign['config'])) {
    $campaignConfig = is_string($editCampaign['config']) ? json_decode($editCampaign['config'], true) : $editCampaign['config'];
    if (!$campaignConfig) $campaignConfig = [];
}

$pageTitle = 'Crear Campaña';
$currentPage = 'campaigns';

$step = $_GET['step'] ?? 1;

$socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
$brandDNAs = \App\Models\BrandDNA::getByAgency($agencyId);
$selectedBrandDNA = null;
if (!empty($_POST['brand_dna_id'])) {
    $selectedBrandDNA = \App\Models\BrandDNA::findById(intval($_POST['brand_dna_id']));
}
$clients = \App\Models\Client::getByAgency($agencyId);
$campaignTemplates = \App\Models\CampaignTemplate::getActive();

$facebookAccounts = array_filter($socialAccounts, fn($a) => $a['platform'] === 'facebook');

ob_start();
?>

<style>
    .wizard-container { max-width: 900px; margin: 0 auto; padding: 0 10px; }
    
    @media (min-width: 769px) {
        .wizard-container { padding: 0; }
    }
    .wizard-steps { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; overflow-x: auto; padding-bottom: 8px; }
    .wizard-steps::-webkit-scrollbar { height: 4px; }
    .wizard-steps::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
    .wizard-steps::before { content: ''; position: absolute; top: 20px; left: 0; right: 0; height: 2px; background: var(--border); z-index: 0; }
    .wizard-step { position: relative; z-index: 1; text-align: center; flex: 0 0 auto; min-width: 60px; }
    .wizard-step .step-number { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-lighter); border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 600; font-size: 12px; color: var(--text-muted); }
    .wizard-step.active .step-number { background: var(--primary); border-color: var(--primary); color: #000; }
    .wizard-step.completed .step-number { background: var(--success); border-color: var(--success); color: #000; }
    .wizard-step .step-label { font-size: 10px; color: var(--text-muted); white-space: nowrap; }
    .wizard-step.active .step-label { color: var(--text); font-weight: 600; }
    
    .wizard-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
    .wizard-title { font-size: 18px; font-weight: 600; margin-bottom: 16px; }
    .wizard-subtitle { color: var(--text-muted); margin-bottom: 16px; font-size: 13px; }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text); }
    .form-group label span { color: var(--text-muted); font-weight: 400; }
    .form-control { width: 100%; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--primary); }
    
    .account-select { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px; }
    .account-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 12px; cursor: pointer; transition: all 0.2s; }
    .account-option:hover { border-color: var(--primary); }
    .account-option.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .account-option .account-name { font-weight: 600; margin-bottom: 4px; font-size: 14px; }
    .account-option .account-id { font-size: 11px; color: var(--text-muted); }
    
    .row { display: flex; flex-direction: column; gap: 12px; }
    .col { flex: 1; }
    
    .chip-container { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .chip { background: var(--bg-lighter); border: 1px solid var(--border); padding: 5px 10px; border-radius: 16px; font-size: 11px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 4px; }
    .chip:hover { border-color: var(--primary); }
    .chip.selected { background: var(--primary); color: #000; border-color: var(--primary); }
    .chip .remove { cursor: pointer; font-size: 12px; }
    
    .search-input { position: relative; }
    .search-results { position: absolute; top: 100%; left: 0; right: 0; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm); max-height: 200px; overflow-y: auto; z-index: 100; display: none; }
    .search-results.active { display: block; }
    .search-result-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid var(--border); font-size: 13px; }
    .search-result-item:last-child { border-bottom: none; }
    .search-result-item:hover { background: var(--bg-lighter); }
    .search-result-item .name { font-weight: 600; }
    
    .brand-dna-box { background: linear-gradient(135deg, rgba(168, 255, 53, 0.1), rgba(168, 255, 53, 0.05)); border: 1px solid var(--primary); border-radius: var(--radius); padding: 14px; margin-bottom: 16px; }
    .brand-dna-box h4 { margin: 0 0 8px 0; font-size: 13px; color: var(--primary); }
    .brand-dna-box p { margin: 0; font-size: 12px; color: var(--text-muted); line-height: 1.5; }
    
    .brand-dna-selector { margin-bottom: 16px; }
    .brand-dna-selector select { width: 100%; }
    .brand-dna-preview { background: linear-gradient(135deg, rgba(168, 255, 53, 0.1), rgba(168, 255, 53, 0.05)); border: 1px solid var(--primary); border-radius: var(--radius); padding: 14px; margin-top: 12px; margin-bottom: 12px; }
    .brand-dna-preview h4 { margin: 0 0 8px 0; font-size: 13px; color: var(--primary); }
    .brand-dna-preview p { margin: 0; font-size: 12px; color: var(--text-muted); line-height: 1.5; }
    .brand-dna-preview .no-selection { color: var(--text-muted); font-style: italic; }
    
    .wizard-actions { display: flex; justify-content: space-between; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); gap: 10px; }
    .btn-back { padding: 10px 16px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; text-align: center; }
    .btn-next { padding: 10px 16px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; font-size: 13px; }
    .btn-next:hover { background: #96E82E; }
    
    .step-content { display: none; }
    .step-content.active { display: block; }
    
    .objective-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
    .objective-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 10px; cursor: pointer; transition: all 0.2s; text-align: center; }
    .objective-option:hover { border-color: var(--primary); }
    .objective-option.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .objective-option .obj-name { font-weight: 600; margin-bottom: 2px; font-size: 15px; }
    .objective-option .obj-desc { font-size: 14px; color: var(--text-muted); }
    
    .template-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
    .template-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius); overflow: hidden; cursor: pointer; transition: all 0.2s; }
    .template-option:hover { border-color: var(--primary); transform: translateY(-2px); }
    .template-option.selected { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(168, 255, 53, 0.3); }
    .template-option .template-preview { height: 60px; background: var(--bg-lighter); display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .template-option .template-info { padding: 8px; }
    .template-option .template-name { font-weight: 600; font-size: 12px; margin-bottom: 2px; }
    .template-option .template-type { font-size: 10px; color: var(--text-muted); }
    
    .budget-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
    .budget-card { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 12px; cursor: pointer; transition: all 0.2s; }
    .budget-card:hover { border-color: var(--primary); }
    .budget-card.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .budget-card .budget-type { font-weight: 600; font-size: 14px; }
    .budget-card .budget-desc { font-size: 11px; color: var(--text-muted); }
    
    .section-title { font-size: 13px; font-weight: 600; margin: 16px 0 10px 0; padding-bottom: 6px; border-bottom: 1px solid var(--border); }
    .section-title:first-child { margin-top: 0; }
    
    .location-picker-container { }
    .radius-control { background: var(--bg-lighter); padding: 12px; border-radius: var(--radius-sm); margin-top: 10px; }
    .radius-control input[type="range"] { -webkit-appearance: none; height: 8px; background: var(--border); border-radius: 4px; outline: none; width: 100%; }
    .radius-control input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; background: var(--primary); border-radius: 50%; cursor: pointer; }
    #locationMap { z-index: 1; }
    .leaflet-container { background: var(--bg); }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .wizard-container {
            padding: 0 12px;
        }
        
        .wizard-steps {
            justify-content: flex-start;
            gap: 4px;
            margin-bottom: 24px;
            padding-bottom: 12px;
        }
        
        .wizard-step {
            min-width: 45px;
            padding: 0 4px;
        }
        
        .wizard-step .step-number {
            width: 28px;
            height: 28px;
            font-size: 11px;
        }
        
        .wizard-step .step-label {
            font-size: 9px;
        }
        
        .wizard-card {
            padding: 16px;
        }
        
        .wizard-title {
            font-size: 16px;
        }
        
        .wizard-subtitle {
            font-size: 12px;
        }
        
        .wizard-actions {
            flex-direction: column-reverse;
            gap: 8px;
            padding-top: 12px;
        }
        
        .wizard-actions .btn-back,
        .wizard-actions .btn-next {
            width: 100%;
            justify-content: center;
            padding: 12px 16px;
            font-size: 14px;
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 14px;
        }
        
        .form-group label {
            font-size: 12px;
        }
        
        .form-control {
            padding: 12px;
            font-size: 14px;
        }
        
        /* Objective grid */
        .objective-grid {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .objective-option {
            padding: 8px;
        }
        
        .objective-option .obj-name {
            font-size: 13px;
        }
        
        .objective-option .obj-desc {
            font-size: 11px;
        }
        
        /* Template grid */
        .template-grid {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        
        /* Account select */
        .account-select {
            gap: 8px;
        }
        
        .account-option {
            padding: 10px;
        }
        
        /* Chip container */
        .chip-container {
            gap: 6px;
        }
        
        .chip {
            padding: 6px 10px;
            font-size: 11px;
        }
        
        /* Row/Col */
        .row {
            gap: 10px;
        }
        
        /* Brand DNA */
        .brand-dna-box,
        .brand-dna-preview {
            padding: 12px;
        }
        
        .brand-dna-box h4,
        .brand-dna-preview h4 {
            font-size: 12px;
        }
        
        .brand-dna-box p,
        .brand-dna-preview p {
            font-size: 11px;
        }
        
        /* Section titles */
        .section-title {
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        /* Map container */
        #locationMapContainer {
            margin-top: 12px;
        }
        
        #locationMap {
            height: 250px !important;
        }
        
        /* Search inputs */
        .search-results {
            max-height: 150px;
        }
        
        .search-result-item {
            padding: 8px 10px;
            font-size: 12px;
        }
        
        /* Filters */
        .filters-bar {
            flex-direction: column;
            gap: 8px;
        }
        
        .filters-bar select {
            width: 100%;
        }
        
        /* Review summary */
        #reviewSummary {
            padding: 12px;
            font-size: 12px;
        }
        
        /* Hidden labels for mobile */
        .mobile-show { display: block; }
        .mobile-hide { display: none; }
    }
    
    @media (max-width: 480px) {
        .wizard-container {
            padding: 0 8px;
        }
        
        .wizard-steps {
            gap: 2px;
        }
        
        .wizard-step {
            min-width: 40px;
        }
        
        .wizard-step .step-number {
            width: 26px;
            height: 26px;
            font-size: 10px;
        }
        
        .wizard-step .step-label {
            font-size: 8px;
        }
        
        .wizard-card {
            padding: 12px;
            border-radius: 8px;
        }
        
        .wizard-title {
            font-size: 15px;
        }
        
        .objective-grid {
            grid-template-columns: 1fr;
        }
        
        .template-grid {
            grid-template-columns: 1fr;
        }
        
        .form-control {
            padding: 10px;
            font-size: 13px;
        }
        
        .btn-back,
        .btn-next {
            padding: 12px;
            font-size: 13px;
        }
        
        #locationMap {
            height: 200px !important;
        }
    }
    
    /* Mobile Header */
    .mobile-header {
        display: none;
    }
    
    @media (max-width: 768px) {
        .mobile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .mobile-header .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-lighter);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            text-decoration: none;
            font-size: 13px;
        }
        
        .mobile-header h1 {
            font-size: 16px;
            margin: 0;
        }
    }
</style>

<div class="mobile-header">
    <a href="<?php echo $baseUrl; ?>campaigns" class="back-btn">← Volver</a>
    <h1> Paso <?php echo $step; ?>/7</h1>
    <div></div>
</div>

<div class="wizard-container">
    <div class="wizard-steps">
        <div class="wizard-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 1 ? '✓' : '1'; ?></div>
            <div class="step-label">Básico</div>
        </div>
        <div class="wizard-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 2 ? '✓' : '2'; ?></div>
            <div class="step-label">Cuenta Ads</div>
        </div>
        <div class="wizard-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 3 ? '✓' : '3'; ?></div>
            <div class="step-label">Audiencia</div>
        </div>
        <div class="wizard-step <?php echo $step >= 4 ? 'active' : ''; ?> <?php echo $step > 4 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 4 ? '✓' : '4'; ?></div>
            <div class="step-label">Estrategia</div>
        </div>
        <div class="wizard-step <?php echo $step >= 5 ? 'active' : ''; ?> <?php echo $step > 5 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 5 ? '✓' : '5'; ?></div>
            <div class="step-label">Presupuesto</div>
        </div>
        <div class="wizard-step <?php echo $step >= 6 ? 'active' : ''; ?> <?php echo $step > 6 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 6 ? '✓' : '6'; ?></div>
            <div class="step-label">Creativo</div>
        </div>
        <div class="wizard-step <?php echo $step >= 7 ? 'active' : ''; ?>">
            <div class="step-number">7</div>
            <div class="step-label">Revisar</div>
        </div>
    </div>
    
    <form method="POST" action="<?php echo $baseUrl; ?>campaigns/store" id="campaignForm">
        <?php if (!empty($editCampaign)): ?>
        <input type="hidden" name="campaign_id" value="<?php echo $editCampaign['id']; ?>">
        <?php endif; ?>
        
        <!-- Step 1: Basic Info -->
        <div class="step-content <?php echo $step == 1 ? 'active' : ''; ?>" data-step="1">
            <div class="wizard-card">
                <div class="wizard-title">Información Básica</div>
                <div class="wizard-subtitle">Define el nombre y objetivo de tu campaña</div>
                
                <div class="brand-dna-selector">
                    <label>🎨 ADN de Marca *</label>
                    <select name="brand_dna_id" class="form-control" id="brandDNASelect" onchange="handleBrandDNASelect(this)">
                        <option value="">Seleccionar ADN de Marca...</option>
                        <?php foreach ($brandDNAs as $dna): ?>
                        <option value="<?php echo $dna['id']; ?>" <?php echo ($selectedBrandDNA && $selectedBrandDNA['id'] == $dna['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dna['name']); ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__create_new__">+ Crear ADN de Marca</option>
                    </select>
                </div>
                
                <div id="brandDNAPreview" class="brand-dna-preview" style="display: <?php echo $selectedBrandDNA ? 'block' : 'none'; ?>">
                    <?php if ($selectedBrandDNA): ?>
                    <h4>🎨 ADN: <?php echo htmlspecialchars($selectedBrandDNA['name']); ?></h4>
                    <p><?php echo htmlspecialchars($selectedBrandDNA['description']); ?></p>
                    
                    <?php else: ?>
                    <p class="no-selection">Selecciona un ADN de Marca</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Nombre de la Campaña *</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej: Verano 2024 - Promo Productos" required>
                </div>
                
                <div class="form-group">
                    <label>Cliente (Opcional)</label>
                    <select name="client_id" class="form-control">
                        <option value="">Sin cliente específico</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Objetivo de la Campaña *</label>
                </div>
                <div class="objective-grid">
                    <div class="objective-option" data-value="OUTCOME_AWARENESS" onclick="selectObjective('OUTCOME_AWARENESS', this)">
                        <div class="obj-name">👁️ Awareness</div>
                        <div class="obj-desc">Conocimiento de marca</div>
                    </div>
                    <div class="objective-option" data-value="OUTCOME_TRAFFIC" onclick="selectObjective('OUTCOME_TRAFFIC', this)">
                        <div class="obj-name">🚀 Tráfico</div>
                        <div class="obj-desc">Llevar personas a tu web</div>
                    </div>
                    <div class="objective-option" data-value="OUTCOME_ENGAGEMENT" onclick="selectObjective('OUTCOME_ENGAGEMENT', this)">
                        <div class="obj-name">💬 Interacción</div>
                        <div class="obj-desc">Comentarios y me gusta</div>
                    </div>
                    <div class="objective-option" data-value="OUTCOME_LEADS" onclick="selectObjective('OUTCOME_LEADS', this)">
                        <div class="obj-name">📝 Leads</div>
                        <div class="obj-desc">Captar contactos</div>
                    </div>
                    <div class="objective-option" data-value="OUTCOME_CONVERSIONS" onclick="selectObjective('OUTCOME_CONVERSIONS', this)">
                        <div class="obj-name">💰 Conversiones</div>
                        <div class="obj-desc">Ventas y registros</div>
                    </div>
                    <div class="objective-option" data-value="OUTCOME_SALES" onclick="selectObjective('OUTCOME_SALES', this)">
                        <div class="obj-name">🛒 Catálogo</div>
                        <div class="obj-desc">Promocionar productos</div>
                    </div>
                </div>
                <input type="hidden" name="objective" id="objectiveInput" value="">
            </div>
        </div>
        
        <!-- Step 2: Ad Account & Fan Page -->
        <div class="step-content <?php echo $step == 2 ? 'active' : ''; ?>" data-step="2">
            <div class="wizard-card">
                <div class="wizard-title">Configuración de Cuenta Publicitaria</div>
                <div class="wizard-subtitle">Selecciona en orden: Business Manager → Cuenta Ads → Fan Page → Instagram → WhatsApp → Pixel</div>
                
                  <!-- 1. Seleccionar Cuenta Facebook Conectada -->
                  <div class="form-group">
                      <label>1. Cuenta de Facebook Conectada *</label>
                  </div>
                  
                  <?php 
                    $hasStep2Saved = !empty($campaignConfig['social_account_id']) || !empty($campaignConfig['business_manager_id']) || !empty($campaignConfig['ad_account_id']) || !empty($campaignConfig['fan_page_id']) || !empty($campaignConfig['instagram_id']) || !empty($campaignConfig['whatsapp_id']) || !empty($campaignConfig['pixel_id']);
                  ?>
                  <?php if ($hasStep2Saved): ?>
                  <!-- Edit Mode: Show saved configuration -->
                  <div id="savedAccountSummary" style="background: var(--bg-lighter); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 24px;">
                      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                          <h4 style="margin: 0; color: var(--primary);">✅ Configuración Guardada</h4>
                          <button type="button" onclick="editAccountConfig()" style="background: var(--bg); border: 1px solid var(--border); color: var(--text); padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Cambiar Configuración</button>
                      </div>
                      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                          <div><strong style="color: var(--text-muted);">Cuenta Facebook:</strong> <br><?php echo htmlspecialchars($campaignConfig['social_account_name'] ?? 'N/A'); ?></div>
                          <div><strong style="color: var(--text-muted);">Business Manager:</strong> <br><?php echo htmlspecialchars($campaignConfig['business_manager_name'] ?? 'N/A'); ?></div>
                          <div><strong style="color: var(--text-muted);">Cuenta Publicitaria:</strong> <br><?php echo htmlspecialchars($campaignConfig['ad_account_name'] ?? 'N/A'); ?></div>
                          <div><strong style="color: var(--text-muted);">Fan Page:</strong> <br><?php echo htmlspecialchars($campaignConfig['fan_page_name'] ?? 'N/A'); ?></div>
                          <?php if (!empty($campaignConfig['instagram_name'])): ?>
                          <div><strong style="color: var(--text-muted);">Instagram:</strong> <br><?php echo htmlspecialchars($campaignConfig['instagram_name']); ?></div>
                          <?php endif; ?>
                          <?php if (!empty($campaignConfig['pixel_name'])): ?>
                          <div><strong style="color: var(--text-muted);">Pixel:</strong> <br><?php echo htmlspecialchars($campaignConfig['pixel_name']); ?></div>
                          <?php endif; ?>
                      </div>
                  </div>
                  <?php endif; ?>

                  <div id="accountSelectionContainer" style="<?php echo isset($hasStep2Saved) && $hasStep2Saved ? 'display: none;' : 'display: block;'; ?>">
                    <?php if (empty($facebookAccounts)): ?>
                        <p style="color: var(--text-muted);">No hay cuentas de Facebook conectadas. <a href="<?php echo $baseUrl; ?>connections">Conectar cuenta</a></p>
                    <?php else: ?>
                    <div class="account-select" style="margin-bottom: 24px;">
                        <?php foreach ($facebookAccounts as $account): ?>
                        <div class="account-option" data-account-id="<?php echo $account['id']; ?>" onclick="selectAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>', '<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>', this)">
                            <div class="account-name"><?php echo htmlspecialchars($account['account_name']); ?></div>
                            <div class="account-id">ID: <?php echo htmlspecialchars($account['account_id']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                  </div>

                  <input type="hidden" name="social_account_id" id="socialAccountInput" value="<?php echo htmlspecialchars($campaignConfig['social_account_id'] ?? ''); ?>">
                  <input type="hidden" name="social_account_name" id="socialAccountName" value="<?php echo htmlspecialchars($campaignConfig['social_account_name'] ?? ''); ?>">
                  <input type="hidden" name="access_token" id="accessToken" value="<?php echo htmlspecialchars($campaignConfig['access_token'] ?? ''); ?>">
                  
                  <!-- Account Details (shows after selection) -->
                  <div id="accountDetails" style="display: none; margin-top: 24px;">
                    
                    <!-- 2. Business Manager -->
                    <div class="section-title">2. Business Manager *</div>
                    <div id="businessManagersLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Business Managers...</div>
                    </div>
                    <div id="businessManagersList"></div>
                      <input type="hidden" name="business_manager_id" id="businessManagerId" value="<?php echo htmlspecialchars($campaignConfig['business_manager_id'] ?? ''); ?>">
                      <input type="hidden" name="business_manager_name" id="businessManagerName" value="<?php echo htmlspecialchars($campaignConfig['business_manager_name'] ?? ''); ?>">
                    
                    <!-- 3. Ad Accounts -->
                    <div class="section-title">3. Cuenta Publicitaria (Ad Account) *</div>
                    <div id="adAccountsLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Cuentas Ads...</div>
                    </div>
                    <div id="adAccountsList"></div>
                      <input type="hidden" name="ad_account_id" id="adAccountId" value="<?php echo htmlspecialchars($campaignConfig['ad_account_id'] ?? ''); ?>">
                      <input type="hidden" name="ad_account_name" id="adAccountName" value="<?php echo htmlspecialchars($campaignConfig['ad_account_name'] ?? ''); ?>">
                    
                    <!-- 4. Fan Pages -->
                    <div class="section-title">4. Fan Page *</div>
                    <div id="fanPagesLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Fan Pages...</div>
                    </div>
                    <div id="fanPagesList"></div>
                      <input type="hidden" name="fan_page_id" id="fanPageId" value="<?php echo htmlspecialchars($campaignConfig['fan_page_id'] ?? ''); ?>">
                      <input type="hidden" name="fan_page_name" id="fanPageName" value="<?php echo htmlspecialchars($campaignConfig['fan_page_name'] ?? ''); ?>">
                    
                    <!-- 5. Instagram -->
                    <div class="section-title">5. Página de Instagram (Opcional)</div>
                    <div id="instagramLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Instagram...</div>
                    </div>
                    <div id="instagramList"></div>
                      <input type="hidden" name="instagram_id" id="instagramId" value="<?php echo htmlspecialchars($campaignConfig['instagram_id'] ?? ''); ?>">
                      <input type="hidden" name="instagram_name" id="instagramName" value="<?php echo htmlspecialchars($campaignConfig['instagram_name'] ?? ''); ?>">
                    
                    <!-- 6. WhatsApp -->
                    <div class="section-title">6. WhatsApp Business (Opcional)</div>
                    <div id="whatsappLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando WhatsApp...</div>
                    </div>
                    <div id="whatsappList"></div>
                      <input type="hidden" name="whatsapp_id" id="whatsappId" value="<?php echo htmlspecialchars($campaignConfig['whatsapp_id'] ?? ''); ?>">
                      <input type="hidden" name="whatsapp_name" id="whatsappName" value="<?php echo htmlspecialchars($campaignConfig['whatsapp_name'] ?? ''); ?>">
                    
                    <!-- 7. Pixels -->
                    <div class="section-title">7. Pixel de Seguimiento</div>
                    <div id="pixelsLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Pixels...</div>
                    </div>
                    <div id="pixelsList"></div>
                      <input type="hidden" name="pixel_id" id="pixelId" value="<?php echo htmlspecialchars($campaignConfig['pixel_id'] ?? ''); ?>">
                      <input type="hidden" name="pixel_name" id="pixelName" value="<?php echo htmlspecialchars($campaignConfig['pixel_name'] ?? ''); ?>">
                </div>
                
                <div id="selectAccountHint" style="margin-top: 24px; padding: 20px; text-align: center; color: var(--text-muted); background: var(--bg-lighter); border-radius: var(--radius-sm);">
                    Selecciona una cuenta de Facebook para cargar las opciones
                </div>
                <?php if (empty($facebookAccounts)): ?>
                    <p style="color: var(--text-muted);">No hay cuentas de Facebook conectadas. <a href="<?php echo $baseUrl; ?>connections">Conectar cuenta</a></p>
                <?php else: ?>
                
                  <input type="hidden" name="social_account_id" id="socialAccountInput" value="<?php echo htmlspecialchars($campaignConfig['social_account_id'] ?? ''); ?>">
                  <input type="hidden" name="social_account_name" id="socialAccountName" value="<?php echo htmlspecialchars($campaignConfig['social_account_name'] ?? ''); ?>">
                <?php endif; ?>
               
            </div>
        </div>
        
        <!-- Step 3: Audience -->
        <div class="step-content <?php echo $step == 3 ? 'active' : ''; ?>" data-step="3">
            <div class="wizard-card">
                <div class="wizard-title">Audiencia</div>
                <div class="wizard-subtitle">Define a quién quieres alcanzar</div>
                
                <!-- Audiencias Guardadas / Personalizadas -->
                <div class="section-title">Audiencias Guardadas (Opcional)</div>
                <div class="form-group">
                    <label>Tipo de audiencia</label>
                    <select id="audienceType" name="audience_type" class="form-control" onchange="changeAudienceType()">
                        <option value="">Seleccionar tipo...</option>
                        <option value="saved">Audiencias Guardadas</option>
                        <option value="custom">Públicos Personalizados</option>
                    </select>
                </div>
                
                <div id="audiencesLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                    <div style="font-size: 24px;">⏳</div>
                    <div>Cargando audiencias...</div>
                </div>
                
                <div id="audiencesList"></div>
                
                <!-- Lista de audiencias seleccionadas -->
                <div id="selectedAudiencesList" class="chip-container"></div>
                <input type="hidden" name="saved_audiences" id="savedAudiencesInput" value="">
                
                <div class="section-title">Ubicación Geográfica</div>
                
                <div class="location-picker-container" id="locationPickerContainer">
                    <div class="form-group">
                        <label>Buscar ciudad o región</label>
                        <div class="search-input">
                            <input type="text" id="locationSearch" class="form-control" placeholder="Escribe el nombre de una ciudad..." oninput="searchLocations(this.value)">
                            <div id="locationResults" class="search-results"></div>
                        </div>
                    </div>
                    
                    <div id="locationMapContainer" style="display: none;">
                        <div id="locationMap" style="height: 350px; border-radius: var(--radius); margin-bottom: 16px; border: 1px solid var(--border);">
                            <button type="button" onclick="centerAllMarkers()" style="position: absolute; top: 10px; right: 10px; z-index: 1000; background: #1b1b1b; color: var(--primary); border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">Ver todos</button>
                        </div>
                        
                        <div class="chip-container" id="selectedLocations"></div>
                        <input type="hidden" name="locations" id="locationsInput" value="">
                    </div>
                </div>
                
                <div class="section-title">Demografía</div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Edad Mínima</label>
                            <select name="age_min" class="form-control">
                                <?php for($i = 13; $i <= 65; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 18 ? 'selected' : ''; ?>><?php echo $i; ?> años</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Edad Máxima</label>
                            <select name="age_max" class="form-control">
                                <?php for($i = 18; $i <= 65; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 65 ? 'selected' : ''; ?>><?php echo $i; ?> años</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Género</label>
                            <select name="gender" class="form-control">
                                <option value="all">Todos</option>
                                <option value="male">Hombres</option>
                                <option value="female">Mujeres</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="section-title">Intereses</div>
                <div class="form-group">
                    <label>Buscar intereses</label>
                    <div class="search-input">
                        <input type="text" id="interestSearch" class="form-control" placeholder="Escribe para buscar intereses..." oninput="searchInterests(this.value)">
                        <div id="interestResults" class="search-results"></div>
                    </div>
                    <div class="chip-container" id="selectedInterests"></div>
                    <input type="hidden" name="interests" id="interestsInput" value="">
                </div>
            </div>
        </div>
        
        <!-- Step 4: Strategy (Templates) -->
        <div class="step-content <?php echo $step == 4 ? 'active' : ''; ?>" data-step="4">
            <div class="wizard-card">
                <div class="wizard-title">Estrategia</div>
                <div class="wizard-subtitle">Selecciona una plantilla de estrategia predefinida (Opcional)</div>
                
                <?php if (empty($campaignTemplates)): ?>
                <div class="empty-state" style="padding: 40px 20px;">
                    <div class="icon" style="font-size: 32px; margin-bottom: 12px;">📋</div>
                    <p style="color: var(--text-muted);">No hay plantillas disponibles. Puedes continuar sin seleccionar una.</p>
                </div>
                <?php else: ?>
                
                <div class="form-group">
                    <label>Filtrar por Categoría</label>
                    <select id="templateCategory" class="form-control" onchange="filterTemplates()">
                        <option value="">Todas las categorías</option>
                        <option value="sales">💰 Ventas</option>
                        <option value="awareness">👁️ Reconocimiento de Marca</option>
                        <option value="traffic">🚀 Tráfico</option>
                        <option value="engagement">💬 Interacción</option>
                        <option value="leads">📝 Captación de Leads</option>
                        <option value="retargeting">🔄 Retargeting</option>
                    </select>
                </div>
                
                <div id="templatesContainer" class="templates-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                    <?php foreach ($campaignTemplates as $template): ?>
                    <div class="template-card" 
                         data-template-id="<?php echo $template['id']; ?>" 
                         data-category="<?php echo $template['category'] ?? ''; ?>"
                         onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo htmlspecialchars($template['name']); ?>', this)"
                         style="background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: all 0.2s;">
                        <div style="font-weight: 600; margin-bottom: 6px; font-size: 14px;"><?php echo htmlspecialchars($template['name']); ?></div>
                        <?php if ($template['description']): ?>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; line-height: 1.4;"><?php echo htmlspecialchars(mb_substr($template['description'], 0, 80)); ?><?php echo mb_strlen($template['description']) > 80 ? '...' : ''; ?></div>
                        <?php endif; ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                            <?php if (!empty($template['campaign_config'])): ?>
                            <span style="font-size: 10px; padding: 3px 6px; background: rgba(168, 255, 53, 0.15); color: var(--primary); border-radius: 3px;">Campaña</span>
                            <?php endif; ?>
                            <?php if (!empty($template['adset_config'])): ?>
                            <span style="font-size: 10px; padding: 3px 6px; background: rgba(168, 255, 53, 0.15); color: var(--primary); border-radius: 3px;">AdSet</span>
                            <?php endif; ?>
                            <?php if (!empty($template['creative_config'])): ?>
                            <span style="font-size: 10px; padding: 3px 6px; background: rgba(168, 255, 53, 0.15); color: var(--primary); border-radius: 3px;">Creativo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="noTemplatesMessage" class="empty-state" style="padding: 20px; display: none;">
                    <p style="color: var(--text-muted);">No hay plantillas en esta categoría.</p>
                </div>
                
                <input type="hidden" name="template_id" id="templateIdInput" value="">
                <input type="hidden" name="template_name" id="templateNameInput" value="">
                <?php endif; ?>
                
                <!-- Categorías Especiales -->
                <div class="section-title" style="margin-top: 24px;">Categorías Especiales (Opcional)</div>
                <div class="form-group">
                    <select name="special_ad_categories" id="specialAdCategories" class="form-control">
                        <option value="">Ninguna</option>
                        <option value="CREDIT">💳 Productos y servicios financieros</option>
                        <option value="EMPLOYMENT">💼 Empleo</option>
                        <option value="HOUSING">🏠 Vivienda</option>
                        <option value="ISSUES_ELECTIONS_POLITICS">🗳️ Temas sociales</option>
                    </select>
                    <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">
                        Obligatorio para campañas relacionadas con estas categorías según políticas de Meta
                    </small>
                </div>
                
                <div style="margin-top: 20px; padding: 12px; background: var(--bg-lighter); border-radius: var(--radius-sm);">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="skipTemplate" onchange="toggleSkipTemplate()" style="width: 18px; height: 18px;">
                        <span style="font-size: 13px;">Continuar sin plantilla</span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Step 5: Budget & Schedule -->
        <div class="step-content <?php echo $step == 5 ? 'active' : ''; ?>" data-step="5">
            <div class="wizard-card">
                <div class="wizard-title">Presupuesto y Programación</div>
                <div class="wizard-subtitle">Define cuánto quieres gastar y cuándo</div>
                
                <div class="section-title">Tipo de Presupuesto</div>
                <div class="budget-grid">
                    <div class="budget-card" data-value="daily" onclick="selectBudgetType('daily_budget', this)">
                        <div class="budget-type">💵 Gasto Diario</div>
                        <div class="budget-desc">Monto fijo por día</div>
                    </div>
                    <div class="budget-card" data-value="lifetime" onclick="selectBudgetType('lifetime_budget', this)">
                        <div class="budget-type">📊 Presupuesto Total</div>
                        <div class="budget-desc">Monto total para toda la campaña</div>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 24px;">
                    <label id="budgetLabel">Monto del Presupuesto ($)</label>
                    <input type="number" name="" id="budgetAmountInput" class="form-control" placeholder="Ej: 100" min="1" step="0.01">
                </div>
                
                <div class="section-title">Fechas de la Campaña</div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Fecha de Inicio</label>
                            <input type="date" name="start_date" class="form-control" id="startDate">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Fecha de Fin (Opcional)</label>
                            <input type="date" name="end_date" class="form-control" id="endDate">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 6: Creative - Image Generation -->
        <div class="step-content <?php echo $step == 6 ? 'active' : ''; ?>" data-step="6">
            <div class="wizard-card">
                <div class="wizard-title">Creativo del Anuncio</div>
                <div class="wizard-subtitle">Genera la imagen de tu anuncio con IA</div>
                
                <div class="form-group">
                    <label>Seleccionar Plantilla</label>
                </div>
                <div class="template-grid">
                    <div class="template-option" data-value="product_showcase" onclick="selectTemplate('product_showcase', this)">
                        <div class="template-preview">🛍️</div>
                        <div class="template-info">
                            <div class="template-name">Producto</div>
                            <div class="template-type">Imagen + Precio</div>
                        </div>
                    </div>
                    <div class="template-option" data-value="lifestyle" onclick="selectTemplate('lifestyle', this)">
                        <div class="template-preview">🌟</div>
                        <div class="template-info">
                            <div class="template-name">Lifestyle</div>
                            <div class="template-type">Estilo de vida</div>
                        </div>
                    </div>
                    <div class="template-option" data-value="testimonial" onclick="selectTemplate('testimonial', this)">
                        <div class="template-preview">💬</div>
                        <div class="template-info">
                            <div class="template-name">Testimonial</div>
                            <div class="template-type">Reseña cliente</div>
                        </div>
                    </div>
                    <div class="template-option" data-value="before_after" onclick="selectTemplate('before_after', this)">
                        <div class="template-preview">🔄</div>
                        <div class="template-info">
                            <div class="template-name">Antes y Después</div>
                            <div class="template-type">Transformación</div>
                        </div>
                    </div>
                    <div class="template-option" data-value="promo" onclick="selectTemplate('promo', this)">
                        <div class="template-preview">🏷️</div>
                        <div class="template-info">
                            <div class="template-name">Promoción</div>
                            <div class="template-type">Oferta especial</div>
                        </div>
                    </div>
                    <div class="template-option" data-value="custom" onclick="selectTemplate('custom', this)">
                        <div class="template-preview">✏️</div>
                        <div class="template-info">
                            <div class="template-name">Personalizado</div>
                            <div class="template-type">Crear desde cero</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="template" id="templateInput" value="">
                
                <div class="form-group">
                    <label>Describe tu producto/servicio para generar la imagen</label>
                    <textarea name="image_prompt" class="form-control" rows="4" placeholder="Ej: Un refresco frío con gotas de sudor, fondo tropical, colores vibrantes, publicidad de饮料"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Texto principal del anuncio (Headline)</label>
                    <input type="text" name="ad_headline" class="form-control" placeholder="Ej: ¡20% de descuento en tu primera compra!">
                </div>
                
                <div class="form-group">
                    <label>Descripción del anuncio</label>
                    <textarea name="ad_description" class="form-control" rows="3" placeholder="Texto persuasivo para tu anuncio..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>URL de destino (donde llegarán los usuarios)</label>
                    <input type="url" name="ad_url" class="form-control" placeholder="https://tu-sitio.com/producto">
                </div>
            </div>
        </div>
        
        <!-- Step 7: Review -->
        <div class="step-content <?php echo $step == 7 ? 'active' : ''; ?>" data-step="7">
            <div class="wizard-card">
                <div class="wizard-title">Revisa y Crea</div>
                <div class="wizard-subtitle">Verifica los datos de tu campaña antes de crear</div>
                
                <div id="reviewSummary" style="background: var(--bg-lighter); border-radius: var(--radius-sm); padding: 20px;">
                    <p style="color: var(--text-muted);">Completa los pasos anteriores para ver el resumen</p>
                </div>
            </div>
        </div>
        
        <div class="wizard-actions">
            <?php if ($step > 1): ?>
            <button type="button" class="btn-back" onclick="prevStep()">← Atrás</button>
            <?php else: ?>
            <a href="<?php echo $baseUrl; ?>campaigns" class="btn-back">Cancelar</a>
            <?php endif; ?>
            
            <?php if (!empty($editCampaign)): ?>
                <?php if ($step < 7): ?>
                <button type="button" class="btn-next" onclick="nextStep()">Siguiente →</button>
                <?php endif; ?>
                <button type="submit" class="btn-next">💾 Guardar</button>
            <?php elseif ($step < 7): ?>
                <button type="button" class="btn-next" onclick="nextStep()">Siguiente →</button>
            <?php else: ?>
                <button type="submit" class="btn-next">Crear Campaña</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
let currentStep = <?php echo $step; ?>;
let selectedAccountId = 0;
let selectedAccountName = '';
let selectedLocations = [];
let selectedInterests = [];
let selectedAudiences = [];
let searchTimeoutLocation = null;
let searchTimeoutInterest = null;

let selectedBusinessManagerId = '';
let selectedBusinessManagerName = '';

const brandDNAs = <?php echo json_encode(array_map(function($dna) {
    return ['id' => $dna['id'], 'name' => $dna['name'], 'description' => mb_substr($dna['description'] ?? '', 0, 150) . (isset($dna['description']) && mb_strlen($dna['description']) > 150 ? '...' : '')];
}, $brandDNAs)); ?>;

const campaignTemplates = <?php echo json_encode(array_map(function($t) {
    return [
        'id' => $t['id'],
        'name' => $t['name'],
        'category' => $t['category'] ?? '',
        'description' => mb_substr($t['description'] ?? '', 0, 80) . (isset($t['description']) && mb_strlen($t['description']) > 80 ? '...' : ''),
        'campaign_config' => $t['campaign_config'],
        'adset_config' => $t['adset_config'],
        'creative_config' => $t['creative_config'],
        'ad_config' => $t['ad_config']
    ];
}, $campaignTemplates)); ?>;

const editCampaign = <?php echo !empty($editCampaign) ? json_encode($editCampaign) : 'null'; ?>;

console.log('editCampaign:', editCampaign);

function handleBrandDNASelect(select) {
    if (select.value === '__create_new__') {
        // Redirect to create new Brand DNA
        const campaignUrl = window.location.href;
        window.location.href = '<?php echo $baseUrl; ?>brand-dna/create?redirect=' + encodeURIComponent(campaignUrl);
        return;
    }
    updateBrandDNAPreview();
}

function updateBrandDNAPreview() {
    const select = document.getElementById('brandDNASelect');
    const preview = document.getElementById('brandDNAPreview');
    const selectedId = select.value;
    
    if (!selectedId) {
        preview.style.display = 'none';
        return;
    }
    
    const selectedDNA = brandDNAs.find(d => d.id == selectedId);
    if (selectedDNA) {
        preview.innerHTML = '<h4>🎨 ADN: ' + selectedDNA.name + '</h4><p>' + selectedDNA.description + '</p>';
        preview.style.display = 'block';
    }
}

// Guardar datos del wizard en localStorage
function saveWizardData() {
    console.log('=== SAVING WIZARD DATA ===');
    console.log('access_token value:', document.getElementById('accessToken')?.value ? 'HAS VALUE (' + document.getElementById('accessToken').value.length + ' chars)' : 'EMPTY');
    
    // Ensure locations input is updated before saving
    if (document.getElementById('locationsInput')) {
        document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
        console.log('locationsInput updated:', document.getElementById('locationsInput').value);
    }
    
    const data = {
        // Step 1
        name: document.querySelector('input[name="name"]')?.value || '',
        objective: document.getElementById('objectiveInput')?.value || '',
        client_id: document.querySelector('select[name="client_id"]')?.value || '',
        brand_dna_id: document.getElementById('brandDNASelect')?.value || '',
        // Step 2
        social_account_id: document.getElementById('socialAccountInput')?.value || '',
        social_account_name: document.getElementById('socialAccountName')?.value || '',
        access_token: document.getElementById('accessToken')?.value || '',
        business_manager_id: document.getElementById('businessManagerId')?.value || '',
        business_manager_name: document.getElementById('businessManagerName')?.value || '',
        ad_account_id: document.getElementById('adAccountId')?.value || '',
        ad_account_name: document.getElementById('adAccountName')?.value || '',
        fan_page_id: document.getElementById('fanPageId')?.value || '',
        fan_page_name: document.getElementById('fanPageName')?.value || '',
        instagram_id: document.getElementById('instagramId')?.value || '',
        instagram_name: document.getElementById('instagramName')?.value || '',
        whatsapp_id: document.getElementById('whatsappId')?.value || '',
        whatsapp_name: document.getElementById('whatsappName')?.value || '',
        pixel_id: document.getElementById('pixelId')?.value || '',
        pixel_name: document.getElementById('pixelName')?.value || '',
        // Step 3
        audience_type: document.getElementById('audienceType')?.value || '',
        saved_audiences: selectedAudiences,
        locations: selectedLocations,
        age_min: document.querySelector('select[name="age_min"]')?.value || '18',
        age_max: document.querySelector('select[name="age_max"]')?.value || '65',
        gender: document.querySelector('select[name="gender"]')?.value || 'all',
        interests: selectedInterests,
        // Step 4 - Strategy
        template_id: document.getElementById('templateIdInput')?.value || '',
        template_name: document.getElementById('templateNameInput')?.value || '',
        special_ad_categories: document.querySelector('select[name="special_ad_categories"]')?.value || '',
        // Step 5 - Budget
        budget_field_name: document.getElementById('budgetAmountInput')?.name || '',
        budget_value: document.getElementById('budgetAmountInput')?.value || '',
        start_time: document.querySelector('input[name="start_date"]')?.value ? document.querySelector('input[name="start_date"]').value + 'T00:00:00-0500' : '',
        end_time: document.querySelector('input[name="end_date"]')?.value ? document.querySelector('input[name="end_date"]').value + 'T23:59:00-0500' : '',
        // Step 6 - Creative
        ad_headline: document.querySelector('input[name="ad_headline"]')?.value || '',
        ad_description: document.querySelector('textarea[name="ad_description"]')?.value || '',
        ad_url: document.querySelector('input[name="ad_url"]')?.value || ''
    };
    console.log('Saving wizard data - locations:', selectedLocations);
    localStorage.setItem('wizard_campaign_data', JSON.stringify(data));
    console.log('Wizard data saved:', data);
}

// Cargar datos del wizard desde localStorage
function loadWizardData() {
    const savedData = localStorage.getItem('wizard_campaign_data');
    if (!savedData) return;
    
    try {
        const data = JSON.parse(savedData);
        console.log('Loading wizard data:', data);
        
        // Step 1
        if (data.name) document.querySelector('input[name="name"]').value = data.name;
        if (data.objective) document.getElementById('objectiveInput').value = data.objective;
        if (data.client_id) document.querySelector('select[name="client_id"]').value = data.client_id;
        if (data.brand_dna_id) {
            document.getElementById('brandDNASelect').value = data.brand_dna_id;
            updateBrandDNAPreview();
        }
        
        // Step 2
        if (data.social_account_id) {
            document.getElementById('socialAccountInput').value = data.social_account_id;
            document.getElementById('socialAccountName').value = data.social_account_name || '';
            document.getElementById('accessToken').value = data.access_token || '';
            selectedAccountId = data.social_account_id;
            selectedAccountName = data.social_account_name || '';
            
            // Si el cuadro resumen existe y está visible, NO cargamos los selects
            // porque el usuario está viendo la versión simplificada (tu idea)
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Mostrar accountDetails
                document.getElementById('accountDetails').style.display = 'block';
                document.getElementById('selectAccountHint').style.display = 'none';
                
                // Highlight selected account
                document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
                
                const accountElements = document.querySelectorAll('.account-option');
                accountElements.forEach(el => {
                    const acctId = el.getAttribute('data-account-id');
                    if (acctId == data.social_account_id) {
                        el.classList.add('selected');
                    }
                });
                
                const accessToken = data.access_token || document.getElementById('accessToken').value;
                if (accessToken) {
                    setTimeout(() => {
                        loadBusinessManagers(accessToken);
                    }, 100);
                }
            }
        }
        
        if (data.business_manager_id) {
            document.getElementById('businessManagerId').value = data.business_manager_id;
            document.getElementById('businessManagerName').value = data.business_manager_name || '';
            selectedBusinessManagerId = data.business_manager_id;
            selectedBusinessManagerName = data.business_manager_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load ad accounts for the business manager
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken) {
                    setTimeout(() => {
                        loadAdAccountsFromBusinessManager(accessToken, data.business_manager_id);
                    }, 500);
                }
            }
        }
        
        if (data.ad_account_id) {
            document.getElementById('adAccountId').value = data.ad_account_id;
            document.getElementById('adAccountName').value = data.ad_account_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load fan pages and pixels
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken) {
                    setTimeout(() => {
                        loadFanPagesFromAccount(accessToken);
                        loadPixelsFromAdAccount(accessToken, data.ad_account_id);
                    }, 1000);
                }
            }
        }
        
        if (data.fan_page_id) {
            document.getElementById('fanPageId').value = data.fan_page_id;
            document.getElementById('fanPageName').value = data.fan_page_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load Instagram and WhatsApp
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken && data.fan_page_id) {
                    setTimeout(() => {
                        loadInstagramFromFanPage(accessToken, data.fan_page_id);
                        loadWhatsAppFromFanPage(accessToken, data.fan_page_id);
                    }, 1500);
                }
            }
        }
        
        if (data.instagram_id) {
            document.getElementById('instagramId').value = data.instagram_id;
            document.getElementById('instagramName').value = data.instagram_name || '';
        }
        
        if (data.whatsapp_id) {
            document.getElementById('whatsappId').value = data.whatsapp_id;
            document.getElementById('whatsappName').value = data.whatsapp_name || '';
        }
        
        if (data.pixel_id) {
            document.getElementById('pixelId').value = data.pixel_id;
            document.getElementById('pixelName').value = data.pixel_name || '';
        }
        
        // Step 3 - Audience type
        if (data.audience_type) {
            document.getElementById('audienceType').value = data.audience_type;
        }
        
        // Show/hide location picker based on audience type
        const locationContainer = document.getElementById('locationPickerContainer');
        if (locationContainer) {
            if (data.audience_type === 'saved') {
                locationContainer.style.display = 'none';
            } else {
                locationContainer.style.display = 'block';
            }
        }
        
        if (data.age_min) {
            document.querySelector('select[name="age_min"]').value = data.age_min;
        }
        if (data.age_max) {
            document.querySelector('select[name="age_max"]').value = data.age_max;
        }
        if (data.gender) {
            document.querySelector('select[name="gender"]').value = data.gender;
        }
        
        if (data.locations && data.locations.length > 0) {
            selectedLocations = data.locations;
            
            // Show map container
            document.getElementById('locationMapContainer').style.display = 'block';
            
            setTimeout(() => {
                initLocationMap();
                
                // Calculate center
                const avgLat = selectedLocations.reduce((sum, l) => sum + l.latitude, 0) / selectedLocations.length;
                const avgLng = selectedLocations.reduce((sum, l) => sum + l.longitude, 0) / selectedLocations.length;
                
                locationMap.setView([avgLat, avgLng], selectedLocations.length === 1 ? 10 : 5);
                
                locationMarker = L.layerGroup().addTo(locationMap);
                locationCircle = L.layerGroup().addTo(locationMap);
                
                selectedLocations.forEach(loc => {
                    if (loc.latitude && loc.longitude) {
                        const radius = loc.radius || 10;
                        
                        L.marker([loc.latitude, loc.longitude]).addTo(locationMarker)
                            .bindPopup(loc.name + ' (' + radius + ' km)');
                        
                        L.circle([loc.latitude, loc.longitude], {
                            radius: radius * 1000,
                            color: '#A8FF35',
                            fillColor: '#A8FF35',
                            fillOpacity: 0.2,
                            weight: 2
                        }).addTo(locationCircle);
                    }
                });
                
                locationMap.invalidateSize();
            }, 500);
            
            renderLocations();
        }
        
        if (data.interests && data.interests.length > 0) {
            selectedInterests = data.interests;
            renderInterests();
        }
        
        // Mostrar audiencias guardadas si existen
        if (data.saved_audiences && data.saved_audiences.length > 0) {
            selectedAudiences = data.saved_audiences;
            renderSelectedAudiences();
        }
        
        // Step 4 - Template/Strategy
        if (data.template_id) {
            document.getElementById('templateIdInput').value = data.template_id;
            document.getElementById('templateNameInput').value = data.template_name || '';
            selectedTemplateId = data.template_id;
            selectedTemplateName = data.template_name || '';
            
            // Highlight selected template card
            document.querySelectorAll('.template-card').forEach(card => {
                if (parseInt(card.dataset.templateId) === parseInt(data.template_id)) {
                    card.style.borderColor = 'var(--primary)';
                    card.style.background = 'rgba(168, 255, 53, 0.1)';
                }
            });
        }
        
        // Step 5 - Budget (new format: daily_budget or lifetime_budget)
        if (data.budget_field_name && data.budget_value) {
            const budgetInput = document.getElementById('budgetAmountInput');
            if (budgetInput) {
                budgetInput.name = data.budget_field_name;
                budgetInput.value = data.budget_value;
            }
            // Highlight the correct budget card
            const budgetLabel = document.getElementById('budgetLabel');
            document.querySelectorAll('.budget-card').forEach(el => {
                if (data.budget_field_name === 'daily_budget' && el.dataset.value === 'daily') {
                    el.classList.add('selected');
                    if (budgetLabel) budgetLabel.textContent = 'Presupuesto Diario ($)';
                } else if (data.budget_field_name === 'lifetime_budget' && el.dataset.value === 'lifetime') {
                    el.classList.add('selected');
                    if (budgetLabel) budgetLabel.textContent = 'Presupuesto Total ($)';
                }
            });
        }
        if (data.start_time || data.start_date) {
            document.querySelector('input[name="start_date"]').value = (data.start_time || data.start_date).split('T')[0];
        }
        if (data.end_time || data.end_date) {
            document.querySelector('input[name="end_date"]').value = (data.end_time || data.end_date).split('T')[0];
        }
        
        // Step 6 - Creative
        if (data.ad_headline) {
            document.querySelector('input[name="ad_headline"]').value = data.ad_headline;
        }
        if (data.ad_description) {
            document.querySelector('textarea[name="ad_description"]').value = data.ad_description;
        }
        if (data.ad_url) {
            document.querySelector('input[name="ad_url"]').value = data.ad_url;
        }
        
    } catch (e) {
        console.error('Error loading wizard data:', e);
    }
}

// Cargar datos al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    // PRE-INJECT EDIT DATA INTO LOCAL STORAGE IF IT'S EMPTY
    if (editCampaign && editCampaign.config) {
        let currentSavedData = localStorage.getItem('wizard_campaign_data');
        if (!currentSavedData || Object.keys(JSON.parse(currentSavedData)).length === 0) {
            console.log('Pre-injecting edit data into wizard local storage...');
            localStorage.setItem('wizard_campaign_data', JSON.stringify(editCampaign.config));
        }
    }

    // If editing campaign, load its data
    if (editCampaign && editCampaign.config) {
        const data = editCampaign.config;
        console.log('=== LOADING EDIT CAMPAIGN DATA ===');
        console.log('Full config:', data);
        
        // Parse JSON strings to objects if needed
        if (typeof data.locations === 'string') {
            try { data.locations = JSON.parse(data.locations); } catch(e) { data.locations = []; }
        }
        if (typeof data.interests === 'string') {
            try { data.interests = JSON.parse(data.interests); } catch(e) { data.interests = []; }
        }
        if (typeof data.saved_audiences === 'string') {
            try { data.saved_audiences = JSON.parse(data.saved_audiences); } catch(e) { data.saved_audiences = []; }
        }
        
        console.log('Locations:', data.locations);
        console.log('Interests:', data.interests);
        console.log('Audience type:', data.audience_type);
        console.log('Budget - daily_budget:', data.daily_budget);
        console.log('Budget - lifetime_budget:', data.lifetime_budget);
        console.log('Start date:', data.start_date);
        console.log('End date:', data.end_date);
        console.log('================================');
        
        // Debug Step 2 data
        console.log('Step 2 - social_account_id:', data.social_account_id);
        console.log('Step 2 - business_manager_id:', data.business_manager_id);
        console.log('Step 2 - ad_account_id:', data.ad_account_id);
        console.log('Step 2 - fan_page_id:', data.fan_page_id);
        console.log('Step 2 - access_token:', data.access_token ? 'HAS VALUE' : 'EMPTY');
        
        // Get additional data from table columns (budget, dates)
        const campaignTableData = {
            budget: editCampaign.budget || null,
            start_date: editCampaign.start_date || null,
            end_date: editCampaign.end_date || null
        };
        console.log('Campaign table data:', campaignTableData);
        
        // Load Step 1 data
        if (data.name) document.querySelector('input[name="name"]').value = data.name;
        if (data.objective) document.getElementById('objectiveInput').value = data.objective;
        if (data.client_id) document.querySelector('select[name="client_id"]').value = data.client_id;
        if (data.brand_dna_id) {
            document.getElementById('brandDNASelect').value = data.brand_dna_id;
            updateBrandDNAPreview();
        }
        
        // Load Step 2 data
        console.log('Step 2 - social_account_id:', data.social_account_id);
        console.log('Step 2 - business_manager_id:', data.business_manager_id);
        console.log('Step 2 - ad_account_id:', data.ad_account_id);
        console.log('Step 2 - fan_page_id:', data.fan_page_id);
        console.log('Step 2 - access_token:', data.access_token ? 'HAS VALUE' : 'EMPTY');
        
        if (data.social_account_id) {
            document.getElementById('socialAccountInput').value = data.social_account_id;
            document.getElementById('socialAccountName').value = data.social_account_name || '';
            document.getElementById('accessToken').value = data.access_token || '';
            selectedAccountId = data.social_account_id;
            selectedAccountName = data.social_account_name || '';
            document.getElementById('accountDetails').style.display = 'block';
            document.getElementById('selectAccountHint').style.display = 'none';
            
            // Highlight selected account
            document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
            const accountElements = document.querySelectorAll('.account-option');
            accountElements.forEach(el => {
                if (el.querySelector('.account-id')?.textContent.includes(data.social_account_id)) {
                    el.classList.add('selected');
                }
            });
            
            const accessToken = data.access_token || document.getElementById('accessToken').value;
            if (accessToken) {
                setTimeout(() => {
                    loadBusinessManagers(accessToken);
                }, 100);
            }
        }
        
        if (data.business_manager_id) {
            document.getElementById('businessManagerId').value = data.business_manager_id;
            document.getElementById('businessManagerName').value = data.business_manager_name || '';
            selectedBusinessManagerId = data.business_manager_id;
            selectedBusinessManagerName = data.business_manager_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load ad accounts for the business manager
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken) {
                    setTimeout(() => {
                        loadAdAccountsFromBusinessManager(accessToken, data.business_manager_id);
                    }, 500);
                }
            }
        }
        
        if (data.ad_account_id) {
            document.getElementById('adAccountId').value = data.ad_account_id;
            document.getElementById('adAccountName').value = data.ad_account_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load fan pages and pixels
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken) {
                    setTimeout(() => {
                        loadFanPagesFromAccount(accessToken);
                        loadPixelsFromAdAccount(accessToken, data.ad_account_id);
                    }, 1000);
                }
            }
        }
        
        if (data.fan_page_id) {
            document.getElementById('fanPageId').value = data.fan_page_id;
            document.getElementById('fanPageName').value = data.fan_page_name || '';
            
            const summaryBox = document.getElementById('savedAccountSummary');
            if (!summaryBox || summaryBox.style.display === 'none') {
                // Auto-load Instagram and WhatsApp
                const accessToken = data.access_token || document.getElementById('accessToken')?.value;
                if (accessToken && data.fan_page_id) {
                    setTimeout(() => {
                        loadInstagramFromFanPage(accessToken, data.fan_page_id);
                        loadWhatsAppFromFanPage(accessToken, data.fan_page_id);
                    }, 1500);
                }
            }
        }
        
        if (data.instagram_id) {
            document.getElementById('instagramId').value = data.instagram_id;
            document.getElementById('instagramName').value = data.instagram_name || '';
        }
        
        if (data.whatsapp_id) {
            document.getElementById('whatsappId').value = data.whatsapp_id;
            document.getElementById('whatsappName').value = data.whatsapp_name || '';
        }
        
        if (data.pixel_id) {
            document.getElementById('pixelId').value = data.pixel_id;
            document.getElementById('pixelName').value = data.pixel_name || '';
        }
        
        // Load Step 3 data
        if (data.audience_type) {
            document.getElementById('audienceType').value = data.audience_type;
            
            // Show/hide location picker based on audience type
            const locationContainer = document.getElementById('locationPickerContainer');
            if (locationContainer) {
                if (data.audience_type === 'saved') {
                    locationContainer.style.display = 'none';
                } else {
                    locationContainer.style.display = 'block';
                }
            }
        }
        
        if (data.age_min) {
            document.querySelector('select[name="age_min"]').value = data.age_min;
        }
        if (data.age_max) {
            document.querySelector('select[name="age_max"]').value = data.age_max;
        }
        if (data.gender) {
            document.querySelector('select[name="gender"]').value = data.gender;
        }
        
        // Load locations (Step 3) - needs map initialization
        console.log('Loading locations, count:', data.locations ? data.locations.length : 0);
        if (data.locations && data.locations.length > 0) {
            selectedLocations = data.locations;
            document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
            document.getElementById('locationMapContainer').style.display = 'block';
            
            // Wait for the map container to be visible and initialized
            setTimeout(() => {
                console.log('Initializing map for locations');
                
                // Initialize map if not already done
                if (!locationMap) {
                    const mapElement = document.getElementById('locationMap');
                    if (mapElement) {
                        locationMap = L.map('locationMap').setView([0, 0], 2);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(locationMap);
                    }
                }
                
                if (locationMap) {
                    const avgLat = selectedLocations.reduce((sum, l) => sum + (parseFloat(l.latitude) || 0), 0) / selectedLocations.length;
                    const avgLng = selectedLocations.reduce((sum, l) => sum + (parseFloat(l.longitude) || 0), 0) / selectedLocations.length;
                    
                    locationMap.setView([avgLat, avgLng], selectedLocations.length === 1 ? 10 : 5);
                    
                    locationMarker = L.layerGroup().addTo(locationMap);
                    locationCircle = L.layerGroup().addTo(locationMap);
                    
                    selectedLocations.forEach(loc => {
                        if (loc.latitude && loc.longitude) {
                            const radius = loc.radius || 10;
                            
                            L.marker([loc.latitude, loc.longitude]).addTo(locationMarker)
                                .bindPopup(loc.name + ' (' + radius + ' km)');
                            
                            L.circle([loc.latitude, loc.longitude], {
                                radius: radius * 1000,
                                color: '#A8FF35',
                                fillColor: '#A8FF35',
                                fillOpacity: 0.2,
                                weight: 2
                            }).addTo(locationCircle);
                        }
                    });
                    
                    locationMap.invalidateSize();
                }
                
                renderLocations();
            }, 100);
        }
        
        // Load interests (Step 3)
        console.log('Loading interests, count:', data.interests ? data.interests.length : 0);
        if (data.interests && data.interests.length > 0) {
            selectedInterests = data.interests;
            document.getElementById('interestsInput').value = JSON.stringify(selectedInterests);
            renderInterests();
        }
        
        // Load saved audiences (Step 3)
        console.log('Loading saved audiences, count:', data.saved_audiences ? data.saved_audiences.length : 0);
        if (data.saved_audiences && data.saved_audiences.length > 0) {
            selectedAudiences = data.saved_audiences;
            document.getElementById('savedAudiencesInput').value = JSON.stringify(selectedAudiences);
            renderSelectedAudiences();
        }
        
        // Load Step 4 - Template
        if (data.template_id) {
            document.getElementById('templateIdInput').value = data.template_id;
            document.getElementById('templateNameInput').value = data.template_name || '';
            selectedTemplateId = data.template_id;
            selectedTemplateName = data.template_name || '';
            
            document.querySelectorAll('.template-card').forEach(card => {
                if (parseInt(card.dataset.templateId) === parseInt(data.template_id)) {
                    card.style.borderColor = 'var(--primary)';
                    card.style.background = 'rgba(168, 255, 53, 0.1)';
                }
            });
        }
        
        // Load Special Ad Categories (Step 4)
        if (data.special_ad_categories) {
            document.querySelector('select[name="special_ad_categories"]').value = data.special_ad_categories;
        }
        
        // Load Step 5 - Budget (new format: daily_budget or lifetime_budget)
        // Support both old format (budget_type + budget) and new format (daily_budget/lifetime_budget)
        let budgetFieldName = '';
        let budgetValue = '';
        
        if (data.daily_budget) {
            budgetFieldName = 'daily_budget';
            budgetValue = data.daily_budget;
        } else if (data.lifetime_budget) {
            budgetFieldName = 'lifetime_budget';
            budgetValue = data.lifetime_budget;
        } else if (data.budget_field_name && data.budget_value) {
            budgetFieldName = data.budget_field_name;
            budgetValue = data.budget_value;
        } else if (data.budget_type && (data.budget || data.budget_amount)) {
            // Backward compatibility with old format
            budgetFieldName = data.budget_type === 'lifetime_budget' ? 'lifetime_budget' : 'daily_budget';
            budgetValue = data.budget || data.budget_amount;
        }
        
        const startDate = data.start_time || data.start_date || '';
        const endDate = data.end_time || data.end_date || '';
        
        console.log('Budget data - field:', budgetFieldName, 'value:', budgetValue, 'start:', startDate, 'end:', endDate);
        
        const budgetAmountEl = document.getElementById('budgetAmountInput');
        const startDateEl = document.querySelector('input[name="start_date"]');
        const endDateEl = document.querySelector('input[name="end_date"]');
        
        if (budgetAmountEl && budgetFieldName && budgetValue) {
            budgetAmountEl.name = budgetFieldName;
            budgetAmountEl.value = budgetValue;
            
            // Highlight the correct budget card and update label
            const budgetLabel = document.getElementById('budgetLabel');
            document.querySelectorAll('.budget-card').forEach(el => {
                if (budgetFieldName === 'daily_budget' && el.dataset.value === 'daily') {
                    el.classList.add('selected');
                    if (budgetLabel) budgetLabel.textContent = 'Presupuesto Diario ($)';
                } else if (budgetFieldName === 'lifetime_budget' && el.dataset.value === 'lifetime') {
                    el.classList.add('selected');
                    if (budgetLabel) budgetLabel.textContent = 'Presupuesto Total ($)';
                }
            });
        }
        if (startDateEl && startDate) {
            startDateEl.value = startDate.split('T')[0];
        }
        if (endDateEl && endDate) {
            endDateEl.value = endDate.split('T')[0];
        }
        
        // Load Step 6 - Creative
        if (data.image_prompt) {
            document.querySelector('input[name="image_prompt"]').value = data.image_prompt;
        }
        if (data.ad_url) {
            document.querySelector('input[name="ad_url"]').value = data.ad_url;
        }
        if (data.ad_headline) {
            document.querySelector('input[name="ad_headline"]').value = data.ad_headline;
        }
        if (data.ad_description) {
            document.querySelector('textarea[name="ad_description"]').value = data.ad_description;
        }
        
        // Save to localStorage for consistency
        saveWizardData();
    } else {
        loadWizardData();
    }
    
    // Set initial visibility of location picker based on audience type
    const audienceType = document.getElementById('audienceType')?.value;
    const locationContainer = document.getElementById('locationPickerContainer');
    if (locationContainer) {
        if (audienceType === 'saved') {
            locationContainer.style.display = 'none';
        } else {
            locationContainer.style.display = 'block';
        }
    }
    
    // Auto-guardar datos cuando el usuario escribe en cualquier campo del wizard
    const wizardForm = document.querySelector('.wizard-container');
    if (wizardForm) {
        wizardForm.addEventListener('input', function(e) {
            const tagName = e.target.tagName.toLowerCase();
            if (tagName === 'input' || tagName === 'select' || tagName === 'textarea') {
                saveWizardData();
            }
        });
    }
});

function selectObjective(value, element) {
    document.querySelectorAll('.objective-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('objectiveInput').value = value;
}

function editAccountConfig() {
    // Hide summary box and show the regular selectors
    const summaryBox = document.getElementById('savedAccountSummary');
    if (summaryBox) summaryBox.style.display = 'none';
    
    document.getElementById('accountSelectionContainer').style.display = 'block';
    
    // Clear all existing values since they want to change them
    document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
    document.getElementById('accountDetails').style.display = 'none';
    document.getElementById('selectAccountHint').style.display = 'block';
    
    // Reset hidden inputs so it doesn't send old data if they leave it incomplete
    document.getElementById('socialAccountInput').value = '';
    document.getElementById('socialAccountName').value = '';
    document.getElementById('accessToken').value = '';
    
    resetDependentFields();
    saveWizardData();
}

function selectAccount(accountId, accountName, accessToken, element) {
    document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('socialAccountInput').value = accountId;
    document.getElementById('socialAccountName').value = accountName;
    document.getElementById('accessToken').value = accessToken;
    selectedAccountId = accountId;
    selectedAccountName = accountName;
    
    // Si hay una tarjeta de configuración guardada, ocultarla y activar el Step 2 para edición
    const savedCard = document.getElementById('savedAccountSummary');
    if (savedCard) savedCard.style.display = 'none';
    // Mostrar los campos del Step 2 y ocultar la selección de cuentas del Step 2
    document.getElementById('accountDetails').style.display = 'block';
    document.getElementById('accountSelectionContainer').style.display = 'none';
    document.getElementById('selectAccountHint').style.display = 'none';
    
    // Save data
    saveWizardData();
    
    // Reset all fields dependientes
    resetDependentFields();
    
    // Cargar BM con la cuenta seleccionada
    loadBusinessManagers(accessToken);
}

function resetDependentFields() {
    document.getElementById('businessManagersList').innerHTML = '<p style="color: var(--text-muted);">Cargando...</p>';
    document.getElementById('adAccountsList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Business Manager primero</p>';
    document.getElementById('fanPagesList').innerHTML = '<p style="color: var(--text-muted);">Selecciona una cuenta publicitaria primero</p>';
    document.getElementById('instagramList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('whatsappList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('pixelsList').innerHTML = '<p style="color: var(--text-muted);">Selecciona una cuenta publicitaria primero</p>';
    
    document.getElementById('businessManagerId').value = '';
    document.getElementById('businessManagerName').value = '';
    document.getElementById('adAccountId').value = '';
    document.getElementById('adAccountName').value = '';
    document.getElementById('fanPageId').value = '';
    document.getElementById('fanPageName').value = '';
    document.getElementById('instagramId').value = '';
    document.getElementById('instagramName').value = '';
    document.getElementById('whatsappId').value = '';
    document.getElementById('whatsappName').value = '';
    document.getElementById('pixelId').value = '';
    document.getElementById('pixelName').value = '';
}

function loadBusinessManagers(accessToken) {
    const loading = document.getElementById('businessManagersLoading');
    const list = document.getElementById('businessManagersList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/businessmanagers?token=' + encodeURIComponent(accessToken))
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.business_managers && data.business_managers.length > 0) {
                let html = '<select class="form-control" onchange="selectBusinessManager(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Business Manager...</option>';
                const savedValue = document.getElementById('businessManagerId').value;
                data.business_managers.forEach(bm => {
                    const selected = savedValue === bm.id ? 'selected' : '';
                    html += '<option value="' + bm.id + '" ' + selected + '>' + bm.name + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                // Si no hay BM, usar la cuenta personal como "sin BM"
                let html = '<p style="color: var(--text-muted);">No se encontraron Business Managers. Usando cuenta directa...</p>';
                html += '<input type="hidden" id="usePersonalAccount" value="1">';
                list.innerHTML = html;
                // Cargar directamente sin BM
                loadAdAccountsFromBusinessManager(accessToken, '');
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('Error loading business managers:', error);
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectBusinessManager(businessManagerId, businessManagerName) {
    document.getElementById('businessManagerId').value = businessManagerId;
    document.getElementById('businessManagerName').value = businessManagerName;
    selectedBusinessManagerId = businessManagerId;
    selectedBusinessManagerName = businessManagerName;
    
    saveWizardData();
    
    // Reset dependent fields
    document.getElementById('adAccountsList').innerHTML = '<p style="color: var(--text-muted);">Cargando...</p>';
    document.getElementById('fanPagesList').innerHTML = '<p style="color: var(--text-muted);">Selecciona una cuenta publicitaria primero</p>';
    document.getElementById('instagramList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('whatsappList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('pixelsList').innerHTML = '<p style="color: var(--text-muted);">Selecciona una cuenta publicitaria primero</p>';
    
    document.getElementById('adAccountId').value = '';
    document.getElementById('adAccountName').value = '';
    document.getElementById('fanPageId').value = '';
    document.getElementById('fanPageName').value = '';
    document.getElementById('instagramId').value = '';
    document.getElementById('instagramName').value = '';
    document.getElementById('whatsappId').value = '';
    document.getElementById('whatsappName').value = '';
    document.getElementById('pixelId').value = '';
    document.getElementById('pixelName').value = '';
    
    const accessToken = document.getElementById('accessToken').value;
    if (accessToken) {
        loadAdAccountsFromBusinessManager(accessToken, businessManagerId);
    }
}

function loadAdAccountsFromBusinessManager(accessToken, businessManagerId) {
    const loading = document.getElementById('adAccountsLoading');
    const list = document.getElementById('adAccountsList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    console.log('Loading ad accounts...', {accessToken: accessToken, businessManagerId: businessManagerId});
    
    let url = '<?php echo $baseUrl; ?>connections/adaccounts?platform=facebook&token=' + encodeURIComponent(accessToken);
    if (businessManagerId) {
        url += '&business_id=' + encodeURIComponent(businessManagerId);
    }
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Ad accounts data:', data);
            loading.style.display = 'none';
            if (data.success && data.ad_accounts && data.ad_accounts.length > 0) {
                let html = '<select class="form-control" onchange="selectAdAccount(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Cuenta Ads...</option>';
                const savedValue = document.getElementById('adAccountId').value;
                data.ad_accounts.forEach(account => {
                    const selected = savedValue === account.id ? 'selected' : '';
                    html += '<option value="' + account.id + '" ' + selected + '>' + account.name + ' (' + account.id + ')</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontraron cuentas publicitarias.</p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('Error loading ad accounts:', error);
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectAdAccount(accountId, accountName) {
    document.getElementById('adAccountId').value = accountId;
    document.getElementById('adAccountName').value = accountName;
    
    saveWizardData();
    
    // Reset dependent fields
    document.getElementById('fanPagesList').innerHTML = '<p style="color: var(--text-muted);">Cargando Fan Pages...</p>';
    document.getElementById('instagramList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('whatsappList').innerHTML = '<p style="color: var(--text-muted);">Selecciona un Fan Page primero</p>';
    document.getElementById('pixelsList').innerHTML = '<p style="color: var(--text-muted);">Cargando Pixels...</p>';
    
    document.getElementById('fanPageId').value = '';
    document.getElementById('fanPageName').value = '';
    document.getElementById('instagramId').value = '';
    document.getElementById('instagramName').value = '';
    document.getElementById('whatsappId').value = '';
    document.getElementById('whatsappName').value = '';
    document.getElementById('pixelId').value = '';
    document.getElementById('pixelName').value = '';
    
    const accessToken = document.getElementById('accessToken').value;
    if (accessToken && accountId) {
        // Load Fan Pages and Pixels
        loadFanPagesFromAccount(accessToken);
        loadPixelsFromAdAccount(accessToken, accountId);
    }
}

function loadFanPagesFromAccount(accessToken) {
    const loading = document.getElementById('fanPagesLoading');
    const list = document.getElementById('fanPagesList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/pages?platform=facebook&token=' + encodeURIComponent(accessToken))
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.pages && data.pages.length > 0) {
                let html = '<select class="form-control" onchange="selectFanPage(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Fan Page...</option>';
                const savedValue = document.getElementById('fanPageId').value;
                data.pages.forEach(page => {
                    const selected = savedValue === page.id ? 'selected' : '';
                    html += '<option value="' + page.id + '" ' + selected + '>' + page.name + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontraron Fan Pages.</p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectFanPage(pageId, pageName) {
    document.getElementById('fanPageId').value = pageId;
    document.getElementById('fanPageName').value = pageName;
    
    saveWizardData();
    
    // Reset dependent fields
    document.getElementById('instagramList').innerHTML = '<p style="color: var(--text-muted);">Cargando Instagram...</p>';
    document.getElementById('whatsappList').innerHTML = '<p style="color: var(--text-muted);">Cargando WhatsApp...</p>';
    
    document.getElementById('instagramId').value = '';
    document.getElementById('instagramName').value = '';
    document.getElementById('whatsappId').value = '';
    document.getElementById('whatsappName').value = '';
    
    const accessToken = document.getElementById('accessToken').value;
    if (accessToken && pageId) {
        // Load Instagram page and WhatsApp for this Fan Page
        loadInstagramFromFanPage(accessToken, pageId);
        loadWhatsAppFromFanPage(accessToken, pageId);
    }
}

function loadInstagramFromFanPage(accessToken, fanPageId) {
    const loading = document.getElementById('instagramLoading');
    const list = document.getElementById('instagramList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/instagram?token=' + encodeURIComponent(accessToken) + '&fanpage=' + encodeURIComponent(fanPageId))
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.instagram_pages && data.instagram_pages.length > 0) {
                let html = '<select class="form-control" onchange="selectInstagram(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Instagram...</option>';
                const savedValue = document.getElementById('instagramId').value;
                data.instagram_pages.forEach(page => {
                    const selected = savedValue === page.id ? 'selected' : '';
                    html += '<option value="' + page.id + '" ' + selected + '>' + page.username + ' (' + page.name + ')</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                                list.innerHTML = '<p style="color: var(--warning);">Tu Instagram no está correctamente vinculado como cuenta Business en este Business Manager.</p>' +
                    '<p style="margin-top: 8px;"><a href="https://www.google.com/search?q=como+vincular+instagram+facebook+business+manager" target="_blank" style="color: var(--primary);">📖 Click aquí para ver cómo solucionarlo</a></p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectInstagram(instagramId, instagramName) {
    document.getElementById('instagramId').value = instagramId;
    document.getElementById('instagramName').value = instagramName;
    saveWizardData();
}

function loadWhatsAppFromFanPage(accessToken, fanPageId) {
    const loading = document.getElementById('whatsappLoading');
    const list = document.getElementById('whatsappList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/whatsapp?token=' + encodeURIComponent(accessToken) + '&fanpage=' + encodeURIComponent(fanPageId))
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.whatsapp_accounts && data.whatsapp_accounts.length > 0) {
                let html = '<select class="form-control" onchange="selectWhatsApp(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar WhatsApp...</option>';
                const savedValue = document.getElementById('whatsappId').value;
                data.whatsapp_accounts.forEach(wa => {
                    const phoneInfo = wa.phone ? ' - ' + wa.phone : '';
                    const selected = savedValue === wa.id ? 'selected' : '';
                    html += '<option value="' + wa.id + '" ' + selected + '>' + wa.name + phoneInfo + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">WhatsApp Business es opcional. No se encontró WhatsApp vinculado a este Fan Page.</p>' +
                    '<p style="margin-top: 8px;"><a href="https://www.google.com/search?q=como+vincular+whatsapp+business+facebook" target="_blank" style="color: var(--primary);">📖 Click aquí para ver cómo solucionarlo</a></p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectWhatsApp(whatsappId, whatsappName) {
    document.getElementById('whatsappId').value = whatsappId;
    document.getElementById('whatsappName').value = whatsappName;
    saveWizardData();
}

function loadPixelsFromAdAccount(accessToken, adAccountId) {
    const loading = document.getElementById('pixelsLoading');
    const list = document.getElementById('pixelsList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/pixels?token=' + encodeURIComponent(accessToken) + '&adaccount=' + encodeURIComponent(adAccountId))
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.pixels && data.pixels.length > 0) {
                let html = '<select class="form-control" onchange="selectPixel(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Pixel...</option>';
                const savedValue = document.getElementById('pixelId').value;
                data.pixels.forEach(pixel => {
                    const accountInfo = pixel.ad_account_name ? ' (' + pixel.ad_account_name + ')' : '';
                    const selected = savedValue === pixel.id ? 'selected' : '';
                    html += '<option value="' + pixel.id + '" ' + selected + '>' + pixel.name + accountInfo + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">Pixel es opcional. No se encontraron Pixels para esta cuenta publicitaria.</p>' +
                    '<p style="margin-top: 8px;"><a href="https://www.google.com/search?q=como+crear+facebook+pixel+business+manager" target="_blank" style="color: var(--primary);">📖 Click aquí para ver cómo solucionarlo</a></p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectPixel(pixelId, pixelName) {
    document.getElementById('pixelId').value = pixelId;
    document.getElementById('pixelName').value = pixelName;
    saveWizardData();
}

function selectTemplate(value, element) {
    document.querySelectorAll('.template-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('templateInput').value = value;
}

function selectBudgetType(value, element) {
    document.querySelectorAll('.budget-card').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    
    // Change the input name to daily_budget or lifetime_budget
    const budgetInput = document.getElementById('budgetAmountInput');
    budgetInput.name = value;
    
    // Update label
    const label = document.getElementById('budgetLabel');
    if (value === 'daily_budget') {
        label.textContent = 'Presupuesto Diario ($)';
    } else {
        label.textContent = 'Presupuesto Total ($)';
    }
    
    saveWizardData();
}

function locationTypeChanged() {
    selectedLocations = [];
    document.getElementById('selectedLocations').innerHTML = '';
    document.getElementById('locationsInput').value = '';
    document.getElementById('locationMapContainer').style.display = 'none';
}

let locationMap = null;
let locationMarker = null;
let locationCircle = null;

function initLocationMap() {
    if (locationMap) return;
    
    locationMap = L.map('locationMap').setView([0, 0], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(locationMap);
}

function searchLocations(keyword) {
    clearTimeout(searchTimeoutLocation);
    const results = document.getElementById('locationResults');
    
    if (keyword.length < 2) {
        results.classList.remove('active');
        return;
    }
    
    searchTimeoutLocation = setTimeout(() => {
        // Use OpenStreetMap Nominatim API (no token required)
        fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(keyword) + '&format=json&limit=5&addressdetails=1')
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    let html = '';
                    data.forEach(loc => {
                        const locName = loc.display_name || loc.name || 'Ubicación sin nombre';
                        const locLat = parseFloat(loc.lat) || 0;
                        const locLng = parseFloat(loc.lon) || 0;
                        const locKey = loc.place_id || '';
                        
                        // Format the display name to be shorter
                        const shortName = locName.split(',').slice(0, 3).join(', ');
                        
                        html += `<div class="search-result-item" onclick="selectLocation('${locKey}', '${shortName.replace(/'/g, "\\'")}', ${locLat}, ${locLng})">
                            <div class="name">${shortName}</div>
                        </div>`;
                    });
                    results.innerHTML = html;
                    results.classList.add('active');
                } else {
                    results.innerHTML = '<div class="search-result-item">No se encontraron ubicaciones</div>';
                    results.classList.add('active');
                }
            })
            .catch(error => {
                console.error('Error searching locations:', error);
                results.innerHTML = '<div class="search-result-item">Error al buscar ubicaciones</div>';
                results.classList.add('active');
            });
    }, 300);
}

function selectLocation(key, name, latitude, longitude) {
    console.log('selectLocation called:', { key, name, latitude, longitude });
    
    document.getElementById('locationSearch').value = '';
    document.getElementById('locationResults').classList.remove('active');
    
    const radius = 10; // Default radius
    
    // Check if already exists (use lat/lng as key for Nominatim)
    const locKey = latitude + ',' + longitude;
    if (!selectedLocations.find(l => (l.latitude + ',' + l.longitude) === locKey)) {
        selectedLocations.push({
            key: locKey,
            name: name,
            latitude: parseFloat(latitude),
            longitude: parseFloat(longitude),
            radius: radius,
            distance_unit: 'kilometer'
        });
        
        // Update hidden input
        document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
    }
    
    console.log('selectedLocations:', selectedLocations);
    
    // Show map container
    document.getElementById('locationMapContainer').style.display = 'block';
    
    initLocationMap();
    
    // Clear layers
    if (locationMarker) locationMap.removeLayer(locationMarker);
    if (locationCircle) locationMap.removeLayer(locationCircle);
    
    locationMarker = L.layerGroup().addTo(locationMap);
    locationCircle = L.layerGroup().addTo(locationMap);
    
    selectedLocations.forEach(loc => {
        const rad = loc.radius || 10;
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        
        console.log('Drawing marker at:', lat, lng);
        
        L.marker([lat, lng]).addTo(locationMarker)
            .bindPopup(loc.name + ' (' + rad + ' km)');
        
        L.circle([lat, lng], {
            radius: rad * 1000,
            color: '#A8FF35',
            fillColor: '#A8FF35',
            fillOpacity: 0.2,
            weight: 2
        }).addTo(locationCircle);
    });
    
    // Fit view
    if (selectedLocations.length === 1) {
        locationMap.setView([parseFloat(latitude), parseFloat(longitude)], 10);
    } else {
        const group = new L.featureGroup(locationMarker.getLayers());
        locationMap.fitBounds(group.getBounds().pad(0.1));
    }
    
    setTimeout(() => {
        locationMap && locationMap.invalidateSize();
    }, 100);
    
    renderLocations();
    saveWizardData();
}

function redrawMapCircles() {
    if (!locationMap) return;
    
    // Get current center and zoom before redrawing
    const currentCenter = locationMap.getCenter();
    const currentZoom = locationMap.getZoom();
    
    // Clear all layers
    if (locationMarker) locationMap.removeLayer(locationMarker);
    if (locationCircle) locationMap.removeLayer(locationCircle);
    
    if (selectedLocations.length === 0) {
        return;
    }
    
    locationMarker = L.layerGroup().addTo(locationMap);
    locationCircle = L.layerGroup().addTo(locationMap);
    
    selectedLocations.forEach((loc, index) => {
        const rad = loc.radius || 10;
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        
        // Create draggable marker
        const marker = L.marker([lat, lng], { draggable: true }).addTo(locationMarker);
        marker.bindPopup(loc.name + ' (' + rad + ' km)');
        
        // Update location when marker is dragged
        marker.on('dragend', function(event) {
            const newLat = event.target.getLatLng().lat;
            const newLng = event.target.getLatLng().lng;
            
            selectedLocations[index].latitude = newLat;
            selectedLocations[index].longitude = newLng;
            
            renderLocations();
            redrawMapCircles();
            saveWizardData();
        });
        
        L.circle([lat, lng], {
            radius: rad * 1000,
            color: '#A8FF35',
            fillColor: '#A8FF35',
            fillOpacity: 0.2,
            weight: 2
        }).addTo(locationCircle);
    });
    
    // Restore center and zoom
    locationMap.setView(currentCenter, currentZoom);
}

function renderLocations() {
    const container = document.getElementById('selectedLocations');
    if (!container) return;
    
    let html = '';
    selectedLocations.forEach((loc, index) => {
        html += `<div class="location-chip" style="background: #1b1b1a; border: 1px solid var(--primary-dim); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 8px; cursor: pointer;" onclick="focusLocation(${index})">`;
        html += `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">`;
        html += `<strong>${loc.name}</strong>`;
        html += `<button type="button" onclick="event.stopPropagation(); removeLocation(${index})" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 18px; padding-left: 10px;"> ×</button>`;
        html += `</div>`;
        html += `<div style="display: flex; align-items: center; gap: 8px;">`;
        html += `<span style="font-size: 12px; color: var(--text-muted);">Radio:</span>`;
        html += `<input type="range" min="1" max="80" value="${loc.radius || 10}" style="flex: 1;" onclick="event.stopPropagation();" onchange="updateLocationRadius(${index}, this.value)" oninput="updateLocationRadius(${index}, this.value)">`;
        html += `<span style="font-size: 12px; min-width: 35px;">${loc.radius || 10} km</span>`;
        html += `</div>`;
        html += `</div>`;
    });
    
   
     
    container.innerHTML = html;
    document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
}

function centerAllMarkers() {
    if (!locationMap || selectedLocations.length === 0) return;
    
    if (selectedLocations.length === 1) {
        locationMap.setView([selectedLocations[0].latitude, selectedLocations[0].longitude], 10);
    } else {
        const group = new L.featureGroup();
        selectedLocations.forEach(loc => {
            group.addLayer(L.marker([loc.latitude, loc.longitude]));
        });
        locationMap.fitBounds(group.getBounds().pad(0.1));
    }
}

function focusLocation(index) {
    const loc = selectedLocations[index];
    if (!loc || !locationMap) return;
    
     // Calculate zoom based on radius
    const radius = loc.radius || 10;
    let zoom = 12;
    if (radius <= 10) {
        zoom = 13;
    } else if (radius <= 20) {
        zoom = 12;
    } else if (radius <= 40) {
        zoom = 11;
    } else {
        zoom = 10;
    }
    
    locationMap.setView([loc.latitude, loc.longitude], zoom);
    
    // Highlight the selected location marker
    if (locationMarker) {
        locationMarker.eachLayer(function(layer) {
            if (layer instanceof L.Marker) {
                if (layer.getLatLng().lat === loc.latitude && layer.getLatLng().lng === loc.longitude) {
                    layer.openPopup();
                }
            }
        });
    }
}

function updateLocationRadius(index, value) {
    if (selectedLocations[index]) {
        selectedLocations[index].radius = parseInt(value);
        
        // Update the displayed radius value
        const spans = document.querySelectorAll('#selectedLocations .location-chip');
        if (spans[index]) {
            const span = spans[index].querySelector('span:last-child');
            if (span) span.textContent = value + ' km';
        }
        
        // Redraw map circles
        redrawMapCircles();
        
        // Calculate zoom based on radius (closer zoom for smaller radius)
        const radius = parseInt(value);
        let zoom = 12; // default
        if (radius <= 10) {
            zoom = 13;
        } else if (radius <= 20) {
            zoom = 12;
        } else if (radius <= 40) {
            zoom = 11;
        } else {
            zoom = 10;
        }
        
        // Center on this specific location with calculated zoom
        const loc = selectedLocations[index];
        if (locationMap) {
            locationMap.setView([loc.latitude, loc.longitude], zoom, { animate: true });
        }
        
        saveWizardData();
    }
}

function removeLocation(index) {
    selectedLocations.splice(index, 1);
    document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
    renderLocations();
    redrawMapCircles();
    saveWizardData();
}

function changeAudienceType() {
    const audienceType = document.getElementById('audienceType').value;
    selectedAudiences = [];
    renderSelectedAudiences();
    document.getElementById('audiencesList').innerHTML = '';
    
    // Show/hide location map based on audience type
    // Show by default, hide for "saved" (audiencias guardadas), show for "custom" (publicos personalizados)
    const locationContainer = document.getElementById('locationPickerContainer');
    if (locationContainer) {
        if (audienceType === 'saved') {
            locationContainer.style.display = 'none';
            // Clear locations when switching to saved audiences
            selectedLocations = [];
            renderLocations();
            // Clear map
            if (locationMap) {
                if (locationMarker) locationMap.removeLayer(locationMarker);
                if (locationCircle) locationMap.removeLayer(locationCircle);
                locationMarker = null;
                locationCircle = null;
            }
            saveWizardData();
        } else {
            locationContainer.style.display = 'block';
        }
    }
    
    if (!audienceType) {
        return;
    }
    
    const adAccountId = document.getElementById('adAccountId').value;
    const accessToken = document.getElementById('accessToken').value;
    
    if (!adAccountId || !accessToken) {
        document.getElementById('audiencesList').innerHTML = '<p style="color: var(--warning);">Selecciona una cuenta publicitaria en el paso 2 primero.</p>';
        return;
    }
    
    loadAudiences(accessToken, adAccountId, audienceType);
}

function loadAudiences(accessToken, adAccountId, audienceType) {
    const loading = document.getElementById('audiencesLoading');
    const list = document.getElementById('audiencesList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    let endpoint = audienceType === 'saved' ? 'saved_audiences' : 'customaudiences';
    let url = '<?php echo $baseUrl; ?>connections/' + endpoint + '?token=' + encodeURIComponent(accessToken) + '&adaccount=' + encodeURIComponent(adAccountId);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.audiences && data.audiences.length > 0) {
                let html = '<select class="form-control" onchange="selectAudience(this.value, this.options[this.selectedIndex])"><option value="">Seleccionar audiencia...</option>';
                data.audiences.forEach(aud => {
                    const count = aud.approximate_count ? aud.approximate_count.toLocaleString() : 'N/A';
                    const name = aud.name || 'Audiencia sin nombre';
                    const description = aud.description || '';
                    html += '<option value="' + aud.id + '" data-name="' + name.replace(/"/g, '&quot;') + '" data-description="' + description.replace(/"/g, '&quot;') + '" data-count="' + count + '">' + name + ' (' + count + ')</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontraron audiencias de este tipo.</p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectAudience(audienceId, optionElement) {
    if (!audienceId) return;
    
    const audienceType = document.getElementById('audienceType').value;
    
    // Para audiencias guardadas solo se permite 1
    if (audienceType === 'saved') {
        // Verificar si ya hay una guardada seleccionada
        const existingSaved = selectedAudiences.find(a => a.type === 'saved');
        if (existingSaved) {
            alert('Solo puedes seleccionar una audiencia guardada. La anterior será reemplazada.');
            selectedAudiences = selectedAudiences.filter(a => a.type !== 'saved');
        }
    } else {
        // Para personalizados permitir múltiples, verificar si ya está seleccionada
        if (selectedAudiences.find(a => a.id === audienceId)) {
            alert('Esta audiencia ya está seleccionada');
            return;
        }
    }
    
    const audienceName = optionElement.getAttribute('data-name');
    const audienceDescription = optionElement.getAttribute('data-description');
    const audienceCount = optionElement.getAttribute('data-count');
    
    selectedAudiences.push({
        id: audienceId,
        name: audienceName,
        description: audienceDescription || '',
        count: audienceCount,
        type: audienceType
    });
    
    renderSelectedAudiences();
    saveWizardData();
    
    // Reset dropdown para permitir seleccionar otra audiencia (solo para personalizados)
    if (audienceType === 'custom') {
        const selectEl = document.querySelector('#audiencesList select');
        if (selectEl) {
            selectEl.selectedIndex = 0;
        }
    }
}

function renderSelectedAudiences() {
    const container = document.getElementById('selectedAudiencesList');
    if (!container) return;
    
    if (selectedAudiences.length === 0) {
        container.innerHTML = '';
        document.getElementById('savedAudiencesInput').value = '';
        return;
    }
    
    let html = '';
    selectedAudiences.forEach((aud, index) => {
        const typeLabel = aud.type === 'saved' ? 'Guardada' : 'Personalizada';
        html += '<div class="chip" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--primary); color: #000; border-radius: 20px; margin: 4px;">';
        html += '<span>' + aud.name + ' (' + aud.count + ') - ' + typeLabel + '</span>';
        html += '<button type="button" onclick="removeAudience(' + index + ')" style="background: none; border: none; color: #000; cursor: pointer; padding: 0; margin-left: 4px;">✕</button>';
        html += '</div>';
    });
    
    // Botón para agregar más audiencias (solo para personalizados)
    const audienceType = document.getElementById('audienceType')?.value;
    if (audienceType === 'custom' && selectedAudiences.length > 0) {
        html += '<button type="button" onclick="showAudienceList()" style="background: var(--bg-lighter); border: 1px dashed var(--border); border-radius: 20px; padding: 8px 16px; cursor: pointer; color: var(--text); margin: 4px;">+ Agregar más</button>';
    }
    
    container.innerHTML = html;
    
    // Update hidden input for form submission
    document.getElementById('savedAudiencesInput').value = JSON.stringify(selectedAudiences);
}

function removeAudience(index) {
    selectedAudiences.splice(index, 1);
    renderSelectedAudiences();
    saveWizardData();
}

function showAudienceList() {
    const audienceType = document.getElementById('audienceType').value;
    const adAccountId = document.getElementById('adAccountId').value;
    const accessToken = document.getElementById('accessToken').value;
    
    if (!adAccountId || !accessToken) {
        alert('Selecciona una cuenta publicitaria en el paso 2 primero.');
        return;
    }
    
    loadAudiences(accessToken, adAccountId, audienceType);
}

function removeSelectedAudience() {
    selectedAudiences = [];
    renderSelectedAudiences();
    saveWizardData();
}

function searchInterests(keyword) {
    clearTimeout(searchTimeoutInterest);
    const results = document.getElementById('interestResults');
    
    if (keyword.length < 2) {
        results.classList.remove('active');
        return;
    }
    
    searchTimeoutInterest = setTimeout(() => {
        fetch('<?php echo $baseUrl; ?>connections/interests?keyword=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.interests && data.interests.length > 0) {
                    let html = '';
                    data.interests.forEach(interest => {
                        html += `<div class="search-result-item" onclick="addInterest('${interest.id}', '${interest.name.replace(/'/g, "\\'")}')">
                            <div class="name">${interest.name}</div>
                        </div>`;
                    });
                    results.innerHTML = html;
                    results.classList.add('active');
                }
            });
    }, 300);
}

function addInterest(id, name) {
    if (!selectedInterests.find(i => i.id === id)) {
        selectedInterests.push({ id, name });
        renderInterests();
    }
    document.getElementById('interestSearch').value = '';
    document.getElementById('interestResults').classList.remove('active');
}

function renderInterests() {
    const container = document.getElementById('selectedInterests');
    let html = '';
    selectedInterests.forEach((interest, index) => {
        html += `<span class="chip selected">${interest.name}<span class="remove" onclick="removeInterest(${index})">×</span></span>`;
    });
    container.innerHTML = html;
    document.getElementById('interestsInput').value = JSON.stringify(selectedInterests);
}

function removeInterest(index) {
    selectedInterests.splice(index, 1);
    renderInterests();
}

function nextStep() {
    // Guardar datos antes de navegar
    saveWizardData();
    
    if (currentStep === 1) {
        const name = document.querySelector('input[name="name"]').value;
        const objective = document.getElementById('objectiveInput').value;
        const brandDNA = document.getElementById('brandDNASelect').value;
        
        if (!name) { alert('Por favor ingresa un nombre para la campaña'); return; }
        if (!objective) { alert('Por favor selecciona un objetivo'); return; }
        if (!brandDNA) { alert('Por favor selecciona un ADN de Marca'); return; }
    }
    if (currentStep === 2) {
        // Verificar si hay datos guardados o seleccionados
        const savedData = JSON.parse(localStorage.getItem('wizard_campaign_data') || '{}');
        const hasAdAccount = selectedAccountId || savedData.ad_account_id;
        if (!hasAdAccount) { alert('Por favor selecciona una cuenta publicitaria'); return; }
    }
    if (currentStep === 4) {
        // Paso de Estrategia - permitir continuar con o sin plantilla
        const templateId = document.getElementById('templateIdInput').value;
        const skipTemplate = document.getElementById('skipTemplate')?.checked;
        if (!templateId && !skipTemplate) {
            if (!confirm('¿Continuar sin seleccionar una plantilla de estrategia?')) {
                return;
            }
        }
    }
    
    // Preserve edit parameter if editing
    const editParam = editCampaign ? '&edit=' + editCampaign.id : '';
    window.location.href = '<?php echo $baseUrl; ?>campaigns/create?step=' + (currentStep + 1) + editParam;
}

function prevStep() {
    saveWizardData();
    const editParam = editCampaign ? '&edit=' + editCampaign.id : '';
    window.location.href = '<?php echo $baseUrl; ?>campaigns/create?step=' + (currentStep - 1) + editParam;
}

let selectedTemplateId = 0;
let selectedTemplateName = '';

function selectTemplate(id, name, element) {
    selectedTemplateId = id;
    selectedTemplateName = name;
    
    document.getElementById('templateIdInput').value = id;
    document.getElementById('templateNameInput').value = name;
    
    document.querySelectorAll('.template-card').forEach(card => {
        card.style.borderColor = 'var(--border)';
        card.style.background = 'var(--bg)';
    });
    element.style.borderColor = 'var(--primary)';
    element.style.background = 'rgba(168, 255, 53, 0.1)';
    
    document.getElementById('skipTemplate').checked = false;
}

function toggleSkipTemplate() {
    const skip = document.getElementById('skipTemplate').checked;
    if (skip) {
        selectedTemplateId = 0;
        selectedTemplateName = '';
        document.getElementById('templateIdInput').value = '';
        document.getElementById('templateNameInput').value = '';
        document.querySelectorAll('.template-card').forEach(card => {
            card.style.borderColor = 'var(--border)';
            card.style.background = 'var(--bg)';
        });
    }
}

function filterTemplates() {
    const category = document.getElementById('templateCategory').value;
    const cards = document.querySelectorAll('.template-card');
    const noTemplatesMsg = document.getElementById('noTemplatesMessage');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const cardCategory = card.dataset.category || '';
        if (category === '' || cardCategory === category) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    if (noTemplatesMsg) {
        noTemplatesMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-input')) {
        document.getElementById('locationResults').classList.remove('active');
        document.getElementById('interestResults').classList.remove('active');
    }
});

// Validate form before submit
document.getElementById('campaignForm').addEventListener('submit', function(e) {
    // Si estamos editando y no en el paso 2, el backend debe saber todo el estado
    saveWizardData();
    
    // Obtener los datos más recientes del wizard antes de enviar
    const savedDataStr = localStorage.getItem('wizard_campaign_data');
    if (savedDataStr) {
        try {
            const data = JSON.parse(savedDataStr);
            
            // Inyectar datos del Step 2 en hidden inputs del form si no estamos en el paso 2
            // Esto asegura que se envíe la información completa de la cuenta conectada sin importar el step
            if (currentStep !== 2) {
                const step2Fields = [
                    'social_account_id', 'social_account_name', 'access_token',
                    'business_manager_id', 'business_manager_name', 
                    'ad_account_id', 'ad_account_name',
                    'fan_page_id', 'fan_page_name',
                    'instagram_id', 'instagram_name',
                    'whatsapp_id', 'whatsapp_name',
                    'pixel_id', 'pixel_name'
                ];
                
                step2Fields.forEach(field => {
                    if (data[field]) {
                        // Buscar si ya existe
                        let hiddenInput = document.querySelector(`input[name="${field}"]`);
                        if (hiddenInput) {
                            hiddenInput.value = data[field];
                        } else {
                            // Crear input temporal para el submit
                            hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = field;
                            hiddenInput.value = data[field];
                            this.appendChild(hiddenInput);
                        }
                    }
                });
            }
        } catch(err) {
            console.error('Error inyectando datos faltantes:', err);
        }
    }
    
    // Debug: verify locations input before submit
    const locationsValue = document.getElementById('locationsInput')?.value;
    console.log('FORM SUBMIT - locationsInput value:', locationsValue);
    console.log('FORM SUBMIT - selectedLocations:', selectedLocations);
    
    const brandDNA = document.getElementById('brandDNASelect').value;
    if (!brandDNA) {
        e.preventDefault();
        alert('Por favor selecciona un ADN de Marca');
        return false;
    }
    
    // Ensure locations input has the latest value
    if (selectedLocations.length > 0) {
        document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
    }
    
    // Clear wizard data after successful submission
    localStorage.removeItem('wizard_campaign_data');
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
