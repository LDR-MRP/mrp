<?php

class Srm extends Controllers
{
    public function __construct()
    {
        // El portal de autogestión es Stateless, las vistas se protegen vía JS/JWT,
        // por lo que no requerimos session_start() forzoso aquí.
        parent::__construct();
    }

    public function login()
    {
        // Data inyectada a la vista
        $data['page_tag'] = "Portal de Proveedores - LDR Solutions";
        $data['page_title'] = "Login Proveedores";
        $data['page_name'] = "srm_login";
        // Apuntamos al JS que creamos
        $data['page_functions_js'] = "srm/auth/srm_login.js"; 

        // Renderiza: Views/Srm_login/Auth/Login.php
        $this->views->getView($this, "../Srm/Auth/Login", $data);
    }

    /**
     * Renderiza el Dashboard Premium (Modern Theme)
     * URL: {{base_url}}/srm
     */
    public function dashboard()
    {
        $data['page_tag'] = "Resumen SRM - LDR Solutions";
        $data['page_title'] = "Dashboard Proveedores";
        $data['page_name'] = "srm_dashboard";
        
        // Apuntamos al JS de hidratación del Dashboard que creamos en el paso anterior
        $data['page_functions_js'] = "srm/dashboard/srm_dashboard.js";

        // Renderiza: Views/Srm/Dashboard.php
        $this->views->getView($this, "dashboard", $data);
    }

    /**
     * Renderiza el Expediente Digital (Carga de archivos con Dropzone)
     * URL: {{base_url}}/srm/dossier
     */
    public function dossier()
    {
        $data['page_tag'] = "Expediente Digital - LDR Solutions";
        $data['page_title'] = "Carga de Documentos";
        $data['page_name'] = "srm_dossier";
        
        // Apuntamos al JS de Dropzone que simula o guarda los documentos
        $data['page_functions_js'] = "srm/dossier/srm_dossier.js";

        // Navegamos al subdirectorio: Views/Srm/Dossier/index.php
        // Tu motor de vistas resolverá esto como: Views/Srm/Dossier/index.php
        $this->views->getView($this, "Dossier/index", $data);
    }

    /**
     * Renderiza el listado de Órdenes de Compra del proveedor
     * URL: {{base_url}}/srm/ordenes
     */
    public function purchaseOrders()
    {
        $data['page_tag'] = "Mis Órdenes de Compra - SRM";
        $data['page_title'] = "Órdenes de Compra";
        $data['page_name'] = "srm_purchase_orders";
        $data['page_functions_js'] = "srm/purchase_orders/srm_purchase_orders.js";

        // CORRECCIÓN: Renderiza: Views/Srm/PurchaseOrders/index.php
        $this->views->getView($this, "PurchaseOrders/index", $data);
    }

    /**
     * Renderiza el Buzón de Carga de Facturas XML + PDF
     * URL: {{base_url}}/srm/facturas
     */
    public function invoices()
    {
        $data['page_tag'] = "Buzón de Facturas - SRM";
        $data['page_title'] = "Buzón de Facturas";
        $data['page_name'] = "srm_invoices";
        $data['page_functions_js'] = "srm/invoices/srm_invoices.js";

        // CORRECCIÓN: Renderiza: Views/Srm/Invoices/index.php
        $this->views->getView($this, "Invoices/index", $data);
    }

    /**
     * Renderiza el Gestor de Cuentas Bancarias Autogestionable
     * URL: {{base_url}}/srm/bancos
     */
    public function bank()
    {
        $data['page_tag'] = "Mis Cuentas Bancarias - LDR Solutions";
        $data['page_title'] = "Cuentas Bancarias";
        $data['page_name'] = "srm_bank";
        
        // Apuntamos al JS del módulo de bancos que escribimos en el paso anterior
        $data['page_functions_js'] = "srm/bank/srm_bank.js";

        // Renderiza: Views/Srm/Bank/index.php (Sigue la taxonomía de tus carpetas de VS Code)
        $this->views->getView($this, "Bank/index", $data);
    }
}