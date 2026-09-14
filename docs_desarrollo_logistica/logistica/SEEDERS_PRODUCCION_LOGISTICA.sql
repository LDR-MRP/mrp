-- ==============================================================================
-- 🚚 SEEDERS DE PRODUCCIÓN — CATÁLOGOS, DESTINOS Y TARIFAS
-- ==============================================================================
-- Este archivo contiene ÚNICAMENTE las inserciones de datos (Seeders).
-- Puedes ejecutarlo directamente si ya creaste las tablas previamente.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. TIPOS DE TRASLADO
INSERT IGNORE INTO `lgs_cat_tipo_traslado` (`id_tipo_traslado`, `nombre`) VALUES
(1, 'Madrina'),
(2, 'Chofer (Rodando)');

-- 2. MOTIVOS DE ENVÍO
INSERT IGNORE INTO `lgs_cat_motivo_envio` (`id_motivo`, `cve_motivo`, `descripcion`) VALUES
(1, 'ENTREGA_DIST',        'Entrega a Distribuidor'),
(2, 'TRASLADO_CARROCERIA', 'Traslado a Carrocería'),
(3, 'MARKETING',           'Marketing / Exposición'),
(4, 'DEMO',                'Unidad Demo'),
(5, 'PRUEBAS',             'Unidad de Pruebas'),
(6, 'PILOTO',              'Unidad Piloto'),
(7, 'DEVOLUCION',          'Devolución'),
(8, 'OTRO',                'Otro motivo');

-- 3. TIPOS DE DESTINO
INSERT IGNORE INTO `lgs_cat_tipo_destino` (`id_tipo_destino`, `cve_destino`, `descripcion`) VALUES
(1, 'DISTRIBUIDOR',  'Distribuidor / Concesionario'),
(2, 'CARROCERO',     'Carrocero / Adaptaciones'),
(3, 'CLIENTE_FINAL', 'Cliente Final'),
(4, 'ALMACEN',       'Almacén'),
(5, 'PLANTA',        'Planta'),
(6, 'OTRO',          'Otro destino');

-- 4. ORÍGENES DE DESPACHO (PLANTAS Y ALMACENES)
INSERT IGNORE INTO `lgs_cat_origenes` (`id_origen`, `nombre`) VALUES
(1, 'Planta 1'),
(2, 'Planta 2'),
(3, 'Planta 3'),
(4, 'Planta 4'),
(5, 'Planta 5'),
(6, 'Almacén Montenegro'),
(7, 'Lagos de Moreno');

-- 5. DESTINOS FRECUENTES (41 PLAZAS DEL TARIFARIO)
INSERT IGNORE INTO `lgs_cat_destinos` (`id_destino`, `nombre`, `id_tipo_destino`) VALUES
(1, 'Aguascalientes', 1),
(2, 'Baja California (Mexicali)', 1),
(3, 'Baja California Sur (La Paz)', 1),
(4, 'Chiapas (Tuxtla)', 1),
(5, 'Chiapas (Tapachula)', 1),
(6, 'Coahuila (Torreón)', 1),
(7, 'Ciudad de México, Tlalpan', 1),
(8, 'Colima', 1),
(9, 'Durango', 1),
(10, 'Guanajuato (León)', 1),
(11, 'Guerrero (Chilpancingo)', 1),
(12, 'Hidalgo (Tula)', 1),
(13, 'Planta 3 Tlajomulco', 5),
(14, 'Planta 2 Jalisco', 5),
(15, 'Jalisco (Xian Motors)', 2),
(16, 'Almacén Montenegro', 4),
(17, 'Cuernavaca', 1),
(18, 'Ecatepec', 1),
(19, 'CMV Tlalnepantla', 1),
(20, 'Michoacán (Uruapan)', 1),
(21, 'Nayarit (Tepic)', 1),
(22, 'Monterrey', 1),
(23, 'Escobedo', 1),
(24, 'CMV Insurgentes', 1),
(25, 'Oaxaca', 1),
(26, 'Puebla (Eco Trucks)', 1),
(27, 'Puebla (Asturcar)', 1),
(28, 'Querétaro', 1),
(29, 'Quintana Roo (Cancún)', 1),
(30, 'San Luis Potosí', 1),
(31, 'Los Mochis', 1),
(32, 'Sinaloa (Cd Obregón)', 1),
(33, 'Sinaloa (Culiacán)', 1),
(34, 'Sonora (Hermosillo)', 1),
(35, 'Tabasco (Villahermosa)', 1),
(36, 'Toluca', 1),
(37, 'Tamaulipas (Altamira)', 1),
(38, 'Tlaxcala', 1),
(39, 'Corporativo Foton CDMX', 4),
(40, 'Yucatán (Mérida)', 1),
(41, 'Zacatecas', 1);

-- 6. SEGMENTOS DE VEHÍCULOS PARA TARIFARIO
INSERT IGNORE INTO `lgs_cat_segmentos` (`id_segmento`, `nombre`, `descripcion`) VALUES
(1, 'Ligeros',             'Vehículos ligeros y compactos ($18/km)'),
(2, 'Medianos',            'Camiones medianos y van de carga ($20/km)'),
(3, 'Pesados',             'Tractocamiones y chasis pesados ($25/km)'),
(4, 'Autobuses',           'Buses urbanos e interurbanos ($28/km)'),
(5, 'Lowboy / Especiales', 'Maquinaria y fletes sobredimensionados ($80/km)');

-- 7. ACTIVIDAD DE TRASLADISTA PARA PROVEEDORES
INSERT IGNORE INTO `prv_cat_actividades` (`cve_actividad`, `descripcion`, `estado`) VALUES
('TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2);

-- 8. TARIFAS Y RUTAS (DESDE ORIGEN: LAGOS DE MORENO - ID 7)
-- Modalidad: Chofer Rodando (id_tipo_traslado = 2)
-- Segmentos: 1=Ligeros ($18), 2=Medianos ($20), 3=Pesados ($25), 4=Buses ($28), 5=Lowboy ($80)
INSERT IGNORE INTO `lgs_costos_rutas` (`id_tipo_traslado`, `id_origen`, `id_destino`, `id_segmento`, `num_vins_min`, `num_vins_max`, `km`, `costo_por_km`, `precio_plano`, `factor`, `activo`) VALUES
-- Aguascalientes (89 km)
(2, 7, 1, 1, 1, 1, 89.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 1, 2, 1, 1, 89.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 1, 3, 1, 1, 89.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 1, 4, 1, 1, 89.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 1, 5, 1, 1, 89.00, 80.0000, 0.00, 1.0000, 2),
-- Baja California Mexicali (2231 km)
(2, 7, 2, 1, 1, 1, 2231.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 2, 2, 1, 1, 2231.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 2, 3, 1, 1, 2231.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 2, 4, 1, 1, 2231.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 2, 5, 1, 1, 2231.00, 80.0000, 0.00, 1.0000, 2),
-- Baja California Sur La Paz (1095 km)
(2, 7, 3, 1, 1, 1, 1095.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 3, 2, 1, 1, 1095.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 3, 3, 1, 1, 1095.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 3, 4, 1, 1, 1095.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 3, 5, 1, 1, 1095.00, 80.0000, 0.00, 1.0000, 2),
-- Chiapas Tuxtla (1253 km)
(2, 7, 4, 1, 1, 1, 1253.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 4, 2, 1, 1, 1253.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 4, 3, 1, 1, 1253.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 4, 4, 1, 1, 1253.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 4, 5, 1, 1, 1253.00, 80.0000, 0.00, 1.0000, 2),
-- Guanajuato León (85 km)
(2, 7, 10, 1, 1, 1, 85.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 10, 2, 1, 1, 85.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 10, 3, 1, 1, 85.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 10, 4, 1, 1, 85.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 10, 5, 1, 1, 85.00, 80.0000, 0.00, 1.0000, 2),
-- CDMX Tlalpan (445 km)
(2, 7, 7, 1, 1, 1, 445.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 7, 2, 1, 1, 445.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 7, 3, 1, 1, 445.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 7, 4, 1, 1, 445.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 7, 5, 1, 1, 445.00, 80.0000, 0.00, 1.0000, 2),
-- Monterrey (683 km)
(2, 7, 22, 1, 1, 1, 683.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 22, 2, 1, 1, 683.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 22, 3, 1, 1, 683.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 22, 4, 1, 1, 683.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 22, 5, 1, 1, 683.00, 80.0000, 0.00, 1.0000, 2),
-- Puebla (545 km)
(2, 7, 26, 1, 1, 1, 545.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 26, 2, 1, 1, 545.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 26, 3, 1, 1, 545.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 26, 4, 1, 1, 545.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 26, 5, 1, 1, 545.00, 80.0000, 0.00, 1.0000, 2),
-- Querétaro (249 km)
(2, 7, 28, 1, 1, 1, 249.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 28, 2, 1, 1, 249.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 28, 3, 1, 1, 249.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 28, 4, 1, 1, 249.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 28, 5, 1, 1, 249.00, 80.0000, 0.00, 1.0000, 2),
-- Toluca (450 km)
(2, 7, 36, 1, 1, 1, 450.00, 18.0000, 0.00, 1.0000, 2),
(2, 7, 36, 2, 1, 1, 450.00, 20.0000, 0.00, 1.0000, 2),
(2, 7, 36, 3, 1, 1, 450.00, 25.0000, 0.00, 1.0000, 2),
(2, 7, 36, 4, 1, 1, 450.00, 28.0000, 0.00, 1.0000, 2),
(2, 7, 36, 5, 1, 1, 450.00, 80.0000, 0.00, 1.0000, 2);

SET FOREIGN_KEY_CHECKS = 1;
