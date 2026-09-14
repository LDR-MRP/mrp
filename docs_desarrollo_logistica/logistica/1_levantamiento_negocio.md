# Levantamiento de Requerimientos — Módulo de Logística
### Versión Rediseñada · 04 Agosto 2026

---

## 1. Propósito y Alcance del Módulo

El Módulo de Logística gestiona de extremo a extremo el traslado físico de vehículos (VINs) desde que son liberados por el área correspondiente hasta su entrega formal en destino. Controla:

- El registro de **Proveedores de Transporte** (Trasladistas) con datos fiscales completos.
- El catálogo de **Choferes** y **Madrinas** vinculados a un proveedor.
- La creación de **Envíos** con asignación de VINs, acomodo de carga, trasladista, origen, destino, km calculado por API y costo automático.
- La agrupación de envíos en **Planeaciones** para aprobación en batch o individual.
- El **despacho** con evidencias multimedia de salida (como recibe el trasladista) y llegada (como se entrega en destino).
- La **solicitud al Área de Entregas** con el acomodo de unidades para evitar sobrecostos por reorganización en campo.
- El **monitoreo geográfico** de rutas activas en tránsito.
- El **cierre de entrega** con confirmación, evidencias y fecha real de llegada.

---

## 2. Actores del Sistema

| Rol | Responsabilidades |
|---|---|
| **Operador de Logística** | Crear envíos, asignar VINs, definir acomodo de carga, agrupar en planeaciones, enviar a aprobación, iniciar ejecución, registrar evidencias, confirmar entrega. |
| **Área de Entregas (Planta)** | Ver solicitudes de entrega, preparar unidades en orden de acomodo, confirmar salida de planta. |
| **Aprobador de Logística** | Revisar planeaciones enviadas, aprobar o regresar con observaciones. Recibe correo automático. |
| **Supervisor (`LOGISTICA_1`)** | Visibilidad total de envíos y bandeja sin restricción por origen/planta. |

---

## 3. Reglas de Negocio

### 3.1 Condiciones para que un VIN aparezca como disponible para envío

Un VIN es elegible para ser asignado a un envío si:
1. Proceso activo = Logística (IDs: 6, 13 o 20 según flujo/origen).
2. `liberada = 1` (liberado por Producción/Calidad).
3. `solicitado = 1`.
4. `id_estado_proceso_finanzas = 3` (Finanzas completado).

### 3.2 Creación del Envío (`lgs_envios`)

Cada envío captura:
- **Folio:** `EN-000001` (auto-generado, secuencial).
- **Tipo de Traslado:** catálogo configurable (Madrina, Chofer/Rodando).
- **Motivo / Razón:** catálogo configurable (Entrega Distribuidora, Traslado Carrocería, Marketing, Demo, Pruebas, Unidad Piloto, Devolución, Otro).
- **Trasladista, Chofer, Madrina:**
  - Si es **Madrina**: Se asignan las madrinas necesarias y sus choferes.
  - Si es **Chofer (Rodando)**: Se asignan directamente los choferes.
- **Origen:** catálogo con ubicación GPS (Planta 1/2/3/4/5, Almacén Montenegro, etc.).
- **Destino:** catálogo con ubicación GPS (Cliente 1, Cliente 2…) + campo libre para nombre/dirección + Tipo de Destino (Distribuidor, Carrocero, Almacén, Otro).
- **Km Total:** calculado automáticamente por API (Google Maps / distancia geodésica) desde coordenadas de origen y destino.
- **Costo Total:** `Km × Costo/km del trasladista × Factor por número de VINs`.
- **Fechas tentativas** de envío y llegada.
- **Comentarios** del envío (campo libre).

### 3.3 Asignación de VINs y Regla de Acomodo

> **Esta es la regla operativa más importante de la Épica 2.**

**A) Para envíos tipo Madrina:**
El operador asigna cada VIN a una madrina específica y define el **orden de carga** (acomodo) siguiendo esta lógica:
- El VIN que se **descarga primero** en destino se coloca **al último** en subir (más accesible).
- El VIN que se **descarga al final** se carga **primero** (más al fondo).

Esto evita que el chofer reorganice unidades en campo, lo que genera sobrecostos y tiempo muerto.
El sistema permite ajustar el orden manualmente (drag-and-drop).

**B) Para envíos tipo Chofer (Rodando):**
Cada VIN se asigna directamente a un Chofer específico. Como van rodando (conduciendo la unidad), no aplica la regla de acomodo ni posición.

### 3.4 Flujo de Planeaciones y Aprobaciones (`lgs_planeaciones`)

1. **Creación:** El operador agrupa uno o más envíos en una Planeación (`EX-000001`).
2. **Envío a Aprobación:** El operador presiona "Enviar a Aprobación". Estado → Enviada (2). Correo automático a aprobadores.
3. **Resolución:**
   - ✅ **Aprobar** → Estado Planeación = Aprobada (5). Todos los envíos → Aprobado (3). Correo al operador.
   - ❌ **Regresar** → Estado Planeación = Regresada (3). Operador corrige y reenvía.

### 3.5 Ejecución del Envío (Despacho)

Solo disponible para envíos con estado Aprobado (3):
1. Operador registra fecha real de salida.
2. Toma evidencias de **recepción** (como recibe el trasladista los VINs): fotos/video.
3. Se crean solicitudes en `lgs_solicitudes_entrega` para el Área de Entregas, con el orden de acomodo.
4. El Área de Entregas confirma salida de cada VIN → estado "Entregado a Trasladista".
5. Al confirmarse todos los VINs → Estado Envío = En Tránsito (6). Monitoreo activo.

### 3.6 Entrega Final en Destino

1. Operador (o responsable en destino) registra fecha real de llegada.
2. Toma evidencias de **entrega** (como se entrega en destino): fotos/video.
3. Confirma entrega → Estado Envío = Entregado (7). Se detiene monitoreo.

### 3.7 Cálculo de Costos

- **Madrina:** `Costo Total = Km Total × Costo por Km (madrina) × Factor (según número de VINs en esa madrina)`
- **Chofer (Rodando):** `Costo Total = Km Total × Costo por Km (rodando)`

- El costo se recalcula automáticamente al agregar, quitar o reasignar VINs.

### 3.8 Evidencias Multimedia

| Momento | Tipo | Descripción |
|---|---|---|
| Salida de Planta | `tipo = 1` | Estado de los VINs al ser cargados en la madrina |
| Llegada a Destino | `tipo = 2` | Estado de los VINs al ser descargados en destino |

- Formatos: JPG, JPEG, PNG (imágenes) y MP4, WEBM, MOV (video).
- Carga múltiple por tipo; eliminación individual.

### 3.9 Nomenclatura de Identificadores

- **Envíos:** `EN-000001`, `EN-000002`…
- **Planeaciones:** `EX-000001`, `EX-000002`…

---

## 4. Estados del Sistema

### Estados de un Envío (`lgs_envios.id_estado`)

| ID | Estado | Descripción |
|:---:|---|---|
| 1 | 🟡 Creado | En planeación, no enviado a aprobación |
| 2 | 🔵 En Revisión | Enviado a aprobación, pendiente |
| 3 | 🟢 Aprobado | Aprobado, listo para ejecutar |
| 4 | 🔴 Regresado | Rechazado, requiere corrección |
| 5 | ⚡ En Ejecución | Despacho iniciado |
| 6 | 🚛 En Tránsito | Unidad en camino |
| 7 | ✅ Entregado | Entrega confirmada en destino |
| 8 | ⛔ Cancelado | Cancelado |

### Estados de una Planeación (`lgs_planeaciones.id_estado`)

| ID | Estado |
|:---:|---|
| 1 | Creada |
| 2 | Enviada a Aprobación |
| 3 | Regresada |
| 5 | Aprobada |

---

## 5. Módulos del Sistema (Pantallas)

| Módulo | Ruta | Función |
|---|---|---|
| **Mis Envíos** | `/Lgs_envios` | Crear envíos, asignar VINs con acomodo, calcular costos |
| **Mis Planeaciones** | `/Lgs_planeaciones` | Agrupar envíos, enviar a aprobación |
| **Aprobaciones** | `/Lgs_aprobaciones` | Revisar y resolver planeaciones pendientes |
| **Ejecución / Despacho** | (dentro de Lgs_envios) | Iniciar despacho, evidencias de salida, notificar entregas |
| **Panel de Rutas** | `/Lgs_panelrutas` | Mapa de envíos en tránsito |
| **Histórico** | (dentro de Lgs_envios) | Envíos completados con trazabilidad completa |
| **Trasladistas** | `/Prv_proveedor` | Proveedores de transporte (**ya existe**) |
| **Madrinas** | `/Prv_madrinas` | Vehículos nodrizas (**ya existe**) |
| **Choferes** | `/Prv_choferes` | Conductores (**ya existe**) |

---

## 6. Criterio de Éxito

La migración se considerará exitosa cuando:

- [ ] El operador puede crear envíos con VINs asignados, acomodo de carga, chofer, madrina, origen, destino y costos automáticos.
- [ ] El operador puede agrupar envíos en planeaciones y enviarlos a aprobación.
- [ ] El aprobador recibe correo y puede aprobar/regresar planeaciones con observaciones.
- [ ] El operador puede iniciar la ejecución del envío con evidencias de salida y solicitudes al Área de Entregas.
- [ ] El Área de Entregas confirma salida de cada VIN según el orden de acomodo.
- [ ] El Panel de Rutas muestra en mapa los envíos en tránsito con origen, destino y datos del trasladista.
- [ ] El operador puede confirmar entrega en destino con evidencias de llegada y fecha real.
- [ ] Todo el historial de un envío (estados, fechas, costos, evidencias) queda registrado y consultable.
