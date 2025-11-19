<?php
	class Pbom extends Controllers{
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
			getPermisos(MPBOM);
		}

		public function Pbom()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "BOM";
			$data['page_title'] = "Plan Maestro de producción <small>BOM</small>";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_pbom.js";
			$this->views->getView($this,"pbom",$data);
		}

        



	}


 ?>