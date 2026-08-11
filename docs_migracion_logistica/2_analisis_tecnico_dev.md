# 📐 Análisis Técnico para Desarrolladores — Módulo de Logística
### Stack: PHP 8 + PDO/MySQL (4 capas) · 04 Agosto 2026

> **Lectura complementaria:** [5_flujo_operativo_logistica.md](5_flujo_operativo_logistica.md) · [PLAN_DESARROLLO_TECNICO_LOGISTICA.md](../PLAN_DESARROLLO_TECNICO_LOGISTICA.md)

---

## 1. Arquitectura del Framework

```
index.php  (Router)
    ├── URL: base_url / Controlador / Metodo / Params
    │         ↓
    ├── Controllers/{Nombre}.php
    │       → Recibe $_POST/$_GET, llama al Service, renderiza vista o responde JSON
    │         ↓
    ├── Services/{Nombre}.php
    │       → Lógica de negocio, transacciones PDO, validaciones de dominio
    │         ↓
    ├── Models/{Nombre}.php   (extiende Mysql)
    │       → SQL raw con PDO. Métodos: select(), select_all(), insert(), update(), delete()
    │         ↓
    └── Base de Datos MySQL
```

**Convenciones:**
- Los Controllers usan el trait `ApiResponser` → `$this->successResponse($data, $msg, $code)` y `$this->errorResponse($msg, $code)`.
- Los Models tienen `$this->getConexion()` para obtener el PDO y hacer transacciones.
- Las Views cargan con `$this->views->getView($this, "../Controlador/index", $data)`.
- El JS hace `fetch(base_url + '/Controlador/metodo', { method: 'POST', body: formData })`.
- La respuesta JSON siempre tiene: `{ "success": bool, "message": "...", "data": {...}, "code": 200 }`.

---

## 2. Tablas Existentes (Épica 1 — Ya en Producción)

### `prv_cat_proveedores` (Trasladistas)
```sql
id_proveedor, nombre_comercial, razon_social, rfc, id_tipo_persona,
id_regimen_fiscal, correo_contacto, telefono_contacto, activo,
created_by, updated_by, created_at, updated_at, deleted_at
```

### `prv_det_choferes`
```sql
id_chofer, id_proveedor, nombre_completo, num_licencia,
tipo_licencia, vigencia_licencia, telefono, estatus_operativo,
created_by, updated_by, created_at, updated_at, deleted_at
```

### `prv_det_madrinas`
```sql
id_madrina, id_proveedor, nombre_madrina, placa_tracto,
placa_caja, capacidad_unidades, num_eco, estatus_operativo,
created_by, updated_by, created_at, updated_at, deleted_at
```

### `prv_det_madrina_chofer_historial`
```sql
id_historial, id_madrina, id_chofer, fecha_inicio, fecha_fin,
activo (1=activo, 0=inactivo), observaciones,
created_by, updated_by, created_at, updated_at
```

### `prv_cat_actividades` + `prv_rel_proveedores_actividades`
Catálogo de actividades. `cve_actividad = 'TRASLADO_UNIDADES'` identifica a los trasladistas.

---

## 3. Tablas a Crear (DDL completo en orden de dependencias FK)

```sql
-- ================================================================
-- ÉPICA 2: CATÁLOGOS CONFIGURABLES
-- ================================================================

CREATE TABLE IF NOT EXISTS lgs_cat_tipo_traslado (
    id_tipo_traslado TINYINT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    activo           TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO lgs_cat_tipo_traslado (nombre) VALUES
('Madrina'), ('Chofer (Rodando)');

-- ──────────────────────────────────────────────────────────────────

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

-- ──────────────────────────────────────────────────────────────────

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

-- ──────────────────────────────────────────────────────────────────

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

-- ──────────────────────────────────────────────────────────────────

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

-- ================================================================
-- ÉPICA 2: ENVÍOS
-- ================================================================

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

-- ──────────────────────────────────────────────────────────────────

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

-- ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS lgs_costos_proveedor_segmento (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor BIGINT NOT NULL COMMENT 'FK prv_cat_proveedores',
    id_segmento  INT NULL COMMENT 'Segmento del vehículo',
    num_vins_min TINYINT DEFAULT 1,
    num_vins_max TINYINT DEFAULT 99,
    costo_por_km DECIMAL(10,4) NOT NULL,
    factor       DECIMAL(5,2) DEFAULT 1.00,
    activo       TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- ÉPICA 3: PLANEACIONES Y APROBACIONES
-- ================================================================

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

-- ================================================================
-- ÉPICA 4: EJECUCIÓN Y SOLICITUDES DE ENTREGA
-- ================================================================

CREATE TABLE IF NOT EXISTS lgs_solicitudes_entrega (
    id_solicitud     BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio         BIGINT NOT NULL,
    id_unidad        BIGINT NOT NULL COMMENT 'VIN FK mrp_unidades_terminadas',
    posicion_acomodo TINYINT UNSIGNED DEFAULT 1,
    id_estado        TINYINT DEFAULT 1
        COMMENT '1=Solicitada 2=Entregada a Trasladista 3=Cancelada',
    solicitado_by    BIGINT UNSIGNED NULL,
    confirmado_by    BIGINT UNSIGNED NULL,
    solicitado_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmado_at    DATETIME NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- ÉPICA 5: EVIDENCIAS MULTIMEDIA
-- ================================================================

CREATE TABLE IF NOT EXISTS lgs_evidencias (
    id_evidencia   BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_envio       BIGINT NOT NULL,
    tipo           TINYINT NOT NULL COMMENT '1=Salida (Recepción) 2=Llegada (Entrega)',
    nombre_archivo VARCHAR(255) NOT NULL COMMENT 'Ruta relativa: Assets/uploads/logistica/',
    tipo_archivo   VARCHAR(10) NULL COMMENT 'jpg, png, mp4, mov, etc.',
    created_by     BIGINT UNSIGNED NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_envio) REFERENCES lgs_envios(id_envio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Lógica de Negocio Crítica — PHP Puro (PDO)

### 4.1 Generador de Folio `EN-000001` con bloqueo transaccional

```php
// En Lgs_enviosModel.php
public function generarFolio(PDO $db): string {
    // SELECT FOR UPDATE para evitar duplicados en concurrencia
    $stmt = $db->prepare("SELECT folio FROM lgs_envios ORDER BY id_envio DESC LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $ultimo = $stmt->fetchColumn();
    if (!$ultimo) {
        return 'EN-000001';
    }
    $num = intval(substr($ultimo, 3)) + 1;
    return 'EN-' . str_pad($num, 6, '0', STR_PAD_LEFT);
}

// Uso en Lgs_enviosService::create()
public function create(array $data, int $userId): int {
    $db = $this->model->getConexion();
    try {
        $db->beginTransaction();
        $folio = $this->model->generarFolio($db);
        $data['folio'] = $folio;
        $id = $this->model->insertEnvio($data, $userId);
        $db->commit();
        return $id;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

### 4.2 Motor de Cálculo de Costo

```php
// En Lgs_enviosService.php
public function calcularCostoUnidad(int $idEnvio, int $idVin): float {
    $envio = $this->model->getEnvio($idEnvio);
    $vin   = $this->model->getVin($idVin);
    
    // Si es Rodando (Chofer): tarifa directa por KM
    if ($envio['id_tipo_traslado'] == 2) { // Asumiendo 2 = Chofer (Rodando)
        $tarifa = $this->model->getTarifaRodando($envio['id_proveedor']);
        return round(floatval($envio['km_total']) * $tarifa['costo_por_km'], 2);
    }
    
    // Si es Madrina: depende de cuántos VINs van en esa misma Madrina
    $numVinsMadrina = $this->model->countVinsEnMadrina($idEnvio, $vin['id_madrina']);
    $tarifa = $this->model->getTarifaMadrina($envio['id_proveedor'], $numVinsMadrina);
    
    if (!$tarifa) return 0.0;
    
    // El costo total de la madrina se divide entre los VINs o se aplica unitario según negocio
    $costoTotalMadrina = floatval($envio['km_total']) * $tarifa['costo_por_km'] * $tarifa['factor'];
    return round($costoTotalMadrina / $numVinsMadrina, 2);
}

public function recalcularCostoTotal(int $idEnvio): float {
    // Sumar el costo_unidad de todos los VINs en el envío
    $vins = $this->model->getVinsDeEnvio($idEnvio);
    $costoTotal = 0;
    foreach ($vins as $vin) {
        $costoVin = $this->calcularCostoUnidad($idEnvio, $vin['id_unidad']);
        $this->model->updateCostoVin($idEnvio, $vin['id_unidad'], $costoVin);
        $costoTotal += $costoVin;
    }
    $this->model->updateCostoTotal($idEnvio, $costoTotal);
    return $costoTotal;
}
```

### 4.3 Asignar VIN con posición de acomodo

```php
// Lgs_enviosService::asignarVin()
public function asignarVin(int $idEnvio, int $idUnidad, array $params, int $userId): bool {
    $db = $this->model->getConexion();
    try {
        $db->beginTransaction();
        // Insertar VIN con su id_madrina o id_chofer y posicion si aplica
        $this->model->insertVin($idEnvio, $idUnidad, $params, $userId);
        // Recalcular costo total del envío y unitario
        $this->recalcularCostoTotal($idEnvio);
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// Reordenar posiciones (drag-and-drop)
public function reordenarVins(int $idEnvio, array $orden): bool {
    // $orden = [ ['id_envio_vin' => 5, 'posicion_acomodo' => 1], ... ]
    $db = $this->model->getConexion();
    $db->beginTransaction();
    try {
        foreach ($orden as $item) {
            $this->model->updatePosicionVin(
                intval($item['id']),
                intval($item['posicion_acomodo'])
            );
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

### 4.4 Enviar Planeación a Aprobación + Correo

```php
// Lgs_planeacionesService::enviarAprobacion()
public function enviarAprobacion(int $idPlaneacion, int $userId): bool {
    $db = $this->model->getConexion();
    try {
        $db->beginTransaction();
        // Estado planeación → 2 (Enviada)
        $this->model->updateEstadoPlaneacion($idPlaneacion, 2, $userId);
        // Estado todos los envíos vinculados → 2 (En Revisión)
        $this->model->updateEstadoEnviosDeP laneacion($idPlaneacion, 2);
        $db->commit();

        // Correo fuera de la transacción (no bloquea rollback si falla el mail)
        $aprobadores = $this->model->getAprobadores();
        $planeacion  = $this->model->getPlaneacion($idPlaneacion);
        foreach ($aprobadores as $apr) {
            sendMailLocal(
                $apr['email'],
                'Planeación de Logística pendiente de aprobación',
                $this->_buildEmailAprobacion($planeacion, $apr)
            );
        }
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

### 4.5 Aprobar / Regresar Planeación

```php
// Lgs_aprobacionesService::aprobar()
public function aprobar(int $idPlaneacion, int $userId): bool {
    $db = $this->model->getConexion();
    try {
        $db->beginTransaction();
        $this->model->updateEstadoPlaneacion($idPlaneacion, 5, $userId); // 5=Aprobada
        $this->model->updateEstadoEnviosDePlaneacion($idPlaneacion, 3);  // 3=Aprobado
        $db->commit();

        // Correo al operador creador
        $planeacion = $this->model->getPlaneacion($idPlaneacion);
        $creador    = $this->model->getUsuario($planeacion['created_by']);
        sendMailLocal($creador['email'], 'Planeación aprobada', $this->_buildEmailAprobado($planeacion));
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// Lgs_aprobacionesService::regresar()
public function regresar(int $idPlaneacion, string $observaciones, int $userId): bool {
    $db = $this->model->getConexion();
    try {
        $db->beginTransaction();
        $this->model->updateEstadoPlaneacionConObs($idPlaneacion, 3, $observaciones, $userId); // 3=Regresada
        $this->model->updateEstadoEnviosDePlaneacion($idPlaneacion, 4); // 4=Regresado
        $db->commit();

        $planeacion = $this->model->getPlaneacion($idPlaneacion);
        $creador    = $this->model->getUsuario($planeacion['created_by']);
        sendMailLocal($creador['email'], 'Planeación regresada con observaciones', $this->_buildEmailRegresado($planeacion));
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

### 4.6 Cálculo de km por API

```php
// Lgs_enviosService::calcularKm()
public function calcularKm(int $idOrigen, int $idDestino): float {
    $origen  = $this->model->getOrigen($idOrigen);
    $destino = $this->model->getDestino($idDestino);

    // Si ambos tienen lat/lng → API de Google o Haversine
    if ($origen['lat'] && $origen['lng'] && $destino['lat'] && $destino['lng']) {
        // Opción A: Google Maps Distance Matrix API
        // $km = $this->_googleDistanceMatrix($origen, $destino);

        // Opción B: Haversine (geodésico, sin API key, aproximado)
        $km = $this->_haversine(
            floatval($origen['lat']),  floatval($origen['lng']),
            floatval($destino['lat']), floatval($destino['lng'])
        );
        return round($km, 2);
    }
    return 0.0;
}

private function _haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371; // Radio de la Tierra en km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
```

---

## 5. Rutas del Sistema (index.php — patrón URL)

```
GET  base_url/Lgs_envios                    → Vista principal de Envíos
GET  base_url/Lgs_envios/getEnvios          → JSON: DataTable de envíos (filtros por $_GET)
GET  base_url/Lgs_envios/getEnvio/{id}      → JSON: Detalle de un envío
POST base_url/Lgs_envios/store              → Crear/Editar envío ($_POST)
POST base_url/Lgs_envios/delete/{id}        → Soft delete
POST base_url/Lgs_envios/asignarVin         → Asignar VIN al envío
POST base_url/Lgs_envios/quitarVin          → Quitar VIN del envío
POST base_url/Lgs_envios/reordenarVins      → Reordenar posición de acomodo (JSON array)
GET  base_url/Lgs_envios/getVinsDisponibles → JSON: VINs disponibles para asignar
GET  base_url/Lgs_envios/calcularKm         → JSON: km calculado de origen→destino
GET  base_url/Lgs_envios/calcularCosto      → JSON: costo estimado

GET  base_url/Lgs_planeaciones              → Vista de Planeaciones
GET  base_url/Lgs_planeaciones/getPlaneaciones     → JSON: DataTable
POST base_url/Lgs_planeaciones/store               → Crear/Editar planeación
POST base_url/Lgs_planeaciones/agregarEnvio        → Agregar envío a planeación
POST base_url/Lgs_planeaciones/quitarEnvio         → Quitar envío de planeación
POST base_url/Lgs_planeaciones/enviarAprobacion    → Enviar planeación + correo

GET  base_url/Lgs_aprobaciones             → Vista del panel de aprobaciones
GET  base_url/Lgs_aprobaciones/getPendientes → JSON: planeaciones en estado 2 o 3
POST base_url/Lgs_aprobaciones/aprobar/{id} → Aprobar + cascada de estados + correo
POST base_url/Lgs_aprobaciones/regresar/{id}→ Regresar con observaciones + correo

POST base_url/Lgs_ejecucion/iniciar        → Iniciar despacho (estado 5 + solicitudes)
POST base_url/Lgs_ejecucion/confirmarVin   → Área Entregas confirma salida de VIN
POST base_url/Lgs_ejecucion/confirmarLlegada → Confirmar llegada (estado 7 + fecha real)

POST base_url/Lgs_evidencias/upload        → Subir foto/video (multipart/form-data)
POST base_url/Lgs_evidencias/delete/{id}   → Eliminar evidencia + archivo físico
GET  base_url/Lgs_evidencias/getByEnvio/{id} → JSON: evidencias de un envío

GET  base_url/Lgs_panelrutas              → Vista del mapa
GET  base_url/Lgs_panelrutas/getRutasActivas → JSON: envíos en estado 6 con lat/lng
```

---

## 6. Validaciones de Formulario — Patrón `Requests/`

```php
// Requests/Lgs_enviosRequest.php
class Lgs_enviosRequest {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['id_proveedor']))            $errors[] = 'Trasladista requerido.';
        if (empty($data['id_origen']))               $errors[] = 'Origen requerido.';
        if (empty($data['id_destino']) && empty($data['destino_nombre_libre']))
                                                     $errors[] = 'Destino requerido.';
        if (empty($data['fecha_tentativa_envio']))   $errors[] = 'Fecha tentativa de envío requerida.';
        if (empty($data['fecha_tentativa_llegada'])) $errors[] = 'Fecha tentativa de llegada requerida.';
        if (!empty($data['fecha_tentativa_llegada']) && !empty($data['fecha_tentativa_envio'])) {
            if (strtotime($data['fecha_tentativa_llegada']) <= strtotime($data['fecha_tentativa_envio'])) {
                $errors[] = 'La fecha de llegada debe ser posterior a la de envío.';
            }
        }
        return $errors;
    }
}

// Requests/Lgs_planeacionesRequest.php
class Lgs_planeacionesRequest {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['descripcion'])) $errors[] = 'Descripción requerida.';
        return $errors;
    }
}
```

---

## 7. Estructura de Archivos por Módulo

```
Controllers/
    Lgs_envios.php
    Lgs_planeaciones.php
    Lgs_aprobaciones.php
    Lgs_ejecucion.php
    Lgs_evidencias.php
    Lgs_panelrutas.php

Services/
    Lgs_enviosService.php
    Lgs_planeacionesService.php
    Lgs_aprobacionesService.php
    Lgs_ejecucionService.php
    Lgs_evidenciasService.php

Models/
    Lgs_enviosModel.php
    Lgs_planeacionesModel.php
    Lgs_aprobacionesModel.php
    Lgs_ejecucionModel.php
    Lgs_evidenciasModel.php
    Lgs_panelrutasModel.php

Requests/
    Lgs_enviosRequest.php
    Lgs_planeacionesRequest.php

Views/
    Lgs_envios/index.php
    Lgs_planeaciones/index.php
    Lgs_aprobaciones/index.php
    Lgs_ejecucion/index.php
    Lgs_panelrutas/index.php

Assets/js/modulos/
    functions_lgs_envios.js
    functions_lgs_planeaciones.js
    functions_lgs_aprobaciones.js
    functions_lgs_ejecucion.js
    functions_lgs_evidencias.js
    functions_lgs_panelrutas.js

Assets/uploads/logistica/
    evidencias/          ← Fotos y videos de salida/llegada
```

---

## 8. Almacenamiento de Evidencias

```php
// En Lgs_evidenciasService.php
public function subirEvidencia(int $idEnvio, int $tipo, array $file, int $userId): int {
    // Validar tipo de archivo
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mov'];
    if (!in_array($ext, $permitidos, true)) {
        throw new Exception("Tipo de archivo no permitido: {$ext}");
    }

    // Validar tamaño máximo (20MB)
    if ($file['size'] > 20971520) {
        throw new Exception("El archivo supera el límite de 20MB.");
    }

    // Nombre único
    $nombreArchivo = uniqid("lgs_{$idEnvio}_") . '.' . $ext;
    $destino = __DIR__ . '/../Assets/uploads/logistica/evidencias/' . $nombreArchivo;

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        throw new Exception("Error al mover el archivo subido.");
    }

    return $this->model->insertEvidencia($idEnvio, $tipo, $nombreArchivo, $ext, $userId);
}

public function eliminarEvidencia(int $idEvidencia, int $userId): bool {
    $ev = $this->model->getEvidencia($idEvidencia);
    if (!$ev) throw new Exception("Evidencia no encontrada.");

    $ruta = __DIR__ . '/../Assets/uploads/logistica/evidencias/' . $ev['nombre_archivo'];
    if (file_exists($ruta)) @unlink($ruta);

    return $this->model->deleteEvidencia($idEvidencia);
}
```

---

## 9. Notificaciones de Correo

El proyecto usa `sendMailLocal()` (helper de PHPMailer ya existente).

```php
// Uso estándar en Services
sendMailLocal(
    $destinatario,        // string: email del destinatario
    $asunto,              // string
    $cuerpoHtml           // string: HTML del correo
);
```

**Correos del módulo de Logística:**

| Evento | Destinatario | Asunto |
|---|---|---|
| Planeación enviada a aprobación | Todos los `lgs_aprobadores` (activo=1) | "Planeación {folio} pendiente de aprobación" |
| Planeación aprobada | Operador creador | "Planeación {folio} aprobada ✅" |
| Planeación regresada | Operador creador | "Planeación {folio} regresada con observaciones" |

---

## 10. Tabla de Referencia — Procesos e IDs

### Procesos que ponen una unidad en Logística

| `id_proceso` | Área | Origen |
|---|---|---|
| 6 | Logística | Puerto |
| 13 | Logística | Planta |
| 20 | Logística | Almacén |

### Estados del Envío (`lgs_envios.id_estado`)

| ID | Estado | Módulo que lo activa |
|:---:|---|---|
| 1 | 🟡 Creado | Formulario de creación |
| 2 | 🔵 En Revisión | "Enviar a Aprobación" |
| 3 | 🟢 Aprobado | Aprobador confirma |
| 4 | 🔴 Regresado | Aprobador regresa |
| 5 | ⚡ En Ejecución | "Iniciar Despacho" |
| 6 | 🚛 En Tránsito | Área de Entregas confirma todos los VINs |
| 7 | ✅ Entregado | "Confirmar Llegada" |
| 8 | ⛔ Cancelado | Cancelación manual |

### Estados de Planeación (`lgs_planeaciones.id_estado`)

| ID | Estado |
|:---:|---|
| 1 | Creada |
| 2 | Enviada a Aprobación |
| 3 | Regresada |
| 5 | Aprobada |

### Estados Solicitud de Entrega (`lgs_solicitudes_entrega.id_estado`)

| ID | Estado |
|:---:|---|
| 1 | Solicitada |
| 2 | Entregada a Trasladista |
| 3 | Cancelada |
