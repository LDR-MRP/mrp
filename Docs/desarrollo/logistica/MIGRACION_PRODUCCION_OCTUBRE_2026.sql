-- ==============================================================================
-- SCRIPT DE MIGRACIÓN: PRODUCCIÓN - MÓDULO LOGÍSTICA & ENVÍOS DIRECTOS
-- Base de Datos: u546825723_dbmrp (Hostinger - MariaDB 10.6+ / 11.x / MySQL 8.0+)
-- Fecha: Octubre 2026
-- Descripción:
--   1. Creación de tablas faltantes para incidencias, evidencias y checklists.
--   2. Incorporación de campos de modalidad de cálculo (Directo vs Escalonado).
--   3. Poblado de catálogos y seeders operativos pendientes.
--   4. Idempotente y seguro para ejecución en Producción (NO elimina datos).
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- FASE 1: CREACIÓN DE TABLAS FALTANTES (IF NOT EXISTS)
-- ------------------------------------------------------------------------------

-- 1.1 Detalle de evidencias multimedia para incidencias
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

-- 1.2 Bitácora / Historial de cambios de estado en incidencias
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

-- 1.3 Checklist digital para trasladistas en Origen y Destino
CREATE TABLE IF NOT EXISTS `lgs_trasladistas_checklist` (
  `id_checklist` INT NOT NULL AUTO_INCREMENT,
  `id_envio` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `tipo_checklist` ENUM('ORIGEN', 'DESTINO') NOT NULL,
  `vin_escaneado` VARCHAR(50) NULL,
  `usuario_registro_id` INT NOT NULL,
  `comentarios` TEXT NULL,
  `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_checklist`),
  KEY `idx_chk_envio` (`id_envio`),
  KEY `idx_chk_unidad` (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 Evidencias fotográficas asociadas al checklist
CREATE TABLE IF NOT EXISTS `lgs_checklist_evidencias` (
  `id_evidencia` INT NOT NULL AUTO_INCREMENT,
  `id_checklist` INT NOT NULL,
  `tipo_foto` VARCHAR(50) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evidencia`),
  KEY `idx_chk_evi_chk` (`id_checklist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------------------------
-- FASE 2: INCORPORACIÓN DE COLUMNAS FALTANTES
-- (Compatible con MariaDB mediante 'IF NOT EXISTS' y con MySQL 8.0 vía PROCEDURE)
-- ------------------------------------------------------------------------------

-- Procedimiento temporal para agregar columnas de forma 100% segura e idempotente
DROP PROCEDURE IF EXISTS `sp_add_column_if_missing`;
DELIMITER //
CREATE PROCEDURE `sp_add_column_if_missing`(
    IN `p_table` VARCHAR(64),
    IN `p_column` VARCHAR(64),
    IN `p_def` VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = p_table 
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 2.1 Modalidad de envío por defecto en catálogo de proveedores
CALL sp_add_column_if_missing('prv_cat_proveedores', 'modalidad_envio_default', "ENUM('ESCALONADO', 'DIRECTO') NOT NULL DEFAULT 'DIRECTO' AFTER `origen`");

-- 2.2 Modalidad de cálculo en envíos
CALL sp_add_column_if_missing('lgs_envios', 'modalidad_calculo', "ENUM('ESCALONADO', 'DIRECTO') NOT NULL DEFAULT 'ESCALONADO' AFTER `tipo_servicio`");

-- 2.3 Origen de referencia en paradas / nodos de ruta
CALL sp_add_column_if_missing('lgs_envios_nodos', 'id_origen_referencia', "BIGINT NULL DEFAULT NULL AFTER `id_ubicacion`");

-- 2.4 Tipo de servicio en unidades / VINs del envío
CALL sp_add_column_if_missing('lgs_envios_vins', 'tipo_servicio', "VARCHAR(20) NOT NULL DEFAULT 'FORANEO' AFTER `costo_unidad`");

-- Eliminar procedimiento temporal
DROP PROCEDURE IF EXISTS `sp_add_column_if_missing`;


-- ------------------------------------------------------------------------------
-- FASE 3: POBLADO DE CATÁLOGOS Y SEEDERS OPERATIVOS (INSERT IGNORE)
-- ------------------------------------------------------------------------------

-- 3.1 Catálogo: Absorción de Daños
INSERT IGNORE INTO `lgs_cat_absorcion_danios` (`clave`, `nombre`, `naturaleza`, `documento`, `genera_gasto`, `activo`) VALUES
('EMPRESA', 'Absorbe la empresa (LDR)', 'CARGO', 'INTERNO', 1, 1),
('PROVEEDOR', 'Absorbe el proveedor (trasladista)', 'DEDUCCION', 'NOTA_CARGO_PROVEEDOR', 1, 1),
('ASEGURADORA', 'Cubre el seguro', 'DEDUCCION', 'ASEGURADORA', 1, 1),
('CLIENTE', 'Absorbe el cliente / distribuidor', 'DEDUCCION', 'CLIENTE', 1, 1),
('TERCERO', 'Tercero responsable', 'DEDUCCION', 'NINGUNO', 1, 1),
('COMPARTIDO', 'Empresa + proveedor (compartido)', 'CARGO', 'INTERNO', 1, 1),
('IMPROCEDENTE', 'No procede (improcedente)', 'NINGUNA', 'NINGUNO', 0, 1);

-- 3.2 Catálogo: Tipos de Gastos Adicionales
INSERT IGNORE INTO `lgs_cat_tipos_gasto_adicional` 
(`clave`, `nombre`, `categoria`, `alcance`, `responsable`, `naturaleza`, `documento`, `calculo`, `unidad_medida`, `requiere_incidencia`, `requiere_documento_soporte`, `activo`) VALUES
('KM_EXTRA_RUTA', 'KM adicional por cambio de ruta', 'RUTA', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'KM', 'km', 0, 0, 1),
('CASETAS_EXTRA', 'Casetas no contempladas', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('ESTADIA', 'Estadía / espera en origen o destino', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'CANTIDAD_X_UNITARIO', 'días', 0, 1, 1),
('PENSION', 'Pensión nocturna / resguardo', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'CANTIDAD_X_UNITARIO', 'días', 0, 1, 1),
('MANIOBRAS', 'Maniobras especiales / grúa', 'OPERATIVO', 'AMBOS', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('PAGO_EXTRA_ACORDADO', 'Pago extra acordado con proveedor', 'ACUERDO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('DANIO_UNIDAD', 'Daño de unidad (según dictamen)', 'DANIO', 'VIN', 'INTERNO', 'CARGO', 'INTERNO', 'MONTO_LIBRE', NULL, 1, 1, 1);

-- 3.3 Catálogo: Motivos Específicos de Gastos Adicionales
INSERT IGNORE INTO `lgs_cat_motivos_gasto_adicional` (`id_tipo_gasto`, `descripcion`, `activo`) VALUES
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='KM_EXTRA_RUTA'), 'Cierre carretero por accidente / clima', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='KM_EXTRA_RUTA'), 'Manifestación o bloqueo social', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='KM_EXTRA_RUTA'), 'Desvío por obra o reparación', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='ESTADIA'), 'Espera excesiva en planta para carga', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='ESTADIA'), 'Espera en distribuidor para descarga', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='CASETAS_EXTRA'), 'Desvío por caseta no contemplada', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='PENSION'), 'Pensión por horario nocturno de restricción', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='MANIOBRAS'), 'Grúa por falla o acceso complicado', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 'Rayón / despostillada en traslado', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 'Golpe o abolladura en maniobra de carga/descarga', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 'Cristal o espejo roto', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='PAGO_EXTRA_ACORDADO'), 'Acuerdo comercial extraordinario', 1),
((SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='PAGO_EXTRA_ACORDADO'), 'Urgencia operativa autorizada', 1);


-- ------------------------------------------------------------------------------
-- FASE 4: VINCULACIONES Y CONFIGURACIONES OPERATIVAS
-- ------------------------------------------------------------------------------

-- 4.1 Enlazar tipos de incidencia existentes con los tipos de gasto sugeridos
UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='KM_EXTRA_RUTA') 
WHERE `clave`='CAMBIO_RUTA';

UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='CASETAS_EXTRA') 
WHERE `clave`='CASETAS';

UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='ESTADIA') 
WHERE `clave`='ESPERA_ESTADIA';

UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='PENSION') 
WHERE `clave`='PENSION';

UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='MANIOBRAS') 
WHERE `clave`='MANIOBRA';

UPDATE `lgs_cat_tipos_incidencia` 
SET `id_tipo_gasto_sugerido` = (SELECT `id_tipo_gasto` FROM `lgs_cat_tipos_gasto_adicional` WHERE `clave`='DANIO_UNIDAD') 
WHERE `clave` IN ('DANIO_UNIDAD', 'FALTANTE_ACCESORIO', 'RECLAMO_CLIENTE');

-- 4.2 Modalidad de cálculo para proveedor Teromo (Escalonado por defecto)
UPDATE `prv_cat_proveedores` 
SET `modalidad_envio_default` = 'ESCALONADO' 
WHERE `razon_social` LIKE '%TEROMO%' OR `id_proveedor` = 1000000202;

-- 4.3 Motivo de entrega a Cliente Final (Punto 1 Observaciones ERP-Logística)
INSERT IGNORE INTO `lgs_cat_motivo_envio` (`cve_motivo`, `descripcion`, `activo`) 
VALUES ('CLIENTE_FINAL', 'Entrega a Cliente Final', 1);

-- 4.4 Plataforma subcontratada base para Live Transfer (Punto 5 Observaciones ERP-Logística)
INSERT INTO `prv_det_plataformas` (`id_proveedor`, `numero_economico`, `placas`, `capacidad_vehiculos`, `estatus_operativo`, `created_by`, `created_at`) 
SELECT 1000000195, 'PLATAFORMA SUBCONTRATADA', 'POR ASIGNAR', 1, 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `prv_det_plataformas` WHERE `id_proveedor` = 1000000195);

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- FASE 5: CONSULTAS DE VERIFICACIÓN POST-EJECUCIÓN
-- ==============================================================================
SELECT 'lgs_det_incidencias_evidencias' AS tabla, COUNT(*) AS registros FROM `lgs_det_incidencias_evidencias`
UNION ALL SELECT 'log_lgs_incidencias', COUNT(*) FROM `log_lgs_incidencias`
UNION ALL SELECT 'lgs_trasladistas_checklist', COUNT(*) FROM `lgs_trasladistas_checklist`
UNION ALL SELECT 'lgs_checklist_evidencias', COUNT(*) FROM `lgs_checklist_evidencias`
UNION ALL SELECT 'lgs_cat_absorcion_danios', COUNT(*) FROM `lgs_cat_absorcion_danios`
UNION ALL SELECT 'lgs_cat_tipos_gasto_adicional', COUNT(*) FROM `lgs_cat_tipos_gasto_adicional`
UNION ALL SELECT 'lgs_cat_motivos_gasto_adicional', COUNT(*) FROM `lgs_cat_motivos_gasto_adicional`
UNION ALL SELECT 'lgs_cat_motivo_envio (activos)', COUNT(*) FROM `lgs_cat_motivo_envio` WHERE `activo` = 1;
