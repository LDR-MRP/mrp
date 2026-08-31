# Bitácora de Commits e Historial de Avances

Este archivo registra el detalle de los commits realizados localmente para facilitar entregas parciales, auditoría y selecciones mediante `git cherry-pick`.

> [!NOTE]
> Este archivo está excluido en `.gitignore` para evitar que se suba a repositorios remotos.

---

## 📌 Historial de Commits Recientes

### 11. `HEAD` - `feat(logistica): formulario de inspeccion de entrega en destino (5 fotos, extras y notas) en modulo de evidencias`
* **Fecha:** 2026-08-25
* **Descripción de Cambios:**
  * **Enfoque Exclusivo en Entrega/Recepción:** Se eliminó el selector confuso de "Salida vs Llegada" del módulo de Evidencias y Cierre de Entrega (`Views/Lgs_evidencias/index.php`), dejando el módulo enfocado en la **Inspección de Entrega en Destino**.
  * **Inspección Completa de Llegada:** Se incorporaron los 5 puntos de inspección fotográfica obligatoria (Frente, Atrás, Lateral Izquierdo, Lateral Derecho, Odómetro/Km Final), botón para adjuntar fotografías adicionales o remisión firmada, y campo de observaciones de recepción por VIN.
  * **Integración de Visor Detallado:** Se integró el `modalVerEvidencias` y la función `verEvidenciasUnidad` para consultar la inspección completa de entrega directamente desde el historial de evidencias.
* **Archivos Afectados:**
  * `Assets/js/modulos/functions_lgs_evidencias.js`
  * `Views/Lgs_evidencias/index.php`
  * `HISTORIAL_COMMITS.md`

---

### 10. `fix(logistica): visualizacion de historico de salida y evidencias en mesa de despacho`
* **Fecha:** 2026-08-25
* **Descripción de Cambios:**
  * **Consulta de Histórico en Mesa de Despacho:** Al hacer clic en "Ver Salida / Evidencias" dentro de la pestaña de Histórico de Despachos (`Views/Lgs_ejecucion/index.php`), ya no redirige a cierre/entrega sino que abre la planilla de salida en modo histórico/consulta con fecha real y observaciones registradas.
  * **Visualización de Evidencias de Salida por VIN:** En el histórico, cada VIN cuenta con el botón de "Ver Evidencias", abriendo el modal detallado de inspección (`modalVerEvidencias`) con las 5 fotografías obligatorias (Frente, Atrás, Lateral Izquierdo, Lateral Derecho, Odómetro), fotos extras y comentarios de inspección capturados en planta.
  * **Ajustes de UI:** Ocultamiento contextual de botones de confirmación/reversión al estar en modo histórico.
* **Archivos Afectados:**
  * `Assets/js/modulos/functions_lgs_ejecucion.js`
  * `Views/Lgs_ejecucion/index.php`
  * `HISTORIAL_COMMITS.md`

---

### 9. `feat(logistica): orden de desembarque secuencial de vins, estados en madrina/entregado y resolucion de destinos gps`
* **Fecha:** 2026-08-25
* **Descripción de Cambios:**
  * **Resolución Completa de Destinos:** Se optimizó `Lgs_panelrutasModel::getDetalleDestinosRuta` para vincular las paradas asignadas (`ev.id_parada`), catálogos de clientes (`cli_clientes`), destinos (`lgs_cat_destinos`) o destino del envío como fallback, garantizando visualización precisa de destinos y coordenadas GPS.
  * **Orden de Desembarque por Itinerario:** Se ordenó la lista de unidades a bordo por la secuencia de paradas de la ruta (`ORDER BY COALESCE(ep.orden, 999) ASC, ev.posicion_acomodo ASC, ev.id ASC`), mostrando primero las unidades que se entregarán en la primera parada del trayecto con insignias `#1`, `#2`, etc.
  * **Badges de Estado de Unidad:** Se agregaron badges visuales dinámicos (`En Madrina` / `Entregado`) en el desglose de VINs (`functions_lgs_panelrutas.js`), con integración al buscador en tiempo real.
  * **Refactorización y Limpieza de Evidencias:** Actualizaciones en controladores y modelos de ejecución y evidencias para soporte multimedia completo y segregación de datos.
  * **Documentación:** Actualización de `0_DDL_LOGISTICA.sql`, `6_bitacora_avances.md`, `CHANGELOG_DB_SCHEMA.md` e `HISTORIAL_COMMITS.md`.
* **Archivos Afectados:**
  * `Models/Lgs_panelrutasModel.php`
  * `Assets/js/modulos/functions_lgs_panelrutas.js`
  * `Controllers/Lgs_aprobaciones.php`
  * `Controllers/Lgs_ejecucion.php`
  * `Controllers/Lgs_evidencias.php`
  * `Models/Lgs_bandejaModel.php`
  * `Models/Lgs_ejecucionModel.php`
  * `Models/Lgs_evidenciasModel.php`
  * `Services/Lgs_ejecucionService.php`
  * `Services/Lgs_panelrutasService.php`
  * `Views/Lgs_ejecucion/chofer_movil.php`
  * `Views/Lgs_ejecucion/index.php`
  * `Views/Lgs_evidencias/index.php`
  * `Views/Lgs_panelrutas/index.php`
  * `Views/Template/nav_admin.php`
  * `docs_migracion_logistica/0_DDL_LOGISTICA.sql`
  * `docs_migracion_logistica/6_bitacora_avances.md`
  * `CHANGELOG_DB_SCHEMA.md`
  * `HISTORIAL_COMMITS.md`

---

### 8. `feat(logistica): flujo de despacho, evidencias trasladista, segregacion multi-sede y entrega qr`
* **Fecha:** 2026-08-19
* **Descripción de Cambios:**
  * **Estructura y Migraciones de BD:** Se creó y ejecutó el script `Scripts/update_lgs_flow_recoleccion_evidencias.sql` añadiendo fecha confirmada de recolección y estatus físico de unidades a `lgs_envios` y `lgs_envios_vins`, además de crear `lgs_trasladistas_checklist` y `lgs_checklist_evidencias`.
  * **Segregación Multi-Sede (`plantaid`):** Se adaptaron `Lgs_ejecucionModel.php`, `Lgs_ejecucionService.php`, `Lgs_ejecucion.php` y `Lgs_panelrutas.php` para restringir la mesa de despacho y el monitoreo GPS al `plantaid` del usuario logueado en sesión.
  * **Programación de Recolección:** Se implementó el modal y endpoint `confirmarRecoleccion` para definir la fecha pactada de recolección y marcar unidades como listas en el área de entregas (`EN_ENTREGAS`).
  * **Portal Móvil de Trasladistas:** Se creó `Views/Lgs_ejecucion/chofer_movil.php` y `Assets/js/modulos/functions_lgs_chofer.js` para escaneo de VINs por cámara, captura de evidencias fotográficas obligatorias (4 ángulos + odómetro) y firma digital.
  * **Entrega en Destino con QR:** Se creó `Views/Lgs_ejecucion/entrega_destino.php` y `Assets/js/modulos/functions_lgs_entrega.js` para lectura de QR de cliente, confirmación de descarga y remisión firmada.
  * **Actualización de Navegación y Documentación:** Se agregaron enlaces directos en `Views/Template/nav_admin.php` y se actualizaron `CHANGELOG_DB_SCHEMA.md` y `HISTORIAL_COMMITS.md`.
* **Archivos Afectados:**
  * `Scripts/update_lgs_flow_recoleccion_evidencias.sql`
  * `Models/Lgs_ejecucionModel.php`
  * `Services/Lgs_ejecucionService.php`
  * `Controllers/Lgs_ejecucion.php`
  * `Models/Lgs_panelrutasModel.php`
  * `Services/Lgs_panelrutasService.php`
  * `Controllers/Lgs_panelrutas.php`
  * `Views/Lgs_ejecucion/index.php`
  * `Views/Lgs_ejecucion/chofer_movil.php`
  * `Views/Lgs_ejecucion/entrega_destino.php`
  * `Assets/js/modulos/functions_lgs_ejecucion.js`
  * `Assets/js/modulos/functions_lgs_chofer.js`
  * `Assets/js/modulos/functions_lgs_entrega.js`
  * `Views/Template/nav_admin.php`
  * `CHANGELOG_DB_SCHEMA.md`
  * `HISTORIAL_COMMITS.md`

---

### 7. `HEAD` - `feat(logistica): tabla ficticia de unidades y secuencia visual de carga (1º en Cargar)`
* **Fecha:** 2026-08-11
* **Descripción de Cambios:**
  * **Tabla Ficticia de Unidades (`lgs_unidades_envios`):** Se creó el script `Scripts/create_lgs_unidades_envios.sql` e integró en `Lgs_enviosModel.php` la auto-inicialización y consulta de unidades con campos `origen` y `destino`.
  * **Secuencia e Indicadores de Carga de VINs:** Se actualizó la interfaz de acomodo (`Views/Lgs_envios/detalle.php` y `Assets/js/modulos/functions_lgs_envios_detalle.js`) para desplegar en tiempo real las insignias de secuencia de carga (`1º EN CARGAR`, `2º EN CARGAR`, etc.), así como la ruta completa `Origen ➔ Destino` en cada tarjeta de unidad.
  * **Actualización de `.gitignore`:** Se excluyeron los archivos/carpetas `docker/`, `docker-compose.yml` e `interfaz ejemplo*` del rastreo de Git y se aplicó la desvinculación limpia tanto en `main` como en la rama de desarrollo.
* **Archivos Afectados:**
  * `.gitignore`
  * `Scripts/create_lgs_unidades_envios.sql`
  * `Models/Lgs_enviosModel.php`
  * `Views/Lgs_envios/detalle.php`
  * `Assets/js/modulos/functions_lgs_envios_detalle.js`
  * `docs_migracion_logistica/6_bitacora_avances.md`
  * `HISTORIAL_COMMITS.md`

---
### 6. `HEAD` - `feat(logistica): integracion de campo destino y asignacion dinamica de madrinas/choferes en envios`
* **Fecha:** 2026-08-11
* **Descripción de Cambios:**
  * **Integración de Destino (Clientes / Distribuidores):** Se agregó el campo `id_destino` (con selector dinámico alimentado desde `cli_clientes` y fallback a `lgs_cat_destinos`) en el formulario de creación de envío, se agregó la columna Destino al DataTable y se alteró la tabla `lgs_envios` en MySQL.
  * **Asignación Dinámica de Madrinas / Choferes:** Se filtraron las Madrinas y Choferes en el modal de selección por la Empresa Trasladista del envío (`id_proveedor`).
  * **Carga de VINs Disponibles:** Se vinculó `mrp_unidades_terminadas` para obtener unidades reales listas en patio/origen sin asignaciones previas.
  * **Acomodo Drag-and-Drop y Recálculo:** Se implementó la interfaz drag-and-drop con SortableJS, el guardado de acomodo en `lgs_envios_vins` y el recálculo automático de costos del envío.
  * **Asignación Exclusiva por Tipo de Traslado:** Si el envío es de tipo **Madrina** (`id_tipo_traslado = 1`), la interfaz oculta la pestaña de choferes rodando y muestra exclusivamente la lista de madrinas del trasladista. Si el envío es **Chofer Rodando** (`id_tipo_traslado = 2`), se muestra exclusivamente la pestaña de selección de choferes rodando.
  * **Corrección de Modelo Bandeja:** Se corrigió el nombre de tabla en `Lgs_bandejaModel.php` (`lgs_cat_destino` -> `lgs_cat_tipo_destino`), garantizando respuesta HTTP 200 en las 9 vistas del módulo.
* **Archivos Afectados:**
  * `Libraries/Core/Mysql.php`
  * `Controllers/Lgs_envios.php`
  * `Models/Lgs_enviosModel.php`
  * `Models/Prv_madrinasModel.php`
  * `Services/Lgs_enviosService.php`
  * `Views/Lgs_envios/index.php`
  * `Views/Lgs_envios/detalle.php`
  * `Assets/js/modulos/functions_lgs_envios.js`
  * `Assets/js/modulos/functions_lgs_envios_detalle.js`
  * `docs_migracion_logistica/0_DDL_LOGISTICA.sql`
  * `CHANGELOG_DB_SCHEMA.md`
  * `PLAN_MASTER_LOGISTICA.md`

---

### 5. `HEAD` - `fix(logistica): correccion de ddl en mysql, conexion pdo en mysql.php y validaciones ajax en envio`
* **Fecha:** 2026-08-11
* **Descripción de Cambios:**
  * **Verificación de Diseño UI:** Se validaron las 8 vistas principales de Logística (`Lgs_*`) y Catálogos de Transporte (`Prv_*`) comprobando el 100% de cumplimiento con las interfaces de referencia (`interfaz ejemplo grid.jpeg` e `interfaz ejemplo form.jpeg`).
  * **Ejecución DDL:** Se ejecutó el script `0_DDL_LOGISTICA.sql` en el contenedor `mrp-db`, creando e inicializando las 13 tablas `lgs_*` del módulo.
  * **Corrección de Conexión PDO:** Se cambió la visibilidad de `$conexion` en `Libraries/Core/Mysql.php` a `protected` para resolver el error `TypeError` en `getConexion()`.
  * **Sanitización de Fechas:** Se corrigieron los valores por defecto de fechas vacías (`""` -> `null`) en `Controllers/Lgs_envios.php`.
  * **Manejo de Errores AJAX:** Se actualizó `saveEnvio()` en `functions_lgs_envios.js` para cerrar alertas de carga en respuestas HTTP 500/404 y mostrar mensajes explicativos al usuario.
  * **Integración de Destino (Clientes / Distribuidores):** Se agregó el campo `id_destino` (con selector dinámico alimentado desde `cli_clientes` y fallback a `lgs_cat_destinos`) en el formulario de creación de envío, se agregó la columna Destino al DataTable y se alteró la tabla `lgs_envios` en MySQL.
  * **Documentación:** Se actualizaron `CHANGELOG_DB_SCHEMA.md` y `PLAN_MASTER_LOGISTICA.md` al 100% de avance.
* **Archivos Afectados:**
  * `Libraries/Core/Mysql.php`
  * `Controllers/Lgs_envios.php`
  * `Models/Lgs_enviosModel.php`
  * `Views/Lgs_envios/index.php`
  * `Assets/js/modulos/functions_lgs_envios.js`
  * `docs_migracion_logistica/0_DDL_LOGISTICA.sql`
  * `CHANGELOG_DB_SCHEMA.md`
  * `PLAN_MASTER_LOGISTICA.md`

---

### 1. `8a0f8f6` - `fix(auth): blindar validacion jwt en middleware, js y sincronizacion automatica sso`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se optimizó la regla `$needsSsoSync` en `index.php` para disparar el login automático por SSO heredado siempre que no haya sesión en MRP (`empty($_SESSION['login'])`) y exista la cookie `token` de RRHH sin bloqueo manual.
  * Se fortaleció `Middlewares/AuthMiddleware.php` agregando lectura de `$_SERVER['REDIRECT_HTTP_AUTHORIZATION']` y fallback automático a cookies `mrp_token`/`srm_token` si no se envía la cabecera HTTP.
  * Se sanitizó la lectura de tokens en `Services/IdentityService.php` y `Assets/js/sys_core.js` para remover prefijos como `Bearer` antes de decodificar Base64Url o usar `JWT::decode`.
* **Archivos Afectados:**
  * `index.php`
  * `Middlewares/AuthMiddleware.php`
  * `Services/IdentityService.php`
  * `Assets/js/sys_core.js`

---

### 2. `1fe377c` - `fix(auth): corregir sincronizacion sso rrhh, redireccion de sesion y flujo de login api`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se activó `ob_start()` en `index.php` para prevenir errores de cabeceras enviadas antes de tiempo (`Headers Already Sent`).
  * Se flexibilizó la decodificación de JWT en `IdentityService.php` y `AuthService.php` utilizando `json_decode(json_encode($decoded), true)` y agregando fallbacks para mapear cualquier variante del payload (`correo`, `email`, `sub`, `email_user`, etc.).
  * Se corrigió la redirección en `Controllers/Login.php` (`sso_login`) integrando limpiado de buffer (`ob_clean`) y redirección de respaldo por script JS (`window.location.href`) para evitar que se quede colgado en pantalla en blanco.
  * Se simplificó la autenticación por formulario en `functions_login.js` eliminando peticiones AJAX secundarias redundantes a la API.
* **Archivos Afectados:**
  * `index.php`
  * `Controllers/Login.php`
  * `Services/IdentityService.php`
  * `Services/AuthService.php`
  * `Models/UsuariosModel.php`
  * `Views/Login/login.php`
  * `Assets/js/modulos/functions_login.js`

---

### 2. `08a9790` - `fix(auth): corregir flujo de restablecimiento de contraseña y navegacion`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se corrigió la salida `echo` por `error_log` en `Helpers/Helpers.php` dentro del manejo de excepciones de `sendMailLocal` para evitar corromper las respuestas JSON de las peticiones AJAX.
  * Se integraron las librerías `SweetAlert2` y `sys_core.js` en la vista `Views/Login/cambiar_password.php`.
  * Se mejoraron los callbacks de `functions_login.js` en `#formRecetPass` y `#formCambiarPass` agregando bloques `try/catch` para parsear JSON, voltear la tarjeta (`flipCard()`) de regreso al login tras solicitar el reseteo y hacer la redirección a `/login` al actualizar la contraseña.
* **Archivos Afectados:**
  * `Helpers/Helpers.php`
  * `Views/Login/cambiar_password.php`
  * `Assets/js/modulos/functions_login.js`

---

### 2. `a1364ba` - `feat(auth): diferenciar mensajes de error entre usuario no registrado y contraseña incorrecta`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se actualizó la consulta `findByEmail` en `UsuariosModel.php` y `LoginModel.php` para validar la existencia del correo independientemente del estatus.
  * Se modificó la lógica en `Controllers/Login.php` y `Services/AuthService.php` para separar explícitamente los mensajes:
    * `"El usuario no se encuentra registrado."`
    * `"La contraseña ingresada es incorrecta."`
    * `"El usuario se encuentra inactivo. Contacte al administrador."`
* **Archivos Afectados:**
  * `Controllers/Login.php`
  * `Models/LoginModel.php`
  * `Models/UsuariosModel.php`
  * `Services/AuthService.php`

---

### 3. `c0e7156` - `feat(ui): implementar notificaciones Toast con SweetAlert2 en login y global`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se incluyeron las hojas de estilo y scripts de SweetAlert2 en `Views/Login/login.php`.
  * Se creó la utilidad global `Sys_Core.UI.notify` en `Assets/js/sys_core.js` usando `Swal.mixin({ toast: true, position: 'top-end', ... })`.
  * Se actualizaron los formularios de login para utilizar Toasts dinámicos en lugar de diálogos emergentes modales.
* **Archivos Afectados:**
  * `Views/Login/login.php`
  * `Assets/js/sys_core.js`
  * `Assets/js/modulos/functions_login.js`

---

### 4. `1f76548` - `fix(auth): corregir carga de configuracion y autoloader en cli_hash_passwords.php`
* **Fecha:** 2026-07-29
* **Descripción de Cambios:**
  * Se corrigió la inclusión de configuración en `cli_hash_passwords.php` para cargar `Config_local.php` o `Config.php` en lugar del archivo inexistente `Config_dev.php`.
  * Se añadió el registro del autoloader del framework `Libraries/Core/Autoload.php` para resolver la clase `Mysql`.
* **Archivos Afectados:**
  * `cli_hash_passwords.php`

---

### 5. `feat(logistica): modulo de administracion de costos y tarifario dinamico por segmento`
* **Fecha:** 2026-08-17
* **Descripción de Cambios:**
  * Se crearon las tablas `lgs_cat_segmentos` y `lgs_costos_rutas` con soporte a factores de volumen y descuentos progresivos.
  * Se vinculó `cat_modelos_vin` con `id_segmento` para resolver el costo automáticamente por tipo de unidad.
  * Se creó la arquitectura MVC + Servicios: `Lgs_costosModel.php`, `Lgs_costosService.php`, `Lgs_costos.php`, `Views/Lgs_costos/index.php` y `functions_lgs_costos.js`.
  * Se integró el recálculo dinámico en `Lgs_enviosService.php` con fallback de compatibilidad.
  * Se añadió el acceso directo en `nav_admin.php` bajo la sección de Logística.
* **Archivos Afectados:**
  * `db_info/lgs_costos_rutas_segmentos.sql`
  * `Models/Lgs_costosModel.php`
  * `Services/Lgs_costosService.php`
  * `Controllers/Lgs_costos.php`
  * `Views/Lgs_costos/index.php`
  * `Assets/js/modulos/functions_lgs_costos.js`
  * `Services/Lgs_enviosService.php`
  * `Views/Template/nav_admin.php`
  * `CHANGELOG_DB_SCHEMA.md`

---

## 🚀 Comandos Útiles para Entregas Parciales

* **Subir un solo commit a una rama remota:**
  ```bash
  git push origin <HASH_DEL_COMMIT>:<NOMBRE_RAMA_REMOTA>
  ```
* **Crear una PR con solo un commit específico:**
  ```bash
  git checkout main
  git pull
  git checkout -b rama-pr-especifica
  git cherry-pick <HASH_DEL_COMMIT>
  git push origin rama-pr-especifica
  ```
