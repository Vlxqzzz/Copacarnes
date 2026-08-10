<?php
// ============================================================================
// CONTROLADOR DE SEGURIDAD, AUTENTICACIÓN Y ROLES (RBAC) - COPACARNES
// Acceso Exclusivo para Personal Autorizado y Administradores de la Empresa
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Re-validar rol y permisos en tiempo real desde la Base de Datos MySQL
 */
if (isset($_SESSION['user']['id']) && isset($pdo) && $pdo) {
    try {
        $stmt_reval = $pdo->prepare("SELECT id, nombre, correo, rol, sede_asignada, estado FROM usuarios WHERE id = ? LIMIT 1");
        $stmt_reval->execute([$_SESSION['user']['id']]);
        $u_fresh = $stmt_reval->fetch();

        if ($u_fresh) {
            if ($u_fresh['estado'] === 'inactivo') {
                unset($_SESSION['user']);
                $script_p = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
                $in_adm = (strpos($script_p, '/admin/') !== false);
                $rel_login = $in_adm ? '../auth/login.php' : 'auth/login.php';
                header("Location: {$rel_login}?error=inactive");
                exit;
            }
            // Actualizar variables de sesión con los últimos permisos y datos de la BD
            $_SESSION['user']['nombre'] = $u_fresh['nombre'];
            $_SESSION['user']['correo'] = $u_fresh['correo'];
            $_SESSION['user']['rol'] = $u_fresh['rol'];
            $_SESSION['user']['sede_asignada'] = $u_fresh['sede_asignada'];
        }
    } catch (Exception $e) {}
}

/**
 * Obtener la ruta del dashboard correspondiente según el rol del usuario (6 Roles Internos)
 */
function get_user_dashboard($rol) {
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $in_admin_dir = (strpos($script_path, '/admin/') !== false);
    $prefix = $in_admin_dir ? '' : 'admin/';

    switch ($rol) {
        case 'dueno':
        case 'admin':
            return $prefix . 'dashboard-dueno.php';
        case 'cajero':
            return $prefix . 'dashboard-cajero.php';
        case 'carnicero':
            return $prefix . 'dashboard-carnicero.php';
        case 'cocinero':
            return $prefix . 'dashboard-cocinero.php';
        case 'domiciliario':
            return $prefix . 'dashboard-domiciliario.php';
        default:
            return $prefix . 'dashboard-dueno.php';
    }
}

/**
 * Proteger una página exigiendo que el usuario haya iniciado sesión
 * y pertenezca a uno de los 6 roles administrativos autorizados.
 */
function require_auth($allowed_roles = []) {
    if (!isset($_SESSION['user']) || empty($_SESSION['user']['id']) || ($_SESSION['user']['rol'] ?? '') === 'cliente') {
        $script_p = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $in_adm = (strpos($script_p, '/admin/') !== false);
        $rel_login = $in_adm ? '../auth/login.php' : 'auth/login.php';
        header("Location: {$rel_login}?error=no_authorized");
        exit;
    }

    $current_role = $_SESSION['user']['rol'] ?? '';

    if (!empty($allowed_roles) && !in_array($current_role, $allowed_roles)) {
        $redirect_target = get_user_dashboard($current_role);
        header("Location: {$redirect_target}?error=no_permission");
        exit;
    }
}

/**
 * Función vacía de compatibilidad (Logs desactivados)
 */
function registrar_log($accion, $modulo, $detalles = '') {
    return true;
}
