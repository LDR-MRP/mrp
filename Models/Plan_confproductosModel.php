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



	public function __construct()
	{
		parent::__construct();
	}

	public function generarClave()
	{
		$fecha = date('Ymd');
		$prefijo = 'C-' . $fecha . '-';

		$sql = "SELECT cve_producto FROM mrp_productos 
            WHERE cve_producto LIKE '$prefijo%' 
            ORDER BY cve_producto DESC 
            LIMIT 1";

		$result = $this->select($sql);
		$numero = 1;

		if (!empty($result)) {
			$ultimoCodigo = $result['cve_producto'];
			$ultimoNumero = (int) substr($ultimoCodigo, -4);
			$numero = $ultimoNumero + 1;
		}

		return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);

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
                IFNULL(d.iddescriptiva, 0)   AS iddescriptiva,
                IFNULL(r.idruta_producto, 0) AS idruta_producto
            FROM mrp_productos AS p
            LEFT JOIN mrp_productos_descriptiva AS d
                ON d.productoid = p.idproducto
            LEFT JOIN mrp_producto_ruta AS r
                ON r.productoid = p.idproducto
            WHERE p.idproducto = {$this->intIdProducto}";

    $request = $this->select($sql);
    return $request;
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
		$this->strDireccion = $direccion;
		$this->strEquipamiento = $equipamiento;
		$this->strFecha = $fecha_creacion;


		$query_insert = "INSERT INTO mrp_productos_descriptiva(
        productoid, marca, modelo, largo_total, distancia_ejes, peso_bruto_vehicular,
        motor, cilindros, desplazamiento_c, tipo_combustible, potencia, torque, transmision,
        eje_delantero, suspension_delantera, eje_trasero, suspension_trasera, llantas, sistema_frenos,
        asistencias, sistema_electrico, capacidad_combustible, direccion, equipamiento, fecha_creacion
    ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";


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
		$this->strDireccion = $direccion;
		$this->strEquipamiento = $equipamiento;

		$sql = "UPDATE mrp_productos_descriptiva SET marca=?, modelo=?, largo_total=?, distancia_ejes=?, peso_bruto_vehicular=?,
        motor=?, cilindros=?, desplazamiento_c=?, tipo_combustible=?, potencia=?, torque=?, transmision=?,
        eje_delantero=?, suspension_delantera=?, eje_trasero=?, suspension_trasera=?, llantas=?, sistema_frenos=?,
        asistencias=?, sistema_electrico=?, capacidad_combustible=?, direccion=?, equipamiento=? WHERE iddescriptiva = $this->intDescriptiva";

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
			$this->strDireccion,
			$this->strEquipamiento
		);
		$request = $this->update($sql, $arrData);
		return $request;

	}


	public function selectOptionEstacionesByLinea($idlinea)
	{
		$this->intlinea = $idlinea;
		$sql = "SELECT * FROM  mrp_estacion 
					WHERE estado = 2 AND lineaid = $this->intlinea";
		$request = $this->select_all($sql);
		return $request;

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







}
?>