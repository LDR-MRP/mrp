-- ==============================================================================
-- SCRIPT DE MIGRACIÓN: TELEMETRÍA, CITAS PREVIAS Y PREPLANEACIONES PLANTA
-- Fecha: Noviembre 2026
-- Entorno: MySQL 8.0 (Local / Producción Hostinger)
-- ==============================================================================

-- 1. Agregar columnas en lgs_envios para telemetría y cita previa
ALTER TABLE `lgs_envios` 
  ADD COLUMN IF NOT EXISTS `madrina_con_telemetria` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_lowboy`,
  ADD COLUMN IF NOT EXISTS `km_real_telemetria` DECIMAL(10,2) NULL DEFAULT NULL AFTER `madrina_con_telemetria`,
  ADD COLUMN IF NOT EXISTS `confirmacion_cita_cliente` TINYINT(1) NOT NULL DEFAULT 0 AFTER `km_real_telemetria`,
  ADD COLUMN IF NOT EXISTS `contacto_confirmacion_cliente` VARCHAR(150) NULL DEFAULT NULL AFTER `confirmacion_cita_cliente`;

-- 2. Agregar columnas de odómetros en lgs_envios_vins
ALTER TABLE `lgs_envios_vins`
  ADD COLUMN IF NOT EXISTS `odometro_inicial` INT NULL DEFAULT NULL AFTER `recibe_nombre`,
  ADD COLUMN IF NOT EXISTS `odometro_final` INT NULL DEFAULT NULL AFTER `odometro_inicial`;

-- 3. Crear tabla cabecera de Preplaneaciones de Planta
CREATE TABLE IF NOT EXISTS `lgs_preplaneaciones` (
  `id_preplaneacion` INT NOT NULL AUTO_INCREMENT,
  `folio` VARCHAR(30) NOT NULL,
  `id_planta` INT NOT NULL,
  `fecha_tentativa_recoleccion` DATETIME DEFAULT NULL,
  `contacto_planta` VARCHAR(150) DEFAULT NULL,
  `fecha_confirmacion` DATETIME DEFAULT NULL,
  `notas_comunicacion` TEXT DEFAULT NULL,
  `id_estado` INT NOT NULL DEFAULT 1 COMMENT '1: Enviado a Planta, 2: Confirmado por Planta, 3: Convertido a Planeación, 4: Cancelado',
  `id_planeacion_generada` INT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_preplaneacion`),
  UNIQUE KEY `uq_preplaneacion_folio` (`folio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Crear tabla detalle de VINs de Preplaneación
CREATE TABLE IF NOT EXISTS `lgs_det_preplaneaciones_vins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_preplaneacion` INT NOT NULL,
  `id_unidad` INT DEFAULT NULL,
  `vin` VARCHAR(30) NOT NULL,
  `modelo` VARCHAR(100) DEFAULT NULL,
  `destino_sugerido` VARCHAR(150) DEFAULT NULL,
  `disponible_planta` TINYINT(1) DEFAULT NULL COMMENT 'NULL: Pendiente, 1: Sí disponible, 0: No disponible',
  `motivo_no_disponible` VARCHAR(255) DEFAULT NULL,
  `confirmado_by` INT DEFAULT NULL,
  `confirmado_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_preplan_id` (`id_preplaneacion`),
  CONSTRAINT `fk_det_preplaneacion` FOREIGN KEY (`id_preplaneacion`) REFERENCES `lgs_preplaneaciones` (`id_preplaneacion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Consolidación y Deduplicación de Distribuidores
-- 5.1 Reasignar sedes mal vinculadas
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = 148 WHERE `id_ubicacion` IN (141, 156, 157); -- Carrocera L Godinez
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = 53 WHERE `id_ubicacion` IN (137, 22); -- Xian Motors
UPDATE `lgs_cat_distribuidores` SET `activo` = 0 WHERE `id_distribuidor` = 18; -- CMV Tlalnepantla redundante

-- 5.2 Eliminar IDs duplicados fantasmas generados por migraciones repetidas
DELETE FROM `lgs_cat_distribuidores` 
WHERE `id_distribuidor` IN (
  135,161, 136,162, 137,163, 138,164, 139,165, 140,166, 141,167, 168, 169, 
  144,170, 145,171, 146,172, 147,173, 174, 149,175, 150,176, 177, 178, 179, 
  154,180, 155,181, 156,182, 183, 158,184, 159,185, 160,186
);

-- 5.3 Asegurar índice UNIQUE para prevenir duplicados a futuro
ALTER TABLE `lgs_cat_distribuidores` ADD UNIQUE KEY IF NOT EXISTS `uq_distribuidor_nombre` (`nombre`);

