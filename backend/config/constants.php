<?php
/**
 * Application Constants and Paths
 * Book Banko Backend & Admin Panel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Default Timezone
date_default_timezone_set('Asia/Kolkata');

// App Info
define('APP_NAME', 'Book Banko');
define('APP_TAGLINE', 'Educational Resources & PDF Reader');
define('APP_VERSION', '1.0.0');

// Base Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('MODULES_PATH', ROOT_PATH . 'modules/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('PDF_UPLOADS_PATH', UPLOADS_PATH . 'pdfs/');
define('ICON_UPLOADS_PATH', UPLOADS_PATH . 'icons/');
define('BANNER_UPLOADS_PATH', UPLOADS_PATH . 'banners/');
define('PRODUCTS_UPLOADS_PATH', UPLOADS_PATH . 'products/');

// Ensure upload directories exist
if (!is_dir(PDF_UPLOADS_PATH)) {
    @mkdir(PDF_UPLOADS_PATH, 0777, true);
}
if (!is_dir(ICON_UPLOADS_PATH)) {
    @mkdir(ICON_UPLOADS_PATH, 0777, true);
}
if (!is_dir(BANNER_UPLOADS_PATH)) {
    @mkdir(BANNER_UPLOADS_PATH, 0777, true);
}
if (!is_dir(PRODUCTS_UPLOADS_PATH)) {
    @mkdir(PRODUCTS_UPLOADS_PATH, 0777, true);
}

// Simple .env Loader
$envFile = ROOT_PATH . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($envKey, $envVal) = explode('=', $line, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            if (!isset($_ENV[$envKey])) {
                $_ENV[$envKey] = $envVal;
                putenv("{$envKey}={$envVal}");
            }
        }
    }
}

// Supabase Constants
define('DB_DRIVER', getenv('DB_DRIVER') ?: ($_ENV['DB_DRIVER'] ?? 'pgsql'));
define('SUPABASE_DB_HOST', getenv('SUPABASE_DB_HOST') ?: ($_ENV['SUPABASE_DB_HOST'] ?? ''));
define('SUPABASE_DB_PORT', getenv('SUPABASE_DB_PORT') ?: ($_ENV['SUPABASE_DB_PORT'] ?? '5432'));
define('SUPABASE_DB_NAME', getenv('SUPABASE_DB_NAME') ?: ($_ENV['SUPABASE_DB_NAME'] ?? 'postgres'));
define('SUPABASE_DB_USER', getenv('SUPABASE_DB_USER') ?: ($_ENV['SUPABASE_DB_USER'] ?? 'postgres'));
define('SUPABASE_DB_PASSWORD', getenv('SUPABASE_DB_PASSWORD') ?: ($_ENV['SUPABASE_DB_PASSWORD'] ?? ''));
define('SUPABASE_DB_SSLMODE', getenv('SUPABASE_DB_SSLMODE') ?: ($_ENV['SUPABASE_DB_SSLMODE'] ?? 'require'));

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? ''));
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: ($_ENV['SUPABASE_ANON_KEY'] ?? ''));
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ($_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? ''));

// Auto-detect dynamic Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Normalize base URL pointing to /backend/ directory
$pos = strpos($scriptDir, '/backend');
if ($pos !== false) {
    $basePath = substr($scriptDir, 0, $pos + 8);
} else {
    $basePath = $scriptDir;
}
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . $host . $basePath . '/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('PDF_UPLOADS_URL', UPLOADS_URL . 'pdfs/');
define('ICON_UPLOADS_URL', UPLOADS_URL . 'icons/');
define('BANNER_UPLOADS_URL', UPLOADS_URL . 'banners/');
define('PRODUCTS_UPLOADS_URL', UPLOADS_URL . 'products/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Admin Brand Colors matching Flutter Theme
define('COLOR_PRIMARY', '#0061A4');
define('COLOR_PRIMARY_DARK', '#004785');
define('COLOR_PRIMARY_BLUE', '#2196F3');
define('COLOR_DARK_NAVY', '#0B3D91');
define('COLOR_CANVAS_BG', '#F7FAFF');
define('COLOR_CONTAINER_BLUE', '#EAF6FF');
define('COLOR_TEXT_PRIMARY', '#172033');
define('COLOR_TEXT_SECONDARY', '#64748B');
