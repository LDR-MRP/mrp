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

				$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver cliente" onClick="fntViewDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
			}
			if ($_SESSION['permisosMod']['u']) {

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar cliente" onClick="fntEditDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
			}
			if ($_SESSION['permisosMod']['d']) {
				$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar cliente" onClick="fntDelDistribuidor(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
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
			empty($_POST['nombre-distribuidores-input']) ||
			empty($_POST['razon-distribuidores-input']) ||
			empty($_POST['rfc-distribuidores-input']) ||
			empty($_POST['repve-distribuidores-input']) ||
			empty($_POST['plaza-distribuidores-input']) ||
			empty($_POST['clasificacion-distribuidores-input']) ||
			empty($_POST['telefono-distribuidores-input'])
		) {
			echo json_encode(['status' => false, 'msg' => 'Datos obligatorios faltantes']);
			return;
		}

		$idDistribuidor = intval($_POST['iddistribuidor']);
		$tipo_cliente_id = intval($_POST['tipo_cliente_id']);
		$regimen_fiscal_id = strClean($_POST['regimen_fiscal_id']);
		$tipo_persona = strClean($_POST['tipo_persona-select']);
		$correo = strClean($_POST['correo-distribuidores-input']);
		$matriz_id = empty($_POST['matriz_id'])
			? null
			: (int) $_POST['matriz_id'];
		$grupo_id = intval($_POST['grupo_id']);
		$nombre_comercial = strClean($_POST['nombre-distribuidores-input']);
		$razon_social = strClean($_POST['razon-distribuidores-input']);
		$rfc = strClean($_POST['rfc-distribuidores-input']);
		$repve = strClean($_POST['repve-distribuidores-input']);
		$plaza = strClean($_POST['plaza-distribuidores-input']);
		$clasificacion = strClean($_POST['clasificacion-distribuidores-input']);
		$estatus = $_POST['estatus-select'];
		$tipo_negocio = $_POST['tipo_negocio-select'];
		$telefono = strClean($_POST['telefono-distribuidores-input']);
		$telefono_alt = strClean($_POST['telefono_alt-input'] ?? '');

		$nombre_fisica = strClean($_POST['nombre_fisica-distribuidores-input']);
		$apellido_paterno = strClean($_POST['apellido_paterno-distribuidores-input']);
		$apellido_materno = strClean($_POST['apellido_materno-distribuidores-input']);
		$fecha_nacimiento = strClean($_POST['fecha_nacimiento-distribuidores-input']);
		$curp = strClean($_POST['curp-distribuidores-input']);

		$representante_legal = strClean($_POST['representante_legal-distribuidores-input']);
		$domicilio_fiscal = strClean($_POST['domicilio_fiscal-distribuidores-input']);

		if (strlen($nombre_comercial) < 3) {
			$arrResponse = ["status" => false, "msg" => "El nombre debe tener al menos 3 caracteres"];
			echo json_encode($arrResponse);
			die();
		}

		if (strlen($razon_social) < 3) {
			$arrResponse = ["status" => false, "msg" => "La razon social debe tener al menos 3 caracteres"];
			echo json_encode($arrResponse);
			die();
		}

		$rfc = strtoupper(trim($rfc));

		if (!preg_match('/^([A-ZÑ&]{3,4})[0-9]{6}([A-Z0-9]{3})$/', $rfc)) {
			$arrResponse = [
				"status" => false,
				"msg" => "El RFC no tiene un formato válido para persona física o moral"
			];
			echo json_encode($arrResponse);
			die();
		}

		if (strlen($repve) < 1) {
			$arrResponse = ["status" => false, "msg" => "El no de repuve debe tener al menos 1 caracter"];
			echo json_encode($arrResponse);
			die();
		}

		if (strlen($plaza) < 3) {
			$arrResponse = ["status" => false, "msg" => "La plaza debe tener al menos 3 caracter"];
			echo json_encode($arrResponse);
			die();
		}

		if (strlen($clasificacion) < 1) {
			$arrResponse = ["status" => false, "msg" => "La clasificacion debe tener al menos 1 caracter"];
			echo json_encode($arrResponse);
			die();
		}

		// Validaciones SOLO si es Persona Física
		if ($tipo_persona === '1') {

			if (strlen($nombre_fisica) < 3) {
				echo json_encode([
					"status" => false,
					"msg" => "El nombre debe tener al menos 3 caracteres"
				]);
				die();
			}

			if (strlen($apellido_paterno) < 3) {
				echo json_encode([
					"status" => false,
					"msg" => "El apellido paterno debe tener al menos 3 caracteres"
				]);
				die();
			}

			if (strlen($apellido_materno) < 3) {
				echo json_encode([
					"status" => false,
					"msg" => "El apellido materno debe tener al menos 3 caracteres"
				]);
				die();
			}

			$curp = strtoupper(trim($curp));

			if (!preg_match('/^[A-Z][AEIOUX][A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z][0-9]$/', $curp)) {
				echo json_encode([
					"status" => false,
					"msg" => "La CURP no tiene un formato válido"
				]);
				die();
			}
		}

		// Validaciones SOLO si es Persona Moral
		if ($tipo_persona === '2') {

			if (strlen($representante_legal) < 3) {
				echo json_encode([
					"status" => false,
					"msg" => "El representante legal debe tener al menos 3 caracteres"
				]);
				die();
			}

			if (strlen($domicilio_fiscal) < 10) {
				echo json_encode([
					"status" => false,
					"msg" => "El domicilio fiscal debe tener al menos 10 caracteres"
				]);
				die();
			}
		}

		// INSERT / UPDATE DISTRIBUIDOR
		if ($idDistribuidor == 0) {
			$idDistribuidor = $this->model->insertDistribuidor(
				$grupo_id,
				$regimen_fiscal_id,
				$tipo_persona,
				$tipo_cliente_id,
				$nombre_fisica,
				$apellido_paterno,
				$apellido_materno,
				$fecha_nacimiento,
				$correo,
				$curp,
				$razon_social,
				$representante_legal,
				$domicilio_fiscal,
				$rfc,
				$nombre_comercial,
				$repve,
				$plaza,
				$clasificacion,
				$estatus,
				$tipo_negocio,
				$matriz_id,
				$telefono,
				$telefono_alt
			);

			$option = 'insert';
		} else {

			$this->model->updateDistribuidor(
				$idDistribuidor,
				$grupo_id,
				$regimen_fiscal_id,
				$tipo_persona,
				$tipo_cliente_id,
				$nombre_fisica,
				$apellido_paterno,
				$apellido_materno,
				$fecha_nacimiento,
				$correo,
				$curp,
				$razon_social,
				$representante_legal,
				$domicilio_fiscal,
				$rfc,
				$nombre_comercial,
				$repve,
				$plaza,
				$clasificacion,
				$estatus,
				$tipo_negocio,
				$matriz_id,
				$telefono,
				$telefono_alt
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

		if (empty($_POST['regional_id'])) {
			echo json_encode([
				'status' => false,
				'msg' => 'Debe seleccionar una regional'
			]);
			return;
		}

		if (!empty($_POST['regional_id']) && is_array($_POST['regional_id'])) {

			if ($option === 'update') {
				$this->model->deleteDistribuidorRegional($idDistribuidor);
			}

			foreach ($_POST['regional_id'] as $regional_id) {
				$this->model->insertDistribuidorRegional(
					intval($regional_id),
					$idDistribuidor
				);
			}
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

	public function getSelectRegionales()
	{
		$htmlOptions = '';
		$arrData = $this->model->selectOptionRegionales();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre']  . ' ' . $arrData[$i]['apellido_paterno'] . ' ' . $arrData[$i]['apellido_materno'] .  '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function getSelectMatrizDistribuidores()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionMatrizDistribuidores();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['razon_social'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function getSelectTipoClientes()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionTipoClientes();
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

	public function getSelectGrupos()
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionGrupos();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre']   .   '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
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
