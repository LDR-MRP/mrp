# Levantamiento de Requerimientos – Módulo de Logística
### Versión Consultor/Negocio · TRUFoton

---

## 1. Propósito y Alcance del Módulo

El Módulo de Logística tiene como objetivo gestionar de extremo a extremo el traslado físico de vehículos (unidades/VINs) desde que son liberadas por el área de Calidad hasta su entrega formal al Distribuidor. Controla:

- El registro y administración de **Proveedores de Transporte** (empresas trasladistas) con datos fiscales completos.
- El catálogo de **Choferes** (conductores) y **Madrinas** (vehículos portadores tipo nodriza/torton).
- La creación y gestión de **Envíos** individuales con asignación de unidades y planificación del traslado (madrina y chofer).
- La agrupación de envíos en **Expedientes** para el flujo de revisión y aprobación formal de costos.
- El despacho físico con carga de **evidencias fotográficas o en video** de salida y llegada.
- La **visualización geográfica** de rutas en tránsito mediante Google Maps.

---

## 2. Actores del Sistema y sus Responsabilidades

| Rol | Quién es | Qué puede hacer |
|---|---|---|
| **Operador de Logística** | Personal de logística | Ver unidades disponibles, crear envíos, asignar VINs, registrar choferes/madrinas, cargar evidencias, solicitar entregas internas, crear expedientes, enviarlos a revisión. |
| **Aprobador de Logística** | Administrador / Gerente de Finanzas | Ver expedientes pendientes de aprobación, aprobar o rechazar con comentarios, recibir notificación por correo. |
| **Supervisor LOGISTICA_1** | Perfil con permiso especial (`LOGISTICA_1`) | Ver unidades de todos los orígenes en la bandeja. |
| **Visualizador** | Distribuidor u otros roles | Consultar rastreo de rutas en panel de mapas. |

---

## 3. Reglas de Negocio

### 3.1. Condiciones para que una Unidad Aparezca en Logística

Una unidad sólo es visible y accionable en la bandeja de logística si cumple **todas** las condiciones siguientes:

1. El **proceso activo** de la unidad es Logística (proceso IDs: 6, 13 o 20, según el flujo/origen).
2. La unidad está **liberada** por Producción (`liberada = 1`).
3. La unidad está **solicitada** por el operador de logística (`solicitado = 1`).
4. El **estado del proceso en Finanzas** está completado (`id_estado_proceso_finanzas = 3`).

Unidades que cumplan con estar liberadas pero aún no fueron solicitadas muestran el botón **"Solicitar"** en la tabla.

### 3.2. Flujo de Solicitud de Entrega Interna (Solo Origen Planta)

Las unidades de **Origen Planta (2)** tienen un paso adicional previo al envío: la *Entrega Interna desde Producción a Logística* dentro de las instalaciones físicas.

- El Operador de Logística pulsa **"Solicitar Entrega"** para notificar a Producción.
- Producción actualiza el estado de entrega a `Completada`.
- Solo cuando el estado de entrega interna es `Completada`, el botón de edición/acción de la unidad en logística se habilita completamente.
- Si el operador cancela la solicitud, Producción vuelve a su estado anterior.

### 3.3. Carrocero: El Flujo "Sándwich"

Cuando una unidad llega a Logística, el operador evalúa si requiere intervención de **Carrocero** (carroza/adaptación del vehículo). Esto genera una bifurcación crítica:

- **Sin Carrocero:** La unidad pasa directo a Distribuidor al finalizar en Logística.
- **Con Carrocero:** La unidad pasa a Carrocero → Calidad inspecciona → Calidad devuelve la unidad a Logística → Logística envía a Distribuidor.

La bandera `carrocero = 1` en la tabla `info_unidad_logistica` es lo que controla este bifurcamiento. **Se activa automáticamente al asignar una unidad a un envío cuyo motivo de movimiento sea "Traslado Carrocero" (motivo ID 6).**

La tabla `sub_procesos` controla el subproceso en que está la unidad. Los valores clave son:

| Origen | 1ª Pierna (con carrocero) | 2ª Pierna (sin carrocero / post-carrocero) |
|---|---|---|
| Puerto (1) | Sub 7 → Sub 38 (Carrocero) | Sub 7 ó 40 → Sub 8 (Distribuidor) |
| Planta (2) | Sub 17 → Sub 18 (Carrocero) | Sub 17 ó 20 → Sub 21 (Distribuidor) |
| Almacén (3) | Sub 30 → Sub 31 (Carrocero) | Sub 30 ó 33 → Sub 34 (Distribuidor) |

### 3.4. El Botón "Siguiente Área" (Finalizar en Logística)

El botón para avanzar la unidad al siguiente proceso (`"Siguiente área"`) sólo se activa si:
1. La unidad tiene **fecha de salida y fecha de llegada** registradas (`tiene_fechas = true`).
2. El subproceso actual coincide con el que corresponde (según origen y presencia de carrocero).

### 3.5. Nomenclatura de Identificadores

El sistema genera nombres con formato auto-incremental:
- **Envíos**: `EN-000001`, `EN-000002`, etc.
- **Expedientes**: `EX-000001`, `EX-000002`, etc.

### 3.6. Cálculo de Costos de Envío

El costo de incluir una unidad en un envío **se calcula automáticamente** al asignarla:
- `Costo = (Costo por Km según segmento de unidad) × (Kilometraje del Envío)`
- La tabla `costos_trasladistas_tipo_unidades` relaciona el segmento de la unidad con el tipo de envío para obtener el costo por kilómetro.
- El costo total del envío se recalcula como la suma de todos los costos de las unidades asignadas.

### 3.7. Flujo de Envíos, Expedientes y Aprobaciones

El proceso operativo y de aprobación funciona de la siguiente manera:
1. **Creación de Envíos**: El operador crea de manera individual cada **Envío** (planificando su ruta, kilometraje, chofer, madrina y asignando sus unidades/VINs). El envío nace en estado `Creado`.
2. **Agrupación en Expediente**: Una vez creados los envíos individuales, el operador los agrupa en un **Expediente** (agrupador para aprobación grupal) que inicia en estado `Creado`.
3. **Envío a Aprobación**: El operador presiona "Enviar a Aprobación" desde el Expediente → El estado del Expediente cambia a `Enviado` (ID 2).
4. **Notificación**: El sistema envía un **correo electrónico automático** a todos los usuarios en la tabla `aprobadores_planeaciones` (aprobadores de expedientes) con un link directo al expediente.
5. **Resolución del Aprobador**: El Aprobador puede:
   - **Aprobar**: El estado del Expediente cambia a `Aprobado` (ID 5). Todos los envíos vinculados a ese expediente cambian automáticamente a estado `Aprobado` (ID 3). Se envía un correo de notificación al creador.
   - **Rechazar/Regresar**: El estado del Expediente cambia a `Regresado` para que el operador realice correcciones operativas.
6. **Visibilidad en Aprobaciones**: Solo los expedientes con estado `Enviado` (ID 2) o `Regresado` (ID 5) aparecen en el panel de Aprobaciones del aprobador.

### 3.8. Proveedores de Transporte (Anteriormente Trasladistas)

Todo chofer y madrina debe estar vinculado a un **Proveedor activo**. Los datos del proveedor incluyen:
- Nombre Comercial, Razón Social.
- RFC con validación según Tipo de Persona (Física o Moral).
- Dirección Fiscal.
- Contacto (correo y teléfono).

### 3.9. Evidencias Multimedia

El sistema acepta y almacena evidencias en dos momentos del traslado:
- **Evidencia de Salida** (motivo 1): Foto/video del estado de la unidad al momento de despacharla.
- **Evidencia de Llegada** (motivo 2): Foto/video del estado de la unidad al recibirla en destino.
- Formatos soportados: Imágenes (JPG, JPEG, PNG, GIF) y video (MP4, WEBM, MOV).
- Se pueden **cargar múltiples archivos** por tipo y **eliminar** individualmente.

---

## 4. Filtros Disponibles en la Bandeja de Unidades

| Filtro | Descripción |
|---|---|
| **Liberado** | Unidades con `liberada = 1` y sub-proceso 4, 14 o 27 |
| **Sin Plan** | Unidades en logística (sub 7, 17, 30) sin estado de proceso (pendientes de planificar) |
| **En mi área** | Unidades con sub-proceso 7, 17 o 30 (directamente en logística) |
| **En espera** | Unidades con `id_estado_proceso = 1` |
| **En proceso** | Unidades con `id_estado_proceso = 2` |
| **Finalizado** | Unidades con `id_estado_proceso = 3` |
| **Retroceso** | Unidades con `id_estado_unidad = 4` |
| **Activos / Inactivos** | Filtro por estatus de la unidad |
| **Por Origen** | Puerto / Planta / Almacén |
| **Más recientes / Más antiguos** | Orden por ID de unidad DESC/ASC |
| **+50 / Todos** | Paginación y límite de registros |

---

## 5. Sub-módulos de la Interfaz de Logística

| Sub-módulo | URL | Propósito |
|---|---|---|
| **Bandeja Principal** | `Logistica.php` | Ver y gestionar unidades en proceso logístico |
| **Mis Envíos** | `Logistica_Envios.php` | CRUD de envíos individuales (planificación unitaria) |
| **Mis Expedientes** | `Logistica_Expedientes.php` | Crear, gestionar y enviar expedientes de aprobación |
| **Mis Aprobaciones** | `Logistica_Aprobaciones.php` | Ver y resolver expedientes de aprobación pendientes |
| **Choferes** | `Logistica_Chofer.php` | CRUD del catálogo de choferes |
| **Madrinas** | `Logistica_Madrinas.php` | CRUD del catálogo de madrinas |
| **Panel de Rutas** | `Logistica_Panelrutas.php` | Mapa de rutas activas en tránsito |

---

## 6. Criterio de Éxito (Entregables del Módulo Migrado)

La migración se considerará exitosa cuando:

- [ ] El operador puede ver, filtrar y buscar unidades en la bandeja de logística.
- [ ] El operador puede solicitar y cancelar entregas internas (Origen Planta).
- [ ] Se pueden crear, editar y eliminar Proveedores con datos fiscales completos.
- [ ] Se pueden crear, editar choferes con carga de licencia en archivo.
- [ ] Se pueden crear, editar madrinas (nodriza) vinculadas a un proveedor.
- [ ] Se pueden crear envíos individuales, asignar unidades, configurar chofer y madrina, y calcular costos automáticamente.
- [ ] Se pueden crear expedientes agrupando envíos y mandarlos a aprobación. El flujo de Expediente → Envío a Aprobación → Aprobación/Rechazo funciona con notificaciones por correo.
- [ ] Se pueden subir y eliminar evidencias multimedia en dos etapas (salida y llegada).
- [ ] El botón "Siguiente área" avanza la unidad correctamente según origen y presencia de carrocero.
- [ ] El Panel de Rutas muestra las unidades en tránsito en un mapa interactivo.
