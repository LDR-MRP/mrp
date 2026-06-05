<?php
class Inv_lineasdproducto extends Controllers
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
        getPermisos(MILPRODUCTO);
    }

    public function inv_lineasdproducto()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Líneas de producto";
        $data['page_title'] = "Líneas de producto";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_inv_lineasdproducto.js";
        $this->views->getView($this, "inv_lineasdproducto", $data);
    }

    //CAPTURAR UNA NUEVA linea producto 
    public function setLineaProducto()
    {
        if ($_POST) {
            if (
                empty($_POST['clave-linea-producto-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intIdLineaProducto = intval($_POST['idlineaproducto']);
                $cve_linea_producto = strClean($_POST['clave-linea-producto-input']);
                $descripcion = strClean($_POST['descripcion-linea-producto-textarea']);
                $estado = intval($_POST['estado-select']);

                if ($intIdLineaProducto == 0) {
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_linea_producto = $this->model->inserLineaProducto($cve_linea_producto, $descripcion, $fecha_creacion, $estado);
                        $option = 1;
                    }
                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_linea_producto = $this->model->updateLineaProducto($intIdLineaProducto, $cve_linea_producto, $descripcion, $estado);
                        $option = 2;
                    }
                }
                if ($request_linea_producto === "exist") {

                    $arrResponse = array(
                        'status' => false,
                        'msg' => '¡Atención! ya existe.'
                    );
                } else if ($request_linea_producto !== false) {

                    if ($option == 1) {
                        $arrResponse = array(
                            'status' => true,
                            'msg' => 'La información se ha registrado exitosamente',
                            'tipo' => 'insert'
                        );
                    } else {
                        $arrResponse = array(
                            'status' => true,
                            'msg' => 'La información ha sido actualizada correctamente.',
                            'tipo' => 'update'
                        );
                    }
                } else {

                    $arrResponse = array(
                        "status" => false,
                        "msg" => 'No es posible almacenar los datos.'
                    );
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getLineasProductos()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectLineasProductos();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';

                if ($arrData[$i]['estado'] == 2) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else if ($arrData[$i]['estado'] == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                }

                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar linea producto" onClick="fntEditLineaProducto(' . $arrData[$i]['idlineaproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnTree = '<button class="btn btn-sm btn-soft-secondary edit-list" title="Agregar sublíneas" onClick="fntEstructuraLinea(' . $arrData[$i]['idlineaproducto'] . ')"><i class="ri-node-tree"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnTree . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getLineaProducto($idlineaproducto)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdLineaProducto = intval($idlineaproducto);
            if ($intIdLineaProducto > 0) {
                $arrData = $this->model->selectLineaProducto($intIdLineaProducto);
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

    public function delLineaProducto()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $intIdLineaProducto = intval($_POST['idlineaproducto']);
                $requestDelete = $this->model->deleteLineaProducto($intIdLineaProducto);

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


    public function getSelectLineasProductos()
    {
        $htmlOptions = '<option value="" selected>--Seleccione--</option>';
        $arrData = $this->model->selectOptionLineasProductos();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['idlineaproducto'] . '">' . $arrData[$i]['cve_linea_producto'] . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }

    public function getSublineas($idLinea)
    {
        $arrData = $this->model->selectSublineas($idLinea);
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function setSublinea()
    {
        if ($_POST) {
            $linea = intval($_POST['lineaproductoid']);
            $cve = strClean($_POST['cve']);
            $desc = strClean($_POST['descripcion']);
            $fecha = date('Y-m-d H:i:s');

            $request = $this->model->insertSublinea($linea, $cve, $desc, $fecha, 2);

            if ($request > 0) {
                $arrResponse = ['status' => true, 'msg' => 'Sublínea creada'];
            } else if ($request == 'exist') {
                $arrResponse = ['status' => false, 'msg' => 'Ya existe'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error'];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function updateSublinea()
    {
        if ($_POST) {
            $id = intval($_POST['idsublinea']);
            $cve = strClean($_POST['cve']);
            $desc = strClean($_POST['descripcion']);

            $request = $this->model->updateSublinea($id, $cve, $desc, 2);

            if ($request) {
                $arrResponse = ['status' => true, 'msg' => 'Actualizado correctamente'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al actualizar'];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function deleteSublinea()
    {
        if ($_POST) {
            $id = intval($_POST['idsublinea']);

            $request = $this->model->deleteSublinea($id);

            if ($request) {
                $arrResponse = ['status' => true, 'msg' => 'Sublínea eliminada'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al eliminar'];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
