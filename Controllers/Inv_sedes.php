<?php
class Inv_sedes extends Controllers
{
    use ApiResponser;

    protected $sedeService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MISEDES);

        $this->sedeService = new Inv_sedeService;

        $this->sedeService->model = $this->model;
    }

    public function Inv_sedes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Sedes";
        $data['page_title'] = "Sedes";
        $data['page_name'] = "Sedes";
        $data['page_functions_js'] = "functions_inv_sedes.js";
        $this->views->getView($this, "inv_sedes", $data);
    }

    //CAPTURAR UNA NUEVA SEDE 
    public function setSede()
    {
        if ($_POST) {
            if (
                empty($_POST['clave-sede-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intsede = intval($_POST['idsede']);
                $cve_sede = strClean($_POST['clave-sede-input']);
                $descripcion = strClean($_POST['descripcion-sede-textarea']);
                $estado = intval($_POST['estado-select']);

                if ($intsede == 0) {
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_sede = $this->model->inserSede($cve_sede, $descripcion, $fecha_creacion, $estado);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_sede = $this->model->updateSede($intsede, $cve_sede, $descripcion, $estado);
                        $option = 2;
                    }
                }
                if ($request_sede > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_sede == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getSedes()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectSedes();
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

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver sede" onClick="fntViewSede(' . $arrData[$i]['idsede'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar sede" onClick="fntEditSede(' . $arrData[$i]['idsede'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar sede" onClick="fntDelInfo(' . $arrData[$i]['idsede'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getSede($idsede)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intsede = intval($idsede);
            if ($intsede > 0) {
                $arrData = $this->model->selectSede($intsede);
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

    public function delSede()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $idsede = intval($_POST['idsede']);
                $request = $this->model->deleteSede($idsede);

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



    public function getSelectSedes()
    {
        $htmlOptions = '<option value="">-- Seleccione sede --</option>';
        $arrData = $this->model->selectSedes();

        if (!empty($arrData)) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['idsede'] . '">' .
                        $arrData[$i]['descripcion'] .
                        '</option>';
                }
            }
        }

        echo $htmlOptions;
        die();
    }

    public function index()
    {
        return $this->apiResponse($this->sedeService->index(sanitizeGet()));
    }
}
