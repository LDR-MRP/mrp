<?php
class Ing_motores extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_MOTORES);
    }

    public function Ing_motores()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Motores";
        $data['page_title'] = "Motores";
        $data['page_name'] = "Motores";
        $data['page_functions_js'] = "functions_ing_motores.js";
        $this->views->getView($this, "ing_motores", $data);
    }

    public function getMotores()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectMotores();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['tipo_motor_label'] = $arrData[$i]['tipo_motor'] == 'ELECTRICO' ? 'Eléctrico' : 'Combustión';
                $arrData[$i]['activo_label'] = $arrData[$i]['activo'] == 1
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';

                $btnEdit = '';
                $btnDelete = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar motor" onClick="fntEditInfo(' . $arrData[$i]['id_motor'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if (!empty($_SESSION['permisosMod']['d'])) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar motor" onClick="fntDelInfo(' . $arrData[$i]['id_motor'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getMotor($id)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($id);
            $arrData = $this->model->selectMotor($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setMotor()
    {
        if ($_POST) {
            if (empty($_POST['fabricante-input']) || empty($_POST['modelo-motor-input'])) {
                $arrResponse = array('status' => false, 'msg' => 'El fabricante y el modelo del motor son obligatorios.');
            } else {
                $intIdMotor = intval($_POST['id_motor']);
                $request = false;
                $option = 1;

                $data = [
                    'fabricante'         => strClean($_POST['fabricante-input']),
                    'modelo_motor'       => strClean($_POST['modelo-motor-input']),
                    'tipo_motor'         => in_array(($_POST['tipo-motor-select'] ?? ''), ['COMBUSTION', 'ELECTRICO']) ? $_POST['tipo-motor-select'] : 'COMBUSTION',
                    'cilindrada'         => $_POST['cilindrada-input'] !== '' ? floatval($_POST['cilindrada-input']) : null,
                    'numero_cilindros'   => $_POST['cilindros-input'] !== '' ? intval($_POST['cilindros-input']) : null,
                    'potencia'           => $_POST['potencia-input'] !== '' ? floatval($_POST['potencia-input']) : null,
                    'unidad_potencia'    => strClean($_POST['unidad-potencia-select'] ?? ''),
                    'torque'             => $_POST['torque-input'] !== '' ? floatval($_POST['torque-input']) : null,
                    'unidad_torque'      => strClean($_POST['unidad-torque-select'] ?? ''),
                    'tipo_combustible'   => strClean($_POST['tipo-combustible-input'] ?? ''),
                    'tipo_admision'      => strClean($_POST['tipo-admision-input'] ?? ''),
                    'fabricante_bateria' => strClean($_POST['fabricante-bateria-input'] ?? ''),
                    'tipo_bateria'       => strClean($_POST['tipo-bateria-input'] ?? ''),
                    'capacidad_bateria'  => strClean($_POST['capacidad-bateria-input'] ?? ''),
                    'consumo'            => strClean($_POST['consumo-input'] ?? ''),
                    'conector'           => strClean($_POST['conector-input'] ?? ''),
                    'proteccion_ip'      => strClean($_POST['proteccion-ip-input'] ?? ''),
                    'sistema_electrico'  => strClean($_POST['sistema-electrico-input'] ?? ''),
                    'activo'             => intval($_POST['activo-select'] ?? 1),
                ];

                if ($intIdMotor == 0) {
                    if (!empty($_SESSION['permisosMod']['w'])) {
                        $request = $this->model->insertMotor($data);
                        $option = 1;
                    }
                } else {
                    if (!empty($_SESSION['permisosMod']['u'])) {
                        $request = $this->model->updateMotor($intIdMotor, $data);
                        $option = 2;
                    }
                }

                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe un motor con ese fabricante y modelo.');
                } else if (!empty($request)) {
                    $arrResponse = $option == 1
                        ? array('status' => true, 'msg' => 'El motor se registró exitosamente.', 'tipo' => 'insert')
                        : array('status' => true, 'msg' => 'El motor se actualizó correctamente.', 'tipo' => 'update');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar el motor.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delMotor()
    {
        if ($_POST) {
            if (!empty($_SESSION['permisosMod']['d'])) {
                $intId = intval($_POST['id_motor']);
                $request = $this->model->deleteMotor($intId);
                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'El motor fue eliminado satisfactoriamente.')
                    : array('status' => false, 'msg' => 'Error al eliminar el motor.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getSelectMotores()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionMotores();
        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id_motor'] . '">' . $row['fabricante'] . ' ' . $row['modelo_motor'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }
}
