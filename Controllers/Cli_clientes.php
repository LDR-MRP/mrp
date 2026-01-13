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
		$data['page_tag'] = "Distribuidores";
		$data['page_title'] = "Distribuidores";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "functions_cli_clientes.js";
		$this->views->getView($this, "cli_clientes", $data);
	}

	public function index()
	{

		$arrData = $this->model->selectDistribuidores();
		for ($i = 0; $i < count($arrData); $i++) {
			$btnView = '';
			$btnEdit = '';
			$btnDelete = '';

			if ($arrData[$i]['estado'] == 2) {
				$arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
			} else if ($arrData[$i]['estado'] == 1) {
				$arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
			}

			if ($_SESSION['permisosMod']['r']) {

				$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver distribuidor" onClick="fntViewDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
			}
			if ($_SESSION['permisosMod']['u']) {

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar distribuidor" onClick="fntEditDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
			}
			if ($_SESSION['permisosMod']['d']) {
				$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar distribuidor" onClick="fntDelDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
			}
			$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
		}
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

		die();
	}

	public function setDistribuidor()
	{
		if (!$_POST) {
			return;
		}

		if (
			empty($_POST['grupo_id']) ||
			empty($_POST['nombre-distribuidores-input']) ||
			empty($_POST['razon-distribuidores-input']) ||
			empty($_POST['rfc-distribuidores-input']) ||
			empty($_POST['repve-distribuidores-input']) ||
			empty($_POST['telefono-distribuidores-input'])
		) {
			echo json_encode(['status' => false, 'msg' => 'Datos obligatorios faltantes']);
			return;
		}

		$idDistribuidor = intval($_POST['iddistribuidor']);
		$grupo_id = intval($_POST['grupo_id']);
		$nombre_comercial = strClean($_POST['nombre-distribuidores-input']);
		$razon_social = strClean($_POST['razon-distribuidores-input']);
		$rfc = strClean($_POST['rfc-distribuidores-input']);
		$repve = strClean($_POST['repve-distribuidores-input']);
		$plaza = strClean($_POST['plaza-distribuidores-input']);
		$estatus = $_POST['estatus-select'];
		$tipo_negocio = $_POST['tipo_negocio-select'];
		$telefono = strClean($_POST['telefono-distribuidores-input']);
		$telefono_alt = strClean($_POST['telefono_alt-input'] ?? '');

		// INSERT / UPDATE DISTRIBUIDOR
		if ($idDistribuidor == 0) {

			$idDistribuidor = $this->model->insertDistribuidor(
				$grupo_id,
				$nombre_comercial,
				$razon_social,
				$rfc,
				$repve,
				$plaza,
				$estatus,
				$tipo_negocio,
				$telefono,
				$telefono_alt,
			);

			$option = 'insert';
		} else {

			$this->model->updateDistribuidor(
				$idDistribuidor,
				$grupo_id,
				$nombre_comercial,
				$razon_social,
				$rfc,
				$repve,
				$plaza,
				$estatus,
				$tipo_negocio,
				$telefono,
				$telefono_alt,
			);

			// LIMPIAR DIRECCIONES
			$this->model->deleteDirecciones($idDistribuidor);
			$this->model->deleteDireccionFiscal($idDistribuidor);

			$option = 'update';
		}

		// DIRECCIÓN PRINCIPAL
		$this->model->insertDireccion(
			$idDistribuidor,
			$_POST['tipo-select'],
			strClean($_POST['calle-distribuidores-input']),
			strClean($_POST['numero_ext-distribuidores-input'] ?? ''),
			strClean($_POST['numero_int-distribuidores-input'] ?? ''),
			strClean($_POST['colonia-distribuidores-input']),
			strClean($_POST['codigo_postal-distribuidores-input']),
			intval($_POST['listPaises']),
			intval($_POST['listEstados']),
			intval($_POST['listMunicipios'])
		);

		// DIRECCIÓN FISCAL
		$mismaDireccion = isset($_POST['mismaDireccion']) && $_POST['mismaDireccion'] == 1;

		if ($mismaDireccion) {

			// COPIAMOS LA DIRECCIÓN PRINCIPAL
			$this->model->insertDireccionFiscal(
				$idDistribuidor,
				strClean($_POST['calle-distribuidores-input']),
				strClean($_POST['numero_ext-distribuidores-input'] ?? ''),
				strClean($_POST['numero_int-distribuidores-input'] ?? ''),
				strClean($_POST['colonia-distribuidores-input']),
				strClean($_POST['codigo_postal-distribuidores-input']),
				intval($_POST['listPaises']),
				intval($_POST['listEstados']),
				intval($_POST['listMunicipios'])
			);
		} else {

			// DIRECCIÓN FISCAL 
			$this->model->insertDireccionFiscal(
				$idDistribuidor,
				strClean($_POST['calle_fiscal']),
				strClean($_POST['numero_ext_fiscal'] ?? ''),
				strClean($_POST['numero_int_fiscal'] ?? ''),
				strClean($_POST['colonia_fiscal']),
				strClean($_POST['codigo_postal_fiscal']),
				intval($_POST['listPaisesFiscal']),
				intval($_POST['listEstadosFiscal']),
				intval($_POST['listMunicipiosFiscal'])
			);
		}

		if (!empty($_POST['listModelos']) && is_array($_POST['listModelos'])) {

			if ($option === 'update') {
				$this->model->deleteDistribuidorModelos($idDistribuidor);
			}

			foreach ($_POST['listModelos'] as $idModelo) {
				$this->model->insertDistribuidorModelo(
					$idDistribuidor,
					intval($idModelo)
				);
			}
		}

		echo json_encode([
			'status' => true,
			'msg' => $option === 'insert'
				? 'Distribuidor registrado correctamente'
				: 'Distribuidor actualizado correctamente',
			'tipo' => $option
		]);
	}

	public function show($iddistribuidor)
	{
		if ($_SESSION['permisosMod']['r']) {
			$intIddistribuidor = intval($iddistribuidor);
			if ($intIddistribuidor > 0) {
				$arrData = $this->model->selectDistribuidor($intIddistribuidor);
				if (empty($arrData)) {
					$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
				} else {

					$arrResponse = array('status' => true, 'data' => $arrData);
				}
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}

	public function destroy()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$iddistribuidor = intval($_POST['iddistribuidor']);
				$requestDelete = $this->model->deleteDistribuidor($iddistribuidor);
				if ($requestDelete) {
					$arrResponse = array('status' => true, 'msg' => 'El registro fue eliminado satisfactoriamente.');
				} else {
					$arrResponse = array('status' => false, 'msg' => 'Error al eliminar el registro.');
				}
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}

	public function getSelectGrupos()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionGrupos();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre']  . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function getSelectModelos()
	{
		$htmlOptions = '';
		$arrData = $this->model->selectOptionModelos();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idlineaproducto'] . '">' . $arrData[$i]['cve_linea_producto']  . '</option>';
				}
			}
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
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre']  . '</option>';
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
}
