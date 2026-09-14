-- ==============================================================================
-- 🚀 LIMPIEZA Y CARGA DE VINS REALES (PLANEACIÓN SEMANA 36) EN BANDEJA LOGÍSTICA
-- ==============================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- 1. LIMPIEZA DE UNIDADES PREVIAS EN BANDEJA Y ENVÍOS
TRUNCATE TABLE `lgs_unidades_entrega_interna`;
TRUNCATE TABLE `lgs_unidades`;
TRUNCATE TABLE `lgs_unidades_envios`;

-- 2. INSERTAR VINS REALES EN lgs_unidades_envios
INSERT INTO `lgs_unidades_envios` (`vin`, `num_serie`, `modelo`, `origen`, `destino`, `estatus`, `created_at`) VALUES
('3LDC2A2F8TA001852', '001852', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LD24B3J7TA002165', '002165', 'EST-S38 / AMT 6X4', 'Planta Lagos de Moreno', 'PROFESIONAL EN SERVICIO Y DIAGNOSTICO', 'disponible', NOW()),
('3LDC2A2F1TA001868', '001868', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDC2A2F5TA002778', '002778', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDC2A2F4TA002092', '002092', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDC2A2F8TA002094', '002094', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDA2B2F3TA002940', '002940', 'HiVan Pasajeros', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDC2A2F5TA002070', '002070', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ARG BROKER', 'disponible', NOW()),
('3LDC2A2F3TA001838', '001838', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2FXTA001822', '001822', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F2TA001846', '001846', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'CAMIONES BUGO', 'disponible', NOW()),
('3LDC2A2F0TA002722', '002722', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDA2A2F2TA003376', '003376', 'VIEW CS2-2501 Pasajeros', 'Planta Lagos de Moreno', 'CAMIONES BUGO', 'disponible', NOW()),
('3LDA2A2F4TA002679', '002679', 'VIEW CS2 Pasajeros', 'Planta Lagos de Moreno', 'CAMIONES METROPOLITANOS GR', 'disponible', NOW()),
('3LDA2A2F5TA002674', '002674', 'VIEW CS2 Pasajeros', 'Planta Lagos de Moreno', 'AUTOS Y CAMIONES ASIA CENTRAL', 'disponible', NOW()),
('LVAV2JVB3SE300676', '300676', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVBXSE300464', '300464', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('3LDC2A2F2TA002088', '002088', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'ECO TRUCKS', 'disponible', NOW()),
('3LDC2B2F3TA000971', '000971', 'Tunland V9 (MHEV)', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F7TA002099', '002099', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'BUILT 2 WORK MOTORS', 'disponible', NOW()),
('3LDC2A2F2TA002740', '002740', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F3TA002763', '002763', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F7TA002765', '002765', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F9TA002721', '002721', 'Tunland V7 gasolina 4X4', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F1TA001837', '001837', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F3TA001841', '001841', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F6TA001851', '001851', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F6TA001848', '001848', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'XIAN MOTORS', 'disponible', NOW()),
('3LDC2A2F6TA002885', '002885', 'Tunland G7 4K22-DC', 'Planta Lagos de Moreno', 'CAMIONES PREMIUM DEL CENTRO', 'disponible', NOW()),
('3LDC2A2FXTA002808', '002808', 'Tunland G7 MT Gasolina', 'Planta Lagos de Moreno', 'PROFESIONAL EN SERVICIO Y DIAGNOSTICO', 'disponible', NOW()),
('3LD23B2J9TA002950', '002950', 'S8-E6 AMT', 'Planta Lagos de Moreno', 'CAMIONES ORIENTALES DE LEON', 'disponible', NOW()),
('3LDC2A2F1TA002874', '002874', 'Tunland G7 4K22-DC', 'Planta Lagos de Moreno', 'PROFESIONAL EN SERVICIO Y DIAGNOSTICO', 'disponible', NOW()),
('3LDC2A2F7TA002880', '002880', 'Tunland G7 4K22-DC', 'Planta Lagos de Moreno', 'PROFESIONAL EN SERVICIO Y DIAGNOSTICO', 'disponible', NOW()),
('3LDA2A2F9TA003326', '003326', 'View CS2 Royal', 'Planta Lagos de Moreno', 'AUTOS ORIENTALES PICACHO', 'disponible', NOW()),
('3LDA2A2F6TA003395', '003395', 'VIEW CS2-2501 Pasajeros', 'Planta Lagos de Moreno', 'VELCEN MOTORS', 'disponible', NOW()),
('3LDA2A2F8TA003396', '003396', 'VIEW CS2-2501 Pasajeros', 'Planta Lagos de Moreno', 'VELCEN MOTORS', 'disponible', NOW()),
('3LDC2A2F5TA001856', '001856', 'Tunland V7 gasolina 4X2', 'Planta Lagos de Moreno', 'VELCEN MOTORS', 'disponible', NOW()),
('3LDA2B2FXTA003406', '003406', 'HiVan Panel', 'Planta Lagos de Moreno', 'AUTOS ORIENTALES PICACHO', 'disponible', NOW()),
('LVAV2JVB6SE300588', '300588', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB0SE300506', '300506', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB5SE300713', '300713', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB4SE300640', '300640', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB3SE300659', '300659', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB8SE300382', '300382', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB9SE300455', '300455', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('LVAV2JVB2SE300555', '300555', 'TM3 1.6L', 'Planta Lagos de Moreno', 'AUTOS EMCG', 'disponible', NOW()),
('3LD34B4J3TA000240', '000240', 'EST-A 6X4 X13-E6', 'Planta Lagos de Moreno', '397 CAP', 'disponible', NOW());

-- 3. REGISTRAR UNIDADES EN BANDEJA DE LOGÍSTICA (lgs_unidades) CON ESTADO PENDIENTE (1)
INSERT INTO `lgs_unidades` (`id_unidad`, `id_motivo`, `id_destino`, `destino_descripcion`, `id_estado_proceso`, `created_by`, `created_at`)
SELECT `id_unidad`, 1, 1, `destino`, 1, 1, NOW() FROM `lgs_unidades_envios`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT COUNT(*) AS total_unidades_cargadas FROM `lgs_unidades`;