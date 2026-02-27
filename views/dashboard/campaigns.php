<?php
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Campañas';
$currentPage = 'campaigns';

$flashSuccess = \Session::getFlash('success');
$flashError = \Session::getFlash('error');

ob_start();
?>

<style>
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
    .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; }
    .stat-card .stat-label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
    .stat-card .stat-value { font-size: 28px; font-weight: 700; }
    .stat-card .stat-value.active { color: var(--success); }
    .stat-card .stat-value.draft { color: var(--warning); }
    .stat-card .stat-value.paused { color: var(--text-muted); }
    
    .campaigns-table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .campaigns-table th { text-align: left; padding: 16px; background: var(--bg-lighter); font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
    .campaigns-table td { padding: 16px; border-bottom: 1px solid var(--border); }
    .campaigns-table tr:last-child td { border-bottom: none; }
    .campaigns-table tr:hover { background: var(--bg-lighter); }
    
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .status-badge.active { background: rgba(46, 213, 115, 0.15); color: var(--success); }
    .status-badge.draft { background: rgba(255, 171, 0, 0.15); color: var(--warning); }
    .status-badge.paused { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }
    .status-badge.completed { background: rgba(24, 119, 242, 0.15); color: #1877F2; }
    
    .campaign-name { font-weight: 600; font-size: 15px; margin-bottom: 4px; }
    .campaign-client { font-size: 12px; color: var(--text-muted); }
    
    .campaign-budget { font-weight: 600; color: var(--primary); }
    
    .campaign-dates { font-size: 12px; color: var(--text-muted); }
    
    .campaign-actions { display: flex; gap: 8px; }
    .btn-action { padding: 6px 12px; border-radius: var(--radius-xs); font-size: 12px; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .btn-view { background: var(--primary-dim); color: var(--primary); }
    .btn-edit { background: rgba(24, 119, 242, 0.15); color: #1877F2; }
    .btn-publish { background: var(--primary); color: #1877F2; }
    .btn-duplicate { background: rgba(168, 255, 53, 0.1); color: var(--primary); }
    .btn-delete { background: rgba(255, 71, 87, 0.15); color: var(--danger); }
    
    .filters-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
    .filters-bar select { padding: 10px 16px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); }
    .btn-new-campaign { background: var(--primary); color: #000; padding: 10px 20px; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-new-campaign:hover { background: #96E82E; }
    
    .empty-state { text-align: center; padding: 60px 20px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); }
    .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
    .empty-state h3 { font-size: 18px; margin-bottom: 8px; }
    .empty-state p { color: var(--text-muted); margin-bottom: 20px; }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .stats-grid { 
            grid-template-columns: repeat(2, 1fr); 
            gap: 12px;
            margin-bottom: 20px;
        }
        .stat-card { padding: 12px; }
        .stat-card .stat-value { font-size: 20px; }
        
        .page-header { 
            flex-direction: column; 
            align-items: flex-start !important;
        }
        .page-header h1 { font-size: 20px; }
        
        .filters-bar {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }
        .filters-bar select,
        .btn-new-campaign {
            width: 100%;
            justify-content: center;
        }
        
        /* Hide table headers on mobile */
        .campaigns-table thead { display: none; }
        
        /* Card layout for mobile */
        .campaigns-table tbody { 
            display: flex; 
            flex-direction: column; 
            gap: 12px;
            padding: 12px;
        }
        
        .campaigns-table tr { 
            display: flex;
            flex-direction: column;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 12px;
            gap: 8px;
        }
        
        .campaigns-table td { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            border-bottom: none;
            border: none;
        }
        
        .campaigns-table td::before {
            content: attr(data-label);
            font-weight: 600;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        
        .campaigns-table td:last-child {
            justify-content: flex-end;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
        }
        
        .campaign-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .stat-card { padding: 10px; }
        .stat-card .stat-label { font-size: 11px; }
        .stat-card .stat-value { font-size: 18px; }
        
        .campaigns-table tbody { padding: 8px; }
        .campaigns-table tr { padding: 10px; }
        
        .btn-action { padding: 8px 10px; font-size: 11px; }
    }
</style>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo $flashSuccess; ?></div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-error"><?php echo $flashError; ?></div>
<?php endif; ?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h1 style="font-size: 24px;">Campañas</h1>
    <a href="<?php echo $baseUrl; ?>campaigns/create" class="btn-new-campaign" onclick="localStorage.removeItem('wizard_campaign_data');">+ Nueva Campaña</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Campañas</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Activas</div>
        <div class="stat-value active"><?php echo $stats['active']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Borrador</div>
        <div class="stat-value draft"><?php echo $stats['draft']; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pausadas</div>
        <div class="stat-value paused"><?php echo $stats['paused']; ?></div>
    </div>
</div>

<form method="GET" action="<?php echo $baseUrl; ?>campaigns" class="filters-bar">
    <select name="status">
        <option value="">Todos los estados</option>
        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Activas</option>
        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Borrador</option>
        <option value="paused" <?php echo $status === 'paused' ? 'selected' : ''; ?>>Pausadas</option>
        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completadas</option>
    </select>
    <button type="submit" style="padding: 10px 20px; background: var(--bg-lighter); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); cursor: pointer;">Filtrar</button>
</form>

<?php if (empty($campaigns)): ?>
<div class="empty-state">
    <div class="icon">📢</div>
    <h3>No hay campañas</h3>
    <p>Crea tu primera campaña publicitaria con IA</p>
    <a href="<?php echo $baseUrl; ?>campaigns/create" class="btn-new-campaign" onclick="localStorage.removeItem('wizard_campaign_data');">+ Nueva Campaña</a>
</div>
<?php else: ?>
<table class="campaigns-table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Estado</th>
            <th>Presupuesto</th>
            <th>Duración</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($campaigns as $camp): ?>
        <tr>
            <td data-label="Nombre">
                <div class="campaign-name"><?php echo htmlspecialchars($camp['name']); ?></div>
                <?php if ($camp['client_name']): ?>
                <div class="campaign-client"><?php echo htmlspecialchars($camp['client_name']); ?></div>
                <?php endif; ?>
            </td>
            <td data-label="Estado">
                <span class="status-badge <?php echo $camp['status']; ?>"><?php echo $camp['status']; ?></span>
            </td>
            <td data-label="Presupuesto">
                <?php 
                $jsonData = json_decode($camp['campaign_json'] ?? '{}', true);
                $budget = $jsonData['daily_budget'] ?? $jsonData['lifetime_budget'] ?? $jsonData['budget'] ?? 0;
                $budgetType = isset($jsonData['lifetime_budget']) ? 'Total' : (isset($jsonData['daily_budget']) ? 'Diario' : (($jsonData['budget_type'] ?? '') === 'lifetime_budget' ? 'Total' : 'Diario'));
                ?>
                <span class="campaign-budget">$<?php echo number_format($budget, 0); ?></span>
                <div style="font-size: 10px; color: var(--text-muted);"><?php echo $budgetType; ?></div>
            </td>
            <td data-label="Duración">
                <div class="campaign-dates">
                    <?php if ($camp['start_date']): ?>
                        <?php echo date('d/m/Y', strtotime($camp['start_date'])); ?>
                        <?php if ($camp['end_date']): ?>
                            - <?php echo date('d/m/Y', strtotime($camp['end_date'])); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Sin fechas
                    <?php endif; ?>
                </div>
            </td>
            <td data-label="Acciones">
                <div class="campaign-actions">
                    <?php if ($camp['status'] === 'draft'): ?>
                    <button onclick="publishCampaign(<?php echo $camp['id']; ?>)" class="btn-action btn-publish" title="Publicar en Meta">🚀</button>
                    <?php endif; ?>
                    <button onclick="viewCampaignJson(<?php echo $camp['id']; ?>)" class="btn-action" title="Ver JSON" style="background: var(--bg-lighter);">📄</button>
                    <a href="<?php echo $baseUrl; ?>campaigns/edit?id=<?php echo $camp['id']; ?>" class="btn-action btn-edit" title="Editar">✏️</a>
                    <a href="<?php echo $baseUrl; ?>campaigns/duplicate?id=<?php echo $camp['id']; ?>" class="btn-action btn-duplicate" title="Duplicar">📋</a>
                    <a href="<?php echo $baseUrl; ?>campaigns/delete?id=<?php echo $camp['id']; ?>" class="btn-action btn-delete" title="Eliminar" onclick="return confirm('¿Eliminar campaña?')">🗑️</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

let currentPublishId = null;

function publishCampaign(id) {
    if (!confirm('¿Publicar esta campaña en Meta Ads?')) return;
    
    currentPublishId = id;
    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳';
    
    // Create progress modal
    const progressModal = document.createElement('div');
    progressModal.id = 'publishProgressModal';
    progressModal.style.cssText = 'position:fixed;z-index:99999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.9);display:flex;align-items:center;justify-content:center;';
    progressModal.innerHTML = `
        <div style="background:var(--card);padding:30px;border-radius:12px;min-width:500px;max-width:800px;max-height:90vh;overflow-y:auto;text-align:center;position:relative;">
            <button onclick="closePublishModal()" style="position:absolute;top:15px;right:15px;background:none;border:none;color:var(--text-primary);font-size:28px;cursor:pointer;z-index:10;">✕</button>
            <h2 style="color:var(--text-primary);margin-bottom:20px;">Publicando Campaña</h2>
            <div id="progressSteps" style="text-align:left;">
                <div id="step1" style="color:var(--text-muted);padding:8px 0;">⏳ Creando campaña...</div>
                <div id="step1_error" style="color:var(--danger);padding:4px 0;font-size:12px;display:none;"></div>
                <div id="step1_json" style="display:none;background:#1a1a1a;padding:10px;border-radius:4px;margin:5px 0 15px 0;font-size:11px;color:#ccc;max-height:150px;overflow:auto;white-space:pre-wrap;"></div>
                
                <div id="step2" style="color:var(--text-muted);padding:8px 0;">⏳ Creando grupo de anuncios...</div>
                <div id="step2_error" style="color:var(--danger);padding:4px 0;font-size:12px;display:none;"></div>
                <div id="step2_json" style="display:none;background:#1a1a1a;padding:10px;border-radius:4px;margin:5px 0 15px 0;font-size:11px;color:#ccc;max-height:150px;overflow:auto;white-space:pre-wrap;"></div>
                
                <div id="step3" style="color:var(--text-muted);padding:8px 0;">⏳ Creando creativo...</div>
                <div id="step3_error" style="color:var(--danger);padding:4px 0;font-size:12px;display:none;"></div>
                <div id="step3_json" style="display:none;background:#1a1a1a;padding:10px;border-radius:4px;margin:5px 0 15px 0;font-size:11px;color:#ccc;max-height:150px;overflow:auto;white-space:pre-wrap;"></div>
                
                <div id="step4" style="color:var(--text-muted);padding:8px 0;">⏳ Creando anuncio...</div>
                <div id="step4_error" style="color:var(--danger);padding:4px 0;font-size:12px;display:none;"></div>
                <div id="step4_json" style="display:none;background:#1a1a1a;padding:10px;border-radius:4px;margin:5px 0 15px 0;font-size:11px;color:#ccc;max-height:150px;overflow:auto;white-space:pre-wrap;"></div>
            </div>
            <div id="progressError" style="color:var(--danger);margin-top:15px;display:none;"></div>
        </div>
    `;
    document.body.appendChild(progressModal);
    
    fetch(BASE_URL + 'campaigns/publish', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Step 1: Campaign - mostrar en verde
            if (data.data?.campaign_id) {
                document.getElementById('step1').innerHTML = '✅ Campaña creada (ID: ' + data.data.campaign_id + ')';
                document.getElementById('step1').style.color = 'var(--success)';
            }
            if (data.debug_payloads?.campaign) {
                document.getElementById('step1_json').style.display = 'block';
                document.getElementById('step1_json').textContent = JSON.stringify(data.debug_payloads.campaign, null, 2);
            }
            
            // Step 2: AdSet - mostrar en verde
            if (data.data?.adset_id) {
                document.getElementById('step2').innerHTML = '✅ Grupo de anuncios creado (ID: ' + data.data.adset_id + ')';
                document.getElementById('step2').style.color = 'var(--success)';
            }
            if (data.debug_payloads?.adset) {
                document.getElementById('step2_json').style.display = 'block';
                document.getElementById('step2_json').textContent = JSON.stringify(data.debug_payloads.adset, null, 2);
            }
            
            // Step 3: Creative - mostrar en verde
            if (data.data?.creative_id) {
                document.getElementById('step3').innerHTML = '✅ Creativo creado (ID: ' + data.data.creative_id + ')';
                document.getElementById('step3').style.color = 'var(--success)';
            }
            if (data.debug_payloads?.creative) {
                document.getElementById('step3_json').style.display = 'block';
                document.getElementById('step3_json').textContent = JSON.stringify(data.debug_payloads.creative, null, 2);
            }
            
            // Step 4: Ad - mostrar en verde
            if (data.data?.ad_id) {
                document.getElementById('step4').innerHTML = '✅ Anuncio creado (ID: ' + data.data.ad_id + ')';
                document.getElementById('step4').style.color = 'var(--success)';
            }
            if (data.debug_payloads?.ad) {
                document.getElementById('step4_json').style.display = 'block';
                document.getElementById('step4_json').textContent = JSON.stringify(data.debug_payloads.ad, null, 2);
            }
            
            setTimeout(() => {
                closePublishModal();
                alert('✅ Campaña publicada exitosamente en Meta');
                location.reload();
            }, 3000);
        } else {
            // Error general o por paso
            document.getElementById('progressError').style.display = 'block';
            document.getElementById('progressError').textContent = '❌ ' + (data.error || 'Error al publicar');
            
            // Mostrar errores específicos por paso si existen
            if (data.step_errors) {
                const getErrorMsg = (stepError) => {
                    if (typeof stepError === 'string') return stepError;
                    if (stepError.error && stepError.error.message) {
                        let msg = stepError.error.message;
                        if (stepError.error.error_user_msg) msg += ' - ' + stepError.error.error_user_msg;
                        if (stepError.error.error_data) msg += ' (' + JSON.stringify(stepError.error.error_data) + ')';
                        return msg;
                    }
                    if (stepError.message) return stepError.message;
                    return JSON.stringify(stepError);
                };

                if (data.step_errors.campaign) {
                    if (data.step_errors.campaign.id) {
                        document.getElementById('step1').innerHTML = '✅ Campaña creada (ID: ' + data.step_errors.campaign.id + ')';
                        document.getElementById('step1').style.color = 'var(--success)';
                    } else {
                        document.getElementById('step1').innerHTML = '❌ Error creando campaña';
                        document.getElementById('step1').style.color = 'var(--danger)';
                        document.getElementById('step1_error').style.display = 'block';
                        document.getElementById('step1_error').textContent = getErrorMsg(data.step_errors.campaign);
                    }
                }
                if (data.step_errors.adset) {
                    if (data.step_errors.adset.id) {
                        document.getElementById('step2').innerHTML = '✅ Grupo de anuncios creado (ID: ' + data.step_errors.adset.id + ')';
                        document.getElementById('step2').style.color = 'var(--success)';
                    } else {
                        document.getElementById('step2').innerHTML = '❌ Error creando grupo de anuncios';
                        document.getElementById('step2').style.color = 'var(--danger)';
                        document.getElementById('step2_error').style.display = 'block';
                        document.getElementById('step2_error').textContent = getErrorMsg(data.step_errors.adset);
                    }
                }
                if (data.step_errors.creative) {
                    if (data.step_errors.creative.id) {
                        document.getElementById('step3').innerHTML = '✅ Creativo creado (ID: ' + data.step_errors.creative.id + ')';
                        document.getElementById('step3').style.color = 'var(--success)';
                    } else {
                        document.getElementById('step3').innerHTML = '❌ Error creando creativo';
                        document.getElementById('step3').style.color = 'var(--danger)';
                        document.getElementById('step3_error').style.display = 'block';
                        document.getElementById('step3_error').textContent = getErrorMsg(data.step_errors.creative);
                    }
                }
                if (data.step_errors.ad) {
                    if (data.step_errors.ad.id) {
                        document.getElementById('step4').innerHTML = '✅ Anuncio creado (ID: ' + data.step_errors.ad.id + ')';
                        document.getElementById('step4').style.color = 'var(--success)';
                    } else {
                        document.getElementById('step4').innerHTML = '❌ Error creando anuncio';
                        document.getElementById('step4').style.color = 'var(--danger)';
                        document.getElementById('step4_error').style.display = 'block';
                        document.getElementById('step4_error').textContent = getErrorMsg(data.step_errors.ad);
                    }
                }
            }
            
            // Mostrar debug
            if (data.debug_payloads) {
                if (data.debug_payloads.campaign) {
                    document.getElementById('step1_json').style.display = 'block';
                    document.getElementById('step1_json').textContent = JSON.stringify(data.debug_payloads.campaign, null, 2);
                }
                if (data.debug_payloads.adset) {
                    document.getElementById('step2_json').style.display = 'block';
                    document.getElementById('step2_json').textContent = JSON.stringify(data.debug_payloads.adset, null, 2);
                }
                if (data.debug_payloads.creative) {
                    document.getElementById('step3_json').style.display = 'block';
                    document.getElementById('step3_json').textContent = JSON.stringify(data.debug_payloads.creative, null, 2);
                }
                if (data.debug_payloads.ad) {
                    document.getElementById('step4_json').style.display = 'block';
                    document.getElementById('step4_json').textContent = JSON.stringify(data.debug_payloads.ad, null, 2);
                }
            }
            
            btn.disabled = false;
            btn.textContent = originalText;
        }
    })
    .catch(err => {
        document.getElementById('progressError').style.display = 'block';
        document.getElementById('progressError').textContent = '❌ Error de conexión: ' + err.message;
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

function closePublishModal() {
    const modal = document.getElementById('publishProgressModal');
    if (modal) {
        document.body.removeChild(modal);
    }
    currentPublishId = null;
}

function viewCampaignJson(id) {
    fetch(BASE_URL + 'campaigns/json?id=' + id, {
        method: 'GET'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('jsonModalContent').textContent = JSON.stringify(data.json, null, 2);
            document.getElementById('jsonModal').style.display = 'flex';
        } else {
            alert('❌ ' + (data.error || 'Error al obtener JSON'));
        }
    })
    .catch(err => {
        alert('❌ Error al obtener JSON: ' + err);
    });
}

function closeJsonModal() {
    document.getElementById('jsonModal').style.display = 'none';
}
</script>

<!-- JSON Modal -->
<div id="jsonModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.8);align-items:center;justify-content:center;">
    <div class="modal-content" style="background:var(--bg-dark);padding:20px;border-radius:8px;max-width:700px;width:90%;max-height:80vh;overflow:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h3 style="margin:0;color:var(--text-primary);">JSON Campaña</h3>
            <button onclick="closeJsonModal()" style="background:none;border:none;color:var(--text-primary);font-size:24px;cursor:pointer;">&times;</button>
        </div>
        <pre id="jsonModalContent" style="background:var(--bg-lighter);padding:15px;border-radius:4px;overflow:auto;font-family:monospace;font-size:12px;color:var(--text-primary);white-space:pre-wrap;word-break:break-all;max-height:60vh;"></pre>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout.php';
