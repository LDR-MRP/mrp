# Módulo: Catálogos Maestros, SAT, Usuarios y Sistema

Total de tablas en este módulo: **26**

---

### 📊 Tabla: `cat_anio_vin`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_cat_anio_vin`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`anio`** | `year` | NO | `` | `` | `` |  |
| **`codigo`** | `char(1)` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `cat_bancos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_banco`** | `char(3)` | NO | `PRI` | `` | `` |  |
| **`nombre_corto`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`razon_social`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`estatus`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_codigos_postales`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_cp`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`cp`** | `char(5)` | NO | `` | `` | `` | C�digo Postal asentamiento (d_codigo) |
| **`asentamiento`** | `varchar(150)` | SÍ | `` | `` | `` | Nombre asentamiento (d_asenta) |
| **`tipo_asentamiento`** | `varchar(100)` | SÍ | `` | `` | `` | Tipo de asentamiento (Cat�logo SEPOMEX) (d_tipo_asenta) |
| **`municipio`** | `varchar(150)` | SÍ | `` | `` | `` | Nombre Municipio (INEGI, Marzo 2013) (D_mnpio) |
| **`estado`** | `varchar(50)` | SÍ | `` | `` | `` | Nombre Entidad (INEGI, Marzo 2013) (c_estado) |
| **`ciudad`** | `varchar(150)` | SÍ | `` | `` | `` | Nombre Ciudad (Cat�logo SEPOMEX) (d_ciudad) |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `cat_condiciones_pago`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_condicion`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`dias_credito`** | `int` | SÍ | `` | `0` | `` |  |
| **`estatus`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_cuentas_contables`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_cuenta_contable`** | `varchar(30)` | NO | `PRI` | `` | `` |  |
| **`nombre_cuenta`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tipo_cuenta`** | `enum('Acreedor','Deudor')` | NO | `` | `` | `` |  |
| **`nivel`** | `int` | SÍ | `` | `1` | `` |  |
| **`estatus`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_estados_mx`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_estado`** | `char(3)` | NO | `PRI` | `` | `` |  |
| **`nombre`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`id_pais`** | `char(3)` | SÍ | `MUL` | `MEX` | `` |  |

---

### 📊 Tabla: `cat_metodos_pago`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmetodopago`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`es_pago_inmediato`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`is_active`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | Usuario que cre� |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` | �ltimo usuario en modificarla |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` | Usuario que la cancel�/elimin� |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `cat_modelos_vin`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_cat_modelo_vin`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`modelo`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`id_fabricante`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`id_tipo_vehiculo`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`peso_bruto_kg`** | `decimal(10,2)` | SÍ | `` | `` | `` |  |
| **`id_tipo_motor`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`potencia_hp`** | `int` | SÍ | `` | `` | `` |  |
| **`distancia_ejes`** | `decimal(10,2)` | SÍ | `` | `` | `` |  |
| **`id_cat_anio_vin`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`id_planta`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`vin_base`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cat_paises`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_pais`** | `char(3)` | NO | `PRI` | `` | `` |  |
| **`nombre`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`estatus`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_vin_fabricantes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_fabricante`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`wmi`** | `char(3)` | NO | `UNI` | `` | `` |  |
| **`fabricante`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_vin_plantas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_planta`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planta`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`caracter`** | `char(1)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_vin_tipo_motor`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_tipo_motor`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`descripcion`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`caracter`** | `char(1)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `cat_vin_tipo_vehiculo`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_tipo_vehiculo`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`categoria`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`descripcion`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`caracter`** | `char(1)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `log_audit`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`resourceid`** | `int` | NO | `` | `` | `` |  |
| **`usuarioid`** | `int` | NO | `` | `` | `` |  |
| **`nombre_tabla`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`accion`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`comentario`** | `text` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `login_logs`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idlog`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idusuario`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`evento`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`ip`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`detalle`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `modulo`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmodulo`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`titulo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`descripcion`** | `text` | NO | `` | `` | `` |  |
| **`status`** | `int` | NO | `` | `1` | `` |  |

---

### 📊 Tabla: `permisos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idpermiso`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`rolid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`moduloid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`r`** | `int` | NO | `` | `0` | `` | Ver |
| **`w`** | `int` | NO | `` | `0` | `` | Crear |
| **`u`** | `int` | NO | `` | `0` | `` | Actualizar |
| **`d`** | `int` | NO | `` | `0` | `` | Eliminar |

---

### 📊 Tabla: `personal_access_tokens`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`tokenable_type`** | `varchar(255)` | NO | `MUL` | `` | `` |  |
| **`tokenable_id`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`name`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`token`** | `varchar(64)` | NO | `UNI` | `` | `` |  |
| **`abilities`** | `text` | SÍ | `` | `` | `` |  |
| **`last_used_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`expires_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `rol`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idrol`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombrerol`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`descripcion`** | `text` | NO | `` | `` | `` |  |
| **`status`** | `int` | NO | `` | `1` | `` |  |

---

### 📊 Tabla: `sat_cat_forma_pago`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_forma_pago`** | `char(2)` | NO | `PRI` | `` | `` |  |
| **`descripcion`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`es_bancarizado`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `sat_cat_metodo_pago`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_metodo_pago`** | `char(3)` | NO | `PRI` | `` | `` |  |
| **`descripcion`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `sat_cat_regimen_fiscal`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_regimen_fiscal`** | `int` | NO | `PRI` | `` | `` |  |
| **`descripcion`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`aplica_fisica`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`aplica_moral`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `sat_cat_tipo_persona`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_tipo_persona`** | `char(1)` | NO | `PRI` | `` | `` |  |
| **`descripcion`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `sat_cat_uso_cfdi`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_uso_cfdi`** | `char(4)` | NO | `PRI` | `` | `` |  |
| **`descripcion`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`aplica_fisica`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`aplica_moral`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `sys_notification_distribution`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `int unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`event_key`** | `varchar(50)` | NO | `MUL` | `` | `` | Ej: supplier_ready, po_received |
| **`rolid`** | `bigint` | NO | `MUL` | `` | `` | FK a tabla rol |
| **`plantaid`** | `bigint unsigned` | SÍ | `` | `` | `` | NULL significa que aplica a todas las plantas (Global) |
| **`is_active`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `usuarios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idusuario`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`numcolaborador`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`nombres`** | `varchar(80)` | NO | `` | `` | `` |  |
| **`apellidos`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`telefono`** | `bigint` | NO | `` | `` | `` |  |
| **`email_user`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`password`** | `varchar(75)` | NO | `` | `` | `` |  |
| **`nit`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`nombrefiscal`** | `varchar(80)` | SÍ | `` | `` | `` |  |
| **`direccionfiscal`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`token`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`rolid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `` | `` | `` |  |
| **`datecreated`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`status`** | `int` | NO | `` | `1` | `` |  |
| **`cambio_password`** | `tinyint(1)` | NO | `` | `0` | `` | 0:NO 1:SI |
| **`avatar`** | `varchar(255)` | NO | `` | `avatar_default.svg` | `` |  |
| **`avatar_file`** | `varchar(255)` | NO | `` | `avatar_default.svg` | `` |  |
| **`avatar_seed`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`avatar_gender`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`avatar_options`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`avatar_updated_at`** | `varchar(255)` | NO | `` | `` | `` |  |

---

