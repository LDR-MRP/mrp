<?php
	class Inv_inventario extends Controllers{
		public function __construct()
		{
			parent::__construct();
			session_start();
			//session_regenerate_id(true);
			if(empty($_SESSION['login']))
			{
				header('Location: '.base_url().'/login');
				die();
			} 
			getPermisos(MIINVENTARIO);
		}

		public function Inv_inventario()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Inventario";
			$data['page_title'] = "Inventario";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_inv_inventario.js";
			$this->views->getView($this,"inv_inventario",$data);
		}


	}


 ?>