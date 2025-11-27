<?php
	class Inv_productossustitutos extends Controllers{
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
			getPermisos(MIPRODUCTOSSUSTITUTOS); 
		}

		public function Inv_productossustitutos()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Productos Sustitutos";
			$data['page_title'] = "Productos Sustitutos";
			$data['page_name'] = "Productos Sustitutos";
			$data['page_functions_js'] = "functions_inv_productossustitutos.js";
			$this->views->getView($this,"inv_productossustitutos",$data);
		}


	}


 ?>