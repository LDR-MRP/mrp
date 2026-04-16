<?php

class Com_orden extends Controllers
{
    use ApiResponser;

    private $compraService;

    private $requisitionService;

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
            "../Com_ordenes/create",
            [
                'page_tag' => "Generar Órden de Compra",
                'page_title' => "Generar Órden de Compra",
                'page_name' => "Generar Órden de Compra",
                'page_functions_js' => "functions_com_ordenes_create.js",

            ]
        );
    }

    public function exportPDF(int $id)
    {
        return $this->compraService->generatePremiumOCPDF($id);
    }
}
?>