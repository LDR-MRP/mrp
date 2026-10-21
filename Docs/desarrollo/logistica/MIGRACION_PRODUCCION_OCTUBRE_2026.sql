-- ==============================================================================
-- SCRIPT MAESTRO DE MIGRACIÓN: PRODUCCIÓN - LOGÍSTICA & ENVÍOS DIRECTOS
-- Base de Datos: u546825723_dbmrp (Hostinger - MariaDB 10.6+ / 11.x / MySQL 8.0+)
-- Fecha: Octubre 2026
-- Descripción Integral:
--   1. FASE 1: Creación de tablas faltantes (evidencias, bitácora de incidencias, checklists).
--   2. FASE 2: Incorporación de columnas para modalidad de cálculo (Directo vs Escalonado).
--   3. FASE 3: Poblado de catálogos y seeders operativos (absorción daños, tipos y motivos de gastos).
--   4. FASE 4: Vinculaciones, motivos y plataforma subcontratada base.
--   5. FASE 5: Depuración y actualización oficial de destinos y razones sociales según Excel.
--              - Desactiva 43 distribuidores dummy y 52 ubicaciones dummy de estados genéricos.
--              - Activa y homologa las 26 Razones Sociales oficiales y los 35 destinos/sedes FOTON/clientes.
--              - Preserva al 100% las plantas de origen y la memoria de distancias (lgs_distancias).
--   6. FASE 6: Consultas de auditoría y verificación post-ejecución.
-- Idempotente y 100% seguro para ejecutar en Producción sin pérdida de datos.
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==============================================================================
-- FASE 1: CREACIÓN DE TABLAS FALTANTES (IF NOT EXISTS)
-- ==============================================================================

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


-- ==============================================================================
-- FASE 2: INCORPORACIÓN DE COLUMNAS FALTANTES
-- ==============================================================================

-- Procedimiento temporal para compatibilidad total (MariaDB y MySQL 8.0)
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

-- 2.5 Campos de Monto Abierto (Acuerdos Comerciales / MKT / Estadías)
CALL sp_add_column_if_missing('lgs_envios', 'es_monto_abierto', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Monto manual por acuerdo comercial, 0=Cálculo estándar tarifario' AFTER `modalidad_calculo`");
CALL sp_add_column_if_missing('lgs_envios', 'monto_pactado', "DECIMAL(12,2) DEFAULT NULL COMMENT 'Monto total acordado cuando es monto abierto' AFTER `es_monto_abierto`");
CALL sp_add_column_if_missing('lgs_envios', 'motivo_monto_abierto', "VARCHAR(255) DEFAULT NULL COMMENT 'Justificación del acuerdo comercial' AFTER `monto_pactado`");

-- Eliminar procedimiento temporal
DROP PROCEDURE IF EXISTS `sp_add_column_if_missing`;


-- ==============================================================================
-- FASE 3: POBLADO DE CATÁLOGOS Y SEEDERS OPERATIVOS (INSERT IGNORE)
-- ==============================================================================

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


-- ==============================================================================
-- FASE 4: VINCULACIONES Y CONFIGURACIONES OPERATIVAS
-- ==============================================================================

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


-- ==============================================================================
-- FASE 5: DEPURACIÓN Y HOMOLOGACIÓN DE DESTINOS Y RAZONES SOCIALES (EXCEL)
-- ==============================================================================

-- 5.1 Desactivar distribuidores ficticios / estados y ciudades genéricas (activo = 0)
UPDATE `lgs_cat_distribuidores`
SET `activo` = 0
WHERE `nombre` IN (
    'Aguascalientes',
    'Baja California (Mexicali)',
    'Baja California Sur (La Paz)',
    'Chiapas (Tapachula)',
    'Chiapas (Tuxtla)',
    'Ciudad de México, Tlalpan',
    'CMV Insurgentes',
    'Coahuila (Torreón)',
    'Colima',
    'Cuernavaca',
    'Distribuidora Bajío',
    'Distribuidora Toluca',
    'Durango',
    'Ecatepec',
    'Escobedo',
    'Guanajuato (León)',
    'Guerrero (Chilpancingo)',
    'Hidalgo (Tula)',
    'LDR Interno',
    'Los Mochis',
    'Michoacán (Uruapan)',
    'Monterrey',
    'Municipio Metepec',
    'Nayarit (Tepic)',
    'Oaxaca',
    'Puebla (Asturcar)',
    'Puebla (Eco Trucks)',
    'Querétaro',
    'Quintana Roo (Cancún)',
    'San Luis Potosí',
    'Sinaloa (Cd Obregón)',
    'Sinaloa (Culiacán)',
    'Sonora (Hermosillo)',
    'Tabasco (Villahermosa)',
    'Tamaulipas (Altamira)',
    'Tlaxcala',
    'Toluca',
    'Transportes Centro',
    'Yucatán (Mérida)',
    'Zacatecas',
    'OPEN ENTRETENIMIENTO',
    'CAMIONES HERMT',
    'LDR SOLUTIONS'
);

-- 5.2 Garantizar las 26 Razones Sociales Oficiales del Excel en lgs_cat_distribuidores
-- A) Activar existentes
UPDATE `lgs_cat_distribuidores`
SET `activo` = 1
WHERE `nombre` IN (
    '397 CAP',
    'ARG BROKER',
    'AUTOS EMCG',
    'AUTOS ORIENTALES PICACHO',
    'AUTOS Y CAMIONES ASIA CENTRAL',
    'BUILT 2 WORK MOTORS',
    'CAMIONES BUGO',
    'CAMIONES INNOVADORES DEL PACIFICO',
    'CAMIONES METROPOLITANOS DEL VALLE',
    'CAMIONES METROPOLITANOS GR',
    'CAMIONES ORIENTALES DE LEON',
    'CAMIONES PREMIUM DEL CENTRO',
    'CAMIONES Y TRACTOCAMIONES DE SONORA',
    'CARROCERA L GODINEZ PREMIUM',
    'CASANOVA RENTING',
    'ECO TRUCKS',
    'GAD VEHICULOS COMERCIALES',
    'GENERADOR',
    'GRUPO CARROCERO MILENIO',
    'LEASING DRIVE RENTAL',
    'PROFESIONAL EN SERVICIO Y DIAGNOSTICO',
    'SIN FACTURA',
    'VEHICULOS COMERCIALES PENINSULA',
    'VELCEN MOTORS',
    'XIAN MOTORS',
    'YUNYI TRANSPORTATION MEXICO'
);

-- B) Insertar las que no existan
INSERT INTO `lgs_cat_distribuidores` (`nombre`, `activo`)
SELECT d.nombre, 1
FROM (
    SELECT '397 CAP' AS nombre UNION ALL
    SELECT 'ARG BROKER' UNION ALL
    SELECT 'AUTOS EMCG' UNION ALL
    SELECT 'AUTOS ORIENTALES PICACHO' UNION ALL
    SELECT 'AUTOS Y CAMIONES ASIA CENTRAL' UNION ALL
    SELECT 'BUILT 2 WORK MOTORS' UNION ALL
    SELECT 'CAMIONES BUGO' UNION ALL
    SELECT 'CAMIONES INNOVADORES DEL PACIFICO' UNION ALL
    SELECT 'CAMIONES METROPOLITANOS DEL VALLE' UNION ALL
    SELECT 'CAMIONES METROPOLITANOS GR' UNION ALL
    SELECT 'CAMIONES ORIENTALES DE LEON' UNION ALL
    SELECT 'CAMIONES PREMIUM DEL CENTRO' UNION ALL
    SELECT 'CAMIONES Y TRACTOCAMIONES DE SONORA' UNION ALL
    SELECT 'CARROCERA L GODINEZ PREMIUM' UNION ALL
    SELECT 'CASANOVA RENTING' UNION ALL
    SELECT 'ECO TRUCKS' UNION ALL
    SELECT 'GAD VEHICULOS COMERCIALES' UNION ALL
    SELECT 'GENERADOR' UNION ALL
    SELECT 'GRUPO CARROCERO MILENIO' UNION ALL
    SELECT 'LEASING DRIVE RENTAL' UNION ALL
    SELECT 'PROFESIONAL EN SERVICIO Y DIAGNOSTICO' UNION ALL
    SELECT 'SIN FACTURA' UNION ALL
    SELECT 'VEHICULOS COMERCIALES PENINSULA' UNION ALL
    SELECT 'VELCEN MOTORS' UNION ALL
    SELECT 'XIAN MOTORS' UNION ALL
    SELECT 'YUNYI TRANSPORTATION MEXICO'
) d
WHERE NOT EXISTS (
    SELECT 1 FROM `lgs_cat_distribuidores` WHERE `nombre` = d.nombre
);


-- 5.3 Desactivar ubicaciones ficticias / nombres genéricos en lgs_cat_ubicaciones (activo = 0)
UPDATE `lgs_cat_ubicaciones`
SET `activo` = 0
WHERE `nombre` IN (
    'Aguascalientes',
    'Baja California (Mexicali)',
    'Baja California Sur (La Paz)',
    'BAJA CALIFORNIA SUR (LA PAZ) + COSTO DE FERRI',
    'Chiapas (Tapachula)',
    'Chiapas (Tuxtla)',
    'Ciudad de México, Tlalpan',
    'CIUDAD DE MEXICO, TLAPAN',
    'CMV Insurgentes',
    'CMV TLANEPANTA',
    'Coahuila (Torreón)',
    'Colima',
    'Cuernavaca',
    'Distribuidora Bajío',
    'Distribuidora Toluca',
    'Durango',
    'Ecatepec',
    'Escobedo',
    'Guanajuato (León)',
    'Guerrero (Chilpancingo)',
    'Hidalgo (Tula)',
    'LDR Interno',
    'Los Mochis',
    'Michoacán (Uruapan)',
    'MICHOACAN URUAPAN',
    'Monterrey',
    'Municipio Metepec',
    'Nayarit (Tepic)',
    'Oaxaca',
    'Puebla (Asturcar)',
    'Puebla (Eco Trucks)',
    'PUEBLA (ECO TRUKS)',
    'Querétaro',
    'Quintana Roo (Cancún)',
    'San Luis Potosí',
    'Sinaloa (Cd Obregón)',
    'Sinaloa (Culiacán)',
    'Sonora (Hermosillo)',
    'Tabasco (Villahermosa)',
    'Tamaulipas (Altamira)',
    'Tlaxcala',
    'Toluca',
    'Transportes Centro',
    'Yucatán (Mérida)',
    'Zacatecas',
    'Agencia Monterrey (Gonzalitos)',
    'ALMACEN MONTENEGRO',
    'EXPO GDL (MJ26+R6C Guadalajara, Jalisco https://maps.app.goo.gl/efZpJba1WKMXKP9PA )',
    'FULONGMA (Nave Tecámac ubicada en Calle 41, Bodega Mz1 Lt6, Paseos de Tecámac, Tecámac, Estado de México, C.P. 55754)',
    'FOTON AGUASCALIENTES',
    'FOTON INSURGENTES Sede 2',
    'Jalisco (Xian Motors)'
);

-- 5.4 Registrar o actualizar los 35 destinos operativos del Excel en lgs_cat_ubicaciones
INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'ALMACEN GDL', 4, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'ALMACEN GDL');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'ALMACEN GDL (XIAN)', 4, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'ALMACEN GDL (XIAN)');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'ALMACEN QUERETARO', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'ALMACEN QUERETARO');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'CARROCERIA AGUIRRE', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'CARROCERIA AGUIRRE');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'Carrocerías y Remolques Israel', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'Carrocerías y Remolques Israel');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'CD. DE MEXICO', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'CD. DE MEXICO');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'CMV Tlalnepantla', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES METROPOLITANOS DEL VALLE' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'CMV Tlalnepantla');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'CORPORATIVO FOTON', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'CORPORATIVO FOTON');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FERTILIZANTES TEPEYAC', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FERTILIZANTES TEPEYAC');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON ALLENDE', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'VELCEN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON ALLENDE');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON ALTAMIRA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'ARG BROKER' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON ALTAMIRA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON APODACA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'VELCEN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON APODACA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON CANCUN', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'VEHICULOS COMERCIALES PENINSULA' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON CANCUN');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON CHINA POBLANA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON CHINA POBLANA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON CUERNAVACA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON CUERNAVACA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON GONZALEZ GALLO', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON GONZALEZ GALLO');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON HERMOSILLO', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES Y TRACTOCAMIONES DE SONORA' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON HERMOSILLO');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON LEON', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES ORIENTALES DE LEON' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON LEON');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON MERIDA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'VEHICULOS COMERCIALES PENINSULA' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON MERIDA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON MONTERREY', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'VELCEN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON MONTERREY');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON PUEBLA (ASTURCAR)', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'AUTOS EMCG' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON PUEBLA (ASTURCAR)');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON QUERETARO', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES PREMIUM DEL CENTRO' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON QUERETARO');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON SAN LUIS', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'AUTOS Y CAMIONES ASIA CENTRAL' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON SAN LUIS');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON SLP', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'AUTOS Y CAMIONES ASIA CENTRAL' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON SLP');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON TEPIC', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES INNOVADORES DEL PACIFICO' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TEPIC');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON TLALNEPANTLA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'CAMIONES METROPOLITANOS DEL VALLE' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TLALNEPANTLA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON TLAXCALA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'ECO TRUCKS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TLAXCALA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON TOLUCA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'BUILT 2 WORK MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TOLUCA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON TULA', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'GRUPO CARROCERO MILENIO' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TULA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON XIAN', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON XIAN');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON ZACATECAS', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'GAD VEHICULOS COMERCIALES' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON ZACATECAS');

-- 5.4.1 Actualización forzosa de id_distribuidor para sedes que ya existían previamente en BD
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'VEHICULOS COMERCIALES PENINSULA' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON MERIDA', 'FOTON CANCUN');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'VELCEN MOTORS' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON MONTERREY', 'FOTON APODACA', 'FOTON ALLENDE');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'XIAN MOTORS' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON XIAN', 'FOTON GONZALEZ GALLO', 'FOTON CUERNAVACA', 'FOTON CHINA POBLANA', 'ALMACEN GDL', 'ALMACEN GDL (XIAN)');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'CAMIONES METROPOLITANOS DEL VALLE' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON TLALNEPANTLA', 'CMV Tlalnepantla');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'CAMIONES PREMIUM DEL CENTRO' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON QUERETARO');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'BUILT 2 WORK MOTORS' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON TOLUCA');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'AUTOS Y CAMIONES ASIA CENTRAL' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON SAN LUIS', 'FOTON SLP');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'ARG BROKER' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON ALTAMIRA');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'CAMIONES ORIENTALES DE LEON' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON LEON');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'CAMIONES Y TRACTOCAMIONES DE SONORA' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON HERMOSILLO');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'GAD VEHICULOS COMERCIALES' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON ZACATECAS');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'CAMIONES INNOVADORES DEL PACIFICO' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON TEPIC');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'AUTOS EMCG' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON PUEBLA (ASTURCAR)');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'ECO TRUCKS' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON TLAXCALA');
UPDATE `lgs_cat_ubicaciones` SET `id_distribuidor` = (SELECT `id_distribuidor` FROM `lgs_cat_distribuidores` WHERE `nombre` = 'GRUPO CARROCERO MILENIO' LIMIT 1), `activo` = 1 WHERE `nombre` IN ('FOTON TULA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'GALLEGO PLANTA TRAILERS MANILA', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'GALLEGO PLANTA TRAILERS MANILA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'GUADALAJARA', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'GUADALAJARA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'MOON PALACE ARENA', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'MOON PALACE ARENA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'Transportes Forja (TAV)', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'Transportes Forja (TAV)');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'VERACRUZ', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'VERACRUZ');

-- 5.5 Habilitar razones sociales en ubicaciones para entregas en matriz
UPDATE `lgs_cat_ubicaciones`
SET `activo` = 1
WHERE `nombre` IN (
    '397 CAP',
    'ARG BROKER',
    'AUTOS EMCG',
    'AUTOS ORIENTALES PICACHO',
    'AUTOS Y CAMIONES ASIA CENTRAL',
    'BUILT 2 WORK MOTORS',
    'CAMIONES BUGO',
    'CAMIONES INNOVADORES DEL PACIFICO',
    'CAMIONES METROPOLITANOS DEL VALLE',
    'CAMIONES METROPOLITANOS GR',
    'CAMIONES ORIENTALES DE LEON',
    'CAMIONES PREMIUM DEL CENTRO',
    'CAMIONES Y TRACTOCAMIONES DE SONORA',
    'CARROCERA L GODINEZ PREMIUM',
    'CASANOVA RENTING',
    'ECO TRUCKS',
    'GAD VEHICULOS COMERCIALES',
    'GENERADOR',
    'GRUPO CARROCERO MILENIO',
    'LEASING DRIVE RENTAL',
    'PROFESIONAL EN SERVICIO Y DIAGNOSTICO',
    'SIN FACTURA',
    'VEHICULOS COMERCIALES PENINSULA',
    'VELCEN MOTORS',
    'XIAN MOTORS',
    'YUNYI TRANSPORTATION MEXICO'
);

-- 5.6 Garantizar que las Plantas y Centros de Ensamble de Carga permanezcan 100% activas
UPDATE `lgs_cat_ubicaciones`
SET `activo` = 1
WHERE `id_tipo_destino` = 5 
   OR `nombre` LIKE '%PLANTA%' 
   OR `nombre` LIKE '%Tlajomulco%' 
   OR `nombre` LIKE '%Lagos de Moreno%';

-- 5.7 Asegurar distancias en lgs_distancias para las sedes operativas oficiales
INSERT INTO `lgs_distancias` (`id_ubicacion_a`, `id_ubicacion_b`, `km`, `created_at`, `updated_at`)
SELECT 
    LEAST(o.id_ubicacion, d.id_ubicacion),
    GREATEST(o.id_ubicacion, d.id_ubicacion),
    r.km,
    NOW(),
    NOW()
FROM (
    SELECT 'PLANTA' AS origen, 'FOTON MERIDA' AS destino, 1891.00 AS km UNION ALL
    SELECT 'PLANTA', 'FOTON CANCUN', 2241.00 UNION ALL
    SELECT 'PLANTA', 'FOTON QUERETARO', 249.00 UNION ALL
    SELECT 'PLANTA', 'FOTON SAN LUIS', 160.00 UNION ALL
    SELECT 'PLANTA', 'FOTON SLP', 160.00 UNION ALL
    SELECT 'PLANTA', 'FOTON MONTERREY', 683.00 UNION ALL
    SELECT 'PLANTA', 'FOTON APODACA', 832.00 UNION ALL
    SELECT 'PLANTA', 'FOTON TLALNEPANTLA', 407.00 UNION ALL
    SELECT 'PLANTA', 'FOTON TOLUCA', 450.00 UNION ALL
    SELECT 'PLANTA', 'FOTON ALTAMIRA', 610.00 UNION ALL
    SELECT 'PLANTA', 'FOTON LEON', 85.00 UNION ALL
    SELECT 'PLANTA', 'FOTON HERMOSILLO', 1540.00 UNION ALL
    SELECT 'PLANTA', 'FOTON ZACATECAS', 403.00 UNION ALL
    SELECT 'PLANTA', 'FOTON TEPIC', 375.00 UNION ALL
    SELECT 'PLANTA', 'FOTON CHINA POBLANA', 545.00 UNION ALL
    SELECT 'PLANTA', 'FOTON GONZALEZ GALLO', 178.00 UNION ALL
    SELECT 'PLANTA', 'FOTON PUEBLA (ASTURCAR)', 544.00 UNION ALL
    SELECT 'PLANTA', 'FOTON TULA', 439.00 UNION ALL
    SELECT 'PLANTA', 'FOTON XIAN', 32.00 UNION ALL
    SELECT 'PLANTA', 'ALMACEN GDL', 38.00 UNION ALL
    SELECT 'PLANTA', 'ALMACEN GDL (XIAN)', 38.00 UNION ALL
    SELECT 'PLANTA', 'FOTON CUERNAVACA', 506.00 UNION ALL
    SELECT 'PLANTA', 'FOTON TLAXCALA', 645.00 UNION ALL
    SELECT 'PLANTA', 'FOTON ALLENDE', 683.00 UNION ALL
    SELECT 'PLANTA', 'CARROCERIA AGUIRRE', 673.00 UNION ALL
    SELECT 'PLANTA', 'Carrocerías y Remolques Israel', 425.00 UNION ALL
    SELECT 'PLANTA', 'GALLEGO PLANTA TRAILERS MANILA', 602.00 UNION ALL
    SELECT 'PLANTA', 'Transportes Forja (TAV)', 778.00 UNION ALL
    SELECT 'ALMACEN GDL', 'FOTON APODACA', 832.00 UNION ALL
    SELECT 'ALMACEN GDL', 'FOTON ALTAMIRA', 800.00 UNION ALL
    SELECT 'ALMACEN GDL', 'FOTON CUERNAVACA', 506.00 UNION ALL
    SELECT 'ALMACEN GDL', 'FOTON TLAXCALA', 645.00 UNION ALL
    SELECT 'ALMACEN GDL', 'CD. DE MEXICO', 533.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON MERIDA', 1891.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON CANCUN', 2241.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON SAN LUIS', 397.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON TOLUCA', 533.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON ZACATECAS', 403.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'FOTON XIAN', 32.00 UNION ALL
    SELECT 'Planta 3 Tlajomulco', 'ALMACEN GDL (XIAN)', 38.00
) r
JOIN `lgs_cat_ubicaciones` o ON o.nombre = r.origen AND o.activo = 1
JOIN `lgs_cat_ubicaciones` d ON d.nombre = r.destino AND d.activo = 1
WHERE o.id_ubicacion <> d.id_ubicacion
ON DUPLICATE KEY UPDATE `km` = VALUES(`km`), `updated_at` = NOW();

SET FOREIGN_KEY_CHECKS = 1;


-- ==============================================================================
-- FASE 6: CONSULTAS DE VERIFICACIÓN POST-EJECUCIÓN
-- ==============================================================================
SELECT '1. Nuevas Tablas de Incidencias / Checklists' AS seccion, '' AS detalle, 0 AS total
UNION ALL SELECT 'lgs_det_incidencias_evidencias', 'Registros', COUNT(*) FROM `lgs_det_incidencias_evidencias`
UNION ALL SELECT 'log_lgs_incidencias', 'Registros', COUNT(*) FROM `log_lgs_incidencias`
UNION ALL SELECT 'lgs_trasladistas_checklist', 'Registros', COUNT(*) FROM `lgs_trasladistas_checklist`
UNION ALL SELECT 'lgs_checklist_evidencias', 'Registros', COUNT(*) FROM `lgs_checklist_evidencias`
UNION ALL SELECT '2. Catálogos Operativos', '', 0
UNION ALL SELECT 'lgs_cat_absorcion_danios', 'Registros', COUNT(*) FROM `lgs_cat_absorcion_danios`
UNION ALL SELECT 'lgs_cat_tipos_gasto_adicional', 'Registros', COUNT(*) FROM `lgs_cat_tipos_gasto_adicional`
UNION ALL SELECT 'lgs_cat_motivos_gasto_adicional', 'Registros', COUNT(*) FROM `lgs_cat_motivos_gasto_adicional`
UNION ALL SELECT 'lgs_cat_motivo_envio', 'Activos', COUNT(*) FROM `lgs_cat_motivo_envio` WHERE `activo` = 1
UNION ALL SELECT '3. Destinos Homologados con Excel', '', 0
UNION ALL SELECT 'Distribuidores Activos (Razones Sociales)', 'Activos', COUNT(*) FROM `lgs_cat_distribuidores` WHERE `activo` = 1
UNION ALL SELECT 'Distribuidores Dummy Inactivos', 'Inactivos', COUNT(*) FROM `lgs_cat_distribuidores` WHERE `activo` = 0
UNION ALL SELECT 'Ubicaciones Activas (Sedes + Clientes + Plantas)', 'Activas', COUNT(*) FROM `lgs_cat_ubicaciones` WHERE `activo` = 1
UNION ALL SELECT 'Ubicaciones Dummy Inactivas', 'Inactivas', COUNT(*) FROM `lgs_cat_ubicaciones` WHERE `activo` = 0
UNION ALL SELECT '4. Estructura Monto Abierto en lgs_envios', 'Columnas', COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lgs_envios' AND COLUMN_NAME IN ('es_monto_abierto', 'monto_pactado', 'motivo_monto_abierto');
