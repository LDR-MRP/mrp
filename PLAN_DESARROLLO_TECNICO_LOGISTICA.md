# 🚚 Plan de Desarrollo Técnico — Módulo de Logística

> **Propósito**: Documento de referencia técnica que mapea el estado actual del código, las tablas de BD, las vistas y controladores existentes, cómo interactúan entre sí, y todo lo que hace falta construir para completar el módulo de Logística.
>
> **Fecha de creación**: 29 de Julio 2026  
> **Rama de trabajo**: `feature/crud-trasladistas-madrinas-choferes` → merge a `devcr`

## 1. 📐 Arquitectura del Framework MRP (cómo funciona el código)

El proyecto usa un **framework PHP personalizado con 4 capas**:

```
URL → index.php (Router) → Controller → Service → Model → MySQL
                                ↓
                           View (PHP + DataTables + AJAX JS)
```

| Capa | Directorio | Responsabilidad |
|---|---|---|
| **Router** | `index.php` | Parsea la URL y despacha al Controlador correcto |
| **Controller** | `Controllers/` | Recibe la petición, llama al Service, retorna vista o JSON |
| **Service** | `Services/` | Lógica de negocio, transacciones DB, orquesta modelos |
| **Model** | `Models/` | Queries SQL directas via PDO (hereda de `Mysql`) |
| **Request** | `Requests/` | Validación de datos de entrada (`$_POST`, `$_GET`) |
| **View** | `Views/{Modulo}/index.php` | HTML + Bootstrap 5 + DataTables + AJAX |
| **JS** | `Assets/js/modulos/functions_{modulo}.js` | AJAX, DataTables, modales y acciones del usuario |

**Convención de URLs:**
```
http://localhost/mrp/{Controlador}/{método}/{params}
Ej: /mrp/Prv_choferes/getChoferes
    /mrp/Prv_madrinas/asignarChofer
```

---

## 2. 🗄️ Estado Real de la Base de Datos (tablas existentes)

### 2.1 Tablas que YA existen y son usadas por Logística

#### `prv_cat_proveedores` — Proveedores de Transporte (Trasladistas)
```sql
id_proveedor      BIGINT PK AUTO_INCREMENT
id_empresa        INT (1)
id_planta         INT NULL
rfc               VARCHAR(13) UNIQUE
rfc_activo        VARCHAR(13) STORED GENERATED
razon_social      VARCHAR(255)
nombre_comercial  VARCHAR(255) NULL
id_tipo_persona   CHAR(1) NULL   -- 'F'=Física, 'M'=Moral
id_regimen_fiscal INT NULL FK→sat_cat_regimen_fiscal
tipo              ENUM('Interno','Externo') DEFAULT 'Externo'
origen            ENUM('Nacional','Extranjero') DEFAULT 'Nacional'
web               VARCHAR(150) NULL
estatus_onboarding ENUM('Prospecto','En Revision','Aprobado','Rechazado') DEFAULT 'Prospecto'
estatus_operativo TINYINT DEFAULT 0
is_retail         TINYINT DEFAULT 0
created_by/updated_by/deleted_by  BIGINT NULL
created_at/updated_at/deleted_at  TIMESTAMP
```
> ⚠️ **Falta**: La tabla NO tiene campos directos de domicilio/dirección fiscal, correo y teléfono de contacto de transporte. Esos datos viven en tablas relacionadas: `prv_det_direcciones` y `prv_det_contactos`.

#### `prv_det_choferes` — Choferes / Operadores
```sql
id_chofer         BIGINT PK AUTO_INCREMENT
id_proveedor      BIGINT FK→prv_cat_proveedores
nombre            VARCHAR(100)
apellidos         VARCHAR(100)
num_licencia      VARCHAR(50)
tipo_licencia     VARCHAR(20) NULL
vigencia_licencia DATE NULL
telefono          VARCHAR(20) NULL
estatus_operativo TINYINT DEFAULT 1
created_by/updated_by/deleted_by  BIGINT NULL
created_at/updated_at/deleted_at  TIMESTAMP
```
> ⚠️ **Falta**: Campo `licencia_file` (ruta al archivo digital de la licencia).

#### `prv_det_madrinas` — Madrinas / Unidades Nodrizas
```sql
id_madrina          BIGINT PK AUTO_INCREMENT
id_proveedor        BIGINT FK→prv_cat_proveedores
numero_economico    VARCHAR(50)
placas              VARCHAR(30)
placa_caja          VARCHAR(30) NULL
marca               VARCHAR(100) NULL
modelo              VARCHAR(100) NULL
anio                INT NULL
color               VARCHAR(50) NULL
num_serie_vin       VARCHAR(50) NULL
capacidad_vehiculos INT DEFAULT 1
estatus_operativo   TINYINT DEFAULT 1
created_by/updated_by/deleted_by  BIGINT NULL
created_at/updated_at/deleted_at  TIMESTAMP
```

#### `prv_det_madrina_chofer_historial` — Historial de Choferes por Madrina ✅
```sql
id_historial  BIGINT PK AUTO_INCREMENT
id_madrina    BIGINT FK→prv_det_madrinas
id_chofer     BIGINT FK→prv_det_choferes
fecha_inicio  DATETIME DEFAULT CURRENT_TIMESTAMP
fecha_fin     DATETIME NULL
activo        TINYINT(1) DEFAULT 1
observaciones TEXT NULL
created_by/updated_by  BIGINT NULL
created_at/updated_at  TIMESTAMP
```

#### Tablas catálogo relacionadas (usadas como `SELECT` en formularios)
```
prv_cat_actividades              — Actividades de proveedor (ej: TRASLADO_UNIDADES)
prv_rel_proveedores_actividades  — Relación N:M proveedor ↔ actividad
prv_det_contactos                — Contactos del proveedor (correo, teléfono)
prv_det_direcciones              — Direcciones del proveedor (fiscal, etc.)
sat_cat_tipo_persona             — Catálogo SAT tipo persona (F/M)
sat_cat_regimen_fiscal           — Catálogo SAT regímenes
```

### 2.2 Tablas que hacen falta crear (pendientes para Logística Operativa)

| Tabla | Estado | Descripción |
|---|---|---|
| `lgs_envios` | ❌ No existe | Envíos individuales de traslado (EN-000001) |
| `lgs_envios_unidades` | ❌ No existe | Asignación de VINs a un envío |
| `lgs_envios_choferes` | ❌ No existe | Chofer(es) asignados a un envío |
| `lgs_envios_madrinas` | ❌ No existe | Madrina(s) asignadas a un envío |
| `lgs_expedientes` | ❌ No existe | Agrupador de envíos para aprobación (EX-000001) |
| `lgs_expedientes_envios` | ❌ No existe | Relación N:M expediente ↔ envío |
| `lgs_aprobadores_expedientes` | ❌ No existe | Usuarios que pueden aprobar expedientes |
| `lgs_unidades` | ❌ No existe | Estado lógico de cada VIN dentro del módulo de logística |
| `lgs_unidades_entrega_interna` | ❌ No existe | Control de entrega interna Producción → Logística (solo Origen Planta) |
| `lgs_evidencias` | ❌ No existe | Fotos/videos de salida y llegada por unidad |
| `lgs_costos_proveedor_segmento` | ❌ No existe | Tarifa por km según proveedor y segmento de unidad |
| `lgs_tipo_envio` | ❌ No existe (o verificar) | Catálogo: Individual, Múltiple, etc. |
| `lgs_motivo_movimiento` | ❌ No existe (o verificar) | Catálogo: Traslado Normal, Traslado Carrocero, etc. |

---

## 3. 🧩 Estado Real del Código (Controllers, Services, Models, Views, JS)

### 3.1 Lo que YA EXISTE y funciona

| Archivo | Tipo | Qué hace |
|---|---|---|
| `Controllers/Prv_choferes.php` | Controller | CRUD completo de choferes. Filtra proveedores por actividad `TRASLADO_UNIDADES`. |
| `Controllers/Prv_madrinas.php` | Controller | CRUD completo de madrinas + historial de choferes + asignación de chofer a madrina. |
| `Controllers/Prv_proveedor.php` | Controller | Vista de catálogo de proveedores. |
| `Services/Prv_choferesService.php` | Service | Lógica de negocio de Choferes (create, update, delete, getAll, getById). |
| `Services/Prv_madrinasService.php` | Service | Lógica de negocio de Madrinas + `asignarChofer()` con transacción. |
| `Models/Prv_choferesModel.php` | Model | SQL: insertChofer, updateChofer, deleteChofer (soft), getChoferes con JOIN a proveedores. |
| `Models/Prv_madrinasModel.php` | Model | SQL: getMadrinas con chofer_actual, getHistorialChoferes, asignarChofer (inactiva previo + inserta nuevo). |
| `Requests/Prv_choferesRequest.php` | Request | Validación de campos de chofer. |
| `Requests/Prv_madrinasRequest.php` | Request | Validación de campos de madrina. |
| `Views/Prv_choferes/index.php` | View | DataTable de choferes + modales de creación/edición. |
| `Views/Prv_madrinas/index.php` | View | DataTable de madrinas + modales de creación/edición + modal de historial de choferes. |
| `Assets/js/modulos/functions_prv_choferes.js` | JS | AJAX para CRUD de choferes. |
| `Assets/js/modulos/functions_prv_madrinas.js` | JS | AJAX para CRUD de madrinas + asignación de chofer. |
| `Views/Template/nav_admin.php` | Nav | Menú "Logística" con links a Trasladistas, Madrinas, Choferes. |

### 3.2 Lo que hace falta crear (código nuevo)

| Archivo | Tipo | Qué necesita hacer |
|---|---|---|
| `Controllers/Lgs_bandeja.php` | Controller | Bandeja principal: listar VINs en proceso logístico con los filtros de negocio. |
| `Controllers/Lgs_envios.php` | Controller | CRUD de Envíos individuales (crear, editar, ver detalle). |
| `Controllers/Lgs_expedientes.php` | Controller | CRUD de Expedientes + acción "Enviar a Aprobación". |
| `Controllers/Lgs_aprobaciones.php` | Controller | Panel de aprobación: ver expedientes enviados, aprobar/rechazar. |
| `Controllers/Lgs_panelrutas.php` | Controller | Pantalla del mapa de rutas activas. |
| `Services/Lgs_enviosService.php` | Service | Lógica de: crear envío, asignar VINs, calcular costos, generar folio EN-. |
| `Services/Lgs_expedientesService.php` | Service | Lógica de: crear expediente, agrupar envíos, flujo de aprobación, notificación por correo. |
| `Services/Lgs_bandejaService.php` | Service | Lógica de: consultar VINs elegibles según reglas de negocio, filtros operativos. |
| `Services/Log_evidenciasService.php` | Service | Lógica de: subir archivo, guardar referencia en BD, borrar archivo físico. |
| `Models/Lgs_enviosModel.php` | Model | SQL: insert/update de `lgs_envios`, consulta de VINs asignados, cálculo de costos. |
| `Models/Lgs_expedientesModel.php` | Model | SQL: insert/update de `lgs_expedientes`, cambio de estado en cascada. |
| `Models/Lgs_bandejaModel.php` | Model | SQL: consulta maestra de VINs en logística con JOINs complejos y filtros. |
| `Models/Log_evidenciasModel.php` | Model | SQL: insert/delete de evidencias fotográficas y de video. |
| `Requests/Lgs_enviosRequest.php` | Request | Validación de campos de creación/edición de envío. |
| `Requests/Lgs_expedientesRequest.php` | Request | Validación de campos de creación de expediente. |
| `Views/Lgs_bandeja/index.php` | View | Tabla de VINs + filtros operativos + botones de acción + modal de detalle. |
| `Views/Lgs_envios/index.php` | View | DataTable de envíos + modal de creación + subpanel de VINs asignados. |
| `Views/Lgs_expedientes/index.php` | View | DataTable de expedientes + modal de agrupación de envíos + detalle de costos. |
| `Views/Lgs_aprobaciones/index.php` | View | DataTable de expedientes pendientes + modal de aprobación/rechazo. |
| `Views/Lgs_panelrutas/index.php` | View | Mapa interactivo con rutas activas. |
| `Assets/js/modulos/functions_lgs_bandeja.js` | JS | AJAX para filtros, solicitud de entrega, acción "Siguiente Área". |
| `Assets/js/modulos/functions_lgs_envios.js` | JS | AJAX para CRUD de envíos y asignación de VINs. |
| `Assets/js/modulos/functions_lgs_expedientes.js` | JS | AJAX para CRUD de expedientes y envío a aprobación. |
| `Assets/js/modulos/functions_lgs_aprobaciones.js` | JS | AJAX para aprobar/rechazar expedientes. |
| `Assets/js/modulos/functions_lgs_evidencias.js` | JS | Dropzone para subida múltiple de fotos/videos. |

---

## 4. 🔗 Mapa de Interacciones del Sistema (cómo se conecta todo)

```
[PROVEEDOR (Trasladista)]
    ├── tiene N → [CHOFERES]
    │               └── asignado via historial → [MADRINA]
    └── tiene N → [MADRINAS]

[UNIDAD/VIN] (tabla existente: inv_captura_vin / mrp_unidades_terminadas)
    ├── si cumple 4 condiciones → aparece en [BANDEJA DE LOGÍSTICA]
    │       Condición 1: proceso activo = 6, 13 o 20
    │       Condición 2: liberada = 1
    │       Condición 3: solicitado = 1
    │       Condición 4: id_estado_proceso_finanzas = 3
    │
    ├── si origen = Planta (2) → pasa por [ENTREGA INTERNA]
    │       Operador solicita → Producción confirma → se habilita en logística
    │
    └── cuando está en bandeja → se asigna a [ENVÍO]
            ├── Envío tiene: Proveedor + Chofer + Madrina + Origen + Destino + KM
            ├── VIN asignado → costo = tarifa_km (por segmento) × km_total
            ├── si motivo = Carrocero → carrocero=1 → subproceso a pierna intermedia
            └── se registran [EVIDENCIAS] (fotos/video salida + llegada)
                    └── cuando hay fechas salida+llegada → se habilita "Siguiente Área"
                            └── avanza subproceso → Distribuidor o Carrocero

[ENVÍO] → se agrupa en [EXPEDIENTE]
    ├── Operador crea expediente + agrupa envíos
    ├── Operador envía a aprobación → estado=Enviado → correo a aprobadores
    └── Aprobador:
            Aprueba → estado=Aprobado → todos los envíos pasan a Aprobado → correo creador
            Rechaza → estado=Regresado → operador corrige
```

---

## 5. 📋 Inventario Detallado: Qué Tenemos vs. Qué Falta

### ÉPICA 1: Catálogos de Transporte

| Item | Estado | Detalle |
|---|---|---|
| Tabla `prv_cat_proveedores` | ✅ Existe | Tiene campos de negocio, le falta dirección fiscal y contacto directo en formulario de logística |
| Tabla `prv_det_choferes` | ✅ Existe | Falta campo `licencia_file` para el archivo digital |
| Tabla `prv_det_madrinas` | ✅ Existe | Completa |
| Tabla `prv_det_madrina_chofer_historial` | ✅ Existe | Completa (tiene observaciones, fechas, activo) |
| Controller Choferes | ✅ Existe | CRUD completo |
| Controller Madrinas | ✅ Existe | CRUD + historial + asignarChofer |
| View Choferes | ✅ Existe | DataTable + modales |
| View Madrinas | ✅ Existe | DataTable + modal historial |
| Menú Logística en nav | ✅ Existe | Trasladistas, Madrinas, Choferes |
| Subida de licencia digital | ❌ Falta | Campo en tabla + UI de upload + preview |
| Validación RFC en proveedor | ⚠️ Parcial | Existe validación básica, falta por tipo persona |
| Filtro de proveedores por actividad | ✅ Funciona | Usa `cve_actividad = 'TRASLADO_UNIDADES'` |

### ÉPICA 2: Bandeja Principal de Unidades

| Item | Estado | Detalle |
|---|---|---|
| Tabla `lgs_unidades` | ❌ No existe | Satélite que registra estado de cada VIN en logística |
| Tabla `lgs_unidades_entrega_interna` | ❌ No existe | Para unidades de Origen Planta |
| Controller `Lgs_bandeja.php` | ❌ No existe | — |
| View `Views/Lgs_bandeja/index.php` | ❌ No existe | — |
| JS `functions_lgs_bandeja.js` | ❌ No existe | — |
| Ruta en nav para Bandeja | ❌ No existe | Falta agregar al menú Logística |
| Ruta en nav para Envíos | ❌ No existe | — |
| Ruta en nav para Expedientes | ❌ No existe | — |
| Ruta en nav para Aprobaciones | ❌ No existe | — |

### ÉPICA 3: Envíos Individuales

| Item | Estado | Detalle |
|---|---|---|
| Tabla `lgs_envios` | ❌ No existe | — |
| Tabla `lgs_envios_unidades` | ❌ No existe | — |
| Tabla `lgs_envios_choferes` | ❌ No existe | — |
| Tabla `lgs_envios_madrinas` | ❌ No existe | — |
| Tabla `lgs_costos_proveedor_segmento` | ❌ No existe | — |
| Catálogos `lgs_tipo_envio` / `lgs_motivo_movimiento` | ❓ Verificar | Puede que existan con otro nombre |
| Controller `Lgs_envios.php` | ❌ No existe | — |
| Service/Model/View/JS | ❌ No existe | — |
| Generador folio `EN-000001` | ❌ No existe | — |
| Motor de cálculo de costo | ❌ No existe | — |

### ÉPICA 4: Expedientes y Aprobaciones

| Item | Estado | Detalle |
|---|---|---|
| Tabla `lgs_expedientes` | ❌ No existe | — |
| Tabla `lgs_expedientes_envios` | ❌ No existe | — |
| Tabla `lgs_aprobadores_expedientes` | ❌ No existe | — |
| Controller `Lgs_expedientes.php` | ❌ No existe | — |
| Controller `Lgs_aprobaciones.php` | ❌ No existe | — |
| Generador folio `EX-000001` | ❌ No existe | — |
| Plantilla correo de aprobación | ❌ No existe | Falta vista en `Views/Emails/` |
| Helper `sendMailLocal()` | ✅ Existe | Funciona con PHPMailer |

### ÉPICA 5: Evidencias Multimedia y Siguiente Área

| Item | Estado | Detalle |
|---|---|---|
| Tabla `lgs_evidencias` | ❌ No existe | — |
| Controller de evidencias | ❌ No existe | — |
| Directorio de uploads | ✅ Existe | `Assets/uploads/` |
| Lógica "Siguiente Área" | ❌ No existe | — |
| Galería multimedia + lightbox | ❌ No existe | — |

### ÉPICA 6: Panel de Rutas

| Item | Estado | Detalle |
|---|---|---|
| Controller `Lgs_panelrutas.php` | ❌ No existe | — |
| Librería de mapas | ⚠️ Parcial | Existe `jsvectormap`, pero NO Leaflet ni Google Maps API |
| View `Views/Lgs_panelrutas/index.php` | ❌ No existe | — |

---

## 6. 🗄️ DDL Completo — Tablas a Crear (en orden por dependencias FK)

```sql
-- 1. Catálogos base
CREATE TABLE lgs_tipo_envio (
    id_tipo_envio   TINYINT PK AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,  -- Ej: Individual, Múltiple, Masivo
    activo          TINYINT DEFAULT 1
);

CREATE TABLE lgs_motivo_movimiento (
    id_motivo       TINYINT PK AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,  -- Ej: Traslado Normal, Traslado Carrocero
    activo          TINYINT DEFAULT 1
);

-- 2. Tarifas por proveedor + segmento
CREATE TABLE lgs_costos_proveedor_segmento (
    id              BIGINT PK AUTO_INCREMENT,
    id_proveedor    BIGINT FK→prv_cat_proveedores,
    id_segmento     INT,                    -- Segmento del vehículo (ligero, mediano, pesado)
    id_tipo_envio   TINYINT FK→lgs_tipo_envio,
    costo_por_km    DECIMAL(10,4) NOT NULL, -- Costo por kilómetro
    created_at/updated_at TIMESTAMP
);

-- 3. Envíos
CREATE TABLE lgs_envios (
    id_envio            BIGINT PK AUTO_INCREMENT,
    folio               VARCHAR(20) UNIQUE NOT NULL,  -- EN-000001
    id_tipo_envio       TINYINT FK→lgs_tipo_envio,
    id_motivo           TINYINT FK→lgs_motivo_movimiento,
    id_proveedor        BIGINT FK→prv_cat_proveedores,
    origen_descripcion  VARCHAR(255) NULL,
    id_destino          INT NULL,                     -- FK a tabla distribuidores
    destino_descripcion VARCHAR(255) NULL,
    kilometraje_total   DECIMAL(10,2) DEFAULT 0,
    costo_total         DECIMAL(12,2) NULL,           -- calculado
    fecha_tentativa     DATE NULL,
    fecha_llegada_real  DATE NULL,
    observaciones       TEXT NULL,
    id_estado           TINYINT DEFAULT 1,
    -- 1=Creado, 2=Asignado, 3=Aprobado, 4=En tránsito, 5=Completado
    id_creador          BIGINT FK→usuarios,
    created_by/updated_by/deleted_by BIGINT NULL,
    created_at/updated_at/deleted_at TIMESTAMP
);

-- 4. Asignación VINs a Envío
CREATE TABLE lgs_envios_unidades (
    id              BIGINT PK AUTO_INCREMENT,
    id_envio        BIGINT FK→lgs_envios ON DELETE CASCADE,
    id_unidad       BIGINT NOT NULL,                  -- FK a tabla de VINs del sistema
    costo_unidad    DECIMAL(12,2) NULL,               -- calculado automáticamente
    id_estado       TINYINT DEFAULT 1,
    id_asignador    BIGINT FK→usuarios,
    UNIQUE (id_envio, id_unidad),
    created_at/updated_at TIMESTAMP
);

-- 5. Choferes y Madrinas asignados a Envío
CREATE TABLE lgs_envios_choferes (
    id          BIGINT PK AUTO_INCREMENT,
    id_envio    BIGINT FK→lgs_envios ON DELETE CASCADE,
    id_chofer   BIGINT FK→prv_det_choferes,
    UNIQUE (id_envio, id_chofer),
    created_at  TIMESTAMP
);

CREATE TABLE lgs_envios_madrinas (
    id          BIGINT PK AUTO_INCREMENT,
    id_envio    BIGINT FK→lgs_envios ON DELETE CASCADE,
    id_madrina  BIGINT FK→prv_det_madrinas,
    UNIQUE (id_envio, id_madrina),
    created_at  TIMESTAMP
);

-- 6. Expedientes de aprobación
CREATE TABLE lgs_expedientes (
    id_expediente       BIGINT PK AUTO_INCREMENT,
    folio               VARCHAR(20) UNIQUE NOT NULL,  -- EX-000001
    kilometraje_total   DECIMAL(10,2) NULL,
    costo_total         DECIMAL(12,2) NULL,
    fecha_expediente    DATE NULL,
    id_estado           TINYINT DEFAULT 1,
    -- 1=Creado, 2=Enviado a aprobación, 3=Regresado, 4=Rechazado, 5=Aprobado
    id_creador          BIGINT FK→usuarios,
    id_aprobador        BIGINT NULL FK→usuarios,
    obs_creador         TEXT NULL,
    obs_aprobador       TEXT NULL,
    created_at/updated_at TIMESTAMP
);

CREATE TABLE lgs_expedientes_envios (
    id              BIGINT PK AUTO_INCREMENT,
    id_expediente   BIGINT FK→lgs_expedientes ON DELETE CASCADE,
    id_envio        BIGINT FK→lgs_envios,
    UNIQUE (id_expediente, id_envio),
    created_at      TIMESTAMP
);

CREATE TABLE lgs_aprobadores_expedientes (
    id          BIGINT PK AUTO_INCREMENT,
    id_usuario  BIGINT FK→usuarios ON DELETE CASCADE,
    activo      TINYINT DEFAULT 1,
    created_at  TIMESTAMP
);

-- 7. Estado de VINs en Logística (satélite)
CREATE TABLE lgs_unidades (
    id_log_unidad           BIGINT PK AUTO_INCREMENT,
    id_unidad               BIGINT NOT NULL,          -- FK al VIN del sistema
    id_envio                BIGINT NULL FK→lgs_envios,
    id_estado_proceso       TINYINT DEFAULT 1,
    -- 1=En espera, 2=En proceso, 3=Finalizado
    carrocero               TINYINT DEFAULT 0,        -- 1=requiere carrocero
    fecha_salida            DATE NULL,
    fecha_llegada           DATE NULL,
    created_at/updated_at   TIMESTAMP
);

CREATE TABLE lgs_unidades_entrega_interna (
    id                  BIGINT PK AUTO_INCREMENT,
    id_unidad           BIGINT NOT NULL,
    solicitado_entrega  TINYINT DEFAULT 0,
    id_estado_entrega   TINYINT DEFAULT 1,            -- 1=Pendiente, 2=Completada
    fecha_entrega       TIMESTAMP NULL,
    created_at/updated_at TIMESTAMP
);

-- 8. Evidencias multimedia
CREATE TABLE lgs_evidencias (
    id_evidencia        BIGINT PK AUTO_INCREMENT,
    id_log_unidad       BIGINT FK→lgs_unidades ON DELETE CASCADE,
    nombre_archivo      VARCHAR(255) NOT NULL,        -- ruta relativa del archivo
    tipo_archivo        VARCHAR(10) NULL,             -- jpg, png, mp4, etc
    motivo              TINYINT NOT NULL,             -- 1=Salida, 2=Llegada
    id_cargador         BIGINT FK→usuarios,
    created_at          TIMESTAMP
);
```

---

## 7. 🛠️ Prerequisitos / Qué Revisar Antes de Empezar

Antes de crear código nuevo, verificar con una consulta o revisión manual:

1. **¿Existe tabla de distribuidores?** → Necesaria como destino en `lgs_envios`.
2. **¿Existe tabla de segmentos de vehículo?** → Necesaria para `lgs_costos_proveedor_segmento`.
3. **¿Cuál es la tabla principal de VINs?** → Revisar si es `inv_captura_vin`, `mrp_unidades_terminadas` o una join entre ambas.
4. **¿Cuáles son los procesos IDs 6, 13 y 20?** → Necesarios para la query maestra de la bandeja.
5. **¿Existe ya tabla `tipo_motivos_movimientos` y `tipo_envios`?** → Según docs técnicos, las mencionan, revisar si ya existen en DB.

---

## 8. 🧭 Orden de Desarrollo Recomendado (dependencias técnicas)

```
Paso 1: Prerequisitos (verificar tablas de distribuidores, segmentos, VINs)
    │
Paso 2: Crear catálogos base (lgs_tipo_envio, lgs_motivo_movimiento)
    │
Paso 3: Crear tabla de tarifas (lgs_costos_proveedor_segmento)
    │
Paso 4: Crear tabla lgs_unidades + lgs_unidades_entrega_interna
    │
Paso 5: Bandeja Principal (Controller + Service + Model + View + JS)
    │       ← PRIMER módulo visible para el usuario de logística
    │
Paso 6: Agregar campos faltantes en tablas existentes
    │       (licencia_file en prv_det_choferes)
    │
Paso 7: Crear tablas de Envíos (lgs_envios + relacionadas)
    │
Paso 8: Módulo de Envíos (Controller + Service + Model + View + JS)
    │       ← incluye generador de folio EN- y motor de costos
    │
Paso 9: Crear tablas de Expedientes (lgs_expedientes + relacionadas)
    │
Paso 10: Módulo de Expedientes (Controller + Service + Model + View + JS)
    │        ← incluye generador de folio EX- y notificación de correo
    │
Paso 11: Módulo de Aprobaciones (Controller + Service + Model + View + JS)
    │
Paso 12: Crear tabla lgs_evidencias
    │
Paso 13: Módulo de Evidencias + Botón "Siguiente Área"
    │
Paso 14: Panel de Rutas (Mapa + integración geodatos)
```
