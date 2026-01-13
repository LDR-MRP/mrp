<?php
class Inv_esquemaimpuestos extends Controllers
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
		getPermisos(MIESQUEMAIMPUESTOS);
	}

	public function Inv_esquemaimpuestos()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Impuestos";
		$data['page_title'] = "Impuestos";
		$data['page_name'] = "Impuestos";
		$data['page_functions_js'] = "functions_inv_esquemaimpuestos.js";
		$this->views->getView($this, "inv_esquemaimpuestos", $data);
	}
	//CAPTURAR UNA NUEVO IMPUESTO 
	public function setImpuesto()
	{
		if ($_POST) {

			if (empty($_POST['clave-impuesto-input']) || empty($_POST['estado-select'])) {
				echo json_encode([
					"status" => false,
					"msg" => "Datos incorrectos"
				], JSON_UNESCAPED_UNICODE);
				die();
			}

			$request = 0; // 🔥 CLAVE
			$id = intval($_POST['idimpuesto']);
			$cve = strClean($_POST['clave-impuesto-input']);
			$desc = strClean($_POST['descripcion-impuesto-textarea']);
			$status = intval($_POST['estado-select']);

			$impuestos = [];
			$aplica = [];

			for ($i = 1; $i <= 8; $i++) {
				$impuestos[$i] = floatval($_POST["impuesto$i"] ?? 0);
				$aplica[$i] = intval($_POST["imp{$i}_aplica"] ?? 0);
			}

			if ($id == 0) {

				if (!$_SESSION['permisosMod']['w']) {
					echo json_encode([
						"status" => false,
						"msg" => "No tienes permisos para registrar"
					], JSON_UNESCAPED_UNICODE);
					die();
				}

				$fecha_creacion = date('Y-m-d H:i:s');

				$request = $this->model->inserImpuesto(
					$cve,
					$desc,
					$impuestos,
					$aplica,
					$fecha_creacion,
					$status
				);

				$msg = "Registro guardado correctamente";
			} else {

				if (!$_SESSION['permisosMod']['u']) {
					echo json_encode([
						"status" => false,
						"msg" => "No tienes permisos para actualizar"
					], JSON_UNESCAPED_UNICODE);
					die();
				}

				$request = $this->model->updateImpuesto(
					$id,
					$cve,
					$desc,
					$impuestos,
					$aplica,
					$status
				);

				$msg = "Registro actualizado correctamente";
			}

			if ($request > 0) {
				echo json_encode([
					"status" => true,
					"msg" => $msg
				], JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode([
					"status" => false,
					"msg" => "No se pudo guardar el registro"
				], JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}



	public function getImpuestos()
	{
		if ($_SESSION['permisosMod']['r']) {
			$arrData = $this->model->selectImpuestos();
			for ($i = 0; $i < count($arrData); $i++) {
				$btnView = '';
				$btnEdit = '';
				$btnDelete = '';

				if ($arrData[$i]['estado'] == 2) {
					$estadoTexto = ($arrData[$i]['estado'] == 2)
						? '<span class="badge bg-success">Activo</span>'
						: '<span class="badge bg-danger">Inactivo</span>';

					$arrData[$i]['estado_texto'] = $estadoTexto;
				} else if ($arrData[$i]['estado'] == 1) {
					$arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
				}

				if ($_SESSION['permisosMod']['r']) {

					$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver impuesto" onClick="fntViewImpuesto(' . $arrData[$i]['idimpuesto'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
				}
				if ($_SESSION['permisosMod']['u']) {

					$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar impuesto" onClick="fntEditImpuesto(' . $arrData[$i]['idimpuesto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
				}
				if ($_SESSION['permisosMod']['d']) {
					$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar impuesto" onClick="fntDelInfo(' . $arrData[$i]['idimpuesto'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
				}
				$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
			}
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}


	public function getImpuesto($idimpuesto)
	{
		if ($_SESSION['permisosMod']['r']) {
			$intidimpuesto = intval($idimpuesto);
			if ($intidimpuesto > 0) {
				$arrData = $this->model->selectImpuesto($intidimpuesto);
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

	public function delImpuesto()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$idimpuesto = intval($_POST['idimpuesto']);
				$request = $this->model->deleteImpuesto($idimpuesto);

				if ($request) {
					$arrResponse = array('status' => true, 'msg' => 'Registro eliminado correctamente');
				} else {
					$arrResponse = array('status' => false, 'msg' => 'No se pudo eliminar');
				}
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}



	public function getSelectImpuestos()
	{
		$htmlOptions = '<option value="" selected>--Seleccione--</option>';
		$arrData = $this->model->selectOptionPrecios();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idimpuesto'] . '">' . $arrData[$i]['cve_precio'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}
}
