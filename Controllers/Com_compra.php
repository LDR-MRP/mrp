<?php

class Com_compra extends Controllers
{
    use ApiResponser;

    private $compraService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(COM_COMPRAS);

        $this->compraService = new Com_compraService();
        $this->compraService->model = $this->model;
    }

    public function Com_compra(): void
    {
        $this->views->getView(
            $this,
            "../Com_compras/com_compra",
            [
                'page_tag' => "Compras",
                'page_title' => "Compras",
                'page_name' => "Compras",
                'page_functions_js' => "functions_com_compras.js",
            ]
        );
    }

    public function create(): array
    {
        return $this->apiResponse($this->compraService->create($_POST));      
    }

    public function generar()
    {
        $this->views->getView(
            $this,
            "../Com_compras/com_compra_nueva",
            [
                'page_tag' => "Generar Órden de Compra",
                'page_title' => "Generar Órden de Compra",
                'page_name' => "Generar Órden de Compra",
                'page_functions_js' => "functions_com_compras_nueva.js",

            ]
        );
    }

    /* ======================================================
     * API (JSON): listar, mostrar, crear, aprobar, rechazar, cancelar, eliminar
     * ====================================================== */
    public function suppliers()
    {
        return $this->apiResponse($this->compraService->suppliers($filters = sanitizeGet()));
    }

    public function store()
    {
        return $this->apiResponse($this->compraService->store(file_get_contents('php://input')));
    }

}
?>