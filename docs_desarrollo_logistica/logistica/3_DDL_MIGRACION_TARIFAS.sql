-- ==============================================================================
-- 🚚 MIGRACIÓN ÉPICA 3: SEPARACIÓN DE TARIFAS POR PROVEEDOR (INDEPENDIENTE DE RUTA)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Crear tabla central de Tarifas por Proveedor
CREATE TABLE IF NOT EXISTS `lgs_tarifas_proveedores` (
    `id_tarifa`        BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_proveedor`     BIGINT NOT NULL COMMENT '0 = Tarifa Base General',
    `id_tipo_traslado` TINYINT NOT NULL COMMENT '1 = Madrina, 2 = Chofer Rodando',
    `id_segmento`      INT NOT NULL,
    `num_vins_min`     INT NOT NULL DEFAULT 1,
    `num_vins_max`     INT NOT NULL DEFAULT 15,
    `costo_por_km`     DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `precio_plano`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `factor`           DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
    `activo`           TINYINT NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tarifa_prov` (`id_proveedor`, `id_tipo_traslado`, `id_segmento`, `num_vins_min`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Migrar los costos actuales asumiendo que el costo por km máximo registrado es la tarifa general/del proveedor
INSERT IGNORE INTO `lgs_tarifas_proveedores` (
    `id_proveedor`, `id_tipo_traslado`, `id_segmento`, `num_vins_min`, `num_vins_max`, 
    `costo_por_km`, `precio_plano`, `factor`
)
SELECT 
    COALESCE(id_proveedor, 0), 
    id_tipo_traslado, 
    id_segmento, 
    num_vins_min, 
    num_vins_max, 
    MAX(costo_por_km), 
    MAX(precio_plano), 
    MAX(factor)
FROM lgs_costos_rutas
WHERE activo != 0
GROUP BY COALESCE(id_proveedor, 0), id_tipo_traslado, id_segmento, num_vins_min, num_vins_max;

SET FOREIGN_KEY_CHECKS = 1;
