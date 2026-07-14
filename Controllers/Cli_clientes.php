<?php
class Cli_clientes extends Controllers
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
		getPermisos(MCCLIENTES);
	}
	
	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA REDIRIGIR A LA VISTA PRINCIPAL INDEX.PHP INLCUYENDO EL ARCHIVO JS 
	|--------------------------------------------------------------------------
	*/

	public function Cli_clientes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_functions_js'] = "/clientes/index.js";
		$this->views->getView($this, "index", $data);
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES
	|--------------------------------------------------------------------------
	*/

	public function getTodos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectTodos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS DISTRIBUIDORES
	|--------------------------------------------------------------------------
	*/
	public function getDistribuidores()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectDistribuidores();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES INTERNOS
	|--------------------------------------------------------------------------
	*/
	public function getInternos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectInternos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES EXTERNOS
	|--------------------------------------------------------------------------
	*/
	public function getExternos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectExternos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES GUBERNAMENTALES
	|--------------------------------------------------------------------------
	*/
	public function getGubernamentales()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectGubernamentales();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			echo json_encode(
				$arrData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

		} catch (Throwable $error) {

			http_response_code(500);

			echo json_encode([
				'status' => false,
				'message' => 'Error al consultar los clientes.',
				'error' => $error->getMessage()
			], JSON_UNESCAPED_UNICODE);
		}

		exit;
	}

	/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA REDIRIGIR A LA VISTA DE CREAR NUEVO CLIENTE INCLUYENDO EL ARCHIVO JS
	|--------------------------------------------------------------------------
	*/
	public function create()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "/clientes/create.js";
		$this->views->getView($this, "create", $data);
	}

		/*
	|--------------------------------------------------------------------------
	| FUNCIÓN PARA REDIRIGIR A LA VISTA DE ACCESOS A CLIENTES INCLUYENDO SU ARCHIVO JS
	|--------------------------------------------------------------------------
	*/
	public function accesos()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_functions_js'] = "/clientes/accesos.js";
		$this->views->getView($this, "accesos", $data);
	}



	public function getSelectRegimenFiscal($tipoPersona = null)
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionRegimenFiscal($tipoPersona);

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' .
				$row['c_regimen_fiscal'] . ' - ' . $row['descripcion'] .
				'</option>';
		}

		echo $htmlOptions;
		die();
	}



	public function getSelectPaises()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionPaises();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function getSelectEstados($pais_id)
	{
		$htmlOptions = '<option value="">--Seleccione estado--</option>';
		$arrData = $this->model->selectEstadosByPais(intval($pais_id));

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
		}

		echo $htmlOptions;
		die();
	}

	public function getSelectMunicipios($estado_id)
	{
		$htmlOptions = '<option value="">--Seleccione municipio--</option>';
		$arrData = $this->model->selectMunicipiosByEstado(intval($estado_id));

		foreach ($arrData as $row) {
			$htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
		}

		echo $htmlOptions;
		die();
	}

	public function getRegionByEstado($estado_id)
	{
		$estado_id = intval($estado_id);

		$arrData = $this->model->selectRegionByEstado($estado_id);

		if (!empty($arrData)) {
			echo json_encode([
				"status" => true,
				"data" => $arrData
			]);
		} else {
			echo json_encode([
				"status" => false
			]);
		}
		die();
	}
}
