<?php
class Inv_lotespedimentos extends Controllers
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
        getPermisos(MILOTESPEDIMENTOS);
    }

    public function Inv_lotespedimentos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Lotes y Pedimentos";
        $data['page_title'] = "Lotes y Pedimentos";
        $data['page_name'] = "Lotes y Pedimentos";
        $data['page_functions_js'] = "functions_inv_lotespedimentos.js";
        $this->views->getView($this, "inv_lotespedimentos", $data);
    }

    public function getLotesPedimentos()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectLotesPedimentos();

            for ($i = 0; $i < count($arrData); $i++) {

                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';

                /* ===== ESTADO ===== */
                if ($arrData[$i]['estado'] == 2) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                }

                /* ===== BOTÓN VER ===== */
                if ($_SESSION['permisosMod']['r']) {

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver registro" onClick="fntViewLotePedimento(' . $arrData[$i]['id_ltpd'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
                }

                /* ===== BOTÓN EDITAR ===== */
                if ($_SESSION['permisosMod']['u']) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar registro" onClick="fntEditLotePedimento(' . $arrData[$i]['id_ltpd'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }

                /* ===== BOTÓN ELIMINAR ===== */
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar registro" onClick="fntDelLotePedimento(' . $arrData[$i]['id_ltpd'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }

                /* ===== OPCIONES ===== */
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function setLote()
    {
        if (!$_SESSION['permisosMod']['w']) {
            echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
            die();
        }

        if ($_POST) {

            if (
                empty($_POST['inventarioid']) ||
                empty($_POST['almacenid']) ||
                empty($_POST['lote_cantidad']) ||
                empty($_POST['lote_lote'])
            ) {
                echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
                die();
            }

            $id = intval($_POST['id_ltpd']);

            $data = [
                'inventarioid' => $_POST['inventarioid'],
                'almacenid'    => $_POST['almacenid'],
                'lote'         => $_POST['lote_lote'],
                'fecha_produccion_lote' => $_POST['lote_fecha_produccion'] ?? null,
                'fecha_caducidad' => $_POST['lote_fecha_caducidad'] ?? null,
                'cantidad' => floatval($_POST['lote_cantidad']),
                'cve_observacion' => $_POST['cve_observacion'] ?? null,
                'estado' => 2
            ];

            if ($id == 0) {
                // INSERT
                $request = $this->model->insertLote($data);
                $msg = 'Lote registrado correctamente';
            } else {
                // UPDATE
                $request = $this->model->updateLotePedimento($id, $data);
                $msg = 'Lote actualizado correctamente';
            }


            $request = $this->model->insertLote($data);

            if ($request > 0) {
                $arrResponse = ['status' => true, 'msg' => 'Lote registrado correctamente'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al registrar lote'];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    public function setPedimento()
    {
        if (!$_SESSION['permisosMod']['w']) {
            echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
            die();
        }

        if ($_POST) {

            if (
                empty($_POST['inventarioid']) ||
                empty($_POST['almacenid']) ||
                empty($_POST['ped_cantidad']) ||
                empty($_POST['pedimento'])
            ) {
                echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
                die();
            }

            $id = intval($_POST['id_ltpd']);

            $data = [
                'inventarioid'    => $_POST['inventarioid'],
                'almacenid'       => $_POST['almacenid'],
                'pedimento'       => $_POST['pedimento'],
                'pedimento_SAT'   => $_POST['pedimento_SAT'] ?? null,
                'fecha_produccion_lote' => $_POST['ped_fecha_produccion'] ?? null,
                'fecha_caducidad' => $_POST['ped_fecha_caducidad'] ?? null,
                'fecha_aduana'    => $_POST['fecha_aduana'] ?? null,
                'nombre_aduana'   => $_POST['nombre_aduana'] ?? null,
                'ciudad'          => $_POST['ciudad'] ?? null,
                'frontera'        => $_POST['frontera'] ?? null,
                'gln'             => $_POST['gln'] ?? null,
                'cantidad'        => floatval($_POST['ped_cantidad']),
                'cve_observacion' => $_POST['cve_observacion'] ?? null,
                'estado'          => 2
            ];

            if ($id == 0) {
                // INSERT
                $request = $this->model->insertPedimento($data);
                $msg = 'Pedimento registrado correctamente';
            } else {
                // UPDATE
                $request = $this->model->updateLotePedimento($id, $data);
                $msg = 'Pedimento actualizado correctamente';
            }

            if ($request > 0) {
                echo json_encode(['status' => true, 'msg' => $msg]);
            } else {
                echo json_encode(['status' => false, 'msg' => 'Error al guardar pedimento']);
            }
        }

        die();
    }


    public function getProductosPorAlmacen($almacenid)
    {
        if ($_SESSION['permisosMod']['r']) {

            $data = $this->model->selectProductosPorAlmacen($almacenid);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getLotePedimento($id)
    {
        $id = intval($id);
        $data = $this->model->getLotePedimento($id);

        if ($data) {
            $arrResponse = [
                'status' => true,
                'data' => $data
            ];
        } else {
            $arrResponse = [
                'status' => false,
                'msg' => 'Datos no encontrados'
            ];
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function delLotePedimento()
    {
        if (!$_SESSION['permisosMod']['d']) {
            echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
            die();
        }

        if ($_POST) {
            $id = intval($_POST['id_ltpd']);

            if ($id <= 0) {
                echo json_encode(['status' => false, 'msg' => 'ID inválido']);
                die();
            }

            $request = $this->model->delLotePedimento($id);

            if ($request) {
                $arrResponse = ['status' => true, 'msg' => 'Registro eliminado correctamente'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al eliminar'];
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
