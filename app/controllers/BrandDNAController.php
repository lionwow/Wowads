<?php
namespace App\Controllers;

class BrandDNAController {
    private function checkAuth() {
        if (!\Session::isLoggedIn()) {
            \Session::redirect('/');
        }
        return \App\Models\User::findById(\Session::getUserId());
    }

    public function index(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $brandDNAs = \App\Models\BrandDNA::getByAgency($agencyId);
        $socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
        
        $pageTitle = 'ADNs de Marca';
        $currentPage = 'brand-dna';
        
        require __DIR__ . '/../../views/dashboard/brand_dna.php';
    }

    public function create(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
        $facebookAccounts = array_filter($socialAccounts, fn($a) => $a['platform'] === 'facebook');
        
        $pageTitle = 'Crear ADN de Marca';
        $currentPage = 'brand-dna';
        
        require __DIR__ . '/../../views/dashboard/brand_dna_create.php';
    }

    public function edit(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_GET['id'] ?? 0;
        $brandDNA = \App\Models\BrandDNA::findById($id);
        
        if (!$brandDNA || $brandDNA['agency_id'] != $agencyId) {
            \Session::redirect('/brand-dna');
        }
        
        $socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
        $facebookAccounts = array_filter($socialAccounts, fn($a) => $a['platform'] === 'facebook');
        
        $pageTitle = 'Editar ADN de Marca';
        $currentPage = 'brand-dna';
        
        require __DIR__ . '/../../views/dashboard/brand_dna_edit.php';
    }

    public function store(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        // Ensure agencyId is valid
        if (empty($agencyId) || $agencyId == 0) {
            $agencyId = $userId;
        }
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $socialAccountId = $_POST['social_account_id'] ?? '';
        $fanpageId = $_POST['fanpage_id'] ?? '';
        $fanpageName = $_POST['fanpage_name'] ?? '';
        $businessDescription = $_POST['business_description'] ?? '';
        $briefingDescription = $_POST['description'] ?? '';
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es requerido']);
            exit;
        }
        
        if (empty($businessDescription) && empty($briefingDescription)) {
            echo json_encode(['success' => false, 'error' => 'La descripción del negocio o el briefing es requerido']);
            exit;
        }
        
        // Use the generated briefing or the business description
        $description = $briefingDescription ?: $businessDescription;
        
        $data = [
            'name' => $name,
            'user_id' => $userId,
            'social_account_id' => !empty($socialAccountId) ? intval($socialAccountId) : null,
            'fanpage_id' => !empty($fanpageId) ? $fanpageId : null,
            'fanpage_name' => !empty($fanpageName) ? $fanpageName : null,
            'description' => $description,
            'brand_values' => '',
            'business_description' => $businessDescription
        ];
        
        try {
            $id = \App\Models\BrandDNA::create($agencyId, $data);
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
        }
        exit;
    }

    public function update(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $id = $_POST['id'] ?? 0;
        $brandDNA = \App\Models\BrandDNA::findById($id);
        
        if (!$brandDNA || $brandDNA['agency_id'] != $agencyId) {
            echo json_encode(['success' => false, 'error' => 'ADN no encontrado']);
            exit;
        }
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $brandValues = $_POST['brand_values'] ?? '';
        $businessDescription = $_POST['business_description'] ?? '';
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'El nombre es requerido']);
            exit;
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'brand_values' => $brandValues,
            'business_description' => $businessDescription
        ];
        
        \App\Models\BrandDNA::update($id, $data);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function delete(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        header('Content-Type: application/json');
        
        $id = $_POST['id'] ?? 0;
        $brandDNA = \App\Models\BrandDNA::findById($id);
        
        if (!$brandDNA || $brandDNA['agency_id'] != $agencyId) {
            echo json_encode(['success' => false, 'error' => 'ADN no encontrado']);
            exit;
        }
        
        \App\Models\BrandDNA::delete($id);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function generateBriefing(): void {
        $user = $this->checkAuth();
        
        header('Content-Type: application/json');
        
        $description = $_POST['description'] ?? '';
        
        if (empty($description)) {
            echo json_encode(['success' => false, 'error' => 'La descripción es requerida']);
            exit;
        }
        
        $result = $this->callAI($description);
        
        if ($result) {
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al generar el briefing']);
        }
        exit;
    }

    private function callAI(string $description): ?array {
        $openai = \App\Models\Settings::getOpenAI();
        $apiKey = $openai['api_key'] ?? '';
        
        if (empty($apiKey)) {
            return null;
        }
        
        $promptTemplate = \App\Models\Settings::getBriefingPrompt();
        $prompt = str_replace('{description}', $description, $promptTemplate);
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un experto en marketing y branding. Responde siempre en español.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $content = $result['choices'][0]['message']['content'] ?? '';
            
            return [
                'description' => $content,
                'brand_values' => ''
            ];
        }
        
        return null;
    }
    
    public function transcribe(): void {
        $user = $this->checkAuth();
        
        header('Content-Type: application/json');
        
        if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'No se recibió el audio']);
            exit;
        }
        
        $openai = \App\Models\Settings::getOpenAI();
        $apiKey = $openai['api_key'] ?? '';
        
        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'error' => 'API de OpenAI no configurada']);
            exit;
        }
        
        $audioPath = $_FILES['audio']['tmp_name'];
        
        $url = 'https://api.openai.com/v1/audio/transcriptions';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $cfile = curl_file_create($audioPath, 'audio/webm', 'audio.webm');
        $postData = [
            'file' => $cfile,
            'model' => 'whisper-1',
            'language' => 'es'
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $text = $result['text'] ?? '';
            echo json_encode(['success' => true, 'text' => $text]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al transcribir audio', 'debug' => ['http_code' => $httpCode, 'response' => $response]]);
        }
        exit;
    }
}
