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

	public function Cli_clientes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Clientes";
		$data['page_title'] = "Clientes";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "/clientes/index.js";
		$this->views->getView($this, "index", $data);
	}

	public function getTodos()
	{
		header('Content-Type: application/json; charset=utf-8');

		try {

			$arrData = $this->model->selectTodos();

			if (!is_array($arrData)) {
				$arrData = [];
			}

			for ($i = 0; $i < count($arrData); $i++) {

				$btnView = '';
				$btnAccess = '';
				$btnEdit = '';
				$btnDelete = '';

				$idcliente = intval($arrData[$i]['idcliente'] ?? 0);

				if (!empty($_SESSION['permisosMod']['r'])) {
					$btnView = '
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-info"
                        title="Ver cliente"
                        onclick="fntViewCliente(' . $idcliente . ')"
                    >
                        <i class="ri-eye-line"></i>
                    </button>
                ';
				}

				if (!empty($_SESSION['permisosMod']['r'])) {
					$btnAccess = '
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-primary"
                        title="Administrar accesos"
                        onclick="fntAccesosCliente(' . $idcliente . ')"
                    >
                        <i class="ri-key-2-line"></i>
                    </button>
                ';
				}

				if (!empty($_SESSION['permisosMod']['u'])) {
					$btnEdit = '
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-warning"
                        title="Editar cliente"
                        onclick="fntEditCliente(' . $idcliente . ')"
                    >
                        <i class="ri-pencil-line"></i>
                    </button>
                ';
				}

				if (!empty($_SESSION['permisosMod']['d'])) {
					$btnDelete = '
                    <button
                        type="button"
                        class="btn btn-sm btn-soft-danger"
                        title="Eliminar cliente"
                        onclick="fntDelCliente(' . $idcliente . ')"
                    >
                        <i class="ri-delete-bin-6-line"></i>
                    </button>
                ';
				}

				$arrData[$i]['options'] = '
                <div class="d-flex justify-content-center gap-1">
                    ' . $btnView . '
                    ' . $btnAccess . '
                    ' . $btnEdit . '
                    ' . $btnDelete . '
                </div>
            ';
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
