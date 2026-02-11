<?php
class Cli_departamentos extends Controllers
{
    use ApiResponser;

    protected $departamentoService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MCDEPARTAMENTOS);

        $this->departamentoService = new Cli_departamentoService($this->model);
    }

    public function Cli_departamentos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Departamentos";
        $data['page_title'] = "Departamentos";
        $data['page_name'] = "departamentos";
        $data['page_functions_js'] = "functions_cli_departamentos.js";
        $this->views->getView($this, "cli_departamentos", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectDepartamentos();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver departamento" onClick="fntViewDepartamento(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar departamento" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar departamento" onClick="fntDelDepartamento(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setDepartamento()
    {
        if ($_POST) {
            if (
                empty($_POST['nombre-departamento-input']) ||
                empty($_POST['descripcion-departamento-input'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intiddepartamento = intval($_POST['iddepartamento']);
                $nombre = strClean($_POST['nombre-departamento-input']);
                $descripcion = strClean($_POST['descripcion-departamento-input']);

                if (strlen($nombre) < 3) {
                    $arrResponse = ["status" => false, "msg" => "El nombre debe tener al menos 3 caracteres"];
                    echo json_encode($arrResponse);
                    die();
                }

                if (strlen($descripcion) < 3) {
                    $arrResponse = ["status" => false, "msg" => "La descripcion debe tener al menos 3 caracteres"];
                    echo json_encode($arrResponse);
                    die();
                }

                if ($intiddepartamento == 0) {

                    //Crear departamento
                    if ($_SESSION['permisosMod']['w']) {
                        $request_departamento = $this->model->insertDepartamento($nombre, $descripcion);
                        $option = 1;
                    }
                } else {
                    //Actualizar departamento   
                    if ($_SESSION['permisosMod']['u']) {
                        $request_departamento = $this->model->updateDepartamento($intiddepartamento, $nombre, $descripcion);
                        $option = 2;
                    }
                }
                if ($request_departamento > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_departamento == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! El departamento ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }


    public function show($iddepartamento)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intiddepartamento = intval($iddepartamento);
            if ($intiddepartamento > 0) {
                $arrData = $this->model->selectDepartamento($intiddepartamento);
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
                $iddepartamento = intval($_POST['iddepartamento']);
                $requestDelete = $this->model->deleteDepartamento($iddepartamento);
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

    /* ======================================================
     * API (JSON): listar, mostrar, crear, aprobar, rechazar, cancelar, eliminar
     * ====================================================== */
    public function indexapi()
    {
        return $this->apiResponse($this->departamentoService->index($filters = sanitizeGet()));
    }
}
