<?php
class Inv_tipo_cambio_moneda extends Controllers
{
    use ApiResponser;

    protected $tipocambiomonedaService;

    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MITIPOCAMBIOMONEDA);

        $this->tipocambiomonedaService = new Inv_tipo_cambio_monedaService;
        $this->tipocambiomonedaService->model = $this->model;
    }

    public function inv_tipo_cambio_moneda()
{
    $data['page_tag'] = "Tipo de Cambio";
    $data['page_title'] = "Tipo de Cambio";
    $data['page_name'] = "Tipo de cambio";
    $data['page_functions_js'] = "functions_inv_tipo_cambio_moneda.js";
    $this->views->getView($this, "inv_tipo_cambio_moneda", $data);
}

public function getTipoCambio()
{
    return $this->apiResponse(
        $this->tipocambiomonedaService->getAll($_GET)
    );
}

public function store()
{
    return $this->apiResponse(
        $this->tipocambiomonedaService->store($_POST)
    );
}

public function getMonedas()
{
    $sql = "SELECT idmoneda, cve_moneda, descripcion 
            FROM wms_moneda 
            WHERE estado = 2";

    $data = $this->model->select_all($sql);

    echo json_encode($data);
    die();
}
}