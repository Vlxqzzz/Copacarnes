<?php
$msg_anonimo_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_sugerencia_anonima_btn'])) {
    $tipo_msg = trim($_POST['tipo_sugerencia'] ?? 'sugerencia');
    $mensaje_txt = trim($_POST['mensaje_anonimo'] ?? '');
    $modo_id = trim($_POST['modo_identidad'] ?? 'anonimo');
    $nombre_rem = ($modo_id === 'con_datos') ? trim($_POST['nombre_remitente'] ?? '') : null;
    $tel_rem = ($modo_id === 'con_datos') ? trim($_POST['telefono_remitente'] ?? '') : null;
    
    if (!empty($mensaje_txt)) {
        if (isset($pdo) && $pdo) {
            try {
                $stmt_anon = $pdo->prepare("INSERT INTO sugerencias_anonimas (tipo, nombre, telefono, mensaje) VALUES (?, ?, ?, ?)");
                $stmt_anon->execute([$tipo_msg, $nombre_rem, $tel_rem, $mensaje_txt]);
                $msg_anonimo_status = '<div style="background: rgba(16, 185, 129, 0.25); border: 1px solid #10b981; color: #34d399; padding: 0.65rem; border-radius: 8px; font-size: 0.82rem; margin-bottom: 0.8rem; text-align: center;"><i class="fa-solid fa-circle-check"></i> ¡Mensaje enviado exitosamente a la administración del propietario!</div>';
            } catch (Exception $e) {
                $msg_anonimo_status = '<div style="background: rgba(239, 68, 68, 0.25); border: 1px solid #ef4444; color: #fca5a5; padding: 0.65rem; border-radius: 8px; font-size: 0.82rem; margin-bottom: 0.8rem; text-align: center;">Error al enviar el mensaje.</div>';
            }
        }
    }
}
?>
    <!-- Footer Completo -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2.5rem; align-items: start;">
                <!-- Columna Izquierda: Marca y Redes -->
                <div class="footer-col" style="display: flex; flex-direction: column; align-items: flex-start; text-align: left;">
                    <a href="<?php echo $base_url; ?>index.php" class="logo" style="margin-bottom: 1.2rem; display: inline-flex;">
                        <img src="<?php echo $base_url; ?>images/logo.jpg?v=<?php echo time(); ?>" alt="Copacarnes Logo" class="logo-img">
                        <span class="logo-text">COPA<span class="text-gold">CARNES</span></span>
                    </a>
                    <p style="text-align: left; margin-bottom: 1.2rem; color: var(--color-text-muted); line-height: 1.6;">
                        Selección exclusiva de cortes de carne madurados de la más alta calidad, criados en pastizales seleccionados y preparados con pasión artesanal por nuestros maestros carniceros.
                    </p>
                    <div class="social-links" style="display: flex !important; gap: 1.2rem !important; align-items: center !important;">
                        <a href="#" class="social-icon" aria-label="Facebook" style="width: 44px !important; height: 44px !important; font-size: 1.2rem !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid #d4af37 !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; color: #d4af37 !important; text-decoration: none !important;">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="Instagram" style="width: 44px !important; height: 44px !important; font-size: 1.2rem !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid #d4af37 !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; color: #d4af37 !important; text-decoration: none !important;">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="WhatsApp" style="width: 44px !important; height: 44px !important; font-size: 1.2rem !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid #d4af37 !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; color: #d4af37 !important; text-decoration: none !important;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="TikTok" style="width: 44px !important; height: 44px !important; font-size: 1.2rem !important; background: rgba(255, 255, 255, 0.05) !important; border: 1px solid #d4af37 !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; color: #d4af37 !important; text-decoration: none !important;">
                            <i class="fa-brands fa-tiktok"></i>
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha: Buzón Directo al Dueño (Anónimo u Opcional con Datos) -->
                <div class="footer-col" style="background: linear-gradient(145deg, #141418, #0a0a0c); border: 1.5px solid rgba(212, 175, 55, 0.5); border-radius: 18px; padding: 1.6rem; box-shadow: 0 12px 30px rgba(0,0,0,0.7); backdrop-filter: blur(10px);">
                    <div style="margin-bottom: 1.2rem; border-bottom: 1px solid rgba(212,175,55,0.25); padding-bottom: 0.8rem;">
                        <h4 style="margin: 0; color: #ffffff; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 0.6rem; letter-spacing: 0.3px;">
                            <i class="fa-solid fa-comment-dots text-gold" style="font-size: 1.3rem;"></i> Buzón Directo al Dueño
                        </h4>
                        <p style="font-size: 0.8rem; color: #94a3b8; margin: 0.35rem 0 0 0; line-height: 1.4;">
                            Envía tus sugerencias, dudas o reclamos directamente a la Dirección. Elige si prefieres enviar de forma <strong>100% Anónima</strong> o incluir tus datos de contacto.
                        </p>
                    </div>

                    <?php if (!empty($msg_anonimo_status)) echo $msg_anonimo_status; ?>

                    <form action="" method="POST" style="display: flex; flex-direction: column; gap: 0.8rem;">
                        
                        <!-- Modalidad de Identidad -->
                        <div>
                            <label style="font-size: 0.78rem; color: #ffffff; font-weight: 700; display: block; margin-bottom: 0.3rem;">
                                Modalidad de Envío:
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                                <label id="lbl_opt_anonimo" style="background: rgba(212, 175, 55, 0.15); padding: 0.65rem 0.5rem; border-radius: 8px; border: 1px solid var(--color-gold); color: #ffffff; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                                    <input type="radio" name="modo_identidad" value="anonimo" checked onchange="toggleIdentidadBuzon(this.value)" style="accent-color: var(--color-gold);">
                                    🕵️ 100% Anónimo
                                </label>
                                <label id="lbl_opt_datos" style="background: rgba(255, 255, 255, 0.04); padding: 0.65rem 0.5rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.15); color: #94a3b8; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                                    <input type="radio" name="modo_identidad" value="con_datos" onchange="toggleIdentidadBuzon(this.value)" style="accent-color: var(--color-gold);">
                                    👤 Dejar Datos
                                </label>
                            </div>
                        </div>

                        <!-- Campos Opcionales de Contacto -->
                        <div id="box_datos_buzon" style="display: none; grid-template-columns: 1fr 1fr; gap: 0.6rem; background: rgba(212, 175, 55, 0.08); padding: 0.8rem; border-radius: 8px; border: 1px solid rgba(212, 175, 55, 0.25);">
                            <div>
                                <label style="font-size: 0.75rem; color: #ffffff; font-weight: 700; display: block; margin-bottom: 0.25rem;">Tu Nombre (Opcional):</label>
                                <input type="text" name="nombre_remitente" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 0.8rem; padding: 0.45rem 0.6rem; background: #07090e !important; color: #ffffff !important; border: 1px solid rgba(212,175,55,0.4) !important; border-radius: 6px;" placeholder="Ej: Carlos Mario">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: #ffffff; font-weight: 700; display: block; margin-bottom: 0.25rem;">Teléfono / WhatsApp:</label>
                                <input type="text" name="telefono_remitente" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 0.8rem; padding: 0.45rem 0.6rem; background: #07090e !important; color: #ffffff !important; border: 1px solid rgba(212,175,55,0.4) !important; border-radius: 6px;" placeholder="Ej: 3001234567">
                            </div>
                        </div>

                        <!-- Tipo de Comentario -->
                        <div>
                            <label style="font-size: 0.78rem; color: #ffffff; font-weight: 700; display: block; margin-bottom: 0.25rem;">Asunto / Tipo de Comentario:</label>
                            <select name="tipo_sugerencia" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 0.82rem; padding: 0.5rem 0.7rem; background: #07090e !important; color: #ffffff !important; border: 1px solid rgba(212, 175, 55, 0.4) !important; border-radius: 6px;">
                                <option value="sugerencia" style="background:#0c0d12; color:#fff;">💡 Sugerencia de Mejora</option>
                                <option value="reclamo" style="background:#0c0d12; color:#fff;">⚠️ Reclamo / Inconformidad</option>
                                <option value="duda" style="background:#0c0d12; color:#fff;">❓ Duda / Pregunta General</option>
                            </select>
                        </div>

                        <!-- Mensaje -->
                        <div>
                            <label style="font-size: 0.78rem; color: #ffffff; font-weight: 700; display: block; margin-bottom: 0.25rem;">Tu Mensaje al Dueño:</label>
                            <textarea name="mensaje_anonimo" required rows="3" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 0.85rem; padding: 0.6rem; background: #07090e !important; color: #ffffff !important; border: 1px solid rgba(212, 175, 55, 0.4) !important; border-radius: 6px; resize: vertical;" placeholder="Escribe aquí tu mensaje de forma sincera y clara..."></textarea>
                        </div>

                        <!-- Botón Enviar -->
                        <button type="submit" name="enviar_sugerencia_anonima_btn" class="btn btn-gold" style="width: 100%; justify-content: center; padding: 0.7rem; font-size: 0.88rem; font-weight: 800; gap: 0.5rem; background: linear-gradient(135deg, #d4af37, #997a15); border: none; color: #000; border-radius: 8px; box-shadow: 0 4px 15px rgba(212,175,55,0.3);">
                            <i class="fa-solid fa-paper-plane"></i> ENVIAR MENSAJE AL DUEÑO
                        </button>
                    </form>
                </div>
            </div>

            <!-- Derechos Reservados -->
            <div class="footer-bottom" style="margin-top: 2rem;">
                <p>&copy; <?php echo date('Y'); ?> <strong>Copacarnes</strong>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
    function toggleIdentidadBuzon(modo) {
        const boxDatos = document.getElementById('box_datos_buzon');
        const lblAnon = document.getElementById('lbl_opt_anonimo');
        const lblDatos = document.getElementById('lbl_opt_datos');

        if (modo === 'con_datos') {
            if (boxDatos) boxDatos.style.display = 'grid';
            if (lblDatos) {
                lblDatos.style.background = 'rgba(212, 175, 55, 0.15)';
                lblDatos.style.borderColor = 'var(--color-gold)';
                lblDatos.style.color = '#ffffff';
            }
            if (lblAnon) {
                lblAnon.style.background = 'rgba(255, 255, 255, 0.04)';
                lblAnon.style.borderColor = 'rgba(255, 255, 255, 0.15)';
                lblAnon.style.color = '#94a3b8';
            }
        } else {
            if (boxDatos) boxDatos.style.display = 'none';
            if (lblAnon) {
                lblAnon.style.background = 'rgba(212, 175, 55, 0.15)';
                lblAnon.style.borderColor = 'var(--color-gold)';
                lblAnon.style.color = '#ffffff';
            }
            if (lblDatos) {
                lblDatos.style.background = 'rgba(255, 255, 255, 0.04)';
                lblDatos.style.borderColor = 'rgba(255, 255, 255, 0.15)';
                lblDatos.style.color = '#94a3b8';
            }
        }
    }
    </script>

    <!-- Script para interacción responsiva -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const navMenu = document.getElementById('navMenu');

            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    const icon = menuToggle.querySelector('i');
                    if (navMenu.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-xmark');
                    } else {
                        icon.classList.remove('fa-xmark');
                        icon.classList.add('fa-bars');
                    }
                });
            }

            // Cerrar menú móvil al hacer clic en un enlace
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                        menuToggle.querySelector('i').classList.remove('fa-xmark');
                        menuToggle.querySelector('i').classList.add('fa-bars');
                    }
                });
            });

            // ScrollSpy dinámico: cambia el subrayado y color dorado según la ubicación del usuario
            const sections = document.querySelectorAll('section[id]');

            function updateActiveNavLink() {
                const scrollY = window.scrollY || window.pageYOffset;
                const headerOffset = 150;

                let currentSectionId = '';

                sections.forEach(section => {
                    const sectionTop = section.offsetTop - headerOffset;
                    const sectionHeight = section.offsetHeight;
                    if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
                        currentSectionId = section.getAttribute('id');
                    }
                });

                if (scrollY < 150) {
                    currentSectionId = 'inicio';
                }

                if (currentSectionId) {
                    navLinks.forEach(link => {
                        const href = link.getAttribute('href');
                        if (href && href.includes('#' + currentSectionId)) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    });
                }
            }

            window.addEventListener('scroll', updateActiveNavLink);
            updateActiveNavLink();
        });
    </script>
</body>
</html>
