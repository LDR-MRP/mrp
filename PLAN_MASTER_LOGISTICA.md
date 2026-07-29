# 🚚 Plan Maestro del Proyecto de Logística (Épicas, Subtareas y Calendario Hábil)

Este documento contiene la estructura completa del proyecto de **Migración e Implementación del Módulo de Logística**, organizado en **Épicas**, **Subtareas**, **Descripciones Técnicas/Negocio** y un **Cronograma Diario por Días Hábiles (Lunes a Viernes)** iniciando hoy **Miércoles 29 de Julio de 2026**.

---

## 📅 Cronograma General de Entrega (Lunes a Viernes)

```
Mié 29 Jul  ──►  Vie 31 Jul  ──►  Lun 3 Ago  ──►  Vie 7 Ago  ──►  Lun 10 Ago ──► Vie 14 Ago
[  Épica 1  ]   [ Épicas 1 y 2 ]  [  Épica 2  ]  [  Épica 3  ]   [ Épicas 4 y 5 ] [ Épicas 5 y 6 ]
```

---

## 📊 Resumen Ejecutivo de Épicas

| # | Épica | Estado Base | Fecha Inicio | Fecha Término |
|---|---|---|---|---|
| **E1** | Catálogos Base de Transporte (Proveedores, Choferes, Madrinas e Historial) | 🟢 80% Avance (CRUDs base creados) | Miércoles 29 Jul 2026 | Jueves 30 Jul 2026 |
| **E2** | Bandeja Principal de Unidades, Entrega Interna y Regla de Carrocero ("Sándwich") | 🟡 10% Avance (Estructura `unidades` existente) | Viernes 31 Jul 2026 | Martes 04 Ago 2026 |
| **E3** | Gestión de Envíos Individuales y Cálculo Automático de Costos | 🔴 0% Avance | Miércoles 05 Ago 2026 | Jueves 06 Ago 2026 |
| **E4** | Agrupación de Expedientes, Flujo de Aprobación y Notificaciones | 🔴 0% Avance | Viernes 07 Ago 2026 | Martes 11 Ago 2026 |
| **E5** | Control de Evidencias Multimedia y Cierre de Área ("Siguiente Área") | 🔴 0% Avance | Miércoles 12 Ago 2026 | Jueves 13 Ago 2026 |
| **E6** | Panel de Rutas Geográficas e Integración de Mapas | 🔴 0% Avance | Viernes 14 Ago 2026 | Viernes 14 Ago 2026 |

---

## 📆 Calendario Diario Detallado por Subtareas

### 🗓️ SEMANA 1: 29 de Julio – 31 de Julio 2026

#### 📍 Miércoles 29 de Julio, 2026 (HOY)
- **ÉPICA 1: Catálogos Base de Transporte**
  - **ST-1.1: Reforzamiento y Validación Fiscal de Proveedores**
    - *Descripción*: Extender la validación en `Prv_trasladistasRequest` para asegurar sintaxis de RFC según el tipo de persona (12 caracteres para Moral, 13 para Física), agregando campos opcionales de contacto (correo y teléfono directo).
  - **ST-1.2: Gestión de Licencia Digital de Choferes (Subida de Archivos)**
    - *Descripción*: Implementar la carga y almacenamiento seguro de la licencia digital en `prv_det_choferes` (`licencia_chofer_file`) con su botón de previsualización/descarga en la vista de Choferes.

#### 📍 Jueves 30 de Julio, 2026
- **ÉPICA 1: Catálogos Base de Transporte**
  - **ST-1.3: Tabla e Historial Dinámico Chofer-Madrina (`madrina_chofer_historial`)**
    - *Descripción*: Crear la tabla `madrina_chofer_historial` (`id`, `id_madrina`, `id_chofer`, `fecha_inicio`, `fecha_fin`, `activo`). Desarrollar el servicio backend para inactivar la asignación previa cuando se reasigne un nuevo chofer a una nodriza.
  - **ST-1.4: Interfaz de Reasignación e Historial de Conductores en Madrinas**
    - *Descripción*: Agregar en el modal de Madrinas la reasignación de chofer activo y la línea de tiempo de conductores históricos.

#### 📍 Viernes 31 de Julio, 2026
- **ÉPICA 2: Bandeja Principal de Unidades, Entrega Interna y Regla del Carrocero**
  - **ST-2.1: Tabla Satélite `info_unidad_logistica`**
    - *Descripción*: Crear la tabla `info_unidad_logistica` (`id_info_unidad_logistica`, `id_unidad`, `id_envio`, `id_estado_proceso`, `carrocero`, `fecha_salida`, `fecha_llegada`).
  - **ST-2.2: Tabla y Flujo de Entrega Interna (`info_logistica_interna`)**
    - *Descripción*: Crear la tabla `info_logistica_interna` y endpoints `POST /logistica/solicitar-entrega` y `POST /logistica/cancelar-entrega` para coordinar unidades de Origen Planta (2).

---

### 🗓️ SEMANA 2: 03 de Agosto – 07 de Agosto 2026

#### 📍 Lunes 03 de Agosto, 2026
- **ÉPICA 2: Bandeja Principal de Unidades, Entrega Interna y Regla del Carrocero**
  - **ST-2.3: Controlador y Vista de la Bandeja Principal de Logística (`Lgs_bandeja.php`)**
    - *Descripción*: Crear `Controllers/Lgs_bandeja.php` y `Views/Logistica/index.php`. Construir la consulta SQL aplicando las 4 reglas de visibilidad operativa.
  - **ST-2.4: Implementación de Filtros Operativos en Bandeja**
    - *Descripción*: Construir los 10 filtros de la bandeja (Liberado, Sin Plan, En mi área, En espera, En proceso, Finalizado, Retroceso, Por Origen, Ordenamiento y Paginación +50).

#### 📍 Martes 04 de Agosto, 2026
- **ÉPICA 2: Bandeja Principal de Unidades, Entrega Interna y Regla del Carrocero**
  - **ST-2.5: Lógica Automática del Flujo "Sándwich" (Carrocero)**
    - *Descripción*: Programar la regla donde asignar motivo "Traslado Carrocero" (ID 6) activa `carrocero = 1` y transiciona el subproceso a la pierna intermedia (Puerto: Sub 7 → 38, Planta: Sub 17 → 18, Almacén: Sub 30 → 31).

#### 📍 Miércoles 05 de Agosto, 2026
- **ÉPICA 3: Envíos Individuales y Cálculo de Costos**
  - **ST-3.1: Estructura de Base de Datos para Envíos y Tarifas**
    - *Descripción*: Crear las tablas `envios`, `asignacion_envios_unidades`, `asignacion_envio_choferes`, `asignacion_envio_madrina` y `costos_proveedores_tipo_unidades`.
  - **ST-3.2: Generador de Folios Automáticos (`EN-000001`)**
    - *Descripción*: Desarrollar el generador de secuencias numéricas con prefijo `EN-` rellenadas a 6 dígitos con ceros a la izquierda.

#### 📍 Jueves 06 de Agosto, 2026
- **ÉPICA 3: Envíos Individuales y Cálculo de Costos**
  - **ST-3.3: Módulo y CRUD de Envíos Individuales (`Lgs_envios.php`)**
    - *Descripción*: Crear `Controllers/Lgs_envios.php` y vista para definir origen, destino, kilometraje total, chofer, madrina y fecha tentativa.
  - **ST-3.4: Asignación de VINs a Envíos y Motor de Cálculo de Costos**
    - *Descripción*: Funcionalidad para vincular VINs a envíos y calcular automáticamente `Costo = (Tarifa/km por segmento) × (Kilometraje)` actualizando el total del envío.

#### 📍 Viernes 07 de Agosto, 2026
- **ÉPICA 4: Agrupación de Expedientes, Flujo de Aprobaciones y Notificaciones**
  - **ST-4.1: Estructura de Base de Datos para Expedientes y Aprobadores**
    - *Descripción*: Crear las tablas `expedientes_aprobacion`, `asignacion_envios_expedientes` y `aprobadores_expedientes`.
  - **ST-4.2: Agrupador de Envíos en Expedientes (`Lgs_expedientes.php`)**
    - *Descripción*: Interfaz donde el operador selecciona envíos creados y los consolida en un expediente (Folio `EX-000001`), calculando la suma total de kilometraje y costos.

---

### 🗓️ SEMANA 3: 10 de Agosto – 14 de Agosto 2026

#### 📍 Lunes 10 de Agosto, 2026
- **ÉPICA 4: Agrupación de Expedientes, Flujo de Aprobaciones y Notificaciones**
  - **ST-4.3: Flujo de "Enviar a Aprobación" y Notificación por Correo**
    - *Descripción*: Al accionar la petición, cambiar el estado del expediente a `Enviado` (ID 2) y enviar correo HTML automático usando PHPMailer a los aprobadores registrados.
  - **ST-4.4: Módulo de Aprobaciones para Finanzas (`Lgs_aprobaciones.php`)**
    - *Descripción*: Crear `Controllers/Lgs_aprobaciones.php` y vista dedicada para que el aprobador consulte los expedientes en estado `Enviado` (2) o `Regresado` (3) y los pueda **Aprobar** o **Rechazar/Regresar** con observaciones.

#### 📍 Martes 11 de Agosto, 2026
- **ÉPICA 4: Agrupación de Expedientes, Flujo de Aprobaciones y Notificaciones**
  - **ST-4.5: Transición de Estados en Cascada Expediente → Envíos**
    - *Descripción*: Programar la transacción SQL que, al ser aprobado un expediente, cambie en cascada el estado de todos sus envíos vinculados a `Aprobado` (ID 3) y notifique por correo al operador creador.

#### 📍 Miércoles 12 de Agosto, 2026
- **ÉPICA 5: Control de Evidencias Multimedia y Cierre de Área**
  - **ST-5.1: Estructura de Base de Datos y Almacenamiento `evidencias_logistica`**
    - *Descripción*: Crear la tabla `evidencias_logistica` (`id_evidencia`, `id_info_unidad_logistica`, `evidencia`, `motivo_evidencia` [1=Salida, 2=Llegada]).
  - **ST-5.2: Gestor AJAX de Carga y Eliminación de Fotos/Videos**
    - *Descripción*: Desarrollar endpoints para subir múltiples archivos multimedia clasificados por motivo (Salida o Llegada) y eliminar físicamente archivos del disco cuando se desechen.

#### 📍 Jueves 13 de Agosto, 2026
- **ÉPICA 5: Control de Evidencias Multimedia y Cierre de Área**
  - **ST-5.3: Componente de Galería y Visor Multimedia en Modal**
    - *Descripción*: Construir en la vista de la bandeja un modal interactivo con pestañas "Evidencia de Salida" y "Evidencia de Llegada", con lightbox para fotos y reproductor para videos.
  - **ST-5.4: Lógica de Validación y Botón "Siguiente Área"**
    - *Descripción*: Programar el botón "Siguiente Área" en la bandeja. Validar que la unidad tenga `fecha_salida` y `fecha_llegada` registradas. Al avanzar, actualizar el subproceso del VIN hacia su destino (Distribuidor o Carrocero) y marcar el estado de proceso como `Finalizado` (3).

#### 📍 Viernes 14 de Agosto, 2026
- **ÉPICA 6: Panel de Rutas Geográficas e Integración de Mapas**
  - **ST-6.1: Integración de Motor de Mapas en la Vista (`Logistica_panelrutas.php`)**
    - *Descripción*: Crear `Controllers/Logistica_panelrutas.php` e integrar el contenedor del mapa interactivo con Leaflet/Google Maps.
  - **ST-6.2: Endpoint de Geodatos de Envíos Activos (`GET /logistica/rutas-activas`)**
    - *Descripción*: Desarrollar el servicio backend que retorna la lista de envíos en estado `En tránsito` (ID 5) con las coordenadas Latitud/Longitud de origen y destino, chofer, madrina y cantidad de VINs.
  - **ST-6.3: Renderizado Dinámico de Marcadores y Rutas en Mapa & Pruebas E2E Finales**
    - *Descripción*: Programar el script de frontend para dibujar los pines de mapa personalizados, trazar las líneas de trayectoria y realizar la validación general de integración del módulo de logística.
