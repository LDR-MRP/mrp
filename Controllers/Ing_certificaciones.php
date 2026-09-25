<?php
class Ing_certificaciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_CERTIFICACIONES);
    }

    public function Ing_certificaciones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Certificaciones";
        $data['page_title'] = "Certificaciones";
        $data['page_name'] = "Certificaciones";
        $data['page_functions_js'] = "functions_ing_certificaciones.js";
        $this->views->getView($this, "ing_certificaciones", $data);
    }

    public function getCertificaciones()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectCertificaciones();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['activo_label'] = $arrData[$i]['activo'] == 1
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';
                $arrData[$i]['requiere_documento_label'] = $arrData[$i]['requiere_documento'] == 1 ? 'Sí' : 'No';
                $arrData[$i]['requiere_vigencia_label'] = $arrData[$i]['requiere_vigencia'] == 1 ? 'Sí' : 'No';

                $btnEdit = '';
                $btnDelete = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar certificación" onClick="fntEditInfo(' . $arrData[$i]['id_certificacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if (!empty($_SESSION['permisosMod']['d'])) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar certificación" onClick="fntDelInfo(' . $arrData[$i]['id_certificacion'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getCertificacion($id)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($id);
            $arrData = $this->model->selectCertificacion($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setCertificacion()
    {
        if ($_POST) {
            if (empty($_POST['codigo-input']) || empty($_POST['nombre-input'])) {
                $arrResponse = array('status' => false, 'msg' => 'El código y el nombre son obligatorios.');
            } else {
                $intId = intval($_POST['id_certificacion']);
                $request = false;
                $option = 1;
                $idusuario = $_SESSION['userData']['idusuario'] ?? null;
                $antes = $intId > 0 ? $this->model->selectCertificacion($intId) : [];

                $data = [
                    'codigo'             => strtoupper(strClean($_POST['codigo-input'])),
                    'nombre'             => strClean($_POST['nombre-input']),
                    'descripcion'        => strClean($_POST['descripcion-textarea'] ?? ''),
                    'autoridad'          => strClean($_POST['autoridad-input'] ?? ''),
                    'tipo'               => strClean($_POST['tipo-input'] ?? ''),
                    'requiere_documento' => !empty($_POST['requiere-documento-check']) ? 1 : 0,
                    'requiere_vigencia'  => !empty($_POST['requiere-vigencia-check']) ? 1 : 0,
                    'activo'             => intval($_POST['activo-select'] ?? 1),
                ];

                if ($intId == 0) {
                    if (!empty($_SESSION['permisosMod']['w'])) {
                        $request = $this->model->insertCertificacion($data);
                        $option = 1;

                        if (!empty($request) && $request !== 'exist') {
                            $this->model->logAudit(
                                $request,
                                AuditAction::CREATED,
                                "Alta de certificación: {$data['codigo']} - {$data['nombre']}",
                                $idusuario
                            );
                        }
                    }
                } else {
                    if (!empty($_SESSION['permisosMod']['u'])) {
                        $request = $this->model->updateCertificacion($intId, $data);
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
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe una certificación con ese código.');
                } else if (!empty($request)) {
                    $arrResponse = $option == 1
                        ? array('status' => true, 'msg' => 'La certificación se registró exitosamente.', 'tipo' => 'insert')
                        : array('status' => true, 'msg' => 'La certificación se actualizó correctamente.', 'tipo' => 'update');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar la certificación.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delCertificacion()
    {
        if ($_POST) {
            if (!empty($_SESSION['permisosMod']['d'])) {
                $intId = intval($_POST['id_certificacion']);
                $request = $this->model->deleteCertificacion($intId);
                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'La certificación fue eliminada satisfactoriamente.')
                    : array('status' => false, 'msg' => 'Error al eliminar la certificación.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getSelectCertificaciones()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionCertificaciones();
        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id_certificacion'] . '">' . $row['codigo'] . ' - ' . $row['nombre'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }
}
