<?php

class Inv_lineasdproductoModel extends Mysql
{
    public $intIdLineaProducto;
    public $strClave;
    public $strCveLineaProducto;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;


    public function __construct()
    {
        parent::__construct();
    }



   

    public function inserLineaProducto($cve_linea_producto, $descripcion, $fecha_creacion,  $intEstatus)
    {
 
        $return = 0;
        $this->strCveLineaProducto = $cve_linea_producto; 
        $this->strDescripcion = $descripcion;
        $this->strFecha = $fecha_creacion;
        $this-> intEstatus = $intEstatus;


        $sql = "SELECT * FROM wms_linea_producto WHERE cve_linea_producto = '{$this->strCveLineaProducto}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO wms_linea_producto(cve_linea_producto,descripcion,fecha_creacion,estado) VALUES(?,?,?,?)";
            
            $arrData = array(
                $this->strCveLineaProducto,
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

    
		public function selectLineasProductos()
		{
			$sql = "SELECT * FROM  wms_linea_producto 
					WHERE estado != 0 ";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectOptionLineasProductos()
		{
			$sql = "SELECT * FROM  wms_linea_producto 
					WHERE estado = 2";
			$request = $this->select_all($sql);
			return $request;
		}

        		public function selectLineaProducto(int $idlineaproducto){
			$this->intIdLineaProducto = $idlineaproducto;
			$sql = "SELECT * FROM wms_linea_producto
					WHERE idlineaproducto = $this->intIdLineaProducto";
			$request = $this->select($sql);
			return $request;
		}

        		public function deleteLineaProducto(int $idlineaproducto)
		{
			$this->intIdLineaProducto = $idlineaproducto;
			$sql = "UPDATE wms_linea_producto SET estado = ? WHERE idlineaproducto = $this->intIdLineaProducto ";
			$arrData = array(0);
			$request = $this->update($sql,$arrData);
			return $request;
		}

        
		public function updateLineaProducto($idlineaproducto, $cve_linea_producto, $descripcion, $estado){
        $this->intIdLineaProducto = $idlineaproducto;
        $this->strCveLineaProducto = $cve_linea_producto;
        $this->strDescripcion = $descripcion;
        $this->intEstatus = $estado;

        $sql = "SELECT * FROM wms_linea_producto WHERE cve_linea_producto = '{$this->strCveLineaProducto}' AND idlineaproducto != {$this->intIdLineaProducto}";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE wms_linea_producto SET cve_linea_producto = ?, descripcion = ?, estado = ?  WHERE idlineaproducto = $this->intIdLineaProducto ";
            $arrData = array(
                $this->strCveLineaProducto,
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