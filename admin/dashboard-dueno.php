<?php
$page_title = "Dashboard Dueño";
$active_menu = "dueno";
$required_roles = ['dueno', 'admin'];

require_once __DIR__ . '/includes/admin_header.php';

$status_msg = '';

// ==========================================
// 1. PROCESAR ACCIONES DE TRABAJADORES (CRUD)
// ==========================================

// A. CREAR TRABAJADOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario_btn'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $pass_raw = trim($_POST['password'] ?? 'admin123');
    $pass_hash = password_hash($pass_raw, PASSWORD_BCRYPT);
    $rol = $_POST['rol'] ?? 'carnicero';
    $sede = $_POST['sede_asignada'] ?? 'Sede Principal';
    $estado = $_POST['estado'] ?? 'activo';
    $avatar = trim($_POST['avatar'] ?? '') ?: 'images/avatar-default.png';

    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar_file']['tmp_name'];
        $file_name = $_FILES['avatar_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = 'avatar_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $avatar = 'uploads/avatars/' . $new_filename;
            }
        }
    }

    if (!empty($nombre) && !empty($correo) && $pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password, rol, sede_asignada, estado, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $correo, $pass_hash, $rol, $sede, $estado, $avatar]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Trabajador <strong>' . htmlspecialchars($nombre) . '</strong> registrado exitosamente en la ' . htmlspecialchars($sede) . '.</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al registrar trabajador: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// B. EDITAR TRABAJADOR & FOTO DE PERFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario_btn'])) {
    $emp_id = intval($_POST['usuario_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = $_POST['rol'] ?? 'carnicero';
    $sede = $_POST['sede_asignada'] ?? 'Sede Principal';
    $estado = $_POST['estado'] ?? 'activo';
    $pass_new = trim($_POST['password'] ?? '');
    $eliminar_avatar = intval($_POST['eliminar_avatar'] ?? 0);
    $avatar = trim($_POST['avatar'] ?? '') ?: 'images/avatar-default.png';

    if ($eliminar_avatar === 1 || $avatar === 'images/avatar-default.png' || strpos($avatar, 'avatar-default') !== false) {
        $avatar = 'images/avatar-default.png';
    } elseif (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar_file']['tmp_name'];
        $file_name = $_FILES['avatar_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = 'avatar_' . $emp_id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $avatar = 'uploads/avatars/' . $new_filename;
            }
        }
    }

    if ($emp_id > 0 && !empty($nombre) && $pdo) {
        try {
            if (!empty($pass_new)) {
                $pass_hash = password_hash($pass_new, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, sede_asignada = ?, estado = ?, avatar = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $correo, $rol, $sede, $estado, $avatar, $pass_hash, $emp_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, sede_asignada = ?, estado = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$nombre, $correo, $rol, $sede, $estado, $avatar, $emp_id]);
            }
            if (isset($_SESSION['user']['id']) && intval($_SESSION['user']['id']) === $emp_id) {
                $_SESSION['user']['avatar'] = $avatar;
                $_SESSION['user']['nombre'] = $nombre;
            }
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Información y foto del trabajador <strong>' . htmlspecialchars($nombre) . '</strong> actualizadas con éxito (Foto: ' . htmlspecialchars($avatar) . ').</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al actualizar trabajador: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// C. ELIMINAR TRABAJADOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario_btn'])) {
    $emp_id = intval($_POST['usuario_id'] ?? 0);
    if ($emp_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$emp_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Registro de trabajador eliminado del sistema.</div>';
        } catch (Exception $e) {}
    }
}

// ==========================================
// 2. PROCESAR ACCIONES DE PRODUCTOS (CRUD)
// ==========================================

// A. CREAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto_btn'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $nombre)));
    }
    $categoria = $_POST['categoria'] ?? 'res';
    $precio = floatval($_POST['precio'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'kg');
    $stock = floatval($_POST['stock'] ?? 10);
    $etiqueta = trim($_POST['etiqueta'] ?? 'Nuevo');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagen = trim($_POST['imagen'] ?? '') ?: 'images/avatar-default.png';
    
    // Procesar archivo de imagen del producto subido desde la computadora
    if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['imagen_file']['tmp_name'];
        $file_name = $_FILES['imagen_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $imagen = 'uploads/products/' . $new_filename;
            }
        }
    }

    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $en_descuento = isset($_POST['en_descuento']) ? 1 : 0;
    $descuento_porcentaje = floatval($_POST['descuento_porcentaje'] ?? 0);
    $precio_oferta = floatval($_POST['precio_oferta'] ?? 0);

    if ($en_descuento && $descuento_porcentaje > 0 && $precio_oferta <= 0) {
        $precio_oferta = round($precio * (1 - ($descuento_porcentaje / 100)));
    }

    if (!empty($nombre) && $precio > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO productos (slug, nombre, categoria, descripcion, corte_tipo, maduracion, origen, precio, unidad, stock, imagen, etiqueta, destacado, en_descuento, descuento_porcentaje, precio_oferta) VALUES (?, ?, ?, ?, 'Corte Seleccionado', 'Fresco', 'Nacional', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$slug, $nombre, $categoria, $descripcion, $precio, $unidad, $stock, $imagen, $etiqueta, $destacado, $en_descuento, $descuento_porcentaje, $precio_oferta]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Producto <strong>' . htmlspecialchars($nombre) . '</strong> creado exitosamente en el catálogo.</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al crear producto: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// B. EDITAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto_btn'])) {
    $id = intval($_POST['producto_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = $_POST['categoria'] ?? 'res';
    $precio = floatval($_POST['precio'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'kg');
    $stock = floatval($_POST['stock'] ?? 0);
    $etiqueta = trim($_POST['etiqueta'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagen = trim($_POST['imagen'] ?? '') ?: 'images/tomahawk.jpg';

    // Procesar archivo de imagen del producto subido desde la computadora
    if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['imagen_file']['tmp_name'];
        $file_name = $_FILES['imagen_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = 'prod_' . $id . '_' . time() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                $imagen = 'uploads/products/' . $new_filename;
            }
        }
    }

    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $en_descuento = isset($_POST['en_descuento']) ? 1 : 0;
    $descuento_porcentaje = floatval($_POST['descuento_porcentaje'] ?? 0);
    $precio_oferta = floatval($_POST['precio_oferta'] ?? 0);

    if ($en_descuento && $descuento_porcentaje > 0 && $precio_oferta <= 0) {
        $precio_oferta = round($precio * (1 - ($descuento_porcentaje / 100)));
    }

    if ($id > 0 && !empty($nombre) && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, categoria = ?, precio = ?, unidad = ?, stock = ?, etiqueta = ?, descripcion = ?, imagen = ?, destacado = ?, en_descuento = ?, descuento_porcentaje = ?, precio_oferta = ? WHERE id = ?");
            $stmt->execute([$nombre, $categoria, $precio, $unidad, $stock, $etiqueta, $descripcion, $imagen, $destacado, $en_descuento, $descuento_porcentaje, $precio_oferta, $id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Producto <strong>' . htmlspecialchars($nombre) . '</strong> actualizado exitosamente.</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al actualizar producto: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// C. ELIMINAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto_btn'])) {
    $id = intval($_POST['producto_id'] ?? 0);
    if ($id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Producto eliminado del catálogo.</div>';
        } catch (Exception $e) {}
    }
}

// D. ACTIVAR / DESACTIVAR PRODUCTO EN LA WEB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado_producto'])) {
    $id = intval($_POST['producto_id'] ?? 0);
    $nuevo_est = ($_POST['nuevo_estado'] === 'inactivo') ? 'inactivo' : 'activo';
    if ($id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE productos SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_est, $id]);
            $label_est = ($nuevo_est === 'activo') ? 'ACTIVADO (Visible en Web)' : 'DESACTIVADO (Oculto de Web)';
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Producto <strong>' . $label_est . '</strong> exitosamente.</div>';
        } catch (Exception $e) {}
    }
}

// E. ELIMINAR SUGERENCIA / RECLAMO ANÓNIMO DE CLIENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_sugerencia_btn'])) {
    $sug_id = intval($_POST['sugerencia_id'] ?? 0);
    if ($sug_id > 0 && $pdo) {
        try {
            $stmt_del_sug = $pdo->prepare("DELETE FROM sugerencias_anonimas WHERE id = ?");
            $stmt_del_sug->execute([$sug_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Mensaje anónimo eliminado exitosamente del buzón.</div>';
        } catch (Exception $e) {}
    }
}

// ==========================================
// 3. PROCESAR ACCIONES DE PROVEEDORES (CRUD)
// ==========================================

// A. CREAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proveedor_btn'])) {
    $nit = trim($_POST['nit_cedula'] ?? '');
    $empresa = trim($_POST['empresa_nombre'] ?? '');
    $contacto = trim($_POST['contacto_persona'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $categoria_insumo = $_POST['categoria_insumo'] ?? 'Ganadería / Res';
    $direccion = trim($_POST['direccion'] ?? '');

    if (!empty($nit) && !empty($empresa) && $pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO proveedores (nit_cedula, empresa_nombre, contacto_persona, telefono, email, categoria_insumo, direccion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')");
            $stmt->execute([$nit, $empresa, $contacto, $telefono, $email, $categoria_insumo, $direccion]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Proveedor <strong>' . htmlspecialchars($empresa) . '</strong> registrado con éxito.</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al registrar proveedor: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// B. EDITAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_proveedor_btn'])) {
    $prov_id = intval($_POST['proveedor_id'] ?? 0);
    $nit = trim($_POST['nit_cedula'] ?? '');
    $empresa = trim($_POST['empresa_nombre'] ?? '');
    $contacto = trim($_POST['contacto_persona'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $categoria_insumo = $_POST['categoria_insumo'] ?? 'Ganadería / Res';
    $estado = $_POST['estado'] ?? 'activo';

    if ($prov_id > 0 && !empty($empresa) && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE proveedores SET nit_cedula = ?, empresa_nombre = ?, contacto_persona = ?, telefono = ?, email = ?, categoria_insumo = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nit, $empresa, $contacto, $telefono, $email, $categoria_insumo, $estado, $prov_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Proveedor <strong>' . htmlspecialchars($empresa) . '</strong> actualizado con éxito.</div>';
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error al actualizar proveedor: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// C. ELIMINAR PROVEEDOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_proveedor_btn'])) {
    $prov_id = intval($_POST['proveedor_id'] ?? 0);
    if ($prov_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
            $stmt->execute([$prov_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Proveedor eliminado del sistema.</div>';
        } catch (Exception $e) {}
    }
}

// ==========================================
// 4. ELIMINACIÓN DE ÓRDENES EN SINK (CARNICERÍA, RESTAURANTE, DOMICILIOS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_orden_carniceria'])) {
    $ord_id = intval($_POST['orden_id'] ?? 0);
    if ($ord_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM ordenes_carniceria WHERE id = ?");
            $stmt->execute([$ord_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Órden de Carnicería #' . $ord_id . ' eliminada del sistema.</div>';
        } catch (Exception $e) {}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_comanda_cocina'])) {
    $com_id = intval($_POST['comanda_id'] ?? 0);
    if ($com_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM comandas_cocina WHERE id = ?");
            $stmt->execute([$com_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Comanda de Cocina #' . $com_id . ' eliminada del sistema.</div>';
        } catch (Exception $e) {}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_domicilio'])) {
    $dom_id = intval($_POST['domicilio_id'] ?? 0);
    if ($dom_id > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM domicilios_envios WHERE id = ?");
            $stmt->execute([$dom_id]);
            $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Domicilio #' . $dom_id . ' eliminado del sistema.</div>';
        } catch (Exception $e) {}
    }
}

// ==========================================
// 5. ACCIONES EN LOTE (SELECCIONAR TODOS / ACCIONES MASIVAS)
// ==========================================

// A. ACCIONES EN LOTE PARA PRODUCTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_lote_productos_btn'])) {
    $ids = isset($_POST['productos_ids']) ? array_map('intval', (array)$_POST['productos_ids']) : [];
    $accion = $_POST['tipo_accion_lote'] ?? '';

    if (!empty($ids) && $pdo) {
        $in_clause = implode(',', array_fill(0, count($ids), '?'));
        try {
            if ($accion === 'eliminar') {
                $stmt = $pdo->prepare("DELETE FROM productos WHERE id IN ($in_clause)");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Se eliminaron <strong>' . count($ids) . '</strong> productos en lote exitosamente.</div>';
            } elseif ($accion === 'visibles') {
                $stmt = $pdo->prepare("UPDATE productos SET estado = 'activo' WHERE id IN ($in_clause)");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-eye"></i> Se activaron <strong>' . count($ids) . '</strong> productos (visibles en web).</div>';
            } elseif ($accion === 'ocultos') {
                $stmt = $pdo->prepare("UPDATE productos SET estado = 'inactivo' WHERE id IN ($in_clause)");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(234, 88, 12, 0.2); border: 1px solid #ea580c; color: #fb923c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-eye-slash"></i> Se ocultaron <strong>' . count($ids) . '</strong> productos de la web.</div>';
            }
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error en acción masiva de productos: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// B. ACCIONES EN LOTE PARA TRABAJADORES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_lote_trabajadores_btn'])) {
    $ids = isset($_POST['trabajadores_ids']) ? array_map('intval', (array)$_POST['trabajadores_ids']) : [];
    $accion = $_POST['tipo_accion_lote'] ?? '';

    if (!empty($ids) && $pdo) {
        $in_clause = implode(',', array_fill(0, count($ids), '?'));
        try {
            if ($accion === 'eliminar') {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id IN ($in_clause) AND rol != 'dueno'");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash"></i> Se eliminaron <strong>' . count($ids) . '</strong> trabajadores del sistema.</div>';
            } elseif ($accion === 'activar') {
                $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'activo' WHERE id IN ($in_clause) AND rol != 'dueno'");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-user-check"></i> Se activaron <strong>' . count($ids) . '</strong> trabajadores.</div>';
            } elseif ($accion === 'inactivar') {
                $stmt = $pdo->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id IN ($in_clause) AND rol != 'dueno'");
                $stmt->execute($ids);
                $status_msg = '<div class="alert alert-success" style="background: rgba(234, 88, 12, 0.2); border: 1px solid #ea580c; color: #fb923c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-user-slash"></i> Se inactivaron <strong>' . count($ids) . '</strong> trabajadores.</div>';
            }
        } catch (Exception $e) {
            $status_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">Error en acción masiva de trabajadores: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// ==========================================
// 6. CONSULTAR DATOS DESDE BASE DE DATOS
// ==========================================
$empleados_lista = [];
$productos_lista = [];
$proveedores_lista = [];
$ordenes_carniceria_admin = [];
$comandas_cocina_admin = [];
$domicilios_admin = [];
$total_empleados_activos = 0;

if ($pdo) {
    try {
        $stmt_all_emp = $pdo->query("SELECT * FROM usuarios WHERE rol != 'cliente' ORDER BY id ASC");
        $empleados_lista = $stmt_all_emp->fetchAll();
        $total_empleados_activos = count($empleados_lista);

        $stmt_p = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
        $productos_lista = $stmt_p->fetchAll();

        $stmt_prov = $pdo->query("SELECT * FROM proveedores ORDER BY id ASC");
        $proveedores_lista = $stmt_prov->fetchAll();

        $stmt_car = $pdo->query("SELECT * FROM ordenes_carniceria ORDER BY id DESC");
        $ordenes_carniceria_admin = $stmt_car->fetchAll();

        $stmt_coc = $pdo->query("SELECT * FROM comandas_cocina ORDER BY id DESC");
        $comandas_cocina_admin = $stmt_coc->fetchAll();

        $stmt_dom = $pdo->query("SELECT * FROM domicilios_envios ORDER BY id DESC");
        $domicilios_admin = $stmt_dom->fetchAll();

        $stmt_sug = $pdo->query("SELECT * FROM sugerencias_anonimas ORDER BY id DESC");
        $sugerencias_anonimas = $stmt_sug->fetchAll();
    } catch (Exception $e) {}
}
?>

<!-- Encabezado Unificado del Dashboard -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-crown text-gold"></i> Dashboard Dueño
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Control unificado de ventas, personal, catálogo de cortes, proveedores y monitoreo en vivo de áreas.
        </p>
    </div>
</div>

<?php echo $status_msg; ?>

<!-- Tarjetas Métricas Reales del ERP -->
<div class="kpi-grid">
    <div class="kpi-card" onclick="document.getElementById('seccion-personal').scrollIntoView({behavior:'smooth'});" style="cursor: pointer;">
        <div class="kpi-title"><i class="fa-solid fa-users text-gold"></i> Personal de la Empresa</div>
        <div class="kpi-value"><?php echo $total_empleados_activos; ?> Empleados</div>
    </div>

    <div class="kpi-card" onclick="document.getElementById('seccion-productos').scrollIntoView({behavior:'smooth'});" style="cursor: pointer;">
        <div class="kpi-title"><i class="fa-solid fa-boxes-stacked text-gold"></i> Catálogo de Cortes</div>
        <div class="kpi-value" style="color: var(--gold); font-size: 1.8rem;"><?php echo count($productos_lista); ?> Productos</div>
    </div>

    <div class="kpi-card" onclick="document.getElementById('seccion-proveedores').scrollIntoView({behavior:'smooth'});" style="cursor: pointer; border-left-color: #3b82f6;">
        <div class="kpi-title"><i class="fa-solid fa-truck-field" style="color: #60a5fa;"></i> Proveedores & Compras</div>
        <div class="kpi-value" style="color: #60a5fa;"><?php echo count($proveedores_lista); ?> Empresas</div>
    </div>

    <div class="kpi-card" style="cursor: pointer; border-left-color: #ef4444;">
        <div class="kpi-title"><i class="fa-solid fa-tags" style="color: #ef4444;"></i> Cortes en Descuento</div>
        <div class="kpi-value" style="color: #ef4444;">
            <?php 
            $desc_count = 0;
            $destacados_count = 0;
            foreach ($productos_lista as $pr) { 
                if (!empty($pr['en_descuento'])) $desc_count++; 
                if (!empty($pr['destacado'])) $destacados_count++; 
            }
            echo $desc_count;
            ?> Ofertas
        </div>
    </div>
</div>



<!-- SECCIÓN: BUZÓN ANÓNIMO DE SUGERENCIAS & RECLAMOS -->
<div class="data-table-card" style="margin-bottom: 2.5rem; border-left: 4px solid var(--gold);">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-user-secret text-gold"></i> Buzón Anónimo de Sugerencias, Reclamos & Dúdas
        </h3>
        <span class="status-pill warning"><?php echo count($sugerencias_anonimas); ?> Mensajes Recibidos</span>
    </div>

    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.2rem;">
        Mensajes de opinión enviados 100% de forma anónima por los clientes desde el pie de página del sitio web.
    </p>

    <?php if (empty($sugerencias_anonimas)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 2rem; background: rgba(0,0,0,0.3); border-radius: 8px;">
            <i class="fa-solid fa-inbox" style="font-size: 2rem; color: var(--gold); margin-bottom: 0.5rem;"></i>
            <p style="margin: 0;">No hay sugerencias o reclamos anónimos registrados en este momento.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.2rem;">
            <?php foreach ($sugerencias_anonimas as $sug): ?>
            <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.2rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.8rem;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                        <?php if ($sug['tipo'] === 'reclamo'): ?>
                            <span class="status-pill danger" style="font-size: 0.75rem;"><i class="fa-solid fa-triangle-exclamation"></i> ⚠️ RECLAMO</span>
                        <?php elseif ($sug['tipo'] === 'duda'): ?>
                            <span class="status-pill warning" style="font-size: 0.75rem; background: rgba(59,130,246,0.2); color:#60a5fa; border:1px solid #3b82f6;"><i class="fa-solid fa-circle-question"></i> ❓ DUDA / PREGUNTA</span>
                        <?php else: ?>
                            <span class="status-pill success" style="font-size: 0.75rem;"><i class="fa-solid fa-lightbulb"></i> 💡 SUGERENCIA</span>
                        <?php endif; ?>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($sug['fecha_hora']); ?></span>
                    </div>

                    <div style="background: rgba(0,0,0,0.4); padding: 0.9rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                        <p style="margin: 0; color: #fff; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($sug['mensaje']); ?></p>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.6rem; margin-top: 0.4rem;">
                    <?php if (!empty($sug['nombre']) || !empty($sug['telefono'])): ?>
                        <div style="font-size: 0.78rem; color: #fff; display: flex; flex-direction: column;">
                            <span style="font-weight: 700; color: var(--gold);"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($sug['nombre'] ?: 'Cliente Registrado'); ?></span>
                            <?php if (!empty($sug['telefono'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $sug['telefono']); ?>" target="_blank" style="color: #34d399; text-decoration: none; font-weight: 700; font-size: 0.75rem;">
                                    <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($sug['telefono']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span style="font-size: 0.75rem; color: #34d399;"><i class="fa-solid fa-user-shield"></i> Remitente Anónimo</span>
                    <?php endif; ?>

                    <form action="dashboard-dueno.php" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este comentario del buzón?');" style="margin: 0;">
                        <input type="hidden" name="sugerencia_id" value="<?php echo $sug['id']; ?>">
                        <button type="submit" name="eliminar_sugerencia_btn" class="btn-export" style="background: rgba(239, 68, 68, 0.2); border-color: #ef4444; color: #fca5a5; font-size: 0.78rem; padding: 0.35rem 0.75rem;">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- SECCIÓN DE MONITOREO & ELIMINACIÓN EN TIEMPO REAL (CARNICERÍA, RESTAURANTE, DOMICILIOS) -->
<div class="data-table-card" style="border-left: 4px solid #3b82f6; margin-bottom: 2rem;">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-layer-group text-gold"></i> Monitoreo & Control de Pedidos Activos por Departamento
        </h3>
        <span style="font-size:0.85rem; color:var(--text-muted);">Sincronización en vivo con Carnicería, Restaurante (Cocina) y Domicilios</span>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.2rem;">
        <!-- Carnicería -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
            <h4 style="margin:0 0 0.8rem 0; color:var(--gold); font-size:0.95rem; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fa-solid fa-drumstick-bite"></i> Carnicería (Desposte)</span>
                <span class="status-pill warning"><?php echo count($ordenes_carniceria_admin); ?> Pedidos</span>
            </h4>
            <?php if (empty($ordenes_carniceria_admin)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin órdenes activas.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.6rem; max-height:350px; overflow-y:auto; padding-right:0.3rem;">
                    <?php foreach ($ordenes_carniceria_admin as $o_car): ?>
                    <div style="background:rgba(255,255,255,0.03); padding:0.6rem 0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.08); font-size:0.82rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#fff; display:block;"><?php echo htmlspecialchars($o_car['cliente_nombre']); ?></strong>
                            <span style="color:var(--text-muted); font-size:0.75rem; display:flex; align-items:center; gap:0.4rem; margin-top:0.2rem;">
                                <?php 
                                $car_img = ($o_car['carnicero_nombre'] === 'Carnicero Disponible' || empty($o_car['carnicero_nombre'])) ? '../images/carnicero-disponible.png' : get_avatar_url($o_car['carnicero_avatar'] ?? '');
                                ?>
                                <img src="<?php echo htmlspecialchars($car_img); ?>" style="width:20px; height:20px; border-radius:50%; object-fit:cover; border:1px solid var(--gold);" alt="Foto">
                                <?php echo htmlspecialchars($o_car['carnicero_nombre'] ?: 'Carnicero Disponible'); ?>
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="status-pill <?php echo ($o_car['estado'] === 'listo' || $o_car['estado'] === 'finalizado' || $o_car['estado'] === 'terminado') ? 'success' : (($o_car['estado'] === 'en_preparacion' || $o_car['estado'] === 'en_corte') ? 'warning' : 'danger'); ?>" style="font-size:0.7rem;">
                                <?php echo strtoupper(htmlspecialchars($o_car['estado'])); ?>
                            </span>
                            <form action="" method="POST" onsubmit="return confirm('¿Eliminar orden de carnicería de \'<?php echo htmlspecialchars($o_car['cliente_nombre']); ?>\'?');" style="display:inline;">
                                <input type="hidden" name="orden_id" value="<?php echo $o_car['id']; ?>">
                                <button type="submit" name="eliminar_orden_carniceria" style="background:none; border:none; color:#fca5a5; cursor:pointer; font-size:0.9rem;" title="Eliminar Órden">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Restaurante (Cocina) -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
            <h4 style="margin:0 0 0.8rem 0; color:#60a5fa; font-size:0.95rem; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fa-solid fa-utensils"></i> Restaurante (Cocina)</span>
                <span class="status-pill success" style="background:rgba(59, 130, 246, 0.2); color:#60a5fa; border-color:#3b82f6;"><?php echo count($comandas_cocina_admin); ?> Comandas</span>
            </h4>
            <?php if (empty($comandas_cocina_admin)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin comandas activas.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.6rem; max-height:350px; overflow-y:auto; padding-right:0.3rem;">
                    <?php foreach ($comandas_cocina_admin as $c_coc): ?>
                    <div style="background:rgba(255,255,255,0.03); padding:0.6rem 0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.08); font-size:0.82rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#fff; display:block;"><?php echo htmlspecialchars($c_coc['mesa_numero'] ?: 'Mesa'); ?></strong>
                            <span style="color:var(--text-muted); font-size:0.75rem;"><?php echo htmlspecialchars($c_coc['platillo_nombre']); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="status-pill <?php echo ($c_coc['estado'] === 'listo' || $c_coc['estado'] === 'terminado' || $c_coc['estado'] === 'entregado') ? 'success' : 'warning'; ?>" style="font-size:0.7rem;">
                                <?php echo strtoupper(htmlspecialchars($c_coc['estado'])); ?>
                            </span>
                            <form action="" method="POST" onsubmit="return confirm('¿Eliminar comanda de cocina?');" style="display:inline;">
                                <input type="hidden" name="comanda_id" value="<?php echo $c_coc['id']; ?>">
                                <button type="submit" name="eliminar_comanda_cocina" style="background:none; border:none; color:#fca5a5; cursor:pointer; font-size:0.9rem;" title="Eliminar Comanda">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Domicilios -->
        <div style="background:rgba(0,0,0,0.3); padding:1rem; border-radius:8px; border:1px solid var(--border-color);">
            <h4 style="margin:0 0 0.8rem 0; color:#34d399; font-size:0.95rem; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fa-solid fa-motorcycle"></i> Domicilios & Envíos</span>
                <span class="status-pill success"><?php echo count($domicilios_admin); ?> Envíos</span>
            </h4>
            <?php if (empty($domicilios_admin)): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);">Sin domicilios activos.</p>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:0.6rem; max-height:350px; overflow-y:auto; padding-right:0.3rem;">
                    <?php foreach ($domicilios_admin as $d_env): ?>
                    <div style="background:rgba(255,255,255,0.03); padding:0.6rem 0.8rem; border-radius:6px; border:1px solid rgba(255,255,255,0.08); font-size:0.82rem; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong style="color:#fff; display:block;"><?php echo htmlspecialchars($d_env['cliente_nombre']); ?></strong>
                            <span style="color:var(--text-muted); font-size:0.75rem;"><?php echo htmlspecialchars($d_env['numero_factura']); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span class="status-pill <?php echo ($d_env['estado'] === 'entregado' || $d_env['estado'] === 'terminado') ? 'success' : (($d_env['estado'] === 'en_camino' || $d_env['estado'] === 'asignado') ? 'warning' : 'danger'); ?>" style="font-size:0.7rem;">
                                <?php echo strtoupper(htmlspecialchars($d_env['estado'])); ?>
                            </span>
                            <form action="" method="POST" onsubmit="return confirm('¿Eliminar domicilio de \'<?php echo htmlspecialchars($d_env['cliente_nombre']); ?>\'?');" style="display:inline;">
                                <input type="hidden" name="domicilio_id" value="<?php echo $d_env['id']; ?>">
                                <button type="submit" name="eliminar_domicilio" style="background:none; border:none; color:#fca5a5; cursor:pointer; font-size:0.9rem;" title="Eliminar Domicilio">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SECCIÓN: GESTIÓN DE TRABAJADORES Y SUS SEDES -->
<!-- SECCIÓN: GESTIÓN DE TRABAJADORES CON ACCIONES EN LOTE -->
<div class="data-table-card" id="seccion-personal">
    <div class="table-header-tools" style="margin-bottom:0.8rem;">
        <h3 style="margin: 0; color: #fff; font-size: 1.1rem;">
            <i class="fa-solid fa-users-gear text-gold"></i> Gestión de Trabajadores & Fotos de Perfil
        </h3>
        <div style="display:flex; gap:0.8rem;">
            <input type="text" class="search-input" data-table="tabla-personal-dueno" placeholder="Buscar trabajador, rol o sede...">
            <button onclick="document.getElementById('modalNuevoUsuario').style.display='flex'" class="btn-export" style="background: rgba(59, 130, 246, 0.2); border-color: #3b82f6; color: #60a5fa; font-weight:700;">
                <i class="fa-solid fa-user-plus"></i> Registrar Trabajador
            </button>
        </div>
    </div>

    <!-- Formulario para Acciones Masivas / Lote de Trabajadores -->
    <form id="form-lote-trabajadores" action="dashboard-dueno.php" method="POST">
        <input type="hidden" name="accion_lote_trabajadores_btn" value="1">
        <input type="hidden" id="tipo_accion_trabajadores" name="tipo_accion_lote" value="">

        <!-- Barra FLOTANTE / DINÁMICA de Acciones Masivas -->
        <div id="barra-acciones-trabajadores" style="display:none; background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.4); border-radius:8px; padding:0.6rem 1rem; margin-bottom:1rem; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.6rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <i class="fa-solid fa-check-double text-gold" style="font-size:1.1rem;"></i>
                <span id="counter-trabajadores" style="color:#fff; font-weight:800; font-size:0.9rem;">0 seleccionados</span>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <button type="button" onclick="deseleccionarTodosTrabajadores()" class="btn-export" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.2); color:#fff; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-square-xmark"></i> Deseleccionar Todos
                </button>
                <button type="button" onclick="ejecutarAccionLoteTrabajadores('activar')" class="btn-export" style="background:rgba(16,185,129,0.2); border-color:#10b981; color:#34d399; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-user-check"></i> Activar Seleccionados
                </button>
                <button type="button" onclick="ejecutarAccionLoteTrabajadores('inactivar')" class="btn-export" style="background:rgba(234,88,12,0.2); border-color:#ea580c; color:#fb923c; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-user-slash"></i> Inactivar Seleccionados
                </button>
                <button type="button" onclick="ejecutarAccionLoteTrabajadores('eliminar')" class="btn-export" style="background:rgba(239,68,68,0.25); border-color:#ef4444; color:#fca5a5; font-size:0.8rem; padding:0.35rem 0.75rem; font-weight:800;">
                    <i class="fa-solid fa-trash"></i> Eliminar Seleccionados
                </button>
            </div>
        </div>

        <table class="custom-table" id="tabla-personal-dueno">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">
                        <input type="checkbox" id="check-all-trabajadores" onclick="toggleSelectAllTrabajadores(this)" style="transform: scale(1.25); cursor: pointer;" title="Seleccionar/Deseleccionar Todos">
                    </th>
                    <th>Empleado / Trabajador</th>
                    <th>Rol Asignado</th>
                    <th>Correo Electrónico</th>
                    <th>Sede Asignada</th>
                    <th>Estado</th>
                    <th>Acciones de Edición</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empleados_lista)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay trabajadores registrados en el sistema.</td></tr>
                <?php else: ?>
                    <?php foreach ($empleados_lista as $emp): ?>
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" class="check-trabajador-item" name="trabajadores_ids[]" value="<?php echo $emp['id']; ?>" onchange="actualizarBarraTrabajadores()" style="transform: scale(1.2); cursor: pointer;" <?php if($emp['rol']==='dueno') echo 'disabled title="Propietario protegido"'; ?>>
                        </td>
                        <td style="display:flex; align-items:center; gap:0.75rem;">
                            <img src="<?php echo htmlspecialchars(get_avatar_url($emp['avatar'])); ?>" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Foto">
                            <strong style="color: #fff; font-size: 0.95rem;"><?php echo htmlspecialchars($emp['nombre']); ?></strong>
                        </td>
                        <td>
                            <?php if ($emp['rol'] === 'dueno'): ?>
                                <span class="status-pill warning"><i class="fa-solid fa-crown"></i> Propietario</span>
                            <?php elseif ($emp['rol'] === 'admin'): ?>
                                <span class="status-pill warning">Administrador</span>
                            <?php elseif ($emp['rol'] === 'cajero'): ?>
                                <span class="status-pill success" style="background:rgba(59,130,246,0.2); color:#60a5fa; border-color:#3b82f6;">Central / Cajero</span>
                            <?php elseif ($emp['rol'] === 'carnicero'): ?>
                                <span class="status-pill warning" style="background:rgba(234,88,12,0.2); color:#fb923c; border-color:#ea580c;">Carnicero</span>
                            <?php elseif ($emp['rol'] === 'cocinero'): ?>
                                <span class="status-pill warning" style="background:rgba(217,119,6,0.2); color:#fcd34d; border-color:#d97706;">Cocinero</span>
                            <?php else: ?>
                                <span class="status-pill success" style="background:rgba(16,185,129,0.2); color:#34d399; border-color:#10b981;">Domiciliario</span>
                            <?php endif; ?>
                        </td>
                        <td><span style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($emp['correo']); ?></span></td>
                        <td>
                            <span class="status-pill success" style="background:rgba(16,185,129,0.15); color:#34d399; border-color:#10b981;">
                                <i class="fa-solid fa-building-user"></i> <?php echo htmlspecialchars($emp['sede_asignada'] ?: 'Sede Principal'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($emp['estado'] === 'activo'): ?>
                                <span class="status-pill success"><i class="fa-solid fa-circle-check"></i> ACTIVO</span>
                            <?php else: ?>
                                <span class="status-pill danger"><i class="fa-solid fa-circle-xmark"></i> INACTIVO</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.4rem;">
                                <button type="button" onclick='abrirModalEditarTrabajador(<?php echo json_encode($emp, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa;" title="Editar Perfil y Foto">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </button>
                                <button type="submit" form="form-eliminar-usr-<?php echo $emp['id']; ?>" class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5;" title="Eliminar Trabajador">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    <?php foreach ($empleados_lista as $emp): ?>
        <form id="form-eliminar-usr-<?php echo $emp['id']; ?>" action="dashboard-dueno.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar a este trabajador del sistema?');" style="display:none;">
            <input type="hidden" name="usuario_id" value="<?php echo $emp['id']; ?>">
            <input type="hidden" name="eliminar_usuario_btn" value="1">
        </form>
    <?php endforeach; ?>
</div>

<!-- SECCIÓN: GESTIÓN DE PRODUCTOS CON ACCIONES EN LOTE -->
<div class="data-table-card" id="seccion-productos">
    <div class="table-header-tools" style="margin-bottom:0.8rem;">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-boxes-stacked text-gold"></i> Gestión de Productos del Catálogo
        </h3>
        <div style="display: flex; gap: 0.8rem;">
            <input type="text" class="search-input" data-table="tabla-productos-admin" placeholder="Filtrar productos por nombre o categoría...">
            <button onclick="document.getElementById('modalNuevoProducto').style.display='flex'" class="btn-export" style="background: linear-gradient(135deg, #8b0000, #5c0000); border-color: #d4af37; font-weight:700;">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Formulario para Acciones Masivas / Lote de Productos -->
    <form id="form-lote-productos" action="dashboard-dueno.php" method="POST">
        <input type="hidden" name="accion_lote_productos_btn" value="1">
        <input type="hidden" id="tipo_accion_productos" name="tipo_accion_lote" value="">

        <!-- Barra FLOTANTE / DINÁMICA de Acciones Masivas -->
        <div id="barra-acciones-productos" style="display:none; background: rgba(212,175,55,0.12); border: 1px solid rgba(212,175,55,0.4); border-radius:8px; padding:0.6rem 1rem; margin-bottom:1rem; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.6rem;">
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <i class="fa-solid fa-check-double text-gold" style="font-size:1.1rem;"></i>
                <span id="counter-productos" style="color:#fff; font-weight:800; font-size:0.9rem;">0 seleccionados</span>
            </div>
            <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                <button type="button" onclick="deseleccionarTodosProductos()" class="btn-export" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.2); color:#fff; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-square-xmark"></i> Deseleccionar Todos
                </button>
                <button type="button" onclick="ejecutarAccionLoteProductos('visibles')" class="btn-export" style="background:rgba(16,185,129,0.2); border-color:#10b981; color:#34d399; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-eye"></i> Hacer Visibles en Web
                </button>
                <button type="button" onclick="ejecutarAccionLoteProductos('ocultos')" class="btn-export" style="background:rgba(234,88,12,0.2); border-color:#ea580c; color:#fb923c; font-size:0.8rem; padding:0.35rem 0.75rem;">
                    <i class="fa-solid fa-eye-slash"></i> Ocultar de Web
                </button>
                
                <button type="button" onclick="ejecutarAccionLoteProductos('eliminar')" class="btn-export" style="background:rgba(239,68,68,0.25); border-color:#ef4444; color:#fca5a5; font-size:0.8rem; padding:0.35rem 0.75rem; font-weight:800;">
                    <i class="fa-solid fa-trash"></i> Eliminar Seleccionados
                </button>
            </div>
        </div>

        <table class="custom-table" id="tabla-productos-admin">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">
                        <input type="checkbox" id="check-all-productos" onclick="toggleSelectAllProductos(this)" style="transform: scale(1.25); cursor: pointer;" title="Seleccionar/Deseleccionar Todos">
                    </th>
                    <th>Nombre del Corte</th>
                    <th>Categoría</th>
                    <th>Precio Regular</th>
                    <th>Descuento / Oferta</th>
                    <th>Destacado</th>
                    <th>Estado en Web</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos_lista)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay productos registrados en la base de datos.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos_lista as $prod): ?>
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" class="check-producto-item" name="productos_ids[]" value="<?php echo $prod['id']; ?>" onchange="actualizarBarraProductos()" style="transform: scale(1.2); cursor: pointer;">
                        </td>
                        <td style="display:flex; align-items:center; gap:0.75rem;">
                            <img src="<?php echo htmlspecialchars(get_avatar_url($prod['imagen'] ?: 'images/tomahawk.jpg')); ?>" style="width:40px; height:40px; border-radius:6px; object-fit:cover; border:1px solid var(--border-color);" alt="Corte">
                            <div>
                                <strong style="color: #fff;"><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                <span style="font-size:0.75rem; color:var(--text-muted); display:block;"><?php echo htmlspecialchars($prod['unidad']); ?></span>
                            </div>
                        </td>
                        <td><span class="status-pill warning"><?php echo strtoupper(htmlspecialchars($prod['categoria'])); ?></span></td>
                        <td><strong style="color:#fff;">$<?php echo number_format($prod['precio'], 0, ',', '.'); ?> COP</strong></td>
                        <td>
                            <?php if (!empty($prod['en_descuento'])): ?>
                                <span class="status-pill danger"><i class="fa-solid fa-tag"></i> -<?php echo $prod['descuento_porcentaje']; ?>% OFF ($<?php echo number_format($prod['precio_oferta'], 0, ',', '.'); ?>)</span>
                            <?php else: ?>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Precio Normal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($prod['destacado'])): ?>
                                <span class="status-pill warning"><i class="fa-solid fa-star"></i> DESTACADO</span>
                            <?php else: ?>
                                <span style="font-size:0.8rem; color:var(--text-muted);">Estándar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="submit" form="form-cambiar-est-prod-<?php echo $prod['id']; ?>" class="btn-export" style="padding:0.25rem 0.6rem; background:<?php echo ($prod['estado'] ?? 'activo') === 'activo' ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)'; ?>; border-color:<?php echo ($prod['estado'] ?? 'activo') === 'activo' ? '#10b981' : '#ef4444'; ?>; color:<?php echo ($prod['estado'] ?? 'activo') === 'activo' ? '#34d399' : '#fca5a5'; ?>;">
                                <?php echo ($prod['estado'] ?? 'activo') === 'activo' ? 'VISIBLE' : 'OCULTO'; ?>
                            </button>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.4rem;">
                                <button type="button" onclick='abrirModalEditar(<?php echo json_encode($prod, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa;" title="Editar Producto">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </button>
                                <button type="submit" form="form-eliminar-prod-<?php echo $prod['id']; ?>" class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5;" title="Eliminar Producto">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
    <?php foreach ($productos_lista as $prod): ?>
        <form id="form-cambiar-est-prod-<?php echo $prod['id']; ?>" action="dashboard-dueno.php" method="POST" style="display:none;">
            <input type="hidden" name="producto_id" value="<?php echo $prod['id']; ?>">
            <input type="hidden" name="nuevo_estado" value="<?php echo ($prod['estado'] ?? 'activo') === 'activo' ? 'inactivo' : 'activo'; ?>">
            <input type="hidden" name="cambiar_estado_producto" value="1">
        </form>
        <form id="form-eliminar-prod-<?php echo $prod['id']; ?>" action="dashboard-dueno.php" method="POST" onsubmit="return confirm('¿Eliminar producto <?php echo htmlspecialchars($prod['nombre']); ?>?');" style="display:none;">
            <input type="hidden" name="producto_id" value="<?php echo $prod['id']; ?>">
            <input type="hidden" name="eliminar_producto_btn" value="1">
        </form>
    <?php endforeach; ?>
</div>

<!-- SECCIÓN: GESTIÓN DE PROVEEDORES & INSUMOS -->
<div class="data-table-card" id="seccion-proveedores">
    <div class="table-header-tools">
        <h3 style="margin: 0; color: #fff; font-size: 1.15rem;">
            <i class="fa-solid fa-truck-field text-gold"></i> Gestión de Proveedores & Compras
        </h3>
        <div style="display: flex; gap: 0.8rem;">
            <input type="text" class="search-input" data-table="tabla-proveedores-admin" placeholder="Buscar proveedor, NIT o insumo...">
            <button onclick="document.getElementById('modalNuevoProveedor').style.display='flex'" class="btn-export" style="background: rgba(59, 130, 246, 0.2); border-color: #3b82f6; color: #60a5fa; font-weight:700;">
                <i class="fa-solid fa-plus"></i> Registrar Proveedor
            </button>
        </div>
    </div>

    <table class="custom-table" id="tabla-proveedores-admin">
        <thead>
            <tr>
                <th>NIT / Cédula</th>
                <th>Empresa Proveedora</th>
                <th>Persona de Contacto</th>
                <th>Teléfono / WhatsApp</th>
                <th>Categoría de Insumo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($proveedores_lista)): ?>
                <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">No hay proveedores registrados en la base de datos.</td></tr>
            <?php else: ?>
                <?php foreach ($proveedores_lista as $prov): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($prov['nit_cedula']); ?></code></td>
                    <td><strong style="color: #fff;"><?php echo htmlspecialchars($prov['empresa_nombre']); ?></strong></td>
                    <td><span style="color: #e2e8f0;"><?php echo htmlspecialchars($prov['contacto_persona']); ?></span></td>
                    <td>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $prov['telefono']); ?>" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 600;">
                            <i class="fa-brands fa-whatsapp"></i> <?php echo htmlspecialchars($prov['telefono']); ?>
                        </a>
                    </td>
                    <td><span class="status-pill warning"><?php echo htmlspecialchars($prov['categoria_insumo']); ?></span></td>
                    <td>
                        <div style="display:flex; gap:0.4rem;">
                            <button onclick='abrirModalEditarProveedor(<?php echo json_encode($prov, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa;" title="Editar Proveedor">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>
                            <form action="dashboard-dueno.php" method="POST" onsubmit="return confirm('¿Eliminar proveedor <?php echo htmlspecialchars($prov['empresa_nombre']); ?>?');" style="display:inline;">
                                <input type="hidden" name="proveedor_id" value="<?php echo $prov['id']; ?>">
                                <button type="submit" name="eliminar_proveedor_btn" class="btn-export" style="padding:0.25rem 0.6rem; background: rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5;" title="Eliminar Proveedor">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL: REGISTRAR NUEVO TRABAJADOR -->
<div id="modalNuevoUsuario" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:560px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-user-plus text-gold"></i> Registrar Nuevo Trabajador</h3>
            <button onclick="document.getElementById('modalNuevoUsuario').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre Completo:</label>
                <input type="text" name="nombre" class="search-input" style="width:100%; margin-top:0.3rem;" required placeholder="Ej: Camilo Torres">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Correo Electrónico:</label>
                    <input type="email" name="correo" class="search-input" style="width:100%; margin-top:0.3rem;" required placeholder="camilo@copacarnes.com">
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Contraseña Inicial:</label>
                    <input type="text" name="password" class="search-input" style="width:100%; margin-top:0.3rem;" value="admin123" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Rol Asignado:</label>
                    <select name="rol" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="dueno">Propietario / Dueño ERP</option>
                        <option value="carnicero">Carnicero</option>
                        <option value="cajero">Central / Cajero</option>
                        <option value="cocinero">Chef Cocinero</option>
                        <option value="domiciliario">Domiciliario</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Sede Asignada:</label>
                    <select name="sede_asignada" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="Sede Principal">Sede Principal (Carnicería & Asadero)</option>
                        <option value="Sede Secundaria">Sede Secundaria (Carnicería)</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-image text-gold"></i> Foto de Perfil (Opcional):</label>
                    <input type="file" id="create_user_file" name="avatar_file" accept="image/*" onchange="previewAvatarImageCreate(this)" style="display:none;">
                    <button type="button" onclick="document.getElementById('create_user_file').click()" class="btn-export" style="background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; width:100%; justify-content:center; padding:0.5rem; font-weight:700; margin-top:0.3rem; font-size:0.8rem;">
                        <i class="fa-solid fa-folder-open"></i> Subir Foto
                    </button>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:1rem; background:rgba(0,0,0,0.35); padding:0.6rem 0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                <img id="avatar_create_preview_img" src="../images/avatar-default.png" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Vista Previa">
                <div>
                    <strong style="color:#fff; font-size:0.82rem; display:block;">Foto Asignada: images/avatar-default.png</strong>
                    <span style="font-size:0.72rem; color:var(--text-muted);">Por defecto se asignará la imagen estándar de trabajador.</span>
                </div>
            </div>

            <input type="hidden" name="avatar" value="images/avatar-default.png">

            <button type="submit" name="crear_usuario_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-plus"></i> REGISTRAR TRABAJADOR
            </button>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR INFORMACIÓN Y FOTO DE TRABAJADOR -->
<div id="modalEditarUsuario" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:580px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-user-pen text-gold"></i> Editar Información de Trabajador</h3>
            <button onclick="document.getElementById('modalEditarUsuario').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="edit_user_id" name="usuario_id">
            <input type="hidden" id="edit_user_eliminar_avatar" name="eliminar_avatar" value="0">

            <!-- Selección de Archivo y Vista Previa Ajustable -->
            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-image text-gold"></i> Foto de Perfil del Trabajador:</label>
                
                <input type="file" id="edit_user_file" name="avatar_file" accept="image/*" onchange="previewAvatarImageDueno(this)" style="display:none;">
                <button type="button" onclick="document.getElementById('edit_user_file').click()" class="btn-export" style="background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; width:100%; justify-content:center; padding:0.65rem; font-weight:700; margin-top:0.3rem;">
                    <i class="fa-solid fa-folder-open"></i> Seleccionar Archivo de Foto de la Computadora
                </button>

                <div style="display:flex; align-items:center; gap:1rem; margin-top:0.6rem; background:rgba(0,0,0,0.35); padding:0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                    <img id="avatar_dueno_preview_img" src="images/avatar-default.png" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Vista Previa">
                    <div>
                        <strong style="color:#fff; font-size:0.88rem; display:block;">Vista Previa de Foto de Perfil</strong>
                        <span style="font-size:0.75rem; color:var(--text-muted);">La foto se ajustará automáticamente en formato circular.</span>
                    </div>
                </div>

                <input type="text" id="edit_user_avatar" name="avatar" class="search-input" style="width:100%; margin-top:0.5rem; font-size:0.8rem;" placeholder="Ruta o URL de la foto (opcional)">
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre Completo:</label>
                <input type="text" id="edit_user_nombre" name="nombre" class="search-input" style="width:100%; margin-top:0.3rem;" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Correo Electrónico:</label>
                    <input type="email" id="edit_user_correo" name="correo" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Cambiar Contraseña (Opcional):</label>
                    <input type="password" name="password" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Dejar en blanco para mantener">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Rol Asignado:</label>
                    <select id="edit_user_rol" name="rol" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="dueno">Propietario / Dueño ERP</option>
                        <option value="carnicero">Carnicero</option>
                        <option value="cajero">Central / Cajero</option>
                        <option value="cocinero">Chef Cocinero</option>
                        <option value="domiciliario">Domiciliario</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Sede Asignada:</label>
                    <select id="edit_user_sede" name="sede_asignada" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="Sede Principal">Sede Principal (Carnicería & Asadero)</option>
                        <option value="Sede Secundaria">Sede Secundaria (Carnicería)</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Estado de Acceso:</label>
                <select id="edit_user_estado" name="estado" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <option value="activo">ACTIVO (Permitir Ingreso)</option>
                    <option value="inactivo">INACTIVO (Bloquear Ingreso)</option>
                </select>
            </div>

            <button type="submit" name="editar_usuario_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS DE TRABAJADOR
            </button>
        </form>
    </div>
</div>

<!-- MODAL: CREAR NUEVO PRODUCTO -->
<div id="modalNuevoProducto" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:600px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-plus text-gold"></i> Crear Nuevo Producto en Catálogo</h3>
            <button onclick="document.getElementById('modalNuevoProducto').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre del Corte / Producto:</label>
                <input type="text" name="nombre" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Ej: Picanha Angus Importada" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Categoría:</label>
                    <select name="categoria" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="res">🥩 Res</option>
                        <option value="cerdo">🐷 Cerdo</option>
                        <option value="pollo">🍗 Pollo</option>
                        <option value="embutidos">🌭 Embutidos</option>
                        <option value="arepas">🫓 Arepas</option>
                        <option value="extras">🍾 Extras</option>
                        <option value="combos">📦 Combos</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Unidad de Medida:</label>
                    <input type="text" name="unidad" class="search-input" style="width:100%; margin-top:0.3rem;" value="Porción 500g" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Precio Regular (COP):</label>
                    <input type="number" name="precio" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="45000" oninput="calcularPrecioOferta('create')" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Etiqueta / Badge:</label>
                    <input type="text" name="etiqueta" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Ej: PREMIUM, OFERTA">
                </div>
            </div>

            <div style="background:rgba(0,0,0,0.3); padding:0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; cursor:pointer; font-weight:700;">
                        <input type="checkbox" name="en_descuento" value="1" onchange="document.getElementById('create_discount_box').style.display = this.checked ? 'grid' : 'none'">
                        <i class="fa-solid fa-tag text-gold"></i> ¿Aplicar Oferta / Descuento?
                    </label>

                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; cursor:pointer; font-weight:700;">
                        <input type="checkbox" name="destacado" value="1">
                        <i class="fa-solid fa-star text-gold"></i> Mostrar en Productos Destacados <span style="font-size:0.75rem; color:var(--gold); background:rgba(212,175,55,0.15); padding:0.15rem 0.45rem; border-radius:10px; border:1px solid rgba(212,175,55,0.4); font-weight:800;">(<?php echo $destacados_count; ?>/4)</span>
                    </label>
                </div>

                <div id="create_discount_box" style="display:none; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:0.8rem;">
                    <div>
                        <label style="font-size:0.8rem; color:var(--text-muted);">% Descuento:</label>
                        <input type="number" name="descuento_porcentaje" class="search-input" style="width:100%;" placeholder="15" oninput="calcularPrecioOferta('create')">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; color:var(--text-muted);">Precio Oferta Final (COP):</label>
                        <input type="number" name="precio_oferta" class="search-input" style="width:100%;" placeholder="Calculado auto">
                    </div>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-image text-gold"></i> Imagen del Producto / Corte:</label>
                
                <input type="file" id="create_prod_file" name="imagen_file" accept="image/*" onchange="previewProductImageCreate(this)" style="display:none;">
                <button type="button" onclick="document.getElementById('create_prod_file').click()" class="btn-export" style="background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; width:100%; justify-content:center; padding:0.65rem; font-weight:700; margin-top:0.3rem;">
                    <i class="fa-solid fa-folder-open"></i> Seleccionar Imagen del Producto de la Computadora
                </button>

                <div style="display:flex; align-items:center; gap:1rem; margin-top:0.6rem; background:rgba(0,0,0,0.35); padding:0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                    <img id="create_prod_preview_img" src="../images/tomahawk.jpg" style="width:70px; height:70px; border-radius:8px; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Vista Previa">
                    <div>
                        <strong style="color:#fff; font-size:0.88rem; display:block;">Vista Previa de Imagen</strong>
                        <span style="font-size:0.75rem; color:var(--text-muted);">Esta imagen se sincronizará automáticamente en la web.</span>
                    </div>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Descripción / Notas:</label>
                <textarea name="descripcion" class="search-input" style="width:100%; margin-top:0.3rem; height:60px;" placeholder="Detalles del corte y recomendación de cocción..."></textarea>
            </div>

            <button type="submit" name="crear_producto_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-plus"></i> CREAR PRODUCTO
            </button>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR PRODUCTO -->
<div id="modalEditarProducto" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:600px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-pen-to-square text-gold"></i> Editar Producto de Catálogo</h3>
            <button onclick="document.getElementById('modalEditarProducto').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="edit_producto_id" name="producto_id">

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre del Corte / Producto:</label>
                <input type="text" id="edit_nombre" name="nombre" class="search-input" style="width:100%; margin-top:0.3rem;" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Categoría:</label>
                    <select id="edit_categoria" name="categoria" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="res">🥩 Res</option>
                        <option value="cerdo">🐷 Cerdo</option>
                        <option value="pollo">🍗 Pollo</option>
                        <option value="embutidos">🌭 Embutidos</option>
                        <option value="arepas">🫓 Arepas</option>
                        <option value="extras">🍾 Extras</option>
                        <option value="combos">📦 Combos</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Unidad de Medida:</label>
                    <input type="text" id="edit_unidad" name="unidad" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Precio Regular (COP):</label>
                    <input type="number" id="edit_precio" name="precio" class="search-input" style="width:100%; margin-top:0.3rem;" oninput="calcularPrecioOferta('edit')" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Etiqueta / Badge:</label>
                    <input type="text" id="edit_etiqueta" name="etiqueta" class="search-input" style="width:100%; margin-top:0.3rem;">
                </div>
            </div>

            <div style="background:rgba(0,0,0,0.3); padding:0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                <div style="display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap;">
                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; cursor:pointer; font-weight:700;">
                        <input type="checkbox" id="edit_en_descuento" name="en_descuento" value="1" onchange="document.getElementById('edit_discount_box').style.display = this.checked ? 'grid' : 'none'">
                        <i class="fa-solid fa-tag text-gold"></i> ¿En Oferta / Descuento?
                    </label>

                    <label style="display:flex; align-items:center; gap:0.5rem; color:#fff; cursor:pointer; font-weight:700;">
                        <input type="checkbox" id="edit_destacado" name="destacado" value="1">
                        <i class="fa-solid fa-star text-gold"></i> Mostrar en Productos Destacados <span style="font-size:0.75rem; color:var(--gold); background:rgba(212,175,55,0.15); padding:0.15rem 0.45rem; border-radius:10px; border:1px solid rgba(212,175,55,0.4); font-weight:800;">(<?php echo $destacados_count; ?>/4)</span>
                    </label>
                </div>

                <div id="edit_discount_box" style="display:none; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:0.8rem;">
                    <div>
                        <label style="font-size:0.8rem; color:var(--text-muted);">% Descuento:</label>
                        <input type="number" id="edit_descuento_porcentaje" name="descuento_porcentaje" class="search-input" style="width:100%;" oninput="calcularPrecioOferta('edit')">
                    </div>
                    <div>
                        <label style="font-size:0.8rem; color:var(--text-muted);">Precio Oferta Final (COP):</label>
                        <input type="number" id="edit_precio_oferta" name="precio_oferta" class="search-input" style="width:100%;">
                    </div>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-image text-gold"></i> Imagen del Producto / Corte:</label>
                
                <input type="file" id="edit_prod_file" name="imagen_file" accept="image/*" onchange="previewProductImageEdit(this)" style="display:none;">
                <button type="button" onclick="document.getElementById('edit_prod_file').click()" class="btn-export" style="background:rgba(59,130,246,0.2); border-color:#3b82f6; color:#60a5fa; width:100%; justify-content:center; padding:0.65rem; font-weight:700; margin-top:0.3rem;">
                    <i class="fa-solid fa-folder-open"></i> Seleccionar Imagen del Producto de la Computadora
                </button>

                <div style="display:flex; align-items:center; gap:1rem; margin-top:0.6rem; background:rgba(0,0,0,0.35); padding:0.8rem; border-radius:8px; border:1px solid var(--border-color);">
                    <img id="edit_prod_preview_img" src="../images/tomahawk.jpg" style="width:70px; height:70px; border-radius:8px; object-fit:cover; border:2px solid var(--gold); background:#111;" alt="Vista Previa">
                    <div>
                        <strong style="color:#fff; font-size:0.88rem; display:block;">Vista Previa de Imagen Actual</strong>
                        <span style="font-size:0.75rem; color:var(--text-muted);">Sube una nueva foto para reemplazar la actual.</span>
                    </div>
                </div>
                <input type="hidden" id="edit_imagen" name="imagen">
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Descripción:</label>
                <textarea id="edit_descripcion" name="descripcion" class="search-input" style="width:100%; margin-top:0.3rem; height:60px;"></textarea>
            </div>

            <button type="submit" name="editar_producto_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS PRODUCTO
            </button>
        </form>
    </div>
</div>

<!-- MODAL: REGISTRAR NUEVO PROVEEDOR -->
<div id="modalNuevoProveedor" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:560px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-truck-field text-gold"></i> Registrar Nuevo Proveedor</h3>
            <button onclick="document.getElementById('modalNuevoProveedor').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">NIT / Cédula:</label>
                    <input type="text" name="nit_cedula" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="900.123.456-7" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Empresa / Razón Social:</label>
                    <input type="text" name="empresa_nombre" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Ganadería Los Andes S.A.S" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Persona de Contacto:</label>
                    <input type="text" name="contacto_persona" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Carlos Mendoza" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Teléfono / WhatsApp:</label>
                    <input type="text" name="telefono" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="+57 300 1234567" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Correo Electrónico:</label>
                    <input type="email" name="email" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="ventas@losandes.com">
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Categoría de Insumo:</label>
                    <select name="categoria_insumo" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="Ganadería / Res">Ganadería / Res</option>
                        <option value="Porcinos / Cerdo">Porcinos / Cerdo</option>
                        <option value="Aves / Pollo">Aves / Pollo</option>
                        <option value="Empaques & Plásticos">Empaques & Plásticos</option>
                        <option value="Sazonadores & Especias">Sazonadores & Especias</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Dirección / Sede Proveedor:</label>
                <input type="text" name="direccion" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Km 14 Vía al Llano">
            </div>

            <button type="submit" name="crear_proveedor_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-plus"></i> REGISTRAR PROVEEDOR
            </button>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR PROVEEDOR -->
<div id="modalEditarProveedor" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:560px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-truck-field text-gold"></i> Editar Datos de Proveedor</h3>
            <button onclick="document.getElementById('modalEditarProveedor').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="dashboard-dueno.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="edit_prov_id" name="proveedor_id">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">NIT / Cédula:</label>
                    <input type="text" id="edit_prov_nit" name="nit_cedula" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Empresa / Razón Social:</label>
                    <input type="text" id="edit_prov_empresa" name="empresa_nombre" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Persona de Contacto:</label>
                    <input type="text" id="edit_prov_contacto" name="contacto_persona" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Teléfono / WhatsApp:</label>
                    <input type="text" id="edit_prov_telefono" name="telefono" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Correo Electrónico:</label>
                    <input type="email" id="edit_prov_email" name="email" class="search-input" style="width:100%; margin-top:0.3rem;">
                </div>
                <div>
                    <label style="font-size:0.85rem; color:var(--text-muted);">Categoría de Insumo:</label>
                    <select id="edit_prov_categoria" name="categoria_insumo" class="search-input" style="width:100%; margin-top:0.3rem;" required>
                        <option value="Ganadería / Res">Ganadería / Res</option>
                        <option value="Porcinos / Cerdo">Porcinos / Cerdo</option>
                        <option value="Aves / Pollo">Aves / Pollo</option>
                        <option value="Empaques & Plásticos">Empaques & Plásticos</option>
                        <option value="Sazonadores & Especias">Sazonadores & Especias</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Estado de Proveedor:</label>
                <select id="edit_prov_estado" name="estado" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <option value="activo">ACTIVO (Comercializando)</option>
                    <option value="inactivo">INACTIVO (Suspendido)</option>
                </select>
            </div>

            <button type="submit" name="editar_proveedor_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS PROVEEDOR
            </button>
        </form>
    </div>
</div>

<script>
function previewAvatarImageDueno(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar_dueno_preview_img').src = e.target.result;
            document.getElementById('edit_user_avatar').value = 'Archivo local seleccionado';
        };
        reader.readAsDataURL(input.files[0]);
    }
}



function abrirModalEditarTrabajador(userObj) {
    document.getElementById('edit_user_id').value = userObj.id;
    document.getElementById('edit_user_eliminar_avatar').value = '0';
    document.getElementById('edit_user_nombre').value = userObj.nombre;
    document.getElementById('edit_user_correo').value = userObj.correo;
    document.getElementById('edit_user_rol').value = userObj.rol;
    document.getElementById('edit_user_sede').value = userObj.sede_asignada || 'Sede Principal';
    document.getElementById('edit_user_estado').value = userObj.estado || 'activo';
    const rawAvatar = userObj.avatar || 'images/avatar-default.png';
    document.getElementById('edit_user_avatar').value = rawAvatar;

    let previewUrl = rawAvatar;
    if (previewUrl && !previewUrl.startsWith('http') && !previewUrl.startsWith('../') && !previewUrl.startsWith('/')) {
        previewUrl = '../' + previewUrl;
    }
    document.getElementById('avatar_dueno_preview_img').src = previewUrl;

    document.getElementById('modalEditarUsuario').style.display = 'flex';
}

function eliminarFotoPerfilDueno() {
    document.getElementById('edit_user_eliminar_avatar').value = '1';
    document.getElementById('edit_user_avatar').value = 'images/avatar-default.png';
    const fileInput = document.getElementById('edit_user_file');
    if (fileInput) fileInput.value = '';
    const preview = document.getElementById('avatar_dueno_preview_img');
    if (preview) {
        preview.src = '../images/avatar-default.png?t=' + Date.now();
    }
}

function previewAvatarImageCreate(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const prev = document.getElementById('avatar_create_preview_img');
            if (prev) prev.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function calcularPrecioOferta(mode) {
    if (mode === 'create') {
        const precio = parseFloat(document.querySelector('input[name="precio"]').value) || 0;
        const pct = parseFloat(document.querySelector('input[name="descuento_porcentaje"]').value) || 0;
        if (precio > 0 && pct > 0) {
            const finalPrice = Math.round(precio * (1 - (pct / 100)));
            document.querySelector('input[name="precio_oferta"]').value = finalPrice;
        } else {
            document.querySelector('input[name="precio_oferta"]').value = '';
        }
    } else {
        const precio = parseFloat(document.getElementById('edit_precio').value) || 0;
        const pct = parseFloat(document.getElementById('edit_descuento_porcentaje').value) || 0;
        if (precio > 0 && pct > 0) {
            const finalPrice = Math.round(precio * (1 - (pct / 100)));
            document.getElementById('edit_precio_oferta').value = finalPrice;
        } else {
            document.getElementById('edit_precio_oferta').value = '';
        }
    }
}

function previewProductImageCreate(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('create_prod_preview_img').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewProductImageEdit(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit_prod_preview_img').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function abrirModalEditar(prod) {
    document.getElementById('edit_producto_id').value = prod.id;
    document.getElementById('edit_nombre').value = prod.nombre;
    document.getElementById('edit_categoria').value = prod.categoria;
    document.getElementById('edit_precio').value = prod.precio;
    document.getElementById('edit_unidad').value = prod.unidad;
    document.getElementById('edit_etiqueta').value = prod.etiqueta || '';

    const rawProdImg = prod.imagen || 'images/tomahawk.jpg';
    document.getElementById('edit_imagen').value = rawProdImg;
    let prodPreviewUrl = rawProdImg;
    if (prodPreviewUrl && !prodPreviewUrl.startsWith('http') && !prodPreviewUrl.startsWith('../') && !prodPreviewUrl.startsWith('/')) {
        prodPreviewUrl = '../' + prodPreviewUrl;
    }
    document.getElementById('edit_prod_preview_img').src = prodPreviewUrl;

    document.getElementById('edit_descripcion').value = prod.descripcion || '';

    document.getElementById('edit_destacado').checked = (parseInt(prod.destacado) === 1);

    const enDesc = (parseInt(prod.en_descuento) === 1);
    document.getElementById('edit_en_descuento').checked = enDesc;
    document.getElementById('edit_descuento_porcentaje').value = prod.descuento_porcentaje || 0;
    
    document.getElementById('edit_discount_box').style.display = enDesc ? 'grid' : 'none';
    if (enDesc) calcularPrecioOferta('edit');

    document.getElementById('modalEditarProducto').style.display = 'flex';
}

function abrirModalEditarProveedor(prov) {
    document.getElementById('edit_prov_id').value = prov.id;
    document.getElementById('edit_prov_nit').value = prov.nit_cedula;
    document.getElementById('edit_prov_empresa').value = prov.empresa_nombre;
    document.getElementById('edit_prov_contacto').value = prov.contacto_persona;
    document.getElementById('edit_prov_telefono').value = prov.telefono;
    document.getElementById('edit_prov_email').value = prov.email || '';
    document.getElementById('edit_prov_categoria').value = prov.categoria_insumo;
    document.getElementById('edit_prov_estado').value = prov.estado || 'activo';

    document.getElementById('modalEditarProveedor').style.display = 'flex';
}

// === SELECCIÓN EN LOTE TRABAJADORES ===
function toggleSelectAllTrabajadores(master) {
    const checkboxes = document.querySelectorAll('.check-trabajador-item:not([disabled])');
    checkboxes.forEach(cb => cb.checked = master.checked);
    actualizarBarraTrabajadores();
}

function deseleccionarTodosTrabajadores() {
    const master = document.getElementById('check-all-trabajadores');
    if (master) master.checked = false;
    toggleSelectAllTrabajadores({ checked: false });
}

function actualizarBarraTrabajadores() {
    const checkboxes = document.querySelectorAll('.check-trabajador-item:checked');
    const total = checkboxes.length;
    const barra = document.getElementById('barra-acciones-trabajadores');
    const counter = document.getElementById('counter-trabajadores');
    const master = document.getElementById('check-all-trabajadores');

    if (counter) counter.innerText = total + ' trabajador' + (total !== 1 ? 'es' : '') + ' seleccionado' + (total !== 1 ? 's' : '');
    if (barra) barra.style.display = (total > 0) ? 'flex' : 'none';

    const allCheckboxes = document.querySelectorAll('.check-trabajador-item:not([disabled])');
    if (master) master.checked = (allCheckboxes.length > 0 && total === allCheckboxes.length);
}

function ejecutarAccionLoteTrabajadores(accion) {
    const checked = document.querySelectorAll('.check-trabajador-item:checked');
    if (checked.length === 0) {
        alert('Por favor selecciona al menos un trabajador.');
        return;
    }

    let confirmMsg = '';
    if (accion === 'eliminar') confirmMsg = '¿Estás seguro de eliminar ' + checked.length + ' trabajador(es) seleccionado(s)? Esta acción no se puede deshacer.';
    else if (accion === 'activar') confirmMsg = '¿Activar ' + checked.length + ' trabajador(es) seleccionado(s)?';
    else if (accion === 'inactivar') confirmMsg = '¿Inactivar ' + checked.length + ' trabajador(es) seleccionado(s)?';

    if (confirm(confirmMsg)) {
        document.getElementById('tipo_accion_trabajadores').value = accion;
        document.getElementById('form-lote-trabajadores').submit();
    }
}

// === SELECCIÓN EN LOTE PRODUCTOS ===
function toggleSelectAllProductos(master) {
    const checkboxes = document.querySelectorAll('.check-producto-item');
    checkboxes.forEach(cb => cb.checked = master.checked);
    actualizarBarraProductos();
}

function deseleccionarTodosProductos() {
    const master = document.getElementById('check-all-productos');
    if (master) master.checked = false;
    toggleSelectAllProductos({ checked: false });
}

function actualizarBarraProductos() {
    const checkboxes = document.querySelectorAll('.check-producto-item:checked');
    const total = checkboxes.length;
    const barra = document.getElementById('barra-acciones-productos');
    const counter = document.getElementById('counter-productos');
    const master = document.getElementById('check-all-productos');

    if (counter) counter.innerText = total + ' producto' + (total !== 1 ? 's' : '') + ' seleccionado' + (total !== 1 ? 's' : '');
    if (barra) barra.style.display = (total > 0) ? 'flex' : 'none';

    const allCheckboxes = document.querySelectorAll('.check-producto-item');
    if (master) master.checked = (allCheckboxes.length > 0 && total === allCheckboxes.length);
}

function ejecutarAccionLoteProductos(accion) {
    const checked = document.querySelectorAll('.check-producto-item:checked');
    if (checked.length === 0) {
        alert('Por favor selecciona al menos un producto.');
        return;
    }

    let confirmMsg = '';
    if (accion === 'eliminar') confirmMsg = '¿Estás seguro de eliminar ' + checked.length + ' producto(s) seleccionado(s)? Esta acción no se puede deshacer.';
    else if (accion === 'visibles') confirmMsg = '¿Hacer visibles en web a ' + checked.length + ' producto(s) seleccionado(s)?';
    else if (accion === 'ocultos') confirmMsg = '¿Ocultar de la web a ' + checked.length + ' producto(s) seleccionado(s)?';

    if (confirm(confirmMsg)) {
        document.getElementById('tipo_accion_productos').value = accion;
        document.getElementById('form-lote-productos').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
