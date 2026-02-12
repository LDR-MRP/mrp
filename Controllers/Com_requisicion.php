<?php

class Com_requisicion extends Controllers
{
    use ApiResponser;

    protected $requisitionService;

    public function __construct()
    {
        parent::__construct();
        session_start();

        getPermisos(COM_COMPRAS);

        $this->requisitionService = new Com_requisicionService;

        $this->requisitionService->model = $this->model;
    }

    public function Com_requisicion() {
        $this->views->getView(
            $this,
            "../Com_compras/com_requisicion",
            [
                'page_tag' => "Requisiciones",
                'page_title' => "Requisiciones",
                'page_name' => "Requisiciones",
                'page_functions_js' => "functions_com_requisiciones.js",

            ]
        );
    }

    public function nueva() {
        $this->views->getView(
            $this,
            "../Com_compras/com_requisicion_nueva",
            [
                'page_tag' => "Nueva Requisición",
                'page_title' => "Nueva Requisición",
                'page_name' => "Nueva Requisición",
                'page_functions_js' => "functions_com_requisiciones_nueva.js",

            ]
        );
    }

    public function detalle(mixed $id) {
        $this->views->getView(
            $this,
            "../Com_compras/com_requisicion_detalle",
            [
                'page_tag' => "Detalle de Requisición",
                'page_title' => "Detalle de Requisición",
                'page_name' => "Detalle de Requisición",
                'page_functions_js' => "functions_com_requisiciones_detalle.js",
            ]
        );
    }
    
    /* ======================================================
     * API (JSON): listar, mostrar, crear, aprobar, rechazar, cancelar, eliminar
     * ====================================================== */
    public function index()
    {
        return $this->apiResponse($this->requisitionService->index($filters = sanitizeGet()));
    }

    public function show(int $id = null)
    {
        return $this->apiResponse($this->requisitionService->requisition($id));
    }

    public function detail(int $id = null)
    {
        return $this->apiResponse($this->requisitionService->detail($id));
    }

    public function store()
    {
        return $this->apiResponse($this->requisitionService->store(file_get_contents('php://input')));
    }

    public function approve()
    {
        return $this->apiResponse($this->requisitionService->changeStatus($_POST, Com_requisicionModel::ESTATUS_APROBADA));
    }

    public function reject()
    {
        return $this->apiResponse($this->requisitionService->changeStatus($_POST, Com_requisicionModel::ESTATUS_RECHAZADA));
    }

    public function cancel()
    {
        return $this->apiResponse($this->requisitionService->changeStatus($_POST, Com_requisicionModel::ESTATUS_CANCELADA));
    }

    public function destroy()
    {
        return $this->apiResponse($this->requisitionService->changeStatus($_POST, Com_requisicionModel::ESTATUS_ELIMINADA));
    }
}
