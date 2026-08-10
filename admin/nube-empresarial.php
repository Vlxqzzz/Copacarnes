<?php
$page_title = "Nube Empresarial";
$active_menu = "nube";
$required_roles = ['dueno', 'admin', 'cajero'];

require_once __DIR__ . '/includes/admin_header.php';

$can_upload = in_array($user['rol'], ['dueno', 'admin', 'cajero']);
$folder_msg = '';

// 1. PROCESAR CREACIÓN DE NUEVA CARPETA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_carpeta_btn'])) {
    if (!$can_upload) {
        $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">⛔ Tu rol no tiene permisos para gestionar carpetas.</div>';
    } else {
        $nombre_car = trim($_POST['nombre_carpeta'] ?? '');
        $color_car = $_POST['color_carpeta'] ?? '#d4af37';
        $icono_car = $_POST['icono_carpeta'] ?? 'fa-folder-closed';

        if (!empty($nombre_car) && $pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO nube_carpetas (nombre, color, icono) VALUES (?, ?, ?)");
                $stmt->execute([$nombre_car, $color_car, $icono_car]);
                $folder_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Carpeta <strong>' . htmlspecialchars($nombre_car) . '</strong> creada con éxito.</div>';
            } catch (Exception $e) {
                $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">❌ Error al crear la carpeta: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// 2. PROCESAR EDICIÓN DE CARPETA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_carpeta_btn'])) {
    if ($can_upload) {
        $id_car = intval($_POST['carpeta_id'] ?? 0);
        $nombre_old = trim($_POST['nombre_anterior'] ?? '');
        $nombre_new = trim($_POST['nombre_carpeta'] ?? '');
        $color_car = $_POST['color_carpeta'] ?? '#d4af37';

        if ($id_car > 0 && !empty($nombre_new) && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE nube_carpetas SET nombre = ?, color = ? WHERE id = ?");
                $stmt->execute([$nombre_new, $color_car, $id_car]);

                if (!empty($nombre_old) && $nombre_old !== $nombre_new) {
                    $stmt_files = $pdo->prepare("UPDATE nube_archivos SET carpeta = ? WHERE carpeta = ?");
                    $stmt_files->execute([$nombre_new, $nombre_old]);
                }
                $folder_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Carpeta <strong>' . htmlspecialchars($nombre_new) . '</strong> actualizada con éxito.</div>';
            } catch (Exception $e) {}
        }
    }
}

if (!function_exists('delete_directory_recursive')) {
    function delete_directory_recursive($dir) {
        if (!is_dir($dir)) return false;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            (is_dir($path)) ? delete_directory_recursive($path) : @unlink($path);
        }
        return @rmdir($dir);
    }
}

// 0. PURGA AUTOMÁTICA DE LA PAPELERA (RETENCIÓN DE 30 DÍAS)
if ($pdo) {
    try {
        $stmt_p_files = $pdo->query("SELECT * FROM nube_archivos WHERE en_papelera = 1 AND fecha_eliminacion <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        foreach ($stmt_p_files->fetchAll() as $of) {
            $pf = __DIR__ . '/' . $of['ruta_archivo'];
            if (file_exists($pf)) @unlink($pf);
            $pdo->prepare("DELETE FROM nube_archivos WHERE id = ?")->execute([$of['id']]);
        }

        $stmt_p_fold = $pdo->query("SELECT * FROM nube_carpetas WHERE en_papelera = 1 AND fecha_eliminacion <= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        foreach ($stmt_p_fold->fetchAll() as $oc) {
            $fn = $oc['nombre'];
            $stmt_f_in = $pdo->prepare("SELECT ruta_archivo FROM nube_archivos WHERE carpeta = ?");
            $stmt_f_in->execute([$fn]);
            foreach ($stmt_f_in->fetchAll() as $fa) {
                $pa = __DIR__ . '/' . $fa['ruta_archivo'];
                if (file_exists($pa)) @unlink($pa);
            }
            $pdo->prepare("DELETE FROM nube_archivos WHERE carpeta = ?")->execute([$fn]);

            if (preg_match('/Transferencias\s*\(([^)]+)\)/i', $fn, $m_date)) {
                $date_folder = trim($m_date[1]);
                $disk_dir = __DIR__ . '/uploads/nube_empresarial/transferencias/' . $date_folder;
                if (is_dir($disk_dir)) delete_directory_recursive($disk_dir);
            }
            $pdo->prepare("DELETE FROM nube_carpetas WHERE id = ?")->execute([$oc['id']]);
        }
    } catch (Exception $e) {}
}

// 3. PROCESAR ELIMINACIÓN DE CARPETA (MOVER A PAPELERA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_carpeta_btn'])) {
    if ($can_upload) {
        $id_car = intval($_POST['carpeta_id'] ?? 0);
        $nombre_car = trim($_POST['nombre_carpeta'] ?? '');

        if ($id_car > 0 && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE nube_carpetas SET en_papelera = 1, fecha_eliminacion = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$id_car]);

                if (!empty($nombre_car)) {
                    $stmt_del_files = $pdo->prepare("UPDATE nube_archivos SET en_papelera = 1, fecha_eliminacion = CURRENT_TIMESTAMP WHERE carpeta = ?");
                    $stmt_del_files->execute([$nombre_car]);
                }
                $folder_msg = '<div class="alert alert-success" style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash-can-arrow-up"></i> Carpeta <strong>' . htmlspecialchars($nombre_car) . '</strong> movida a la Papelera. Se eliminará permanentemente en 30 días.</div>';
            } catch (Exception $e) {
                $folder_msg = '<div class="alert alert-error">Error al mover carpeta a la papelera: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// 4. PROCESAR EDICIÓN DE ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_archivo_btn'])) {
    if ($can_upload) {
        $file_id = intval($_POST['archivo_id'] ?? 0);
        $nuevo_nombre = trim($_POST['nombre_original'] ?? '');
        $nueva_carpeta = $_POST['carpeta'] ?? 'Facturas & Contratos';
        $nueva_version = trim($_POST['version'] ?? 'v1.0');
        $nuevos_comentarios = trim($_POST['comentarios'] ?? '');

        if ($file_id > 0 && !empty($nuevo_nombre) && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE nube_archivos SET nombre_original = ?, carpeta = ?, version = ?, comentarios = ? WHERE id = ?");
                $stmt->execute([$nuevo_nombre, $nueva_carpeta, $nueva_version, $nuevos_comentarios, $file_id]);
                $folder_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Archivo <strong>' . htmlspecialchars($nuevo_nombre) . '</strong> actualizado con éxito.</div>';
            } catch (Exception $e) {}
        }
    }
}

// 5. PROCESAR ELIMINACIÓN DE ARCHIVO (MOVER A PAPELERA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_archivo_btn'])) {
    if ($can_upload) {
        $file_id = intval($_POST['archivo_id'] ?? 0);
        if ($file_id > 0 && $pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE nube_archivos SET en_papelera = 1, fecha_eliminacion = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$file_id]);
                $folder_msg = '<div class="alert alert-success" style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fbbf24; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash-can-arrow-up"></i> Archivo movido a la Papelera de Reciclaje. Se eliminará permanentemente en 30 días.</div>';
            } catch (Exception $e) {}
        }
    }
}

// 7. PROCESAR RESTAURACIÓN DESDE PAPELERA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restaurar_item_btn'])) {
    $tipo_item = $_POST['tipo_item'] ?? 'archivo';
    $item_id = intval($_POST['item_id'] ?? 0);

    if ($pdo && $item_id > 0) {
        try {
            if ($tipo_item === 'carpeta') {
                $stmt_c = $pdo->prepare("SELECT nombre FROM nube_carpetas WHERE id = ?");
                $stmt_c->execute([$item_id]);
                $c_info = $stmt_c->fetch();
                if ($c_info) {
                    $pdo->prepare("UPDATE nube_carpetas SET en_papelera = 0, fecha_eliminacion = NULL WHERE id = ?")->execute([$item_id]);
                    $pdo->prepare("UPDATE nube_archivos SET en_papelera = 0, fecha_eliminacion = NULL WHERE carpeta = ?")->execute([$c_info['nombre']]);
                }
            } else {
                $pdo->prepare("UPDATE nube_archivos SET en_papelera = 0, fecha_eliminacion = NULL WHERE id = ?")->execute([$item_id]);
            }
            $folder_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-rotate-left"></i> Elemento restaurado con éxito a la Nube.</div>';
        } catch (Exception $e) {}
    }
}

// 8. PROCESAR PURGA PERMANENTE INDIVIDUAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purgar_definitivo_btn'])) {
    if ($can_upload) {
        $tipo_item = $_POST['tipo_item'] ?? 'archivo';
        $item_id = intval($_POST['item_id'] ?? 0);

        if ($pdo && $item_id > 0) {
            try {
                if ($tipo_item === 'carpeta') {
                    $stmt_c = $pdo->prepare("SELECT nombre FROM nube_carpetas WHERE id = ?");
                    $stmt_c->execute([$item_id]);
                    $c_info = $stmt_c->fetch();
                    if ($c_info) {
                        $f_name = $c_info['nombre'];
                        $stmt_files = $pdo->prepare("SELECT ruta_archivo FROM nube_archivos WHERE carpeta = ?");
                        $stmt_files->execute([$f_name]);
                        foreach ($stmt_files->fetchAll() as $fa) {
                            $pa = __DIR__ . '/' . $fa['ruta_archivo'];
                            if (file_exists($pa)) @unlink($pa);
                        }
                        if (preg_match('/Transferencias\s*\(([^)]+)\)/i', $f_name, $m_date)) {
                            $date_folder = trim($m_date[1]);
                            $disk_dir = __DIR__ . '/uploads/nube_empresarial/transferencias/' . $date_folder;
                            if (is_dir($disk_dir)) delete_directory_recursive($disk_dir);
                        }
                        $pdo->prepare("DELETE FROM nube_archivos WHERE carpeta = ?")->execute([$f_name]);
                        $pdo->prepare("DELETE FROM nube_carpetas WHERE id = ?")->execute([$item_id]);
                    }
                } else {
                    $stmt_f = $pdo->prepare("SELECT ruta_archivo FROM nube_archivos WHERE id = ?");
                    $stmt_f->execute([$item_id]);
                    $f_info = $stmt_f->fetch();
                    if ($f_info) {
                        $pa = __DIR__ . '/' . $f_info['ruta_archivo'];
                        if (file_exists($pa)) @unlink($pa);
                        $pdo->prepare("DELETE FROM nube_archivos WHERE id = ?")->execute([$item_id]);
                    }
                }
                $folder_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-fire"></i> Elemento eliminado permanentemente del servidor.</div>';
            } catch (Exception $e) {}
        }
    }
}

// 9. PROCESAR VACIADO COMPLETO DE PAPELERA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaciar_papelera_btn'])) {
    if ($can_upload) {
        if ($pdo) {
            try {
                $stmt_f = $pdo->query("SELECT ruta_archivo FROM nube_archivos WHERE en_papelera = 1");
                foreach ($stmt_f->fetchAll() as $fa) {
                    $pa = __DIR__ . '/' . $fa['ruta_archivo'];
                    if (file_exists($pa)) @unlink($pa);
                }
                $pdo->exec("DELETE FROM nube_archivos WHERE en_papelera = 1");

                $stmt_c = $pdo->query("SELECT nombre FROM nube_carpetas WHERE en_papelera = 1");
                foreach ($stmt_c->fetchAll() as $ca) {
                    if (preg_match('/Transferencias\s*\(([^)]+)\)/i', $ca['nombre'], $m_date)) {
                        $date_folder = trim($m_date[1]);
                        $disk_dir = __DIR__ . '/uploads/nube_empresarial/transferencias/' . $date_folder;
                        if (is_dir($disk_dir)) delete_directory_recursive($disk_dir);
                    }
                }
                $pdo->exec("DELETE FROM nube_carpetas WHERE en_papelera = 1");

                $folder_msg = '<div class="alert alert-success" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-trash-can"></i> Papelera vaciada por completo.</div>';
            } catch (Exception $e) {}
        }
    }
}

// 6. PROCESAR CARGA DE ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file_btn'])) {
    if (!$can_upload) {
        $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">⛔ Tu rol no tiene permisos para subir archivos.</div>';
    } else {
        $carpeta = $_POST['carpeta'] ?? 'Facturas & Contratos';
        $comentarios = trim($_POST['comentarios'] ?? '');
        
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $err_code = $_FILES['archivo']['error'] ?? 4;
            $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">❌ Por favor selecciona un archivo válido. (Código error: ' . $err_code . ')</div>';
        } else {
            $file_name = $_FILES['archivo']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'xlsx', 'xls', 'docx', 'doc', 'png', 'jpg', 'jpeg', 'txt', 'csv'];

            if (in_array($ext, $allowed)) {
                $target_dir = __DIR__ . '/uploads/';
                if (!file_exists($target_dir)) {
                    @mkdir($target_dir, 0777, true);
                }

                $clean_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file_name);
                $target_file = $target_dir . $clean_filename;

                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $target_file)) {
                    $tipo_str = strtoupper($ext);
                    $tamano_kb = round($_FILES['archivo']['size'] / 1024, 2);
                    $rel_path = 'uploads/' . $clean_filename;
                    
                    if ($pdo) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO nube_archivos (carpeta, nombre_archivo, nombre_original, tipo_archivo, tamano_kb, ruta_archivo, usuario_id, usuario_nombre, rol, comentarios, version) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'v1.0')");
                            $stmt->execute([$carpeta, $clean_filename, $file_name, $tipo_str, $tamano_kb, $rel_path, $user['id'] ?? 1, $user['nombre'] ?? 'Usuario', $user['rol'] ?? 'admin', $comentarios]);
                            $folder_msg = '<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><i class="fa-solid fa-circle-check"></i> Archivo <strong>' . htmlspecialchars($file_name) . '</strong> subido con éxito a la carpeta <strong>' . htmlspecialchars($carpeta) . '</strong>.</div>';
                        } catch (Exception $e) {
                            $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">❌ Error al registrar en la base de datos: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                } else {
                    $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">❌ Error al guardar el archivo en el servidor.</div>';
                }
            } else {
                $folder_msg = '<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">❌ Formato no permitido (.' . htmlspecialchars($ext) . '). Formatos permitidos: PDF, Excel, Word e Imágenes.</div>';
            }
        }
    }
}

// 7. CONSULTAR CARPETAS Y ARCHIVOS REALES DE LA BASE DE DATOS
$carpetas_list = [];
$papelera_carpetas = [];
$papelera_archivos = [];
$total_papelera = 0;

if ($pdo) {
    try {
        // Auto-sincronización de carpetas de transferencias guardadas en el disco
        $trans_base = __DIR__ . '/uploads/nube_empresarial/transferencias';
        if (is_dir($trans_base)) {
            $date_dirs = glob($trans_base . '/*', GLOB_ONLYDIR);
            foreach ($date_dirs as $d_dir) {
                $folder_date = basename($d_dir);
                $folder_name = 'Transferencias (' . $folder_date . ')';

                // 1. Asegurar carpeta en la BD si no está en papelera
                $stmt_c = $pdo->prepare("SELECT id, en_papelera FROM nube_carpetas WHERE nombre = ? LIMIT 1");
                $stmt_c->execute([$folder_name]);
                $c_found = $stmt_c->fetch();
                if (!$c_found) {
                    $stmt_i = $pdo->prepare("INSERT INTO nube_carpetas (nombre, color, icono, en_papelera) VALUES (?, '#10b981', 'fa-folder-closed', 0)");
                    $stmt_i->execute([$folder_name]);
                }

                if (!$c_found || intval($c_found['en_papelera']) === 0) {
                    // Escanear archivos si la carpeta está activa
                    $files = glob($d_dir . '/*.*');
                    foreach ($files as $f_path) {
                        $f_name = basename($f_path);
                        $rel_path = 'uploads/nube_empresarial/transferencias/' . $folder_date . '/' . $f_name;
                        
                        $stmt_f_chk = $pdo->prepare("SELECT id FROM nube_archivos WHERE ruta_archivo = ? LIMIT 1");
                        $stmt_f_chk->execute([$rel_path]);
                        if (!$stmt_f_chk->fetch()) {
                            $ext = strtoupper(pathinfo($f_name, PATHINFO_EXTENSION));
                            $kb = file_exists($f_path) ? round(filesize($f_path) / 1024, 2) : 0;
                            $stmt_f_ins = $pdo->prepare("INSERT INTO nube_archivos (carpeta, nombre_archivo, nombre_original, tipo_archivo, tamano_kb, ruta_archivo, usuario_id, usuario_nombre, rol, comentarios, version, en_papelera) VALUES (?, ?, ?, ?, ?, ?, 1, 'Cajero POS', 'cajero', 'Comprobante de transferencia', 'v1.0', 0)");
                            $stmt_f_ins->execute([$folder_name, $f_name, $f_name, $ext, $kb, $rel_path]);
                        }
                    }
                }
            }
        }

        $stmt_carp = $pdo->query("SELECT * FROM nube_carpetas WHERE en_papelera = 0 OR en_papelera IS NULL ORDER BY id ASC");
        $carpetas_list = $stmt_carp->fetchAll();

        $stmt_files_all = $pdo->query("SELECT * FROM nube_archivos WHERE en_papelera = 0 OR en_papelera IS NULL ORDER BY id DESC");
        $all_files = $stmt_files_all->fetchAll();

        $stmt_pap_c = $pdo->query("SELECT * FROM nube_carpetas WHERE en_papelera = 1 ORDER BY fecha_eliminacion DESC");
        $papelera_carpetas = $stmt_pap_c->fetchAll();

        $stmt_pap_f = $pdo->query("SELECT * FROM nube_archivos WHERE en_papelera = 1 ORDER BY fecha_eliminacion DESC");
        $papelera_archivos = $stmt_pap_f->fetchAll();

        $total_papelera = count($papelera_carpetas) + count($papelera_archivos);

        foreach ($carpetas_list as &$c) {
            $c['archivos'] = [];
            foreach ($all_files as $f) {
                if ($f['carpeta'] === $c['nombre']) {
                    $c['archivos'][] = $f;
                }
            }
        }
    } catch (Exception $e) {}
}
?>

<!-- Encabezado de Nube Empresarial -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; margin: 0; color: #fff;">
            <i class="fa-solid fa-cloud text-gold"></i> Nube Empresarial Copacarnes (Google Drive)
        </h1>
        <p style="margin: 0.3rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">
            Almacenamiento seguro de contratos, facturas, estados financieros y manuales internos.
        </p>
    </div>
    <?php if ($can_upload): ?>
    <div style="display:flex; gap:0.8rem;">
        <button onclick="document.getElementById('modalPapeleraReciclaje').style.display='flex'" class="btn-export" style="background: rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5; font-weight:800; display:inline-flex; align-items:center; gap:0.4rem;">
            <i class="fa-solid fa-trash-can"></i> 🗑️ Papelera (<?php echo $total_papelera; ?>)
        </button>

        <button onclick="document.getElementById('modalCrearCarpeta').style.display='flex'" class="btn-export" style="background: rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa; font-weight:700;">
            <i class="fa-solid fa-folder-plus"></i> + Nueva Carpeta
        </button>

        <button onclick="document.getElementById('modalUpload').style.display='flex'" class="btn-export" style="background: linear-gradient(135deg, #d4af37, #aa820a); color:#000; font-weight:800;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Subir Archivo a la Nube
        </button>
    </div>
    <?php endif; ?>
</div>

<?php echo $folder_msg; ?>

<!-- MODAL 1: CREAR NUEVA CARPETA -->
<div id="modalCrearCarpeta" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:480px; width:100%; background:#0d1630; border-color:#3b82f6;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-folder-plus text-gold"></i> Crear Nueva Carpeta</h3>
            <button onclick="document.getElementById('modalCrearCarpeta').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="nube-empresarial.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre de la Carpeta:</label>
                <input type="text" name="nombre_carpeta" class="search-input" style="width:100%; margin-top:0.3rem;" placeholder="Ej. Recibos de Proveedores 2026" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Color Identificador:</label>
                <select name="color_carpeta" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <option value="var(--gold)">Dorado (#d4af37)</option>
                    <option value="#3b82f6">Azul (#3b82f6)</option>
                    <option value="#10b981">Verde (#10b981)</option>
                    <option value="#f59e0b">Naranja (#f59e0b)</option>
                    <option value="#ef4444">Rojo (#ef4444)</option>
                    <option value="#8b5cf6">Morado (#8b5cf6)</option>
                </select>
            </div>

            <button type="submit" name="crear_carpeta_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:#3b82f6; border-color:#3b82f6; color:#fff; font-weight:800;">
                <i class="fa-solid fa-plus-circle"></i> CREAR CARPETA
            </button>
        </form>
    </div>
</div>

<!-- MODAL 2: EDITAR CARPETA -->
<div id="modalEditarCarpeta" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:480px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-pen-to-square text-gold"></i> Editar Carpeta</h3>
            <button onclick="document.getElementById('modalEditarCarpeta').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="nube-empresarial.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="edit_carpeta_id" name="carpeta_id">
            <input type="hidden" id="edit_nombre_anterior" name="nombre_anterior">

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nuevo Nombre de la Carpeta:</label>
                <input type="text" id="edit_nombre_carpeta" name="nombre_carpeta" class="search-input" style="width:100%; margin-top:0.3rem;" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Color Identificador:</label>
                <select id="edit_color_carpeta" name="color_carpeta" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <option value="var(--gold)">Dorado (#d4af37)</option>
                    <option value="#3b82f6">Azul (#3b82f6)</option>
                    <option value="#10b981">Verde (#10b981)</option>
                    <option value="#f59e0b">Naranja (#f59e0b)</option>
                    <option value="#ef4444">Rojo (#ef4444)</option>
                    <option value="#8b5cf6">Morado (#8b5cf6)</option>
                </select>
            </div>

            <button type="submit" name="editar_carpeta_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS DE CARPETA
            </button>
        </form>
    </div>
</div>

<!-- MODAL 3: EDITAR DETALLES DE ARCHIVO -->
<div id="modalEditarArchivo" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10001; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:500px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-file-pen text-gold"></i> Editar Archivo</h3>
            <button onclick="document.getElementById('modalEditarArchivo').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="nube-empresarial.php" method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" id="edit_file_id" name="archivo_id">

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Nombre del Archivo:</label>
                <input type="text" id="edit_file_nombre" name="nombre_original" class="search-input" style="width:100%; margin-top:0.3rem;" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Mover a Carpeta:</label>
                <select id="edit_file_carpeta" name="carpeta" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <?php foreach ($carpetas_list as $c_sel): ?>
                        <option value="<?php echo htmlspecialchars($c_sel['nombre']); ?>"><?php echo htmlspecialchars($c_sel['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Versión del Documento:</label>
                <input type="text" id="edit_file_version" name="version" class="search-input" style="width:100%; margin-top:0.3rem;" value="v1.0" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Comentarios / Notas:</label>
                <textarea id="edit_file_comentarios" name="comentarios" class="search-input" style="width:100%; height:70px; margin-top:0.3rem;"></textarea>
            </div>

            <button type="submit" name="editar_archivo_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); border-color:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-save"></i> GUARDAR CAMBIOS DEL ARCHIVO
            </button>
        </form>
    </div>
</div>

<!-- MODAL 4: SUBIR NUEVO ARCHIVO -->
<div id="modalUpload" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:550px; width:100%; background:#0d1630; border-color:var(--gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0; color:#fff;"><i class="fa-solid fa-cloud-arrow-up text-gold"></i> Subir Nuevo Documento</h3>
            <button onclick="document.getElementById('modalUpload').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>

        <form action="nube-empresarial.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;">
            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Carpeta Destino:</label>
                <select name="carpeta" class="search-input" style="width:100%; margin-top:0.3rem;">
                    <?php foreach ($carpetas_list as $c_select): ?>
                        <option value="<?php echo htmlspecialchars($c_select['nombre']); ?>"><?php echo htmlspecialchars($c_select['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Seleccionar Archivo (PDF, Excel, Word, Imagen):</label>
                <input type="file" name="archivo" class="search-input" style="width:100%; margin-top:0.3rem;" required>
            </div>

            <div>
                <label style="font-size:0.85rem; color:var(--text-muted);">Comentarios u Observaciones:</label>
                <textarea name="comentarios" class="search-input" style="width:100%; height:70px; margin-top:0.3rem;" placeholder="Detalles de la versión o contenido..."></textarea>
            </div>

            <button type="submit" name="upload_file_btn" class="btn-export" style="justify-content:center; padding:0.8rem; background:var(--gold); color:#000; font-weight:800;">
                <i class="fa-solid fa-upload"></i> GUARDAR EN LA NUBE
            </button>
        </form>
    </div>
</div>

<!-- Modal Visor de Documentos (PDF e Imágenes) -->
<div id="modalViewer" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10002; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:900px; width:100%; height:85vh; background:#0d1630; border-color:var(--gold); display:flex; flex-direction:column;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 id="viewerTitle" style="margin:0; color:#fff; font-size:1.1rem;"><i class="fa-solid fa-file-pdf text-gold"></i> Visualizador de Documento</h3>
            <button onclick="cerrarVisor()" style="background:none; border:none; color:#fff; font-size:1.6rem; cursor:pointer;">&times;</button>
        </div>
        <div style="flex:1; width:100%; height:100%; background:#000; border-radius:8px; overflow:hidden;">
            <iframe id="viewerFrame" src="" style="width:100%; height:100%; border:none;"></iframe>
        </div>
    </div>
</div>

<!-- Modal Flotante de Archivos por Carpeta Seleccionada -->
<div id="modalCarpetaArchivos" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9998; align-items:center; justify-content:center; padding:1.5rem;">
    <div class="data-table-card" style="max-width:920px; width:100%; background:#0d1630; border-color:var(--gold); max-height:85vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color);">
            <h3 id="modalCarpetaTitulo" style="margin:0; color:#fff; font-size:1.2rem;">
                <i class="fa-solid fa-folder-open text-gold"></i> Archivos de la Carpeta
            </h3>
            <button onclick="document.getElementById('modalCarpetaArchivos').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.6rem; cursor:pointer;">&times;</button>
        </div>

        <div id="modalCarpetaContenido"></div>
    </div>
</div>

<!-- GRID DINÁMICO DE CARPETAS DE ALMACENAMIENTO (CREAR / EDITAR / ELIMINAR / NAVEGAR) -->
<div style="margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 1rem 0; color: #fff; font-size: 1.15rem;">
        <i class="fa-solid fa-folder-tree text-gold"></i> Carpetas de Almacenamiento
    </h3>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.2rem;">
        <?php foreach ($carpetas_list as $carp): ?>
        <div class="kpi-card" style="position:relative; border-left-color: <?php echo htmlspecialchars($carp['color']); ?>; padding-right: 2rem;">
            <!-- Botones de Acción de Carpeta (Editar y Eliminar) -->
            <?php if ($can_upload): ?>
            <div style="position:absolute; top:10px; right:10px; display:flex; gap:0.3rem;">
                <button onclick='abrirModalEditarCarpeta(<?php echo json_encode($carp, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' style="background:rgba(59, 130, 246, 0.2); border:1px solid #3b82f6; color:#60a5fa; width:26px; height:26px; border-radius:4px; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Editar Carpeta">
                    <i class="fa-solid fa-pen" style="font-size:0.75rem;"></i>
                </button>

                <form action="nube-empresarial.php" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la carpeta \'<?php echo htmlspecialchars($carp['nombre']); ?>\' y todos sus archivos?');" style="display:inline;">
                    <input type="hidden" name="carpeta_id" value="<?php echo $carp['id']; ?>">
                    <input type="hidden" name="nombre_carpeta" value="<?php echo htmlspecialchars($carp['nombre']); ?>">
                    <button type="submit" name="eliminar_carpeta_btn" style="background:rgba(239, 68, 68, 0.2); border:1px solid #ef4444; color:#fca5a5; width:26px; height:26px; border-radius:4px; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Eliminar Carpeta">
                        <i class="fa-solid fa-trash" style="font-size:0.75rem;"></i>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div onclick='abrirCarpetaModal(<?php echo json_encode($carp['nombre']); ?>, <?php echo json_encode($carp['archivos'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' style="cursor:pointer; display:flex; align-items:center; gap:0.8rem;">
                <i class="fa-solid <?php echo htmlspecialchars($carp['icono'] ?: 'fa-folder-closed'); ?>" style="font-size:2.2rem; color:<?php echo htmlspecialchars($carp['color']); ?>;"></i>
                <div>
                    <strong style="color:#fff; display:block; font-size:0.95rem; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; max-width:160px;"><?php echo htmlspecialchars($carp['nombre']); ?></strong>
                    <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo count($carp['archivos']); ?> Archivo(s)</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function abrirVisor(ruta, nombre) {
    document.getElementById('viewerTitle').innerHTML = '<i class="fa-solid fa-eye text-gold"></i> Visualizando: ' + nombre;
    document.getElementById('viewerFrame').src = ruta;
    document.getElementById('modalViewer').style.display = 'flex';
}

function cerrarVisor() {
    document.getElementById('modalViewer').style.display = 'none';
    document.getElementById('viewerFrame').src = '';
}

function abrirModalEditarCarpeta(carp) {
    document.getElementById('edit_carpeta_id').value = carp.id;
    document.getElementById('edit_nombre_anterior').value = carp.nombre;
    document.getElementById('edit_nombre_carpeta').value = carp.nombre;
    document.getElementById('edit_color_carpeta').value = carp.color || 'var(--gold)';

    document.getElementById('modalEditarCarpeta').style.display = 'flex';
}

function abrirModalEditarArchivo(file) {
    document.getElementById('edit_file_id').value = file.id;
    document.getElementById('edit_file_nombre').value = file.nombre_original;
    document.getElementById('edit_file_carpeta').value = file.carpeta;
    document.getElementById('edit_file_version').value = file.version || 'v1.0';
    document.getElementById('edit_file_comentarios').value = file.comentarios || '';

    document.getElementById('modalEditarArchivo').style.display = 'flex';
}

function abrirCarpetaModal(nombreCarpeta, archivos) {
    document.getElementById('modalCarpetaTitulo').innerHTML = '<i class="fa-solid fa-folder-open text-gold"></i> Carpeta: <span style="color:var(--gold);">' + nombreCarpeta + '</span>';
    
    let html = '';
    if (!archivos || archivos.length === 0) {
        html = '<div style="text-align:center; padding:2.5rem; color:var(--text-muted);"><i class="fa-solid fa-folder-open" style="font-size:3rem; margin-bottom:0.8rem; opacity:0.5;"></i><br>No hay archivos guardados en esta carpeta.</div>';
    } else {
        html = '<table class="custom-table"><thead><tr><th>Tipo</th><th>Nombre del Archivo</th><th>Subido por</th><th>Tamaño</th><th>Versión</th><th>Fecha y Hora</th><th>Acciones (Visualizar / Editar / Eliminar / Descargar)</th></tr></thead><tbody>';
        
        archivos.forEach(file => {
            let iconHtml = '<i class="fa-solid fa-file-pdf" style="color:#ef4444; font-size:1.3rem;"></i>';
            if (['EXCEL', 'XLSX', 'XLS', 'CSV'].includes(file.tipo_archivo)) {
                iconHtml = '<i class="fa-solid fa-file-excel" style="color:#10b981; font-size:1.3rem;"></i>';
            } else if (['PNG', 'JPG', 'JPEG'].includes(file.tipo_archivo)) {
                iconHtml = '<i class="fa-solid fa-file-image" style="color:#f59e0b; font-size:1.3rem;"></i>';
            } else if (!['PDF'].includes(file.tipo_archivo)) {
                iconHtml = '<i class="fa-solid fa-file-word" style="color:#3b82f6; font-size:1.3rem;"></i>';
            }

            let fileJson = JSON.stringify(file).replace(/'/g, "&apos;").replace(/"/g, "&quot;");

            let btnActions = '<div style="display:flex; gap:0.3rem;">' +
                '<!-- 1. Visualizar -->' +
                '<button onclick="abrirVisor(\'' + file.ruta_archivo + '\', \'' + file.nombre_original + '\')" class="btn-export" style="padding:0.25rem 0.5rem; background:rgba(59, 130, 246, 0.2); border-color:#3b82f6; color:#60a5fa; font-weight:700;" title="Visualizar Documento"><i class="fa-solid fa-eye"></i> Visualizar</button>' +
                '<!-- 2. Editar -->' +
                '<button onclick=\'abrirModalEditarArchivo(' + JSON.stringify(file) + ')\' class="btn-export" style="padding:0.25rem 0.5rem; background:rgba(212, 175, 55, 0.2); border-color:var(--gold); color:var(--gold); font-weight:700;" title="Editar Archivo"><i class="fa-solid fa-pen"></i> Editar</button>' +
                '<!-- 3. Descargar -->' +
                '<a href="' + file.ruta_archivo + '" download class="btn-export" style="padding:0.25rem 0.5rem; background:rgba(16, 185, 129, 0.2); border-color:#10b981; color:#34d399; font-weight:700; text-decoration:none;" title="Descargar Archivo"><i class="fa-solid fa-download"></i> Descargar</a>' +
                '<!-- 4. Eliminar -->' +
                '<form action="nube-empresarial.php" method="POST" onsubmit="return confirm(\'¿Deseas eliminar este archivo?\');" style="display:inline;">' +
                    '<input type="hidden" name="archivo_id" value="' + file.id + '">' +
                    '<button type="submit" name="eliminar_archivo_btn" class="btn-export" style="padding:0.25rem 0.5rem; background:rgba(239, 68, 68, 0.2); border-color:#ef4444; color:#fca5a5; font-weight:700;" title="Eliminar Archivo"><i class="fa-solid fa-trash"></i> Eliminar</button>' +
                '</form>' +
            '</div>';

            html += '<tr>' +
                '<td>' + iconHtml + '</td>' +
                '<td><strong style="color:#fff;">' + file.nombre_original + '</strong></td>' +
                '<td>' + file.usuario_nombre + '</td>' +
                '<td>' + file.tamano_kb + ' KB</td>' +
                '<td><span class="status-pill success">' + file.version + '</span></td>' +
                '<td><i class="fa-solid fa-clock" style="color:var(--text-muted);"></i> ' + file.fecha_hora + '</td>' +
                '<td>' + btnActions + '</td>' +
            '</tr>';
        });

        html += '</tbody></table>';
    }

    document.getElementById('modalCarpetaContenido').innerHTML = html;
    document.getElementById('modalCarpetaArchivos').style.display = 'flex';
}
</script>

<!-- MODAL OVERLAY: PAPELERA DE RECICLAJE (RETENCIÓN 30 DÍAS) -->
<div id="modalPapeleraReciclaje" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.88); backdrop-filter:blur(8px); z-index:99999; align-items:center; justify-content:center; padding:1.5rem; box-sizing:border-box;">
    <div class="data-table-card" style="max-width:920px; width:100%; background:#0d1630; border:1.5px solid #ef4444; max-height:85vh; overflow-y:auto; box-shadow:0 20px 50px rgba(0,0,0,0.9);">
        
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(239,68,68,0.3); padding-bottom:0.8rem; margin-bottom:1.2rem; flex-wrap:wrap; gap:0.5rem;">
            <div>
                <h3 style="margin:0; color:#fff; font-size:1.25rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
                    <i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> 🗑️ Papelera de Reciclaje ERP Copacarnes
                </h3>
                <span style="font-size:0.82rem; color:var(--text-muted); display:block; margin-top:0.2rem;">
                    Los elementos se conservan durante <strong style="color:#fbbf24;">30 días</strong> antes de eliminarse permanentemente del servidor.
                </span>
            </div>
            
            <div style="display:flex; gap:0.6rem; align-items:center;">
                <?php if ($total_papelera > 0 && $can_upload): ?>
                <form action="nube-empresarial.php" method="POST" onsubmit="return confirm('¿Seguro que deseas vaciar toda la Papelera de Reciclaje? Todos los archivos se borrarán permanentemente del disco.');" style="margin:0;">
                    <button type="submit" name="vaciar_papelera_btn" class="btn-export" style="background:rgba(239,68,68,0.25); border-color:#ef4444; color:#fca5a5; font-weight:800; font-size:0.82rem; padding:0.4rem 0.8rem;">
                        <i class="fa-solid fa-broom"></i> 🧹 Vaciar Papelera Completa
                    </button>
                </form>
                <?php endif; ?>
                <button type="button" onclick="document.getElementById('modalPapeleraReciclaje').style.display='none'" style="background:none; border:none; color:#fff; font-size:1.8rem; cursor:pointer; padding:0; line-height:1;">&times;</button>
            </div>
        </div>

        <?php if ($total_papelera === 0): ?>
            <div style="text-align:center; padding:3rem 1rem; color:var(--text-muted);">
                <i class="fa-solid fa-trash-can-arrow-up" style="font-size:3rem; color:#10b981; margin-bottom:1rem; display:block;"></i>
                <h4 style="color:#fff; margin:0 0 0.4rem 0;">La Papelera de Reciclaje está vacía</h4>
                <p style="margin:0; font-size:0.88rem;">No hay archivos ni carpetas pendientes de eliminación en los últimos 30 días.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table" style="width:100%; border-collapse:collapse; font-size:0.88rem;">
                    <thead>
                        <tr style="background:rgba(239,68,68,0.1); border-bottom:1px solid rgba(239,68,68,0.3); color:#fca5a5; font-size:0.8rem; text-transform:uppercase;">
                            <th style="padding:0.75rem;">Tipo</th>
                            <th style="padding:0.75rem;">Nombre del Elemento</th>
                            <th style="padding:0.75rem;">Carpeta Origen</th>
                            <th style="padding:0.75rem;">Fecha Eliminación</th>
                            <th style="padding:0.75rem; text-align:center;">Tiempo Restante</th>
                            <th style="padding:0.75rem; text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 1. Carpetas en Papelera -->
                        <?php foreach ($papelera_carpetas as $pc): 
                            $f_el = new DateTime($pc['fecha_eliminacion'] ?? 'now');
                            $f_purge = (clone $f_el)->modify('+30 days');
                            $now_dt = new DateTime('now');
                            $diff = $now_dt->diff($f_purge);
                            $dias = $diff->invert ? 0 : $diff->days;
                        ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05); background:rgba(239,68,68,0.05);">
                            <td style="padding:0.75rem;"><i class="fa-solid fa-folder text-gold" style="font-size:1.2rem;"></i> Carpeta</td>
                            <td style="padding:0.75rem;"><strong style="color:#fff;"><?php echo htmlspecialchars($pc['nombre']); ?></strong></td>
                            <td style="padding:0.75rem; color:var(--text-muted);">&mdash; Directorio Principal &mdash;</td>
                            <td style="padding:0.75rem; color:#94a3b8;"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pc['fecha_eliminacion']); ?></td>
                            <td style="padding:0.75rem; text-align:center;">
                                <span class="status-pill warning" style="background:rgba(245,158,11,0.2); border-color:#f59e0b; color:#fbbf24; font-size:0.75rem; font-weight:800;">
                                    ⌛ <?php echo $dias; ?> día(s) restantes
                                </span>
                            </td>
                            <td style="padding:0.75rem; text-align:right;">
                                <div style="display:flex; gap:0.4rem; justify-content:flex-end;">
                                    <form action="nube-empresarial.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="tipo_item" value="carpeta">
                                        <input type="hidden" name="item_id" value="<?php echo $pc['id']; ?>">
                                        <button type="submit" name="restaurar_item_btn" class="btn-export" style="background:rgba(16,185,129,0.2); border-color:#10b981; color:#34d399; font-size:0.78rem; font-weight:700; padding:0.3rem 0.6rem;" title="Restaurar Carpeta">
                                            <i class="fa-solid fa-rotate-left"></i> Restaurar
                                        </button>
                                    </form>

                                    <form action="nube-empresarial.php" method="POST" onsubmit="return confirm('¿Eliminar definitivamente la carpeta <?php echo htmlspecialchars($pc['nombre']); ?>? Esta acción no se puede deshacer.');" style="margin:0;">
                                        <input type="hidden" name="tipo_item" value="carpeta">
                                        <input type="hidden" name="item_id" value="<?php echo $pc['id']; ?>">
                                        <button type="submit" name="purgar_definitivo_btn" class="btn-export" style="background:rgba(239,68,68,0.2); border-color:#ef4444; color:#fca5a5; font-size:0.78rem; font-weight:700; padding:0.3rem 0.6rem;" title="Eliminar Definitivamente">
                                            <i class="fa-solid fa-fire"></i> Purgar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- 2. Archivos en Papelera -->
                        <?php foreach ($papelera_archivos as $pa): 
                            $f_el = new DateTime($pa['fecha_eliminacion'] ?? 'now');
                            $f_purge = (clone $f_el)->modify('+30 days');
                            $now_dt = new DateTime('now');
                            $diff = $now_dt->diff($f_purge);
                            $dias = $diff->invert ? 0 : $diff->days;
                        ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <td style="padding:0.75rem;"><i class="fa-solid fa-file" style="color:#60a5fa; font-size:1.2rem;"></i> Archivo</td>
                            <td style="padding:0.75rem;"><strong style="color:#fff;"><?php echo htmlspecialchars($pa['nombre_original']); ?></strong></td>
                            <td style="padding:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($pa['carpeta']); ?></td>
                            <td style="padding:0.75rem; color:#94a3b8;"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pa['fecha_eliminacion']); ?></td>
                            <td style="padding:0.75rem; text-align:center;">
                                <span class="status-pill warning" style="background:rgba(245,158,11,0.2); border-color:#f59e0b; color:#fbbf24; font-size:0.75rem; font-weight:800;">
                                    ⌛ <?php echo $dias; ?> día(s) restantes
                                </span>
                            </td>
                            <td style="padding:0.75rem; text-align:right;">
                                <div style="display:flex; gap:0.4rem; justify-content:flex-end;">
                                    <form action="nube-empresarial.php" method="POST" style="margin:0;">
                                        <input type="hidden" name="tipo_item" value="archivo">
                                        <input type="hidden" name="item_id" value="<?php echo $pa['id']; ?>">
                                        <button type="submit" name="restaurar_item_btn" class="btn-export" style="background:rgba(16,185,129,0.2); border-color:#10b981; color:#34d399; font-size:0.78rem; font-weight:700; padding:0.3rem 0.6rem;" title="Restaurar Archivo">
                                            <i class="fa-solid fa-rotate-left"></i> Restaurar
                                        </button>
                                    </form>

                                    <form action="nube-empresarial.php" method="POST" onsubmit="return confirm('¿Eliminar definitivamente <?php echo htmlspecialchars($pa['nombre_original']); ?> del servidor?');" style="margin:0;">
                                        <input type="hidden" name="tipo_item" value="archivo">
                                        <input type="hidden" name="item_id" value="<?php echo $pa['id']; ?>">
                                        <button type="submit" name="purgar_definitivo_btn" class="btn-export" style="background:rgba(239,68,68,0.2); border-color:#ef4444; color:#fca5a5; font-size:0.78rem; font-weight:700; padding:0.3rem 0.6rem;" title="Eliminar Definitivamente">
                                            <i class="fa-solid fa-fire"></i> Purgar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
