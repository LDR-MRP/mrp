<?php
class Cli_regionales extends Controllers
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
        getPermisos(MCLI_REGIONALES);
    }

    public function Cli_regionales()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Regionales";
        $data['page_title'] = "Regionales";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_regionales.js";
        $this->views->getView($this, "cli_regionales", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectRegionales();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver regional" onClick="fntViewRegional(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar regional" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar regional" onClick="fntDelRegional(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setRegional()
    {
        if ($_POST) {

            if (
                empty($_POST['nombre-regional-input']) ||
                empty($_POST['apellido_paterno-regional-input']) ||
                empty($_POST['apellido_materno-regional-input'])
            ) {
                $arrResponse = array(
                    "status" => false,
                    "msg" => "Datos incorrectos."
                );
            } else {
                $intidregional = intval($_POST['idregional']);
                $nombre = strClean($_POST['nombre-regional-input']);
                $apellido_paterno = strClean($_POST['apellido_paterno-regional-input']);
                $apellido_materno = strClean($_POST['apellido_materno-regional-input']);

                if (strlen($nombre) < 3) {
                    $arrResponse = [
                        "status" => false,
                        "msg" => "El nombre debe tener al menos 3 caracteres."
                    ];
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (strlen($apellido_paterno) < 3) {
                    $arrResponse = [
                        "status" => false,
                        "msg" => "El apellido paterno debe tener al menos 3 caracteres."
                    ];
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if (strlen($apellido_materno) < 3) {
                    $arrResponse = [
                        "status" => false,
                        "msg" => "El apellido materno debe tener al menos 3 caracteres."
                    ];
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }

                if ($intidregional == 0) {
                    // INSERT
                    if ($_SESSION['permisosMod']['w']) {
                        $request_regional = $this->model->insertRegional(
                            $nombre,
                            $apellido_paterno,
                            $apellido_materno,
                        );
                        $option = 1;
                    }
                } else {
                    // UPDATE
                    if ($_SESSION['permisosMod']['u']) {
                        $request_regional = $this->model->updateRegional(
                            $intidregional,
                            $nombre,
                            $apellido_paterno,
                            $apellido_materno,
                        );
                        $option = 2;
                    }
                }

                if ($request_regional > 0) {
                    if ($option == 1) {
                        $arrResponse = array(
                            'status' => true,
                            'msg' => 'La información se ha registrado exitosamente.',
                            'tipo' => 'insert'
                        );
                    } else {
                        $arrResponse = array(
                            'status' => true,
                            'msg' => 'La información ha sido actualizada correctamente.',
                            'tipo' => 'update'
                        );
                    }
                } elseif ($request_regional === "exist") {
                    $arrResponse = array(
                        'status' => false,
                        'msg' => '¡Atención! El regional ya existe.'
                    );
                } else {
                    $arrResponse = array(
                        'status' => false,
                        'msg' => 'No es posible almacenar los datos.'
                    );
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function show($idregional)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidregional = intval($idregional);
            if ($intidregional > 0) {
                $arrData = $this->model->selectRegional($intidregional);
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
                $idregional = intval($_POST['idregional']);
                $requestDelete = $this->model->deleteRegional($idregional);
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
