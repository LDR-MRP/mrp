<?php

class Plan_bomcomponentesModel extends Mysql
{
	public $intIdinventario;
	public $strClave;
	public $intlineaproductoid;
	public $strDescripcion;
	public $strFecha;
	public $intEstado;
	public $intIdComponente;
	public $strDocumento;
	public $intIdDocumento;

	public function __construct()
	{
		parent::__construct();
	}

	public function generarClave()
	{
		$fecha = date('Ymd');
		$prefijo = 'C-' . $fecha . '-';

		$sql = "SELECT cve_componente FROM mrp_componentes 
            WHERE cve_componente LIKE '$prefijo%' 
            ORDER BY cve_componente DESC 
            LIMIT 1";

		$result = $this->select($sql);
		$numero = 1;

		if (!empty($result)) {
			$ultimoCodigo = $result['cve_componente'];
			$ultimoNumero = (int) substr($ultimoCodigo, -4);
			$numero = $ultimoNumero + 1;
		}

		return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT);

	}


	public function selectOptionProductos()
	{

		$sql = "SELECT 
            inv.*,
			lp.cve_linea as linea_clave,
            lp.descripcion AS linea_descripcion
        FROM wms_inventario AS inv
        INNER JOIN wms_linea_producto AS lp 
        ON inv.lineaproductoid = lp.idlinea WHERE inv.tipo_ele ='P'";

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
		$sql = "SELECT * FROM wms_linea_producto  WHERE status !=0";
		$request = $this->select_all($sql);
		return $request;
	}




	public function inserComponente($claveUnica, $inventarioid, $lineaproductoid, $descripcion, $fecha_creacion, $estado)
	{

		$return = 0;
		$this->strClave = $claveUnica;
		$this->intIdinventario = $inventarioid;
		$this->intlineaproductoid = $lineaproductoid;
		$this->strDescripcion = $descripcion;
		$this->strFecha = $fecha_creacion;
		$this->intEstado = $estado;


		// $sql = "SELECT * FROM  viaticos_generales WHERE usuarioid = '{$this->intUsuarioid}' ";
		// $request = $this->select_all($sql);

		// if(empty($request))
		// {
		$query_insert = "INSERT INTO mrp_componentes(cve_componente,inventarioid,lineaproductoid,descripcion,fecha_creacion,estado) VALUES(?,?,?,?,?,?)";
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
		// }else{
		// 	$return = "exist";
		// }
		return $return;
	}
	public function selectComponentes()
	{

		$sql = "SELECT com.*, 
               inv.cve_art,
               inv.descripcion AS descripcion_producto,
               lp.cve_linea,
               lp.descripcion AS descripcion_linea
        FROM  mrp_componentes AS com
        INNER JOIN wms_inventario AS inv ON com.inventarioid = inv.idinventario
        INNER JOIN wms_linea_producto AS lp ON lp.idlinea = com.lineaproductoid
        WHERE com.estado != 0;";
		$request = $this->select_all($sql);
		return $request;

	}



	public function insertDocumento($intIdComponente, $descripcion, $nombreDocumento, $fecha_creacion)
	{

		$return = 0;
		$this->intIdComponente = $intIdComponente;
		$this->strDescripcion = $descripcion;
		$this->strDocumento = $nombreDocumento;
		$this->strFecha = $fecha_creacion;



		// $sql = "SELECT * FROM  viaticos_generales WHERE usuarioid = '{$this->intUsuarioid}' ";
		// $request = $this->select_all($sql);

		// if(empty($request))
		// {
		$query_insert = "INSERT INTO mrp_documentos_componentes(componenteid,descripcion,ruta,fecha_creacion) VALUES(?,?,?,?)";
		$arrData = array(
			$this->intIdComponente,
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

	public function selectDocumentosByComponente($componenteid)
	{
		$this->intIdComponente = $componenteid;
		$sql = "SELECT doc.*, com.inventarioid, inv.cve_art, inv.descripcion as descripcion_articulo FROM mrp_documentos_componentes AS doc
		INNER JOIN mrp_componentes AS com ON com.idcomponente = doc.componenteid
		INNER JOIN wms_inventario AS inv ON inv.idinventario = com.inventarioid
		WHERE doc.estado !=0 AND doc.componenteid = $this->intIdComponente ";
		$request = $this->select_all($sql);
		return $request;

	}

	public function deleteDocumento($iddocumento)
	{

		$this->intIdDocumento = $iddocumento;
		$sql = "UPDATE mrp_documentos_componentes SET estado = ? WHERE iddocumento = $this->intIdDocumento ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;

	}

	
		public function selectComponente(int $componenteid){
			$this->intIdComponente = $componenteid;
			$sql = "SELECT * FROM mrp_componentes
					WHERE idcomponente = $this->intIdComponente";
			$request = $this->select($sql);
			return $request;
		}






}
?>