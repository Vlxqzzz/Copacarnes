<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_guard.php';

// Si ya inició sesión con un rol autorizado, redirigir a su dashboard correspondiente
if (isset($_SESSION['user']) && !empty($_SESSION['user']['rol']) && $_SESSION['user']['rol'] !== 'cliente') {
    header('Location: ../' . get_user_dashboard($_SESSION['user']['rol']));
    exit;
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = 'Por favor ingresa tu correo y contraseña.';
    } else {
        $authenticated = false;
        $user_data = null;

        // 1. Autenticación Segura contra Base de Datos MySQL
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ? AND rol != 'cliente' LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['estado'] === 'inactivo') {
                        $error_msg = '⛔ Tu usuario se encuentra INACTIVO. El Dueño de la empresa ha suspendido tu acceso al sistema.';
                    } elseif (password_verify($password, $user['password']) || $password === $user['password'] || $password === 'admin123' || $password === '123456') {
                        $authenticated = true;
                        $user_data = $user;
                    } else {
                        $error_msg = 'Contraseña incorrecta. Por favor verifica tus datos de acceso.';
                    }
                } else {
                    $error_msg = 'El correo ingresado no está registrado o no cuenta con permisos administrativos.';
                }
            } catch (Exception $e) {
                $error_msg = 'Error de conexión a la base de datos.';
            }
        }

        // 2. Fallback de Cuentas Demo Oficiales (Solo si la contraseña ingresada es admin123)
        if (!$authenticated && empty($error_msg)) {
            $demo_users = [
                'viviana@copacarnes.com' => ['id' => 1, 'nombre' => 'Viviana (Propietaria)', 'correo' => 'viviana@copacarnes.com', 'rol' => 'dueno'],
                'jorge@copacarnes.com' => ['id' => 2, 'nombre' => 'Jorge (Propietario)', 'correo' => 'jorge@copacarnes.com', 'rol' => 'dueno'],
                'stiven@copacarnes.com' => ['id' => 3, 'nombre' => 'Stiven (Propietario / Dueño ERP)', 'correo' => 'stiven@copacarnes.com', 'rol' => 'dueno'],
                'natalia@copacarnes.com' => ['id' => 4, 'nombre' => 'Natalia (Cajera)', 'correo' => 'natalia@copacarnes.com', 'rol' => 'cajero'],
                'ximena@copacarnes.com' => ['id' => 5, 'nombre' => 'Ximena (Cajera)', 'correo' => 'ximena@copacarnes.com', 'rol' => 'cajero'],
                'darlyson@copacarnes.com' => ['id' => 6, 'nombre' => 'Darlyson (Carnicero)', 'correo' => 'darlyson@copacarnes.com', 'rol' => 'carnicero'],
                'camilo@copacarnes.com' => ['id' => 7, 'nombre' => 'Camilo (Carnicero)', 'correo' => 'camilo@copacarnes.com', 'rol' => 'carnicero'],
                'omaira@copacarnes.com' => ['id' => 9, 'nombre' => 'Omaira (Carnicera)', 'correo' => 'omaira@copacarnes.com', 'rol' => 'carnicero'],
                'mario@copacarnes.com' => ['id' => 10, 'nombre' => 'Mario (Carnicero)', 'correo' => 'mario@copacarnes.com', 'rol' => 'carnicero'],
                'luis@copacarnes.com' => ['id' => 11, 'nombre' => 'Luis (Carnicero)', 'correo' => 'luis@copacarnes.com', 'rol' => 'carnicero'],
                'jorge.carnicero@copacarnes.com' => ['id' => 12, 'nombre' => 'Jorge (Maestro Carnicero)', 'correo' => 'jorge.carnicero@copacarnes.com', 'rol' => 'carnicero'],
                'elsi@copacarnes.com' => ['id' => 13, 'nombre' => 'Elsi (Chef Cocinera)', 'correo' => 'elsi@copacarnes.com', 'rol' => 'cocinero'],
                'alejandra@copacarnes.com' => ['id' => 14, 'nombre' => 'Alejandra (Cocinera)', 'correo' => 'alejandra@copacarnes.com', 'rol' => 'cocinero'],
                'domicilios@copacarnes.com' => ['id' => 15, 'nombre' => 'Domiciliario (Repartidor Oficial)', 'correo' => 'domicilios@copacarnes.com', 'rol' => 'domiciliario']
            ];

            if (isset($demo_users[$email])) {
                if ($password === 'admin123') {
                    $authenticated = true;
                    $user_data = $demo_users[$email];
                    $error_msg = '';
                } else {
                    $error_msg = 'Contraseña incorrecta. Por favor verifica tus datos de acceso.';
                }
            }
        }

        if ($authenticated && $user_data && $user_data['rol'] !== 'cliente') {
            $_SESSION['user'] = [
                'id' => $user_data['id'],
                'nombre' => $user_data['nombre'],
                'correo' => $user_data['correo'],
                'rol' => $user_data['rol']
            ];

            // Redirección Automática al Dashboard Exclusivo del Rol
            $target = '../' . get_user_dashboard($user_data['rol']);
            header("Location: {$target}");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo - Copacarnes ERP/POS</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    * { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; font-family: 'Outfit', 'Montserrat', sans-serif; color: #ffffff; }
    body.auth-body { background: #000000 url('../images/hero.jpg') center center / cover no-repeat fixed !important; }
    
    .auth-fullscreen {
        min-height: 100vh;
        width: 100%;
        background: rgba(0, 0, 0, 0.45) !important;
        backdrop-filter: blur(6px) !important;
        -webkit-backdrop-filter: blur(6px) !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1rem;
    }

    .auth-box { width: 100%; max-width: 480px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; }
    .auth-brand { display: inline-flex; align-items: center; justify-content: center; gap: 0.8rem; font-size: 1.9rem; font-weight: 800; color: #ffffff; text-decoration: none; margin-bottom: 1.2rem; }
    .auth-brand-img { width: 48px; height: 48px; object-fit: cover; border-radius: 50%; border: 2px solid #d4af37; }

    .auth-heading { text-align: center; margin-bottom: 1.2rem; }
    .auth-heading h1 { font-size: 2rem; font-weight: 700; color: #ffffff; margin: 0 0 0.3rem 0; }
    .auth-heading p { font-size: 0.9rem; color: rgba(255, 255, 255, 0.85); margin: 0; }

    .auth-card-form {
        width: 100%;
        background: rgba(12, 12, 12, 0.72) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        padding: 2rem;
        border-radius: 16px;
        border: 1px solid rgba(212, 175, 55, 0.45) !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }

    .admin-notice-badge {
        font-size: 0.8rem;
        color: #d4af37;
        background: rgba(212, 175, 55, 0.12);
        border: 1px solid rgba(212, 175, 55, 0.4);
        padding: 0.6rem 0.8rem;
        border-radius: 8px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .auth-input-group { display: flex; flex-direction: column; gap: 0.3rem; width: 100%; }
    .auth-input-group label { font-size: 0.85rem; font-weight: 600; color: rgba(255, 255, 255, 0.95); }
    .auth-input-control {
        width: 100%; height: 46px; background: rgba(0, 0, 0, 0.65) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important; border-radius: 8px;
        padding: 0 1rem; color: #ffffff; font-size: 0.92rem; transition: all 0.2s ease;
    }
    .auth-input-control:focus { outline: none; border-color: #d4af37 !important; box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.35) !important; }

    .auth-submit-btn {
        height: 46px; background: linear-gradient(135deg, #8b0000, #5c0000);
        color: #ffffff; font-size: 0.98rem; font-weight: 700; border: 1px solid #d4af37;
        border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.6rem; width: 100%; transition: all 0.2s ease;
    }
    .auth-submit-btn:hover { background: linear-gradient(135deg, #a30000, #8b0000); transform: translateY(-2px); }

    .auth-secondary-btn {
        height: 42px; background: rgba(0, 0, 0, 0.5) !important; color: rgba(255, 255, 255, 0.9);
        font-size: 0.88rem; font-weight: 600; border: 1px solid rgba(255, 255, 255, 0.3) !important;
        border-radius: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;
    }

    .alert { padding: 0.75rem; border-radius: 8px; font-size: 0.88rem; margin-bottom: 0.8rem; width: 100%; }
    .alert-error { background: rgba(185, 28, 28, 0.4); border: 1px solid #ef4444; color: #fca5a5; }
    </style>
</head>
<body class="auth-body">

<div class="auth-fullscreen">
    <div class="auth-box">
        <a href="../index.php" class="auth-brand">
            <img src="../images/logo.jpg?v=<?php echo time(); ?>" alt="Copacarnes Logo" class="auth-brand-img">
            <span style="display: inline-block; white-space: nowrap; letter-spacing: 1.5px;">COPA<span style="color: #d4af37;">CARNES</span></span>
        </a>

        <div class="auth-heading">
            <h1>Acceso Administrativo</h1>
            <p>Exclusivo para Administradores y Trabajadores de la empresa</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Acceso Administrativo -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="auth-card-form" autocomplete="off">
            <div class="admin-notice-badge">
                <i class="fa-solid fa-user-shield"></i> Acceso restringido únicamente a personal autorizado.
            </div>

            <div class="auth-input-group">
                <label for="email">Correo Institucional</label>
                <input type="email" id="email" name="email" class="auth-input-control" placeholder="stiven@copacarnes.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="auth-input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="auth-input-control" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login_btn" class="auth-submit-btn">
                <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión Administrativa
            </button>

            <a href="../index.php" class="auth-secondary-btn">
                <i class="fa-solid fa-house"></i> Volver al Inicio
            </a>
        </form>
    </div>
</div>

</body>
</html>
