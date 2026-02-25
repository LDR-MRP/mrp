<?php
class Inv_precios extends Controllers
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
        getPermisos(MIPRECIOS);
    }

    public function Inv_precios()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Precios";
        $data['page_title'] = "Precios";
        $data['page_name'] = "lineasproducto";
        $data['page_functions_js'] = "functions_inv_precios.js";
        $this->views->getView($this, "inv_precios", $data);
    }

    //CAPTURAR UNA NUEVO PRECIO 
    public function setPrecio()
    {
        if ($_POST) {
            if (
                empty($_POST['clave-precio-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intidprecio = intval($_POST['idprecio']);
                $cve_precio = strClean($_POST['clave-precio-input']);
                $descripcion = strClean($_POST['descripcion-precio-textarea']);
                $impuesto = intval($_POST['impuesto-select']);
                $estado = intval($_POST['estado-select']);

                if ($intidprecio == 0) {
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_precio = $this->model->inserPrecio($cve_precio, $descripcion, $impuesto, $fecha_creacion, $estado);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_precio = $this->model->updatePrecio($intidprecio, $cve_precio, $descripcion, $impuesto, $estado);
                        $option = 2;
                    }
                }
                if ($request_precio > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_precio == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getPrecios()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectPrecios();
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

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver precio" onClick="fntViewPrecio(' . $arrData[$i]['idprecio'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar precio" onClick="fntEditPrecio(' . $arrData[$i]['idprecio'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar precio" onClick="fntDelInfo(' . $arrData[$i]['idprecio'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getPrecio($idprecio)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidprecio = intval($idprecio);
            if ($intidprecio > 0) {
                $arrData = $this->model->selectPrecio($intidprecio);
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

    public function delPrecio()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $idprecio = intval($_POST['idprecio']);
                $request = $this->model->deletePrecio($idprecio);

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



    public function getSelectPrecios()
    {
        $htmlOptions = '<option value="" selected>--Seleccione--</option>';
        $arrData = $this->model->selectOptionPrecios();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['idprecio'] . '">' . $arrData[$i]['cve_precio'] . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }

    public function getSelectImpuestos()
    {
        $htmlOptions = '<option value="" selected>--Seleccione--</option>';
        $arrData = $this->model->selectImpuestos();

        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                $htmlOptions .= '<option value="' . $arrData[$i]['idimpuesto'] . '">'
                    . $arrData[$i]['cve_impuesto'] . ' - ' . $arrData[$i]['descripcion'] .
                    '</option>';
            }
        }

        echo $htmlOptions;
        die();
    }
}
