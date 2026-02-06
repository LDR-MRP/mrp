<?php
class Cli_puestos extends Controllers
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
        getPermisos(MCLI_PUESTOS);
    }

    public function Cli_puestos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Puestos";
        $data['page_title'] = "Puestos";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_puestos.js";
        $this->views->getView($this, "cli_puestos", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectPuestos();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver puesto" onClick="fntViewPuesto(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar puesto" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar puesto" onClick="fntDelPuesto(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setPuesto()
    {
        if ($_POST) {
            if (
                empty($_POST['listPuestos']) ||
                empty($_POST['nombre-puestos-input']) ||
                empty($_POST['descripcion-puestos-input'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intIdpuesto = intval($_POST['idpuesto']);
                $departamento_id = intval($_POST['listPuestos']);
                $nombre = strClean($_POST['nombre-puestos-input']);
                $descripcion = strClean($_POST['descripcion-puestos-input']);

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

                if ($intIdpuesto == 0) {

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_puesto = $this->model->insertPuesto($departamento_id, $nombre, $descripcion);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_puesto = $this->model->updatePuesto($intIdpuesto, $departamento_id, $nombre, $descripcion);
                        $option = 2;
                    }
                }
                if ($request_puesto > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_puesto == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! El puesto ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function show($idpuesto)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidpuesto = intval($idpuesto);
            if ($intidpuesto > 0) {
                $arrData = $this->model->selectPuesto($intidpuesto);
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
                $idpuesto = intval($_POST['idpuesto']);
                $requestDelete = $this->model->deletePuesto($idpuesto);
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


    public function getSelectDepartamentos()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionDepartamentos();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre']  . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }
}
