<?php
$page_title = "Dashboard Domiciliario";
$active_menu = "domiciliario";
$required_roles = ['dueno', 'admin', 'domiciliario'];

require_once __DIR__ . '/includes/admin_header.php';

$dom_msg = '';

// Procesar cambio de estado de domicilio por el domiciliario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_domicilio'])) {
    $domicilio_id = intval($_POST['domicilio_id'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'entregado';

    if ($pdo && $domicilio_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE domicilios_envios SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $domicilio_id]);
            if (function_exists('registrar_log')) {
                registrar_log('Estado Domicilio Actualizado', 'Domicilios', "Pedido #{$domicilio_id} actualizado a '{$nuevo_estado}'.");
            }

            $lbl = ($nuevo_estado === 'en_camino') ? 'EN CAMINO AL CLIENTE' : 'ENTREGADO CONFIRMADO';
            $dom_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Estado de envío #' . $domicilio_id . ' actualizado exitosamente a <strong>' . $lbl . '</strong>. El Cajero ha sido notificado en tiempo real.</div>';
        } catch (Exception $e) {}
    }
}

// Obtener pedidos de domicilio asignados
$pedidos_domicilio = [];
if ($pdo) {
    try {
        if ($user['rol'] === 'domiciliario') {
            $stmt = $pdo->prepare("SELECT * FROM domicilios_envios WHERE domiciliario_id = ? OR domiciliario_id IS NULL ORDER BY fecha_hora DESC");
            $stmt->execute([$user['id']]);
        } else {
            $stmt = $pdo->query("SELECT * FROM domicilios_envios ORDER BY fecha_hora DESC");
        }
        $pedidos_domicilio = $stmt->fetchAll();

        // Enriquecer teléfonos y direcciones si dicen "Contacto Web" o "Dirección Registrada"
        foreach ($pedidos_domicilio as &$p) {
            if (empty($p['cliente_telefono']) || $p['cliente_telefono'] === 'Contacto Web') {
                if (preg_match('/\[Teléfono:\s*([^\]]+)\]/', $p['notas_entrega'] ?? '', $m_tel)) {
                    $p['cliente_telefono'] = trim($m_tel[1]);
                }
            }
            if (empty($p['direccion_entrega']) || $p['direccion_entrega'] === 'Dirección Registrada') {
                if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/', $p['notas_entrega'] ?? '', $m_dir)) {
                    $p['direccion_entrega'] = trim($m_dir[1]);
                }
            }
            if ((empty($p['cliente_telefono']) || $p['cliente_telefono'] === 'Contacto Web' || empty($p['direccion_entrega']) || $p['direccion_entrega'] === 'Dirección Registrada') && !empty($p['cliente_nombre'])) {
                $stmt_s = $pdo->prepare("SELECT telefono, detalles FROM solicitudes_contacto WHERE nombre LIKE ? ORDER BY id DESC LIMIT 1");
                $stmt_s->execute(['%' . $p['cliente_nombre'] . '%']);
                if ($r_s = $stmt_s->fetch()) {
                    if ((empty($p['cliente_telefono']) || $p['cliente_telefono'] === 'Contacto Web') && !empty($r_s['telefono'])) {
                        $p['cliente_telefono'] = $r_s['telefono'];
                    }
                    if ((empty($p['direccion_entrega']) || $p['direccion_entrega'] === 'Dirección Registrada') && preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/', $r_s['detalles'], $m_dir2)) {
                        $p['direccion_entrega'] = trim($m_dir2[1]);
                    }
                }
            }
        }
        unset($p);
    } catch (Exception $e) {}
}

if (!function_exists('generar_link_whatsapp_domicilio')) {
    function generar_link_whatsapp_domicilio($tel_raw, $nom_raw = '') {
        $clean = preg_replace('/[^0-9]/', '', (string)$tel_raw);
        if (empty($clean)) return '#';
        if (strlen($clean) === 10 && strpos($clean, '3') === 0) {
            $clean = '57' . $clean;
        }
        $cli = trim(explode('(', $nom_raw)[0]);
        $msg = rawurlencode("Hola " . ($cli ?: 'Cliente') . ", te contactamos de COPACARNES por tu pedido a domicilio.");
        return "https://api.whatsapp.com/send?phone=" . $clean . "&text=" . $msg;
    }
}

// Cálculo de ganancias por tarifas del día
$ganancias_tarifas = 0;
$entregas_hoy = 0;
foreach ($pedidos_domicilio as $p) {
    if ($p['estado'] === 'entregado') {
        $ganancias_tarifas += floatval($p['tarifa_domicilio']);
        $entregas_hoy++;
    }
}
?>

<!-- Encabezado del Domiciliario -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-motorcycle text-gold"></i> Dashboard Domiciliario
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Pedidos asignados por la caja registradora, optimización de ruta y confirmación de entregas en tiempo real.
        </p>
    </div>
    <div style="display: flex; gap: 0.8rem;">
        <button onclick="location.reload()" class="btn-export" style="background: rgba(16, 185, 129, 0.2); border-color: #10b981; color: #34d399;">
            <i class="fa-solid fa-arrows-rotate"></i> Actualizar Pedidos
        </button>
    </div>
</div>

<?php echo $dom_msg; ?>

<!-- Tarjetas KPI del Domiciliario -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-title">Entregas Realizadas Hoy</div>
        <div class="kpi-value"><?php echo $entregas_hoy; ?> Envíos</div>
        <div class="kpi-sub"><i class="fa-solid fa-circle-check"></i> Confirmadas en Sistema</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-title">Pedidos Asignados</div>
        <div class="kpi-value" style="color: #3b82f6;"><?php echo count($pedidos_domicilio); ?> Pedidos</div>
        <div class="kpi-sub" style="color: #3b82f6;"><i class="fa-solid fa-route"></i> En Rutas de Entrega</div>
    </div>
</div>

<!-- Lista de Pedidos Asignados para Entrega -->
<div class="data-table-card" style="border-left: 4px solid #3b82f6;">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-box-archive text-gold"></i> Pedidos Asignados para Entrega
        </h3>
        <input type="text" class="search-input" data-table="tabla-domicilios" placeholder="Buscar pedido, cliente o dirección...">
    </div>

    <table class="custom-table" id="tabla-domicilios">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Teléfono Contacto</th>
                <th>Dirección de Entrega</th>
                <th>Valor a Cobrar / Pago</th>
                <th>Fecha y Hora</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pedidos_domicilio)): ?>
                <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:1.5rem;">No tienes pedidos de domicilio asignados en este momento.</td></tr>
            <?php else: ?>
                <?php foreach ($pedidos_domicilio as $ped): ?>
                <tr>
                    <td><strong style="color:#fff;"><?php echo htmlspecialchars($ped['cliente_nombre']); ?></strong></td>
                    <td>
                        <?php 
                        $wsp_url = generar_link_whatsapp_domicilio($ped['cliente_telefono'], $ped['cliente_nombre']);
                        if ($wsp_url !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($wsp_url); ?>" target="_blank" style="color:#10b981; text-decoration:none; font-weight:600;">
                                <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($ped['cliente_telefono']); ?>
                            </a>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-size:0.85rem;"><i class="fa-solid fa-phone-slash"></i> <?php echo htmlspecialchars($ped['cliente_telefono'] ?: 'Sin Teléfono'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#fff; font-weight:600;"><?php echo htmlspecialchars($ped['direccion_entrega']); ?></td>
                    <td>
                        <?php if (($ped['estado_pago'] ?? '') === 'pagado'): ?>
                            <span class="status-pill success" style="font-size:0.75rem; background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399;"><i class="fa-solid fa-check-double"></i> YA PAGADO ($ 0)</span>
                        <?php else: ?>
                            <strong style="color:var(--gold); font-size:0.95rem;">$ <?php echo number_format(!empty($ped['monto_cobrar']) ? $ped['monto_cobrar'] : ($ped['tarifa_domicilio'] ?? 8000), 0, ',', '.'); ?> COP</strong><br>
                            <span style="font-size:0.75rem; color:#f59e0b;"><i class="fa-solid fa-hand-holding-dollar"></i> Cobrar en <?php echo ucfirst($ped['metodo_pago'] ?? 'efectivo'); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($ped['comprobante_pago'])): ?>
                            <div style="margin-top:0.25rem;">
                                <a href="javascript:void(0);" onclick="abrirModalComprobante('<?php echo htmlspecialchars($ped['comprobante_pago']); ?>')" style="color:#60a5fa; font-size:0.75rem; text-decoration:none; font-weight:700; cursor:pointer;">
                                    <i class="fa-solid fa-paperclip"></i> Ver Comprobante
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><i class="fa-solid fa-clock" style="color:var(--text-muted);"></i> <?php echo htmlspecialchars($ped['fecha_hora']); ?></td>
                    <td>
                        <?php if ($ped['estado'] === 'entregado'): ?>
                            <span class="status-pill success"><i class="fa-solid fa-circle-check"></i> ENTREGADO</span>
                        <?php elseif ($ped['estado'] === 'en_camino'): ?>
                            <span class="status-pill warning"><i class="fa-solid fa-motorcycle"></i> EN CAMINO</span>
                        <?php else: ?>
                            <span class="status-pill danger"><i class="fa-solid fa-clock"></i> PENDIENTE</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center;">
                            <button type="button" onclick='verDetalleDomicilio(<?php echo json_encode($ped, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="btn-export" style="padding:0.35rem 0.65rem; background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; font-weight:700;" title="Ver Detalle Completo">
                                <i class="fa-solid fa-eye"></i> Ver Detalle
                            </button>
                            <?php if ($ped['estado'] === 'asignado' || $ped['estado'] === 'pendiente'): ?>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="domicilio_id" value="<?php echo $ped['id']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="en_camino">
                                    <button type="submit" name="cambiar_estado_domicilio" class="btn-export" style="padding:0.35rem 0.75rem; background:rgba(245, 158, 11, 0.2); border-color:#f59e0b; color:#fbbf24; font-weight:700;">
                                        <i class="fa-solid fa-motorcycle"></i> Pasar a EN CAMINO
                                    </button>
                                </form>
                            <?php elseif ($ped['estado'] === 'en_camino'): ?>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="domicilio_id" value="<?php echo $ped['id']; ?>">
                                    <input type="hidden" name="nuevo_estado" value="entregado">
                                    <button type="submit" name="cambiar_estado_domicilio" class="btn-export" style="padding:0.35rem 0.75rem; background:rgba(16, 185, 129, 0.2); border-color:#10b981; color:#34d399; font-weight:700;">
                                        <i class="fa-solid fa-circle-check"></i> Pasar a ENTREGADO
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:0.85rem; color:#34d399; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Entrega Completada</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL: DETALLE COMPLETO DEL PEDIDO PARA DOMICILIARIO -->
<div id="modalDetalleDomicilio" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:580px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-motorcycle text-gold"></i> Detalle Completo de Envío a Domicilio</h3>
            <button onclick="document.getElementById('modalDetalleDomicilio').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <div style="display:flex; flex-direction:column; gap:1rem;">
            <!-- Origen / Área Encargada -->
            <div style="background:rgba(0,0,0,0.4); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-store text-gold"></i> Origen / Área del Pedido:</span>
                <span id="dom_modal_origen_badge" class="status-pill warning" style="font-size:0.85rem; font-weight:800;">🥩 Carnicería</span>
            </div>

            <!-- Cliente & Teléfono -->
            <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Cliente destinatario:</span>
                    <strong style="color:var(--gold); font-size:1.1rem;" id="dom_modal_cliente">Nombre del Cliente</strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Teléfono / WhatsApp:</span>
                    <a id="dom_modal_wsp" href="#" target="_blank" style="color:#10b981; font-weight:700; text-decoration:none; font-size:1rem;">
                        <i class="fa-brands fa-whatsapp"></i> <span id="dom_modal_telefono">0000000000</span>
                    </a>
                </div>
            </div>

            <!-- Dirección Destino & Mapa -->
            <div style="background:rgba(16,185,129,0.1); padding:1rem; border-radius:8px; border:1.5px solid #10b981;">
                <span style="font-size:0.8rem; color:#34d399; font-weight:700; display:block; margin-bottom:0.3rem;">
                    <i class="fa-solid fa-location-dot"></i> Dirección Exacta de Entrega:
                </span>
                <p style="margin:0 0 0.8rem 0; color:#fff; font-size:1.1rem; font-weight:800; line-height:1.4;" id="dom_modal_direccion">
                    Dirección Registrada
                </p>
                <a id="dom_modal_maps" href="#" target="_blank" class="btn-export" style="background:#10b981; border-color:#10b981; color:#000; font-weight:800; width:100%; justify-content:center; padding:0.6rem;">
                    <i class="fa-solid fa-map-location-dot"></i> Abrir en Google Maps / Waze
                </a>
            </div>

            <!-- Productos & Especificaciones -->
            <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
                <span style="font-size:0.8rem; color:var(--text-muted); display:block; margin-bottom:0.4rem;">
                    <i class="fa-solid fa-box text-gold"></i> Contenido / Especificación del Pedido:
                </span>
                <p style="margin:0; color:#fff; font-size:0.92rem; line-height:1.5;" id="dom_modal_notas">
                    Detalles del corte o comanda.
                </p>
            </div>

            <!-- Valor a Cobrar y Estado -->
            <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.4); padding:0.8rem 1rem; border-radius:8px;">
                <div>
                    <span style="font-size:0.78rem; color:var(--text-muted); display:block;">Valor a Cobrar al Entregar:</span>
                    <strong style="color:var(--gold); font-size:1.05rem;" id="dom_modal_tarifa">$ 0 COP</strong>
                    <div id="dom_modal_comprobante_box" style="margin-top:0.25rem; display:none;">
                        <a id="dom_modal_comprobante_link" href="javascript:void(0);" onclick="abrirModalComprobante(this.getAttribute('data-url'))" style="color:#60a5fa; font-size:0.78rem; font-weight:700; text-decoration:none; cursor:pointer;">
                            <i class="fa-solid fa-paperclip"></i> Ver Comprobante de Pago
                        </a>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.78rem; color:var(--text-muted); display:block;">Estado Actual:</span>
                    <span id="dom_modal_estado_badge" class="status-pill warning">EN CAMINO</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalleDomicilio(ped) {
    const rawText = (ped.cliente_nombre || '') + ' ' + (ped.direccion_entrega || '') + ' ' + (ped.notas_entrega || '');

    let cliNom = ped.cliente_nombre || 'Cliente Web';
    if (cliNom.includes('[Entrega a Domicilio') || cliNom.includes('menu del dia')) {
        if (ped.notas_entrega && !ped.notas_entrega.includes('[')) {
            cliNom = ped.notas_entrega.trim();
        }
    }
    document.getElementById('dom_modal_cliente').innerText = cliNom;

    let dir = ped.direccion_entrega || 'Dirección Registrada';
    if (dir === 'Dirección Registrada' || dir.includes('Sede Principal')) {
        const matchDir = rawText.match(/\[Entrega a Domicilio en:\s*([^\]]+)\]/i);
        if (matchDir && matchDir[1]) {
            dir = matchDir[1].trim();
        }
    }
    document.getElementById('dom_modal_direccion').innerText = dir;
    document.getElementById('dom_modal_maps').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(dir);

    let tel = ped.cliente_telefono || '';
    if (!tel || tel === 'Contacto Web' || tel === 'Sin Teléfono') {
        const matchTel = rawText.match(/\[Teléfono:\s*([^\]]+)\]/i);
        if (matchTel && matchTel[1]) {
            tel = matchTel[1].trim();
        }
    }
    
    let cleanTel = tel.replace(/[^0-9]/g, '');
    if (cleanTel.length === 10 && cleanTel.startsWith('3')) {
        cleanTel = '57' + cleanTel;
    }

    const wspBtn = document.getElementById('dom_modal_wsp');
    const telSpan = document.getElementById('dom_modal_telefono');

    if (cleanTel && cleanTel.length >= 7) {
        const msg = encodeURIComponent("Hola " + cliNom + ", te contactamos de COPACARNES por tu pedido a domicilio.");
        wspBtn.href = "https://api.whatsapp.com/send?phone=" + cleanTel + "&text=" + msg;
        telSpan.innerText = tel;
        wspBtn.style.pointerEvents = 'auto';
        wspBtn.style.opacity = '1';
    } else {
        wspBtn.href = "javascript:alert('No se registró un número de teléfono válido para este cliente.');";
        telSpan.innerHTML = '<i class="fa-solid fa-phone-slash"></i> Sin Teléfono Registrado';
    }

    // Origen / Área del Pedido
    const origenBadge = document.getElementById('dom_modal_origen_badge');
    const lowerText = rawText.toLowerCase();
    if (lowerText.includes('[origen: restaurante]') || lowerText.includes('menu del dia') || lowerText.includes('restaurante') || lowerText.includes('cocina') || lowerText.includes('almuerzo') || lowerText.includes('platillo') || lowerText.includes('asd')) {
        origenBadge.innerHTML = '<i class="fa-solid fa-utensils"></i> 🍽️ Restaurante (Cocina)';
        origenBadge.style.background = 'rgba(59, 130, 246, 0.2)';
        origenBadge.style.borderColor = '#3b82f6';
        origenBadge.style.color = '#60a5fa';
    } else if (lowerText.includes('[origen: carnicería]') || lowerText.includes('[origen: carniceria]') || lowerText.includes('carniceria') || lowerText.includes('corte') || lowerText.includes('kg') || lowerText.includes('desposte') || lowerText.includes('freir') || lowerText.includes('zxc')) {
        origenBadge.innerHTML = '<i class="fa-solid fa-drumstick-bite"></i> 🥩 Carnicería';
        origenBadge.style.background = 'rgba(245, 158, 11, 0.2)';
        origenBadge.style.borderColor = '#f59e0b';
        origenBadge.style.color = '#fbbf24';
    } else {
        origenBadge.innerHTML = '<i class="fa-solid fa-box"></i> 📦 Sede General';
        origenBadge.style.background = 'rgba(212, 175, 55, 0.2)';
        origenBadge.style.borderColor = '#d4af37';
        origenBadge.style.color = '#d4af37';
    }

    let notasLimpias = (ped.notas_entrega || ped.numero_factura || 'Sin notas adicionales.')
        .replace(/\[Origen:\s*[^\]]+\]/gi, '')
        .replace(/\[Entrega a Domicilio en:\s*([^\]]+)\]/gi, '')
        .replace(/\[Teléfono:\s*([^\]]+)\]/gi, '')
        .trim();
    if (!notasLimpias) notasLimpias = 'Sin especificaciones adicionales.';
    document.getElementById('dom_modal_notas').innerText = notasLimpias;

    const isPagado = (ped.estado_pago === 'pagado');
    const monto = parseFloat(ped.monto_cobrar || 0);
    const tarifa = parseFloat(ped.tarifa_domicilio || 8000);
    const valFinal = (monto > 0) ? monto : (isPagado ? 0 : tarifa);
    const met = (ped.metodo_pago || 'efectivo').toUpperCase();

    if (isPagado) {
        document.getElementById('dom_modal_tarifa').innerHTML = '<span style="color:#34d399;"><i class="fa-solid fa-check-double"></i> YA PAGADO ($ 0 COP)</span>';
    } else {
        document.getElementById('dom_modal_tarifa').innerHTML = '$ ' + valFinal.toLocaleString('es-CO') + ' COP <span style="font-size:0.78rem; color:#f59e0b; font-weight:normal;">(' + met + ')</span>';
    }

    const compBox = document.getElementById('dom_modal_comprobante_box');
    const compLink = document.getElementById('dom_modal_comprobante_link');
    if (ped.comprobante_pago) {
        compLink.setAttribute('data-url', ped.comprobante_pago);
        compBox.style.display = 'block';
    } else {
        compBox.style.display = 'none';
    }

    const stBadge = document.getElementById('dom_modal_estado_badge');
    const st = ped.estado || 'pendiente';
    if (st === 'entregado') {
        stBadge.className = 'status-pill success';
        stBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> ENTREGADO';
    } else if (st === 'en_camino') {
        stBadge.className = 'status-pill warning';
        stBadge.innerHTML = '<i class="fa-solid fa-motorcycle"></i> EN CAMINO';
    } else {
        stBadge.className = 'status-pill danger';
        stBadge.innerHTML = '<i class="fa-solid fa-clock"></i> PENDIENTE RECOGIDA';
    }

    document.getElementById('modalDetalleDomicilio').style.display = 'flex';
}
</script>

<!-- MODAL OVERLAY: VISUALIZADOR INTERNO DE COMPROBANTE DE PAGO -->
<div id="modalComprobantePago" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:999999; align-items:center; justify-content:center; padding:1rem; box-sizing:border-box;">
    <div style="max-width:650px; width:100%; background:#0d1630; border:1.5px solid var(--gold); border-radius:16px; padding:1.4rem; box-shadow:0 20px 50px rgba(0,0,0,0.9); display:flex; flex-direction:column; gap:1.2rem;">
        
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(212,175,55,0.3); padding-bottom:0.6rem;">
            <h4 style="margin:0; color:#fff; font-size:1.15rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-file-invoice-dollar text-gold"></i> Comprobante de Pago Adjunto
            </h4>
            <button type="button" onclick="cerrarModalComprobante()" style="background:none; border:none; color:#fff; font-size:1.8rem; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        
        <div style="display:flex; justify-content:center; align-items:center; min-height:220px; max-height:75vh; overflow:auto; background:rgba(0,0,0,0.5); border-radius:10px; padding:0.6rem; border:1px solid rgba(255,255,255,0.1);" id="box_preview_comprobante_content">
            <img id="img_comprobante_preview" src="" style="max-width:100%; max-height:70vh; border-radius:8px; object-fit:contain; display:none;" alt="Comprobante">
            <iframe id="iframe_comprobante_preview" src="" style="width:100%; height:70vh; border:none; border-radius:8px; display:none;"></iframe>
        </div>

        <div style="display:flex; justify-content:flex-start; align-items:center;">
            <a id="btn_descargar_comprobante" href="#" download target="_blank" class="btn btn-export" style="background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; font-size:0.82rem; font-weight:700; padding:0.45rem 0.9rem; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                <i class="fa-solid fa-download"></i> Descargar Archivo Original
            </a>
        </div>
    </div>
</div>

<script>
function abrirModalComprobante(url) {
    if (!url) {
        alert('No hay un archivo de comprobante disponible para esta solicitud.');
        return;
    }
    const imgPreview = document.getElementById('img_comprobante_preview');
    const iframePreview = document.getElementById('iframe_comprobante_preview');
    const btnDownload = document.getElementById('btn_descargar_comprobante');

    btnDownload.href = url;
    const ext = url.split('.').pop().split('?')[0].toLowerCase();

    if (ext === 'pdf') {
        imgPreview.style.display = 'none';
        iframePreview.src = url;
        iframePreview.style.display = 'block';
    } else {
        iframePreview.style.display = 'none';
        iframePreview.src = '';
        imgPreview.src = url;
        imgPreview.style.display = 'block';
    }

    document.getElementById('modalComprobantePago').style.display = 'flex';
}

function cerrarModalComprobante() {
    const modal = document.getElementById('modalComprobantePago');
    const iframePreview = document.getElementById('iframe_comprobante_preview');
    if (iframePreview) iframePreview.src = '';
    if (modal) modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
