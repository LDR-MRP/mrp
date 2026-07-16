<?php

class Com_sourcing extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Entrega el HTML del Panel de Negociaciones
     * URL: {{base_url}}/com_sourcing
     */
    public function Com_sourcing(): void
    {
        $data['page_tag'] = "Panel de Negociaciones";
        $data['page_title'] = "Gestión de folios";
        $data['page_functions_js'] = "functions_com_sourcing_index.js";
        $this->views->getView($this, "../Com_sourcing/index", $data);
    }

    /**
     * Entrega el HTML de la Bandeja de Negociaciones
     * URL: {{base_url}}/com_sourcing/inbox
     */
    public function inbox(): void
    {
        $data['page_tag'] = "Partidas por Negociar";
        $data['page_title'] = "Selección y agrupación";
        $data['page_functions_js'] = "functions_com_sourcing_inbox.js";
        $this->views->getView($this, "../Com_sourcing/inbox", $data);
    }

    /**
     * Entrega el HTML del Detalle de Negociaciones
     * URL: {{base_url}}/com_sourcing/detail
     */
    public function detail(): void
    {
        $data['page_tag'] = "Detalle de Negociación";
        $data['page_title'] = "Captura, comparación y adjudicación";
        $data['page_functions_js'] = "functions_com_sourcing_detail.js";
        $this->views->getView($this, "../Com_sourcing/detail", $data);
    }
}