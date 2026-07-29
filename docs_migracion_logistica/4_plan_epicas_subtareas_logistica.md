# 🚚 Plan Maestro del Proyecto de Logística (Épicas, Subtareas y Alcance)

Este documento contiene la estructura completa del proyecto de **Migración e Implementación del Módulo de Logística**, organizado en **Épicas**, **Subtareas** y **Descripciones Técnicas/Negocio**, indicando qué componentes ya existen en el repositorio `mrp`, de dónde se pueden reutilizar y qué se desarrollará totalmente nuevo.

---

## 📊 Resumen Ejecutivo de Épicas

| # | Épica | Estado Base | Nuevos Desarrollos Clave |
|---|---|---|---|
| **E1** | Catálogos Base de Transporte (Proveedores, Choferes, Madrinas e Historial) | 🟢 80% Avance (CRUDs base creados) | Historial dinámico de choferes por nodriza y subida de archivos de licencia. |
| **E2** | Bandeja Principal de Unidades, Entrega Interna y Regla de Carrocero ("Sándwich") | 🟡 10% Avance (Estructura `unidades` existente) | Tablas `info_unidad_logistica`, `info_logistica_interna`, lógica de bifurcación y filtros. |
| **E3** | Gestión de Envíos Individuales y Cálculo Automático de Costos | 🔴 0% Avance | Nomenclatura `EN-XXXXXX`, asignación de VINs, cálculo `km × tarifa` por segmento. |
| **E4** | Agrupación de Expedientes, Flujo de Aprobación y Notificaciones | 🔴 0% Avance | Nomenclatura `EX-XXXXXX`, Workflow de aprobación grupal, emails automáticos. |
| **E5** | Control de Evidencias Multimedia y Cierre de Área ("Siguiente Área") | 🔴 0% Avance | Carga/borrado de fotos y videos (Salida/Llegada) y avance de subproceso. |
| **E6** | Panel de Rutas Geográficas e Integración de Mapas | 🔴 0% Avance | Integración de Google Maps / Leaflet para visualización en tiempo real. |

---

## 🎯 Detalle Completo de Épicas y Subtareas

### 📦 ÉPICA 1: Catálogos Base de Transporte (Proveedores, Choferes, Madrinas e Historial)

- **De dónde se saca / Lo que ya tenemos:**
  - Tabla existente: `prv_cat_proveedores` (usada para la entidad de proveedores/trasladistas).
  - Tablas recién creadas: `prv_det_madrinas` y `prv_det_choferes`.
  - Código ya construido en la rama `feature/crud-trasladistas-madrinas-choferes`:
    - `Controllers/Prv_trasladistas.php`, `Prv_madrinas.php`, `Prv_choferes.php`.
    - `Models/Prv_trasladistasModel.php`, `Prv_madrinasModel.php`, `Prv_choferesModel.php`.
    - `Services/Prv_trasladistasService.php`, `Prv_madrinasService.php`, `Prv_choferesService.php`.
    - `Requests/Prv_trasladistasRequest.php`, `Prv_madrinasRequest.php`, `Prv_choferesRequest.php`.
    - Vistas y JS interactivo DataTables en `Views/` y `Assets/js/modulos/`.

- **Lo que vamos a hacer nuevo:**
  - Creación de la tabla `madrina_chofer_historial` para auditar la relación dinámica de qué chofer conduce qué nodriza en cada periodo.
  - Endpoint y componente UI para subir y almacenar el archivo digital de la licencia de conducir del chofer en formato PDF/imagen.

---

#### 🔹 Subtareas de la Épica 1:

1. **ST-1.1: Reforzamiento y Validación Fiscal de Proveedores**
   - **Descripción**: Extender la validación en `Prv_trasladistasRequest` para asegurar que el RFC cumpla la sintaxis estricta según el tipo de persona (12 caracteres para Persona Moral, 13 para Persona Física), agregando campos opcionales de contacto (correo y teléfono directo de operaciones).
2. **ST-1.2: Gestión de Licencia Digital de Choferes (Subida de Archivos)**
   - **Descripción**: Implementar la carga y almacenamiento seguro de la licencia digital en `prv_det_choferes` (`licencia_chofer_file`). Incluye la vista de previsualización/descarga de la licencia en la interfaz de Choferes.
3. **ST-1.3: Tabla e Historial Dinámico Chofer-Madrina (`madrina_chofer_historial`)**
   - **Descripción**: Crear la tabla `madrina_chofer_historial` (`id`, `id_madrina`, `id_chofer`, `fecha_inicio`, `fecha_fin`, `activo`). Desarrollar el servicio backend para inactivar la asignación previa cuando se asigna un nuevo chofer a una madrina.
4. **ST-1.4: Interfaz de Reasignación e Historial de Conductores en Madrinas**
   - **Descripción**: Agregar en el modal de edición de Madrinas la opción para cambiar el chofer activo y consultar la línea de tiempo histórica de quiénes han operado dicha unidad nodriza.

---

### 🏬 ÉPICA 2: Bandeja Principal de Unidades, Entrega Interna y Regla del Carrocero ("Sándwich")

- **De dónde se saca / Lo que ya tenemos:**
  - Tabla global de unidades existente en la base de datos: `unidades` / `inv_captura_vin`.
  - Módulos de consulta de catálogo SAT/Plantas y arquitectura de vistas base en `Views/Template/nav_admin.php`.

- **Lo que vamos a hacer nuevo:**
  - Tablas `info_unidad_logistica` y `info_logistica_interna`.
  - Lógica de evaluación de reglas de negocio para determinar si una unidad es visible en Logística (Proceso IDs 6, 13 o 20, `liberada = 1`, `solicitado = 1`, `id_estado_proceso_finanzas = 3`).
  - Lógica de "Entrega Interna desde Producción" para unidades de Origen Planta (2).
  - Lógica de bifurcación "Sándwich" por Carrocero (`carrocero = 1`).

---

#### 🔹 Subtareas de la Épica 2:

1. **ST-2.1: Tabla Satélite `info_unidad_logistica`**
   - **Descripción**: Crear la tabla `info_unidad_logistica` (`id_info_unidad_logistica`, `id_unidad`, `id_envio`, `id_estado_proceso`, `carrocero`, `fecha_salida`, `fecha_llegada`).
2. **ST-2.2: Tabla y Flujo de Entrega Interna (`info_logistica_interna`)**
   - **Descripción**: Crear la tabla `info_logistica_interna` para controlar las entregas de unidades producidas en Planta (Origen 2). Implementar endpoints `POST /logistica/solicitar-entrega` y `POST /logistica/cancelar-entrega` para coordinar con el área de Producción.
3. **ST-2.3: Controlador y Vista de la Bandeja Principal de Logística (`Logistica.php`)**
   - **Descripción**: Crear `Controllers/Logistica.php` y `Views/Logistica/index.php`. Construir la consultaSQL con `INNER JOIN` a `unidades`, `sub_procesos` e `info_logistica_interna` aplicando las 4 reglas de visibilidad operativa.
4. **ST-2.4: Implementación de Filtros Operativos en Bandeja**
   - **Descripción**: Desarrollar los 10 filtros de la bandeja (Liberado, Sin Plan, En mi área, En espera, En proceso, Finalizado, Retroceso, Por Origen, Ordenamiento y Paginación +50).
5. **ST-2.5: Lógica Automática del Flujo "Sándwich" (Carrocero)**
   - **Descripción**: Programar la regla de bifurcación: si la unidad se asigna a un envío con motivo "Traslado Carrocero" (ID 6), activar la bandera `carrocero = 1` y transicionar el subproceso a la pierna intermedia (ej. Sub 7 → Sub 38 en Puerto, Sub 17 → Sub 18 en Planta, Sub 30 → Sub 31 en Almacén).

---

### 🚛 ÉPICA 3: Gestión de Envíos Individuales y Cálculo Automático de Costos

- **De dónde se saca / Lo que ya tenemos:**
  - Tablas maestras de catálogos: `distribuidores`, `origenes_envios`, `tipo_motivos_movimientos`, `tipo_envios`.
  - Módulo base de Proveedores (`prv_cat_proveedores`), Choferes (`prv_det_choferes`) y Madrinas (`prv_det_madrinas`).

- **Lo que vamos a hacer nuevo:**
  - Tablas: `envios`, `asignacion_envios_unidades`, `asignacion_envio_choferes`, `asignacion_envio_madrina`, `costos_proveedores_tipo_unidades`.
  - Generador de folio autoincremental `EN-XXXXXX`.
  - Motor de cálculo de costo por unidad asignada: `Costo = (Costo/km por segmento) × (km del envío)`.

---

#### 🔹 Subtareas de la Épica 3:

1. **ST-3.1: Estructura de Base de Datos para Envíos y Tarifas**
   - **Descripción**: Crear las tablas `envios`, `asignacion_envios_unidades`, `asignacion_envio_choferes`, `asignacion_envio_madrina` y `costos_proveedores_tipo_unidades`.
2. **ST-3.2: Generador de Folios Automáticos (`EN-000001`)**
   - **Descripción**: Implementar en la capa de Servicio un generador de nomenclatura secuencia `EN-` con formateo a 6 dígitos rellenados con ceros a la izquierda.
3. **ST-3.3: Módulo y CRUD de Envíos Individuales (`Logistica_envios.php`)**
   - **Descripción**: Crear `Controllers/Logistica_envios.php` y `Views/Logistica_envios/index.php`. Permitir definir origen, destino, kilometraje total, chofer asignado, madrina asignada y fecha tentativa.
4. **ST-3.4: Asignación de VINs a Envíos y Motor de Cálculo de Costos**
   - **Descripción**: Implementar la funcionalidad para vincular múltiples VINs a un envío. El backend debe consultar la tarifa de la tabla `costos_proveedores_tipo_unidades` según el segmento del vehículo y multiplicar por `kilometraje_total` para guardar `costo_x_unidad` y actualizar el `costo_total` del envío.

---

### 📑 ÉPICA 4: Agrupación de Expedientes, Flujo de Aprobaciones y Notificaciones

- **De dónde se saca / Lo que ya tenemos:**
  - Servicio de correo SMTP (`Helpers/Helpers.php` -> `sendMailLocal()`).
  - Roles de usuario (`rol`) y tabla de usuarios (`usuarios`).

- **Lo que vamos a hacer nuevo:**
  - Tablas: `expedientes_aprobacion`, `asignacion_envios_expedientes`, `aprobadores_expedientes`.
  - Generador de folio autoincremental `EX-XXXXXX`.
  - Interfaz de "Mis Expedientes" para el Operador de Logística.
  - Interfaz de "Mis Aprobaciones" para el Administrador / Gerente de Finanzas.
  - Disparador de plantilla de correo electrónico HTML notificando a aprobadores con enlace directo al expediente.

---

#### 🔹 Subtareas de la Épica 4:

1. **ST-4.1: Estructura de Base de Datos para Expedientes y Aprobadores**
   - **Descripción**: Crear las tablas `expedientes_aprobacion`, `asignacion_envios_expedientes` y `aprobadores_expedientes`.
2. **ST-4.2: Agrupador de Envíos en Expedientes (`Logistica_expedientes.php`)**
   - **Descripción**: Desarrollar la pantalla donde el operador selecciona varios envíos creados y los consolida en un **Expediente** (Folio `EX-000001`), calculando la suma total de kilometraje y costos.
3. **ST-4.3: Flujo de "Enviar a Aprobación" y Notificación por Correo**
   - **Descripción**: Al presionar "Enviar a Aprobación", el expediente pasa a Estado `Enviado` (ID 2) y se dispara un correo HTML automático utilizando PHPMailer hacia todos los correos registrados en `aprobadores_expedientes`.
4. **ST-4.4: Módulo de Aprobaciones para Finanzas (`Logistica_aprobaciones.php`)**
   - **Descripción**: Crear `Controllers/Logistica_aprobaciones.php` y vista dedicada para que el aprobador consulte los expedientes en estado `Enviado` (2) o `Regresado` (3), revise el desglose de envíos/unidades y pueda **Aprobar** o **Rechazar/Regresar** con observaciones.
5. **ST-4.5: Transición de Estados en Cascada Expediente → Envíos**
   - **Descripción**: Programar la transacción SQL que, al ser aprobado un expediente, cambie en cascada el estado de todos sus envíos vinculados a `Aprobado` (ID 3) y notifique por correo al operador creador.

---

### 📷 ÉPICA 5: Control de Evidencias Multimedia y Cierre de Área ("Siguiente Área")

- **De dónde se saca / Lo que ya tenemos:**
  - Directorio de almacenamiento de uploads en Docker (`Assets/uploads/`).
  - Helper de manipulación de archivos.

- **Lo que vamos a hacer nuevo:**
  - Tabla `evidencias_logistica`.
  - Gestor de subida de archivos múltiples (Fotos JPG/PNG y Videos MP4/WEBM/MOV).
  - Modal dinámico de evidencias con previsualización en miniatura, reproductor de video y borrado individual.
  - Validación de requisitos para habilitar el botón **"Siguiente Área"** (finalizar logística).

---

#### 🔹 Subtareas de la Épica 5:

1. **ST-5.1: Estructura de Base de Datos y Almacenamiento `evidencias_logistica`**
   - **Descripción**: Crear la tabla `evidencias_logistica` (`id_evidencia`, `id_info_unidad_logistica`, `evidencia`, `motivo_evidencia` [1=Salida, 2=Llegada]).
2. **ST-5.2: Gestor AJAX de Carga y Eliminación de Fotos/Videos**
   - **Descripción**: Desarrollar endpoints para subir múltiples archivos multimedia clasificados por motivo (Salida o Llegada) y eliminar físicamente archivos del disco cuando se desechen.
3. **ST-5.3: Componente de Galería y Visor Multimedia en Modal**
   - **Descripción**: Construir en la vista de la bandeja un modal interactivo con pestañas "Evidencia de Salida" y "Evidencia de Llegada", con lightbox para fotos y reproductor para videos.
4. **ST-5.4: Lógica de Validación y Botón "Siguiente Área"**
   - **Descripción**: Programar el botón "Siguiente Área" en la bandeja. Validar que la unidad tenga `fecha_salida` y `fecha_llegada` registradas. Al avanzar, actualizar el subproceso del VIN hacia la etapa destino (Distribuidor o Carrocero) y marcar el estado de proceso como `Finalizado` (3).

---

### 🗺️ ÉPICA 6: Panel de Rutas Geográficas e Integración de Mapas

- **De dónde se saca / Lo que ya tenemos:**
  - Librerías JS base en la plantilla (`Assets/minimal/libs/jsvectormap/`).
  - Registros de envíos con origen, destino y chofer/madrina asignados.

- **Lo que vamos a hacer nuevo:**
  - Vista dedicada `Logistica_panelrutas.php`.
  - Integración con API de Mapas (Google Maps JS API / Leaflet OpenStreetMap).
  - Representación de marcadores de origen (Planta/Puerto), destino (Distribuidor) y trazado de polilíneas de ruta para envíos en tránsito.

---

#### 🔹 Subtareas de la Épica 6:

1. **ST-6.1: Integración de Motor de Mapas en la Vista (`Logistica_panelrutas.php`)**
   - **Descripción**: Crear `Controllers/Logistica_panelrutas.php` e integrar el contenedor del mapa interactivo con Leaflet/Google Maps.
2. **ST-6.2: Endpoint de Geodatos de Envíos Activos (`GET /logistica/rutas-activas`)**
   - **Descripción**: Desarrollar el servicio backend que retorna la lista de envíos en estado `En tránsito` (ID 5) con las coordenadas Latitud/Longitud de origen y destino, datos del chofer, placas de madrina y cantidad de VINs transportados.
3. **ST-6.3: Renderizado Dinámico de Marcadores y Rutas en Mapa**
   - **Descripción**: Programar el script de frontend para dibujar los pines de mapa personalizados, trazar las líneas de trayectoria y mostrar un infowindow/popup al hacer clic en una ruta activa.
