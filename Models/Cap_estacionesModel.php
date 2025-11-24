<?php

class Cap_estacionesModel extends Mysql
{

	public $intIdestacion;
	public $strClave;
	public $intLinea;
	public $strNombre;
	public $strProceso;
	public $strEstandar;
	public $strUnidad;
	public $strTiempo;
	public $strMx;
	public $strDescripcion;
	public $strFecha;
	public $intEstatus;


	public function __construct()
	{
		parent::__construct();
	}




	public function generarClave()
	{
		$fechaCorta = date('ymd'); // Ej: 251121
		$prefijo = 'ES-' . $fechaCorta . '-';

		$sql = "SELECT cve_estacion 
            FROM mrp_estacion
            WHERE cve_estacion LIKE '{$prefijo}%' 
            ORDER BY cve_estacion DESC 
            LIMIT 1";

		$result = $this->select($sql);
		$numero = 1;

		if (!empty($result)) {
			$ultimaClave = $result['cve_estacion'];      // PLT-251121-0003
			$ultimoNumero = (int) substr($ultimaClave, -3);
			$numero = $ultimoNumero + 1;
		}

		return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
	}

	public function insertEstacion($claveUnica, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $fecha_creacion, $estado)
	{

		$return = 0;
		$this->strClave = $claveUnica;
		$this->intLinea = $linea;
		$this->strNombre = $nombre_estacion;
		$this->strProceso = $proceso;
		$this->strEstandar = $estandar;
		$this->strUnidad = $unidaddmedida;
		$this->strTiempo = $tiempoajuste;
		$this->strMx = $mxinput;
		$this->strDescripcion = $descripcion;
		$this->strFecha = $fecha_creacion;
		$this->intEstatus = $estado;


		$sql = "SELECT * FROM mrp_estacion WHERE nombre_estacion = '{$this->strNombre}' ";
		$request = $this->select_all($sql);

		if (empty($request)) {
			$query_insert = "INSERT INTO mrp_estacion(cve_estacion,lineaid,nombre_estacion,proceso,estandar,unidad_medida,tiempo_ajuste,mxn,descripcion,fecha_creacion,estado) VALUES(?,?,?,?,?,?,?,?,?,?,?)";
			$arrData = array(
				$this->strClave,
				$this->intLinea,
				$this->strNombre,
				$this->strProceso,
				$this->strEstandar,
				$this->strUnidad,
				$this->strTiempo,
				$this->strMx,
				$this->strDescripcion,
				$this->strFecha,
				$this->intEstatus
			);
			$request_insert = $this->insert($query_insert, $arrData);
			$return = $request_insert;
		} else {
			$return = "exist";
		}
		return $return;

	}



	public function selectEstaciones()
	{
		$sql = "SELECT est.*, li.nombre_linea
FROM  mrp_estacion AS est
INNER JOIN mrp_linea AS li ON est.lineaid = li.idlinea
		WHERE est.estado != 0 ";
		$request = $this->select_all($sql);
		return $request;
	}

	public function selectOptionPlantas()
	{
		$sql = "SELECT * FROM  mrp_linea 
					WHERE estado = 2";
		$request = $this->select_all($sql);
		return $request;
	}

	public function selectEstacion(int $idestacion)
	{
		$this->intIdestacion = $idestacion;
		$sql = "SELECT est.*, li.nombre_linea
FROM  mrp_estacion AS est
INNER JOIN mrp_linea AS li ON est.lineaid = li.idlinea
					WHERE idestacion = $this->intIdestacion";
		$request = $this->select($sql);
		return $request;
	}

	public function deleteEstacion(int $idestacion)
	{
		$this->intIdestacion = $idestacion;
		$sql = "UPDATE mrp_estacion SET estado = ? WHERE idestacion = $this->intIdestacion ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;
	}


	public function updateEstacion($idestacion, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $estado)
	{


		$this->intIdestacion = $idestacion;
		$this->intLinea = $linea;
		$this->strNombre = $nombre_estacion;
		$this->strProceso = $proceso;
		$this->strEstandar = $estandar;
		$this->strUnidad = $unidaddmedida;
		$this->strTiempo = $tiempoajuste;
		$this->strMx = $mxinput;
		$this->strDescripcion = $descripcion;
		$this->intEstatus = $estado;

		// Verificar duplicado EXCLUYENDO el mismo registro
		$sql = "SELECT * FROM mrp_estacion 
            WHERE nombre_estacion = '{$this->strNombre}' 
              AND idestacion != {$this->intIdestacion}";
		$request = $this->select_all($sql);

		if (empty($request)) {
			$sql = "UPDATE mrp_estacion 
                SET lineaid	 = ?, 
                    nombre_estacion = ?, 
                    proceso = ?,
					estandar = ?,
					unidad_medida = ?,
					tiempo_ajuste = ?,
					mxn = ?,
					descripcion = ?,
					estado = ?
                WHERE idestacion = {$this->intIdestacion}";
			$arrData = array(
				$this->intLinea,
				$this->strNombre,
				$this->strProceso,
				$this->strEstandar,
				$this->strUnidad,
				$this->strTiempo,
				$this->strMx,
				$this->strDescripcion,
				$this->intEstatus
			);
			$request = $this->update($sql, $arrData);
		} else {
			$request = "exist";
		}

		return $request;
	}





}
?>