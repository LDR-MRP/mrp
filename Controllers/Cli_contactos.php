<?php
class Cli_contactos extends Controllers
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
        getPermisos(MCLI_CONTACTOS);
    }

    public function Cli_contactos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Contactos";
        $data['page_title'] = "Contactos";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cli_contactos.js";
        $this->views->getView($this, "cli_contactos", $data);
    }

    public function index()
    {
        $arrData = $this->model->selectContactos();
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

                $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver contacto" onClick="fntViewContacto(' . $arrData[$i]['id'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
            }
            if ($_SESSION['permisosMod']['u']) {

                $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar contacto" onClick="fntEditInfo(' . $arrData[$i]['id'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar contacto" onClick="fntDelContacto(' . $arrData[$i]['id'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
            }
            $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();
    }

    public function setContacto()
    {
        if ($_POST) {
            if (
                empty($_POST['listDistribuidores']) || empty($_POST['listPuestos']) || empty($_POST['nombre-contactos-input']) || empty($_POST['extension-contactos-input']) || empty($_POST['telefono-contactos-input']) || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intIdcontacto = intval($_POST['idcontacto']);
                $distribuidor_id = intval($_POST['listDistribuidores']);
                $puesto_id = intval($_POST['listPuestos']);
                $nombre = strClean($_POST['nombre-contactos-input']);
                $correo = strClean($_POST['correo-contactos-input']);
                $extension = strClean($_POST['extension-contactos-input']);
                $telefono = strClean($_POST['telefono-contactos-input']);
                $estado = intval($_POST['estado-select']);

                if ($intIdcontacto == 0) {

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_contacto = $this->model->insertContacto($distribuidor_id, $puesto_id, $nombre, $correo, $extension, $telefono, $estado);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_contacto = $this->model->updateContacto($intIdcontacto, $distribuidor_id, $puesto_id, $nombre, $correo, $extension, $telefono, $estado);
                        $option = 2;
                    }
                }
                if ($request_contacto > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_contacto == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! El contacto ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function show($idcontacto)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidcontacto = intval($idcontacto);
            if ($intidcontacto > 0) {
                $arrData = $this->model->selectContacto($intidcontacto);
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
                $idcontacto = intval($_POST['idcontacto']);
                $requestDelete = $this->model->deleteContacto($idcontacto);
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

    public function getSelectDistribuidores()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionDistribuidores();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre_comercial']  . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }

    public function getSelectPuestos()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionPuestos();
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
