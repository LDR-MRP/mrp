<?php

class Plan_confproductosModel extends Mysql
{
	public $intIdinventario;
	public $strClave;
	public $intlineaproductoid;
	public $strDescripcion;
	public $strFecha;
	public $intEstado;
	public $intIdProducto;
	public $strDocumento;
	public $intIdDocumento;
	public $strTipoDoc;

	//////////////////////
	//AUDITORIA

	public $intModulo;
	public $intAccion;
	public $intIdUsuario;
	public $strTabla;
	public $intIdregistro;
	public $strfecha_creacion;
	public $strip;
	public $strDetalle;
    public $strNorma;

	//////////////////////
	//DESRIPTIVA
	public $intDescriptiva;
	public $strMarca;
	public $strModelo;
	public $strLargoTotal;
	public $strDistanciaEjes;
	public $strPesoBrutoVehicular;
	public $strMotor;
	public $strCilindros;
	public $strDesplazamientoC;
	public $strDesplazamientoCilindros;
	public $strDesplazamiento;
	public $strTipoCombustible;
	public $strPotencia;
	public $strTorque;
	public $strTransmision;
	public $strEjeDelantero;
	public $strSuspensionDelantera;
	public $strEjeTrasero;
	public $strSuspensionTrasera;
	public $strLlantas;
	public $strSistemaFrenos;
	public $strAsistencias;
	public $strSistemaElectrico;
	public $strCapacidadCombustible;
	public $strDireccion;
	public $strEquipamiento;
	public $intlinea;

	// set detalle ruta
	public $intPlantaid;
	public $intlineid;
	// RUta detalle 
	public $intRutaid;
	public $intEstacionid;
	public $intOrden;

	public $intIdEstacion;
	public $intIdEspecificacion;
	public $intIdespecificacion;
	public $intIdalmacen;

	//COMPONENTES
	public $intAlmacen;
	public $intProducto;
public $intEstacion;
public $intInventario;
public $intCantidad;
public $intIdDetalle;
public $intIdRuta;

	public function __construct()
	{
		parent::__construct();
	}

public function generarClave()
{
    $fecha   = date('Ymd');
    $version = 'V01';
    $prefijo = 'P-' . $fecha . '-';

    $sql = "SELECT cve_producto 
            FROM mrp_productos 
            WHERE cve_producto LIKE '$prefijo%' 
            ORDER BY cve_producto DESC 
            LIMIT 1";

    $result = $this->select($sql);
    $numero = 1;

    if (!empty($result)) {
        $ultimoCodigo = $result['cve_producto'];

    
        $ultimoNumero = (int) substr($ultimoCodigo, -8, 4);
        $numero = $ultimoNumero + 1;
    }

    return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT) . '-' . $version;
}


	public function insertAuditoria($modulo, $accion, $id_usuario, $tabla, $idregistro, $fecha_creacion, $ip, $detalle)
	{


		$return = 0;
		$this->intModulo = $modulo;
		$this->intAccion = $accion;
		$this->intIdUsuario = $id_usuario;
		$this->strTabla = $tabla;
		$this->intIdregistro = $idregistro;
		$this->strfecha_creacion = $fecha_creacion;
		$this->strip = $ip;
		$this->strDetalle = $detalle;

		$query_insert = "INSERT INTO mrp_auditoria(moduloid,accionid,usuarioid,tabla_afectada,id_registro,fecha_hora,ip,navegador) VALUES(?,?,?,?,?,?,?,?)";
		$arrData = array(
			$this->intModulo,
			$this->intAccion,
			$this->intIdUsuario,
			$this->strTabla,
			$this->intIdregistro,
			$this->strfecha_creacion,
			$this->strip,
			$this->strDetalle
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;

	}


	public function selectOptionProductos()
	{

		$sql = "SELECT 
            inv.*,
			lp.cve_linea_producto as linea_clave,
            lp.descripcion AS linea_descripcion
        FROM wms_inventario AS inv
        INNER JOIN wms_linea_producto AS lp 
        ON inv.lineaproductoid = lp.idlineaproducto WHERE inv.tipo_elemento ='P'";

		$request = $this->select_all($sql);
		return $request;
	}

	public function selectInventario($idinventario)
	{
		$this->intIdinventario = $idinventario;
		$sql = "SELECT * FROM wms_inventario WHERE idinventario = $this->intIdinventario";
		$request = $this->select($sql);
		return $request;
	}

	public function selectOptionLineasProductos()
	{
		$sql = "SELECT * FROM wms_linea_producto  WHERE estado !=0";
		$request = $this->select_all($sql);
		return $request;
	}




	public function inserProducto($claveUnica, $inventarioid, $lineaproductoid, $descripcion, $fecha_creacion, $estado)
	{

		$return = 0;
		$this->strClave = $claveUnica;
		$this->intIdinventario = $inventarioid;
		$this->intlineaproductoid = $lineaproductoid;
		$this->strDescripcion = $descripcion;
		$this->strFecha = $fecha_creacion;
		$this->intEstado = $estado;

		$query_insert = "INSERT INTO mrp_productos(cve_producto,inventarioid,lineaproductoid,descripcion,fecha_creacion,estado) VALUES(?,?,?,?,?,?)";
		$arrData = array(
			$this->strClave,
			$this->intIdinventario,
			$this->intlineaproductoid,
			$this->strDescripcion,
			$this->strFecha,
			$this->intEstado
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;
	}


	public function updateProducto($intIdProducto, $inventarioid, $lineaproductoid, $descripcion, $estado)
	{
		$this->intIdProducto = $intIdProducto;
		$this->intIdinventario = $inventarioid;
		$this->intlineaproductoid = $lineaproductoid;
		$this->strDescripcion = $descripcion;
		$this->intEstado = $estado;


		$sql = "UPDATE mrp_productos SET inventarioid = ?, lineaproductoid = ?, descripcion = ?, estado = ? WHERE idproducto = $this->intIdProducto";
		$arrData = array(
			$this->intIdinventario,
			$this->intlineaproductoid,
			$this->strDescripcion,
			$this->intEstado
		);
		$request = $this->update($sql, $arrData);

		return $request;
	} 
	public function selectProductos()
	{

		$sql = "SELECT com.*,
		       com.estado AS estado_producto, 
               inv.cve_articulo,
               inv.descripcion AS descripcion_producto,
               lp.cve_linea_producto,
               lp.descripcion AS descripcion_linea
        FROM  mrp_productos AS com
        INNER JOIN wms_inventario AS inv ON com.inventarioid = inv.idinventario
        INNER JOIN wms_linea_producto AS lp ON lp.idlineaproducto = com.lineaproductoid
        WHERE com.estado != 0;";
		$request = $this->select_all($sql);
		return $request;

	}



	public function insertDocumento($intIdProducto, $strTipoDocumento, $descripcion, $nombreDocumento, $fecha_creacion)
	{

		$return = 0;
		$this->intIdProducto = $intIdProducto;
		$this->strTipoDoc = $strTipoDocumento;
		$this->strDescripcion = $descripcion;
		$this->strDocumento = $nombreDocumento;
		$this->strFecha = $fecha_creacion;



		// $sql = "SELECT * FROM  viaticos_generales WHERE usuarioid = '{$this->intUsuarioid}' ";
		// $request = $this->select_all($sql);

		// if(empty($request))
		// {
		$query_insert = "INSERT INTO mrp_productos_documentos(productoid,tipo_documento,descripcion,ruta,fecha_creacion) VALUES(?,?,?,?,?)";
		$arrData = array(
			$this->intIdProducto,
			$this->strTipoDoc,
			$this->strDescripcion,
			$this->strDocumento,
			$this->strFecha
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;
		// }else{
		// 	$return = "exist";
		// }
		return $return;
	}

	public function selectDocumentosByProducto($productoid)
	{
		$this->intIdProducto = $productoid;
		$sql = "SELECT doc.*, com.inventarioid, inv.cve_articulo, inv.descripcion as descripcion_articulo FROM mrp_productos_documentos AS doc
		INNER JOIN mrp_productos AS com ON com.idproducto = doc.productoid
		INNER JOIN wms_inventario AS inv ON inv.idinventario = com.inventarioid
		WHERE doc.estado !=0 AND doc.productoid = $this->intIdProducto ";
		$request = $this->select_all($sql);
		return $request;

	}



	public function selectDescriptivaByProducto($descriptivaid)
	{
		$this->intDescriptiva = $descriptivaid;
		$sql = "SELECT * FROM mrp_productos_descriptiva WHERE iddescriptiva = $this->intDescriptiva ";
		$request = $this->select_all($sql);
		return $request;

	}

	public function deleteDocumento($iddocumento)
	{

		$this->intIdDocumento = $iddocumento;
		$sql = "UPDATE mrp_productos_documentos SET estado = ? WHERE iddocumento = $this->intIdDocumento ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;

	}


public function selectProducto(int $productoid)
{
    $this->intIdProducto = $productoid;

    $sql = "SELECT 
                p.*, 
                IFNULL(d.iddescriptiva, 0)    AS iddescriptiva,
                IFNULL(r.idruta_producto, 0) AS idruta_producto
            FROM mrp_productos AS p
            LEFT JOIN mrp_productos_descriptiva AS d
                ON d.productoid = p.idproducto
            LEFT JOIN mrp_producto_ruta AS r
                ON r.productoid = p.idproducto
            WHERE p.idproducto = {$this->intIdProducto}";

    return $this->select($sql);
}







	public function insertDescriptiva(
		$productoid,
		$marca,
		$modelo,
		$largo_total,
		$distancia_ejes,
		$peso_bruto_vehicular,
		$motor,
		$cilindros,
		$desplazamiento_c,
		$tipo_combustible,
		$potencia,
		$torque,
		$transmision,
		$eje_delantero,
		$suspension_delantera,
		$eje_trasero,
		$suspension_trasera,
		$llantas,
		$sistema_frenos,
		$asistencias,
		$sistema_electrico,
		$capacidad_combustible,
        $norma,
		$direccion,
		$equipamiento,
		$fecha_creacion
	) {

		$return = 0;

		// Asignación de valores a propiedades
		$this->intIdProducto = $productoid;
		$this->strMarca = $marca;
		$this->strModelo = $modelo;
		$this->strLargoTotal = $largo_total;
		$this->strDistanciaEjes = $distancia_ejes;
		$this->strPesoBrutoVehicular = $peso_bruto_vehicular;
		$this->strMotor = $motor;
		$this->strCilindros = $cilindros;
		$this->strDesplazamientoC = $desplazamiento_c;
		$this->strTipoCombustible = $tipo_combustible;
		$this->strPotencia = $potencia;
		$this->strTorque = $torque;
		$this->strTransmision = $transmision;
		$this->strEjeDelantero = $eje_delantero;
		$this->strSuspensionDelantera = $suspension_delantera;
		$this->strEjeTrasero = $eje_trasero;
		$this->strSuspensionTrasera = $suspension_trasera;
		$this->strLlantas = $llantas;
		$this->strSistemaFrenos = $sistema_frenos;
		$this->strAsistencias = $asistencias;
		$this->strSistemaElectrico = $sistema_electrico;
		$this->strCapacidadCombustible = $capacidad_combustible;
        $this->strNorma = $norma;
		$this->strDireccion = $direccion;
		$this->strEquipamiento = $equipamiento;
		$this->strFecha = $fecha_creacion;


		$query_insert = "INSERT INTO mrp_productos_descriptiva(
        productoid, marca, modelo, largo_total, distancia_ejes, peso_bruto_vehicular,
        motor, cilindros, desplazamiento_c, tipo_combustible, potencia, torque, transmision,
        eje_delantero, suspension_delantera, eje_trasero, suspension_trasera, llantas, sistema_frenos,
        asistencias, sistema_electrico, capacidad_combustible, norma, direccion, equipamiento, fecha_creacion
    ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";


		$arrData = array(
			$this->intIdProducto,
			$this->strMarca,
			$this->strModelo,
			$this->strLargoTotal,
			$this->strDistanciaEjes,
			$this->strPesoBrutoVehicular,
			$this->strMotor,
			$this->strCilindros,
			$this->strDesplazamientoC,
			$this->strTipoCombustible,
			$this->strPotencia,
			$this->strTorque,
			$this->strTransmision,
			$this->strEjeDelantero,
			$this->strSuspensionDelantera,
			$this->strEjeTrasero,
			$this->strSuspensionTrasera,
			$this->strLlantas,
			$this->strSistemaFrenos,
			$this->strAsistencias,
			$this->strSistemaElectrico,
			$this->strCapacidadCombustible,
            $this->strNorma,
			$this->strDireccion,
			$this->strEquipamiento,
			$this->strFecha
		);

		// Ejecutar insert
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;
	}



	public function updateDescriptiva(
		$descriptivaid,
		$marca,
		$modelo,
		$largo_total,
		$distancia_ejes,
		$peso_bruto_vehicular,
		$motor,
		$cilindros,
		$desplazamiento_c,
		$tipo_combustible,
		$potencia,
		$torque,
		$transmision,
		$eje_delantero,
		$suspension_delantera,
		$eje_trasero,
		$suspension_trasera,
		$llantas,
		$sistema_frenos,
		$asistencias,
		$sistema_electrico,
		$capacidad_combustible,
        $norma,
		$direccion,
		$equipamiento
	) {
		$this->intDescriptiva = $descriptivaid;
		$this->strMarca = $marca;
		$this->strModelo = $modelo;
		$this->strLargoTotal = $largo_total;
		$this->strDistanciaEjes = $distancia_ejes;
		$this->strPesoBrutoVehicular = $peso_bruto_vehicular;
		$this->strMotor = $motor;
		$this->strCilindros = $cilindros;
		$this->strDesplazamientoC = $desplazamiento_c;
		$this->strTipoCombustible = $tipo_combustible;
		$this->strPotencia = $potencia;
		$this->strTorque = $torque;
		$this->strTransmision = $transmision;
		$this->strEjeDelantero = $eje_delantero;
		$this->strSuspensionDelantera = $suspension_delantera;
		$this->strEjeTrasero = $eje_trasero;
		$this->strSuspensionTrasera = $suspension_trasera;
		$this->strLlantas = $llantas;
		$this->strSistemaFrenos = $sistema_frenos;
		$this->strAsistencias = $asistencias;
		$this->strSistemaElectrico = $sistema_electrico;
		$this->strCapacidadCombustible = $capacidad_combustible;
        $this->strNorma = $norma;
		$this->strDireccion = $direccion;
		$this->strEquipamiento = $equipamiento;

		$sql = "UPDATE mrp_productos_descriptiva SET marca=?, modelo=?, largo_total=?, distancia_ejes=?, peso_bruto_vehicular=?,
        motor=?, cilindros=?, desplazamiento_c=?, tipo_combustible=?, potencia=?, torque=?, transmision=?,
        eje_delantero=?, suspension_delantera=?, eje_trasero=?, suspension_trasera=?, llantas=?, sistema_frenos=?,
        asistencias=?, sistema_electrico=?, capacidad_combustible=?, norma=?, direccion=?, equipamiento=? WHERE iddescriptiva = $this->intDescriptiva";

		$arrData = array(
			$this->strMarca,
			$this->strModelo,
			$this->strLargoTotal,
			$this->strDistanciaEjes,
			$this->strPesoBrutoVehicular,
			$this->strMotor,
			$this->strCilindros,
			$this->strDesplazamientoC,
			$this->strTipoCombustible,
			$this->strPotencia,
			$this->strTorque,
			$this->strTransmision,
			$this->strEjeDelantero,
			$this->strSuspensionDelantera,
			$this->strEjeTrasero,
			$this->strSuspensionTrasera,
			$this->strLlantas,
			$this->strSistemaFrenos,
			$this->strAsistencias,
			$this->strSistemaElectrico,
			$this->strCapacidadCombustible,
            $this->strNorma,
			$this->strDireccion,
			$this->strEquipamiento
		);
		$request = $this->update($sql, $arrData);
		return $request;

	}


public function selectOptionEstacionesByLinea($idlinea)
{
    $this->intlinea = (int)$idlinea;

    $sql = "SELECT 
            e.*,
            COALESCE(m.mantenimiento, 1) AS mantenimiento
        FROM mrp_estacion e
        LEFT JOIN mrp_estacion_mantenimiento m 
            ON m.estacionid = e.idestacion
           AND m.estado = 2
        WHERE e.estado = 2
          AND e.lineaid = $this->intlinea
    ";

    return $this->select_all($sql);
}




	/////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////
	// QUERYS PARA INSERTAR DETALLE DE RUTAS DEL PRODUCTO
	/////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////

	public function insertRuta($prod, $planta, $linea, $fecha_creacion_ruta)
	{

		$this->intlineaproductoid = $prod;
		$this->intPlantaid = $planta;
		$this->intlineid = $linea;
		$this->strfecha_creacion = $fecha_creacion_ruta;

		
		$query_insert = "INSERT INTO mrp_producto_ruta(productoid,plantaid,lineaid,fecha_creacion) VALUES(?,?,?,?)";
		$arrData = array(
			$this->intlineaproductoid,
			$this->intPlantaid,
			$this->intlineid,
			$this->strfecha_creacion
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;

	}

	public function insertRutaDetalle($idruta, $idestacion, $orden, $fecha_creacion_ruta_detalle)
	{
		$this->intRutaid = $idruta;
		$this->intEstacionid = $idestacion;
		$this->intOrden = $orden;
		$this->strfecha_creacion = $fecha_creacion_ruta_detalle;

		$query_insert = "INSERT INTO mrp_producto_ruta_detalle(ruta_productoid,estacionid,orden,fecha_creacion) VALUES(?,?,?,?)";
		$arrData = array(
			$this->intRutaid,
			$this->intEstacionid,
			$this->intOrden,
			$this->strfecha_creacion
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;

	}

	

		public function updateRutaDetalleOrdenCero($intEspecificacionid, $descripcion){

		$this->intIdEspecificacion = $intEspecificacionid;
		$this->strDescripcion = $descripcion;



		$sql = "UPDATE mrp_estacion_especificaciones SET especificacion = ?  WHERE idespecificacion =$this->intIdEspecificacion";
		$arrData = array(
			$this->strDescripcion
		);
		$request = $this->update($sql, $arrData);

		return $request;

	}


	

	public function EspecificacionesByEstacion($idestacion){
				$this->intIdEstacion = $idestacion;
		$sql = "SELECT * FROM mrp_estacion_especificaciones WHERE estacionid = $this->intIdEstacion AND estado !=0";
		$request = $this->select_all($sql);
		return $request;
	}

	public function insertEspecificacion($intIdproducto, $intEstacionId, $descripcion, $fecha_creacion)
	{
		$return =0;
		$this->intIdProducto = $intIdproducto;
		$this->intIdEstacion = $intEstacionId;
		$this->strDescripcion = $descripcion;
		$this->strFecha = $fecha_creacion;

		$query_insert = "INSERT INTO mrp_estacion_especificaciones(productoid,estacionid,especificacion,fecha_creacion) VALUES(?,?,?,?)";
		$arrData = array(
			$this->intIdProducto,
			$this->intIdEstacion,
			$this->strDescripcion,
			$this->strFecha
		);
		$request_insert = $this->insert($query_insert, $arrData);
		$return = $request_insert;

		return $return;

	}

	public function updateEspecificacion($intEspecificacionid, $descripcion){

		$this->intIdEspecificacion = $intEspecificacionid;
		$this->strDescripcion = $descripcion;



		$sql = "UPDATE mrp_estacion_especificaciones SET especificacion = ?  WHERE idespecificacion =$this->intIdEspecificacion";
		$arrData = array(
			$this->strDescripcion
		);
		$request = $this->update($sql, $arrData);

		return $request;

	}


		public function deleteEspecificacion($idespecificacion)
	{

		$this->intIdespecificacion = $idespecificacion;
		$sql = "UPDATE mrp_estacion_especificaciones SET estado = ? WHERE idespecificacion = $this->intIdespecificacion ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;

	}

	

		public function selectEspecificacion(int $idespecificacion){
			$this->intIdespecificacion = $idespecificacion;
			$sql = "SELECT * FROM mrp_estacion_especificaciones
					WHERE idespecificacion = $this->intIdespecificacion";
			$request = $this->select($sql);
			return $request;
		}


	/////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////
	// QUERYS PARA EL APARTADO DE COMPONENTES
	/////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////
		

			public function selectOptionAlmacenes()
	{

		$sql = "SELECT * FROM wms_almacenes WHERE estado =2";

		$request = $this->select_all($sql);
		return $request;
	}

    public function selectHerramientasv1(int $idalmacen)
{
    $this->intIdalmacen = (int)$idalmacen;
    $sql = "SELECT
            mov.idmovinventario,
            mov.inventarioid,
            mov.almacenid,
            mov.estado,
            mov.cantidad,
            mov.fecha_movimiento,
            inv.cve_articulo,
            inv.descripcion AS descripcion_articulo,
            inv.unidad_salida,
            inv.ultimo_costo,
			inv.tipo_elemento
        FROM wms_movimientos_inventario mov
        INNER JOIN wms_inventario inv ON inv.idinventario = mov.inventarioid
        WHERE mov.estado = 2
          AND mov.almacenid = {$this->intIdalmacen}
		  AND inv.tipo_elemento='H'
    ";

    return $this->select_all($sql) ?: [];
}

public function selectHerramientas(int $idalmacen)
{
    $this->intIdalmacen = (int)$idalmacen;
    $sql = "SELECT
            mov.idmultialmacen,
            mov.inventarioid,
            mov.almacenid,
            -- mov.cantidad,
            -- mov.fecha_movimiento,
            mov.existencia,
            inv.cve_articulo,
            inv.descripcion AS descripcion_articulo,
            inv.unidad_salida,
            inv.ultimo_costo,
			inv.tipo_elemento
        FROM wms_multialmacen mov
        INNER JOIN wms_inventario inv ON inv.idinventario = mov.inventarioid
        WHERE  mov.almacenid = {$this->intIdalmacen}
		  AND inv.tipo_elemento='H'
    ";

    return $this->select_all($sql) ?: [];
}

public function selectComponentesv1(int $idalmacen)
{
    $this->intIdalmacen = (int)$idalmacen;
    $sql = "SELECT
            mov.idmovinventario,
            mov.inventarioid,
            mov.almacenid,
            mov.estado,
            mov.cantidad,
            mov.fecha_movimiento,
			mov.existencia,
            inv.cve_articulo,
            inv.descripcion AS descripcion_articulo,
            inv.unidad_salida,
            inv.ultimo_costo,
			inv.tipo_elemento
        FROM wms_movimientos_inventario mov
        INNER JOIN wms_inventario inv ON inv.idinventario = mov.inventarioid
        WHERE mov.estado = 2
          AND mov.almacenid = {$this->intIdalmacen}
		  AND inv.tipo_elemento='C'
    ";

    return $this->select_all($sql) ?: [];
}

public function selectComponentes(int $idalmacen)
{
    $this->intIdalmacen = (int)$idalmacen;
    $sql = "SELECT
            mov.idmultialmacen,
            mov.inventarioid,
            mov.almacenid,
            -- mov.cantidad,
            -- mov.fecha_movimiento,
			mov.existencia,
            inv.cve_articulo,
            inv.descripcion AS descripcion_articulo,
            inv.unidad_salida,
            inv.ultimo_costo,
			inv.tipo_elemento
        FROM wms_multialmacen mov
        INNER JOIN wms_inventario inv ON inv.idinventario = mov.inventarioid
        WHERE mov.almacenid = {$this->intIdalmacen}
		  AND inv.tipo_elemento='C'
    ";

    return $this->select_all($sql) ?: [];
}




public function insertComponenteEstacion($idAlmacen,$idProducto,$idEstacion,$inventarioid,$cantidad,$estado,$fecha)
{
    $query = "INSERT INTO mrp_estacion_componentes
              (almacenid, productoid, estacionid, inventarioid, cantidad, estado, fecha_creacion)
              VALUES (?,?,?,?,?,?,?)";
    $arrData = [
        (int)$idAlmacen,
        (int)$idProducto,
        (int)$idEstacion,
        (int)$inventarioid,
        (int)$cantidad,
        (int)$estado,
        $fecha
    ];
    // return $this->insert($query, $arrData);


		$request_insert = $this->insert($query, $arrData);
		$return = $request_insert;

		return $return;
}



public function updateComponenteEstacion($idcomponente, $cantidad, $estado)
{
    $query = "UPDATE mrp_estacion_componentes
              SET cantidad = ?, estado = ?
              WHERE idcomponente = ?";
    $arrData = [(int)$cantidad, (int)$estado, (int)$idcomponente];
    return $this->update($query, $arrData);
}


public function softDeleteComponentesNoEnLista($idAlmacen,$idProducto,$idEstacion,$idsMantener = [])
{
  $base = "UPDATE mrp_estacion_componentes
           SET estado = 0
           WHERE almacenid = ? AND productoid = ? AND estacionid = ?";

  $params = [$idAlmacen,$idProducto,$idEstacion];

  if (!empty($idsMantener)) {
    $placeholders = implode(',', array_fill(0, count($idsMantener), '?'));
    $base .= " AND inventarioid NOT IN ($placeholders)";
    $params = array_merge($params, $idsMantener);
  }

  return $this->update($base, $params);
}

public function selectExistentesComponentes($idAlmacen, $idProducto, $idEstacion)
{
    $this->intAlmacen  = (int)$idAlmacen;
    $this->intProducto = (int)$idProducto;
    $this->intEstacion = (int)$idEstacion;

    $sql = "SELECT inventarioid
            FROM mrp_estacion_componentes
            WHERE almacenid = {$this->intAlmacen}
              AND productoid = {$this->intProducto}
              AND estacionid = {$this->intEstacion}";

    $request = $this->select_all($sql);
    return $request;
}



public function selectComponentesEstacion($idestacion, $idproducto)
{
    $idestacion = (int)$idestacion;
    $idproducto = (int)$idproducto;

    $sql = "SELECT 
              com.idcomponente,
              com.almacenid,
              com.inventarioid,
              com.cantidad,
              inv.cve_articulo,
              inv.descripcion,
              inv.unidad_salida
            FROM mrp_estacion_componentes com
            INNER JOIN wms_inventario inv ON inv.idinventario = com.inventarioid
            WHERE com.estacionid = {$idestacion}
              AND com.productoid = {$idproducto}
              AND com.estado = 2";

    return $this->select_all($sql);
}


public function selectComponentesEstacionAllEstados($idestacion, $idproducto, $idalmacen)
{
    $idestacion = (int)$idestacion;
    $idproducto = (int)$idproducto;
    $idalmacen  = (int)$idalmacen;

    $sql = "SELECT idcomponente, inventarioid, estado
            FROM mrp_estacion_componentes
            WHERE estacionid = {$idestacion}
              AND productoid = {$idproducto}
              AND almacenid  = {$idalmacen}";

    return $this->select_all($sql);
}


public function softDeleteComponentesNoIncluidos($idAlmacen, $idProducto, $idEstacion, $idsIncoming)
{
    $idAlmacen  = (int)$idAlmacen;
    $idProducto = (int)$idProducto;
    $idEstacion = (int)$idEstacion;


    if (empty($idsIncoming)) {
        $sql = "UPDATE mrp_estacion_componentes
                SET estado = 0
                WHERE almacenid = {$idAlmacen}
                  AND productoid = {$idProducto}
                  AND estacionid = {$idEstacion}";
        return $this->update($sql, []);
    }

    $ids = array_map('intval', $idsIncoming);
    $inList = implode(',', $ids);

    $sql = "UPDATE mrp_estacion_componentes
            SET estado = 0
            WHERE almacenid = {$idAlmacen}
              AND productoid = {$idProducto}
              AND estacionid = {$idEstacion}
              AND inventarioid NOT IN ({$inList})";

    return $this->update($sql, []);
}







/////////////////////////////////////////////
// FUNCIONES PARA EL GUARDADO DE RUTA
/////////////////////////////////////////////
public function selectRutaByProducto(int $rutaid)
{
    $rutaid = (int)$rutaid;

    $sql = "SELECT
                r.idruta_producto,
                r.productoid,
                r.plantaid,
                r.lineaid,
                d.estacionid,
                d.orden,
				d.iddetalle 
            FROM mrp_producto_ruta r
            LEFT JOIN mrp_producto_ruta_detalle d
                   ON d.ruta_productoid = r.idruta_producto
                  AND d.estado = 2
            WHERE r.idruta_producto = {$rutaid}
              AND r.estado = 2
            ORDER BY d.orden ASC";

    $rows = $this->select_all($sql);


    if (empty($rows)) return [];

  
    $payload = [
        "listPlantasSelect"   => (string)($rows[0]['plantaid'] ?? "0"),
        "listLineasSelect"    => (string)($rows[0]['lineaid'] ?? "0"),
        "idproducto_proceso"  => (string)($rows[0]['productoid'] ?? "0"),
        "detalle_ruta"        => []
    ];

    foreach ($rows as $r) {
    
        if (empty($r['estacionid'])) continue;

        $payload["detalle_ruta"][] = [
			"iddetalle" => (string)$r['iddetalle'],
            "idestacion" => (string)$r['estacionid'],
            "orden"      => (int)$r['orden']
        ];
    }

   
    return [$payload];
}




public function selectHerramientasEstacion($idestacion, $idproducto)
{
    $idestacion = (int)$idestacion;
    $idproducto = (int)$idproducto;

    $sql = "SELECT 
              herr.idherramienta,
              herr.almacenid,
              herr.inventarioid,
              herr.cantidad,
              inv.cve_articulo,
              inv.descripcion,
              inv.unidad_salida
            FROM mrp_estacion_herramientas herr
            INNER JOIN wms_inventario inv ON inv.idinventario = herr.inventarioid
            WHERE herr.estacionid = {$idestacion}
              AND herr.productoid = {$idproducto}
              AND herr.estado = 2";

    return $this->select_all($sql);
}



public function selectHerramientasEstacionAllEstados($idestacion, $idproducto, $idalmacen)
{
    $idestacion = (int)$idestacion;
    $idproducto = (int)$idproducto;
    $idalmacen  = (int)$idalmacen;

    $sql = "SELECT idherramienta, inventarioid, estado
            FROM mrp_estacion_herramientas
            WHERE estacionid = {$idestacion}
              AND productoid = {$idproducto}
              AND almacenid  = {$idalmacen}";

    return $this->select_all($sql);
}


public function updateHerramientaEstacion($idherramienta, $cantidad, $estado)
{
    $query = "UPDATE mrp_estacion_herramientas
              SET cantidad = ?, estado = ?
              WHERE idherramienta  = ?";
    $arrData = [(int)$cantidad, (int)$estado, (int)$idherramienta];
    return $this->update($query, $arrData);
}


public function insertHerramientaEstacion($idAlmacen,$idProducto,$idEstacion,$inventarioid,$cantidad,$estado,$fecha)
{
    $query = "INSERT INTO mrp_estacion_herramientas
              (almacenid, productoid, estacionid, inventarioid, cantidad, estado, fecha_creacion)
              VALUES (?,?,?,?,?,?,?)";
    $arrData = [
        (int)$idAlmacen,
        (int)$idProducto,
        (int)$idEstacion,
        (int)$inventarioid,
        (int)$cantidad,
        (int)$estado,
        $fecha
    ];
    // return $this->insert($query, $arrData);
		$request_insert = $this->insert($query, $arrData);
		$return = $request_insert;

		return $return;
}


public function softDeleteHerramientasNoIncluidos($idAlmacen, $idProducto, $idEstacion, $idsIncoming)
{
    $idAlmacen  = (int)$idAlmacen;
    $idProducto = (int)$idProducto;
    $idEstacion = (int)$idEstacion;

  
    if (empty($idsIncoming)) {
        $sql = "UPDATE mrp_estacion_herramientas
                SET estado = 0
                WHERE almacenid = {$idAlmacen}
                  AND productoid = {$idProducto}
                  AND estacionid = {$idEstacion}";
        return $this->update($sql, []);
    }

    $ids = array_map('intval', $idsIncoming);
    $inList = implode(',', $ids);

    $sql = "UPDATE mrp_estacion_herramientas
            SET estado = 0
            WHERE almacenid = {$idAlmacen}
              AND productoid = {$idProducto}
              AND estacionid = {$idEstacion}
              AND inventarioid NOT IN ({$inList})";

    return $this->update($sql, []);
}







	


    // public function insertRuta(int $productoid, int $plantaid, int $lineaid, string $fecha_creacion, string $descripcion_ruta = '')
    // {
    //     $sql = "INSERT INTO mrp_producto_ruta
    //             (productoid, plantaid, lineaid, descripcion_ruta, fecha_creacion, estado)
    //             VALUES (?, ?, ?, ?, ?, 1)";
    //     $arrData = [$productoid, $plantaid, $lineaid, $descripcion_ruta, $fecha_creacion];

    //     return (int)$this->insert($sql, $arrData); // regresa idruta_producto
    // }

 public function updateRutaHeader(int $idruta, int $planta, int $linea)
    {
        $this->intIdRuta = $idruta;

        $sql = "UPDATE mrp_producto_ruta
                SET plantaid = ?, lineaid = ?
                WHERE idruta_producto = $this->intIdRuta";

        $arrData = array($planta, $linea);
        $request = $this->update($sql, $arrData);
        return $request;
    }


    public function getRutaHeader(int $idruta_producto)
    {
        $sql = "SELECT idruta_producto, productoid, plantaid, lineaid, descripcion_ruta, fecha_creacion, estado
                FROM mrp_producto_ruta
                WHERE idruta_producto = ? LIMIT 1";
        return $this->select($sql, [$idruta_producto]);
    }

// ==========================================================
    //  VALIDAR QUE RUTA EXISTA Y SEA DEL PRODUCTO
    // ==========================================================
    public function rutaExisteParaProducto(int $idruta, int $prod)
    {
        $sql = "SELECT idruta_producto
                FROM mrp_producto_ruta
                WHERE idruta_producto = $idruta
                  AND productoid = $prod
                LIMIT 1";

        $request = $this->select($sql);
        return !empty($request);
    }

    // =========================================================
    //  DETALLE: mrp_producto_ruta_detalle
    // =========================================================

    // public function insertRutaDetalle(int $ruta_productoid, int $estacionid, int $orden, string $fecha_creacion)
    // {
    //     $sql = "INSERT INTO mrp_producto_ruta_detalle
    //             (ruta_productoid, estacionid, orden, fecha_creacion, estado)
    //             VALUES (?, ?, ?, ?, 1)";
    //     $arrData = [$ruta_productoid, $estacionid, $orden, $fecha_creacion];

    //     return (int)$this->insert($sql, $arrData); // regresa iddetalle
    // }

    public function updateRutaDetalleOrden(int $iddetalle, int $orden)
    {
        $sql = "UPDATE mrp_producto_ruta_detalle
                SET orden = ?
                WHERE iddetalle = ? AND estado = 2";
        return $this->update($sql, [$orden, $iddetalle]);
    }

    public function updateRutaDetalle(int $iddetalle, int $idestacion, int $orden)
    {
        $this->intIdDetalle = $iddetalle;

        $sql = "UPDATE mrp_producto_ruta_detalle
                SET estacionid = ?, orden = ?, estado = ?
                WHERE iddetalle = $this->intIdDetalle";

        $arrData = array($idestacion, $orden, 2);
        $request = $this->update($sql, $arrData);
        return $request;
    }

// ==========================================================
    //  DELETE LÓGICO 
    // ==========================================================
    public function deleteRutaDetalleLogico(int $iddetalle)
    {
        $this->intIdDetalle = $iddetalle;

        $sql = "UPDATE mrp_producto_ruta_detalle
                SET estado = ?, orden = ?
                WHERE iddetalle = $this->intIdDetalle";

        $arrData = array(0, 0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function getDetallesRutaActivos(int $ruta_productoid)
    {
        $sql = "SELECT iddetalle, ruta_productoid, estacionid, orden
                FROM mrp_producto_ruta_detalle
                WHERE ruta_productoid = ? AND estado = 2
                ORDER BY orden ASC";
        return $this->select_all($sql, [$ruta_productoid]);
    }


    public function disableDetallesNoEnPayload(int $idruta, array $idsDetalleVistos)
    {
        $this->intIdRuta = $idruta;

        if (empty($idsDetalleVistos)) return false;

        $ids = implode(',', array_map('intval', $idsDetalleVistos));

        $sql = "UPDATE mrp_producto_ruta_detalle
                SET estado = 0, orden = 0
                WHERE ruta_productoid = $this->intIdRuta
                  AND iddetalle NOT IN ($ids)";


        $request = $this->update($sql, []);
        return $request;
    }

    public function reindexOrdenRuta(int $idruta)
    {
        $this->intIdRuta = $idruta;

        $sql = "SELECT iddetalle
                FROM mrp_producto_ruta_detalle
                WHERE ruta_productoid = $this->intIdRuta
                  AND estado = 2
                ORDER BY orden ASC, iddetalle ASC";

        $rows = $this->select_all($sql);
        if (empty($rows)) return false;

        $i = 1;
        foreach ($rows as $r) {
            $idd = (int)$r['iddetalle'];

            $sqlUp = "UPDATE mrp_producto_ruta_detalle
                      SET orden = ?
                      WHERE iddetalle = $idd";

            $this->update($sqlUp, array($i));
            $i++;
        }

        return true;
    }

	public function deleteEspecificacionEstacionLogico(int $idestacion){
	$this->intEstacionid = $idestacion;

		$sql = "UPDATE mrp_estacion_especificaciones SET estado = ?  WHERE estacionid =$this->intEstacionid";
		$arrData = array(
			0
		);
		$request = $this->update($sql, $arrData);

		return $request;
	}

		public function deleteComponentesEstacionLogico(int $idestacion){
	$this->intEstacionid = $idestacion;

		$sql = "UPDATE mrp_estacion_componentes SET estado = ?  WHERE estacionid =$this->intEstacionid";
		$arrData = array(
			0
		);
		$request = $this->update($sql, $arrData);

		return $request;
	}

			public function deleteHerramientaEstacionLogico(int $idestacion){
	$this->intEstacionid = $idestacion;

		$sql = "UPDATE mrp_estacion_herramientas SET estado = ?  WHERE estacionid =$this->intEstacionid";
		$arrData = array(
			0
		);
		$request = $this->update($sql, $arrData);

		return $request;
	}


	//FUNCIÓN PARA GENERAR EL REPORTE DEL PRODUCTO PDF
public function selectProductoReporte(int $productoid)
{
    $this->intIdProducto = $productoid;


    $sqlProd = "SELECT p.*,l.descripcion as nombre_linea
                FROM mrp_productos p
				INNER JOIN wms_linea_producto AS l
				ON p.lineaproductoid = l.idlineaproducto 
				-- INNER JOIN mrp_planta AS pl
				-- ON l.plantaid = pl.idplanta
                WHERE p.idproducto = {$this->intIdProducto}
                LIMIT 1";
    $producto = $this->select($sqlProd);

    if (empty($producto)) {
        return [
            'producto' => false,
            'msg'      => 'No existe el producto',
            'documentacion' => [
                'status' => false,
                'msg'    => 'No existe ningun registro',
                'data'   => []
            ],
            'descriptiva_tecnica' => [
                'status' => false,
                'msg'    => 'No existe información',
                'data'   => (object)[]
            ],
            'producto_configurado' => [
                'status' => false,
                'msg'    => 'No existe configuración de ruta',
                'data'   => (object)[]
            ],
        ];
    }

    // ---------------------------------------------------------
    // DOCUMENTACIÓN 
    // ---------------------------------------------------------
    $sqlDocs = "SELECT *
                FROM mrp_productos_documentos
                WHERE productoid = {$this->intIdProducto}
                  AND estado = 2";
    $docs = $this->select_all($sqlDocs);

    $documentacion = !empty($docs)
        ? ['status' => true,  'data' => $docs]
        : ['status' => false, 'msg'  => 'No existe ningun registro', 'data' => []];

    // ---------------------------------------------------------
    // DESCRIPTIVA TÉCNICA
    // ---------------------------------------------------------
    $sqlDesc = "SELECT *
                FROM mrp_productos_descriptiva
                WHERE productoid = {$this->intIdProducto}
                  AND estado = 2
                LIMIT 1";
    $desc = $this->select($sqlDesc);

    $descriptiva_tecnica = !empty($desc)
        ? ['status' => true,  'data' => $desc]
        : ['status' => false, 'msg'  => 'No existe información', 'data' => (object)[]];


    $sqlRuta = "SELECT *
                FROM mrp_producto_ruta
                WHERE productoid = {$this->intIdProducto}
                  AND estado = 2
                LIMIT 1";
    $ruta = $this->select($sqlRuta);

    if (empty($ruta)) {
        return [
            'producto'             => $producto,
            'documentacion'        => $documentacion,
            'descriptiva_tecnica'  => $descriptiva_tecnica,
            'producto_configurado' => [
                'status' => false,
                'msg'    => 'No existe configuración de ruta',
                'data'   => (object)[]
            ]
        ];
    }

    $idruta_producto = (int)($ruta['idruta_producto'] ?? 0);


    if ($idruta_producto <= 0) {
        $ruta['estaciones_registradas'] = [];
        return [
            'producto'             => $producto,
            'documentacion'        => $documentacion,
            'descriptiva_tecnica'  => $descriptiva_tecnica,
            'producto_configurado' => [
                'status' => true,
                'data'   => $ruta
            ]
        ];
    }


    $sqlEst = "SELECT
                    d.iddetalle,
                    d.ruta_productoid,
                    d.estacionid,
                    d.orden,
                    d.estado AS estado_detalle,

                    e.idestacion        AS est_idestacion,
                    e.estado            AS est_estado,
                    e.cve_estacion      AS est_cve_estacion,
                    e.nombre_estacion   AS est_nombre_estacion
               FROM mrp_producto_ruta_detalle d
               INNER JOIN mrp_estacion e ON e.idestacion = d.estacionid
               WHERE d.ruta_productoid = {$idruta_producto}
                 AND d.estado = 2
                 AND e.estado = 2
               ORDER BY d.orden ASC";

    $estaciones_registradas = $this->select_all($sqlEst);
    if (!is_array($estaciones_registradas)) $estaciones_registradas = [];


    if (empty($estaciones_registradas)) {
        $ruta['estaciones_registradas'] = [];
        return [
            'producto'             => $producto,
            'documentacion'        => $documentacion,
            'descriptiva_tecnica'  => $descriptiva_tecnica,
            'producto_configurado' => [
                'status' => true,
                'data'   => $ruta
            ]
        ];
    }


    $idsEstaciones = [];
    foreach ($estaciones_registradas as $r) {
        $idsEstaciones[] = (int)($r['estacionid'] ?? 0);
    }
    $idsEstaciones = array_values(array_unique(array_filter($idsEstaciones)));

    $in = implode(',', $idsEstaciones);

    $sqlEsp = "SELECT *
               FROM mrp_estacion_especificaciones
               WHERE productoid = {$this->intIdProducto}
                 AND estado = 2
                 AND estacionid IN ({$in})";
    $rowsEsp = $this->select_all($sqlEsp);
    if (!is_array($rowsEsp)) $rowsEsp = [];


    $sqlHer = "SELECT h.*, i.descripcion AS nombre_material 
               FROM mrp_estacion_herramientas AS h
			   INNER JOIN wms_inventario AS i
			   ON h.inventarioid = i.idinventario
               WHERE productoid = {$this->intIdProducto}
                 AND h.estado = 2
                 AND h.estacionid IN ({$in})";
    $rowsHer = $this->select_all($sqlHer);
    if (!is_array($rowsHer)) $rowsHer = [];


    $sqlComp = "SELECT c.*, i.descripcion AS nombre_componente
                FROM mrp_estacion_componentes AS c
				INNER JOIN wms_inventario AS i
			     ON c.inventarioid = i.idinventario
                WHERE productoid = {$this->intIdProducto}
                  AND c.estado = 2
                  AND c.estacionid IN ({$in})";
    $rowsComp = $this->select_all($sqlComp);
    if (!is_array($rowsComp)) $rowsComp = [];


    $mapEsp = [];
    foreach ($rowsEsp as $x) {
        $eid = (int)($x['estacionid'] ?? 0);
        if ($eid > 0) $mapEsp[$eid][] = $x;
    }

    $mapHer = [];
    foreach ($rowsHer as $x) {
        $eid = (int)($x['estacionid'] ?? 0);
        if ($eid > 0) $mapHer[$eid][] = $x;
    }

    $mapComp = [];
    foreach ($rowsComp as $x) {
        $eid = (int)($x['estacionid'] ?? 0);
        if ($eid > 0) $mapComp[$eid][] = $x;
    }


    foreach ($estaciones_registradas as $k => $row) {
        $eid = (int)($row['estacionid'] ?? 0);

        $esp  = $mapEsp[$eid]  ?? [];
        $her  = $mapHer[$eid]  ?? [];
        $comp = $mapComp[$eid] ?? [];

        $estaciones_registradas[$k]['especificaciones'] = !empty($esp)
            ? ['status' => true,  'data' => $esp]
            : ['status' => false, 'msg' => 'No existe registro', 'data' => []];

        $estaciones_registradas[$k]['herramientas'] = !empty($her)
            ? ['status' => true,  'data' => $her]
            : ['status' => false, 'msg' => 'No existe registro', 'data' => []];

        $estaciones_registradas[$k]['componentes'] = !empty($comp)
            ? ['status' => true,  'data' => $comp]
            : ['status' => false, 'msg' => 'No existe registro', 'data' => []];
    }

   
    $ruta['estaciones_registradas'] = $estaciones_registradas;

    $producto_configurado = [
        'status' => true,
        'data'   => $ruta
    ];

    return [
        'producto'             => $producto,
        'documentacion'        => $documentacion,
        'descriptiva_tecnica'  => $descriptiva_tecnica,
        'producto_configurado' => $producto_configurado
    ];
}





}
?>