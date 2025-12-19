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

		$arrData = $this->model->selectClientes();
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

				$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver cliente" onClick="fntViewCliente(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
			}
			if ($_SESSION['permisosMod']['u']) {

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar cliente" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
			}
			if ($_SESSION['permisosMod']['d']) {
				$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar cliente" onClick="fntDelCliente(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
			}
			$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
		}
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

		die();
	}

	public function show($idcliente)
	{
		if ($_SESSION['permisosMod']['r']) {
			$intIdcliente = intval($idcliente);
			if ($intIdcliente > 0) {
				$arrData = $this->model->selectCliente($intIdcliente);
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

	public function setCliente()
	{
		if ($_POST) {
			if (
				empty($_POST['nombre-cliente-input'])
				|| empty($_POST['estado-select'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
			} else {

				$intIdplanta = intval($_POST['idplanta']);
				$estado = intval($_POST['estado-select']);

				if ($intIdplanta == 0) {

					$claveUnica = $this->model->generarClave();

					//Crear 
					if ($_SESSION['permisosMod']['w']) {
						$request_planta = $this->model->inserPlanta($claveUnica, $estado);
						$option = 1;
					}
				} else {
					//Actualizar
					if ($_SESSION['permisosMod']['u']) {
						$request_planta = $this->model->updatePlanta($intIdplanta, $estado);
						$option = 2;
					}
				}
				if ($request_planta > 0) {
					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
					} else {
						$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
					}
				} else if ($request_planta == 'exist') {
					$arrResponse = array('status' => false, 'msg' => '¡Atención! La categoría ya existe.');
				} else {
					$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
	}
	public function destroy()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$idcliente = intval($_POST['idcliente']);
				$requestDelete = $this->model->deleteCliente($idcliente);
				if ($requestDelete) {
					$arrResponse = array('status' => true, 'msg' => 'El registro fue eliminado satisfactoriamente.');
				} else {
					$arrResponse = array('status' => false, 'msg' => 'Error al eliminar el usuario.');
				}
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}
}
