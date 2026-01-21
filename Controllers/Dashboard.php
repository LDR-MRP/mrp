<?php 

	class Dashboard extends Controllers{
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
			getPermisos(MDASHBOARD);
		}

		public function dashboard()
		{
			$data['page_id'] = 2;
			$data['page_tag'] = "Dashboard - MRP";
			$data['page_title'] = "Dashboard - MRP";
			$data['page_name'] = "dashboard";
			$data['page_functions_js'] = "functions_dashboard.js";

			$anio = date('Y');
			$mes = date('m');
			if( $_SESSION['userData']['idrol'] == RADMINISTRADOR ){
				$this->views->getView($this,"dashboardAdministrador",$data);
			}else{
				$this->views->getView($this,"dashboard",$data);
			}
		}



	}
 ?>