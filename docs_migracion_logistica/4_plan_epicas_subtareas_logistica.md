# 📋 Plan de Épicas y Subtareas — Módulo de Logística
### Versión Rediseñada y Validada · 04 Agosto 2026

> **Flujo de referencia:** [5_flujo_operativo_logistica.md](5_flujo_operativo_logistica.md)  
> **Inicio:** Martes 04 de Agosto 2026 · Horario: Lunes–Viernes 8:00–17:00

---

## ✅ ÉPICA 1 — Catálogos Base de Transporte
**Estado:** 🟢 100% COMPLETADO (29–30 Jul 2026)

| ST | Descripción | Estado |
|---|---|:---:|
| ST-1.1 | Validación fiscal de Proveedores (RFC por tipo persona), CRUD de Proveedores y Actividades | ✅ |
| ST-1.2 | CRUD de Choferes con licencia vinculada a proveedor | ✅ |
| ST-1.3 | Tabla `prv_det_madrina_chofer_historial` + servicio `asignarChofer` con transacción | ✅ |
| ST-1.4 | Vista de Madrinas con modal de historial de choferes y reasignación | ✅ |
| ST-1.5 | Menú de Logística en `nav_admin.php` con rutas de Madrinas, Choferes y Bandeja | ✅ |

---

## 🟡 ÉPICA 2 — Catálogos de Logística y Módulo de Envíos
**Estado:** 🟡 EN DESARROLLO (04–07 Ago 2026)  
**Objetivo:** Operador puede crear envíos con VINs, acomodo de carga, trasladista, origen, destino, km y costo calculado.

### 📍 Martes 04 de Agosto, 2026 (HOY)

#### ST-2.1 — Catálogos configurables de Logística (SQL)
- **Tipo:** SQL / Migración BD  
- Crear tablas:
  - `lgs_cat_tipo_traslado` — Normal, Urgente, Demo, Piloto, Programado
  - `lgs_cat_motivo_envio` — Entrega Dist., Carrocería, Marketing, Demo, Pruebas, Piloto, Devolución, Otro
  - `lgs_cat_tipo_destino` — Distribuidor, Carrocero, Cliente Final, Almacén, Planta, Otro
  - `lgs_cat_origenes` — Planta 1/2/3/4/5, Almacén Montenegro (con lat/lng)
  - `lgs_cat_destinos` — Clientes con lat/lng + campo nombre libre
- Insertar valores iniciales en todos los catálogos.

#### ST-2.2 — Tabla principal `lgs_envios` (SQL)
- **Tipo:** SQL / Migración BD  
- Campos clave: `folio` (EN-000001), `id_tipo_traslado`, `id_motivo`, `id_proveedor`, `id_chofer`, `id_madrina`, `id_origen`, `id_destino`, `destino_nombre_libre`, `km_total`, `costo_por_km`, `factor_unidades`, `costo_total`, `fecha_tentativa_envio`, `fecha_tentativa_llegada`, `fecha_salida_real`, `fecha_llegada_real`, `recibe_nombre`, `observaciones`, `id_estado` (8 estados), auditoría.

### 📍 Miércoles 05 de Agosto, 2026

#### ST-2.3 — Tablas de detalle del Envío (SQL)
- **Tipo:** SQL / Migración BD  
- `lgs_envios_vins`: VINs asignados con campo `posicion_acomodo` (orden de carga en madrina) y `costo_unidad`.
- `lgs_envios_choferes`, `lgs_envios_madrinas`: relaciones del envío.

#### ST-2.4 — Tabla de tarifas `lgs_costos_proveedor_segmento` (SQL)
- **Tipo:** SQL / Migración BD  
- Relaciona proveedor + segmento de unidad + rango de VINs (min/max) con `costo_por_km` y `factor`.
- Base del motor de cálculo automático de costos.

### 📍 Jueves 06 de Agosto, 2026

#### ST-2.5 — Controller + Service + Model de Envíos (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_envios.php`: vista index, getEnvios (DataTable), getEnvio(id), store (crear/editar), delete, asignarVin, quitarVin, getVinsDisponibles.
- `Services/Lgs_enviosService.php`: lógica de negocio, generador de folio `EN-` con bloqueo transaccional, motor de cálculo de costo total.
- `Models/Lgs_enviosModel.php`: queries SQL de envíos y VINs.
- `Requests/Lgs_enviosRequest.php`: validación de campos.

#### ST-2.6 — Vista y JS de Envíos (PHP + JS)
- **Tipo:** Programación  
- `Views/Lgs_envios/index.php`: DataTable de envíos + modal de creación con selects de catálogos + subpanel de VINs asignados con drag-and-drop para reordenar acomodo.
- `Assets/js/modulos/functions_lgs_envios.js`: AJAX completo + librería de drag-and-drop para posicion_acomodo.

### 📍 Viernes 07 de Agosto, 2026

#### ST-2.7 — Integración API de cálculo de km (PHP + JS)
- **Tipo:** Programación  
- Endpoint interno que recibe `id_origen` e `id_destino`, obtiene lat/lng de los catálogos y llama a la API de distancias (Google Maps Distance Matrix o Haversine geodésico como fallback).
- Retorna km y tiempo estimado. Se dispara automáticamente al seleccionar origen y destino en el formulario.

#### ST-2.8 — Pruebas y ajustes Épica 2
- **Tipo:** Programación / QA  
- Prueba del flujo completo: crear envío → asignar VINs → reordenar acomodo → ver costos calculados.
- Agregar ruta en menú de navegación: `Mis Envíos`.

---

## 🔴 ÉPICA 3 — Planeaciones y Aprobaciones de Envíos
**Estado:** 🔴 PENDIENTE (10–14 Ago 2026)  
**Objetivo:** Operador agrupa envíos, envía a aprobación en batch o individual. Aprobador resuelve con correo automático.

### 📍 Lunes 10 de Agosto, 2026

#### ST-3.1 — Tablas de Planeaciones (SQL)
- **Tipo:** SQL / Migración BD  
- `lgs_planeaciones`: folio EX-, descripción, km/costo totales, estados (1=Creada, 2=Enviada, 3=Regresada, 5=Aprobada), observaciones operador/aprobador.
- `lgs_planeaciones_envios`: relación N:M.
- `lgs_aprobadores`: usuarios aprobadores con activo/inactivo.

#### ST-3.2 — Controller + Service + Model de Planeaciones (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_planeaciones.php`: vista index, getPlaneaciones, store, enviarAprobacion, getEnviosDisponibles.
- `Services/Lgs_planeacionesService.php`: generador folio EX-, sumatorio de km y costos, envío de correo a aprobadores vía PHPMailer.
- `Models/Lgs_planeacionesModel.php`.

### 📍 Martes 11 de Agosto, 2026

#### ST-3.3 — Vista y JS de Planeaciones (PHP + JS)
- **Tipo:** Programación  
- `Views/Lgs_planeaciones/index.php`: DataTable + modal de creación con selección múltiple de envíos + sumatorio en tiempo real de km y costo.
- `Assets/js/modulos/functions_lgs_planeaciones.js`.

#### ST-3.4 — Acción "Enviar a Aprobación" + Correo automático
- **Tipo:** Programación  
- Cambio de estado en cascada: Planeación → Enviada + todos sus Envíos → En Revisión.
- Correo vía PHPMailer a todos los registros en `lgs_aprobadores` con link directo al expediente.

### 📍 Miércoles 12 de Agosto, 2026

#### ST-3.5 — Controller + Vista de Aprobaciones (PHP)
- **Tipo:** Programación  
- `Controllers/Lgs_aprobaciones.php`: vista index, getPlaneacionesPendientes, aprobar(id), regresar(id).
- `Views/Lgs_aprobaciones/index.php`: DataTable con planeaciones en estado Enviada o Regresada + modal de resolución con campo de observaciones.

#### ST-3.6 — Lógica de Aprobar / Regresar
- **Tipo:** Programación  
- **Aprobar:** Estado Planeación = 5, Estado todos los Envíos = 3 (Aprobado). Correo de confirmación al operador creador.
- **Regresar:** Estado Planeación = 3, Estado Envíos = 4 (Regresado). Correo con observaciones del aprobador.

### 📍 Jueves 13 de Agosto, 2026

#### ST-3.7 — JS de Aprobaciones + Pruebas integración (JS + QA)
- **Tipo:** Programación / QA  
- `Assets/js/modulos/functions_lgs_aprobaciones.js`.
- Prueba del ciclo completo: Crear Envío → Planeación → Enviar → Aprobar/Regresar → Verificar estados y correos.

### 📍 Viernes 14 de Agosto, 2026

#### ST-3.8 — Ajustes y rutas de menú Épica 3
- **Tipo:** Programación / QA  
- Agregar `Mis Planeaciones` y `Aprobaciones` al menú de Logística en `nav_admin.php`.
- Revisión de flujo completo Épica 2 + Épica 3.

---

## 🔴 ÉPICA 4 — Ejecución del Envío y Confirmación de Entrega
**Estado:** 🔴 PENDIENTE (17–18 Ago 2026)  
**Objetivo:** Operador inicia despacho con evidencias, Área de Entregas confirma salida, operador confirma llegada.

### 📍 Lunes 17 de Agosto, 2026

#### ST-4.1 — Tabla `lgs_solicitudes_entrega` (SQL)
- **Tipo:** SQL / Migración BD  
- Campos: `id_envio`, `id_unidad` (VIN), `posicion_acomodo`, `id_estado` (1=Solicitada, 2=Entregada a Trasladista, 3=Cancelada), `solicitado_by`, `confirmado_by`, fechas.

#### ST-4.2 — Funcionalidad "Iniciar Ejecución" del Envío (PHP + JS)
- **Tipo:** Programación  
- Solo para envíos en estado Aprobado (3).
- Registra `fecha_salida_real`, crea registros en `lgs_solicitudes_entrega` con `posicion_acomodo` de los VINs.
- Estado Envío → 5 (En Ejecución).

### 📍 Martes 18 de Agosto, 2026

#### ST-4.3 — Panel del Área de Entregas (PHP + JS)
- **Tipo:** Programación  
- Vista con VINs solicitados ordenados por `posicion_acomodo` (el primero en bajar de la madrina al final, el último primero).
- Botón "Confirmar Entrega de Unidad" por VIN.

#### ST-4.4 — Confirmación de salida → Envío En Tránsito
- **Tipo:** Programación  
- Cuando todos los VINs de un envío estén en estado 2 (Entregada a Trasladista) → Envío → 6 (En Tránsito). Inicia monitoreo en Panel de Rutas.

---

## 🔴 ÉPICA 5 — Evidencias Multimedia
**Estado:** 🔴 PENDIENTE (19–20 Ago 2026)  
**Objetivo:** Subida de fotos/video en dos momentos del traslado. Galería con lightbox y reproductor.

### 📍 Miércoles 19 de Agosto, 2026

#### ST-4.5 — Confirmación de Llegada y Entrega Final
- Formulario: `fecha_llegada_real`, `recibe_nombre`, `observaciones`.
- Estado Envío → 7 (Entregado). Se detiene monitoreo.

#### ST-5.1 — Tabla `lgs_evidencias` + Endpoint AJAX (SQL + PHP)
- Crear tabla `lgs_evidencias` (id_envio, tipo 1=Salida 2=Llegada, nombre_archivo, tipo_archivo, created_by).
- `Controllers/Lgs_evidencias.php`: upload (múltiple), delete.
- `Services/Lgs_evidenciasService.php`: almacenamiento en `Assets/uploads/logistica/`.

### 📍 Jueves 20 de Agosto, 2026

#### ST-5.2 — Componente de Galería + Lightbox + Reproductor
- Modal con pestañas: "Evidencia de Salida" / "Evidencia de Llegada".
- Lightbox para imágenes, reproductor HTML5 para videos.

#### ST-5.3 — Integración de Evidencias en el Flujo
- Las evidencias de Salida se capturan al iniciar ejecución (ST-4.2).
- Las evidencias de Llegada se capturan antes de confirmar entrega final (ST-4.5).

---

## 🔴 ÉPICA 6 — Panel de Rutas Geográficas
**Estado:** 🔴 PENDIENTE (21 Ago 2026)  
**Objetivo:** Mapa interactivo con todos los envíos en tránsito.

### 📍 Viernes 21 de Agosto, 2026

#### ST-6.1 — Integración de Motor de Mapas
- `Controllers/Lgs_panelrutas.php`.
- `Views/Lgs_panelrutas/index.php` con contenedor del mapa (Leaflet.js o Google Maps API).

#### ST-6.2 — Endpoint de Geodatos de Envíos En Tránsito
- `GET /Lgs_panelrutas/getRutasActivas`: devuelve envíos en estado 6 con `lat`/`lng` de origen y destino, folio, trasladista, chofer, madrina, # VINs.

#### ST-6.3 — Renderizado Dinámico y Pruebas E2E Finales
- `Assets/js/modulos/functions_lgs_panelrutas.js`: pines personalizados por envío, línea de ruta, panel lateral con datos.
- Pruebas E2E del módulo completo de Logística.
