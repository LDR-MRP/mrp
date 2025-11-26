<?php
	class Pla_productos extends Controllers{
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
			getPermisos(MPPRODUCTOS);
		}

		public function Pla_productos()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Productos";
			$data['page_title'] = "Productos terminados";
			$data['page_name'] = "Productos";
			$data['page_functions_js'] = "functions_pla_productos.js";
			$this->views->getView($this,"pla_productos",$data);
		}

        



	}


 ?>