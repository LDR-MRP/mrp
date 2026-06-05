<?php

class Accountspayableinvoice extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Entrega el HTML de la Bandeja de Conciliación de Cuentas por Pagar
     * URL: {{base_url}}/cxp_facturas
     */
    public function index(): void
    {
        $data['page_tag'] = "Bandeja de Facturas - LDR Solutions";
        $data['page_title'] = "Cuentas por Pagar (CxP)";
        $data['page_name'] = "cxp_facturas";
        $data['page_functions_js'] = "functions_cxp_invoices.js";
        $this->views->getView($this, "../Cxp_facturas/index", $data);
    }
}