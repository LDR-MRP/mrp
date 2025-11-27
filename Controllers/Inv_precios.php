<?php
	class Inv_precios extends Controllers{
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
			getPermisos(MIPRECIOS); 
		}

		public function Inv_precios()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Precios";
			$data['page_title'] = "Precios";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_inv_precios.js";
			$this->views->getView($this,"inv_precios",$data);
		}


	}


 ?>