<?php
require_once __DIR__ . '/config/db.php';

// Catálogo Completo de Productos
$productos_db = [
    'tomahawk' => [
        'nombre' => 'Tomahawk Steak Prime',
        'badge' => 'Res - Corte Especial',
        'precio' => '$165.000',
        'precio_anterior' => null,
        'imagen' => 'images/tomahawk.jpg',
        'desc' => 'Corte estrella de intenso marmoleo y sabor profundo. Posee un hueso largo francés expuesto que le confiere una presentación majestuosa al carbón y conserva sus jugos naturales durante la cocción.'
    ],
    'picanha' => [
        'nombre' => 'Picanha Prime Angus',
        'badge' => 'Res - Selección',
        'precio' => '$78.000',
        'precio_anterior' => null,
        'imagen' => 'images/picanha.jpg',
        'desc' => 'Corte tierno por excelencia conocido como la tapa de cuadril. Posee una generosa capa de grasa uniforme en la superficie que al derretirse a la brasa carameliza la carne con una sazón insuperable.'
    ],
    'burger' => [
        'nombre' => 'Pack Burger Artesanal (x4)',
        'badge' => 'Combo Burger',
        'precio' => '$42.000',
        'precio_anterior' => null,
        'imagen' => 'images/burger.jpg',
        'desc' => 'Medallones gourmet elaborados diariamente por nuestros maestros carniceros mediante una mezcla secreta de entraña y picanha. Sin conservantes ni rellenos artificiales.'
    ],
    'bife' => [
        'nombre' => 'Bife Ancho Seleccionado',
        'badge' => 'Res - Jugoso',
        'precio' => '$65.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Corte emblemático de la parrillada tradicional. Su infiltración de grasa intramuscular le otorga una suculencia única que se deshace en paladar.'
    ],
    'lomo-fino' => [
        'nombre' => 'Lomo Fino de Res (kg)',
        'badge' => 'Res - En Descuento',
        'badge_discount' => '-10% OFF',
        'expira' => 'Expira en 2 días',
        'precio' => '$52.000',
        'precio_anterior' => '$58.000',
        'imagen' => 'images/hero.jpg',
        'desc' => 'El corte más magro, tierno y suave de toda la res. Prácticamente sin grasa, perfecto para porcionar en medallones, preparar lomo al trapo o salteados gourmet.'
    ],
    'costilla-cerdo' => [
        'nombre' => 'Costilla de Cerdo BBQ (kg)',
        'badge' => 'Cerdo - Jugoso',
        'precio' => '$38.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Costilla de cerdo tipo St. Louis con abundante carne y marmoleo perfecto. Ideal para asar a fuego lento a la parrilla o glasear con salsa BBQ.'
    ],
    'chicharron' => [
        'nombre' => 'Chicharrón Carnudo (kg)',
        'badge' => 'Cerdo - En Descuento',
        'badge_discount' => '-13% OFF',
        'expira' => 'Expira en 3 días',
        'precio' => '$26.000',
        'precio_anterior' => '$30.000',
        'imagen' => 'images/hero.jpg',
        'desc' => 'Tocino de cerdo cuidadosamente seleccionado con una alta proporción de carne magra. Garantiza la formación de una garra crujiente perfecta y por dentro la carne más jugosa.'
    ],
    'lomo-cerdo' => [
        'nombre' => 'Lomo de Cerdo Seleccionado (kg)',
        'badge' => 'Cerdo - Fresco',
        'precio' => '$24.500',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Corte magro y extremadamente limpio de cerdo. Ideal para milanesas, chuletas horneadas o medallions a la mostaza.'
    ],
    'pechuga' => [
        'nombre' => 'Pechuga de Pollo Deshuesada (kg)',
        'badge' => 'Pollo - Magro',
        'precio' => '$21.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Pechuga entera de pollo totalmente deshuesada y sin piel. Máxima frescura y alto valor proteico para dietas saludables.'
    ],
    'alitas' => [
        'nombre' => 'Alitas de Pollo para Asar (kg)',
        'badge' => 'Pollo - En Descuento',
        'badge_discount' => '-17% OFF',
        'expira' => 'Expira hoy',
        'precio' => '$16.500',
        'precio_anterior' => '$20.000',
        'imagen' => 'images/hero.jpg',
        'desc' => 'Alitas de pollo frescas, totalmente limpias y porcionadas en plano y muslito. Listas para marinar a tu gusto con adobo artesanal, barbacoa o picante.'
    ],
    'chorizo-artesanal' => [
        'nombre' => 'Chorizo Artesanal de la Casa (pack x6)',
        'badge' => 'Embutidos - Receta Casa',
        'precio' => '$28.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Elaborado con 100% magro de cerdo y especias naturales sin harina ni colorantes artificiales. Sabor picante suave y textura inigualable a la brasa.'
    ],
    'morcilla' => [
        'nombre' => 'Morcilla Tradicional (pack x6)',
        'badge' => 'Embutidos - Artesanal',
        'precio' => '$22.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Morcilla criolla preparada con arroz, especias verdes y finas hierbas. Envoltura crujiente y relleno cremoso al asar.'
    ],
    'chimichurri' => [
        'nombre' => 'Chimichurri de la Casa (Frasco 250g)',
        'badge' => 'Extras - Aderezo',
        'precio' => '$15.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Aderezo artesanal argentino-colombiano a base de perejil fresco, ajo, ají suave, aceite de oliva virgen y especias maceradas.'
    ],
    'carbon' => [
        'nombre' => 'Carbón Vegetal Premium (Bolsa 3kg)',
        'badge' => 'Extras - Parrilla',
        'precio' => '$14.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Carbón vegetal de madera dura seleccionado. Garantiza encendido rápido, alto poder calórico y brasa de larga duración sin humo molesto.'
    ],
    'sal-marina' => [
        'nombre' => 'Sal Marina con Especias (Frasco 200g)',
        'badge' => 'Extras - Sazón',
        'precio' => '$18.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Mezcla parrillera de sal marina gruesa enriquecida con pimienta negra molida, pimienta dulce, romero y ajo deshidratado.'
    ],
    'combo-asado' => [
        'nombre' => 'Combo Asado Familiar (4-6p)',
        'badge' => 'Combo - En Descuento',
        'badge_discount' => '-12% OFF',
        'expira' => 'Expira en 24h',
        'precio' => '$128.000',
        'precio_anterior' => '$145.000',
        'imagen' => 'images/hero.jpg',
        'desc' => 'El paquete definitivo para tus reuniones familiares. Incluye 1 Picanha Angus (1kg), 1kg de Costilla de Cerdo jugosa, 1 Pack de Chorizos Artesanales (x6) y un frasco de Chimichurri de la casa.'
    ],
    'combo-pareja' => [
        'nombre' => 'Combo Parrillero Pareja (2p)',
        'badge' => 'Combo - Pareja',
        'precio' => '$85.000',
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Perfecto para una velada especial de 2 personas. Incluye 500g de Bife Ancho, 500g de Lomo de Cerdo, 2 Chorizos Artesanales y 1 Chimichurri.'
    ]
];

// Obtener ID / Slug del producto desde la URL
$id_producto = isset($_GET['id']) ? strtolower(trim($_GET['id'])) : 'tomahawk';
$p = null;

// 1. Buscar primero en la Base de Datos MySQL (Productos agregados dinámicamente)
if (!empty($id_producto) && isset($pdo) && $pdo) {
    try {
        $stmt_p = $pdo->prepare("SELECT * FROM productos WHERE (LOWER(slug) = ? OR LOWER(nombre) LIKE ? OR id = ?) AND (estado IS NULL OR estado = 'activo') LIMIT 1");
        $stmt_p->execute([$id_producto, '%' . $id_producto . '%', intval($id_producto)]);
        $db_prod = $stmt_p->fetch();

        if ($db_prod) {
            $precio_fmt = '$' . number_format($db_prod['precio'], 0, ',', '.') . ' COP';
            $precio_ant_fmt = null;
            if (!empty($db_prod['en_descuento']) && floatval($db_prod['precio_oferta']) > 0) {
                $precio_ant_fmt = '$' . number_format($db_prod['precio'], 0, ',', '.') . ' COP';
                $precio_fmt = '$' . number_format($db_prod['precio_oferta'], 0, ',', '.') . ' COP';
            }

            $cat_label = ucfirst($db_prod['categoria'] ?? 'Corte');
            $badg_lbl = $cat_label . (!empty($db_prod['etiqueta']) ? ' - ' . $db_prod['etiqueta'] : '');

            $p = [
                'nombre' => $db_prod['nombre'],
                'badge' => $badg_lbl,
                'badge_discount' => (!empty($db_prod['en_descuento']) && floatval($db_prod['descuento_porcentaje']) > 0) ? '-' . intval($db_prod['descuento_porcentaje']) . '% OFF' : null,
                'precio' => $precio_fmt,
                'precio_anterior' => $precio_ant_fmt,
                'imagen' => !empty($db_prod['imagen']) ? $db_prod['imagen'] : 'images/hero.jpg',
                'desc' => !empty($db_prod['descripcion']) ? $db_prod['descripcion'] : 'Producto fresco y seleccionado de la más alta calidad COPACARNES, preparado por nuestros maestros carniceros bajo estándares artesanales.'
            ];
        }
    } catch (Exception $e) {}
}

// 2. Fallback al catálogo estático si aplica
if (!$p && isset($productos_db[$id_producto])) {
    $p = $productos_db[$id_producto];
}

// 3. Fallback dinámico secundario si no fue encontrado
if (!$p) {
    $name_param = isset($_GET['name']) ? trim($_GET['name']) : 'Producto Copacarnes';
    $price_param = isset($_GET['price']) ? trim($_GET['price']) : '$35.000 COP';
    $p = [
        'nombre' => ucwords($name_param),
        'badge' => 'Selección Especial',
        'precio' => $price_param,
        'precio_anterior' => null,
        'imagen' => 'images/hero.jpg',
        'desc' => 'Producto fresco y seleccionado de la más alta calidad, preparado por nuestros maestros carniceros bajo rigurosos estándares de higiene y sabor.'
    ];
}

$page_title = $p['nombre'];
include __DIR__ . '/includes/header.php';
?>

<style>
.product-detail-section {
    padding: clamp(5.5rem, 8vw, 7.5rem) 1rem 4rem 1rem;
    min-height: 85vh;
}

.breadcrumb-nav {
    max-width: 1200px;
    margin: 0 auto 2rem auto;
    font-size: 0.9rem;
    color: var(--color-text-muted);
}

.breadcrumb-nav a {
    color: var(--color-gold);
    text-decoration: none;
}

.breadcrumb-nav a:hover {
    text-decoration: underline;
}

.product-detail-layout {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3.5rem;
    align-items: start;
}

.product-detail-media {
    position: relative;
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--color-border);
    background: var(--color-bg-card);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
}

.product-detail-media img {
    width: 100%;
    height: clamp(300px, 40vw, 480px);
    object-fit: cover;
    display: block;
}

.product-detail-info {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.product-detail-title {
    font-size: clamp(1.8rem, 3.5vw + 0.5rem, 2.5rem);
    color: #ffffff;
    margin: 0.5rem 0 0 0;
    line-height: 1.2;
}

.product-detail-price-box {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    background: rgba(20, 20, 20, 0.8);
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    border-left: 4px solid var(--color-gold);
    width: fit-content;
    max-width: 100%;
    flex-wrap: wrap;
}

.product-detail-price {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 800;
    color: var(--color-gold);
}

.product-detail-price-old {
    font-size: 1.2rem;
    color: var(--color-text-muted);
    text-decoration: line-through;
}

.product-detail-desc {
    font-size: 1.05rem;
    color: var(--color-text-muted);
    line-height: 1.7;
}

.detail-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

@media (max-width: 900px) {
    .product-detail-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

@media (max-width: 480px) {
    .detail-actions {
        flex-direction: column;
    }
    .detail-actions .btn {
        width: 100%;
        justify-content: center;
        min-height: 48px;
    }
}
</style>

<section class="product-detail-section">
    <div class="container">
        <!-- Navegación Breadcrumb -->
        <div class="breadcrumb-nav">
            <a href="index.php"><i class="fa-solid fa-house"></i> Inicio</a> &nbsp;/&nbsp; 
            <a href="productos.php">Catálogo de Productos</a> &nbsp;/&nbsp; 
            <span><?php echo htmlspecialchars($p['nombre']); ?></span>
        </div>

        <div class="product-detail-layout">
            <!-- Columna Izquierda: Imagen e Insignias -->
            <div class="product-detail-media">
                <img src="<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>">
                
                <?php if (isset($p['expira'])): ?>
                    <span style="position: absolute; top: 16px; left: 16px; background: rgba(10, 10, 10, 0.9); color: #d4af37; border: 1px solid #d4af37; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; backdrop-filter: blur(4px); z-index: 2;">
                        <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($p['expira']); ?>
                    </span>
                <?php endif; ?>

                <?php if (isset($p['badge_discount'])): ?>
                    <span style="position: absolute; top: 16px; right: 16px; background: var(--color-primary); color: #ffffff; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 700; z-index: 2;">
                        <?php echo htmlspecialchars($p['badge_discount']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Columna Derecha: Información del Producto -->
            <div class="product-detail-info">
                <div>
                    <span class="section-tag"><i class="fa-solid fa-tag text-gold"></i> <?php echo htmlspecialchars($p['badge']); ?></span>
                    <h1 class="product-detail-title"><?php echo htmlspecialchars($p['nombre']); ?></h1>
                </div>

                <div class="product-detail-price-box">
                    <?php if (!empty($p['precio_anterior'])): ?>
                        <span class="product-detail-price-old"><?php echo htmlspecialchars($p['precio_anterior']); ?></span>
                    <?php endif; ?>
                    <span class="product-detail-price"><?php echo htmlspecialchars($p['precio']); ?></span>
                </div>

                <p class="product-detail-desc"><?php echo htmlspecialchars($p['desc']); ?></p>

                <!-- Botones de Acción -->
                <div class="detail-actions">
                    <a href="index.php#contacto" class="btn btn-primary" style="padding: 0.9rem 1.8rem;">
                        <i class="fa-solid fa-calendar-check"></i> Solicitar / Reservar en Contacto
                    </a>
                    <a href="productos.php" class="btn btn-outline" style="padding: 0.9rem 1.5rem;">
                        <i class="fa-solid fa-arrow-left"></i> Volver al Catálogo
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
