<?php

class Prv_proveedor  extends Controllers{

    use ApiResponser;

    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        getPermisos(PRV_PROVEEDORES);
    }

    public function Prv_proveedor() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/index",
            [
                'page_tag' => "Proveedores",
                'page_title' => "Proveedores",
                'page_name' => "Proveedores",
                'page_functions_js' => "functions_prv_proveedores_index.js",
            ]
        );
    }

    public function create() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/create",
            [
                'page_tag' => "Nuevo",
                'page_title' => "Nuevo",
                'page_name' => "Nuevo",
                'page_icon' => "ri-user-add-line",
                'page_action' => "Alta de Registro",
                'page_action_type' => "Nuevo Proveedor",
                'page_description' => "Complete la información fiscal y comercial para dar de alta al socio.",
                'page_functions_js' => "functions_prv_proveedores_create.js",
            ]
        );
    }

    public function edit() {
        $this->views->getView(
            $this,
            "../Prv_proveedor/create",
            [
                'page_tag' => "Editar",
                'page_title' => "Editar",
                'page_name' => "Editar",
                'page_icon' => "ri-edit-2-line",
                'page_action' => "Edición de Registro",
                'page_action_type' => "Editar Proveedor",
                'page_description' => "Complete la información fiscal y comercial para editar al socio.",
                'page_functions_js' => "functions_prv_proveedores_create.js",
            ]
        );
    }

    /**
     * Renderiza el Reporte Analítico de Onboarding para el CEO
     * URL: {{base_url}}/prv_proveedor/reporte
     */
    public function reporte(): void
    {
        $data['page_tag'] = "Reporte Ejecutivo - LDR Solutions";
        $data['page_title'] = "Reporte de Onboarding";
        $data['page_name'] = "prv_reporte_onboarding";
        
        // JS exclusivo que dará vida a los gráficos y la tabla ejecutiva
        $data['page_functions_js'] = "functions_prv_proveedores_reporte_index.js";

        // Renderiza de forma Stateless: Views/Prv_proveedor/reporte.php
        $this->views->getView($this, "../Prv_proveedor/reporte", $data);
    }
    public function delete(){
        // Recibir datos del POST body
        $idProveedor = $_POST['idproveedor'] ?? null;
        $rfc = $_POST['rfc'] ?? null;

        if (empty($idProveedor)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID de proveedor no proporcionado'
            ]);
            return;
        }

        // Obtener ID del usuario en sesión
        $userId = $_SESSION['idUser'] ?? null;

        if (empty($userId)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ]);
            return;
        }

        // Instanciar modelo
        $proveedorModel = new Prv_proveedorModel();

        try {
            // Ejecutar soft-delete
            $result = $proveedorModel->destroy((int)$idProveedor, (int)$userId);

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Proveedor eliminado correctamente'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar el proveedor'
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
