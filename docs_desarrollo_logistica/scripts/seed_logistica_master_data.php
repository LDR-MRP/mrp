<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("Este script solo debe ejecutarse desde consola (CLI).\n");
}

echo "=== INICIANDO CONSTRUCTOR DE DATOS MAESTROS Y TARIFARIO DE LOGÍSTICA ===\n\n";

$baseDir = dirname(__DIR__);
$segmentosSqlFile = $baseDir . '/segmentos (1).sql';
$tipoUnidadesSqlFile = $baseDir . '/tipo_unidades.sql';
$modelosSqlFile = $baseDir . '/modelos.sql';
$tarifarioCsvFile = $baseDir . '/tarifario_rutas_completo chofer y madrinas.csv';

$outputSqlFile = $baseDir . '/Scripts/seed_logistica_master_data.sql';

if (!file_exists($segmentosSqlFile) || !file_exists($tipoUnidadesSqlFile) || !file_exists($modelosSqlFile) || !file_exists($tarifarioCsvFile)) {
    die("Error: Faltan archivos requeridos en el directorio raíz.\n");
}

function sanitizeText(string $str): string {
    $replacements = [
        'AlmacÃÂ©n' => 'Almacén',
        'AlmacÃ©n'  => 'Almacén',
        'Almacï¿½n' => 'Almacén',
        'QuerÃ©taro' => 'Querétaro',
        'Querï¿½taro' => 'Querétaro',
        'MichoacÃ¡n' => 'Michoacán',
        'CanciÃºn'  => 'Cancún',
        'LeÃ³n'     => 'León',
        'MÃ©xico'   => 'México',
        'AutobÃºs'  => 'Autobús',
        'TractocamiÃ³n' => 'Tractocamión',
        'CarrocerÃ­a' => 'Carrocería',
        'ExposiciÃ³n' => 'Exposición',
        'DevoluciÃ³n' => 'Devolución',
        'AlmacÃ©n 1' => 'Almacén 1'
    ];
    $str = strtr($str, $replacements);
    if (mb_check_encoding($str, 'UTF-8')) {
        $converted = @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        if ($converted && mb_check_encoding($converted, 'UTF-8') && !preg_match('/[ÃÂï¿½]/', $converted)) {
            $str = $converted;
        }
    }
    return trim($str);
}

$sqlOutput = "-- ==============================================================================\n";
$sqlOutput .= "-- SCRIPT CONSOLIDADO DE DATOS MAESTROS, TARIFARIOS Y BANDEJA DE LOGÍSTICA\n";
$sqlOutput .= "-- MRP & LOGÍSTICA - LDR SOLUTIONS\n";
$sqlOutput .= "-- FECHA: " . date('Y-m-d H:i:s') . "\n";
$sqlOutput .= "-- ==============================================================================\n\n";
$sqlOutput .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sqlOutput .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
$sqlOutput .= "SET NAMES utf8mb4;\n";
$sqlOutput .= "START TRANSACTION;\n\n";

// -----------------------------------------------------------------------------
// 0. ASEGURAR TABLAS DDL DE LOGÍSTICA Y ESTRUCTURA EN PRODUCCIÓN
// -----------------------------------------------------------------------------
$sqlOutput .= "-- -----------------------------------------------------------------------------\n";
$sqlOutput .= "-- 0. TABLAS BASE DE LOGÍSTICA Y EXTENSIÓN DE CAT_MODELOS_VIN\n";
$sqlOutput .= "-- -----------------------------------------------------------------------------\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_segmentos` (
  `id_segmento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 2,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_segmento`),
  UNIQUE KEY `uq_lgs_segmento_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_traslado` (
  `id_tipo_traslado` tinyint(4) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tipo_traslado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "INSERT INTO `lgs_cat_tipo_traslado` (`id_tipo_traslado`, `nombre`, `activo`) VALUES
(1, 'Madrina', 1),
(2, 'Chofer (Rodando)', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `activo` = 1;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_origenes` (
  `id_origen` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_origen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_destinos` (
  `id_destino` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `nombre_libre` varchar(255) DEFAULT NULL,
  `id_tipo_destino` tinyint(4) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_costos_rutas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_tipo_traslado` tinyint(4) NOT NULL,
  `id_origen` int(11) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `id_segmento` int(11) NOT NULL,
  `num_vins_min` tinyint(4) NOT NULL DEFAULT 1,
  `num_vins_max` tinyint(4) NOT NULL DEFAULT 99,
  `km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_por_km` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `precio_plano` decimal(12,2) NOT NULL DEFAULT 0.00,
  `factor` decimal(5,2) DEFAULT 1.00,
  `activo` tinyint(1) DEFAULT 2,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_costos_rutas_traslado` (`id_tipo_traslado`),
  KEY `fk_costos_rutas_origen` (`id_origen`),
  KEY `fk_costos_rutas_destino` (`id_destino`),
  KEY `fk_costos_rutas_segmento` (`id_segmento`),
  UNIQUE KEY `uq_ruta_traslado_segmento_vins` (`id_tipo_traslado`, `id_origen`, `id_destino`, `id_segmento`, `num_vins_min`, `num_vins_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_motivo_envio` (
  `id_motivo` int(11) NOT NULL AUTO_INCREMENT,
  `cve_motivo` varchar(50) DEFAULT NULL,
  `descripcion` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_motivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_destino` (
  `id_tipo_destino` int(11) NOT NULL AUTO_INCREMENT,
  `cve_destino` varchar(50) DEFAULT NULL,
  `descripcion` varchar(150) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_unidades_envios` (
  `id_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `vin` varchar(50) NOT NULL,
  `num_serie` varchar(50) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `origen` varchar(150) DEFAULT NULL,
  `destino` varchar(150) DEFAULT NULL,
  `estatus` varchar(50) DEFAULT 'disponible',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_unidad`),
  UNIQUE KEY `uq_lgs_unidades_vin` (`vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `lgs_unidades` (
  `id_lgs_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad` int(11) NOT NULL,
  `id_motivo` int(11) DEFAULT NULL,
  `id_destino` int(11) DEFAULT NULL,
  `destino_descripcion` varchar(255) DEFAULT NULL,
  `id_estado_proceso` tinyint(4) NOT NULL DEFAULT 1,
  `fecha_salida` datetime DEFAULT NULL,
  `fecha_llegada` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lgs_unidad`),
  KEY `idx_lgs_id_unidad` (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

// Asegurar columnas id_segmento y vin_base en cat_modelos_vin de forma dinámica e idempotente
$sqlOutput .= "-- Asegurar columna id_segmento en cat_modelos_vin
SET @col_seg_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cat_modelos_vin' AND COLUMN_NAME = 'id_segmento');
SET @query_seg = IF(@col_seg_exists = 0, 'ALTER TABLE `cat_modelos_vin` ADD COLUMN `id_segmento` int(11) DEFAULT NULL AFTER `id_planta`', 'SELECT 1');
PREPARE stmt_seg FROM @query_seg;
EXECUTE stmt_seg;
DEALLOCATE PREPARE stmt_seg;\n\n";

$sqlOutput .= "-- Asegurar columna vin_base en cat_modelos_vin
SET @col_vb_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cat_modelos_vin' AND COLUMN_NAME = 'vin_base');
SET @query_vb = IF(@col_vb_exists = 0, 'ALTER TABLE `cat_modelos_vin` ADD COLUMN `vin_base` varchar(30) DEFAULT NULL AFTER `id_segmento`', 'SELECT 1');
PREPARE stmt_vb FROM @query_vb;
EXECUTE stmt_vb;
DEALLOCATE PREPARE stmt_vb;\n\n";

// -----------------------------------------------------------------------------
// 1. TABLAS MAESTRAS: SEGMENTOS, TIPO_UNIDADES, MODELOS
// -----------------------------------------------------------------------------
echo "1. Procesando tablas maestras (segmentos, tipo_unidades, modelos)...\n";

$sqlOutput .= "-- -----------------------------------------------------------------------------\n";
$sqlOutput .= "-- 1. TABLAS MAESTRAS DE PRODUCTOS: SEGMENTOS, TIPO_UNIDADES Y MODELOS\n";
$sqlOutput .= "-- -----------------------------------------------------------------------------\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `segmentos` (
  `id_segmento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_segmento` varchar(50) NOT NULL,
  PRIMARY KEY (`id_segmento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "INSERT INTO `segmentos` (`id_segmento`, `nombre_segmento`) VALUES
(1, 'Heavy Duty Truck'),
(2, 'Light Duty Truck (LDT)'),
(3, 'Medium Duty Truck (MDT)'),
(4, 'Mini Truck'),
(5, 'Passengers')
ON DUPLICATE KEY UPDATE `nombre_segmento` = VALUES(`nombre_segmento`);\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `tipo_unidades` (
  `id_tipo_unidad` int(11) NOT NULL AUTO_INCREMENT,
  `id_segmento` int(11) NOT NULL,
  `nombre_tipo_unidad` varchar(100) NOT NULL,
  PRIMARY KEY (`id_tipo_unidad`),
  KEY `fk_tipo_unidad_segmento` (`id_segmento`),
  CONSTRAINT `fk_tipo_unidad_segmento` FOREIGN KEY (`id_segmento`) REFERENCES `segmentos` (`id_segmento`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

$sqlOutput .= "INSERT INTO `tipo_unidades` (`id_tipo_unidad`, `id_segmento`, `nombre_tipo_unidad`) VALUES
(1, 5, 'VIEW'),
(2, 5, 'Grand VIEW'),
(3, 5, 'HiVan'),
(4, 5, 'Tunland'),
(5, 4, 'Wonder'),
(6, 4, 'TM3'),
(7, 2, 'Miler'),
(8, 2, 'S3'),
(9, 2, 'S4'),
(10, 2, 'S5'),
(11, 2, 'S6'),
(12, 3, 'S8'),
(13, 3, 'S12'),
(14, 3, 'S13'),
(15, 3, 'S20'),
(16, 1, 'S35'),
(17, 1, 'GTL'),
(18, 1, 'S40'),
(19, 1, 'EST-A'),
(20, 1, 'Galaxy'),
(21, 1, 'EST'),
(22, 5, 'Buses'),
(23, 1, 'GALAXUS')
ON DUPLICATE KEY UPDATE `id_segmento` = VALUES(`id_segmento`), `nombre_tipo_unidad` = VALUES(`nombre_tipo_unidad`);\n\n";

$sqlOutput .= "CREATE TABLE IF NOT EXISTS `modelos` (
  `id_modelo` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_unidad` int(11) NOT NULL,
  `nombre_modelo` varchar(100) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  PRIMARY KEY (`id_modelo`),
  KEY `fk_modelos_tipo_unidad` (`id_tipo_unidad`),
  CONSTRAINT `fk_modelos_tipo_unidad` FOREIGN KEY (`id_tipo_unidad`) REFERENCES `tipo_unidades` (`id_tipo_unidad`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";

// Extraer filas de modelos.sql
$modelosContent = file_get_contents($modelosSqlFile);
if (preg_match('/INSERT INTO `modelos`.*?VALUES\s*(.*?);/s', $modelosContent, $matches)) {
    $sqlOutput .= "INSERT INTO `modelos` (`id_modelo`, `id_tipo_unidad`, `nombre_modelo`, `nombre_producto`) VALUES\n";
    $sqlOutput .= trim($matches[1]);
    $sqlOutput .= "\nON DUPLICATE KEY UPDATE `id_tipo_unidad` = VALUES(`id_tipo_unidad`), `nombre_modelo` = VALUES(`nombre_modelo`), `nombre_producto` = VALUES(`nombre_producto`);\n\n";
}

// -----------------------------------------------------------------------------
// 2. SINCRONIZACIÓN DE CAT_MODELOS_VIN Y SEGMENTOS LOGÍSTICOS
// -----------------------------------------------------------------------------
echo "2. Mapeando y sincronizando cat_modelos_vin con lgs_cat_segmentos...\n";

$sqlOutput .= "-- -----------------------------------------------------------------------------\n";
$sqlOutput .= "-- 2. SINCRONIZACIÓN DE CATÁLOGO DE MODELOS VIN Y SEGMENTOS LOGÍSTICOS\n";
$sqlOutput .= "-- -----------------------------------------------------------------------------\n\n";

$sqlOutput .= "INSERT INTO `lgs_cat_segmentos` (`id_segmento`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'LIGEROS', 'Vehículos ligeros: Miler, S3, S4, S5, S6, TM, Wonder, Tunland, HiVan, View, Grand View', 2),
(2, 'MEDIANO', 'Vehículos medianos: S8, S12, S13, S20, Chasis', 2),
(3, 'PESADO', 'Vehículos pesados: S35, S38, S40, GTL, EST-A, Galaxy, EST, Galaxus', 2),
(4, 'BUSES', 'Buses: AUV BJ6118 CNG, D9 Midibus, Beccar Urbi, Ayco Orion, Chasis Araña', 2),
(5, 'LOWBOY', 'Equipo Lowboy / Plataforma especializada', 2)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`), `activo` = 2;\n\n";

// Lista curada de modelos para cat_modelos_vin
$modelosVinData = [
    // LIGEROS (1) - LDT, Mini Truck y Passengers
    ['modelo' => 'Aumark S3', 'vin_base' => 'LVBV11', 'id_segmento' => 1],
    ['modelo' => 'Aumark S3 EV', 'vin_base' => 'LVBV12', 'id_segmento' => 1],
    ['modelo' => 'Aumark S3-E6 AMT', 'vin_base' => 'LVBV13', 'id_segmento' => 1],
    ['modelo' => 'Aumark S3-E6 MT', 'vin_base' => 'LVBV14', 'id_segmento' => 1],
    ['modelo' => 'Aumark S4', 'vin_base' => 'LVBV15', 'id_segmento' => 1],
    ['modelo' => 'Aumark S5', 'vin_base' => 'LVBV16', 'id_segmento' => 1],
    ['modelo' => 'Aumark S5-E6 AMT', 'vin_base' => 'LVBV17', 'id_segmento' => 1],
    ['modelo' => 'Aumark S5-E6 MT', 'vin_base' => 'LVBV18', 'id_segmento' => 1],
    ['modelo' => 'Aumark S6', 'vin_base' => 'LVBV19', 'id_segmento' => 1],
    ['modelo' => 'Aumark S6-E6 MT', 'vin_base' => 'LVBV20', 'id_segmento' => 1],
    ['modelo' => 'Miler 4.5T DR', 'vin_base' => 'LVBV21', 'id_segmento' => 1],
    ['modelo' => 'Miler 4.84.5T RS', 'vin_base' => 'LVBV22', 'id_segmento' => 1],
    ['modelo' => 'Miler-EV', 'vin_base' => 'LVBV23', 'id_segmento' => 1],
    ['modelo' => 'Aumark TM', 'vin_base' => 'LVBV24', 'id_segmento' => 1],
    ['modelo' => 'TM3 1.6L', 'vin_base' => 'LVBV25', 'id_segmento' => 1],
    ['modelo' => 'TM EV', 'vin_base' => 'LVBV26', 'id_segmento' => 1],
    ['modelo' => 'Wonder', 'vin_base' => 'LVBV27', 'id_segmento' => 1],
    ['modelo' => 'Wonder EV', 'vin_base' => 'LVBV28', 'id_segmento' => 1],
    ['modelo' => 'Tunland E5', 'vin_base' => 'LVBV29', 'id_segmento' => 1],
    ['modelo' => 'Tunland G7 AT', 'vin_base' => 'LVBV30', 'id_segmento' => 1],
    ['modelo' => 'Tunland G7 MT', 'vin_base' => 'LVBV31', 'id_segmento' => 1],
    ['modelo' => 'Tunland G7 MT Gasolina', 'vin_base' => 'LVBV32', 'id_segmento' => 1],
    ['modelo' => 'Tunland G9 AT', 'vin_base' => 'LVBV33', 'id_segmento' => 1],
    ['modelo' => 'Tunland V7 (MHEV)', 'vin_base' => 'LVBV34', 'id_segmento' => 1],
    ['modelo' => 'Tunland V9 (MHEV)', 'vin_base' => 'LVBV35', 'id_segmento' => 1],
    ['modelo' => 'TUNLAND EV', 'vin_base' => 'LVBV36', 'id_segmento' => 1],
    ['modelo' => 'Tunland V7 gasolina 4x2', 'vin_base' => 'LVBV37', 'id_segmento' => 1],
    ['modelo' => 'Tunland V7 gasolina 4x4', 'vin_base' => 'LVBV38', 'id_segmento' => 1],
    ['modelo' => 'View CS2 Panel', 'vin_base' => 'LVBV39', 'id_segmento' => 1],
    ['modelo' => 'View CS2 Royal', 'vin_base' => 'LVBV40', 'id_segmento' => 1],
    ['modelo' => 'View CS2 Pasajeros', 'vin_base' => 'LVBV41', 'id_segmento' => 1],
    ['modelo' => 'VIEW EV', 'vin_base' => 'LVBV42', 'id_segmento' => 1],
    ['modelo' => 'VIEW Grand AT Panel', 'vin_base' => 'LVBV43', 'id_segmento' => 1],
    ['modelo' => 'HiVan Pasajeros', 'vin_base' => 'LVBV44', 'id_segmento' => 1],
    ['modelo' => 'HiVan Panel', 'vin_base' => 'LVBV45', 'id_segmento' => 1],
    ['modelo' => 'HiVan-EV', 'vin_base' => 'LVBV46', 'id_segmento' => 1],
    ['modelo' => 'EV-Hivan Pro', 'vin_base' => 'LVBV47', 'id_segmento' => 1],
    ['modelo' => 'Toano Panel', 'vin_base' => 'LVBV48', 'id_segmento' => 1],
    ['modelo' => 'Toano Pasajero', 'vin_base' => 'LVBV49', 'id_segmento' => 1],

    // MEDIANO (2) - MDT (S8, S12, S13, S20)
    ['modelo' => 'Aumark S8', 'vin_base' => 'LVBV50', 'id_segmento' => 2],
    ['modelo' => 'Aumark S8 (R19.5)', 'vin_base' => 'LVBV51', 'id_segmento' => 2],
    ['modelo' => 'Aumark S8-E6 AMT', 'vin_base' => 'LVBV52', 'id_segmento' => 2],
    ['modelo' => 'Aumark S12-2402', 'vin_base' => 'LVBV53', 'id_segmento' => 2],
    ['modelo' => 'Aumark S12-EV', 'vin_base' => 'LVBV54', 'id_segmento' => 2],
    ['modelo' => 'Aumark S12-E6', 'vin_base' => 'LVBV55', 'id_segmento' => 2],
    ['modelo' => 'Aumark S13', 'vin_base' => 'LVBV56', 'id_segmento' => 2],
    ['modelo' => 'Aumark S20', 'vin_base' => 'LVBV57', 'id_segmento' => 2],

    // PESADO (3) - HDT (S35, S38, S40, GTL, EST-A, Galaxy, Galaxus)
    ['modelo' => 'Aumark S35', 'vin_base' => 'LVBV60', 'id_segmento' => 3],
    ['modelo' => 'EST S38 AMT', 'vin_base' => 'LVBV61', 'id_segmento' => 3],
    ['modelo' => 'EST S40', 'vin_base' => 'LVBV62', 'id_segmento' => 3],
    ['modelo' => 'EST-A 2853-(CNG)', 'vin_base' => 'LVBV63', 'id_segmento' => 3],
    ['modelo' => 'EST-A 6x2', 'vin_base' => 'LVBV64', 'id_segmento' => 3],
    ['modelo' => 'EST-A 6x4', 'vin_base' => 'LVBV65', 'id_segmento' => 3],
    ['modelo' => 'EST-A 6x4 560', 'vin_base' => 'LVBV66', 'id_segmento' => 3],
    ['modelo' => 'EST-A / 3246 Rel. 3.08', 'vin_base' => 'LVBV67', 'id_segmento' => 3],
    ['modelo' => 'EST-A / 3246 Rel. 3.36', 'vin_base' => 'LVBV68', 'id_segmento' => 3],
    ['modelo' => 'Galaxy / 3256 / Rel. 2.71', 'vin_base' => 'LVBV69', 'id_segmento' => 3],
    ['modelo' => 'Galaxy / 3256 / Rel. 3.08', 'vin_base' => 'LVBV70', 'id_segmento' => 3],
    ['modelo' => 'Galaxy / 3256 / Rel. 3.36', 'vin_base' => 'LVBV71', 'id_segmento' => 3],
    ['modelo' => 'Galaxy / 3256 / Rel. 3.70', 'vin_base' => 'LVBV72', 'id_segmento' => 3],
    ['modelo' => 'Galaxus / Rel. 4.10', 'vin_base' => 'LVBV73', 'id_segmento' => 3],
    ['modelo' => 'Galaxus / Rel. 3.70', 'vin_base' => 'LVBV74', 'id_segmento' => 3],
    ['modelo' => 'Galaxus / Rel. 3.36', 'vin_base' => 'LVBV75', 'id_segmento' => 3],
    ['modelo' => 'GTL / 2491 / Rel. 3.08', 'vin_base' => 'LVBV76', 'id_segmento' => 3],

    // BUSES (4) - Autobuses, Carrozados, Arañas y Midibus
    ['modelo' => 'D9-BECCAR URBI G2', 'vin_base' => 'LVBV80', 'id_segmento' => 4],
    ['modelo' => 'AUV BJ6118 Chasis CNG', 'vin_base' => 'LVBV81', 'id_segmento' => 4],
    ['modelo' => 'FOTON D9 Midibus', 'vin_base' => 'LVBV82', 'id_segmento' => 4],
    ['modelo' => 'Aumark S10 Bus', 'vin_base' => 'LVBV83', 'id_segmento' => 4],
    ['modelo' => 'AYCO ORION FT Bus', 'vin_base' => 'LVBV84', 'id_segmento' => 4],

    // LOWBOY (5) - Equipo Sobredimensionado / Plataforma
    ['modelo' => 'Equipo Especial Lowboy', 'vin_base' => 'LVBV90', 'id_segmento' => 5],
    ['modelo' => 'Chasis Sobredimensionado Lowboy', 'vin_base' => 'LVBV91', 'id_segmento' => 5],
];

foreach ($modelosVinData as $m) {
    $mName = addslashes($m['modelo']);
    $vBase = addslashes($m['vin_base']);
    $idSeg = (int)$m['id_segmento'];

    $sqlOutput .= "INSERT INTO `cat_modelos_vin` (`modelo`, `id_fabricante`, `id_tipo_vehiculo`, `peso_bruto_kg`, `id_tipo_motor`, `potencia_hp`, `distancia_ejes`, `id_cat_anio_vin`, `id_planta`, `id_segmento`, `vin_base`, `fecha_creacion`, `estado`)
VALUES ('{$mName}', 1, 1, 12000.00, 1, 350, 4500.00, 1, 1, {$idSeg}, '{$vBase}', NOW(), 2)
ON DUPLICATE KEY UPDATE `id_segmento` = {$idSeg}, `vin_base` = '{$vBase}', `estado` = 2;\n";
}
$sqlOutput .= "\n";

// -----------------------------------------------------------------------------
// 3. TARIFARIO DE RUTAS (ORIGENES, DESTINOS, TARIFAS CHOFER Y MADRINA)
// -----------------------------------------------------------------------------
echo "3. Procesando tarifario_rutas_completo chofer y madrinas.csv...\n";

$sqlOutput .= "-- -----------------------------------------------------------------------------\n";
$sqlOutput .= "-- 3. CATÁLOGOS DE ORÍGENES, DESTINOS Y TARIFARIO DE COSTOS DE RUTAS\n";
$sqlOutput .= "-- -----------------------------------------------------------------------------\n\n";

$csvHandle = fopen($tarifarioCsvFile, 'r');
if (!$csvHandle) {
    die("Error abriendo el CSV de tarifas.\n");
}

$origenesMap = [];
$destinosMap = [];
$tarifasFilas = [];

$segmentoColMap = [
    'LIGEROS' => 1,
    'MEDIANO' => 2,
    'PESADO' => 3,
    'BUSES' => 4,
    'LOWBOY' => 5
];

$lineNumber = 0;
$inDesglose = false;

while (($row = fgetcsv($csvHandle, 0, ',')) !== false) {
    $lineNumber++;
    if (empty($row) || empty($row[0])) continue;
    $firstCol = trim($row[0]);
    if (strpos($firstCol, 'DESGLOSE DETALLADO') !== false) {
        $inDesglose = true;
        continue;
    }
    if (strpos($firstCol, '#') === 0 || $firstCol === 'MODALIDAD') {
        continue;
    }

    if (!$inDesglose) {
        // Sección 1: Resumen matricial
        // MODALIDAD, ORIGEN, DESTINO, KM, LIGEROS, MEDIANO, PESADO, BUSES, LOWBOY
        if (count($row) >= 9) {
            $modalidad = sanitizeText(trim($row[0]));
            $origen = sanitizeText(trim($row[1]));
            $destino = sanitizeText(trim($row[2]));
            $km = floatval(str_replace(',', '', trim($row[3])));

            if (!empty($origen)) $origenesMap[$origen] = true;
            if (!empty($destino)) $destinosMap[$destino] = true;

            $idTipoTraslado = (stripos($modalidad, 'Chofer') !== false) ? 2 : 1;

            $costos = [
                1 => floatval(str_replace(',', '', trim($row[4]))),
                2 => floatval(str_replace(',', '', trim($row[5]))),
                3 => floatval(str_replace(',', '', trim($row[6]))),
                4 => floatval(str_replace(',', '', trim($row[7]))),
                5 => floatval(str_replace(',', '', trim($row[8]))),
            ];

            // Si es Chofer o si aún no tenemos el desglose detallado, registramos la tarifa matricial base
            foreach ($costos as $idSeg => $costoTotal) {
                $costoKm = ($km > 0) ? round($costoTotal / $km, 4) : 0;
                $precioPlano = ($km <= 0) ? $costoTotal : 0;

                $tarifasFilas[] = [
                    'tipo_traslado' => $idTipoTraslado,
                    'origen' => $origen,
                    'destino' => $destino,
                    'km' => $km,
                    'id_segmento' => $idSeg,
                    'costo_por_km' => $costoKm,
                    'precio_plano' => $precioPlano,
                    'min' => 1,
                    'max' => ($idTipoTraslado === 2) ? 1 : 15,
                    'factor' => 1.00
                ];
            }
        }
    } else {
        // Sección 2: Desglose por factores (1 a 15)
        // MODALIDAD,ORIGEN,DESTINO,KM,SEGMENTO,COSTO_POR_KM,PRECIO_PLANO,UNIDADES_MIN,UNIDADES_MAX,FACTOR_MULTIPLICADOR,PRECIO_UNITARIO_VIN,TOTAL_FLETE
        if (count($row) >= 10) {
            $modalidad = sanitizeText(trim($row[0]));
            $origen = sanitizeText(trim($row[1]));
            $destino = sanitizeText(trim($row[2]));
            $km = floatval(str_replace(',', '', trim($row[3])));
            $segName = strtoupper(trim($row[4]));
            $costoKm = floatval(str_replace(',', '', trim($row[5])));
            $precioPlano = floatval(str_replace(',', '', trim($row[6])));
            $minU = intval(trim($row[7]));
            $maxU = intval(trim($row[8]));
            $factor = floatval(str_replace(',', '', trim($row[9])));

            if (!empty($origen)) $origenesMap[$origen] = true;
            if (!empty($destino)) $destinosMap[$destino] = true;

            $idTipoTraslado = (stripos($modalidad, 'Chofer') !== false) ? 2 : 1;
            $idSeg = $segmentoColMap[$segName] ?? 1;

            $tarifasFilas[] = [
                'tipo_traslado' => $idTipoTraslado,
                'origen' => $origen,
                'destino' => $destino,
                'km' => $km,
                'id_segmento' => $idSeg,
                'costo_por_km' => $costoKm,
                'precio_plano' => $precioPlano,
                'min' => $minU,
                'max' => $maxU,
                'factor' => $factor
            ];
        }
    }
}
fclose($csvHandle);

// Limpiar nombres con caracteres corruptos previos si existieran
$sqlOutput .= "UPDATE `lgs_cat_origenes` SET `nombre` = 'Almacén Montenegro Central' WHERE `nombre` LIKE '%Montenegro%';\n";
$sqlOutput .= "UPDATE `lgs_cat_destinos` SET `nombre` = 'Almacén Montenegro Central' WHERE `nombre` LIKE '%Montenegro%';\n\n";

// Insertar orígenes
foreach (array_keys($origenesMap) as $orig) {
    $origSafe = addslashes($orig);
    $sqlOutput .= "INSERT INTO `lgs_cat_origenes` (`nombre`, `activo`)
SELECT '{$origSafe}', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_origenes` WHERE `nombre` = '{$origSafe}') LIMIT 1;\n";
}
$sqlOutput .= "\n";

// Insertar destinos
foreach (array_keys($destinosMap) as $dest) {
    $destSafe = addslashes($dest);
    $sqlOutput .= "INSERT INTO `lgs_cat_destinos` (`nombre`, `activo`)
SELECT '{$destSafe}', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE `nombre` = '{$destSafe}') LIMIT 1;\n";
}
$sqlOutput .= "\n";

// Inserción de tarifas con subqueries a origenes y destinos
echo "Generando inserts para " . count($tarifasFilas) . " registros de tarifas...\n";

// Para no saturar con miles de líneas redundantes, agrupamos y usamos subqueries seguras
$sqlOutput .= "-- Inserción de costos de rutas\n";
foreach ($tarifasFilas as $t) {
    $origSafe = addslashes($t['origen']);
    $destSafe = addslashes($t['destino']);
    $tt = (int)$t['tipo_traslado'];
    $seg = (int)$t['id_segmento'];
    $km = (float)$t['km'];
    $costoKm = (float)$t['costo_por_km'];
    $plano = (float)$t['precio_plano'];
    $min = (int)$t['min'];
    $max = (int)$t['max'];
    $f = (float)$t['factor'];

    $sqlOutput .= "INSERT INTO `lgs_costos_rutas` (`id_tipo_traslado`, `id_origen`, `id_destino`, `id_segmento`, `num_vins_min`, `num_vins_max`, `km`, `costo_por_km`, `precio_plano`, `factor`, `activo`)
SELECT {$tt}, o.id_origen, d.id_destino, {$seg}, {$min}, {$max}, {$km}, {$costoKm}, {$plano}, {$f}, 2
FROM `lgs_cat_origenes` o
JOIN `lgs_cat_destinos` d
WHERE o.nombre = '{$origSafe}' AND d.nombre = '{$destSafe}'
ON DUPLICATE KEY UPDATE `km` = VALUES(`km`), `costo_por_km` = VALUES(`costo_por_km`), `precio_plano` = VALUES(`precio_plano`), `factor` = VALUES(`factor`), `activo` = 2;\n";
}
$sqlOutput .= "\n";

// -----------------------------------------------------------------------------
// 4. BANDEJA DE ENTRADA CON INFORMACIÓN REAL (BUSES Y LOWBOY INCLUIDOS)
// -----------------------------------------------------------------------------
echo "4. Poblando la Bandeja de Entrada de Logística con información real...\n";

$sqlOutput .= "-- -----------------------------------------------------------------------------\n";
$sqlOutput .= "-- 4. BANDEJA DE ENTRADA DE LOGÍSTICA: UNIDADES REALES, BUSES Y LOWBOY\n";
$sqlOutput .= "-- -----------------------------------------------------------------------------\n\n";

$unidadesBandeja = [
    // LIGEROS & PASSENGERS
    [
        'vin' => '3FADP4BJ8RM100101',
        'num_serie' => 'SN-LDT-3001',
        'modelo' => 'Aumark S3',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'AGUASCALIENTES',
        'motivo_id' => 1, // Venta Directa
        'destino_tipo_id' => 2, // Agencia
        'estado_proceso' => 1, // Pendiente
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100102',
        'num_serie' => 'SN-LDT-3002',
        'modelo' => 'Aumark S4',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'GUANAJUATO (LEON)',
        'motivo_id' => 2, // Traslado
        'destino_tipo_id' => 1, // Distribuidor
        'estado_proceso' => 2, // En Tránsito
        'salida' => date('Y-m-d 08:30:00', strtotime('-1 day')),
        'llegada' => date('Y-m-d 16:00:00')
    ],
    [
        'vin' => '3FADP4BJ8RM100103',
        'num_serie' => 'SN-LDT-3003',
        'modelo' => 'Aumark S6-E6 MT',
        'origen' => 'Almacén Montenegro Central',
        'destino' => 'CORPORATIVO FOTON CDMX',
        'motivo_id' => 3, // Demo / Expo
        'destino_tipo_id' => 3, // Patio Central
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100104',
        'num_serie' => 'SN-PAS-4001',
        'modelo' => 'Tunland G7 AT',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'PUEBLA (ASTURCAR)',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 2,
        'salida' => date('Y-m-d 07:00:00'),
        'llegada' => date('Y-m-d 18:00:00', strtotime('+1 day'))
    ],
    [
        'vin' => '3FADP4BJ8RM100105',
        'num_serie' => 'SN-PAS-4002',
        'modelo' => 'HiVan Pasajeros',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'MONTERREY',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100106',
        'num_serie' => 'SN-PAS-4003',
        'modelo' => 'View CS2 Panel',
        'origen' => 'Planta Toluca',
        'destino' => 'QUERETARO',
        'motivo_id' => 2,
        'destino_tipo_id' => 1,
        'estado_proceso' => 3, // Entregado
        'salida' => date('Y-m-d 06:00:00', strtotime('-3 days')),
        'llegada' => date('Y-m-d 14:00:00', strtotime('-2 days'))
    ],
    [
        'vin' => '3FADP4BJ8RM100107',
        'num_serie' => 'SN-PAS-4004',
        'modelo' => 'TM3 1.6L',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'JALISCO (XIAN MOTORS)',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100108',
        'num_serie' => 'SN-PAS-4005',
        'modelo' => 'Wonder EV',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'CMV INSURGENTES',
        'motivo_id' => 3,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],

    // MEDIANOS (MDT) S8 a S20
    [
        'vin' => '3FADP4BJ8RM100201',
        'num_serie' => 'SN-MDT-5001',
        'modelo' => 'Aumark S8',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'COAHUILA (TORREON)',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 2,
        'salida' => date('Y-m-d 09:00:00'),
        'llegada' => date('Y-m-d 20:00:00', strtotime('+1 day'))
    ],
    [
        'vin' => '3FADP4BJ8RM100202',
        'num_serie' => 'SN-MDT-5002',
        'modelo' => 'Aumark S12-EV',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'SAN LUIS POTOSI',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100203',
        'num_serie' => 'SN-MDT-5003',
        'modelo' => 'Aumark S20',
        'origen' => 'Almacén Montenegro Central',
        'destino' => 'VERACRUZ',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],

    // PESADOS (HDT) EST-A, Galaxy, Galaxus, S38
    [
        'vin' => '3FADP4BJ8RM100301',
        'num_serie' => 'SN-HDT-6001',
        'modelo' => 'Galaxy / 3256 / Rel.:3.08 (Nacional)',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'MONTERREY',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 2,
        'salida' => date('Y-m-d 06:00:00'),
        'llegada' => date('Y-m-d 19:00:00')
    ],
    [
        'vin' => '3FADP4BJ8RM100302',
        'num_serie' => 'SN-HDT-6002',
        'modelo' => 'EST-A 6x4',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'CHIAPAS (TUXTLA)',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100303',
        'num_serie' => 'SN-HDT-6003',
        'modelo' => 'Galaxus / Rel.: 3.36',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'BAJA CALIFORNIA (MEXICALI)',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100304',
        'num_serie' => 'SN-HDT-6004',
        'modelo' => 'EST S38 AMT',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'CIUDAD DE MEXICO, TLAPAN',
        'motivo_id' => 1,
        'destino_tipo_id' => 2,
        'estado_proceso' => 3,
        'salida' => date('Y-m-d 08:00:00', strtotime('-2 days')),
        'llegada' => date('Y-m-d 18:00:00', strtotime('-1 day'))
    ],
    [
        'vin' => '3FADP4BJ8RM100305',
        'num_serie' => 'SN-HDT-6005',
        'modelo' => 'GTL / 2491 / Rel.:3.08',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'HIDALGO (TULA)',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],

    // SALIDAS DE BUSES
    [
        'vin' => '3FADP4BJ8RM100401',
        'num_serie' => 'SN-BUS-7001',
        'modelo' => 'AUV BJ6118 Chasis CNG',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'CMV TLANEPANTA',
        'motivo_id' => 1, // Venta Directa
        'destino_tipo_id' => 2, // Agencia
        'estado_proceso' => 1, // Pendiente
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100402',
        'num_serie' => 'SN-BUS-7002',
        'modelo' => 'D9-BECCAR URBI G2',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'GUADALAJARA (ZAPOPAN)',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 2, // En Tránsito
        'salida' => date('Y-m-d 05:00:00'),
        'llegada' => date('Y-m-d 11:30:00')
    ],
    [
        'vin' => '3FADP4BJ8RM100403',
        'num_serie' => 'SN-BUS-7003',
        'modelo' => 'FOTON D9 Midibus (Nacional)',
        'origen' => 'Planta Toluca',
        'destino' => 'PUEBLA (ECO TRUKS)',
        'motivo_id' => 3, // Demo / Expo
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100404',
        'num_serie' => 'SN-BUS-7004',
        'modelo' => 'AYCO ORION FT',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'OAXACA',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],

    // SALIDAS DE LOWBOY
    [
        'vin' => '3FADP4BJ8RM100501',
        'num_serie' => 'SN-LOW-8001',
        'modelo' => 'Galaxy / 3256 Lowboy Especial',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'BAJA CALIFORNIA SUR (LA PAZ) + COSTO DE FERRI',
        'motivo_id' => 1,
        'destino_tipo_id' => 5, // Aduana / Puerto
        'estado_proceso' => 2, // En Tránsito
        'salida' => date('Y-m-d 04:00:00', strtotime('-1 day')),
        'llegada' => date('Y-m-d 22:00:00', strtotime('+2 days'))
    ],
    [
        'vin' => '3FADP4BJ8RM100502',
        'num_serie' => 'SN-LOW-8002',
        'modelo' => 'EST-A 6x4 Lowboy Sobredimensionado',
        'origen' => 'LAGOS DE MORENO',
        'destino' => 'CHIAPAS (TAPACHULA)',
        'motivo_id' => 1,
        'destino_tipo_id' => 1,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ],
    [
        'vin' => '3FADP4BJ8RM100503',
        'num_serie' => 'SN-LOW-8003',
        'modelo' => 'Chasis Pesado Lowboy',
        'origen' => 'Almacén Montenegro Central',
        'destino' => 'Agencia Monterrey (Gonzalitos)',
        'motivo_id' => 2,
        'destino_tipo_id' => 2,
        'estado_proceso' => 1,
        'salida' => null,
        'llegada' => null
    ]
];

foreach ($unidadesBandeja as $ub) {
    $vin = addslashes($ub['vin']);
    $ns = addslashes($ub['num_serie']);
    $mod = addslashes($ub['modelo']);
    $orig = addslashes($ub['origen']);
    $dest = addslashes($ub['destino']);
    $motId = (int)$ub['motivo_id'];
    $destTipoId = (int)$ub['destino_tipo_id'];
    $estProc = (int)$ub['estado_proceso'];
    $fSalida = $ub['salida'] ? "'" . $ub['salida'] . "'" : "NULL";
    $fLlegada = $ub['llegada'] ? "'" . $ub['llegada'] . "'" : "NULL";

    $sqlOutput .= "INSERT INTO `lgs_unidades_envios` (`vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`, `created_at`)
VALUES ('{$vin}', '{$ns}', '{$mod}', '{$orig}', '{$dest}', 'disponible', NOW())
ON DUPLICATE KEY UPDATE `num_serie` = '{$ns}', `modelo` = '{$mod}', `origen` = '{$orig}', `destino` = '{$dest}';\n";

    $sqlOutput .= "INSERT INTO `lgs_unidades` (`id_unidad`, `id_motivo`, `id_destino`, `destino_descripcion`, `id_estado_proceso`, `fecha_salida`, `fecha_llegada`, `created_by`, `created_at`)
SELECT u.id_unidad, {$motId}, {$destTipoId}, '{$dest}', {$estProc}, {$fSalida}, {$fLlegada}, 1, NOW()
FROM `lgs_unidades_envios` u
WHERE u.vin = '{$vin}'
ON DUPLICATE KEY UPDATE `id_motivo` = {$motId}, `id_destino` = {$destTipoId}, `destino_descripcion` = '{$dest}', `id_estado_proceso` = {$estProc}, `fecha_salida` = {$fSalida}, `fecha_llegada` = {$fLlegada};\n";
}
$sqlOutput .= "\n";

$sqlOutput .= "COMMIT;\n";
$sqlOutput .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// Guardar archivo SQL consolidado
file_put_contents($outputSqlFile, $sqlOutput);
echo "✓ Archivo SQL consolidado generado con éxito en: {$outputSqlFile}\n";
echo "✓ Total tamaño: " . round(filesize($outputSqlFile) / 1024, 2) . " KB\n\n";
