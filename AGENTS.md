# Sistema MRP / Logística

Sistema integral de gestión de recursos empresariales y logística de traslados vehiculares (madrinas, plataformas, rodando).

## Stack y Versiones
- **PHP:** 8.2 (FPM / CLI en Docker `mrp-app`)
- **Base de Datos:** MySQL 8.0 (Docker `mrp-db`)
- **Arquitectura:** MVC personalizado (`Controllers`, `Models`, `Views`, `Services`, `Libraries`)
- **Librerías principales:** `dompdf/dompdf ^3.1`, `phpmailer/phpmailer ^7.1`, `firebase/php-jwt ^7.1`
- **Frontend:** Vanilla JS, jQuery, DataTables, Bootstrap / Admin Dashboard

## Comandos Principales
- **Iniciar Contenedores:** `docker compose up -d`
- **Ver logs de aplicación:** `docker compose logs -f app`
- **Lint sintaxis PHP:** `docker exec mrp-app bash -c 'for f in Controllers/*.php Models/*.php Services/*.php; do php -l "$f"; done'`
- **Acceso MySQL CLI:** `docker exec -it mrp-db mysql -u $DB_USER -p$DB_PASSWORD $DB_NAME`

## Estructura del Proyecto
| Ruta | Qué hay | Buscar aquí cuando... |
| :--- | :--- | :--- |
| `Controllers/` | Controladores MVC de los módulos | Se requiera definir endpoints, respuestas JSON o vistas |
| `Models/` | Modelos de persistencia con PDO (`Mysql.php`) | Se agreguen consultas SQL o manipulación de datos |
| `Services/` | Capa de lógica de negocio transaccional | Se implementen reglas de estado, aprobaciones o recálculos |
| `Views/` | Vistas HTML/PHP divididas por módulo | Se ajusten formularios, tablas interactivas o modales |
| `Assets/js/modulos/` | Scripts JS específicos por módulo | Se manipule el DOM, DataTables, SweetAlert2 o peticiones AJAX |
| `Libraries/Core/` | Clases núcleo del framework (`Conexion`, `Mysql`, `Autoload`) | Se modifique la conexión a base de datos o el cargador |
| `Config/` | Configuración global (`Config.php`, `App.php`) | Se configuren URLs base, puertos o constantes globales |

## Convenciones y Zonas Sensibles
- **Tabla de Usuarios:** La tabla principal de usuarios del sistema es `usuarios` con llave primaria `idusuario` (nunca `idpersona`).
- **Estados de Envío:** Flujo de estados `1: Borrador`, `2: Por Confirmar`, `3: Confirmado`, `4: Despachado`, `6: En Tránsito`, `7: Entregado`.
- **Zonas Sensibles:** No modificar directamente `Libraries/Core/Mysql.php` ni alterar las columnas de auditoría sin revisión previa.

