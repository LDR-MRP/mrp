<?php
class Inv_multialmacenes extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIMULTIALMACENES);
    }

    public function Inv_multialmacenes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Multialmacenes";
        $data['page_title'] = "Multialmacenes";
        $data['page_name'] = "multialmacenes";
        $data['page_functions_js'] = "functions_inv_multialmacenes.js";
        $this->views->getView($this, "inv_multialmacenes", $data);
    }

    //CAPTURAR UN NUEVO MULTIALMACEN
    public function setMultialmacen()
    {
        if ($_POST) {

            if (
                empty($_POST['listInventario']) ||
                empty($_POST['listAlmacenes'])
            ) {
                $arrResponse = ['status' => false, 'msg' => 'Datos incorrectos.'];
            } else {

                $idmultialmacen = intval($_POST['idmultialmacen']);
                $inventarioid  = intval($_POST['listInventario']);
                $almacenid     = intval($_POST['listAlmacenes']);
                $existencia    = floatval($_POST['existencia-input']);
                $stockmin      = floatval($_POST['stockminimo-input']);
                $stockmax      = floatval($_POST['stockmaximo-input']);

                if ($idmultialmacen == 0) {

                    if ($_SESSION['permisosMod']['w']) {
                        $request = $this->model->insertMultialmacen($inventarioid, $almacenid, $existencia, $stockmin, $stockmax);
                        $tipo = "insert";
                    }
                } else {

                    if ($_SESSION['permisosMod']['u']) {
                        $request = $this->model->updateMultialmacen(
                            $idmultialmacen,
                            $inventarioid,
                            $almacenid,
                            $existencia,
                            $stockmin,
                            $stockmax
                        );
                        $tipo = "update";
                    }
                }

                if ($request === "exist") {

                    $arrResponse = [
                        'status' => false,
                        'msg' => 'Ya existe ese inventario en ese almacén'
                    ];
                } else if ($request > 0) {

                    $arrResponse = [
                        'status' => true,
                        'msg' => $tipo == 'insert'
                            ? 'Registro creado correctamente'
                            : 'Registro actualizado correctamente'
                    ];
                } else {

                    $arrResponse = [
                        'status' => false,
                        'msg' => 'No se pudo guardar'
                    ];
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Buscar inventarios para el autocomplete
    public function searchInventarios()
    {
        if ($_GET) {
            $term = strClean($_GET['term']);
            $arrData = $this->model->searchInventarios($term);

            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    "id" => $row['idinventario'],
                    "label" => $row['cve_articulo'] . " - " . $row['descripcion'],
                    "value" => $row['cve_articulo'] . " - " . $row['descripcion']
                ];
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getSelectInventariosJson()
    {
        $arrData = $this->model->selectInventariosPC_H();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }


    // Traer todos los multialmacenes
    public function getmultialmacenes()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectMultialmacenes();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Traer un multialmacen
    public function getMultialmacen($id)
    {
        if ($_SESSION['permisosMod']['r']) {
            $id = intval($id);
            $arrData = $this->model->selectMultialmacen($id);
            if (empty($arrData)) {
                $arrResponse = ['status' => false, 'msg' => 'Datos no encontrados.'];
            } else {
                $arrResponse = ['status' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Eliminar
    public function delmultiAlmacen()
    {
        if ($_POST) {
            $id = intval($_POST['idmultialmacen']);
            $request = $this->model->deleteMultialmacen($id);
            if ($request) {
                $arrResponse = ['status' => true, 'msg' => 'Registro eliminado correctamente.'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al eliminar.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Select inventarios solo P,C,H
    // Select inventarios solo P,C,H
    public function getSelectInventarios()
    {
        $htmlOptions = '<option value="">--Seleccione Inventario--</option>';
        $arrData = $this->model->selectInventariosPC_H(); // tu model ya filtra P,C,H
        foreach ($arrData as $inv) {
            $htmlOptions .= '<option value="' . $inv['idinventario'] . '">' . $inv['cve_articulo'] . ' - ' . $inv['descripcion'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }


    // Select almacenes
    public function getSelectAlmacenes()
    {
        $htmlOptions = '<option value="">--Seleccione Almacén--</option>';
        $arrData = $this->model->selectOptionAlmacenes();
        foreach ($arrData as $alm) {
            $htmlOptions .= '<option value="' . $alm['idalmacen'] . '">' . $alm['descripcion'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }
}
