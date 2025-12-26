<?php
	class Plan_planeacion extends Controllers{
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
			getPermisos(MPPLANPRODUCCION);
		}

		public function Plan_planeacion()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Planeación";
			$data['page_title'] = "Plan de producción";
			$data['page_name'] = "Planeación";
			$data['page_functions_js'] = "functions_plan_planeacion.js";
			$this->views->getView($this,"plan_planeacion",$data);
		}

        



	}


 ?>