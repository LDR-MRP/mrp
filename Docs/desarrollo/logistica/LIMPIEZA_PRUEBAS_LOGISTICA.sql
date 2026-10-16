-- ==============================================================================
-- SCRIPT: LIMPIEZA DE PRUEBAS OPERATIVAS DE LOGÍSTICA Y LIBERACIÓN DE VINS
-- Fecha: 15 de Octubre de 2026
-- Objetivo:
--   1. Eliminar envíos, rutas, paradas, tramos de costeo, planeaciones e incidencias de prueba.
--   2. Liberar todos los VINs / Unidades para que queden disponibles para nuevos envíos.
--   3. PRESERVAR catálogos maestros (ubicaciones, tarifas, proveedores, motivos, etc.).
--   4. Seguro e idempotente: crea tablas faltantes si aún no existen para evitar error #1146.
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 0. GARANTIZAR EXISTENCIA DE TABLAS (EVITA ERROR #1146 SI NO SE HA CORRIDO MIGRACIÓN)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_det_incidencias_evidencias` (
  `id_evidencia` INT NOT NULL AUTO_INCREMENT,
  `id_incidencia` INT NOT NULL,
  `id_envio_vin` INT DEFAULT NULL,
  `tipo` ENUM('FOTO','VIDEO','DICTAMEN','REPORTE_CLIENTE','OTRO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FOTO',
  `ruta_archivo` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano_bytes` INT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`),
  KEY `idx_inc_evi_inc` (`id_incidencia`),
  KEY `idx_inc_evi_vin` (`id_envio_vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `log_lgs_incidencias` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_incidencia` INT NOT NULL,
  `estado_anterior` TINYINT DEFAULT NULL,
  `estado_nuevo` TINYINT NOT NULL,
  `comentario` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_usuario` INT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_inc_inc` (`id_incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_trasladistas_checklist` (
  `id_checklist` INT NOT NULL AUTO_INCREMENT,
  `id_envio` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `tipo_checklist` ENUM('ORIGEN', 'DESTINO') NOT NULL,
  `vin_escaneado` VARCHAR(50) NULL,
  `usuario_registro_id` INT NOT NULL,
  `comentarios` TEXT NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_checklist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lgs_checklist_evidencias` (
  `id_evidencia` INT NOT NULL AUTO_INCREMENT,
  `id_checklist` INT NOT NULL,
  `tipo_foto` VARCHAR(50) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 1. INCIDENCIAS, EVIDENCIAS Y GASTOS ADICIONALES DE PRUEBA
-- ------------------------------------------------------------------------------
DELETE FROM `lgs_det_incidencias_evidencias`;
DELETE FROM `lgs_det_incidencias_vins`;
DELETE FROM `log_lgs_incidencias`;
DELETE FROM `lgs_tra_incidencias`;

DELETE FROM `lgs_det_gastos_adicionales_docs`;
DELETE FROM `lgs_det_gastos_adicionales_vins`;
DELETE FROM `log_lgs_gastos_adicionales`;
DELETE FROM `lgs_tra_gastos_adicionales`;

DELETE FROM `lgs_checklist_evidencias`;
DELETE FROM `lgs_trasladistas_checklist`;
DELETE FROM `lgs_envios_evidencias`;
DELETE FROM `lgs_evidencias`;

-- ------------------------------------------------------------------------------
-- 2. PLANEACIONES DE TRASLADOS DE PRUEBA
-- ------------------------------------------------------------------------------
DELETE FROM `lgs_planeaciones_envios`;
DELETE FROM `lgs_planeaciones`;

-- ------------------------------------------------------------------------------
-- 3. ENVÍOS, RUTAS MULTI-NODO, PARADAS Y COSTEO
-- ------------------------------------------------------------------------------
DELETE FROM `lgs_envios_vins`;
DELETE FROM `lgs_envios_tramos_costos`;
DELETE FROM `lgs_envios_nodos`;
DELETE FROM `lgs_envios_paradas`;
DELETE FROM `lgs_solicitudes_entrega`;
DELETE FROM `lgs_envios`;

-- ------------------------------------------------------------------------------
-- 4. LIBERACIÓN DE VINS / UNIDADES (BANDEJA Y AUXILIARES)
-- ------------------------------------------------------------------------------
-- Resetear estado de proceso en lgs_unidades (1 = Pendiente / Disponible)
UPDATE `lgs_unidades`
SET `id_estado_proceso` = 1,
    `fecha_salida` = NULL,
    `fecha_llegada` = NULL,
    `updated_at` = NOW()
WHERE `id_lgs_unidad` > 0;

-- Resetear estatus en lgs_unidades_envios
UPDATE `lgs_unidades_envios`
SET `estatus` = 'disponible'
WHERE `id_unidad` > 0;

-- ------------------------------------------------------------------------------
-- 5. REINICIO DE CONTADORES AUTO_INCREMENT
-- ------------------------------------------------------------------------------
ALTER TABLE `lgs_envios` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_envios_nodos` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_envios_paradas` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_envios_vins` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_envios_tramos_costos` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_planeaciones` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_planeaciones_envios` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_tra_incidencias` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_tra_gastos_adicionales` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_evidencias` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_envios_evidencias` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_trasladistas_checklist` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_checklist_evidencias` AUTO_INCREMENT = 1;
ALTER TABLE `lgs_det_incidencias_evidencias` AUTO_INCREMENT = 1;
ALTER TABLE `log_lgs_incidencias` AUTO_INCREMENT = 1;

-- Garantizar catálogo de Entrega a Cliente Final
INSERT IGNORE INTO `lgs_cat_motivo_envio` (`cve_motivo`, `descripcion`, `activo`) 
VALUES ('CLIENTE_FINAL', 'Entrega a Cliente Final', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 6. CONSULTA DE VERIFICACIÓN
-- ------------------------------------------------------------------------------
SELECT 
    (SELECT COUNT(*) FROM lgs_envios) AS total_envios,
    (SELECT COUNT(*) FROM lgs_planeaciones) AS total_planeaciones,
    (SELECT COUNT(*) FROM lgs_envios_vins) AS total_vins_asignados,
    (SELECT COUNT(*) FROM lgs_unidades WHERE id_estado_proceso = 1) AS vins_libres_bandeja,
    (SELECT COUNT(*) FROM lgs_unidades_envios WHERE estatus = 'disponible') AS vins_libres_auxiliares;
