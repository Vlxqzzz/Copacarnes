<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth_guard.php';

// Si se requiere un rol especifico para la página, se valida antes de cargar
if (isset($required_roles) && is_array($required_roles)) {
    require_auth($required_roles);
} else {
    require_auth();
}

function get_avatar_url($avatar) {
    if (empty($avatar)) {
        return '../images/avatar-default.png';
    }
    if (strpos($avatar, 'http://') === 0 || strpos($avatar, 'https://') === 0) {
        return $avatar;
    }
    if (strpos($avatar, '../') === 0) {
        return $avatar;
    }
    if (strpos($avatar, '/') === 0) {
        return '..' . $avatar;
    }
    return '../' . ltrim($avatar, '/');
}

$user = $_SESSION['user'] ?? [
    'nombre' => 'Propietario Copacarnes',
    'rol' => 'dueno',
    'correo' => 'dueno@copacarnes.com',
    'avatar' => 'images/avatar-default.png'
];

if (isset($_SESSION['user']['id']) && $pdo) {
    try {
        $stmt_u = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt_u->execute([$_SESSION['user']['id']]);
        $fresh_user = $stmt_u->fetch();
        if ($fresh_user) {
            $_SESSION['user'] = $fresh_user;
            $user = $fresh_user;
        }
    } catch (Exception $e) {}
}

$role_labels = [
    'dueno' => ['label' => 'Propietario(a)', 'color' => '#d4af37', 'icon' => 'fa-crown'],
    'admin' => ['label' => 'Propietario(a)', 'color' => '#d4af37', 'icon' => 'fa-crown'],
    'cajero' => ['label' => 'Cajera', 'color' => '#2a9d8f', 'icon' => 'fa-headset'],
    'carnicero' => ['label' => 'Carnicero', 'color' => '#e76f51', 'icon' => 'fa-drumstick-bite'],
    'cocinero' => ['label' => 'Cocinera', 'color' => '#f4a261', 'icon' => 'fa-utensils'],
    'domiciliario' => ['label' => 'Domiciliario', 'color' => '#457b9d', 'icon' => 'fa-motorcycle']
];

$user_role_info = $role_labels[$user['rol']] ?? $role_labels['dueno'];
$page_title_text = $page_title ?? 'Dashboard Administrativo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_text); ?> - Copacarnes ERP</title>
    
    <!-- Favicon Icon Oficial del Logo -->
    <link rel="icon" type="image/png" href="../images/favicon.png?v=<?php echo time(); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico?v=<?php echo time(); ?>">
    <link rel="apple-touch-icon" href="../images/favicon.png?v=<?php echo time(); ?>">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js para Gráficas Interactivas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    :root {
        --bg-main: #060913;
        --bg-card: rgba(13, 20, 36, 0.85);
        --bg-sidebar: #090e1d;
        --border-color: rgba(212, 175, 55, 0.25);
        --gold: #d4af37;
        --crimson: #8b0000;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
    }

    * { box-sizing: border-box; }
    body {
        margin: 0; padding: 0;
        background-color: var(--bg-main);
        color: var(--text-main);
        font-family: 'Outfit', 'Montserrat', sans-serif;
        display: flex;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Sidebar Colapsable inspirada en Stripe & Linear */
    .admin-sidebar {
        width: 260px;
        background: var(--bg-sidebar);
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        position: fixed;
        top: 0; left: 0; bottom: 0;
        z-index: 1000;
    }

    .sidebar-header {
        padding: 1.5rem 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        border-bottom: 1px solid var(--border-color);
    }
    .sidebar-logo-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--gold); }
    .sidebar-brand-name { font-weight: 800; font-size: 1.25rem; letter-spacing: 1px; color: #fff; }

    .sidebar-menu {
        flex: 1;
        padding: 1.2rem 0.8rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .menu-heading {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: var(--text-muted);
        margin: 1.2rem 0.6rem 0.4rem;
        font-weight: 700;
    }

    .menu-item {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        padding: 0.75rem 1rem;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 10px;
        transition: all 0.2s ease;
    }

    .menu-item:hover, .menu-item.active {
        color: #ffffff;
        background: rgba(212, 175, 55, 0.15);
        border-left: 3px solid var(--gold);
    }

    .menu-item i { font-size: 1.1rem; width: 22px; text-align: center; color: var(--gold); }

    .sidebar-footer {
        padding: 1rem;
        border-top: 1px solid var(--border-color);
        background: rgba(0,0,0,0.3);
    }

    /* Área Principal de Contenido */
    .admin-main {
        margin-left: 260px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    /* Navbar Superior */
    .admin-topbar {
        height: 70px;
        background: rgba(9, 14, 29, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        position: sticky;
        top: 0;
        z-index: 900;
    }

    .topbar-left { display: flex; align-items: center; gap: 1rem; }
    .breadcrumbs { font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; }
    .breadcrumbs a { color: var(--gold); text-decoration: none; }

    .topbar-right { display: flex; align-items: center; gap: 1.5rem; }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.9rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--gold);
        color: var(--gold);
    }

    .user-profile-btn {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        color: #fff;
        text-decoration: none;
        background: rgba(255,255,255,0.05);
        padding: 0.4rem 0.8rem;
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.15);
    }

    .btn-logout {
        color: #ef4444;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    /* Contenido del Dashboard */
    .dashboard-container {
        padding: 2rem;
        flex: 1;
    }

    /* Estilos KPI Cards (Stripe / Vercel style) */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 1.2rem;
        margin-bottom: 2rem;
    }

    .kpi-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.4rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        position: relative;
        overflow: hidden;
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: var(--gold);
    }

    .kpi-title { font-size: 0.82rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 0.5rem; }
    .kpi-value { font-size: 1.8rem; font-weight: 800; color: #ffffff; }
    .kpi-sub { font-size: 0.78rem; color: #10b981; margin-top: 0.4rem; display: flex; align-items: center; gap: 0.3rem; }

    /* Tablas Avanzadas y Adaptabilidad */
    .data-table-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-header-tools {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.2rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .search-input {
        background: rgba(0,0,0,0.5);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0.55rem 1rem;
        color: #fff;
        font-size: 0.88rem;
        width: 250px;
    }

    .export-btns { display: flex; gap: 0.5rem; }
    .btn-export {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        padding: 0.45rem 0.8rem;
        font-size: 0.8rem;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
    }
    .btn-export:hover { background: rgba(212,175,55,0.2); border-color: var(--gold); }

    table.custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.88rem;
        min-width: 600px;
    }

    table.custom-table th {
        background: rgba(0,0,0,0.4);
        color: var(--gold);
        padding: 0.9rem;
        border-bottom: 1px solid var(--border-color);
        font-weight: 700;
        white-space: nowrap;
    }

    table.custom-table td {
        padding: 0.9rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: #e2e8f0;
    }

    table.custom-table tr:hover { background: rgba(255,255,255,0.03); }

    .status-pill {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-pill.success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
    .status-pill.warning { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid #f59e0b; }
    .status-pill.danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid #ef4444; }

    /* Botón Hamburguesa & Overlay Móvil */
    .sidebar-toggle-btn {
        display: none;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid var(--border-color);
        color: #fff;
        width: 42px;
        height: 42px;
        border-radius: 8px;
        font-size: 1.2rem;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .sidebar-toggle-btn:hover {
        background: rgba(212, 175, 55, 0.2);
        color: var(--gold);
        border-color: var(--gold);
    }

    .sidebar-backdrop {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(4px);
        z-index: 998;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-backdrop.active {
        display: block;
        opacity: 1;
    }

    /* Media Queries de Adaptabilidad para Admin Dashboards */
    @media (max-width: 1024px) {
        .admin-sidebar {
            left: -280px;
            box-shadow: 10px 0 30px rgba(0,0,0,0.8);
        }
        .admin-sidebar.active {
            left: 0;
        }
        .admin-main {
            margin-left: 0;
            width: 100%;
        }
        .sidebar-toggle-btn {
            display: flex;
        }
        .admin-topbar {
            padding: 0 1.2rem;
        }
        .dashboard-container {
            padding: 1.25rem 1rem;
        }
    }

    @media (max-width: 767px) {
        .topbar-right {
            gap: 0.6rem;
        }
        .role-badge {
            display: none;
        }
        .user-profile-btn span {
            display: none;
        }
        .user-profile-btn {
            padding: 0.3rem;
            border-radius: 50%;
        }
        .kpi-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .data-table-card {
            padding: 1rem 0.8rem;
            border-radius: 10px;
        }
        .table-header-tools {
            flex-direction: column;
            align-items: stretch;
        }
        .search-input {
            width: 100% !important;
        }
        .export-btns {
            width: 100%;
            justify-content: space-between;
        }
        .btn-export {
            flex: 1;
            justify-content: center;
        }

        /* Modales Responsivos */
        #modalDetalleSolicitud > div,
        #modalDetalleDomicilio > div,
        #modalNuevaComanda > div,
        .modal-content-custom {
            max-height: 90vh !important;
            overflow-y: auto !important;
            padding: 1rem !important;
            margin: 0 0.5rem !important;
        }
    }

    @media (max-width: 480px) {
        .admin-topbar {
            height: 60px;
        }
        .breadcrumbs {
            font-size: 0.78rem;
        }
    }
    </style>
</head>
<body>

<div id="sidebarBackdrop" class="sidebar-backdrop"></div>

<!-- Sidebar de Navegación del Sistema (6 Roles del Personal) -->
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <img src="../images/logo.jpg?v=<?php echo time(); ?>" alt="Copacarnes Logo" class="sidebar-logo-img">
        <div>
            <div class="sidebar-brand-name">COPA<span style="color: var(--gold);">CARNES</span></div>
        </div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-heading">DASHBOARDS DEL PERSONAL</div>
        
        <a href="dashboard-dueno.php" class="menu-item <?php echo ($active_menu ?? '') === 'dueno' || ($active_menu ?? '') === 'admin' ? 'active' : ''; ?>">
            <i class="fa-solid fa-crown"></i> Dueño
        </a>
        <a href="dashboard-cajero.php" class="menu-item <?php echo ($active_menu ?? '') === 'cajero' ? 'active' : ''; ?>">
            <i class="fa-solid fa-headset"></i> Central de Pedidos
        </a>
        <a href="dashboard-carnicero.php" class="menu-item <?php echo ($active_menu ?? '') === 'carnicero' ? 'active' : ''; ?>">
            <i class="fa-solid fa-drumstick-bite"></i> Carnicería
        </a>
        <a href="dashboard-cocinero.php" class="menu-item <?php echo ($active_menu ?? '') === 'cocinero' ? 'active' : ''; ?>">
            <i class="fa-solid fa-utensils"></i> Restaurante
        </a>
        <a href="dashboard-domiciliario.php" class="menu-item <?php echo ($active_menu ?? '') === 'domiciliario' ? 'active' : ''; ?>">
            <i class="fa-solid fa-motorcycle"></i> Domicilios
        </a>

        <?php if (in_array($user['rol'] ?? '', ['dueno', 'admin', 'cajero'])): ?>
            <div class="menu-heading">MÓDULOS EMPRESARIALES</div>
            <a href="nube-empresarial.php" class="menu-item <?php echo ($active_menu ?? '') === 'nube' ? 'active' : ''; ?>">
                <i class="fa-solid fa-cloud"></i> Nube Empresarial
            </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; font-weight: 600;">
            Copacarnes S.A.S
        </div>
    </div>
</aside>

<!-- Principal Contenedor -->
<div class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" type="button" class="sidebar-toggle-btn" title="Abrir Menú de Navegación">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="breadcrumbs">
                <a href="#"><i class="fa-solid fa-gauge"></i> Dashboard</a> &rsaquo;
                <span><?php echo htmlspecialchars($page_title_text); ?></span>
            </div>
        </div>

        <div class="topbar-right">
            <div class="role-badge">
                <i class="fa-solid <?php echo $user_role_info['icon']; ?>"></i>
                <?php echo htmlspecialchars($user_role_info['label']); ?>
            </div>

            <div class="user-profile-btn" style="display:flex; align-items:center; gap:0.5rem;">
                <img src="<?php echo htmlspecialchars(get_avatar_url($user['avatar'] ?? '')); ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Foto">
                <span style="font-size: 0.85rem; font-weight: 600; color:#fff;"><?php echo htmlspecialchars($user['nombre']); ?></span>
            </div>

            <a href="../auth/logout.php" class="btn-logout">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </div>

    <div class="dashboard-container">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            backdrop.classList.toggle('active');
        });

        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('active');
            backdrop.classList.remove('active');
        });
    }
});
</script>
