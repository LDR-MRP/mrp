# 📋 Plan de Épicas y Subtareas — Módulo de Logística
### Versión Extendida con Histórico Completo · Agosto 2026

> **Flujo de referencia:** [5_flujo_operativo_logistica.md](5_flujo_operativo_logistica.md)  
> **Calendario de Ejecución:** Lunes a Viernes · 8:00 a 17:00

---

## 📊 Resumen General de Épicas

| Épica | Descripción | Fechas de Desarrollo | Estado |
|---|---|:---:|:---:|
| **ÉPICA 1** | Catálogos Base de Transporte (Proveedores, Choferes, Madrinas) | 29–30 Jul 2026 | 🟢 100% COMPLETADO |
| **ÉPICA 2** | Catálogos de Logística, Envíos, Acomodo Drag-and-Drop y Tarifas | 04–07 Ago 2026 | 🟢 100% COMPLETADO |
| **ÉPICA 3** | Planeaciones y Mesa de Aprobaciones con Notificaciones | 10–14 Ago 2026 | 🟢 100% COMPLETADO |
| **ÉPICA 4** | Despacho, Segregación Multi-Sede (`plantaid`) y Programación de Recolección | 17–18 Ago 2026 | 🟢 100% COMPLETADO |
| **ÉPICA 5** | Portal Móvil de Trasladistas e Inspección con Evidencias Fotográficas | 19–20 Ago 2026 | 🟢 100% COMPLETADO |
| **ÉPICA 6** | Monitoreo GPS de Rutas y Confirmación de Entrega en Destino con QR | 21–22 Ago 2026 | 🟢 100% COMPLETADO |

---

## 🟢 ÉPICA 1 — Catálogos Base de Transporte
**Estado:** 🟢 100% COMPLETADO (29–30 Jul 2026)

### 📍 Miércoles 29 de Julio, 2026
#### ST-1.1 — Validación fiscal de Proveedores y Actividades
- **Tipo:** SQL / Backend
- Validación estricta de RFC (Física 13, Moral 12) y vinculación a catálogo de actividades (`prv_cat_actividades` y `prv_rel_proveedores_actividades`).
- **Estado:** ✅ Completado

#### ST-1.2 — CRUD de Choferes y Licencias
- **Tipo:** Programación
- Registro de choferes con vigencia de licencias y vinculación al proveedor trasladista.
- **Estado:** ✅ Completado

### 📍 Jueves 30 de Julio, 2026
#### ST-1.3 — Asignación Chofer-Madrina con Historial Transaccional
- **Tipo:** SQL / Backend
- Creación de tabla `prv_det_madrina_chofer_historial` y lógica transaccional de un único chofer activo.
- **Estado:** ✅ Completado

#### ST-1.4 — Vista de Madrinas y Menú de Transporte
- **Tipo:** Frontend
- Modal de historial de asignaciones y configuración de permisos en `nav_admin.php`.
- **Estado:** ✅ Completado

---

## 🟢 ÉPICA 2 — Catálogos de Logística y Módulo de Envíos
**Estado:** 🟢 100% COMPLETADO (04–07 Ago 2026)  
**Objetivo:** Operador puede crear envíos con VINs, acomodo de carga, trasladista, origen, destino, km y costo calculado.

### 📍 Martes 04 de Agosto, 2026
#### ST-2.1 — Catálogos configurables de Logística (SQL)
- **Tipo:** SQL / Migración BD  
- Creación de tablas: `lgs_cat_tipo_traslado`, `lgs_cat_motivo_envio`, `lgs_cat_tipo_destino`, `lgs_cat_origenes`, `lgs_cat_destinos`.
- Inserción de valores iniciales en catálogos.
- **Estado:** ✅ Completado

#### ST-2.2 — Tabla principal `lgs_envios` (SQL)
- **Tipo:** SQL / Migración BD  
- Creación de `lgs_envios` con folios transaccionales `EN-000001`, estados de envío y campos de costos/distancia.
- **Estado:** ✅ Completado

### 📍 Miércoles 05 de Agosto, 2026
#### ST-2.3 — Tablas de detalle del Envío y VINs (SQL)
- **Tipo:** SQL / Migración BD  
- `lgs_envios_vins`: VINs asignados con campo `posicion_acomodo` (orden de carga en madrina) y `costo_unidad`.
- **Estado:** ✅ Completado

#### ST-2.4 — Tabla de tarifas y segmentación vehicular (SQL)
- **Tipo:** SQL / Migración BD  
- Matriz de tarifas normalizada (`lgs_costos_rutas`, `lgs_cat_segmentos`) por volumen de unidades y segmento vehicular.
- **Estado:** ✅ Completado

### 📍 Jueves 06 de Agosto, 2026
#### ST-2.5 — Controller + Service + Model de Envíos (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_envios.php`, `Services/Lgs_enviosService.php`, `Models/Lgs_enviosModel.php`.
- Motor de cálculo de costo total por segmento y kilometraje.
- **Estado:** ✅ Completado

#### ST-2.6 — Vista y JS de Envíos con Drag-and-Drop (PHP + JS)
- **Tipo:** Programación  
- `Views/Lgs_envios/index.php` y `detalle.php` con librería **SortableJS**.
- Indicadores visuales de secuencia de carga: insignias `1º EN CARGAR`, `2º EN CARGAR`, etc.
- **Estado:** ✅ Completado

### 📍 Viernes 07 de Agosto, 2026
#### ST-2.7 — Integración API de cálculo de km y distancias (PHP + JS)
- **Tipo:** Programación  
- Integración de Google Maps Distance Matrix API y fórmula Haversine como respaldo.
- **Estado:** ✅ Completado

#### ST-2.8 — Pruebas integradas de acomodo y asignación
- **Tipo:** QA / Pruebas  
- Validación del flujo completo: Creación de envío ➔ Asignación de VINs ➔ Acomodo Drag & Drop ➔ Costeo automático.
- **Estado:** ✅ Completado

---

## 🟢 ÉPICA 3 — Planeaciones y Aprobaciones de Envíos
**Estado:** 🟢 100% COMPLETADO (10–14 Ago 2026)  
**Objetivo:** Operador agrupa envíos en planeaciones (`EX-000001`), envía a aprobación ejecutiva y aprobador autoriza o rechaza.

### 📍 Lunes 10 de Agosto, 2026
#### ST-3.1 — Tablas de Planeaciones (SQL)
- **Tipo:** SQL / Migración BD  
- Creación de `lgs_planeaciones`, `lgs_planeaciones_envios` y `lgs_aprobadores`.
- **Estado:** ✅ Completado

#### ST-3.2 — Controller + Service + Model de Planeaciones (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_planeaciones.php`, `Services/Lgs_planeacionesService.php`, `Models/Lgs_planeacionesModel.php`.
- Generador de folio `EX-` y sumatorio transaccional de costos.
- **Estado:** ✅ Completado

### 📍 Martes 11 de Agosto, 2026
#### ST-3.3 — Vista y JS de Planeaciones (PHP + JS)
- **Tipo:** Programación  
- `Views/Lgs_planeaciones/index.php` y `functions_lgs_planeaciones.js`.
- Selección múltiple de envíos con cálculo en tiempo real de costos y km acumulados.
- **Estado:** ✅ Completado

#### ST-3.4 — Acción "Enviar a Aprobación" + Notificación
- **Tipo:** Programación  
- Bloqueo transaccional de envíos y pase a estado `En Revisión` (2).
- **Estado:** ✅ Completado

### 📍 Miércoles 12 de Agosto, 2026
#### ST-3.5 — Controller + Vista de Aprobaciones (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_aprobaciones.php` y `Views/Lgs_aprobaciones/index.php`.
- Tarjetas de KPIs ejecutivos con monto solicitado y autorizado.
- **Estado:** ✅ Completado

#### ST-3.6 — Lógica de Dictamen Ejecutivo (Aprobar / Regresar)
- **Tipo:** Programación  
- **Aprobar:** Plan ➔ Aprobado, Envíos ➔ Estado 3 (Aprobado).
- **Rechazar:** Plan ➔ Rechazado, Envíos ➔ Estado 4 (Regresado) con registro de observaciones.
- **Estado:** ✅ Completado

### 📍 Jueves 13 de Agosto, 2026
#### ST-3.7 — JS de Aprobaciones y Validación AJAX
- **Tipo:** Programación / QA  
- `Assets/js/modulos/functions_lgs_aprobaciones.js` con confirmaciones SweetAlert2.
- **Estado:** ✅ Completado

### 📍 Viernes 14 de Agosto, 2026
#### ST-3.8 — Permisos RBAC y Rutas de Menú
- **Tipo:** Configuración  
- Configuración de roles y accesos directos en el menú de navegación.
- **Estado:** ✅ Completado

---

## 🟢 ÉPICA 4 — Despacho, Segregación Multi-Sede y Programación de Recolección
**Estado:** 🟢 100% COMPLETADO (17–18 Ago 2026)  
**Objetivo:** Gestión de salidas por sede (`plantaid`), confirmación de fecha de recolección y control de patio de origen.

### 📍 Lunes 17 de Agosto, 2026
#### ST-4.1 — Tabla `lgs_solicitudes_entrega` y Estados Físicos de Unidades (SQL)
- **Tipo:** SQL / Migración BD  
- `lgs_solicitudes_entrega` y columna `estado_unidad_fisico` (`EN_PATIO`, `EN_ENTREGAS`, `EN_RUTA`, `ENTREGADO`).
- **Estado:** ✅ Completado

#### ST-4.2 — Segregación por Sede (`plantaid`) en Backend
- **Tipo:** Programación  
- Filtrado automático del DataTable de la mesa de despacho (`getEnviosDespacho`) y rutas GPS de acuerdo a la sede del usuario (`$_SESSION['userData']['plantaid']`), reservando acceso global a Super Admin.
- **Estado:** ✅ Completado

### 📍 Martes 18 de Agosto, 2026
#### ST-4.3 — Programación de Fecha de Recolección (Mesa de Despacho)
- **Tipo:** Programación  
- Endpoint `confirmarRecoleccion` y modal en `Views/Lgs_ejecucion/index.php`.
- Al confirmar fecha pactada, el envío pasa a estado `5 (Confirmado Recolección)` y las unidades se mueven a `EN_ENTREGAS` en el patio de origen.
- **Estado:** ✅ Completado

#### ST-4.4 — Validación Individual de Carga en Patio
- **Tipo:** Programación  
- Planilla de acomodo con confirmación individual de VINs entregados a la madrina.
- **Estado:** ✅ Completado

---

## 🟢 ÉPICA 5 — Portal Móvil de Trasladistas e Inspección con Evidencias
**Estado:** 🟢 100% COMPLETADO (19–20 Ago 2026)  
**Objetivo:** Interfaz táctil para que el chofer escanee VINs, capture fotos obligatorias de 4 ángulos + odómetro y confirme recepción.

### 📍 Miércoles 19 de Agosto, 2026
#### ST-5.1 — Tablas de Checklists y Evidencias Multimedia (SQL)
- **Tipo:** SQL / Migración BD  
- Creación de `lgs_trasladistas_checklist` y `lgs_checklist_evidencias`.
- Script consolidado en `Scripts/update_lgs_flow_recoleccion_evidencias.sql`.
- **Estado:** ✅ Completado

#### ST-5.2 — Vista Móvil Responsiva para Trasladistas
- **Tipo:** Programación  
- Creación de `Views/Lgs_ejecucion/chofer_movil.php` y `Assets/js/modulos/functions_lgs_chofer.js`.
- Consulta de viajes activos asignados al chofer autenticado (`/Lgs_ejecucion/getEnviosChofer`).
- **Estado:** ✅ Completado

### 📍 Jueves 20 de Agosto, 2026
#### ST-5.3 — Escáner de Código de Barras / QR de VINs por Cámara
- **Tipo:** Programación  
- Integración de `Html5Qrcode` para lectura en tiempo real del VIN desde la cámara del dispositivo móvil.
- **Estado:** ✅ Completado

#### ST-5.4 — Carga Obligatoria de 5 Fotografías de Inspección
- **Tipo:** Programación  
- Captura de fotos (Frente, Atrás, Lateral Izquierdo, Lateral Derecho, Odómetro) con almacenamiento seguro en `Assets/images/uploads/evidencias/`.
- **Estado:** ✅ Completado

---

## 🟢 ÉPICA 6 — Monitoreo GPS en Rutas y Confirmación de Entrega en Destino con QR
**Estado:** 🟢 100% COMPLETADO (21–22 Ago 2026)  
**Objetivo:** Monitoreo activo de unidades en tránsito, lectura de QR de cliente en destino y entrega final.

### 📍 Viernes 21 de Agosto, 2026
#### ST-6.1 — Doble Verificación de Salida y Monitoreo de Rutas GPS
- **Tipo:** Programación  
- Doble firma: Logística valida salida y Trasladista confirma entrada. Envío pasa a `6 = En Tránsito` y unidades a `EN_RUTA`.
- Activación en el mapa interactivo de `Controllers/Lgs_panelrutas.php`.
- **Estado:** ✅ Completado

#### ST-6.2 — Interfaz de Confirmación de Entrega con QR
- **Tipo:** Programación  
- Creación de `Views/Lgs_ejecucion/entrega_destino.php` y `Assets/js/modulos/functions_lgs_entrega.js`.
- **Estado:** ✅ Completado

### 📍 Sábado 22 de Agosto, 2026
#### ST-6.3 — Lectura de QR del Destinatario y Remisión Firmada
- **Tipo:** Programación / QA  
- Escaneo de QR del cliente/concesionario para validación de identidad y ubicación de descarga.
- Carga de remisión firmada y firma digital en pantalla táctil.
- Transición del envío a estado `7 = Entregado` y conclusión del ciclo.
- **Estado:** ✅ Completado
