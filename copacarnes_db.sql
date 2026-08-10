-- ============================================================================
-- BASE DE DATOS COMPLETA FASE 2: copacarnes_db
-- Sistema Empresarial ERP / CRM / POS / KDS / Delivery / Nube Empresarial
-- Compatible con MySQL / MariaDB / phpMyAdmin
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `copacarnes_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `copacarnes_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. TABLA: usuarios (Personal Autorizado y Estructura Organizacional Real)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `correo` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('dueno', 'admin', 'cajero', 'carnicero', 'cocinero', 'domiciliario') NOT NULL,
  `telefono` VARCHAR(25) DEFAULT NULL,
  `documento` VARCHAR(30) DEFAULT NULL,
  `direccion` TEXT DEFAULT NULL,
  `sede_asignada` VARCHAR(100) NOT NULL DEFAULT 'Sede Principal',
  `avatar` VARCHAR(255) DEFAULT 'images/avatar-default.png',
  `estado` ENUM('activo', 'inactivo', 'bloqueado') NOT NULL DEFAULT 'activo',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contraseñas con Hash Bcrypt para el equipo oficial de Copacarnes
INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password`, `rol`, `telefono`, `documento`, `sede_asignada`, `estado`) VALUES
-- DUEÑOS (2)
(1, 'Viviana (Propietaria)', 'viviana@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'dueno', '+57 316 9998877', '1017123456', 'Sede Principal', 'activo'),
(2, 'Jorge (Propietario)', 'jorge@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'dueno', '+57 316 9998888', '1017123457', 'Sede Principal', 'activo'),

-- ADMINISTRADOR (1)
(3, 'Stiven (Administrador General)', 'stiven@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'admin', '+57 316 3746875', '1018234567', 'Sede Principal', 'activo'),

-- CAJEROS (2)
(4, 'Natalia (Cajera)', 'natalia@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'cajero', '+57 302 2185285', '1019345678', 'Sede Principal', 'activo'),
(5, 'Ximena (Cajera)', 'ximena@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'cajero', '+57 302 2185286', '1019345679', 'Sede Principal', 'activo'),

-- CARNICEROS (7)
(6, 'Darlyson (Carnicero)', 'darlyson@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445561', '1020456781', 'Sede Secundaria', 'activo'),
(7, 'Camilo (Carnicero)', 'camilo@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445562', '1020456782', 'Sede Secundaria', 'activo'),
(8, 'Andrés (Carnicero)', 'andres@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445563', '1020456783', 'Sede Secundaria', 'activo'),
(9, 'Omaira (Carnicera)', 'omaira@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445564', '1020456784', 'Sede Secundaria', 'activo'),
(10, 'Mario (Carnicero)', 'mario@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445565', '1020456785', 'Sede Principal', 'activo'),
(11, 'Luis (Carnicero)', 'luis@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445566', '1020456786', 'Sede Principal', 'activo'),
(12, 'Jorge (Maestro Carnicero)', 'jorge.carnicero@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'carnicero', '+57 311 4445567', '1020456787', 'Sede Principal', 'activo'),

-- COCINEROS (2)
(13, 'Elsi (Chef Cocinera)', 'elsi@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'cocinero', '+57 315 7778891', '1021567891', 'Sede Principal', 'activo'),
(14, 'Alejandra (Cocinera)', 'alejandra@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'cocinero', '+57 315 7778892', '1021567892', 'Sede Principal', 'activo'),

-- DOMICILIARIO (1)
(15, 'Deivi (Domiciliario Oficial)', 'deivi@copacarnes.com', '$2y$10$4r6iH.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c', 'domiciliario', '+57 318 8889900', '1022678901', 'Sede Principal', 'activo');

-- ----------------------------------------------------------------------------
-- 3. TABLA: productos
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `categoria` ENUM('res', 'cerdo', 'aves', 'embutidos', 'asador', 'combos') NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `corte_tipo` VARCHAR(100) DEFAULT NULL,
  `maduracion` VARCHAR(50) DEFAULT NULL,
  `origen` VARCHAR(100) DEFAULT NULL,
  `precio` DECIMAL(10, 2) NOT NULL,
  `unidad` VARCHAR(30) NOT NULL DEFAULT 'kg',
  `stock` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `imagen` VARCHAR(255) NOT NULL,
  `etiqueta` VARCHAR(50) DEFAULT NULL,
  `destacado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` (`slug`, `nombre`, `categoria`, `descripcion`, `corte_tipo`, `maduracion`, `origen`, `precio`, `unidad`, `stock`, `imagen`, `etiqueta`, `destacado`) VALUES
('tomahawk', 'Tomahawk Premium 1kg', 'res', 'Corte con hueso largo expuesto, marmoleo excepcional clase Angus.', 'Ribeye con hueso extra largo', '28 Días Dry Aged', 'Ganado Angus Seleccionado', 95000.00, 'kg', 35.50, 'images/tomahawk.jpg', 'Top Ventas', 1),
('picanha', 'Picanha Angus 1.2kg', 'res', 'Capa de grasa uniforme ideal para espadas brasileñas o sellado a la parrilla.', 'Tapa de Cuadril / Punta de Anca', '21 Días Wet Aged', 'Ganado Bovino de Pastizal', 78000.00, '1.2 kg', 42.00, 'images/picanha.jpg', 'Premium Gold', 1),
('bife', 'Bife de Chorizo 800g', 'res', 'Corte clásico argentino con grosor perfecto y jugosidad inigualable.', 'Lomo Ancho sin Hueso', '21 Días', 'Ganado Vacuno Nacional', 62000.00, '800g', 50.00, 'images/burger.jpg', 'Popular', 1),
('lomo-fino', 'Lomo Fino Seleccionado', 'res', 'El corte más tierno y magro de la res, libre de grasa.', 'Solomito / Tenderloin', '14 Días', 'Ganado Vacuno Nacional', 85000.00, 'kg', 28.00, 'images/tomahawk.jpg', 'Gourmet', 0),
('costilla-cerdo', 'Costilla de Cerdo BBQ', 'cerdo', 'Costillar tierno con excelente proporción de carne y grasa.', 'Baby Back Ribs', 'Fresco', 'Granjas Porcinas Certificadas', 45000.00, 'kg', 60.00, 'images/sede.jpg', 'Favorito', 1),
('chicharron', 'Chicharrón de Cerdo Especial', 'cerdo', 'Corte de tocino carnudo con piel crujiente al freír o azar.', 'Tocino Carnudo', 'Fresco', 'Granjas Porcinas Certificadas', 38000.00, 'kg', 45.00, 'images/picanha.jpg', 'Tradicional', 0),
('lomo-cerdo', 'Lomo de Cerdo Enmarinado', 'cerdo', 'Magro, tierno y sazonado con finas hierbas naturales.', 'Cañón de Cerdo', 'Enmarinado 24h', 'Granjas Porcinas Certificadas', 36000.00, 'kg', 30.00, 'images/burger.jpg', 'Saludable', 0),
('pechuga', 'Pechuga de Pollo Deshuesada', 'aves', 'Pechuga fresca, sin piel ni hueso, lista para plancha o parrilla.', 'Pechuga Entera', 'Fresca', 'Avícola Certificada', 24000.00, 'kg', 80.00, 'images/tomahawk.jpg', 'Ligero', 0),
('alitas', 'Alitas de Pollo Marinadas 1kg', 'aves', 'Alitas de pollo frescas en marinada especial para asar o freír.', 'Alitas Enteras', 'Marinada BBQ', 'Avícola Certificada', 28000.00, 'kg', 55.00, 'images/burger.jpg', 'Para Compartir', 0),
('chorizo-artesanal', 'Chorizo Artesanal 500g', 'embutidos', 'Embutido de cerdo seleccionado con condimentos naturales sin preservantes.', 'Chorizo Santarrosano', 'Curado Artesanal', 'Receta de la Casa', 26000.00, '500g', 90.00, 'images/sede.jpg', 'Artesanal', 1),
('morcilla', 'Morcilla Tradicional 500g', 'embutidos', 'Receta antioqueña tradicional con arroz, poleo y especias.', 'Morcilla Paisa', 'Fresco', 'Receta de la Casa', 22000.00, '500g', 75.00, 'images/picanha.jpg', 'Tradición', 0),
('chimichurri', 'Chimichurri de la Casa 250ml', 'asador', 'Salsa artesanal a base de perejil fresco, ajo, aceite de oliva y especias.', 'Salsa Acompañante', 'Macerado 7 Días', 'Receta de la Casa', 18000.00, '250 ml', 120.00, 'images/tomahawk.jpg', 'Imprescindible', 0),
('carbon', 'Carbón Vegetal de Encino 5kg', 'asador', 'Carbón de alta densidad, encendido rápido y brasa duradera.', 'Carbón de Asador', 'Secado Natural', 'Bosques Sustentables', 25000.00, '5 kg', 150.00, 'images/sede.jpg', 'Accesorios', 0),
('sal-marina', 'Sal Marina Parrillera 500g', 'asador', 'Grano grueso especial para sellado de carnes a la parrilla.', 'Sal Parrilla', 'Cristalizada', 'Salinas Naturales', 12000.00, '500g', 200.00, 'images/burger.jpg', 'Esencial', 0),
('combo-asado', 'Combo Asado Familiar (4-6 Personas)', 'combos', 'Incluye: 1kg Tomahawk, 1.2kg Picanha, 500g Chorizo, Chimichurri y Carbón.', 'Combo Especial', 'Variada', 'Selección Copacarnes', 185000.00, 'Combo', 20.00, 'images/tomahawk.jpg', 'Imperdible', 1),
('combo-pareja', 'Combo Asado Pareja (2 Personas)', 'combos', 'Incluye: 800g Bife de Chorizo, 500g Chorizo Artesanal y Chimichurri.', 'Combo Pareja', 'Variada', 'Selección Copacarnes', 98000.00, 'Combo', 30.00, 'images/picanha.jpg', 'Recomendado', 1);

-- ----------------------------------------------------------------------------
-- 4. TABLA: lotes_carniceria (Control de Producción y Vencimientos)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `lotes_carniceria`;
CREATE TABLE `lotes_carniceria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `numero_lote` VARCHAR(50) NOT NULL UNIQUE,
  `cantidad_kg` DECIMAL(10,2) NOT NULL,
  `fecha_ingreso` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `proveedor` VARCHAR(120) NOT NULL,
  `estado` ENUM('fresco', 'maduracion', 'proximo_vencer', 'agotado') NOT NULL DEFAULT 'fresco',
  CONSTRAINT `fk_lotes_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lotes_carniceria` (`producto_id`, `numero_lote`, `cantidad_kg`, `fecha_ingreso`, `fecha_vencimiento`, `proveedor`, `estado`) VALUES
(1, 'LT-2026-0801', 50.00, '2026-08-01', '2026-08-25', 'Ganadería Angus del Norte', 'maduracion'),
(2, 'LT-2026-0802', 80.00, '2026-08-01', '2026-08-28', 'Bovinos San Fernando', 'fresco'),
(3, 'LT-2026-0728', 40.00, '2026-07-28', '2026-08-10', 'Frigorífico Central', 'proximo_vencer');

-- ----------------------------------------------------------------------------
-- 5. TABLA: cajas (Control de Caja Registradora para Natalia y Ximena)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cajas`;
CREATE TABLE `cajas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sede` VARCHAR(50) NOT NULL DEFAULT 'Sede Principal',
  `cajero_id` INT NOT NULL,
  `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` DATETIME DEFAULT NULL,
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 200000.00,
  `ventas_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjeta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_nequi` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_totales` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
  CONSTRAINT `fk_cajas_cajero` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cajas` (`sede`, `cajero_id`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_nequi`, `ventas_totales`, `estado`) VALUES
('Sede Principal', 4, 200000.00, 480000.00, 620000.00, 350000.00, 1450000.00, 'abierta'),
('Sede Secundaria', 5, 200000.00, 320000.00, 410000.00, 290000.00, 1020000.00, 'abierta');

-- ----------------------------------------------------------------------------
-- 6. TABLA: ventas (Facturación y Registro POS / Pedidos)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_factura` VARCHAR(40) NOT NULL UNIQUE,
  `cliente_id` INT DEFAULT NULL,
  `cliente_nombre` VARCHAR(120) NOT NULL,
  `cajero_id` INT DEFAULT NULL,
  `tipo_venta` ENUM('pos_caja', 'restaurante', 'domicilio') NOT NULL DEFAULT 'pos_caja',
  `subtotal` DECIMAL(10,2) NOT NULL,
  `impuestos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `metodo_pago` ENUM('Efectivo', 'Tarjeta', 'Nequi / Bancolombia', 'Transferencia') NOT NULL DEFAULT 'Efectivo',
  `estado` ENUM('completada', 'pendiente', 'cancelada') NOT NULL DEFAULT 'completada',
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ventas` (`numero_factura`, `cliente_id`, `cliente_nombre`, `cajero_id`, `tipo_venta`, `subtotal`, `impuestos`, `total`, `metodo_pago`, `estado`) VALUES
('FAC-2026-001', NULL, 'Cliente Mostrador Sede 1', 4, 'pos_caja', 185000.00, 0.00, 185000.00, 'Tarjeta', 'completada'),
('FAC-2026-002', NULL, 'Cliente Pedido Domicilio', 4, 'domicilio', 98000.00, 0.00, 98000.00, 'Nequi / Bancolombia', 'completada'),
('FAC-2026-003', NULL, 'Cliente Parrilla Mesa 4', 4, 'restaurante', 157000.00, 0.00, 157000.00, 'Efectivo', 'completada');

-- ----------------------------------------------------------------------------
-- 7. TABLA: comandas_cocina (Kitchen Display System para Elsi y Alejandra)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `comandas_cocina`;
CREATE TABLE `comandas_cocina` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT DEFAULT NULL,
  `mesa_numero` VARCHAR(20) NOT NULL DEFAULT 'Para Llevar',
  `platillo_nombre` VARCHAR(150) NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `notas` TEXT DEFAULT NULL,
  `prioridad` ENUM('normal', 'alta', 'urgente') NOT NULL DEFAULT 'normal',
  `estado` ENUM('pendiente', 'en_preparacion', 'listo', 'entregado') NOT NULL DEFAULT 'pendiente',
  `tiempo_estimado_min` INT NOT NULL DEFAULT 20,
  `cocinero_id` INT DEFAULT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comandas_cocinero` FOREIGN KEY (`cocinero_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `comandas_cocina` (`venta_id`, `mesa_numero`, `platillo_nombre`, `cantidad`, `notas`, `prioridad`, `estado`, `tiempo_estimado_min`, `cocinero_id`) VALUES
(3, 'Mesa 4', 'Tomahawk 1kg a la Parrilla Término Medio', 1, 'Papa al horno y chimichurri extra', 'urgente', 'en_preparacion', 25, 13),
(NULL, 'Mesa 2', 'Bife de Chorizo 800g Bien Asado', 2, 'Ensalada fresca sin cebolla', 'alta', 'pendiente', 20, 14),
(NULL, 'Mesa 6', 'Parrillada Mixta Copacarnes', 1, 'Servir con yuca frita', 'normal', 'listo', 15, 13);

-- ----------------------------------------------------------------------------
-- 8. TABLA: domicilios_envios (Panel de Entregas para Deivi)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `domicilios_envios`;
CREATE TABLE `domicilios_envios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL,
  `cliente_nombre` VARCHAR(120) NOT NULL,
  `cliente_telefono` VARCHAR(25) NOT NULL,
  `direccion_entrega` TEXT NOT NULL,
  `domiciliario_id` INT DEFAULT NULL,
  `estado` ENUM('pendiente', 'asignado', 'en_camino', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `tarifa_domicilio` DECIMAL(10,2) NOT NULL DEFAULT 8000.00,
  `notas_entrega` TEXT DEFAULT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_domicilios_domiciliario` FOREIGN KEY (`domiciliario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `domicilios_envios` (`venta_id`, `cliente_nombre`, `cliente_telefono`, `direccion_entrega`, `domiciliario_id`, `estado`, `tarifa_domicilio`, `notas_entrega`) VALUES
(2, 'Familia Gómez', '+57 318 5554433', 'Transversal 39A #72-10, Apto 402, Laureles', 15, 'en_camino', 8000.00, 'Tocar timbre 402.'),
(1, 'Carlos Jaramillo', '+57 301 9991122', 'Calle 10 #43E-12, El Poblado', 15, 'entregado', 10000.00, 'Entregado en portería.');

-- ----------------------------------------------------------------------------
-- 9. TABLA: nube_archivos (Nube Empresarial)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `nube_archivos`;
CREATE TABLE `nube_archivos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `carpeta` VARCHAR(100) NOT NULL DEFAULT 'General',
  `nombre_archivo` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `tipo_archivo` VARCHAR(50) NOT NULL,
  `tamano_kb` DECIMAL(10,2) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `usuario_id` INT NOT NULL,
  `usuario_nombre` VARCHAR(120) NOT NULL,
  `rol` VARCHAR(30) NOT NULL,
  `comentarios` TEXT DEFAULT NULL,
  `version` VARCHAR(20) NOT NULL DEFAULT 'v1.0',
  `descargas` INT NOT NULL DEFAULT 0,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_nube_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `nube_archivos` (`carpeta`, `nombre_archivo`, `nombre_original`, `tipo_archivo`, `tamano_kb`, `ruta_archivo`, `usuario_id`, `usuario_nombre`, `rol`, `comentarios`, `version`) VALUES
('Facturas y Contratos', 'contrato_proveedor_angus_2026.pdf', 'Contrato Ganadería Angus 2026.pdf', 'PDF', 1450.00, 'uploads/contrato_angus.pdf', 1, 'Viviana (Propietaria)', 'dueno', 'Contrato anual de suministro de carne Angus.', 'v1.0'),
('Balances Financieros', 'balance_general_julio_2026.xlsx', 'Balance Financiero Julio 2026.xlsx', 'Excel', 2300.00, 'uploads/balance_julio.xlsx', 2, 'Jorge (Propietario)', 'dueno', 'Reporte consolidado de utilidad e impuestos.', 'v1.2'),
('Manuales Internos', 'manual_procedimientos_carniceria.pdf', 'Manual Operaciones Carniceria.pdf', 'PDF', 980.00, 'uploads/manual_carniceria.pdf', 3, 'Stiven (Administrador General)', 'admin', 'Normas de aseo y protocolo de cortes.', 'v2.0');

-- ----------------------------------------------------------------------------
-- 10. TABLA: promociones
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `promociones`;
CREATE TABLE `promociones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `descuento_porcentaje` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `precio_anterior` DECIMAL(10, 2) NOT NULL,
  `precio_oferta` DECIMAL(10, 2) NOT NULL,
  `producto_slug` VARCHAR(50) DEFAULT NULL,
  `imagen` VARCHAR(255) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `estado` ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_promociones_producto` FOREIGN KEY (`producto_slug`) REFERENCES `productos` (`slug`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `promociones` (`titulo`, `descripcion`, `descuento_porcentaje`, `precio_anterior`, `precio_oferta`, `producto_slug`, `imagen`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES
('Viernes de Tomahawk Premium', '15% de descuento en corte Tomahawk Angus de 1kg.', 15.00, 95000.00, 80750.00, 'tomahawk', 'images/tomahawk.jpg', '2026-08-01', '2026-08-31', 'activa'),
('Combo Asado Parrillero Familiar', 'Super precio en combo familiar para 6 personas con chimichurri gratis.', 18.00, 185000.00, 151700.00, 'combo-asado', 'images/tomahawk.jpg', '2026-08-01', '2026-08-15', 'activa'),
('Especial de Embutidos Artesanales', 'Lleva 2 paquetes de Chorizo Artesanal con precio especial.', 20.00, 52000.00, 41600.00, 'chorizo-artesanal', 'images/sede.jpg', '2026-08-01', '2026-08-20', 'activa');

-- ----------------------------------------------------------------------------
-- 11. TABLA: solicitudes_contacto (Reservas y Pedidos enviados desde el Sitio Web)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `solicitudes_contacto`;
CREATE TABLE `solicitudes_contacto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `correo` VARCHAR(120) NOT NULL,
  `sede_tipo` VARCHAR(150) NOT NULL,
  `detalles` TEXT NOT NULL,
  `estado` ENUM('pendiente', 'atendido', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `solicitudes_contacto` (`nombre`, `telefono`, `correo`, `sede_tipo`, `detalles`) VALUES
('Carlos Mendoza', '+57 300 1234567', 'carlos@gmail.com', 'Sede Principal - Reserva Mesa Restaurante', 'Reserva para 4 personas este viernes a las 7:00 PM. Corte Tomahawk.');

-- ----------------------------------------------------------------------------
-- 12. TABLA: proveedores
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nit_cedula` VARCHAR(30) NOT NULL UNIQUE,
  `empresa_nombre` VARCHAR(150) NOT NULL,
  `contacto_persona` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `categoria_insumo` VARCHAR(80) NOT NULL,
  `direccion` TEXT DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `proveedores` (`id`, `nit_cedula`, `empresa_nombre`, `contacto_persona`, `telefono`, `email`, `categoria_insumo`, `estado`) VALUES
(1, '900.123.456-1', 'Ganadería Angus del Norte S.A.S', 'Fernando Gomez', '+57 310 4445566', 'contacto@angusnorte.com', 'Ganadería / Res', 'activo'),
(2, '890.987.654-3', 'Frigorífico San Juan S.A.', 'María López', '+57 315 2223344', 'ventas@frigosanjuan.com', 'Ganadería / Res', 'activo'),
(3, '800.555.777-2', 'Porcinos del Valle Ltda', 'Carlos Ruiz', '+57 318 6667788', 'porcinos@valle.com', 'Porcina', 'activo'),
(4, '901.333.222-5', 'Avícola Santa Rita', 'Andrés Tobón', '+57 301 9998877', 'pedidos@santarita.com', 'Avícola', 'activo'),
(5, '830.444.111-9', 'Embutidos Artesanales del Campo', 'Javier Medina', '+57 312 8889900', 'embutidos@delcampo.com', 'Embutidos', 'activo'),
(6, '900.777.888-4', 'Especias & Chimichurris Gourmet', 'Diana Morales', '+57 320 1112233', 'chimichurri@gourmet.com', 'Condimentos', 'activo'),
(7, '811.002.334-7', 'Carbonería & Maderas de Encino', 'Jorge Patiño', '+57 314 7776655', 'carbon@encino.com', 'Accesorios', 'activo'),
(8, '901.888.777-6', 'Empaques Térmicos & Vacío S.A.S', 'Claudia Vargas', '+57 317 4443322', 'empaques@vacio.com', 'Empaques', 'activo'),
(9, '890.112.334-0', 'Sales & Condimentos del Caribe', 'Roberto Silva', '+57 313 5556677', 'sales@caribe.com', 'Condimentos', 'activo'),
(10, '900.221.445-8', 'Distribuidora Arepas Don José', 'José Restrepo', '+57 300 7778899', 'arepas@donjose.com', 'Arepas', 'activo'),
(11, '860.999.888-1', 'Insumos Fríos & Refrigeración', 'Hernán Villa', '+57 316 2221100', 'frios@refrigeracion.com', 'Accesorios', 'activo'),
(12, '901.456.789-2', 'Boutique de Cuchillos & Asador', 'Santiago Arias', '+57 311 3334455', 'asador@boutique.com', 'Accesorios', 'activo');

SET FOREIGN_KEY_CHECKS = 1;
