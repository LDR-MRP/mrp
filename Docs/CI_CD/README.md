CI/CD y despliegue a Staging

Este documento explica de forma sencilla cómo es el flujo para subir cambios al proyecto y cómo funciona el despliegue automático al ambiente de Staging.

Flujo para subir cambios

Crea una nueva rama a partir de dev(Tu Nombre (pull con satging)) o staging:

git checkout -b feature/tu-tarea
Desarrolla tu funcionalidad, pruébala localmente (Docker/XAMPP/WSL/Local Server) y sube tu rama al repositorio.
Cuando esté lista, crea un Pull Request (PR) hacia la rama staging.
Al crear el PR, GitHub ejecutará automáticamente una validación para comprobar que el proyecto no tenga errores de sintaxis en PHP y que Composer esté correcto.
Si la validación falla, GitHub mostrará el error. Solo revisa el detalle del workflow, corrige el problema y vuelve a hacer push.
Una vez que todo esté en verde, el Tech Lead revisará tu código. Si todo está correcto, aprobará el PR y realizará el merge.
Después del merge a staging, el despliegue se realiza automáticamente. El servidor recibe los cambios, actualiza el proyecto y deja el ambiente de Staging listo para pruebas.

El proyecto utiliza dos workflows de GitHub Actions:

**ci-pr.yml**

Se ejecuta cada vez que se abre o actualiza un Pull Request.

Su objetivo es validar que:

- El código PHP no tenga errores de sintaxis.
- Composer pueda ejecutarse correctamente.

Si alguna validación falla, el PR no podrá integrarse hasta corregir el problema.

**deploy-staging.yml**

Se ejecuta automáticamente cuando hay un merge hacia la rama staging.

Este workflow:

Se conecta al servidor mediante SSH.
Actualiza el repositorio (git pull).
Ejecuta composer install si es necesario.
Reinicia los contenedores Docker (solo si aplica y lo pasamos a docker).

De esta forma, el ambiente de Staging siempre queda actualizado con la última versión aprobada del proyecto.