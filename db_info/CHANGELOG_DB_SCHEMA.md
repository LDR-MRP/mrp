# Bitácora de Cambios de Base de Datos (DB Migrations Log)

Este documento registra la estructura de todas las tablas **nuevas**, **modificadas** o **relacionales** creadas en la base de datos `db_mrp` durante el desarrollo de nuevas funcionalidades y refactorizaciones.

---

## 📅 Cambios Recientes (Módulo: Proveedores & Logística `Prv_`)

### 1. 📊 Tabla Catálogo: `prv_cat_actividades`
Catálogo de actividades o giros de negocio que un proveedor puede desempeñar en el sistema.

* **Estatus en BD:** ✅ Creada y ejecutada en `mrp-db`.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario / Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_actividad`** | `int` | NO | `PRI` | `NULL` | `auto_increment` | Identificador único de la actividad |
| **`cve_actividad`** | `varchar(30)` | NO | `UNI` | `NULL` | | Clave única de la actividad (`TRASLADO_UNIDADES`, `CARROCERO`, etc.) |
| **`descripcion`** | `varchar(150)` | NO | | `NULL` | | Descripción detallada de la actividad |
| **`estado`** | `tinyint(1)` | SÍ | | `2` | | Estado del registro (0=Eliminada, 1=Inactiva, 2=Activa) |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de creación |
| **`updated_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | `on update CURRENT_TIMESTAMP` | Fecha de actualización |

#### 📝 Datos Semilla (Inserts Iniciales):
```sql
INSERT INTO prv_cat_actividades (id_actividad, cve_actividad, descripcion, estado) VALUES
(1, 'TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2),
(2, 'CARROCERO', 'Servicios de Carrozado y Adaptaciones de Vehículos', 2),
(3, 'REFACCIONES', 'Suministro de Refacciones y Componentes', 2),
(4, 'TI_SOFTWARE', 'Tecnologías de la Información y Licenciamiento', 2),
(5, 'SERVICIOS_GRALES', 'Servicios Generales, Mantenimiento y Operativos', 2);
```

---

### 2. 📊 Tabla Relacional (Pivot N:M): `prv_rel_proveedores_actividades`
Asocia los proveedores registrados en `prv_cat_proveedores` con una o múltiples actividades registradas en `prv_cat_actividades`.

* **Estatus en BD:** ✅ Creada y ejecutada en `mrp-db`.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario / Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_rel_actividad`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | ID primario relacional |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `NULL` | | FK a `prv_cat_proveedores.id_proveedor` |
| **`id_actividad`** | `int` | NO | `MUL` | `NULL` | | FK a `prv_cat_actividades.id_actividad` |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de asignación |

* **Índices:** `UNIQUE KEY uq_prv_actividad (id_proveedor, id_actividad)`

---

### 3. 📝 Modificación a Tabla: `prv_det_madrinas`
Se agregaron nuevos atributos de la unidad/nodriza para control logístico completo.

* **Estatus en BD:** ✅ Modificada en `mrp-db`.

| Columna Nueva | Tipo | Nulo | Descripción |
| :--- | :--- | :---: | :--- |
| **`placa_caja`** | `varchar(30)` | SÍ | Placas de la caja o remolque articulado |
| **`anio`** | `int` | SÍ | Año del modelo del vehículo nodriza |
| **`color`** | `varchar(50)` | SÍ | Color del tracto/madrina |
| **`num_serie_vin`** | `varchar(50)` | SÍ | Número de Serie / VIN de la unidad |

---

### 4. 📊 Tabla Detalle: `prv_det_madrina_chofer_historial`
Registra el historial dinámico de conductores que han manejado la madrina a lo largo del tiempo, manteniendo 1 único chofer activo a la vez.

* **Estatus en BD:** ✅ Creada y ejecutada en `mrp-db`.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario / Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_historial`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | ID primario del registro histórico |
| **`id_madrina`** | `bigint unsigned` | NO | `MUL` | `NULL` | | FK a `prv_det_madrinas.id_madrina` |
| **`id_chofer`** | `bigint unsigned` | NO | `MUL` | `NULL` | | FK a `prv_det_choferes.id_chofer` |
| **`fecha_inicio`** | `datetime` | NO | | `CURRENT_TIMESTAMP` | | Fecha y hora en que inició el turno |
| **`fecha_fin`** | `datetime` | SÍ | | `NULL` | | Fecha y hora en que concluyó el turno |
| **`activo`** | `tinyint(1)` | SÍ | | `1` | | `1` = Conductor activo actualmente, `0` = Conductor previo |
| **`observaciones`** | `text` | SÍ | | `NULL` | | Motivo o notas de la asignación |
| **`created_by`** | `bigint unsigned` | SÍ | | `NULL` | | Usuario que asignó el chofer |
| **`updated_by`** | `bigint unsigned` | SÍ | | `NULL` | | Usuario que desasignó/actualizó |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de creación |
| **`updated_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | `on update CURRENT_TIMESTAMP` | Fecha de actualización |

---

## 📜 Script SQL de Migración Completo

```sql
-- 1. Catálogo de Actividades
CREATE TABLE IF NOT EXISTS prv_cat_actividades (
    id_actividad INT AUTO_INCREMENT PRIMARY KEY,
    cve_actividad VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(150) NOT NULL,
    estado TINYINT(1) DEFAULT 2 COMMENT '0=Eliminada 1=Inactiva 2=Activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO prv_cat_actividades (id_actividad, cve_actividad, descripcion, estado) VALUES
(1, 'TRASLADO_UNIDADES', 'Traslado y Logística de Unidades (Trasladistas)', 2),
(2, 'CARROCERO', 'Servicios de Carrozado y Adaptaciones de Vehículos', 2),
(3, 'REFACCIONES', 'Suministro de Refacciones y Componentes', 2),
(4, 'TI_SOFTWARE', 'Tecnologías de la Información y Licenciamiento', 2),
(5, 'SERVICIOS_GRALES', 'Servicios Generales, Mantenimiento y Operativos', 2);

-- 2. Tabla Pivot Relacional N:M
CREATE TABLE IF NOT EXISTS prv_rel_proveedores_actividades (
    id_rel_actividad BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor BIGINT UNSIGNED NOT NULL,
    id_actividad INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_actividad) REFERENCES prv_cat_actividades(id_actividad) ON DELETE CASCADE,
    UNIQUE KEY uq_prv_actividad (id_proveedor, id_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Atributos adicionales en prv_det_madrinas
ALTER TABLE prv_det_madrinas
ADD COLUMN IF NOT EXISTS placa_caja VARCHAR(30) NULL AFTER placas,
ADD COLUMN IF NOT EXISTS anio INT NULL AFTER modelo,
ADD COLUMN IF NOT EXISTS color VARCHAR(50) NULL AFTER anio,
ADD COLUMN IF NOT EXISTS num_serie_vin VARCHAR(50) NULL AFTER color;

-- 4. Tabla de Historial de Choferes de Madrina
CREATE TABLE IF NOT EXISTS prv_det_madrina_chofer_historial (
    id_historial BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_madrina BIGINT UNSIGNED NOT NULL,
    id_chofer BIGINT UNSIGNED NOT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NULL,
    activo TINYINT(1) DEFAULT 1 COMMENT '1=Chofer asignado actualmente 0=Histórico',
    observaciones TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_madrina) REFERENCES prv_det_madrinas(id_madrina) ON DELETE CASCADE,
    FOREIGN KEY (id_chofer) REFERENCES prv_det_choferes(id_chofer) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 📅 Cambios Épica 2 — Módulo Logística Operativa (`Lgs_`)

### 5. 📊 Tablas de Logística Operativa (`Lgs_`) — Épicas 2 a 6

* **Estatus en BD:** ✅ Creadas y ejecutadas en `mrp-db` (11 de Agosto 2026).
* **Script Fuente:** [docs_migracion_logistica/0_DDL_LOGISTICA.sql](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/0_DDL_LOGISTICA.sql)

#### Resumen de Tablas Creadas:

| Tabla | Tipo | Descripción | Estatus |
| :--- | :--- | :--- | :---: |
| **`lgs_cat_tipo_traslado`** | Catálogo | Tipos de traslado (Madrina, Chofer Rodando) | ✅ Creada |
| **`lgs_cat_motivo_envio`** | Catálogo | Motivos de traslado (Entrega Dist., Carrocería, Devolución, etc.) | ✅ Creada |
| **`lgs_cat_tipo_destino`** | Catálogo | Tipos de destino (Distribuidor, Carrocero, Cliente Final, Almacén, Planta, Otro) | ✅ Creada |
| **`lgs_cat_origenes`** | Catálogo | Puntos de origen con coordenadas GPS para cálculo de distancia | ✅ Creada |
| **`lgs_cat_destinos`** | Catálogo | Puntos destino con coordenadas GPS y alias personalizados | ✅ Creada |
| **`lgs_envios`** | Principal | Cabecera de envíos con folio `EN-`, origen, tipo, costo y fechas | ✅ Creada |
| **`lgs_envios_vins`** | Detalle | VINs asignados al envío con posición de acomodo en madrina | ✅ Creada |
| **`lgs_costos_proveedor_segmento`** | Tarifas | Matriz de costo por km según proveedor, segmento y factor volumen | ✅ Creada |
| **`lgs_planeaciones`** | Agrupador | Planeaciones para autorización ejecutiva con folio `EX-` | ✅ Creada |
| **`lgs_planeaciones_envios`** | Pivot N:M | Relación entre planeaciones y envíos | ✅ Creada |
| **`lgs_aprobadores`** | Catálogo | Padrón de usuarios autorizados para aprobar planeaciones | ✅ Creada |
| **`lgs_solicitudes_entrega`** | Despacho | Solicitudes de despacho al área de entregas físicas | ✅ Creada |
| **`lgs_evidencias`** | Multimedia | Fotos y vídeos de salida (origen) y entrega (destino) | ✅ Creada |

---

## 📜 Script SQL de Migración Ejecutado (`0_DDL_LOGISTICA.sql`)

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Catálogos Base de Logística
CREATE TABLE IF NOT EXISTS lgs_cat_tipo_traslado (
    id_tipo_traslado TINYINT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    activo           TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_tipo_traslado (nombre) VALUES ('Madrina'), ('Chofer (Rodando)');

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
('Planta 1'), ('Planta 2'), ('Planta 3'), ('Planta 4'), ('Planta 5'), ('Almacén Montenegro');

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

-- 2. Envíos y Detalles
CREATE TABLE IF NOT EXISTS lgs_envios (
    id_envio                BIGINT AUTO_INCREMENT PRIMARY KEY,
    folio                   VARCHAR(20) UNIQUE NOT NULL COMMENT 'EN-000001',
    id_tipo_traslado        TINYINT NULL,
    id_motivo               INT NULL,
    id_proveedor            BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    id_origen               INT NULL COMMENT 'FK lgs_cat_origenes',
    id_destino              BIGINT NULL COMMENT 'FK cli_clientes / lgs_cat_destinos',
    destino_nombre_libre    VARCHAR(255) NULL COMMENT 'Alias libre del punto destino',
    km_total                DECIMAL(10,2) DEFAULT 0,
    costo_total             DECIMAL(12,2) NULL,
    fecha_tentativa_envio   DATE NULL,
    fecha_tentativa_llegada DATE NULL,
    fecha_salida_real       DATETIME NULL,
    fecha_llegada_real      DATETIME NULL,
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

CREATE TABLE IF NOT EXISTS lgs_envios_vins (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL,
    id_destino       INT NULL,
    destino_nombre_libre VARCHAR(255) NULL,
    id_madrina       BIGINT NULL,
    id_chofer        BIGINT NULL,
    posicion_acomodo TINYINT UNSIGNED NULL,
    costo_unidad     DECIMAL(12,2) NULL,
    fecha_entrega_real DATETIME NULL,
    recibe_nombre    VARCHAR(150) NULL,
    id_estado        TINYINT DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_envio_vin (id_envio, id_unidad),
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES lgs_cat_destinos(id_destino) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
```

---

## 📅 Cambios Recientes — Administrador de Costos Logísticos y Tarifario por Segmento (`Lgs_costos`)

* **Fecha:** 17 de Agosto 2026
* **Estatus en BD:** ✅ Creadas y ejecutadas en `mrp-db`.
* **Script Fuente:** [db_info/lgs_costos_rutas_segmentos.sql](file:///home/christianguarneros/proyectos/mrp/db_info/lgs_costos_rutas_segmentos.sql)

### 6. 📊 Tabla Catálogo: `lgs_cat_segmentos`
Catálogo maestro de segmentos vehiculares para cotización logística (Ligeros, Medianos, Pesados, Buses, Lowboy).

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_segmento`** | `int` | NO | `PRI` | `NULL` | `auto_increment` | Identificador único del segmento |
| **`nombre`** | `varchar(100)` | NO | `UNI` | `NULL` | | Nombre del segmento (`LIGEROS`, `MEDIANO`, `PESADO`, `BUSES`, `LOWBOY`) |
| **`descripcion`** | `text` | SÍ | | `NULL` | | Descripción y ejemplos de modelos pertenecientes al segmento |
| **`activo`** | `tinyint(1)` | SÍ | | `2` | | 1=Inactivo, 2=Activo, 0=Eliminado |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de registro |
| **`updated_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | `on update CURRENT_TIMESTAMP` | Fecha de actualización |

### 7. 📝 Modificación a Tabla: `cat_modelos_vin`
Se agregó la relación foránea hacia el segmento de tarifa logística.

| Columna Nueva | Tipo | Nulo | Descripción |
| :--- | :--- | :---: | :--- |
| **`id_segmento`** | `int` | SÍ | FK hacia `lgs_cat_segmentos.id_segmento` (ON DELETE SET NULL) |

### 8. 📊 Tabla Principal: `lgs_costos_rutas`
Matriz normalizada de tarifas por ruta, tipo de traslado, segmento y rangos de unidades para descuentos por escala.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | ID primario de la tarifa |
| **`id_tipo_traslado`** | `tinyint` | NO | `MUL` | `NULL` | | FK a `lgs_cat_tipo_traslado` (1=Madrina, 2=Chofer Rodando) |
| **`id_origen`** | `int` | NO | `MUL` | `NULL` | | FK a `lgs_cat_origenes` |
| **`id_destino`** | `int` | NO | `MUL` | `NULL` | | FK a `lgs_cat_destinos` |
| **`id_segmento`** | `int` | NO | `MUL` | `NULL` | | FK a `lgs_cat_segmentos` |
| **`num_vins_min`** | `tinyint` | NO | | `1` | | Cantidad mínima de VINs para activar este rango/factor |
| **`num_vins_max`** | `tinyint` | NO | | `99` | | Cantidad máxima de VINs para este rango/factor |
| **`km`** | `decimal(10,2)` | NO | | `0.00` | | Kilómetros de distancia para la ruta |
| **`costo_por_km`** | `decimal(10,4)` | NO | | `0.0000` | | Tarifa unitaria por kilómetro |
| **`precio_plano`** | `decimal(12,2)` | NO | | `0.00` | | Costo fijo por tramo (ferry, caseta especial, etc.) |
| **`factor`** | `decimal(5,2)` | SÍ | | `1.00` | | Multiplicador de volumen o descuento |
| **`activo`** | `tinyint(1)` | SÍ | | `2` | | 1=Inactivo, 2=Activo, 0=Eliminado |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de creación |
| **`updated_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | `on update CURRENT_TIMESTAMP` | Fecha de actualización |

* **Índices:** `UNIQUE KEY uq_ruta_traslado_segmento_vins (id_tipo_traslado, id_origen, id_destino, id_segmento, num_vins_min, num_vins_max)`

```sql
-- DDL Ejecutado:
CREATE TABLE IF NOT EXISTS lgs_cat_segmentos (
    id_segmento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_segmentos (id_segmento, nombre, descripcion) VALUES
(1, 'LIGEROS', 'Vehículos ligeros: Miller, S3, S5, S6, etc.'),
(2, 'MEDIANO', 'Vehículos medianos: S8, S12, S20, etc.'),
(3, 'PESADO', 'Vehículos pesados: EST-A, Galaxy, EST-S35, S35, S38, ISG, X13, etc.'),
(4, 'BUSES', 'Buses: AUV, Arañas o buses carrozados'),
(5, 'LOWBOY', 'Equipo Lowboy');

ALTER TABLE cat_modelos_vin ADD COLUMN id_segmento INT NULL AFTER id_planta;
ALTER TABLE cat_modelos_vin ADD CONSTRAINT fk_modelos_vin_segmento FOREIGN KEY (id_segmento) REFERENCES lgs_cat_segmentos(id_segmento) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS lgs_costos_rutas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_tipo_traslado TINYINT NOT NULL,
    id_origen INT NOT NULL,
    id_destino INT NOT NULL,
    id_segmento INT NOT NULL,
    num_vins_min TINYINT NOT NULL DEFAULT 1,
    num_vins_max TINYINT NOT NULL DEFAULT 99,
    km DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_por_km DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    precio_plano DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    factor DECIMAL(5,2) DEFAULT 1.00,
    activo TINYINT(1) DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_traslado) REFERENCES lgs_cat_tipo_traslado(id_tipo_traslado) ON DELETE CASCADE,
    FOREIGN KEY (id_origen) REFERENCES lgs_cat_origenes(id_origen) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES lgs_cat_destinos(id_destino) ON DELETE CASCADE,
    FOREIGN KEY (id_segmento) REFERENCES lgs_cat_segmentos(id_segmento) ON DELETE CASCADE,
    UNIQUE KEY uq_ruta_traslado_segmento_vins (id_tipo_traslado, id_origen, id_destino, id_segmento, num_vins_min, num_vins_max)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 📅 Cambios Recientes — Despacho, Checklists de Trasladistas y Evidencias (`Lgs_ejecucion`)

* **Fecha:** 19 de Agosto 2026
* **Estatus en BD:** ✅ Creadas y ejecutadas en `mrp-db`.
* **Script Fuente:** [Scripts/update_lgs_flow_recoleccion_evidencias.sql](file:///home/christianguarneros/proyectos/mrp/Scripts/update_lgs_flow_recoleccion_evidencias.sql)

### 9. 📝 Modificación a Tablas: `lgs_envios` y `lgs_envios_vins`
* Se agregó la columna `fecha_confirmada_recoleccion DATE NULL` a `lgs_envios`.
* Se actualizó la semántica de `id_estado` a: `1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=Confirmado Recolección 6=En Tránsito 7=Entregado 8=Cancelado`.
* Se agregó `estado_unidad_fisico VARCHAR(50) DEFAULT 'EN_PATIO'` a `lgs_envios_vins` (`EN_PATIO`, `EN_ENTREGAS`, `EN_RUTA`, `ENTREGADO`).

### 10. 📊 Tabla: `lgs_trasladistas_checklist`
Almacena el registro de inspección digital realizada por el chofer y el personal de planta.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_checklist`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | ID del checklist |
| **`id_envio`** | `bigint` | NO | `MUL` | `NULL` | | FK a `lgs_envios.id_envio` |
| **`id_unidad`** | `bigint` | NO | | `NULL` | | ID de la unidad/VIN |
| **`tipo_checklist`** | `enum('entrada_trasladista','salida_planta','entrega_destino')` | NO | | `NULL` | | Momento del checklist |
| **`vin_escaneado`** | `varchar(50)` | NO | | `NULL` | | VIN confirmado por escáner |
| **`usuario_registro_id`** | `int` | NO | | `NULL` | | Usuario responsable |
| **`comentarios`** | `text` | SÍ | | `NULL` | | Daños u observaciones |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de captura |

### 11. 📊 Tabla: `lgs_checklist_evidencias`
Almacena las fotografías obligatorias asociadas a cada inspección.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_evidencia`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | ID de la evidencia |
| **`id_checklist`** | `bigint` | NO | `MUL` | `NULL` | | FK a `lgs_trasladistas_checklist.id_checklist` |
| **`tipo_foto`** | `varchar(50)` | NO | | `NULL` | | Tipo de foto (frente, atras, odometro, etc.) |
| **`ruta_archivo`** | `varchar(255)` | NO | | `NULL` | | Ruta de almacenamiento físico |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de carga |

---

### 12. 📊 Tabla de Itinerario: `lgs_envios_paradas`
Registra las paradas intermedias y destinos sucesivos de una misma ruta o envío consolidado.

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_parada`** | `bigint` | NO | `PRI` | `NULL` | `auto_increment` | Identificador único de la parada |
| **`id_envio`** | `bigint` | NO | `MUL` | `NULL` | | FK a `lgs_envios.id_envio` (CASCADE) |
| **`orden`** | `tinyint unsigned` | NO | | `1` | | Secuencia numérica de la parada en el recorrido |
| **`id_destino_cat`** | `bigint` | SÍ | | `NULL` | | FK a `lgs_cat_destinos.id_destino` o `cli_clientes.idcliente` |
| **`destino_nombre_libre`** | `varchar(255)` | SÍ | | `NULL` | | Nombre o referencia libre de destino |
| **`km_tramo`** | `decimal(10,2)` | SÍ | | `0.00` | | Distancia en kilómetros calculada para el tramo |
| **`observaciones`** | `text` | SÍ | | `NULL` | | Notas operativas del tramo |
| **`created_at`** | `timestamp` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de creación del registro |

* **Relación con VINs (`lgs_envios_vins.id_parada`):** Cada unidad asignada al envío se vincula a su `id_parada` correspondiente, determinando su secuencia de desembarque (`ORDER BY COALESCE(ep.orden, 999) ASC`) y su destino final con coordenadas GPS.

---

### 13. 📊 Tabla Auxiliar de Simulación / Staging: `lgs_unidades_envios`
Almacena unidades disponibles para carga y pruebas de simulación de despacho.

* **Script Fuente:** [Scripts/create_lgs_unidades_envios.sql](file:///home/christianguarneros/proyectos/mrp/Scripts/create_lgs_unidades_envios.sql)

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Descripción |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_unidad`** | `int(11)` | NO | `PRI` | `NULL` | `auto_increment` | ID de unidad |
| **`vin`** | `varchar(50)` | NO | `UNI` | `NULL` | | Número de Identificación Vehicular |
| **`num_serie`** | `varchar(50)` | SÍ | | `NULL` | | Número de serie |
| **`modelo`** | `varchar(100)` | SÍ | | `NULL` | | Modelo comercial del vehículo |
| **`origen`** | `varchar(150)` | SÍ | | `NULL` | | Punto de origen / Planta |
| **`destino`** | `varchar(150)` | SÍ | | `NULL` | | Punto de destino comercial |
| **`estatus`** | `varchar(50)` | SÍ | | `'disponible'`| | Estatus de disponibilidad para envíos |
| **`created_at`** | `datetime` | SÍ | | `CURRENT_TIMESTAMP` | | Fecha de registro |

---

## 🗂️ Índice de Scripts SQL de Migración Disponibles

| Script SQL | Ubicación | Propósito / Alcance |
| :--- | :--- | :--- |
| `0_DDL_LOGISTICA.sql` | [docs_migracion_logistica/0_DDL_LOGISTICA.sql](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/0_DDL_LOGISTICA.sql) | DDL inicial de catálogos y tablas de envíos de Logística |
| `MIGRACION_PRODUCCION_LOGISTICA.sql` | [docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql) | Script maestro consolidado e idempotente para Producción con todas las tablas y catálogos |
| `lgs_costos_rutas_segmentos.sql` | [db_info/lgs_costos_rutas_segmentos.sql](file:///home/christianguarneros/proyectos/mrp/db_info/lgs_costos_rutas_segmentos.sql) | DDL y catalogación de segmentos vehiculares y tarifas por ruta |
| `add_lgs_envios_paradas.sql` | [Scripts/add_lgs_envios_paradas.sql](file:///home/christianguarneros/proyectos/mrp/Scripts/add_lgs_envios_paradas.sql) | Soporte de paradas múltiples / multi-destino en envíos |
| `update_lgs_flow_recoleccion_evidencias.sql` | [Scripts/update_lgs_flow_recoleccion_evidencias.sql](file:///home/christianguarneros/proyectos/mrp/Scripts/update_lgs_flow_recoleccion_evidencias.sql) | Tablas para checklists digitales de traslado y evidencias de inspección |
| `create_lgs_unidades_envios.sql` | [Scripts/create_lgs_unidades_envios.sql](file:///home/christianguarneros/proyectos/mrp/Scripts/create_lgs_unidades_envios.sql) | Tabla auxiliar de unidades y seeders de prueba |
| `SEEDERS_MADRINAS_CHOFERES.sql` | [docs_migracion_logistica/SEEDERS_MADRINAS_CHOFERES.sql](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/SEEDERS_MADRINAS_CHOFERES.sql) | Catálogo semilla de nodrizas/madrinas y choferes para pruebas |
| `SEEDERS_PRODUCCION_LOGISTICA.sql` | [docs_migracion_logistica/SEEDERS_PRODUCCION_LOGISTICA.sql](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/SEEDERS_PRODUCCION_LOGISTICA.sql) | Seeders maestros de orígenes, destinos y matrices de costeo |
