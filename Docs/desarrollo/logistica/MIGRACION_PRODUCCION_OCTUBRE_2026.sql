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
INSERT INTO `lgs_cat_distribuidores` (`nombre`, `activo`) VALUES
('397 CAP', 1),
('ARG BROKER', 1),
('AUTOS EMCG', 1),
('AUTOS ORIENTALES PICACHO', 1),
('AUTOS Y CAMIONES ASIA CENTRAL', 1),
('BUILT 2 WORK MOTORS', 1),
('CAMIONES BUGO', 1),
('CAMIONES INNOVADORES DEL PACIFICO', 1),
('CAMIONES METROPOLITANOS DEL VALLE', 1),
('CAMIONES METROPOLITANOS GR', 1),
('CAMIONES ORIENTALES DE LEON', 1),
('CAMIONES PREMIUM DEL CENTRO', 1),
('CAMIONES Y TRACTOCAMIONES DE SONORA', 1),
('CARROCERA L GODINEZ PREMIUM', 1),
('CASANOVA RENTING', 1),
('ECO TRUCKS', 1),
('GAD VEHICULOS COMERCIALES', 1),
('GENERADOR', 1),
('GRUPO CARROCERO MILENIO', 1),
('LEASING DRIVE RENTAL', 1),
('PROFESIONAL EN SERVICIO Y DIAGNOSTICO', 1),
('SIN FACTURA', 1),
('VEHICULOS COMERCIALES PENINSULA', 1),
('VELCEN MOTORS', 1),
('XIAN MOTORS', 1),
('YUNYI TRANSPORTATION MEXICO', 1)
ON DUPLICATE KEY UPDATE `activo` = 1;

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

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON CHINA POBLANA', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON CHINA POBLANA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON CUERNAVACA', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON CUERNAVACA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON GONZALEZ GALLO', 1, 1
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

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON PUEBLA (ASTURCAR)', 1, 1
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

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON TEPIC', 1, 1
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

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`)
SELECT 'FOTON TULA', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON TULA');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON XIAN', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'XIAN MOTORS' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON XIAN');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `id_distribuidor`, `activo`)
SELECT 'FOTON ZACATECAS', 1, (SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = 'GAD VEHICULOS COMERCIALES' LIMIT 1), 1
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE `nombre` = 'FOTON ZACATECAS');

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
UNION ALL SELECT 'Ubicaciones Dummy Inactivas', 'Inactivas', COUNT(*) FROM `lgs_cat_ubicaciones` WHERE `activo` = 0;
