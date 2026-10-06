-- ============================================================================
-- Ingeniería - Perfil Jurídico (solo carga de certificaciones)
-- Fecha: 05-oct-2026
--
-- Crea el módulo de permisos "Ing - Certificaciones (Jurídico)" con idmodulo = 84
-- (debe coincidir con la constante ING_JURIDICO de Config/Config.php).
--
-- ANTES DE CORRER: verifica que el id 84 esté libre:
--     SELECT * FROM modulo WHERE idmodulo = 84;     -- debe regresar 0 filas
-- Si ya existe, usa otro id libre (ej. 85) y cambia AMBOS: este script y la
-- constante ING_JURIDICO en Config/Config.php.
--
-- Permisos del módulo:
--   r = ver la lista de configuraciones y sus certificaciones
--   u = guardar certificaciones / adjuntar archivos
--   (w y d no se usan en esta pantalla)
-- ============================================================================

INSERT INTO modulo (idmodulo, titulo, descripcion, status)
VALUES (84, 'Ing - Certificaciones (Jurídico)', 'Carga de certificaciones por configuración de vehículo (perfil Jurídico)', 1);

-- Administrador (rol 1) con acceso completo al nuevo módulo.
INSERT INTO permisos (rolid, moduloid, r, w, u, d)
SELECT 1, 84, 1, 0, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM permisos WHERE rolid = 1 AND moduloid = 84);

-- ============================================================================
-- DESPUÉS (desde la pantalla Roles del sistema, sin SQL):
--   1. Crear el rol "Jurídico".
--   2. En sus permisos marcar ÚNICAMENTE el módulo
--      "Ing - Certificaciones (Jurídico)" con Lectura (r) y Actualizar (u).
--   3. Asignar ese rol a los usuarios de Jurídico.
-- ============================================================================
