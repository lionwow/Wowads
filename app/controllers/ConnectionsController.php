<?php
namespace App\Controllers;

class ConnectionsController {
    private function checkAuth(bool $json = false) {
        if (!\Session::isLoggedIn()) {
            if ($json) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            \Session::redirect('/');
        }
        return \App\Models\User::findById(\Session::getUserId());
    }

    public function index(): void {
        $user = $this->checkAuth();
        $agencyId = $user['agency_id'] ?? $user['id'];
        
        $accounts = \App\Models\SocialAccount::getByAgency($agencyId);
        
        require __DIR__ . '/../../views/dashboard/connections.php';
    }

    public function connect(): void {
        $user = $this->checkAuth();
        
        $platform = $_GET['platform'] ?? '';
        
        // Generate OAuth URL based on platform
        $redirectUri = $this->getRedirectUri($platform);
        
        switch ($platform) {
            case 'facebook':
                $oauthUrl = $this->getFacebookOAuthUrl($redirectUri);
                break;
            case 'tiktok':
                $oauthUrl = $this->getTikTokOAuthUrl($redirectUri);
                break;
            case 'google':
                $oauthUrl = $this->getGoogleOAuthUrl($redirectUri);
                break;
            default:
                \Session::redirect('/connections');
        }
        
        header('Location: ' . $oauthUrl);
        exit;
    }

    public function callback(): void {
        $user = $this->checkAuth();
        $platform = $_GET['platform'] ?? '';
        $code = $_GET['code'] ?? '';
        
        if (!$code) {
            \Session::redirect('/connections?error=no_code');
        }
        
        $redirectUri = $this->getRedirectUri($platform);
        
        // Exchange code for token
        $tokenData = $this->exchangeCodeForToken($platform, $code, $redirectUri);
        
        if ($tokenData) {
            // Get account info
            $accountInfo = $this->getAccountInfo($platform, $tokenData['access_token']);
            
            if ($accountInfo) {
                $agencyId = $user['agency_id'] ?? null;
                
                // Check if this account is already connected
                $existingAccount = \App\Models\SocialAccount::findByAccountId($accountInfo['id'], $platform, $agencyId);
                
                if ($existingAccount) {
                    \Session::redirect('/connections?error=already_connected');
                }
                
                \App\Models\SocialAccount::create(
                    $user['id'],
                    $agencyId,
                    $platform,
                    $accountInfo['id'],
                    $accountInfo['name'],
                    $tokenData['access_token'],
                    $tokenData['refresh_token'] ?? null
                );
                
                \Session::redirect('/connections?success=connected');
            }
        }
        
        \Session::redirect('/connections?error=connection_failed');
    }

    public function disconnect(): void {
        $this->checkAuth();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\SocialAccount::disconnect((int)$id);
        }
        
        \Session::redirect('/connections');
    }

    public function delete(): void {
        $this->checkAuth();
        
        $id = $_GET['id'] ?? 0;
        if ($id) {
            \App\Models\SocialAccount::delete((int)$id);
        }
        
        \Session::redirect('/connections');
    }

    private function getRedirectUri(string $platform): string {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $baseUrl = ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) ? '' : rtrim($scriptDir, '/');
        return $scheme . '://' . $host . $baseUrl . '/connections/callback?platform=' . $platform;
    }

    private function getFacebookOAuthUrl(string $redirectUri): string {
        $meta = \App\Models\Settings::getMetaApi();
        $appId = $meta['app_id'] ?? '';
        
        if (empty($appId)) {
            \Session::redirect('/connections?error=no_app_configured');
            return '';
        }
        
        $scope = 'ads_management,business_management,pages_read_engagement';
        $oauthUrl = "https://www.facebook.com/v25.0/dialog/oauth?client_id={$appId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scope}&response_type=code";
        
        return $oauthUrl;
    }

    private function getTikTokOAuthUrl(string $redirectUri): string {
        $appId = 'YOUR_TIKTOK_APP_ID';
        $scope = 'user.info.basic,video.list';
        return "https://www.tiktok.com/v2/auth/authorize/?client_key={$appId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scope}&response_type=code";
    }

    private function getGoogleOAuthUrl(string $redirectUri): string {
        $clientId = 'YOUR_GOOGLE_CLIENT_ID';
        $scope = 'https://www.googleapis.com/auth/adwords';
        return "https://accounts.google.com/o/oauth2/v2/auth?client_id={$clientId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scope}&response_type=code&access_type=offline";
    }

    private function exchangeCodeForToken(string $platform, string $code, string $redirectUri): ?array {
        if ($platform === 'facebook') {
            return $this->exchangeFacebookCodeForToken($code, $redirectUri);
        }
        
        return [
            'access_token' => 'demo_access_token_' . time(),
            'refresh_token' => 'demo_refresh_token_' . time(),
            'expires_in' => 3600
        ];
    }
    
    private function exchangeFacebookCodeForToken(string $code, string $redirectUri): ?array {
        $meta = \App\Models\Settings::getMetaApi();
        
        if (empty($meta['app_id']) || empty($meta['app_secret'])) {
            return null;
        }
        
        $appId = $meta['app_id'];
        $appSecret = $meta['app_secret'];
        
        $url = "https://graph.facebook.com/v25.0/oauth/access_token?" . http_build_query([
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['access_token'])) {
                $longLivedToken = $this->getLongLivedAccessToken($data['access_token'], $appId, $appSecret);
                return $longLivedToken ?: $data;
            }
        }
        
        return null;
    }
    
    private function getLongLivedAccessToken(string $shortLivedToken, string $appId, string $appSecret): ?array {
        $url = "https://graph.facebook.com/v25.0/oauth/access_token?" . http_build_query([
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }

    private function getAccountInfo(string $platform, string $accessToken): ?array {
        // Get actual account info from the platform's API
        
        switch ($platform) {
            case 'facebook':
                return $this->getFacebookAccountInfo($accessToken);
            case 'tiktok':
                return [
                    'id' => 'tt_' . time(),
                    'name' => 'Demo TikTok Account'
                ];
            case 'google':
                return [
                    'id' => 'ggl_' . time(),
                    'name' => 'Demo Google Ads Account'
                ];
            default:
                return null;
        }
    }
    
    private function getFacebookAccountInfo(string $accessToken): ?array {
        $url = "https://graph.facebook.com/v25.0/me?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return [
                'id' => $data['id'],
                'name' => $data['name']
            ];
        }
        
        return null;
    }
    
    public function getPages(): void {
        $user = $this->checkAuth(true);
        $platform = $_GET['platform'] ?? 'facebook';
        $token = $_GET['token'] ?? '';
        $adAccountId = $_GET['adaccount'] ?? '';
        $businessId = $_GET['business_id'] ?? '';
        
        $pages = [];
        
        // If adaccount is provided, get assigned pages from that ad account
        if (!empty($token) && !empty($adAccountId)) {
            // First try: GET /act_{ad-account-id}/assigned_pages
            $pages = $this->getAssignedPagesFromAdAccount($token, $adAccountId);
            
            // If no pages from ad account, try getting from business
            if (empty($pages) && !empty($businessId)) {
                // Second try: GET /{business-id}/owned_pages
                $pages = $this->getOwnedPagesFromBusiness($token, $businessId);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'pages' => $pages,
                'debug' => ['adaccount' => $adAccountId, 'business_id' => $businessId]
            ]);
            exit;
        }
        
        // If token is provided but no adaccount, get all pages
        if (!empty($token)) {
            if ($platform === 'facebook') {
                // Get pages from user
                $pages = $this->getFacebookPages($token);
                
                // Also get pages from businesses
                $businesses = $this->getFacebookBusinesses($token);
                foreach ($businesses as $business) {
                    $businessPages = $this->getFacebookPagesFromBusiness($token, $business['id']);
                    $pages = array_merge($pages, $businessPages);
                }
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'pages' => $pages,
                    'debug' => [
                        'from_user' => count($this->getFacebookPages($token)),
                        'businesses_count' => count($businesses)
                    ]
                ]);
                exit;
            }
        }
        
        // Otherwise, get from stored accounts
        $agencyId = $user['agency_id'] ?? $user['id'];
        $accounts = \App\Models\SocialAccount::getByAgency($agencyId, $platform);
        
        if ($platform === 'facebook' && !empty($accounts)) {
            foreach ($accounts as $account) {
                if (!empty($account['access_token'])) {
                    // Get pages from user
                    $accountPages = $this->getFacebookPages($account['access_token']);
                    if ($accountPages) {
                        $pages = array_merge($pages, $accountPages);
                    }
                    
                    // Get pages from businesses
                    $businesses = $this->getFacebookBusinesses($account['access_token']);
                    foreach ($businesses as $business) {
                        $businessPages = $this->getFacebookPagesFromBusiness($account['access_token'], $business['id']);
                        foreach ($businessPages as $page) {
                            $page['business_id'] = $business['id'];
                            $page['business_name'] = $business['name'];
                            $pages[] = $page;
                        }
                    }
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pages' => $pages]);
        exit;
    }
    
    private function getAssignedPagesFromAdAccount(string $accessToken, string $adAccountId): array {
        // Normalize ad account ID to act_{id} format
        $normalizedId = $adAccountId;
        if (!str_starts_with($normalizedId, 'act_')) {
            $normalizedId = 'act_' . ltrim($adAccountId, 'act_');
        }
        
        // GET /act_{ad-account-id}/assigned_pages
        $url = "https://graph.facebook.com/v25.0/" . $normalizedId . "/assigned_pages?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,category'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        error_log("Assigned Pages API HTTP " . $httpCode . ": " . $response);
        return [];
    }
    
    private function getOwnedPagesFromBusiness(string $accessToken, string $businessId): array {
        // GET /{business-id}/owned_pages
        $url = "https://graph.facebook.com/v25.0/" . $businessId . "/owned_pages?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,category'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        error_log("Owned Pages API HTTP " . $httpCode . ": " . $response);
        return [];
    }
    
    private function getFacebookPagesFromBusiness(string $accessToken, string $businessId): array {
        $url = "https://graph.facebook.com/v25.0/" . $businessId . "/pages?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,category,access_token'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    private function getFacebookPages(string $accessToken): array {
        $url = "https://graph.facebook.com/v25.0/me/accounts?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,category,access_token'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    public function getAdAccounts(): void {
        $user = $this->checkAuth(true);
        $platform = $_GET['platform'] ?? 'facebook';
        $token = $_GET['token'] ?? '';
        $businessId = $_GET['business_id'] ?? '';
        
        $adAccounts = [];
        
        // If business_id is provided, get ad accounts ONLY from that business
        if (!empty($token) && !empty($businessId)) {
            $adAccounts = $this->getOwnedAdAccountsFromBusiness($token, $businessId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'ad_accounts' => $adAccounts,
                'debug' => ['business_id' => $businessId, 'source' => 'business_owned']
            ]);
            exit;
        }
        
        // If token is provided but no business_id, get from all businesses
        if (!empty($token)) {
            // Get user's ad accounts
            $adAccounts = $this->getFacebookAdAccounts($token);
            
            // Also get businesses and their ad accounts
            $businesses = $this->getFacebookBusinesses($token);
            foreach ($businesses as $business) {
                $businessAdAccounts = $this->getFacebookAdAccountsFromBusiness($token, $business['id']);
                $adAccounts = array_merge($adAccounts, $businessAdAccounts);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'ad_accounts' => $adAccounts,
                'debug' => [
                    'from_user' => count($this->getFacebookAdAccounts($token)),
                    'from_businesses' => count($businesses)
                ]
            ]);
            exit;
        }
        
        // Otherwise, get from stored accounts
        $agencyId = $user['agency_id'] ?? $user['id'];
        $accounts = \App\Models\SocialAccount::getByAgency($agencyId, $platform);
        
        if ($platform === 'facebook' && !empty($accounts)) {
            foreach ($accounts as $account) {
                if (!empty($account['access_token'])) {
                    // Get user's ad accounts
                    $accountsList = $this->getFacebookAdAccounts($account['access_token']);
                    if ($accountsList) {
                        foreach ($accountsList as $adAccount) {
                            $adAccount['connected_account_id'] = $account['id'];
                            $adAccounts[] = $adAccount;
                        }
                    }
                    
                    // Get businesses and their ad accounts
                    $businesses = $this->getFacebookBusinesses($account['access_token']);
                    foreach ($businesses as $business) {
                        $businessAdAccounts = $this->getFacebookAdAccountsFromBusiness($account['access_token'], $business['id']);
                        foreach ($businessAdAccounts as $adAccount) {
                            $adAccount['connected_account_id'] = $account['id'];
                            $adAccount['business_id'] = $business['id'];
                            $adAccount['business_name'] = $business['name'];
                            $adAccounts[] = $adAccount;
                        }
                    }
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'ad_accounts' => $adAccounts]);
        exit;
    }
    
    private function getOwnedAdAccountsFromBusiness(string $accessToken, string $businessId): array {
        // GET /{business-id}/owned_ad_accounts - only ad accounts owned by this business
        $url = "https://graph.facebook.com/v25.0/" . $businessId . "/owned_ad_accounts?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,account_status,currency,timezone_name'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        error_log("Owned AdAccounts API HTTP " . $httpCode . ": " . $response);
        return [];
    }
    
    private function getFacebookAdAccounts(string $accessToken): array {
        $url = "https://graph.facebook.com/v25.0/me/adaccounts?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,account_status,currency,timezone_name',
            'limit' => 100
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    public function getPixels(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        $adAccountId = $_GET['adaccount'] ?? '';
        
        $pixels = [];
        $debug = [];
        
        // Flow: User → Businesses → Ad Accounts → Pixels
        
        // If ad account ID is provided, get pixels from that specific account
        if (!empty($token) && !empty($adAccountId)) {
            $pixels = $this->getFacebookPixelsFromAdAccount($token, $adAccountId);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'pixels' => $pixels, 
                'source' => 'specific_adaccount',
                'adaccount_id' => $adAccountId
            ]);
            exit;
        }
        
        // If token is provided but no ad account, follow the full flow
        if (!empty($token)) {
            // Step 1: Get Businesses
            $businesses = $this->getFacebookBusinesses($token);
            $debug['businesses_count'] = count($businesses);
            
            // Step 2: Get Ad Accounts (from user and businesses)
            $adAccounts = $this->getFacebookAdAccounts($token);
            
            // Add ad accounts from businesses
            foreach ($businesses as $business) {
                $businessAdAccounts = $this->getFacebookAdAccountsFromBusiness($token, $business['id']);
                $adAccounts = array_merge($adAccounts, $businessAdAccounts);
            }
            
            $debug['adaccounts_count'] = count($adAccounts);
            
            // Step 3: Get Pixels from each Ad Account
            foreach ($adAccounts as $adAccount) {
                $accountPixels = $this->getFacebookAdsPixels($token, $adAccount['id']);
                foreach ($accountPixels as $pixel) {
                    $pixel['ad_account_id'] = $adAccount['id'];
                    $pixel['ad_account_name'] = $adAccount['name'] ?? '';
                    $pixels[] = $pixel;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'pixels' => $pixels, 
                'count' => count($pixels),
                'debug' => $debug
            ]);
            exit;
        }
        
        // Otherwise, get from stored accounts
        $agencyId = $user['agency_id'] ?? $user['id'];
        $accounts = \App\Models\SocialAccount::getByAgency($agencyId, 'facebook');
        
        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                if (!empty($account['access_token'])) {
                    // Get businesses
                    $businesses = $this->getFacebookBusinesses($account['access_token']);
                    
                    // Get ad accounts
                    $accountAdAccounts = $this->getFacebookAdAccounts($account['access_token']);
                    foreach ($businesses as $business) {
                        $businessAdAccounts = $this->getFacebookAdAccountsFromBusiness($account['access_token'], $business['id']);
                        $accountAdAccounts = array_merge($accountAdAccounts, $businessAdAccounts);
                    }
                    
                    // Get pixels
                    foreach ($accountAdAccounts as $adAccount) {
                        $accountPixels = $this->getFacebookAdsPixels($account['access_token'], $adAccount['id']);
                        foreach ($accountPixels as $pixel) {
                            $pixel['ad_account_id'] = $adAccount['id'];
                            $pixel['ad_account_name'] = $adAccount['name'] ?? '';
                            $pixels[] = $pixel;
                        }
                    }
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pixels' => $pixels, 'count' => count($pixels)]);
        exit;
    }
    
    public function getSavedAudiences(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        $adAccountId = $_GET['adaccount'] ?? '';
        
        header('Content-Type: application/json');
        
        if (empty($token) || empty($adAccountId)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters', 'audiences' => []]);
            exit;
        }
        
        $audiences = $this->getSavedAudiencesFromAdAccount($token, $adAccountId);
        
        echo json_encode([
            'success' => true, 
            'audiences' => $audiences
        ]);
        exit;
    }
    
    public function getCustomAudiences(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        $adAccountId = $_GET['adaccount'] ?? '';
        
        header('Content-Type: application/json');
        
        if (empty($token) || empty($adAccountId)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters', 'audiences' => []]);
            exit;
        }
        
        $audiences = $this->getCustomAudiencesFromAdAccount($token, $adAccountId);
        
        echo json_encode([
            'success' => true, 
            'audiences' => $audiences
        ]);
        exit;
    }
    
    private function getSavedAudiencesFromAdAccount(string $accessToken, string $adAccountId): array {
        $normalizedId = $adAccountId;
        if (!str_starts_with($normalizedId, 'act_')) {
            $normalizedId = 'act_' . ltrim($adAccountId, 'act_');
        }
        
        $url = "https://graph.facebook.com/v25.0/" . $normalizedId . "/saved_audiences?access_token=" . urlencode($accessToken);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        error_log("Saved Audiences API HTTP " . $httpCode . ": " . $response);
        return [];
    }
    
    private function getCustomAudiencesFromAdAccount(string $accessToken, string $adAccountId): array {
        $normalizedId = $adAccountId;
        if (!str_starts_with($normalizedId, 'act_')) {
            $normalizedId = 'act_' . ltrim($adAccountId, 'act_');
        }
        
        $url = "https://graph.facebook.com/v25.0/" . $normalizedId . "/customaudiences?access_token=" . urlencode($accessToken);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        error_log("Custom Audiences API HTTP " . $httpCode . ": " . $response);
        return [];
    }
    
    private function getFacebookBusinesses(string $accessToken): array {
        $url = "https://graph.facebook.com/v25.0/me/businesses?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    private function getFacebookAdAccountsFromBusiness(string $accessToken, string $businessId): array {
        $url = "https://graph.facebook.com/v25.0/" . $businessId . "/adaccounts?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,account_status'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    private function getFacebookAdsPixels(string $accessToken, string $adAccountId): array {
        // Normalize ad account ID to act_{id} format
        $normalizedId = $adAccountId;
        if (!str_starts_with($normalizedId, 'act_')) {
            $normalizedId = 'act_' . ltrim($adAccountId, 'act_');
        }
        
        $url = "https://graph.facebook.com/v25.0/" . $normalizedId . "/adspixels?" . http_build_query([
            'access_token' => $accessToken,
            'fields' => 'id,name,creation_time'
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
            error_log("Facebook AdsPixels API Error: " . $curlError);
            return [];
        }
        
        if ($httpCode !== 200) {
            error_log("Facebook AdsPixels HTTP " . $httpCode . ": " . $response);
            return [];
        }
        
        $data = json_decode($response, true);
        return $data['data'] ?? [];
    }
    
    private function getFacebookPixelsFromAdAccount(string $accessToken, string $adAccountId): array {
        return $this->getFacebookAdsPixels($accessToken, $adAccountId);
    }
    
    public function getInterests(): void {
        $user = $this->checkAuth(true);
        
        $keyword = $_GET['keyword'] ?? '';
        
        $interests = [];
        
        if (!empty($keyword) && strlen($keyword) >= 2) {
            $interests = $this->searchFacebookInterests($keyword);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'interests' => $interests]);
        exit;
    }
    
    private function searchFacebookInterests(string $keyword): array {
        $meta = \App\Models\Settings::getMetaApi();
        
        if (empty($meta['access_token'])) {
            return [];
        }
        
        $url = "https://graph.facebook.com/v25.0/search?" . http_build_query([
            'type' => 'adinterest',
            'q' => $keyword,
            'access_token' => $meta['access_token'],
            'limit' => 20
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    public function getLocations(): void {
        $user = $this->checkAuth(true);
        
        $keyword = $_GET['keyword'] ?? '';
        $locationType = $_GET['type'] ?? 'country';
        
        $locations = [];
        
        if (!empty($keyword) && strlen($keyword) >= 2) {
            $locations = $this->searchFacebookLocations($keyword, $locationType);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'locations' => $locations]);
        exit;
    }
    
    public function getGeoLocations(): void {
        $user = $this->checkAuth(true);
        
        $token = $_GET['token'] ?? '';
        $keyword = $_GET['q'] ?? '';
        
        header('Content-Type: application/json');
        
        if (empty($token)) {
            echo json_encode(['success' => false, 'error' => 'No access token provided', 'locations' => []]);
            exit;
        }
        
        if (empty($keyword) || strlen($keyword) < 2) {
            echo json_encode(['success' => true, 'locations' => []]);
            exit;
        }
        
        $url = "https://graph.facebook.com/v19.0/search?" . http_build_query([
            'type' => 'adgeolocation',
            'q' => $keyword,
            'location_types' => ['city', 'region', 'country'],
            'fields' => 'key,name,latitude,longitude',
            'access_token' => $token,
            'limit' => 25
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $locations = [];
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            // Debug log
            error_log('GeoLocation API response: ' . $response);
            
            if (!empty($data['data'])) {
                foreach ($data['data'] as $loc) {
                    $lat = isset($loc['latitude']) ? floatval($loc['latitude']) : 0;
                    $lng = isset($loc['longitude']) ? floatval($loc['longitude']) : 0;
                    
                    $locations[] = [
                        'key' => $loc['key'] ?? '',
                        'name' => $loc['name'] ?? '',
                        'latitude' => $lat,
                        'longitude' => $lng
                    ];
                }
            }
        } else {
            error_log('GeoLocation API error. HTTP: ' . $httpCode . ' Response: ' . $response);
        }
        
        echo json_encode([
            'success' => true, 
            'locations' => $locations,
            'debug' => ['http_code' => $httpCode, 'keyword' => $keyword, 'count' => count($locations)]
        ]);
        exit;
    }
    
    private function searchFacebookLocations(string $keyword, string $type = 'country'): array {
        $meta = \App\Models\Settings::getMetaApi();
        
        if (empty($meta['access_token'])) {
            return [];
        }
        
        $typeMap = [
            'country' => 'adge',
            'region' => 'adgeolocation',
            'olocationcity' => 'adgeolocation',
            'zip' => 'adgeolocation'
        ];
        
        $url = "https://graph.facebook.com/v25.0/search?" . http_build_query([
            'type' => $typeMap[$type] ?? 'adgeolocation',
            'q' => $keyword,
            'access_token' => $meta['access_token'],
            'limit' => 20
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['data'] ?? [];
        }
        
        return [];
    }
    
    public function getBusinessManagers(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No token provided']);
            exit;
        }
        
        $businessManagers = $this->getFacebookBusinesses($token);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'business_managers' => $businessManagers,
            'count' => count($businessManagers)
        ]);
        exit;
    }
    
    public function getInstagram(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        $fanPageId = $_GET['fanpage'] ?? '';
        
        header('Content-Type: application/json');
        
        if (empty($token) || empty($fanPageId)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters', 'instagram_pages' => []]);
            exit;
        }
        
        $instagramPages = $this->getInstagramFromFanPage($token, $fanPageId);
        
        echo json_encode([
            'success' => true, 
            'instagram_pages' => $instagramPages,
            'debug' => ['fanpage_id' => $fanPageId]
        ]);
        exit;
    }
    
    private function getInstagramFromFanPage(string $accessToken, string $fanPageId): array {
        $url = "https://graph.facebook.com/v25.0/" . $fanPageId . "?fields=instagram_business_account&access_token=" . urlencode($accessToken);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $instagramPages = [];
        
        if ($curlError) {
            error_log("Instagram API Error: " . $curlError);
            return $instagramPages;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['instagram_business_account']['id'])) {
                $igAccountId = $data['instagram_business_account']['id'];
                
                $detailsUrl = "https://graph.facebook.com/v25.0/" . $igAccountId . "?fields=id,name,username&access_token=" . urlencode($accessToken);
                
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $detailsUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
                $detailsResponse = curl_exec($ch2);
                $detailsHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                
                if ($detailsHttpCode === 200) {
                    $igData = json_decode($detailsResponse, true);
                    $instagramPages[] = [
                        'id' => $igData['id'],
                        'name' => $igData['name'] ?? $igData['username'] ?? 'Instagram',
                        'username' => $igData['username'] ?? ''
                    ];
                }
            }
        } else {
            error_log("Instagram API HTTP " . $httpCode . ": " . $response);
        }
        
        return $instagramPages;
    }
    
    public function getWhatsApp(): void {
        $user = $this->checkAuth(true);
        $token = $_GET['token'] ?? '';
        $fanPageId = $_GET['fanpage'] ?? '';
        
        header('Content-Type: application/json');
        
        if (empty($token) || empty($fanPageId)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters', 'whatsapp_accounts' => []]);
            exit;
        }
        
        $whatsappAccounts = $this->getWhatsAppFromFanPage($token, $fanPageId);
        
        echo json_encode([
            'success' => true, 
            'whatsapp_accounts' => $whatsappAccounts,
            'debug' => ['fanpage_id' => $fanPageId]
        ]);
        exit;
    }
    
    private function getWhatsAppFromFanPage(string $accessToken, string $fanPageId): array {
        // Get WhatsApp Business accounts from the Fan Page
        $url = "https://graph.facebook.com/v25.0/" . $fanPageId . "?fields=whatsapp_business_account&access_token=" . urlencode($accessToken);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $whatsappAccounts = [];
        
        if ($curlError) {
            error_log("WhatsApp API Error: " . $curlError);
            return $whatsappAccounts;
        }
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['whatsapp_business_account']['id'])) {
                $waAccountId = $data['whatsapp_business_account']['id'];
                
                // Get phone numbers: GET /{waba-id}/phone_numbers
                $phoneUrl = "https://graph.facebook.com/v25.0/" . $waAccountId . "/phone_numbers?" . http_build_query([
                    'access_token' => $accessToken
                ]);
                
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $phoneUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
                $phoneResponse = curl_exec($ch2);
                $phoneHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);
                
                if ($phoneHttpCode === 200) {
                    $phoneData = json_decode($phoneResponse, true);
                    $phones = $phoneData['data'] ?? [];
                    
                    foreach ($phones as $phone) {
                        $whatsappAccounts[] = [
                            'id' => $phone['id'] ?? $waAccountId,
                            'name' => $phone['verified_name'] ?? 'WhatsApp Business',
                            'phone' => $phone['phone_number'] ?? ''
                        ];
                    }
                }
                
                // If no phone numbers, get basic info
                if (empty($whatsappAccounts)) {
                    $detailsUrl = "https://graph.facebook.com/v25.0/" . $waAccountId . "?fields=id,name&access_token=" . urlencode($accessToken);
                    
                    $ch3 = curl_init();
                    curl_setopt($ch3, CURLOPT_URL, $detailsUrl);
                    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch3, CURLOPT_TIMEOUT, 15);
                    $detailsResponse = curl_exec($ch3);
                    $detailsHttpCode = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
                    curl_close($ch3);
                    
                    if ($detailsHttpCode === 200) {
                        $waData = json_decode($detailsResponse, true);
                        $whatsappAccounts[] = [
                            'id' => $waData['id'],
                            'name' => $waData['name'] ?? 'WhatsApp Business',
                            'phone' => ''
                        ];
                    }
                }
            }
        } else {
            error_log("WhatsApp API HTTP " . $httpCode . ": " . $response);
        }
        
        return $whatsappAccounts;
    }
}
