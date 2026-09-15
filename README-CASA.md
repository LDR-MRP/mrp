# Instrucciones para continuar en casa

Este repositorio contiene todo lo necesario para continuar tu trabajo en tu otra computadora (incluyendo Dockerfiles, configuración y la base de datos).

## 1. Importar la Base de Datos

Hemos exportado la base de datos actual al archivo `db_mrp_backup.sql`. Para restaurarla en tu entorno de casa:

1. Levanta los contenedores con Docker Compose:
   ```bash
   docker-compose up -d
   ```
2. Importa el archivo SQL al contenedor de MySQL:
   ```bash
   docker exec -i mrp-db mysql -u root -proot_password db_mrp < db_mrp_backup.sql
   ```

## 2. Configuración de Entorno Local

Recuerda que estamos usando `Config/Config_local.php` para el entorno local. Si tu servidor en casa usa un puerto distinto o una configuración diferente, asegúrate de editar este archivo.

## 3. Notas sobre `.gitignore` y Push a Producción

**¡IMPORTANTE!** En esta rama (`backup-casa`) hicimos un "commit forzado" de archivos que normalmente estarían ignorados (por ejemplo, la carpeta `vendor` u otros generados) para asegurar que tengas absolutamente todo sin necesidad de correr `composer install` o instalar dependencias de nuevo en tu casa.

Cuando termines tus pruebas en casa y quieras volver a hacer un push hacia producción o la rama principal:

1. Asegúrate de NO subir el archivo `db_mrp_backup.sql` a producción.
2. Revisa tu archivo `.gitignore` para confirmar que esté ignorando las dependencias (`/vendor/`, `/node_modules/`, etc.) y configuraciones locales (`Config_local.php`).
3. Te recomiendo volver a la rama principal (e.g. `main` o `master`), fusionar (merge) solo los archivos de código fuente que hayas modificado, y hacer el push de manera normal.

```bash
# Ejemplo para continuar:
git checkout main
git merge backup-casa
# ... resolver conflictos si los hay ...
git push origin main
```
