# MRP System - Custom PHP MVC Framework

Este es un sistema de gestión MRP (Manufacturing Resource Planning) desarrollado sobre un framework **MVC propio** utilizando **PHP Nativo**. La arquitectura ha sido evolucionada para adoptar patrones modernos inspirados en Laravel, como la separación de lógica de negocio en Servicios y la transformación de datos mediante Resources.

## Características Principales

* **Arquitectura MVC:** Separación clara entre Modelos, Vistas y Controladores.
* **Capa de Servicios:** Lógica de negocio centralizada para procesos de MRP complejos.
* **API Resources (pendiente):** Transformación y formateo de datos antes de ser enviados al cliente.
* **API Responser:** Estandarización de respuestas JSON (`status`, `code`, `message`, `data`).
* **Routing Inteligente (pendiente):** Mapeo automático de verbos HTTP (Ej: `POST` → `create()`) sin declarar rutas manuales.
* **Compatible con PHP 8.2+:** Tipado de propiedades y cumplimiento de estándares modernos.

---

## Estructura del Proyecto

```text
mrp/
├── Assets/             # Archivos estáticos (JS, CSS, Imágenes, Libs Externas)
├── Config/             # Configuraciones globales y constantes del sistema
├── Controllers/        # Controladores (Gestionan el flujo de la petición)
├── Helpers/            # Funciones de utilidad global
├── Libraries/          # Núcleo del Framework
│   └── Core/           # Clases base (Autoload, Mysql, Controllers, ApiResponser)
├── Models/             # Interacción directa con la Base de Datos (CRUD)
├── Requests/           # Reglas de validación para formularios
├── Resources/          # Transformadores de datos para respuestas API
├── Services/           # Capa de Lógica de Negocio (Cálculos, Reglas MRP)
├── Views/              # Interfaz de usuario (Plantillas y Modales)
└── index.php           # Punto de entrada único del sistema

```
## Taxonomía de Tablas
|Prefijo|Tipo de Tabla|Descripción|Ejemplo|
|:--|:--|:--|:--|
|`{$parent}_cat_`|Catálogo (Master)|Datos maestros de larga duración (estáticos).|`wms_cat_monedas`|
|`{$parent}_tra_`|Transaccional|Registros de eventos que ocurren en el tiempo.|`com_tra_compras, wms_tra_movimientos`|
|`{$parent}_rel_`|Relacional (Pivot)|Tablas para relaciones Muchos a Muchos (N:N).|`prv_rel_proveedor_marcas`|
|`log_`|Bitácora/Auditoría|Registros de eventos del sistema (Inmutable).|`log_audit, log_accesos`|
|`{$parent}_det_`|Detalle|Extensiones de una transaccional (1:N).|`com_det_compra_partidas`|