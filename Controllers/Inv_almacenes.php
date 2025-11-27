<?php
	class Inv_almacenes extends Controllers{
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
			getPermisos(MIALMACENES);
		}

		public function Inv_almacenes()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Almacenes";
			$data['page_title'] = "Almacenes";
			$data['page_name'] = "almacenes";
			$data['page_functions_js'] = "functions_inv_almacenes.js";
			$this->views->getView($this,"inv_almacenes",$data);
		}


	}


 ?>