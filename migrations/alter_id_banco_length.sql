-- Migración para ampliar el campo id_banco en prv_det_cuentas_bancarias
-- El campo original era VARCHAR(5) pero el sistema de IDs X0000N puede generar hasta 6 caracteres
-- Se amplía a VARCHAR(10) para tener margen suficiente

USE u546825723_dbmrp;

ALTER TABLE prv_det_cuentas_bancarias
MODIFY COLUMN id_banco VARCHAR(10) NOT NULL;
