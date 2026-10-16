-- ==============================================================================
-- SCRIPT DE ACTUALIZACIÓN: DESTINOS, RAZONES SOCIALES Y CLIENTES FINALES
-- Fuente Oficial: semana 41.csv / semana 41-ok.xlsx / resgitro actividad.xlsx
-- Objetivo:
--   1. Desactivar todos los nombres genéricos de estados y ciudades dummy (Aguascalientes, Durango, etc.).
--   2. Garantizar que en el catálogo de distribuidores y destinos SOLO figuren:
--      a) Razones Sociales oficiales de clientes / distribuidores del Excel.
--      b) Sedes operativas, agencias FOTON, almacenes, carrocerías y clientes finales.
--      c) Plantas de ensamble / origen (preservadas).
--   3. Idempotente y seguro para Producción.
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. DESACTIVAR DISTRIBUIDORES DUMMY / ESTADOS GENÉRICOS (activo = 0)
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 2. GARANTIZAR RAZONES SOCIALES OFICIALES DEL EXCEL EN lgs_cat_distribuidores
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 3. DESACTIVAR UBICACIONES DUMMY / ESTADOS GENÉRICOS EN lgs_cat_ubicaciones
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 4. INSERTAR O ACTUALIZAR DESTINOS OFICIALES DEL EXCEL EN lgs_cat_ubicaciones
-- ------------------------------------------------------------------------------
-- Helper de inserción segura
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

-- Asegurar que las razones sociales también estén activas como ubicaciones directas si se requiere entrega en matriz
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

-- Asegurar que las Plantas de Carga siempre permanezcan activas
UPDATE `lgs_cat_ubicaciones`
SET `activo` = 1
WHERE `id_tipo_destino` = 5 OR `nombre` LIKE '%PLANTA%' OR `nombre` LIKE '%Tlajomulco%' OR `nombre` LIKE '%Lagos de Moreno%';

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 5. CONSULTA DE VERIFICACIÓN
-- ------------------------------------------------------------------------------
SELECT 'Distribuidores Activos (Razones Sociales)' AS catalogo, COUNT(*) AS total FROM lgs_cat_distribuidores WHERE activo = 1
UNION ALL SELECT 'Distribuidores Inactivos (Dummy depurados)', COUNT(*) FROM lgs_cat_distribuidores WHERE activo = 0
UNION ALL SELECT 'Ubicaciones Activas (Destinos y Plantas)', COUNT(*) FROM lgs_cat_ubicaciones WHERE activo = 1
UNION ALL SELECT 'Ubicaciones Inactivas (Dummy depurados)', COUNT(*) FROM lgs_cat_ubicaciones WHERE activo = 0;
