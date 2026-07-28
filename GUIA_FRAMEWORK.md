# Guía del Framework MRP (PHP Nativo - Arquitectura MVC + Servicios)

Este documento detalla la estructura, arquitectura, convenciones y el flujo de trabajo para desarrollar módulos dentro del **Framework MVC casero del Sistema MRP**, compatible con **PHP 8.2+**.

---

## 📁 1. Estructura General del Proyecto

| Carpeta / Archivo | Función y Contenido |
| :--- | :--- |
| `index.php` | **Punto de entrada único**. Configura el entorno (Local/Prod), sesiones (SSO), y redirige las solicitudes a la API o al enrutador web. |
| `Libraries/Core/` | **Núcleo del Framework**. Contiene las clases base: Autoload, Controllers, Mysql, ApiResponser, ApiRouter y Views. |
| `Controllers/` | **Controladores**. Reciben las peticiones, invocan validaciones y delegan la lógica a los Servicios. |
| `Services/` | **Lógica de Negocio**. Cálculos MRP, reglas transaccionales y procesos complejos. |
| `Requests/` | **Validación**. Clases de validación previa de datos (`$_POST`, `$_GET`, payloads JSON). |
| `Models/` | **Persistencia (SQL)**. Consultas puras a la base de datos (Insert, Select, Update, Delete). |
| `Views/` | **Vistas HTML/PHP**. Plantillas de la interfaz gráfica y modales. |
| `Routes/` | Declaración de rutas para la API REST (`Routes/api.php`). |
| `Config/` | Constantes de configuración global, credenciales de BD y URLs del sistema. |
| `Helpers/` | Funciones auxiliares de uso común (`base_url()`, `media()`, `dep()`, etc.). |

---

## 🔄 2. Cómo Funciona el Enrutamiento

El sistema soporta dos modos de enrutamiento:

### A. Modo MVC Tradicional (Vistas Web)
- **Patrón de URL**: `http://dominio.com/{Controlador}/{Metodo}/{Parametros}`
- Ejemplo: `http://dominio.com/inv_almacenes/ver/15`
  - Carga el controlador `Controllers/Inv_almacenes.php`.
  - Ejecuta el método `ver('15')`.
  - Instancia automáticamente su modelo correspondiente `Models/Inv_almacenesModel.php` mediante la clase base `Controllers`.

### B. Modo API REST (`/api/...`)
- **Patrón de URL**: `http://dominio.com/api/v1/{endpoint}`
- Se procesa mediante `Libraries/Core/ApiRouter.php` consultando las rutas registradas en `Routes/api.php`. Soporta verbos HTTP (`GET`, `POST`, `PUT`, `DELETE`), parámetros dinámicos en URL (`{id}`) y Middlewares.

---

## 🗄️ 3. Taxonomía de Base de Datos (Estándar MRP)

Todas las tablas de la base de datos deben seguir la convención de nombres estandarizada:

| Prefijo | Tipo de Tabla | Ejemplo | Descripción |
| :--- | :--- | :--- | :--- |
| `{$modulo}_cat_` | Catálogo (Maestro) | `wms_cat_monedas` | Datos maestros estáticos o semiestáticos. |
| `{$modulo}_tra_` | Transaccional | `com_tra_compras` | Eventos y operaciones producidos en el tiempo. |
| `{$modulo}_det_` | Detalle | `com_det_compra_partidas` | Detalle (1:N) de una tabla transaccional. |
| `{$modulo}_rel_` | Relacional (Pivot) | `prv_rel_proveedor_marcas` | Tablas intermedias para relaciones Muchos a Muchos (N:M). |
| `log_` | Bitácora / Auditoría | `log_audit` | Registros inmutables de eventos y accesos al sistema. |

---

## 🛠️ 4. Flujo Paso a Paso para Programar un Módulo

Para crear un nuevo módulo (por ejemplo: **Compras**), se sigue una arquitectura de **4 capas**:

```text
[Cliente / Request] ➔ 1. Request (Validación) ➔ 2. Controller (Orquestador) ➔ 3. Service (Reglas/Lógica) ➔ 4. Model (SQL)
```

### Paso 1: Crear la Validación (`Requests/Com_comprasRequest.php`)
```php
<?php

class Com_comprasRequest {
    public static function validate(array $data): array {
        $errors = [];
        if (empty($data['id_proveedor'])) {
            $errors[] = "El proveedor es obligatorio.";
        }
        if (empty($data['productos']) || !is_array($data['productos'])) {
            $errors[] = "Debe incluir al menos un producto.";
        }
        return $errors;
    }
}
```

### Paso 2: Crear el Modelo (`Models/Com_comprasModel.php`)
Solo contiene sentencias SQL puras usando la clase base `Mysql`. No debe tener lógica de negocio.
```php
<?php

class Com_comprasModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }

    public function insertCompra(array $data): int {
        $query = "INSERT INTO com_tra_compras (id_proveedor, total, fecha) VALUES (?, ?, NOW())";
        $arrData = [$data['id_proveedor'], $data['total']];
        return $this->insert($query, $arrData);
    }
}
```

### Paso 3: Crear el Servicio (`Services/Com_comprasService.php`)
Alberga la lógica de negocio y el manejo de transacciones SQL. **Nunca hace `echo` ni redirige con `header()`**.
```php
<?php

class Com_comprasService {
    private Com_comprasModel $model;

    public function __construct() {
        $this->model = new Com_comprasModel();
    }

    public function create(array $data): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $idCompra = $this->model->insertCompra($data);
            if ($idCompra <= 0) {
                throw new Exception("Error al registrar la cabecera de la compra.", 500);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            throw $e;
        }
    }
}
```

### Paso 4: Crear el Controlador (`Controllers/Com_compras.php`)
Usa el trait `ApiResponser` para devolver respuestas estandarizadas en formato JSON (`status`, `code`, `message`, `data`).
```php
<?php

class Com_compras extends Controllers {
    use ApiResponser;

    public function __construct() {
        parent::__construct();
    }

    public function create(): void {
        try {
            $errors = Com_comprasRequest::validate($_POST);
            if (!empty($errors)) {
                $this->errorResponse("Errores de validación", 422, $errors);
            }

            $service = new Com_comprasService();
            $service->create($_POST);

            $this->successResponse(null, "Compra registrada con éxito.", 201);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }
}
```

---

## 📌 5. Reglas de Oro del Proyecto
1. **Evitar propiedades dinámicas**: Declarar explícitamente las propiedades de clase para garantizar compatibilidad con **PHP 8.2+**.
2. **Separación estricta de responsabilidades**: No colocar sentencias SQL en los controladores ni `echo`/vistas dentro de los Servicios.
3. **Manejo de Transacciones**: Toda operación que afecte múltiples tablas debe ejecutarse dentro de un bloque `$db->beginTransaction()`, `$db->commit()` y `$db->rollBack()` en la capa de Servicio.
4. **Respuestas Estandarizadas**: Utilizar el trait `ApiResponser` (`successResponse` / `errorResponse`) para todas las respuestas de API.
