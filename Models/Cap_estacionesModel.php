<?php

class Cap_estacionesModel extends Mysql
{

	public $intIdestacion;
	public $strClave;
	public $intPlanta;
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
	public $strTipomantenimiento;
	public $strFechaProgramada;
	public $strfechaInicio;
	public $strfechaFin;
	public $strcomentarios;
	public $strfecha_creacion;
	public $intIdmantenimiento;


	public function __construct()
	{
		parent::__construct();
	}




public function generarClave(int $idLinea)
{
    // 1. Obtener la clave de la línea (ej: PL01-LN01)
    $sqlLinea = "SELECT cve_linea 
                 FROM mrp_linea 
                 WHERE idlinea = {$idLinea}
                 LIMIT 1";

    $linea = $this->select($sqlLinea);

    if (empty($linea)) {
        // Si no encuentra la línea, puedes regresar null o lanzar excepción
        return null;
    }

    $cveLinea = $linea['cve_linea'];      // Ej: PL01-LN01
    $prefijo  = $cveLinea . '-ES';        // Ej: PL01-LN01-ES

    // 2. Buscar la última estación de ESA línea (consecutivo por línea)
    //    cve_estacion tiene forma: PL01-LN01-ES01
    //    SUBSTRING_INDEX(..., '-', -1)  -> "ES01"
    //    SUBSTRING(..., 3)              -> "01"
    $sqlEstacion = "SELECT cve_estacion
                    FROM mrp_estacion
                    WHERE lineaid = {$idLinea}          -- O lineaid, según tu columna
                      AND cve_estacion LIKE '{$prefijo}%'
                      AND estado != 0
                    ORDER BY CAST(
                        SUBSTRING(
                            SUBSTRING_INDEX(cve_estacion, '-', -1),
                            3
                        ) AS UNSIGNED
                    ) DESC
                    LIMIT 1";

    $result = $this->select($sqlEstacion);

    $numero = 1;

    if (!empty($result)) {
        $ultimaClave = $result['cve_estacion'];  // Ej: PL01-LN01-ES05
        $sufijo      = substr($ultimaClave, strrpos($ultimaClave, '-') + 1); // "ES05"
        $numStr      = substr($sufijo, 2);  // "05"
        $numero      = ((int)$numStr) + 1;
    }

    // 3. Construir clave final: PL01-LN01-ES01, PL01-LN01-ES02...
    return $prefijo . str_pad($numero, 2, '0', STR_PAD_LEFT);
}



	public function insertEstacion($claveUnica, $planta, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $fecha_creacion, $estado)
	{

		$return = 0;
		$this->strClave = $claveUnica;
		$this->intPlanta = $planta;
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


		$sql = "SELECT * FROM mrp_estacion WHERE plantaid = '{$this->intPlanta}' AND lineaid = '{$this->intLinea}' AND nombre_estacion = '{$this->strNombre}' ";
		$request = $this->select_all($sql);

		if (empty($request)) {
			$query_insert = "INSERT INTO mrp_estacion(cve_estacion,plantaid,lineaid,nombre_estacion,proceso,estandar,unidad_medida,tiempo_ajuste,mxn,descripcion,fecha_creacion,estado) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
			$arrData = array(
				$this->strClave,
				$this->intPlanta,
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
    $sql = "SELECT 
                est.*, 
                li.nombre_linea,

                /* ID del último mantenimiento (o NULL si no existe) */
                mem.idmantenimiento AS id_mantenimiento,

                /* Tipo de mantenimiento (o 1 si no tiene ninguno) */
                COALESCE(mem.mantenimiento, 1) AS estacion_mantenimiento

            FROM mrp_estacion AS est
            INNER JOIN mrp_linea AS li 
                ON est.lineaid = li.idlinea

            /* Último mantenimiento por estación */
            LEFT JOIN (
                SELECT 
                    m1.estacionid,
                    m1.idmantenimiento,
                    m1.mantenimiento
                FROM mrp_estacion_mantenimiento m1
                INNER JOIN (
                    SELECT 
                        estacionid,
                        MAX(idmantenimiento) AS ultimo_mto
                    FROM mrp_estacion_mantenimiento
                    GROUP BY estacionid
                ) m2 
                    ON m1.estacionid   = m2.estacionid
                   AND m1.idmantenimiento = m2.ultimo_mto
            ) AS mem 
                ON mem.estacionid = est.idestacion

            WHERE est.estado != 0;
";

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


	public function Mantenimiento($idestacion, $planta, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $estado)
	{


		$this->intIdestacion = $idestacion;
		$this->intPlanta = $planta;
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
            WHERE plantaid = '{$this->intPlanta}' AND lineaid = '{$this->intLinea}' AND nombre_estacion = '{$this->strNombre}' 
              AND idestacion != {$this->intIdestacion}";
		$request = $this->select_all($sql);

		if (empty($request)) {
			$sql = "UPDATE mrp_estacion 
                SET plantaid= ?,
				    lineaid = ?, 
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
				$this->intPlanta,
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

	


		public function insertManteminiento($intIdEstacion, $tipoMantenimiento, $fechaProgramada, $fechaInicio, $fechaFin, $mantenimiento, $comentarios, $fecha_creacion)
	{

		$return = 0;
		$this->intIdestacion = $intIdEstacion;
		$this->strTipomantenimiento = $tipoMantenimiento;
		$this->strFechaProgramada = $fechaProgramada;
		$this->strfechaInicio = $fechaInicio;
		$this->strfechaFin = $fechaFin;
		$this->intEstatus = $mantenimiento;
		$this->strcomentarios = $comentarios;
		$this->strfecha_creacion = $fecha_creacion;


			$query_insert = "INSERT INTO mrp_estacion_mantenimiento(estacionid,tipo,fecha_programada,fecha_inicio,fecha_fin,comentarios,fecha_creacion,mantenimiento) VALUES(?,?,?,?,?,?,?,?)";
			$arrData = array(
				$this->intIdestacion,
				$this->strTipomantenimiento,
				$this->strFechaProgramada,
				$this->strfechaInicio,
				$this->strfechaFin,
				$this->strcomentarios,
				$this->strfecha_creacion,
				$this->intEstatus
			);
			$request_insert = $this->insert($query_insert, $arrData);
			$return = $request_insert;

		return $return;

	}

	public function updateEstacionMantenimiento(int $idestacion){

		$this->intIdestacion = $idestacion;
		$sql = "UPDATE mrp_estacion SET estado = ? WHERE idestacion = $this->intIdestacion ";
		$arrData = array(3);
		$request = $this->update($sql, $arrData);
		return $request;

	}



		public function selectMantenimiento(int $idmantenimiento)
	{
		$this->intIdmantenimiento = $idmantenimiento;
		$sql = "SELECT * FROM  mrp_estacion_mantenimiento WHERE idmantenimiento = $this->intIdmantenimiento";
		$request = $this->select($sql);
		return $request;
	}



	public function updateMantenimiento(int $intIdMantenimiento, $tipoMantenimiento, $fechaProgramada, $fechaInicio, $fechaFin, $mantenimiento, $comentarios)
	{

		$this->intIdmantenimiento = $intIdMantenimiento;

		$this->strTipomantenimiento = $tipoMantenimiento;
		$this->strFechaProgramada = $fechaProgramada;
		$this->strfechaInicio = $fechaInicio;
		$this->strfechaFin = $fechaFin;
		$this->intEstatus = $mantenimiento;
		$this->strcomentarios = $comentarios;




		$sql = "UPDATE mrp_estacion_mantenimiento SET tipo = ?, fecha_programada = ?, fecha_inicio = ?, fecha_fin = ?, comentarios = ?, mantenimiento=? WHERE idmantenimiento  = $this->intIdmantenimiento ";
		$arrData = array(
			$this->strTipomantenimiento,
			$this->strFechaProgramada,
			$this->strfechaInicio,
			$this->strfechaFin,
			$this->strcomentarios,
            $this->intEstatus
		);
		$request = $this->update($sql, $arrData);
		return $request;

	}


		public function MantenimientoByEstacion(int $idestacion)
	{
		$this->intIdestacion = $idestacion;
		$sql = "SELECT * FROM mrp_estacion_mantenimiento WHERE estacionid = $this->intIdestacion";
		$request = $this->select_all($sql);
		return $request;
	}





}
?>