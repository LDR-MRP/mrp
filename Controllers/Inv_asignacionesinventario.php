<?php
	class Inv_asignacionesinventario extends Controllers{
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
			getPermisos(MIASIGNACIONESINV); 
		}

		public function Inv_asignacionesinventario()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Asignaciones de Inventario";
			$data['page_title'] = "Asignaciones de Inventario";
			$data['page_name'] = "Asignaciones de Inventario";
			$data['page_functions_js'] = "functions_inv_asignacionesinventario.js";
			$this->views->getView($this,"inv_asignacionesinventario",$data);
		}


	}


 ?>