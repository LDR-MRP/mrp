<?php

class Lgs_panelrutas extends Controllers
{
    use ApiResponser;

    private Lgs_panelrutasService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_panelrutasService();
    }

    /**
     * Renderiza la vista principal del Panel de Rutas Geográficas
     * URL: {{base_url}}/Lgs_panelrutas
     */
    public function Lgs_panelrutas(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_panelrutas/index",
            [
                'page_tag' => "Monitoreo GPS",
                'page_title' => "Panel de Rutas Geográficas",
                'page_name' => "lgs_panelrutas",
                'page_functions_js' => "functions_lgs_panelrutas.js",
            ]
        );
    }

    /**
     * Devuelve el JSON con las rutas activas y sus coordenadas GPS
     * URL: {{base_url}}/Lgs_panelrutas/getRutasMapa
     */
    public function getRutasMapa(): void
    {
        try {
            $plantaId = ($_SESSION['userData']['idrol'] ?? 0) == 1 ? null : ($_SESSION['userData']['plantaid'] ?? null);
            $data = $this->service->getRutasActivasMapa($plantaId);
            echo $this->successResponse($data, "Rutas activas en tránsito obtenidas");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
