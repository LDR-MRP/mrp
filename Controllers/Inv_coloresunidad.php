<?php
// Inventario > Catálogos > Colores de unidad
// Alta de los colores que después se asignan a cada unidad en la pestaña
// "Portal Web" de Configuración de productos (Inv_inventario).
class Inv_coloresunidad extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MICOLORESUNIDAD);
    }

    public function Inv_coloresunidad()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }
        $data['page_tag'] = "Colores de unidad";
        $data['page_title'] = "Colores de unidad";
        $data['page_name'] = "colores_unidad";
        $data['page_functions_js'] = "functions_inv_coloresunidad.js";
        $this->views->getView($this, "inv_coloresunidad", $data);
    }

    private function responder(array $arr): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function setColor()
    {
        if (!$_POST) {
            $this->responder(['status' => false, 'msg' => 'Petición inválida']);
        }

        $idcolor = intval($_POST['idcolor'] ?? 0);
        $nombre = strClean(trim($_POST['nombre'] ?? ''));
        $descripcion = strClean(trim($_POST['descripcion'] ?? ''));
        $estado = intval($_POST['estado'] ?? 2) === 1 ? 1 : 2;

        if ($nombre === '') {
            $this->responder(['status' => false, 'msg' => 'El nombre del color es obligatorio']);
        }

        if ($idcolor === 0) {
            if (empty($_SESSION['permisosMod']['w'])) {
                $this->responder(['status' => false, 'msg' => 'No tienes permiso para crear colores']);
            }
            $resp = $this->model->insertColor($nombre, $descripcion, $estado);
            $msgOk = 'Color registrado correctamente';
        } else {
            if (empty($_SESSION['permisosMod']['u'])) {
                $this->responder(['status' => false, 'msg' => 'No tienes permiso para actualizar colores']);
            }
            $resp = $this->model->updateColor($idcolor, $nombre, $descripcion, $estado);
            $msgOk = 'Color actualizado correctamente';
        }

        if ($resp === "exist") {
            $this->responder(['status' => false, 'msg' => '¡Atención! Ya existe un color con ese nombre']);
        }

        $this->responder($resp !== false
            ? ['status' => true, 'msg' => $msgOk]
            : ['status' => false, 'msg' => 'No fue posible guardar el color']);
    }

    public function getColores()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->responder([]);
        }

        $arrData = $this->model->selectColores();
        foreach ($arrData as &$row) {
            $id = (int) $row['idcolor'];
            $row['estado_badge'] = ((int) $row['estado'] === 2)
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            $btnEdit = !empty($_SESSION['permisosMod']['u'])
                ? '<button class="btn btn-sm btn-soft-warning" title="Editar" onClick="fntEditColor(' . $id . ')"><i class="ri-pencil-fill align-bottom"></i></button>'
                : '';
            $btnDel = !empty($_SESSION['permisosMod']['d'])
                ? '<button class="btn btn-sm btn-soft-danger" title="Eliminar" onClick="fntDelColor(' . $id . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>'
                : '';
            $row['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDel . '</div>';
        }
        unset($row);

        $this->responder($arrData);
    }

    public function getColor($idcolor)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->responder(['status' => false, 'msg' => 'Sin permiso']);
        }
        $data = $this->model->selectColor(intval($idcolor));
        $this->responder(empty($data)
            ? ['status' => false, 'msg' => 'Datos no encontrados']
            : ['status' => true, 'data' => $data]);
    }

    public function delColor()
    {
        if (empty($_SESSION['permisosMod']['d'])) {
            $this->responder(['status' => false, 'msg' => 'No tienes permiso para eliminar colores']);
        }
        $idcolor = intval($_POST['idcolor'] ?? 0);
        $ok = $idcolor > 0 ? $this->model->deleteColor($idcolor) : false;
        $this->responder($ok
            ? ['status' => true, 'msg' => 'Color eliminado correctamente']
            : ['status' => false, 'msg' => 'No se pudo eliminar el color']);
    }
}
