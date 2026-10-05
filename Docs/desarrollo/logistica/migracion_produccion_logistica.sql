-- ==============================================================================
-- SCRIPT DE MIGRACIÓN Y HOMOLOGACIÓN: PRODUCCIÓN LOGÍSTICA
-- Base de datos: u546825723_dbmrp
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. CREACIÓN DE TABLAS FALTANTES PARA PLATAFORMAS
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prv_det_plataformas` (
  `id_plataforma` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_proveedor` bigint(20) NOT NULL COMMENT 'FK prv_cat_proveedores',
  `numero_economico` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_serie_vin` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacidad_vehiculos` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `estatus_operativo` tinyint(1) DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_plataforma`),
  KEY `idx_plataforma_prov` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prv_det_plataforma_chofer_historial` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_plataforma` bigint(20) NOT NULL,
  `id_chofer` bigint(20) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. DEPURACIÓN Y ACTUALIZACIÓN DE TARIFAS (lgs_tarifas_proveedores)
-- ------------------------------------------------------------------------------

-- 2.1 Eliminar factores obsoletos 1 y 10 en Madrina (rango de regla de negocio: 2 a 9 unidades)
DELETE FROM `lgs_tarifas_proveedores` 
WHERE `id_tipo_traslado` = 1 AND (`num_vins_min` < 2 OR `num_vins_min` > 9);

-- 2.2 Corregir tarifas SLC y SLL en Plataforma (Tipo traslado = 3)
-- Factor 1 (1 unidad): SLC $4,320.00 / SLL $6,480.00
UPDATE `lgs_tarifas_proveedores` 
SET `precio_slc` = 4320.00, `precio_sll` = 6480.00 
WHERE `id_tipo_traslado` = 3 AND `num_vins_min` = 1;

-- Factores 2, 3 y 4 (2 o más unidades / Lowboy): SLC $3,850.00 / SLL $5,780.00
UPDATE `lgs_tarifas_proveedores` 
SET `precio_slc` = 3850.00, `precio_sll` = 5780.00 
WHERE `id_tipo_traslado` = 3 AND `num_vins_min` >= 2;

-- 2.3 Asegurar tarifas de Chofer / Rodando (Tipo traslado = 2): SLC $1,440.00 / SLL $2,240.00
UPDATE `lgs_tarifas_proveedores` 
SET `precio_slc` = 1440.00, `precio_sll` = 2240.00 
WHERE `id_tipo_traslado` = 2;

-- ------------------------------------------------------------------------------
-- 3. CLASIFICACIÓN DE MODELOS POR SEGMENTO (cat_modelos_vin)
-- Segmentos: 1=LIGEROS, 2=MEDIANO, 3=PESADO, 4=BUSES, 5=LOWBOY
-- ------------------------------------------------------------------------------
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 1; -- Tunland G7 TM GS 4x2
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 2; -- Aumark S3
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 3; -- Aumark S3 EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 4; -- Aumark S3-E6 AMT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 5; -- Aumark S3-E6 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 6; -- Aumark S4
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 7; -- Aumark S5
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 8; -- Aumark S5-E6 AMT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 9; -- Aumark S5-E6 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 10; -- Aumark S6
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 11; -- Aumark S6-E6 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 12; -- Miler 4.5T DR
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 13; -- Miler 4.84.5T RS
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 14; -- Miler-EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 15; -- Aumark TM
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 16; -- TM3 1.6L
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 17; -- TM EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 18; -- Wonder
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 19; -- Wonder EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 20; -- Tunland E5
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 21; -- Tunland G7 AT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 22; -- Tunland G7 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 23; -- Tunland G7 MT Gasolina
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 24; -- Tunland G9 AT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 25; -- Tunland V7 (MHEV)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 26; -- Tunland V9 (MHEV)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 27; -- TUNLAND EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 28; -- Tunland V7 gasolina 4x2
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 29; -- Tunland V7 gasolina 4x4
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 30; -- View CS2 Panel
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 31; -- View CS2 Royal
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 32; -- View CS2 Pasajeros
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 33; -- VIEW EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 34; -- VIEW Grand AT Panel
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 35; -- HiVan Pasajeros
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 36; -- HiVan Panel
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 37; -- HiVan-EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 38; -- EV-Hivan Pro
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 39; -- Toano Panel
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 40; -- Toano Pasajero
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 41; -- Aumark S8
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 42; -- Aumark S8 (R19.5)
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 43; -- Aumark S8-E6 AMT
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 44; -- Aumark S12-2402
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 45; -- Aumark S12-EV
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 46; -- Aumark S12-E6
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 47; -- Aumark S13
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 48; -- Aumark S20
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 49; -- Aumark S35
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 50; -- EST S38 AMT
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 51; -- EST S40
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 52; -- EST-A 2853-(CNG)
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 53; -- EST-A 6x2
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 54; -- EST-A 6x4
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 55; -- EST-A 6x4 560
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 56; -- EST-A / 3246 Rel. 3.08
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 57; -- EST-A / 3246 Rel. 3.36
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 58; -- Galaxy / 3256 / Rel. 2.71
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 59; -- Galaxy / 3256 / Rel. 3.08
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 60; -- Galaxy / 3256 / Rel. 3.36
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 61; -- Galaxy / 3256 / Rel. 3.70
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 62; -- Galaxus / Rel. 4.10
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 63; -- Galaxus / Rel. 3.70
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 64; -- Galaxus / Rel. 3.36
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 65; -- GTL / 2491 / Rel. 3.08
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 66; -- D9-BECCAR URBI G2
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 67; -- AUV BJ6118 Chasis CNG
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 68; -- FOTON D9 Midibus
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 69; -- Aumark S10 Bus
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 70; -- AYCO ORION FT Bus
UPDATE `cat_modelos_vin` SET `id_segmento` = 5 WHERE `id_cat_modelo_vin` = 71; -- Equipo Especial Lowboy
UPDATE `cat_modelos_vin` SET `id_segmento` = 5 WHERE `id_cat_modelo_vin` = 72; -- Chasis Sobredimensionado Lowboy
UPDATE `cat_modelos_vin` SET `id_segmento` = 4 WHERE `id_cat_modelo_vin` = 78; -- VIEW CS2 PASAJE COMBUSTION
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 79; -- GTL / 2491 / Rel.:3.08 (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 80; -- Aumark S6-E6-MT (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 81; -- EST-S38 / AMT (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 82; -- EST-A (6X4) Rel.:3.08 / X13-EVI (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 83; -- HiVan Pasajeros (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 84; -- VIEW CS2-2501 Pasajeros (NACIONAL)
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 85; -- S5-E6 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 86; -- Aumark S5-E6
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 87; -- Aumark S5-E6-MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 88; -- S3-E6 MT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 89; -- Tunland G7 4K22-DC
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 90; -- Wonder DC
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 91; -- Tunland G7 4X4
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 92; -- VIEW CS2-2501 Pasajeros
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 93; -- HiVan Cargo
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 94; -- GTL / 2491
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 95; -- EST-A 6X4 X13-E6
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 96; -- EST-S38 / AMT 6X4
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 97; -- GALAXUS
UPDATE `cat_modelos_vin` SET `id_segmento` = 3 WHERE `id_cat_modelo_vin` = 98; -- Galaxy 3256
UPDATE `cat_modelos_vin` SET `id_segmento` = 2 WHERE `id_cat_modelo_vin` = 99; -- S8-E6 AMT
UPDATE `cat_modelos_vin` SET `id_segmento` = 1 WHERE `id_cat_modelo_vin` = 100; -- Aumark S5-E6-MT (NACIONAL)

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- FIN DE SCRIPT DE MIGRACIÓN
-- ==============================================================================
