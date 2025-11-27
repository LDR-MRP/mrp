<?php
	class Inv_lineasdproducto extends Controllers{
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
			getPermisos(MILPRODUCTO);
		}

		public function Inv_lineasdproducto()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Líneas de producto";
			$data['page_title'] = "Líneas de producto";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_inv_lineasdproducto.js";
			$this->views->getView($this,"inv_lineasdproducto",$data);
		}

        



	}


 ?>