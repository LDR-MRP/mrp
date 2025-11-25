<?php 

	class Cap_lineasdtrabajoModel extends Mysql
	{

	public $intIdlinea;
    public $strClave;
    public $strNombre;
    public $strFecha;
    public $intEstatus;
	public $intPlanta;


		public function __construct()
		{
			parent::__construct();
		}



public function generarClave(int $idPlanta)
{
    // 1. Obtener la clave de la planta (PL01, PL02, etc.)
    $sqlPlanta = "SELECT cve_planta 
                  FROM mrp_planta 
                  WHERE idplanta = {$idPlanta}
                  LIMIT 1";

    $planta = $this->select($sqlPlanta);

    if (empty($planta)) {
        // Si no encuentra planta, puedes manejar el error como quieras
        return null; 
    }

    $cvePlanta = $planta['cve_planta']; // Ej: PL01
    $prefijo   = $cvePlanta . '-LN';     // Ej: PL01-L

    // 2. Buscar la última línea registrada para esa planta
    $sqlLinea = "SELECT cve_linea 
                 FROM mrp_linea
                 WHERE plantaid = {$idPlanta}
                   AND cve_linea LIKE '{$prefijo}%'
                   AND estado != 0
                 ORDER BY CAST(REPLACE(cve_linea, '{$prefijo}', '') AS UNSIGNED) DESC
                 LIMIT 1";

    $result = $this->select($sqlLinea);

    $numero = 1;

    if (!empty($result)) {
        $ultimaClave = $result['cve_linea'];     // Ej: PL01-L05
        // quitar el prefijo "PL01-L" y quedarnos con "05"
        $numeroStr   = str_replace($prefijo, '', $ultimaClave);
        $numero      = ((int) $numeroStr) + 1;
    }

    // 3. Construir la clave de línea: PL01-L01, PL01-L02, etc.
    return $prefijo . str_pad($numero, 2, '0', STR_PAD_LEFT);
}

 
    public function inserLinea($claveUnica, $planta, $nombre_linea, $fecha_creacion, $intEstatus)
    {

        $return = 0;
        $this->strClave = $claveUnica;
        $this->strNombre = $nombre_linea;
		$this->intPlanta = $planta;
        $this->strFecha = $fecha_creacion;
        $this-> intEstatus = $intEstatus;


        $sql = "SELECT * FROM mrp_linea WHERE nombre_linea = '{$this->strNombre}' ";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO mrp_linea(cve_linea,plantaid,nombre_linea,fecha_creacion,estado) VALUES(?,?,?,?,?)";
            $arrData = array(
                $this->strClave,
				$this->intPlanta,
                $this->strNombre,
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



	public function selectLineas()
	{
		$sql = "SELECT li.*, pla.nombre_planta
FROM mrp_linea AS li
INNER JOIN mrp_planta AS pla ON li.plantaid = pla.idplanta
		WHERE li.estado != 0 ";
		$request = $this->select_all($sql);
		return $request;
	}

        		public function selectOptionLineas($idplanta)
		{
            $this->intPlanta = $idplanta;
			$sql = "SELECT * FROM  mrp_linea 
					WHERE estado = 2 AND plantaid = $this->intPlanta";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectLinea(int $idlinea){
			$this->intIdlinea = $idlinea;
			$sql = "SELECT * FROM mrp_linea
					WHERE idlinea = $this->intIdlinea";
			$request = $this->select($sql);
			return $request;
		}

        		public function deleteLinea(int $idlinea)
		{
			$this->intIdlinea = $idlinea;
			$sql = "UPDATE mrp_linea SET estado = ? WHERE idlinea = $this->intIdlinea ";
			$arrData = array(0);
			$request = $this->update($sql,$arrData);
			return $request;
		}

     
public function updateLinea($idlinea, $planta, $nombre_planta, $estado){
    $this->intIdlinea  = $idlinea;
    $this->intPlanta   = $planta;
    $this->strNombre   = $nombre_planta;
    $this->intEstatus  = $estado;

    // Verificar duplicado EXCLUYENDO el mismo registro
    $sql = "SELECT * FROM mrp_linea 
            WHERE nombre_linea = '{$this->strNombre}' 
              AND idlinea != {$this->intIdlinea}";
    $request = $this->select_all($sql);

    if (empty($request)) {
        $sql = "UPDATE mrp_linea 
                SET plantaid = ?, 
                    nombre_linea = ?, 
                    estado = ? 
                WHERE idlinea = {$this->intIdlinea}";
        $arrData = array(
            $this->intPlanta,
            $this->strNombre,
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