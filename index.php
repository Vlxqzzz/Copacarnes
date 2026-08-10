<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_guard.php';

// PROCESAR FORMULARIO DE CONTACTO / RESERVAS (PATRÓN POST-REDIRECT-GET ANTI-REPRODUCCIÓN Y DUPLICACIÓN AL REFRESCAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['email'] ?? ($_POST['correo'] ?? ''));
    $tipo = trim($_POST['sede'] ?? 'Sede Principal - Solicitud General');
    $metodo = $_POST['metodo_entrega'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $detalles = trim($_POST['mensaje'] ?? ($_POST['detalles'] ?? ''));
    $carnicero_id = intval($_POST['carnicero_id'] ?? 0);
    $carnicero_nombre = trim($_POST['carnicero_nombre'] ?? 'Carnicero Disponible');

    if ($metodo === 'domicilio' && !empty($direccion)) {
        $detalles .= "\n[Entrega a Domicilio en: " . $direccion . "]";
    }

    if (!empty($carnicero_nombre) && $carnicero_nombre !== 'Carnicero Disponible') {
        $detalles .= "\n[Carnicero Seleccionado: " . $carnicero_nombre . "]";
    }

    // Fallbacks para asegurar que la solicitud nunca se pierda por un campo faltante
    if (empty($nombre)) {
        $nombre = !empty($correo) ? strtok($correo, '@') : 'Cliente Web';
    }
    if (empty($telefono)) {
        $telefono = 'Sin Teléfono';
    }
    if (empty($detalles)) {
        $detalles = 'Sin especificaciones adicionales.';
    }

    if ($pdo) {
        try {
            // Anti-duplicación: Solo bloquea si nombre, teléfono Y detalles son EXACTAMENTE iguales en menos de 30 segundos
            $stmt_dup = $pdo->prepare("SELECT id FROM solicitudes_contacto WHERE nombre = ? AND telefono = ? AND detalles = ? AND fecha_hora >= NOW() - INTERVAL 30 SECOND LIMIT 1");
            $stmt_dup->execute([$nombre, $telefono, $detalles]);
            $is_duplicate = $stmt_dup->fetch();

            if (!$is_duplicate) {
                $stmt = $pdo->prepare("INSERT INTO solicitudes_contacto (nombre, telefono, correo, sede_tipo, detalles, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
                $stmt->execute([$nombre, $telefono, $correo, $tipo, $detalles]);

                if (function_exists('registrar_log')) {
                    registrar_log('Nueva Solicitud Web', 'Contacto / Reservas', "Solicitud de {$nombre} ({$tipo}).");
                }
            }
        } catch (Exception $e) {
            error_log('Error al registrar solicitud en index.php: ' . $e->getMessage());
        }
    }

    // Redireccionar con parámetro GET (PRG) para eliminar el re-envío de formulario en F5
    header("Location: index.php?enviado=1&cliente=" . urlencode($nombre) . "#contacto");
    exit();
}

$page_title = "Copacarnes | Carniceria Restaurante";
$page_css = "home.css";
include __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero / Portada Principal - Enfocada 90% en Carnicería Boutique -->
<section id="inicio" class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-container">
        <div class="hero-content">
            <h1>Carnes Cuidadosamente <span class="text-gold">Seleccionadas</span></h1>
            <p>
                Bienvenidos a <strong class="text-gold">Copacarnes</strong>. Somos tu <strong>carnicería especializada de tradición</strong>. Te ofrecemos desposte diario con la máxima frescura y cortes porcionados exactamente a tu gusto (Res, Cerdo, Pollo y Embutidos) para tu hogar o asados familiares. Y si deseas probarlos preparados al instante, contamos con servicio de restaurante parrilla al carbón.
            </p>
            <div class="hero-buttons">
                <a href="#productos" class="btn btn-gold">
                    <i class="fa-solid fa-store"></i> Ver Cortes de Carnicería
                </a>
                <a href="#contacto" class="btn btn-outline">
                    <i class="fa-solid fa-cart-shopping"></i> Hacer Pedido
                </a>
            </div>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <span class="stat-number">15+</span>
                <span class="stat-label">Años de Tradición</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">2</span>
                <span class="stat-label">Sedes de Carnicería</span>
            </div>
        </div>
    </div>
</section>

<!-- 2. Sección Conócenos / Tradición Carnicera -->
<section id="conocenos" class="section section-dark">
    <div class="container">
        <div class="about-grid">
            <div class="about-img-container">
                <img src="images/hero.jpg" alt="Maestro Carnicero Copacarnes">
                <div class="about-badge-floating">
                    <i class="fa-solid fa-drumstick-bite"></i>
                    <div>
                        <strong style="color: #fff; display: block; font-size: 1.1rem;">Carnicería Tradicional</strong>
                        <span style="color: var(--color-text-muted); font-size: 0.85rem;">Especialistas en Carne Fresca</span>
                    </div>
                </div>
            </div>

            <div class="about-content">
                <span class="section-tag">Nuestra Identidad Carnicera</span>
                <h2 class="section-title">Expertos en Cortes de Carne Fresca & Calidad Premium</h2>
                <p style="color: var(--color-text-muted); margin-bottom: 1.5rem; font-size: 1.05rem;">
                    En <strong class="text-gold">Copacarnes</strong> somos una <strong>carnicería especializada</strong> dedicada a brindarte la mejor carne para tu mesa. Nuestro enfoque principal es ofrecer desposte fresco diario de res, cerdo, pollo y embutidos artesanales con rigurosa inocuidad y porcionado experto.
                </p>
                <p style="color: var(--color-text-muted); margin-bottom: 2rem;">
                    Nuestros carniceros atienden tus pedidos de forma personalizada, seleccionando el grosor y tipo de corte exacto para tus comidas diarias o asados de fin de semana. Adicionalmente, en nuestra sede principal ofrecemos servicio de <strong>restaurante asadero</strong> para deleitar a quienes prefieran saborear sus cortes preparados a la parrilla.
                </p>

                <div class="about-features">
                    <div class="feature-box">
                        <h4><i class="fa-solid fa-drumstick-bite text-gold"></i> Desposte & Porcionado a Medida</h4>
                        <p>Atención directa con carniceros expertos que preparan exactamente lo que necesitas para tu cocina o parrilla.</p>
                    </div>
                    <div class="feature-box">
                        <h4><i class="fa-solid fa-utensils text-gold"></i> Servicio Complementario de Asadero</h4>
                        <p>Disfruta de nuestros propios cortes servidos directamente al carbón en nuestro restaurante boutique.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cuadro / Sección: Conoce a Nuestro Equipo COPACARNES por Roles -->
        <?php
        $equipo_grupos = [
            'dueno' => [
                'titulo' => '👑 Propietarios & Administración',
                'miembros' => []
            ],
            'carnicero' => [
                'titulo' => '🥩 Maestros Carniceros',
                'miembros' => []
            ],
            'cocinero' => [
                'titulo' => '🍳 Chefs & Cocina de Asadero',
                'miembros' => []
            ],
            'cajero' => [
                'titulo' => '💳 Cajeras & Atención al Cliente',
                'miembros' => []
            ],
            'domiciliario' => [
                'titulo' => '🛵 Domiciliarios & Logística',
                'miembros' => []
            ]
        ];

        if ($pdo) {
            try {
                $stmt_eq = $pdo->query("SELECT id, nombre, rol, avatar FROM usuarios WHERE (estado IS NULL OR estado = 'activo') AND LOWER(nombre) NOT LIKE '%stiven%' AND id != 12 AND LOWER(nombre) NOT LIKE '%jorge (carnicero)%' ORDER BY FIELD(rol, 'dueno', 'carnicero', 'cocinero', 'cajero', 'domiciliario'), id ASC");
                $all_users = $stmt_eq->fetchAll();

                foreach ($all_users as $mb) {
                    $r = strtolower($mb['rol']);
                    if (isset($equipo_grupos[$r])) {
                        $equipo_grupos[$r]['miembros'][] = $mb;
                    } else {
                        $equipo_grupos['carnicero']['miembros'][] = $mb;
                    }
                }
            } catch (Exception $e) {}
        }

        if (!function_exists('formatear_rol_equipo')) {
            function formatear_rol_equipo($rol_raw, $nombre_raw) {
                $r = strtolower($rol_raw);
                if ($r === 'dueno') return 'Propietario(a)';
                if ($r === 'carnicero') return 'Carnicero';
                if ($r === 'cocinero') return 'Cocinera';
                if ($r === 'cajero') return 'Cajera';
                if ($r === 'domiciliario') return 'Domiciliario';
                return ucfirst($r);
            }
        }

        if (!function_exists('formatear_nombre_equipo')) {
            function formatear_nombre_equipo($nombre_raw) {
                $nom = mb_check_encoding($nombre_raw, 'UTF-8') ? $nombre_raw : utf8_encode($nombre_raw);
                if (preg_match('/^([^\(]+)/', $nom, $m)) {
                    return trim($m[1]);
                }
                return trim($nom);
            }
        }
        ?>

        <div style="margin-top: 2.5rem; padding-top: 2rem; border-top: 1px solid rgba(212, 175, 55, 0.2);">
            <div class="text-center" style="margin-bottom: 1.5rem;">
                <span class="section-tag" style="margin-bottom: 0.3rem;"><i class="fa-solid fa-users text-gold"></i> Talento Humano</span>
                <h3 style="font-size: 1.5rem; font-weight: 800; color: #ffffff; margin: 0.2rem 0 0.4rem 0;">
                    Conoce a nuestro equipo <span class="text-gold">COPACARNES</span>
                </h3>
            </div>

            <!-- Grilla de Tarjetas Badge del Equipo (Diseño Solicitado) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.2rem;">
                <?php 
                $all_members = [];
                foreach ($equipo_grupos as $grupo_key => $grupo_data) {
                    if (!empty($grupo_data['miembros'])) {
                        foreach ($grupo_data['miembros'] as $mb) {
                            $all_members[] = $mb;
                        }
                    }
                }
                ?>
                <?php foreach ($all_members as $mb): 
                    $nom_clean = formatear_nombre_equipo($mb['nombre']);
                    $rol_clean = formatear_rol_equipo($mb['rol'], $mb['nombre']);
                    $foto_url = !empty($mb['avatar']) ? $mb['avatar'] : 'images/avatar-default.png';
                ?>
                <div style="background: linear-gradient(135deg, rgba(35, 40, 52, 0.95), rgba(18, 22, 32, 0.95)); border: 2px solid rgba(212, 175, 55, 0.3); border-radius: 18px; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 1.1rem; box-shadow: 4px 6px 18px rgba(0,0,0,0.5); transition: all 0.25s ease;" onmouseover="this.style.borderColor='var(--color-gold)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(212,175,55,0.25)';" onmouseout="this.style.borderColor='rgba(212, 175, 55, 0.3)'; this.style.transform='translateY(0)'; this.style.boxShadow='4px 6px 18px rgba(0,0,0,0.5)';">
                    
                    <!-- Marco Fotográfico Cuadrado Redondeado -->
                    <div style="width: 68px; height: 68px; min-width: 68px; border-radius: 14px; padding: 2px; background: #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.4); overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="<?php echo htmlspecialchars($nom_clean); ?>" style="width: 100%; height: 100%; border-radius: 12px; object-fit: cover;">
                    </div>

                    <!-- Nombre Subrayado y Cargo -->
                    <div style="display: flex; flex-direction: column; justify-content: center; overflow: hidden;">
                        <h4 style="color: #ffffff; font-size: 1.05rem; font-weight: 700; margin: 0 0 0.25rem 0; text-decoration: underline; text-underline-offset: 3px; text-decoration-color: rgba(255,255,255,0.7); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($nom_clean); ?>">
                            <?php echo htmlspecialchars($nom_clean, ENT_QUOTES, 'UTF-8'); ?>
                        </h4>
                        <span style="color: var(--color-gold); font-size: 0.88rem; font-weight: 600; line-height: 1.2;">
                            <?php echo htmlspecialchars($rol_clean, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- 3. Sección Nuestras Sedes -->
<section id="sedes" class="section">
    <div class="container text-center">
        <h2 class="section-title">Nuestras Sedes</h2>
        <p class="section-subtitle">Visita nuestras sedes de carnicería fresca y servicio de restaurante.</p>

        <div class="sedes-grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 480px)); justify-content: center;">
            <!-- Sede 1: Principal -->
            <div class="sede-card">
                <div class="sede-img">
                    <img src="images/restaurante.jpg" alt="Sede Principal Zona Gourmet">
                    <span class="sede-tag"><i class="fa-solid fa-star"></i> Sede Principal & Restaurante</span>
                </div>
                <div class="sede-info">
                    <div class="sede-detail">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Av. Principal #45-18</span>
                    </div>
                    <div class="sede-detail">
                        <i class="fa-solid fa-phone"></i>
                        <span>+57 316 3746875</span>
                    </div>
                    <div class="sede-detail">
                        <i class="fa-solid fa-clock"></i>
                        <span>Lun - Sáb: 7:00 AM - 7:00 PM<br>Dom y Festivos: 7:00 AM - 2:00 PM</span>
                    </div>
                    <div style="display: flex; gap: 0.8rem; margin-top: 1.2rem;">
                        <button type="button" onclick="openMapModal('principal')" class="btn btn-outline" style="flex: 1; font-size: 0.82rem; padding: 0.7rem 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; cursor: pointer;">
                            <i class="fa-solid fa-map-location-dot"></i> Cómo Llegar
                        </button>
                        <a href="#contacto" class="btn btn-primary" style="flex: 1.2; font-size: 0.82rem; padding: 0.7rem 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none;">
                            <i class="fa-solid fa-calendar-check"></i> Reservar / Pedido
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sede 2: Secundaria -->
            <div class="sede-card">
                <div class="sede-img">
                    <img src="images/sede.jpg" alt="Sede Secundaria Carniceria">
                    <span class="sede-tag" style="background-color: #333;"><i class="fa-solid fa-store"></i> Sede Secundaria</span>
                </div>
                <div class="sede-info">
                    <div class="sede-detail">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Calle 127 #15-32</span>
                    </div>
                    <div class="sede-detail">
                        <i class="fa-solid fa-phone"></i>
                        <span>+57 302 2185285</span>
                    </div>
                    <div class="sede-detail">
                        <i class="fa-solid fa-clock"></i>
                        <span>Lun - Sáb: 7:00 AM - 7:00 PM<br>Dom y Festivos: 7:00 AM - 2:00 PM</span>
                    </div>
                    <div style="display: flex; gap: 0.8rem; margin-top: 1.2rem;">
                        <button type="button" onclick="openMapModal('secundaria')" class="btn btn-outline" style="flex: 1; font-size: 0.82rem; padding: 0.7rem 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; cursor: pointer;">
                            <i class="fa-solid fa-map-location-dot"></i> Cómo Llegar
                        </button>
                        <a href="#contacto" class="btn btn-primary" style="flex: 1.2; font-size: 0.82rem; padding: 0.7rem 0.3rem; display: flex; align-items: center; justify-content: center; gap: 0.3rem; text-decoration: none;">
                            <i class="fa-solid fa-basket-shopping"></i> Hacer Pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. Sección Restaurante Asadero (10% Experiencia Complementaria) -->
<section id="restaurante" class="section section-dark" style="border-top: 1px dashed rgba(212, 175, 55, 0.3); border-bottom: 1px dashed rgba(212, 175, 55, 0.3);">
    <div class="container">
        <div class="restaurant-wrapper">
            <div class="restaurant-grid">
                <div>
                    <span class="section-tag"><i class="fa-solid fa-utensils text-gold"></i> Asadero & Parrilla</span>
                    <h2 class="section-title">El Asadero <span class="text-gold">Copacarnes</span></h2>
                    <p style="color: var(--color-text-muted); margin-bottom: 1.5rem; font-size: 1.05rem;">
                        ¿Deseas probar nuestros propios cortes de carnicería asados en su punto perfecto? En nuestra <strong>Sede Principal</strong> contamos con servicio opcional de restaurante parrilla al carbón para disfrutar en familia.
                    </p>
                    <ul style="margin-bottom: 2rem; color: var(--color-text-muted);">
                        <li style="margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-check text-gold"></i> Carne 100% fresca seleccionada de nuestra propia carnicería.</li>
                        <li style="margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-check text-gold"></i> Asado al carbón en su punto óptimo de jugosidad.</li>
                        <li><i class="fa-solid fa-circle-check text-gold"></i> Reservas de mesa para eventos o almuerzos de fin de semana.</li>
                    </ul>
                    <a href="#contacto" class="btn btn-gold">
                        <i class="fa-solid fa-calendar-check"></i> Reservar Mesa en Restaurante
                    </a>
                </div>

                <?php
                $menu_del_dia_items = [];
                if ($pdo) {
                    try {
                        $stmt_md = $pdo->query("SELECT * FROM menu_del_dia WHERE activo = 1 ORDER BY id ASC");
                        $menu_del_dia_items = $stmt_md->fetchAll();
                    } catch (Exception $e) {}
                }
                $horario_global = !empty($menu_del_dia_items[0]['horario_atencion']) ? $menu_del_dia_items[0]['horario_atencion'] : '11:30 AM - 3:00 PM';
                ?>
                <div class="restaurant-menu-preview" style="background: rgba(15, 23, 42, 0.85); border: 1.5px solid var(--color-gold); border-radius: 16px; padding: 1.8rem; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.2rem; border-bottom: 1px dashed rgba(212, 175, 55, 0.3); padding-bottom: 0.8rem;">
                        <h3 style="margin: 0; color: #ffffff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-utensils text-gold"></i> Menú del Día (Almuerzo Ejecutivo)
                        </h3>
                        <span class="product-badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                            <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($horario_global); ?>
                        </span>
                    </div>

                    <?php if (empty($menu_del_dia_items)): ?>
                        <p style="color: var(--color-text-muted); font-size: 0.9rem; text-align: center; margin: 2rem 0;">Consulta el menú del día directamente con nuestras cocineras.</p>
                    <?php else: ?>
                        <?php foreach ($menu_del_dia_items as $m_item): ?>
                        <div class="menu-item-line">
                            <span class="menu-item-name">
                                <i class="fa-solid <?php echo htmlspecialchars($m_item['icono'] ?? 'fa-drumstick-bite'); ?> text-gold" style="margin-right: 0.35rem;"></i>
                                <?php echo htmlspecialchars($m_item['titulo']); ?>
                            </span>
                            <span class="menu-item-dots"></span>
                            <span class="menu-item-price" style="color: var(--color-gold); font-weight: 800;">$<?php echo number_format($m_item['precio'], 0, ',', '.'); ?> COP</span>
                        </div>
                        <?php if (!empty($m_item['descripcion'])): ?>
                            <p class="menu-item-desc"><?php echo htmlspecialchars($m_item['descripcion']); ?></p>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <button type="button" onclick="abrirModalCartaRestaurante()" class="btn btn-gold" style="width: 100%; margin-top: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem; font-weight: 800; font-size: 0.9rem;">
                        <i class="fa-solid fa-book-open"></i> Abrir Carta Completa del Restaurante
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Sección Productos Destacados -->
<section id="productos" class="section">
    <div class="container">
        <!-- Encabezado Productos Destacados -->
        <div class="text-center">
            <h2 class="section-title">Productos Destacados</h2>
            <p class="section-subtitle">Selección superior de Res, Cerdo, Pollo y Embutidos artesanales empacados y porcionados a tu medida para tu hogar o asado.</p>
        </div>

        <!-- Grilla 1: Destacados Dinámicos (Mínimo 3, Máximo 4) -->
        <?php
        $destacados_lista = [];
        if ($pdo) {
            try {
                $stmt_d = $pdo->query("SELECT * FROM productos WHERE (estado IS NULL OR estado = 'activo') AND destacado = 1 ORDER BY id DESC LIMIT 4");
                $destacados_lista = $stmt_d->fetchAll();

                if (count($destacados_lista) < 3) {
                    $existing_ids = array_column($destacados_lista, 'id');
                    $in_clause = !empty($existing_ids) ? ("AND id NOT IN (" . implode(',', $existing_ids) . ")") : "";
                    $limit_fill = 4 - count($destacados_lista);
                    $stmt_fill = $pdo->query("SELECT * FROM productos WHERE (estado IS NULL OR estado = 'activo') {$in_clause} ORDER BY id ASC LIMIT {$limit_fill}");
                    $destacados_lista = array_merge($destacados_lista, $stmt_fill->fetchAll());
                }
            } catch (Exception $e) {}
        }
        ?>
        <div class="products-grid">
            <?php foreach ($destacados_lista as $prod_item): ?>
            <div class="product-card" onclick="window.location.href='producto-detalle.php?id=<?php echo $prod_item['slug']; ?>';" style="cursor: pointer;">
                <div class="product-img">
                    <img src="<?php echo htmlspecialchars($prod_item['imagen']); ?>" alt="<?php echo htmlspecialchars($prod_item['nombre']); ?>">
                    <span class="product-badge"><i class="fa-solid fa-star text-gold"></i> <?php echo htmlspecialchars($prod_item['etiqueta'] ?: 'Destacado'); ?></span>
                </div>
                <div class="product-body">
                    <h3 class="product-title"><a href="producto-detalle.php?id=<?php echo $prod_item['slug']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($prod_item['nombre']); ?></a></h3>
                    <p class="product-desc"><?php echo htmlspecialchars($prod_item['descripcion']); ?></p>
                    <div class="product-footer">
                        <span class="product-price">$ <?php echo number_format($prod_item['precio'], 0, ',', '.'); ?> COP</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Subsección 2: Productos en Descuento (Mínimo 3, Máximo 4) -->
        <div style="margin-top: 5rem; width: 100%;">
            <div class="text-center" style="margin-bottom: 2.5rem;">
                <h3 class="section-title" style="font-size: 2.2rem;"><i class="fa-solid fa-tags text-red"></i> Productos en Descuento</h3>
                <p class="section-subtitle">Aprovecha nuestros precios especiales por tiempo limitado en cortes seleccionados.</p>
            </div>

            <?php
            $descuentos_lista = [];
            if ($pdo) {
                try {
                    $stmt_desc = $pdo->query("SELECT * FROM productos WHERE (estado IS NULL OR estado = 'activo') AND en_descuento = 1 ORDER BY id DESC LIMIT 4");
                    $descuentos_lista = $stmt_desc->fetchAll();

                    if (count($descuentos_lista) < 3) {
                        $existing_ids = array_column($descuentos_lista, 'id');
                        $in_clause = !empty($existing_ids) ? ("AND id NOT IN (" . implode(',', $existing_ids) . ")") : "";
                        $limit_fill = 4 - count($descuentos_lista);
                        $stmt_fill = $pdo->query("SELECT * FROM productos WHERE (estado IS NULL OR estado = 'activo') {$in_clause} ORDER BY id DESC LIMIT {$limit_fill}");
                        $descuentos_lista = array_merge($descuentos_lista, $stmt_fill->fetchAll());
                    }
                } catch (Exception $e) {}
            }
            ?>
            <div class="products-grid">
                <?php foreach ($descuentos_lista as $desc_item): 
                    $pct = !empty($desc_item['descuento_porcentaje']) ? floatval($desc_item['descuento_porcentaje']) : 15;
                    $p_oferta = !empty($desc_item['precio_oferta']) && $desc_item['precio_oferta'] > 0 ? floatval($desc_item['precio_oferta']) : round($desc_item['precio'] * (1 - ($pct / 100)));
                ?>
                <div class="product-card" onclick="window.location.href='producto-detalle.php?id=<?php echo $desc_item['slug']; ?>';" style="border-color: rgba(139, 0, 0, 0.4); cursor: pointer;">
                    <div class="product-img">
                        <img src="<?php echo htmlspecialchars($desc_item['imagen']); ?>" alt="<?php echo htmlspecialchars($desc_item['nombre']); ?>">
                        <span style="position: absolute; top: 12px; left: 12px; background: rgba(10, 10, 10, 0.88); color: #d4af37; border: 1px solid #d4af37; padding: 0.3rem 0.65rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700; display: flex; align-items: center; gap: 0.35rem; backdrop-filter: blur(4px); z-index: 2;">
                            <i class="fa-solid fa-clock"></i> Oferta Especial
                        </span>
                        <span class="product-badge" style="background-color: var(--color-primary);">-<?php echo number_format($pct, 0); ?>% OFF</span>
                    </div>
                    <div class="product-body">
                        <h3 class="product-title"><a href="producto-detalle.php?id=<?php echo $desc_item['slug']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($desc_item['nombre']); ?></a></h3>
                        <p class="product-desc"><?php echo htmlspecialchars($desc_item['descripcion']); ?></p>
                        <div class="product-footer">
                            <div>
                                <span style="font-size: 0.85rem; color: var(--color-text-muted); text-decoration: line-through; margin-right: 0.4rem;">$ <?php echo number_format($desc_item['precio'], 0, ',', '.'); ?></span>
                                <span class="product-price" style="color: #ff6b6b;">$ <?php echo number_format($p_oferta, 0, ',', '.'); ?> COP</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Botón Ver Todos los Productos -->
        <div style="margin-top: 4rem; width: 100%;" class="text-center">
            <a href="productos.php" class="btn btn-gold" style="padding: 0.9rem 2.2rem; font-size: 1rem; border-radius: var(--radius-sm);">
                <i class="fa-solid fa-store"></i> Ver Todos los Productos
            </a>
        </div>
    </div>
</section>

<!-- 6. Sección de Pedidos de Carnicería & Reservas -->
<section id="contacto" class="section section-dark">
    <div class="container">
        <div class="text-center">
            <h2 class="section-title">Hacer pedido o Reservar</h2>
            <p class="section-subtitle">Realiza tu pedido de cortes frescos para entrega a domicilio, recogida en sede o reserva tu mesa en el asadero.</p>
        </div>

        <div class="contact-grid">
            <div class="contact-info-card">
                <h3 style="font-size: 1.5rem; color: var(--color-gold); margin-bottom: 2rem;">Información Directa</h3>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="contact-text">
                        <h4>Teléfonos & WhatsApp</h4>
                        <p><strong>Sede Principal:</strong> +57 316 3746875<br><strong>Sede Secundaria:</strong> +57 302 2185285</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="contact-text">
                        <h4>Correo Electrónico</h4>
                        <p>contacto@copacarnes.com<br>reservas@copacarnes.com</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="contact-text">
                        <h4>Nuestras Sedes</h4>
                        <p>
                            <strong>Sede Principal:</strong> Av. Principal #45-18 (Carnicería & Restaurante)<br>
                            <strong>Sede Secundaria:</strong> Calle 127 #15-32 (Carnicería)
                        </p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="contact-text">
                        <h4>Información de Reserva</h4>
                        <p>
                            Para reservar mesa en el <strong>Restaurante Asadero (Sede Principal)</strong> o solicitar pedidos especiales de carnicería, indícanos fecha, hora, número de personas o cortes requeridos.
                        </p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <?php
                $carniceros_db = [];
                if ($pdo) {
                    try {
                        $stmt_carn = $pdo->query("SELECT id, nombre, correo, sede_asignada, avatar FROM usuarios WHERE rol = 'carnicero' AND estado = 'activo' ORDER BY id ASC");
                        $carniceros_db = $stmt_carn->fetchAll();
                    } catch (Exception $e) {}
                }

                if (isset($_GET['enviado']) && $_GET['enviado'] == '1') {
                    $cli_lbl = !empty($_GET['cliente']) ? htmlspecialchars($_GET['cliente']) : 'Cliente';
                    echo '<div id="msg-alerta-contacto" class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">';
                    echo '  <div><i class="fa-solid fa-circle-check"></i> ¡Gracias por comunicarte, <strong>' . $cli_lbl . '</strong>! Tu mensaje ha sido enviado con éxito. Nos pondremos en contacto contigo muy pronto.</div>';
                    echo '  <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:#34d399; font-size:1.4rem; cursor:pointer; line-height:1; padding:0 0.4rem;" title="Cerrar">&times;</button>';
                    echo '</div>';
                }
                ?>
                <form action="index.php#contacto" method="POST" id="form-contacto-web">
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej. Carlos Mendoza" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono / Celular</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control" placeholder="+57 300 123 4567" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="tu@correo.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sede">Sede de Interés / Tipo de Solicitud</label>
                        <select id="sede" name="sede" class="form-control" style="background-color: #0A0A0A;" required onchange="toggleEntregaOptions()">
                            <option value="Sede Principal - Reserva Restaurante">Sede Principal - Reserva Mesa Restaurante</option>
                            <option value="Sede Principal - Pedido Restaurante">Sede Principal - Pedido de Restaurante</option>
                            <option value="Sede Principal - Pedido Carniceria">Sede Principal - Pedido de Carnicería</option>
                            <option value="Sede Secundaria - Pedido Carniceria">Sede Secundaria - Pedido de Carnicería</option>
                        </select>
                    </div>

                    <!-- Opción Condicional 1: Método de Entrega (Solo para Pedidos de Carnicería) -->
                    <div class="form-group" id="grupo-metodo-entrega" style="display: none;">
                        <label class="form-label" for="metodo_entrega">Opciones de Entrega</label>
                        <select id="metodo_entrega" name="metodo_entrega" class="form-control" style="background-color: #0A0A0A;" onchange="toggleDireccionField()">
                            <option value="recoger">Recoger en Sede</option>
                            <option value="domicilio">Enviar a Domicilio</option>
                        </select>
                    </div>

                    <!-- Opción Condicional 2: Dirección de Envío (Solo si se selecciona Enviar a Domicilio) -->
                    <div class="form-group" id="grupo-direccion" style="display: none;">
                        <label class="form-label" for="direccion">Dirección Completa de Envío</label>
                        <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Ej. Calle 127 #15-32, Apto 502, Edificio Los Pinos">
                    </div>

                    <!-- Opción Condicional 3: Carnicero Seleccionado (Señalado en Verde) -->
                    <div class="form-group" id="grupo-carnicero-elegido" style="display: none;">
                        <label class="form-label" style="color: #34d399;"><i class="fa-solid fa-user-check text-gold"></i> Carnicero Asignado para Tu Pedido</label>
                        <div style="background: rgba(16, 185, 129, 0.12); border: 1.5px solid #10b981; padding: 0.85rem 1.2rem; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img id="carnicero-elegido-img" src="images/carnicero-disponible.png" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-gold); background: #000;">
                                <div>
                                    <strong id="carnicero-elegido-nombre" style="color: #ffffff; font-size: 1.05rem; display: block;">Carnicero Disponible</strong>
                                    <span id="carnicero-elegido-sub" style="color: var(--color-text-muted); font-size: 0.8rem;">Se asignará automáticamente al recibir el pedido</span>
                                </div>
                            </div>
                            <span id="carnicero-elegido-badge" class="product-badge" style="background: rgba(212, 175, 55, 0.2); color: var(--color-gold); border: 1px solid var(--color-gold); padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.75rem;"><i class="fa-solid fa-clock"></i> Por Defecto</span>
                        </div>
                        <input type="hidden" id="input_carnicero_id" name="carnicero_id" value="0">
                        <input type="hidden" id="input_carnicero_nombre" name="carnicero_nombre" value="Carnicero Disponible">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mensaje">Detalles de Reserva / Pedido</label>
                        <textarea id="mensaje" name="mensaje" class="form-control" placeholder="Para reservas: Fecha, Hora y Número de personas. 
Para carnicería: Cortes y Kilos deseados..." required style="height: 110px;"></textarea>
                    </div>
                    <button type="submit" name="contact_submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Mensaje / Solicitar Reserva
                    </button>
                </form>
            </div>

            <!-- Panel de Selección de Carniceros por Sede -->
            <div id="panel-lista-carniceros" class="contact-info-card" style="display: none; background: rgba(10, 10, 10, 0.95); border: 1.5px dashed var(--color-gold); padding: 1.8rem; height: 100%; min-height: 580px; flex-direction: column; justify-content: space-between;">
                
                <!-- Encabezado Superior de Sede Actual -->
                <div id="badge-sede-actual" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(15, 23, 42, 0.8)); border: 1.5px solid var(--color-gold); color: #ffffff; padding: 0.85rem 1rem; border-radius: 10px; font-weight: 800; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.6rem; margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fa-solid fa-store text-gold" style="font-size: 1.15rem;"></i> <span id="texto-sede-actual">CARNICEROS - SEDE PRINCIPAL</span>
                </div>

                <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 1.2rem; text-align: center;">
                    Haz clic en la foto para seleccionar tu carnicero de preferencia:
                </p>

                <!-- Grilla 2 Columnas Ocupando el 100% del Espacio Vertical Disponible -->
                <div id="grid-carniceros-cards" style="display: grid; grid-template-columns: repeat(2, 1fr); grid-auto-rows: 1fr; gap: 1.2rem; flex: 1; height: 100%; align-items: stretch;">
                    
                    <!-- Opción Por Defecto: Carnicero Disponible -->
                    <div class="carnicero-card-item active-card" data-id="0" data-nombre="Carnicero Disponible" data-sede="Cualquier Sede" data-avatar="images/carnicero-disponible.png" onclick="seleccionarCarniceroItem(this)" style="background: rgba(212, 175, 55, 0.16); border: 2px solid var(--color-gold); padding: 1.8rem 0.8rem; border-radius: 14px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.4);">
                        <img src="images/carnicero-disponible.png" style="width: 95px; height: 95px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-gold); background: #000; margin-bottom: 0.75rem; box-shadow: 0 6px 15px rgba(0,0,0,0.6);" alt="Carnicero Disponible">
                        <strong style="color: #ffffff; font-size: 0.98rem; text-align: center; font-weight: 700; line-height: 1.25;">Carnicero Disponible</strong>
                    </div>

                    <?php foreach ($carniceros_db as $carn): 
                        $c_avatar = !empty($carn['avatar']) ? $carn['avatar'] : 'images/avatar-default.png';
                        $c_nombre_display = trim(preg_replace('/\s*\(.*?\)/', '', $carn['nombre']));
                    ?>
                    <div class="carnicero-card-item" data-id="<?php echo $carn['id']; ?>" data-nombre="<?php echo htmlspecialchars($carn['nombre']); ?>" data-sede="<?php echo htmlspecialchars($carn['sede_asignada'] ?: 'Sede Principal'); ?>" data-avatar="<?php echo htmlspecialchars($c_avatar); ?>" onclick="seleccionarCarniceroItem(this)" style="background: rgba(255,255,255,0.03); border: 1px solid var(--color-border); padding: 1.8rem 0.8rem; border-radius: 14px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                        <img src="<?php echo htmlspecialchars($c_avatar); ?>" style="width: 95px; height: 95px; border-radius: 50%; object-fit: cover; border: 3px solid #3b82f6; background: #000; margin-bottom: 0.75rem; box-shadow: 0 6px 15px rgba(0,0,0,0.6);" alt="<?php echo htmlspecialchars($carn['nombre']); ?>">
                        <strong style="color: #ffffff; font-size: 0.98rem; text-align: center; font-weight: 700; line-height: 1.25;"><?php echo htmlspecialchars($c_nombre_display); ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. Sección Promoción de Redes Sociales (Ubicación Señalada en Rojo) -->
<section id="redes-sociales" class="section" style="background: linear-gradient(135deg, #060913, #0d1630); border-top: 1px solid rgba(212, 175, 55, 0.3); border-bottom: 1px solid rgba(212, 175, 55, 0.3); padding: 4.5rem 0; overflow: hidden; position: relative;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3.5rem; align-items: center;">
            
            <!-- Columna Izquierda: Información de Redes & Conexión -->
            <div>
                <span class="section-tag" style="margin-bottom: 0.8rem; display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(212, 175, 55, 0.15); color: var(--color-gold); border: 1px solid var(--color-gold); padding: 0.35rem 0.85rem; border-radius: 50px; font-size: 0.82rem; font-weight: 700;">
                    <i class="fa-solid fa-hashtag text-gold"></i> Comunidad & Redes Sociales
                </span>

                <h2 style="font-size: 2.3rem; font-weight: 900; color: #ffffff; margin: 0.5rem 0 1rem 0; line-height: 1.25;">
                    ¡Síguenos en nuestras <span class="text-gold">Redes Oficiales</span> y vive la experiencia!
                </h2>

                <p style="color: var(--color-text-muted); font-size: 1.02rem; line-height: 1.6; margin-bottom: 2rem;">
                    Conéctate con nuestra comunidad gourmet. Mira videos exclusivos de cortes asados al carbón, recomendaciones del Maestro Carnicero, ofertas del día y eventos especiales en nuestro restaurante.
                </p>

                <!-- Tarjetas de Redes Sociales -->
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
                    <!-- Instagram Card -->
                    <a href="https://instagram.com" target="_blank" style="text-decoration: none;">
                        <div style="background: rgba(15, 23, 42, 0.8); border: 1.5px solid rgba(225, 48, 108, 0.4); border-radius: 14px; padding: 0.9rem 1.2rem; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#e1306c'; this.style.transform='translateX(6px)';" onmouseout="this.style.borderColor='rgba(225, 48, 108, 0.4)'; this.style.transform='translateX(0)';">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; box-shadow: 0 4px 15px rgba(225, 48, 108, 0.3);">
                                    <i class="fa-brands fa-instagram"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: #fff; font-size: 1rem; font-weight: 800;">Instagram Oficial</h4>
                                    <span style="color: #94a3b8; font-size: 0.82rem;">@copacarnes &bull; Fotos & Reels de Asados</span>
                                </div>
                            </div>
                            <span style="color: #e1306c; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; gap: 0.3rem;">
                                Ver Perfil <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </a>

                    <!-- TikTok Card -->
                    <a href="https://tiktok.com" target="_blank" style="text-decoration: none;">
                        <div style="background: rgba(15, 23, 42, 0.8); border: 1.5px solid rgba(0, 242, 234, 0.4); border-radius: 14px; padding: 0.9rem 1.2rem; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#00f2fe'; this.style.transform='translateX(6px)';" onmouseout="this.style.borderColor='rgba(0, 242, 234, 0.4)'; this.style.transform='translateX(0)';">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #000; border: 1px solid #00f2fe; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; box-shadow: 0 4px 15px rgba(0, 242, 234, 0.3);">
                                    <i class="fa-brands fa-tiktok"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: #fff; font-size: 1rem; font-weight: 800;">TikTok Videos</h4>
                                    <span style="color: #94a3b8; font-size: 0.82rem;">@copacarnes &bull; Videos cortos & Tips Parrilleros</span>
                                </div>
                            </div>
                            <span style="color: #00f2fe; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; gap: 0.3rem;">
                                Ver TikTok <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </a>

                    <!-- WhatsApp & Facebook Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <a href="https://wa.me/573163746875" target="_blank" style="text-decoration: none;">
                            <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 14px; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 0.8rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#10b981';" onmouseout="this.style.borderColor='rgba(16, 185, 129, 0.4)';">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff;">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </div>
                                <div>
                                    <h5 style="margin: 0; color: #fff; font-size: 0.88rem; font-weight: 700;">WhatsApp</h5>
                                    <span style="color: #34d399; font-size: 0.75rem;">Atención Directa</span>
                                </div>
                            </div>
                        </a>

                        <a href="https://facebook.com" target="_blank" style="text-decoration: none;">
                            <div style="background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 14px; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 0.8rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='#3b82f6';" onmouseout="this.style.borderColor='rgba(59, 130, 246, 0.4)';">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: #1877f2; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff;">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </div>
                                <div>
                                    <h5 style="margin: 0; color: #fff; font-size: 0.88rem; font-weight: 700;">Facebook</h5>
                                    <span style="color: #60a5fa; font-size: 0.75rem;">Página Oficial</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Imagen Grande de Promoción de Redes -->
            <div style="position: relative; text-align: center; display: flex; justify-content: center; align-items: center;">
                <div style="position: absolute; top: -20px; left: -20px; right: -20px; bottom: -20px; background: radial-gradient(circle, rgba(212, 175, 55, 0.25) 0%, rgba(0,0,0,0) 70%); border-radius: 30px; z-index: 1;"></div>
                <img src="images/la-casta-redes.png?v=<?php echo time(); ?>" alt="La Casta Copacarnes Redes Sociales" style="position: relative; z-index: 2; width: 100%; max-width: 480px; max-height: 480px; object-fit: contain; border-radius: 24px; border: 3px solid var(--color-gold); box-shadow: 0 20px 40px rgba(0,0,0,0.85); transition: transform 0.3s ease; background: #000;" onmouseover="this.style.transform='scale(1.03)';" onmouseout="this.style.transform='scale(1)';">
            </div>

        </div>
    </div>
</section>

<script>
function toggleEntregaOptions() {
    const sede = document.getElementById('sede').value;
    const grupoEntrega = document.getElementById('grupo-metodo-entrega');
    const grupoDireccion = document.getElementById('grupo-direccion');
    const direccionInput = document.getElementById('direccion');
    const grupoCarniceroElegido = document.getElementById('grupo-carnicero-elegido');
    const panelListaCarniceros = document.getElementById('panel-lista-carniceros');

    if (sede.includes('Pedido')) {
        grupoEntrega.style.display = 'block';
        toggleDireccionField();
    } else {
        grupoEntrega.style.display = 'none';
        grupoDireccion.style.display = 'none';
        if (direccionInput) direccionInput.required = false;
    }

    if (sede.includes('Carniceria') || sede.includes('Carnicería')) {
        if (grupoCarniceroElegido) grupoCarniceroElegido.style.display = 'block';
        if (panelListaCarniceros) panelListaCarniceros.style.display = 'flex';
        filtrarCarnicerosPorSede(sede);
    } else {
        if (grupoCarniceroElegido) grupoCarniceroElegido.style.display = 'none';
        if (panelListaCarniceros) panelListaCarniceros.style.display = 'none';
    }
}

function toggleDireccionField() {
    const metodo = document.getElementById('metodo_entrega').value;
    const grupoDireccion = document.getElementById('grupo-direccion');
    const direccionInput = document.getElementById('direccion');
    const sede = document.getElementById('sede').value;

    if (sede.includes('Pedido') && metodo === 'domicilio') {
        grupoDireccion.style.display = 'block';
        if (direccionInput) direccionInput.required = true;
    } else {
        grupoDireccion.style.display = 'none';
        if (direccionInput) direccionInput.required = false;
    }
}

function filtrarCarnicerosPorSede(sedeTexto) {
    const esPrincipal = (sedeTexto && sedeTexto.includes('Principal'));
    const targetSede = esPrincipal ? 'Sede Principal' : 'Sede Secundaria';

    const textBadge = document.getElementById('texto-sede-actual');
    if (textBadge) {
        textBadge.innerText = 'CARNICEROS - ' + targetSede.toUpperCase();
    }

    const cards = document.querySelectorAll('.carnicero-card-item');
    cards.forEach(card => {
        const cSede = card.getAttribute('data-sede');
        const cId = card.getAttribute('data-id');

        if (cId === '0' || cSede === targetSede) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });

    const defaultCard = document.querySelector('.carnicero-card-item[data-id="0"]');
    if (defaultCard) seleccionarCarniceroItem(defaultCard);
}

function seleccionarCarniceroItem(el) {
    const cards = document.querySelectorAll('.carnicero-card-item');
    cards.forEach(c => {
        c.style.borderColor = 'var(--color-border)';
        c.style.background = 'rgba(255,255,255,0.03)';
        const img = c.querySelector('img');
        if (img && c.getAttribute('data-id') !== '0') {
            img.style.borderColor = '#3b82f6';
        }
    });

    el.style.borderColor = 'var(--color-gold)';
    el.style.background = 'rgba(212, 175, 55, 0.16)';
    const elImg = el.querySelector('img');
    if (elImg) {
        elImg.style.borderColor = 'var(--color-gold)';
    }

    const id = el.getAttribute('data-id');
    const nombre = el.getAttribute('data-nombre');
    const sede = el.getAttribute('data-sede');
    const avatar = el.getAttribute('data-avatar');

    document.getElementById('input_carnicero_id').value = id;
    document.getElementById('input_carnicero_nombre').value = nombre;

    const chosenImg = document.getElementById('carnicero-elegido-img');
    if (chosenImg) chosenImg.src = avatar;
    const chosenName = document.getElementById('carnicero-elegido-nombre');
    if (chosenName) chosenName.innerText = nombre;

    const subSpan = document.getElementById('carnicero-elegido-sub');
    const badgeSpan = document.getElementById('carnicero-elegido-badge');

    if (subSpan && badgeSpan) {
        if (id === '0') {
            subSpan.innerText = 'Se asignará automáticamente al despachar';
            badgeSpan.innerHTML = '<i class="fa-solid fa-clock"></i> Por Defecto';
            badgeSpan.style.borderColor = 'var(--color-gold)';
            badgeSpan.style.color = 'var(--color-gold)';
        } else {
            subSpan.innerText = 'Carnicero Seleccionado por el Cliente (' + sede + ')';
            badgeSpan.innerHTML = '<i class="fa-solid fa-check"></i> Seleccionado';
            badgeSpan.style.borderColor = '#10b981';
            badgeSpan.style.color = '#34d399';
        }
    }
}

function toggleDireccionField() {
    const metodo = document.getElementById('metodo_entrega').value;
    const grupoDireccion = document.getElementById('grupo-direccion');
    const direccionInput = document.getElementById('direccion');
    const sede = document.getElementById('sede').value;

    if (sede.includes('Pedido') && metodo === 'domicilio') {
        grupoDireccion.style.display = 'block';
        if (direccionInput) direccionInput.required = true;
    } else {
        grupoDireccion.style.display = 'none';
        if (direccionInput) direccionInput.required = false;
    }
}

/* Modal de Google Maps */
function openMapModal(sedeType) {
    const modal = document.getElementById('mapModal');
    const iframe = document.getElementById('mapModalIframe');
    const title = document.getElementById('mapModalTitle');
    const externalBtn = document.getElementById('mapModalExternalBtn');

    const maps = {
        'principal': {
            title: 'Sede Principal - Carnicería & Asadero',
            url: 'https://www.google.com/maps/embed?pb=!4v1785702106796!6m8!1m7!1szTlZR7-7W8MtgCafmIOxsQ!2m2!1d6.346999243203424!2d-75.50889410328091!3f288.28398652130113!4f-5.8465765065143955!5f0.7820865974627469',
            directUrl: 'https://www.google.com/maps/@6.3469992,-75.5088941,3a,75y,288.28h,90t/data=!3m6!1e1!3m4!1szTlZR7-7W8MtgCafmIOxsQ!2e0!7i16384!8i8192'
        },
        'secundaria': {
            title: 'Sede Secundaria - Carnicería',
            url: 'https://www.google.com/maps/embed?pb=!4v1785702079755!6m8!1m7!1s9JorG4YCBVm7dl5F_Adz_Q!2m2!1d6.346743834875953!2d-75.50937467503473!3f94.1871453660705!4f-6.00295281374342!5f0.7820865974627469',
            directUrl: 'https://www.google.com/maps/@6.3467438,-75.5093747,3a,75y,94.19h,90t/data=!3m6!1e1!3m4!1s9JorG4YCBVm7dl5F_Adz_Q!2e0!7i16384!8i8192'
        }
    };

    if (maps[sedeType]) {
        title.innerHTML = '<i class="fa-solid fa-location-dot text-gold"></i> ' + maps[sedeType].title;
        iframe.src = maps[sedeType].url;
        externalBtn.href = maps[sedeType].directUrl;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeMapModal() {
    const modal = document.getElementById('mapModal');
    const iframe = document.getElementById('mapModalIframe');
    modal.style.display = 'none';
    iframe.src = '';
    document.body.style.overflow = 'auto';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('mapModal');
    if (e.target === modal) {
        closeMapModal();
    }
});

function abrirModalCartaRestaurante() {
    const modal = document.getElementById('modalCartaRestaurante');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalCartaRestaurante() {
    const modal = document.getElementById('modalCartaRestaurante');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

window.abrirModalCartaRestaurante = abrirModalCartaRestaurante;
window.cerrarModalCartaRestaurante = cerrarModalCartaRestaurante;

window.addEventListener('click', function(e) {
    const modalMap = document.getElementById('mapModal');
    if (e.target === modalMap) {
        closeMapModal();
    }
    const modalCarta = document.getElementById('modalCartaRestaurante');
    if (e.target === modalCarta) {
        cerrarModalCartaRestaurante();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const formWeb = document.getElementById('form-contacto-web');
    if (formWeb) {
        formWeb.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                setTimeout(function() {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando Mensaje...';
                }, 20);
            }
        });
    }

    const alertMsg = document.getElementById('msg-alerta-contacto');
    if (alertMsg) {
        // Limpiar URL eliminando los parametros GET (?enviado=1)
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname + '#contacto');
        }
        // Auto-desaparecer tras 5 segundos con animacion suave
        setTimeout(function() {
            alertMsg.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alertMsg.style.opacity = '0';
            alertMsg.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                if (alertMsg.parentNode) alertMsg.remove();
            }, 600);
        }, 5000);
    }
});
</script>

<!-- Modal Sobreposición: Carta Completa del Restaurante Copacarnes -->
<div id="modalCartaRestaurante" class="map-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 8, 20, 0.75) !important; backdrop-filter: blur(5px) !important; -webkit-backdrop-filter: blur(5px) !important; z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="map-modal-content" style="background: #0d1630; border: 1.5px solid var(--color-gold); border-radius: 20px; max-width: 920px; width: 100%; max-height: 90vh; overflow-y: auto !important; box-shadow: 0 25px 60px rgba(0,0,0,0.9); position: relative; padding: 0;">
        
        <!-- Header del Modal -->
        <div style="position: sticky; top: 0; background: #0f172a; padding: 1.2rem 1.6rem; border-bottom: 1.5px solid rgba(212, 175, 55, 0.4); display: flex; justify-content: space-between; align-items: center; z-index: 10;">
            <h3 style="margin: 0; color: #ffffff; font-size: 1.2rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-book-open text-gold"></i> Carta Completa & Menú Asadero Copacarnes
            </h3>
            <button type="button" onclick="cerrarModalCartaRestaurante()" style="background: none; border: none; color: #ffffff; font-size: 2rem; cursor: pointer; line-height: 1; padding: 0;" aria-label="Cerrar">&times;</button>
        </div>

        <div style="padding: 1.8rem;">
            <!-- Categoría 1: Cortes de Res & Cerdo a la Parrilla -->
            <div style="margin-bottom: 2rem;">
                <h4 style="color: var(--color-gold); border-bottom: 1px dashed rgba(212,175,55,0.3); padding-bottom: 0.4rem; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-fire"></i> Cortes Especiales al Carbón
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Churrasco de Lomo (400g)</span>
                            <span style="color: var(--color-gold);">$48.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Jugoso corte de lomo de res marinado al carbón con papa, yuca y chimichurri casero.</p>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Punta de Anca Premium (400g)</span>
                            <span style="color: var(--color-gold);">$52.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Corte con gordo exterior dorado al carbón, servido con arepa y ensalada de la casa.</p>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Baby Beef Tradicional (350g)</span>
                            <span style="color: var(--color-gold);">$45.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Magro, suave y sin grasa, asado al instante con patacón y guacamole.</p>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Costillas BBQ al Carbón (500g)</span>
                            <span style="color: var(--color-gold);">$42.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Costillas de cerdo en salsa artesanal agridulce con papa criolla.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría 2: Picadas Tradicionales -->
            <div style="margin-bottom: 2rem;">
                <h4 style="color: var(--color-gold); border-bottom: 1px dashed rgba(212,175,55,0.3); padding-bottom: 0.4rem; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-drumstick-bite"></i> Picadas para Compartir
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Picada Mixta Copacarnes (3-4 p.)</span>
                            <span style="color: var(--color-gold);">$68.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Res, Cerdo, Pollo, Chorizo santarrosano, Chicharrón, Arepas y Papa criolla.</p>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Gran Picada Familiar (5-6 p.)</span>
                            <span style="color: var(--color-gold);">$95.000 COP</span>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin: 0;">Porción generosa de todos nuestros cortes al carbón con yuca, papa y guacamole.</p>
                    </div>
                </div>
            </div>

            <!-- Categoría 3: Entradas, Sopas & Bebidas -->
            <div>
                <h4 style="color: var(--color-gold); border-bottom: 1px dashed rgba(212,175,55,0.3); padding-bottom: 0.4rem; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-glass-water"></i> Entradas & Bebidas
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Chorizo Santarrosano con Arepa</span>
                            <span style="color: var(--color-gold);">$12.000 COP</span>
                        </div>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Chicharrón Crocante con Yuca</span>
                            <span style="color: var(--color-gold);">$16.000 COP</span>
                        </div>
                    </div>

                    <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 800; color: #fff; margin-bottom: 0.3rem;">
                            <span>Jugos Naturales / Limonada</span>
                            <span style="color: var(--color-gold);">$8.000 COP</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boton de accion dentro del modal -->
            <div style="margin-top: 2rem; text-align: center;">
                <a href="#contacto" onclick="cerrarModalCartaRestaurante()" class="btn btn-gold" style="padding: 0.8rem 2rem; font-weight: 800;">
                    <i class="fa-solid fa-calendar-check"></i> Reservar Mesa o Hacer Pedido Ahora
                </a>
            </div>
        </div>
    </div>
</div>

<div id="mapModal" class="map-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 8, 20, 0.42) !important; backdrop-filter: blur(2px) !important; -webkit-backdrop-filter: blur(2px) !important; z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="map-modal-content" style="background: #0d1630; border: 1px solid #d4af37; border-radius: 16px; max-width: 880px; width: 100%; overflow: hidden !important; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8); position: relative;">
        <div style="padding: 1rem 1.2rem; background: rgba(15, 23, 42, 0.98); border-bottom: 1px solid rgba(212, 175, 55, 0.3); display: flex; justify-content: space-between; align-items: center; gap: 1rem; overflow: hidden;">
            <h3 id="mapModalTitle" style="margin: 0; color: #ffffff; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 1;">
                <i class="fa-solid fa-location-dot text-gold"></i> Ubicación en Google Maps
            </h3>
            <div style="display: flex; align-items: center; gap: 0.8rem; flex-shrink: 0;">
                <a id="mapModalExternalBtn" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.9rem; border-color: #d4af37; color: #d4af37; border-radius: 20px; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; white-space: nowrap;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver en Google Maps
                </a>
                <button type="button" onclick="closeMapModal()" style="background: none !important; border: none !important; color: #ffffff !important; font-size: 1.8rem !important; cursor: pointer !important; line-height: 1 !important; padding: 0 !important; margin: 0 !important; width: 32px !important; height: 32px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; outline: none !important; overflow: hidden !important; appearance: none !important; -webkit-appearance: none !important;" aria-label="Cerrar">&times;</button>
            </div>
        </div>
        <div style="width: 100%; height: 450px; background: #000;">
            <iframe id="mapModalIframe" src="" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
