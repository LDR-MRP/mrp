-- ==========================================================
-- MIGRACIONES PARA TABLAS EXISTENTES
-- ==========================================================
ALTER TABLE lgs_envios MODIFY fecha_tentativa_envio DATETIME;
ALTER TABLE lgs_envios MODIFY fecha_tentativa_llegada DATETIME;
ALTER TABLE lgs_envios_vins ADD COLUMN id_nodo_subida BIGINT DEFAULT NULL;
ALTER TABLE lgs_envios_vins ADD COLUMN id_nodo_bajada BIGINT DEFAULT NULL;

-- ==========================================================
-- CREACIÓN Y POBLADO DE TABLAS NUEVAS Y CATÁLOGOS LGS
-- ==========================================================
