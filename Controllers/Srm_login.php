<?php 

class Srm_login extends Controllers {
    
    public function __construct()
    {
        // NO iniciamos session_start() aquí.
        // La autenticación del SRM es 100% Stateless basada en JWT.
        parent::__construct();
    }

    public function Srm_login()
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
}
?>