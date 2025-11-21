<?php
class Cap_estaciones extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();
		//session_regenerate_id(true);
		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MCLINEAS);
	}

	public function Cap_estaciones()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Estaciones";
		$data['page_title'] = "Estaciones";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "functions_cap_estaciones.js";
		$this->views->getView($this, "cap_estaciones", $data);
	}

	//CAPTURAR UNA NUEVA LÍNEA
	public function setEstacion()
	{
		if ($_POST) {
			if (
				empty($_POST['nombre-linea-input'])
				|| empty($_POST['proceso-linea-input'])
				|| empty($_POST['estandar-input'])
				|| empty($_POST['unidad-medida-input'])
				|| empty($_POST['merma-fija-input'])
				|| empty($_POST['merma-proceso-input'])
				|| empty($_POST['tiempo-ajuste-input'])
				|| empty($_POST['unidad-entrada-input'])
				|| empty($_POST['unidad-salida-input'])
				|| empty($_POST['usd-input'])
				|| empty($_POST['mx-input'])
				|| empty($_POST['estado-select'])
				|| empty($_POST['descripcion-linea-textarea'])

			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
			} else {

				$intIdestacion = intval($_POST['idestacion']);
				$strNombre = strClean($_POST['nombre-linea-input']);
				$strProceso = strClean($_POST['proceso-linea-input']);
				$strEstandar = strClean($_POST['estandar-input']);
				$strUnMedida = strClean($_POST['unidad-medida-input']);
				$strMermaFija = strClean($_POST['merma-fija-input']);
				$strMermaProceso = strClean($_POST['merma-proceso-input']);
				$strTiemajuste = strClean($_POST['tiempo-ajuste-inpu']);
				$strUnEntrada = strClean($_POST['unidad-entrada-input']);
				$strUnSalida = strClean($_POST['unidad-salida-input']);
				$strUSD = strClean($_POST['usd-input']);
				$strMX = strClean($_POST['mx-input']);
				$intEstatus = strClean($_POST['estado-select']);
				$strDescripcion = strClean($_POST['descripcion-linea-textarea']);

				if ($intIdestacion == 0) {

					$claveUnica = $this->model->generarClave();

						//Crear
						if($_SESSION['permisosMod']['w']){
							$request_cateria = $this->model->insertEstacion($claveUnica,$strNombre,$strProceso,$strEstandar,$strUnMedida,$strMermaFija,$strMermaProceso,$strTiemajuste,$strUnEntrada,$strUnSalida,$strUSD,$strMX,$intEstatus,$strDescripcion);
							$option = 1;
						}

				}
			}
		}
	}





}


?>