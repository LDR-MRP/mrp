# Módulo: MRP (Producción, Estaciones, BOM y Calidad)

Total de tablas en este módulo: **50**

---

### 📊 Tabla: `mrp_acciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idaccion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`nombre`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` |  |

---

### 📊 Tabla: `mrp_acciones_notificaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idnotificacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`accionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`usuario_origen`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`usuario_destino`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`tipo_notificacion`** | `tinyint` | NO | `` | `` | `` | \n    1=Solicitud asistencia,\n    2=Falta material\n   |
| **`enviado_correo`** | `tinyint` | NO | `` | `1` | `` | 1=No, 2=Si |
| **`fecha_envio`** | `datetime` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `1` | `` | \n    1=Pendiente,\n    2=Enviada,\n    3=Leida,\n    4=Atendida\n   |

---

### 📊 Tabla: `mrp_acciones_produccion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idaccion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`idordengeneral`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`unidad`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`origen_accion`** | `tinyint` | NO | `` | `` | `` | 1=No conforme, 2=Paro manual |
| **`tipo_accion`** | `tinyint` | NO | `` | `` | `` | \n    1=Paro momentaneo,\n    2=Retiro AGV,\n    3=Unidad alarmada,\n    4=Solicitud asistencia,\n    5=Falta material\n   |
| **`fecha_inicio`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`fecha_fin`** | `datetime` | SÍ | `` | `` | `` |  |
| **`minutos_total`** | `decimal(10,2)` | SÍ | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`usuarioidfin`** | `bigint` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | \n    1=Pendiente,\n    2=Activo,\n    3=Cerrado,\n    4=Cancelado\n   |

---

### 📊 Tabla: `mrp_auditoria`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idauditoria`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`moduloid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`accionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tabla_afectada`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`id_registro`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_hora`** | `datetime` | NO | `` | `` | `` |  |
| **`ip`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`navegador`** | `varchar(255)` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_calidad_inspeccion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idinspeccion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idorden`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`numot`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_cierre`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_calidad_inspeccion_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetalle`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`idinspeccion`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`especificacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`resultado`** | `enum('OK','NO_OK')` | NO | `` | `` | `` |  |
| **`comentario_no_ok`** | `text` | SÍ | `` | `` | `` |  |
| **`accion_correctiva`** | `text` | SÍ | `` | `` | `` |  |
| **`comentario`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_calidad_inspeccion_evidencia`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idevidencia`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`iddetalle`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_original`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`archivo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`mime`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`size_bytes`** | `int` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_dias_festivos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `int` | NO | `PRI` | `` | `auto_increment` |  |
| **`fecha`** | `date` | NO | `UNI` | `` | `` |  |
| **`descripcion`** | `varchar(150)` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_estacion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idestacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_estacion`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`lineaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_estacion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`proceso`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estandar`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`unidad_medida`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tiempo_ajuste`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`mxn`** | `decimal(10,2)` | NO | `` | `` | `` |  |
| **`descripcion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`herramientas`** | `tinyint(1)` | NO | `` | `0` | `` | 0=no 1=si |
| **`tiene_subensamble`** | `tinyint(1)` | NO | `` | `0` | `` | 0:NO 1:SI |
| **`estado`** | `tinyint(1)` | NO | `` | `` | `` | 0=Eliminada 1=Inactivo 2=Activo |

---

### 📊 Tabla: `mrp_estacion_ayudas_visuales`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idayuda`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`titulo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tipo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`archivo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_componentes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idcomponente`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `int` | NO | `` | `` | `` |  |
| **`estado`** | `int` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_especificaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idespecificacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`especificacion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`asignado`** | `tinyint(1)` | NO | `` | `0` | `` | 0:NO 1:SI |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` | 0=Eliminado\n1=Inactivo\n2=Activo |

---

### 📊 Tabla: `mrp_estacion_especificaciones_criticas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idespecificacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`especificacion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` | 0=Eliminado 1=Inactivo 2=Activo |

---

### 📊 Tabla: `mrp_estacion_herramientas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idherramienta`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `bigint` | NO | `` | `` | `` |  |
| **`estado`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_mantenimiento`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idmantenimiento`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`responsable`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tipo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_programada`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_inicio`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_fin`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`mantenimiento`** | `tinyint(1)` | NO | `` | `1` | `` | 1=Sin Mantenimiento\n2=Pendiente/Programado\n3=En proceso\n4=Finalizado\n5=Cancelado |
| **`comentarios`** | `text` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminado 1=Inactivo 2=Activo |

---

### 📊 Tabla: `mrp_estacion_pdi`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idpdi`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`titulo`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`descripcion`** | `text` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_pdi_punto`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idpuntopdi`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`zonaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`punto`** | `varchar(500)` | NO | `` | `` | `` |  |
| **`orden`** | `int` | NO | `` | `1` | `` |  |
| **`check_china`** | `tinyint` | NO | `` | `0` | `` |  |
| **`check_mexico`** | `tinyint` | NO | `` | `0` | `` |  |
| **`check_i1`** | `tinyint` | NO | `` | `0` | `` |  |
| **`check_i2`** | `tinyint` | NO | `` | `0` | `` |  |
| **`check_i3`** | `tinyint` | NO | `` | `0` | `` |  |
| **`check_i4`** | `tinyint` | NO | `` | `0` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_pdi_puntos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idpuntopdi`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`punto`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`categoria`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`criterio`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`severidad`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`evidencia`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`orden`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_pdi_resultado`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idresultado`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`idordengeneral`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`unidad_actual`** | `varchar(100)` | SÍ | `` | `` | `` |  |
| **`idpuntopdi`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`tipo_check`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`resultado`** | `tinyint` | NO | `` | `` | `` |  |
| **`observacion`** | `text` | SÍ | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | SÍ | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_estacion_pdi_zona`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idzona`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`pdiid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_zona`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`referencia`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`orden`** | `int` | NO | `` | `1` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_estacion_subensamble`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsubensamble`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_estacion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`proceso`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estandar`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`tiempo_ajuste`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`herramientas`** | `tinyint` | NO | `` | `0` | `` | 0=no 1=si |
| **`estado`** | `tinyint` | NO | `` | `` | `` | 0=Eliminada 1=Inactivo 2=Activo\t |

---

### 📊 Tabla: `mrp_linea`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idlinea`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_linea`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`nombre_linea`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` | 0=Eliminada 1=inactiva 2=Activa |

---

### 📊 Tabla: `mrp_operaciones_criticas_realizadas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idoperacionrealizada`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_origen`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`subensambleid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`idespecificacion`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`operacion_texto`** | `text` | NO | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_registro`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`unidad`** | `varchar(255)` | SÍ | `` | `` | `` |  |
| **`resultado`** | `tinyint(1)` | NO | `` | `1` | `` | 1=Conforme 2=No conforme |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_operaciones_realizadas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idoperacionrealizada`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `` | `` | `` |  |
| **`tipo_origen`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`subensambleid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`idespecificacion`** | `bigint` | NO | `` | `` | `` |  |
| **`operacion_texto`** | `text` | NO | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `` | `` | `` |  |
| **`numcolaborador`** | `varchar(30)` | SÍ | `` | `` | `` |  |
| **`fecha_registro`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`unidad`** | `varchar(255)` | SÍ | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_ordenes_trabajo`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idorden`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`num_sub_orden`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_inicio`** | `datetime` | NO | `` | `` | `` |  |
| **`fecha_fin`** | `datetime` | NO | `` | `` | `` |  |
| **`comentarios`** | `text` | NO | `` | `` | `` |  |
| **`estatus`** | `tinyint(1)` | NO | `` | `1` | `` | 1=Pendiente\n2=En proceso\n3=Finalizada |
| **`calidad`** | `tinyint(1)` | NO | `` | `1` | `` | 0=Sin asignar 1=Pendiente 2:Completado |
| **`estampado`** | `tinyint(1)` | NO | `` | `0` | `` | 0= No requiere 1=Pendiente 2=En proceso 3=Finalizada |
| **`operaciones`** | `tinyint(1)` | NO | `` | `0` | `` | 0=Sin asignar 1=Pendiente 2:Completado |
| **`especificaciones_criticas`** | `tinyint(1)` | NO | `` | `0` | `` | 0=Sin asignar 1=Pendiente 2:Completado |
| **`accion_produccion`** | `tinyint(1)` | NO | `` | `0` | `` | 0=Sin acci�n, 1=Paro moment�neo, 2=Retiro AGV, 3=Unidad alarmada, 4=Solicitud asistencia, 5=Falta material |
| **`accion_activa`** | `tinyint(1)` | NO | `` | `1` | `` | 1=No, 2=Si |

---

### 📊 Tabla: `mrp_ordenes_trabajo_subensamble`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idorden_subensamble`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`num_sub_orden`** | `varchar(80)` | NO | `` | `` | `` |  |
| **`codigo_scan`** | `varchar(120)` | SÍ | `MUL` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `1` | `` |  |
| **`fecha_inicio_real`** | `datetime` | SÍ | `` | `` | `` |  |
| **`fecha_fin_real`** | `datetime` | SÍ | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`operaciones`** | `tinyint(1)` | NO | `` | `0` | `` | 0=Sin asignar 1=Pendiente 2:Completado |
| **`especificaciones_criticas`** | `tinyint(1)` | NO | `` | `0` | `` | 0=Sin asignar 1=Pendiente 2:Completado |

---

### 📊 Tabla: `mrp_ot_chat`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idchat`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`numorden`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`subot`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `` | `0` | `` | OMITIR ESTE CAMPO |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`planeacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`user_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`user_name`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`message`** | `text` | NO | `` | `` | `` |  |
| **`created_at`** | `timestamp` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED on update CURRENT_TIMESTAMP` |  |

---

### 📊 Tabla: `mrp_planeacion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idplaneacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`num_orden`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`num_pedido`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`supervisorid`** | `bigint` | NO | `` | `` | `` | VIENE DE LA TABLA USUARIOS :) |
| **`prioridad`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`cantidad`** | `int` | NO | `` | `` | `` |  |
| **`fecha_requerida`** | `date` | SÍ | `` | `` | `` |  |
| **`fecha_inicio`** | `datetime` | SÍ | `` | `` | `` |  |
| **`fecha_fin`** | `datetime` | SÍ | `` | `` | `` |  |
| **`notas`** | `text` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `` | `` | `` |  |
| **`fase`** | `tinyint(1)` | NO | `` | `2` | `` | 1=Borrador\n2=Pendiente/Programada\n3=En proceso\n4=Pausada/En espera\n5=Completada/Finalizada\n6=Cancelada\n7=Reprogramada |
| **`fecha_inicio_real`** | `datetime` | SÍ | `` | `` | `` |  |
| **`usuario_inicio`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_fin_real`** | `datetime` | SÍ | `` | `` | `` |  |
| **`usuario_fin`** | `bigint` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_planeacion_estacion`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_planeacion_estacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`orden`** | `int` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`estampado`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`calidad`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`operaciones`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`especificaciones`** | `tinyint(1)` | NO | `` | `0` | `` |  |

---

### 📊 Tabla: `mrp_planeacion_estacion_calidadpdi`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`rol`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_planeacion_estacion_calidadpuntoscriticos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`rol`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_planeacion_estacion_operador`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`rol`** | `enum('ENCARGADO','AYUDANTE')` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_planeacion_subensamble`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_planeacion_subensamble`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`planeacion_estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`orden_sub`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `mrp_planeacion_subensamble_operador`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_planeacion_subensamble_operador`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`planeacion_subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`rol`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `mrp_planta`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idplanta`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_planta`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`nombre_planta`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`direccion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` |  |

---

### 📊 Tabla: `mrp_produccion_evento`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idproduccion_evento`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`tipo_origen`** | `varchar(20)` | NO | `` | `` | `` |  |
| **`orden_trabajoid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`orden_subensambleid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`planeacion_estacionid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`planeacion_subensambleid`** | `bigint` | SÍ | `` | `` | `` |  |
| **`usuarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`accion`** | `varchar(20)` | NO | `MUL` | `` | `` |  |
| **`fecha_hora`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`observaciones`** | `text` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_producto_fases`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`id_producto_fase`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`documentacion`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`descriptiva`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`procesos`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`especificaciones_criticas`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`calidad`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |
| **`finalizado`** | `tinyint(1)` | SÍ | `` | `0` | `` |  |

---

### 📊 Tabla: `mrp_producto_ruta`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idruta_producto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`lineaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`descripcion_ruta`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_producto_ruta_detalle`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddetalle`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`ruta_productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`orden`** | `smallint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estampado`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`calidad`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`operaciones`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`especificaciones`** | `tinyint(1)` | NO | `` | `0` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_productos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idproducto`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`cve_producto`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`lineaproductoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`descripcion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`avance_general`** | `int` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminada\n1=Inactivo \n2=Activo  |

---

### 📊 Tabla: `mrp_productos_descriptiva`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddescriptiva`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`marca`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`modelo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`largo_total`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`distancia_ejes`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`peso_bruto_vehicular`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`motor`** | `text` | NO | `` | `` | `` |  |
| **`cilindros`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`desplazamiento_c`** | `varchar(255)` | NO | `` | `` | `` | Peso chasis cabina |
| **`tipo_combustible`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`potencia`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`torque`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`transmision`** | `text` | NO | `` | `` | `` |  |
| **`eje_delantero`** | `text` | NO | `` | `` | `` |  |
| **`suspension_delantera`** | `text` | NO | `` | `` | `` |  |
| **`eje_trasero`** | `text` | NO | `` | `` | `` |  |
| **`suspension_trasera`** | `text` | NO | `` | `` | `` |  |
| **`llantas`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`sistema_frenos`** | `text` | NO | `` | `` | `` |  |
| **`asistencias`** | `text` | NO | `` | `` | `` |  |
| **`sistema_electrico`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`capacidad_combustible`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`norma`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`direccion`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`equipamiento`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminado\n1=Activo\n2=Inactivo |

---

### 📊 Tabla: `mrp_productos_documentos`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`iddocumento`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`tipo_documento`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`descripcion`** | `text` | NO | `` | `` | `` |  |
| **`ruta`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `2` | `` | 0=Eliminada\n1=Inactivo \n2=Activo  |

---

### 📊 Tabla: `mrp_subensamble_ayudas_visuales`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idaysubayuda`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`titulo`** | `varchar(150)` | NO | `` | `` | `` |  |
| **`tipo`** | `varchar(30)` | NO | `` | `` | `` |  |
| **`archivo`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |

---

### 📊 Tabla: `mrp_subensamble_componentes`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsubcomponente`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `bigint` | NO | `` | `` | `` |  |
| **`estado`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_subensamble_especificaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idespecificacionsubensamble`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `` | `` | `` |  |
| **`especificacion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`asignado`** | `tinyint(1)` | NO | `` | `0` | `` | 0:NO 1:SI |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_subensamble_especificaciones_criticas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idespecificacionsubensamble`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`productoid`** | `bigint` | NO | `` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `` | `` | `` |  |
| **`especificacion`** | `text` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` |  |

---

### 📊 Tabla: `mrp_subensamble_herramientas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idsubherramienta`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`almacenid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`productoid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`subensambleid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`inventarioid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`cantidad`** | `bigint` | NO | `` | `` | `` |  |
| **`estado`** | `bigint` | NO | `` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |

---

### 📊 Tabla: `mrp_unidades_fuera_linea`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idfuera`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`accionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`productoid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`estacionid`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`idordengeneral`** | `bigint` | SÍ | `MUL` | `` | `` |  |
| **`unidad`** | `varchar(100)` | NO | `` | `` | `` |  |
| **`fecha_salida`** | `datetime` | NO | `` | `CURRENT_TIMESTAMP` | `DEFAULT_GENERATED` |  |
| **`usuario_salida`** | `bigint` | SÍ | `` | `` | `` |  |
| **`fecha_reincorporacion`** | `datetime` | SÍ | `` | `` | `` |  |
| **`usuario_reincorporacion`** | `bigint` | SÍ | `` | `` | `` |  |
| **`estado`** | `tinyint` | NO | `` | `1` | `` | \n    1=Fuera de linea,\n    2=Reincorporada,\n    3=Cancelada\n   |

---

### 📊 Tabla: `mrp_unidades_terminadas`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idunidad`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`clave`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`num_unidad`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`planeacionid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`plantaid`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_creacion`** | `datetime` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `2` | `` | 1=activo 0=Eliminado |

---

### 📊 Tabla: `mrp_vin_asignaciones`

| Campo | Tipo | Nulo | Llave | Defecto | Extra | Comentario |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- |
| **`idasignacion`** | `bigint` | NO | `PRI` | `` | `auto_increment` |  |
| **`orden_trabajo_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`num_unidad`** | `varchar(50)` | NO | `` | `` | `` |  |
| **`numero_serie_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`numero_motor`** | `varchar(255)` | NO | `` | `` | `` |  |
| **`vin_origen`** | `text` | NO | `` | `` | `` |  |
| **`numero_transmision`** | `text` | NO | `` | `` | `` |  |
| **`usuario_id`** | `bigint` | NO | `MUL` | `` | `` |  |
| **`fecha_asignacion`** | `date` | NO | `` | `` | `` |  |
| **`estado`** | `tinyint(1)` | NO | `` | `1` | `` | 1=Registrado/activo 0=Eliminado/baja\t |

---

