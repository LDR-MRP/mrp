# Plan de Desarrollo Paso a Paso: Módulo de Logística (PHP + PDO)

Este plan está diseñado para guiar el desarrollo ordenado del sistema de logística paso a paso sobre el framework PHP del proyecto.

---

## 🛠️ Entorno del Proyecto
*   **Backend & Frontend:** PHP 8 + PDO/MySQL con arquitectura MVC de 4 capas (Controllers, Services, Models, Views + JS vanilla con DataTables).
*   **Base de datos:** MySQL (`proyectos/mrp`).

---

## 🗺️ FASE 1: Catálogos de Logística y Módulo de Envíos (Épica 2)

### Paso 1: Migración SQL de Catálogos y Envíos
*   **Objetivo:** Crear tablas `lgs_cat_tipo_traslado`, `lgs_cat_motivo_envio`, `lgs_cat_tipo_destino`, `lgs_cat_origenes`, `lgs_cat_destinos`, `lgs_envios`, `lgs_envios_vins` y `lgs_costos_proveedor_segmento`.
*   **Instrucciones:** Ejecutar el script DDL de la Épica 2.

### Paso 2: Backend de Envíos (`Lgs_envios`)
*   **Objetivo:** Desarrollar `Lgs_enviosModel.php`, `Lgs_enviosService.php` y `Lgs_envios.php`.
*   **Instrucciones:**
    1.  Generador de folios `EN-000001` con transacción PDO (`SELECT FOR UPDATE`).
    2.  Motor de cálculo de costos (km_total × tarifa × factor).
    3.  Asignación de VINs con posición de acomodo en la madrina.

### Paso 3: Vista y Frontend de Envíos
*   **Objetivo:** Crear `Views/Lgs_envios/index.php` y `Assets/js/modulos/functions_lgs_envios.js`.
*   **Instrucciones:**
    1.  DataTable de envíos con filtros dinámicos.
    2.  Modal de creación de envíos.
    3.  Acomodo de VINs en la madrina con drag-and-drop.
    4.  Cálculo dinámico de km por coordenadas de origen y destino.

---

## 🗺️ FASE 2: Planeaciones y Aprobaciones (Épica 3)

### Paso 1: Migración de Planeaciones
*   **Objetivo:** Crear tablas `lgs_planeaciones`, `lgs_planeaciones_envios` y `lgs_aprobadores`.

### Paso 2: Módulo de Planeaciones (`Lgs_planeaciones`)
*   **Objetivo:** Permitir al operador agrupar envíos en planeaciones (`EX-000001`) y enviarlas a aprobación.
*   **Notificaciones:** Envío de correo automático a todos los aprobadores vía `sendMailLocal()`.

### Paso 3: Panel de Aprobaciones (`Lgs_aprobaciones`)
*   **Objetivo:** Aprobador revisa planeaciones enviadas o regresadas y ejecuta la acción de Aprobar o Regresar con observaciones.

---

## 🗺️ FASE 3: Ejecución, Despacho y Evidencias (Épicas 4 y 5)

### Paso 1: Ejecución del Envío (`Lgs_ejecucion`)
*   **Objetivo:** Iniciar despacho, generar solicitudes al Área de Entregas respetando el orden de acomodo de VINs.

### Paso 2: Evidencias Multimedia (`Lgs_evidencias`)
*   **Objetivo:** Subida de fotos y videos de salida y llegada. Galería interactiva con lightbox y reproductor.

---

## 🗺️ FASE 4: Panel de Rutas Geográficas (Épica 6)

### Paso 1: Integración de Mapa (`Lgs_panelrutas`)
*   **Objetivo:** Vista interactiva con Leaflet / Google Maps API mostrando envíos en tránsito (estado 6) con origen, destino y trazado de ruta.
