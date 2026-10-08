-- =====================================================================
-- MIGRACIÓN: Módulo de Incidencias Operativas y Gastos Adicionales
-- Base de Datos: db_mrp / u546825723_dbmrp
-- Proyecto: LDR Solutions MRP - Logística
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Catálogo: Absorción de Daños / Dictamen
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_cat_absorcion_danios` (
  `id_absorcion` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(40) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `naturaleza` ENUM('CARGO','DEDUCCION','NINGUNA') NOT NULL DEFAULT 'NINGUNA',
  `documento` ENUM('INTERNO','NOTA_CARGO_PROVEEDOR','ASEGURADORA','CLIENTE','NINGUNO') NOT NULL DEFAULT 'NINGUNO',
  `genera_gasto` TINYINT(1) NOT NULL DEFAULT 1,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_absorcion_danios` (`clave`, `nombre`, `naturaleza`, `documento`, `genera_gasto`, `activo`) VALUES
('EMPRESA', 'Absorbe la empresa (LDR)', 'CARGO', 'INTERNO', 1, 1),
('PROVEEDOR', 'Absorbe el proveedor (trasladista)', 'DEDUCCION', 'NOTA_CARGO_PROVEEDOR', 1, 1),
('ASEGURADORA', 'Cubre el seguro', 'DEDUCCION', 'ASEGURADORA', 1, 1),
('CLIENTE', 'Absorbe el cliente / distribuidor', 'DEDUCCION', 'CLIENTE', 1, 1),
('TERCERO', 'Tercero responsable', 'DEDUCCION', 'NINGUNO', 1, 1),
('COMPARTIDO', 'Empresa + proveedor (compartido)', 'CARGO', 'INTERNO', 1, 1),
('IMPROCEDENTE', 'No procede (improcedente)', 'NINGUNA', 'NINGUNO', 0, 1);

-- ---------------------------------------------------------------------
-- 2. Catálogo: Tipos de Gastos Adicionales
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_cat_tipos_gasto_adicional` (
  `id_tipo_gasto` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(40) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `categoria` ENUM('RUTA','OPERATIVO','DANIO','ACUERDO') NOT NULL DEFAULT 'OPERATIVO',
  `alcance` ENUM('ENVIO','VIN','AMBOS') NOT NULL DEFAULT 'ENVIO',
  `responsable` ENUM('INTERNO','PROVEEDOR','CLIENTE','TERCERO','ASEGURADORA') NOT NULL DEFAULT 'INTERNO',
  `naturaleza` ENUM('CARGO','DEDUCCION') NOT NULL DEFAULT 'CARGO',
  `documento` ENUM('FACTURA_PROVEEDOR','NOTA_CARGO_PROVEEDOR','INTERNO') NOT NULL DEFAULT 'FACTURA_PROVEEDOR',
  `calculo` ENUM('KM','CANTIDAD_X_UNITARIO','MONTO_LIBRE') NOT NULL DEFAULT 'MONTO_LIBRE',
  `unidad_medida` VARCHAR(20) NULL,
  `requiere_incidencia` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_documento_soporte` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_tipos_gasto_adicional` 
(`clave`, `nombre`, `categoria`, `alcance`, `responsable`, `naturaleza`, `documento`, `calculo`, `unidad_medida`, `requiere_incidencia`, `requiere_documento_soporte`, `activo`) VALUES
('KM_EXTRA_RUTA', 'KM adicional por cambio de ruta', 'RUTA', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'KM', 'km', 0, 0, 1),
('CASETAS_EXTRA', 'Casetas no contempladas', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('ESTADIA', 'Estadía / espera en origen o destino', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'CANTIDAD_X_UNITARIO', 'días', 0, 1, 1),
('PENSION', 'Pensión nocturna / resguardo', 'OPERATIVO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'CANTIDAD_X_UNITARIO', 'días', 0, 1, 1),
('MANIOBRAS', 'Maniobras especiales / grúa', 'OPERATIVO', 'AMBOS', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('PAGO_EXTRA_ACORDADO', 'Pago extra acordado con proveedor', 'ACUERDO', 'ENVIO', 'INTERNO', 'CARGO', 'FACTURA_PROVEEDOR', 'MONTO_LIBRE', NULL, 0, 1, 1),
('DANIO_UNIDAD', 'Daño de unidad (según dictamen)', 'DANIO', 'VIN', 'INTERNO', 'CARGO', 'INTERNO', 'MONTO_LIBRE', NULL, 1, 1, 1);

-- ---------------------------------------------------------------------
-- 3. Catálogo: Tipos de Incidencias Operativas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_cat_tipos_incidencia` (
  `id_tipo_incidencia` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(40) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `requiere_dictamen` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_evidencia` TINYINT(1) NOT NULL DEFAULT 0,
  `id_tipo_gasto_sugerido` INT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `fk_tipo_inc_gasto_idx` (`id_tipo_gasto_sugerido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `lgs_cat_tipos_incidencia` 
(`clave`, `nombre`, `requiere_dictamen`, `requiere_evidencia`, `id_tipo_gasto_sugerido`, `activo`) VALUES
('CAMBIO_RUTA', 'Cambio de ruta / Cierre carretero / Desvío', 0, 0, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='KM_EXTRA_RUTA'), 1),
('CASETAS', 'Casetas no contempladas', 0, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='CASETAS_EXTRA'), 1),
('ESPERA_ESTADIA', 'Espera / Estadía prolongada', 0, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='ESTADIA'), 1),
('PENSION', 'Pensión nocturna por resguardo', 0, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='PENSION'), 1),
('MANIOBRA', 'Maniobra especial / Grúa', 0, 0, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='MANIOBRAS'), 1),
('DANIO_UNIDAD', 'Daño de unidad (golpe, rayón, abolladura)', 1, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 1),
('FALTANTE_ACCESORIO', 'Faltante de accesorio o equipo', 1, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 1),
('RECLAMO_CLIENTE', 'Reclamo o inconformidad de cliente / distribuidor', 1, 1, (SELECT id_tipo_gasto FROM lgs_cat_tipos_gasto_adicional WHERE clave='DANIO_UNIDAD'), 1),
('RETRASO', 'Retraso en tránsito sin desvío', 0, 0, NULL, 1),
('DOCUMENTACION', 'Problema con documentación / guía', 0, 0, NULL, 1),
('OTRO', 'Otro evento operativo', 0, 0, NULL, 1);

-- ---------------------------------------------------------------------
-- 4. Catálogo: Motivos Específicos de Gastos / Incidencias
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_cat_motivos_gasto_adicional` (
  `id_motivo_gasto` INT AUTO_INCREMENT PRIMARY KEY,
  `id_tipo_gasto` INT NULL,
  `descripcion` VARCHAR(150) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `fk_motivo_tipo_gasto_idx` (`id_tipo_gasto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ---------------------------------------------------------------------
-- 5. Transaccional: Incidencias Operativas (`lgs_tra_incidencias`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_tra_incidencias` (
  `id_incidencia` INT AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(20) NOT NULL UNIQUE,
  `id_envio` INT NOT NULL,
  `id_tipo_incidencia` INT NOT NULL,
  `id_proveedor` INT NULL,
  `origen` ENUM('OPERACION','CHOFER','CLIENTE','PROVEEDOR','PLANTA') NOT NULL DEFAULT 'OPERACION',
  `severidad` ENUM('BAJA','MEDIA','ALTA') NOT NULL DEFAULT 'MEDIA',
  `fecha_incidente` DATETIME NOT NULL,
  `ubicacion_texto` VARCHAR(255) NULL,
  `descripcion` TEXT NOT NULL,
  `estado_envio_al_registrar` TINYINT NOT NULL,
  `es_post_entrega` TINYINT(1) NOT NULL DEFAULT 0,
  `id_absorcion` INT NULL,
  `porcentaje_proveedor` DECIMAL(5,2) NULL,
  `dictamen_notas` TEXT NULL,
  `dictaminado_by` INT NULL,
  `dictaminado_at` DATETIME NULL,
  `id_estado` TINYINT NOT NULL DEFAULT 1 COMMENT '0:Cancelada, 1:Abierta, 2:En Investigacion, 3:Dictaminada, 4:Con Gastos, 5:Resuelta sin costo, 6:Cerrada',
  `created_by` INT NOT NULL,
  `updated_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  KEY `idx_inc_envio` (`id_envio`),
  KEY `idx_inc_tipo` (`id_tipo_incidencia`),
  KEY `idx_inc_proveedor` (`id_proveedor`),
  KEY `idx_inc_estado` (`id_estado`),
  KEY `idx_inc_absorcion` (`id_absorcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Detalle: VINs afectados por la incidencia (`lgs_det_incidencias_vins`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_det_incidencias_vins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_incidencia` INT NOT NULL,
  `id_envio_vin` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `vin` VARCHAR(50) NOT NULL,
  `estado_vin_al_incidente` ENUM('A_BORDO','ENTREGADO') NOT NULL DEFAULT 'A_BORDO',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_inc_vin_inc` (`id_incidencia`),
  KEY `idx_inc_vin_env` (`id_envio_vin`),
  KEY `idx_inc_vin_str` (`vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Detalle: Evidencias de incidencias (`lgs_det_incidencias_evidencias`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_det_incidencias_evidencias` (
  `id_evidencia` INT AUTO_INCREMENT PRIMARY KEY,
  `id_incidencia` INT NOT NULL,
  `id_envio_vin` INT NULL,
  `tipo` ENUM('FOTO','VIDEO','DICTAMEN','REPORTE_CLIENTE','OTRO') NOT NULL DEFAULT 'FOTO',
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) NOT NULL,
  `tamano_bytes` INT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_inc_evi_inc` (`id_incidencia`),
  KEY `idx_inc_evi_vin` (`id_envio_vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Bitácora: Cambios de estado en incidencias (`log_lgs_incidencias`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_lgs_incidencias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_incidencia` INT NOT NULL,
  `estado_anterior` TINYINT NULL,
  `estado_nuevo` TINYINT NOT NULL,
  `comentario` VARCHAR(255) NULL,
  `id_usuario` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_log_inc_inc` (`id_incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Transaccional: Gastos Adicionales (`lgs_tra_gastos_adicionales`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_tra_gastos_adicionales` (
  `id_gasto` INT AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(20) NOT NULL UNIQUE,
  `id_envio` INT NOT NULL,
  `id_incidencia` INT NULL,
  `id_tipo_gasto` INT NOT NULL,
  `id_motivo_gasto` INT NULL,
  `alcance_aplicado` ENUM('ENVIO','VIN') NOT NULL DEFAULT 'ENVIO',
  `id_proveedor` INT NULL,
  `responsable` ENUM('INTERNO','PROVEEDOR','CLIENTE','TERCERO','ASEGURADORA') NOT NULL DEFAULT 'INTERNO',
  `naturaleza` ENUM('CARGO','DEDUCCION') NOT NULL DEFAULT 'CARGO',
  `documento` ENUM('FACTURA_PROVEEDOR','NOTA_CARGO_PROVEEDOR','INTERNO') NOT NULL DEFAULT 'FACTURA_PROVEEDOR',
  `id_absorcion` INT NULL,
  `justificacion_independiente` TEXT NULL,
  `km_aprobados` DECIMAL(10,2) NULL,
  `km_adicionales` DECIMAL(10,2) NULL,
  `tipo_servicio_original` VARCHAR(15) NULL,
  `tipo_servicio_nuevo` VARCHAR(15) NULL,
  `cantidad` DECIMAL(10,2) NULL,
  `precio_unitario` DECIMAL(12,2) NULL,
  `tarifa_aplicada` DECIMAL(10,2) NULL,
  `monto_calculado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `monto_final` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `justificacion_ajuste` VARCHAR(255) NULL,
  `moneda` CHAR(3) NOT NULL DEFAULT 'MXN',
  `fecha_gasto` DATETIME NOT NULL,
  `descripcion` TEXT NOT NULL,
  `id_estado` TINYINT NOT NULL DEFAULT 1 COMMENT '0:Cancelado, 1:Registrado, 2:En Revision, 3:Aprobado, 4:Rechazado, 5:Documentado',
  `motivo_rechazo` VARCHAR(255) NULL,
  `aprobado_by` INT NULL,
  `aprobado_at` DATETIME NULL,
  `doc_tipo` ENUM('FACTURA','NOTA_CARGO','POLIZA_INTERNA') NULL,
  `doc_folio` VARCHAR(50) NULL,
  `doc_uuid` VARCHAR(100) NULL,
  `doc_fecha` DATE NULL,
  `id_cxp_factura` INT NULL,
  `created_by` INT NOT NULL,
  `updated_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  KEY `idx_gasto_envio` (`id_envio`),
  KEY `idx_gasto_inc` (`id_incidencia`),
  KEY `idx_gasto_tipo` (`id_tipo_gasto`),
  KEY `idx_gasto_prov` (`id_proveedor`),
  KEY `idx_gasto_est` (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. Detalle: Reparto del gasto por VIN (`lgs_det_gastos_adicionales_vins`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_det_gastos_adicionales_vins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_gasto` INT NOT NULL,
  `id_envio_vin` INT NOT NULL,
  `id_unidad` INT NOT NULL,
  `vin` VARCHAR(50) NOT NULL,
  `porcentaje` DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
  `monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gas_vin_gasto` (`id_gasto`),
  KEY `idx_gas_vin_env` (`id_envio_vin`),
  KEY `idx_gas_vin_str` (`vin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. Detalle: Documentos financieros del gasto (`lgs_det_gastos_adicionales_docs`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lgs_det_gastos_adicionales_docs` (
  `id_documento` INT AUTO_INCREMENT PRIMARY KEY,
  `id_gasto` INT NOT NULL,
  `tipo` ENUM('TICKET','COTIZACION','FACTURA_XML','FACTURA_PDF','NOTA_CARGO','ACUERDO','OTRO') NOT NULL DEFAULT 'FACTURA_PDF',
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) NOT NULL,
  `tamano_bytes` INT NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gas_doc_gasto` (`id_gasto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. Bitácora: Cambios de estado en gastos (`log_lgs_gastos_adicionales`)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `log_lgs_gastos_adicionales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_gasto` INT NOT NULL,
  `estado_anterior` TINYINT NULL,
  `estado_nuevo` TINYINT NOT NULL,
  `comentario` VARCHAR(255) NULL,
  `id_usuario` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_log_gas_gasto` (`id_gasto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. Vista: Costo Real por VIN (`vw_lgs_costo_real_vin`)
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_lgs_costo_real_vin` AS
SELECT 
    ev.id AS id_envio_vin,
    ev.id_envio,
    ev.id_unidad,
    COALESCE(u.vin, ut.clave, CONCAT('VIN-', ev.id_unidad)) AS vin,
    COALESCE(ev.costo_unidad, 0.00) AS costo_planeado_unidad,
    COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) AS total_cargos_adicionales,
    COALESCE(SUM(CASE WHEN g.naturaleza = 'DEDUCCION' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) AS total_deducciones,
    (COALESCE(ev.costo_unidad, 0.00) + COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00)) AS costo_real_unidad,
    (COALESCE(ev.costo_unidad, 0.00) + COALESCE(SUM(CASE WHEN g.naturaleza = 'CARGO' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00) - COALESCE(SUM(CASE WHEN g.naturaleza = 'DEDUCCION' AND g.id_estado IN (3, 5) THEN gv.monto ELSE 0 END), 0.00)) AS costo_neto_unidad,
    COUNT(DISTINCT CASE WHEN g.id_estado IN (1, 2, 3, 5) THEN g.id_gasto END) AS total_gastos_activos
FROM lgs_envios_vins ev
LEFT JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
LEFT JOIN mrp_unidades_terminadas ut ON ut.idunidad = ev.id_unidad
LEFT JOIN lgs_det_gastos_adicionales_vins gv ON gv.id_envio_vin = ev.id
LEFT JOIN lgs_tra_gastos_adicionales g ON g.id_gasto = gv.id_gasto AND g.deleted_at IS NULL
GROUP BY ev.id, ev.id_envio, ev.id_unidad;

SET FOREIGN_KEY_CHECKS = 1;
