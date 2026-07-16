<?php

class Accountspayablepayment extends Controllers
{
    public function __construct()
    {
        // 100% Stateless (Sin session_start)
        parent::__construct();
    }

    /**
     * Entrega el HTML de la Bandeja de Programación de Pagos (Dispersión)
     * URL: {{base_url}}/accountspayablepayment
     */
    public function index(): void
    {
        $data['page_tag'] = "Programación de Pagos - LDR Solutions";
        $data['page_title'] = "Dispersión de Pagos";
        $data['page_name'] = "accountspayablepayment";
        $data['page_functions_js'] = "functions_cxp_payments_index.js";
        $this->views->getView($this, "../Cxp_pagos/index", $data);
    }
}