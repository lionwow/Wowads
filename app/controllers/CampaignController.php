<?php
namespace App\Controllers;

class CampaignController {
    private function checkAuth() {
        if (!\Session::isLoggedIn()) {
            \Session::redirect('/');
        }
        return \App\Models\User::findById(\Session::getUserId());
    }

    public function index(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $status = $_GET['status'] ?? null;
        
        // Get campaigns by user (or by agency if no user_id)
        $campaigns = \App\Models\Campaign::getByUser($userId, $status);
        
        // If no campaigns, try by agency
        if (empty($campaigns)) {
            $campaigns = \App\Models\Campaign::getByAgency($agencyId, $status);
        }
        
        $stats = \App\Models\Campaign::getStats($agencyId);
        
        require __DIR__ . '/../../views/dashboard/campaigns.php';
    }

    public function create(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $step = $_GET['step'] ?? 1;
        
        $socialAccounts = \App\Models\SocialAccount::getByAgency($agencyId);
        $brandDNA = \App\Models\BrandDNA::getActive($agencyId);
        $clients = \App\Models\Client::getByAgency($agencyId);
        
        // Check if editing existing campaign
        $editCampaignId = $_GET['edit'] ?? null;
        $editCampaign = null;
        if ($editCampaignId) {
            $editCampaign = \App\Models\Campaign::findById((int)$editCampaignId);
            // Verify ownership
            if ($editCampaign && ($editCampaign['user_id'] == $user['id'] || $editCampaign['agency_id'] == $agencyId)) {
                // Load campaign JSON config
                $editCampaign['config'] = json_decode($editCampaign['campaign_json'] ?? '{}', true);
            } else {
                $editCampaign = null;
            }
        }
        
        $platform = $_GET['platform'] ?? null;
        
        require __DIR__ . '/../../views/dashboard/campaign_create.php';
    }

    public function store(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        // Si agencyId está vacío o es 0, usar el ID del usuario
        if (empty($agencyId) || $agencyId == 0) {
            $agencyId = $user['id'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $clientId = !empty($_POST['client_id']) ? intval($_POST['client_id']) : null;
            $brandDnaId = !empty($_POST['brand_dna_id']) ? intval($_POST['brand_dna_id']) : null;
            $socialAccountId = $_POST['social_account_id'] ?? null;
            $socialAccountName = $_POST['social_account_name'] ?? '';
            $accessToken = $_POST['access_token'] ?? '';
            
            // Debug: log all POST data
            error_log('=== POST DATA ===');
            foreach ($_POST as $key => $value) {
                if ($key === 'access_token') {
                    error_log('access_token: ' . strlen($value) . ' chars');
                } else {
                    error_log($key . ': ' . substr($value, 0, 100));
                }
            }
            error_log('===============');
            
            $objective = $_POST['objective'] ?? '';
            
            $businessManagerId = $_POST['business_manager_id'] ?? '';
            $businessManagerName = $_POST['business_manager_name'] ?? '';
            $adAccountId = $_POST['ad_account_id'] ?? '';
            $adAccountName = $_POST['ad_account_name'] ?? '';
            $fanPageId = $_POST['fan_page_id'] ?? '';
            $fanPageName = $_POST['fan_page_name'] ?? '';
            $instagramId = $_POST['instagram_id'] ?? '';
            $instagramName = $_POST['instagram_name'] ?? '';
            $whatsappId = $_POST['whatsapp_id'] ?? '';
            $whatsappName = $_POST['whatsapp_name'] ?? '';
            $pixelId = $_POST['pixel_id'] ?? '';
            $pixelName = $_POST['pixel_name'] ?? '';
            
            $budgetType = !empty($_POST['daily_budget']) ? 'daily_budget' : (!empty($_POST['lifetime_budget']) ? 'lifetime_budget' : 'daily_budget');
            $budgetAmount = $_POST['daily_budget'] ?? $_POST['lifetime_budget'] ?? 0;
            $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] . 'T00:00:00-0500' : null;
            $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] . 'T23:59:00-0500' : null;
            
            $locationType = $_POST['location_type'] ?? 'country';
            $locations = $_POST['locations'] ?? '[]';
            $ageMin = $_POST['age_min'] ?? 18;
            $ageMax = $_POST['age_max'] ?? 65;
            $gender = $_POST['gender'] ?? 'all';
            $interests = $_POST['interests'] ?? '[]';
            
            $template = $_POST['template'] ?? '';
            $templateId = !empty($_POST['template_id']) ? intval($_POST['template_id']) : null;
            $templateName = $_POST['template_name'] ?? '';
            $specialAdCategories = !empty($_POST['special_ad_categories']) ? $_POST['special_ad_categories'] : '[]';
            $imagePrompt = $_POST['image_prompt'] ?? '';
            $adHeadline = $_POST['ad_headline'] ?? '';
            $adDescription = $_POST['ad_description'] ?? '';
            $adUrl = $_POST['ad_url'] ?? '';
            
            // Validate client_id - ensure it exists in clients table or set to null
            if ($clientId) {
                $client = \App\Models\Client::findById($clientId);
                if (!$client) {
                    $clientId = null;
                }
            }
            
            if (empty($name)) {
                \Session::setFlash('error', 'El nombre de la campaña es requerido');
                \Session::redirect('/campaigns/create');
                return;
            }
            
            $config = json_encode([
                'name' => $name,
                'client_id' => $clientId,
                'brand_dna_id' => $brandDnaId,
                'objective' => $objective,
                'social_account_id' => $socialAccountId,
                'social_account_name' => $socialAccountName,
                'access_token' => $accessToken,
                'business_manager_id' => $businessManagerId,
                'business_manager_name' => $businessManagerName,
                'ad_account_id' => $adAccountId,
                'ad_account_name' => $adAccountName,
                'fan_page_id' => $fanPageId,
                'fan_page_name' => $fanPageName,
                'instagram_id' => $instagramId,
                'instagram_name' => $instagramName,
                'whatsapp_id' => $whatsappId,
                'whatsapp_name' => $whatsappName,
                'pixel_id' => $pixelId,
                'pixel_name' => $pixelName,
                $budgetType => $budgetAmount,
                'start_time' => $startDate,
                'end_time' => $endDate,
                'location_type' => $locationType,
                'locations' => $locations,
                'audience_type' => $_POST['audience_type'] ?? '',
                'saved_audiences' => $_POST['saved_audiences'] ?? '[]',
                'age_min' => $ageMin,
                'age_max' => $ageMax,
                'gender' => $gender,
                'interests' => $interests,
                'template' => $template,
                'template_id' => $templateId,
                'template_name' => $templateName,
                'special_ad_categories' => $specialAdCategories,
                'image_prompt' => $imagePrompt,
                'ad_headline' => $adHeadline,
                'ad_description' => $adDescription,
                'ad_url' => $adUrl
            ]);
            
            // Check if editing existing campaign
            $campaignId = $_POST['campaign_id'] ?? null;
            
            if ($campaignId) {
                // Update existing campaign
                $existingCampaign = \App\Models\Campaign::findById((int)$campaignId);
                if ($existingCampaign && ($existingCampaign['user_id'] == $userId || $existingCampaign['agency_id'] == $agencyId)) {
                    \App\Models\Campaign::update((int)$campaignId, [
                        'client_id' => $clientId,
                        'brand_dna_id' => $brandDnaId,
                        'template_id' => $templateId,
                        'name' => $name,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'campaign_json' => $config
                    ]);
                    \Session::setFlash('success', 'Campaña actualizada exitosamente');
                    \Session::redirect('/campaigns');
                    return;
                }
            }
            
            // Create new campaign
            \App\Models\Campaign::create([
                'user_id' => $userId,
                'agency_id' => $agencyId,
                'client_id' => $clientId,
                'brand_dna_id' => $brandDnaId,
                'template_id' => $templateId,
                'name' => $name,
                'status' => 'draft',
                'budget' => $budgetAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'campaign_json' => $config
            ]);
            
            \Session::setFlash('success', 'Campaña creada exitosamente');
            \Session::redirect('/campaigns');
        }
    }

    public function edit(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_GET['id'] ?? 0;
        $campaign = \App\Models\Campaign::findById((int)$id);
        
        // Allow access if user owns the campaign OR is in the same agency
        if (!$campaign || ($campaign['user_id'] != $userId && $campaign['agency_id'] != $agencyId)) {
            \Session::setFlash('error', 'Campaña no encontrada');
            \Session::redirect('/campaigns');
            return;
        }
        
        // Redirect to create wizard with step 1 (for now, can add edit mode later)
        \Session::redirect('/campaigns/create?step=1&edit=' . $id);
    }

    public function update(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $campaign = \App\Models\Campaign::findById((int)$id);
            
            if (!$campaign || ($campaign['user_id'] != $userId && $campaign['agency_id'] != $agencyId)) {
                \Session::setFlash('error', 'Campaña no encontrada');
                \Session::redirect('/campaigns');
                return;
            }
            
            $name = $_POST['name'] ?? $campaign['name'];
            $clientId = $_POST['client_id'] ?? $campaign['client_id'];
            $budget = $_POST['budget'] ?? $campaign['budget'];
            $startDate = $_POST['start_date'] ?? $campaign['start_date'];
            $endDate = $_POST['end_date'] ?? $campaign['end_date'];
            $status = $_POST['status'] ?? $campaign['status'];
            
            \App\Models\Campaign::update((int)$id, [
                'name' => $name,
                'client_id' => $clientId,
                'budget' => $budget,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status
            ]);
            
            \Session::setFlash('success', 'Campaña actualizada');
            \Session::redirect('/campaigns');
        }
    }

    public function delete(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_GET['id'] ?? 0;
        $campaign = \App\Models\Campaign::findById((int)$id);
        
        if ($campaign && ($campaign['user_id'] == $userId || $campaign['agency_id'] == $agencyId)) {
            \App\Models\Campaign::delete((int)$id);
            \Session::setFlash('success', 'Campaña eliminada');
        }
        
        \Session::redirect('/campaigns');
    }

    public function duplicate(): void {
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_GET['id'] ?? 0;
        $campaign = \App\Models\Campaign::findById((int)$id);
        
        if ($campaign && ($campaign['user_id'] == $userId || $campaign['agency_id'] == $agencyId)) {
            \App\Models\Campaign::create([
                'user_id' => $userId,
                'agency_id' => $campaign['agency_id'],
                'client_id' => $campaign['client_id'],
                'name' => $campaign['name'] . ' (Copia)',
                'status' => 'draft',
                'budget' => $campaign['budget'],
                'start_date' => null,
                'end_date' => null
            ]);
            \Session::setFlash('success', 'Campaña duplicada');
        }
        
        \Session::redirect('/campaigns');
    }
    
    public function publish(): void {
        header('Content-Type: application/json');
        
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_POST['id'] ?? 0;
        $campaign = \App\Models\Campaign::findById((int)$id);
        
        if (!$campaign || ($campaign['user_id'] != $userId && $campaign['agency_id'] != $agencyId)) {
            echo json_encode(['success' => false, 'error' => 'Campaña no encontrada']);
            exit;
        }
        
        if ($campaign['status'] === 'active') {
            echo json_encode(['success' => false, 'error' => 'La campaña ya está publicada']);
            exit;
        }
        
        // Get campaign config
        $config = json_decode($campaign['campaign_json'] ?? '{}', true);
        
        // Get template if exists
        $template = null;
        if (!empty($campaign['template_id'])) {
            $template = \App\Models\CampaignTemplate::findById($campaign['template_id']);
        }
        
        // Build final JSON for Meta API
        $finalJson = $this->buildMetaCampaignJson($campaign, $config, $template);
        
        // Log del JSON construido
        error_log('=== FINAL JSON CONSTRUIDO ===');
        error_log('Campaign: ' . json_encode($finalJson['campaign']));
        error_log('AdSet: ' . json_encode($finalJson['adset']));
        
        // Get access token from campaign_json first, fallback to social account table
        $accessToken = $config['access_token'] ?? null;
        
        if (!$accessToken) {
            $socialAccountId = $config['social_account_id'] ?? null;
            if ($socialAccountId) {
                $socialAccount = \App\Models\SocialAccount::findById($socialAccountId);
                $accessToken = $socialAccount['access_token'] ?? null;
            }
        }
        
        error_log('=== PUBLISH CAMPAIGN ===');
        error_log('Access Token: ' . ($accessToken ? 'EXISTS (' . strlen($accessToken) . ' chars)' : 'EMPTY'));
        error_log('Ad Account ID: ' . ($config['ad_account_id'] ?? 'NOT FOUND'));
        error_log('Objective: ' . ($config['objective'] ?? 'NOT FOUND'));
        
        if (!$accessToken) {
            echo json_encode(['success' => false, 'error' => 'No hay token de acceso disponible']);
            exit;
        }
        
        // Send to Meta API
        $metaResult = $this->createMetaCampaign($finalJson, $accessToken, $config);
        
        // Always include debug info
        $metaResult['debug_info'] = [
            'ad_account_id' => $config['ad_account_id'] ?? 'NOT SET',
            'objective' => $config['objective'] ?? 'NOT SET',
            'token_length' => $accessToken ? strlen($accessToken) : 0
        ];
        
        if ($metaResult['success']) {
            // Update campaign with Meta IDs
            \App\Models\Campaign::update((int)$id, [
                'status' => 'active',
                'meta_campaign_id' => $metaResult['campaign_id'] ?? null,
                'meta_adset_id' => $metaResult['adset_id'] ?? null,
                'meta_ad_id' => $metaResult['ad_id'] ?? null,
                'published_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'campaign_id' => $metaResult['campaign_id'],
                    'adset_id' => $metaResult['adset_id'],
                    'creative_id' => $metaResult['creative_id'] ?? null,
                    'ad_id' => $metaResult['ad_id']
                ],
                'debug_payloads' => $metaResult['debug_payloads'] ?? null
            ]);
        } else {
            // Include debug info in error
            $errorData = ['success' => false, 'error' => $metaResult['error'] ?? 'Error al publicar en Meta'];
            if (isset($metaResult['debug_payloads'])) {
                $errorData['debug_payloads'] = $metaResult['debug_payloads'];
            }
            if (isset($metaResult['api_request'])) {
                $errorData['api_request'] = $metaResult['api_request'];
            }
            if (isset($metaResult['api_response'])) {
                $errorData['api_response'] = $metaResult['api_response'];
            }
            if (isset($metaResult['step_errors'])) {
            $errorData['step_errors'] = $metaResult['step_errors'];
        }
        echo json_encode($errorData);
        }
    }
    
    private function buildMetaCampaignJson($campaign, $config, $template): array {
        // ============================================
        // 1️⃣ PRIMERO: Merge de configs (campaign_json > template)
        // ============================================
        
        // Get template configs
        $campaignTemplate = $template ? json_decode($template['campaign_config'] ?? '{}', true) : [];
        $adsetTemplate = $template ? json_decode($template['adset_config'] ?? '{}', true) : [];
        $creativeTemplate = $template ? json_decode($template['creative_config'] ?? '{}', true) : [];
        $adTemplate = $template ? json_decode($template['ad_config'] ?? '{}', true) : [];
        
        // ============================================
        // 2️⃣ CAMPAIGN - Parámetros de Campaign
        // name: campaign_json
        // objective: campaign_json
        // status: template (campaign_config)
        // special_ad_categories: campaign_json
        // is_adset_budget_sharing_enabled: campaign_json
        // ============================================
        
        $objective = $config['objective'] ?? $campaignTemplate['objective'] ?? 'OUTCOME_CONVERSIONS';
        $status = $campaignTemplate['status'] ?? 'PAUSED';
        $specialAdCategories = $config['special_ad_categories'] ?? $campaignTemplate['special_ad_categories'] ?? '';
        $isAdsetBudgetSharing = $config['is_adset_budget_sharing_enabled'] ?? $campaignTemplate['is_adset_budget_sharing_enabled'] ?? false;
        
        // Si hay una categoría seleccionada (no vacía ni "[]"), crear array, sino array vacío
        if (!empty($specialAdCategories) && $specialAdCategories !== '[]') {
            $specialAdCategoriesValue = is_array($specialAdCategories) ? $specialAdCategories : [$specialAdCategories];
        } else {
            $specialAdCategoriesValue = [];
        }
        
        $json['campaign'] = [
            'name' => $campaign['name'],
            'objective' => $objective,
            'status' => $status,
            'special_ad_categories' => $specialAdCategoriesValue,
            'is_adset_budget_sharing_enabled' => $isAdsetBudgetSharing
        ];
        
        // ============================================
        // 3️⃣ ADSET - Parámetros de AdSet
        // name: campaign_json
        // campaign_id: placeholder (se llena al crear campaña)
        // daily_budget / lifetime_budget: campaign_json
        // billing_event: template (adset_config)
        // optimization_goal: template (adset_config)
        // bid_strategy: template (adset_config)
        // destination_type: template (adset_config)
        // start_time: campaign_json
        // end_time: campaign_json
        // ============================================
        
        // Budget - tomar directamente de campaign_json (daily_budget o lifetime_budget)
        
        // Dates
        $startDate = $config['start_time'] ?? '';
        $endDate = $config['end_time'] ?? '';
        
        // Targeting - custom locations
        $locations = json_decode($config['locations'] ?? '[]', true);
        $customLocations = [];
        
        if (!empty($locations) && is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['latitude']) && isset($loc['longitude'])) {
                    $customLocations[] = [
                        'latitude' => floatval($loc['latitude']),
                        'longitude' => floatval($loc['longitude']),
                        'radius' => floatval($loc['radius'] ?? 10),
                        'distance_unit' => $loc['distance_unit'] ?? 'kilometer'
                    ];
                }
            }
        }
        
        // Age
        $ageMin = intval($config['age_min'] ?? $adsetTemplate['targeting']['age_min'] ?? 25);
        $ageMax = intval($config['age_max'] ?? $adsetTemplate['targeting']['age_max'] ?? 45);
        
        // Gender (1 = male, 2 = female, all = [1,2])
        $gender = $config['gender'] ?? null;
        $genders = [];
        if ($gender === 'male') {
            $genders = [1];
        } elseif ($gender === 'female') {
            $genders = [2];
        } elseif ($gender === 'all') {
            $genders = [1, 2];
        } else {
            // Fallback to template genders
            $genders = $adsetTemplate['targeting']['genders'] ?? [1, 2];
        }
        
        // Interests
        $interests = json_decode($config['interests'] ?? '[]', true);
        if (empty($interests) && !empty($adsetTemplate['targeting']['interests'])) {
            $interests = $adsetTemplate['targeting']['interests'];
        }
        
        $json['adset'] = [
            'name' => 'AdSet - ' . $campaign['name'],
            'campaign_id' => 'CAMPAIGN_ID_AQUI',
            'billing_event' => $adsetTemplate['billing_event'] ?? 'IMPRESSIONS',
            'optimization_goal' => $adsetTemplate['optimization_goal'] ?? 'CONVERSATIONS',
            'bid_strategy' => $adsetTemplate['bid_strategy'] ?? 'LOWEST_COST_WITHOUT_CAP',
            'destination_type' => $adsetTemplate['destination_type'] ?? 'WEBSITE',
            'start_time' => $startDate ?: '',
            'end_time' => $endDate ?: '',
            'targeting' => [
                'geo_locations' => [
                    'custom_locations' => !empty($customLocations) ? $customLocations : ($adsetTemplate['targeting']['geo_locations']['custom_locations'] ?? [])
                ],
                'age_min' => $ageMin,
                'age_max' => $ageMax,
                'genders' => $genders,
                'interests' => $interests,
                'publisher_platforms' => $adsetTemplate['targeting']['publisher_platforms'] ?? ['facebook', 'instagram'],
                'facebook_positions' => $adsetTemplate['targeting']['facebook_positions'] ?? ['feed', 'story'],
                'instagram_positions' => $adsetTemplate['targeting']['instagram_positions'] ?? ['stream', 'story']
            ],
            'status' => $adsetTemplate['status'] ?? 'PAUSED'
        ];
        
        // Agregar solo el budget que exista - nunca ambos (valor debe ser > 0)
        $lifetimeBudgetValue = isset($config['lifetime_budget']) ? floatval($config['lifetime_budget']) : 0;
        $dailyBudgetValue = isset($config['daily_budget']) ? floatval($config['daily_budget']) : 0;
        
        if ($lifetimeBudgetValue > 0) {
            $json['adset']['lifetime_budget'] = $lifetimeBudgetValue;
        } elseif ($dailyBudgetValue > 0) {
            $json['adset']['daily_budget'] = $dailyBudgetValue;
        }
        
        // ============================================
        // 4️⃣ CREATIVE - Parámetros de Creative (ADCreatives)
        // name: campaign_json
        // page_id: campaign_json
        // message: campaign_json
        // description: campaign_json
        // image_url: campaign_json
        // link: campaign_json
        // name (headline): campaign_json
        // call_to_action.type: template (campaign_config)
        // ============================================
        $json['creative'] = [
            'name' => 'Creative - ' . $campaign['name'],
            'object_story_spec' => [
                'page_id' => $config['fan_page_id'] ?? $creativeTemplate['page_id'] ?? '',
                'link_data' => [
                    'message' => $config['ad_description'] ?? $creativeTemplate['message'] ?? 'Texto del anuncio aquí',
                    'description' => $config['ad_description'] ?? $creativeTemplate['description'] ?? '',
                    'image_url' => $config['image_url'] ?? $creativeTemplate['image_url'] ?? '',
                    'link' => $config['ad_url'] ?? $creativeTemplate['link'] ?? '',
                    'name' => $config['ad_headline'] ?? $creativeTemplate['name'] ?? 'Headline aquí',
                    'caption' => $config['ad_headline'] ?? $creativeTemplate['caption'] ?? '',
                    'call_to_action' => [
                        'type' => $creativeTemplate['call_to_action_type'] ?? 'SHOP_NOW'
                    ]
                ]
            ]
        ];
        
        // ============================================
        // 5️⃣ AD - Parámetros de Ad
        // ============================================
        $json['ad'] = [
            'name' => 'Ad - ' . $campaign['name'],
            'status' => $adTemplate['status'] ?? 'PAUSED'
        ];
        
        return $json;
    }
    
    private function getCountryCodeFromLocation($locationName): ?string {
        $countryMap = [
            'colombia' => 'CO',
            'mexico' => 'MX',
            'argentina' => 'AR',
            'chile' => 'CL',
            'peru' => 'PE',
            'españa' => 'ES',
            'usa' => 'US',
            'united states' => 'US',
            'brasil' => 'BR',
            'ecuador' => 'EC',
            'uruguay' => 'UY',
            'panama' => 'PA',
            'costa rica' => 'CR',
            'guatemala' => 'GT'
        ];
        
        $name = strtolower($locationName);
        foreach ($countryMap as $key => $code) {
            if (strpos($name, $key) !== false) {
                return $code;
            }
        }
        return null;
    }
    
    private function buildTargeting($config, $adsetTemplate): array {
        $locations = json_decode($config['locations'] ?? '[]', true);
        $interests = json_decode($config['interests'] ?? '[]', true);
        
        $geoLocations = ['countries' => []];
        if (!empty($locations)) {
            $countries = array_unique(array_column($locations, 'country'));
            if (!empty($countries)) {
                $geoLocations = ['countries' => $countries];
            }
        }
        
        return [
            'geo_locations' => $geoLocations,
            'age_min' => intval($config['age_min'] ?? $adsetTemplate['targeting']['age_min'] ?? 25),
            'age_max' => intval($config['age_max'] ?? $adsetTemplate['targeting']['age_max'] ?? 45),
            'genders' => $config['gender'] === 'all' ? [1, 2] : [intval($config['gender'] ?? 1)],
            'interests' => $interests,
            'publisher_platforms' => $adsetTemplate['targeting']['publisher_platforms'] ?? ['facebook', 'instagram'],
            'facebook_positions' => $adsetTemplate['targeting']['facebook_positions'] ?? ['feed', 'story'],
            'instagram_positions' => $adsetTemplate['targeting']['instagram_positions'] ?? ['stream', 'story']
        ];
    }
    
    private function convertOptimizationGoal($goal): string {
        $map = [
            // Old/alternative names
            'CONVERSIONS' => 'CONVERSATIONS',
            'TRAFFIC' => 'LINK_CLICKS',
            'APP_INSTALLS' => 'APP_INSTALLS',
            'LEAD_GENERATION' => 'LEAD_GENERATION',
            'ENGAGEMENT' => 'POST_ENGAGEMENT',
            'VIDEO_VIEWS' => 'VIDEO_VIEWS',
            'STORE_VISITS' => 'STORE_VISITS',
            'REACH' => 'REACH',
            // New format
            'OUTCOME_CONVERSIONS' => 'CONVERSATIONS',
            'OUTCOME_TRAFFIC' => 'LINK_CLICKS',
            'OUTCOME_APP_PROMOTION' => 'APP_INSTALLS',
            'OUTCOME_LEADS' => 'LEAD_GENERATION',
            'OUTCOME_ENGAGEMENT' => 'POST_ENGAGEMENT',
            'OUTCOME_SALES' => 'CONVERSATIONS',
            'OUTCOME_AWARENESS' => 'REACH'
        ];
        return $map[$goal] ?? 'CONVERSATIONS';
    }
    
    public function json(): void {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $user = $this->checkAuth();
        $userId = $user['id'];
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $id = $_GET['id'] ?? 0;
        $campaign = \App\Models\Campaign::findById((int)$id);
        
        if (!$campaign || ($campaign['user_id'] != $userId && $campaign['agency_id'] != $agencyId)) {
            echo json_encode(['success' => false, 'error' => 'Campaña no encontrada']);
            exit;
        }
        
        $config = json_decode($campaign['campaign_json'] ?? '{}', true);
        
        // Get template if exists
        $template = null;
        $templateId = $campaign['template_id'] ?? null;
        
        if (!empty($templateId)) {
            $template = \App\Models\CampaignTemplate::findById((int)$templateId);
        }

        // Build combined JSON (same logic as publish)
        try {
            $combinedJson = $this->buildMetaCampaignJson($campaign, $config, $template);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'json' => $combinedJson
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    private function createMetaCampaign($finalJson, $accessToken, $config): array {
        // Obtener ad_account_id desde config
        $adAccountId = $config['ad_account_id'] ?? '';
        
        if (empty($adAccountId)) {
            return ['success' => false, 'error' => 'ID de cuenta publicitaria no encontrado'];
        }
        
        // Ensure adAccountId has act_ prefix
        if (strpos($adAccountId, 'act_') !== 0) {
            $adAccountId = 'act_' . $adAccountId;
        }
        
        $apiVersion = 'v25.0';
        $baseUrl = "https://graph.facebook.com/$apiVersion";
        
        // ============================================
        // 1️⃣ CREAR CAMPAÑA
        // ============================================
        $campaignData = $finalJson['campaign'];
        $campaignPayload = [
            'name' => $campaignData['name'],
            'objective' => $campaignData['objective'],
            'status' => 'PAUSED',
            'access_token' => $accessToken
        ];
        
        // Si hay ad_account_id y budget, agregarlos
        if (!empty($campaignData['ad_account_id'])) {
            $campaignPayload['ad_account_id'] = $campaignData['ad_account_id'];
        }
        if (!empty($campaignData['budget'])) {
            $campaignPayload['budget'] = $campaignData['budget'];
        }
        // special_ad_categories: si es "[]" o vacío, enviar array vacío [], si no, enviar con la categoría
        $specialCats = $campaignData['special_ad_categories'] ?? '';
        if ($specialCats === '' || $specialCats === '[]' || empty($specialCats)) {
            $campaignPayload['special_ad_categories'] = [];
        } else {
            $campaignPayload['special_ad_categories'] = [$specialCats];
        }
        if (!empty($campaignData['start_time'])) {
            $campaignPayload['start_time'] = $campaignData['start_time'];
        }
        if (!empty($campaignData['stop_time'])) {
            $campaignPayload['stop_time'] = $campaignData['stop_time'];
        }
        if (isset($campaignData['is_adset_budget_sharing_enabled'])) {
            $campaignPayload['is_adset_budget_sharing_enabled'] = $campaignData['is_adset_budget_sharing_enabled'];
        }
        
        // Guardar payload completo para debug
        $debugPayloads = [
            'campaign' => $campaignPayload,
            'adset' => null,
            'creative' => null,
            'ad' => null
        ];
        
        $campaignResponse = $this->makeApiRequest('POST', "$baseUrl/$adAccountId/campaigns", $campaignPayload);
        
        if (empty($campaignResponse['id'])) {
            return [
                'success' => false, 
                'error' => 'Error al crear campaña: ' . ($campaignResponse['error']['message'] ?? json_encode($campaignResponse)), 
                'step_errors' => [
                    'campaign' => $campaignResponse
                ],
                'debug_payloads' => $debugPayloads,
                'api_request' => [
                    'url' => "$baseUrl/$adAccountId/campaigns",
                    'method' => 'POST',
                    'payload' => $campaignPayload
                ],
                'api_response' => $campaignResponse
            ];
        }
        
        $campaignId = $campaignResponse['id'];
        
        // ============================================
        // 2️⃣ CREAR ADSET
        // ============================================
        $adsetData = $finalJson['adset'];
        
        $adsetPayload = [
            'name' => $adsetData['name'],
            'campaign_id' => $campaignId,
            'billing_event' => $adsetData['billing_event'],
            'optimization_goal' => $adsetData['optimization_goal'],
            'bid_strategy' => $adsetData['bid_strategy'],
            'destination_type' => $adsetData['destination_type'] ?? 'WEBSITE',
            'targeting' => json_encode($adsetData['targeting']),
            'status' => $adsetData['status'],
            'access_token' => $accessToken
        ];
        
        // Set budget field dynamically (daily_budget or lifetime_budget) - solo uno, nunca ambos
        // Primero unset cualquier budget existente para asegurar que no queden ambos
        unset($adsetPayload['daily_budget'], $adsetPayload['lifetime_budget']);
        
        // Validar que el valor sea mayor a 0 antes de agregar
        $lifetimeBudgetValue = isset($adsetData['lifetime_budget']) ? floatval($adsetData['lifetime_budget']) : 0;
        $dailyBudgetValue = isset($adsetData['daily_budget']) ? floatval($adsetData['daily_budget']) : 0;
        
        if ($lifetimeBudgetValue > 0) {
            $adsetPayload['lifetime_budget'] = $lifetimeBudgetValue;
        } elseif ($dailyBudgetValue > 0) {
            $adsetPayload['daily_budget'] = $dailyBudgetValue;
        }
        
        // Limpiar cualquier valor null antes de enviar
        $adsetPayload = array_filter($adsetPayload, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Debug: mostrar EXACTAMENTE lo que se envía
        $debugPayloads['adset'] = [
            'name' => $adsetData['name'],
            'campaign_id' => $campaignId,
            'billing_event' => $adsetData['billing_event'],
            'optimization_goal' => $adsetData['optimization_goal'],
            'bid_strategy' => $adsetData['bid_strategy'],
            'destination_type' => $adsetData['destination_type'] ?? 'WEBSITE',
            'targeting' => $adsetData['targeting'],
            'status' => $adsetData['status'],
            'access_token' => $accessToken ? substr($accessToken, 0, 20) . '...' : 'EMPTY'
        ];
        
        // Solo agregar al debug el budget que realmente se envió
        if (isset($adsetPayload['daily_budget'])) {
            $debugPayloads['adset']['daily_budget'] = $adsetPayload['daily_budget'];
        }
        if (isset($adsetPayload['lifetime_budget'])) {
            $debugPayloads['adset']['lifetime_budget'] = $adsetPayload['lifetime_budget'];
        }
        
        $adsetResponse = $this->makeApiRequest('POST', "$baseUrl/$adAccountId/adsets", $adsetPayload);
        
        if (empty($adsetResponse['id'])) {
            return [
                'success' => false, 
                'error' => 'Error al crear adset: ' . ($adsetResponse['error']['message'] ?? json_encode($adsetResponse)), 
                'step_errors' => [
                    'campaign' => ['id' => $campaignId],
                    'adset' => $adsetResponse
                ],
                'debug_payloads' => $debugPayloads
            ];
        }
        
        $adsetId = $adsetResponse['id'];
        
        // ============================================
        // 3️⃣ CREAR CREATIVE
        // ============================================
        $creativeData = $finalJson['creative'];
        
        $creativePayload = [
            'name' => $creativeData['name'],
            'object_story_spec' => json_encode([
                'page_id' => $creativeData['page_id'],
                'link_data' => [
                    'message' => $creativeData['message'],
                    'link' => $creativeData['link'],
                    'caption' => $creativeData['caption'],
                    'call_to_action' => [
                        'type' => $creativeData['call_to_action_type']
                    ]
                ]
            ]),
            'access_token' => $accessToken
        ];
        $debugPayloads['creative'] = [
            'name' => $creativeData['name'],
            'page_id' => $creativeData['page_id'],
            'message' => $creativeData['message'],
            'link' => $creativeData['link'],
            'caption' => $creativeData['caption'],
            'call_to_action_type' => $creativeData['call_to_action_type'],
            'access_token' => $accessToken ? substr($accessToken, 0, 20) . '...' : 'EMPTY'
        ];
        
        $creativeResponse = $this->makeApiRequest('POST', "$baseUrl/$adAccountId/adcreatives", $creativePayload);
        
        if (empty($creativeResponse['id'])) {
            return [
                'success' => false, 
                'error' => 'Error al crear creativo: ' . ($creativeResponse['error']['message'] ?? json_encode($creativeResponse)), 
                'step_errors' => [
                    'campaign' => ['id' => $campaignId],
                    'adset' => ['id' => $adsetId],
                    'creative' => $creativeResponse
                ],
                'debug_payloads' => $debugPayloads
            ];
        }
        
        $creativeId = $creativeResponse['id'];
        
        // ============================================
        // 4️⃣ CREAR AD
        // ============================================
        $adData = $finalJson['ad'];
        
        $adPayload = [
            'name' => $adData['name'],
            'adset_id' => $adsetId,
            'creative' => json_encode([
                'creative_id' => $creativeId
            ]),
            'status' => $adData['status'],
            'access_token' => $accessToken
        ];
        $debugPayloads['ad'] = [
            'name' => $adData['name'],
            'adset_id' => $adsetId,
            'creative_id' => $creativeId,
            'status' => $adData['status'],
            'access_token' => $accessToken ? substr($accessToken, 0, 20) . '...' : 'EMPTY'
        ];
        
        $adResponse = $this->makeApiRequest('POST', "$baseUrl/$adAccountId/ads", $adPayload);
        
        if (empty($adResponse['id'])) {
            return [
                'success' => false, 
                'error' => 'Error al crear anuncio: ' . ($adResponse['error']['message'] ?? json_encode($adResponse)), 
                'step_errors' => [
                    'campaign' => ['id' => $campaignId],
                    'adset' => ['id' => $adsetId],
                    'creative' => ['id' => $creativeId],
                    'ad' => $adResponse
                ],
                'debug_payloads' => $debugPayloads
            ];
        }
        
        $adId = $adResponse['id'];
        
        return [
            'success' => true,
            'campaign_id' => $campaignId,
            'adset_id' => $adsetId,
            'creative_id' => $creativeId,
            'ad_id' => $adId,
            'debug_payloads' => $debugPayloads
        ];
    }
    
    private function makeApiRequest(string $method, string $url, array $data): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            // Usar json_encode para preservar arrays vacíos
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => ['message' => $error], 'http_code' => $httpCode, 'raw_response' => $response];
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            return [
                'error' => ['message' => "HTTP $httpCode: " . ($decoded['error']['message'] ?? $response)], 
                'http_code' => $httpCode,
                'raw_response' => $response,
                'decoded' => $decoded
            ];
        }
        
        return $decoded ?? [];
    }
}
