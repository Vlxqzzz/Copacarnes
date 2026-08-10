<?php
// ============================================================================
// CONFIGURACIÓN DE CONEXIÓN A LA BASE DE DATOS MYSQL / PHPMYADMIN
// Copacarnes - Carnicería Boutique & Restaurante Asadero
// ============================================================================

$db_host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$db_port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$db_user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
$db_name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'copacarnes_db');

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Si la base de datos aún no se ha creado o falla la conexión, PDO queda en null
    $pdo = null;
}
