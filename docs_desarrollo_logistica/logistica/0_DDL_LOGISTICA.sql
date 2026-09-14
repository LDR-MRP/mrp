-- ==============================================================================
-- 🚚 SCRIPT DE BASE DE DATOS DEFINITIVO — MÓDULO DE LOGÍSTICA
-- Versión: Actualizada y Consolidada (Agosto 2026)
-- ==============================================================================
-- Contiene todas las tablas del módulo de Logística:
-- 1. Catálogos Base (Traslados, Motivos, Tipos de Destino, Orígenes, Destinos, Segmentos)
-- 2. Proveedores / Trasladistas (Madrinas, Choferes, Historial)
-- 3. Bandeja de Unidades Logísticas y Entrega Interna
-- 4. Tarifario de Rutas y Precios
-- 5. Envíos, Paradas y VINs Asignados
-- 6. Planeaciones y Aprobaciones
-- 7. Despacho, Checklist Móvil, Evidencias Multimedia y Solicitudes de Patio
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────
-- 1. CATÁLOGOS BASE
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_cat_tipo_traslado` (
    `id_tipo_traslado` TINYINT AUTO_INCREMENT PRIMARY KEY,
    `nombre`           VARCHAR(100) NOT NULL,
    `activo`           TINYINT(1) DEFAULT 1,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_tipo_traslado` (`id_tipo_traslado`, `nombre`) VALUES
(1, 'Madrina'),
(2, 'Chofer (Rodando)');

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

CREATE TABLE IF NOT EXISTS `lgs_cat_origenes` (
    `id_origen`  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(150) NOT NULL UNIQUE,
    `direccion`  VARCHAR(255) NULL,
    `lat`        DECIMAL(10,7) NULL COMMENT 'Latitud GPS',
    `lng`        DECIMAL(10,7) NULL COMMENT 'Longitud GPS',
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_origenes` (`id_origen`, `nombre`) VALUES
(1, 'Planta 1'), (2, 'Planta 2'), (3, 'Planta 3'),
(4, 'Planta 4'), (5, 'Planta 5'), (6, 'Almacén Montenegro'), (7, 'Lagos de Moreno');

CREATE TABLE IF NOT EXISTS `lgs_cat_destinos` (
    `id_destino`      INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`          VARCHAR(150) NOT NULL UNIQUE,
    `nombre_libre`    VARCHAR(255) NULL,
    `id_tipo_destino` TINYINT NULL,
    `direccion`       VARCHAR(255) NULL,
    `lat`             DECIMAL(10,7) NULL,
    `lng`             DECIMAL(10,7) NULL,
    `activo`          TINYINT(1) DEFAULT 1,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_tipo_destino`) REFERENCES `lgs_cat_tipo_destino`(`id_tipo_destino`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ─────────────────────────────────────────────────────────────────
-- 2. BANDEJA DE LOGÍSTICA Y UNIDADES
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_unidades` (
    `id_lgs_unidad`       BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_unidad`           BIGINT NOT NULL COMMENT 'FK mrp_unidades_terminadas',
    `id_motivo`           INT NULL,
    `id_destino`          INT NULL COMMENT 'FK lgs_cat_tipo_destino',
    `destino_descripcion` VARCHAR(255) NULL,
    `id_estado_proceso`   TINYINT DEFAULT 1 COMMENT '1=Pendiente, 2=En Tránsito, 3=Entregado',
    `fecha_salida`        DATETIME NULL,
    `fecha_llegada`       DATETIME NULL,
    `created_by`          BIGINT UNSIGNED NULL,
    `updated_by`          BIGINT UNSIGNED NULL,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_lgs_unidad` (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_unidades_entrega_interna` (
    `id_entrega_interna` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_unidad`          BIGINT NOT NULL,
    `id_estado`          TINYINT DEFAULT 1 COMMENT '1=Solicitada, 2=Confirmada, 3=Cancelada',
    `observaciones`      TEXT NULL,
    `solicitado_by`      BIGINT UNSIGNED NULL,
    `confirmado_by`      BIGINT UNSIGNED NULL,
    `solicitado_at`      DATETIME NULL,
    `confirmado_at`      DATETIME NULL,
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_unidades_envios` (
    `id_unidad`   INT AUTO_INCREMENT PRIMARY KEY,
    `vin`         VARCHAR(50) NOT NULL UNIQUE,
    `num_serie`   VARCHAR(50) NULL,
    `modelo`      VARCHAR(100) NOT NULL,
    `color`       VARCHAR(50) DEFAULT 'Blanco',
    `origen`      VARCHAR(100) DEFAULT 'Planta 1',
    `destino`     VARCHAR(100) DEFAULT 'Aguascalientes',
    `estatus`     VARCHAR(50) DEFAULT 'DISPONIBLE',
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────
-- 3. TARIFARIO MATRICIAL DE RUTAS Y COSTOS
-- ─────────────────────────────────────────────────────────────────

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

-- ─────────────────────────────────────────────────────────────────
-- 4. CABECERA DE ENVÍOS, PARADAS Y ASIGNACIÓN DE VINS
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_envios` (
    `id_envio`                     BIGINT AUTO_INCREMENT PRIMARY KEY,
    `folio`                        VARCHAR(20) UNIQUE NOT NULL COMMENT 'EN-000001',
    `id_tipo_traslado`             TINYINT NULL,
    `id_motivo`                    INT NULL,
    `id_proveedor`                 BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    `id_origen`                    INT NULL COMMENT 'FK lgs_cat_origenes',
    `id_destino`                   BIGINT NULL COMMENT 'FK lgs_cat_destinos',
    `destino_nombre_libre`         VARCHAR(255) NULL,
    `km_total`                     DECIMAL(10,2) DEFAULT 0.00,
    `costo_total`                  DECIMAL(12,2) NULL,
    `fecha_tentativa_envio`        DATE NULL,
    `fecha_tentativa_llegada`      DATE NULL,
    `fecha_confirmada_recoleccion` DATE NULL COMMENT 'Pactada con el trasladista',
    `fecha_salida_real`            DATETIME NULL,
    `fecha_llegada_real`           DATETIME NULL,
    `observaciones`                TEXT NULL,
    `id_estado`                    TINYINT DEFAULT 1
        COMMENT '1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=Programado 6=En Tránsito 7=Entregado 8=Cancelado',
    `created_by`                   BIGINT UNSIGNED NULL,
    `updated_by`                   BIGINT UNSIGNED NULL,
    `created_at`                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`                   TIMESTAMP NULL,
    FOREIGN KEY (`id_tipo_traslado`) REFERENCES `lgs_cat_tipo_traslado`(`id_tipo_traslado`) ON DELETE SET NULL,
    FOREIGN KEY (`id_motivo`)        REFERENCES `lgs_cat_motivo_envio`(`id_motivo`) ON DELETE SET NULL,
    FOREIGN KEY (`id_origen`)        REFERENCES `lgs_cat_origenes`(`id_origen`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    `estado_unidad_fisico` VARCHAR(50) DEFAULT 'EN_PATIO' COMMENT 'EN_PATIO, EN_ENTREGAS, EN_RUTA, ENTREGADO',
    `costo_unidad`         DECIMAL(12,2) NULL,
    `fecha_entrega_real`   DATETIME NULL,
    `recibe_nombre`        VARCHAR(150) NULL,
    `id_estado`            TINYINT DEFAULT 1,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_envio_vin` (`id_envio`, `id_unidad`),
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE,
    FOREIGN KEY (`id_destino`) REFERENCES `lgs_cat_destinos`(`id_destino`) ON DELETE SET NULL,
    FOREIGN KEY (`id_parada`)  REFERENCES `lgs_envios_paradas`(`id_parada`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────
-- 5. PLANEACIONES Y APROBACIONES
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `lgs_planeaciones` (
    `id_planeacion` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `folio`         VARCHAR(20) UNIQUE NOT NULL COMMENT 'EX-000001',
    `descripcion`   VARCHAR(255) NULL,
    `km_total`      DECIMAL(10,2) NULL,
    `costo_total`   DECIMAL(12,2) NULL,
    `id_estado`     TINYINT DEFAULT 1 COMMENT '1=Creada 2=Enviada 3=Regresada 5=Aprobada',
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

-- ─────────────────────────────────────────────────────────────────
-- 6. DESPACHO, CHECKLIST TRASLADISTA, SOLICITUDES Y EVIDENCIAS
-- ─────────────────────────────────────────────────────────────────

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

CREATE TABLE IF NOT EXISTS `lgs_trasladistas_checklist` (
    `id_checklist`        INT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`            INT NOT NULL,
    `id_unidad`           INT NOT NULL,
    `tipo_checklist`      VARCHAR(50) NOT NULL COMMENT 'entrada_trasladista, salida_planta, entrega_destino',
    `vin_escaneado`       VARCHAR(50) NOT NULL,
    `usuario_registro_id` INT NOT NULL,
    `comentarios`         TEXT NULL,
    `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_chk_envio` (`id_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_checklist_evidencias` (
    `id_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
    `id_checklist` INT NOT NULL,
    `tipo_foto`    VARCHAR(50) NOT NULL COMMENT 'frente, atras, lateral_izq, lateral_der, odometro, etc.',
    `ruta_archivo` VARCHAR(255) NOT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_chk_ev` (`id_checklist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_envios_evidencias` (
    `id_envio_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`           INT NOT NULL,
    `tipo_evidencia`     VARCHAR(50) NOT NULL DEFAULT 'salida',
    `archivos_json`      LONGTEXT NULL,
    `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_envio_tipo` (`id_envio`, `tipo_evidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_evidencias` (
    `id_evidencia`   BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`       BIGINT NOT NULL,
    `id_unidad`      BIGINT NULL COMMENT 'FK unidad/VIN específico o NULL si es general del envío',
    `tipo_evidencia` TINYINT NOT NULL COMMENT '1=Salida/Patio, 2=Llegada/Destino',
    `ruta_archivo`   VARCHAR(255) NOT NULL,
    `observaciones`  TEXT NULL,
    `created_by`     BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ev_envio` (`id_envio`),
    KEY `idx_ev_unidad` (`id_unidad`),
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
