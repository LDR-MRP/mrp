# Módulo: WMS (Inventarios, Almacenes y Movimientos)

Total de tablas en este módulo: **38**

---

### 📊 Tabla: `inv_recepcion_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetallerecepcion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`recepcionid`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`idrequisicionarticulo`** | `int` | NO | `MUL` | `` | `` | Enlace a la partida original de la requisici�n |
| **`inventarioid`** | `bigint unsigned` | NO | `` | `` | `` | ID del producto en wms_inventario |
| **`cantidad_recibida`** | `decimal(12,4)` | NO | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `inv_recepciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idrecepcion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`idcompra`** | `bigint unsigned` | NO | `MUL` | `` | `` | OC Origen |
| **`plantaid`** | `bigint unsigned` | NO | `MUL` | `` | `` | Planta donde se recibe f�sicamente |
| **`usuarioid`** | `bigint unsigned` | NO | `` | `` | `` | Almacenista responsable del conteo |
| **`num_remision`** | `varchar(50)` | NO | `` | `` | `` | Folio del documento f�sico del proveedor |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | Usuario que registr� la entrada |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `wms_almacenes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idalmacen`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_almacen`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`listaprecioid`** | `bigint` | NO | `` | `` | `` |  |
| **`cve_entrada`** | `int` | NO | `` | `` | `` |  |
| **`cve_salida`** | `int` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(40)` | NO | `` | `` | `` |  |
| **`direccion`** | `varchar(60)` | NO | `` | `` | `` |  |
| **`encargado`** | `varchar(60)` | NO | `` | `` | `` |  |
| **`telefono`** | `varchar(16)` | NO | `` | `` | `` |  |
| **`correo`** | `varchar(250)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_claves_alternas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idclavealterna`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cve_alterna`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`tipo`** | `varchar(1)` | SÍ | `` | `` | `` | C=Cliente\nP=proveedor\nI=Interna |

---

### 📊 Tabla: `wms_claves_productos_sustitutos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_clave_lista`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre_lista`** | `varchar(150)` | NO | `UNI` | `` | `` |  |
| **`activo`** | `tinyint` | NO | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `wms_claves_productos_sustitutos_det`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_detalle`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_clave_lista`** | `bigint` | NO | `` | `` | `` |  |
| **`idinventario`** | `bigint` | NO | `` | `` | `` |  |
| **`activo`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `wms_conceptos_mov`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idconcepmov`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_concep_mov`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`cpn`** | `varchar(1)` | NO | `` | `` | `` | C=cliente\nP=proveedor\nN=ninguno |
| **`tipo_movimiento`** | `varchar(1)` | NO | `` | `` | `` | E=Entrada\nS=Salida |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` | 0=Eliminada 1=inactiva 2=Activa |
| **`signo`** | `smallint` | NO | `` | `` | `` | 1=entrada\n-1=Salida |

---

### 📊 Tabla: `wms_descuentos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddescuento`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_descuento`** | `varchar(10)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_fotos_inventario`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idfotoinventario`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`foto`** | `varchar(255)` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `wms_impuestos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idimpuesto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_impuesto`** | `int` | NO | `UNI` | `` | `` | Clave esquema |
| **`descripcion`** | `varchar(40)` | SÍ | `` | `` | `` | Descripci�n |
| **`impuesto1`** | `double` | SÍ | `` | `` | `` | Impuesto 1 |
| **`imp1_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 1 aplica a {0,1} .: 0=Exento, 1 = Precio Base |
| **`impuesto2`** | `double` | SÍ | `` | `` | `` | Impuesto 2 |
| **`imp2_aplica`** | `int` | SÍ | `` | `` | `` | aplica a {0,1,2} .: 0= Excento, 1 = Precio Base , 2 =Acumulado 1 |
| **`impuesto3`** | `double` | SÍ | `` | `` | `` | Impuesto 3 |
| **`imp3_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 3 aplica a {0,1,2,3} .: 0= Exento, 1 = Precio Base , 2 =Acumulado 1, 2= Acumulado 3 |
| **`impuesto4`** | `double` | SÍ | `` | `` | `` | Impuesto\n4\n |
| **`imp4_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 4 aplica a {0,1,3,4} .: 0= Exento, 1 = Precio Base , 2 =Acumulado 1, 2= Acumulado 3, 4=Acumulado 3 |
| **`impuesto5`** | `double` | SÍ | `` | `` | `` | Impuesto 5 |
| **`imp5_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 5 aplica a {0,1,2,3,4,6,7} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4 |
| **`impuesto6`** | `double` | SÍ | `` | `` | `` | Impuesto 6\n |
| **`imp6_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 6 aplica a {0,1,2,3,4,6,7,8} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5 |
| **`impuesto7`** | `double` | SÍ | `` | `` | `` | Impuesto 7 |
| **`imp7_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 7 aplica a {0,1,2,3,4,6,7,8,9} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5, 9 = Acumulado6 |
| **`impuesto8`** | `double` | SÍ | `` | `` | `` | Impuesto 8 |
| **`imp8_aplica`** | `int` | SÍ | `` | `` | `` | Impuesto 8 aplica a {0,1,2,3,4,6,7,8,9,10} .: 0=Precio Base, 1 = Acumulado1, 2 = Acumulado2, 3 = Acumulado3, 4 = Exento, 6 = No aplica, 7 = Acumulado4, 8 = Acumulado5, 9 = Acumulado6, 10 = Acumulado7 |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `` | `` |  |
| **`estado`** | `varchar(1)` | SÍ | `` | `2` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_inventario`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idinventario`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_articulo`** | `varchar(50)` | NO | `UNI` | `` | `` |  |
| **`descripcion`** | `varchar(1000)` | NO | `` | `` | `` |  |
| **`notas`** | `varchar(250)` | SÍ | `` | `` | `` |  |
| **`lineaproductoid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`serie`** | `varchar(1)` | NO | `` | `` | `` | S=si\nN=NO |
| **`unidad_salida`** | `varchar(10)` | SÍ | `` | `` | `` |  |
| **`unidad_empaque`** | `decimal(10,6)` | NO | `` | `` | `` |  |
| **`ubicacion`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`idmarca`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`tiempo_surtido`** | `int` | NO | `` | `` | `` |  |
| **`ultimo_costo`** | `decimal(18,6)` | NO | `` | `` | `` |  |
| **`tipo_elemento`** | `varchar(1)` | NO | `` | `` | `` | K=Kit\nC=Componente\nP=Producto\nS=Servicio\nH=Herramienta |
| **`unidad_entrada`** | `varchar(10)` | NO | `` | `` | `` |  |
| **`factor_unidades`** | `decimal(10,6)` | NO | `` | `` | `` |  |
| **`lote`** | `varchar(1)` | SÍ | `` | `` | `` | S=Si\nN=No |
| **`pedimiento`** | `varchar(1)` | SÍ | `` | `` | `` | S=Si\nN=No |
| **`peso`** | `decimal(10,6)` | SÍ | `` | `` | `` |  |
| **`volumen`** | `decimal(10,6)` | SÍ | `` | `` | `` |  |
| **`stock_minimo`** | `double` | SÍ | `` | `` | `` |  |
| **`stock_maximo`** | `double` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `` | `` |  |
| **`estado`** | `varchar(1)` | SÍ | `` | `` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_inventario_fiscal`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idfiscal`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`clave_sat`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`desc_sat`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`clave_unidad_sat`** | `varchar(10)` | SÍ | `` | `` | `` |  |
| **`desc_unidad_sat`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`clave_fraccion_sat`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`desc_fraccion_sat`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`clave_aduana_sat`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`desc_aduana_sat`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `2` | `` |  |

---

### 📊 Tabla: `wms_inventario_impuestos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idinvimpuesto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`idimpuesto`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `wms_inventario_linea`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_inv_linea`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`sublineaproductoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `2` | `` |  |

---

### 📊 Tabla: `wms_inventario_moneda`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_inv_moneda`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`idmoneda`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_cambio`** | `decimal(18,6)` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | SÍ | `` | `2` | `` |  |

---

### 📊 Tabla: `wms_inventario_precios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_inv_precio`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`idprecio`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`precio`** | `decimal(10,2)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | SÍ | `` | `2` | `` |  |

---

### 📊 Tabla: `wms_inventario_proveedores`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_inv_proveedores`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `wms_kit_config`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idkitconfig`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `UNI` | `` | `` |  |
| **`precio`** | `decimal(10,5)` | SÍ | `` | `0.00000` | `` |  |
| **`descripcion`** | `text` | SÍ | `` | `` | `` |  |
| **`estado`** | `int` | SÍ | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `wms_kit_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idkitdetalle`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idkitconfig`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`producto_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `decimal(12,4)` | NO | `` | `1.0000` | `` |  |
| **`porcentaje`** | `decimal(5,2)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `wms_linea_producto`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idlineaproducto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_linea_producto`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_ltpd`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_ltpd`** | `int` | NO | `PRI` | `` | `auto_increment` | N�mero de Registro |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`lote`** | `varchar(12)` | SÍ | `` | `` | `` | Lote |
| **`pedimento`** | `varchar(21)` | SÍ | `` | `` | `` | Pedimento |
| **`almacenid`** | `bigint` | SÍ | `` | `` | `` | Clave de almac�n |
| **`fecha_caducidad`** | `date` | SÍ | `` | `` | `` | Fecha de caducidad |
| **`fecha_aduana`** | `datetime` | SÍ | `` | `` | `` | Fecha de aduana |
| **`fecha_ult_mov`** | `datetime` | SÍ | `` | `` | `` | Fecha de �ltimo movimiento |
| **`nombre_aduana`** | `varchar(40)` | SÍ | `` | `` | `` | Aduana |
| **`cantidad`** | `double` | SÍ | `` | `` | `` | Cantidad {0.0 ..} |
| **`cve_observacion`** | `int` | SÍ | `` | `` | `` | Clave de observaciones |
| **`ciudad`** | `varchar(60)` | SÍ | `` | `` | `` | Ciudad |
| **`frontera`** | `varchar(60)` | SÍ | `` | `` | `` | Frontera |
| **`fecha_produccion_lote`** | `datetime` | SÍ | `` | `` | `` | fecha de producci�n |
| **`gln`** | `varchar(13)` | SÍ | `` | `` | `` | N�mero de Localizaci�n Global |
| **`pedimento_SAT`** | `varchar(21)` | SÍ | `` | `` | `` | Pedimento SAT |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `varchar(1)` | SÍ | `` | `2` | `` | \t0=Eliminada 1=inactiva 2=Activa\t |

---

### 📊 Tabla: `wms_marcas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`codigo`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`fecha_registro`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | NO | `` | `` | `` | \t0=Eliminado, 1=Inactivo, 2=Activo |

---

### 📊 Tabla: `wms_moneda`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmoneda`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`descripcion`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`simbolo`** | `varchar(4)` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`cve_moneda`** | `varchar(4)` | SÍ | `` | `` | `` | Moneda para el SAT |
| **`estado`** | `tinyint(1)` | SÍ | `` | `` | `` | \t0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_movimientos_almacenes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmovimientoalmacen`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`folio`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`almacen_origenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`almacen_destinoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`referencia`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`fecha`** | `datetime` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | SÍ | `` | `1` | `` |  |

---

### 📊 Tabla: `wms_movimientos_almacenes_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetalle`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`movimientoalmacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `decimal(18,4)` | NO | `` | `` | `` |  |
| **`costo_unitario`** | `decimal(18,4)` | SÍ | `` | `0.0000` | `` |  |

---

### 📊 Tabla: `wms_movimientos_inventario`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmovinventario`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`numero_movimiento`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`concepmovid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`referencia`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`cantidad`** | `double` | SÍ | `` | `` | `` |  |
| **`costo_cantidad`** | `double` | SÍ | `` | `` | `` |  |
| **`precio`** | `double` | SÍ | `` | `` | `` |  |
| **`costo`** | `double` | SÍ | `` | `` | `` |  |
| **`existencia`** | `double` | SÍ | `` | `` | `` |  |
| **`signo`** | `int` | SÍ | `` | `` | `` | 1=Entrada\n-1=Salida |
| **`fecha_movimiento`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `wms_multialmacen`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmultialmacen`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`control_almacen`** | `varchar(10)` | SÍ | `` | `` | `` |  |
| **`existencia`** | `double` | SÍ | `` | `` | `` |  |
| **`stock_minimo`** | `double` | SÍ | `` | `` | `` |  |
| **`stock_maximo`** | `double` | SÍ | `` | `` | `` |  |
| **`compras_x_recibir`** | `double` | SÍ | `` | `` | `` |  |
| **`pendiente_surtir`** | `double` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `wms_numeros_series`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_numeros_serie`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`numero_serie`** | `varchar(17)` | NO | `UNI` | `` | `` |  |
| **`referencia`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`costo`** | `decimal(10,6)` | NO | `` | `` | `` |  |
| **`fecha`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `varchar(1)` | NO | `` | `` | `` | 1= Disponible, 2=No disponible |
| **`tipo_generacion`** | `varchar(10)` | SÍ | `` | `orden` | `` |  |

---

### 📊 Tabla: `wms_precios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idprecio`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_precio`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`impuestoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_proveedor_articulos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idconvenio`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`idinventario`** | `bigint` | NO | `MUL` | `` | `` | FK hacia wms_inventario |
| **`precio_referencia`** | `decimal(18,6)` | NO | `` | `` | `` | �ltimo precio negociado |
| **`id_moneda`** | `char(3)` | SÍ | `` | `MXN` | `` |  |
| **`fecha_acuerdo`** | `date` | NO | `` | `` | `` |  |
| **`comentarios`** | `text` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `wms_recepcion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idrecepcion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`compraid`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`folio`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`fecha_recepcion`** | `datetime` | NO | `` | `` | `` |  |
| **`usuarioid`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`estatus`** | `enum('abierta','parcial','cerrada')` | SÍ | `` | `abierta` | `` |  |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `wms_recepcion_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetalle`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`recepcionid`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`inventarioid`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`codigo_barras`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`lote`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`cantidad_esperada`** | `decimal(12,2)` | NO | `` | `0.00` | `` |  |
| **`cantidad_recibida`** | `decimal(12,2)` | NO | `` | `0.00` | `` |  |
| **`escaneado`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `wms_sedes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsede`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_sede`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_sublinea_producto`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsublineaproducto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`lineaproductoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cve_sublinea_producto`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` | \t0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_tipo_cambio_moneda`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idtipocambio`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`monedaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_cambio`** | `decimal(10,6)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `wms_ubicaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idubicaciones`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`zonaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`pasillo`** | `int` | NO | `` | `` | `` |  |
| **`seccion`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`nivel`** | `int` | NO | `` | `` | `` |  |
| **`lugar`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminada 1=Asignada\n2=Disponible |

---

### 📊 Tabla: `wms_ubicaciones_asignadas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idubicacionasignada`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`ubicacionesid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`cantidad`** | `decimal(10,2)` | SÍ | `` | `0.00` | `` |  |

---

### 📊 Tabla: `wms_zonas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idzona`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`sedeid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cve_zona`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`descripcion`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | \t0=Eliminada 1=inactiva 2=Activa |

---

