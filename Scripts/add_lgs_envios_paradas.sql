-- ==============================================================================
-- Migración: Soporte Multi-Destino / Múltiples Paradas por Envío
-- Fecha: 12 Agosto 2026
-- ==============================================================================

-- 1. Tabla de paradas ordenadas por envío
CREATE TABLE IF NOT EXISTS lgs_envios_paradas (
    id_parada            BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio             BIGINT NOT NULL,
    orden                TINYINT UNSIGNED NOT NULL DEFAULT 1
                         COMMENT '1 = primera parada, 2 = segunda, etc.',
    id_destino_cat       BIGINT NULL
                         COMMENT 'FK a cli_clientes (distribuidor) o lgs_cat_destinos',
    destino_nombre_libre VARCHAR(255) NULL
                         COMMENT 'Nombre libre del punto de entrega',
    km_tramo             DECIMAL(10,2) DEFAULT 0
                         COMMENT 'Km desde el origen o desde la parada anterior',
    observaciones        TEXT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Paradas ordenadas de la ruta de un envio';

-- 2. Agregar columna id_parada a lgs_envios_vins
ALTER TABLE lgs_envios_vins
    ADD COLUMN id_parada BIGINT NULL
        COMMENT 'FK lgs_envios_paradas - parada donde se baja este VIN'
        AFTER id_destino;
