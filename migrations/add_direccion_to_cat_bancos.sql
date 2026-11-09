-- Agregar campo dirección a la tabla cat_bancos
-- Fecha: 2026-11-05
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE cat_bancos ADD COLUMN direccion VARCHAR(255) DEFAULT NULL COMMENT 'Dirección física del banco';

SET FOREIGN_KEY_CHECKS = 1;
