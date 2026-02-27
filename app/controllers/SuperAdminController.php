<?php
namespace App\Controllers;

class SuperAdminController {
    private function checkAdmin() {
        if (!\Session::isLoggedIn()) {
            \Session::redirect('/admin');
            exit;
        }
        
        $userId = \Session::getUserId();
        $user = \App\Models\User::findById($userId);
        
        if (!$user || $user['role'] !== 'admin') {
            \Session::redirect('/admin');
            exit;
        }
        
        return $user;
    }

    public function index(): void {
        $this->checkAdmin();
        
        $stats = [
            'total_users' => \App\Models\User::getCount(),
            'total_agencies' => \App\Models\Agency::getCount(),
            'total_agencies_active' => \App\Models\Agency::getCount('active'),
            'active_users' => \App\Models\User::getCount(),
        ];
        
        require __DIR__ . '/../../views/superadmin/dashboard.php';
    }

    public function users(): void {
        $this->checkAdmin();
        
        $role = $_GET['role'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        
        $users = \App\Models\User::getAll($role, $status, $search);
        
        require __DIR__ . '/../../views/superadmin/users.php';
    }

    public function agencies(): void {
        $this->checkAdmin();
        
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? '';
        
        $agencies = \App\Models\Agency::getAll($status, $search);
        
        require __DIR__ . '/../../views/superadmin/agencies.php';
    }

    public function settings(): void {
        $this->checkAdmin();
        
        $metaApi = \App\Models\Settings::getMetaApi();
        $openai = \App\Models\Settings::getOpenAI();
        $stripe = \App\Models\Settings::getStripe();
        $unsplash = \App\Models\Settings::getUnsplash();
        $briefingPrompt = \App\Models\Settings::getBriefingPrompt();
        
        $flashSuccess = \Session::getFlash('success');
        $flashError = \Session::getFlash('error');
        
        require __DIR__ . '/../../views/superadmin/settings.php';
    }

    public function settingsSave(): void {
        $this->checkAdmin();
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'] ?? '';
            
            if ($type === 'meta') {
                \App\Models\Settings::setMetaApi([
                    'app_id' => trim($_POST['app_id'] ?? ''),
                    'app_secret' => trim($_POST['app_secret'] ?? ''),
                    'access_token' => trim($_POST['access_token'] ?? ''),
                    'ad_account_id' => trim($_POST['ad_account_id'] ?? '')
                ]);
                \App\Models\Settings::set('meta_status', 'disconnected');
                $message = 'Credenciales de Meta guardadas correctamente';
            } elseif ($type === 'openai') {
                \App\Models\Settings::setOpenAI([
                    'api_key' => trim($_POST['api_key'] ?? ''),
                    'image_model' => trim($_POST['image_model'] ?? 'dall-e-3'),
                    'gpt_model' => trim($_POST['gpt_model'] ?? 'gpt-4o')
                ]);
                \App\Models\Settings::set('openai_status', 'disconnected');
                $message = 'Credenciales de OpenAI guardadas correctamente';
            } elseif ($type === 'stripe') {
                \App\Models\Settings::setStripe([
                    'publishable_key' => trim($_POST['publishable_key'] ?? ''),
                    'secret_key' => trim($_POST['secret_key'] ?? ''),
                    'webhook_secret' => trim($_POST['webhook_secret'] ?? '')
                ]);
                $message = 'Credenciales de Stripe guardadas correctamente';
            } elseif ($type === 'unsplash') {
                \App\Models\Settings::setUnsplash([
                    'api_key' => trim($_POST['api_key'] ?? '')
                ]);
                \App\Models\Settings::set('unsplash_status', 'disconnected');
                $message = 'Credenciales de Unsplash guardadas correctamente';
            } elseif ($type === 'briefing_prompt') {
                \App\Models\Settings::setBriefingPrompt($_POST['briefing_prompt'] ?? '');
                $message = 'Prompt de briefing guardado correctamente';
            } else {
                $message = 'Tipo de configuración no reconocido';
            }
            
            if ($isAjax) {
                while (ob_get_level()) ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $message]);
                exit;
            }
            
            \Session::setFlash('success', $message);
        }
        
        \Session::redirect('/superadmin/settings');
    }

    public function settingsTest(): void {
        $this->checkAdmin();
        
        $type = $_GET['type'] ?? '';
        $result = ['success' => false, 'message' => 'Tipo de conexión no válido'];
        
        if ($type === 'meta') {
            $result = $this->testMetaConnection();
        } elseif ($type === 'openai') {
            $result = $this->testOpenAIConnection();
        } elseif ($type === 'unsplash') {
            $result = $this->testUnsplashConnection();
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    private function testMetaConnection(): array {
        $meta = \App\Models\Settings::getMetaApi();
        
        if (empty($meta['app_id']) || empty($meta['app_secret']) || empty($meta['access_token'])) {
            \App\Models\Settings::set('meta_status', 'disconnected');
            return ['success' => false, 'message' => 'Credenciales incompletas. Completa App ID, App Secret y Access Token'];
        }
        
        try {
            $appId = $meta['app_id'];
            $appSecret = $meta['app_secret'];
            $accessToken = $meta['access_token'];
            
            $url = "https://graph.facebook.com/v25.0/debug_token?" . http_build_query([
                'input_token' => $accessToken,
                'access_token' => $appId . '|' . $appSecret
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                \App\Models\Settings::set('meta_status', 'disconnected');
                return ['success' => false, 'message' => 'Error de conexión: ' . $curlError];
            }
            
            $data = json_decode($response, true);
            
            if ($httpCode !== 200) {
                \App\Models\Settings::set('meta_status', 'disconnected');
                $errorMsg = $data['error']['message'] ?? 'Error al conectar con Meta API';
                return ['success' => false, 'message' => $errorMsg];
            }
            
            if (!isset($data['data']['is_valid']) || !$data['data']['is_valid']) {
                \App\Models\Settings::set('meta_status', 'disconnected');
                $errorMsg = $data['data']['error_message'] ?? 'El token de acceso no es válido';
                return ['success' => false, 'message' => $errorMsg];
            }
            
            $expiresAt = $data['data']['expires_at'] ?? 0;
            if ($expiresAt > 0 && $expiresAt < time()) {
                \App\Models\Settings::set('meta_status', 'disconnected');
                return ['success' => false, 'message' => 'El token de acceso ha expirado'];
            }
            
            \App\Models\Settings::set('meta_status', 'connected');
            \App\Models\Settings::set('meta_last_test', date('Y-m-d H:i:s'));
            return ['success' => true, 'message' => 'Conexión exitosa con Meta API. Token válido hasta: ' . ($expiresAt > 0 ? date('d/m/Y H:i', $expiresAt) : 'sin expiración')];
            
        } catch (\Exception $e) {
            \App\Models\Settings::set('meta_status', 'disconnected');
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function testOpenAIConnection(): array {
        $openai = \App\Models\Settings::getOpenAI();
        
        if (empty($openai['api_key'])) {
            \App\Models\Settings::set('openai_status', 'disconnected');
            return ['success' => false, 'message' => 'API Key no configurada'];
        }
        
        try {
            $url = "https://api.openai.com/v1/models";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $openai['api_key']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                \App\Models\Settings::set('openai_status', 'disconnected');
                return ['success' => false, 'message' => 'Error de conexión: ' . $curlError];
            }
            
            if ($httpCode === 200) {
                \App\Models\Settings::set('openai_status', 'connected');
                \App\Models\Settings::set('openai_last_test', date('Y-m-d H:i:s'));
                return ['success' => true, 'message' => 'Conexión exitosa con OpenAI'];
            } elseif ($httpCode === 401) {
                \App\Models\Settings::set('openai_status', 'disconnected');
                return ['success' => false, 'message' => 'API Key inválida. Verifica tu clave en platform.openai.com'];
            } else {
                \App\Models\Settings::set('openai_status', 'disconnected');
                return ['success' => false, 'message' => 'Error HTTP: ' . $httpCode];
            }
        } catch (\Exception $e) {
            \App\Models\Settings::set('openai_status', 'disconnected');
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function testUnsplashConnection(): array {
        $unsplash = \App\Models\Settings::getUnsplash();
        
        if (empty($unsplash['api_key'])) {
            \App\Models\Settings::set('unsplash_status', 'disconnected');
            return ['success' => false, 'message' => 'API Key no configurada'];
        }
        
        try {
            $url = "https://api.unsplash.com/me";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Client-ID " . $unsplash['api_key']
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                \App\Models\Settings::set('unsplash_status', 'disconnected');
                return ['success' => false, 'message' => 'Error de conexión: ' . $curlError];
            }
            
            if ($httpCode === 200) {
                \App\Models\Settings::set('unsplash_status', 'connected');
                \App\Models\Settings::set('unsplash_last_test', date('Y-m-d H:i:s'));
                return ['success' => true, 'message' => 'Conexión exitosa con Unsplash'];
            } elseif ($httpCode === 401) {
                \App\Models\Settings::set('unsplash_status', 'disconnected');
                return ['success' => false, 'message' => 'API Key inválida. Verifica tu clave en unsplash.com/developers'];
            } else {
                \App\Models\Settings::set('unsplash_status', 'disconnected');
                return ['success' => false, 'message' => 'Error HTTP: ' . $httpCode];
            }
        } catch (\Exception $e) {
            \App\Models\Settings::set('unsplash_status', 'disconnected');
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function plans(): void {
        $this->checkAdmin();
        require __DIR__ . '/../../views/superadmin/plans.php';
    }

    public function reports(): void {
        $this->checkAdmin();
        require __DIR__ . '/../../views/superadmin/reports.php';
    }

    public function userDetail(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        $user = \App\Models\User::findById($id);
        
        if (!$user) {
            \Session::redirect('/superadmin/users');
        }
        
        require __DIR__ . '/../../views/superadmin/user_detail.php';
    }

    // Actions
    public function userUpdate(): void {
        $this->checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'client';
            $credits = floatval($_POST['credits'] ?? 0);
            
            if ($id && $name && $email) {
                \App\Models\User::update($id, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $role,
                    'credits' => $credits
                ]);
            }
        }
        
        \Session::redirect('/superadmin/users');
    }

    public function userSuspend(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\User::suspend($id);
        }
        
        \Session::redirect('/superadmin/users');
    }

    public function userActivate(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\User::activate($id);
        }
        
        \Session::redirect('/superadmin/users');
    }

    public function userDelete(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\User::delete($id);
        }
        
        \Session::redirect('/superadmin/users');
    }

    // Agency Actions
    public function agencyUpdate(): void {
        $this->checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $website = trim($_POST['website'] ?? '');
            
            if ($id && $name) {
                \App\Models\Agency::update($id, [
                    'name' => $name,
                    'website' => $website
                ]);
            }
        }
        
        \Session::redirect('/superadmin/agencies');
    }

    public function agencySuspend(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\Agency::suspend($id);
        }
        
        \Session::redirect('/superadmin/agencies');
    }

    public function agencyActivate(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\Agency::activate($id);
        }
        
        \Session::redirect('/superadmin/agencies');
    }

    public function agencyDelete(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\Agency::delete($id);
        }
        
        \Session::redirect('/superadmin/agencies');
    }

    // Masquerade: Login as another user
    public function masquerade(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \Session::masqueradeAs((int)$id);
            \Session::redirect('/dashboard');
        }
        
        \Session::redirect('/superadmin/users');
    }

    // Return to admin from masquerade
    public function stopMasquerade(): void {
        if (\Session::isMasquerading()) {
            \Session::stopMasquerading();
            \Session::redirect('/superadmin');
        }
        
        \Session::redirect('/dashboard');
    }

    // Campaign Templates
    public function campaignTemplates(): void {
        $this->checkAdmin();
        
        $templates = \App\Models\CampaignTemplate::getAll();
        
        $pageTitle = 'Plantillas de Campañas';
        require __DIR__ . '/../../views/superadmin/templates.php';
    }

    public function campaignTemplateCreate(): void {
        $this->checkAdmin();
        
        $pageTitle = 'Crear Plantilla';
        require __DIR__ . '/../../views/superadmin/template_edit.php';
    }

    public function campaignTemplateEdit(): void {
        $this->checkAdmin();
        
        $id = $_GET['id'] ?? 0;
        $template = \App\Models\CampaignTemplate::findById($id);
        
        if (!$template) {
            \Session::redirect('/superadmin/templates');
        }
        
        $pageTitle = 'Editar Plantilla';
        require __DIR__ . '/../../views/superadmin/template_edit.php';
    }

    public function campaignTemplateStore(): void {
        $this->checkAdmin();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $campaignConfig = $_POST['campaign_config'] ?? '{}';
        $adsetConfig = $_POST['adset_config'] ?? '';
        $creativeConfig = $_POST['creative_config'] ?? '';
        $adConfig = $_POST['ad_config'] ?? '';
        $isActive = isset($_POST['is_active']) && in_array($_POST['is_active'], ['on', '1', 'true']) ? 1 : 0;
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es requerido']);
            exit;
        }
        
        // Validar que campaign_config no esté vacío
        if (empty(trim($campaignConfig))) {
            echo json_encode(['success' => false, 'error' => 'La configuración de campaña es requerida']);
            exit;
        }
        
        // Validar que sea JSON válido
        $jsonTest = json_decode($campaignConfig);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'La configuración de campaña debe ser un JSON válido: ' . json_last_error_msg()]);
            exit;
        }
        
        $data = [
            'name' => $name,
            'category' => !empty($category) ? $category : null,
            'description' => $description,
            'campaign_config' => $campaignConfig,
            'adset_config' => !empty($adsetConfig) ? $adsetConfig : null,
            'creative_config' => !empty($creativeConfig) ? $creativeConfig : null,
            'ad_config' => !empty($adConfig) ? $adConfig : null,
            'is_active' => $isActive
        ];
        
        try {
            error_log('Creating template with data: ' . print_r($data, true));
            $id = \App\Models\CampaignTemplate::create($data);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            error_log('CampaignTemplate create error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
        }
        exit;
    }

    public function campaignTemplateUpdate(): void {
        $this->checkAdmin();
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';
        $campaignConfig = $_POST['campaign_config'] ?? '{}';
        $adsetConfig = $_POST['adset_config'] ?? '';
        $creativeConfig = $_POST['creative_config'] ?? '';
        $adConfig = $_POST['ad_config'] ?? '';
        
        // Checkbox: si no viene o viene vacío, es 0; si viene 'on', es 1
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'on' ? 1 : 0;
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es requerido']);
            exit;
        }
        
        // Validar que campaign_config sea JSON válido
        $jsonTest = json_decode($campaignConfig);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'La configuración de campaña debe ser un JSON válido']);
            exit;
        }
        
        $template = \App\Models\CampaignTemplate::findById($id);
        if (!$template) {
            echo json_encode(['success' => false, 'error' => 'Plantilla no encontrada']);
            exit;
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'campaign_config' => $campaignConfig,
            'adset_config' => $adsetConfig ?: null,
            'creative_config' => $creativeConfig ?: null,
            'ad_config' => $adConfig ?: null,
            'is_active' => $isActive
        ];
        
        \App\Models\CampaignTemplate::update($id, $data);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function campaignTemplateDelete(): void {
        $this->checkAdmin();
        
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            exit;
        }
        
        \App\Models\CampaignTemplate::delete($id);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function campaignTemplateToggle(): void {
        $this->checkAdmin();
        
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? 0;
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            exit;
        }
        
        \App\Models\CampaignTemplate::toggleActive($id);
        
        echo json_encode(['success' => true]);
        exit;
    }
}
