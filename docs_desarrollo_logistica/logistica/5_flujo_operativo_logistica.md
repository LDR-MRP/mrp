# 🚚 Flujo Operativo del Módulo de Logística — v2
### Versión: Rediseño Definitivo · 04 Agosto 2026

> **Regla de oro:** Primero el flujo completo y validado. Luego el código.  
> **Importante:** Todo parte de una tabla genérica de envíos. Los VINs se asignan a esa tabla con reglas de acomodo de carga para evitar sobrecostos.

---

## 🗺️ Mapa General del Módulo (5 Etapas)

```
┌────────────┐   ┌────────────────────┐   ┌──────────────────┐   ┌─────────────────┐   ┌──────────────────┐
│  ETAPA 1   │ → │      ETAPA 2       │ → │     ETAPA 3      │ → │    ETAPA 4      │ → │    ETAPA 5       │
│  Planear   │   │   Aprobar Envío    │   │  Ejecutar Envío  │   │  En Tránsito    │   │  Llegada y       │
│  Envío     │   │   (Planeación)     │   │  (Despacho)      │   │  (Monitoreo)    │   │  Entrega Final   │
└────────────┘   └────────────────────┘   └──────────────────┘   └─────────────────┘   └──────────────────┘
```

---

## 📋 ETAPA 1 — Planeación del Envío (`lgs_envios`)

**Quién lo hace:** Operador de Logística  
**Módulo:** `Lgs_envios` — Tabla genérica de envíos

### ¿Qué se captura al crear un envío?

```
┌─────────────────────────────────────────────────────────────────┐
│                     CABECERA DEL ENVÍO                          │
├──────────────────────────┬──────────────────────────────────────┤
│  Folio                   │  EN-000001 (auto-generado)           │
│  Tipo de Traslado        │  Madrina / Chofer (Rodando)          │
│  Razón / Motivo          │  Catálogo: Entrega Dist / Carrocería │
│                          │  / Marketing / Pruebas / Piloto, etc.│
│  Trasladista             │  prv_cat_proveedores (trasladistas)  │
│  Asignaciones            │  Si es Madrina: Se asignan Madrinas  │
│                          │  y Choferes asociados.               │
│                          │  Si es Chofer: Solo Choferes.        │
│  Origen                  │  Catálogo: Planta 1/2/3/4/5,         │
│                          │  Almacén Montenegro, etc.            │
│                          │  + Coordenadas lat/lng del origen    │
│  Múltiples Destinos      │  Puede tener 1 o más destinos        │
│  (Paradas)               │  (ej. Carrocero -> Distribuidor)     │
│  Km Total                │  Suma de los km de cada tramo de la  │
│                          │  ruta (Origen -> Destino 1 -> Dest 2)│
│  Costo por Km            │  Del contrato con el trasladista     │
│  Costo Total Estimado    │  - Madrina: Km × Costo/km × Factor   │
│                          │  - Chofer:  Km × Costo/km            │
│  Fecha Tentativa Envío   │  Fecha planificada de salida         │
│  Fecha Tentativa Llegada │  Fecha planificada de llegada        │
│  Comentarios             │  Campo abierto de observaciones      │
│  Estado                  │  🟡 Creado                           │
└──────────────────────────┴──────────────────────────────────────┘
```

### ¿Qué VINs lleva ese envío?

```
┌─────────────────────────────────────────────────────────────────┐
│                   ASIGNACIÓN DE VINs                            │
│                                                                 │
│  El operador agrega los VINs desde el listado de unidades       │
│  disponibles para envío.                                        │
│                                                                 │
│  ⭐ REGLA DE ACOMODO (Solo para traslados en Madrina)           │
│  ────────────────────────────────────────────────────────────── │
│  El planeador de envíos define **manualmente** el orden de      │
│  bajada/subida, priorizando los destinos de las múltiples       │
│  paradas de la ruta.                                            │
│   → El primer VIN en bajar va AL ÚLTIMO en subir a la madrina  │
│   → El último VIN en bajar va AL PRIMERO (más accesible)       │
│                                                                 │
│  El sistema permite asignar cada VIN a una Madrina específica   │
│  y definir en qué Destino (parada) se va a bajar.               │
│                                                                 │
│  ⭐ REGLA PARA CHOFERES (Rodando)                               │
│  ────────────────────────────────────────────────────────────── │
│  Cada VIN se asigna directamente a un Chofer. No aplica lógica  │
│  de acomodo, solo el destino final del VIN.                     │
│                                                                 │
│  Campos por VIN asignado:                                       │
│   • VIN (número)                                                │
│   • Destino del VIN (dónde se va a entregar)                    │
│   • Madrina o Chofer asignado                                   │
│   • Posición en madrina (si aplica)                             │
│   • Costo unitario calculado (según segmento y volumen)         │
└─────────────────────────────────────────────────────────────────┘
```

### Catálogos requeridos para esta etapa

| Catálogo | Tabla | Ejemplos de valores |
|---|---|---|
| **Tipo de Traslado** | `lgs_cat_tipo_traslado` | Madrina, Chofer (Rodando) |
| **Motivo / Razón** | `lgs_cat_motivo_envio` | Entrega Dist., Carrocería, Marketing, Demo, Pruebas, Unidad Piloto, Otro |
| **Tipo de Destino** | El **operador de logística** es el único responsable de confirmar la llegada a planta, almacenes origen y llegadas a destinos (distribuidor, clientes, evento). <br> * Como hay **múltiples paradas**, el operador confirma la entrega de los VINs correspondientes a cada parada. <br> * Se suben las **Evidencias de Llegada** (foto de la unidad entregada, firma de remisión). <br> * El sistema registra la `fecha_llegada_real` para cada parada/VIN. | Específico + ubicación (lat/lng) + campo libre para nombre |

---

## ✅ ETAPA 2 — Aprobación de Envíos (`lgs_planeaciones`)

**Quién lo hace:** Operador crea la planeación → Aprobador la resuelve  
**Módulo:** `Lgs_planeaciones` y `Lgs_aprobaciones`

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2.1 — Operador agrupa envíos en una Planeación           │
│                                                                 │
│  Una Planeación (EX-000001) puede contener:                     │
│   → UN solo envío (aprobación individual)                       │
│   → MÚLTIPLES envíos (aprobación en paquete/batch)             │
│                                                                 │
│  Captura: Folio EX-, Nombre/descripción, lista de envíos,      │
│  total general de km y costo.                                   │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2.2 — Operador presiona "Enviar a Aprobación"            │
│                                                                 │
│  Estado de la Planeación → 2 (Enviada)                          │
│  Estado de los Envíos vinculados → 2 (En revisión)             │
│                                                                 │
│  El sistema envía correo automático a los aprobadores          │
│  registrados en la tabla `lgs_aprobadores`.                    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2.3 — Aprobador resuelve                                  │
│                                                                 │
│  ✅ APRUEBA → Estado Planeación = Aprobada (5)                  │
│              Estado todos los Envíos = Aprobado (3)            │
│              Correo de confirmación al operador                 │
│                                                                 │
│  ❌ REGRESA → Estado Planeación = Regresada (3)                 │
│              Operador recibe correo con observaciones          │
│              Puede corregir y re-enviar                         │
└─────────────────────────────────────────────────────────────────┘
```

### Estados de un Envío (`lgs_envios.id_estado`)

| ID | Estado | Descripción |
|:---:|---|---|
| **1** | 🟡 Creado | Envío en planeación, aún no enviado a aprobación |
| **2** | 🔵 En Revisión | Enviado a aprobación, pendiente de resolución |
| **3** | 🟢 Aprobado | Planeación aprobada, listo para ejecutar |
| **4** | 🔴 Regresado | Rechazado/observado, requiere corrección |
| **5** | ⚡ En Ejecución | Despacho iniciado, unidad en proceso de salida |
| **6** | 🚛 En Tránsito | Unidad salió, en camino al destino |
| **7** | ✅ Entregado | Unidad llegó y fue confirmada en destino |
| **8** | ⛔ Cancelado | Envío cancelado |

---

## 🚛 ETAPA 3 — Ejecución del Envío (Despacho)

**Quién lo hace:** Operador de Logística + Área de Entregas (Planta)  
**Módulo:** Módulo de Ejecución (dentro de `Lgs_envios`)

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3.1 — Operador inicia ejecución del envío                 │
│                                                                 │
│  Solo aplica para envíos en estado 3 (Aprobado)                │
│                                                                 │
│  Registra:                                                      │
│   • Fecha real de salida (puede diferir de la tentativa)       │
│   • Hora real de inicio                                         │
│                                                                 │
│  Estado Envío → 5 (En Ejecución)                               │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3.2 — Toma de Evidencias de RECEPCIÓN                     │
│             (cómo recibe el trasladista las unidades)           │
│                                                                 │
│  Evidencia Tipo: Recepción / Salida de Planta                  │
│   • Fotos del estado de cada VIN al cargar                     │
│   • Video de confirmación si aplica                             │
│   • Se almacenan en lgs_evidencias (tipo = 1: Salida)          │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3.3 — Solicitud al Área de Entregas                       │
│                                                                 │
│  Para cada VIN del envío, se crea un registro en la tabla      │
│  genérica `lgs_solicitudes_entrega` con:                        │
│   • VIN                                                         │
│   • Estado: "Solicitada" (1)                                    │
│   • Fecha y hora de solicitud                                   │
│   • Posición de acomodo en la madrina (del Paso 1)             │
│                                                                 │
│  El Área de Entregas ve estas solicitudes en su panel          │
│  y prepara las unidades en el orden de acomodo.                │
│  Cuando entregan la unidad al chofer → Estado: "Entregada a    │
│  Trasladista" (2) + Fecha y hora real de salida de planta.    │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3.4 — Unidad marcada "En Tránsito"                        │
│                                                                 │
│  Una vez que el Área de Entregas confirma la salida:           │
│   → Estado del Envío → 6 (En Tránsito)                         │
│   → Fecha real de salida queda registrada                       │
│   → Se activa el seguimiento en el Panel de Rutas (GPS/mapa)   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📡 ETAPA 4 — Monitoreo En Tránsito (Panel de Rutas)

**Quién lo ve:** Operador de Logística, Supervisores, Aprobadores  
**Módulo:** `Lgs_panelrutas`

*   Solo se monitorean los envíos en estado **6 (En Tránsito)**.
*   **Fuente de GPS:** Se utiliza el GPS de un **proveedor externo (API)**. Se manda el número de VIN a la API y esta retorna las coordenadas actuales. 
*   **Visualización:** El operador ve en el mapa cada VIN moviéndose hacia sus múltiples destinos.
*   **Visibilidad:** Todos los operadores pueden ver todos los envíos activos de cualquier área o planta.

---

## 📦 ETAPA 5 — Llegada y Entrega Final en Destino

**Quién lo hace:** Operador de Logística (confirma) / Responsable en Destino  
**Módulo:** Módulo de Llegada (dentro de `Lgs_envios`)

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 5.1 — Descarga de unidades en Destino                     │
│                                                                 │
│  Las unidades se bajan en el orden de acomodo planificado:     │
│   → El primero en subir a la madrina → último en bajar        │
│   → El último en subir → primero en bajar (más accesible)     │
│                                                                 │
│  (Futura mejora: cálculo automático de orden óptimo de         │
│  descarga según destinos si hay múltiples paradas)             │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 5.2 — Toma de Evidencias de ENTREGA                       │
│                                                                 │
│  Evidencia Tipo: Llegada / Entrega a Destino                   │
│   • Fotos del estado de cada VIN al descargar                 │
│   • Foto de firma de recibido (si aplica)                      │
│   • Video de confirmación si aplica                             │
│   • Se almacenan en lgs_evidencias (tipo = 2: Llegada)         │
└─────────────────────┬───────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────────┐
│  PASO 5.3 — Confirmar Entrega                                   │
│                                                                 │
│  El operador registra:                                          │
│   • Fecha real de llegada                                       │
│   • Hora real de entrega                                        │
│   • Nombre de quien recibe (campo libre)                        │
│                                                                 │
│  Al confirmar:                                                  │
│   → Estado del Envío → 7 (Entregado) ✅                        │
│   → Cada VIN del envío queda marcado como Entregado           │
│   → Se detiene el monitoreo del envío en el Panel de Rutas     │
│   → El envío pasa a histórico (ya no aparece en activos)       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Tablas Requeridas — Mapa Completo

### Catálogos (configurables, no programarlos fijos)

| Tabla | Descripción | Valores iniciales |
|---|---|---|
| `lgs_cat_tipo_traslado` | Tipo del traslado | Normal, Urgente, Programado, Demo, Piloto |
| `lgs_cat_motivo_envio` | Razón del movimiento | Entrega Distribuidora, Traslado Carrocería, Marketing, Demo, Pruebas, Unidad Piloto, Devolución, Otro |
| `lgs_cat_tipo_destino` | Tipo de punto destino | Distribuidor/Concesionario, Carrocero, Cliente Final, Almacén, Planta, Otro |
| `lgs_cat_origenes` | Puntos de origen con ubicación | Planta 1 (lat,lng), Planta 2, Planta 3, Planta 4, Planta 5, Almacén Montenegro (lat,lng) |
| `lgs_cat_destinos` | Clientes y destinos con ubicación | Cliente 1 (lat,lng), Cliente 2, … + campo libre para nombre y dirección |

### Tablas operativas

| Tabla | Descripción |
|---|---|
| `lgs_envios` | Cabecera del envío: folio EN-, tipo, motivo, trasladista, origen, destino, km, costo, fechas, estado |
| `lgs_envios_vins` | Detalle VINs asignados al envío: VIN, posición de acomodo en madrina, costo unitario |
| `lgs_planeaciones` | Agrupador de envíos para aprobación: folio EX-, estado, totales |
| `lgs_planeaciones_envios` | Relación N:M planeación ↔ envío |
| `lgs_aprobadores` | Usuarios que pueden aprobar planeaciones |
| `lgs_solicitudes_entrega` | Solicitudes al Área de Entregas: VIN, estado, fecha solicitud, fecha entrega real |
| `lgs_evidencias` | Fotos/videos vinculados a un envío: tipo 1=Salida, 2=Llegada |

---

## 🧩 Módulos del Sistema (Pantallas)

| Módulo | Ruta | Descripción |
|---|---|---|
| **Mis Envíos** | `/Lgs_envios` | Crear y gestionar envíos individuales. Asignar VINs con acomodo. |
| **Mis Planeaciones** | `/Lgs_planeaciones` | Agrupar envíos y enviarlos a aprobación. |
| **Aprobaciones** | `/Lgs_aprobaciones` | Panel del aprobador: aprobar o regresar planeaciones. |
| **Ejecución** | (dentro de Lgs_envios) | Iniciar despacho, tomar evidencias, notificar área de entregas. |
| **Panel de Rutas** | `/Lgs_panelrutas` | Mapa de envíos en tránsito. |
| **Histórico** | (dentro de Lgs_envios) | Envíos completados con toda su trazabilidad. |
| **Catálogos** | (admin) | Gestión de orígenes, destinos, motivos, tipos. |
| **Trasladistas** | `/Prv_proveedor` | Ya existe — Proveedores de transporte. |
| **Madrinas** | `/Prv_madrinas` | Ya existe — Vehículos nodrizas. |
| **Choferes** | `/Prv_choferes` | Ya existe — Conductores. |

---

## ✅ Decisiones Definidas por Negocio (04 Ago 2026)

- **Acomodo automático vs manual:** El planeador de envíos decide manualmente cuál VIN se sube primero, segundo, etc.
- **Múltiples paradas:** SÍ. Un envío puede tener más de un destino (ej. primero Carrocero, luego Distribuidor).
- **GPS real o estimado:** Se utiliza GPS real de un proveedor vía API externa enviando el VIN para obtener coordenadas.
- **Confirmación de llegada:** El operador de logística en planta es quien confirma llegadas a almacenes, distribuidores, clientes y eventos.
- **Costo por factor de unidades:** Es una matriz exacta: Precio por trasladista + Segmento de la unidad (Heavy, Medium, Light) + Cantidad de VINs (1, 2, 3... n). Cada combinación tiene un precio.
- **Visibilidad por rol:** Todos los operadores pueden ver todos los envíos. 
