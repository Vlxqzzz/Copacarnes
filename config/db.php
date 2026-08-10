<?php
// ============================================================================
// CONFIGURACIÓN DE CONEXIÓN MULTI-ENTORNO ULTRA-RÁPIDA (RAILWAY / CLOUD / LARAGON)
// Copacarnes - Carnicería Boutique & Restaurante Asadero
// ============================================================================

$db_host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'mysql.railway.internal');
$db_port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$db_user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : 'oCGBdG0bJVJDbuqXhqcwBytTRGPxnHQD');
$db_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'railway');

$pdo = null;
$db_error_msg = '';

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 2, // Timeout ultra-rápido de 2s para evitar demoras
];

// Intento 1: Variables de entorno o red interna directa de Railway
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (Throwable $e1) {
    $db_error_msg = $e1->getMessage();
    
    // Intento 2: Red interna directa de Railway (mysql.railway.internal:3306)
    try {
        $dsn2 = "mysql:host=mysql.railway.internal;port=3306;dbname=railway;charset=utf8mb4";
        $pdo = new PDO($dsn2, 'root', 'oCGBdG0bJVJDbuqXhqcwBytTRGPxnHQD', $pdo_options);
    } catch (Throwable $e2) {
        $db_error_msg = $e2->getMessage();
        
        // Intento 3: Servidor local de Laragon
        try {
            $dsn3 = "mysql:host=localhost;port=3306;dbname=copacarnes_db;charset=utf8mb4";
            $pdo = new PDO($dsn3, 'root', '', $pdo_options);
        } catch (Throwable $e3) {
            // Intento 4: Proxy público de Railway (kodama.proxy.rlwy.net:31124)
            try {
                $dsn4 = "mysql:host=kodama.proxy.rlwy.net;port=31124;dbname=railway;charset=utf8mb4";
                $pdo = new PDO($dsn4, 'root', 'oCGBdG0bJVJDbuqXhqcwBytTRGPxnHQD', $pdo_options);
            } catch (Throwable $e4) {
                $pdo = null;
                $db_error_msg = "Error DB: " . $e1->getMessage();
            }
        }
    }
}

$GLOBALS['db_error_msg'] = $db_error_msg;
