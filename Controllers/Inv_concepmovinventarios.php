<?php
class Inv_concepmovinventarios extends Controllers
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
        getPermisos(MICONCEPTOSMOVIMIENTOS);
    }

    public function Inv_concepmovinventarios()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Conceptos Movimientos Inventario";
        $data['page_title'] = "Conceptos Movimientos Inventario";
        $data['page_name'] = "Conceptos Movimientos Inventario";
        $data['page_functions_js'] = "functions_inv_concepmovinventarios.js";
        $this->views->getView($this, "inv_concepmovinventarios", $data);
    }

    //CAPTURAR UNA NUEVO CONCEPTO DE MOVIMIENTO
    public function setConcepto()
    {
        if ($_POST) {

            if (
                empty($_POST['clave-concepto-input']) ||
                empty($_POST['estado-select']) ||
                empty($_POST['tipo_mov'])
            ) {
                echo json_encode([
                    "status" => false,
                    "msg" => "Datos incompletos"
                ]);
                die();
            }

            $intidconcepmov = intval($_POST['idconcepmov']);
            $cve_concep_mov = strClean($_POST['clave-concepto-input']);
            $descripcion    = strClean($_POST['descripcion-concepto-textarea']);
            $cpn            = strClean($_POST['asociado-select']);
            $estado         = intval($_POST['estado-select']);

            // ✅ AHORA SÍ EXISTE
            $tipo_mov = $_POST['tipo_mov']; // E | S
            $signo    = ($tipo_mov === 'E') ? 1 : -1;

            if ($intidconcepmov == 0) {

                if ($_SESSION['permisosMod']['w']) {
                    $request = $this->model->insertConcepto(
                        $cve_concep_mov,
                        $descripcion,
                        $cpn,
                        $tipo_mov,
                        $estado,
                        $signo
                    );
                }
            } else {

                if ($_SESSION['permisosMod']['u']) {
                    $request = $this->model->updateConcepto(
                        $intidconcepmov,
                        $cve_concep_mov,
                        $descripcion,
                        $cpn,
                        $tipo_mov,
                        $estado,
                        $signo
                    );
                }
            }

            if ($request > 0) {
                echo json_encode([
                    "status" => true,
                    "msg" => "Registro guardado correctamente"
                ]);
            } elseif ($request === "exist") {
                echo json_encode([
                    "status" => false,
                    "msg" => "El concepto ya existe"
                ]);
            } else {
                echo json_encode([
                    "status" => false,
                    "msg" => "Error al guardar"
                ]);
            }
        }
        die();
    }


    public function getConceptos()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectConceptos();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';
                //PERMITE MOSTRAR EL ESTADO DEL CONCEPTO SI ES 2= ACTIVO O 1= INACTIVO
                if ($arrData[$i]['estado'] == 2) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else if ($arrData[$i]['estado'] == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                }
                //PERMITE MOSTRAR EL TIPO DE CPN ASOCIADO AL CONCEPTO
                if ($arrData[$i]['cpn'] == 'C') {
                    $arrData[$i]['cpn'] = '<span class="">Cliente</span>';
                } else if ($arrData[$i]['cpn'] == 'P') {
                    $arrData[$i]['cpn'] = '<span class="">Proveedor</span>';
                }else if ($arrData[$i]['cpn'] == 'N') {
                    $arrData[$i]['cpn'] = '<span class="">Ninguno</span>';
                }
                //PERMITE MOSTRAR EL TIPO DE MOVIMIENTO DEL CONCEPTO
                if ($arrData[$i]['tipo_movimiento'] == 'E') {
                    $arrData[$i]['tipo_movimiento'] = '<span class="">Entrada</span>';
                } else if ($arrData[$i]['tipo_movimiento'] == 'S') {
                    $arrData[$i]['tipo_movimiento'] = '<span class="">Salida</span>';
                }

                if ($_SESSION['permisosMod']['r']) {

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver concepto" onClick="fntViewConcepto(' . $arrData[$i]['idconcepmov'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar concepto" onClick="fntEditConcepto(' . $arrData[$i]['idconcepmov'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar concepto" onClick="fntDelInfo(' . $arrData[$i]['idconcepmov'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getConcepto($idconcepmov)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidconcepmov = intval($idconcepmov);
            if ($intidconcepmov > 0) {
                $arrData = $this->model->selectConcepto($intidconcepmov);
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

    public function delConcepto()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $idconcepmov = intval($_POST['idconcepmov']);
                $request = $this->model->deleteConcepto($idconcepmov);

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



    public function getSelectConceptos()
    {
        $htmlOptions = '<option value="" selected>--Seleccione--</option>';
        $arrData = $this->model->selectOptionConceptos();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['idconcepmov'] . '">' . $arrData[$i]['cve_concep_mov'] . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }
}
