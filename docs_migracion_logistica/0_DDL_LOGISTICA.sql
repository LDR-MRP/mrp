-- ==============================================================================
-- 🚚 SCRIPT DE BASE DE DATOS — MÓDULO DE LOGÍSTICA
-- Versión: 04 de Agosto 2026 (Múltiples Paradas, Madrina/Chofer Rodando)
-- ==============================================================================
-- Este script crea todas las tablas necesarias para las Épicas 2, 3, 4, 5 y 6.
-- Está listo para ejecutarse en Desarrollo y Producción.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────
-- ÉPICA 2: CATÁLOGOS DE LOGÍSTICA
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_cat_tipo_traslado (
    id_tipo_traslado TINYINT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    activo           TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_tipo_traslado (nombre) VALUES
('Madrina'), ('Chofer (Rodando)');

CREATE TABLE IF NOT EXISTS lgs_cat_motivo_envio (
    id_motivo   INT AUTO_INCREMENT PRIMARY KEY,
    cve_motivo  VARCHAR(40) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NOT NULL,
    activo      TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_motivo_envio (cve_motivo, descripcion) VALUES
('ENTREGA_DIST',        'Entrega a Distribuidor'),
('TRASLADO_CARROCERIA', 'Traslado a Carrocería'),
('MARKETING',           'Marketing / Exposición'),
('DEMO',                'Unidad Demo'),
('PRUEBAS',             'Unidad de Pruebas'),
('PILOTO',              'Unidad Piloto'),
('DEVOLUCION',          'Devolución'),
('OTRO',                'Otro motivo');

CREATE TABLE IF NOT EXISTS lgs_cat_tipo_destino (
    id_tipo_destino TINYINT AUTO_INCREMENT PRIMARY KEY,
    cve_destino     VARCHAR(40) NOT NULL UNIQUE,
    descripcion     VARCHAR(150) NOT NULL,
    activo          TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_tipo_destino (cve_destino, descripcion) VALUES
('DISTRIBUIDOR',  'Distribuidor / Concesionario'),
('CARROCERO',     'Carrocero / Adaptaciones'),
('CLIENTE_FINAL', 'Cliente Final'),
('ALMACEN',       'Almacén'),
('PLANTA',        'Planta'),
('OTRO',          'Otro destino');

CREATE TABLE IF NOT EXISTS lgs_cat_origenes (
    id_origen  INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(150) NOT NULL,
    direccion  VARCHAR(255) NULL,
    lat        DECIMAL(10,7) NULL COMMENT 'Latitud para API de km',
    lng        DECIMAL(10,7) NULL COMMENT 'Longitud para API de km',
    activo     TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_origenes (nombre) VALUES
('Planta 1'), ('Planta 2'), ('Planta 3'), ('Planta 4'), ('Planta 5'),
('Almacén Montenegro');

CREATE TABLE IF NOT EXISTS lgs_cat_destinos (
    id_destino      INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    nombre_libre    VARCHAR(255) NULL COMMENT 'Alias personalizado',
    id_tipo_destino TINYINT NULL,
    direccion       VARCHAR(255) NULL,
    lat             DECIMAL(10,7) NULL,
    lng             DECIMAL(10,7) NULL,
    activo          TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_destino) REFERENCES lgs_cat_tipo_destino(id_tipo_destino) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- ÉPICA 2: MÓDULO DE ENVÍOS PRINCIPAL
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_envios (
    id_envio                BIGINT AUTO_INCREMENT PRIMARY KEY,
    folio                   VARCHAR(20) UNIQUE NOT NULL COMMENT 'EN-000001',
    id_tipo_traslado        TINYINT NULL,
    id_motivo               INT NULL,
    id_proveedor            BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    id_origen               INT NULL COMMENT 'FK lgs_cat_origenes',
    id_destino              BIGINT NULL COMMENT 'FK cli_clientes / lgs_cat_destinos',
    destino_nombre_libre    VARCHAR(255) NULL COMMENT 'Alias libre del punto destino',
    km_total                DECIMAL(10,2) DEFAULT 0 COMMENT 'Suma de tramos si hay múltiples paradas',
    costo_total             DECIMAL(12,2) NULL COMMENT 'Calculado dinámicamente según VINs/Madrinas/Choferes',
    fecha_tentativa_envio   DATE NULL,
    fecha_tentativa_llegada DATE NULL,
    fecha_salida_real       DATETIME NULL,
    fecha_llegada_real      DATETIME NULL COMMENT 'Cuando todos los destinos fueron entregados',
    observaciones           TEXT NULL,
    id_estado               TINYINT DEFAULT 1
        COMMENT '1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=En Ejecución 6=En Tránsito 7=Entregado 8=Cancelado',
    created_by              BIGINT UNSIGNED NULL,
    updated_by              BIGINT UNSIGNED NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              TIMESTAMP NULL,
    FOREIGN KEY (id_tipo_traslado) REFERENCES lgs_cat_tipo_traslado(id_tipo_traslado) ON DELETE SET NULL,
    FOREIGN KEY (id_motivo)        REFERENCES lgs_cat_motivo_envio(id_motivo) ON DELETE SET NULL,
    FOREIGN KEY (id_origen)        REFERENCES lgs_cat_origenes(id_origen) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgs_envios_paradas (
    id_parada            BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio             BIGINT NOT NULL,
    orden                TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = primera parada, 2 = segunda, etc.',
    id_destino_cat       BIGINT NULL COMMENT 'FK a cli_clientes (distribuidor) o lgs_cat_destinos',
    destino_nombre_libre VARCHAR(255) NULL COMMENT 'Nombre libre del punto de entrega',
    km_tramo             DECIMAL(10,2) DEFAULT 0 COMMENT 'Km desde el origen o desde la parada anterior',
    observaciones        TEXT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Paradas ordenadas de la ruta de un envio';

CREATE TABLE IF NOT EXISTS lgs_envios_vins (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL COMMENT 'FK mrp_unidades_terminadas',
    id_destino       INT NULL COMMENT 'Parada/Destino donde se bajará este VIN',
    id_parada        BIGINT NULL COMMENT 'FK lgs_envios_paradas — parada donde se descarga',
    destino_nombre_libre VARCHAR(255) NULL COMMENT 'Alias libre para entrega especial',
    id_madrina       BIGINT NULL COMMENT 'Madrina en la que viaja (si tipo = Madrina)',
    id_chofer        BIGINT NULL COMMENT 'Chofer que maneja (si tipo = Chofer Rodando)',
    posicion_acomodo TINYINT UNSIGNED NULL
        COMMENT 'Orden de carga en la madrina. 1 = primero en subir. NULL si es Chofer Rodando',
    costo_unidad     DECIMAL(12,2) NULL COMMENT 'Calculado automáticamente',
    fecha_entrega_real DATETIME NULL COMMENT 'Fecha de llegada para esta unidad',
    recibe_nombre    VARCHAR(150) NULL COMMENT 'Quien recibió la unidad',
    id_estado        TINYINT DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_envio_vin (id_envio, id_unidad),
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES lgs_cat_destinos(id_destino) ON DELETE SET NULL,
    FOREIGN KEY (id_parada)  REFERENCES lgs_envios_paradas(id_parada) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgs_costos_proveedor_segmento (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor    BIGINT NOT NULL,
    id_segmento     INT NULL COMMENT 'FK mrp_segmentos o string',
    num_vins_min    TINYINT DEFAULT 1,
    num_vins_max    TINYINT DEFAULT 99,
    costo_por_km    DECIMAL(10,4) NOT NULL,
    factor          DECIMAL(5,2) DEFAULT 1.00,
    activo          TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- ÉPICA 3: PLANEACIONES Y APROBACIONES
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_planeaciones (
    id_planeacion BIGINT AUTO_INCREMENT PRIMARY KEY,
    folio         VARCHAR(20) UNIQUE NOT NULL COMMENT 'EX-000001',
    descripcion   VARCHAR(255) NULL,
    km_total      DECIMAL(10,2) NULL,
    costo_total   DECIMAL(12,2) NULL,
    id_estado     TINYINT DEFAULT 1 COMMENT '1=Creada 2=Enviada 3=Regresada 5=Aprobada',
    obs_operador  TEXT NULL,
    obs_aprobador TEXT NULL,
    created_by    BIGINT UNSIGNED NULL,
    aprobado_by   BIGINT UNSIGNED NULL,
    aprobado_at   DATETIME NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgs_planeaciones_envios (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_planeacion BIGINT NOT NULL,
    id_envio      BIGINT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_plan_envio (id_planeacion, id_envio),
    FOREIGN KEY (id_planeacion) REFERENCES lgs_planeaciones(id_planeacion) ON DELETE CASCADE,
    FOREIGN KEY (id_envio)      REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgs_aprobadores (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_usuario BIGINT NOT NULL,
    activo     TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- ÉPICA 4: SOLICITUDES A ÁREA DE ENTREGAS
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_solicitudes_entrega (
    id_solicitud     BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL COMMENT 'FK mrp_unidades_terminadas',
    posicion_acomodo TINYINT UNSIGNED NULL,
    id_estado        TINYINT DEFAULT 1 COMMENT '1=Solicitada 2=Entregada a Trasladista 3=Cancelada',
    solicitado_by    BIGINT UNSIGNED NULL,
    confirmado_by    BIGINT UNSIGNED NULL,
    solicitado_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmado_at    DATETIME NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- ÉPICA 5: EVIDENCIAS MULTIMEDIA
-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_evidencias (
    id_evidencia   BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio       BIGINT NOT NULL,
    id_vin         BIGINT NULL COMMENT 'Opcional: Si la evidencia pertenece solo a un VIN o parada específica',
    tipo           TINYINT NOT NULL COMMENT '1=Salida(Origen) 2=Llegada(Destino)',
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Ruta: Assets/uploads/logistica/evidencias/',
    tipo_archivo   VARCHAR(10) NULL COMMENT 'jpg, png, mp4...',
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
