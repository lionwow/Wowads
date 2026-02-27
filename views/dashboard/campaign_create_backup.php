<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Crear Campaña';
$currentPage = 'campaigns';

$step = $_GET['step'] ?? 1;

$socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
$brandDNA = \App\Models\BrandDNA::getActive($agencyId);
$clients = \App\Models\Client::getByAgency($agencyId);

$facebookAccounts = array_filter($socialAccounts, fn($a) => $a['platform'] === 'facebook');

ob_start();
?>

<style>
    .wizard-container { max-width: 900px; margin: 0 auto; }
    .wizard-steps { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; }
    .wizard-steps::before { content: ''; position: absolute; top: 20px; left: 0; right: 0; height: 2px; background: var(--border); z-index: 0; }
    .wizard-step { position: relative; z-index: 1; text-align: center; flex: 1; }
    .wizard-step .step-number { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-lighter); border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-weight: 600; color: var(--text-muted); }
    .wizard-step.active .step-number { background: var(--primary); border-color: var(--primary); color: #000; }
    .wizard-step.completed .step-number { background: var(--success); border-color: var(--success); color: #000; }
    .wizard-step .step-label { font-size: 11px; color: var(--text-muted); }
    .wizard-step.active .step-label { color: var(--text); font-weight: 600; }
    
    .wizard-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 32px; }
    .wizard-title { font-size: 20px; font-weight: 600; margin-bottom: 24px; }
    .wizard-subtitle { color: var(--text-muted); margin-bottom: 24px; font-size: 14px; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text); }
    .form-group label span { color: var(--text-muted); font-weight: 400; }
    .form-control { width: 100%; padding: 12px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 14px; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--primary); }
    
    .account-select { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
    .account-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: all 0.2s; }
    .account-option:hover { border-color: var(--primary); }
    .account-option.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .account-option .account-name { font-weight: 600; margin-bottom: 4px; }
    .account-option .account-id { font-size: 12px; color: var(--text-muted); }
    
    .row { display: flex; gap: 16px; }
    .col { flex: 1; }
    
    .chip-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .chip { background: var(--bg-lighter); border: 1px solid var(--border); padding: 6px 12px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .chip:hover { border-color: var(--primary); }
    .chip.selected { background: var(--primary); color: #000; border-color: var(--primary); }
    .chip .remove { cursor: pointer; font-size: 14px; }
    
    .search-input { position: relative; }
    .search-results { position: absolute; top: 100%; left: 0; right: 0; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm); max-height: 200px; overflow-y: auto; z-index: 100; display: none; }
    .search-results.active { display: block; }
    .search-result-item { padding: 10px 16px; cursor: pointer; border-bottom: 1px solid var(--border); }
    .search-result-item:last-child { border-bottom: none; }
    .search-result-item:hover { background: var(--bg-lighter); }
    .search-result-item .name { font-weight: 600; }
    
    .brand-dna-box { background: linear-gradient(135deg, rgba(168, 255, 53, 0.1), rgba(168, 255, 53, 0.05)); border: 1px solid var(--primary); border-radius: var(--radius); padding: 20px; margin-bottom: 24px; }
    .brand-dna-box h4 { margin: 0 0 12px 0; font-size: 14px; color: var(--primary); }
    .brand-dna-box p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.6; }
    
    .wizard-actions { display: flex; justify-content: space-between; margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border); }
    .btn-back { padding: 12px 24px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-next { padding: 12px 24px; background: var(--primary); color: #000; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
    .btn-next:hover { background: #96E82E; }
    
    .step-content { display: none; }
    .step-content.active { display: block; }
    
    .objective-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
    .objective-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: all 0.2s; text-align: center; }
    .objective-option:hover { border-color: var(--primary); }
    .objective-option.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .objective-option .obj-name { font-weight: 600; margin-bottom: 4px; }
    .objective-option .obj-desc { font-size: 11px; color: var(--text-muted); }
    
    .template-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
    .template-option { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius); overflow: hidden; cursor: pointer; transition: all 0.2s; }
    .template-option:hover { border-color: var(--primary); transform: translateY(-2px); }
    .template-option.selected { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(168, 255, 53, 0.3); }
    .template-option .template-preview { height: 80px; background: var(--bg-lighter); display: flex; align-items: center; justify-content: center; font-size: 32px; }
    .template-option .template-info { padding: 12px; }
    .template-option .template-name { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
    .template-option .template-type { font-size: 11px; color: var(--text-muted); }
    
    .budget-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .budget-card { background: var(--bg); border: 2px solid var(--border); border-radius: var(--radius-sm); padding: 16px; cursor: pointer; transition: all 0.2s; }
    .budget-card:hover { border-color: var(--primary); }
    .budget-card.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .budget-card .budget-type { font-weight: 600; }
    .budget-card .budget-desc { font-size: 12px; color: var(--text-muted); }
    
    .section-title { font-size: 14px; font-weight: 600; margin: 24px 0 16px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    .section-title:first-child { margin-top: 0; }
</style>

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
            <div class="step-label">Presupuesto</div>
        </div>
        <div class="wizard-step <?php echo $step >= 5 ? 'active' : ''; ?> <?php echo $step > 5 ? 'completed' : ''; ?>">
            <div class="step-number"><?php echo $step > 5 ? '✓' : '5'; ?></div>
            <div class="step-label">Creativo</div>
        </div>
        <div class="wizard-step <?php echo $step >= 6 ? 'active' : ''; ?>">
            <div class="step-number">6</div>
            <div class="step-label">Revisar</div>
        </div>
    </div>
    
    <form method="POST" action="<?php echo $baseUrl; ?>campaigns/store" id="campaignForm">
        
        <!-- Step 1: Basic Info -->
        <div class="step-content <?php echo $step == 1 ? 'active' : ''; ?>" data-step="1">
            <div class="wizard-card">
                <div class="wizard-title">Información Básica</div>
                <div class="wizard-subtitle">Define el nombre y objetivo de tu campaña</div>
                
                <?php if ($brandDNA): ?>
                <div class="brand-dna-box">
                    <h4>🎨 ADN de Marca: <?php echo htmlspecialchars($brandDNA['name']); ?></h4>
                    <p><?php echo htmlspecialchars($brandDNA['description']); ?></p>
                </div>
                <?php endif; ?>
                
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
                    <div class="objective-option" data-value="BRAND_AWARENESS" onclick="selectObjective('BRAND_AWARENESS', this)">
                        <div class="obj-name">👁️ Awareness</div>
                        <div class="obj-desc">Conocimiento de marca</div>
                    </div>
                    <div class="objective-option" data-value="TRAFFIC" onclick="selectObjective('TRAFFIC', this)">
                        <div class="obj-name">🚀 Tráfico</div>
                        <div class="obj-desc">Llevar personas a tu web</div>
                    </div>
                    <div class="objective-option" data-value="ENGAGEMENT" onclick="selectObjective('ENGAGEMENT', this)">
                        <div class="obj-name">💬 Interacción</div>
                        <div class="obj-desc">Comentarios y me gusta</div>
                    </div>
                    <div class="objective-option" data-value="LEAD_GENERATION" onclick="selectObjective('LEAD_GENERATION', this)">
                        <div class="obj-name">📝 Leads</div>
                        <div class="obj-desc">Captar contactos</div>
                    </div>
                    <div class="objective-option" data-value="CONVERSIONS" onclick="selectObjective('CONVERSIONS', this)">
                        <div class="obj-name">💰 Conversiones</div>
                        <div class="obj-desc">Ventas y registros</div>
                    </div>
                    <div class="objective-option" data-value="PRODUCT_CATALOG_SALES" onclick="selectObjective('PRODUCT_CATALOG_SALES', this)">
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
                <?php if (empty($facebookAccounts)): ?>
                    <p style="color: var(--text-muted);">No hay cuentas de Facebook conectadas. <a href="<?php echo $baseUrl; ?>connections">Conectar cuenta</a></p>
                <?php else: ?>
                <div class="account-select" style="margin-bottom: 24px;">
                    <?php foreach ($facebookAccounts as $account): ?>
                    <div class="account-option" onclick="selectAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>', '<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>', this)">
                        <div class="account-name"><?php echo htmlspecialchars($account['account_name']); ?></div>
                        <div class="account-id">ID: <?php echo htmlspecialchars($account['account_id']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="social_account_id" id="socialAccountInput" value="">
                <input type="hidden" name="social_account_name" id="socialAccountName" value="">
                <input type="hidden" name="access_token" id="accessToken" value="">
                <?php endif; ?>
                
                <!-- Account Details (shows after selection) -->
                <div id="accountDetails" style="display: none; margin-top: 24px;">
                    
                    <!-- 2. Business Manager -->
                    <div class="section-title">2. Business Manager *</div>
                    <div id="businessManagersLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Business Managers...</div>
                    </div>
                    <div id="businessManagersList"></div>
                    <input type="hidden" name="business_manager_id" id="businessManagerId" value="">
                    <input type="hidden" name="business_manager_name" id="businessManagerName" value="">
                    
                    <!-- 3. Ad Accounts -->
                    <div class="section-title">3. Cuenta Publicitaria (Ad Account) *</div>
                    <div id="adAccountsLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Cuentas Ads...</div>
                    </div>
                    <div id="adAccountsList"></div>
                    <input type="hidden" name="ad_account_id" id="adAccountId" value="">
                    <input type="hidden" name="ad_account_name" id="adAccountName" value="">
                    
                    <!-- 4. Fan Pages -->
                    <div class="section-title">4. Fan Page *</div>
                    <div id="fanPagesLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Fan Pages...</div>
                    </div>
                    <div id="fanPagesList"></div>
                    <input type="hidden" name="fan_page_id" id="fanPageId" value="">
                    <input type="hidden" name="fan_page_name" id="fanPageName" value="">
                    
                    <!-- 5. Instagram -->
                    <div class="section-title">5. Página de Instagram (Opcional)</div>
                    <div id="instagramLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Instagram...</div>
                    </div>
                    <div id="instagramList"></div>
                    <input type="hidden" name="instagram_id" id="instagramId" value="">
                    <input type="hidden" name="instagram_name" id="instagramName" value="">
                    
                    <!-- 6. WhatsApp -->
                    <div class="section-title">6. WhatsApp Business (Opcional)</div>
                    <div id="whatsappLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando WhatsApp...</div>
                    </div>
                    <div id="whatsappList"></div>
                    <input type="hidden" name="whatsapp_id" id="whatsappId" value="">
                    <input type="hidden" name="whatsapp_name" id="whatsappName" value="">
                    
                    <!-- 7. Pixels -->
                    <div class="section-title">7. Pixel de Seguimiento</div>
                    <div id="pixelsLoading" style="display: none; text-align: center; padding: 20px; color: var(--text-muted);">
                        <div style="font-size: 24px;">⏳</div>
                        <div>Cargando Pixels...</div>
                    </div>
                    <div id="pixelsList"></div>
                    <input type="hidden" name="pixel_id" id="pixelId" value="">
                    <input type="hidden" name="pixel_name" id="pixelName" value="">
                </div>
                
                <div id="selectAccountHint" style="margin-top: 24px; padding: 20px; text-align: center; color: var(--text-muted); background: var(--bg-lighter); border-radius: var(--radius-sm);">
                    Selecciona una cuenta de Facebook para cargar las opciones
                </div>
                <?php if (empty($facebookAccounts)): ?>
                    <p style="color: var(--text-muted);">No hay cuentas de Facebook conectadas. <a href="<?php echo $baseUrl; ?>connections">Conectar cuenta</a></p>
                <?php else: ?>
                <div class="account-select" style="margin-bottom: 24px;">
                    <?php foreach ($facebookAccounts as $account): ?>
                    <div class="account-option" onclick="selectAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>', '<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>', this)">
                        <div class="account-name"><?php echo htmlspecialchars($account['account_name']); ?></div>
                        <div class="account-id">ID: <?php echo htmlspecialchars($account['account_id']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
              
               
            </div>
        </div>
        
        <!-- Step 3: Audience -->
        <div class="step-content <?php echo $step == 3 ? 'active' : ''; ?>" data-step="3">
            <div class="wizard-card">
                <div class="wizard-title">Audiencia</div>
                <div class="wizard-subtitle">Define a quién quieres alcanzar</div>
                
                <div class="section-title">Ubicación</div>
                <div class="form-group">
                    <label>Tipo de ubicación</label>
                    <select name="location_type" class="form-control" onchange="locationTypeChanged()">
                        <option value="country">País</option>
                        <option value="region">Región/Estado</option>
                        <option value="city">Ciudad</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Buscar ubicación</label>
                    <div class="search-input">
                        <input type="text" id="locationSearch" class="form-control" placeholder="Escribe para buscar..." oninput="searchLocations(this.value)">
                        <div id="locationResults" class="search-results"></div>
                    </div>
                    <div class="chip-container" id="selectedLocations"></div>
                    <input type="hidden" name="locations" id="locationsInput" value="">
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
        
        <!-- Step 4: Budget & Schedule -->
        <div class="step-content <?php echo $step == 4 ? 'active' : ''; ?>" data-step="4">
            <div class="wizard-card">
                <div class="wizard-title">Presupuesto y Programación</div>
                <div class="wizard-subtitle">Define cuánto quieres gastar y cuándo</div>
                
                <div class="section-title">Tipo de Presupuesto</div>
                <div class="budget-grid">
                    <div class="budget-card" data-value="daily" onclick="selectBudgetType('daily', this)">
                        <div class="budget-type">💵 Gasto Diario</div>
                        <div class="budget-desc">Monto fijo por día</div>
                    </div>
                    <div class="budget-card" data-value="lifetime" onclick="selectBudgetType('lifetime', this)">
                        <div class="budget-type">📊 Presupuesto Total</div>
                        <div class="budget-desc">Monto total para toda la campaña</div>
                    </div>
                </div>
                <input type="hidden" name="budget_type" id="budgetTypeInput" value="">
                
                <div class="form-group" style="margin-top: 24px;">
                    <label>Monto del Presupuesto ($)</label>
                    <input type="number" name="budget_amount" class="form-control" placeholder="Ej: 100" min="1" step="0.01">
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
        
        <!-- Step 5: Creative - Image Generation -->
        <div class="step-content <?php echo $step == 5 ? 'active' : ''; ?>" data-step="5">
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
        
        <!-- Step 6: Review -->
        <div class="step-content <?php echo $step == 6 ? 'active' : ''; ?>" data-step="6">
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
            <a href="<?php echo $baseUrl; ?>campaigns/create?step=<?php echo $step - 1; ?>" class="btn-back">← Atrás</a>
            <?php else: ?>
            <a href="<?php echo $baseUrl; ?>campaigns" class="btn-back">Cancelar</a>
            <?php endif; ?>
            
            <?php if ($step < 6): ?>
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
let searchTimeoutLocation = null;
let searchTimeoutInterest = null;

let selectedBusinessManagerId = '';
let selectedBusinessManagerName = '';

function selectObjective(value, element) {
    document.querySelectorAll('.objective-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('objectiveInput').value = value;
}

function selectAccount(accountId, accountName, accessToken, element) {
    document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('socialAccountInput').value = accountId;
    document.getElementById('socialAccountName').value = accountName;
    document.getElementById('accessToken').value = accessToken;
    selectedAccountId = accountId;
    selectedAccountName = accountName;
    
    // Show account details
    document.getElementById('accountDetails').style.display = 'block';
    document.getElementById('selectAccountHint').style.display = 'none';
    
    // Reset all fields
    resetDependentFields();
    
    // Load Business Managers
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
                data.business_managers.forEach(bm => {
                    html += '<option value="' + bm.id + '">' + bm.name + '</option>';
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
    
    let url = '<?php echo $baseUrl; ?>connections/adaccounts?platform=facebook&token=' + encodeURIComponent(accessToken);
    if (businessManagerId) {
        url += '&business_id=' + encodeURIComponent(businessManagerId);
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.ad_accounts && data.ad_accounts.length > 0) {
                let html = '<select class="form-control" onchange="selectAdAccount(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar Cuenta Ads...</option>';
                data.ad_accounts.forEach(account => {
                    html += '<option value="' + account.id + '">' + account.name + ' (' + account.id + ')</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontraron cuentas publicitarias.</p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectAdAccount(accountId, accountName) {
    document.getElementById('adAccountId').value = accountId;
    document.getElementById('adAccountName').value = accountName;
    
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
                data.pages.forEach(page => {
                    html += '<option value="' + page.id + '">' + page.name + '</option>';
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
                data.instagram_pages.forEach(page => {
                    html += '<option value="' + page.id + '">' + page.username + ' (' + page.name + ')</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontró página de Instagram asociada.</p>';
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
}

function loadWhatsAppFromFanPage(accessToken, fanPageId) {
    const loading = document.getElementById('whatsappLoading');
    const list = document.getElementById('whatsappList');
    
    loading.style.display = 'block';
    list.innerHTML = '';
    
    fetch('<?php echo $baseUrl; ?>connections/whatsapp?token=' + encodeURIComponent(accessToken))
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.whatsapp_accounts && data.whatsapp_accounts.length > 0) {
                let html = '<select class="form-control" onchange="selectWhatsApp(this.value, this.options[this.selectedIndex].text)"><option value="">Seleccionar WhatsApp...</option>';
                data.whatsapp_accounts.forEach(wa => {
                    const phoneInfo = wa.phone ? ' - ' + wa.phone : '';
                    html += '<option value="' + wa.id + '">' + wa.name + phoneInfo + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontró WhatsApp Business. Verifica que tengas una cuenta de WhatsApp Business vinculada.</p>';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('WhatsApp error:', error);
            list.innerHTML = '<p style="color: var(--danger);">Error: ' + error.message + '</p>';
        });
}

function selectWhatsApp(whatsappId, whatsappName) {
    document.getElementById('whatsappId').value = whatsappId;
    document.getElementById('whatsappName').value = whatsappName;
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
                data.pixels.forEach(pixel => {
                    const accountInfo = pixel.ad_account_name ? ' (' + pixel.ad_account_name + ')' : '';
                    html += '<option value="' + pixel.id + '">' + pixel.name + accountInfo + '</option>';
                });
                html += '</select>';
                list.innerHTML = html;
            } else {
                list.innerHTML = '<p style="color: var(--text-muted);">No se encontraron Pixels para esta cuenta.</p>';
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
}

function selectTemplate(value, element) {
    document.querySelectorAll('.template-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('templateInput').value = value;
}

function selectBudgetType(value, element) {
    document.querySelectorAll('.budget-card').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('budgetTypeInput').value = value;
}

function locationTypeChanged() {
    selectedLocations = [];
    document.getElementById('selectedLocations').innerHTML = '';
    document.getElementById('locationsInput').value = '';
}

function searchLocations(keyword) {
    clearTimeout(searchTimeoutLocation);
    const results = document.getElementById('locationResults');
    
    if (keyword.length < 2) {
        results.classList.remove('active');
        return;
    }
    
    searchTimeoutLocation = setTimeout(() => {
        fetch('<?php echo $baseUrl; ?>connections/locations?keyword=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.locations && data.locations.length > 0) {
                    let html = '';
                    data.locations.forEach(loc => {
                        html += `<div class="search-result-item" onclick="addLocation('${loc.key}', '${loc.name.replace(/'/g, "\\'")}')">
                            <div class="name">${loc.name}</div>
                        </div>`;
                    });
                    results.innerHTML = html;
                    results.classList.add('active');
                }
            });
    }, 300);
}

function addLocation(key, name) {
    if (!selectedLocations.find(l => l.key === key)) {
        selectedLocations.push({ key, name });
        renderLocations();
    }
    document.getElementById('locationSearch').value = '';
    document.getElementById('locationResults').classList.remove('active');
}

function renderLocations() {
    const container = document.getElementById('selectedLocations');
    let html = '';
    selectedLocations.forEach((loc, index) => {
        html += `<span class="chip selected">${loc.name}<span class="remove" onclick="removeLocation(${index})">×</span></span>`;
    });
    container.innerHTML = html;
    document.getElementById('locationsInput').value = JSON.stringify(selectedLocations);
}

function removeLocation(index) {
    selectedLocations.splice(index, 1);
    renderLocations();
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
    if (currentStep === 1) {
        const name = document.querySelector('input[name="name"]').value;
        const objective = document.getElementById('objectiveInput').value;
        if (!name) { alert('Por favor ingresa un nombre para la campaña'); return; }
        if (!objective) { alert('Por favor selecciona un objetivo'); return; }
    }
    if (currentStep === 2) {
        if (!selectedAccountId) { alert('Por favor selecciona una cuenta de Facebook'); return; }
    }
    window.location.href = '<?php echo $baseUrl; ?>campaigns/create?step=' + (currentStep + 1);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-input')) {
        document.getElementById('locationResults').classList.remove('active');
        document.getElementById('interestResults').classList.remove('active');
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
?>
