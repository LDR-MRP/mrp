-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: db_mrp
-- ------------------------------------------------------
-- Server version	8.0.45

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
-- Table structure for table `cat_anio_vin`
--

DROP TABLE IF EXISTS `cat_anio_vin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_anio_vin` (
  `id_cat_anio_vin` bigint NOT NULL AUTO_INCREMENT,
  `anio` year NOT NULL,
  `codigo` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_cat_anio_vin`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_bancos`
--

DROP TABLE IF EXISTS `cat_bancos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_bancos` (
  `id_banco` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_corto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estatus` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_banco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_codigos_postales`
--

DROP TABLE IF EXISTS `cat_codigos_postales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_codigos_postales` (
  `id_cp` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cp` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'Código Postal asentamiento (d_codigo)',
  `asentamiento` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Nombre asentamiento (d_asenta)',
  `tipo_asentamiento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Tipo de asentamiento (Catálogo SEPOMEX) (d_tipo_asenta)',
  `municipio` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Nombre Municipio (INEGI, Marzo 2013) (D_mnpio)',
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Nombre Entidad (INEGI, Marzo 2013) (c_estado)',
  `ciudad` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Nombre Ciudad (Catálogo SEPOMEX) (d_ciudad)',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cp`)
) ENGINE=InnoDB AUTO_INCREMENT=196606 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_colores`
--

DROP TABLE IF EXISTS `cat_colores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_colores` (
  `id` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_condiciones_pago`
--

DROP TABLE IF EXISTS `cat_condiciones_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_condiciones_pago` (
  `id_condicion` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dias_credito` int DEFAULT '0',
  `estatus` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_condicion`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_cuentas_contables`
--

DROP TABLE IF EXISTS `cat_cuentas_contables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_cuentas_contables` (
  `id_cuenta_contable` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_cuenta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_cuenta` enum('Acreedor','Deudor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` int DEFAULT '1',
  `estatus` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_cuenta_contable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_estados_mx`
--

DROP TABLE IF EXISTS `cat_estados_mx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_estados_mx` (
  `id_estado` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_pais` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MEX',
  PRIMARY KEY (`id_estado`),
  KEY `fk_est_pais` (`id_pais`),
  CONSTRAINT `fk_est_pais` FOREIGN KEY (`id_pais`) REFERENCES `cat_paises` (`id_pais`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_metodos_pago`
--

DROP TABLE IF EXISTS `cat_metodos_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_metodos_pago` (
  `idmetodopago` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_pago_inmediato` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned NOT NULL COMMENT 'Usuario que creó',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'Último usuario en modificarla',
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Usuario que la canceló/eliminó',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idmetodopago`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_modelos`
--

DROP TABLE IF EXISTS `cat_modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_modelos` (
  `id` int NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marca` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_modelos_vin`
--

DROP TABLE IF EXISTS `cat_modelos_vin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_modelos_vin` (
  `id_cat_modelo_vin` bigint NOT NULL AUTO_INCREMENT,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_fabricante` bigint DEFAULT NULL,
  `id_tipo_vehiculo` bigint DEFAULT NULL,
  `peso_bruto_kg` decimal(10,2) DEFAULT NULL,
  `id_tipo_motor` bigint DEFAULT NULL,
  `potencia_hp` int DEFAULT NULL,
  `distancia_ejes` decimal(10,2) DEFAULT NULL,
  `id_cat_anio_vin` bigint NOT NULL,
  `id_planta` bigint DEFAULT NULL,
  `id_segmento` int DEFAULT NULL,
  `vin_base` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id_cat_modelo_vin`),
  KEY `id_cat_anio_vin` (`id_cat_anio_vin`),
  KEY `fk_vin_fabricante` (`id_fabricante`),
  KEY `fk_vin_tipo_vehiculo` (`id_tipo_vehiculo`),
  KEY `fk_vin_tipo_motor` (`id_tipo_motor`),
  KEY `fk_vin_planta` (`id_planta`),
  CONSTRAINT `fk_vin_fabricante` FOREIGN KEY (`id_fabricante`) REFERENCES `cat_vin_fabricantes` (`id_fabricante`),
  CONSTRAINT `fk_vin_planta` FOREIGN KEY (`id_planta`) REFERENCES `cat_vin_plantas` (`id_planta`),
  CONSTRAINT `fk_vin_tipo_motor` FOREIGN KEY (`id_tipo_motor`) REFERENCES `cat_vin_tipo_motor` (`id_tipo_motor`),
  CONSTRAINT `fk_vin_tipo_vehiculo` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `cat_vin_tipo_vehiculo` (`id_tipo_vehiculo`),
  CONSTRAINT `id_cat_anio_vin` FOREIGN KEY (`id_cat_anio_vin`) REFERENCES `cat_anio_vin` (`id_cat_anio_vin`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_paises`
--

DROP TABLE IF EXISTS `cat_paises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_paises` (
  `id_pais` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estatus` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id_pais`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_vin_fabricantes`
--

DROP TABLE IF EXISTS `cat_vin_fabricantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_vin_fabricantes` (
  `id_fabricante` bigint NOT NULL AUTO_INCREMENT,
  `wmi` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fabricante` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`id_fabricante`),
  UNIQUE KEY `wmi` (`wmi`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_vin_plantas`
--

DROP TABLE IF EXISTS `cat_vin_plantas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_vin_plantas` (
  `id_planta` bigint NOT NULL AUTO_INCREMENT,
  `planta` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caracter` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`id_planta`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_vin_tipo_motor`
--

DROP TABLE IF EXISTS `cat_vin_tipo_motor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_vin_tipo_motor` (
  `id_tipo_motor` bigint NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caracter` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`id_tipo_motor`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_vin_tipo_vehiculo`
--

DROP TABLE IF EXISTS `cat_vin_tipo_vehiculo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cat_vin_tipo_vehiculo` (
  `id_tipo_vehiculo` bigint NOT NULL AUTO_INCREMENT,
  `categoria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `caracter` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`id_tipo_vehiculo`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes`
--

DROP TABLE IF EXISTS `cli_clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes` (
  `idcliente` bigint NOT NULL AUTO_INCREMENT,
  `idtipo_cliente` bigint NOT NULL,
  `idregimen_fiscal` bigint NOT NULL,
  `tipo_persona` enum('FISICA','MORAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_cliente` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `razon_social` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_comercial` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sitio_web` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_alta` date NOT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `celular` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2' COMMENT '0=Eliminado 1=Inactivo 2=Activo',
  `clave_distribuidor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `zona_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `territorio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `responsable_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `requiere_acceso_portal` tinyint(1) NOT NULL DEFAULT '0',
  `correo_acceso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_empleado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `departamento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `centro_costos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jefe_inmediato` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo_corporativo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `origen_cliente` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ejecutivo_asignado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `segmento_mercado` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dependencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_administrativa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nivel_gobierno` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `partida_presupuestal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_contratacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuarioid` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcliente`),
  UNIQUE KEY `codigo_cliente` (`codigo_cliente`),
  KEY `idtipo_cliente` (`idtipo_cliente`),
  KEY `idtipo_persona` (`idregimen_fiscal`),
  KEY `idregimen_fiscal` (`idregimen_fiscal`),
  KEY `idtipo_cliente_2` (`idtipo_cliente`),
  KEY `idregimen_fiscal_2` (`idregimen_fiscal`),
  CONSTRAINT `cli_clientes_ibfk_1` FOREIGN KEY (`idregimen_fiscal`) REFERENCES `cli_regimenes_fiscales` (`id`),
  CONSTRAINT `cli_clientes_ibfk_2` FOREIGN KEY (`idtipo_cliente`) REFERENCES `cli_tipos_cliente` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100000006 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_bancos`
--

DROP TABLE IF EXISTS `cli_clientes_bancos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_bancos` (
  `idbanco` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `banco` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `titular_cuenta` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_cuenta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clabe` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moneda_cuenta` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'MXN',
  `referencia_bancaria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idbanco`),
  KEY `idx_cli_bancos_idcliente` (`idcliente`),
  CONSTRAINT `fk_cli_banco_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_comercial`
--

DROP TABLE IF EXISTS `cli_clientes_comercial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_comercial` (
  `idcomercial` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `lista_precio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moneda` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MXN',
  `forma_pago` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `limite_credito` decimal(15,2) NOT NULL DEFAULT '0.00',
  `dias_credito` int NOT NULL DEFAULT '0',
  `descuento_autorizado` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ejecutivo_cuenta` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `canal_venta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clasificacion_comercial` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observaciones_comerciales` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estado` tinyint NOT NULL DEFAULT '2',
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcomercial`),
  UNIQUE KEY `uk_cli_comercial_cliente` (`idcliente`),
  CONSTRAINT `fk_cli_comercial_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_contactos`
--

DROP TABLE IF EXISTS `cli_clientes_contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_contactos` (
  `idcontacto` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notificar` tinyint(1) NOT NULL DEFAULT '0',
  `usuarioid` bigint NOT NULL,
  `puesto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcontacto`),
  KEY `idcliente` (`idcliente`),
  CONSTRAINT `cli_clientes_contactos_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_documentos`
--

DROP TABLE IF EXISTS `cli_clientes_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_documentos` (
  `iddocumento` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `tipo_documento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ruta_archivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tamano_bytes` bigint NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddocumento`),
  KEY `idx_cli_documentos_cliente` (`idcliente`),
  CONSTRAINT `fk_cli_documentos_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_fiscal`
--

DROP TABLE IF EXISTS `cli_clientes_fiscal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_fiscal` (
  `idfiscal` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `rfc` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `curp` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `regimen_fiscal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `uso_cfdi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_postal_fiscal` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo_facturacion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requiere_factura` tinyint(1) NOT NULL DEFAULT '1',
  `estado` tinyint NOT NULL DEFAULT '2',
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idfiscal`),
  UNIQUE KEY `uk_cli_fiscal_cliente` (`idcliente`),
  UNIQUE KEY `uk_cli_fiscal_rfc` (`rfc`),
  CONSTRAINT `fk_cli_fiscal_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_clientes_sucursales`
--

DROP TABLE IF EXISTS `cli_clientes_sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_clientes_sucursales` (
  `idsucursal` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `nombre_sucursal` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `correo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `responsable` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_exterior` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_interior` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `colonia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_postal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `municipio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado_republica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pais` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `usuarioid` bigint NOT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idsucursal`),
  KEY `idcliente` (`idcliente`),
  CONSTRAINT `cli_clientes_sucursales_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_departamentos`
--

DROP TABLE IF EXISTS `cli_departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_departamentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminado, 1=Inactivo, 2=Activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_direcciones`
--

DROP TABLE IF EXISTS `cli_direcciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_direcciones` (
  `iddireccion` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `tipo_direccion` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `calle` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_exterior` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_interior` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `colonia` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_postal` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `municipio` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado_republica` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pais` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estado` tinyint NOT NULL DEFAULT '2',
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddireccion`),
  KEY `idx_cli_direcciones_cliente` (`idcliente`),
  CONSTRAINT `fk_cli_direcciones_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_estados`
--

DROP TABLE IF EXISTS `cli_estados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_estados` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `pais_id` bigint NOT NULL,
  `region_id` bigint NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `abreviatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `pais_id` (`pais_id`),
  KEY `region_id` (`region_id`),
  KEY `pais_id_2` (`pais_id`),
  KEY `region_id_2` (`region_id`),
  KEY `pais_id_3` (`pais_id`),
  KEY `region_id_3` (`region_id`),
  CONSTRAINT `cli_estados_ibfk_1` FOREIGN KEY (`region_id`) REFERENCES `cli_regiones` (`id`),
  CONSTRAINT `cli_estados_ibfk_2` FOREIGN KEY (`pais_id`) REFERENCES `cli_paises` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_municipios`
--

DROP TABLE IF EXISTS `cli_municipios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_municipios` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `estado_id` bigint NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `estado_id` (`estado_id`),
  CONSTRAINT `cli_municipios_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `cli_estados` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2458 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_paises`
--

DROP TABLE IF EXISTS `cli_paises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_paises` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_iso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_regimenes_fiscales`
--

DROP TABLE IF EXISTS `cli_regimenes_fiscales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_regimenes_fiscales` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `c_regimen_fiscal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `persona_fisica` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `persona_moral` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_regiones`
--

DROP TABLE IF EXISTS `cli_regiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_regiones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` bigint NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_sucursales_direcciones`
--

DROP TABLE IF EXISTS `cli_sucursales_direcciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_sucursales_direcciones` (
  `iddireccion` bigint NOT NULL AUTO_INCREMENT,
  `idsucursal` bigint NOT NULL,
  `pais_id` bigint NOT NULL,
  `estado_id` bigint NOT NULL,
  `municipio_id` bigint NOT NULL,
  `calle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `numero_exterior` int NOT NULL,
  `numero_interior` int NOT NULL,
  `codigo_postal` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '1',
  `estado` tinyint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_actualizacion` datetime NOT NULL,
  PRIMARY KEY (`iddireccion`),
  KEY `idsucursal` (`idsucursal`),
  KEY `pais_id` (`pais_id`),
  KEY `estado_id` (`estado_id`),
  KEY `municipio_id` (`municipio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_tipos_cliente`
--

DROP TABLE IF EXISTS `cli_tipos_cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_tipos_cliente` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_usuarios_acceso`
--

DROP TABLE IF EXISTS `cli_usuarios_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_usuarios_acceso` (
  `idusuario_acceso` bigint NOT NULL AUTO_INCREMENT,
  `idcliente` bigint NOT NULL,
  `nombre_usuario` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `apellido` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `url_portal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ultimo_login` datetime NOT NULL,
  `ultimo_envio_accesos` datetime DEFAULT NULL,
  `token_recuperacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `token_recuperacion_expira` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `doble_autenticacion` tinyint(1) NOT NULL DEFAULT '0',
  `requiere_cambio_password` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_cambio_password` datetime DEFAULT NULL,
  `intentos_fallidos` int NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  `created_by` bigint DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_actualizacion` datetime NOT NULL,
  PRIMARY KEY (`idusuario_acceso`),
  UNIQUE KEY `uk_cli_usuario_acceso_correo` (`correo`),
  UNIQUE KEY `uk_cli_usuario_acceso_usuario` (`nombre_usuario`),
  KEY `idx_cli_usuario_cliente` (`idcliente`),
  KEY `idx_cli_usuario_estado` (`estado`),
  KEY `idcliente` (`idcliente`),
  KEY `idcliente_2` (`idcliente`),
  CONSTRAINT `cli_usuarios_acceso_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_usuarios_acceso_envios`
--

DROP TABLE IF EXISTS `cli_usuarios_acceso_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_usuarios_acceso_envios` (
  `idenvio` bigint NOT NULL AUTO_INCREMENT,
  `idusuario_acceso` bigint NOT NULL,
  `idcliente` bigint NOT NULL,
  `correo_destino` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_envio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CREDENCIALES, REENVIO_CREDENCIALES, PIN_2FA, RECUPERACION_PASSWORD',
  `asunto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resultado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ENVIADO, FALLIDO',
  `detalle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `enviado_por` bigint DEFAULT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idenvio`),
  KEY `idx_envio_usuario` (`idusuario_acceso`),
  KEY `idx_envio_cliente` (`idcliente`),
  KEY `idx_envio_fecha` (`fecha_envio`),
  KEY `idusuario_acceso` (`idusuario_acceso`),
  KEY `idcliente` (`idcliente`),
  CONSTRAINT `cli_usuarios_acceso_envios_ibfk_1` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`),
  CONSTRAINT `cli_usuarios_acceso_envios_ibfk_2` FOREIGN KEY (`idusuario_acceso`) REFERENCES `cli_usuarios_acceso` (`idusuario_acceso`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_usuarios_acceso_logs`
--

DROP TABLE IF EXISTS `cli_usuarios_acceso_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_usuarios_acceso_logs` (
  `idlog` bigint NOT NULL AUTO_INCREMENT,
  `idusuario_acceso` bigint DEFAULT NULL,
  `idcliente` bigint DEFAULT NULL,
  `tipo_evento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LOGIN_EXITOSO, LOGIN_FALLIDO, LOGOUT, BLOQUEADO, PIN_ENVIADO, PIN_VALIDADO, PIN_FALLIDO, PASSWORD_CAMBIADA',
  `resultado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'EXITOSO, FALLIDO, BLOQUEADO, INFORMATIVO',
  `correo_intento` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispositivo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_dispositivo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `navegador` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version_navegador` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sistema_operativo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion_aproximada` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_sesion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `motivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_evento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idlog`),
  KEY `idx_log_usuario` (`idusuario_acceso`),
  KEY `idx_log_cliente` (`idcliente`),
  KEY `idx_log_fecha` (`fecha_evento`),
  KEY `idx_log_resultado` (`resultado`),
  KEY `idx_log_tipo_evento` (`tipo_evento`),
  KEY `idusuario_acceso` (`idusuario_acceso`),
  KEY `idcliente` (`idcliente`),
  CONSTRAINT `cli_usuarios_acceso_logs_ibfk_1` FOREIGN KEY (`idusuario_acceso`) REFERENCES `cli_usuarios_acceso` (`idusuario_acceso`),
  CONSTRAINT `cli_usuarios_acceso_logs_ibfk_2` FOREIGN KEY (`idcliente`) REFERENCES `cli_clientes` (`idcliente`)
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cli_usuarios_acceso_pines`
--

DROP TABLE IF EXISTS `cli_usuarios_acceso_pines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cli_usuarios_acceso_pines` (
  `idpin` bigint NOT NULL AUTO_INCREMENT,
  `idusuario_acceso` bigint NOT NULL,
  `codigo_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `challenge` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime NOT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `intentos` int NOT NULL DEFAULT '0',
  `max_intentos` int NOT NULL DEFAULT '5',
  `utilizado` tinyint(1) NOT NULL DEFAULT '0',
  `direccion_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_sesion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idpin`),
  KEY `idx_pin_usuario` (`idusuario_acceso`),
  KEY `idx_pin_expiracion` (`fecha_expiracion`),
  KEY `idx_pin_utilizado` (`utilizado`),
  KEY `idusuario_acceso` (`idusuario_acceso`),
  CONSTRAINT `cli_usuarios_acceso_pines_ibfk_1` FOREIGN KEY (`idusuario_acceso`) REFERENCES `cli_usuarios_acceso` (`idusuario_acceso`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_ordenes_compra`
--

DROP TABLE IF EXISTS `com_ordenes_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_ordenes_compra` (
  `idcompra` bigint unsigned NOT NULL AUTO_INCREMENT,
  `requisicionid` int NOT NULL COMMENT 'ID de la requisición que originó esta compra',
  `proveedorid` bigint unsigned NOT NULL COMMENT 'Proveedor seleccionado para esta OC',
  `plantaid` bigint DEFAULT NULL COMMENT 'Para qué planta',
  `almacenid` bigint unsigned NOT NULL COMMENT 'Almacén donde se recibirá la mercancía',
  `estatus` enum('emitida','en_transito','recibida','recibida_parcial','cerrada','cancelada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'emitida' COMMENT 'Estado actual de la OC',
  `moneda` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MXN' COMMENT 'Código ISO de la moneda',
  `tipo_cambio` decimal(15,6) DEFAULT '1.000000' COMMENT 'Tipo de cambio al momento de emitir',
  `subtotal` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `iva` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `total` decimal(18,6) NOT NULL DEFAULT '0.000000',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Condiciones comerciales o notas para el proveedor',
  `created_by` bigint unsigned NOT NULL COMMENT 'Usuario que generó la OC',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'Último usuario en modificarla',
  `deleted_by` bigint unsigned DEFAULT NULL COMMENT 'Usuario que la canceló/eliminó',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idcompra`),
  KEY `idx_oc_requisicion` (`requisicionid`),
  KEY `idx_oc_proveedor` (`proveedorid`),
  KEY `idx_oc_estatus` (`estatus`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_ordenes_compra_detalle`
--

DROP TABLE IF EXISTS `com_ordenes_compra_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_ordenes_compra_detalle` (
  `iddetalle` bigint unsigned NOT NULL AUTO_INCREMENT,
  `compraid` bigint unsigned NOT NULL,
  `idrequisicionarticulo` int NOT NULL COMMENT 'Enlace a la partida exacta de la requisición origen',
  `inventarioid` bigint NOT NULL COMMENT 'El producto/servicio real',
  `tipo_elemento` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'P' COMMENT 'P=Producto, S=Servicio, H=Herramienta, K=Kit',
  `cantidad` decimal(12,4) NOT NULL COMMENT 'Cantidad real a comprar a este proveedor',
  `costo_unitario` decimal(18,6) NOT NULL COMMENT 'Precio real negociado con el proveedor',
  `porcentaje_descuento` decimal(5,2) DEFAULT '0.00',
  `descuento_partida` decimal(18,6) DEFAULT '0.000000' COMMENT 'Monto de descuento aplicado a esta línea',
  `impuesto_partida` decimal(18,6) DEFAULT '0.000000' COMMENT 'Monto de IVA u otros impuestos de esta línea',
  `subtotal_partida` decimal(18,6) NOT NULL COMMENT '(cantidad * costo_unitario) - descuento',
  `created_by` bigint unsigned NOT NULL COMMENT 'Usuario que generó la orden de compra',
  `updated_by` bigint DEFAULT NULL COMMENT 'Último usuario en modificarla',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `idx_ocd_compra` (`compraid`),
  KEY `idx_ocd_req_articulo` (`idrequisicionarticulo`),
  KEY `fk_ocd_inventario` (`inventarioid`),
  CONSTRAINT `fk_ocd_compra` FOREIGN KEY (`compraid`) REFERENCES `com_ordenes_compra` (`idcompra`) ON DELETE CASCADE,
  CONSTRAINT `fk_ocd_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_requisicion_cotizaciones`
--

DROP TABLE IF EXISTS `com_requisicion_cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_requisicion_cotizaciones` (
  `idcotizacion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idrequisicionarticulo` int NOT NULL,
  `src_evento_sourcing_id` bigint unsigned DEFAULT NULL,
  `id_proveedor` bigint unsigned DEFAULT NULL,
  `tipo_fuente` enum('REGISTRADO','PROSPECTO','RETAIL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGISTRADO',
  `nombre_prospecto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentarios_comprador` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `specs_particulares_proveedor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `moneda` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MXN',
  `tipo_cambio` decimal(15,6) DEFAULT '1.000000',
  `precio_unitario` decimal(18,6) NOT NULL,
  `iva_inc` tinyint(1) NOT NULL DEFAULT '0',
  `precio_base_mxn` decimal(18,6) NOT NULL DEFAULT '0.000000' COMMENT 'Subtotal normalizado en MXN',
  `es_ganadora` tinyint(1) DEFAULT '0',
  `pago_inmediato` tinyint(1) DEFAULT '0',
  `estatus_cotizacion` enum('BORRADOR','ENVIADA','GANADORA','DESCARTADA') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'BORRADOR',
  `url_pdf_cotizacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url_foto_producto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url_referencia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `adjudicado_por` decimal(18,6) unsigned DEFAULT '0.000000' COMMENT 'Subtotal normalizado en MXN',
  `id_orden_compra_final` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idcotizacion`),
  KEY `fk_cotiz_prv` (`id_proveedor`),
  KEY `fk_cotiz_req_item` (`idrequisicionarticulo`),
  KEY `fk_cotizacion_src_evento` (`src_evento_sourcing_id`),
  KEY `idx_cotizacion_oc` (`id_orden_compra_final`),
  CONSTRAINT `fk_cotiz_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`),
  CONSTRAINT `fk_cotiz_req_item` FOREIGN KEY (`idrequisicionarticulo`) REFERENCES `com_requisiciones_detalle` (`idrequisicionarticulo`) ON DELETE CASCADE,
  CONSTRAINT `fk_cotizacion_src_evento` FOREIGN KEY (`src_evento_sourcing_id`) REFERENCES `src_eventos_sourcing` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_requisicion_items_nuevos`
--

DROP TABLE IF EXISTS `com_requisicion_items_nuevos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_requisicion_items_nuevos` (
  `iditemnuevo` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idrequisicionarticulo` int NOT NULL COMMENT 'FK a la partida de la requisicion',
  `justificacion_proyecto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion_sourcing` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `especificaciones_tecnicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `dimensiones_principales` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `normas_requeridas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `volumen_anual` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio_objetivo` decimal(18,6) DEFAULT '0.000000',
  `fecha_inicio_negociacion` date DEFAULT NULL,
  `fecha_limite_acuerdo` date DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL COMMENT 'Último usuario en modificarla',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'Último usuario en modificarla',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`iditemnuevo`),
  UNIQUE KEY `idrequisicionarticulo` (`idrequisicionarticulo`),
  CONSTRAINT `fk_item_nuevo_req` FOREIGN KEY (`idrequisicionarticulo`) REFERENCES `com_requisiciones_detalle` (`idrequisicionarticulo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_requisiciones`
--

DROP TABLE IF EXISTS `com_requisiciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_requisiciones` (
  `idrequisicion` int NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_empresa` int DEFAULT NULL,
  `usuarioid` bigint NOT NULL COMMENT 'Quién solicita',
  `plantaid` bigint NOT NULL,
  `departamentoid` bigint unsigned DEFAULT NULL COMMENT 'Para qué área',
  `centro_costo` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'De qué se trata',
  `tipo_requisicion` enum('standard','directa') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'standard',
  `idmetodopago` int DEFAULT NULL,
  `fecha_requerida` date DEFAULT NULL COMMENT 'Para cuándo',
  `prioridad` enum('baja','media','alta','critica') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `estatus` enum('borrador','pendiente','aprobada','rechazada','en compra','cancelada','finalizada','eliminada') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `monto_estimado` decimal(18,6) DEFAULT '0.000000' COMMENT 'Monto estimado de referencia para presupuesto',
  `justificacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Justificación de la compra',
  `url_referencia` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Link de Amazon/ML',
  `modified_by` bigint DEFAULT NULL COMMENT 'Quién autorizó (referencia a users)',
  `modified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idrequisicion`),
  UNIQUE KEY `idx_requisicion_folio` (`folio`),
  KEY `idx_requisicion_planta` (`plantaid`),
  KEY `fk_req_pago` (`idmetodopago`),
  CONSTRAINT `fk_req_pago` FOREIGN KEY (`idmetodopago`) REFERENCES `cat_metodos_pago` (`idmetodopago`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `com_requisiciones_detalle`
--

DROP TABLE IF EXISTS `com_requisiciones_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_requisiciones_detalle` (
  `idrequisicionarticulo` int NOT NULL AUTO_INCREMENT,
  `requisicionid` int NOT NULL,
  `src_evento_sourcing_id` bigint unsigned DEFAULT NULL,
  `inventarioid` bigint unsigned DEFAULT NULL,
  `id_proveedor` bigint unsigned DEFAULT NULL,
  `cantidad` decimal(12,4) NOT NULL COMMENT 'Usamos decimal por si hay medidas como litros o kilos',
  `precio_unitario_estimado` decimal(18,6) DEFAULT '0.000000' COMMENT 'Precio de referencia para presupuesto',
  `notas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Notas específicas del item (ej: ''Color negro'')',
  `created_by` bigint unsigned NOT NULL COMMENT 'Usuario que generó la partida',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'Último usuario en modificarla',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idrequisicionarticulo`),
  KEY `fk_requisicion_detalle_src_evento` (`src_evento_sourcing_id`),
  KEY `idx_req_detalle_proveedor` (`id_proveedor`),
  CONSTRAINT `fk_req_detalle_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`),
  CONSTRAINT `fk_requisicion_detalle_src_evento` FOREIGN KEY (`src_evento_sourcing_id`) REFERENCES `src_eventos_sourcing` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cxp_tra_facturas`
--

DROP TABLE IF EXISTS `cxp_tra_facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cxp_tra_facturas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL COMMENT 'FK a tu tabla maestra de proveedores (wms_proveedores)',
  `id_compra` bigint unsigned NOT NULL COMMENT 'FK a tu tabla de ordenes de compra (com_ordenes_compra)',
  `serie_folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Serie y Folio de la factura (Ej: FAC_A-104)',
  `uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID de timbre fiscal SAT (36 caracteres)',
  `monto_total` decimal(12,4) NOT NULL COMMENT 'Monto total neto validado contra el XML',
  `fecha_vencimiento` date DEFAULT NULL,
  `url_xml` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path relativo en el storage del XML',
  `url_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path relativo en el storage del PDF',
  `estatus_validacion` tinyint DEFAULT '0' COMMENT '0: Pendiente, 1: Validada (CxP), 2: Rechazada',
  `estatus_pago` enum('PENDIENTE','PROGRAMADO','PAGADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDIENTE',
  `motivo_rechazo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Nota explicativa si falla la auditoría manual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soporte SoftDeletes Laravel',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'ID de prv_cat_usuarios (proveedor) que la subió',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'ID del empleado interno que aprobó/rechazó',
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `idx_cxp_fac_uuid` (`uuid`),
  KEY `idx_cxp_fac_compra` (`id_compra`),
  KEY `idx_cxp_fac_estatus` (`estatus_validacion`),
  KEY `idx_cxp_fac_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cxp_fac_pago` (`estatus_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla transaccional para registro de pasivos (Cuentas por Pagar)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inv_recepcion_detalle`
--

DROP TABLE IF EXISTS `inv_recepcion_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_recepcion_detalle` (
  `iddetallerecepcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recepcionid` bigint unsigned NOT NULL,
  `idrequisicionarticulo` int NOT NULL COMMENT 'Enlace a la partida original de la requisición',
  `inventarioid` bigint unsigned NOT NULL COMMENT 'ID del producto en wms_inventario',
  `cantidad_recibida` decimal(12,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`iddetallerecepcion`),
  KEY `idx_det_recepcion_parent` (`recepcionid`),
  KEY `idx_det_recepcion_item` (`idrequisicionarticulo`),
  CONSTRAINT `fk_det_recepcion` FOREIGN KEY (`recepcionid`) REFERENCES `inv_recepciones` (`idrecepcion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inv_recepciones`
--

DROP TABLE IF EXISTS `inv_recepciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_recepciones` (
  `idrecepcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `idcompra` bigint unsigned NOT NULL COMMENT 'OC Origen',
  `plantaid` bigint unsigned NOT NULL COMMENT 'Planta donde se recibe físicamente',
  `usuarioid` bigint unsigned NOT NULL COMMENT 'Almacenista responsable del conteo',
  `num_remision` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Folio del documento físico del proveedor',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL COMMENT 'Usuario que registró la entrada',
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idrecepcion`),
  KEY `idx_recepcion_oc` (`idcompra`),
  KEY `idx_recepcion_planta` (`plantaid`),
  CONSTRAINT `fk_recepcion_oc` FOREIGN KEY (`idcompra`) REFERENCES `com_ordenes_compra` (`idcompra`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inv_unidades`
--

DROP TABLE IF EXISTS `inv_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_modelo` int DEFAULT NULL,
  `id_color` int DEFAULT NULL,
  `origen_ubicacion_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_aprobadores`
--

DROP TABLE IF EXISTS `lgs_aprobadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_aprobadores` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_destinos`
--

DROP TABLE IF EXISTS `lgs_cat_destinos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_destinos` (
  `id_destino` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_libre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tipo_destino` tinyint DEFAULT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_destino`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_distribuidores`
--

DROP TABLE IF EXISTS `lgs_cat_distribuidores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_distribuidores` (
  `id_distribuidor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_distribuidor`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_motivo_envio`
--

DROP TABLE IF EXISTS `lgs_cat_motivo_envio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_motivo_envio` (
  `id_motivo` int NOT NULL AUTO_INCREMENT,
  `cve_motivo` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_motivo`),
  UNIQUE KEY `cve_motivo` (`cve_motivo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_origenes`
--

DROP TABLE IF EXISTS `lgs_cat_origenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_origenes` (
  `id_origen` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_origen`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_segmentos`
--

DROP TABLE IF EXISTS `lgs_cat_segmentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_segmentos` (
  `id_segmento` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_segmento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_tipo_destino`
--

DROP TABLE IF EXISTS `lgs_cat_tipo_destino`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_tipo_destino` (
  `id_tipo_destino` tinyint NOT NULL AUTO_INCREMENT,
  `cve_destino` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tipo_destino`),
  UNIQUE KEY `cve_destino` (`cve_destino`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_tipo_traslado`
--

DROP TABLE IF EXISTS `lgs_cat_tipo_traslado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_tipo_traslado` (
  `id_tipo_traslado` tinyint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tipo_traslado`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_cat_ubicaciones`
--

DROP TABLE IF EXISTS `lgs_cat_ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_cat_ubicaciones` (
  `id_ubicacion` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tipo_destino` tinyint DEFAULT NULL COMMENT '1=Distribuidor, 2=Carrocero, 3=Cliente, 4=Almacen, 5=Planta, 6=Otro',
  `id_distribuidor` int DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ubicacion`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `id_tipo_destino` (`id_tipo_destino`),
  KEY `idx_distribuidor` (`id_distribuidor`),
  CONSTRAINT `lgs_cat_ubicaciones_ibfk_1` FOREIGN KEY (`id_tipo_destino`) REFERENCES `lgs_cat_tipo_destino` (`id_tipo_destino`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_checklist_evidencias`
--

DROP TABLE IF EXISTS `lgs_checklist_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_checklist_evidencias` (
  `id_evidencia` int NOT NULL AUTO_INCREMENT,
  `id_checklist` int NOT NULL,
  `tipo_foto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`),
  KEY `idx_chk_ev` (`id_checklist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_costos_proveedor_segmento`
--

DROP TABLE IF EXISTS `lgs_costos_proveedor_segmento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_costos_proveedor_segmento` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint NOT NULL,
  `id_segmento` int DEFAULT NULL,
  `num_vins_min` int NOT NULL DEFAULT '1',
  `num_vins_max` int NOT NULL DEFAULT '99',
  `costo_por_km` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `factor` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `activo` tinyint NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prov_costo` (`id_proveedor`,`id_segmento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_costos_rutas`
--

DROP TABLE IF EXISTS `lgs_costos_rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_costos_rutas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_tipo_traslado` tinyint NOT NULL COMMENT '1=Madrina, 2=Chofer Rodando',
  `id_origen` int NOT NULL,
  `id_destino` int NOT NULL,
  `id_segmento` int NOT NULL,
  `id_proveedor` bigint unsigned DEFAULT NULL,
  `num_vins_min` int NOT NULL DEFAULT '1',
  `num_vins_max` int NOT NULL DEFAULT '1',
  `km` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_por_km` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `precio_plano` decimal(12,2) NOT NULL DEFAULT '0.00',
  `factor` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `activo` tinyint NOT NULL DEFAULT '2' COMMENT '2=Activo, 0=Inactivo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ruta_lookup` (`id_tipo_traslado`,`id_origen`,`id_destino`,`id_segmento`),
  KEY `idx_costo_ruta_prov` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=3801 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_distancias`
--

DROP TABLE IF EXISTS `lgs_distancias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_distancias` (
  `id_distancia` int NOT NULL AUTO_INCREMENT,
  `id_ubicacion_a` int NOT NULL COMMENT 'FK lgs_cat_ubicaciones (ID menor para bidireccional)',
  `id_ubicacion_b` int NOT NULL COMMENT 'FK lgs_cat_ubicaciones (ID mayor para bidireccional)',
  `km` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_distancia`),
  UNIQUE KEY `uq_distancia_par` (`id_ubicacion_a`,`id_ubicacion_b`),
  KEY `id_ubicacion_b` (`id_ubicacion_b`),
  CONSTRAINT `lgs_distancias_ibfk_1` FOREIGN KEY (`id_ubicacion_a`) REFERENCES `lgs_cat_ubicaciones` (`id_ubicacion`) ON DELETE CASCADE,
  CONSTRAINT `lgs_distancias_ibfk_2` FOREIGN KEY (`id_ubicacion_b`) REFERENCES `lgs_cat_ubicaciones` (`id_ubicacion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=605 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios`
--

DROP TABLE IF EXISTS `lgs_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios` (
  `id_envio` bigint NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Folio EN-000001',
  `id_tipo_traslado` tinyint DEFAULT NULL,
  `id_motivo` int DEFAULT NULL,
  `id_proveedor` bigint NOT NULL COMMENT 'FK prv_cat_proveedores',
  `id_origen` int DEFAULT NULL COMMENT 'FK lgs_cat_origenes',
  `id_destino` bigint DEFAULT NULL COMMENT 'Destino principal/final',
  `destino_nombre_libre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `km_total` decimal(10,2) DEFAULT '0.00',
  `costo_total` decimal(12,2) DEFAULT NULL,
  `fecha_tentativa_envio` datetime DEFAULT NULL,
  `fecha_tentativa_llegada` datetime DEFAULT NULL,
  `fecha_confirmada_recoleccion` date DEFAULT NULL COMMENT 'Pactada con el trasladista para patio',
  `fecha_salida_real` datetime DEFAULT NULL COMMENT 'Salida física de planta',
  `fecha_llegada_real` datetime DEFAULT NULL COMMENT 'Llegada y entrega en destino',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `id_estado` tinyint DEFAULT '1' COMMENT '1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=Programado 6=En Tránsito 7=Entregado 8=Cancelado',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_envio`),
  UNIQUE KEY `folio` (`folio`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios_evidencias`
--

DROP TABLE IF EXISTS `lgs_envios_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios_evidencias` (
  `id_envio_evidencia` int NOT NULL AUTO_INCREMENT,
  `id_envio` int NOT NULL,
  `tipo_evidencia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'salida',
  `archivos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_envio_evidencia`),
  UNIQUE KEY `uk_envio_tipo` (`id_envio`,`tipo_evidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios_nodos`
--

DROP TABLE IF EXISTS `lgs_envios_nodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios_nodos` (
  `id_nodo` bigint NOT NULL AUTO_INCREMENT,
  `id_envio` bigint NOT NULL,
  `orden` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'Posición en la ruta (0=Origen, 1..N=Paradas)',
  `tipo_nodo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'entrega',
  `id_ubicacion` int DEFAULT NULL COMMENT 'FK lgs_cat_ubicaciones',
  `destino_nombre_libre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `km_tramo_anterior` decimal(10,2) DEFAULT '0.00' COMMENT 'Km desde el nodo anterior hasta este',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_estimada` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_nodo`),
  KEY `id_envio` (`id_envio`),
  KEY `id_ubicacion` (`id_ubicacion`),
  CONSTRAINT `lgs_envios_nodos_ibfk_1` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE,
  CONSTRAINT `lgs_envios_nodos_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `lgs_cat_ubicaciones` (`id_ubicacion`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios_paradas`
--

DROP TABLE IF EXISTS `lgs_envios_paradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios_paradas` (
  `id_parada` bigint NOT NULL AUTO_INCREMENT,
  `id_envio` bigint NOT NULL,
  `orden` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Primera parada, 2=Segunda...',
  `id_destino_cat` bigint DEFAULT NULL COMMENT 'FK cli_clientes o lgs_cat_destinos',
  `destino_nombre_libre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `km_tramo` decimal(10,2) DEFAULT '0.00' COMMENT 'Km de este tramo',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_parada`),
  KEY `id_envio` (`id_envio`),
  CONSTRAINT `lgs_envios_paradas_ibfk_1` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios_tramos_costos`
--

DROP TABLE IF EXISTS `lgs_envios_tramos_costos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios_tramos_costos` (
  `id_tramo_costo` bigint NOT NULL AUTO_INCREMENT,
  `id_envio` bigint NOT NULL,
  `id_madrina` bigint DEFAULT NULL,
  `id_chofer` bigint DEFAULT NULL,
  `id_nodo_origen` bigint NOT NULL COMMENT 'FK lgs_envios_nodos',
  `id_nodo_destino` bigint NOT NULL COMMENT 'FK lgs_envios_nodos',
  `km_tramo` decimal(10,2) NOT NULL,
  `vins_ligeros` int DEFAULT '0',
  `vins_medianos` int DEFAULT '0',
  `vins_pesados` int DEFAULT '0',
  `vins_especiales` int DEFAULT '0',
  `factor_aplicado` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `costo_estimado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tarifa_usada_id` bigint DEFAULT NULL COMMENT 'FK a lgs_costos_rutas',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tramo_costo`),
  KEY `id_envio` (`id_envio`),
  KEY `id_nodo_origen` (`id_nodo_origen`),
  KEY `id_nodo_destino` (`id_nodo_destino`),
  CONSTRAINT `lgs_envios_tramos_costos_ibfk_1` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE,
  CONSTRAINT `lgs_envios_tramos_costos_ibfk_2` FOREIGN KEY (`id_nodo_origen`) REFERENCES `lgs_envios_nodos` (`id_nodo`) ON DELETE CASCADE,
  CONSTRAINT `lgs_envios_tramos_costos_ibfk_3` FOREIGN KEY (`id_nodo_destino`) REFERENCES `lgs_envios_nodos` (`id_nodo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=425 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_envios_vins`
--

DROP TABLE IF EXISTS `lgs_envios_vins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_envios_vins` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_envio` bigint NOT NULL,
  `id_unidad` bigint NOT NULL COMMENT 'ID de la unidad terminada',
  `id_destino` int DEFAULT NULL,
  `id_parada` bigint DEFAULT NULL COMMENT 'Parada donde se entrega este VIN',
  `id_nodo_subida` bigint DEFAULT NULL COMMENT 'En qué nodo se sube a la madrina',
  `id_nodo_bajada` bigint DEFAULT NULL COMMENT 'En qué nodo se baja de la madrina',
  `destino_nombre_libre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_madrina` bigint DEFAULT NULL COMMENT 'FK prv_det_madrinas',
  `id_chofer` bigint DEFAULT NULL COMMENT 'FK prv_det_choferes',
  `posicion_acomodo` tinyint unsigned DEFAULT NULL COMMENT '1 = primero en subir a la madrina',
  `estado_unidad_fisico` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'EN_PATIO' COMMENT 'EN_PATIO, EN_ENTREGAS, EN_RUTA, ENTREGADO',
  `costo_unidad` decimal(12,2) DEFAULT NULL,
  `fecha_entrega_real` datetime DEFAULT NULL,
  `recibe_nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_estado` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_envio_vin` (`id_envio`,`id_unidad`),
  KEY `fk_vin_nodo_subida` (`id_nodo_subida`),
  KEY `fk_vin_nodo_bajada` (`id_nodo_bajada`),
  CONSTRAINT `fk_vin_nodo_bajada` FOREIGN KEY (`id_nodo_bajada`) REFERENCES `lgs_envios_nodos` (`id_nodo`) ON DELETE SET NULL,
  CONSTRAINT `fk_vin_nodo_subida` FOREIGN KEY (`id_nodo_subida`) REFERENCES `lgs_envios_nodos` (`id_nodo`) ON DELETE SET NULL,
  CONSTRAINT `lgs_envios_vins_ibfk_1` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_evidencias`
--

DROP TABLE IF EXISTS `lgs_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_evidencias` (
  `id_evidencia` bigint NOT NULL AUTO_INCREMENT,
  `id_envio` bigint NOT NULL,
  `id_unidad` bigint DEFAULT NULL,
  `tipo_evidencia` tinyint NOT NULL COMMENT '1: Salida, 2: Llegada',
  `ruta_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`),
  KEY `id_envio` (`id_envio`),
  CONSTRAINT `lgs_evidencias_ibfk_1` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_planeaciones`
--

DROP TABLE IF EXISTS `lgs_planeaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_planeaciones` (
  `id_planeacion` bigint NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Folio EX-000001',
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `km_total` decimal(10,2) DEFAULT NULL,
  `costo_total` decimal(12,2) DEFAULT NULL,
  `id_estado` tinyint DEFAULT '1' COMMENT '1=Creada 2=Enviada 3=Regresada 5=Aprobada',
  `obs_operador` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `obs_aprobador` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `aprobado_by` bigint unsigned DEFAULT NULL,
  `aprobado_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_planeacion`),
  UNIQUE KEY `folio` (`folio`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_planeaciones_envios`
--

DROP TABLE IF EXISTS `lgs_planeaciones_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_planeaciones_envios` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_planeacion` bigint NOT NULL,
  `id_envio` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_envio` (`id_planeacion`,`id_envio`),
  KEY `id_envio` (`id_envio`),
  CONSTRAINT `lgs_planeaciones_envios_ibfk_1` FOREIGN KEY (`id_planeacion`) REFERENCES `lgs_planeaciones` (`id_planeacion`) ON DELETE CASCADE,
  CONSTRAINT `lgs_planeaciones_envios_ibfk_2` FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios` (`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_solicitudes_entrega`
--

DROP TABLE IF EXISTS `lgs_solicitudes_entrega`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_solicitudes_entrega` (
  `id_solicitud` int NOT NULL AUTO_INCREMENT,
  `id_envio` int NOT NULL,
  `id_unidad` int NOT NULL,
  `orden_acomodo` int DEFAULT '1',
  `confirmado` tinyint(1) DEFAULT '0' COMMENT '0=En patio, 1=Entregado/Cargado',
  `fecha_confirmacion` datetime DEFAULT NULL,
  `confirmado_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_solicitud`),
  UNIQUE KEY `uk_envio_unidad` (`id_envio`,`id_unidad`),
  KEY `idx_sol_envio` (`id_envio`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_tarifas_proveedores`
--

DROP TABLE IF EXISTS `lgs_tarifas_proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_tarifas_proveedores` (
  `id_tarifa` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint NOT NULL COMMENT '0 = Tarifa Base General',
  `id_tipo_traslado` tinyint NOT NULL COMMENT '1 = Madrina, 2 = Chofer Rodando',
  `id_segmento` int NOT NULL,
  `num_vins_min` int NOT NULL DEFAULT '1',
  `num_vins_max` int NOT NULL DEFAULT '15',
  `costo_por_km` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `precio_plano` decimal(12,2) NOT NULL DEFAULT '0.00',
  `factor` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `activo` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tarifa`),
  UNIQUE KEY `uk_tarifa_prov` (`id_proveedor`,`id_tipo_traslado`,`id_segmento`,`num_vins_min`)
) ENGINE=InnoDB AUTO_INCREMENT=2009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_trasladistas_checklist`
--

DROP TABLE IF EXISTS `lgs_trasladistas_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_trasladistas_checklist` (
  `id_checklist` int NOT NULL AUTO_INCREMENT,
  `id_envio` int NOT NULL,
  `id_unidad` int NOT NULL,
  `tipo_checklist` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'entrada_trasladista, salida_planta, entrega_destino',
  `vin_escaneado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_registro_id` int NOT NULL,
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_checklist`),
  KEY `idx_chk_envio` (`id_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_unidades`
--

DROP TABLE IF EXISTS `lgs_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_unidades` (
  `id_lgs_unidad` int NOT NULL AUTO_INCREMENT,
  `id_unidad` int NOT NULL,
  `id_motivo` int DEFAULT NULL,
  `id_destino` int DEFAULT NULL,
  `destino_descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_estado_proceso` tinyint NOT NULL DEFAULT '1',
  `fecha_salida` datetime DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `created_by` int DEFAULT '1',
  `updated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lgs_unidad`),
  KEY `idx_lgs_id_unidad` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_unidades_entrega_interna`
--

DROP TABLE IF EXISTS `lgs_unidades_entrega_interna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_unidades_entrega_interna` (
  `id_entrega_interna` int NOT NULL AUTO_INCREMENT,
  `id_unidad` int NOT NULL,
  `id_estado` tinyint NOT NULL DEFAULT '1',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `solicitado_by` int DEFAULT NULL,
  `solicitado_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `confirmado_by` int DEFAULT NULL,
  `confirmado_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_entrega_interna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lgs_unidades_envios`
--

DROP TABLE IF EXISTS `lgs_unidades_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lgs_unidades_envios` (
  `id_unidad` int NOT NULL AUTO_INCREMENT,
  `vin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `num_serie` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `origen` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `destino` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estatus` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'disponible',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_unidad`),
  UNIQUE KEY `uq_lgs_unidades_vin` (`vin`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_audit`
--

DROP TABLE IF EXISTS `log_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resourceid` int NOT NULL,
  `usuarioid` int NOT NULL,
  `nombre_tabla` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `idlog` bigint NOT NULL AUTO_INCREMENT,
  `idusuario` bigint NOT NULL,
  `evento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `detalle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idlog`),
  KEY `idusuario` (`idusuario`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=1975 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modelos`
--

DROP TABLE IF EXISTS `modelos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modelos` (
  `id_modelo` int NOT NULL AUTO_INCREMENT,
  `id_tipo_unidad` int NOT NULL,
  `nombre_modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_producto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_modelo`),
  KEY `fk_modelos_tipo_unidad` (`id_tipo_unidad`),
  CONSTRAINT `fk_modelos_tipo_unidad` FOREIGN KEY (`id_tipo_unidad`) REFERENCES `tipo_unidades` (`id_tipo_unidad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modulo`
--

DROP TABLE IF EXISTS `modulo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulo` (
  `idmodulo` bigint NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idmodulo`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_acciones`
--

DROP TABLE IF EXISTS `mrp_acciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_acciones` (
  `idaccion` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idaccion`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_acciones_notificaciones`
--

DROP TABLE IF EXISTS `mrp_acciones_notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_acciones_notificaciones` (
  `idnotificacion` bigint NOT NULL AUTO_INCREMENT,
  `accionid` bigint DEFAULT NULL,
  `usuario_origen` bigint DEFAULT NULL,
  `usuario_destino` bigint DEFAULT NULL,
  `tipo_notificacion` tinyint NOT NULL COMMENT '\r\n    1=Solicitud asistencia,\r\n    2=Falta material\r\n  ',
  `enviado_correo` tinyint NOT NULL DEFAULT '1' COMMENT '1=No, 2=Si',
  `fecha_envio` datetime DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '\r\n    1=Pendiente,\r\n    2=Enviada,\r\n    3=Leida,\r\n    4=Atendida\r\n  ',
  PRIMARY KEY (`idnotificacion`),
  KEY `accionid` (`accionid`),
  KEY `usuario_origen` (`usuario_origen`),
  KEY `usuario_destino` (`usuario_destino`),
  CONSTRAINT `mrp_acciones_notificaciones_ibfk_1` FOREIGN KEY (`usuario_origen`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_acciones_notificaciones_ibfk_2` FOREIGN KEY (`usuario_destino`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_acciones_notificaciones_ibfk_3` FOREIGN KEY (`accionid`) REFERENCES `mrp_acciones_produccion` (`idaccion`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_acciones_produccion`
--

DROP TABLE IF EXISTS `mrp_acciones_produccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_acciones_produccion` (
  `idaccion` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint DEFAULT NULL,
  `estacionid` bigint DEFAULT NULL,
  `idordengeneral` bigint DEFAULT NULL,
  `unidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `origen_accion` tinyint NOT NULL COMMENT '1=No conforme, 2=Paro manual',
  `tipo_accion` tinyint NOT NULL COMMENT '\r\n    1=Paro momentaneo,\r\n    2=Retiro AGV,\r\n    3=Unidad alarmada,\r\n    4=Solicitud asistencia,\r\n    5=Falta material\r\n  ',
  `fecha_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT NULL,
  `minutos_total` decimal(10,2) DEFAULT NULL,
  `usuarioid` bigint DEFAULT NULL,
  `usuarioidfin` bigint NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '\r\n    1=Pendiente,\r\n    2=Activo,\r\n    3=Cerrado,\r\n    4=Cancelado\r\n  ',
  PRIMARY KEY (`idaccion`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `idordengeneral` (`idordengeneral`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_acciones_produccion_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_acciones_produccion_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_acciones_produccion_ibfk_3` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_acciones_produccion_ibfk_4` FOREIGN KEY (`idordengeneral`) REFERENCES `mrp_ordenes_trabajo` (`idorden`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_auditoria`
--

DROP TABLE IF EXISTS `mrp_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_auditoria` (
  `idauditoria` bigint NOT NULL AUTO_INCREMENT,
  `moduloid` bigint NOT NULL,
  `accionid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `tabla_afectada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_registro` bigint NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `navegador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`idauditoria`),
  KEY `idmodulo` (`moduloid`),
  KEY `idaccion` (`accionid`),
  KEY `idusuario` (`usuarioid`),
  CONSTRAINT `mrp_auditoria_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_auditoria_ibfk_2` FOREIGN KEY (`accionid`) REFERENCES `mrp_acciones` (`idaccion`),
  CONSTRAINT `mrp_auditoria_ibfk_3` FOREIGN KEY (`moduloid`) REFERENCES `modulo` (`idmodulo`)
) ENGINE=InnoDB AUTO_INCREMENT=743 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_calidad_inspeccion`
--

DROP TABLE IF EXISTS `mrp_calidad_inspeccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_calidad_inspeccion` (
  `idinspeccion` bigint NOT NULL AUTO_INCREMENT,
  `idorden` bigint NOT NULL,
  `numot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL,
  `fecha_cierre` datetime NOT NULL,
  PRIMARY KEY (`idinspeccion`),
  KEY `idorden` (`idorden`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_calidad_inspeccion_ibfk_1` FOREIGN KEY (`idorden`) REFERENCES `mrp_ordenes_trabajo` (`idorden`),
  CONSTRAINT `mrp_calidad_inspeccion_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_calidad_inspeccion_ibfk_3` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_calidad_inspeccion_ibfk_4` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_calidad_inspeccion_detalle`
--

DROP TABLE IF EXISTS `mrp_calidad_inspeccion_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_calidad_inspeccion_detalle` (
  `iddetalle` bigint NOT NULL AUTO_INCREMENT,
  `idinspeccion` bigint NOT NULL,
  `especificacionid` bigint NOT NULL,
  `resultado` enum('OK','NO_OK') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `comentario_no_ok` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `accion_correctiva` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `idinspeccion` (`idinspeccion`),
  KEY `especificacionid` (`especificacionid`),
  CONSTRAINT `mrp_calidad_inspeccion_detalle_ibfk_1` FOREIGN KEY (`idinspeccion`) REFERENCES `mrp_calidad_inspeccion` (`idinspeccion`),
  CONSTRAINT `mrp_calidad_inspeccion_detalle_ibfk_2` FOREIGN KEY (`especificacionid`) REFERENCES `mrp_estacion_especificaciones` (`idespecificacion`)
) ENGINE=InnoDB AUTO_INCREMENT=787 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_calidad_inspeccion_evidencia`
--

DROP TABLE IF EXISTS `mrp_calidad_inspeccion_evidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_calidad_inspeccion_evidencia` (
  `idevidencia` bigint NOT NULL AUTO_INCREMENT,
  `iddetalle` bigint NOT NULL,
  `nombre_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `size_bytes` int DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idevidencia`),
  KEY `iddetalle` (`iddetalle`),
  CONSTRAINT `mrp_calidad_inspeccion_evidencia_ibfk_1` FOREIGN KEY (`iddetalle`) REFERENCES `mrp_calidad_inspeccion_detalle` (`iddetalle`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_dias_festivos`
--

DROP TABLE IF EXISTS `mrp_dias_festivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_dias_festivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion`
--

DROP TABLE IF EXISTS `mrp_estacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion` (
  `idestacion` bigint NOT NULL AUTO_INCREMENT,
  `cve_estacion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `plantaid` bigint NOT NULL,
  `lineaid` bigint NOT NULL,
  `nombre_estacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proceso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estandar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_medida` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tiempo_ajuste` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mxn` decimal(10,2) NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `herramientas` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=no 1=si',
  `tiene_subensamble` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:NO 1:SI',
  `estado` tinyint(1) NOT NULL COMMENT '0=Eliminada 1=Inactivo 2=Activo',
  PRIMARY KEY (`idestacion`),
  KEY `lineaid` (`lineaid`),
  KEY `plantaid` (`plantaid`),
  CONSTRAINT `mrp_estacion_ibfk_1` FOREIGN KEY (`lineaid`) REFERENCES `mrp_linea` (`idlinea`),
  CONSTRAINT `mrp_estacion_ibfk_2` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_ayudas_visuales`
--

DROP TABLE IF EXISTS `mrp_estacion_ayudas_visuales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_ayudas_visuales` (
  `idayuda` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idayuda`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_ayudas_visuales_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_estacion_ayudas_visuales_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_componentes`
--

DROP TABLE IF EXISTS `mrp_estacion_componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_componentes` (
  `idcomponente` bigint NOT NULL AUTO_INCREMENT,
  `almacenid` bigint NOT NULL,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `cantidad` int NOT NULL,
  `estado` int NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idcomponente`),
  KEY `almacenid` (`almacenid`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `mrp_estacion_componentes_ibfk_1` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `mrp_estacion_componentes_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_estacion_componentes_ibfk_3` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_estacion_componentes_ibfk_4` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=254 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_especificaciones`
--

DROP TABLE IF EXISTS `mrp_estacion_especificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_especificaciones` (
  `idespecificacion` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `especificacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `asignado` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:NO 1:SI',
  `estado` tinyint(1) NOT NULL DEFAULT '2' COMMENT '0=Eliminado\r\n1=Inactivo\r\n2=Activo',
  PRIMARY KEY (`idespecificacion`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_especificaciones_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_estacion_especificaciones_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_especificaciones_criticas`
--

DROP TABLE IF EXISTS `mrp_estacion_especificaciones_criticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_especificaciones_criticas` (
  `idespecificacion` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `especificacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2' COMMENT '0=Eliminado 1=Inactivo 2=Activo',
  PRIMARY KEY (`idespecificacion`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_especificaciones_criticas_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_estacion_especificaciones_criticas_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_herramientas`
--

DROP TABLE IF EXISTS `mrp_estacion_herramientas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_herramientas` (
  `idherramienta` bigint NOT NULL AUTO_INCREMENT,
  `almacenid` bigint NOT NULL,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `cantidad` bigint NOT NULL,
  `estado` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idherramienta`),
  KEY `almacenid` (`almacenid`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `mrp_estacion_herramientas_ibfk_1` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `mrp_estacion_herramientas_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_estacion_herramientas_ibfk_3` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_estacion_herramientas_ibfk_4` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_mantenimiento`
--

DROP TABLE IF EXISTS `mrp_estacion_mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_mantenimiento` (
  `idmantenimiento` bigint NOT NULL AUTO_INCREMENT,
  `estacionid` bigint NOT NULL,
  `responsable` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_programada` datetime NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `mantenimiento` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Sin Mantenimiento\r\n2=Pendiente/Programado\r\n3=En proceso\r\n4=Finalizado\r\n5=Cancelado',
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminado 1=Inactivo 2=Activo',
  PRIMARY KEY (`idmantenimiento`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_mantenimiento_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_pdi`
--

DROP TABLE IF EXISTS `mrp_estacion_pdi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_pdi` (
  `idpdi` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estado` tinyint NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idpdi`),
  KEY `idx_productoid` (`productoid`),
  KEY `idx_estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_pdi_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_estacion_pdi_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_pdi_punto`
--

DROP TABLE IF EXISTS `mrp_estacion_pdi_punto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_pdi_punto` (
  `idpuntopdi` bigint NOT NULL AUTO_INCREMENT,
  `zonaid` bigint NOT NULL,
  `punto` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `orden` int NOT NULL DEFAULT '1',
  `check_china` tinyint NOT NULL DEFAULT '0',
  `check_mexico` tinyint NOT NULL DEFAULT '0',
  `check_i1` tinyint NOT NULL DEFAULT '0',
  `check_i2` tinyint NOT NULL DEFAULT '0',
  `check_i3` tinyint NOT NULL DEFAULT '0',
  `check_i4` tinyint NOT NULL DEFAULT '0',
  `estado` tinyint NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idpuntopdi`),
  KEY `idx_zonaid` (`zonaid`),
  CONSTRAINT `mrp_estacion_pdi_punto_ibfk_1` FOREIGN KEY (`zonaid`) REFERENCES `mrp_estacion_pdi_zona` (`idzona`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_pdi_puntos`
--

DROP TABLE IF EXISTS `mrp_estacion_pdi_puntos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_pdi_puntos` (
  `idpuntopdi` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint DEFAULT NULL,
  `estacionid` bigint DEFAULT NULL,
  `punto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `criterio` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `severidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `evidencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `orden` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idpuntopdi`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_pdi_resultado`
--

DROP TABLE IF EXISTS `mrp_estacion_pdi_resultado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_pdi_resultado` (
  `idresultado` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint DEFAULT NULL,
  `estacionid` bigint DEFAULT NULL,
  `idordengeneral` bigint DEFAULT NULL,
  `unidad_actual` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idpuntopdi` bigint DEFAULT NULL,
  `tipo_check` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `resultado` tinyint NOT NULL,
  `observacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `usuarioid` bigint DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint DEFAULT '2',
  PRIMARY KEY (`idresultado`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `idordengeneral` (`idordengeneral`),
  KEY `idpuntopdi` (`idpuntopdi`),
  CONSTRAINT `mrp_estacion_pdi_resultado_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_estacion_pdi_resultado_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_estacion_pdi_resultado_ibfk_3` FOREIGN KEY (`idordengeneral`) REFERENCES `mrp_ordenes_trabajo` (`idorden`),
  CONSTRAINT `mrp_estacion_pdi_resultado_ibfk_4` FOREIGN KEY (`idpuntopdi`) REFERENCES `mrp_estacion_pdi_punto` (`idpuntopdi`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_pdi_zona`
--

DROP TABLE IF EXISTS `mrp_estacion_pdi_zona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_pdi_zona` (
  `idzona` bigint NOT NULL AUTO_INCREMENT,
  `pdiid` bigint NOT NULL,
  `nombre_zona` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '1',
  `estado` tinyint NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idzona`),
  KEY `idx_pdiid` (`pdiid`),
  CONSTRAINT `mrp_estacion_pdi_zona_ibfk_1` FOREIGN KEY (`pdiid`) REFERENCES `mrp_estacion_pdi` (`idpdi`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_estacion_subensamble`
--

DROP TABLE IF EXISTS `mrp_estacion_subensamble`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_estacion_subensamble` (
  `idsubensamble` bigint NOT NULL AUTO_INCREMENT,
  `estacionid` bigint NOT NULL,
  `nombre_estacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proceso` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estandar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tiempo_ajuste` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `herramientas` tinyint NOT NULL DEFAULT '0' COMMENT '0=no 1=si',
  `estado` tinyint NOT NULL COMMENT '0=Eliminada 1=Inactivo 2=Activo	',
  PRIMARY KEY (`idsubensamble`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_estacion_subensamble_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_linea`
--

DROP TABLE IF EXISTS `mrp_linea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_linea` (
  `idlinea` bigint NOT NULL AUTO_INCREMENT,
  `cve_linea` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `plantaid` bigint NOT NULL,
  `nombre_linea` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idlinea`),
  KEY `plantaid` (`plantaid`),
  CONSTRAINT `mrp_linea_ibfk_1` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_operaciones_criticas_realizadas`
--

DROP TABLE IF EXISTS `mrp_operaciones_criticas_realizadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_operaciones_criticas_realizadas` (
  `idoperacionrealizada` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `tipo_origen` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estacionid` bigint DEFAULT NULL,
  `subensambleid` bigint DEFAULT NULL,
  `idespecificacion` bigint NOT NULL,
  `operacion_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuarioid` bigint NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint NOT NULL DEFAULT '2',
  `unidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resultado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Conforme 2=No conforme',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`idoperacionrealizada`),
  KEY `productoid` (`productoid`),
  KEY `usuarioid` (`usuarioid`),
  KEY `idespecificacion` (`idespecificacion`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_operaciones_criticas_realizadas_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_operaciones_criticas_realizadas_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_operaciones_criticas_realizadas_ibfk_3` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_operaciones_criticas_realizadas_ibfk_4` FOREIGN KEY (`idespecificacion`) REFERENCES `mrp_estacion_especificaciones_criticas` (`idespecificacion`)
) ENGINE=InnoDB AUTO_INCREMENT=321 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_operaciones_realizadas`
--

DROP TABLE IF EXISTS `mrp_operaciones_realizadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_operaciones_realizadas` (
  `idoperacionrealizada` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `tipo_origen` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estacionid` bigint DEFAULT NULL,
  `subensambleid` bigint DEFAULT NULL,
  `idespecificacion` bigint NOT NULL,
  `operacion_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usuarioid` bigint NOT NULL,
  `numcolaborador` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint NOT NULL DEFAULT '2',
  `unidad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`idoperacionrealizada`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_ordenes_trabajo`
--

DROP TABLE IF EXISTS `mrp_ordenes_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_ordenes_trabajo` (
  `idorden` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_estacionid` bigint NOT NULL,
  `num_sub_orden` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Pendiente\r\n2=En proceso\r\n3=Finalizada',
  `calidad` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=Sin asignar 1=Pendiente 2:Completado',
  `estampado` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0= No requiere 1=Pendiente 2=En proceso 3=Finalizada',
  `operaciones` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Sin asignar 1=Pendiente 2:Completado',
  `especificaciones_criticas` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Sin asignar 1=Pendiente 2:Completado',
  `accion_produccion` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Sin acción, 1=Paro momentáneo, 2=Retiro AGV, 3=Unidad alarmada, 4=Solicitud asistencia, 5=Falta material',
  `accion_activa` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=No, 2=Si',
  PRIMARY KEY (`idorden`),
  KEY `planeacion_estacionid` (`planeacion_estacionid`),
  CONSTRAINT `mrp_ordenes_trabajo_ibfk_1` FOREIGN KEY (`planeacion_estacionid`) REFERENCES `mrp_planeacion_estacion` (`id_planeacion_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=2660 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_ordenes_trabajo_subensamble`
--

DROP TABLE IF EXISTS `mrp_ordenes_trabajo_subensamble`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_ordenes_trabajo_subensamble` (
  `idorden_subensamble` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_subensambleid` bigint NOT NULL,
  `num_sub_orden` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `codigo_scan` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '1',
  `fecha_inicio_real` datetime DEFAULT NULL,
  `fecha_fin_real` datetime DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operaciones` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Sin asignar 1=Pendiente 2:Completado',
  `especificaciones_criticas` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=Sin asignar 1=Pendiente 2:Completado',
  PRIMARY KEY (`idorden_subensamble`),
  KEY `idx_planeacion_subensambleid` (`planeacion_subensambleid`),
  KEY `idx_codigo_scan` (`codigo_scan`),
  CONSTRAINT `mrp_ordenes_trabajo_subensamble_ibfk_1` FOREIGN KEY (`planeacion_subensambleid`) REFERENCES `mrp_planeacion_subensamble` (`id_planeacion_subensamble`)
) ENGINE=InnoDB AUTO_INCREMENT=601 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_ot_chat`
--

DROP TABLE IF EXISTS `mrp_ot_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_ot_chat` (
  `idchat` bigint NOT NULL AUTO_INCREMENT,
  `numorden` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `subot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `productoid` bigint NOT NULL DEFAULT '0' COMMENT 'OMITIR ESTE CAMPO',
  `estacionid` bigint NOT NULL,
  `planeacionid` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `user_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idchat`),
  KEY `estacionid` (`estacionid`),
  KEY `planeacionid` (`planeacionid`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `mrp_ot_chat_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_ot_chat_ibfk_2` FOREIGN KEY (`planeacionid`) REFERENCES `mrp_planeacion` (`idplaneacion`),
  CONSTRAINT `mrp_ot_chat_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion`
--

DROP TABLE IF EXISTS `mrp_planeacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion` (
  `idplaneacion` bigint NOT NULL AUTO_INCREMENT,
  `num_orden` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `productoid` bigint NOT NULL,
  `num_pedido` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisorid` bigint NOT NULL COMMENT 'VIENE DE LA TABLA USUARIOS :)',
  `prioridad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` int NOT NULL,
  `fecha_requerida` date DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `notas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint NOT NULL,
  `plantaid` bigint NOT NULL,
  `fase` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1=Borrador\r\n2=Pendiente/Programada\r\n3=En proceso\r\n4=Pausada/En espera\r\n5=Completada/Finalizada\r\n6=Cancelada\r\n7=Reprogramada',
  `fecha_inicio_real` datetime DEFAULT NULL,
  `usuario_inicio` bigint NOT NULL,
  `fecha_fin_real` datetime DEFAULT NULL,
  `usuario_fin` bigint NOT NULL,
  PRIMARY KEY (`idplaneacion`),
  KEY `productoid` (`productoid`),
  CONSTRAINT `mrp_planeacion_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_estacion`
--

DROP TABLE IF EXISTS `mrp_planeacion_estacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_estacion` (
  `id_planeacion_estacion` bigint NOT NULL AUTO_INCREMENT,
  `planeacionid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `orden` int NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  `estampado` tinyint(1) NOT NULL DEFAULT '0',
  `calidad` tinyint(1) NOT NULL DEFAULT '0',
  `operaciones` tinyint(1) NOT NULL DEFAULT '0',
  `especificaciones` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_planeacion_estacion`),
  KEY `planeacionid` (`planeacionid`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_planeacion_estacion_ibfk_1` FOREIGN KEY (`planeacionid`) REFERENCES `mrp_planeacion` (`idplaneacion`),
  CONSTRAINT `mrp_planeacion_estacion_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_estacion_calidadpdi`
--

DROP TABLE IF EXISTS `mrp_planeacion_estacion_calidadpdi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_estacion_calidadpdi` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_estacionid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `rol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `planeacion_estacionid` (`planeacion_estacionid`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_planeacion_estacion_calidadpdi_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_planeacion_estacion_calidadpdi_ibfk_2` FOREIGN KEY (`planeacion_estacionid`) REFERENCES `mrp_planeacion_estacion` (`id_planeacion_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_estacion_calidadpuntoscriticos`
--

DROP TABLE IF EXISTS `mrp_planeacion_estacion_calidadpuntoscriticos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_estacion_calidadpuntoscriticos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_estacionid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `rol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `planeacion_estacionid` (`planeacion_estacionid`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_planeacion_estacion_calidadpuntoscriticos_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_planeacion_estacion_calidadpuntoscriticos_ibfk_2` FOREIGN KEY (`planeacion_estacionid`) REFERENCES `mrp_planeacion_estacion` (`id_planeacion_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_estacion_operador`
--

DROP TABLE IF EXISTS `mrp_planeacion_estacion_operador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_estacion_operador` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_estacionid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `rol` enum('ENCARGADO','AYUDANTE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`),
  KEY `planeacion_estacionid` (`planeacion_estacionid`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_planeacion_estacion_operador_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_planeacion_estacion_operador_ibfk_2` FOREIGN KEY (`planeacion_estacionid`) REFERENCES `mrp_planeacion_estacion` (`id_planeacion_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=273 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_subensamble`
--

DROP TABLE IF EXISTS `mrp_planeacion_subensamble`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_subensamble` (
  `id_planeacion_subensamble` bigint NOT NULL AUTO_INCREMENT,
  `planeacionid` bigint NOT NULL,
  `planeacion_estacionid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `orden_sub` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_planeacion_subensamble`),
  KEY `idx_planeacionid` (`planeacionid`),
  KEY `idx_planeacion_estacionid` (`planeacion_estacionid`),
  KEY `idx_estacionid` (`estacionid`),
  KEY `idx_subensambleid` (`subensambleid`),
  CONSTRAINT `mrp_planeacion_subensamble_ibfk_1` FOREIGN KEY (`planeacionid`) REFERENCES `mrp_planeacion` (`idplaneacion`),
  CONSTRAINT `mrp_planeacion_subensamble_ibfk_2` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_planeacion_subensamble_ibfk_3` FOREIGN KEY (`subensambleid`) REFERENCES `mrp_estacion_subensamble` (`idsubensamble`),
  CONSTRAINT `mrp_planeacion_subensamble_ibfk_4` FOREIGN KEY (`planeacion_estacionid`) REFERENCES `mrp_planeacion_estacion` (`id_planeacion_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planeacion_subensamble_operador`
--

DROP TABLE IF EXISTS `mrp_planeacion_subensamble_operador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planeacion_subensamble_operador` (
  `id_planeacion_subensamble_operador` bigint NOT NULL AUTO_INCREMENT,
  `planeacion_subensambleid` bigint NOT NULL,
  `usuarioid` bigint NOT NULL,
  `rol` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_planeacion_subensamble_operador`),
  KEY `idx_planeacion_subensambleid` (`planeacion_subensambleid`),
  KEY `idx_usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_planeacion_subensamble_operador_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_planeacion_subensamble_operador_ibfk_2` FOREIGN KEY (`planeacion_subensambleid`) REFERENCES `mrp_planeacion_subensamble` (`id_planeacion_subensamble`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_planta`
--

DROP TABLE IF EXISTS `mrp_planta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_planta` (
  `idplanta` bigint NOT NULL AUTO_INCREMENT,
  `cve_planta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_planta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`idplanta`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_produccion_evento`
--

DROP TABLE IF EXISTS `mrp_produccion_evento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_produccion_evento` (
  `idproduccion_evento` bigint NOT NULL AUTO_INCREMENT,
  `tipo_origen` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `orden_trabajoid` bigint DEFAULT NULL,
  `orden_subensambleid` bigint DEFAULT NULL,
  `planeacion_estacionid` bigint DEFAULT NULL,
  `planeacion_subensambleid` bigint DEFAULT NULL,
  `usuarioid` bigint NOT NULL,
  `accion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estado` tinyint NOT NULL DEFAULT '2',
  PRIMARY KEY (`idproduccion_evento`),
  KEY `idx_usuarioid` (`usuarioid`),
  KEY `idx_accion` (`accion`),
  KEY `usuarioid` (`usuarioid`),
  CONSTRAINT `mrp_produccion_evento_ibfk_1` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`idusuario`)
) ENGINE=InnoDB AUTO_INCREMENT=828 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_producto_fases`
--

DROP TABLE IF EXISTS `mrp_producto_fases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_producto_fases` (
  `id_producto_fase` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `documentacion` tinyint(1) DEFAULT '0',
  `descriptiva` tinyint(1) DEFAULT '0',
  `procesos` tinyint(1) DEFAULT '0',
  `especificaciones_criticas` tinyint(1) DEFAULT '0',
  `calidad` tinyint(1) DEFAULT '0',
  `finalizado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_producto_fase`),
  KEY `productoid` (`productoid`),
  CONSTRAINT `mrp_producto_fases_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_producto_ruta`
--

DROP TABLE IF EXISTS `mrp_producto_ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_producto_ruta` (
  `idruta_producto` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `plantaid` bigint NOT NULL,
  `lineaid` bigint NOT NULL,
  `descripcion_ruta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idruta_producto`),
  KEY `productoid` (`productoid`),
  KEY `plantaid` (`plantaid`),
  KEY `lineaid` (`lineaid`),
  CONSTRAINT `mrp_producto_ruta_ibfk_1` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`),
  CONSTRAINT `mrp_producto_ruta_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_producto_ruta_ibfk_3` FOREIGN KEY (`lineaid`) REFERENCES `mrp_linea` (`idlinea`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_producto_ruta_detalle`
--

DROP TABLE IF EXISTS `mrp_producto_ruta_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_producto_ruta_detalle` (
  `iddetalle` bigint NOT NULL AUTO_INCREMENT,
  `ruta_productoid` bigint NOT NULL,
  `estacionid` bigint NOT NULL,
  `orden` smallint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estampado` tinyint(1) NOT NULL DEFAULT '0',
  `calidad` tinyint(1) NOT NULL DEFAULT '0',
  `operaciones` tinyint(1) NOT NULL DEFAULT '0',
  `especificaciones` tinyint(1) NOT NULL DEFAULT '0',
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`iddetalle`),
  KEY `ruta_productoid` (`ruta_productoid`),
  KEY `estacionid` (`estacionid`),
  CONSTRAINT `mrp_producto_ruta_detalle_ibfk_1` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_producto_ruta_detalle_ibfk_2` FOREIGN KEY (`ruta_productoid`) REFERENCES `mrp_producto_ruta` (`idruta_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_productos`
--

DROP TABLE IF EXISTS `mrp_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_productos` (
  `idproducto` bigint NOT NULL AUTO_INCREMENT,
  `cve_producto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `inventarioid` bigint NOT NULL,
  `plantaid` bigint NOT NULL,
  `lineaproductoid` bigint NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `avance_general` int NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminada\r\n1=Inactivo \r\n2=Activo ',
  PRIMARY KEY (`idproducto`),
  KEY `inventarioid` (`inventarioid`),
  KEY `lineaproductoid` (`lineaproductoid`),
  KEY `plantaid` (`plantaid`),
  CONSTRAINT `mrp_productos_ibfk_1` FOREIGN KEY (`lineaproductoid`) REFERENCES `wms_linea_producto` (`idlineaproducto`),
  CONSTRAINT `mrp_productos_ibfk_2` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `mrp_productos_ibfk_3` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_productos_descriptiva`
--

DROP TABLE IF EXISTS `mrp_productos_descriptiva`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_productos_descriptiva` (
  `iddescriptiva` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `marca` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `modelo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `largo_total` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `distancia_ejes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `peso_bruto_vehicular` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `motor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cilindros` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `desplazamiento_c` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Peso chasis cabina',
  `tipo_combustible` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `potencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `torque` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `transmision` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `eje_delantero` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `suspension_delantera` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `eje_trasero` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `suspension_trasera` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `llantas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sistema_frenos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `asistencias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sistema_electrico` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `capacidad_combustible` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `norma` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `equipamiento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminado\r\n1=Activo\r\n2=Inactivo',
  PRIMARY KEY (`iddescriptiva`),
  KEY `productoid` (`productoid`),
  CONSTRAINT `mrp_productos_descriptiva_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_productos_documentos`
--

DROP TABLE IF EXISTS `mrp_productos_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_productos_documentos` (
  `iddocumento` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `tipo_documento` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ruta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminada\r\n1=Inactivo \r\n2=Activo ',
  PRIMARY KEY (`iddocumento`),
  KEY `productoid` (`productoid`),
  CONSTRAINT `mrp_productos_documentos_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_subensamble_ayudas_visuales`
--

DROP TABLE IF EXISTS `mrp_subensamble_ayudas_visuales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_subensamble_ayudas_visuales` (
  `idaysubayuda` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `titulo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idaysubayuda`),
  KEY `productoid` (`productoid`),
  KEY `subensambleid` (`subensambleid`),
  CONSTRAINT `mrp_subensamble_ayudas_visuales_ibfk_1` FOREIGN KEY (`subensambleid`) REFERENCES `mrp_estacion_subensamble` (`idsubensamble`),
  CONSTRAINT `mrp_subensamble_ayudas_visuales_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_subensamble_componentes`
--

DROP TABLE IF EXISTS `mrp_subensamble_componentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_subensamble_componentes` (
  `idsubcomponente` bigint NOT NULL AUTO_INCREMENT,
  `almacenid` bigint NOT NULL,
  `productoid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `cantidad` bigint NOT NULL,
  `estado` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idsubcomponente`),
  KEY `almacenid` (`almacenid`),
  KEY `productoid` (`productoid`),
  KEY `subensambleid` (`subensambleid`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `mrp_subensamble_componentes_ibfk_1` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_subensamble_componentes_ibfk_2` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `mrp_subensamble_componentes_ibfk_3` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `mrp_subensamble_componentes_ibfk_4` FOREIGN KEY (`subensambleid`) REFERENCES `mrp_estacion_subensamble` (`idsubensamble`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_subensamble_especificaciones`
--

DROP TABLE IF EXISTS `mrp_subensamble_especificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_subensamble_especificaciones` (
  `idespecificacionsubensamble` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `especificacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `asignado` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:NO 1:SI',
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idespecificacionsubensamble`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_subensamble_especificaciones_criticas`
--

DROP TABLE IF EXISTS `mrp_subensamble_especificaciones_criticas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_subensamble_especificaciones_criticas` (
  `idespecificacionsubensamble` bigint NOT NULL AUTO_INCREMENT,
  `productoid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `especificacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idespecificacionsubensamble`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_subensamble_herramientas`
--

DROP TABLE IF EXISTS `mrp_subensamble_herramientas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_subensamble_herramientas` (
  `idsubherramienta` bigint NOT NULL AUTO_INCREMENT,
  `almacenid` bigint NOT NULL,
  `productoid` bigint NOT NULL,
  `subensambleid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `cantidad` bigint NOT NULL,
  `estado` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`idsubherramienta`),
  KEY `almacenid` (`almacenid`),
  KEY `productoid` (`productoid`),
  KEY `subensambleid` (`subensambleid`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `mrp_subensamble_herramientas_ibfk_1` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `mrp_subensamble_herramientas_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_subensamble_herramientas_ibfk_3` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_unidades_fuera_linea`
--

DROP TABLE IF EXISTS `mrp_unidades_fuera_linea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_unidades_fuera_linea` (
  `idfuera` bigint NOT NULL AUTO_INCREMENT,
  `accionid` bigint DEFAULT NULL,
  `productoid` bigint DEFAULT NULL,
  `estacionid` bigint DEFAULT NULL,
  `idordengeneral` bigint DEFAULT NULL,
  `unidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_salida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_salida` bigint DEFAULT NULL,
  `fecha_reincorporacion` datetime DEFAULT NULL,
  `usuario_reincorporacion` bigint DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '\r\n    1=Fuera de linea,\r\n    2=Reincorporada,\r\n    3=Cancelada\r\n  ',
  PRIMARY KEY (`idfuera`),
  KEY `accionid` (`accionid`),
  KEY `productoid` (`productoid`),
  KEY `estacionid` (`estacionid`),
  KEY `idordengeneral` (`idordengeneral`),
  CONSTRAINT `mrp_unidades_fuera_linea_ibfk_1` FOREIGN KEY (`accionid`) REFERENCES `mrp_acciones_produccion` (`idaccion`),
  CONSTRAINT `mrp_unidades_fuera_linea_ibfk_2` FOREIGN KEY (`productoid`) REFERENCES `mrp_productos` (`idproducto`),
  CONSTRAINT `mrp_unidades_fuera_linea_ibfk_3` FOREIGN KEY (`estacionid`) REFERENCES `mrp_estacion` (`idestacion`),
  CONSTRAINT `mrp_unidades_fuera_linea_ibfk_4` FOREIGN KEY (`idordengeneral`) REFERENCES `mrp_ordenes_trabajo` (`idorden`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_unidades_terminadas`
--

DROP TABLE IF EXISTS `mrp_unidades_terminadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_unidades_terminadas` (
  `idunidad` bigint NOT NULL AUTO_INCREMENT,
  `clave` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `num_unidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `planeacionid` bigint NOT NULL,
  `plantaid` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1=activo 0=Eliminado',
  PRIMARY KEY (`idunidad`),
  KEY `planeacionid` (`planeacionid`),
  KEY `plantaid` (`plantaid`),
  CONSTRAINT `mrp_unidades_terminadas_ibfk_1` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`),
  CONSTRAINT `mrp_unidades_terminadas_ibfk_2` FOREIGN KEY (`planeacionid`) REFERENCES `mrp_planeacion` (`idplaneacion`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mrp_vin_asignaciones`
--

DROP TABLE IF EXISTS `mrp_vin_asignaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mrp_vin_asignaciones` (
  `idasignacion` bigint NOT NULL AUTO_INCREMENT,
  `orden_trabajo_id` bigint NOT NULL,
  `num_unidad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_serie_id` bigint NOT NULL,
  `numero_motor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vin_origen` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_transmision` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` bigint NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=Registrado/activo 0=Eliminado/baja	',
  PRIMARY KEY (`idasignacion`),
  KEY `orden_trabajo_id` (`orden_trabajo_id`),
  KEY `numero_serie_id` (`numero_serie_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `mrp_vin_asignaciones_ibfk_1` FOREIGN KEY (`numero_serie_id`) REFERENCES `wms_numeros_series` (`id_numeros_serie`),
  CONSTRAINT `mrp_vin_asignaciones_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`idusuario`),
  CONSTRAINT `mrp_vin_asignaciones_ibfk_3` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `mrp_ordenes_trabajo` (`idorden`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permisos` (
  `idpermiso` bigint NOT NULL AUTO_INCREMENT,
  `rolid` bigint NOT NULL,
  `moduloid` bigint NOT NULL,
  `r` int NOT NULL DEFAULT '0' COMMENT 'Ver',
  `w` int NOT NULL DEFAULT '0' COMMENT 'Crear',
  `u` int NOT NULL DEFAULT '0' COMMENT 'Actualizar',
  `d` int NOT NULL DEFAULT '0' COMMENT 'Eliminar',
  PRIMARY KEY (`idpermiso`),
  KEY `rolid` (`rolid`),
  KEY `moduloid` (`moduloid`),
  CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`rolid`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `permisos_ibfk_2` FOREIGN KEY (`moduloid`) REFERENCES `modulo` (`idmodulo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_cat_actividades`
--

DROP TABLE IF EXISTS `prv_cat_actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_cat_actividades` (
  `id_actividad` int NOT NULL AUTO_INCREMENT,
  `cve_actividad` varchar(30) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `estado` tinyint(1) DEFAULT '2' COMMENT '0=Eliminada 1=Inactiva 2=Activa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_actividad`),
  UNIQUE KEY `cve_actividad` (`cve_actividad`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_cat_proveedores`
--

DROP TABLE IF EXISTS `prv_cat_proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_cat_proveedores` (
  `id_proveedor` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_empresa` int NOT NULL DEFAULT '1',
  `id_planta` int DEFAULT NULL,
  `rfc` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc_activo` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (if((`deleted_at` is null),`rfc`,NULL)) STORED,
  `razon_social` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_comercial` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_tipo_persona` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'F: Persona Física, M: Persona Moral',
  `id_regimen_fiscal` int DEFAULT NULL,
  `tipo` enum('Interno','Externo') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Externo',
  `origen` enum('Nacional','Extranjero') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Nacional',
  `web` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'https://',
  `estatus_onboarding` enum('Prospecto','En Revision','Aprobado','Rechazado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Prospecto',
  `estatus_operativo` tinyint DEFAULT '0' COMMENT '0: Inactivo/Bloqueado, 1: Activo/Operativo',
  `is_retail` tinyint DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `uk_empresa_rfc_activo` (`id_empresa`,`rfc_activo`),
  KEY `fk_prv_regimen` (`id_regimen_fiscal`),
  KEY `idx_prv_rfc` (`rfc`),
  KEY `idx_prv_onboarding` (`estatus_onboarding`),
  CONSTRAINT `fk_prv_regimen` FOREIGN KEY (`id_regimen_fiscal`) REFERENCES `sat_cat_regimen_fiscal` (`id_regimen_fiscal`)
) ENGINE=InnoDB AUTO_INCREMENT=1000000204 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_cat_usuarios`
--

DROP TABLE IF EXISTS `prv_cat_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_cat_usuarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proveedor_id` int NOT NULL COMMENT 'FK a tu tabla maestra de proveedores (ej. wms_proveedores)',
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash BCRYPT/ARGON2',
  `nombre_contacto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estatus` enum('PRE_REGISTERED','INVITED','ONBOARDING','ACTIVE','SUSPENDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ACTIVE',
  `ultimo_acceso` datetime DEFAULT NULL,
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft Deletes Laravel',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'ID del admin que lo invitó o NULL si fue self-onboarding',
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_prv_usu_email` (`email`),
  KEY `idx_prv_usu_estatus` (`estatus`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credenciales de acceso para el portal de proveedores (SRM)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_choferes`
--

DROP TABLE IF EXISTS `prv_det_choferes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_choferes` (
  `id_chofer` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint NOT NULL COMMENT 'FK prv_cat_proveedores',
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_licencia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_licencia` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'A',
  `vigencia_licencia` date DEFAULT NULL,
  `telefono` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estatus_operativo` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_chofer`),
  KEY `idx_chofer_prov` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_config_financiera`
--

DROP TABLE IF EXISTS `prv_det_config_financiera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_config_financiera` (
  `id_config_financiera` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `id_condicion_pago` int DEFAULT NULL,
  `cuenta_contable` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `limite_credito` decimal(16,2) DEFAULT '0.00',
  `id_moneda_defecto` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MXN',
  `tasa_iva_default` decimal(5,2) DEFAULT '16.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_config_financiera`)
) ENGINE=InnoDB AUTO_INCREMENT=1000000182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_contactos`
--

DROP TABLE IF EXISTS `prv_det_contactos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_contactos` (
  `id_contacto` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `nombre` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `puesto` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notificar_compras` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_contacto`),
  KEY `fk_cont_prv` (`id_proveedor`),
  CONSTRAINT `fk_cont_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_cuentas_bancarias`
--

DROP TABLE IF EXISTS `prv_det_cuentas_bancarias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_cuentas_bancarias` (
  `id_cuenta_bancaria` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `id_banco` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_moneda` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `cuenta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swift_bic` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Para transferencias internacionales',
  `clabe` varchar(18) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban` varchar(34) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Estándar internacional',
  `url_pdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint DEFAULT '0',
  `estatus_aprobacion` enum('PENDIENTE','APROBADO','RECHAZADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'PENDIENTE',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cuenta_bancaria`),
  UNIQUE KEY `clabe` (`clabe`),
  KEY `fk_bank_prv` (`id_proveedor`),
  KEY `fk_bank_sat` (`id_banco`),
  CONSTRAINT `fk_bank_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`),
  CONSTRAINT `fk_bank_sat` FOREIGN KEY (`id_banco`) REFERENCES `cat_bancos` (`id_banco`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_direcciones`
--

DROP TABLE IF EXISTS `prv_det_direcciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_direcciones` (
  `id_direccion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `tipo` enum('Fiscal','Entrega','Bodega','Oficina') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_ext` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_int` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colonia` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cp` char(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_direccion`),
  KEY `fk_dir_prv` (`id_proveedor`),
  CONSTRAINT `fk_dir_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_expediente`
--

DROP TABLE IF EXISTS `prv_det_expediente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_expediente` (
  `id_documento` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `tipo_documento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_archivo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vencimiento` date DEFAULT NULL,
  `estatus_validacion` tinyint DEFAULT '0' COMMENT '0: Pendiente, 1: OK, 2: Rechazado',
  `motivo_rechazo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_documento`),
  UNIQUE KEY `idx_supplier_doc_type` (`id_proveedor`,`tipo_documento`),
  CONSTRAINT `fk_exp_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_madrina_chofer_historial`
--

DROP TABLE IF EXISTS `prv_det_madrina_chofer_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_madrina_chofer_historial` (
  `id_historial` bigint NOT NULL AUTO_INCREMENT,
  `id_madrina` bigint NOT NULL,
  `id_chofer` bigint NOT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_fin` datetime DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `idx_hist_madrina` (`id_madrina`),
  KEY `idx_hist_chofer` (`id_chofer`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_det_madrinas`
--

DROP TABLE IF EXISTS `prv_det_madrinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_det_madrinas` (
  `id_madrina` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint NOT NULL COMMENT 'FK prv_cat_proveedores',
  `numero_economico` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `placas` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `placa_caja` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_serie_vin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacidad_vehiculos` tinyint unsigned NOT NULL DEFAULT '1',
  `estatus_operativo` tinyint(1) DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_madrina`),
  KEY `idx_madrina_prov` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_rel_proveedores_actividades`
--

DROP TABLE IF EXISTS `prv_rel_proveedores_actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_rel_proveedores_actividades` (
  `id_rel_actividad` bigint NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `id_actividad` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rel_actividad`),
  UNIQUE KEY `uq_prv_actividad` (`id_proveedor`,`id_actividad`),
  KEY `id_actividad` (`id_actividad`),
  CONSTRAINT `prv_rel_proveedores_actividades_ibfk_1` FOREIGN KEY (`id_actividad`) REFERENCES `prv_cat_actividades` (`id_actividad`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=299 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_tra_evaluaciones`
--

DROP TABLE IF EXISTS `prv_tra_evaluaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_tra_evaluaciones` (
  `id_evaluacion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `periodo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score_cumplimiento` decimal(5,2) DEFAULT NULL,
  `score_calidad` decimal(5,2) DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_evaluacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_tra_incidencias`
--

DROP TABLE IF EXISTS `prv_tra_incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_tra_incidencias` (
  `id_incidencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `tipo_incidencia` enum('Calidad','Entrega Tarde','Facturacion','Otros') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gravedad` enum('Baja','Media','Alta','Critica') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario_reporta` bigint unsigned DEFAULT NULL,
  `fecha_incidencia` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prv_tra_onboarding`
--

DROP TABLE IF EXISTS `prv_tra_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prv_tra_onboarding` (
  `id_onboarding` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `paso_actual` int DEFAULT '1',
  `comentarios_revision` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `id_usuario_validador` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_onboarding`),
  KEY `fk_onb_prv` (`id_proveedor`),
  CONSTRAINT `fk_onb_prv` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rol`
--

DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rol` (
  `idrol` bigint NOT NULL AUTO_INCREMENT,
  `nombrerol` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sat_cat_forma_pago`
--

DROP TABLE IF EXISTS `sat_cat_forma_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sat_cat_forma_pago` (
  `id_forma_pago` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_bancarizado` tinyint(1) DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_forma_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sat_cat_metodo_pago`
--

DROP TABLE IF EXISTS `sat_cat_metodo_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sat_cat_metodo_pago` (
  `id_metodo_pago` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_metodo_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sat_cat_regimen_fiscal`
--

DROP TABLE IF EXISTS `sat_cat_regimen_fiscal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sat_cat_regimen_fiscal` (
  `id_regimen_fiscal` int NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aplica_fisica` tinyint(1) DEFAULT '0',
  `aplica_moral` tinyint(1) DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_regimen_fiscal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sat_cat_tipo_persona`
--

DROP TABLE IF EXISTS `sat_cat_tipo_persona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sat_cat_tipo_persona` (
  `id_tipo_persona` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tipo_persona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sat_cat_uso_cfdi`
--

DROP TABLE IF EXISTS `sat_cat_uso_cfdi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sat_cat_uso_cfdi` (
  `id_uso_cfdi` char(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aplica_fisica` tinyint(1) DEFAULT '0',
  `aplica_moral` tinyint(1) DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_uso_cfdi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `segmentos`
--

DROP TABLE IF EXISTS `segmentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segmentos` (
  `id_segmento` int NOT NULL AUTO_INCREMENT,
  `nombre_segmento` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_segmento`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `src_eventos_sourcing`
--

DROP TABLE IF EXISTS `src_eventos_sourcing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `src_eventos_sourcing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `planta_id` bigint unsigned DEFAULT NULL,
  `comprador_id` bigint unsigned NOT NULL,
  `estatus_evento` enum('ABIERTO','DICTAMEN','ADJUDICADO','CANCELADO') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'ABIERTO',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`),
  KEY `idx_src_evento_status` (`estatus_evento`),
  KEY `idx_src_evento_comprador` (`comprador_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sys_notification_distribution`
--

DROP TABLE IF EXISTS `sys_notification_distribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_notification_distribution` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Ej: supplier_ready, po_received',
  `rolid` bigint NOT NULL COMMENT 'FK a tabla rol',
  `plantaid` bigint unsigned DEFAULT NULL COMMENT 'NULL significa que aplica a todas las plantas (Global)',
  `is_active` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dist_rol` (`rolid`),
  KEY `idx_event_lookup` (`event_key`,`plantaid`),
  CONSTRAINT `fk_dist_rol` FOREIGN KEY (`rolid`) REFERENCES `rol` (`idrol`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipo_unidades`
--

DROP TABLE IF EXISTS `tipo_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_unidades` (
  `id_tipo_unidad` int NOT NULL AUTO_INCREMENT,
  `id_segmento` int NOT NULL,
  `nombre_tipo_unidad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_tipo_unidad`),
  KEY `fk_tipo_unidad_segmento` (`id_segmento`),
  CONSTRAINT `fk_tipo_unidad_segmento` FOREIGN KEY (`id_segmento`) REFERENCES `segmentos` (`id_segmento`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `idusuario` bigint NOT NULL AUTO_INCREMENT,
  `numcolaborador` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `nombres` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `apellidos` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `telefono` bigint NOT NULL,
  `email_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `password` varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `nit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `nombrefiscal` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `direccionfiscal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci DEFAULT NULL,
  `rolid` bigint NOT NULL,
  `plantaid` bigint NOT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` int NOT NULL DEFAULT '1',
  `cambio_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:NO 1:SI',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL DEFAULT 'avatar_default.svg',
  `avatar_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL DEFAULT 'avatar_default.svg',
  `avatar_seed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `avatar_gender` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `avatar_options` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  `avatar_updated_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_swedish_ci NOT NULL,
  PRIMARY KEY (`idusuario`),
  KEY `rolid` (`rolid`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rolid`) REFERENCES `rol` (`idrol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `w_ot_kpi_base`
--

DROP TABLE IF EXISTS `w_ot_kpi_base`;
/*!50001 DROP VIEW IF EXISTS `w_ot_kpi_base`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `w_ot_kpi_base` AS SELECT 
 1 AS `idorden`,
 1 AS `num_sub_orden`,
 1 AS `planeacionid`,
 1 AS `num_orden`,
 1 AS `productoid`,
 1 AS `supervisorid`,
 1 AS `prioridad`,
 1 AS `cantidad_planeada`,
 1 AS `fecha_requerida`,
 1 AS `fecha_inicio_planeada`,
 1 AS `fecha_fin_planeada`,
 1 AS `estacionid`,
 1 AS `cve_estacion`,
 1 AS `nombre_estacion`,
 1 AS `proceso`,
 1 AS `orden_estacion`,
 1 AS `encargado_id`,
 1 AS `encargado_nombre`,
 1 AS `ayudante_id`,
 1 AS `ayudante_nombre`,
 1 AS `estandar_min`,
 1 AS `fecha_inicio_real`,
 1 AS `fecha_fin_real`,
 1 AS `duracion_real_min`,
 1 AS `estatus`,
 1 AS `calidad`,
 1 AS `cerrada`,
 1 AS `en_tiempo`,
 1 AS `eficiencia_pct_base`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wms_almacenes`
--

DROP TABLE IF EXISTS `wms_almacenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_almacenes` (
  `idalmacen` bigint NOT NULL AUTO_INCREMENT,
  `cve_almacen` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `listaprecioid` bigint NOT NULL,
  `plantaid` bigint DEFAULT NULL,
  `cve_entrada` int NOT NULL,
  `cve_salida` int NOT NULL,
  `descripcion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `direccion` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `encargado` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correo` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idalmacen`),
  KEY `plantaid_FK_almacenes` (`plantaid`),
  CONSTRAINT `plantaid_FK_almacenes` FOREIGN KEY (`plantaid`) REFERENCES `mrp_planta` (`idplanta`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_claves_alternas`
--

DROP TABLE IF EXISTS `wms_claves_alternas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_claves_alternas` (
  `idclavealterna` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `cve_alterna` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'C=Cliente\r\nP=proveedor\r\nI=Interna',
  PRIMARY KEY (`idclavealterna`),
  KEY `inventarioid_FK_inventario` (`inventarioid`),
  CONSTRAINT `inventarioid_FK_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_claves_productos_sustitutos`
--

DROP TABLE IF EXISTS `wms_claves_productos_sustitutos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_claves_productos_sustitutos` (
  `id_clave_lista` bigint NOT NULL AUTO_INCREMENT,
  `nombre_lista` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_clave_lista`),
  UNIQUE KEY `uq_nombre_lista` (`nombre_lista`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_claves_productos_sustitutos_det`
--

DROP TABLE IF EXISTS `wms_claves_productos_sustitutos_det`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_claves_productos_sustitutos_det` (
  `id_detalle` bigint NOT NULL AUTO_INCREMENT,
  `id_clave_lista` bigint NOT NULL,
  `idinventario` bigint NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detalle`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_conceptos_mov`
--

DROP TABLE IF EXISTS `wms_conceptos_mov`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_conceptos_mov` (
  `idconcepmov` bigint NOT NULL AUTO_INCREMENT,
  `cve_concep_mov` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cpn` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'C=cliente\r\nP=proveedor\r\nN=ninguno',
  `tipo_movimiento` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'E=Entrada\r\nS=Salida',
  `estado` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=Eliminada 1=inactiva 2=Activa',
  `signo` smallint NOT NULL COMMENT '1=entrada\r\n-1=Salida',
  PRIMARY KEY (`idconcepmov`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_descuentos`
--

DROP TABLE IF EXISTS `wms_descuentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_descuentos` (
  `iddescuento` bigint NOT NULL AUTO_INCREMENT,
  `cve_descuento` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`iddescuento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_fotos_inventario`
--

DROP TABLE IF EXISTS `wms_fotos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_fotos_inventario` (
  `idfotoinventario` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`idfotoinventario`),
  KEY `fotos_inventarioid_FK_inventario` (`inventarioid`),
  CONSTRAINT `fotos_inventarioid_FK_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=1461 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_impuestos`
--

DROP TABLE IF EXISTS `wms_impuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_impuestos` (
  `idimpuesto` bigint NOT NULL AUTO_INCREMENT,
  `cve_impuesto` int NOT NULL COMMENT 'Clave esquema',
  `descripcion` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Descripción',
  `impuesto1` double DEFAULT NULL COMMENT 'Impuesto 1',
  `imp1_aplica` int DEFAULT NULL COMMENT 'Impuesto 1 aplica a {0,1} .: 0=Exento, 1 = Precio Base',
  `impuesto2` double DEFAULT NULL COMMENT 'Impuesto 2',
  `imp2_aplica` int DEFAULT NULL COMMENT 'aplica a {0,1,2} .: 0= Excento, 1 = Precio Base , 2 =Acumulado 1',
  `impuesto3` double DEFAULT NULL COMMENT 'Impuesto 3',
  `imp3_aplica` int DEFAULT NULL COMMENT 'Impuesto 3 aplica a {0,1,2,3} .: 0= Exento, 1 = Precio Base , 2 =Acumulado 1, 2= Acumulado 3',
  `impuesto4` double DEFAULT NULL COMMENT 'Impuesto\r\n4\r\n',
  `imp4_aplica` int DEFAULT NULL COMMENT 'Impuesto 4 aplica a {0,1,3,4} .: 0= Exento, 1 = Precio Base , 2 =Acumulado 1, 2= Acumulado 3, 4=Acumulado 3',
  `impuesto5` double DEFAULT NULL COMMENT 'Impuesto 5',
  `imp5_aplica` int DEFAULT NULL COMMENT 'Impuesto 5 aplica a {0,1,2,3,4,6,7} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4',
  `impuesto6` double DEFAULT NULL COMMENT 'Impuesto 6\r\n',
  `imp6_aplica` int DEFAULT NULL COMMENT 'Impuesto 6 aplica a {0,1,2,3,4,6,7,8} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5',
  `impuesto7` double DEFAULT NULL COMMENT 'Impuesto 7',
  `imp7_aplica` int DEFAULT NULL COMMENT 'Impuesto 7 aplica a {0,1,2,3,4,6,7,8,9} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5, 9 = Acumulado6',
  `impuesto8` double DEFAULT NULL COMMENT 'Impuesto 8',
  `imp8_aplica` int DEFAULT NULL COMMENT 'Impuesto 8 aplica a {0,1,2,3,4,6,7,8,9,10} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5, 9 = Acumulado6, 10 = Acumulado7',
  `fecha_creacion` datetime DEFAULT NULL,
  `estado` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '2' COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idimpuesto`),
  UNIQUE KEY `cve_impuesto` (`cve_impuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario`
--

DROP TABLE IF EXISTS `wms_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario` (
  `idinventario` bigint NOT NULL AUTO_INCREMENT,
  `cve_articulo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notas` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lineaproductoid` bigint DEFAULT NULL,
  `serie` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'S=si\r\nN=NO',
  `unidad_salida` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unidad_empaque` decimal(10,6) NOT NULL,
  `ubicacion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `idmarca` bigint DEFAULT NULL,
  `tiempo_surtido` decimal(10,6) NOT NULL,
  `ultimo_costo` decimal(18,6) NOT NULL,
  `tipo_elemento` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'K=Kit\r\nC=Componente\r\nP=Producto\r\nS=Servicio\r\nH=Herramienta',
  `unidad_entrada` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `factor_unidades` decimal(10,6) NOT NULL,
  `lote` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'S=Si\r\nN=No',
  `pedimiento` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'S=Si\r\nN=No',
  `peso` decimal(10,6) DEFAULT NULL,
  `volumen` decimal(10,6) DEFAULT NULL,
  `stock_minimo` double DEFAULT NULL,
  `stock_maximo` double DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT NULL,
  `estado` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idinventario`),
  UNIQUE KEY `uk_cve_articulo` (`cve_articulo`),
  KEY `inv_lineaproductoid_fk_lineaprod` (`lineaproductoid`),
  KEY `id_marca_FK_inv` (`idmarca`),
  CONSTRAINT `id_marca_FK_inv` FOREIGN KEY (`idmarca`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `inv_lineaproductoid_fk_lineaprod` FOREIGN KEY (`lineaproductoid`) REFERENCES `wms_linea_producto` (`idlineaproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=14401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_fiscal`
--

DROP TABLE IF EXISTS `wms_inventario_fiscal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_fiscal` (
  `idfiscal` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint DEFAULT NULL,
  `clave_sat` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desc_sat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clave_unidad_sat` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desc_unidad_sat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clave_fraccion_sat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desc_fraccion_sat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clave_aduana_sat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desc_aduana_sat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` tinyint DEFAULT '2',
  PRIMARY KEY (`idfiscal`),
  KEY `inventarioid_BK_inventario_fiscal` (`inventarioid`),
  CONSTRAINT `inventarioid_BK_inventario_fiscal` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_impuestos`
--

DROP TABLE IF EXISTS `wms_inventario_impuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_impuestos` (
  `idinvimpuesto` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `idimpuesto` bigint NOT NULL,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`idinvimpuesto`),
  UNIQUE KEY `uk_producto_impuesto` (`inventarioid`,`idimpuesto`),
  KEY `fk_inv_imp_impuesto` (`idimpuesto`),
  CONSTRAINT `fk_inv_imp_impuesto` FOREIGN KEY (`idimpuesto`) REFERENCES `wms_impuestos` (`idimpuesto`),
  CONSTRAINT `fk_inv_imp_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_linea`
--

DROP TABLE IF EXISTS `wms_inventario_linea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_linea` (
  `id_inv_linea` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `sublineaproductoid` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint DEFAULT '2',
  PRIMARY KEY (`id_inv_linea`),
  KEY `inventarioid` (`inventarioid`),
  KEY `sublineaproductoid_FK_wms_inventario_linea` (`sublineaproductoid`),
  CONSTRAINT `sublineaproductoid_FK_wms_inventario_linea` FOREIGN KEY (`sublineaproductoid`) REFERENCES `wms_sublinea_producto` (`idsublineaproducto`),
  CONSTRAINT `wms_inventario_linea_ibfk_1` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=471 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_moneda`
--

DROP TABLE IF EXISTS `wms_inventario_moneda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_moneda` (
  `id_inv_moneda` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `idmoneda` bigint NOT NULL,
  `tipo_cambio` decimal(18,6) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint DEFAULT '2',
  PRIMARY KEY (`id_inv_moneda`),
  KEY `fk_inv_moneda_inventario` (`inventarioid`),
  KEY `fk_inv_moneda_moneda` (`idmoneda`),
  CONSTRAINT `fk_inv_moneda_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `fk_inv_moneda_moneda` FOREIGN KEY (`idmoneda`) REFERENCES `wms_moneda` (`idmoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_precios`
--

DROP TABLE IF EXISTS `wms_inventario_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_precios` (
  `id_inv_precio` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `idprecio` bigint NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `fecha_creacion` datetime DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '2',
  PRIMARY KEY (`id_inv_precio`),
  KEY `inventarioid_FK_precios` (`inventarioid`),
  KEY `idprecio_FK_inv_precios` (`idprecio`),
  CONSTRAINT `idprecio_FK_inv_precios` FOREIGN KEY (`idprecio`) REFERENCES `wms_precios` (`idprecio`),
  CONSTRAINT `inventarioid_FK_precios` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_inventario_proveedores`
--

DROP TABLE IF EXISTS `wms_inventario_proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_inventario_proveedores` (
  `id_inv_proveedores` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `id_proveedor` bigint unsigned NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_inv_proveedores`),
  KEY `inventarioid_fk_inv_prov` (`inventarioid`),
  KEY `id_proveedor_fk_inv_prov` (`id_proveedor`),
  CONSTRAINT `id_proveedor_fk_inv_prov` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`),
  CONSTRAINT `inventarioid_fk_inv_prov` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_kit_config`
--

DROP TABLE IF EXISTS `wms_kit_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_kit_config` (
  `idkitconfig` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `precio` decimal(10,5) DEFAULT '0.00000',
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estado` int DEFAULT '2',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idkitconfig`),
  UNIQUE KEY `unique_inventario` (`inventarioid`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `wms_kit_config_ibfk_1` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_kit_detalle`
--

DROP TABLE IF EXISTS `wms_kit_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_kit_detalle` (
  `idkitdetalle` bigint NOT NULL AUTO_INCREMENT,
  `idkitconfig` bigint NOT NULL,
  `producto_id` bigint NOT NULL,
  `cantidad` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `estado` tinyint NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idkitdetalle`),
  UNIQUE KEY `uk_kit_producto` (`idkitconfig`,`producto_id`),
  KEY `fk_kit_detalle_producto` (`producto_id`),
  CONSTRAINT `fk_kit_detalle_kit` FOREIGN KEY (`idkitconfig`) REFERENCES `wms_kit_config` (`idkitconfig`),
  CONSTRAINT `fk_kit_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_linea_producto`
--

DROP TABLE IF EXISTS `wms_linea_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_linea_producto` (
  `idlineaproducto` bigint NOT NULL AUTO_INCREMENT,
  `cve_linea_producto` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idlineaproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_llaves_inventario`
--

DROP TABLE IF EXISTS `wms_llaves_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_llaves_inventario` (
  `idllave` bigint NOT NULL AUTO_INCREMENT,
  `vinid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `tipo_llave` enum('principal','duplicado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almacenid` bigint NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '1' COMMENT '1=Disponible, 2=Prestada, 3=Traslado',
  `fecha_alta` datetime DEFAULT CURRENT_TIMESTAMP,
  `observaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`idllave`),
  UNIQUE KEY `uk_vin_tipo` (`vinid`,`tipo_llave`),
  KEY `inventarioid_FK_llaves_inventario` (`inventarioid`),
  KEY `almacenid_FK_llaves_inventario` (`almacenid`),
  CONSTRAINT `almacenid_FK_llaves_inventario` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `inventarioid_FK_llaves_inventario` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `vinid_FK_llaves_inventario` FOREIGN KEY (`vinid`) REFERENCES `wms_numeros_series` (`id_numeros_serie`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_llaves_movimientos`
--

DROP TABLE IF EXISTS `wms_llaves_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_llaves_movimientos` (
  `idmovimiento` bigint NOT NULL AUTO_INCREMENT,
  `llaveid` bigint NOT NULL,
  `tipo_movimiento` enum('traslado','prestamo') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referenciaid` bigint NOT NULL,
  `tipo_accion` enum('salida','recepcion','prestamo','devolucion') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almacen_origenid` bigint DEFAULT NULL,
  `almacen_destinoid` bigint DEFAULT NULL,
  `responsable` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entregado_porid` bigint DEFAULT NULL,
  `usuarioid` bigint DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_prevista_devolucion` date DEFAULT NULL,
  `observaciones` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`idmovimiento`),
  KEY `llaveid` (`llaveid`),
  KEY `idx_llaves_movimientos_entregado_porid` (`entregado_porid`),
  CONSTRAINT `wms_llaves_movimientos_ibfk_1` FOREIGN KEY (`llaveid`) REFERENCES `wms_llaves_inventario` (`idllave`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_ltpd`
--

DROP TABLE IF EXISTS `wms_ltpd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_ltpd` (
  `id_ltpd` int NOT NULL AUTO_INCREMENT COMMENT 'Número de Registro',
  `inventarioid` bigint NOT NULL,
  `lote` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Lote',
  `pedimento` varchar(21) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Pedimento',
  `almacenid` bigint DEFAULT NULL COMMENT 'Clave de almacén',
  `fecha_caducidad` date DEFAULT NULL COMMENT 'Fecha de caducidad',
  `fecha_aduana` datetime DEFAULT NULL COMMENT 'Fecha de aduana',
  `fecha_ult_mov` datetime DEFAULT NULL COMMENT 'Fecha de último movimiento',
  `nombre_aduana` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Aduana',
  `cantidad` double DEFAULT NULL COMMENT 'Cantidad {0.0 ..}',
  `cve_observacion` int DEFAULT NULL COMMENT 'Clave de observaciones',
  `ciudad` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Ciudad',
  `frontera` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Frontera',
  `fecha_produccion_lote` datetime DEFAULT NULL COMMENT 'fecha de producción',
  `gln` varchar(13) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Número de Localización Global',
  `pedimento_SAT` varchar(21) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Pedimento SAT',
  `fecha_creacion` datetime NOT NULL,
  `estado` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '2' COMMENT '	0=Eliminada 1=inactiva 2=Activa	',
  PRIMARY KEY (`id_ltpd`),
  KEY `inventarioid` (`inventarioid`),
  CONSTRAINT `inventarioid` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_marcas`
--

DROP TABLE IF EXISTS `wms_marcas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_marcas` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint NOT NULL COMMENT '	0=Eliminado, 1=Inactivo, 2=Activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_moneda`
--

DROP TABLE IF EXISTS `wms_moneda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_moneda` (
  `idmoneda` bigint NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `simbolo` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL,
  `cve_moneda` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Moneda para el SAT',
  `estado` tinyint(1) DEFAULT NULL COMMENT '	0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idmoneda`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_movimientos_almacenes`
--

DROP TABLE IF EXISTS `wms_movimientos_almacenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_movimientos_almacenes` (
  `idmovimientoalmacen` bigint NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almacen_origenid` bigint NOT NULL,
  `almacen_destinoid` bigint NOT NULL,
  `referencia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint DEFAULT '1',
  PRIMARY KEY (`idmovimientoalmacen`),
  KEY `almacen_destinoid` (`almacen_destinoid`),
  KEY `almacen_origenid` (`almacen_origenid`),
  CONSTRAINT `almacen_destinoid` FOREIGN KEY (`almacen_destinoid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `almacen_origenid` FOREIGN KEY (`almacen_origenid`) REFERENCES `wms_almacenes` (`idalmacen`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_movimientos_almacenes_detalle`
--

DROP TABLE IF EXISTS `wms_movimientos_almacenes_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_movimientos_almacenes_detalle` (
  `iddetalle` bigint NOT NULL AUTO_INCREMENT,
  `movimientoalmacenid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `cantidad` decimal(18,4) NOT NULL,
  `costo_unitario` decimal(18,4) DEFAULT '0.0000',
  PRIMARY KEY (`iddetalle`),
  KEY `movimientoalmacenid` (`movimientoalmacenid`),
  KEY `inventarioid_FK_movalmacen` (`inventarioid`),
  CONSTRAINT `inventarioid_FK_movalmacen` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `movimientoalmacenid` FOREIGN KEY (`movimientoalmacenid`) REFERENCES `wms_movimientos_almacenes` (`idmovimientoalmacen`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_movimientos_inventario`
--

DROP TABLE IF EXISTS `wms_movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_movimientos_inventario` (
  `idmovinventario` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `almacenid` bigint NOT NULL,
  `numero_movimiento` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `concepmovid` bigint NOT NULL,
  `referencia` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` double DEFAULT NULL,
  `costo_cantidad` double DEFAULT NULL,
  `precio` double DEFAULT NULL,
  `costo` double DEFAULT NULL,
  `existencia` double DEFAULT NULL,
  `signo` int DEFAULT NULL COMMENT '1=Entrada\r\n-1=Salida',
  `fecha_movimiento` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idmovinventario`),
  KEY `inventarioid` (`inventarioid`),
  KEY `almacenid` (`almacenid`),
  KEY `wms_movimientos_inventario_ibfk_3` (`concepmovid`),
  CONSTRAINT `wms_movimientos_inventario_ibfk_1` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `wms_movimientos_inventario_ibfk_2` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `wms_movimientos_inventario_ibfk_3` FOREIGN KEY (`concepmovid`) REFERENCES `wms_conceptos_mov` (`idconcepmov`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_multialmacen`
--

DROP TABLE IF EXISTS `wms_multialmacen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_multialmacen` (
  `idmultialmacen` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `almacenid` bigint NOT NULL,
  `control_almacen` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `existencia` double DEFAULT NULL,
  `stock_minimo` double DEFAULT NULL,
  `stock_maximo` double DEFAULT NULL,
  `compras_x_recibir` double DEFAULT NULL,
  `pendiente_surtir` double DEFAULT NULL,
  PRIMARY KEY (`idmultialmacen`),
  UNIQUE KEY `inventarioid` (`inventarioid`,`almacenid`),
  UNIQUE KEY `uk_inv_alm` (`inventarioid`,`almacenid`),
  KEY `inventarioid_FK_multialmacenes` (`inventarioid`),
  KEY `almacenid_FK_multialmacenes` (`almacenid`),
  CONSTRAINT `almacenid_FK_multialmacenes` FOREIGN KEY (`almacenid`) REFERENCES `wms_almacenes` (`idalmacen`),
  CONSTRAINT `inventarioid_FK_multialmacenes` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_numeros_series`
--

DROP TABLE IF EXISTS `wms_numeros_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_numeros_series` (
  `id_numeros_serie` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `almacenid` bigint NOT NULL,
  `numero_serie` varchar(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `referencia` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `costo` decimal(10,6) NOT NULL,
  `fecha` datetime NOT NULL,
  `estado` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '1= Disponible, 2=No disponible',
  `tipo_generacion` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'orden',
  PRIMARY KEY (`id_numeros_serie`),
  UNIQUE KEY `numero_serie` (`numero_serie`),
  KEY `inventarioid_bk_numeros_series` (`inventarioid`),
  KEY `almacenid_bk_numeros_serie` (`almacenid`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_precios`
--

DROP TABLE IF EXISTS `wms_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_precios` (
  `idprecio` bigint NOT NULL AUTO_INCREMENT,
  `cve_precio` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `impuestoid` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idprecio`),
  KEY `impuestoid_FK_precios` (`impuestoid`),
  CONSTRAINT `impuestoid_FK_precios` FOREIGN KEY (`impuestoid`) REFERENCES `wms_impuestos` (`idimpuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_proveedor_articulos`
--

DROP TABLE IF EXISTS `wms_proveedor_articulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_proveedor_articulos` (
  `idconvenio` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint unsigned NOT NULL,
  `idinventario` bigint NOT NULL COMMENT 'FK hacia wms_inventario',
  `precio_referencia` decimal(18,6) NOT NULL COMMENT 'Último precio negociado',
  `id_moneda` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'MXN',
  `fecha_acuerdo` date NOT NULL,
  `comentarios` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`idconvenio`),
  UNIQUE KEY `idx_unique_prov_art` (`id_proveedor`,`idinventario`),
  KEY `fk_convenio_inventario` (`idinventario`),
  CONSTRAINT `fk_conv_inv` FOREIGN KEY (`idinventario`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `fk_conv_prov` FOREIGN KEY (`id_proveedor`) REFERENCES `prv_cat_proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_recepcion`
--

DROP TABLE IF EXISTS `wms_recepcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_recepcion` (
  `idrecepcion` bigint unsigned NOT NULL AUTO_INCREMENT,
  `compraid` bigint unsigned NOT NULL,
  `folio` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_recepcion` datetime NOT NULL,
  `usuarioid` bigint unsigned NOT NULL,
  `estatus` enum('abierta','parcial','cerrada') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'abierta',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idrecepcion`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_recepcion_detalle`
--

DROP TABLE IF EXISTS `wms_recepcion_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_recepcion_detalle` (
  `iddetalle` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recepcionid` bigint unsigned NOT NULL,
  `inventarioid` bigint unsigned NOT NULL,
  `codigo_barras` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lote` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cantidad_esperada` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cantidad_recibida` decimal(12,2) NOT NULL DEFAULT '0.00',
  `escaneado` tinyint(1) DEFAULT '0',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddetalle`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_recepcion_documentos`
--

DROP TABLE IF EXISTS `wms_recepcion_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_recepcion_documentos` (
  `iddocumento` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recepcionid` bigint unsigned NOT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ruta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iddocumento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_recepcion_evidencias`
--

DROP TABLE IF EXISTS `wms_recepcion_evidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_recepcion_evidencias` (
  `idevidencia` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recepcionid` bigint unsigned NOT NULL,
  `inventarioid` bigint unsigned NOT NULL,
  `detalleid` bigint unsigned DEFAULT NULL,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo` enum('foto','documento') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'foto',
  `ruta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idevidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_sedes`
--

DROP TABLE IF EXISTS `wms_sedes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_sedes` (
  `idsede` bigint NOT NULL AUTO_INCREMENT,
  `cve_sede` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idsede`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_sublinea_producto`
--

DROP TABLE IF EXISTS `wms_sublinea_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_sublinea_producto` (
  `idsublineaproducto` bigint NOT NULL AUTO_INCREMENT,
  `lineaproductoid` bigint NOT NULL,
  `cve_sublinea_producto` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '2' COMMENT '	0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idsublineaproducto`),
  KEY `lineaproductoid_FK_wms_sublinea_producto` (`lineaproductoid`),
  CONSTRAINT `lineaproductoid_FK_wms_sublinea_producto` FOREIGN KEY (`lineaproductoid`) REFERENCES `wms_linea_producto` (`idlineaproducto`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_tipo_cambio_moneda`
--

DROP TABLE IF EXISTS `wms_tipo_cambio_moneda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_tipo_cambio_moneda` (
  `idtipocambio` bigint NOT NULL AUTO_INCREMENT,
  `monedaid` bigint NOT NULL,
  `tipo_cambio` decimal(10,6) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL COMMENT '0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idtipocambio`),
  KEY `monedaid_FK_wms_tipo_cambio` (`monedaid`),
  CONSTRAINT `monedaid_FK_wms_tipo_cambio` FOREIGN KEY (`monedaid`) REFERENCES `wms_moneda` (`idmoneda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_traslados_anomalias`
--

DROP TABLE IF EXISTS `wms_traslados_anomalias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_traslados_anomalias` (
  `idanomalia` bigint NOT NULL AUTO_INCREMENT,
  `trasladoid` bigint NOT NULL,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `vinid` bigint DEFAULT NULL,
  `inventarioid` bigint DEFAULT NULL,
  `almacen_destinoid` bigint NOT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'VIN_NO_PERTENECE',
  `usuario_id` bigint NOT NULL,
  `fecha` datetime NOT NULL,
  `atendido` tinyint(1) NOT NULL DEFAULT '0',
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`idanomalia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_traslados_llaves`
--

DROP TABLE IF EXISTS `wms_traslados_llaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_traslados_llaves` (
  `idllave_traslado` bigint NOT NULL AUTO_INCREMENT,
  `trasladoid` bigint NOT NULL,
  `iddetalle` bigint NOT NULL,
  `llaveid` bigint NOT NULL,
  `tipo_llave` enum('principal','duplicado') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entrega_llave` tinyint NOT NULL DEFAULT '0',
  `estado_llave` tinyint NOT NULL DEFAULT '1',
  `fecha_entrega` datetime DEFAULT NULL,
  `responsable_recepcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `observaciones_recepcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`idllave_traslado`),
  KEY `trasladoid` (`trasladoid`),
  KEY `iddetalle` (`iddetalle`),
  KEY `llaveid` (`llaveid`),
  CONSTRAINT `wms_traslados_llaves_ibfk_1` FOREIGN KEY (`trasladoid`) REFERENCES `wms_traslados_unidades` (`idtraslado`),
  CONSTRAINT `wms_traslados_llaves_ibfk_2` FOREIGN KEY (`iddetalle`) REFERENCES `wms_traslados_unidades_detalle` (`iddetalle`),
  CONSTRAINT `wms_traslados_llaves_ibfk_3` FOREIGN KEY (`llaveid`) REFERENCES `wms_llaves_inventario` (`idllave`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_traslados_trasladistas`
--

DROP TABLE IF EXISTS `wms_traslados_trasladistas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_traslados_trasladistas` (
  `idtrasladista` bigint NOT NULL AUTO_INCREMENT,
  `trasladoid` bigint NOT NULL,
  `nombre` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contacto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_licencia` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vigencia_licencia` date DEFAULT NULL,
  `archivo_licencia` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idtrasladista`),
  KEY `trasladoid_wms_traslados_trasladistas` (`trasladoid`),
  CONSTRAINT `trasladoid_wms_traslados_trasladistas` FOREIGN KEY (`trasladoid`) REFERENCES `wms_traslados_unidades` (`idtraslado`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_traslados_unidades`
--

DROP TABLE IF EXISTS `wms_traslados_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_traslados_unidades` (
  `idtraslado` bigint NOT NULL AUTO_INCREMENT,
  `folio` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `almacen_origenid` bigint NOT NULL,
  `almacen_destinoid` bigint NOT NULL,
  `tipo_traslado` enum('rodando','madrina','grua') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proveedorid` int DEFAULT NULL,
  `fecha_programada` date DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `usuarioid` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` tinyint DEFAULT '1' COMMENT '1 = Pendiente\r\n2 = Finalizado\r\n3 = Cancelado\r\n4 = En tránsito',
  `fecha_salida` datetime DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `usuario_salida` bigint DEFAULT NULL,
  `usuario_recepcion` bigint DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `usuario_llegada` bigint DEFAULT NULL,
  PRIMARY KEY (`idtraslado`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_traslados_unidades_detalle`
--

DROP TABLE IF EXISTS `wms_traslados_unidades_detalle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_traslados_unidades_detalle` (
  `iddetalle` bigint NOT NULL AUTO_INCREMENT,
  `trasladoid` bigint NOT NULL,
  `vinid` bigint NOT NULL,
  `inventarioid` bigint NOT NULL,
  `vin` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado_recepcion` tinyint NOT NULL DEFAULT '1' COMMENT '1 Pendiente, 2 Recibido, 3 Faltante',
  `responsable_recepcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `usuario_llegada` bigint DEFAULT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `trasladoid_wms_traslados_unidades_detalle` (`trasladoid`),
  CONSTRAINT `trasladoid_wms_traslados_unidades_detalle` FOREIGN KEY (`trasladoid`) REFERENCES `wms_traslados_unidades` (`idtraslado`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_ubicaciones`
--

DROP TABLE IF EXISTS `wms_ubicaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_ubicaciones` (
  `idubicaciones` bigint NOT NULL AUTO_INCREMENT,
  `zonaid` bigint NOT NULL,
  `pasillo` int NOT NULL,
  `seccion` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nivel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `lugar` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '0=Eliminada 1=Asignada\r\n2=Disponible',
  PRIMARY KEY (`idubicaciones`),
  UNIQUE KEY `unique_ubicacion` (`zonaid`,`lugar`),
  CONSTRAINT `zonaid_FK_wms_ubicaciones` FOREIGN KEY (`zonaid`) REFERENCES `wms_zonas` (`idzona`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_ubicaciones_asignadas`
--

DROP TABLE IF EXISTS `wms_ubicaciones_asignadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_ubicaciones_asignadas` (
  `idubicacionasignada` bigint NOT NULL AUTO_INCREMENT,
  `inventarioid` bigint NOT NULL,
  `ubicacionesid` bigint NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `cantidad` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`idubicacionasignada`),
  KEY `inventarioid_FK_ubicciones_asignadas` (`inventarioid`),
  KEY `ubicacionesid_FK_ubicaciones_asignadas` (`ubicacionesid`),
  CONSTRAINT `inventarioid_FK_ubicciones_asignadas` FOREIGN KEY (`inventarioid`) REFERENCES `wms_inventario` (`idinventario`),
  CONSTRAINT `ubicacionesid_FK_ubicaciones_asignadas` FOREIGN KEY (`ubicacionesid`) REFERENCES `wms_ubicaciones` (`idubicaciones`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wms_zonas`
--

DROP TABLE IF EXISTS `wms_zonas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_zonas` (
  `idzona` bigint NOT NULL AUTO_INCREMENT,
  `sedeid` bigint NOT NULL,
  `cve_zona` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `estado` tinyint NOT NULL DEFAULT '2' COMMENT '	0=Eliminada 1=inactiva 2=Activa',
  PRIMARY KEY (`idzona`),
  KEY `sedeid_FK_wms_zonas` (`sedeid`),
  CONSTRAINT `sedeid_FK_wms_zonas` FOREIGN KEY (`sedeid`) REFERENCES `wms_sedes` (`idsede`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `w_ot_kpi_base`
--

/*!50001 DROP VIEW IF EXISTS `w_ot_kpi_base`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u546825723_mrpuser`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `w_ot_kpi_base` AS select `ot`.`idorden` AS `idorden`,`ot`.`num_sub_orden` AS `num_sub_orden`,`pe`.`planeacionid` AS `planeacionid`,`p`.`num_orden` AS `num_orden`,`p`.`productoid` AS `productoid`,`p`.`supervisorid` AS `supervisorid`,`p`.`prioridad` AS `prioridad`,`p`.`cantidad` AS `cantidad_planeada`,`p`.`fecha_requerida` AS `fecha_requerida`,`p`.`fecha_inicio` AS `fecha_inicio_planeada`,`p`.`fecha_fin` AS `fecha_fin_planeada`,`pe`.`estacionid` AS `estacionid`,`e`.`cve_estacion` AS `cve_estacion`,`e`.`nombre_estacion` AS `nombre_estacion`,`e`.`proceso` AS `proceso`,`pe`.`orden` AS `orden_estacion`,`enc`.`usuarioid` AS `encargado_id`,concat(`uenc`.`nombres`,' ',`uenc`.`apellidos`) AS `encargado_nombre`,`ayu`.`usuarioid` AS `ayudante_id`,concat(`uayu`.`nombres`,' ',`uayu`.`apellidos`) AS `ayudante_nombre`,cast(nullif(`e`.`estandar`,'') as decimal(12,2)) AS `estandar_min`,nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00') AS `fecha_inicio_real`,nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00') AS `fecha_fin_real`,(case when ((nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00') is not null) and (nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00') is not null)) then (timestampdiff(SECOND,nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00'),nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00')) / 60.0) else NULL end) AS `duracion_real_min`,`ot`.`estatus` AS `estatus`,`ot`.`calidad` AS `calidad`,(case when (nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00') is not null) then 1 else 0 end) AS `cerrada`,(case when ((cast(nullif(`e`.`estandar`,'') as decimal(12,2)) is not null) and (cast(nullif(`e`.`estandar`,'') as decimal(12,2)) > 0) and (nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00') is not null) and (nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00') is not null) and ((timestampdiff(SECOND,nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00'),nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00')) / 60.0) <= cast(nullif(`e`.`estandar`,'') as decimal(12,2)))) then 1 else 0 end) AS `en_tiempo`,(case when ((cast(nullif(`e`.`estandar`,'') as decimal(12,2)) is not null) and (cast(nullif(`e`.`estandar`,'') as decimal(12,2)) > 0) and (nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00') is not null) and (nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00') is not null) and ((timestampdiff(SECOND,nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00'),nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00')) / 60.0) > 0)) then ((cast(nullif(`e`.`estandar`,'') as decimal(12,2)) / (timestampdiff(SECOND,nullif(`ot`.`fecha_inicio`,'0000-00-00 00:00:00'),nullif(`ot`.`fecha_fin`,'0000-00-00 00:00:00')) / 60.0)) * 100) else NULL end) AS `eficiencia_pct_base` from (((((((`mrp_ordenes_trabajo` `ot` join `mrp_planeacion_estacion` `pe` on((`pe`.`id_planeacion_estacion` = `ot`.`planeacion_estacionid`))) join `mrp_planeacion` `p` on((`p`.`idplaneacion` = `pe`.`planeacionid`))) join `mrp_estacion` `e` on((`e`.`idestacion` = `pe`.`estacionid`))) left join `mrp_planeacion_estacion_operador` `enc` on(((`enc`.`planeacion_estacionid` = `pe`.`id_planeacion_estacion`) and (`enc`.`rol` = 'ENCARGADO') and (`enc`.`estado` = 2)))) left join `usuarios` `uenc` on((`uenc`.`idusuario` = `enc`.`usuarioid`))) left join `mrp_planeacion_estacion_operador` `ayu` on(((`ayu`.`planeacion_estacionid` = `pe`.`id_planeacion_estacion`) and (`ayu`.`rol` = 'AYUDANTE') and (`ayu`.`estado` = 2)))) left join `usuarios` `uayu` on((`uayu`.`idusuario` = `ayu`.`usuarioid`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-25 20:55:17
