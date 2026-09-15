-- ==============================================================================
-- 🚚 MIGRACIÓN ÉPICA 1: ESTRUCTURA DE DATOS PARA COSTOS POR MADRINA Y RUTAS LIBRES
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. CATÁLOGO UNIFICADO DE UBICACIONES
-- Reemplaza a lgs_cat_origenes y lgs_cat_destinos
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_cat_ubicaciones` (
    `id_ubicacion`    INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`          VARCHAR(150) NOT NULL UNIQUE,
    `direccion`       VARCHAR(255) NULL,
    `id_tipo_destino` TINYINT NULL COMMENT '1=Distribuidor, 2=Carrocero, 3=Cliente, 4=Almacen, 5=Planta, 6=Otro',
    `lat`             DECIMAL(10,7) NULL,
    `lng`             DECIMAL(10,7) NULL,
    `activo`          TINYINT(1) DEFAULT 1,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_tipo_destino`) REFERENCES `lgs_cat_tipo_destino`(`id_tipo_destino`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migración de orígenes a ubicaciones (tipo 5 = Planta)
INSERT IGNORE INTO `lgs_cat_ubicaciones` (`nombre`, `direccion`, `id_tipo_destino`, `lat`, `lng`, `activo`, `created_at`)
SELECT `nombre`, `direccion`, 5, `lat`, `lng`, `activo`, `created_at` FROM `lgs_cat_origenes`;

-- Migración de destinos a ubicaciones
INSERT IGNORE INTO `lgs_cat_ubicaciones` (`nombre`, `direccion`, `id_tipo_destino`, `lat`, `lng`, `activo`, `created_at`)
SELECT `nombre`, `direccion`, `id_tipo_destino`, `lat`, `lng`, `activo`, `created_at` FROM `lgs_cat_destinos`;

-- Actualizar lgs_costos_rutas para apuntar al catálogo unificado
UPDATE `lgs_costos_rutas` r
JOIN `lgs_cat_destinos` d ON r.id_destino = d.id_destino
JOIN `lgs_cat_ubicaciones` u ON d.nombre = u.nombre
SET r.id_destino = u.id_ubicacion;

UPDATE `lgs_costos_rutas` r
JOIN `lgs_cat_origenes` o ON r.id_origen = o.id_origen
JOIN `lgs_cat_ubicaciones` u ON o.nombre = u.nombre
SET r.id_origen = u.id_ubicacion;

-- -----------------------------------------------------------------------------
-- 2. RUTAS LIBRES: NODOS DE ENVÍO
-- Reemplaza a lgs_envios_paradas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_envios_nodos` (
    `id_nodo`              BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`             BIGINT NOT NULL,
    `orden`                TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Posición en la ruta (0=Origen, 1..N=Paradas)',
    `tipo_nodo`            VARCHAR(20) NOT NULL DEFAULT 'entrega' COMMENT 'origen, carga, entrega',
    `id_ubicacion`         INT NULL COMMENT 'FK lgs_cat_ubicaciones',
    `destino_nombre_libre` VARCHAR(255) NULL,
    `km_tramo_anterior`    DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Km desde el nodo anterior hasta este',
    `observaciones`        TEXT NULL,
    `fecha_estimada`       DATETIME NULL,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE,
    FOREIGN KEY (`id_ubicacion`) REFERENCES `lgs_cat_ubicaciones`(`id_ubicacion`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Asegurar tipo DATETIME en lgs_envios para fecha y hora completas
ALTER TABLE `lgs_envios` 
    MODIFY COLUMN `fecha_tentativa_envio` DATETIME NULL,
    MODIFY COLUMN `fecha_tentativa_llegada` DATETIME NULL;

-- Migrar Orígenes de los envíos existentes como Nodo 0
INSERT INTO `lgs_envios_nodos` (`id_envio`, `orden`, `id_ubicacion`, `km_tramo_anterior`)
SELECT 
    e.id_envio, 
    0 AS orden, 
    u.id_ubicacion, 
    0.00 AS km_tramo_anterior
FROM lgs_envios e
JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen
JOIN lgs_cat_ubicaciones u ON o.nombre = u.nombre;

-- Migrar Paradas de los envíos existentes
INSERT INTO `lgs_envios_nodos` (`id_envio`, `orden`, `id_ubicacion`, `destino_nombre_libre`, `km_tramo_anterior`)
SELECT 
    p.id_envio, 
    p.orden, 
    u.id_ubicacion, 
    p.destino_nombre_libre, 
    p.km_tramo
FROM lgs_envios_paradas p
LEFT JOIN lgs_cat_destinos d ON p.id_destino_cat = d.id_destino
LEFT JOIN lgs_cat_ubicaciones u ON d.nombre = u.nombre;

-- Migrar Destinos finales de los envíos si no están ya en paradas
-- Se asignará como el último nodo (orden máximo + 1)
INSERT INTO `lgs_envios_nodos` (`id_envio`, `orden`, `id_ubicacion`, `destino_nombre_libre`, `km_tramo_anterior`)
SELECT 
    e.id_envio, 
    COALESCE((SELECT MAX(orden) + 1 FROM lgs_envios_paradas WHERE id_envio = e.id_envio), 1) AS orden, 
    u.id_ubicacion, 
    e.destino_nombre_libre, 
    e.km_total - COALESCE((SELECT SUM(km_tramo) FROM lgs_envios_paradas WHERE id_envio = e.id_envio), 0) AS km_tramo_anterior
FROM lgs_envios e
LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino
LEFT JOIN lgs_cat_ubicaciones u ON d.nombre = u.nombre
WHERE e.id_destino IS NOT NULL OR e.destino_nombre_libre IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 3. ASIGNACIÓN DE VINS: SUBIDA Y BAJADA
-- Modifica lgs_envios_vins
-- -----------------------------------------------------------------------------
ALTER TABLE `lgs_envios_vins`
    ADD COLUMN `id_nodo_subida` BIGINT NULL COMMENT 'En qué nodo se sube a la madrina' AFTER `id_parada`,
    ADD COLUMN `id_nodo_bajada` BIGINT NULL COMMENT 'En qué nodo se baja de la madrina' AFTER `id_nodo_subida`;

ALTER TABLE `lgs_envios_vins`
    ADD CONSTRAINT `fk_vin_nodo_subida` FOREIGN KEY (`id_nodo_subida`) REFERENCES `lgs_envios_nodos`(`id_nodo`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_vin_nodo_bajada` FOREIGN KEY (`id_nodo_bajada`) REFERENCES `lgs_envios_nodos`(`id_nodo`) ON DELETE SET NULL;

-- Asignar el nodo 0 como subida y el nodo correspondiente como bajada
UPDATE lgs_envios_vins v
JOIN lgs_envios_nodos n_subida ON v.id_envio = n_subida.id_envio AND n_subida.orden = 0
SET v.id_nodo_subida = n_subida.id_nodo;

UPDATE lgs_envios_vins v
JOIN lgs_envios_paradas p ON v.id_parada = p.id_parada
JOIN lgs_envios_nodos n_bajada ON v.id_envio = n_bajada.id_envio AND n_bajada.orden = p.orden
SET v.id_nodo_bajada = n_bajada.id_nodo
WHERE v.id_parada IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 4. TABLA DE HISTORIAL DE COSTOS POR TRAMO (MOTOR DE COSTOS)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_envios_tramos_costos` (
    `id_tramo_costo`    BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio`          BIGINT NOT NULL,
    `id_madrina`        BIGINT NULL,
    `id_chofer`         BIGINT NULL,
    `id_nodo_origen`    BIGINT NOT NULL COMMENT 'FK lgs_envios_nodos',
    `id_nodo_destino`   BIGINT NOT NULL COMMENT 'FK lgs_envios_nodos',
    `km_tramo`          DECIMAL(10,2) NOT NULL,
    `vins_ligeros`      INT DEFAULT 0,
    `vins_medianos`     INT DEFAULT 0,
    `vins_pesados`      INT DEFAULT 0,
    `vins_especiales`   INT DEFAULT 0,
    `factor_aplicado`   DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    `costo_estimado`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tarifa_usada_id`   BIGINT NULL COMMENT 'FK a lgs_costos_rutas',
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE,
    FOREIGN KEY (`id_nodo_origen`) REFERENCES `lgs_envios_nodos`(`id_nodo`) ON DELETE CASCADE,
    FOREIGN KEY (`id_nodo_destino`) REFERENCES `lgs_envios_nodos`(`id_nodo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. AJUSTAR TABLA LGS_ENVIOS PARA NO DEPENDER DE ORIGEN/DESTINO VIEJOS
-- -----------------------------------------------------------------------------
-- Aunque dejemos las columnas viejas temporalmente para no romper todo de golpe,
-- el backend debe empezar a usar lgs_envios_nodos.
-- En un futuro se eliminarían id_origen e id_destino de lgs_envios.

SET FOREIGN_KEY_CHECKS = 1;
