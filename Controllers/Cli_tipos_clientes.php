<?php
class Cli_tipos_clientes extends Controllers
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
        getPermisos(MCLI_TIPOS_CLIENTES);
    }

    public function Cli_tipos_clientes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Tipo de clientes";
        $data['page_title'] = "Tipo de clientes";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_tipos_clientes.js";
        $this->views->getView($this, "cli_tipos_clientes", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectTiposClientes();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver tipo de cliente" onClick="fntViewTipoCliente(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar tipo de cliente" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar tipo de cliente" onClick="fntDelTipoCliente(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setTipoCliente()
    {
        if ($_POST) {

            if (
                empty($_POST['nombre-tipocliente-input'])
            ) {
                $arrResponse = array(
                    "status" => false,
                    "msg" => "Datos incorrectos."
                );
            } else {

                $intIdTipoCliente = intval($_POST['idtipocliente']);
                $nombre = strClean($_POST['nombre-tipocliente-input']);
                $descripcion = strClean($_POST['descripcion-tipocliente-input']);

                if (strlen($nombre) < 3) {
                    $arrResponse = ["status" => false, "msg" => "El nombre del tipo de cliente debe tener al menos 3 caracteres"];
                    echo json_encode($arrResponse);
                    die();
                }

                if ($intIdTipoCliente == 0) {
                    // INSERT
                    if ($_SESSION['permisosMod']['w']) {
                        $request_tipocliente = $this->model->insertTipoCliente(
                            $nombre,
                            $descripcion,
                        );
                        $option = 1;
                    }
                } else {
                    // UPDATE
                    if ($_SESSION['permisosMod']['u']) {
                        $request_tipocliente = $this->model->updateTipoCliente(
                            $intIdTipoCliente,
                            $nombre,
                            $descripcion,
                        );
                        $option = 2;
                    }
                }

                if ($request_tipocliente > 0) {
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
                } else if ($request_tipocliente === "exist") {
                    $arrResponse = array(
                        'status' => false,
                        'msg' => '¡Atención! El tipo de cliente ya existe.'
                    );
                } else {
                    $arrResponse = array(
                        'status' => false,
                        'msg' => 'No es posible almacenar los datos.'
                    );
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function show($idtipocliente)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidTipoCliente = intval($idtipocliente);
            if ($intidTipoCliente > 0) {
                $arrData = $this->model->selectTipoCliente($intidTipoCliente);
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
                $idtipocliente = intval($_POST['idtipocliente']);
                $requestDelete = $this->model->deleteTipoCliente($idtipocliente);
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
