<?php

class Plan_bomcomponentesModel extends Mysql
{
	public $intIdAlmacen;

	public function __construct()
	{
		parent::__construct();
	}



	public function selectOptionAlmacenes()
	{
		// $this->intIdAlmacen = $idalmacen;
		$sql = "SELECT * FROM wms_almacenes";
		$request = $this->select_all($sql);
		return $request;
	} 

		public function selectAlmacen($idalmacen)
	{
		$this->intIdAlmacen = $idalmacen;
		$sql = "SELECT * FROM wms_almacenes WHERE idalmacen = $this->intIdAlmacen";
		$request = $this->select($sql);
		return $request;
	} 

	

}
?>