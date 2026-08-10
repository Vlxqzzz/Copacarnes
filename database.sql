-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: copacarnes_db
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cajas`
--

DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sede` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sede Principal',
  `cajero_id` int NOT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT '200000.00',
  `ventas_efectivo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ventas_tarjeta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ventas_nequi` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ventas_totales` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('abierta','cerrada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  PRIMARY KEY (`id`),
  KEY `fk_cajas_cajero` (`cajero_id`),
  CONSTRAINT `fk_cajas_cajero` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajas`
--

LOCK TABLES `cajas` WRITE;
/*!40000 ALTER TABLE `cajas` DISABLE KEYS */;
INSERT INTO `cajas` VALUES (1,'Sede Principal',4,'2026-08-02 17:02:27',NULL,200000.00,480000.00,620000.00,350000.00,1450000.00,'abierta'),(2,'Sede Secundaria',5,'2026-08-02 17:02:27',NULL,200000.00,320000.00,410000.00,290000.00,1020000.00,'abierta');
/*!40000 ALTER TABLE `cajas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comandas_cocina`
--

DROP TABLE IF EXISTS `comandas_cocina`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comandas_cocina` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venta_id` int DEFAULT NULL,
  `mesa_numero` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Para Llevar',
  `platillo_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `prioridad` enum('normal','alta','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `estado` enum('pendiente','en_preparacion','en_proceso','listo','listo_domicilio','terminado','entregado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `tiempo_estimado_min` int NOT NULL DEFAULT '20',
  `cocinero_id` int DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_comandas_cocinero` (`cocinero_id`),
  CONSTRAINT `fk_comandas_cocinero` FOREIGN KEY (`cocinero_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comandas_cocina`
--

LOCK TABLES `comandas_cocina` WRITE;
/*!40000 ALTER TABLE `comandas_cocina` DISABLE KEYS */;
/*!40000 ALTER TABLE `comandas_cocina` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `domicilios_envios`
--

DROP TABLE IF EXISTS `domicilios_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `domicilios_envios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FAC-2026-002',
  `venta_id` int DEFAULT NULL,
  `cliente_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefono` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Contacto Web',
  `direccion_entrega` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Dirección Registrada',
  `domiciliario_id` int DEFAULT NULL,
  `domiciliario_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Deivi (Domiciliario Oficial)',
  `estado` enum('pendiente','asignado','en_camino','entregado','terminado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `tarifa_domicilio` decimal(10,2) NOT NULL DEFAULT '8000.00',
  `notas_entrega` text COLLATE utf8mb4_unicode_ci,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto_cobrar` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado_pago` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'por_cobrar',
  `metodo_pago` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `comprobante_pago` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_domicilios_domiciliario` (`domiciliario_id`),
  CONSTRAINT `fk_domicilios_domiciliario` FOREIGN KEY (`domiciliario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `domicilios_envios`
--

LOCK TABLES `domicilios_envios` WRITE;
/*!40000 ALTER TABLE `domicilios_envios` DISABLE KEYS */;
/*!40000 ALTER TABLE `domicilios_envios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lotes_carniceria`
--

DROP TABLE IF EXISTS `lotes_carniceria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotes_carniceria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `numero_lote` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad_kg` decimal(10,2) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `proveedor` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('fresco','maduracion','proximo_vencer','agotado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fresco',
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_lote` (`numero_lote`),
  KEY `fk_lotes_producto` (`producto_id`),
  CONSTRAINT `fk_lotes_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lotes_carniceria`
--

LOCK TABLES `lotes_carniceria` WRITE;
/*!40000 ALTER TABLE `lotes_carniceria` DISABLE KEYS */;
/*!40000 ALTER TABLE `lotes_carniceria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_del_dia`
--

DROP TABLE IF EXISTS `menu_del_dia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_del_dia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `icono` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-drumstick-bite',
  `horario_atencion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '11:30 AM - 3:00 PM',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_del_dia`
--

LOCK TABLES `menu_del_dia` WRITE;
/*!40000 ALTER TABLE `menu_del_dia` DISABLE KEYS */;
/*!40000 ALTER TABLE `menu_del_dia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nube_archivos`
--

DROP TABLE IF EXISTS `nube_archivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nube_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `carpeta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_archivo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano_kb` decimal(10,2) NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int NOT NULL,
  `usuario_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentarios` text COLLATE utf8mb4_unicode_ci,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'v1.0',
  `descargas` int NOT NULL DEFAULT '0',
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `en_papelera` tinyint(1) DEFAULT '0',
  `fecha_eliminacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_nube_usuario` (`usuario_id`),
  CONSTRAINT `fk_nube_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nube_archivos`
--

LOCK TABLES `nube_archivos` WRITE;
/*!40000 ALTER TABLE `nube_archivos` DISABLE KEYS */;
INSERT INTO `nube_archivos` VALUES (18,'Transferencias (2026-08-09)','transferencia_20260809_161355_176.png','Comprobante_Stiven_Velasquez_admin.png','PNG',156.58,'uploads/nube_empresarial/transferencias/2026-08-09/transferencia_20260809_161355_176.png',3,'Stiven (Administrador General)','cajero','Comprobante de pago por transferencia del cliente Stiven Velasquez','v1.0',0,'2026-08-09 11:13:55',1,'2026-08-09 16:14:09');
/*!40000 ALTER TABLE `nube_archivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nube_carpetas`
--

DROP TABLE IF EXISTS `nube_carpetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nube_carpetas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `icono` varchar(50) NOT NULL DEFAULT 'fa-folder-closed',
  `color` varchar(30) NOT NULL DEFAULT 'var(--gold)',
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `en_papelera` tinyint(1) DEFAULT '0',
  `fecha_eliminacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nube_carpetas`
--

LOCK TABLES `nube_carpetas` WRITE;
/*!40000 ALTER TABLE `nube_carpetas` DISABLE KEYS */;
INSERT INTO `nube_carpetas` VALUES (13,'Transferencias (2026-08-09)','fa-folder-closed','#10b981',NULL,'2026-08-09 11:13:55',1,'2026-08-09 16:14:09');
/*!40000 ALTER TABLE `nube_carpetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordenes_carniceria`
--

DROP TABLE IF EXISTS `ordenes_carniceria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_carniceria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_orden` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carnicero_id` int DEFAULT '0',
  `carnicero_nombre` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT 'Carnicero Disponible',
  `cliente_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cliente Mostrador',
  `corte_detalle` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kilos` decimal(10,2) NOT NULL DEFAULT '1.00',
  `estado` enum('pendiente','en_preparacion','en_corte','listo','listo_domicilio','terminado','finalizado','entregado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `fk_ordenes_carnicero` (`carnicero_id`),
  CONSTRAINT `fk_ordenes_carnicero` FOREIGN KEY (`carnicero_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordenes_carniceria`
--

LOCK TABLES `ordenes_carniceria` WRITE;
/*!40000 ALTER TABLE `ordenes_carniceria` DISABLE KEYS */;
/*!40000 ALTER TABLE `ordenes_carniceria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'res',
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `corte_tipo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maduracion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `unidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg',
  `stock` decimal(10,2) NOT NULL DEFAULT '50.00',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `etiqueta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destacado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `en_descuento` tinyint(1) NOT NULL DEFAULT '0',
  `descuento_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `precio_oferta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promociones`
--

DROP TABLE IF EXISTS `promociones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promociones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descuento_porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `precio_anterior` decimal(10,2) NOT NULL,
  `precio_oferta` decimal(10,2) NOT NULL,
  `producto_slug` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activa','inactiva') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_promociones_producto` (`producto_slug`),
  CONSTRAINT `fk_promociones_producto` FOREIGN KEY (`producto_slug`) REFERENCES `productos` (`slug`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promociones`
--

LOCK TABLES `promociones` WRITE;
/*!40000 ALTER TABLE `promociones` DISABLE KEYS */;
INSERT INTO `promociones` VALUES (1,'Viernes de Tomahawk Premium','15% de descuento en corte Tomahawk Angus de 1kg.',15.00,95000.00,80750.00,NULL,'images/tomahawk.jpg','2026-08-01','2026-08-31','activa','2026-08-02 22:02:27'),(2,'Combo Asado Parrillero Familiar','Super precio en combo familiar para 6 personas con chimichurri gratis.',18.00,185000.00,151700.00,NULL,'images/tomahawk.jpg','2026-08-01','2026-08-15','activa','2026-08-02 22:02:27'),(3,'Especial de Embutidos Artesanales','Lleva 2 paquetes de Chorizo Artesanal con precio especial.',20.00,52000.00,41600.00,NULL,'images/sede.jpg','2026-08-01','2026-08-20','activa','2026-08-02 22:02:27');
/*!40000 ALTER TABLE `promociones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nit_cedula` varchar(30) NOT NULL,
  `empresa_nombre` varchar(150) NOT NULL,
  `contacto_persona` varchar(120) NOT NULL,
  `telefono` varchar(30) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `categoria_insumo` varchar(80) NOT NULL,
  `direccion` text,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nit_cedula` (`nit_cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_contacto`
--

DROP TABLE IF EXISTS `solicitudes_contacto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_contacto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sede_tipo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalles` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','atendido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_contacto`
--

LOCK TABLES `solicitudes_contacto` WRITE;
/*!40000 ALTER TABLE `solicitudes_contacto` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_contacto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sugerencias_anonimas`
--

DROP TABLE IF EXISTS `sugerencias_anonimas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sugerencias_anonimas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sugerencia',
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nuevo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sugerencias_anonimas`
--

LOCK TABLES `sugerencias_anonimas` WRITE;
/*!40000 ALTER TABLE `sugerencias_anonimas` DISABLE KEYS */;
/*!40000 ALTER TABLE `sugerencias_anonimas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trabajadores`
--

DROP TABLE IF EXISTS `trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajadores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','trabajador') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trabajador',
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trabajadores`
--

LOCK TABLES `trabajadores` WRITE;
/*!40000 ALTER TABLE `trabajadores` DISABLE KEYS */;
INSERT INTO `trabajadores` VALUES (1,'Administrador Principal','admin@copacarnes.com','$2y$10$e8wF5PjPZ9S.qJtB4gRye.Yw0h8x8v5jQ7G8zF5cW1v7Z8a9b0cde','admin','+57 316 3746875','activo','2026-08-02 20:58:03'),(2,'Juan P├®rez (Maestro Carnicero)','juan.perez@copacarnes.com','$2y$10$e8wF5PjPZ9S.qJtB4gRye.Yw0h8x8v5jQ7G8zF5cW1v7Z8a9b0cde','trabajador','+57 302 2185285','activo','2026-08-02 20:58:03');
/*!40000 ALTER TABLE `trabajadores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('dueno','admin','cajero','carnicero','cocinero','domiciliario') COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'images/avatar-default.png',
  `estado` enum('activo','inactivo','bloqueado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sede_asignada` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sede Principal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Viviana (Propietaria)','viviana@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','dueno','+57 316 9998877','1017123456','Sede Principal - Copacabana','images/Viviana.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(2,'Jorge (Propietario)','jorge@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','dueno','+57 316 9998888','1017123457','Sede Principal - Copacabana','images/Jorge.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(3,'Stiven (Administrador General)','stiven@copacarnes.com','$2y$10$JhevjWkPICK3nYJ1BpVLxO3jOAd4g6vTRHCK3vGw2xUUEDl1sirAu','dueno','+57 316 3746875','1018234567','Calle 127 #15-32, Medell├¡n','uploads/avatars/avatar_3_1785799347.jpg','activo','2026-08-02 22:02:27','Sede Principal'),(4,'Natalia (Cajera)','natalia@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','cajero','+57 302 2185285','1019345678','Sede Principal','images/Natalia.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(5,'Ximena (Cajera)','ximena@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','cajero','+57 302 2185286','1019345679','Sede Secundaria','images/Ximena.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(6,'Darlyson (Carnicero)','darlyson@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','carnicero','+57 311 4445561','1020456781','Planta de Desposte','images/Darlison.jpeg','activo','2026-08-02 22:02:27','Sede Secundaria'),(9,'Omaira (Carnicera)','omaira@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','carnicero','+57 311 4445564','1020456784','Sede Secundaria','images/Omaira.jpeg','activo','2026-08-02 22:02:27','Sede Secundaria'),(10,'Mario (Carnicero)','mario@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','carnicero','+57 311 4445565','1020456785','Sede Principal','images/Mario.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(11,'Luis (Carnicero)','luis@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','carnicero','+57 311 4445566','1020456786','Planta de Desposte','images/Luis.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(12,'Jorge (Carnicero)','jorge.carnicero@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','carnicero','+57 311 4445567','1020456787','Sede Principal','images/Jorge.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(13,'Elsi (Cocinera)','elsi@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','cocinero','+57 315 7778891','1021567891','Cocina Restaurante Asadero','images/Elsi.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(14,'Alejandra (Cocinera)','alejandra@copacarnes.com','\\.WfR.b7V.Q.vVvXreB/O8hQ.q7H8aB.C1v2W3X4Y5Z6a7b8c','cocinero','+57 315 7778892','1021567892','Cocina Restaurante Asadero','images/Alejandra.jpeg','activo','2026-08-02 22:02:27','Sede Principal'),(19,'Andres (Carnicero)','andres@copacarnes.com','$2y$10$V4Eth8jLAS4L8NbtNIwkMuGWvdI2AZk5w5pFqtKSIMfg0pp337K96','carnicero',NULL,NULL,NULL,'uploads/avatars/avatar_19_1786211719.jpeg','activo','2026-08-08 17:55:04','Sede Secundaria');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `cliente_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cajero_id` int DEFAULT NULL,
  `tipo_venta` enum('pos_caja','restaurante','domicilio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pos_caja',
  `subtotal` decimal(10,2) NOT NULL,
  `impuestos` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Nequi / Bancolombia','Transferencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Efectivo',
  `estado` enum('completada','pendiente','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completada',
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'FAC-2026-001',NULL,'Cliente Mostrador Sede 1',4,'pos_caja',185000.00,0.00,185000.00,'Tarjeta','completada','2026-08-02 17:02:27'),(2,'FAC-2026-002',NULL,'Cliente Pedido Domicilio',4,'domicilio',98000.00,0.00,98000.00,'Nequi / Bancolombia','completada','2026-08-02 17:02:27'),(3,'FAC-2026-003',NULL,'Cliente Parrilla Mesa 4',4,'restaurante',157000.00,0.00,157000.00,'Efectivo','completada','2026-08-02 17:02:27'),(4,'FAC-2026-779',NULL,'Cliente Mostrador',1,'pos_caja',95000.00,0.00,95000.00,'Efectivo','completada','2026-08-02 17:30:01');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-09 19:10:31
