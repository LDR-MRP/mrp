# 🚚 Plan Maestro del Proyecto de Logística
### Versión Rediseñada y Validada · 04 Agosto 2026

Este documento contiene la estructura del proyecto de **Migración e Implementación del Módulo de Logística**, organizado en Épicas, Subtareas y Cronograma por días hábiles (Lunes–Viernes 8:00–17:00), iniciando el **Martes 04 de Agosto de 2026**.

> **Flujo de referencia:** Ver [5_flujo_operativo_logistica.md](docs_migracion_logistica/5_flujo_operativo_logistica.md) para el detalle completo de las 5 etapas.

---

## 📅 Cronograma General de Entrega

```
Mié 29 Jul ─► Jue 30 Jul │ Mar 04 Ago ─► Vie 07 Ago │ Lun 10 Ago ─► Vie 14 Ago │ Lun 17 Ago ─► Vie 21 Ago
[ Épica 1 (Completada)  ] [      Épica 2     ] [          Épica 3           ] [      Épicas 4, 5 y 6   ]
```

---

## 📊 Resumen Ejecutivo de Épicas

| # | Épica | Estado | Fecha Inicio | Fecha Término |
|---|---|:---:|---|---|
| **E1** | Catálogos Base de Transporte (Proveedores, Choferes, Madrinas e Historial) | 🟢 100% Completado | 29 Jul 2026 | 30 Jul 2026 |
| **E2** | Catálogos de Logística + Módulo de Envíos (Planeación, VINs, Acomodo, Costos, Km API) | 🟡 En Desarrollo | 04 Ago 2026 | 07 Ago 2026 |
| **E3** | Planeaciones y Aprobaciones de Envíos (Agrupador EX-, Notificación Mail, Panel) | 🔴 Pendiente | 10 Ago 2026 | 14 Ago 2026 |
| **E4** | Ejecución del Envío, Despacho y Solicitudes al Área de Entregas (Orden Acomodo) | 🔴 Pendiente | 17 Ago 2026 | 18 Ago 2026 |
| **E5** | Evidencias Multimedia (Salida/Llegada) y Confirmación de Entrega en Destino | 🔴 Pendiente | 19 Ago 2026 | 20 Ago 2026 |
| **E6** | Panel de Rutas Geográficas (Monitoreo En Tránsito con Mapa) | 🔴 Pendiente | 21 Ago 2026 | 21 Ago 2026 |


## 📆 Calendario Diario Detallado por Subtareas

### 🗓️ SEMANA 0 (Histórico): 29 – 30 de Julio 2026

#### 📍 Miércoles 29 de Julio, 2026 ✅ Completado
- **ÉPICA 1:**
  - **ST-1.1:** Validación fiscal de Proveedores de Transporte (RFC por tipo persona, actividades).
  - **ST-1.2:** CRUD de Choferes con base.

#### 📍 Jueves 30 de Julio, 2026 ✅ Completado
- **ÉPICA 1:**
  - **ST-1.3:** Tabla `prv_det_madrina_chofer_historial` + servicio de reasignación de chofer.
  - **ST-1.4:** Interfaz de historial y reasignación en modal de Madrinas.

---

### 🗓️ SEMANA 1: 04 – 07 de Agosto 2026

#### 📍 Martes 04 de Agosto, 2026 (HOY)
- **ÉPICA 2: Catálogos de Logística + Base del Módulo de Envíos**
  - **ST-2.1:** Crear catálogos configurables: `lgs_cat_tipo_traslado`, `lgs_cat_motivo_envio`, `lgs_cat_tipo_destino`, `lgs_cat_origenes`, `lgs_cat_destinos`.
  - **ST-2.2:** Crear tabla principal `lgs_envios` con folio `EN-000001`, campos de origen/destino, km, costos y estados.

#### 📍 Miércoles 05 de Agosto, 2026
- **ÉPICA 2:**
  - **ST-2.3:** Crear tablas de detalle: `lgs_envios_vins` (con campo `posicion_acomodo`), `lgs_envios_choferes`, `lgs_envios_madrinas`.
  - **ST-2.4:** Crear tabla `lgs_costos_proveedor_segmento` para tarifas y motor de cálculo automático de costos.

#### 📍 Jueves 06 de Agosto, 2026
- **ÉPICA 2:**
  - **ST-2.5:** Controller `Lgs_envios.php` + Service + Model + Vista `Views/Lgs_envios/index.php`.
  - **ST-2.6:** JS `functions_lgs_envios.js`: DataTable de envíos, modal de creación, asignación de VINs con orden de acomodo drag-and-drop.

#### 📍 Viernes 07 de Agosto, 2026
- **ÉPICA 2:**
  - **ST-2.7:** Integración API de cálculo de km (Google Maps Distance Matrix o Haversine geodésico) según coordenadas de `lgs_cat_origenes` y `lgs_cat_destinos`.
  - **ST-2.8:** Generador de folio `EN-000001` + motor de cálculo de costo total automatizado.

---

### 🗓️ SEMANA 2: 10 – 14 de Agosto 2026

#### 📍 Lunes 10 de Agosto, 2026
- **ÉPICA 3: Planeaciones y Aprobaciones**
  - **ST-3.1:** Crear tablas: `lgs_planeaciones`, `lgs_planeaciones_envios`, `lgs_aprobadores`.
  - **ST-3.2:** Controller `Lgs_planeaciones.php` + Service + Model + Vista. Generador folio `EX-000001`.

#### 📍 Martes 11 de Agosto, 2026
- **ÉPICA 3:**
  - **ST-3.3:** Funcionalidad de agrupar envíos en una planeación (selección múltiple, sumatorio de km y costos en tiempo real).
  - **ST-3.4:** Acción "Enviar a Aprobación" → cambio de estado en cascada + correo automático a aprobadores (`sendMailLocal`).

#### 📍 Miércoles 12 de Agosto, 2026
- **ÉPICA 3:**
  - **ST-3.5:** Controller `Lgs_aprobaciones.php` + Vista del panel de aprobador.
  - **ST-3.6:** Acciones Aprobar / Regresar con observaciones. Cambio de estado en cascada (Planeación → Envíos). Correo de confirmación al operador.

#### 📍 Jueves 13 de Agosto, 2026
- **ÉPICA 3:**
  - **ST-3.7:** JS `functions_lgs_planeaciones.js` + `functions_lgs_aprobaciones.js`. Flujo completo integrado y probado.

#### 📍 Viernes 14 de Agosto, 2026
- **ÉPICA 3:**
  - **ST-3.8:** Pruebas de integración del ciclo completo: Crear Envío → Planeación → Aprobación → Estado actualizado + alta de rutas en `nav_admin.php`.

---

### 🗓️ SEMANA 3: 17 – 21 de Agosto 2026

#### 📍 Lunes 17 de Agosto, 2026
- **ÉPICA 4: Ejecución del Envío (Despacho)**
  - **ST-4.1:** Crear tabla `lgs_solicitudes_entrega` con campos: VIN, estado (Solicitada/Confirmada), posición de acomodo, fechas.
  - **ST-4.2:** Funcionalidad "Iniciar Ejecución" en el envío: registra fecha real de salida, cambia estado a En Ejecución (5).

#### 📍 Martes 18 de Agosto, 2026
- **ÉPICA 4:**
  - **ST-4.3:** Panel del Área de Entregas: vista con VINs solicitados en orden de acomodo.
  - **ST-4.4:** Confirmación de salida de cada VIN → "Entregado a Trasladista" → Envío pasa a En Tránsito (6).

#### 📍 Miércoles 19 de Agosto, 2026
- **ÉPICA 4 & ÉPICA 5: Confirmación de Llegada + Evidencias Multimedia**
  - **ST-4.5:** Funcionalidad de Confirmación de Llegada: fecha real, nombre de quien recibe, observaciones. Estado → Entregado (7).
  - **ST-5.1:** Crear tabla `lgs_evidencias` (id_envio, tipo 1=Salida / 2=Llegada, archivo, created_by).

#### 📍 Jueves 20 de Agosto, 2026
- **ÉPICA 5:**
  - **ST-5.2:** Endpoints AJAX de subida y eliminación de fotos/videos. Galería con lightbox y reproductor de video en modal.
  - **ST-5.3:** Integrar evidencias en el flujo de ejecución (salida) y de llegada (destino). Validar evidencias antes de finalizar.

#### 📍 Viernes 21 de Agosto, 2026
- **ÉPICA 6: Panel de Rutas Geográficas**
  - **ST-6.1:** Controller `Lgs_panelrutas.php` + integración de Leaflet/Google Maps.
  - **ST-6.2:** Endpoint `GET /Lgs_panelrutas/getRutasActivas`: devuelve envíos en estado 6 (En Tránsito) con coordenadas de origen/destino, trasladista, chofer y # de VINs.
  - **ST-6.3:** Renderizado de pines, líneas de ruta y panel lateral de información por envío activo + Pruebas finales E2E.
