<?php

class Com_compra extends Controllers
{
    use ApiResponser;

    private $comprasService;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(COM_COMPRAS);

        $this->comprasService = new Com_comprasService();
        $this->comprasService->model = $this->model;
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
        return $this->apiResponse($this->comprasService->create($_POST));      
    }

    public function generar()
    {
        $this->views->getView(
            $this,
            "../Com_compras/com_compra_generar",
            [
                'page_tag' => "Generar Órden de Compra",
                'page_title' => "Generar Órden de Compra",
                'page_name' => "Generar Órden de Compra",
                'page_functions_js' => "functions_com_compras_generar.js",

            ]
        );
    }
}
?>