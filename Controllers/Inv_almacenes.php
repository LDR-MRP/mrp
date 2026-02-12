<?php
class Inv_almacenes extends Controllers
{
	use ApiResponser;

	protected $almacenService;

	public function __construct()
	{
		parent::__construct();
		session_start();
		//session_regenerate_id(true);
		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MIALMACENES);

		$this->almacenService = new Inv_almacenService;

		$this->almacenService->model = $this->model;
	}

	public function Inv_almacenes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Almacenes";
		$data['page_title'] = "Almacenes";
		$data['page_name'] = "almacenes";
		$data['page_functions_js'] = "functions_inv_almacenes.js";
		$this->views->getView($this, "inv_almacenes", $data);
	}

	public function setAlmacen()
	{
		if ($_POST) {
			if (
				empty($_POST['clave-almacen-input']) ||
				empty($_POST['direccion-input']) ||
				empty($_POST['encargado-input']) ||
				empty($_POST['telefono-input']) ||
				empty($_POST['listPrecios']) ||
				empty($_POST['estado-select'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
			} else {

				$intidalmacen = intval($_POST['idalmacen']);
				$clave_almacen = strClean($_POST['clave-almacen-input']);
				$descripcion = strClean($_POST['descripcion-almacen-textarea']);
				$direccion = strClean($_POST['direccion-input']);
				$encargado = strClean($_POST['encargado-input']);
				$telefono = strClean($_POST['telefono-input']);
				$precio = intval($_POST['listPrecios']);
				$estado = intval($_POST['estado-select']);


				if ($intidalmacen == 0) {


					$fecha_creacion = date('Y-m-d H:i:s');

					//Crear 
					if ($_SESSION['permisosMod']['w']) {
						$request_almacen = $this->model->inserAlmacen($clave_almacen, $descripcion, $direccion, $encargado, $telefono, $precio, $fecha_creacion, $estado);
						$option = 1;
					}
				} else {
					//Actualizar
					if ($_SESSION['permisosMod']['u']) {
						$request_almacen = $this->model->updateAlmacen($intidalmacen, $clave_almacen, $descripcion, $direccion, $encargado, $telefono, $precio, $estado);
						$option = 2;
					}
				}

				if ($request_almacen === 'exist') {

					$arrResponse = array('status' => false, 'msg' => '¡Atención! El almacen ya existe.');
				} else if ($request_almacen > 0) {

					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
					} else {
						$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
					}
				} else {

					$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
	}

	public function getAlmacenes()
	{
		if ($_SESSION['permisosMod']['r']) {
			$arrData = $this->model->selectAlmacenes();
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

					$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver almacen" onClick="fntViewAlmacen(' . $arrData[$i]['idalmacen'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
				}
				if ($_SESSION['permisosMod']['u']) {

					$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar almacen" onClick="fntEditInfo(' . $arrData[$i]['idalmacen'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
				}
				if ($_SESSION['permisosMod']['d']) {
					$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar almacen" onClick="fntDelInfo(' . $arrData[$i]['idalmacen'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
				}
				$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
			}
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}


	public function getAlmacen($idalmacen)
	{
		if ($_SESSION['permisosMod']['r']) {
			$intidalmacen = intval($idalmacen);
			if ($intidalmacen > 0) {
				$arrData = $this->model->selectAlmacen($intidalmacen);
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

	public function delAlmacen()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$intidalmacen = intval($_POST['idalmacen']);
				$requestDelete = $this->model->deleteAlmacen($intidalmacen);
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


	public function getSelectAlmacenes($idprecio)
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionAlmacenes($idprecio);
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">' . $arrData[$i]['cve_almacen'] . ' - ' . $arrData[$i]['descripcion'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

	public function showAll()
    {
        return $this->apiResponse($this->almacenService->showAll());
    }
}
