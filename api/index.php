<?php
// ============================================================================
// VERCEL PHP SERVERLESS ROUTER & ENTRYPOINT
// Copacarnes - Carnicería Boutique & Restaurante Asadero
// ============================================================================

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '' || $uri === '/') {
    require_once __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/..' . $uri;

if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}

if (file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        require_once $file;
        exit;
    }

    $mimes = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf'
    ];

    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($file);
        exit;
    }
}

require_once __DIR__ . '/../index.php';
