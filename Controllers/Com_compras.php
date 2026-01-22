<?php

class Com_compras extends Controllers
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

    public function Com_compras(): void
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Compras";
        $data['page_title'] = "Compras";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_com_compras.js";

        $this->views->getView($this, "com_compras", $data);
    }

    public function index(): void
    {
        echo json_encode($this->comprasService->index(), JSON_UNESCAPED_UNICODE);
    }

    public function create(): array
    {
        return $this->apiResponse($this->comprasService->create($_POST));      
    }
}
?>