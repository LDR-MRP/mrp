-- ==============================================================================
-- CARGA DE UNIDADES ARCHIVO 27-10.xlsx EN BANDEJA DE LOGÍSTICA Y CATÁLOGOS
-- Base de datos: u546825723_dbmrp
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. GARANTIZAR ORÍGENES EN CATÁLOGOS (lgs_cat_origenes y lgs_cat_ubicaciones)
-- ------------------------------------------------------------------------------
INSERT INTO `lgs_cat_origenes` (`nombre`, `activo`, `created_at`)
SELECT 'Planta Lagos de Moreno', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_origenes` WHERE LOWER(TRIM(nombre)) LIKE '%lagos%');

INSERT INTO `lgs_cat_origenes` (`nombre`, `activo`, `created_at`)
SELECT 'Carr. Costera del Golfo 862', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_origenes` WHERE LOWER(TRIM(nombre)) LIKE '%costera%golfo%');

INSERT INTO `lgs_cat_origenes` (`nombre`, `activo`, `created_at`)
SELECT 'Alesso Cummins', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_origenes` WHERE LOWER(TRIM(nombre)) LIKE '%alesso%cummins%');

-- Ubicaciones operativas tipo Planta/Origen (id_tipo_destino = 5)
INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'Carr. Costera del Golfo 862', 5, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE LOWER(TRIM(nombre)) LIKE '%costera%golfo%');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'Alesso Cummins', 5, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE LOWER(TRIM(nombre)) LIKE '%alesso%cummins%');


-- ------------------------------------------------------------------------------
-- 2. GARANTIZAR DESTINOS Y DISTRIBUIDORES EN CATÁLOGOS
-- ------------------------------------------------------------------------------
-- Catálogo de distribuidores (lgs_cat_distribuidores)
INSERT INTO `lgs_cat_distribuidores` (`nombre`, `activo`, `created_at`)
SELECT 'AUTOS Y CAMIONES ASIA CENTRAL', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_distribuidores` WHERE LOWER(TRIM(nombre)) LIKE '%asia central%');

INSERT INTO `lgs_cat_distribuidores` (`nombre`, `activo`, `created_at`)
SELECT 'LEASING DRIVE RENTAL', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_distribuidores` WHERE LOWER(TRIM(nombre)) LIKE '%leasing drive%');

INSERT INTO `lgs_cat_distribuidores` (`nombre`, `activo`, `created_at`)
SELECT 'CAMIONES, AUTOBUSES Y TRACTOCAMIONES CATSA', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_distribuidores` WHERE LOWER(TRIM(nombre)) LIKE '%catsa%');

-- Catálogo de destinos (lgs_cat_destinos)
INSERT INTO `lgs_cat_destinos` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'JETOUR GONZALITOS', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE LOWER(TRIM(nombre)) LIKE '%gonzalitos%');

INSERT INTO `lgs_cat_destinos` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'FOTON TLALNEPANTLA', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE LOWER(TRIM(nombre)) LIKE '%tlalnepantla%');

INSERT INTO `lgs_cat_destinos` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'AUTOS Y CAMIONES ASIA CENTRAL', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE LOWER(TRIM(nombre)) LIKE '%asia central%');

INSERT INTO `lgs_cat_destinos` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'LEASING DRIVE RENTAL', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE LOWER(TRIM(nombre)) LIKE '%leasing drive%');

INSERT INTO `lgs_cat_destinos` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'CAMIONES, AUTOBUSES Y TRACTOCAMIONES CATSA', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_destinos` WHERE LOWER(TRIM(nombre)) LIKE '%catsa%');

-- Ubicaciones operativas tipo Destino (id_tipo_destino = 1)
INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'JETOUR GONZALITOS', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE LOWER(TRIM(nombre)) LIKE '%gonzalitos%');

INSERT INTO `lgs_cat_ubicaciones` (`nombre`, `id_tipo_destino`, `activo`, `created_at`)
SELECT 'FOTON TLALNEPANTLA', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `lgs_cat_ubicaciones` WHERE LOWER(TRIM(nombre)) LIKE '%tlalnepantla%');


-- ------------------------------------------------------------------------------
-- 3. INSERTAR O ACTUALIZAR UNIDADES EN lgs_unidades_envios
-- ------------------------------------------------------------------------------
INSERT INTO `lgs_unidades_envios` (`id_unidad`, `vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`, `created_at`)
VALUES
(
    NULL,
    '3LDA2A2F4TA002598',
    '002598',
    'VIEW CS2 Pasajeros',
    'Planta Lagos de Moreno',
    'AUTOS Y CAMIONES ASIA CENTRAL - JETOUR GONZALITOS',
    'disponible',
    NOW()
),
(
    NULL,
    '3LD34B4J6SA001722',
    '001722',
    'Galaxy 3256',
    'Carr. Costera del Golfo 862',
    'LEASING DRIVE RENTAL - FOTON TLALNEPANTLA',
    'disponible',
    NOW()
),
(
    NULL,
    '3LDA2B2F7TA000446',
    '000446',
    'HiVan Panel',
    'Alesso Cummins',
    'CAMIONES, AUTOBUSES Y TRACTOCAMIONES CATSA - PLANTA',
    'disponible',
    NOW()
)
ON DUPLICATE KEY UPDATE 
    `num_serie` = VALUES(`num_serie`),
    `modelo`    = VALUES(`modelo`),
    `origen`    = VALUES(`origen`),
    `destino`   = VALUES(`destino`),
    `estatus`   = 'disponible';


-- ------------------------------------------------------------------------------
-- 4. INSERTAR EN LA BANDEJA DE LOGÍSTICA (lgs_unidades) VINCULANDO POR VIN
-- ------------------------------------------------------------------------------
-- 4.1 Insertar aquellas que aún no existan en lgs_unidades
INSERT INTO `lgs_unidades` (
    `id_lgs_unidad`,
    `id_unidad`,
    `id_motivo`,
    `id_destino`,
    `destino_descripcion`,
    `id_estado_proceso`,
    `fecha_salida`,
    `fecha_llegada`,
    `created_by`,
    `created_at`
)
SELECT 
    NULL,
    u.id_unidad,
    1, -- Venta Directa
    1, -- Distribuidor Autorizado
    tmp.destino,
    1, -- Pendiente (disponible para asignación)
    NULL,
    NULL,
    1,
    NOW()
FROM (
    SELECT '3LDA2A2F4TA002598' AS vin, 'AUTOS Y CAMIONES ASIA CENTRAL - JETOUR GONZALITOS' AS destino
    UNION ALL
    SELECT '3LD34B4J6SA001722' AS vin, 'LEASING DRIVE RENTAL - FOTON TLALNEPANTLA' AS destino
    UNION ALL
    SELECT '3LDA2B2F7TA000446' AS vin, 'CAMIONES, AUTOBUSES Y TRACTOCAMIONES CATSA - PLANTA' AS destino
) tmp
INNER JOIN `lgs_unidades_envios` u ON u.vin = tmp.vin
WHERE NOT EXISTS (
    SELECT 1 FROM `lgs_unidades` lu WHERE lu.id_unidad = u.id_unidad
);

-- 4.2 Si ya existían en lgs_unidades, actualizar destino y estado a Pendiente (disponible)
UPDATE `lgs_unidades` lu
INNER JOIN `lgs_unidades_envios` u ON lu.id_unidad = u.id_unidad
INNER JOIN (
    SELECT '3LDA2A2F4TA002598' AS vin, 'AUTOS Y CAMIONES ASIA CENTRAL - JETOUR GONZALITOS' AS destino
    UNION ALL
    SELECT '3LD34B4J6SA001722' AS vin, 'LEASING DRIVE RENTAL - FOTON TLALNEPANTLA' AS destino
    UNION ALL
    SELECT '3LDA2B2F7TA000446' AS vin, 'CAMIONES, AUTOBUSES Y TRACTOCAMIONES CATSA - PLANTA' AS destino
) tmp ON tmp.vin = u.vin
SET lu.destino_descripcion = tmp.destino,
    lu.id_estado_proceso   = 1,
    lu.fecha_salida        = NULL,
    lu.fecha_llegada       = NULL,
    lu.updated_at          = NOW();

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 5. VERIFICACIÓN DE ALTA EN BANDEJA DE LOGÍSTICA
-- ------------------------------------------------------------------------------
SELECT 
    lu.id_lgs_unidad,
    u.id_unidad,
    u.vin,
    u.num_serie,
    u.modelo,
    u.origen,
    lu.destino_descripcion,
    u.estatus AS estatus_unidad_envios,
    CASE lu.id_estado_proceso
        WHEN 1 THEN 'Pendiente (Disponible en Bandeja)'
        WHEN 2 THEN 'En Tránsito'
        WHEN 3 THEN 'Entregado'
        ELSE 'Otro'
    END AS estado_bandeja
FROM `lgs_unidades` lu
INNER JOIN `lgs_unidades_envios` u ON lu.id_unidad = u.id_unidad
WHERE u.vin IN ('3LDA2A2F4TA002598', '3LD34B4J6SA001722', '3LDA2B2F7TA000446');
