-- ==============================================================================
-- 🚚 MIGRACIÓN / SEEDER: TRASLADISTAS A PROVEEDORES + SOPORTE DE TARIFAS POR PROVEEDOR
-- ==============================================================================
-- 1. Registra los 19 trasladistas (por nombre) en prv_cat_proveedores.
-- 2. Los clasifica con la actividad 'TRASLADO_UNIDADES' en prv_rel_proveedores_actividades.
-- 3. Habilita la flexibilidad de Tarifas por Ruta (Base compartida y por Proveedor).
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. CATÁLOGO DE ACTIVIDADES Y VINCULACIÓN
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prv_cat_actividades` (
    `id_actividad`  INT AUTO_INCREMENT PRIMARY KEY,
    `cve_actividad` VARCHAR(30) NOT NULL UNIQUE,
    `descripcion`   VARCHAR(150) NOT NULL,
    `estado`        TINYINT(1) DEFAULT 2,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `prv_cat_actividades` (`cve_actividad`, `descripcion`, `estado`) VALUES
('TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2);

CREATE TABLE IF NOT EXISTS `prv_rel_proveedores_actividades` (
    `id_proveedor` BIGINT NOT NULL,
    `id_actividad` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_proveedor`, `id_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. INSERCIÓN DE LOS 19 TRASLADISTAS EN prv_cat_proveedores (DATOS BASE / NOMBRE)
-- ------------------------------------------------------------------------------
INSERT INTO `prv_cat_proveedores` (
    `id_empresa`, `rfc`, `razon_social`, `nombre_comercial`, `id_tipo_persona`, 
    `id_regimen_fiscal`, `tipo`, `origen`, `estatus_onboarding`, `estatus_operativo`, `created_by`
) VALUES
(1, 'MLT180101AA1', 'MADRIMEX LOGISTICA Y TRASLADOS', 'MADRIMEX LOGISTICA Y TRASLADOS', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'GAVD850101AA1', 'JOSE DANIEL GARCIA VAZQUEZ', 'JOSE DANIEL GARCIA VAZQUEZ', 'F', 626, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'FCA180202BB2', 'FHAM CONSULTORIA Y ASESORIA LOGISTICA AUTOMOTRIZ', 'FHAM CONSULTORIA Y ASESORIA LOGISTICA AUTOMOTRIZ', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'ATR180303CC3', 'AUTOMOTIVE TRANSLEAD', 'AUTOMOTIVE TRANSLEAD', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'MAL180404DD4', 'MALDONADITO', 'MALDONADITO', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'RLG180505EE5', 'RBK LOGISTICS GROUP', 'RBK LOGISTICS GROUP', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'SLO180606FF6', 'SCX LOGISTICS', 'SCX LOGISTICS', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'TMO180707GG7', 'TLC MOTION SA DE CV', 'TLC MOTION SA DE CV', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'CLI180808HH8', 'CLINICAR', 'CLINICAR', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'JIL180909II9', 'J&D INTERNATIONAL LOGISTICS', 'J&D INTERNATIONAL LOGISTICS', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'LTR181010JJ0', 'LIVE TRANSFER', 'LIVE TRANSFER', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'KME181111KK1', 'KUUMKUMI DE MEXICO', 'KUUMKUMI DE MEXICO', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'CFM181212LL2', 'CAR FLEET MANAGER', 'CAR FLEET MANAGER', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'RTR190113MM3', 'ROAD TRANSFER', 'ROAD TRANSFER', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'CEM190214NN4', 'CAJAS Y EMBALAJES MMARAN', 'CAJAS Y EMBALAJES MMARAN', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'GHE190315OO5', 'GRUAS HERRERA', 'GRUAS HERRERA', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'INT190416PP6', 'INTERDABS', 'INTERDABS', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'LTE190517QQ7', 'LOGISTICA TEROMO', 'LOGISTICA TEROMO', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1),
(1, 'TSA190618RR8', 'TRASLADOS SACBE', 'TRASLADOS SACBE', 'M', 601, 'Externo', 'Nacional', 'Aprobado', 1, 1)
ON DUPLICATE KEY UPDATE 
    `razon_social` = VALUES(`razon_social`),
    `nombre_comercial` = VALUES(`nombre_comercial`),
    `estatus_onboarding` = 'Aprobado',
    `estatus_operativo` = 1,
    `deleted_at` = NULL;

-- ------------------------------------------------------------------------------
-- 3. VINCULAR PROVEEDORES CON LA ACTIVIDAD 'TRASLADO_UNIDADES'
-- ------------------------------------------------------------------------------
SET @id_act_traslado = (SELECT `id_actividad` FROM `prv_cat_actividades` WHERE `cve_actividad` = 'TRASLADO_UNIDADES' LIMIT 1);

INSERT IGNORE INTO `prv_rel_proveedores_actividades` (`id_proveedor`, `id_actividad`)
SELECT `id_proveedor`, @id_act_traslado
FROM `prv_cat_proveedores`
WHERE `razon_social` IN (
    'MADRIMEX LOGISTICA Y TRASLADOS',
    'JOSE DANIEL GARCIA VAZQUEZ',
    'FHAM CONSULTORIA Y ASESORIA LOGISTICA AUTOMOTRIZ',
    'AUTOMOTIVE TRANSLEAD',
    'MALDONADITO',
    'RBK LOGISTICS GROUP',
    'SCX LOGISTICS',
    'TLC MOTION SA DE CV',
    'CLINICAR',
    'J&D INTERNATIONAL LOGISTICS',
    'LIVE TRANSFER',
    'KUUMKUMI DE MEXICO',
    'CAR FLEET MANAGER',
    'ROAD TRANSFER',
    'CAJAS Y EMBALAJES MMARAN',
    'GRUAS HERRERA',
    'INTERDABS',
    'LOGISTICA TEROMO',
    'TRASLADOS SACBE'
);

-- ------------------------------------------------------------------------------
-- 4. SOPORTE DE FLEXIBILIDAD EN TARIFAS POR PROVEEDOR Y POR RUTA
-- ------------------------------------------------------------------------------
-- Matriz auxiliar por proveedor y segmento (si se requiere tarifa plana por km)
CREATE TABLE IF NOT EXISTS `lgs_costos_proveedor_segmento` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor` BIGINT NOT NULL,
    `id_segmento` INT NULL,
    `num_vins_min` INT NOT NULL DEFAULT 1,
    `num_vins_max` INT NOT NULL DEFAULT 99,
    `costo_por_km` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `factor` DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    `activo` TINYINT NOT NULL DEFAULT 2,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_prov_costo` (`id_proveedor`, `id_segmento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
