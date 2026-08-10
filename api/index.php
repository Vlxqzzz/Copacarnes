<?php
// ============================================================================
// VERCEL PHP SERVERLESS ROUTER & ENTRYPOINT
// Copacarnes - Carnicería Boutique & Restaurante Asadero
// ============================================================================

// Obtener la ruta solicitada por el navegador
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Normalizar la ruta raíz
if ($uri === '' || $uri === '/') {
    require_once __DIR__ . '/../index.php';
    exit;
}

// Ruta física en el proyecto
$file = __DIR__ . '/..' . $uri;

// Si es un directorio, buscar index.php dentro del directorio
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}

// Si el archivo PHP existe, ejecutarlo directamente
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    require_once $file;
    exit;
}

// Si no coincide con un archivo PHP específico, cargar index.php principal
require_once __DIR__ . '/../index.php';
