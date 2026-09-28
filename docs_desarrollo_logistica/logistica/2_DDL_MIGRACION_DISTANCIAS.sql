-- ==============================================================================
-- 🚚 MIGRACIÓN ÉPICA 2: MEMORIA PROGRESIVA DE DISTANCIAS BIDIRECCIONALES
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `lgs_distancias` (
    `id_distancia`    INT AUTO_INCREMENT PRIMARY KEY,
    `id_ubicacion_a`  INT NOT NULL COMMENT 'FK lgs_cat_ubicaciones (ID menor para bidireccional)',
    `id_ubicacion_b`  INT NOT NULL COMMENT 'FK lgs_cat_ubicaciones (ID mayor para bidireccional)',
    `km`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_distancia_par` (`id_ubicacion_a`, `id_ubicacion_b`),
    FOREIGN KEY (`id_ubicacion_a`) REFERENCES `lgs_cat_ubicaciones`(`id_ubicacion`) ON DELETE CASCADE,
    FOREIGN KEY (`id_ubicacion_b`) REFERENCES `lgs_cat_ubicaciones`(`id_ubicacion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poblar memoria inicial desde lgs_costos_rutas existentes donde km > 0
INSERT IGNORE INTO `lgs_distancias` (`id_ubicacion_a`, `id_ubicacion_b`, `km`)
SELECT 
    LEAST(r.id_origen, r.id_destino) AS id_ubicacion_a,
    GREATEST(r.id_origen, r.id_destino) AS id_ubicacion_b,
    MAX(r.km) AS km
FROM lgs_costos_rutas r
WHERE r.id_origen > 0 AND r.id_destino > 0 AND r.km > 0
GROUP BY LEAST(r.id_origen, r.id_destino), GREATEST(r.id_origen, r.id_destino);

SET FOREIGN_KEY_CHECKS = 1;
