<?php
class Inv_picking extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIPICKING);
    }

    public function Inv_picking()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Picking";
        $data['page_title'] = "Picking";
        $data['page_name'] = "Picking";
        $data['page_functions_js'] = "functions_inv_picking.js";

        $this->views->getView($this, "inv_picking", $data);
    }

    // =========================
    // OCs pendientes
    // =========================
    public function getOrdenesCompraPendientes()
    {
        $arrData = $this->model->selectOrdenesCompraPendientes();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =========================
    // Detalle OC
    // =========================
    public function getDetalleOC($idCompra)
    {
        $arrData = $this->model->selectDetalleOC($idCompra);
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =========================
    // Ejecutar Picking
    // =========================
    public function setPicking()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $idCompra      = $data['compraid'];
        $observaciones = $data['observaciones'] ?? '';
        $detalle       = $data['detalle'];
        $usuarioId     = $_SESSION['idUser'];

        $picking = $this->model->getPickingByCompra($idCompra);

        if (empty($picking)) {
            $pickingid = $this->model->insertPicking(
                $idCompra,
                '',             // pedido_cliente
                'MEDIA',        // prioridad
                $observaciones,
                $usuarioId
            );
        } else {
            $pickingid = $picking['idpicking'];
        }

        $completo = true;

        foreach ($detalle as $item) {

            $cantidadSolicitada = floatval($item['cantidad_solicitada']);
            $cantidadRecibida   = floatval($item['cantidad_recibida']);

            if ($cantidadRecibida <= 0) {
                $completo = false;
                continue;
            }

            // Guardar recepción detalle
            $this->model->insertDetallePicking(
                $pickingid,
                $item['inventarioid'],
                null, // ubicación aún no definida (putaway después)
                $item['lote'] ?? '',
                $cantidadSolicitada,
                $cantidadRecibida,
                $item['observaciones'] ?? ''
            );

            // Validar parcial
            if ($cantidadRecibida < $cantidadSolicitada) {
                $completo = false;
            }
        }

        $estatus = $completo ? 'cerrada' : 'recibida_parcial';
        $this->model->updateOrdenCompra($idCompra, $estatus);

        echo json_encode([
            "status" => true,
            "msg" => $completo
                ? "Recepción registrada correctamente"
                : "Recepción parcial registrada correctamente"
        ]);
        die();
    }

    public function getHeaderOC($idCompra)
    {
        $arrData = $this->model->selectHeaderOC($idCompra);
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }
}
