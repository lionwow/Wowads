<?php
// Ensure BASE_URL is defined (same logic as layout.php)
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '/wowads/' : rtrim($scriptDir, '/') . '/';
} else {
    $baseUrl = BASE_URL;
}

$pageTitle = 'Crear ADN de Marca';
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
    
    .step { margin-bottom: 32px; }
    .step-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    
    .account-select { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
    .account-option { 
        background: var(--bg); 
        border: 2px solid var(--border); 
        border-radius: var(--radius-sm); 
        padding: 16px; 
        cursor: pointer; 
        transition: all 0.2s; 
    }
    .account-option:hover { border-color: var(--primary); }
    .account-option.selected { border-color: var(--primary); background: rgba(168, 255, 53, 0.1); }
    .account-option .account-name { font-weight: 600; margin-bottom: 4px; }
    .account-option .account-id { font-size: 12px; color: var(--text-muted); }
    
    .hidden { display: none; }
    
    .audio-btn { 
        background: var(--bg-lighter); 
        border: 1px solid var(--border); 
        padding: 10px 16px; 
        border-radius: var(--radius-sm); 
        cursor: pointer; 
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .audio-btn:hover { border-color: var(--primary); }
    .audio-btn.recording { background: rgba(255, 71, 87, 0.1); border-color: var(--danger); }
    
    .loading { text-align: center; padding: 40px; color: var(--text-muted); }
    .loading-spinner { 
        width: 40px; height: 40px; 
        border: 3px solid var(--border); 
        border-top-color: var(--primary); 
        border-radius: 50%; 
        animation: spin 1s linear infinite; 
        margin: 0 auto 16px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    
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
    
    .form-actions { display: flex; gap: 12px; margin-top: 24px; }
    
    .audio-btn { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        border: none; 
        padding: 12px 20px; 
        border-radius: 25px; 
        cursor: pointer; 
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    .audio-btn:hover { 
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    .audio-btn.recording { 
        background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        box-shadow: 0 4px 15px rgba(255, 65, 108, 0.4);
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .audio-btn svg {
        width: 18px;
        height: 18px;
    }
</style>

<div class="page-header">
    <h1 class="page-title">Crear ADN de Marca</h1>
    <a href="<?php echo $baseUrl; ?>brand-dna" class="btn btn-back">← Volver</a>
</div>

<div class="card">
    <form id="brandDNAForm" action="<?php echo $baseUrl; ?>brand-dna/store" method="POST">
        <!-- Step 1: Select Account -->
        <div class="step">
            <div class="step-title">1. Seleccionar Cuenta de Facebook</div>
            <?php if (empty($facebookAccounts)): ?>
            <p style="color: var(--text-muted);">No hay cuentas de Facebook conectadas. <a href="<?php echo $baseUrl; ?>connections">Conectar cuenta</a></p>
            <?php else: ?>
            <div class="account-select">
                <?php foreach ($facebookAccounts as $account): ?>
                <div class="account-option" onclick="selectAccount(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>', '<?php echo htmlspecialchars($account['access_token'] ?? ''); ?>', this)">
                    <div class="account-name"><?php echo htmlspecialchars($account['account_name']); ?></div>
                    <div class="account-id">ID: <?php echo htmlspecialchars($account['account_id']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="social_account_id" id="socialAccountId" value="">
            <input type="hidden" name="access_token" id="accessToken" value="">
            <?php endif; ?>
        </div>
        
        <!-- Step 2: Select Fanpage -->
        <div class="step hidden" id="fanpageStep">
            <div class="step-title">2. Seleccionar Fan Page</div>
            <div id="fanpagesLoading" class="loading hidden">
                <div class="loading-spinner"></div>
                <div>Cargando Fan Pages...</div>
            </div>
            <div class="form-group">
                <select id="fanpageSelect" class="form-control" onchange="handleFanpageSelect(this)">
                    <option value="">Selecciona una Fan Page...</option>
                </select>
            </div>
            <input type="hidden" name="fanpage_id" id="fanpageId" value="">
            <input type="hidden" name="fanpage_name" id="fanpageName" value="">
        </div>
        
        <!-- Step 3: Business Description -->
        <div class="step hidden" id="descriptionStep">
            <div class="step-title">3. Describe tu Negocio</div>
            
            <div class="form-group">
                <label>Nombre del ADN de Marca *</label>
                <input type="text" name="name" class="form-control" placeholder="Ej: Mi Marca - Alimentación Saludable" required>
            </div>
            
            <div class="form-group">
                <label>Descripción de tu negocio (para generar el briefing) *</label>
                <div style="margin-bottom: 12px;">
                    <button type="button" class="audio-btn" id="audioBtn" onclick="toggleRecording()">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1c0 .55 1v6-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                        <span id="audioBtnText">Grabar Audio</span>
                    </button>
                    <span style="margin-left: 12px; font-size: 12px; color: var(--text-muted);">Habla sobre tu negocio y el audio se transcribirá automáticamente</span>
                </div>
                <textarea name="business_description" id="businessDescription" class="form-control" rows="6" placeholder="Describe tu negocio: ¿Qué vendes? ¿Cuál es tu propuesta de valor? ¿Quién es tu público objetivo? ¿Qué te diferencia de la competencia?"></textarea>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="generateBriefing()">Generar ADN con IA</button>
            
            <div id="briefingLoading" class="loading hidden">
                <div class="loading-spinner"></div>
                <div>Generando briefing con IA...</div>
            </div>
            
            <div id="briefingResult" class="hidden" onchange="document.getElementById('generatedBriefing').value = this.textContent;"></div>
            <input type="hidden" name="description" id="generatedBriefing" value="">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">Guardar ADN</button>
            </div>
        </div>
    </form>
</div>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

let selectedAccountId = 0;
let selectedAccessToken = '';

function selectAccount(accountId, accountName, accessToken, element) {
    document.querySelectorAll('.account-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    
    document.getElementById('socialAccountId').value = accountId;
    document.getElementById('accessToken').value = accessToken;
    selectedAccountId = accountId;
    selectedAccessToken = accessToken;
    
    // Load fanpages into dropdown
    document.getElementById('fanpageStep').classList.remove('hidden');
    document.getElementById('fanpagesLoading').classList.remove('hidden');
    
    const fanpageSelect = document.getElementById('fanpageSelect');
    fanpageSelect.innerHTML = '<option value="">Cargando Fan Pages...</option>';
    
    fetch(BASE_URL + 'connections/pages?platform=facebook&token=' + encodeURIComponent(accessToken))
        .then(r => r.json())
        .then(data => {
            document.getElementById('fanpagesLoading').classList.add('hidden');
            if (data.success && data.pages && data.pages.length > 0) {
                let options = '<option value="">Selecciona una Fan Page...</option>';
                data.pages.forEach(page => {
                    options += `<option value="${page.id}|${page.name.replace(/\|/g, ' ').replace(/'/g, "\\'")}">${page.name}</option>`;
                });
                fanpageSelect.innerHTML = options;
            } else {
                fanpageSelect.innerHTML = '<option value="">No se encontraron Fan Pages</option>';
            }
        })
        .catch(err => {
            document.getElementById('fanpagesLoading').classList.add('hidden');
            fanpageSelect.innerHTML = '<option value="">Error al cargar Fan Pages</option>';
        });
}

function handleFanpageSelect(select) {
    const value = select.value;
    if (!value) {
        document.getElementById('fanpageId').value = '';
        document.getElementById('fanpageName').value = '';
        document.getElementById('descriptionStep').classList.add('hidden');
        return;
    }
    
    const parts = value.split('|');
    const pageId = parts[0];
    const pageName = parts[1];
    
    document.getElementById('fanpageId').value = pageId;
    document.getElementById('fanpageName').value = pageName;
    document.getElementById('descriptionStep').classList.remove('hidden');
}

function generateBriefing() {
    const description = document.getElementById('businessDescription').value.trim();
    
    if (!description) {
        alert('Por favor describe tu negocio');
        return;
    }
    
    document.getElementById('briefingLoading').classList.remove('hidden');
    document.getElementById('briefingResult').classList.add('hidden');
    
    fetch(BASE_URL + 'brand-dna/generate-briefing', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'description=' + encodeURIComponent(description)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('briefingLoading').classList.add('hidden');
        if (data.success && data.data) {
            document.getElementById('briefingResult').textContent = data.data.description;
            document.getElementById('briefingResult').classList.remove('hidden');
            document.getElementById('generatedBriefing').value = data.data.description;
        } else {
            alert(data.error || 'Error al generar briefing');
        }
    })
    .catch(err => {
        document.getElementById('briefingLoading').classList.add('hidden');
        alert('Error al generar briefing');
    });
}

let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

async function toggleRecording() {
    if (!isRecording) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const text = await transcribeAudio(audioBlob);
                if (text) {
                    const textarea = document.getElementById('businessDescription');
                    textarea.value += (textarea.value ? '\n\n' : '') + text;
                }
            };
            
            mediaRecorder.start();
            isRecording = true;
            document.getElementById('audioBtn').classList.add('recording');
            document.getElementById('audioBtnText').textContent = 'Detener';
        } catch (err) {
            alert('No se pudo acceder al micrófono');
        }
    } else {
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('audioBtn').classList.remove('recording');
        document.getElementById('audioBtnText').textContent = 'Grabar Audio';
    }
}

async function transcribeAudio(audioBlob) {
    document.getElementById('audioBtnText').textContent = 'Transcribiendo...';
    
    const formData = new FormData();
    formData.append('audio', audioBlob, 'audio.webm');
    
    try {
        const response = await fetch(BASE_URL + 'brand-dna/transcribe', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success && data.text) {
            document.getElementById('audioBtnText').textContent = 'Grabar Audio';
            return data.text;
        } else {
            alert(data.error || 'Error al transcribir audio');
            document.getElementById('audioBtnText').textContent = 'Grabar Audio';
            return '';
        }
    } catch (err) {
        console.error('Error transcribing:', err);
        alert('Error al transcribir audio');
        document.getElementById('audioBtnText').textContent = 'Grabar Audio';
        return '';
    }
}

document.getElementById('brandDNAForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Guardando...';
    
    const formData = new FormData(this);
    
    fetch(BASE_URL + 'brand-dna/store', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = BASE_URL + 'brand-dna';
        } else {
            alert(data.error || 'Error al guardar');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Guardar ADN';
        }
    })
    .catch(err => {
        console.error('Error:', err);
        // If fetch fails, submit the form traditionally
        this.submit();
    });
});
</script>
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
