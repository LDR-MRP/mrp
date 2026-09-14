# 🗄️ Guía y Manual de Migraciones de Base de Datos (`db_mrp`)

Este documento detalla el procedimiento técnico, orden de ejecución, scripts disponibles e instrucciones operativas para la aplicación segura de migraciones y cambios de esquema en la base de datos `db_mrp` (tanto en entornos de Desarrollo local Docker como en Staging y Producción).

---

## 📌 Principios de Migración en el Proyecto

1. **Idempotencia Obligatoria:** Todos los scripts DDL y DML deben ser seguros de re-ejecutar sin provocar errores de duplicación (`CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE INTO`, `ADD COLUMN IF NOT EXISTS` o verificación de columnas).
2. **Preservación de Integridad:** Ningún script de migración debe truncar o eliminar tablas existentes con datos transaccionales (`DROP TABLE` está prohibido en scripts de producción).
3. **Control de Llaves Foráneas:** Los scripts desactivan temporalmente la validación de claves foráneas (`SET FOREIGN_KEY_CHECKS = 0;`) al inicio y la restauran al final (`SET FOREIGN_KEY_CHECKS = 1;`).
4. **Codificación:** Todos los scripts utilizan `utf8mb4` con collation `utf8mb4_unicode_ci` para soportar acentos, caracteres especiales y compatibilidad completa.

---

## 🗂️ Inventario de Scripts SQL y Migraciones

### 1. Migración Consolidada para Producción
* **Archivo:** [`docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql`](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql)
* **Propósito:** Script maestro todo-en-uno que consolida todas las tablas de catálogos, envíos, costeo, paradas, checklists, aprobaciones y los seeders base de orígenes y destinos (41 plazas).
* **Entornos:** Staging / Producción / Replicación limpia.

---

### 2. Scripts Modulares por Épica / Funcionalidad

| Orden | Script SQL | Ubicación | Descripción / Contenido |
| :---: | :--- | :--- | :--- |
| **01** | `0_DDL_LOGISTICA.sql` | [`docs_migracion_logistica/0_DDL_LOGISTICA.sql`](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/0_DDL_LOGISTICA.sql) | DDL inicial: `lgs_cat_tipo_traslado`, `lgs_cat_motivo_envio`, `lgs_cat_tipo_destino`, `lgs_cat_origenes`, `lgs_cat_destinos`, `lgs_envios`, `lgs_envios_vins`, `lgs_costos_proveedor_segmento`, `lgs_planeaciones`, `lgs_planeaciones_envios`, `lgs_aprobadores`, `lgs_solicitudes_entrega`, `lgs_evidencias`. |
| **02** | `lgs_costos_rutas_segmentos.sql` | [`db_info/lgs_costos_rutas_segmentos.sql`](file:///home/christianguarneros/proyectos/mrp/db_info/lgs_costos_rutas_segmentos.sql) | Matriz tarifaria: `lgs_cat_segmentos` (Ligeros, Mediano, Pesado, Buses, Lowboy), alter a `cat_modelos_vin` (`id_segmento`), y tabla `lgs_costos_rutas`. |
| **03** | `add_lgs_envios_paradas.sql` | [`Scripts/add_lgs_envios_paradas.sql`](file:///home/christianguarneros/proyectos/mrp/Scripts/add_lgs_envios_paradas.sql) | Soporte multi-destino: tabla `lgs_envios_paradas` e incorporación de `id_parada` en `lgs_envios_vins`. |
| **04** | `update_lgs_flow_recoleccion_evidencias.sql` | [`Scripts/update_lgs_flow_recoleccion_evidencias.sql`](file:///home/christianguarneros/proyectos/mrp/Scripts/update_lgs_flow_recoleccion_evidencias.sql) | Despacho y checklists: `lgs_trasladistas_checklist`, `lgs_checklist_evidencias`, y columnas de confirmación de recolección y estado físico de unidad. |
| **05** | `create_lgs_unidades_envios.sql` | [`Scripts/create_lgs_unidades_envios.sql`](file:///home/christianguarneros/proyectos/mrp/Scripts/create_lgs_unidades_envios.sql) | Staging / Pruebas: tabla `lgs_unidades_envios` y carga de 8 VINs demo. |
| **06** | `SEEDERS_MADRINAS_CHOFERES.sql` | [`docs_migracion_logistica/SEEDERS_MADRINAS_CHOFERES.sql`](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/SEEDERS_MADRINAS_CHOFERES.sql) | Datos semilla de proveedores trasladistas, flota de nodrizas y catálogo de choferes. |
| **07** | `SEEDERS_PRODUCCION_LOGISTICA.sql` | [`docs_migracion_logistica/SEEDERS_PRODUCCION_LOGISTICA.sql`](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/SEEDERS_PRODUCCION_LOGISTICA.sql) | Seeders maestros: 7 orígenes (Plantas 1-5, Montenegro, Lagos), 41 destinos nacionales y matriz de tarifas base. |

---

## 🚀 Instrucciones de Ejecución

### Opción A: Ejecución en Entorno Local (Docker)

Para aplicar las migraciones en el contenedor de base de datos local `mrp-db`:

```bash
# 1. Entrar a la raíz del proyecto
cd /home/christianguarneros/proyectos/mrp

# 2. Ejecutar el script maestro de logística en el contenedor MySQL
docker exec -i mrp-db mysql -u root -proot db_mrp < docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql

# 3. (Opcional) Ejecutar seeders de madrinas y choferes para pruebas locales
docker exec -i mrp-db mysql -u root -proot db_mrp < docs_migracion_logistica/SEEDERS_MADRINAS_CHOFERES.sql

# 4. (Opcional) Crear tabla auxiliar de unidades para el simulador de carga
docker exec -i mrp-db mysql -u root -proot db_mrp < Scripts/create_lgs_unidades_envios.sql
```

---

### Opción B: Ejecución en Servidor de Producción / Staging (MySQL CLI / phpMyAdmin)

#### Vía Terminal SSH:
```bash
mysql -h <DB_HOST> -u <DB_USER> -p <DB_NAME> < docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql
```

#### Vía phpMyAdmin / DBeaver / MySQL Workbench:
1. Abrir la herramienta de administración y seleccionar la base de datos `db_mrp`.
2. Abrir la pestaña **SQL** o **Ejecutar Script**.
3. Cargar el contenido de [`MIGRACION_PRODUCCION_LOGISTICA.sql`](file:///home/christianguarneros/proyectos/mrp/docs_migracion_logistica/MIGRACION_PRODUCCION_LOGISTICA.sql).
4. Ejecutar la consulta. Al finalizar, verificar que todas las tablas inicien con el prefijo `lgs_` o las extensiones `prv_`.

---

## 🛠️ Herramientas CLI de Migración y Datos

El proyecto cuenta con utilidades CLI en PHP para tareas masivas de migración de datos:

### 1. Migrador Masivo de Proveedores (`cli_generate_migration.php`)
* **Ubicación:** [`cli_generate_migration.php`](file:///home/christianguarneros/proyectos/mrp/cli_generate_migration.php)
* **Clase de soporte:** [`Scripts/MassSupplierMigrator.php`](file:///home/christianguarneros/proyectos/mrp/Scripts/MassSupplierMigrator.php)
* **Uso:** Procesa un archivo `proveedores.csv` (limpiando problemas de saltos de línea de Excel y encodings Windows-1252 a UTF-8) y genera un archivo SQL listo para migrar el padrón de proveedores hacia `prv_cat_proveedores`.
* **Ejecución:**
  ```bash
  php cli_generate_migration.php
  ```

### 2. Cifrador de Contraseñas Legacy (`cli_hash_passwords.php`)
* **Ubicación:** [`cli_hash_passwords.php`](file:///home/christianguarneros/proyectos/mrp/cli_hash_passwords.php)
* **Clase de soporte:** [`Scripts/MassPasswordHasher.php`](file:///home/christianguarneros/proyectos/mrp/Scripts/MassPasswordHasher.php)
* **Uso:** Convierte contraseñas en texto plano o hashes antiguos a `password_hash()` con algoritmo BCRYPT seguro.
* **Ejecución:**
  ```bash
  php cli_hash_passwords.php
  ```

---

## ✅ Verificación Posterior a la Migración

Para confirmar que la migración se aplicó exitosamente, ejecutar la siguiente consulta SQL de verificación:

```sql
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME LIKE 'lgs_%' OR TABLE_NAME LIKE 'prv_%'
ORDER BY TABLE_NAME ASC;
```

**Tablas mínimas esperadas (Logística & Traslados):**
- `lgs_aprobadores`
- `lgs_cat_destinos`
- `lgs_cat_motivo_envio`
- `lgs_cat_origenes`
- `lgs_cat_segmentos`
- `lgs_cat_tipo_destino`
- `lgs_cat_tipo_traslado`
- `lgs_checklist_evidencias`
- `lgs_costos_rutas`
- `lgs_envios`
- `lgs_envios_paradas`
- `lgs_envios_vins`
- `lgs_evidencias`
- `lgs_planeaciones`
- `lgs_planeaciones_envios`
- `lgs_solicitudes_entrega`
- `lgs_trasladistas_checklist`
- `prv_cat_actividades`
- `prv_rel_proveedores_actividades`
- `prv_det_madrinas`
- `prv_det_choferes`
- `prv_det_madrina_chofer_historial`

---

## 📚 Documentación Relacionada

* 📖 **[CHANGELOG_DB_SCHEMA.md](file:///home/christianguarneros/proyectos/mrp/CHANGELOG_DB_SCHEMA.md):** Bitácora detallada de cambios por versión y campo.
* 📦 **[db_info/MODULE_LOGISTICS_LGS.md](file:///home/christianguarneros/proyectos/mrp/db_info/MODULE_LOGISTICS_LGS.md):** Diccionario de datos de tablas LGS.
* 🏭 **[db_info/README.md](file:///home/christianguarneros/proyectos/mrp/db_info/README.md):** Mapa general de todos los módulos de base de datos del MRP.
