<?php
class Cap_plantas extends Controllers
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
        getPermisos(MCPLANTAS);
    }

    public function Cap_plantas()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Plantas";
        $data['page_title'] = "Plantas de <small>de trabajo</small>";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cap_plantas.js";
        $this->views->getView($this, "cap_plantas", $data);
    }

    //CAPTURAR UNA NUEVA PLANTA 
    public function setPlanta()
    {
        if ($_POST) {
            if (
                empty($_POST['nombre-planta-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
            } else {

                $intIdplanta = intval($_POST['idplanta']);
                $nombre_planta = strClean($_POST['nombre-planta-input']);
                $estado = intval($_POST['estado-select']);


                if ($intIdplanta == 0) {

                    $claveUnica = $this->model->generarClave();
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_planta = $this->model->inserPlanta($claveUnica, $nombre_planta, $fecha_creacion, $estado);
                        $option = 1;
                    }

                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_planta = $this->model->updatePlanta($intIdplanta, $nombre_planta, $estado);
                        $option = 2;
                    }
                }
                if ($request_planta > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');

                    }
                    else{
                    	$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_planta == 'exist') {
                    $arrResponse = array('st atus' => false, 'msg' => '¡Atención! La categoría ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getPlantas()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectPlantas();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';

                if ($arrData[$i]['estado'] == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                }

                if ($_SESSION['permisosMod']['r']) {

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver planta" onClick="fntViewPlanta(' . $arrData[$i]['idplanta'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar planta" onClick="fntEditInfo(' . $arrData[$i]['idplanta'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar planta" onClick="fntDelInfo(' . $arrData[$i]['idplanta'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getPlanta($idplanta)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdplanta = intval($idplanta);
            if ($intIdplanta > 0) {
                $arrData = $this->model->selectPlanta($intIdplanta);
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

    public function delPlanta()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $intIdplanta = intval($_POST['idPlanta']);
                $requestDelete = $this->model->deletePlanta($intIdplanta);
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


?>