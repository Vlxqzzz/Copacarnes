<?php
$page_title = "Dashboard Cocinero";
$active_menu = "cocinero";
$required_roles = ['dueno', 'admin', 'cocinero'];

require_once __DIR__ . '/includes/admin_header.php';

$cocinero_msg = '';

// PROCESAR CAMBIO SECUENCIAL DE ESTADO DE LA COMANDA EN COCINA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_comanda'])) {
    $comanda_id = intval($_POST['comanda_id'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'listo';

    if ($pdo && $comanda_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE comandas_cocina SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $comanda_id]);

            $label_txt = ($nuevo_estado === 'en_preparacion') ? 'EN PROCESO' : (($nuevo_estado === 'listo') ? 'TERMINADO / LISTO' : 'ENTREGADO');
            $cocinero_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-fire-burner"></i> Comanda #' . $comanda_id . ' actualizada a <strong>' . $label_txt . '</strong>.</div>';
        } catch (Exception $e) {
            $cocinero_msg = '<div class="alert alert-error">Error al cambiar estado de comanda: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// CONSULTAR TODAS LAS COMANDAS ACTIVAS DE COCINA DESDE BASE DE DATOS
$comandas_lista = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM comandas_cocina WHERE estado IN ('pendiente', 'en_preparacion', 'en_proceso') ORDER BY id DESC");
        $comandas_lista = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// PROCESAR GESTIÓN DEL MENÚ DEL DÍA POR LAS COCINERAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_menu_item_btn'])) {
    $item_id = intval($_POST['item_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $icono = trim($_POST['icono'] ?? 'fa-drumstick-bite');
    $horario = trim($_POST['horario_atencion'] ?? '11:30 AM - 3:00 PM');

    if ($pdo && !empty($titulo) && $precio > 0) {
        try {
            if ($item_id > 0) {
                $stmt = $pdo->prepare("UPDATE menu_del_dia SET titulo = ?, precio = ?, descripcion = ?, icono = ?, horario_atencion = ? WHERE id = ?");
                $stmt->execute([$titulo, $precio, $descripcion, $icono, $horario, $item_id]);
                $cocinero_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Menú del Día: <strong>' . htmlspecialchars($titulo) . '</strong> actualizado con éxito.</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO menu_del_dia (titulo, precio, descripcion, icono, horario_atencion) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $precio, $descripcion, $icono, $horario]);
                $cocinero_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Nuevo plato agregado al Menú del Día: <strong>' . htmlspecialchars($titulo) . '</strong>.</div>';
            }
        } catch (Exception $e) {
            $cocinero_msg = '<div class="alert alert-error">Error al guardar Menú del Día: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_menu_item_btn'])) {
    $item_id = intval($_POST['item_id'] ?? 0);
    if ($pdo && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM menu_del_dia WHERE id = ?");
            $stmt->execute([$item_id]);
            $cocinero_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Plato eliminado del Menú del Día.</div>';
        } catch (Exception $e) {}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_menu_item_btn'])) {
    $item_id = intval($_POST['item_id'] ?? 0);
    $nuevo_est = intval($_POST['nuevo_estado'] ?? 1);
    if ($pdo && $item_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE menu_del_dia SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_est, $item_id]);
        } catch (Exception $e) {}
    }
}

// CONSULTAR MENÚ DEL DÍA PARA EL DASHBOARD DE LAS COCINERAS
$menu_del_dia_lista = [];
if ($pdo) {
    try {
        $stmt_m = $pdo->query("SELECT * FROM menu_del_dia ORDER BY id ASC");
        $menu_del_dia_lista = $stmt_m->fetchAll();
    } catch (Exception $e) {}
}

// CONTEO DE INDICADORES KDS
$pendientes_cnt = 0;
$preparacion_cnt = 0;
$listos_cnt = 0;

foreach ($comandas_lista as $c) {
    if ($c['estado'] === 'en_preparacion') $preparacion_cnt++;
    elseif ($c['estado'] === 'listo' || $c['estado'] === 'entregado' || $c['estado'] === 'terminado') $listos_cnt++;
    else $pendientes_cnt++;
}
?>

<!-- Encabezado KDS de Cocina -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-utensils text-gold"></i> Dashboard Cocinero
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Pantalla en tiempo real de platos del restaurante, tiempos de cocción a la parrilla y comandas.
        </p>
    </div>
    <div style="display: flex; gap: 0.8rem;">
        <button onclick="location.reload()" class="btn-export" style="background: rgba(212, 175, 55, 0.2); border-color: var(--gold); color: var(--gold);">
            <i class="fa-solid fa-arrows-rotate"></i> Actualizar Pantalla KDS
        </button>
    </div>
</div>

<?php echo $cocinero_msg; ?>

<!-- Tarjetas KPI de Resumen KDS -->
<div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #ef4444;">
        <div class="kpi-title">Comandas Pendientes</div>
        <div class="kpi-value" style="color: #ef4444;"><?php echo $pendientes_cnt; ?> Comandas</div>
        <div class="kpi-sub" style="color: #ef4444;"><i class="fa-solid fa-clock"></i> Esperando Inicio de Cocción</div>
    </div>
    <div class="kpi-card" style="border-left-color: #f59e0b;">
        <div class="kpi-title">En Preparación / Fuego</div>
        <div class="kpi-value" style="color: #f59e0b;"><?php echo $preparacion_cnt; ?> En Parrilla</div>
        <div class="kpi-sub" style="color: #f59e0b;"><i class="fa-solid fa-fire-burner"></i> En Proceso de Cocción</div>
    </div>
    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title">Platos Terminados / Listos</div>
        <div class="kpi-value" style="color: #10b981;"><?php echo $listos_cnt; ?> Servidos</div>
        <div class="kpi-sub" style="color: #10b981;"><i class="fa-solid fa-circle-check"></i> Listos para Mesa o Entrega</div>
    </div>
</div>

<!-- SECCIÓN: EDICIÓN DEL MENÚ DEL DÍA (MÓDULO COCINERAS) -->
<div class="data-table-card" style="margin-bottom: 2.2rem; border-left: 4px solid var(--gold); background: rgba(15, 23, 42, 0.7);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.2rem; border-bottom: 1px dashed rgba(212,175,55,0.3); padding-bottom: 0.8rem;">
        <div>
            <h3 style="margin: 0; color: #fff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-utensils text-gold"></i> Gestión & Edición del Menú del Día (Almuerzo Ejecutivo)
            </h3>
            <span style="font-size:0.8rem; color:var(--text-muted);">Los cambios guardados aquí se actualizarán automáticamente en la página principal para los clientes.</span>
        </div>
        <button type="button" onclick="abrirModalNuevoMenu()" class="btn btn-gold" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-plus-circle"></i> + Agregar Plato al Menú
        </button>
    </div>

    <?php if (empty($menu_del_dia_lista)): ?>
        <p style="color:var(--text-muted); font-size:0.9rem; text-align:center; padding:1.5rem 0;">No hay platos registrados en el menú del día. Presiona el botón para agregar el primero.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1rem;">
            <?php foreach ($menu_del_dia_lista as $item_menu): ?>
            <div style="background:rgba(0,0,0,0.4); border:1px solid <?php echo $item_menu['activo'] ? 'var(--gold)' : 'rgba(255,255,255,0.1)'; ?>; padding:1rem; border-radius:10px; display:flex; flex-direction:column; justify-content:space-between; gap:0.8rem; opacity:<?php echo $item_menu['activo'] ? '1' : '0.6'; ?>;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                        <strong style="color:#fff; font-size:1.05rem; display:flex; align-items:center; gap:0.4rem;">
                            <i class="fa-solid <?php echo htmlspecialchars($item_menu['icono'] ?? 'fa-drumstick-bite'); ?> text-gold"></i>
                            <?php echo htmlspecialchars($item_menu['titulo']); ?>
                        </strong>
                        <strong style="color:var(--gold); font-size:1.1rem;">$<?php echo number_format($item_menu['precio'], 0, ',', '.'); ?> COP</strong>
                    </div>
                    <p style="margin:0; color:var(--text-muted); font-size:0.85rem; line-height:1.4;">
                        <?php echo htmlspecialchars($item_menu['descripcion']); ?>
                    </p>
                    <span style="font-size:0.75rem; color:#94a3b8; margin-top:0.4rem; display:block;">
                        <i class="fa-solid fa-clock"></i> Horario: <?php echo htmlspecialchars($item_menu['horario_atencion']); ?>
                    </span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid rgba(255,255,255,0.08); padding-top:0.6rem; margin-top:0.2rem;">
                    <!-- Toggle Activo / Inactivo -->
                    <form action="dashboard-cocinero.php" method="POST" style="margin:0;">
                        <input type="hidden" name="item_id" value="<?php echo $item_menu['id']; ?>">
                        <input type="hidden" name="nuevo_estado" value="<?php echo $item_menu['activo'] ? '0' : '1'; ?>">
                        <button type="submit" name="toggle_menu_item_btn" class="btn-export" style="font-size:0.78rem; padding:0.3rem 0.6rem; background:<?php echo $item_menu['activo'] ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)'; ?>; border-color:<?php echo $item_menu['activo'] ? '#10b981' : '#ef4444'; ?>; color:<?php echo $item_menu['activo'] ? '#34d399' : '#fca5a5'; ?>;">
                            <?php if ($item_menu['activo']): ?>
                                <i class="fa-solid fa-eye"></i> Visible en Web
                            <?php else: ?>
                                <i class="fa-solid fa-eye-slash"></i> Oculto en Web
                            <?php endif; ?>
                        </button>
                    </form>

                    <div style="display:flex; gap:0.4rem;">
                        <button type="button" onclick='abrirModalEditarMenu(<?php echo json_encode($item_menu, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn-export" style="font-size:0.78rem; padding:0.3rem 0.6rem; background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa;">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>

                        <form action="dashboard-cocinero.php" method="POST" onsubmit="return confirm('¿Eliminar <?php echo htmlspecialchars($item_menu['titulo']); ?> del menú del día?');" style="margin:0;">
                            <input type="hidden" name="item_id" value="<?php echo $item_menu['id']; ?>">
                            <button type="submit" name="eliminar_menu_item_btn" class="btn-export" style="font-size:0.78rem; padding:0.3rem 0.6rem; background:rgba(239,68,68,0.2); border-color:#ef4444; color:#fca5a5;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- TABLA DEDICADA DE RESERVAS DE RESTAURANTE PARA LAS COCINERAS -->
<?php
$reservas_cocina_lista = [];
$comandas_regulares_lista = [];
foreach ($comandas_lista as $c_item) {
    $m_t = strtolower($c_item['mesa_numero'] ?? '');
    $n_t = strtolower($c_item['notas'] ?? '');
    $p_t = strtolower($c_item['platillo_nombre'] ?? '');
    if (strpos($m_t, 'reserva') !== false || strpos($n_t, 'reserva') !== false || strpos($p_t, 'reserva') !== false) {
        $reservas_cocina_lista[] = $c_item;
    } else {
        $comandas_regulares_lista[] = $c_item;
    }
}
?>

<?php if (!empty($reservas_cocina_lista)): ?>
<div class="data-table-card" style="margin-bottom: 2rem; border-left: 4px solid #3b82f6; background: rgba(59, 130, 246, 0.08);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
        <h3 style="margin: 0; color: #60a5fa; font-size: 1.15rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="fa-solid fa-calendar-check text-gold"></i> 🍽️ Lista de Reservas de Restaurante (Enviadas por Cajera)
        </h3>
        <span class="status-pill warning" style="background:rgba(59,130,246,0.25); color:#60a5fa; border:1px solid #3b82f6; font-weight:800; font-size:0.82rem;">
            <?php echo count($reservas_cocina_lista); ?> Reserva(s) Activa(s)
        </span>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            <thead>
                <tr style="background:rgba(15,23,42,0.8); color:#60a5fa; font-size:0.8rem; text-transform:uppercase;">
                    <th style="padding:0.75rem;">Mesa / Asignación</th>
                    <th style="padding:0.75rem;">Detalles del Pedido / Menú</th>
                    <th style="padding:0.75rem;">Fecha y Hora</th>
                    <th style="padding:0.75rem; text-align:center;">Estado Cocina</th>
                    <th style="padding:0.75rem; text-align:right;">Acción Cocinera</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas_cocina_lista as $rc): 
                    $is_final = ($rc['estado'] === 'listo' || $rc['estado'] === 'entregado' || $rc['estado'] === 'terminado');
                    $is_prep = ($rc['estado'] === 'en_preparacion');
                    $st_badge = $is_final ? '<span class="status-pill success"><i class="fa-solid fa-circle-check"></i> LISTO MESA</span>' : ($is_prep ? '<span class="status-pill warning"><i class="fa-solid fa-fire"></i> EN PARRILLA</span>' : '<span class="status-pill error"><i class="fa-solid fa-clock"></i> PENDIENTE</span>');
                ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                    <td style="padding:0.75rem;">
                        <strong style="color:#fff; font-size:0.95rem; display:block;"><?php echo htmlspecialchars($rc['mesa_numero']); ?></strong>
                        <span style="font-size:0.75rem; color:#60a5fa; font-weight:700;"><i class="fa-solid fa-chair"></i> Reserva Confirmada</span>
                    </td>
                    <td style="padding:0.75rem; max-width:320px;">
                        <strong style="color:var(--gold); font-size:0.9rem; display:block;"><?php echo htmlspecialchars($rc['platillo_nombre']); ?></strong>
                        <span style="color:#94a3b8; font-size:0.82rem; line-height:1.3; display:block;"><?php echo htmlspecialchars($rc['notas']); ?></span>
                    </td>
                    <td style="padding:0.75rem; color:var(--text-muted); font-size:0.82rem;">
                        <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($rc['fecha_hora']); ?>
                    </td>
                    <td style="padding:0.75rem; text-align:center;">
                        <?php echo $st_badge; ?>
                    </td>
                    <td style="padding:0.75rem; text-align:right;">
                        <div style="display:flex; gap:0.4rem; justify-content:flex-end;">
                            <?php if (!$is_prep && !$is_final): ?>
                                <form action="dashboard-cocinero.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="comanda_id" value="<?php echo $rc['id']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="en_preparacion">
                                    <button type="submit" name="cambiar_estado_comanda" class="btn-export" style="background:rgba(245,158,11,0.2); border-color:#f59e0b; color:#fbbf24; font-size:0.78rem; font-weight:800; padding:0.35rem 0.6rem;">
                                        <i class="fa-solid fa-fire"></i> A Fuego
                                    </button>
                                </form>
                            <?php elseif ($is_prep): ?>
                                <form action="dashboard-cocinero.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="comanda_id" value="<?php echo $rc['id']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="listo">
                                    <button type="submit" name="cambiar_estado_comanda" class="btn-export" style="background:rgba(16,185,129,0.2); border-color:#10b981; color:#34d399; font-size:0.78rem; font-weight:800; padding:0.35rem 0.6rem;">
                                        <i class="fa-solid fa-check-double"></i> Listo Mesa
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:#34d399; font-size:0.8rem; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Servido</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- GRID KDS DE COMANDAS EN VIVO (CONECTADO A MYSQL) -->
<h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.6rem;">
    <i class="fa-solid fa-fire-flame-curved text-gold"></i> Pantalla de Comandas de Cocina & Asadero
</h3>

<?php if (empty($comandas_regulares_lista)): ?>
    <div class="data-table-card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
        <i class="fa-solid fa-utensils" style="font-size: 2.5rem; color: var(--gold); display: block; margin-bottom: 0.8rem;"></i>
        No hay comandas de pedidos activos en la cocina en este momento. Las órdenes despachadas aparecerán aquí automáticamente.
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem;">
        <?php foreach ($comandas_regulares_lista as $com): 
            $is_final = ($com['estado'] === 'listo' || $com['estado'] === 'entregado' || $com['estado'] === 'terminado');
            $is_prep = ($com['estado'] === 'en_preparacion');
            $border_color = $is_final ? '#10b981' : ($is_prep ? '#f59e0b' : '#ef4444');
            $num_comanda = !empty($com['numero_comanda']) ? $com['numero_comanda'] : ('COM-' . str_pad($com['id'], 3, '0', STR_PAD_LEFT));
            $plato_txt = !empty($com['platillo_nombre']) ? ($com['cantidad'] . 'x ' . $com['platillo_nombre']) : ($com['platos_detalles'] ?? 'Plato Asadero');
            $notas_txt = !empty($com['notas']) ? $com['notas'] : ($com['observaciones'] ?? 'Sin especificaciones');
            $mesa_origen = !empty($com['mesa_numero']) ? $com['mesa_numero'] : ($com['mesa'] ?? 'Servicio');
        ?>
        <div class="data-table-card" style="margin:0; border-left: 4px solid <?php echo $border_color; ?>; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                    <span style="font-weight: 800; font-size: 1.1rem; color: #fff;">
                        <?php echo htmlspecialchars($mesa_origen); ?> &bull; <code><?php echo htmlspecialchars($num_comanda); ?></code>
                    </span>
                    <!-- Botón para abrir vista detallada -->
                    <button type="button" onclick='abrirModalDetalleComanda(<?php echo json_encode($com, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn-export" style="padding:0.3rem 0.65rem; background:rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa; font-weight:700; font-size:0.78rem;" title="Ver Detalle Completo">
                        <i class="fa-solid fa-eye"></i> Detalle
                    </button>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.8rem; line-height: 1.5;">
                    <i class="fa-solid fa-clock"></i> <span style="color:#fff;"><?php echo htmlspecialchars($com['fecha_hora']); ?></span><br>
                    <?php 
                    $is_reserva = (strpos(strtolower($mesa_origen), 'reserva') !== false || strpos(strtolower($notas_txt), 'reserva') !== false);
                    $is_dom_coc = (strpos($notas_txt, '[Entrega a Domicilio') !== false || strpos(strtolower($notas_txt), 'domicilio') !== false || strpos(strtolower($plato_txt), 'domicilio') !== false);
                    ?>
                    <?php if ($is_reserva): ?>
                        <span class="product-badge" style="background:rgba(59,130,246,0.25); color:#60a5fa; border:1px solid #3b82f6; margin-top:0.4rem; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; font-weight:800; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-calendar-check"></i> 🍽️ RESERVA DE RESTAURANTE</span>
                    <?php elseif ($is_dom_coc): ?>
                        <span class="product-badge" style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid #10b981; margin-top:0.4rem; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-motorcycle"></i> ENTREGA A DOMICILIO</span>
                    <?php else: ?>
                        <span class="product-badge" style="background:rgba(212,175,55,0.2); color:var(--gold); border:1px solid var(--gold); margin-top:0.4rem; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-store"></i> RECOGER EN SEDE / RESTAURANTE</span>
                    <?php endif; ?>
                </div>

                <div style="background: rgba(0,0,0,0.5); padding: 0.9rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 1rem;">
                    <strong style="color: var(--gold); display: block; font-size: 1rem; margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($plato_txt); ?>
                    </strong>
                    <p style="margin:0; color:#fff; font-size:0.88rem; line-height:1.4;">
                        <i class="fa-solid fa-circle-info" style="color:var(--text-muted);"></i> <?php echo htmlspecialchars($notas_txt); ?>
                    </p>
                </div>
            </div>

            <!-- Botones de Acción Secuenciales para el Cocinero -->
            <div style="margin-top: 0.8rem;">
                <?php if (!$is_prep && !$is_final): ?>
                    <form action="dashboard-cocinero.php" method="POST" style="width: 100%;">
                        <input type="hidden" name="comanda_id" value="<?php echo $com['id']; ?>">
                        <input type="hidden" name="nuevo_estado" value="en_preparacion">
                        <button type="submit" name="cambiar_estado_comanda" class="btn-export" style="width: 100%; justify-content: center; background: rgba(245, 158, 11, 0.2); border-color: #f59e0b; color: #fbbf24; padding: 0.65rem; font-weight: 800; font-size:0.95rem;">
                            <i class="fa-solid fa-fire"></i> Pasar a EN PROCESO
                        </button>
                    </form>
                <?php elseif ($is_prep): ?>
                    <?php if ($is_dom_coc): ?>
                        <form action="dashboard-cocinero.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="comanda_id" value="<?php echo $com['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="listo_domicilio">
                            <button type="submit" name="cambiar_estado_comanda" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #10b981, #047857); border-color: #10b981; color: #fff; padding: 0.75rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-paper-plane"></i> 🛵 MARCAR COMO TERMINADO (Enviar a Central para Domicilio)
                            </button>
                        </form>
                    <?php else: ?>
                        <form action="dashboard-cocinero.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="comanda_id" value="<?php echo $com['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="listo">
                            <button type="submit" name="cambiar_estado_comanda" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #d4af37, #997a15); border-color: var(--gold); color: #000; padding: 0.75rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-store"></i> 🏬 MARCAR COMO TERMINADO (Listo para Recoger en Sede)
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($is_dom_coc): ?>
                        <div style="text-align: center; color: #34d399; font-weight: 800; font-size: 0.9rem; padding: 0.6rem; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; border-radius: 6px;">
                            <i class="fa-solid fa-motorcycle"></i> Plato Terminado - Enviado a Central (Para Domicilio)
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--gold); font-weight: 800; font-size: 0.9rem; padding: 0.6rem; background: rgba(212, 175, 55, 0.15); border: 1px solid var(--gold); border-radius: 6px;">
                            <i class="fa-solid fa-store"></i> Plato Terminado - Listo para Recoger en Sede
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- MODAL: VISTA DETALLADA COMPLETA DE LA COMANDA -->
<div id="modalDetalleComanda" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:600px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;" id="modal_det_titulo"><i class="fa-solid fa-utensils text-gold"></i> Detalle Completo de la Comanda</h3>
            <button onclick="document.getElementById('modalDetalleComanda').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <div style="display:flex; flex-direction:column; gap:1rem;">
            <!-- Tipo / Origen del Pedido (Mesa, Web o Domicilio) -->
            <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:8px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:0.8rem; color:var(--text-muted); display:block;">Canal / Origen del Pedido:</span>
                    <strong style="font-size:1.1rem; color:#fff;" id="modal_det_origen">Mesa 4</strong>
                </div>
                <div id="modal_det_tipo_badge">
                    <span class="status-pill warning"><i class="fa-solid fa-utensils"></i> Servicio en Mesa</span>
                </div>
            </div>

            <!-- Hora Exacta & Tiempo Estimado -->
            <div style="background:rgba(0,0,0,0.3); padding:0.9rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:0.8rem; color:var(--text-muted); display:block;"><i class="fa-solid fa-clock text-gold"></i> Hora de Recepción:</span>
                    <strong style="color:#fff; font-size:0.95rem;" id="modal_det_hora">2026-08-02 17:02:27</strong>
                </div>
                <div>
                    <span style="font-size:0.8rem; color:var(--text-muted); display:block;"><i class="fa-solid fa-stopwatch text-gold"></i> Tiempo Estimado:</span>
                    <strong style="color:var(--gold); font-size:0.95rem;" id="modal_det_tiempo">20 min</strong>
                </div>
            </div>

            <!-- Platillo y Especificaciones -->
            <div style="background:rgba(0,0,0,0.5); padding:1.2rem; border-radius:8px; border:1px solid var(--border-color);">
                <span style="font-size:0.8rem; color:var(--gold); font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.4rem;">Plato o Preparación Requerida:</span>
                <h4 style="margin:0 0 0.6rem 0; color:#fff; font-size:1.15rem;" id="modal_det_plato">1x Tomahawk 1kg a la Parrilla</h4>
                
                <span style="font-size:0.8rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.3rem;">Especificaciones & Notas de Cocina:</span>
                <p style="margin:0; color:#e2e8f0; font-size:0.92rem; line-height:1.5;" id="modal_det_notas">Término Medio. Papa al horno y chimichurri extra.</p>
            </div>

            <!-- Estado de la Comanda -->
            <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3); padding:0.8rem 1rem; border-radius:8px;">
                <span style="font-size:0.85rem; color:var(--text-muted);">Estado Actual en Cocina:</span>
                <span id="modal_det_estado_badge"><span class="status-pill danger">PENDIENTE</span></span>
            </div>
        </div>

    </div>
</div>

<script>
function abrirModalDetalleComanda(com) {
    const numCom = com.numero_comanda || ('COM-' + String(com.id).padStart(3, '0'));
    const origen = com.mesa_numero || com.mesa || 'Servicio General';
    const plato = (com.cantidad ? com.cantidad + 'x ' : '') + (com.platillo_nombre || com.platos_detalles || 'Plato Asadero');
    const notas = com.notas || com.observaciones || 'Sin especificaciones adicionales.';
    const hora = com.fecha_hora || 'Fecha no registrada';
    const tiempo = (com.tiempo_estimado_min || 20) + ' min';

    const elemTit = document.getElementById('modal_det_titulo'); if (elemTit) elemTit.innerHTML = '<i class="fa-solid fa-utensils text-gold"></i> Detalle Completo &bull; <code>' + numCom + '</code>';
    const elemOrg = document.getElementById('modal_det_origen'); if (elemOrg) elemOrg.innerText = origen;
    const elemHor = document.getElementById('modal_det_hora'); if (elemHor) elemHor.innerText = hora;
    const elemTmp = document.getElementById('modal_det_tiempo'); if (elemTmp) elemTmp.innerText = tiempo;
    const elemPla = document.getElementById('modal_det_plato'); if (elemPla) elemPla.innerText = plato;
    const elemNot = document.getElementById('modal_det_notas'); if (elemNot) elemNot.innerText = notas;

    // Determinar badge del tipo/origen del pedido
    const badgeContainer = document.getElementById('modal_det_tipo_badge');
    if (origen.toLowerCase().includes('domicilio')) {
        badgeContainer.innerHTML = '<span class="status-pill danger"><i class="fa-solid fa-motorcycle"></i> Pedido a Domicilio</span>';
    } else if (origen.toLowerCase().includes('mesa')) {
        badgeContainer.innerHTML = '<span class="status-pill warning"><i class="fa-solid fa-utensils"></i> Servicio en ' + origen + '</span>';
    } else {
        badgeContainer.innerHTML = '<span class="status-pill success"><i class="fa-solid fa-globe"></i> Solicitud Web / Reserva</span>';
    }

    // Determinar badge de estado
    const estadoContainer = document.getElementById('modal_det_estado_badge');
    if (com.estado === 'listo' || com.estado === 'entregado' || com.estado === 'terminado') {
        estadoContainer.innerHTML = '<span class="status-pill success"><i class="fa-solid fa-circle-check"></i> TERMINADO / LISTO</span>';
    } else if (com.estado === 'en_preparacion') {
        estadoContainer.innerHTML = '<span class="status-pill warning"><i class="fa-solid fa-fire"></i> EN PROCESO DE COCCIÓN</span>';
    } else {
        estadoContainer.innerHTML = '<span class="status-pill danger"><i class="fa-solid fa-clock"></i> PENDIENTE</span>';
    }

    document.getElementById('modalDetalleComanda').style.display = 'flex';
}
</script>

<!-- MODAL OVERLAY: GESTIÓN & EDICIÓN DEL MENÚ DEL DÍA (COCINERAS) -->
<div id="modalGestionMenuDia" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center; padding:1rem; box-sizing:border-box;">
    <div style="max-width:550px; width:100%; background:#0d1630; border:1.5px solid var(--gold); border-radius:16px; padding:1.5rem; box-shadow:0 20px 50px rgba(0,0,0,0.8);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(212,175,55,0.3); padding-bottom:0.8rem; margin-bottom:1rem;">
            <h3 style="margin:0; color:#fff; font-size:1.15rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;" id="modal_menu_form_titulo">
                <i class="fa-solid fa-pen-to-square text-gold"></i> Formulario Menú del Día
            </h3>
            <button type="button" onclick="document.getElementById('modalGestionMenuDia').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.8rem; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>

        <form action="dashboard-cocinero.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="menu_item_id" name="item_id" value="0">
            <input type="hidden" id="menu_item_icono" name="icono" value="fa-utensils">
            <input type="hidden" id="menu_item_horario" name="horario_atencion" value="11:30 AM - 3:00 PM">

            <!-- Título del Plato -->
            <div>
                <label style="font-size:0.85rem; color:#fff; font-weight:700; display:block; margin-bottom:0.35rem;">
                    <i class="fa-solid fa-utensils text-gold"></i> Título / Nombre del Plato:
                </label>
                <input type="text" id="menu_item_titulo" name="titulo" required class="form-control" style="width:100%; box-sizing:border-box; font-size:0.9rem; padding:0.6rem 0.8rem; background:#07090e !important; color:#fff !important; border:1px solid var(--gold) !important; border-radius:8px;" placeholder="Ej: Churrasco Ejecutivo al Carbón">
            </div>

            <!-- Precio COP ($) -->
            <div>
                <label style="font-size:0.85rem; color:#fff; font-weight:700; display:block; margin-bottom:0.35rem;">
                    <i class="fa-solid fa-sack-dollar text-gold"></i> Precio COP ($):
                </label>
                <input type="number" id="menu_item_precio" name="precio" required step="500" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.9rem; padding:0.6rem 0.8rem; background:#07090e !important; color:#fff !important; border:1px solid var(--gold) !important; border-radius:8px;" placeholder="18000">
            </div>

            <!-- Descripción & Acompañamientos -->
            <div>
                <label style="font-size:0.85rem; color:#fff; font-weight:700; display:block; margin-bottom:0.35rem;">
                    <i class="fa-solid fa-align-left text-gold"></i> Descripción & Acompañamientos:
                </label>
                <textarea id="menu_item_descripcion" name="descripcion" rows="3" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.88rem; padding:0.6rem; background:#07090e !important; color:#fff !important; border:1px solid var(--gold) !important; border-radius:8px; resize:vertical;" placeholder="Ej: Sopa del día + Corte a la parrilla + Papa cocida/Yuca + Ensalada fresca + Jugo de fruta natural."></textarea>
            </div>

            <!-- Botones Guardar / Cancelar -->
            <div style="display:flex; justify-content:flex-end; gap:0.8rem; margin-top:0.5rem; border-top:1px solid rgba(255,255,255,0.1); padding-top:0.9rem;">
                <button type="button" onclick="document.getElementById('modalGestionMenuDia').style.display='none'" class="btn btn-export" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.25); color:#fff; font-size:0.85rem; padding:0.5rem 1rem;">Cancelar</button>
                <button type="submit" name="guardar_menu_item_btn" class="btn btn-gold" style="padding:0.55rem 1.4rem; font-size:0.88rem; font-weight:800; background:linear-gradient(135deg, #d4af37, #997a15); border:none; color:#000; border-radius:8px; cursor:pointer;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Plato
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevoMenu() {
    document.getElementById('modal_menu_form_titulo').innerHTML = '<i class="fa-solid fa-plus-circle text-gold"></i> Agregar Nuevo Plato al Menú del Día';
    document.getElementById('menu_item_id').value = '0';
    document.getElementById('menu_item_titulo').value = '';
    document.getElementById('menu_item_precio').value = '18000';
    document.getElementById('menu_item_descripcion').value = '';
    document.getElementById('modalGestionMenuDia').style.display = 'flex';
}

function abrirModalEditarMenu(item) {
    document.getElementById('modal_menu_form_titulo').innerHTML = '<i class="fa-solid fa-pen-to-square text-gold"></i> Editar Plato del Menú del Día';
    document.getElementById('menu_item_id').value = item.id || 0;
    document.getElementById('menu_item_titulo').value = item.titulo || '';
    document.getElementById('menu_item_precio').value = item.precio || 0;
    document.getElementById('menu_item_descripcion').value = item.descripcion || '';
    document.getElementById('modalGestionMenuDia').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
