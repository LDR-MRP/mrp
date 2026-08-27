-- A. Catálogo de Segmentos
CREATE TABLE IF NOT EXISTS lgs_cat_segmentos (
    id_segmento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL COMMENT 'Ej: LIGEROS, MEDIANO, PESADO, BUSES, LOWBOY',
    descripcion TEXT NULL,
    activo TINYINT(1) DEFAULT 2 COMMENT '1=Inactivo, 2=Activo, 0=Eliminado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar Datos Semilla
INSERT IGNORE INTO lgs_cat_segmentos (id_segmento, nombre, descripcion) VALUES
(1, 'LIGEROS', 'Vehículos ligeros: Miller, S3, S5, S6, etc.'),
(2, 'MEDIANO', 'Vehículos medianos: S8, S12, S20, etc.'),
(3, 'PESADO', 'Vehículos pesados: EST-A, Galaxy, EST-S35, S35, S38, ISG, X13, etc.'),
(4, 'BUSES', 'Buses: AUV, Arañas o buses carrozados'),
(5, 'LOWBOY', 'Equipo Lowboy');

-- B. Agregar Relación en Catálogo de Modelos VIN
ALTER TABLE cat_modelos_vin ADD COLUMN id_segmento INT NULL AFTER id_planta;
ALTER TABLE cat_modelos_vin ADD CONSTRAINT fk_modelos_vin_segmento FOREIGN KEY (id_segmento) REFERENCES lgs_cat_segmentos(id_segmento) ON DELETE SET NULL;

-- C. Tabla de Costos de Ruta Normalizada con Volumen (Descuentos)
CREATE TABLE IF NOT EXISTS lgs_costos_rutas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_tipo_traslado TINYINT NOT NULL COMMENT 'FK lgs_cat_tipo_traslado (1=Madrina, 2=Chofer Rodando)',
    id_origen INT NOT NULL COMMENT 'FK lgs_cat_origenes',
    id_destino INT NOT NULL COMMENT 'FK lgs_cat_destinos',
    id_segmento INT NOT NULL COMMENT 'FK lgs_cat_segmentos',
    num_vins_min TINYINT NOT NULL DEFAULT 1 COMMENT 'Mínimo de unidades para esta tarifa/factor',
    num_vins_max TINYINT NOT NULL DEFAULT 99 COMMENT 'Máximo de unidades para esta tarifa/factor',
    km DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_por_km DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    precio_plano DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Costo fijo (ej: ferry u otros fijos por tramo)',
    factor DECIMAL(5,2) DEFAULT 1.00 COMMENT 'Factor de descuento/ajuste multiplicador (ej: 0.85)',
    activo TINYINT(1) DEFAULT 2 COMMENT '1=Inactivo, 2=Activo, 0=Eliminado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_traslado) REFERENCES lgs_cat_tipo_traslado(id_tipo_traslado) ON DELETE CASCADE,
    FOREIGN KEY (id_origen) REFERENCES lgs_cat_origenes(id_origen) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES lgs_cat_destinos(id_destino) ON DELETE CASCADE,
    FOREIGN KEY (id_segmento) REFERENCES lgs_cat_segmentos(id_segmento) ON DELETE CASCADE,
    UNIQUE KEY uq_ruta_traslado_segmento_vins (id_tipo_traslado, id_origen, id_destino, id_segmento, num_vins_min, num_vins_max)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
