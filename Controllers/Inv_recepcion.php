<?php
class Inv_recepcion extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        getPermisos(COM_COMPRAS);
    }

    public function create()
    {
        $this->views->getView(
            $this,
            "../Inv_recepcion/create",
            [
                'page_tag' => "Recepción",
                'page_title' => "Recepción",
                'page_name' => "Recepción",
                'page_functions_js' => "functions_inv_recepcion_create.js",

            ]
        );

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIRECEPCION);
    }

    public function Inv_recepcion()
    {
        $data['page_tag'] = "Recepción";
        $data['page_title'] = "Recepción";
        $data['page_name'] = "Recepción";
        $data['page_functions_js'] = "functions_inv_recepcion.js";

        $this->views->getView($this, "inv_recepcion", $data);
    }

    public function getOrdenesCompraPendientes()
    {
        echo json_encode($this->model->selectOrdenesCompraPendientes(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getDetalleOC($idCompra)
    {
        echo json_encode($this->model->selectDetalleOC($idCompra), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getHeaderOC($idCompra)
    {
        echo json_encode($this->model->selectHeaderOC($idCompra), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function setRecepcion()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $idCompra       = $data['compraid'];
        $detalle        = $data['detalle'];
        $observaciones  = $data['observaciones'] ?? '';
        $usuarioId      = $_SESSION['idUser'];

        $recepcion = $this->model->getRecepcionByCompra($idCompra);

        if (empty($recepcion)) {
            $recepcionid = $this->model->insertRecepcion($idCompra, $observaciones, $usuarioId);
        } else {
            $recepcionid = $recepcion['idrecepcion'];
            $this->model->updateRecepcionHeader($recepcionid, $observaciones);
        }

        foreach ($detalle as $item) {
            $sol = floatval($item['cantidad_solicitada']);
            $rec = floatval($item['cantidad_recibida']);

            if ($rec <= 0) {
                continue;
            }

            if ($rec > $sol) {
                echo json_encode([
                    "status" => false,
                    "msg" => "No puedes recibir más de lo solicitado."
                ]);
                die();
            }

            $this->model->insertDetalleRecepcion(
                $recepcionid,
                $item['inventarioid'],
                $item['codigo'],
                $item['lote'],
                $sol,
                $rec,
                $item['observaciones']
            );
        }

        $completa = $this->model->recepcionCompleta($idCompra);

        $this->model->updateRecepcionStatus(
            $recepcionid,
            $completa ? 'cerrada' : 'parcial'
        );

        echo json_encode([
            "status" => true,
            "msg" => $completa
                ? "Recepción registrada correctamente"
                : "Recepción parcial registrada correctamente"
        ]);
        die();
    }

    public function getOrdenesActivas()
    {
        echo json_encode($this->model->selectOrdenesActivas(), JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenesCerradas()
    {
        echo json_encode($this->model->selectOrdenesCerradas(), JSON_UNESCAPED_UNICODE);
        die();
    }
    public function getOrdenesAbiertas()
    {
        echo json_encode($this->model->selectOrdenesAbiertas(), JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getOrdenesParciales()
    {
        echo json_encode($this->model->selectOrdenesParciales(), JSON_UNESCAPED_UNICODE);
        die();

    }
}
