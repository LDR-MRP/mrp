<?php
class Ing_especificaciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_ESPECIFICACIONES);
    }

    public function Ing_especificaciones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Especificaciones";
        $data['page_title'] = "Especificaciones";
        $data['page_name'] = "Especificaciones";
        $data['page_functions_js'] = "functions_ing_especificaciones.js";
        $this->views->getView($this, "ing_especificaciones", $data);
    }

    public function getEspecificaciones()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectEspecificaciones();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['activo_label'] = $arrData[$i]['activo'] == 1
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';
                $arrData[$i]['desglose_facturacion_label'] = $arrData[$i]['desglose_facturacion'] == 1 ? 'Sí' : 'No';

                $btnEdit = '';
                $btnDelete = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar especificación" onClick="fntEditInfo(' . $arrData[$i]['id_especificacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if (!empty($_SESSION['permisosMod']['d'])) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar especificación" onClick="fntDelInfo(' . $arrData[$i]['id_especificacion'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getEspecificacion($id)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($id);
            $arrData = $this->model->selectEspecificacion($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setEspecificacion()
    {
        if ($_POST) {
            if (empty($_POST['categoria-select']) || empty($_POST['clave-input'])) {
                $arrResponse = array('status' => false, 'msg' => 'La categoría y la clave son obligatorias.');
            } else {
                $intId = intval($_POST['id_especificacion']);
                $request = false;
                $option = 1;

                $data = [
                    'categoria'             => strClean($_POST['categoria-select']),
                    'clave'                 => strClean($_POST['clave-input']),
                    'unidad'                => strClean($_POST['unidad-input'] ?? ''),
                    'desglose_facturacion'  => !empty($_POST['desglose-facturacion-check']) ? 1 : 0,
                    'orden'                 => $_POST['orden-input'] !== '' ? intval($_POST['orden-input']) : null,
                    'activo'                => intval($_POST['activo-select'] ?? 1),
                ];

                if ($intId == 0) {
                    if (!empty($_SESSION['permisosMod']['w'])) {
                        $request = $this->model->insertEspecificacion($data);
                        $option = 1;
                    }
                } else {
                    if (!empty($_SESSION['permisosMod']['u'])) {
                        $request = $this->model->updateEspecificacion($intId, $data);
                        $option = 2;
                    }
                }

                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe esa clave dentro de la categoría seleccionada.');
                } else if (!empty($request)) {
                    $arrResponse = $option == 1
                        ? array('status' => true, 'msg' => 'La especificación se registró exitosamente.', 'tipo' => 'insert')
                        : array('status' => true, 'msg' => 'La especificación se actualizó correctamente.', 'tipo' => 'update');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar la especificación.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delEspecificacion()
    {
        if ($_POST) {
            if (!empty($_SESSION['permisosMod']['d'])) {
                $intId = intval($_POST['id_especificacion']);
                $request = $this->model->deleteEspecificacion($intId);
                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'La especificación fue eliminada satisfactoriamente.')
                    : array('status' => false, 'msg' => 'Error al eliminar la especificación.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function getSelectCategorias()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionCategorias();
        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . htmlspecialchars($row['nombre'], ENT_QUOTES) . '">' . htmlspecialchars($row['nombre'], ENT_QUOTES) . '</option>';
        }
        echo $htmlOptions;
        die();
    }

    public function setCategoria()
    {
        if ($_POST) {
            if (empty($_SESSION['permisosMod']['w'])) {
                die();
            }
            if (empty($_POST['nombre'])) {
                $arrResponse = array('status' => false, 'msg' => 'El nombre de la categoría es obligatorio.');
            } else {
                $request = $this->model->insertCategoria(strClean($_POST['nombre']));
                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe una categoría con ese nombre.');
                } else if (!empty($request)) {
                    $arrResponse = array('status' => true, 'msg' => 'Categoría agregada correctamente.', 'nombre' => strClean($_POST['nombre']));
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar la categoría.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Listado para el modal "Gestionar categorías" (editar nombre/orden,
    // activar/desactivar). Solo lectura, permiso r.
    public function getCategorias()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectCategorias();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['activo_label'] = $arrData[$i]['activo'] == 1
                    ? '<span class="badge bg-success-subtle text-success">Activa</span>'
                    : '<span class="badge bg-danger-subtle text-danger">Inactiva</span>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Guarda los cambios de una fila del modal "Gestionar categorías"
    // (nombre, orden, activo). Requiere permiso u, como cualquier edición.
    public function updateCategoria()
    {
        if ($_POST) {
            if (empty($_SESSION['permisosMod']['u'])) {
                die();
            }
            $intId = intval($_POST['id_categoria'] ?? 0);
            if ($intId == 0 || empty($_POST['nombre'])) {
                $arrResponse = array('status' => false, 'msg' => 'Datos incompletos para actualizar la categoría.');
            } else {
                $data = [
                    'nombre' => strClean($_POST['nombre']),
                    'orden'  => ($_POST['orden'] ?? '') !== '' ? intval($_POST['orden']) : null,
                    'activo' => intval($_POST['activo'] ?? 1),
                ];
                $request = $this->model->updateCategoria($intId, $data);
                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe otra categoría con ese nombre.');
                } else if (!empty($request)) {
                    $arrResponse = array('status' => true, 'msg' => 'Categoría actualizada correctamente.');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible actualizar la categoría.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}
