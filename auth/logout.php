<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php';

if (isset($_SESSION['user'])) {
    registrar_log('Cierre de Sesión', 'Autenticación', "El usuario {$_SESSION['user']['nombre']} cerró sesión.");
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header('Location: login.php?msg=logout_success');
exit;
