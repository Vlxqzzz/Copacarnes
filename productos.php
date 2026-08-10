<?php
require_once __DIR__ . '/config/db.php';
$page_title = "Catálogo de Productos";
include __DIR__ . '/includes/header.php';

$all_prods = [];
if ($pdo) {
    try {
        $stmt_cat = $pdo->query("SELECT * FROM productos WHERE (estado IS NULL OR estado = 'activo') ORDER BY id ASC");
        $all_prods = $stmt_cat->fetchAll();
    } catch (Exception $e) {}
}
?>

<style>
.catalog-section {
    padding: 7.5rem 1.5rem 5rem 1.5rem;
    min-height: 85vh;
}

.catalog-header {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 3rem auto;
}

.catalog-controls {
    max-width: 1000px;
    margin: 0 auto 3.5rem auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    align-items: center;
}

.search-box-wrapper {
    position: relative;
    width: 100%;
    max-width: 600px;
}

.search-box-input {
    width: 100%;
    height: 52px;
    padding: 0 1.5rem 0 3.2rem;
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid var(--color-gold);
    border-radius: 30px;
    color: #ffffff;
    font-size: 1rem;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
    outline: none;
    transition: all 0.25s ease;
}

.search-box-input:focus {
    border-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.3);
    background: rgba(15, 23, 42, 0.95);
}

.search-box-icon {
    position: absolute;
    left: 1.2rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-gold);
    font-size: 1.1rem;
    pointer-events: none;
}

.filter-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    justify-content: center;
}

.filter-pill {
    padding: 0.6rem 1.4rem;
    border-radius: 25px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid var(--color-border);
    color: var(--color-text-muted);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-pill:hover {
    border-color: var(--color-gold);
    color: #ffffff;
    transform: translateY(-2px);
}

.filter-pill.active {
    background: linear-gradient(135deg, #8b0000, #5c0000);
    border-color: var(--color-gold);
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(139, 0, 0, 0.4);
}

.badge-discount-pill {
    background: rgba(139, 0, 0, 0.4) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    color: #fca5a5 !important;
}

.badge-discount-pill.active {
    background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
    border-color: #f87171 !important;
    color: #ffffff !important;
}

.catalog-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.product-item-card {
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    position: relative;
}

.product-item-card:hover {
    transform: translateY(-7px);
    border-color: var(--color-gold);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(212, 175, 55, 0.15);
}

.product-item-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
}

.product-item-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0, 0, 0, 0.8);
    color: var(--color-gold);
    border: 1px solid var(--color-gold);
    padding: 0.3rem 0.7rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    backdrop-filter: blur(4px);
}

.product-item-badge-discount {
    background: #8b0000 !important;
    color: #ffffff !important;
    border-color: #ef4444 !important;
}

.product-item-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-item-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #ffffff;
}

.product-item-desc {
    font-size: 0.88rem;
    color: var(--color-text-muted);
    margin: 0 0 1.2rem 0;
    line-height: 1.5;
    flex-grow: 1;
}

.product-item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.product-item-price {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--color-gold);
}

.product-item-price-old {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    text-decoration: line-through;
    margin-right: 0.4rem;
}

.no-results-box {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 1rem;
    color: var(--color-text-muted);
    font-size: 1.1rem;
    display: none;
}
</style>

<section class="catalog-section">
    <div class="container">
        <div class="catalog-header">
            <span class="section-tag"><i class="fa-solid fa-store text-gold"></i> Carnicería & Asadero</span>
            <h1 class="section-title">Catálogo Completo de Productos</h1>
            <p class="section-subtitle">Explora nuestra selección completa de carnes frescas de res, cerdo, pollo, embutidos artesanales, adicionales y combos familiares.</p>
            
            <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                <a href="index.php#productos" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.6rem; padding: 0.65rem 1.4rem; font-size: 0.9rem; border-color: rgba(212, 175, 55, 0.5); color: #d4af37; border-radius: 20px;">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
                </a>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros -->
        <div class="catalog-controls">
            <div class="search-box-wrapper">
                <i class="fa-solid fa-magnifying-glass search-box-icon"></i>
                <input type="text" id="productSearch" class="search-box-input" placeholder="Buscar por nombre (ej. Tomahawk, Chicharrón, Combo)..." onkeyup="filterProducts()">
            </div>

            <div class="filter-categories">
                <button class="filter-pill active" onclick="setCategory('all', this)"><i class="fa-solid fa-border-all"></i> Todos</button>
                <button class="filter-pill" onclick="setCategory('res', this)"><i class="fa-solid fa-cow"></i> Res</button>
                <button class="filter-pill" onclick="setCategory('cerdo', this)"><i class="fa-solid fa-piggy-bank"></i> Cerdo</button>
                <button class="filter-pill" onclick="setCategory('pollo', this)"><i class="fa-solid fa-drumstick-bite"></i> Pollo</button>
                <button class="filter-pill" onclick="setCategory('embutidos', this)"><i class="fa-solid fa-joint"></i> Embutidos</button>
                <button class="filter-pill" onclick="setCategory('arepas', this)"><i class="fa-solid fa-cookie"></i> Arepas</button>
                <button class="filter-pill" onclick="setCategory('extras', this)"><i class="fa-solid fa-bottle-droplet"></i> Extras</button>
                <button class="filter-pill" onclick="setCategory('combos', this)"><i class="fa-solid fa-box-open"></i> Combos</button>
            </div>
        </div>

        <!-- Grilla de Productos Dinámica desde la BD MySQL -->
        <div class="catalog-products-grid" id="productsGrid">
            <?php if (empty($all_prods)): ?>
                <div class="no-results-box" style="display:block;">No hay productos disponibles en el catálogo.</div>
            <?php else: ?>
                <?php foreach ($all_prods as $p): ?>
                <div class="product-item-card" data-category="<?php echo htmlspecialchars($p['categoria']); ?>" data-name="<?php echo htmlspecialchars(strtolower($p['nombre'])); ?>" onclick="window.location.href='producto-detalle.php?id=<?php echo $p['slug']; ?>';" style="cursor: pointer;">
                    <div style="position: relative;">
                        <img src="<?php echo htmlspecialchars($p['imagen']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" class="product-item-img">
                        <span class="product-item-badge"><?php echo htmlspecialchars(strtoupper($p['categoria'])); ?> - <?php echo htmlspecialchars($p['etiqueta'] ?: 'Fresco'); ?></span>
                    </div>
                    <div class="product-item-body">
                        <h3 class="product-item-title"><a href="producto-detalle.php?id=<?php echo $p['slug']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($p['nombre']); ?></a></h3>
                        <p class="product-item-desc"><?php echo htmlspecialchars($p['descripcion']); ?></p>
                        <div class="product-item-footer">
                            <span class="product-item-price">$ <?php echo number_format($p['precio'], 0, ',', '.'); ?> COP</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="no-results-box" id="noResults">
                <i class="fa-solid fa-circle-question" style="font-size: 2.5rem; color: var(--color-gold); margin-bottom: 1rem;"></i>
                <div>No se encontraron productos que coincidan con tu búsqueda.</div>
            </div>
        </div>
    </div>
</section>

<script>
let currentCategory = 'all';

function setCategory(category, button) {
    currentCategory = category;
    
    // Actualizar estados visuales de los botones
    document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');

    filterProducts();
}

function filterProducts() {
    const searchInput = document.getElementById('productSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-item-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cardCategory = card.getAttribute('data-category') || '';
        const cardName = card.getAttribute('data-name') || '';

        const matchesCategory = (currentCategory === 'all') || cardCategory.includes(currentCategory);
        const matchesSearch = (searchInput === '') || cardName.includes(searchInput);

        if (matchesCategory && matchesSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const noResults = document.getElementById('noResults');
    if (visibleCount === 0 && cards.length > 0) {
        noResults.style.display = 'block';
    } else {
        noResults.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
