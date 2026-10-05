<?php
/**
 * Vercel Serverless Function Router
 * Book Banko
 */

// Route static assets if requested
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/backend/(.*)$#', $uri, $matches)) {
    $targetFile = __DIR__ . '/../backend/' . $matches[1];
    if (is_file($targetFile)) {
        $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
        if ($ext === 'php') {
            require $targetFile;
            exit;
        } else {
            $mimeTypes = [
                'css' => 'text/css',
                'js'  => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'woff2' => 'font/woff2',
                'woff' => 'font/woff',
                'ttf' => 'font/ttf',
            ];
            $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
            header("Content-Type: {$contentType}");
            readfile($targetFile);
            exit;
        }
    }
}

if (preg_match('#^/assets/(.*)$#', $uri, $matches)) {
    $targetFile = __DIR__ . '/../backend/assets/' . $matches[1];
    if (is_file($targetFile)) {
        $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
        ];
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
        header("Content-Type: {$contentType}");
        readfile($targetFile);
        exit;
    }
}

// Forward to backend router
require_once __DIR__ . '/../backend/index.php';
