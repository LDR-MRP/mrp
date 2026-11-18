ALTER TABLE prv_cat_proveedores DROP index uk_empresa_rfc_activo;

ALTER TABLE prv_cat_proveedores MODIFY COLUMN rfc_activo VARCHAR(13) GENERATED ALWAYS AS(IF(deleted_at IS NULL AND rfc <> 'XEXX010101000' AND rfc <> 'XAXX010101000', rfc,null)) STORED;

ALTER TABLE prv_cat_proveedores ADD UNIQUE KEY `uk_empresa_rfc_activo` (`id_empresa`,`rfc_activo`);


ALTER TABLE `u546825723_dbmrp`.`prv_det_direcciones` 
CHANGE COLUMN `cp` `cp` CHAR(8) NULL DEFAULT NULL ;