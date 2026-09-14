# Módulo: Clientes, Sucursales y Usuarios de Portal

Total de tablas en este módulo: **20**

---

### 📊 Tabla: `cli_clientes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcliente`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idtipo_cliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`idregimen_fiscal`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_persona`** | `enum('FISICA','MORAL')` | NO | `` | `` | `` |  |
| **`codigo_cliente`** | `varchar(50)` | NO | `UNI` | `` | `` |  |
| **`razon_social`** | `varchar(200)` | NO | `` | `` | `` |  |
| **`nombre_comercial`** | `varchar(200)` | SÍ | `` | `` | `` |  |
| **`correo`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`sitio_web`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_alta`** | `date` | NO | `` | `` | `` |  |
| **`telefono`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`celular`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` | 0=Eliminado 1=Inactivo 2=Activo |
| **`clave_distribuidor`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`zona_comercial`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`territorio`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`responsable_comercial`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`requiere_acceso_portal`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`correo_acceso`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`numero_empleado`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`departamento`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`centro_costos`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`jefe_inmediato`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`correo_corporativo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`origen_cliente`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`ejecutivo_asignado`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`segmento_mercado`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`dependencia`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`unidad_administrativa`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`nivel_gobierno`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`partida_presupuestal`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tipo_contratacion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_bancos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idbanco`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`banco`** | `varchar(120)` | SÍ | `` | `` | `` |  |
| **`titular_cuenta`** | `varchar(200)` | SÍ | `` | `` | `` |  |
| **`numero_cuenta`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`clabe`** | `varchar(18)` | SÍ | `` | `` | `` |  |
| **`moneda_cuenta`** | `varchar(10)` | SÍ | `` | `MXN` | `` |  |
| **`referencia_bancaria`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_comercial`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcomercial`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `UNI` | `` | `` |  |
| **`lista_precio`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`moneda`** | `varchar(10)` | NO | `` | `MXN` | `` |  |
| **`forma_pago`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`limite_credito`** | `decimal(15,2)` | NO | `` | `0.00` | `` |  |
| **`dias_credito`** | `int` | NO | `` | `0` | `` |  |
| **`descuento_autorizado`** | `decimal(5,2)` | NO | `` | `0.00` | `` |  |
| **`ejecutivo_cuenta`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`canal_venta`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`clasificacion_comercial`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`observaciones_comerciales`** | `text` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_contactos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcontacto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`correo`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`telefono`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`tipo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`notificar`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`usuarioid`** | `bigint` | NO | `` | `` | `` |  |
| **`puesto`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_documentos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddocumento`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_documento`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`nombre_original`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`nombre_archivo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`ruta_archivo`** | `varchar(500)` | NO | `` | `` | `` |  |
| **`mime_type`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`tamano_bytes`** | `bigint` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_fiscal`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idfiscal`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `UNI` | `` | `` |  |
| **`rfc`** | `varchar(13)` | NO | `UNI` | `` | `` |  |
| **`curp`** | `varchar(18)` | SÍ | `` | `` | `` |  |
| **`regimen_fiscal`** | `varchar(10)` | NO | `` | `` | `` |  |
| **`uso_cfdi`** | `varchar(10)` | NO | `` | `` | `` |  |
| **`codigo_postal_fiscal`** | `varchar(5)` | NO | `` | `` | `` |  |
| **`correo_facturacion`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`requiere_factura`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_clientes_sucursales`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsucursal`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_sucursal`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`telefono`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`correo`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`responsable`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`calle`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`numero_exterior`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`numero_interior`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`colonia`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`codigo_postal`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`municipio`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado_republica`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`pais`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |
| **`usuarioid`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_departamentos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`descripcion`** | `text` | SÍ | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminado, 1=Inactivo, 2=Activo |

---

### 📊 Tabla: `cli_direcciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddireccion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_direccion`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`calle`** | `varchar(180)` | NO | `` | `` | `` |  |
| **`numero_exterior`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`numero_interior`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`colonia`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`codigo_postal`** | `varchar(5)` | NO | `` | `` | `` |  |
| **`municipio`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`estado_republica`** | `varchar(120)` | NO | `` | `` | `` |  |
| **`pais`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`referencias`** | `text` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `cli_estados`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`pais_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`region_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`abreviatura`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_municipios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`estado_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_paises`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`codigo_iso`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_regimenes_fiscales`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`c_regimen_fiscal`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`persona_fisica`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`persona_moral`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_regiones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `bigint` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_sucursales_direcciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddireccion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idsucursal`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`pais_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estado_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`municipio_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`calle`** | `text` | NO | `` | `` | `` |  |
| **`numero_exterior`** | `int` | NO | `` | `` | `` |  |
| **`numero_interior`** | `int` | NO | `` | `` | `` |  |
| **`codigo_postal`** | `varchar(10)` | NO | `` | `` | `` |  |
| **`referencia`** | `text` | NO | `` | `` | `` |  |
| **`es_principal`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `cli_tipos_cliente`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `cli_usuarios_acceso`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idusuario_acceso`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_usuario`** | `varchar(100)` | SÍ | `UNI` | `` | `` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`apellido`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`correo`** | `varchar(50)` | NO | `UNI` | `` | `` |  |
| **`password_hash`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`url_portal`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`telefono`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`ultimo_login`** | `datetime` | NO | `` | `` | `` |  |
| **`ultimo_envio_accesos`** | `datetime` | SÍ | `` | `` | `` |  |
| **`token_recuperacion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`token_recuperacion_expira`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`doble_autenticacion`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`requiere_cambio_password`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`fecha_cambio_password`** | `datetime` | SÍ | `` | `` | `` |  |
| **`intentos_fallidos`** | `int` | NO | `` | `0` | `` |  |
| **`bloqueado_hasta`** | `datetime` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `MUL` | `2` | `` |  |
| **`created_by`** | `bigint` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_actualizacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `cli_usuarios_acceso_envios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idenvio`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idusuario_acceso`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`idcliente`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`correo_destino`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`tipo_envio`** | `varchar(50)` | NO | `` | `` | `` | CREDENCIALES, REENVIO_CREDENCIALES, PIN_2FA, RECUPERACION_PASSWORD |
| **`asunto`** | `varchar(200)` | SÍ | `` | `` | `` |  |
| **`resultado`** | `varchar(20)` | NO | `` | `` | `` | ENVIADO, FALLIDO |
| **`detalle`** | `text` | SÍ | `` | `` | `` |  |
| **`enviado_por`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_envio`** | `datetime` | NO | `MUL` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `cli_usuarios_acceso_logs`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idlog`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idusuario_acceso`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`idcliente`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`tipo_evento`** | `varchar(50)` | NO | `MUL` | `` | `` | LOGIN_EXITOSO, LOGIN_FALLIDO, LOGOUT, BLOQUEADO, PIN_ENVIADO, PIN_VALIDADO, PIN_FALLIDO, PASSWORD_CAMBIADA |
| **`resultado`** | `varchar(20)` | NO | `MUL` | `` | `` | EXITOSO, FALLIDO, BLOQUEADO, INFORMATIVO |
| **`correo_intento`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`direccion_ip`** | `varchar(45)` | SÍ | `` | `` | `` |  |
| **`dispositivo`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`tipo_dispositivo`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`navegador`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`version_navegador`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`sistema_operativo`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`ubicacion_aproximada`** | `varchar(200)` | SÍ | `` | `` | `` |  |
| **`id_sesion`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`user_agent`** | `text` | SÍ | `` | `` | `` |  |
| **`motivo`** | `text` | SÍ | `` | `` | `` |  |
| **`fecha_evento`** | `datetime` | NO | `MUL` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `cli_usuarios_acceso_pines`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idpin`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idusuario_acceso`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`codigo_hash`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`challenge`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_generacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_expiracion`** | `datetime` | NO | `MUL` | `` | `` |  |
| **`fecha_validacion`** | `datetime` | SÍ | `` | `` | `` |  |
| **`intentos`** | `int` | NO | `` | `0` | `` |  |
| **`max_intentos`** | `int` | NO | `` | `5` | `` |  |
| **`utilizado`** | `tinyint(1)` | NO | `MUL` | `0` | `` |  |
| **`direccion_ip`** | `varchar(45)` | SÍ | `` | `` | `` |  |
| **`id_sesion`** | `varchar(150)` | SÍ | `` | `` | `` |  |

---

