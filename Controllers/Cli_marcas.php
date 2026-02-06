<?php
class Cli_marcas extends Controllers
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
        getPermisos(MCMARCAS);
    }

    public function Cli_marcas()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Marcas";
        $data['page_title'] = "Marcas";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_marcas.js";
        $this->views->getView($this, "cli_marcas", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectMarcas();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver marca" onClick="fntViewMarca(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar marca" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar marca" onClick="fntDelMarca(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setMarca()
    {
        if ($_POST) {
            if (
                empty($_POST['nombre-marca-input']) ||
                empty($_POST['codigo-marca-input'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intIdmarca = intval($_POST['idmarca']);
                $marca = strClean($_POST['nombre-marca-input']);
                $codigo = strClean($_POST['codigo-marca-input']);

                if (strlen($marca) < 3) {
                    $arrResponse = ["status" => false, "msg" => "El nombre debe tener al menos 3 caracteres"];
                    echo json_encode($arrResponse);
                    die();
                }

                if (!preg_match('/^[A-Z0-9_-]+$/i', $codigo)) {
                    $arrResponse = ["status" => false, "msg" => "El código tiene caracteres no válidos"];
                    echo json_encode($arrResponse);
                    die();
                }

                if ($intIdmarca == 0) {

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_marca = $this->model->insertMarca($marca, $codigo);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_marca = $this->model->updateMarca($intIdmarca, $marca, $codigo);
                        $option = 2;
                    }
                }
                if ($request_marca > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_marca == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! La marca ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function show($idmarca)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidmarca = intval($idmarca);
            if ($intidmarca > 0) {
                $arrData = $this->model->selectMarca($intidmarca);
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
                $idmarca = intval($_POST['idmarca']);
                $requestDelete = $this->model->deleteMarca($idmarca);
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
