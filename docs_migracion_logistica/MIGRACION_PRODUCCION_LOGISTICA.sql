-- ==============================================================================
-- 🚚 SCRIPT MIGRACIÓN DB PRODUCCIÓN — MÓDULO LOGÍSTICA COMPLETO + SEEDERS
-- Fecha de generación: Agosto 2026
-- ==============================================================================
-- Este script es seguro e idempotente (CREATE TABLE IF NOT EXISTS, INSERT IGNORE,
-- y verificaciones dinámicas de columnas).
-- No borra ni modifica datos existentes en Producción.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. CATÁLOGOS BASE DE LOGÍSTICA
-- ─────────────────────────────────────────────────────────────────────────────

-- 1.1 Tipos de Traslado (Madrina vs Chofer Rodando)
CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_traslado` (
    `id_tipo_traslado` TINYINT AUTO_INCREMENT PRIMARY KEY,
    `nombre`           VARCHAR(100) NOT NULL,
    `activo`           TINYINT(1) DEFAULT 1,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_tipo_traslado` (`id_tipo_traslado`, `nombre`) VALUES
(1, 'Madrina'),
(2, 'Chofer (Rodando)');

-- 1.2 Motivos de Envío
CREATE TABLE IF NOT EXISTS `lgs_cat_motivo_envio` (
    `id_motivo`   INT AUTO_INCREMENT PRIMARY KEY,
    `cve_motivo`  VARCHAR(40) NOT NULL UNIQUE,
    `descripcion` VARCHAR(150) NOT NULL,
    `activo`      TINYINT(1) DEFAULT 1,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_motivo_envio` (`id_motivo`, `cve_motivo`, `descripcion`) VALUES
(1, 'ENTREGA_DIST',        'Entrega a Distribuidor'),
(2, 'TRASLADO_CARROCERIA', 'Traslado a Carrocería'),
(3, 'MARKETING',           'Marketing / Exposición'),
(4, 'DEMO',                'Unidad Demo'),
(5, 'PRUEBAS',             'Unidad de Pruebas'),
(6, 'PILOTO',              'Unidad Piloto'),
(7, 'DEVOLUCION',          'Devolución'),
(8, 'OTRO',                'Otro motivo');

-- 1.3 Tipos de Destino
CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_destino` (
    `id_tipo_destino` TINYINT AUTO_INCREMENT PRIMARY KEY,
    `cve_destino`     VARCHAR(40) NOT NULL UNIQUE,
    `descripcion`     VARCHAR(150) NOT NULL,
    `activo`          TINYINT(1) DEFAULT 1,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_tipo_destino` (`id_tipo_destino`, `cve_destino`, `descripcion`) VALUES
(1, 'DISTRIBUIDOR',  'Distribuidor / Concesionario'),
(2, 'CARROCERO',     'Carrocero / Adaptaciones'),
(3, 'CLIENTE_FINAL', 'Cliente Final'),
(4, 'ALMACEN',       'Almacén'),
(5, 'PLANTA',        'Planta'),
(6, 'OTRO',          'Otro destino');

-- 1.4 Orígenes de Despacho (Plantas y Almacenes)
CREATE TABLE IF NOT EXISTS `lgs_cat_origenes` (
    `id_origen`  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(150) NOT NULL UNIQUE,
    `direccion`  VARCHAR(255) NULL,
    `lat`        DECIMAL(10,7) NULL,
    `lng`        DECIMAL(10,7) NULL,
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_origenes` (`id_origen`, `nombre`) VALUES
(1, 'Planta 1'),
(2, 'Planta 2'),
(3, 'Planta 3'),
(4, 'Planta 4'),
(5, 'Planta 5'),
(6, 'Almacén Montenegro'),
(7, 'Lagos de Moreno');

-- 1.5 Destinos Frecuentes / Puntos de Entrega (Seeder de las 41 plazas del tarifario)
CREATE TABLE IF NOT EXISTS `lgs_cat_destinos` (
    `id_destino`      INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`          VARCHAR(150) NOT NULL UNIQUE,
    `nombre_libre`    VARCHAR(255) NULL,
    `id_tipo_destino` TINYINT NULL,
    `direccion`       VARCHAR(255) NULL,
    `lat`             DECIMAL(10,7) NULL,
    `lng`             DECIMAL(10,7) NULL,
    `activo`          TINYINT(1) DEFAULT 1,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_destinos` (`id_destino`, `nombre`, `id_tipo_destino`) VALUES
(1, 'Aguascalientes', 1),
(2, 'Baja California (Mexicali)', 1),
(3, 'Baja California Sur (La Paz)', 1),
(4, 'Chiapas (Tuxtla)', 1),
(5, 'Chiapas (Tapachula)', 1),
(6, 'Coahuila (Torreón)', 1),
(7, 'Ciudad de México, Tlalpan', 1),
(8, 'Colima', 1),
(9, 'Durango', 1),
(10, 'Guanajuato (León)', 1),
(11, 'Guerrero (Chilpancingo)', 1),
(12, 'Hidalgo (Tula)', 1),
(13, 'Planta 3 Tlajomulco', 5),
(14, 'Planta 2 Jalisco', 5),
(15, 'Jalisco (Xian Motors)', 2),
(16, 'Almacén Montenegro', 4),
(17, 'Cuernavaca', 1),
(18, 'Ecatepec', 1),
(19, 'CMV Tlalnepantla', 1),
(20, 'Michoacán (Uruapan)', 1),
(21, 'Nayarit (Tepic)', 1),
(22, 'Monterrey', 1),
(23, 'Escobedo', 1),
(24, 'CMV Insurgentes', 1),
(25, 'Oaxaca', 1),
(26, 'Puebla (Eco Trucks)', 1),
(27, 'Puebla (Asturcar)', 1),
(28, 'Querétaro', 1),
(29, 'Quintana Roo (Cancún)', 1),
(30, 'San Luis Potosí', 1),
(31, 'Los Mochis', 1),
(32, 'Sinaloa (Cd Obregón)', 1),
(33, 'Sinaloa (Culiacán)', 1),
(34, 'Sonora (Hermosillo)', 1),
(35, 'Tabasco (Villahermosa)', 1),
(36, 'Toluca', 1),
(37, 'Tamaulipas (Altamira)', 1),
(38, 'Tlaxcala', 1),
(39, 'Corporativo Foton CDMX', 4),
(40, 'Yucatán (Mérida)', 1),
(41, 'Zacatecas', 1);

-- 1.6 Segmentos de Vehículos para Tarifario
CREATE TABLE IF NOT EXISTS `lgs_cat_segmentos` (
    `id_segmento` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `activo`      TINYINT(1) DEFAULT 2,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_segmentos` (`id_segmento`, `nombre`, `descripcion`) VALUES
(1, 'Ligeros',             'Vehículos ligeros y compactos ($18/km)'),
(2, 'Medianos',            'Camiones medianos y van de carga ($20/km)'),
(3, 'Pesados',             'Tractocamiones y chasis pesados ($25/km)'),
(4, 'Autobuses',           'Buses urbanos e interurbanos ($28/km)'),
(5, 'Lowboy / Especiales', 'Maquinaria y fletes sobredimensionados ($80/km)');

-- 1.7 Actividad de Proveedor Trasladista
CREATE TABLE IF NOT EXISTS `prv_cat_actividades` (
    `id_actividad`  INT AUTO_INCREMENT PRIMARY KEY,
    `cve_actividad` VARCHAR(30) NOT NULL UNIQUE,
    `descripcion`   VARCHAR(150) NOT NULL,
    `estado`        TINYINT(1) DEFAULT 2,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `prv_cat_actividades` (`cve_actividad`, `descripcion`, `estado`) VALUES
('TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2);


-- ─────────────────────────────────────────────────────────────────────────────
-- 2. MADRINAS Y CHOFERES (PROVEEDORES / TRASLADISTAS)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `prv_det_madrinas` (
    `id_madrina`          BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor`        BIGINT NOT NULL,
    `numero_economico`    VARCHAR(50) NOT NULL,
    `placas`              VARCHAR(20) NOT NULL,
    `placa_caja`          VARCHAR(20) NULL,
    `marca`               VARCHAR(50) NULL,
    `modelo`              VARCHAR(50) NULL,
    `anio`                INT NULL,
    `color`               VARCHAR(50) NULL,
    `num_serie_vin`       VARCHAR(50) NULL,
    `capacidad_vehiculos` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estatus_operativo`   TINYINT(1) DEFAULT 1,
    `created_by`          BIGINT UNSIGNED NULL,
    `updated_by`          BIGINT UNSIGNED NULL,
    `deleted_by`          BIGINT UNSIGNED NULL,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP NULL,
    KEY `idx_madrina_prov` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prv_det_choferes` (
    `id_chofer`          BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor`       BIGINT NOT NULL,
    `nombre`             VARCHAR(100) NOT NULL,
    `apellidos`          VARCHAR(100) NOT NULL,
    `num_licencia`       VARCHAR(50) NOT NULL,
    `tipo_licencia`      VARCHAR(10) DEFAULT 'A',
    `vigencia_licencia`  DATE NULL,
    `telefono`           VARCHAR(30) NULL,
    `estatus_operativo`  TINYINT(1) DEFAULT 1,
    `created_by`         BIGINT UNSIGNED NULL,
    `updated_by`         BIGINT UNSIGNED NULL,
    `deleted_by`         BIGINT UNSIGNED NULL,
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`         TIMESTAMP NULL,
    KEY `idx_chofer_prov` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prv_det_madrina_chofer_historial` (
    `id_historial`  BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_madrina`    BIGINT NOT NULL,
    `id_chofer`     BIGINT NOT NULL,
    `fecha_inicio`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_fin`     DATETIME NULL,
    `activo`        TINYINT(1) DEFAULT 1,
    `observaciones` TEXT NULL,
    `created_by`    BIGINT UNSIGNED NULL,
    `updated_by`    BIGINT UNSIGNED NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_hist_madrina` (`id_madrina`),
    KEY `idx_hist_chofer` (`id_chofer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- 3. TARIFARIO MATRICIAL DE RUTAS Y COSTOS (Lgs_costos) + SEEDERS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_costos_rutas` (
    `id`               BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_tipo_traslado` TINYINT NOT NULL COMMENT '1=Madrina, 2=Chofer Rodando',
    `id_origen`        INT NOT NULL,
    `id_destino`       INT NOT NULL,
    `id_segmento`      INT NOT NULL,
    `num_vins_min`     INT NOT NULL DEFAULT 1,
    `num_vins_max`     INT NOT NULL DEFAULT 1,
    `km`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `costo_por_km`     DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `precio_plano`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `factor`           DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    `activo`           TINYINT NOT NULL DEFAULT 2 COMMENT '2=Activo, 0=Inactivo',
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ruta_lookup` (`id_tipo_traslado`, `id_origen`, `id_destino`, `id_segmento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.1 Seeder de Rutas y Tarifas desde Origen: Lagos de Moreno (id_origen = 7)
-- Modalidad: Chofer Rodando (id_tipo_traslado = 2)
-- Segmentos: 1=Ligeros ($18), 2=Medianos ($20), 3=Pesados ($25), 4=Buses ($28), 5=Lowboy ($80)
INSERT IGNORE INTO `lgs_costos_rutas` (`id_tipo_traslado`, `id_origen`, `id_destino`, `id_segmento`, `num_vins_min`, `num_vins_max`, `km`, `costo_por_km`, `precio_plano`, `factor`, `activo`) VALUES
-- Aguascalientes (89 km)
(2, 7, 1, 1, 1, 1, 89.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 1, 2, 1, 1, 89.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 1, 3, 1, 1, 89.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 1, 4, 1, 1, 89.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 1, 5, 1, 1, 89.00, 80.0000, 0.00, 1.0000, 2),
-- Baja California Mexicali (2231 km)
(2, 7, 2, 1, 1, 1, 2231.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 2, 2, 1, 1, 2231.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 2, 3, 1, 1, 2231.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 2, 4, 1, 1, 2231.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 2, 5, 1, 1, 2231.00, 80.0000, 0.00, 1.0000, 2),
-- Baja California Sur La Paz (1095 km)
(2, 7, 3, 1, 1, 1, 1095.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 3, 2, 1, 1, 1095.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 3, 3, 1, 1, 1095.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 3, 4, 1, 1, 1095.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 3, 5, 1, 1, 1095.00, 80.0000, 0.00, 1.0000, 2),
-- Chiapas Tuxtla (1253 km)
(2, 7, 4, 1, 1, 1, 1253.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 4, 2, 1, 1, 1253.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 4, 3, 1, 1, 1253.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 4, 4, 1, 1, 1253.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 4, 5, 1, 1, 1253.00, 80.0000, 0.00, 1.0000, 2),
-- Guanajuato León (85 km)
(2, 7, 10, 1, 1, 1, 85.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 10, 2, 1, 1, 85.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 10, 3, 1, 1, 85.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 10, 4, 1, 1, 85.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 10, 5, 1, 1, 85.00, 80.0000, 0.00, 1.0000, 2),
-- CDMX Tlalpan (445 km)
(2, 7, 7, 1, 1, 1, 445.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 7, 2, 1, 1, 445.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 7, 3, 1, 1, 445.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 7, 4, 1, 1, 445.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 7, 5, 1, 1, 445.00, 80.0000, 0.00, 1.0000, 2),
-- Monterrey (683 km)
(2, 7, 22, 1, 1, 1, 683.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 22, 2, 1, 1, 683.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 22, 3, 1, 1, 683.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 22, 4, 1, 1, 683.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 22, 5, 1, 1, 683.00, 80.0000, 0.00, 1.0000, 2),
-- Puebla (545 km)
(2, 7, 26, 1, 1, 1, 545.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 26, 2, 1, 1, 545.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 26, 3, 1, 1, 545.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 26, 4, 1, 1, 545.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 26, 5, 1, 1, 545.00, 80.0000, 0.00, 1.0000, 2),
-- Querétaro (249 km)
(2, 7, 28, 1, 1, 1, 249.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 28, 2, 1, 1, 249.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 28, 3, 1, 1, 249.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 28, 4, 1, 1, 249.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 28, 5, 1, 1, 249.00, 80.0000, 0.00, 1.0000, 2),
-- Toluca (450 km)
(2, 7, 36, 1, 1, 1, 450.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 36, 2, 1, 1, 450.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 36, 3, 1, 1, 450.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 36, 4, 1, 1, 450.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 36, 5, 1, 1, 450.00, 80.0000, 0.00, 1.0000, 2);


-- ─────────────────────────────────────────────────────────────────────────────
-- 4. CABECERA DE ENVÍOS, PARADAS Y ASIGNACIÓN DE VINS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_envios` (
    `id_envio`                     BIGINT AUTO_INCREMENT PRIMARY KEY,
    `folio`                        VARCHAR(20) UNIQUE NOT NULL COMMENT 'Folio EN-000001',
    `id_tipo_traslado`             TINYINT NULL,
    `id_motivo`                    INT NULL,
    `id_proveedor`                 BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    `id_origen`                    INT NULL COMMENT 'FK lgs_cat_origenes',
    `id_destino`                   BIGINT NULL COMMENT 'Destino principal/final',
    `destino_nombre_libre`         VARCHAR(255) NULL,
    `km_total`                     DECIMAL(10,2) DEFAULT 0.00,
    `costo_total`                  DECIMAL(12,2) NULL,
    `fecha_tentativa_envio`        DATE NULL,
    `fecha_tentativa_llegada`      DATE NULL,
    `fecha_confirmada_recoleccion` DATE NULL COMMENT 'Pactada con el trasladista para patio',
    `fecha_salida_real`            DATETIME NULL COMMENT 'Salida física de planta',
    `fecha_llegada_real`           DATETIME NULL COMMENT 'Llegada y entrega en destino',
    `observaciones`                TEXT NULL,
    `id_estado`                    TINYINT DEFAULT 1
        COMMENT '1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=Programado 6=En Tránsito 7=Entregado 8=Cancelado',
    `created_by`                   BIGINT UNSIGNED NULL,
    `updated_by`                   BIGINT UNSIGNED NULL,
    `created_at`                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`                   TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Validación de columna fecha_confirmada_recoleccion por si la tabla ya existía
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME = 'lgs_envios' AND COLUMN_NAME = 'fecha_confirmada_recoleccion' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `lgs_envios` ADD COLUMN `fecha_confirmada_recoleccion` DATE NULL AFTER `fecha_tentativa_llegada`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `lgs_envios_paradas` (
    `id_parada`            BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`             BIGINT NOT NULL,
    `orden`                TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `id_destino_cat`       BIGINT NULL,
    `destino_nombre_libre` VARCHAR(255) NULL,
    `km_tramo`             DECIMAL(10,2) DEFAULT 0.00,
    `observaciones`        TEXT NULL,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_envios_vins` (
    `id`                   BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`             BIGINT NOT NULL,
    `id_unidad`            BIGINT NOT NULL,
    `id_destino`           INT NULL,
    `id_parada`            BIGINT NULL,
    `destino_nombre_libre` VARCHAR(255) NULL,
    `id_madrina`           BIGINT NULL,
    `id_chofer`            BIGINT NULL,
    `posicion_acomodo`     TINYINT UNSIGNED NULL,
    `estado_unidad_fisico` VARCHAR(50) DEFAULT 'EN_PATIO',
    `costo_unidad`         DECIMAL(12,2) NULL,
    `fecha_entrega_real`   DATETIME NULL,
    `recibe_nombre`        VARCHAR(150) NULL,
    `id_estado`            TINYINT DEFAULT 1,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_envio_vin` (`id_envio`, `id_unidad`),
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Validación de columna estado_unidad_fisico por si la tabla ya existía
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME = 'lgs_envios_vins' AND COLUMN_NAME = 'estado_unidad_fisico' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@col_exists = 0, "ALTER TABLE `lgs_envios_vins` ADD COLUMN `estado_unidad_fisico` VARCHAR(50) DEFAULT 'EN_PATIO' AFTER `posicion_acomodo`", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ─────────────────────────────────────────────────────────────────────────────
-- 5. PLANEACIONES AGRUPADAS Y APROBACIONES
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_planeaciones` (
    `id_planeacion` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `folio`         VARCHAR(20) UNIQUE NOT NULL COMMENT 'Folio EX-000001',
    `descripcion`   VARCHAR(255) NULL,
    `km_total`      DECIMAL(10,2) NULL,
    `costo_total`   DECIMAL(12,2) NULL,
    `id_estado`     TINYINT DEFAULT 1,
    `obs_operador`  TEXT NULL,
    `obs_aprobador` TEXT NULL,
    `created_by`    BIGINT UNSIGNED NULL,
    `aprobado_by`   BIGINT UNSIGNED NULL,
    `aprobado_at`   DATETIME NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_planeaciones_envios` (
    `id`            BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_planeacion` BIGINT NOT NULL,
    `id_envio`      BIGINT NOT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plan_envio` (`id_planeacion`, `id_envio`),
    FOREIGN KEY (`id_planeacion`) REFERENCES `lgs_planeaciones`(`id_planeacion`) ON DELETE CASCADE,
    FOREIGN KEY (`id_envio`)      REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_aprobadores` (
    `id`         BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` BIGINT NOT NULL,
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
-- 6. MESA DE DESPACHO, SOLICITUDES DE PATIO, CHECKLIST Y EVIDENCIAS
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_solicitudes_entrega` (
    `id_solicitud`       INT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`           INT NOT NULL,
    `id_unidad`          INT NOT NULL,
    `orden_acomodo`      INT DEFAULT 1,
    `confirmado`         TINYINT(1) DEFAULT 0,
    `fecha_confirmacion` DATETIME NULL,
    `confirmado_by`      INT NULL,
    `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_envio_unidad` (`id_envio`, `id_unidad`),
    KEY `idx_sol_envio` (`id_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_envios_evidencias` (
    `id_envio_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`           INT NOT NULL,
    `tipo_evidencia`     VARCHAR(50) NOT NULL DEFAULT 'salida',
    `archivos_json`      LONGTEXT NULL,
    `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_envio_tipo` (`id_envio`, `tipo_evidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_trasladistas_checklist` (
    `id_checklist`        INT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`            INT NOT NULL,
    `id_unidad`           INT NOT NULL,
    `tipo_checklist`      VARCHAR(50) NOT NULL,
    `vin_escaneado`       VARCHAR(50) NOT NULL,
    `usuario_registro_id` INT NOT NULL,
    `comentarios`         TEXT NULL,
    `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_chk_envio` (`id_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_checklist_evidencias` (
    `id_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
    `id_checklist` INT NOT NULL,
    `tipo_foto`    VARCHAR(50) NOT NULL,
    `ruta_archivo` VARCHAR(255) NOT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_chk_ev` (`id_checklist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_evidencias` (
    `id_evidencia`   BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`       BIGINT NOT NULL,
    `id_unidad`      BIGINT NULL,
    `tipo_evidencia` TINYINT NOT NULL COMMENT '1: Salida, 2: Llegada',
    `ruta_archivo`   VARCHAR(255) NOT NULL,
    `observaciones`  TEXT NULL,
    `created_by`     BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
