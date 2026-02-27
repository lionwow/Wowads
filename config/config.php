<?php
// Basic runtime configuration. These values are intended to be customized
// after deployment via FTP for a real environment.
define('SITE_TITLE', 'WowAds');
define('DB_HOST', 'localhost');
define('DB_NAME', 'u244794733_wowads');
define('DB_USER', 'u244794733_wowads');
define('DB_PASS', 'LIONwow*2027*');

// Configuración de correo SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@wowads.com');
define('SMTP_FROM_NAME', 'WowAds');

// Dynamic base URL (auto-detect)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$scriptDir = str_replace('\\', '/', $scriptDir);
define('BASE_URL', $scriptDir === '/' || $scriptDir === '\\' ? '/' : rtrim($scriptDir, '/') . '/');
define('ASSETS_URL', BASE_URL . 'assets');

?>
