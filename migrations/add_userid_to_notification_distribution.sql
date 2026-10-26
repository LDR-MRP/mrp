-- Migración: Agregar campo userid a sys_notification_distribution
-- Fecha: 2026-10-19
-- Descripción: Permitir notificaciones directas a usuarios además de por rol

-- Paso 1: Si la tabla no existe, crearla con la nueva estructura
CREATE TABLE IF NOT EXISTS sys_notification_distribution (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rolid INT NULL,
    userid INT NULL,
    event_key VARCHAR(100) NOT NULL,
    plantaid INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rolid) REFERENCES rol(idrol) ON DELETE CASCADE,
    FOREIGN KEY (userid) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_distribution (rolid, userid, event_key, plantaid),
    INDEX idx_event_plant (event_key, plantaid, is_active),
    INDEX idx_userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Paso 2: Si la tabla ya existe, modificarla (agregar userid y restricciones)
-- Verificar si la columna userid ya existe
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sys_notification_distribution'
    AND COLUMN_NAME = 'userid'
);

-- Si la columna no existe, agregarla
SET @alter_sql = IF(@column_exists = 0,
    'ALTER TABLE sys_notification_distribution 
     ADD COLUMN userid INT NULL AFTER rolid,
     ADD FOREIGN KEY (userid) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
     ADD INDEX idx_userid (userid)',
    'SELECT "Columna userid ya existe" AS message'
);

PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 3: Modificar rolid para permitir NULL (si es NOT NULL)
SET @rolid_nullable = (
    SELECT IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sys_notification_distribution'
    AND COLUMN_NAME = 'rolid'
);

SET @alter_rolid = IF(@rolid_nullable = 'NO',
    'ALTER TABLE sys_notification_distribution MODIFY COLUMN rolid INT NULL',
    'SELECT "Columna rolid ya es NULL" AS message'
);

PREPARE stmt2 FROM @alter_rolid;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Paso 4: Agregar constraint UNIQUE si no existe
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sys_notification_distribution'
    AND CONSTRAINT_NAME = 'unique_distribution'
);

SET @add_constraint = IF(@constraint_exists = 0,
    'ALTER TABLE sys_notification_distribution 
     ADD UNIQUE KEY unique_distribution (rolid, userid, event_key, plantaid)',
    'SELECT "Constraint unique_distribution ya existe" AS message'
);

PREPARE stmt3 FROM @add_constraint;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
