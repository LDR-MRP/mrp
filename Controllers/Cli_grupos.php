<?php
class Cli_grupos extends Controllers
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
        getPermisos(MCLI_GRUPOS);
    }

    public function Cli_grupos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Grupos";
        $data['page_title'] = "Grupos";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_grupos.js";
        $this->views->getView($this, "cli_grupos", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectGrupos();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver grupo" onClick="fntViewGrupo(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar grupo" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar grupo" onClick="fntDelGrupo(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setGrupo()
    {
        if ($_POST) {

            if (
                empty($_POST['codigo-grupo-input']) ||
                empty($_POST['nombre-grupo-input']) ||
                empty($_POST['descripcion-grupo-input']) ||
                empty($_POST['estado-select'])
            ) {
                $arrResponse = array(
                    "status" => false,
                    "msg" => "Datos incorrectos."
                );
            } else {

                $intIdgrupo = intval($_POST['idgrupo']);
                $codigo = strClean($_POST['codigo-grupo-input']);
                $nombre = strClean($_POST['nombre-grupo-input']);
                $descripcion = strClean($_POST['descripcion-grupo-input']);
                $estado = intval($_POST['estado-select']);

                if ($intIdgrupo == 0) {
                    // INSERT
                    if ($_SESSION['permisosMod']['w']) {
                        $request_grupo = $this->model->insertGrupo(
                            $codigo,
                            $nombre,
                            $descripcion,
                            $estado
                        );
                        $option = 1;
                    }
                } else {
                    // UPDATE
                    if ($_SESSION['permisosMod']['u']) {
                        $request_grupo = $this->model->updateGrupo(
                            $intIdgrupo,
                            $codigo,
                            $nombre,
                            $descripcion,
                            $estado
                        );
                        $option = 2;
                    }
                }

                if ($request_grupo > 0) {
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
                } elseif ($request_grupo === "exist") {
                    $arrResponse = array(
                        'status' => false,
                        'msg' => '¡Atención! El grupo ya existe.'
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


    public function show($idgrupo)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidgrupo = intval($idgrupo);
            if ($intidgrupo > 0) {
                $arrData = $this->model->selectGrupo($intidgrupo);
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
                $idgrupo = intval($_POST['idgrupo']);
                $requestDelete = $this->model->deleteGrupo($idgrupo);
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
