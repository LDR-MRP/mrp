# Plan de Aprendizaje y Desarrollo Paso a Paso: Módulo de Logística (TDD + Best Practices)

Este plan está diseñado para que implementes el sistema de logística de forma autodidacta, paso a paso, aplicando **Desarrollo Guiado por Pruebas (TDD)** en Laravel (`tms-api`) y conectándolo con React (`tms`).

Puedes entregarle este plan a tu agente de IA para que te guíe y valide cada fase individualmente antes de avanzar.

---

## 🛠️ Entorno Identificado en WSL
*   **Backend (`~/proyectos/logistica/tms-api`):** Laravel 13.x (PHP 8.3) con soporte nativo para SQLite y Sanctum.
*   **Frontend (`~/proyectos/logistica/tms`):** React 19.x estructurado con Vite.

---

## 🗺️ FASE 1: Cimientos de la API y TDD (Catálogos Básicos)

El objetivo de esta fase es dominar la creación de endpoints seguros con validación, usando pruebas automatizadas antes de escribir código.

### Paso 1: Configurar Base de Datos de Prueba (SQLite)
*   **Objetivo:** Configurar un entorno local y rápido para pruebas en memoria.
*   **Práctica:**
    1.  Crea o edita tu `.env` en `tms-api` con `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:` (o ruta a un archivo `.sqlite` local para desarrollo).
    2.  Ejecuta `php artisan migrate` para asegurar que la conexión básica de Laravel funcione.

### Paso 2: CRUD de Proveedores con TDD
*   **Objetivo:** Escribir la prueba primero y luego construir el código mínimo necesario para que pase.
*   **Instrucciones Paso a Paso:**
    1.  Crea la clase de prueba: `php artisan make:test ProveedorTest`.
    2.  En `tests/Feature/ProveedorTest.php`, escribe una prueba para verificar que `POST /api/logistica/proveedores` valida el RFC según tipo de persona (Moral = 12 caracteres, Física = 13 caracteres) y responde con un código `422` en caso de error.
    3.  Ejecuta la prueba con `php artisan test` (debe fallar).
    4.  Crea la migración de `proveedores`, el modelo `Proveedor`, el request de validación `StoreProveedorRequest` y el controlador `ProveedorController`.
    5.  Escribe el código necesario hasta que la prueba pase en verde (`php artisan test`).
    6.  Agrega pruebas de verificación para los métodos `GET`, `PUT` y `DELETE`.

### Paso 3: CRUD de Choferes (Manejo de Archivos)
*   **Objetivo:** Implementar la relación con Proveedores y la subida segura de licencias en PDF o imágenes.
*   **Instrucciones Paso a Paso:**
    1.  Escribe en `tests/Feature/ChoferTest.php` una prueba para simular la subida de un archivo:
        ```php
        Storage::fake('logistica');
        $file = UploadedFile::fake()->create('licencia.pdf', 1024);
        ```
    2.  Verifica que el archivo sea guardado con un nombre sanitizado en el disco y que se inserte la FK hacia un `Proveedor` existente.
    3.  Implementa la migración de `choferes`, el modelo `Chofer` con relaciones Eloquent, y pasa la prueba.

### Paso 4: CRUD de Madrinas e Historial de Choferes de Madrinas
*   **Objetivo:** Implementar el CRUD para madrinas y un historial dinámico de asignación de choferes (`madrina_chofer_historial`) para tener flexibilidad sobre qué chofer conduce qué madrina en cada momento, considerando que un chofer de madrina maneja la nodriza y su relación varía a lo largo del tiempo.
*   **Instrucciones Paso a Paso:**
    1.  Crea `tests/Feature/MadrinaTest.php`.
    2.  Escribe pruebas para validar:
        - Que al crear una madrina, la capacidad sea un entero positivo (ej. 1 a 20) y las placas (tracto y caja) tengan formatos válidos.
        - Que se pueda asignar un chofer activo a una madrina (creando un registro en la tabla de historial con `activo = true` y `fecha_inicio`).
        - Que al reasignar un chofer a una madrina, el chofer activo anterior pase a estar inactivo (`activo = false` y se registre su `fecha_fin`), y se cree el nuevo registro activo.
        - Que se pueda consultar el historial completo de choferes que han conducido una madrina en particular.
    3.  Crea las migraciones para `madrinas` (sin campo de chofer de texto estático legado, sino vinculada dinámicamente) y `madrina_chofer_historial`.
    4.  Crea los modelos `Madrina` y `MadrinaChoferHistorial` definiendo sus relaciones Eloquent (`choferActivo`, `historialChoferes`).
    5.  Implementa `MadrinaController` con los métodos CRUD estándar más un método/endpoint `POST /api/logistica/madrinas/{madrina}/asignar-chofer` para gestionar las reasignaciones históricas.
    6.  Ejecuta `php artisan test --filter=MadrinaTest` para corroborar que la lógica funcione correctamente.


---

## 📬 FASE 2: Integración con Postman y Documentación

*   **Objetivo:** Aprender a probar las APIs de forma interactiva y documentar el comportamiento para el frontend.
*   **Práctica:**
    1.  Crea una colección en Postman llamada **TRUFoton - Logística**.
    2.  Configura variables de entorno (`baseUrl`, `token`).
    3.  Registra las peticiones para el CRUD de Proveedores, Choferes y Madrinas.
    4.  Usa la pestaña **Tests** de Postman para escribir aserciones básicas:
        ```javascript
        pm.test("Status code is 200", function () {
            pm.response.to.have.status(200);
        });
        ```

---

## 🧠 FASE 3: Lógica de Negocio y Reglas Operativas Complejas

Aquí es donde entra la parte más importante y particular del negocio de TRUFoton.

### Paso 1: Creación de Envíos e Integración de Unidades (Cálculo de Costo Automático)
*   **Prueba a Escribir (`tests/Feature/EnvioTest.php`):**
    *   Verificar que se puede crear un **Envío** individual de manera exitosa (nace en estado `Creado`).
    *   Verificar que al asociar una unidad de cierto segmento a dicho envío, el backend consulte la tabla de costos y calcule el `costo_x_unidad = costo_km * km_envio`.
    *   Verificar que si el motivo del envío es "Traslado Carrocero" (ID 6), el campo `carrocero` en `info_unidad_logistica` cambie automáticamente a `1`.

### Paso 2: Agrupación en Expedientes y Flujo de Aprobación (Notificación Mails)
*   **Prueba a Escribir (`tests/Feature/ExpedienteAprobacionTest.php`):**
    *   Verificar que se pueden agrupar uno o más envíos en un **Expediente** (agrupador de aprobación que inicia en `Creado`).
    *   Probar que un expediente en estado `Creado` no puede ser visto en la bandeja del aprobador.
    *   Probar que al accionar "Enviar a aprobación" en el expediente (cambia a `Enviado`), se envíe un email de notificación a los aprobadores (`Mail::fake()`).
    *   Probar que solo un usuario con el rol de Aprobador de Logística puede aprobar el expediente, lo que automáticamente cambia su estado a `Aprobado` (ID 5) y actualiza todos los envíos contenidos dentro del expediente a estado `Aprobado` (ID 3).

### Paso 3: Control Operativo y Evidencias Multimedia
*   **Prueba a Escribir (`tests/Feature/EvidenciaTest.php`):**
    *   Permitir subir múltiples fotos/videos para Salida (motivo 1) y Llegada (motivo 2).
    *   Verificar que el endpoint borre el archivo físico del disco al eliminar la evidencia.

### Paso 4: Transiciones de Subproceso y "Siguiente Área"
*   **Prueba a Escribir (`tests/Feature/FinalizarLogisticaTest.php`):**
    *   Verificar que el endpoint `POST /api/logistica/unidades/{unidad}/finalizar` avance el subproceso del VIN de acuerdo con la tabla de matriz de origen/carrocero (ej. Puerto con carrocero avanza a subproceso 38, Puerto sin carrocero avanza a subproceso 8).

---

## 🎨 FASE 4: Frontend React (Consumo de API con Buenas Prácticas)

Una vez que la API está al 100% probada y robusta, puedes iniciar el Frontend.

1.  **Axios Wrapper y Clientes API:** Encapsula las peticiones a endpoints en servicios aislados (ej. `services/proveedorService.js`).
2.  **Manejo de Estado con Hooks:** Utiliza custom hooks (como `useProveedores.js`) para separar la UI de la lógica de negocio y llamadas de red.
3.  **UI Components Limpios:** Crea formularios dinámicos que cambien y validen el formato del RFC dependiendo de la selección del dropdown "Tipo de Persona" (Física/Moral).

---

## 🤖 Cómo usar esta guía con un Agente Guía
Cuando invoques al agente para que te asista a programar, puedes usar este formato:
> *"Hola. Quiero avanzar en la **Fase 1: Paso 2 (TDD de Proveedores)**. Ayúdame a generar el caso de prueba inicial de validación de RFC en Laravel para ver cómo falla y guíame para corregir la migración y request."*
