-- Agregar campo para identificar bancos agregados por usuarios
-- Usamos el campo estatus existente para identificar bancos activos
ALTER TABLE cat_bancos 
ADD COLUMN origen_registro ENUM('SISTEMA', 'USUARIO') DEFAULT 'SISTEMA';
