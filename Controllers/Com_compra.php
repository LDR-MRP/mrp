<?php

class Com_compra extends Controllers
{
    use ApiResponser;

    private $compraService;

    private $requisitionService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        getPermisos(COM_COMPRAS);

        $this->compraService = new Com_compraService();
        $this->requisitionService = new Com_requisicionService();
    }

    public function Com_compra(): void
    {
        $this->views->getView(
            $this,
            "../Com_compras/index",
            [
                'page_tag' => "Compras",
                'page_title' => "Compras",
                'page_name' => "Compras",
                'page_functions_js' => "functions_com_compras_index.js",
            ]
        );
    }

    public function generar()
    {
        $this->views->getView(
            $this,
            "../Com_compras/com_compra_create",
            [
                'page_tag' => "Generar Órden de Compra",
                'page_title' => "Generar Órden de Compra",
                'page_name' => "Generar Órden de Compra",
                'page_functions_js' => "functions_com_compras_create.js",

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

    public function create(): array
    {
        return $this->apiResponse($this->compraService->create($_POST));      
    }

    /**
     * Obtiene la lista de requisiciones de compra.
     *
     * @return array|json La lista de requisiciones de compra.
     */
    public function getReqs(): array
    {
        return $this->apiResponse($this->requisitionService->index(sanitizeGet()));
    }

}
?>