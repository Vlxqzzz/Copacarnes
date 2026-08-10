<?php
// Determinar la ruta relativa dinámica para enlaces y assets
if (!isset($base_url)) {
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $script_dir = rtrim($script_dir, '/');
    if (basename($script_dir) === 'auth' || basename($script_dir) === 'includes') {
        $base_url = '../';
    } else {
        $base_url = './';
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Copacarnes' : 'Copacarnes | Carniceria Restaurante'; ?></title>
    <meta name="description" content="Copacarnes ofrece los mejores cortes de carne fresca, atención tradicional de carnicería y servicio de restaurante parrilla al carbón.">
    
    <!-- Favicon Icon Oficial del Logo -->
    <link rel="icon" type="image/png" href="<?php echo $base_url; ?>images/favicon.png?v=<?php echo time(); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo $base_url; ?>favicon.ico?v=<?php echo time(); ?>">
    <link rel="apple-touch-icon" href="<?php echo $base_url; ?>images/favicon.png?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Montserrat:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Hojas de estilo modularizadas -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/global.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/footer.css">
    <?php if (isset($page_css) && !empty($page_css)): ?>
        <link rel="stylesheet" href="<?php echo $base_url . 'css/' . $page_css; ?>">
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? htmlspecialchars($body_class) : ''; ?>">

    <!-- Header & Barra de Navegación Fija -->
    <header class="main-header">
        <nav class="navbar">
            <a href="<?php echo $base_url; ?>index.php" class="logo">
                <img src="<?php echo $base_url; ?>images/logo.jpg?v=<?php echo time(); ?>" alt="Copacarnes Logo" class="logo-img">
                <span class="logo-text">COPA<span class="text-gold">CARNES</span></span>
            </a>

            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#inicio" class="nav-link">Inicio</a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#conocenos" class="nav-link">Conócenos</a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#sedes" class="nav-link">Sedes</a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#restaurante" class="nav-link">Restaurante</a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#productos" class="nav-link">Productos</a>
                    </li>
                    <li>
                        <a href="<?php echo $base_url; ?>index.php#contacto" class="nav-link btn-gold-link" style="color:var(--color-gold); font-weight:800;"><i class="fa-solid fa-cart-shopping"></i> Hacer Pedido</a>
                    </li>
                </ul>

                <div class="nav-auth">
                    <a href="<?php echo $base_url; ?>auth/login.php" class="btn btn-outline <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-user"></i> Iniciar sesión
                    </a>
                </div>
            </div>
        </nav>
    </header>
