# Estándar de Desarrollo y Flujo de Trabajo (Sistema MRP)

Este documento define la arquitectura, normas de codificación, convenciones de base de datos y el flujo de trabajo con Git que **todos los desarrolladores y equipos** deben seguir estrictamente al trabajar en este proyecto.

---

## 🏗️ 1. Arquitectura del Framework (PHP 8.2+ MVC + Servicios)

El proyecto utiliza un **Framework MVC propio en PHP Nativo**, evolucionado hacia patrones modernos de 4 capas:

```text
[Cliente / Request] ➔ 1. Request (Validación) ➔ 2. Controller (Orquestador) ➔ 3. Service (Lógica/Reglas) ➔ 4. Model (SQL)
```

### Reglas por Capa:
1. **Validación (`Requests/[Modulo]Request.php`)**:
   - Centraliza las reglas de validación de datos (`$_POST`, `$_GET`, payloads JSON).
   - Si falla la validación, detiene la ejecución inmediatamente y devuelve los errores al cliente.
2. **Controladores (`Controllers/[Modulo].php`)**:
   - Orquestador delgado. Recibe la petición, ejecuta la validación, delega al Servicio y devuelve la respuesta.
   - Usa el trait `ApiResponser` (`successResponse` / `errorResponse`).
   - **Regla PHP 8.2+**: Todas las propiedades deben declararse explícitamente para evitar el aviso `Deprecated: Creation of dynamic property`.
3. **Servicios (`Services/[Modulo]Service.php`)**:
   - Contiene la inteligencia de negocio (cálculos MRP, validaciones de stock y transacciones).
   - **REGLA ESTRICTA**: Jamás debe imprimir (`echo`), ni usar `header()`, ni renderizar HTML.
   - Las operaciones multi-tabla deben envolverse en transacciones: `$db->beginTransaction()`, `$db->commit()`, `$db->rollBack()`.
4. **Modelos (`Models/[Modulo]Model.php`)**:
   - Contiene únicamente sentencias SQL puras usando la clase base `Mysql`.
   - **REGLA ESTRICTA**: No debe contener condiciones `if` de negocio ni formateo de datos.
5. **Vistas (`Views/[Modulo]/[modulo].php`)**:
   - Plantillas HTML/PHP para la interfaz gráfica y modales.

---

## 🐳 2. Entorno de Desarrollo Local (Docker)

El desarrollo local se ejecuta en contenedores Docker:
- **Aplicación Web**: [http://localhost/mrp](http://localhost/mrp)
- **phpMyAdmin**: [http://localhost:8081](http://localhost:8081)
- **MySQL**: Contenedor `mrp-db` (Puerto 3306, BD: `db_mrp`, User: `mrp_user`, Pass: `mrp_password`).
- **Configuración Local**: Definida en `Config/Config_local.php` (ignorado en `.gitignore`).

---

## 🌿 3. Flujo de Trabajo en Git (Git Flow / Feature Branching)

Todos los integrantes del equipo deben seguir este flujo de ramas:

1. **Rama Base Personal**: `devcr` (contiene la infraestructura local y avances).
2. **Creación de Rama por Actividad**: Para cada nueva funcionalidad o corrección, crear una rama dedicada:
   ```bash
   git checkout -b feature/nombre-actividad
   ```
3. **Promoción a Staging / Main**:
   - Al terminar y probar la actividad localmente en Docker, crear un **Pull Request (PR)** hacia `staging`.
   - Tras la verificación en `staging`, se realiza la promoción a `main`.

---

## 🗄️ 4. Convención de Nombres en Base de Datos

Todas las tablas de la base de datos deben seguir la taxonomía oficial:

| Prefijo | Tipo de Tabla | Ejemplo | Descripción |
| :--- | :--- | :--- | :--- |
| `{$modulo}_cat_` | Catálogo (Maestro) | `wms_cat_monedas` | Datos maestros estáticos o de larga duración. |
| `{$modulo}_tra_` | Transaccional | `com_tra_compras` | Registro de eventos y transacciones en el tiempo. |
| `{$modulo}_det_` | Detalle | `com_det_compra_partidas` | Extensión 1:N de una tabla transaccional. |
| `{$modulo}_rel_` | Relacional (Pivot) | `prv_rel_proveedor_marcas` | Tablas intermedias para relaciones N:M. |
| `log_` | Bitácora / Auditoría | `log_audit` | Registros inmutables de auditoría e historial. |

---

## 📌 5. Lista de Verificación de Desarrollo
Antes de completar cualquier tarea de programación, se debe verificar:
- [ ] La validación de datos está en `Requests/`.
- [ ] La lógica y transacciones SQL están en `Services/`.
- [ ] Las consultas SQL puras están en `Models/`.
- [ ] Las respuestas JSON usan `ApiResponser`.
- [ ] No existen propiedades dinámicas no declaradas (Compatibilidad PHP 8.2+).
- [ ] Las pruebas fueron validadas en el entorno Docker local ([http://localhost/mrp](http://localhost/mrp)).
