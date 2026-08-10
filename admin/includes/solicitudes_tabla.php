<?php
// 1. Procesar cambio de estado de solicitud si se envió un formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_solicitud'])) {
    $solicitud_id = intval($_POST['solicitud_id'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'atendido';

    if ($pdo && $solicitud_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE solicitudes_contacto SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $solicitud_id]);

            if (function_exists('registrar_log')) {
                registrar_log('Cambio Estado Solicitud', 'Reservas/Contacto', "Solicitud #{$solicitud_id} actualizada a '{$nuevo_estado}'.");
            }
        } catch (Exception $e) {}
    }
}

// 2. Procesar eliminación de solicitud de la web (Sincronizada con Carnicería, Cocina y Domicilios)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_solicitud'])) {
    $solicitud_id = intval($_POST['solicitud_id'] ?? 0);

    if ($pdo && $solicitud_id > 0) {
        try {
            $stmt_get = $pdo->prepare("SELECT nombre, detalles FROM solicitudes_contacto WHERE id = ?");
            $stmt_get->execute([$solicitud_id]);
            $sol_info = $stmt_get->fetch();

            if ($sol_info) {
                $cliente = trim($sol_info['nombre']);

                $stmt = $pdo->prepare("DELETE FROM solicitudes_contacto WHERE id = ?");
                $stmt->execute([$solicitud_id]);

                if (!empty($cliente)) {
                    $stmt_c = $pdo->prepare("DELETE FROM ordenes_carniceria WHERE cliente_nombre = ? OR corte_detalle LIKE ?");
                    $stmt_c->execute([$cliente, '%' . $cliente . '%']);

                    $stmt_k = $pdo->prepare("DELETE FROM comandas_cocina WHERE notas LIKE ? OR platillo_nombre LIKE ?");
                    $stmt_k->execute(['%' . $cliente . '%', '%' . $cliente . '%']);

                    $stmt_d = $pdo->prepare("DELETE FROM domicilios_envios WHERE cliente_nombre = ? OR direccion_entrega LIKE ?");
                    $stmt_d->execute([$cliente, '%' . $cliente . '%']);
                }

                if (function_exists('registrar_log')) {
                    registrar_log('Eliminar Solicitud', 'Reservas/Contacto', "Solicitud #{$solicitud_id} de '{$cliente}' eliminada de todos los sistemas.");
                }
            }
        } catch (Exception $e) {}
    }
}

// 3. Procesar Despacho Inteligente Directo (Auto-detección del área de destino)
$solicitud_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['despachar_auto_solicitud_btn'])) {
    $sol_id = intval($_POST['solicitud_id'] ?? 0);
    if ($sol_id > 0 && $pdo) {
        try {
            $stmt_sol = $pdo->prepare("SELECT * FROM solicitudes_contacto WHERE id = ?");
            $stmt_sol->execute([$sol_id]);
            $sol_item = $stmt_sol->fetch();

            if ($sol_item) {
                $cliente = trim($sol_item['nombre'] ?? 'Cliente Web');
                $telefono = trim($sol_item['telefono'] ?? '');
                $detalles = trim($sol_item['detalles'] ?? '');
                $sede_tipo = strtolower(trim($sol_item['sede_tipo'] ?? ''));

                // Preservar el teléfono en el detalle del pedido para que pase a carnicería/cocina y luego al domiciliario
                if (!empty($telefono) && strpos($detalles, '[Teléfono:') === false) {
                    $detalles .= ' [Teléfono: ' . $telefono . ']';
                }

                $detalles_lower = strtolower($detalles);

                // Auto-detectar área encargada de la preparación primero (Carnicería o Restaurante/Cocina)
                $is_carniceria = (strpos($sede_tipo, 'carniceria') !== false || strpos($detalles_lower, 'carnicero') !== false || strpos($detalles_lower, 'corte') !== false || strpos($detalles_lower, 'kg') !== false || strpos($detalles_lower, 'desposte') !== false);
                $is_restaurante = (strpos($sede_tipo, 'restaurante') !== false || strpos($sede_tipo, 'cocina') !== false || strpos($detalles_lower, 'restaurante') !== false || strpos($detalles_lower, 'cocina') !== false || strpos($detalles_lower, 'mesa') !== false || strpos($detalles_lower, 'platillo') !== false);

                if ($is_carniceria) {
                    // 1. SIEMPRE VA PRIMERO A CARNICERÍA (Carnicero asignado o disponible para preparación/desposte)
                    $carnicero_id = null;
                    $carnicero_nombre = 'Carnicero Disponible';

                    // Extraer carnicero específico si el cliente lo eligió en el formulario web
                    if (preg_match('/\[Carnicero Seleccionado:\s*([^\]]+)\]/i', $detalles, $m_car)) {
                        $c_name_raw = trim($m_car[1]);
                        $first_name = trim(explode('(', $c_name_raw)[0]);

                        $stmt_c_find = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE (nombre LIKE ? OR nombre LIKE ?) AND rol = 'carnicero' LIMIT 1");
                        $stmt_c_find->execute(['%' . $c_name_raw . '%', '%' . $first_name . '%']);
                        if ($r_car = $stmt_c_find->fetch()) {
                            $carnicero_id = intval($r_car['id']);
                            $carnicero_nombre = $r_car['nombre'];
                        } else {
                            $carnicero_nombre = $c_name_raw;
                        }
                    }

                    $num_ord = 'ORD-CAR-' . rand(100, 999);
                    $stmt_car = $pdo->prepare("INSERT INTO ordenes_carniceria (numero_orden, cliente_nombre, carnicero_id, carnicero_nombre, kilos, corte_detalle, estado) VALUES (?, ?, ?, ?, 1.00, ?, 'pendiente')");
                    $stmt_car->execute([$num_ord, $cliente, $carnicero_id, $carnicero_nombre, $detalles]);

                    if ($carnicero_id) {
                        $solicitud_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-drumstick-bite"></i> Pedido de <strong>' . htmlspecialchars($cliente) . '</strong> despachado PRIMERO a Carnicería (asignado exclusivamente a <strong>' . htmlspecialchars($carnicero_nombre) . '</strong>). Al finalizar la preparación, pasará automáticamente al domiciliario.</div>';
                    } else {
                        $solicitud_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-drumstick-bite"></i> Pedido de <strong>' . htmlspecialchars($cliente) . '</strong> despachado PRIMERO a Carnicería (Carnicero Disponible). Al finalizar el corte, pasará automáticamente al domiciliario.</div>';
                    }
                } elseif ($is_restaurante) {
                    // 2. SIEMPRE VA A RESTAURANTE / COCINA (Diferenciando inteligente entre Reserva de Mesa y Pedido de Comida)
                    $is_reserva = (strpos($sede_tipo, 'reserva') !== false || strpos($detalles_lower, 'reserva') !== false || strpos($detalles_lower, 'reservar') !== false);

                    if ($is_reserva) {
                        $mesa_final = 'Reserva ' . $cliente;
                        $solicitud_msg = '<div class="alert alert-success" style="background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #60a5fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-calendar-check"></i> Reserva de <strong>' . htmlspecialchars($cliente) . '</strong> enviada a la <strong>Tabla de Reservas de Restaurante</strong>.</div>';
                    } else {
                        $mesa_final = 'Pedido Web ' . $cliente;
                        $solicitud_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-utensils"></i> Pedido de comida de <strong>' . htmlspecialchars($cliente) . '</strong> enviado a la <strong>Tabla de Pedidos del Restaurante (Cocineras)</strong>.</div>';
                    }

                    $stmt_coc = $pdo->prepare("INSERT INTO comandas_cocina (mesa_numero, platillo_nombre, notas, estado) VALUES (?, ?, ?, 'pendiente')");
                    $stmt_coc->execute([$mesa_final, $detalles, 'Cliente: ' . $cliente . ' | Tel: ' . $telefono]);
                } else {
                    // 3. DESPACHAR DIRECTAMENTE A DOMICILIOS (Para envíos que no requieren preparación previa)
                    $num_fac = 'FAC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $stmt_dom = $pdo->prepare("INSERT INTO domicilios_envios (numero_factura, cliente_nombre, cliente_telefono, direccion_entrega, tarifa_domicilio, estado) VALUES (?, ?, ?, ?, 8000, 'pendiente')");
                    $stmt_dom->execute([$num_fac, $cliente, $telefono, $detalles]);
                    $solicitud_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-motorcycle"></i> Pedido de <strong>' . htmlspecialchars($cliente) . '</strong> despachado directamente a <strong>Domicilios</strong>.</div>';
                }

                // Eliminar la solicitud de la bandeja de entrada web para no dejarla duplicada
                $stmt_del = $pdo->prepare("DELETE FROM solicitudes_contacto WHERE id = ?");
                $stmt_del->execute([$sol_id]);
            }
        } catch (Exception $e) {
            $solicitud_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al despachar solicitud: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Obtener todas las solicitudes recibidas desde la página web
$lista_solicitudes = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM solicitudes_contacto ORDER BY fecha_hora DESC");
        $lista_solicitudes = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>

<?php if (!empty($solicitud_msg)) echo $solicitud_msg; ?>

<!-- Componente Visual de Solicitudes y Reservas de Clientes (Acceso para Cajero, Admin y Dueño) -->
<div class="data-table-card" style="border-left: 4px solid var(--gold);">
    <div class="table-header-tools">
        <div>
            <h3 style="margin: 0; color: #fff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-envelope-open-text text-gold"></i> 📨 Solicitudes de Reservas & Pedidos Especiales
            </h3>
            <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.85rem;">
                Mensajes y reservas enviadas por clientes en tiempo real desde el sitio web principal.
            </p>
        </div>
        <input type="text" class="search-input" data-table="tabla-solicitudes-web" placeholder="Buscar por cliente, teléfono o sede...">
    </div>

    <?php if (empty($lista_solicitudes)): ?>
        <div style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            <i class="fa-solid fa-inbox" style="font-size: 2rem; color: var(--gold); display: block; margin-bottom: 0.5rem;"></i>
            No hay solicitudes recibidas aún. Las nuevas reservas del sitio web aparecerán aquí automáticamente.
        </div>
    <?php else: ?>
        <table class="custom-table" id="tabla-solicitudes-web">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente / Nombre</th>
                    <th>Teléfono / WhatsApp</th>
                    <th>Correo Electrónico</th>
                    <th>Sede / Tipo de Solicitud</th>
                    <th>Detalles de la Reserva / Pedido</th>
                    <th>Fecha y Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista_solicitudes as $sol): ?>
                <tr>
                    <td><code>#<?php echo htmlspecialchars($sol['id']); ?></code></td>
                    <td><strong style="color: #fff;"><?php echo htmlspecialchars($sol['nombre']); ?></strong></td>
                    <td>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $sol['telefono']); ?>" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 600;">
                            <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($sol['telefono']); ?>
                        </a>
                    </td>
                    <td><span style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($sol['correo']); ?></span></td>
                    <td><span class="status-pill warning"><?php echo htmlspecialchars($sol['sede_tipo']); ?></span></td>
                    <td style="font-size:0.85rem; max-width: 280px;">
                        <button type="button" onclick='verModalDetalleSolicitud(<?php echo json_encode($sol, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)' class="btn-export" style="padding: 0.35rem 0.65rem; background: rgba(212,175,55,0.18); border-color: var(--gold); color: #fff; font-size: 0.78rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; width: 100%; justify-content: center;">
                            <i class="fa-solid fa-up-right-and-pointer-from-center text-gold"></i> Ver Detalle Completo
                        </button>
                    </td>
                    <td><i class="fa-solid fa-clock" style="color:var(--text-muted);"></i> <?php echo htmlspecialchars($sol['fecha_hora']); ?></td>
                    <td>
                        <span class="status-pill warning" style="background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid #f59e0b;"><i class="fa-solid fa-hourglass-half"></i> PENDIENTE DE DESPACHO</span>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.4rem; align-items:center;">
                            <!-- Botón Despachar Directo -->
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="solicitud_id" value="<?php echo $sol['id']; ?>">
                                <button type="submit" name="despachar_auto_solicitud_btn" class="btn-export" style="padding:0.35rem 0.65rem; background: linear-gradient(135deg, #10b981, #047857); border-color:#10b981; color:#fff; font-weight:800; display:inline-flex; align-items:center; gap:0.3rem;" title="Despachar automáticamente al área correspondiente">
                                    <i class="fa-solid fa-paper-plane"></i> Despachar
                                </button>
                            </form>

                            <!-- Botón Eliminar Pedido / Solicitud 🗑️ -->
                            <form action="" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la solicitud #<?php echo $sol['id']; ?> de \'<?php echo htmlspecialchars($sol['nombre']); ?>\'?');" style="display:inline;">
                                <input type="hidden" name="solicitud_id" value="<?php echo $sol['id']; ?>">
                                <button type="submit" name="eliminar_solicitud" class="btn-export" style="padding:0.35rem 0.65rem; background: rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5;" title="Eliminar Solicitud">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- OVERLAY MODAL: DETALLE COMPLETO Y ORDENADO DE SOLICITUD WEB -->
<div id="modalDetalleSolicitudWeb" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center; padding:1rem; box-sizing:border-box;">
    <div style="max-width:650px; width:100%; background:#0d1630; border:1.5px solid var(--gold); border-radius:16px; padding:1.5rem; box-shadow:0 20px 50px rgba(0,0,0,0.8); max-height:90vh; overflow-y:auto; display:flex; flex-direction:column; gap:1.2rem;">
        
        <!-- Header del Modal -->
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(212,175,55,0.3); padding-bottom:0.8rem;">
            <div>
                <h3 style="margin:0; color:#fff; font-size:1.2rem; display:flex; align-items:center; gap:0.5rem;" id="modal_sol_titulo">
                    <i class="fa-solid fa-file-invoice text-gold"></i> Detalle Completo de Solicitud Web
                </h3>
                <span style="font-size:0.8rem; color:var(--text-muted);" id="modal_sol_subtitulo">ID Solicitud #000</span>
            </div>
            <button type="button" onclick="cerrarModalDetalleSolicitud()" style="background:none; border:none; color:#fff; font-size:1.8rem; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>

        <!-- Bloque 1: Cliente & Contacto -->
        <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:10px; border:1px solid var(--border-color); display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:0.2rem;"><i class="fa-solid fa-user text-gold"></i> Nombre del Cliente:</span>
                <strong style="color:#fff; font-size:1.05rem;" id="modal_sol_cliente">Cliente Web</strong>
            </div>
            <div>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:0.2rem;"><i class="fa-brands fa-whatsapp" style="color:#10b981;"></i> Teléfono / WhatsApp:</span>
                <a id="modal_sol_wsp_link" href="#" target="_blank" style="color:#34d399; font-weight:700; text-decoration:none; font-size:0.95rem; display:inline-flex; align-items:center; gap:0.4rem;">
                    <i class="fa-brands fa-whatsapp"></i> <span id="modal_sol_telefono">Sin teléfono</span>
                </a>
            </div>
        </div>

        <!-- Bloque 2: Servicio & Sede Destino -->
        <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:10px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.8rem;">
            <div>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:0.2rem;"><i class="fa-solid fa-store text-gold"></i> Sede & Tipo de Solicitud:</span>
                <span id="modal_sol_tipo_badge" class="status-pill warning" style="font-size:0.85rem;">General</span>
            </div>
            <div>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-bottom:0.2rem;"><i class="fa-solid fa-clock text-gold"></i> Fecha de Recepción:</span>
                <strong style="color:#fff; font-size:0.9rem;" id="modal_sol_fecha">Fecha</strong>
            </div>
        </div>

        <!-- Bloque 3: Dirección de Entrega a Domicilio (Si Aplica) -->
        <div id="modal_sol_dir_box" style="background:rgba(16, 185, 129, 0.12); padding:1rem; border-radius:10px; border:1px solid #10b981; display:none; flex-direction:column; gap:0.6rem;">
            <span style="font-size:0.8rem; color:#34d399; font-weight:700;"><i class="fa-solid fa-location-dot"></i> Dirección Registrada para Entrega a Domicilio:</span>
            <strong style="color:#fff; font-size:1rem;" id="modal_sol_direccion">Dirección</strong>
            <div>
                <a id="modal_sol_maps_btn" href="#" target="_blank" class="btn btn-export" style="background:linear-gradient(135deg, #10b981, #047857); border-color:#10b981; color:#fff; font-size:0.8rem; font-weight:800; padding:0.4rem 0.8rem; display:inline-flex; align-items:center; gap:0.4rem;">
                    <i class="fa-solid fa-map-location-dot"></i> Abrir Dirección en Google Maps / Waze
                </a>
            </div>
        </div>

        <!-- Bloque 4: Carnicero Seleccionado (Si Aplica) -->
        <div id="modal_sol_carnicero_box" style="background:rgba(212,175,55,0.12); padding:1rem; border-radius:10px; border:1px solid var(--gold); display:none; align-items:center; gap:0.8rem;">
            <img id="modal_sol_carnicero_img" src="../images/carnicero-disponible.png" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--gold);" alt="Carnicero">
            <div>
                <span style="font-size:0.78rem; color:var(--text-muted); display:block;">Maestro Carnicero Seleccionado por el Cliente:</span>
                <strong style="color:var(--gold); font-size:1rem;" id="modal_sol_carnicero_nombre">Carnicero Disponible</strong>
            </div>
        </div>

        <!-- Bloque 5: Especificaciones & Productos Solicitados -->
        <div style="background:rgba(0,0,0,0.5); padding:1.2rem; border-radius:10px; border:1px solid var(--border-color);">
            <span style="font-size:0.8rem; color:var(--gold); font-weight:700; text-transform:uppercase; display:block; margin-bottom:0.5rem;"><i class="fa-solid fa-list-check"></i> Especificaciones & Productos del Pedido / Reserva:</span>
            <p style="margin:0; color:#fff; font-size:0.95rem; line-height:1.6; white-space:pre-wrap; background:rgba(0,0,0,0.4); padding:0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.06);" id="modal_sol_detalles_texto">Especificaciones</p>
        </div>

        </div>

    </div>
</div>

<script>
function verModalDetalleSolicitud(sol) {
    const rawDetalles = sol.detalles || '';
    
    // Extraer Dirección
    let direccion = '';
    const matchDir = rawDetalles.match(/\[Entrega a Domicilio en:\s*([^\]]+)\]/i);
    if (matchDir && matchDir[1]) {
        direccion = matchDir[1].trim();
    }

    // Extraer Carnicero
    let carniceroNom = '';
    const matchCarn = rawDetalles.match(/\[Carnicero Seleccionado:\s*([^\]]+)\]/i);
    if (matchCarn && matchCarn[1]) {
        carniceroNom = matchCarn[1].trim();
    }

    // Limpiar texto de productos / especificaciones quitando los tags [ ... ]
    let detallesLimpiados = rawDetalles.replace(/\[[^\]]+\]/g, '').trim();
    if (!detallesLimpiados) detallesLimpiados = 'Sin especificaciones adicionales.';

    document.getElementById('modal_sol_titulo').innerHTML = '<i class="fa-solid fa-file-invoice text-gold"></i> Solicitud de Pedido / Reserva';
    document.getElementById('modal_sol_subtitulo').innerText = 'ID Solicitud #' + String(sol.id).padStart(3, '0');
    document.getElementById('modal_sol_cliente').innerText = sol.nombre || 'Cliente Web';
    
    let tel = sol.telefono || '';
    let cleanTel = tel.replace(/[^0-9]/g, '');
    if (cleanTel.length === 10 && cleanTel.startsWith('3')) cleanTel = '57' + cleanTel;
    
    const wspLink = document.getElementById('modal_sol_wsp_link');
    document.getElementById('modal_sol_telefono').innerText = tel || 'Sin teléfono';
    if (cleanTel) {
        wspLink.href = 'https://api.whatsapp.com/send?phone=' + cleanTel + '&text=' + encodeURIComponent('Hola ' + sol.nombre + ', te contactamos de COPACARNES por tu solicitud.');
    } else {
        wspLink.href = '#';
    }

    document.getElementById('modal_sol_tipo_badge').innerText = sol.sede_tipo || 'Solicitud General';
    document.getElementById('modal_sol_fecha').innerText = sol.fecha_hora || '';

    // Dirección Box
    const dirBox = document.getElementById('modal_sol_dir_box');
    if (direccion) {
        document.getElementById('modal_sol_direccion').innerText = direccion;
        document.getElementById('modal_sol_maps_btn').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(direccion);
        dirBox.style.display = 'flex';
    } else {
        dirBox.style.display = 'none';
    }

    // Carnicero Box
    const carnicerosAvatarsMap = <?php 
        $carniceros_avatars_map = [];
        if ($pdo) {
            try {
                $stmt_carn_av = $pdo->query("SELECT id, nombre, avatar FROM usuarios WHERE rol = 'carnicero'");
                while ($r_c = $stmt_carn_av->fetch()) {
                    $c_full = trim($r_c['nombre']);
                    $c_first = trim(explode('(', $c_full)[0]);
                    $av = !empty($r_c['avatar']) ? $r_c['avatar'] : 'images/carnicero-disponible.png';
                    if (!str_starts_with($av, '../') && !str_starts_with($av, 'http') && !str_starts_with($av, '/')) {
                        $av = '../' . $av;
                    }
                    $carniceros_avatars_map[mb_strtolower($c_first, 'UTF-8')] = $av;
                    $carniceros_avatars_map[mb_strtolower($c_full, 'UTF-8')] = $av;
                }
            } catch (Exception $e) {}
        }
        echo json_encode($carniceros_avatars_map, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); 
    ?>;

    const carniceroBox = document.getElementById('modal_sol_carnicero_box');
    if (carniceroNom) {
        document.getElementById('modal_sol_carnicero_nombre').innerText = carniceroNom;
        const cleanName = carniceroNom.split('(')[0].trim().toLowerCase();
        const fullLower = carniceroNom.trim().toLowerCase();
        const avatarSrc = carnicerosAvatarsMap[cleanName] || carnicerosAvatarsMap[fullLower] || '../images/carnicero-disponible.png';
        document.getElementById('modal_sol_carnicero_img').src = avatarSrc;
        carniceroBox.style.display = 'flex';
    } else {
        carniceroBox.style.display = 'none';
    }

    document.getElementById('modal_sol_detalles_texto').innerText = detallesLimpiados;

    const modal = document.getElementById('modalDetalleSolicitudWeb');
    modal.style.display = 'flex';
}

function cerrarModalDetalleSolicitud() {
    document.getElementById('modalDetalleSolicitudWeb').style.display = 'none';
}
</script>
