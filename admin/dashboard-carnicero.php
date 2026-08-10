<?php
$page_title = "Dashboard Carnicero";
$active_menu = "carnicero";
$required_roles = ['dueno', 'admin', 'carnicero'];

require_once __DIR__ . '/includes/admin_header.php';

$carnicero_msg = '';

// PROCESAR CAMBIO SECUENCIAL DE ESTADO DE LA ÓRDEN DE CARNICERÍA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_corte'])) {
    $orden_id = intval($_POST['orden_id'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'finalizado';

    if ($pdo && $orden_id > 0) {
        try {
            if ($nuevo_estado === 'en_preparacion' || $nuevo_estado === 'en_corte' || $nuevo_estado === 'en_proceso') {
                // Verificar si la orden ya tiene un carnicero asignado específicamente
                $stmt_chk = $pdo->prepare("SELECT carnicero_id, carnicero_nombre FROM ordenes_carniceria WHERE id = ?");
                $stmt_chk->execute([$orden_id]);
                $ord_chk = $stmt_chk->fetch();

                $is_disponible = (!$ord_chk || empty($ord_chk['carnicero_id']) || $ord_chk['carnicero_nombre'] === 'Carnicero Disponible' || empty($ord_chk['carnicero_nombre']));

                if ($is_disponible) {
                    // Tomar posesión de la orden solo si decía 'Carnicero Disponible'
                    $stmt = $pdo->prepare("UPDATE ordenes_carniceria SET estado = ?, carnicero_id = ?, carnicero_nombre = ? WHERE id = ?");
                    $stmt->execute([$nuevo_estado, $user['id'], $user['nombre'], $orden_id]);
                    $asig_nombre = $user['nombre'];
                } else {
                    // Mantener intacto el carnicero asignado previamente (ej: Mario, Jorge, etc.)
                    $stmt = $pdo->prepare("UPDATE ordenes_carniceria SET estado = ? WHERE id = ?");
                    $stmt->execute([$nuevo_estado, $orden_id]);
                    $asig_nombre = $ord_chk['carnicero_nombre'];
                }
            } else {
                $stmt = $pdo->prepare("UPDATE ordenes_carniceria SET estado = ? WHERE id = ?");
                $stmt->execute([$nuevo_estado, $orden_id]);
                $asig_nombre = $user['nombre'];
            }

            $estado_label = ($nuevo_estado === 'en_preparacion' || $nuevo_estado === 'en_proceso') ? 'EN PROCESO' : 'TERMINADO';
            $carnicero_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Orden #' . $orden_id . ' (asignada a <strong>' . htmlspecialchars($asig_nombre) . '</strong>) actualizada a <strong>' . $estado_label . '</strong>.</div>';
        } catch (Exception $e) {
            $carnicero_msg = '<div class="alert alert-error">Error al cambiar estado: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// OBTENER ÓRDENES DE CARNICERÍA
$ordenes_carnicero = [];
if ($pdo) {
    try {
        if ($user['rol'] === 'carnicero') {
            $user_id = $user['id'];
            $user_nom = $user['nombre'];
            $user_first_name = trim(explode('(', $user_nom)[0]);
            
            // 1. Pedidos asignados a este carnicero o Carnicero Disponible solo en estado pendiente o en preparacion/corte
            $stmt = $pdo->prepare("SELECT * FROM ordenes_carniceria WHERE ((carnicero_id = ? OR carnicero_nombre LIKE ? OR carnicero_nombre LIKE ?) OR ( (carnicero_id IS NULL OR carnicero_id = 0 OR carnicero_nombre = 'Carnicero Disponible' OR carnicero_nombre = '' OR carnicero_nombre IS NULL) AND estado = 'pendiente' )) AND estado IN ('pendiente', 'en_preparacion', 'en_corte') ORDER BY id DESC");
            $stmt->execute([$user_id, '%' . $user_nom . '%', '%' . $user_first_name . '%']);
        } else {
            $stmt = $pdo->query("SELECT * FROM ordenes_carniceria WHERE estado IN ('pendiente', 'en_preparacion', 'en_corte') ORDER BY id DESC");
        }
        $ordenes_carnicero = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// CONTEO POR ESTADOS
$pendientes_cnt = 0;
$preparacion_cnt = 0;
$finalizados_cnt = 0;

foreach ($ordenes_carnicero as $o) {
    if ($o['estado'] === 'en_preparacion' || $o['estado'] === 'en_corte' || $o['estado'] === 'en_proceso') $preparacion_cnt++;
    elseif ($o['estado'] === 'finalizado' || $o['estado'] === 'listo' || $o['estado'] === 'terminado') $finalizados_cnt++;
    else $pendientes_cnt++;
}
?>

<!-- Encabezado del Carnicero -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-drumstick-bite text-gold"></i> Dashboard Carnicero
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Recepción de órdenes de cortes de carne despachadas por la Central de Pedidos.
        </p>
    </div>
    <div style="display: flex; gap: 0.8rem;">
        <button onclick="location.reload()" class="btn-export" style="background: rgba(212, 175, 55, 0.2); border-color: var(--gold); color: var(--gold);">
            <i class="fa-solid fa-arrows-rotate"></i> Actualizar Órdenes
        </button>
    </div>
</div>

<?php echo $carnicero_msg; ?>

<!-- Tarjetas KPI de Resumen de Órdenes -->
<div class="kpi-grid">
    <div class="kpi-card" style="border-left-color: #ef4444;">
        <div class="kpi-title">Órdenes Pendientes</div>
        <div class="kpi-value" style="color: #ef4444;"><?php echo $pendientes_cnt; ?> Órdenes</div>
        <div class="kpi-sub" style="color: #ef4444;"><i class="fa-solid fa-clock"></i> Por Iniciar Preparación</div>
    </div>
    <div class="kpi-card" style="border-left-color: #f59e0b;">
        <div class="kpi-title">En Proceso / Corte</div>
        <div class="kpi-value" style="color: #f59e0b;"><?php echo $preparacion_cnt; ?> Órdenes</div>
        <div class="kpi-sub" style="color: #f59e0b;"><i class="fa-solid fa-scissors"></i> En Mesa de Desposte</div>
    </div>
    <div class="kpi-card" style="border-left-color: #10b981;">
        <div class="kpi-title">Cortes Terminados</div>
        <div class="kpi-value" style="color: #10b981;"><?php echo $finalizados_cnt; ?> Órdenes</div>
        <div class="kpi-sub" style="color: #10b981;"><i class="fa-solid fa-circle-check"></i> Listos para Entrega</div>
    </div>
</div>

<!-- TARJETAS DE ÓRDENES EN TIEMPO REAL (ESTILO KDS PARA CARNICERÍA) -->
<h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.6rem;">
    <i class="fa-solid fa-list-check text-gold"></i> Órdenes de Preparación Asignadas
</h3>

<?php if (empty($ordenes_carnicero)): ?>
    <div class="data-table-card" style="text-align: center; padding: 3rem; color: var(--text-muted);">
        <i class="fa-solid fa-inbox" style="font-size: 2.5rem; color: var(--gold); display: block; margin-bottom: 0.8rem;"></i>
        No tienes órdenes de desposte pendientes en este momento. Las nuevas órdenes enviadas aparecerán aquí.
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem;">
        <?php foreach ($ordenes_carnicero as $ord): 
            $is_final = ($ord['estado'] === 'finalizado' || $ord['estado'] === 'listo' || $ord['estado'] === 'terminado');
            $is_prep = ($ord['estado'] === 'en_preparacion' || $ord['estado'] === 'en_corte' || $ord['estado'] === 'en_proceso');
            $border_color = $is_final ? '#10b981' : ($is_prep ? '#f59e0b' : '#ef4444');
            $num_orden = !empty($ord['numero_orden']) ? $ord['numero_orden'] : ('ORD-CAR-' . str_pad($ord['id'], 3, '0', STR_PAD_LEFT));
            $kilos_val = !empty($ord['kilos_solicitados']) ? $ord['kilos_solicitados'] : ($ord['kilos'] ?? 1);
            $especificacion = !empty($ord['corte_especificacion']) ? $ord['corte_especificacion'] : ($ord['corte_detalle'] ?? 'Corte Especial');
            $is_domicilio = (strpos($especificacion, '[Entrega a Domicilio') !== false || strpos(strtolower($especificacion), 'domicilio') !== false);
        ?>
        <div class="data-table-card" style="margin:0; border-left: 4px solid <?php echo $border_color; ?>; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                    <span style="font-weight: 800; font-size: 1.1rem; color: #fff;">
                        <code><?php echo htmlspecialchars($num_orden); ?></code>
                    </span>
                    <?php if ($is_final): ?>
                        <span class="status-pill success"><i class="fa-solid fa-circle-check"></i> TERMINADO</span>
                    <?php elseif ($is_prep): ?>
                        <span class="status-pill warning"><i class="fa-solid fa-scissors"></i> EN PROCESO</span>
                    <?php else: ?>
                        <span class="status-pill danger"><i class="fa-solid fa-clock"></i> PENDIENTE</span>
                    <?php endif; ?>
                </div>

                <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 0.8rem; line-height: 1.5;">
                    <i class="fa-solid fa-user text-gold"></i> Cliente: <strong style="color:#fff;"><?php echo htmlspecialchars($ord['cliente_nombre']); ?></strong>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin:0.4rem 0;">
                        <?php 
                        $car_foto = ($ord['carnicero_nombre'] === 'Carnicero Disponible' || empty($ord['carnicero_nombre'])) ? '../images/carnicero-disponible.png' : get_avatar_url($ord['carnicero_avatar'] ?? '');
                        ?>
                        <img src="<?php echo htmlspecialchars($car_foto); ?>" style="width:26px; height:26px; border-radius:50%; object-fit:cover; border:1.5px solid var(--gold); background:#111;" alt="Foto">
                        <span>Carnicero Asignado: <strong style="color:#60a5fa;"><?php echo htmlspecialchars($ord['carnicero_nombre'] ?: 'Carnicero Disponible'); ?></strong></span>
                    </div>
                    <i class="fa-solid fa-clock"></i> Recibido: <?php echo htmlspecialchars($ord['fecha_hora']); ?><br>
                    <?php if ($is_domicilio): ?>
                        <span class="product-badge" style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid #10b981; margin-top:0.4rem; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-motorcycle"></i> ENTREGA A DOMICILIO</span>
                    <?php else: ?>
                        <span class="product-badge" style="background:rgba(212,175,55,0.2); color:var(--gold); border:1px solid var(--gold); margin-top:0.4rem; padding:0.25rem 0.65rem; border-radius:4px; font-size:0.75rem; display:inline-flex; align-items:center; gap:0.3rem;"><i class="fa-solid fa-store"></i> RECOGER EN SEDE</span>
                    <?php endif; ?>
                </div>

                <div style="background: rgba(0,0,0,0.5); padding: 0.9rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 1rem;">
                    <strong style="color: var(--gold); display: block; font-size: 0.95rem; margin-bottom: 0.4rem;">
                        <i class="fa-solid fa-drumstick-bite"></i> Detalles & Especificación de Corte:
                    </strong>
                    <p style="margin:0; color:#fff; font-size:0.9rem; line-height:1.4;">
                        <?php echo htmlspecialchars($especificacion); ?>
                    </p>
                </div>
            </div>

            <!-- Botones de Acción Secuenciales para el Carnicero -->
            <div style="margin-top: 0.8rem;">
                <?php 
                $is_disponible = (empty($ord['carnicero_id']) || $ord['carnicero_nombre'] === 'Carnicero Disponible' || empty($ord['carnicero_nombre']));
                ?>
                <?php if (!$is_prep && !$is_final): ?>
                    <form action="dashboard-carnicero.php" method="POST" style="width: 100%;">
                        <input type="hidden" name="orden_id" value="<?php echo $ord['id']; ?>">
                        <input type="hidden" name="nuevo_estado" value="en_preparacion">
                        <?php if ($is_disponible): ?>
                            <button type="submit" name="cambiar_estado_corte" onclick="return confirm('¿Aceptar este pedido y asignártelo exclusivamente a ti (<?php echo htmlspecialchars($user['nombre']); ?>)?');" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #10b981, #059669); border-color: #10b981; color: #fff; padding: 0.75rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-circle-check"></i> ▶ ACEPTAR PEDIDO (Asignármelo)
                            </button>
                        <?php else: ?>
                            <button type="submit" name="cambiar_estado_corte" class="btn-export" style="width: 100%; justify-content: center; background: rgba(245, 158, 11, 0.2); border-color: #f59e0b; color: #fbbf24; padding: 0.65rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-play"></i> Pasar a EN PROCESO
                            </button>
                        <?php endif; ?>
                    </form>
                <?php elseif ($is_prep): ?>
                    <?php if ($is_domicilio): ?>
                        <form action="dashboard-carnicero.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="orden_id" value="<?php echo $ord['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="listo_domicilio">
                            <button type="submit" name="cambiar_estado_corte" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #10b981, #047857); border-color: #10b981; color: #fff; padding: 0.75rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-paper-plane"></i> 🛵 MARCAR COMO TERMINADO (Enviar a Central para Domicilio)
                            </button>
                        </form>
                    <?php else: ?>
                        <form action="dashboard-carnicero.php" method="POST" style="width: 100%;">
                            <input type="hidden" name="orden_id" value="<?php echo $ord['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="finalizado">
                            <button type="submit" name="cambiar_estado_corte" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #d4af37, #997a15); border-color: var(--gold); color: #000; padding: 0.75rem; font-weight: 800; font-size:0.95rem;">
                                <i class="fa-solid fa-store"></i> 🏬 MARCAR COMO TERMINADO (Listo para Recoger en Sede)
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($is_domicilio): ?>
                        <div style="text-align: center; color: #34d399; font-weight: 800; font-size: 0.9rem; padding: 0.6rem; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; border-radius: 6px;">
                            <i class="fa-solid fa-motorcycle"></i> Corte Terminado - Enviado a Central (Para Domicilio)
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--gold); font-weight: 800; font-size: 0.9rem; padding: 0.6rem; background: rgba(212, 175, 55, 0.15); border: 1px solid var(--gold); border-radius: 6px;">
                            <i class="fa-solid fa-store"></i> Corte Terminado - Listo para Recoger en Sede
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
