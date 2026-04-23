<?php
class Inv_captura_vin extends Controllers
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
		getPermisos(MICAPTURAVIN);

		// 🔥 AGREGA ESTO
		$this->service = new Inv_captura_vinService();
		$this->service->model = $this->model; //  importante
	}


	public function Inv_captura_vin()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Captura de vin";
		$data['page_title'] = "Captura de vin";
		$data['page_name'] = "Captura de vin";
		$data['page_functions_js'] = "functions_inv_captura_vin.js";
		$this->views->getView($this, "inv_captura_vin", $data);
	}



	public function store()
	{
		try {
			$data = $_POST;

			$request = $this->service->store($data);

			echo json_encode($request, JSON_UNESCAPED_UNICODE);
		} catch (Exception $e) {
			echo json_encode([
				"status" => false,
				"msg" => $e->getMessage()
			]);
		}
		die();
	}
	public function getAniosVin()
	{
		$arrData = $this->model->selectAniosVin();
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();
	}

	public function getModelosVin()
	{
		$request = $this->service->getAll();
		echo json_encode($request, JSON_UNESCAPED_UNICODE);
		die();
	}
}
