<?php
/**
 * Unified Vercel Serverless Entrypoint & Router
 * Book Banko Backend & Admin Panel
 */
ob_start();

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = rawurldecode($requestUri);

// Handle static assets
if (preg_match('#^/assets/(.+)$#', $requestUri, $matches)) {
    $assetPath = __DIR__ . '/../backend/assets/' . $matches[1];
    if (is_file($assetPath)) {
        $ext = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($assetPath);
        exit;
    }
}

// Handle uploaded assets if present locally
if (preg_match('#^/uploads/(.+)$#', $requestUri, $matches)) {
    $uploadPath = __DIR__ . '/../backend/uploads/' . $matches[1];
    if (is_file($uploadPath)) {
        $ext = strtolower(pathinfo($uploadPath, PATHINFO_EXTENSION));
        $mimes = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        readfile($uploadPath);
        exit;
    }
}

// Strip leading/trailing slashes and normalize
$cleanPath = trim($requestUri, '/');

// Strip leading 'backend/' if present in URI
if (str_starts_with($cleanPath, 'backend/')) {
    $cleanPath = substr($cleanPath, 8);
}

// Root path -> backend/index.php
if (empty($cleanPath) || $cleanPath === 'index.php') {
    require __DIR__ . '/../backend/index.php';
    exit;
}

// Target file inside backend directory
$targetFile = __DIR__ . '/../backend/' . $cleanPath;

// If directory, check for index.php inside it
if (is_dir($targetFile)) {
    $targetFile = rtrim($targetFile, '/') . '/index.php';
} elseif (!file_exists($targetFile) && file_exists($targetFile . '.php')) {
    $targetFile .= '.php';
}

if (is_file($targetFile)) {
    // Set script filename and path info for accurate PHP runtime detection
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME'] = '/' . ltrim(str_replace('\\', '/', substr($targetFile, strlen(dirname(__DIR__)))), '/');
    
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// 404 Fallback
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
echo "<h2>404 Not Found</h2><p>The requested path '" . htmlspecialchars($requestUri) . "' was not found on Book Banko server.</p>";
exit;
