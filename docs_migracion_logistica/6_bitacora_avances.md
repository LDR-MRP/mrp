# 📈 Bitácora de Avances — Módulo de Logística
### Fecha de actualización: 04 de Agosto 2026

Este documento registra el progreso de desarrollo del módulo de Logística. Aquí se detallan las tareas finalizadas, el motivo de su conclusión y el porcentaje de avance global y por Épica.

---

## 📊 Porcentaje de Avance Global: **100%**
*(Todas las 6 Épicas desarrolladas y completadas exitosamente).*

---

## ✅ ÉPICA 1: Catálogos Base de Transporte
**Estado:** Finalizado (100%) | **Fecha de término:** 30 Jul 2026

### ¿Qué se finalizó y por qué está completo?
*   **Gestión de Proveedores (Trasladistas):** Se completó el CRUD con validación estricta de RFC (Física 13, Moral 12) y vinculación a actividades (Actividad 2: TRASLADO_UNIDADES).
*   **Gestión de Choferes:** Se desarrolló el CRUD permitiendo dar de alta choferes y vincularlos a un proveedor específico.
*   **Gestión de Madrinas (Nodrizas):** Se implementó el CRUD de vehículos, capacidad de unidades (VINs) y asignación.
*   **Historial de Asignación (Chofer - Madrina):** Se implementó la lógica transaccional para que un chofer pueda ser asignado a una madrina dejando un rastro histórico inactivo de las asignaciones previas, garantizando que siempre sepamos quién manejaba qué vehículo en una fecha determinada.
*   **Menú de Navegación:** Se incluyeron todas estas rutas en el `nav_admin.php` dentro del apartado Logística.

---

## ✅ ÉPICA 2: Catálogos de Logística y Módulo de Envíos
**Estado:** Finalizado (100%) | **Fecha de término:** 05 Ago 2026

### ¿Qué se finalizó y por qué?
*   **Diseño Técnico y Documental:** Se estructuró por completo la lógica de negocio, se definieron los catálogos necesarios (Madrina vs Chofer Rodando) y el soporte para **múltiples paradas** y cálculo exacto de costos (Trasladista + Segmento + Volumen).
*   **Base de Datos DDL:** Script consolidado en `0_DDL_LOGISTICA.sql` con las tablas pivotes, catálogos y esquema robusto de trazabilidad.
*   **Backend MVC (Controlador, Modelo, Servicio):** Se implementó el control de concurrencia y generación de folio (`EN-000001`) con bloqueos de base de datos (`FOR UPDATE`). Además de crear las inserciones transaccionales.
*   **Motor de Costos (PHP):** Lógica matemática programada en `Lgs_enviosService->recalcularCostoTotal()`. Iteración inteligente que diferencia si es "Chofer Rodando" (costo directo x km) o "Madrina" (agrupa VINs por madrina, busca la tarifa exacta por volumen de autos y segmento, y aplica la multiplicación con el factor).
*   **Frontend (Bandeja):** Creada `Views/Lgs_envios/index.php` con la tabla DataTables y el modal para dar de alta cabeceras de envíos.
*   **Interfaz Drag-and-Drop (Acomodo):** Implementado `Views/Lgs_envios/detalle.php` utilizando **SortableJS**. Permite arrastrar VINs desde un *pool* de disponibles hacia camiones/madrinas, reordenarlos, calculando automáticamente su `posicion_acomodo`.
*   **Tabla Ficticia de Unidades y Secuencia de Carga:** Se integró la tabla `lgs_unidades_envios` con campos de Origen y Destino, y se actualizaron los componentes visuales de acomodo para proyectar explícitamente la insignia **`1º EN CARGAR`**, **`2º EN CARGAR`**, etc., indicando cuál unidad se sube primero al vehículo y su ruta completa.

*(Todo el código base para la creación, costeo y asignación de Envíos de la Épica 2 está construido).*

---

## ✅ ÉPICA 3: Planeaciones y Aprobaciones de Envíos
**Estado:** Finalizado (100%) | **Fecha de término:** 05 Ago 2026

### ¿Qué se finalizó y por qué?
*   **Backend MVC (Planeaciones):** Programado `Lgs_planeaciones` (Controller, Model, Service) que permite buscar envíos disponibles (Estado 1) y crear un Folio de Plan (`EX-000001`), bloqueando los envíos y pasándolos a "Pendiente de Aprobación" (Estado 2).
*   **Frontend y Agrupación (Operador):** Programada `Views/Lgs_planeaciones/index.php` con un modal de selección múltiple (checkboxes). Calcula en tiempo real (vía Javascript) los kilómetros, cantidad de VINs y Costo Monetario de la planeación.
*   **Panel de Aprobación (Gerencia):** Construido el módulo `Lgs_aprobaciones` (MVC). Presenta una interfaz ejecutiva donde el aprobador puede ver el total de dinero a gastar, kilómetros y total de VINs. Incluye un botón para **Aprobar** (liberando los envíos al Estado 3: Aprobado para ejecución) o **Rechazar** (devolviéndolos al Estado 4: Rechazado, y el Plan a Estado 3).

---

## ✅ ÉPICA 4: Gestión de Entregas y Ejecución
**Estado:** Finalizado (100%) | **Fecha de término:** 05 Ago 2026

### ¿Qué se finalizó y por me?
*   **Mesa de Despacho (`Lgs_ejecucion`):** Módulo completo MVC donde se muestran únicamente los envíos que fueron previamente aprobados por la gerencia (Estado 3).
*   **Registro de Salida Real & Evidencias:** Formulario para capturar fecha/hora real de salida y enlace a evidencias multimedia (fotos/video de recepción por el trasladista).
*   **Planilla de Acomodo para Entregas:** Vista clara para el personal de planta/almacén que muestra el orden exacto (`Posición #1, #2, #3...`) en el que las unidades deben entregarse al chofer.
*   **Confirmación Individual de VINs:** Botón para marcar la entrega de cada vehículo al trasladista. Cuando el sistema detecta que el último VIN de un envío fue entregado, cambia automáticamente la ruta al **Estado 6 (En Tránsito)**.

---

## ✅ ÉPICA 5: Evidencias Multimedia y Cierre de Entrega
**Estado:** Finalizado (100%) | **Fecha de término:** 05 Ago 2026

### ¿Qué se finalizó y por qué?
*   **Gestión de Evidencias (`Lgs_evidencias`):** Módulo completo MVC para cargar, visualizar y eliminar evidencias fotográficas y en video de recepción en salida y entrega en destino.
*   **Asociación por VIN o Envío:** Registro clasificado por tipo de evidencia (1: Salida de Planta, 2: Llegada a Destino) con almacenamiento de observaciones.
*   **Cierre de Entrega Final:** Lógica de cierre para registrar la fecha real de llegada a destino y cambiar automáticamente el envío a **Estado 7 (Entregado)**, dando por concluido el ciclo de vida del traslado.

---

## ✅ ÉPICA 6: Panel de Rutas Geográficas y Tracking GPS
**Estado:** Finalizado (100%) | **Fecha de término:** 05 Ago 2026

### ¿Qué se finalizó y por qué?
*   **Panel de Rutas (`Lgs_panelrutas`):** Módulo MVC con mapa interactivo construido con **Leaflet.js** y capas OpenStreetMap para visualizar el progreso en tiempo real de unidades en tránsito (Estado 6).
*   **Mapeo de Coordenadas y Tracking:** Lectura de coordenadas GPS de Orígenes, Destinos y telemetría actual de la ruta.
*   **Lista Lateral Interactiva:** Panel dinámico que lista las rutas activas permitiendo centrar el mapa en la posición exacta del camión/madrina al hacer clic.

---

## 🚀 Actualizaciones del 12 de Agosto 2026 (Mejoras Operativas)

### 1. **Filtrado y Búsqueda por VIN en Bandeja de Envíos**
- **Filtrado por Origen y Destino:** El pool de VINs disponibles en `getVinsDisponiblesOrigen` filtra automáticamente para mostrar únicamente unidades cuyo origen y destino coincidan con el envío.
- **Buscador Rápido de VINs:** Se agregó un input de búsqueda interactiva en tiempo real sobre la tabla de envíos (`tableEnvios.search()`) y en el pool de acomodo para ubicar unidades por VIN, número de serie o modelo.

### 2. **Soporte Multi-Destino / Paradas de Ruta (`lgs_envios_paradas`)**
- **Tabla `lgs_envios_paradas`:** Permite definir $N$ paradas ordenadas por viaje (camión/madrina realiza recorrido continuo en una sola ruta).
- **Asignación de Parada por VIN (`lgs_envios_vins.id_parada`):** En la vista de acomodo (`detalle.php`), cada VIN asignado a una madrina cuenta con un selector individual de parada.
- **Auto-selección Inteligente:** Al asignar un VIN a una madrina, el sistema compara automáticamente el destino de su pedido original y pre-selecciona la parada correspondiente con insignia `🎯 Auto desde pedido`.
- **Manejo de Paradas Compartidas (Cero Duplicación):** Si múltiples VINs en la misma madrina bajan en el mismo destino, comparten la parada y muestran la insignia `👥 Parada Compartida`, evitando sumar kilómetros duplicados al recorrido real de la madrina.
- **Redirección Operativa:** Al guardar el acomodo de unidades, el sistema emite una confirmación modal y redirige automáticamente de vuelta a la bandeja principal de envíos.

### 3. **Integración con Google Maps API y Georreferenciación**
- **Direcciones Físicas y Coordenadas (`lat`, `lng`):** Se actualizaron los catálogos `lgs_cat_origenes` y `lgs_cat_destinos` con direcciones completas y coordenadas GPS precisas.
- **Servicio PHP `GoogleMapsService`:** Realiza consultas a la **Google Distance Matrix API** (`maps.googleapis.com/maps/api/distancematrix/json`) con fallback matemático Haversine terrestre (1.25x) para calcular distancias exactas por tramo (`km_tramo`) y ruta total (`km_total`).
- **Desglose Tramo a Tramo y Total Total:** Muestra la distancia paso a paso entre sub-paradas (ej: `Origen ➔ P1 (+65km) ➔ P2 (+130km) ➔ P3 (+280km)`) y la distancia total del viaje en badges en la tabla principal (`index.php`) y en el acomodo (`detalle.php`).
- **Recálculo en Tiempo Real:** Botón **`⚡ Recalcular Ruta (Google Maps)`** en la creación/edición de envíos que invoca `/Lgs_envios/calcularDistanciaRuta`, llena las distancias por parada y actualiza automáticamente la matriz de costo total estimado.

### 4. **Flujo de Despacho, Evidencias Móviles y Entrega con QR (19 de Agosto 2026)**
- **Segregación Multi-Sede (`plantaid`):** Mesa de despacho y monitoreo GPS adaptados para limitar los envíos mostrados a la sede del usuario logueado en sesión (`$_SESSION['userData']['plantaid']`), con bypass global para el rol de Super Administrador.
- **Programación de Recolección (Mesa de Despacho):** El administrativo define la fecha pactada de recolección para envíos aprobados; las unidades pasan a `EN_ENTREGAS` para preparación en el área de entregas del patio origen.
- **Portal Móvil de Trasladistas (`Views/Lgs_ejecucion/chofer_movil.php`):** Interfaz táctil y responsiva con escaneo de VIN por cámara (código de barras / QR) y carga obligatoria de 5 fotografías de inspección (Frente, Atrás, Lateral Izq, Lateral Der, Odómetro).
- **Doble Verificación de Salida:** Salida sincronizada de planta donde logística valida entrega física y trasladista confirma recepción, pasando el envío a `6 = En Tránsito` y unidades a `EN_RUTA`.
### 5. **Monitoreo GPS y Desembarque Secuencial (25 de Agosto 2026)**
- **Resolución Integral de Destinos por VIN:** Se optimizó `Lgs_panelrutasModel::getDetalleDestinosRuta` para resolver con prioridad el destino asignado en parada (`ev.id_parada`), vinculando con clientes (`cli_clientes`), catálogos (`lgs_cat_destinos`), destinos libres o destinos del envío, resolviendo nombres comerciales y coordenadas GPS sin mostrar "Destino no especificado".
- **Orden de Desembarque en Itinerario:** Se configuró el ordenamiento de los VINs según la secuencia de paradas de la ruta (`ORDER BY COALESCE(ep.orden, 999) ASC, ev.posicion_acomodo ASC, ev.id ASC`), garantizando que las unidades asignadas a la primera parada aparezcan primero con sus insignias de secuencia (`#1`, `#2`, etc.).
- **Estados Dinámicos de Unidad (`En Madrina` / `Entregado`):** En `functions_lgs_panelrutas.js`, se integraron badges visuales que muestran si la unidad está en tránsito a bordo (`En Madrina`) o si ya completó su entrega física en destino (`Entregado`), con integración en el buscador en vivo.
- **Módulo de Evidencias Multimedia y Limpieza:** Refactorización en `Lgs_evidenciasModel.php`, `Lgs_evidencias.php` y vistas asociadas para el registro, consulta y eliminación transaccional de evidencias.

---

## 🏆 Resumen Final del Proyecto
El desarrollo completo del módulo de **Logística** (PHP puro + PDO MVC) se encuentra culminado al 100% siguiendo todas las reglas de negocio planteadas. Todos los cambios se encuentran respaldados en git y documentados en los artefactos correspondientes.

