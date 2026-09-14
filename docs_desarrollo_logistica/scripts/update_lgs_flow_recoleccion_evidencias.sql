-- ------------------------------------------------------------------------------
-- MIGRACIÓN: Control de Flujo de Despacho, Evidencias y Checklists de Traslado
-- ------------------------------------------------------------------------------

-- 1. Modificar tabla lgs_envios para agregar fecha de recolección confirmada y actualizar comentarios de estado
ALTER TABLE `lgs_envios` 
    ADD COLUMN `fecha_confirmada_recoleccion` DATE NULL AFTER `fecha_tentativa_llegada`,
    MODIFY COLUMN `id_estado` TINYINT DEFAULT 1 COMMENT '1=Creado 2=En Revisión 3=Aprobado 4=Regresado 5=Confirmado Recolección 6=En Tránsito 7=Entregado 8=Cancelado';

-- 2. Modificar tabla lgs_envios_vins para incluir el estado físico de la unidad
ALTER TABLE `lgs_envios_vins` 
    ADD COLUMN `estado_unidad_fisico` VARCHAR(50) DEFAULT 'EN_PATIO' COMMENT 'EN_PATIO, EN_ENTREGAS, EN_RUTA, ENTREGADO' AFTER `id_estado`;

-- 3. Crear tabla para los checklists de inspección
CREATE TABLE IF NOT EXISTS `lgs_trasladistas_checklist` (
    `id_checklist` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_envio` BIGINT NOT NULL,
    `id_unidad` BIGINT NOT NULL,
    `tipo_checklist` ENUM('entrada_trasladista', 'salida_planta', 'entrega_destino') NOT NULL,
    `vin_escaneado` VARCHAR(50) NOT NULL,
    `usuario_registro_id` INT NOT NULL COMMENT 'FK a usuarios o prv_cat_usuarios',
    `comentarios` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_envio`) REFERENCES `lgs_envios`(`id_envio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Crear tabla para almacenar las evidencias fotográficas de los checklists
CREATE TABLE IF NOT EXISTS `lgs_checklist_evidencias` (
    `id_evidencia` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_checklist` BIGINT NOT NULL,
    `tipo_foto` VARCHAR(50) NOT NULL COMMENT 'frente, atras, lateral_izq, lateral_der, odometro, dano_especifico, entrega_qr, firma',
    `ruta_archivo` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_checklist`) REFERENCES `lgs_trasladistas_checklist`(`id_checklist`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
