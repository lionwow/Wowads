<?php
// Debug: see what's happening
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Front controller (simple MVC skeleton) for a cPanel-friendly PHP 8+ app
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/SuperAdminController.php';
require_once __DIR__ . '/app/controllers/ConnectionsController.php';
require_once __DIR__ . '/app/controllers/ClientAccountsController.php';
require_once __DIR__ . '/app/controllers/ImagesController.php';
require_once __DIR__ . '/app/controllers/CampaignController.php';
require_once __DIR__ . '/app/controllers/BrandDNAController.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/Agency.php';
require_once __DIR__ . '/app/models/SocialAccount.php';
require_once __DIR__ . '/app/models/Image.php';
require_once __DIR__ . '/app/models/Campaign.php';
require_once __DIR__ . '/app/models/BrandDNA.php';
require_once __DIR__ . '/app/models/Client.php';
require_once __DIR__ . '/app/models/Settings.php';
require_once __DIR__ . '/core/Session.php';

// Autoload simple approach for our app structure
spl_autoload_register(function($class) {
    // Get just the class name without namespace
    $className = $class;
    if (strpos($class, '\\') !== false) {
        $className = substr($class, strrpos($class, '\\') + 1);
    }
    
    // Convert namespace to path
    $classPath = str_replace('\\', '/', $class);
    $paths = [
        __DIR__ . '/app/controllers/' . $classPath . '.php',
        __DIR__ . '/app/controllers/' . $className . '.php',
        __DIR__ . '/app/models/' . $classPath . '.php',
        __DIR__ . '/app/models/' . $className . '.php',
        __DIR__ . '/core/' . $classPath . '.php',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) { require_once $p; return; }
    }
});

// Init session management
Session::start();

// Simple router based on path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
$scriptDir = trim($scriptDir, '/');

// Remove the script directory from the URI to get the clean path
if ($scriptDir && strpos($requestUri, $scriptDir) === 1) {
    $uri = substr($requestUri, strlen($scriptDir) + 1);
    $uri = trim(parse_url($uri, PHP_URL_PATH), '/');
} else {
    $uri = trim(parse_url($requestUri, PHP_URL_PATH), '/');
}

// Remove index.php from uri if present
$uri = str_replace('index.php', '', $uri);
$uri = trim($uri, '/');

// Skip router for static files
$staticExtensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'webp', 'mp4', 'webm'];
$uriParts = explode('.', $uri);
$extension = end($uriParts);
if (in_array(strtolower($extension), $staticExtensions)) {
    $file = __DIR__ . '/' . $uri;
    if (file_exists($file)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];
        header('Content-Type: ' . ($mimeTypes[strtolower($extension)] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// Home is the login page (redirect to dashboard if already logged in)
if ($uri === '' || $uri === 'index.php' || $uri === 'index') {
    if (Session::isLoggedIn()) {
        Session::redirect('/dashboard');
    }
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->loginPost();
    } else {
        $ctrl->loginForm();
    }
    exit;
}

if ($uri === 'auth/login') {
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->loginPost();
    } else {
        $ctrl->loginForm();
    }
    exit;
}

if ($uri === 'auth/register') {
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->registerPost();
    } else {
        $ctrl->registerForm();
    }
    exit;
}

if ($uri === 'auth/forgot-password') {
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->forgotPasswordPost();
    } else {
        $ctrl->forgotPasswordForm();
    }
    exit;
}

if ($uri === 'auth/reset-password') {
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->resetPasswordPost();
    } else {
        $ctrl->resetPasswordForm();
    }
    exit;
}

if ($uri === 'dashboard') {
    $ctrl = new \App\Controllers\DashboardController();
    $ctrl->index();
    exit;
}

// Connections routes
if ($uri === 'connections') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->index();
    exit;
}

if ($uri === 'connections/connect') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->connect();
    exit;
}

if ($uri === 'connections/callback') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->callback();
    exit;
}

if ($uri === 'connections/pages') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getPages();
    exit;
}

if ($uri === 'connections/adaccounts') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getAdAccounts();
    exit;
}

if ($uri === 'connections/pixels') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getPixels();
    exit;
}

if ($uri === 'connections/businessmanagers') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getBusinessManagers();
    exit;
}

if ($uri === 'connections/instagram') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getInstagram();
    exit;
}

if ($uri === 'connections/whatsapp') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getWhatsApp();
    exit;
}

if ($uri === 'connections/saved_audiences') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getSavedAudiences();
    exit;
}

if ($uri === 'connections/customaudiences') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getCustomAudiences();
    exit;
}

if ($uri === 'connections/interests') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getInterests();
    exit;
}

if ($uri === 'connections/locations') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getLocations();
    exit;
}

if ($uri === 'connections/geolocation') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->getGeoLocations();
    exit;
}

if ($uri === 'connections/disconnect') {
    $ctrl = new \App\Controllers\ConnectionsController();
    $ctrl->disconnect();
    exit;
}

// Client Accounts routes
if ($uri === 'client-accounts') {
    $ctrl = new \App\Controllers\ClientAccountsController();
    $ctrl->index();
    exit;
}

if ($uri === 'client-accounts/add') {
    $ctrl = new \App\Controllers\ClientAccountsController();
    $ctrl->add();
    exit;
}

// Images routes
if ($uri === 'images') {
    $ctrl = new \App\Controllers\ImagesController();
    $ctrl->index();
    exit;
}

if ($uri === 'images/delete') {
    $ctrl = new \App\Controllers\ImagesController();
    $ctrl->delete();
    exit;
}

// Brand DNA routes (moved before campaigns to avoid conflicts)
if ($uri === 'brand-dna') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->index();
    exit;
}

if ($uri === 'brand-dna/create') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->create();
    exit;
}

if ($uri === 'brand-dna/edit') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->edit();
    exit;
}

if ($uri === 'brand-dna/store') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->store();
    exit;
}

if ($uri === 'brand-dna/update') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->update();
    exit;
}

if ($uri === 'brand-dna/delete') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->delete();
    exit;
}

if ($uri === 'brand-dna/generate-briefing') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->generateBriefing();
    exit;
}

if ($uri === 'brand-dna/transcribe') {
    $ctrl = new \App\Controllers\BrandDNAController();
    $ctrl->transcribe();
    exit;
}

// Campaign routes
if ($uri === 'campaigns') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->index();
    exit;
}

if ($uri === 'campaigns/create') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->create();
    exit;
}

if ($uri === 'campaigns/store') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->store();
    exit;
}

if ($uri === 'campaigns/edit') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->edit();
    exit;
}

if ($uri === 'campaigns/update') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->update();
    exit;
}

if ($uri === 'campaigns/delete') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->delete();
    exit;
}

if ($uri === 'campaigns/duplicate') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->duplicate();
    exit;
}

if ($uri === 'campaigns/publish') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->publish();
    exit;
}

if ($uri === 'campaigns/json') {
    $ctrl = new \App\Controllers\CampaignController();
    $ctrl->json();
    exit;
}

if ($uri === 'client-accounts/delete') {
    $ctrl = new \App\Controllers\ClientAccountsController();
    $ctrl->delete();
    exit;
}

if ($uri === 'auth/logout') {
    Session::logout();
    Session::redirect('/');
}

// Admin login routes
if ($uri === 'admin') {
    $ctrl = new \App\Controllers\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ctrl->adminLoginPost();
    } else {
        $ctrl->adminLoginForm();
    }
    exit;
}

// Super Admin routes
if ($uri === 'superadmin') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->index();
    exit;
}

if ($uri === 'superadmin/users') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->users();
    exit;
}

if ($uri === 'superadmin/agencies') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->agencies();
    exit;
}

if ($uri === 'superadmin/settings') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->settings();
    exit;
}

if ($uri === 'superadmin/plans') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->plans();
    exit;
}

if ($uri === 'superadmin/reports') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->reports();
    exit;
}

if (preg_match('#^superadmin/user/(\d+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->userDetail();
    exit;
}

// Super Admin Actions - Users
if ($uri === 'superadmin/user/update') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->userUpdate();
    exit;
}

if ($uri === 'superadmin/user/suspend') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->userSuspend();
    exit;
}

if ($uri === 'superadmin/user/activate') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->userActivate();
    exit;
}

if ($uri === 'superadmin/user/delete') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->userDelete();
    exit;
}

// Super Admin Actions - Agencies
if ($uri === 'superadmin/agency/update') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->agencyUpdate();
    exit;
}

if ($uri === 'superadmin/agency/suspend') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->agencySuspend();
    exit;
}

if ($uri === 'superadmin/agency/activate') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->agencyActivate();
    exit;
}

if ($uri === 'superadmin/agency/delete') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->agencyDelete();
    exit;
}

// Settings routes
if ($uri === 'superadmin/settings-save') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->settingsSave();
    exit;
}

if ($uri === 'superadmin/settings-test') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->settingsTest();
    exit;
}

// Masquerade routes
if ($uri === 'superadmin/masquerade') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->masquerade();
    exit;
}

if ($uri === 'superadmin/stop-masquerade') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->stopMasquerade();
    exit;
}

// Campaign Templates
if ($uri === 'superadmin/templates') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplates();
    exit;
}

if ($uri === 'superadmin/templates/create') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateCreate();
    exit;
}

if ($uri === 'superadmin/templates/edit') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateEdit();
    exit;
}

if ($uri === 'superadmin/templates/store') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateStore();
    exit;
}

if ($uri === 'superadmin/templates/update') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateUpdate();
    exit;
}

if ($uri === 'superadmin/templates/delete') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateDelete();
    exit;
}

if ($uri === 'superadmin/templates/toggle') {
    $ctrl = new \App\Controllers\SuperAdminController();
    $ctrl->campaignTemplateToggle();
    exit;
}

// 404 fallback
http_response_code(404);
echo '404 Not Found - URI: ' . $uri;
