<?php
class Inv_zonas extends Controllers
{
    use ApiResponser;

    protected $zonaService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIZONAS);

        $this->zonaService = new Inv_zonaService;

        $this->zonaService->model = $this->model;
    }

    public function Inv_zonas()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Zonas";
        $data['page_title'] = "Zonas";
        $data['page_name'] = "Zonas";
        $data['page_functions_js'] = "functions_inv_zonas.js";
        $this->views->getView($this, "inv_zonas", $data);
    }

    //CAPTURAR UNA NUEVA SEDE 
    public function setZona()
	{
		if ($_POST) {
			if (
				empty($_POST['clave-zona-input']) ||
				empty($_POST['listSedes']) ||
				empty($_POST['estado-select'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
			} else {

				$intidzona = intval($_POST['idzona']);
				$clave_zona = strClean($_POST['clave-zona-input']);
				$descripcion = strClean($_POST['descripcion-zona-textarea']);
				$sede = intval($_POST['listSedes']);
				$estado = intval($_POST['estado-select']);


				if ($intidzona == 0) {


					$fecha_creacion = date('Y-m-d H:i:s');

					//Crear 
					if ($_SESSION['permisosMod']['w']) {
						$request_zona = $this->model->inserZona($clave_zona, $descripcion, $sede, $fecha_creacion, $estado);
						$option = 1;
					}
				} else {
					//Actualizar
					if ($_SESSION['permisosMod']['u']) {
						$request_zona = $this->model->updateZona($intidzona, $clave_zona, $descripcion, $sede, $estado);
						$option = 2;
					}
				}

				if ($request_zona === 'exist') {

					$arrResponse = array('status' => false, 'msg' => '¡Atención! La zona ya existe.');
				} else if ($request_zona !== false) {

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

	public function getZonas()
	{
		if ($_SESSION['permisosMod']['r']) {
			$arrData = $this->model->selectZonas();
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

					$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver zona" onClick="fntViewZona(' . $arrData[$i]['idzona'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
				}
				if ($_SESSION['permisosMod']['u']) {

					$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar zona" onClick="fntEditInfo(' . $arrData[$i]['idzona'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
				}
				if ($_SESSION['permisosMod']['d']) {
					$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar zona" onClick="fntDelInfo(' . $arrData[$i]['idzona'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
				}
				$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
			}
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}


	public function getZona($idzona)
	{
		if ($_SESSION['permisosMod']['r']) {
			$intidzona = intval($idzona);
			if ($intidzona > 0) {
				$arrData = $this->model->selectZona($intidzona);
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

	public function delZona()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$intidzona = intval($_POST['idzona']);
				$requestDelete = $this->model->deleteZona($intidzona);
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


	public function getSelectZonas($idprecio)
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionZonas($idprecio);
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idzona'] . '">' . $arrData[$i]['cve_zona'] . ' - ' . $arrData[$i]['descripcion'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();
	}

    public function index()
    {
        return $this->apiResponse($this->zonaService->index(sanitizeGet()));
    }
}
