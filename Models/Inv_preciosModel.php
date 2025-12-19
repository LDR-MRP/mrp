<?php

class Inv_preciosModel extends Mysql
{
    public $intidprecio;
    public $strClave;
    public $strCvePrecio;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;
    public $intImpuesto;


    public function __construct()
    {
        parent::__construct();
    }



   

    public function inserPrecio($cve_precio, $descripcion, $impuesto, $fecha_creacion,  $intEstatus)
    {
 
        $return = 0;
        $this->strCvePrecio = $cve_precio; 
        $this->strDescripcion = $descripcion;
        $this->intImpuesto = $impuesto;
        $this->strFecha = $fecha_creacion;
        $this-> intEstatus = $intEstatus;


        $sql = "SELECT * FROM wms_precios WHERE cve_precio = '{$this->strCvePrecio}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO wms_precios(cve_precio,descripcion,con_impuesto,fecha_creacion,estado) VALUES(?,?,?,?,?)";
            
            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->intImpuesto,
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

    
		public function selectPrecios()
		{
			$sql = "SELECT * FROM  wms_precios 
					WHERE estado != 0 ";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectOptionPrecios()
		{
			$sql = "SELECT * FROM  wms_precios 
					WHERE estado = 2";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectPrecio(int $idprecio){
			$this->intidprecio = $idprecio;
			$sql = "SELECT * FROM wms_precios
					WHERE idprecio = $this->intidprecio";
			$request = $this->select($sql);
			return $request;
		}

        		public function deletePrecio(int $idprecio)
		{
			$this->intidprecio = $idprecio;
			$sql = "UPDATE wms_precios SET estado = ? WHERE idprecio = $this->intidprecio ";
			$arrData = array(0);
			$request = $this->update($sql,$arrData);
			return $request;
		}

        
		public function updatePrecio($idprecio, $cve_precio, $descripcion, $impuesto, $estado){
        $this->intidprecio = $idprecio;
        $this->strCvePrecio = $cve_precio;
        $this->strDescripcion = $descripcion;
        $this->intImpuesto = $impuesto;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM wms_precios WHERE cve_precio = '{$this->strCvePrecio}' AND idprecio != {$this->intidprecio}";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE wms_precios SET cve_precio = ?, descripcion = ?, con_impuesto = ?, estado = ?  WHERE idprecio = $this->intidprecio ";
            $arrData = array(
                $this->strCvePrecio,
                $this->strDescripcion,
                $this->intImpuesto,
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