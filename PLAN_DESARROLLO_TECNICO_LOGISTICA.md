# 📐 Plan de Desarrollo Técnico — Módulo de Logística
### Versión Rediseñada · 04 Agosto 2026

Este documento describe la arquitectura técnica, el inventario de código y los DDL necesarios para implementar el Módulo de Logística completo.

> **Lectura complementaria:** [5_flujo_operativo_logistica.md](docs_migracion_logistica/5_flujo_operativo_logistica.md) · [1_levantamiento_negocio.md](docs_migracion_logistica/1_levantamiento_negocio.md)

---

## 1. 🏗️ Arquitectura del Sistema

El proyecto utiliza un framework PHP 4 capas propio:

```
index.php (Router)
    └── Controllers/{Nombre}.php       ← Recibe request, llama al Service, renderiza vista
            └── Services/{Nombre}.php  ← Lógica de negocio, transacciones, validaciones
                    └── Models/{Nombre}.php  ← SQL raw con PDO/MySQL
                            └── Base de datos MySQL
    └── Views/{Nombre}/index.php       ← HTML + PHP mínimo
    └── Assets/js/modulos/functions_{nombre}.js  ← AJAX + DataTables + UI
```

**Patrón de Request:**
```
URL: base_url/Controller/Method/Params
Ejemplo: base_url/Lgs_envios/store
```

**Patrón de Respuesta API:**
```json
{ "success": true|false, "message": "...", "data": {...}, "code": 200 }
```

---

## 2. 🗄️ Estado de Tablas en Base de Datos

### 2.1 Tablas que YA EXISTEN (Épica 1 — Completa)

| Tabla | Estado | Descripción |
|---|:---:|---|
| `prv_cat_proveedores` | ✅ | Proveedores de transporte (trasladistas) |
| `prv_cat_actividades` | ✅ | Catálogo de actividades por proveedor |
| `prv_rel_proveedores_actividades` | ✅ | Relación N:M proveedor ↔ actividad |
| `prv_det_choferes` | ✅ | Choferes vinculados a proveedor |
| `prv_det_madrinas` | ✅ | Madrinas vinculadas a proveedor |
| `prv_det_madrina_chofer_historial` | ✅ | Historial de asignación chofer ↔ madrina |
| `prv_det_contactos` | ✅ | Contactos del proveedor |
| `prv_det_direcciones` | ✅ | Direcciones del proveedor |

### 2.2 Tablas que FALTAN CREAR (Épicas 2–6)

#### Catálogos configurables (Épica 2)

| Tabla | Descripción |
|---|---|
| `lgs_cat_tipo_traslado` | Tipo del traslado: Normal, Urgente, Demo, Piloto, Programado |
| `lgs_cat_motivo_envio` | Motivo/razón: Entrega Dist., Carrocería, Marketing, Demo, Pruebas, Piloto, Devolución, Otro |
| `lgs_cat_tipo_destino` | Tipo de punto destino: Distribuidor, Carrocero, Cliente Final, Almacén, Planta, Otro |
| `lgs_cat_origenes` | Puntos de origen con nombre y coordenadas GPS (lat/lng) |
| `lgs_cat_destinos` | Clientes y puntos destino con nombre, dirección y coordenadas GPS |

#### Tablas operativas — Envíos (Épica 2)

| Tabla | Descripción |
|---|---|
| `lgs_envios` | Cabecera del envío: folio EN-, tipo, motivo, trasladista, origen, destino, km, costo, fechas, estado |
| `lgs_envios_vins` | VINs asignados al envío con posición de acomodo en madrina y costo unitario |
| `lgs_envios_choferes` | Chofer(es) asignados al envío |
| `lgs_envios_madrinas` | Madrina(s) asignadas al envío |
| `lgs_costos_proveedor_segmento` | Tarifa por km según proveedor, segmento de unidad y número de VINs (factor) |

#### Planeaciones y Aprobaciones (Épica 3)

| Tabla | Descripción |
|---|---|
| `lgs_planeaciones` | Agrupador de envíos para aprobación: folio EX-, estado, totales |
| `lgs_planeaciones_envios` | Relación N:M planeación ↔ envío |
| `lgs_aprobadores` | Usuarios con permiso de aprobar planeaciones |

#### Ejecución y Entregas (Épica 4)

| Tabla | Descripción |
|---|---|
| `lgs_solicitudes_entrega` | Solicitudes al Área de Entregas: VIN, estado, posición acomodo, fechas |

#### Evidencias (Épica 5)

| Tabla | Descripción |
|---|---|
| `lgs_evidencias` | Fotos/videos por envío: tipo 1=Salida, 2=Llegada, ruta de archivo, created_by |

---

## 3. 🧩 Inventario de Código

### 3.1 Lo que YA EXISTE y funciona (Épica 1)

| Archivo | Tipo | Función |
|---|---|---|
| `Controllers/Prv_choferes.php` | Controller | CRUD Choferes |
| `Controllers/Prv_madrinas.php` | Controller | CRUD Madrinas + historial + asignarChofer |
| `Controllers/Prv_proveedor.php` | Controller | Vista catálogo de proveedores |
| `Services/Prv_choferesService.php` | Service | Lógica de Choferes |
| `Services/Prv_madrinasService.php` | Service | Lógica de Madrinas |
| `Models/Prv_choferesModel.php` | Model | SQL Choferes |
| `Models/Prv_madrinasModel.php` | Model | SQL Madrinas + historial |
| `Requests/Prv_choferesRequest.php` | Request | Validación Chofer |
| `Requests/Prv_madrinasRequest.php` | Request | Validación Madrina |
| `Views/Prv_choferes/index.php` | View | DataTable + modales |
| `Views/Prv_madrinas/index.php` | View | DataTable + modal historial |
| `Assets/js/modulos/functions_prv_choferes.js` | JS | AJAX CRUD Choferes |
| `Assets/js/modulos/functions_prv_madrinas.js` | JS | AJAX CRUD Madrinas |
| `Views/Template/nav_admin.php` | Nav | Menú Logística con links (actualizado) |

### 3.2 Lo que FALTA CREAR (Épicas 2–6)

| Archivo | Tipo | Épica | Descripción |
|---|---|:---:|---|
| `Controllers/Lgs_envios.php` | Controller | 2 | CRUD de Envíos: crear, editar, asignar VINs, calcular costos |
| `Services/Lgs_enviosService.php` | Service | 2 | Lógica de envíos, folio EN-, motor de costos, API km |
| `Models/Lgs_enviosModel.php` | Model | 2 | SQL envíos, VINs asignados, costos |
| `Requests/Lgs_enviosRequest.php` | Request | 2 | Validación de campos de envío |
| `Views/Lgs_envios/index.php` | View | 2 | DataTable + modal creación + asignación VINs con drag-and-drop |
| `Assets/js/modulos/functions_lgs_envios.js` | JS | 2 | AJAX envíos + orden de acomodo |
| `Controllers/Lgs_planeaciones.php` | Controller | 3 | CRUD Planeaciones + acción "Enviar a Aprobación" |
| `Services/Lgs_planeacionesService.php` | Service | 3 | Lógica planeaciones, folio EX-, correo aprobadores |
| `Models/Lgs_planeacionesModel.php` | Model | 3 | SQL planeaciones y relaciones |
| `Views/Lgs_planeaciones/index.php` | View | 3 | Agrupación de envíos + totales |
| `Assets/js/modulos/functions_lgs_planeaciones.js` | JS | 3 | AJAX planeaciones |
| `Controllers/Lgs_aprobaciones.php` | Controller | 3 | Panel del aprobador |
| `Services/Lgs_aprobacionesService.php` | Service | 3 | Aprobar/Regresar con cascada de estados |
| `Models/Lgs_aprobacionesModel.php` | Model | 3 | SQL aprobaciones |
| `Views/Lgs_aprobaciones/index.php` | View | 3 | DataTable pendientes + modal resolución |
| `Assets/js/modulos/functions_lgs_aprobaciones.js` | JS | 3 | AJAX aprobaciones |
| `Controllers/Lgs_ejecucion.php` | Controller | 4 | Iniciar despacho, solicitar entregas, confirmar llegada |
| `Services/Lgs_ejecucionService.php` | Service | 4 | Lógica de despacho y confirmación |
| `Models/Lgs_ejecucionModel.php` | Model | 4 | SQL ejecución y solicitudes de entrega |
| `Views/Lgs_ejecucion/` | View | 4 | Vista despacho y confirmación |
| `Controllers/Lgs_evidencias.php` | Controller | 5 | Subida/eliminación de fotos y videos |
| `Services/Lgs_evidenciasService.php` | Service | 5 | Validación, almacenamiento y borrado de archivos |
| `Models/Lgs_evidenciasModel.php` | Model | 5 | SQL evidencias |
| `Assets/js/modulos/functions_lgs_evidencias.js` | JS | 5 | Dropzone múltiple + lightbox + reproductor |
| `Controllers/Lgs_panelrutas.php` | Controller | 6 | Mapa de rutas activas |
| `Views/Lgs_panelrutas/index.php` | View | 6 | Mapa interactivo Leaflet/Google Maps |
| `Assets/js/modulos/functions_lgs_panelrutas.js` | JS | 6 | Renderizado de pines y rutas |

---

## 4. 🔗 Diagrama de Interacciones

```
[PROVEEDOR (Trasladista)]
    ├── tiene N → [CHOFERES]
    └── tiene N → [MADRINAS]

[ENVÍO (lgs_envios)]
    ├── tiene 1 Proveedor, 1 Chofer, 1 Madrina
    ├── tiene 1 Origen (lgs_cat_origenes, lat/lng)
    ├── tiene 1 Destino (lgs_cat_destinos, lat/lng)
    ├── tiene 1 Tipo Traslado (lgs_cat_tipo_traslado)
    ├── tiene 1 Motivo (lgs_cat_motivo_envio)
    ├── tiene N → [VINs] (lgs_envios_vins, con posición de acomodo)
    │       └── Costo unitario = Km × tarifa × factor
    ├── tiene N → [EVIDENCIAS] (lgs_evidencias)
    │       └── tipo: 1=Salida, 2=Llegada
    └── tiene N → [SOLICITUDES ENTREGA] (lgs_solicitudes_entrega)

[PLANEACIÓN (lgs_planeaciones)]
    ├── agrupa N → [ENVÍOS]
    ├── tiene N → [APROBADORES] (lgs_aprobadores)
    └── flujo: Creada → Enviada → Aprobada | Regresada

ESTADOS DEL ENVÍO:
  1:Creado → 2:En Revisión → 3:Aprobado → 5:En Ejecución → 6:En Tránsito → 7:Entregado
```

---

## 5. 📋 DDL — Tablas a Crear (en orden por dependencias FK)

```sql
-- ─── ÉPICA 2: CATÁLOGOS ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_cat_tipo_traslado (
    id_tipo_traslado TINYINT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    activo           TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_tipo_traslado (nombre) VALUES
('Madrina'), ('Chofer (Rodando)');

-- ─────────────────────────────────────────────────────────────────

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

-- ─────────────────────────────────────────────────────────────────

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

-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_cat_origenes (
    id_origen   INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    direccion   VARCHAR(255) NULL,
    lat         DECIMAL(10,7) NULL,
    lng         DECIMAL(10,7) NULL,
    activo      TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_origenes (nombre) VALUES
('Planta 1'), ('Planta 2'), ('Planta 3'), ('Planta 4'), ('Planta 5'),
('Almacén Montenegro');

-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_cat_destinos (
    id_destino      INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    nombre_libre    VARCHAR(255) NULL COMMENT 'Nombre personalizado para el punto destino',
    id_tipo_destino TINYINT NULL,
    direccion       VARCHAR(255) NULL,
    lat             DECIMAL(10,7) NULL,
    lng             DECIMAL(10,7) NULL,
    activo          TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tipo_destino) REFERENCES lgs_cat_tipo_destino(id_tipo_destino) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ÉPICA 2: ENVÍOS ─────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_envios (
    id_envio                BIGINT AUTO_INCREMENT PRIMARY KEY,
    folio                   VARCHAR(20) UNIQUE NOT NULL COMMENT 'EN-000001',
    id_tipo_traslado        TINYINT NULL,
    id_motivo               INT NULL,
    id_proveedor            BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    id_origen               INT NULL COMMENT 'FK lgs_cat_origenes',
    id_destino              INT NULL COMMENT 'FK lgs_cat_destinos',
    destino_nombre_libre    VARCHAR(255) NULL COMMENT 'Nombre libre del destino si no está en catálogo',
    km_total                DECIMAL(10,2) DEFAULT 0,
    costo_total             DECIMAL(12,2) NULL COMMENT 'Calculado dinámicamente según VINs/Madrinas/Choferes',
    fecha_tentativa_envio   DATE NULL,
    fecha_tentativa_llegada DATE NULL,
    fecha_salida_real       DATETIME NULL,
    fecha_llegada_real      DATETIME NULL,
    recibe_nombre           VARCHAR(150) NULL COMMENT 'Nombre de quien recibe en destino',
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
    FOREIGN KEY (id_origen)        REFERENCES lgs_cat_origenes(id_origen) ON DELETE SET NULL,
    FOREIGN KEY (id_destino)       REFERENCES lgs_cat_destinos(id_destino) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_envios_vins (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL COMMENT 'FK mrp_unidades_terminadas',
    id_destino       INT NULL COMMENT 'Parada/Destino donde se bajará este VIN',
    id_madrina       BIGINT NULL COMMENT 'Madrina en la que viaja (si tipo = Madrina)',
    id_chofer        BIGINT NULL COMMENT 'Chofer que maneja (si tipo = Chofer Rodando)',
    posicion_acomodo TINYINT UNSIGNED NULL
        COMMENT 'Orden de carga en la madrina. 1 = primero en subir. NULL si es Chofer Rodando',
    costo_unidad     DECIMAL(12,2) NULL COMMENT 'Calculado automáticamente',
    id_estado        TINYINT DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_envio_vin (id_envio, id_unidad),
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE,
    FOREIGN KEY (id_destino) REFERENCES lgs_cat_destinos(id_destino) ON DELETE SET NULL
    /* FOREIGN KEY (id_madrina) REFERENCES prv_det_madrinas(id_madrina) ON DELETE SET NULL */
    /* FOREIGN KEY (id_chofer) REFERENCES prv_det_choferes(id_chofer) ON DELETE SET NULL */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_costos_proveedor_segmento (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor    BIGINT NOT NULL,
    id_segmento     INT NULL COMMENT 'Segmento del vehículo',
    num_vins_min    TINYINT DEFAULT 1,
    num_vins_max    TINYINT DEFAULT 99,
    costo_por_km    DECIMAL(10,4) NOT NULL,
    factor          DECIMAL(5,2) DEFAULT 1.00,
    activo          TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ÉPICA 3: PLANEACIONES ───────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_planeaciones (
    id_planeacion   BIGINT AUTO_INCREMENT PRIMARY KEY,
    folio           VARCHAR(20) UNIQUE NOT NULL COMMENT 'EX-000001',
    descripcion     VARCHAR(255) NULL,
    km_total        DECIMAL(10,2) NULL,
    costo_total     DECIMAL(12,2) NULL,
    id_estado       TINYINT DEFAULT 1 COMMENT '1=Creada 2=Enviada 3=Regresada 5=Aprobada',
    obs_operador    TEXT NULL,
    obs_aprobador   TEXT NULL,
    created_by      BIGINT UNSIGNED NULL,
    aprobado_by     BIGINT UNSIGNED NULL,
    aprobado_at     DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  BIGINT NOT NULL,
    activo      TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ÉPICA 4: SOLICITUDES DE ENTREGA ─────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_solicitudes_entrega (
    id_solicitud     BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL COMMENT 'VIN',
    posicion_acomodo TINYINT UNSIGNED DEFAULT 1,
    id_estado        TINYINT DEFAULT 1 COMMENT '1=Solicitada 2=Entregada a Trasladista 3=Cancelada',
    solicitado_by    BIGINT UNSIGNED NULL,
    confirmado_by    BIGINT UNSIGNED NULL,
    solicitado_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmado_at    DATETIME NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ÉPICA 5: EVIDENCIAS ─────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_evidencias (
    id_evidencia   BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio       BIGINT NOT NULL,
    tipo           TINYINT NOT NULL COMMENT '1=Salida (Recepción) 2=Llegada (Entrega)',
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Ruta relativa en Assets/uploads/logistica/',
    tipo_archivo   VARCHAR(10) NULL COMMENT 'jpg, png, mp4, mov, etc.',
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 6. 🛠️ Prerequisitos — Verificar antes de Implementar

| # | Pregunta | Acción |
|:---:|---|---|
| 1 | ¿Cuál es la tabla principal de VINs disponibles para envío? | Confirmar si es `mrp_unidades_terminadas` o un JOIN |
| 2 | ¿Existe tabla de segmentos de vehículo? | Necesaria para `lgs_costos_proveedor_segmento` |
| 3 | ¿Qué API se usará para cálculo de km? | Google Maps Distance Matrix API vs cálculo geodésico |
| 4 | ¿El GPS en tránsito es real (teléfono chofer) o solo visual? | Determina complejidad del Panel de Rutas |
| 5 | ¿Quién confirma llegada: operador en planta o responsable en destino? | Define si se necesita portal externo o solo interno |

---

## 7. 🧭 Orden de Desarrollo (con dependencias técnicas)

```
Paso 1: DDL catálogos (lgs_cat_*)                     [E2 - ST-2.1]
    │
Paso 2: DDL lgs_envios + lgs_envios_vins + costos     [E2 - ST-2.2 a 2.4]
    │
Paso 3: Controller + Service + Model + View Envíos    [E2 - ST-2.5 a 2.6]
    │       ← Primer módulo visible para el operador
    │
Paso 4: API de km + Generador folio EN- + Motor costo [E2 - ST-2.7 a 2.8]
    │
Paso 5: DDL Planeaciones + Aprobadores                [E3 - ST-3.1]
    │
Paso 6: Módulo de Planeaciones (folio EX-)            [E3 - ST-3.2 a 3.4]
    │
Paso 7: Módulo de Aprobaciones + correo               [E3 - ST-3.5 a 3.7]
    │
Paso 8: DDL Solicitudes de Entrega                    [E4 - ST-4.1]
    │
Paso 9: Módulo de Ejecución / Despacho                [E4 - ST-4.2 a 4.4]
    │
Paso 10: Confirmación de Llegada y Entrega Final      [E4 - ST-4.5]
    │
Paso 11: DDL lgs_evidencias + Módulo de Evidencias    [E5 - ST-5.1 a 5.3]
    │
Paso 12: Panel de Rutas (Leaflet/GMaps)               [E6 - ST-6.1 a 6.3]
```
