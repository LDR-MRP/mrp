<?php
class Ing_transmisiones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_TRANSMISIONES);
    }

    public function Ing_transmisiones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Transmisiones";
        $data['page_title'] = "Transmisiones";
        $data['page_name'] = "Transmisiones";
        $data['page_functions_js'] = "functions_ing_transmisiones.js";
        $this->views->getView($this, "ing_transmisiones", $data);
    }

    public function getTransmisiones()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectTransmisiones();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['activo_label'] = $arrData[$i]['activo'] == 1
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';

                $btnEdit = '';
                $btnDelete = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar transmisión" onClick="fntEditInfo(' . $arrData[$i]['id_transmision'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if (!empty($_SESSION['permisosMod']['d'])) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar transmisión" onClick="fntDelInfo(' . $arrData[$i]['id_transmision'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getTransmision($id)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($id);
            $arrData = $this->model->selectTransmision($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setTransmision()
    {
        if ($_POST) {
            if (empty($_POST['fabricante-input']) || empty($_POST['modelo-input'])) {
                $arrResponse = array('status' => false, 'msg' => 'El fabricante y el modelo son obligatorios.');
            } else {
                $intId = intval($_POST['id_transmision']);
                $request = false;
                $option = 1;
                $idusuario = $_SESSION['userData']['idusuario'] ?? null;
                $antes = $intId > 0 ? $this->model->selectTransmision($intId) : [];

                $data = [
                    'fabricante'         => strClean($_POST['fabricante-input']),
                    'modelo'             => strClean($_POST['modelo-input']),
                    'tipo'               => strClean($_POST['tipo-select'] ?? ''),
                    'numero_velocidades' => strClean($_POST['velocidades-input'] ?? ''),
                    'descripcion'        => strClean($_POST['descripcion-textarea'] ?? ''),
                    'activo'             => intval($_POST['activo-select'] ?? 1),
                ];

                if ($intId == 0) {
                    if (!empty($_SESSION['permisosMod']['w'])) {
                        $request = $this->model->insertTransmision($data);
                        $option = 1;

                        if (!empty($request) && $request !== 'exist') {
                            $this->model->logAudit(
                                $request,
                                AuditAction::CREATED,
                                "Alta de transmisión: {$data['fabricante']} {$data['modelo']}",
                                $idusuario
                            );
                        }
                    }
                } else {
                    if (!empty($_SESSION['permisosMod']['u'])) {
                        $request = $this->model->updateTransmision($intId, $data);
                        $option = 2;

                        if (!empty($request) && $request !== 'exist') {
                            $this->model->logAudit(
                                $intId,
                                AuditAction::UPDATED,
                                auditDiff($antes, $data),
                                $idusuario
                            );
                        }
                    }
                }

                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe una transmisión con ese fabricante y modelo.');
                } else if (!empty($request)) {
                    $arrResponse = $option == 1
                        ? array('status' => true, 'msg' => 'La transmisión se registró exitosamente.', 'tipo' => 'insert')
                        : array('status' => true, 'msg' => 'La transmisión se actualizó correctamente.', 'tipo' => 'update');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar la transmisión.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delTransmision()
    {
        if ($_POST) {
            if (!empty($_SESSION['permisosMod']['d'])) {
                $intId = intval($_POST['id_transmision']);
                $request = $this->model->deleteTransmision($intId);
                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'La transmisión fue eliminada satisfactoriamente.')
                    : array('status' => false, 'msg' => 'Error al eliminar la transmisión.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getSelectTransmisiones()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionTransmisiones();
        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id_transmision'] . '">' . $row['fabricante'] . ' ' . $row['modelo'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }
}
