<?php

class Cap_plantasModel extends Mysql
{
    public $intIdplanta;
    public $strClave;
    public $strNombre;
    public $strFecha;
    public $intEstatus;
    public $strDireccion;


    public function __construct()
    {
        parent::__construct();
    }

    public function generarClave()
{
    $fechaCorta = date('ymd'); // Ej: 251121
    $prefijo = 'PLT-' . $fechaCorta . '-';

    $sql = "SELECT cve_planta 
            FROM mrp_planta
            WHERE cve_planta LIKE '{$prefijo}%' 
            ORDER BY cve_planta DESC 
            LIMIT 1";

    $result = $this->select($sql);
    $numero = 1;

    if (!empty($result)) {
        $ultimaClave = $result['cve_planta'];      // PLT-251121-0003
        $ultimoNumero = (int) substr($ultimaClave, -3); 
        $numero = $ultimoNumero + 1;
    }

    return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
}

   

    public function inserPlanta($claveUnica, $nombre_planta, $fecha_creacion, $direccion, $intEstatus)
    {

        $return = 0;
        $this->strClave = $claveUnica;
        $this->strNombre = $nombre_planta;
        $this->strFecha = $fecha_creacion;
        $this->strDireccion = $direccion;
        $this-> intEstatus = $intEstatus;


        $sql = "SELECT * FROM mrp_planta WHERE nombre_planta = '{$this->strNombre}' ";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO mrp_planta(cve_planta,nombre_planta,fecha_creacion,direccion,estado) VALUES(?,?,?,?,?)";
            $arrData = array(
                $this->strClave,
                $this->strNombre,
                $this->strFecha,
                $this->strDireccion,
                $this->intEstatus
            );
            $request_insert = $this->insert($query_insert, $arrData);
            $return = $request_insert;
        } else {
            $return = "exist";
        }
        return $return;

    }

    
		public function selectPlantas()
		{
			$sql = "SELECT * FROM  mrp_planta 
					WHERE estado != 0 ";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectOptionPlantas()
		{
			$sql = "SELECT * FROM  mrp_planta 
					WHERE estado = 2";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectPlanta(int $idplanta){
			$this->intIdplanta = $idplanta;
			$sql = "SELECT * FROM mrp_planta
					WHERE idplanta = $this->intIdplanta";
			$request = $this->select($sql);
			return $request;
		}

        		public function deletePlanta(int $idplanta)
		{
			$this->intIdplanta = $idplanta;
			$sql = "UPDATE mrp_planta SET estado = ? WHERE idplanta = $this->intIdplanta ";
			$arrData = array(0);
			$request = $this->update($sql,$arrData);
			return $request;
		}

        
		public function updatePlanta($idplanta, $nombre_planta, $direccion, $estado){
        $this->intIdplanta = $idplanta;
        $this->strNombre = $nombre_planta;
        $this->strDireccion = $direccion;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM mrp_planta WHERE nombre_planta = '{$this->strNombre}' AND idplanta != {$this->intIdplanta}";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE mrp_planta SET nombre_planta = ?, direccion = ?, estado = ?  WHERE idplanta = $this->intIdplanta ";
            $arrData = array(
                $this->strNombre,
                $this->strDireccion,
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