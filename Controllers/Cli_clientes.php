<?php
	class Cli_clientes extends Controllers{
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
			getPermisos(MCCLIENTES);
		}

		public function Cli_clientes()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Clientes";
			$data['page_title'] = "Clientes";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_cli_clientes.js";
			$this->views->getView($this,"cli_clientes",$data);
		}


	}


 ?>