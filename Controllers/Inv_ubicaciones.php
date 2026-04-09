<?php
class Inv_ubicaciones extends Controllers
{
    use ApiResponser;

    protected $ubicacionesService;

    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MIUBICACIONES);

        $this->ubicacionesService = new Inv_ubicacionesService;
        $this->ubicacionesService->model = $this->model;
    }

    public function inv_ubicaciones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Ubicaciones";
        $data['page_title'] = "Ubicaciones";
        $data['page_name'] = "Ubicaciones";
        $data['page_functions_js'] = "functions_inv_ubicaciones.js";

        $this->views->getView($this, "inv_ubicaciones", $data);
    }

    public function getUbicaciones()
    {
        return $this->apiResponse(
            $this->ubicacionesService->getUbicaciones()
        );
    }

    public function store()
{
    return $this->apiResponse(
        $this->ubicacionesService->store($_POST)
    );
}
}
