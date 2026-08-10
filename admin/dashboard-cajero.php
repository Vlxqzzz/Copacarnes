<?php
$page_title = "Dashboard Cajero";
$active_menu = "cajero";
$required_roles = ['dueno', 'admin', 'cajero'];

require_once __DIR__ . '/includes/admin_header.php';

$pos_msg = '';

// 1. PROCESAR ACCIONES DE ATENDER / CANCELAR SOLICITUDES DEL SITIO WEB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_solicitud'])) {
    $sol_id = intval($_POST['solicitud_id'] ?? 0);
    $nuevo_est = $_POST['nuevo_estado'] ?? 'atendido';

    if ($sol_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE solicitudes_contacto SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_est, $sol_id]);
            $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Estado del pedido del cliente actualizado a <strong>' . strtoupper($nuevo_est) . '</strong>.</div>';
        } catch (Exception $e) {}
    }
}

// 1.B PROCESAR ELIMINACIÓN DE SOLICITUDES DEL SITIO WEB (SINCRONIZADA CON CARNICERÍA, COCINA Y DOMICILIOS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_solicitud'])) {
    $sol_id = intval($_POST['solicitud_id'] ?? 0);

    if ($sol_id > 0 && $pdo) {
        try {
            $stmt_get = $pdo->prepare("SELECT nombre FROM solicitudes_contacto WHERE id = ?");
            $stmt_get->execute([$sol_id]);
            $sol_info = $stmt_get->fetch();

            if ($sol_info) {
                $cliente = trim($sol_info['nombre']);

                $stmt = $pdo->prepare("DELETE FROM solicitudes_contacto WHERE id = ?");
                $stmt->execute([$sol_id]);

                if (!empty($cliente)) {
                    $stmt_c = $pdo->prepare("DELETE FROM ordenes_carniceria WHERE cliente_nombre = ? OR corte_detalle LIKE ?");
                    $stmt_c->execute([$cliente, '%' . $cliente . '%']);

                    $stmt_k = $pdo->prepare("DELETE FROM comandas_cocina WHERE notas LIKE ? OR platillo_nombre LIKE ?");
                    $stmt_k->execute(['%' . $cliente . '%', '%' . $cliente . '%']);

                    $stmt_d = $pdo->prepare("DELETE FROM domicilios_envios WHERE cliente_nombre = ? OR direccion_entrega LIKE ?");
                    $stmt_d->execute([$cliente, '%' . $cliente . '%']);
                }
            }
            $pos_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Solicitud #' . $sol_id . ' eliminada de todos los paneles y áreas de la empresa.</div>';
        } catch (Exception $e) {}
    }
}
// 2. PROCESAR DESPACHO Y ASIGNACIÓN DE PEDIDOS (CARNICERÍA, RESTAURANTE O DOMICILIO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_despacho_btn'])) {
    $tipo_destino = $_POST['tipo_destino'] ?? 'carniceria';
    $cliente = trim($_POST['cliente_nombre'] ?? 'Cliente Web');
    $telefono = trim($_POST['cliente_telefono'] ?? '');
    $detalles = trim($_POST['detalles_pedido'] ?? '');
    $sol_id = intval($_POST['solicitud_id'] ?? 0);
    $carnicero_id = intval($_POST['carnicero_id'] ?? 0);
    $carnicero_nombre = trim($_POST['carnicero_nombre'] ?? 'Carnicero Disponible');

    if ($pdo) {
        try {
            // Preservar indicativo de Domicilio si existe en la solicitud web original
            if ($sol_id > 0) {
                $stmt_orig_sol = $pdo->prepare("SELECT detalles, sede_tipo FROM solicitudes_contacto WHERE id = ?");
                $stmt_orig_sol->execute([$sol_id]);
                if ($r_orig = $stmt_orig_sol->fetch()) {
                    if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/i', $r_orig['detalles'], $m_dir)) {
                        if (strpos($detalles, '[Entrega a Domicilio') === false) {
                            $detalles .= ' [Entrega a Domicilio en: ' . trim($m_dir[1]) . ']';
                        }
                    } elseif (strpos(strtolower($r_orig['sede_tipo']), 'domicilio') !== false || strpos(strtolower($r_orig['detalles']), 'domicilio') !== false) {
                        if (strpos($detalles, 'domicilio') === false) {
                            $detalles .= ' [Entrega a Domicilio]';
                        }
                    }
                }
            }

            if ($tipo_destino === 'carniceria') {
                if ($carnicero_nombre !== 'Carnicero Disponible' && !empty($carnicero_nombre) && $carnicero_id === 0) {
                    $first_name = trim(explode('(', $carnicero_nombre)[0]);
                    $stmt_c_find = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE (nombre LIKE ? OR nombre LIKE ?) AND rol = 'carnicero' LIMIT 1");
                    $stmt_c_find->execute(['%' . $carnicero_nombre . '%', '%' . $first_name . '%']);
                    if ($r_car = $stmt_c_find->fetch()) {
                        $carnicero_id = intval($r_car['id']);
                        $carnicero_nombre = $r_car['nombre'];
                    }
                }

                // Anti-duplicación para carnicería
                $stmt_chk_car = $pdo->prepare("SELECT id FROM ordenes_carniceria WHERE cliente_nombre = ? AND corte_detalle = ? AND estado IN ('pendiente', 'en_preparacion', 'en_corte') LIMIT 1");
                $stmt_chk_car->execute([$cliente, $detalles]);
                if (!$stmt_chk_car->fetch()) {
                    $num_ord = 'ORD-CAR-' . rand(100, 999);
                    $stmt = $pdo->prepare("INSERT INTO ordenes_carniceria (numero_orden, cliente_nombre, carnicero_id, carnicero_nombre, kilos, corte_detalle, estado) VALUES (?, ?, ?, ?, 1.00, ?, 'pendiente')");
                    $stmt->execute([$num_ord, $cliente, ($carnicero_id > 0 ? $carnicero_id : null), $carnicero_nombre, $detalles]);
                }

                if ($carnicero_nombre !== 'Carnicero Disponible' && !empty($carnicero_nombre)) {
                    $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Pedido despachado EXCLUSIVAMENTE a la cuenta del carnicero <strong>' . htmlspecialchars($carnicero_nombre) . '</strong>.</div>';
                } else {
                    $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-users"></i> Pedido enviado a TODOS los carniceros de la sede (Carnicero Disponible). El primero en ponerlo EN PROCESO lo tomará exclusivamente.</div>';
                }

            } elseif ($tipo_destino === 'restaurante') {
                $mesa_input = trim($_POST['mesa_numero_reserva'] ?? '');
                $hora_input = trim($_POST['hora_reserva'] ?? '');
                
                $mesa_final = !empty($mesa_input) ? $mesa_input : ('Reserva ' . $cliente);
                $platillo_final = !empty($detalles) ? $detalles : 'Platillos / Especificaciones de la Reserva';
                $notas_final = 'Cliente: ' . $cliente . (!empty($hora_input) ? ' | Hora/Notas: ' . $hora_input : '');

                $stmt_chk_coc = $pdo->prepare("SELECT id FROM comandas_cocina WHERE mesa_numero = ? AND platillo_nombre = ? AND estado IN ('pendiente', 'en_preparacion') LIMIT 1");
                $stmt_chk_coc->execute([$mesa_final, $platillo_final]);
                if (!$stmt_chk_coc->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO comandas_cocina (mesa_numero, platillo_nombre, notas, estado) VALUES (?, ?, ?, 'pendiente')");
                    $stmt->execute([$mesa_final, $platillo_final, $notas_final]);
                }
                $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-utensils"></i> Reserva / Pedido de <strong>' . htmlspecialchars($cliente) . '</strong> para <strong>' . htmlspecialchars($mesa_final) . '</strong> enviada directamente a la cocina de las cocineras.</div>';

            } elseif ($tipo_destino === 'domicilio') {
                $stmt_chk_dom = $pdo->prepare("SELECT id FROM domicilios_envios WHERE cliente_nombre = ? AND (direccion_entrega = ? OR notas_entrega = ?) AND estado IN ('pendiente', 'asignado', 'en_camino') LIMIT 1");
                $stmt_chk_dom->execute([$cliente, $detalles, $detalles]);
                if (!$stmt_chk_dom->fetch()) {
                    $num_fac = 'FAC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO domicilios_envios (numero_factura, cliente_nombre, cliente_telefono, direccion_entrega, tarifa_domicilio, estado) VALUES (?, ?, ?, ?, 8000, 'pendiente')");
                    $stmt->execute([$num_fac, $cliente, $telefono, $detalles]);
                }
                $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-motorcycle"></i> Pedido registrado en la Central de Domicilios para <strong>' . htmlspecialchars($cliente) . '</strong>.</div>';
            }

            if ($sol_id > 0) {
                $stmt_del_sol = $pdo->prepare("DELETE FROM solicitudes_contacto WHERE id = ?");
                $stmt_del_sol->execute([$sol_id]);
            }
        } catch (Exception $e) {
            $pos_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al procesar despacho: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// 2.5 PROCESAR DESPACHO DIRECTO A DOMICILIARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['despachar_a_domiciliario_btn'])) {
    $cli_nom = trim($_POST['domicilio_cliente'] ?? 'Cliente Domicilio');
    $cli_tel = trim($_POST['domicilio_telefono'] ?? '');
    $cli_dir = trim($_POST['domicilio_direccion'] ?? '');
    $cli_not = trim($_POST['domicilio_notas'] ?? '');
    $orig_tbl = trim($_POST['origen_tabla'] ?? '');
    $orig_id = intval($_POST['origen_id'] ?? 0);

    // Extraer dirección real de las notas/detalles si viene en formato tag
    if (empty($cli_dir) || $cli_dir === 'Dirección Registrada') {
        if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/', $cli_not, $m_dir)) {
            $cli_dir = trim($m_dir[1]);
        }
    }

    // Extraer teléfono real de las notas si viene en formato tag
    if (empty($cli_tel) || $cli_tel === 'Contacto Web') {
        if (preg_match('/\[Teléfono:\s*([^\]]+)\]/', $cli_not, $m_tel)) {
            $cli_tel = trim($m_tel[1]);
        }
    }

    // Buscar en solicitudes_contacto si aún falta teléfono o dirección real
    if (($cli_dir === 'Dirección Registrada' || empty($cli_dir) || $cli_tel === 'Contacto Web' || empty($cli_tel)) && $pdo) {
        $stmt_find_sol = $pdo->prepare("SELECT telefono, detalles FROM solicitudes_contacto WHERE nombre LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt_find_sol->execute(['%' . $cli_nom . '%']);
        if ($r_sol = $stmt_find_sol->fetch()) {
            if (empty($cli_tel) || $cli_tel === 'Contacto Web') {
                if (!empty($r_sol['telefono'])) $cli_tel = $r_sol['telefono'];
            }
            if (empty($cli_dir) || $cli_dir === 'Dirección Registrada') {
                if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/', $r_sol['detalles'], $m_dir)) {
                    $cli_dir = trim($m_dir[1]);
                }
            }
        }
    }

    if (empty($cli_dir)) $cli_dir = 'Sede Principal Copacarnes - Entregar a Cliente';
    if (empty($cli_tel)) $cli_tel = '3100000000';

    $monto_cobrar = floatval($_POST['monto_cobrar'] ?? 0);
    $estado_pago = trim($_POST['estado_pago'] ?? 'por_cobrar');
    $metodo_pago = trim($_POST['metodo_pago'] ?? 'efectivo');
    $comprobante_url = null;

    if (isset($_FILES['comprobante_pago']) && $_FILES['comprobante_pago']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['comprobante_pago']['tmp_name'];
        $file_name = $_FILES['comprobante_pago']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        if (in_array($ext, $allowed)) {
            $fecha_hoy = date('Y-m-d');
            $new_filename = 'transferencia_' . date('Ymd_His') . '_' . rand(100, 999) . '.' . $ext;
            
            // Estructura en Nube Empresarial: uploads/nube_empresarial/transferencias/YYYY-MM-DD/
            $cloud_dir = __DIR__ . '/uploads/nube_empresarial/transferencias/' . $fecha_hoy . '/';
            if (!is_dir($cloud_dir)) {
                mkdir($cloud_dir, 0777, true);
            }
            if (move_uploaded_file($file_tmp, $cloud_dir . $new_filename)) {
                $comprobante_url = 'uploads/nube_empresarial/transferencias/' . $fecha_hoy . '/' . $new_filename;

                // Registrar en Nube Empresarial ERP
                if ($pdo) {
                    try {
                        $nombre_carpeta_dia = 'Transferencias (' . $fecha_hoy . ')';
                        
                        // 1. Asegurar que la carpeta del día exista en nube_carpetas
                        $stmt_chk_car = $pdo->prepare("SELECT id FROM nube_carpetas WHERE nombre = ? LIMIT 1");
                        $stmt_chk_car->execute([$nombre_carpeta_dia]);
                        if (!$stmt_chk_car->fetch()) {
                            $stmt_ins_car = $pdo->prepare("INSERT INTO nube_carpetas (nombre, color, icono) VALUES (?, '#10b981', 'fa-folder-closed')");
                            $stmt_ins_car->execute([$nombre_carpeta_dia]);
                        }

                        // 2. Registrar el archivo en nube_archivos
                        $tamano_kb = file_exists($cloud_dir . $new_filename) ? round(filesize($cloud_dir . $new_filename) / 1024, 2) : 0;
                        $stmt_ins_file = $pdo->prepare("INSERT INTO nube_archivos (carpeta, nombre_archivo, nombre_original, tipo_archivo, tamano_kb, ruta_archivo, usuario_id, usuario_nombre, rol, comentarios, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cajero', ?, 'v1.0')");
                        $stmt_ins_file->execute([
                            $nombre_carpeta_dia,
                            $new_filename,
                            'Comprobante_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cli_nom) . '_' . $file_name,
                            strtoupper($ext),
                            $tamano_kb,
                            $comprobante_url,
                            $_SESSION['user']['id'] ?? 1,
                            $_SESSION['user']['nombre'] ?? 'Cajero POS',
                            'Comprobante de pago por transferencia del cliente ' . $cli_nom
                        ]);
                    } catch (Exception $e_nube) {}
                }
            }
        }
    }

    if ($pdo && !empty($cli_nom)) {
        try {
            $num_fac = 'FAC-DOM-' . rand(100, 999);

            // Prender el tag de origen para que el domiciliario sepa exactamente de dónde viene el pedido
            $origen_area_tag = ($orig_tbl === 'ordenes_carniceria') ? '[Origen: Carnicería]' : (($orig_tbl === 'comandas_cocina') ? '[Origen: Restaurante]' : '');
            if (!empty($origen_area_tag) && strpos($cli_not, '[Origen:') === false) {
                $cli_not = $origen_area_tag . ' ' . $cli_not;
            }

            // Estado inicial al asignar es 'pendiente' para que pase a 'en_camino' y luego 'entregado'
            $stmt_d = $pdo->prepare("INSERT INTO domicilios_envios (numero_factura, cliente_nombre, cliente_telefono, direccion_entrega, domiciliario_nombre, estado, notas_entrega, monto_cobrar, estado_pago, metodo_pago, comprobante_pago) VALUES (?, ?, ?, ?, 'Domiciliario Oficial', 'pendiente', ?, ?, ?, ?, ?)");
            $stmt_d->execute([$num_fac, $cli_nom, $cli_tel, $cli_dir, $cli_not, $monto_cobrar, $estado_pago, $metodo_pago, $comprobante_url]);

            if ($orig_tbl === 'ordenes_carniceria' && $orig_id > 0) {
                $stmt_u = $pdo->prepare("UPDATE ordenes_carniceria SET estado = 'entregado' WHERE id = ?");
                $stmt_u->execute([$orig_id]);
            } elseif ($orig_tbl === 'comandas_cocina' && $orig_id > 0) {
                $stmt_u = $pdo->prepare("UPDATE comandas_cocina SET estado = 'entregado' WHERE id = ?");
                $stmt_u->execute([$orig_id]);
            }

            $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-motorcycle"></i> Pedido de <strong>' . htmlspecialchars($cli_nom) . '</strong> enviado exitosamente al Domiciliario en estado <strong>PENDIENTE DE RECOGIDA</strong>.</div>';
        } catch (Exception $e) {
            $pos_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al despachar a domiciliario: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// 2.6 PROCESAR ENTREGA EN SEDE AL CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar_cliente_sede_btn'])) {
    $orig_tbl = trim($_POST['origen_tabla'] ?? '');
    $orig_id = intval($_POST['origen_id'] ?? 0);
    $cli_nom = trim($_POST['cliente_nombre'] ?? 'Cliente');

    if ($pdo && $orig_id > 0) {
        try {
            if ($orig_tbl === 'ordenes_carniceria') {
                $stmt_u = $pdo->prepare("UPDATE ordenes_carniceria SET estado = 'entregado' WHERE id = ?");
                $stmt_u->execute([$orig_id]);
            } elseif ($orig_tbl === 'comandas_cocina') {
                $stmt_u = $pdo->prepare("UPDATE comandas_cocina SET estado = 'entregado' WHERE id = ?");
                $stmt_u->execute([$orig_id]);
            }

            $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Pedido de <strong>' . htmlspecialchars($cli_nom) . '</strong> entregado al cliente en sede exitosamente.</div>';
        } catch (Exception $e) {}
    }
}

// 2.7 PROCESAR DESPACHO DE RESERVA WEB A COCINA (ASIGNAR MESA Y ENVIAR A COCINERAS)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['despachar_reserva_cocina_btn'])) {
    $sol_id = intval($_POST['solicitud_id'] ?? 0);
    $cliente = trim($_POST['cliente_nombre'] ?? 'Cliente Reserva');
    $telefono = trim($_POST['cliente_telefono'] ?? '');
    $mesa_input = trim($_POST['mesa_numero_reserva'] ?? 'Mesa Restaurante');
    $hora_input = trim($_POST['hora_reserva'] ?? '');
    $detalles = trim($_POST['detalles_reserva'] ?? 'Reserva de Mesa Restaurante');

    $mesa_final = !empty($mesa_input) ? $mesa_input : ('Reserva ' . $cliente);
    $platillo_final = '🍽️ RESERVA DE RESTAURANTE';
    $notas_final = '[RESERVA RESTAURANTE' . (!empty($hora_input) ? ' - HORA: ' . $hora_input : '') . '] [Cliente: ' . $cliente . ']' . (!empty($telefono) ? ' [Teléfono: ' . $telefono . ']' : '') . ' ' . $detalles;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comandas_cocina (mesa_numero, platillo_nombre, notas, estado) VALUES (?, ?, ?, 'pendiente')");
            $stmt->execute([$mesa_final, $platillo_final, $notas_final]);

            if ($sol_id > 0) {
                $stmt_u = $pdo->prepare("DELETE FROM solicitudes_contacto WHERE id = ?");
                $stmt_u->execute([$sol_id]);
            }

            $pos_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-calendar-check"></i> Reserva de <strong>' . htmlspecialchars($cliente) . '</strong> asignada a <strong>' . htmlspecialchars($mesa_final) . '</strong> enviada con éxito a la lista de reservas de las Cocineras.</div>';
        } catch (Exception $e) {
            $pos_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al procesar reserva a cocina: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// 3. CONSULTAR DATOS REALES DE PEDIDOS Y ASIGNACIONES DESDE BASE DE DATOS
$solicitudes_web = [];
$carniceros = [];
$ordenes_car = [];
$comandas_coc = [];
$domicilios = [];
$pedidos_listos_domicilio = [];
$pedidos_listos_sede = [];

if ($pdo) {
    try {
        $stmt_sol = $pdo->query("SELECT * FROM solicitudes_contacto WHERE (estado IS NULL OR estado = '' OR estado = 'pendiente') ORDER BY id DESC");
        $solicitudes_web = $stmt_sol->fetchAll();

        $stmt_c = $pdo->query("SELECT id, nombre, rol, sede_asignada, avatar FROM usuarios WHERE rol = 'carnicero' AND estado = 'activo' ORDER BY id ASC");
        $carniceros = $stmt_c->fetchAll();

        $stmt_ocar = $pdo->query("SELECT * FROM ordenes_carniceria ORDER BY id DESC LIMIT 10");
        $ordenes_car = $stmt_ocar->fetchAll();

        $stmt_ococ = $pdo->query("SELECT * FROM comandas_cocina ORDER BY id DESC LIMIT 10");
        $comandas_coc = $stmt_ococ->fetchAll();

        $stmt_dom = $pdo->query("SELECT * FROM domicilios_envios ORDER BY id DESC LIMIT 10");
        $domicilios = $stmt_dom->fetchAll();

        // Consultar TODOS los pedidos finalizados de Carnicería y clasificar entre Domicilio y Recoger en Sede
        $stmt_lsc_all = $pdo->query("SELECT id, numero_orden, cliente_nombre, carnicero_nombre, corte_detalle AS detalle, estado, fecha_hora FROM ordenes_carniceria WHERE estado IN ('finalizado', 'listo', 'terminado', 'listo_domicilio') ORDER BY id DESC");
        while ($r = $stmt_lsc_all->fetch()) {
            $is_domicilio = false;
            $dir = 'Dirección Registrada';
            $tel = '';

            // 1. Revisar si en la orden viene el tag o la palabra domicilio
            if ($r['estado'] === 'listo_domicilio' || strpos(strtolower($r['detalle']), 'domicilio') !== false) {
                $is_domicilio = true;
            }

            if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/i', $r['detalle'], $m_d)) {
                $dir = trim($m_d[1]);
                $is_domicilio = true;
            }

            // 2. Buscar en solicitudes_contacto por el nombre del cliente
            $stmt_t = $pdo->prepare("SELECT telefono, detalles, sede_tipo FROM solicitudes_contacto WHERE nombre LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt_t->execute(['%' . $r['cliente_nombre'] . '%']);
            if ($r_t = $stmt_t->fetch()) {
                $tel = $r_t['telefono'];
                $sol_combo = strtolower($r_t['sede_tipo'] . ' ' . $r_t['detalles']);
                if (strpos($sol_combo, 'domicilio') !== false || strpos($sol_combo, '[entrega a domicilio') !== false) {
                    $is_domicilio = true;
                }
                if ($dir === 'Dirección Registrada' && preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/i', $r_t['detalles'], $m_sol_dir)) {
                    $dir = trim($m_sol_dir[1]);
                }
            }

            if (empty($tel) && preg_match('/\[Teléfono:\s*([^\]]+)\]/i', $r['detalle'], $m_t)) {
                $tel = trim($m_t[1]);
            }

            if ($is_domicilio) {
                $pedidos_listos_domicilio[] = [
                    'id' => $r['id'],
                    'tabla' => 'ordenes_carniceria',
                    'origen_area' => '🥩 Carnicería',
                    'cliente_nombre' => $r['cliente_nombre'],
                    'telefono' => !empty($tel) ? $tel : 'Contacto Web',
                    'direccion' => $dir,
                    'detalle' => $r['detalle'],
                    'fecha_hora' => $r['fecha_hora']
                ];
            } else {
                $pedidos_listos_sede[] = [
                    'id' => $r['id'],
                    'tabla' => 'ordenes_carniceria',
                    'origen_area' => '🥩 Carnicería',
                    'num_doc' => $r['numero_orden'],
                    'cliente_nombre' => $r['cliente_nombre'],
                    'encargado' => $r['carnicero_nombre'],
                    'detalle' => $r['detalle'],
                    'fecha_hora' => $r['fecha_hora']
                ];
            }
        }

        // Consultar TODOS los pedidos finalizados de Cocina / Restaurante y clasificar entre Domicilio y Recoger en Sede
        $stmt_lsk_all = $pdo->query("SELECT id, mesa_numero, platillo_nombre, notas AS detalle, estado, fecha_hora FROM comandas_cocina WHERE estado IN ('finalizado', 'listo', 'terminado', 'listo_domicilio') ORDER BY id DESC");
        while ($r = $stmt_lsk_all->fetch()) {
            $full_text = $r['mesa_numero'] . ' ' . $r['platillo_nombre'] . ' ' . $r['detalle'];
            $is_domicilio = false;
            $dir = 'Dirección Registrada';
            $tel = '';

            if ($r['estado'] === 'listo_domicilio' || strpos(strtolower($full_text), 'domicilio') !== false) {
                $is_domicilio = true;
            }

            if (preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/i', $full_text, $m_d)) {
                $dir = trim($m_d[1]);
                $is_domicilio = true;
            }

            // Extraer cliente real
            $cli_nombre_real = 'Cliente Restaurante';
            if (preg_match('/(?:Pedido Web|Reserva)\s+(.+)/i', $r['mesa_numero'], $m_m)) {
                $cli_nombre_real = trim($m_m[1]);
            } elseif (preg_match('/Cliente:\s*([^\|\[\]]+)/i', $full_text, $m_c)) {
                $cli_nombre_real = trim($m_c[1]);
            } elseif (!empty($r['mesa_numero']) && strpos($r['mesa_numero'], 'Mesa') === false) {
                $cli_nombre_real = trim($r['mesa_numero']);
            }

            $stmt_t = $pdo->prepare("SELECT telefono, detalles, sede_tipo FROM solicitudes_contacto WHERE nombre LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt_t->execute(['%' . $cli_nombre_real . '%']);
            if ($r_t = $stmt_t->fetch()) {
                $tel = $r_t['telefono'];
                $sol_combo = strtolower($r_t['sede_tipo'] . ' ' . $r_t['detalles']);
                if (strpos($sol_combo, 'domicilio') !== false || strpos($sol_combo, '[entrega a domicilio') !== false) {
                    $is_domicilio = true;
                }
                if ($dir === 'Dirección Registrada' && preg_match('/\[Entrega a Domicilio en:\s*([^\]]+)\]/i', $r_t['detalles'], $m_sol_dir)) {
                    $dir = trim($m_sol_dir[1]);
                }
            }

            if (empty($tel) && preg_match('/\[Teléfono:\s*([^\]]+)\]/i', $full_text, $m_t)) {
                $tel = trim($m_t[1]);
            }

            // Detalle limpio de platillos
            $det_clean_p = preg_replace('/\[[^\]]+\]/', '', $r['platillo_nombre']);
            $det_clean_p = trim($det_clean_p) ?: $r['platillo_nombre'];

            if ($is_domicilio) {
                $pedidos_listos_domicilio[] = [
                    'id' => $r['id'],
                    'tabla' => 'comandas_cocina',
                    'origen_area' => '🍽️ Restaurante',
                    'cliente_nombre' => $cli_nombre_real,
                    'telefono' => !empty($tel) ? $tel : 'Contacto Web',
                    'direccion' => $dir,
                    'detalle' => $det_clean_p,
                    'fecha_hora' => $r['fecha_hora']
                ];
            } else {
                $pedidos_listos_sede[] = [
                    'id' => $r['id'],
                    'tabla' => 'comandas_cocina',
                    'origen_area' => '🍽️ Restaurante',
                    'num_doc' => 'COM-' . str_pad($r['id'], 3, '0', STR_PAD_LEFT),
                    'cliente_nombre' => $cli_nombre_real,
                    'encargado' => 'Chef & Cocina',
                    'detalle' => $det_clean_p,
                    'fecha_hora' => $r['fecha_hora']
                ];
            }
        }
    } catch (Exception $e) {}
}
?>

<!-- Encabezado Central de Despacho -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-headset text-gold"></i> Dashboard Cajero
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Recepción de clientes de la web, asignación inteligente de carniceros por sede y despacho a cocina y domicilios.
        </p>
    </div>
    <div style="display: flex; gap: 0.8rem;">
        <a href="nube-empresarial.php" class="btn-export" style="background: rgba(59, 130, 246, 0.2); border-color: #3b82f6; color: #60a5fa;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Nube Empresarial (Archivos)
        </a>
    </div>
</div>

<?php echo $pos_msg; ?>

<!-- Tarjetas KPI de Resumen de la Central -->
<div class="kpi-grid">
    <div class="kpi-card" style="cursor: pointer;">
        <div class="kpi-title"><i class="fa-solid fa-inbox text-gold"></i> Pedidos Recibidos Web</div>
        <div class="kpi-value"><?php echo count($solicitudes_web); ?> Solicitudes</div>
        <div class="kpi-sub" style="color:var(--gold);">Desde la Portada Web</div>
    </div>
    <div class="kpi-card" style="cursor: pointer;">
        <div class="kpi-title"><i class="fa-solid fa-drumstick-bite text-gold"></i> Despachados a Carnicería</div>
        <div class="kpi-value" style="color:#f59e0b;"><?php echo count($ordenes_car); ?> Pedidos</div>
        <div class="kpi-sub">Trabajo de Carniceros</div>
    </div>
    <div class="kpi-card" style="cursor: pointer;">
        <div class="kpi-title"><i class="fa-solid fa-utensils text-gold"></i> Despachados a Cocina</div>
        <div class="kpi-value" style="color:#3b82f6;"><?php echo count($comandas_coc); ?> Comandas</div>
        <div class="kpi-sub">Chef & Cocina KDS</div>
    </div>
    <div class="kpi-card" style="cursor: pointer; border-left-color:#10b981;">
        <div class="kpi-title"><i class="fa-solid fa-motorcycle" style="color:#10b981;"></i> Enviados a Domicilio</div>
        <div class="kpi-value" style="color:#34d399;"><?php echo count($domicilios); ?> Envíos</div>
        <div class="kpi-sub">Envíos de Domicilio</div>
    </div>
</div>

<!-- SECCIÓN 0.1: TABLA DE SOLICITUDES Y PEDIDOS RECIBIDOS DESDE LA WEB -->
<div style="margin-bottom: 2rem;">
    <?php require_once __DIR__ . '/includes/solicitudes_tabla.php'; ?>
</div>
<div class="data-table-card" style="margin-bottom: 2rem; border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.08);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
        <h3 style="margin: 0; color: #34d399; font-size: 1.15rem;">
            <i class="fa-solid fa-motorcycle"></i> Pedidos Terminados Listos para Despachar a Domicilio
        </h3>
        <span class="status-pill success"><?php echo count($pedidos_listos_domicilio); ?> Listos para Envío</span>
    </div>

    <?php if (empty($pedidos_listos_domicilio)): ?>
        <p style="font-size:0.9rem; color:var(--text-muted); margin:0; padding:0.5rem 0;">No hay pedidos de carnicería o cocina pendientes de asignación de domiciliario en este momento.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:1rem;">
            <?php foreach ($pedidos_listos_domicilio as $pld): ?>
            <div style="background:rgba(0,0,0,0.4); padding:1rem; border-radius:8px; border:1px solid #10b981; display:flex; flex-direction:column; gap:0.6rem;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong style="color:#fff; font-size:1rem;"><?php echo htmlspecialchars($pld['cliente_nombre']); ?></strong>
                    <span class="status-pill warning" style="font-size:0.75rem;"><i class="fa-solid fa-store"></i> <?php echo htmlspecialchars($pld['origen_area']); ?></span>
                </div>
                <div style="font-size:0.85rem; color:var(--text-muted); line-height:1.4;">
                    <div><strong style="color:var(--gold);"><i class="fa-solid fa-align-left"></i> Detalle:</strong> <?php 
                        $det_clean = preg_replace('/\[[^\]]+\]/', '', $pld['detalle']);
                        echo htmlspecialchars(trim($det_clean) ?: $pld['detalle']); 
                    ?></div>
                    <div><strong style="color:#34d399;"><i class="fa-solid fa-location-dot"></i> Entrega en:</strong> <?php echo htmlspecialchars($pld['direccion']); ?></div>
                </div>
                <form action="dashboard-cajero.php" method="POST" enctype="multipart/form-data" style="margin-top:0.4rem; display:flex; flex-direction:column; gap:0.6rem; background:rgba(15,23,42,0.8); padding:0.85rem; border-radius:8px; border:1px solid rgba(212,175,55,0.4);">
                    <input type="hidden" name="domicilio_cliente" value="<?php echo htmlspecialchars($pld['cliente_nombre']); ?>">
                    <input type="hidden" name="domicilio_telefono" value="<?php echo htmlspecialchars($pld['telefono']); ?>">
                    <input type="hidden" name="domicilio_direccion" value="<?php echo htmlspecialchars($pld['direccion']); ?>">
                    <input type="hidden" name="domicilio_notas" value="<?php echo htmlspecialchars($pld['detalle']); ?>">
                    <input type="hidden" name="origen_tabla" value="<?php echo htmlspecialchars($pld['tabla']); ?>">
                    <input type="hidden" name="origen_id" value="<?php echo htmlspecialchars($pld['id']); ?>">

                    <div style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap:0.6rem; width:100%; box-sizing:border-box;">
                        <div style="min-width:0; width:100%;">
                            <label style="font-size:0.75rem; color:#ffffff; font-weight:700; display:block; margin-bottom:0.2rem;">Estado de Pago:</label>
                            <select name="estado_pago" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.82rem; padding:0.4rem 0.6rem; background:#0f172a !important; color:#ffffff !important; border:1px solid #d4af37 !important; border-radius:6px;" onchange="toggleCobroCajero(this)">
                                <option value="por_cobrar" style="background:#0f172a; color:#ffffff;">💵 Por Cobrar</option>
                                <option value="pagado" style="background:#0f172a; color:#ffffff;">✅ Ya Pagado</option>
                            </select>
                        </div>
                        <div class="box-monto-cobrar-cajero" style="min-width:0; width:100%;">
                            <label style="font-size:0.75rem; color:#d4af37; font-weight:700; display:block; margin-bottom:0.2rem;">Valor a Cobrar ($):</label>
                            <input type="number" name="monto_cobrar" value="0" step="any" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.85rem; padding:0.4rem 0.6rem; background:#0f172a !important; color:#34d399 !important; border:1px solid #d4af37 !important; font-weight:800; border-radius:6px;" placeholder="0">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap:0.6rem; width:100%; box-sizing:border-box; margin-top:0.4rem;">
                        <div style="min-width:0; width:100%;">
                            <label style="font-size:0.75rem; color:#ffffff; font-weight:700; display:block; margin-bottom:0.2rem;">Método de Pago:</label>
                            <select name="metodo_pago" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.82rem; padding:0.4rem 0.6rem; background:#0f172a !important; color:#ffffff !important; border:1px solid #d4af37 !important; border-radius:6px;" onchange="toggleComprobanteCajero(this)">
                                <option value="efectivo" style="background:#0f172a; color:#ffffff;">💵 Efectivo</option>
                                <option value="transferencia" style="background:#0f172a; color:#ffffff;">📲 Transferencia</option>
                            </select>
                        </div>
                        <div class="box-comprobante-cajero" style="display:none; min-width:0; width:100%;">
                            <label style="font-size:0.75rem; color:#60a5fa; font-weight:700; display:block; margin-bottom:0.2rem;"><i class="fa-solid fa-paperclip"></i> Comprobante:</label>
                            <input type="file" name="comprobante_pago" accept="image/*,.pdf" class="form-control" style="width:100%; box-sizing:border-box; font-size:0.75rem; padding:0.25rem 0.4rem; background:#0f172a !important; color:#ffffff !important; border:1px solid #3b82f6 !important; border-radius:6px;">
                        </div>
                    </div>

                    <button type="submit" name="despachar_a_domiciliario_btn" class="btn-export" style="width:100%; justify-content:center; background:linear-gradient(135deg, #10b981, #047857); border-color:#10b981; color:#fff; font-weight:800; padding:0.65rem; margin-top:0.3rem;">
                        <i class="fa-solid fa-paper-plane"></i> 🚀 DESPACHAR A DOMICILIARIO
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- SECCIÓN: PEDIDOS LISTOS PARA ENTREGAR EN SEDE (RECOGER EN SEDE) -->
<?php if (!empty($pedidos_listos_sede)): ?>
<div class="data-table-card" style="border-left: 4px solid var(--gold); margin-bottom: 2rem;">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-store text-gold"></i> Pedidos Listos para Entregar en Sede (Recoger en Sede)
        </h3>
        <span class="product-badge" style="background: rgba(212, 175, 55, 0.2); color: var(--gold); border: 1px solid var(--gold); padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.8rem;">
            <?php echo count($pedidos_listos_sede); ?> Listos para Recoger
        </span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">
        <?php foreach ($pedidos_listos_sede as $pls): ?>
        <div style="background: rgba(212, 175, 55, 0.08); border: 1.5px solid var(--gold); padding: 1rem; border-radius: 8px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong style="color: #fff; font-size: 1.05rem;"><?php echo htmlspecialchars($pls['cliente_nombre']); ?></strong>
                    <span class="product-badge" style="background: rgba(212, 175, 55, 0.2); color: var(--gold); border: 1px solid var(--gold); font-size: 0.75rem;">
                        <?php echo htmlspecialchars($pls['origen_area']); ?>
                    </span>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                    <i class="fa-solid fa-receipt"></i> Ref: <?php echo htmlspecialchars($pls['num_doc']); ?> &bull; Encargado: <strong style="color: #60a5fa;"><?php echo htmlspecialchars($pls['encargado']); ?></strong>
                </div>
                <div style="background: rgba(0,0,0,0.4); padding: 0.6rem; border-radius: 6px; font-size: 0.85rem; color: #fff; margin-bottom: 0.8rem;">
                    <?php 
                        $det_clean_sede = preg_replace('/\[[^\]]+\]/', '', $pls['detalle']);
                        echo htmlspecialchars(trim($det_clean_sede) ?: $pls['detalle']); 
                    ?>
                </div>
            </div>

            <form action="dashboard-cajero.php" method="POST" style="width: 100%;">
                <input type="hidden" name="origen_tabla" value="<?php echo htmlspecialchars($pls['tabla']); ?>">
                <input type="hidden" name="origen_id" value="<?php echo $pls['id']; ?>">
                <input type="hidden" name="cliente_nombre" value="<?php echo htmlspecialchars($pls['cliente_nombre']); ?>">
                <button type="submit" name="entregar_cliente_sede_btn" class="btn-export" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #d4af37, #997a15); border-color: var(--gold); color: #000; padding: 0.65rem; font-weight: 800; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-check"></i> ✅ ENTREGAR AL CLIENTE EN SEDE
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>





<!-- SECCIÓN 3: MONITOREO DE ESTADOS EN VIVO DE TODOS LOS DESPACHOS -->
<div class="data-table-card">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-eye text-gold"></i> Monitoreo de Estados en Vivo (Carnicería, Restaurante y Domicilio)
        </h3>
        <span style="font-size:0.85rem; color:var(--text-muted);">El cajero puede visualizar en tiempo real el progreso de cada área</span>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.2rem;">
        <!-- Despachos Carnicería -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
            <h4 style="margin:0 0 0.8rem 0; color:var(--gold); font-size:0.95rem;"><i class="fa-solid fa-drumstick-bite"></i> Estado en Carnicería</h4>
            <?php if (empty($ordenes_car)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin órdenes recientes.</p>
            <?php else: ?>
                <?php foreach ($ordenes_car as $oc): 
                    $cli_car = !empty($oc['cliente_nombre']) ? $oc['cliente_nombre'] : 'Cliente Mostrador';
                    $car_nom = !empty($oc['carnicero_nombre']) ? $oc['carnicero_nombre'] : 'Carnicero Disponible';
                    $kilos_car = !empty($oc['kilos']) ? $oc['kilos'] : '1.0';
                    $st_car = $oc['estado'] ?? 'pendiente';
                    $is_dom_oc = ($st_car === 'listo_domicilio' || strpos($oc['corte_detalle'], '[Entrega a Domicilio') !== false);
                ?>
                <div style="padding:0.65rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                    <strong style="color:#fff;"><?php echo htmlspecialchars((string)$cli_car); ?></strong><br>
                    <span style="color:var(--text-muted); font-size:0.78rem;">
                        Carnicero: <strong style="color:var(--gold);"><?php echo htmlspecialchars((string)$car_nom); ?></strong> &bull; <?php echo htmlspecialchars((string)$kilos_car); ?>kg
                    </span><br>
                    <?php if ($st_car === 'finalizado' || $st_car === 'terminado' || $st_car === 'listo_domicilio'): ?>
                        <?php if ($is_dom_oc): ?>
                            <span class="status-pill success" style="font-size:0.75rem;"><i class="fa-solid fa-motorcycle"></i> TERMINADO (Enviar a Domicilio)</span>
                        <?php else: ?>
                            <span class="status-pill success" style="font-size:0.75rem; background:rgba(212,175,55,0.2); color:var(--gold); border:1px solid var(--gold);"><i class="fa-solid fa-store"></i> RECOGER EN SEDE (Listo)</span>
                        <?php endif; ?>
                    <?php elseif ($st_car === 'en_preparacion' || $st_car === 'en_corte'): ?>
                        <span class="status-pill warning" style="font-size:0.75rem;"><i class="fa-solid fa-fire"></i> EN PROCESO (Desposte)</span>
                    <?php elseif ($st_car === 'entregado'): ?>
                        <span class="status-pill info" style="font-size:0.75rem; background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid #3b82f6;"><i class="fa-solid fa-check-double"></i> DESPACHADO / ENTREGADO</span>
                    <?php else: ?>
                        <span class="status-pill danger" style="font-size:0.75rem;"><i class="fa-solid fa-clock"></i> PENDIENTE EN CARNICERÍA</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Despachos Cocina -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid #3b82f6;">
            <h4 style="margin:0 0 0.8rem 0; color:#60a5fa; font-size:0.95rem;"><i class="fa-solid fa-utensils"></i> Estado en Restaurante (Cocina)</h4>
            <?php if (empty($comandas_coc)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin comandas recientes.</p>
            <?php else: ?>
                <?php foreach ($comandas_coc as $cc): 
                    $mesa_lbl = !empty($cc['mesa_numero']) ? $cc['mesa_numero'] : (!empty($cc['mesa']) ? $cc['mesa'] : 'Mesa 1');
                    $plato_lbl = !empty($cc['platillo_nombre']) ? $cc['platillo_nombre'] : (!empty($cc['platos_detalles']) ? $cc['platos_detalles'] : (!empty($cc['observaciones']) ? $cc['observaciones'] : 'Comanda Restaurante'));
                    $st_coc = $cc['estado'] ?? 'pendiente';
                    $is_dom_cc = ($st_coc === 'listo_domicilio' || strpos($cc['notas'], '[Entrega a Domicilio') !== false);
                ?>
                <div style="padding:0.65rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                    <strong style="color:#fff;"><?php echo htmlspecialchars((string)$mesa_lbl); ?></strong><br>
                    <span style="color:var(--text-muted); font-size:0.78rem;"><?php echo htmlspecialchars((string)$plato_lbl); ?></span><br>
                    <?php if ($st_coc === 'listo' || $st_coc === 'terminado' || $st_coc === 'listo_domicilio'): ?>
                        <?php if ($is_dom_cc): ?>
                            <span class="status-pill success" style="font-size:0.75rem;"><i class="fa-solid fa-motorcycle"></i> TERMINADO (Enviar a Domicilio)</span>
                        <?php else: ?>
                            <span class="status-pill success" style="font-size:0.75rem; background:rgba(212,175,55,0.2); color:var(--gold); border:1px solid var(--gold);"><i class="fa-solid fa-store"></i> RECOGER EN SEDE (Listo)</span>
                        <?php endif; ?>
                    <?php elseif ($st_coc === 'en_preparacion' || $st_coc === 'en_proceso'): ?>
                        <span class="status-pill warning" style="font-size:0.75rem;"><i class="fa-solid fa-fire"></i> EN PREPARACIÓN</span>
                    <?php elseif ($st_coc === 'entregado'): ?>
                        <span class="status-pill info" style="font-size:0.75rem; background:rgba(59,130,246,0.2); color:#60a5fa; border:1px solid #3b82f6;"><i class="fa-solid fa-check-double"></i> DESPACHADO / ENTREGADO</span>
                    <?php else: ?>
                        <span class="status-pill danger" style="font-size:0.75rem;"><i class="fa-solid fa-clock"></i> PENDIENTE EN COCINA</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Despachos Domicilio -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid #10b981;">
            <h4 style="margin:0 0 0.8rem 0; color:#34d399; font-size:0.95rem;"><i class="fa-solid fa-motorcycle"></i> Estado en Ruta de Domicilio</h4>
            <?php if (empty($domicilios)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin domicilios recientes.</p>
            <?php else: ?>
                <?php foreach ($domicilios as $dm): 
                    $cli_dom = !empty($dm['cliente_nombre']) ? $dm['cliente_nombre'] : 'Cliente Domicilio';
                    $dir_dom = !empty($dm['direccion_entrega']) ? $dm['direccion_entrega'] : (!empty($dm['numero_factura']) ? $dm['numero_factura'] : 'Dirección Registrada');
                    $st_dom = $dm['estado'] ?? 'pendiente';
                ?>
                <div style="padding:0.65rem 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.85rem;">
                    <strong style="color:#fff;"><?php echo htmlspecialchars((string)$cli_dom); ?></strong><br>
                    <span style="color:var(--text-muted); font-size:0.78rem;"><?php echo htmlspecialchars((string)$dir_dom); ?></span><br>
                    <?php if ($st_dom === 'entregado' || $st_dom === 'terminado'): ?>
                        <span class="status-pill success" style="font-size:0.75rem;"><i class="fa-solid fa-circle-check"></i> ENTREGADO AL CLIENTE</span>
                    <?php elseif ($st_dom === 'en_camino' || $st_dom === 'asignado'): ?>
                        <span class="status-pill warning" style="font-size:0.75rem; background:rgba(245,158,11,0.2); color:#fbbf24; border:1px solid #f59e0b;"><i class="fa-solid fa-motorcycle"></i> EN CAMINO CON DOMICILIARIO</span>
                    <?php else: ?>
                        <span class="status-pill danger" style="font-size:0.75rem;"><i class="fa-solid fa-clock"></i> PENDIENTE DE RECOGIDA</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchDestino(tipo) {
    document.getElementById('box_carniceria').style.display = (tipo === 'carniceria') ? 'block' : 'none';
    document.getElementById('box_restaurante').style.display = (tipo === 'restaurante') ? 'block' : 'none';
    document.getElementById('box_domicilio').style.display = (tipo === 'domicilio') ? 'block' : 'none';

    const boxCarn = document.getElementById('disp_carnicero_box');
    const solIdElem = document.getElementById('despacho_solicitud_id');
    const solId = solIdElem ? parseInt(solIdElem.value || 0) : 0;

    if (boxCarn) {
        if (tipo === 'carniceria' && solId > 0) {
            boxCarn.style.setProperty('display', 'inline-flex', 'important');
        } else {
            boxCarn.style.setProperty('display', 'none', 'important');
        }
    }
}

function selectButcherCard(cardElem, id) {
    document.querySelectorAll('.butcher-card').forEach(c => {
        c.style.borderColor = 'var(--border-color)';
        c.style.background = 'rgba(0,0,0,0.4)';
        const subBadge = c.querySelector('.status-badge-sub');
        if (subBadge) subBadge.style.display = 'none';
    });

    cardElem.style.borderColor = 'var(--gold)';
    cardElem.style.background = 'rgba(212, 175, 55, 0.18)';
    const subBadge = cardElem.querySelector('.status-badge-sub');
    if (subBadge) subBadge.style.display = 'block';

    document.getElementById('selected_carnicero_id').value = id;
}

function filtrarCarnicerosPorSede(sedeTexto) {
    const badge = document.getElementById('sede_filtro_badge');
    const isSecundaria = (sedeTexto && (sedeTexto.toLowerCase().includes('secundaria') || sedeTexto.toLowerCase().includes('boutique')));
    const targetSede = isSecundaria ? 'Sede Secundaria' : 'Sede Principal';

    if (badge) {
        badge.innerHTML = '<i class="fa-solid fa-store"></i> Carniceros: ' + targetSede;
    }

    document.querySelectorAll('#grid_carniceros_cards .butcher-card').forEach(card => {
        const cardSede = card.getAttribute('data-sede');
        if (cardSede === 'all') {
            card.style.display = 'block';
        } else if (cardSede === targetSede) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function cargarEnDespacho(sol) {
    document.getElementById('despacho_solicitud_id').value = sol.id || 0;
    document.getElementById('despacho_cliente').value = sol.nombre || '';
    document.getElementById('despacho_telefono').value = sol.telefono || '';
    let detallesPreservados = sol.detalles || '';
    detallesPreservados = detallesPreservados.replace(/\[Carnicero Seleccionado:\s*[^\]]+\]/gi, '').trim();
    document.getElementById('despacho_detalles').value = detallesPreservados;

    const mesaElem = document.getElementById('mesa_numero_reserva');
    if (mesaElem) {
        mesaElem.value = 'Reserva - ' + (sol.nombre || 'Cliente Web');
    }

    // Extraer Carnicero Seleccionado por el cliente si viene en detalles
    let carniceroNom = 'Carnicero Disponible';
    let carniceroId = 0;

    if (sol.detalles && sol.detalles.includes('[Carnicero Seleccionado:')) {
        const match = sol.detalles.match(/\[Carnicero Seleccionado:\s*([^\]]+)\]/);
        if (match && match[1]) {
            carniceroNom = match[1].trim();
        }
    }

    document.getElementById('despacho_carnicero_nombre').value = carniceroNom;
    document.getElementById('despacho_carnicero_id').value = carniceroId;

    document.getElementById('disp_cliente_nombre').innerText = sol.nombre || 'Cliente Web';
    document.getElementById('disp_carnicero_nombre').innerText = carniceroNom;
    document.getElementById('disp_detalles_texto').innerText = sol.detalles || 'Sin especificaciones';

    const radCarniceria = document.querySelector('input[name="tipo_destino"][value="carniceria"]');
    const radRestaurante = document.querySelector('input[name="tipo_destino"][value="restaurante"]');
    const radDomicilio = document.querySelector('input[name="tipo_destino"][value="domicilio"]');

    if (radCarniceria) radCarniceria.disabled = false;
    if (radRestaurante) radRestaurante.disabled = false;
    if (radDomicilio) radDomicilio.disabled = false;

    let targetDestino = 'carniceria';

    if (sol.sede_tipo && sol.sede_tipo.toLowerCase().includes('restaurante')) {
        targetDestino = 'restaurante';
        if (radCarniceria) radCarniceria.disabled = true;
        if (radDomicilio) radDomicilio.disabled = true;
    } else if (sol.sede_tipo && (sol.sede_tipo.toLowerCase().includes('carniceria') || sol.sede_tipo.toLowerCase().includes('carnicería'))) {
        targetDestino = 'carniceria';
        if (radRestaurante) radRestaurante.disabled = true;
    } else if (sol.sede_tipo && sol.sede_tipo.toLowerCase().includes('domicilio')) {
        targetDestino = 'domicilio';
    }

    const radTarget = document.querySelector('input[name="tipo_destino"][value="' + targetDestino + '"]');
    if (radTarget) radTarget.checked = true;
    switchDestino(targetDestino);

    document.getElementById('seccion-despacho-form').scrollIntoView({behavior:'smooth'});
}

function toggleCobroCajero(sel) {
    const form = sel.closest('form');
    const montoBox = form.querySelector('.box-monto-cobrar-cajero');
    const montoInput = form.querySelector('input[name="monto_cobrar"]');
    if (sel.value === 'pagado') {
        if (montoInput) montoInput.value = '0';
        if (montoBox) montoBox.style.setProperty('display', 'none', 'important');
    } else {
        if (montoBox) montoBox.style.setProperty('display', 'block', 'important');
    }
}

function toggleComprobanteCajero(sel) {
    const form = sel.closest('form');
    const compBox = form.querySelector('.box-comprobante-cajero');
    if (compBox) {
        compBox.style.display = (sel.value === 'transferencia') ? 'block' : 'none';
    }
}
</script>

<!-- OVERLAY MODAL: DETALLE COMPLETO Y ORDENADO DE SOLICITUD WEB (CENTRAL DE PEDIDOS) -->
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

            <!-- Posición Señalada en Rojo: Dirección / Modalidad de Entrega -->
            <div id="modal_sol_dir_header_box" style="display:none; padding:0.45rem 0.85rem; border-radius:8px; font-weight:800; font-size:0.88rem; align-items:center; gap:0.4rem; border:1px solid #10b981; background:rgba(16,185,129,0.2); color:#34d399;">
                <span id="modal_sol_dir_header_text"><i class="fa-solid fa-motorcycle"></i> Domicilio</span>
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
    document.getElementById('modal_sol_subtitulo').innerText = 'ID Solicitud #' + String(sol.id || 0).padStart(3, '0');
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

    // Dirección / Modalidad en la Posición Señalada en Rojo
    const dirHeaderBox = document.getElementById('modal_sol_dir_header_box');
    const dirHeaderText = document.getElementById('modal_sol_dir_header_text');
    if (direccion) {
        dirHeaderText.innerHTML = '<i class="fa-solid fa-motorcycle"></i> Domicilio: <strong style="color:#fff;">' + direccion + '</strong>';
        dirHeaderBox.style.background = 'rgba(16, 185, 129, 0.25)';
        dirHeaderBox.style.borderColor = '#10b981';
        dirHeaderBox.style.color = '#34d399';
        dirHeaderBox.style.display = 'inline-flex';
    } else if ((sol.sede_tipo || '').toLowerCase().includes('domicilio') || (rawDetalles || '').toLowerCase().includes('domicilio')) {
        dirHeaderText.innerHTML = '<i class="fa-solid fa-motorcycle"></i> Entrega a Domicilio';
        dirHeaderBox.style.background = 'rgba(16, 185, 129, 0.25)';
        dirHeaderBox.style.borderColor = '#10b981';
        dirHeaderBox.style.color = '#34d399';
        dirHeaderBox.style.display = 'inline-flex';
    } else if ((sol.sede_tipo || '').toLowerCase().includes('reserva') || (rawDetalles || '').toLowerCase().includes('reserva')) {
        dirHeaderText.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Reserva de Mesa';
        dirHeaderBox.style.background = 'rgba(59, 130, 246, 0.25)';
        dirHeaderBox.style.borderColor = '#3b82f6';
        dirHeaderBox.style.color = '#60a5fa';
        dirHeaderBox.style.display = 'inline-flex';
    } else {
        dirHeaderText.innerHTML = '<i class="fa-solid fa-store"></i> Recoger en Sede';
        dirHeaderBox.style.background = 'rgba(212, 175, 55, 0.25)';
        dirHeaderBox.style.borderColor = 'var(--gold)';
        dirHeaderBox.style.color = 'var(--gold)';
        dirHeaderBox.style.display = 'inline-flex';
    }

    // Dirección Box Inferior
    const dirBox = document.getElementById('modal_sol_dir_box');
    if (direccion) {
        document.getElementById('modal_sol_direccion').innerText = direccion;
        document.getElementById('modal_sol_maps_btn').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(direccion);
        dirBox.style.display = 'flex';
    } else {
        dirBox.style.display = 'none';
    }

    // Carnicero Box
    const carniceroBox = document.getElementById('modal_sol_carnicero_box');
    if (carniceroNom) {
        document.getElementById('modal_sol_carnicero_nombre').innerText = carniceroNom;
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

<!-- MODAL: ASIGNAR MESA Y DESPACHAR RESERVA A COCINA -->
<div id="modalAsignarReservaCocina" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:520px; width:100%; background:#0d1630; border:1.5px solid #3b82f6; box-shadow:0 20px 50px rgba(0,0,0,0.9);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem; padding-bottom:0.6rem; border-bottom:1px solid rgba(255,255,255,0.1);">
            <h3 style="margin:0; color:#fff; font-size:1.15rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
                <i class="fa-solid fa-chair" style="color:#60a5fa;"></i> 🍽️ Asignar Mesa & Enviar Reserva a Cocineras
            </h3>
            <button type="button" onclick="document.getElementById('modalAsignarReservaCocina').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.6rem; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form action="dashboard-cajero.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="res_sol_id" name="solicitud_id" value="0">
            <input type="hidden" id="res_cli_nombre" name="cliente_nombre" value="">
            <input type="hidden" id="res_cli_tel" name="cliente_telefono" value="">

            <div>
                <label style="font-size:0.82rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">Cliente de la Reserva:</label>
                <input type="text" id="res_cli_display" class="form-control" style="width:100%; background:rgba(0,0,0,0.4); color:#fff; font-weight:700;" readonly>
            </div>

            <div>
                <label style="font-size:0.85rem; color:#60a5fa; font-weight:700; display:block; margin-bottom:0.3rem;">
                    <i class="fa-solid fa-utensils"></i> Mesa Asignada / Nombre de Reserva:
                </label>
                <input type="text" name="mesa_numero_reserva" id="res_mesa_num" class="form-control" style="width:100%; border-color:#3b82f6;" placeholder="Ej. Mesa 4 - Reserva Pepe" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--gold); font-weight:700; display:block; margin-bottom:0.3rem;">
                    <i class="fa-solid fa-clock"></i> Hora de Atención de la Reserva:
                </label>
                <input type="text" name="hora_reserva" id="res_hora_atencion" class="form-control" style="width:100%; border-color:var(--gold);" placeholder="Ej. 19:30 PM (Hoy)" required>
            </div>

            <div>
                <label style="font-size:0.82rem; color:var(--text-muted); display:block; margin-bottom:0.3rem;">Platos / Especificaciones del Cliente:</label>
                <textarea name="detalles_reserva" id="res_detalles_txt" class="form-control" style="width:100%; height:90px;" required></textarea>
            </div>

            <button type="submit" name="despachar_reserva_cocina_btn" class="btn-export" style="width:100%; justify-content:center; background:linear-gradient(135deg, #3b82f6, #1d4ed8); border-color:#3b82f6; color:#fff; font-weight:800; padding:0.75rem; font-size:0.95rem; margin-top:0.5rem;">
                <i class="fa-solid fa-paper-plane"></i> 🚀 CONFIRMAR & ENVIAR A REPARTO COCINERA
            </button>
        </form>
    </div>
</div>

<script>
function abrirModalAsignarReserva(sol) {
    document.getElementById('res_sol_id').value = sol.id || 0;
    document.getElementById('res_cli_nombre').value = sol.nombre || '';
    document.getElementById('res_cli_tel').value = sol.telefono || '';
    document.getElementById('res_cli_display').value = (sol.nombre || 'Cliente') + (sol.telefono ? ' (' + sol.telefono + ')' : '');
    document.getElementById('res_mesa_num').value = 'Mesa 1 - Reserva ' + (sol.nombre || 'Cliente');
    document.getElementById('res_hora_atencion').value = '20:00 PM';
    document.getElementById('res_detalles_txt').value = sol.detalles || 'Platos solicitados en la reserva';
    document.getElementById('modalAsignarReservaCocina').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
