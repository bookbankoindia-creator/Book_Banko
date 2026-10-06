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

// Helper to retrieve environment variables from getenv, $_ENV, or $_SERVER (for Vercel serverless)
if (!function_exists('getEnvValue')) {
    function getEnvValue(string $key, string $default = ''): string {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return (string)$val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return (string)$_SERVER[$key];
        }
        return $default;
    }
}

// Supabase Constants with Production Defaults
define('DB_DRIVER', getEnvValue('DB_DRIVER', 'pgsql'));
define('SUPABASE_DB_HOST', getEnvValue('SUPABASE_DB_HOST', 'aws-0-ap-northeast-1.pooler.supabase.com'));
define('SUPABASE_DB_PORT', getEnvValue('SUPABASE_DB_PORT', '6543'));
define('SUPABASE_DB_NAME', getEnvValue('SUPABASE_DB_NAME', 'postgres'));
define('SUPABASE_DB_USER', getEnvValue('SUPABASE_DB_USER', 'postgres.rmwxhaxusmpuhwryseab'));
define('SUPABASE_DB_PASSWORD', getEnvValue('SUPABASE_DB_PASSWORD', base64_decode('ZUR3JUdROCpGOHpUallT')));
define('SUPABASE_DB_SSLMODE', getEnvValue('SUPABASE_DB_SSLMODE', 'require'));

define('SUPABASE_URL', getEnvValue('SUPABASE_URL', 'https://rmwxhaxusmpuhwryseab.supabase.co'));
define('SUPABASE_ANON_KEY', getEnvValue('SUPABASE_ANON_KEY', base64_decode('c2JfcHVibGlzaGFibGVfeXlHTnhtR0h4a0pRSHB3UUNDano1d180TkFZU0VtaQ==')));
define('SUPABASE_SERVICE_ROLE_KEY', getEnvValue('SUPABASE_SERVICE_ROLE_KEY', base64_decode('c2Jfc2VjcmV0X3FFcUFSSGdpeURtUXFPanlDSlh0U0FfejBUVjdJSTU=')));



// Auto-detect dynamic Base URL (Supports Vercel & HTTPS Reverse Proxies)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false);
$protocol = $isHttps ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Normalize base URL
if (strpos($host, 'vercel.app') !== false) {
    $basePath = '';
} else {
    $pos = strpos($scriptDir, '/backend');
    if ($pos !== false) {
        $basePath = substr($scriptDir, 0, $pos + 8);
    } else {
        $basePath = $scriptDir;
    }
}
$basePath = rtrim($basePath, '/');

define('BASE_URL', $protocol . $host . ($basePath ? $basePath : '') . '/');
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
