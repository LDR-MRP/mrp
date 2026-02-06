# Guía de Desarrollo: Creación de Módulos (Estándar MRP)

Para mantener la integridad de la arquitectura **mrp** y asegurar la compatibilidad con **PHP 8.2+**, todos los nuevos módulos deben seguir este flujo de trabajo. Esta estructura separa la lógica de negocio de la persistencia de datos y la presentación.

---

## 1. La Capa de Validación (`Requests/`)
Esta capa se encarga de centralizar las reglas de validación de los formularios. Su objetivo es asegurar que los datos recibidos (`$_POST`, `$_GET`, `$_PUT`) cumplan con los requisitos de negocio antes de procesarlos.
- **Ubicación:** `/Requests/[Nombre]Request.php`
- **Regla de Oro:** Si la validación falla, el flujo debe detenerse inmediatamente y devolver un error al cliente.

```php
<?php

class CompraRequest {

    public static function validate(array $data) {
        $errors = [];

        if (empty($data['id_proveedor'])) {
            $errors[] = "El proveedor es obligatorio.";
        }

        if (empty($data['productos']) || !is_array($datos['productos'])) {
            $errors[] = "Debe incluir al menos un producto.";
        }

        foreach ($data['productos'] as $prod) {
            if ($prod['cantidad'] <= 0) {
                $errors[] = "La cantidad del producto {$prod['id']} debe ser mayor a cero.";
            }
        }

        return $errors;
    }
}
```
## 2. El Controlador (`Controllers/`)
El controlador es un orquestador "delgado". Su función es recibir la petición, delegar al servicio y devolver una respuesta estandarizada.

* **Ubicación:** `/Controllers/[Nombre].php`
* **Regla de Oro:** Debe declarar sus propiedades para evitar el error *Deprecated: Creation of dynamic property*.

```php
<?php

class Com_compras extends Controllers
{
    use ApiResponser;

    public function __construct()
    {
        parent::__construct();
    }

    public function create(): array
    {
        try{
            $request = new Com_comprasRequest($_POST);

            $request->validate();

            $comprasService = new Com_comprasService();

            $comprasService->create(data: $request->all());

            return $this->successResponse(message: "Compra procesada con éxito.", code: 201);

        } catch(Throwable $t){
            $code = $t->getCode();

            return $this->errorResponse(
                ($code == 422) ? "Errores de validación" : "Error de sistema",
                ($code >= 400 && $code <= 599) ? $code : 500,
                ($code == 422) ? json_decode($t->getMessage()) : $t->getMessage()
            );
        }
        
    }
}
```

## 3. La Capa de Servicio (`Services/`)
Aquí reside la `inteligencia` del MRP. Cálculos matemáticos, validaciones de stock y reglas de negocio.
- **Ubicación:** `/Services/NombreModuloService.php`
- Regla: Jamás debe imprimir (`echo`) ni usar `header()`. Solo devuelve datos o `false`.

```php
<?php
class Com_comprasService{

    public function create($data): bool
    {
        try {
            $db = $this->model->getConexion();

            $db->beginTransaction();

            $idCompra = $this->model->insertCompra($data);

            if($idCompra <= 0){
                throw new Exception("Error al registrar la cabecera de la compra.", 500);
            }

            $detalle = json_decode($data['detalle_partidas'], true);
            foreach ($detalle as $item) {
                $this->model->insertDetalle($idCompra, $item);
            }

            $db->commit();
            
            return true;

        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            return false;
        }
    }
}
```

## 4. El Modelo (`Models/`)
Únicamente contiene sentencias SQL puras.
- **Regla:** No debe contener condiciones `if` de negocio ni formatos de moneda.