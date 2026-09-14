<?php
class Ing_modelos extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_MODELOS);
    }

    public function Ing_modelos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Modelos";
        $data['page_title'] = "Modelos (detalle de ingeniería)";
        $data['page_name'] = "Modelos";
        $data['page_functions_js'] = "functions_ing_modelos.js";
        $this->views->getView($this, "ing_modelos", $data);
    }

    // Segmento = wms_linea_producto, Modelo/Familia = wms_sublinea_producto
    // (ya administrados en Inv_lineasdproducto). Aquí solo se captura el
    // detalle adicional que le falta a esas tablas para representar un vehículo.
    public function getModelos()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectModelos();
            for ($i = 0; $i < count($arrData); $i++) {
                $tieneDetalle = !empty($arrData[$i]['id_modelo_detalle']);
                $arrData[$i]['detalle_label'] = $tieneDetalle
                    ? '<span class="badge bg-success">Capturado</span>'
                    : '<span class="badge bg-warning text-dark">Pendiente</span>';

                $btnEdit = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar detalle" onClick="fntEditInfo(' . $arrData[$i]['idsublineaproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getModelo($idSublinea)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($idSublinea);
            $arrData = $this->model->selectModelo($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setModeloDetalle()
    {
        if ($_POST) {
            $intIdSublinea = intval($_POST['id_sublineaproducto']);
            if ($intIdSublinea <= 0) {
                $arrResponse = array('status' => false, 'msg' => 'Modelo no válido.');
            } else if (empty($_SESSION['permisosMod']['u']) && empty($_SESSION['permisosMod']['w'])) {
                $arrResponse = array('status' => false, 'msg' => 'No tienes permiso para esta acción.');
            } else {
                $data = [
                    'marca'           => strClean($_POST['marca-input'] ?? 'FOTON'),
                    'tipo_carroceria' => strClean($_POST['carroceria-input'] ?? ''),
                    'estado'          => strClean($_POST['estado-select'] ?? 'ACTIVO'),
                    'version'         => strClean($_POST['version-input'] ?? ''),
                    'fecha_inicio'    => !empty($_POST['fecha-inicio-input']) ? $_POST['fecha-inicio-input'] : null,
                    'fecha_fin'       => !empty($_POST['fecha-fin-input']) ? $_POST['fecha-fin-input'] : null,
                ];

                $request = $this->model->upsertDetalle($intIdSublinea, $data);

                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'El detalle del modelo se guardó correctamente.')
                    : array('status' => false, 'msg' => 'No fue posible guardar el detalle del modelo.');
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
