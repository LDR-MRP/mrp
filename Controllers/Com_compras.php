<?php

class Com_compras extends Controllers
{
    use ApiResponser;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(COM_COMPRAS);
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
        $arrData = $this->model->selectCompras();

        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }

    public function create(): array
    {
        try{
            $request = new Com_comprasRequest($_POST);

            $request->validate();

            $comprasService = new Com_comprasService();

            $comprasService->create($request->all());

            return $this->successResponse(message: "Compra procesada con éxito.", code: 201);

        } catch(Throwable $t){
            $code = $t->getCode();

            return $this->errorResponse(
                ($code == 422) ? "Errores de validación" : "Error de sistema",
                ($code >= 400 && $code <= 599) ? $code : 500,
                ($code == 422) ? json_decode($t->getMessage()) : $t->getMessage()
            );
        }
        
    }
}
?>