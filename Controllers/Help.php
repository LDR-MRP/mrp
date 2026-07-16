<?php
class Help extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        // getPermisos(MAYUDA); // Descomentar cuando se registre el módulo en la BD
    }

    public function requisitions()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        
        $data['page_tag'] = "Manual de Usuario - MRP";
        $data['page_title'] = "Centro de Conocimiento";
        $data['page_name'] = "manual_usuario";
        $data['page_functions_js'] = "functions_help.js";
        
        $this->views->getView($this, "../Help/Requisition/user_manual", $data);
    }

    public function purchases()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        
        $data['page_tag'] = "Manual de Usuario - MRP";
        $data['page_title'] = "Centro de Conocimiento";
        $data['page_name'] = "manual_usuario";
        $data['page_functions_js'] = "functions_help.js";
        
        $this->views->getView($this, "../Help/PurchaseOrder/user_manual", $data);
    }

    public function suppliers()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        
        $data['page_tag'] = "Manual de Usuario - MRP";
        $data['page_title'] = "Centro de Conocimiento";
        $data['page_name'] = "manual_usuario";
        $data['page_functions_js'] = "functions_help.js";
        
        $this->views->getView($this, "../Help/Supplier/user_manual", $data);
    }
}