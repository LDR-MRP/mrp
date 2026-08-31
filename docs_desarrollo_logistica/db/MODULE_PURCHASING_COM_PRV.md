# Módulo: Compras, Requisiciones y Proveedores

Total de tablas en este módulo: **18**

---

### 📊 Tabla: `com_ordenes_compra`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcompra`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`requisicionid`** | `int` | NO | `MUL` | `` | `` | ID de la requisici�n que origin� esta compra |
| **`proveedorid`** | `bigint unsigned` | NO | `MUL` | `` | `` | Proveedor seleccionado para esta OC |
| **`plantaid`** | `bigint` | SÍ | `` | `` | `` | Para qu� planta |
| **`almacenid`** | `bigint unsigned` | NO | `` | `` | `` | Almac�n donde se recibir� la mercanc�a |
| **`estatus`** | `enum('emitida','en_transito','recibida','recibida_parcial','cerrada','cancelada')` | SÍ | `MUL` | `emitida` | `` | Estado actual de la OC |
| **`moneda`** | `varchar(3)` | SÍ | `` | `MXN` | `` | C�digo ISO de la moneda |
| **`tipo_cambio`** | `decimal(15,6)` | SÍ | `` | `1.000000` | `` | Tipo de cambio al momento de emitir |
| **`subtotal`** | `decimal(18,6)` | NO | `` | `0.000000` | `` |  |
| **`iva`** | `decimal(18,6)` | NO | `` | `0.000000` | `` |  |
| **`total`** | `decimal(18,6)` | NO | `` | `0.000000` | `` |  |
| **`observaciones`** | `text` | SÍ | `` | `` | `` | Condiciones comerciales o notas para el proveedor |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | Usuario que gener� la OC |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` | �ltimo usuario en modificarla |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` | Usuario que la cancel�/elimin� |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `com_ordenes_compra_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetalle`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`compraid`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`idrequisicionarticulo`** | `int` | NO | `MUL` | `` | `` | Enlace a la partida exacta de la requisici�n origen |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` | El producto/servicio real |
| **`tipo_elemento`** | `char(1)` | NO | `` | `P` | `` | P=Producto, S=Servicio, H=Herramienta, K=Kit |
| **`cantidad`** | `decimal(12,4)` | NO | `` | `` | `` | Cantidad real a comprar a este proveedor |
| **`costo_unitario`** | `decimal(18,6)` | NO | `` | `` | `` | Precio real negociado con el proveedor |
| **`porcentaje_descuento`** | `decimal(5,2)` | SÍ | `` | `0.00` | `` |  |
| **`descuento_partida`** | `decimal(18,6)` | SÍ | `` | `0.000000` | `` | Monto de descuento aplicado a esta l�nea |
| **`impuesto_partida`** | `decimal(18,6)` | SÍ | `` | `0.000000` | `` | Monto de IVA u otros impuestos de esta l�nea |
| **`subtotal_partida`** | `decimal(18,6)` | NO | `` | `` | `` | (cantidad * costo_unitario) - descuento |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | Usuario que gener� la orden de compra |
| **`updated_by`** | `bigint` | SÍ | `` | `` | `` | �ltimo usuario en modificarla |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `com_requisicion_cotizaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcotizacion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`idrequisicionarticulo`** | `int` | NO | `MUL` | `` | `` |  |
| **`src_evento_sourcing_id`** | `bigint unsigned` | SÍ | `MUL` | `` | `` |  |
| **`id_proveedor`** | `bigint unsigned` | SÍ | `MUL` | `` | `` |  |
| **`tipo_fuente`** | `enum('REGISTRADO','PROSPECTO','RETAIL')` | NO | `` | `REGISTRADO` | `` |  |
| **`nombre_prospecto`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`comentarios_comprador`** | `text` | SÍ | `` | `` | `` |  |
| **`specs_particulares_proveedor`** | `text` | SÍ | `` | `` | `` |  |
| **`moneda`** | `char(3)` | SÍ | `` | `MXN` | `` |  |
| **`tipo_cambio`** | `decimal(15,6)` | SÍ | `` | `1.000000` | `` |  |
| **`precio_unitario`** | `decimal(18,6)` | NO | `` | `` | `` |  |
| **`iva_inc`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`precio_base_mxn`** | `decimal(18,6)` | NO | `` | `0.000000` | `` | Subtotal normalizado en MXN |
| **`es_ganadora`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`pago_inmediato`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`estatus_cotizacion`** | `enum('BORRADOR','ENVIADA','GANADORA','DESCARTADA')` | SÍ | `` | `BORRADOR` | `` |  |
| **`url_pdf_cotizacion`** | `text` | SÍ | `` | `` | `` |  |
| **`url_foto_producto`** | `text` | SÍ | `` | `` | `` |  |
| **`url_referencia`** | `text` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`adjudicado_por`** | `decimal(18,6) unsigned` | SÍ | `` | `0.000000` | `` | Subtotal normalizado en MXN |
| **`id_orden_compra_final`** | `bigint unsigned` | SÍ | `MUL` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `com_requisicion_items_nuevos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iditemnuevo`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`idrequisicionarticulo`** | `int` | NO | `UNI` | `` | `` | FK a la partida de la requisicion |
| **`justificacion_proyecto`** | `text` | SÍ | `` | `` | `` |  |
| **`categoria`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`descripcion_sourcing`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`especificaciones_tecnicas`** | `text` | SÍ | `` | `` | `` |  |
| **`dimensiones_principales`** | `text` | SÍ | `` | `` | `` |  |
| **`normas_requeridas`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`volumen_anual`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`precio_objetivo`** | `decimal(18,6)` | SÍ | `` | `0.000000` | `` |  |
| **`fecha_inicio_negociacion`** | `date` | SÍ | `` | `` | `` |  |
| **`fecha_limite_acuerdo`** | `date` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | �ltimo usuario en modificarla |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` | �ltimo usuario en modificarla |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `com_requisiciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idrequisicion`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`folio`** | `varchar(20)` | SÍ | `UNI` | `` | `` |  |
| **`id_empresa`** | `int` | SÍ | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `` | `` | `` | Qui�n solicita |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`departamentoid`** | `bigint unsigned` | SÍ | `` | `` | `` | Para qu� �rea |
| **`centro_costo`** | `varchar(45)` | SÍ | `` | `` | `` |  |
| **`titulo`** | `varchar(255)` | NO | `` | `` | `` | De qu� se trata |
| **`tipo_requisicion`** | `enum('standard','directa')` | SÍ | `` | `standard` | `` |  |
| **`idmetodopago`** | `int` | SÍ | `MUL` | `` | `` |  |
| **`fecha_requerida`** | `date` | SÍ | `` | `` | `` | Para cu�ndo |
| **`prioridad`** | `enum('baja','media','alta','critica')` | SÍ | `` | `media` | `` |  |
| **`estatus`** | `enum('borrador','pendiente','aprobada','rechazada','en compra','cancelada','finalizada','eliminada')` | SÍ | `` | `pendiente` | `` |  |
| **`monto_estimado`** | `decimal(18,6)` | SÍ | `` | `0.000000` | `` | Monto estimado de referencia para presupuesto |
| **`justificacion`** | `text` | SÍ | `` | `` | `` | Justificaci�n de la compra |
| **`url_referencia`** | `text` | SÍ | `` | `` | `` | Link de Amazon/ML |
| **`modified_by`** | `bigint` | SÍ | `` | `` | `` | Qui�n autoriz� (referencia a users) |
| **`modified_at`** | `datetime` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `com_requisiciones_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idrequisicionarticulo`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`requisicionid`** | `int` | NO | `` | `` | `` |  |
| **`src_evento_sourcing_id`** | `bigint unsigned` | SÍ | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`id_proveedor`** | `bigint unsigned` | SÍ | `MUL` | `` | `` |  |
| **`cantidad`** | `decimal(12,4)` | NO | `` | `` | `` | Usamos decimal por si hay medidas como litros o kilos |
| **`precio_unitario_estimado`** | `decimal(18,6)` | SÍ | `` | `0.000000` | `` | Precio de referencia para presupuesto |
| **`notas`** | `varchar(255)` | SÍ | `` | `` | `` | Notas espec�ficas del item (ej: 'Color negro') |
| **`created_by`** | `bigint unsigned` | NO | `` | `` | `` | Usuario que gener� la partida |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` | �ltimo usuario en modificarla |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `cxp_tra_facturas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `` | `` | `` | FK a tu tabla maestra de proveedores (wms_proveedores) |
| **`id_compra`** | `bigint unsigned` | NO | `MUL` | `` | `` | FK a tu tabla de ordenes de compra (com_ordenes_compra) |
| **`serie_folio`** | `varchar(50)` | SÍ | `` | `` | `` | Serie y Folio de la factura (Ej: FAC_A-104) |
| **`uuid`** | `varchar(36)` | NO | `UNI` | `` | `` | UUID de timbre fiscal SAT (36 caracteres) |
| **`monto_total`** | `decimal(12,4)` | NO | `` | `` | `` | Monto total neto validado contra el XML |
| **`fecha_vencimiento`** | `date` | SÍ | `MUL` | `` | `` |  |
| **`url_xml`** | `varchar(255)` | NO | `` | `` | `` | Path relativo en el storage del XML |
| **`url_pdf`** | `varchar(255)` | NO | `` | `` | `` | Path relativo en el storage del PDF |
| **`estatus_validacion`** | `tinyint` | SÍ | `MUL` | `0` | `` | 0: Pendiente, 1: Validada (CxP), 2: Rechazada |
| **`estatus_pago`** | `enum('PENDIENTE','PROGRAMADO','PAGADO')` | SÍ | `MUL` | `PENDIENTE` | `` |  |
| **`motivo_rechazo`** | `text` | SÍ | `` | `` | `` | Nota explicativa si falla la auditor�a manual |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` | Soporte SoftDeletes Laravel |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` | ID de prv_cat_usuarios (proveedor) que la subi� |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` | ID del empleado interno que aprob�/rechaz� |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_cat_proveedores`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_proveedor`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_empresa`** | `int` | NO | `MUL` | `1` | `` |  |
| **`id_planta`** | `int` | SÍ | `` | `` | `` |  |
| **`rfc`** | `varchar(13)` | NO | `MUL` | `` | `` |  |
| **`rfc_activo`** | `varchar(13)` | SÍ | `` | `` | `STORED GENERATED` |  |
| **`razon_social`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`nombre_comercial`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`id_tipo_persona`** | `char(1)` | SÍ | `` | `` | `` | F: Persona F�sica, M: Persona Moral |
| **`id_regimen_fiscal`** | `int` | SÍ | `MUL` | `` | `` |  |
| **`tipo`** | `enum('Interno','Externo')` | SÍ | `` | `Externo` | `` |  |
| **`origen`** | `enum('Nacional','Extranjero')` | SÍ | `` | `Nacional` | `` |  |
| **`web`** | `varchar(150)` | SÍ | `` | `https://` | `` |  |
| **`estatus_onboarding`** | `enum('Prospecto','En Revision','Aprobado','Rechazado')` | SÍ | `MUL` | `Prospecto` | `` |  |
| **`estatus_operativo`** | `tinyint` | SÍ | `` | `0` | `` | 0: Inactivo/Bloqueado, 1: Activo/Operativo |
| **`is_retail`** | `tinyint` | SÍ | `` | `0` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_cat_usuarios`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`proveedor_id`** | `int` | NO | `` | `` | `` | FK a tu tabla maestra de proveedores (ej. wms_proveedores) |
| **`email`** | `varchar(150)` | NO | `UNI` | `` | `` |  |
| **`password`** | `varchar(255)` | NO | `` | `` | `` | Hash BCRYPT/ARGON2 |
| **`nombre_contacto`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`estatus`** | `enum('PRE_REGISTERED','INVITED','ONBOARDING','ACTIVE','SUSPENDED')` | SÍ | `MUL` | `ACTIVE` | `` |  |
| **`ultimo_acceso`** | `datetime` | SÍ | `` | `` | `` |  |
| **`reset_token`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` | Soft Deletes Laravel |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` | ID del admin que lo invit� o NULL si fue self-onboarding |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_det_config_financiera`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_config_financiera`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`id_condicion_pago`** | `int` | SÍ | `` | `` | `` |  |
| **`cuenta_contable`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`limite_credito`** | `decimal(16,2)` | SÍ | `` | `0.00` | `` |  |
| **`id_moneda_defecto`** | `char(3)` | SÍ | `` | `MXN` | `` |  |
| **`tasa_iva_default`** | `decimal(5,2)` | SÍ | `` | `16.00` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_det_contactos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_contacto`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`nombre`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`puesto`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`email`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`telefono`** | `varchar(25)` | SÍ | `` | `` | `` |  |
| **`notificar_compras`** | `tinyint(1)` | SÍ | `` | `1` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_det_cuentas_bancarias`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_cuenta_bancaria`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`id_banco`** | `char(3)` | SÍ | `MUL` | `` | `` |  |
| **`id_moneda`** | `char(3)` | NO | `` | `MXN` | `` |  |
| **`cuenta`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`swift_bic`** | `varchar(15)` | SÍ | `` | `` | `` | Para transferencias internacionales |
| **`clabe`** | `varchar(18)` | SÍ | `UNI` | `` | `` |  |
| **`iban`** | `varchar(34)` | SÍ | `` | `` | `` | Est�ndar internacional |
| **`url_pdf`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`es_principal`** | `tinyint` | SÍ | `` | `0` | `` |  |
| **`estatus_aprobacion`** | `enum('PENDIENTE','APROBADO','RECHAZADO')` | SÍ | `` | `PENDIENTE` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_det_direcciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_direccion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`tipo`** | `enum('Fiscal','Entrega','Bodega','Oficina')` | SÍ | `` | `` | `` |  |
| **`calle`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`num_ext`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`num_int`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`colonia`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`cp`** | `char(5)` | SÍ | `` | `` | `` |  |
| **`municipio`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`ciudad`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`estado`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`es_principal`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_det_expediente`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_documento`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`tipo_documento`** | `varchar(50)` | SÍ | `` | `` | `` |  |
| **`url_archivo`** | `text` | NO | `` | `` | `` |  |
| **`vencimiento`** | `date` | SÍ | `` | `` | `` |  |
| **`estatus_validacion`** | `tinyint` | SÍ | `` | `0` | `` | 0: Pendiente, 1: OK, 2: Rechazado |
| **`motivo_rechazo`** | `text` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_tra_evaluaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_evaluacion`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`periodo`** | `varchar(20)` | SÍ | `` | `` | `` |  |
| **`score_cumplimiento`** | `decimal(5,2)` | SÍ | `` | `` | `` |  |
| **`score_calidad`** | `decimal(5,2)` | SÍ | `` | `` | `` |  |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_tra_incidencias`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_incidencia`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `` | `` | `` |  |
| **`tipo_incidencia`** | `enum('Calidad','Entrega Tarde','Facturacion','Otros')` | SÍ | `` | `` | `` |  |
| **`descripcion`** | `text` | SÍ | `` | `` | `` |  |
| **`gravedad`** | `enum('Baja','Media','Alta','Critica')` | SÍ | `` | `` | `` |  |
| **`id_usuario_reporta`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`fecha_incidencia`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `prv_tra_onboarding`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_onboarding`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`id_proveedor`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`paso_actual`** | `int` | SÍ | `` | `1` | `` |  |
| **`comentarios_revision`** | `text` | SÍ | `` | `` | `` |  |
| **`fecha_aprobacion`** | `timestamp` | SÍ | `` | `` | `` |  |
| **`id_usuario_validador`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `src_eventos_sourcing`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint unsigned` | NO | `PRI` | `` | `auto_increment` |  |
| **`folio`** | `varchar(20)` | NO | `UNI` | `` | `` |  |
| **`titulo`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`planta_id`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`comprador_id`** | `bigint unsigned` | NO | `MUL` | `` | `` |  |
| **`estatus_evento`** | `enum('ABIERTO','DICTAMEN','ADJUDICADO','CANCELADO')` | SÍ | `MUL` | `ABIERTO` | `` |  |
| **`created_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`updated_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`deleted_by`** | `bigint unsigned` | SÍ | `` | `` | `` |  |
| **`created_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`updated_at`** | `timestamp` | SÍ | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |
| **`deleted_at`** | `timestamp` | SÍ | `` | `` | `` |  |

---

