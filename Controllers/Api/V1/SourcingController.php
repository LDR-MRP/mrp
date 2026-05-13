<?php
declare(strict_types=1);

namespace Controllers\Api\V1;

class SourcingController
{
    use \ApiResponser;

    protected \SourcingService $sourcingService;

    public array $request = [];

    public function __construct() {
        $this->sourcingService = new \SourcingService();
    }

    /**
     * Obtiene la ficha técnica y todas las cotizaciones de una partida.
     * GET /api/v1/sourcing/comparison/{id_partida}
     */
    public function getComparison(int $idPartida): string {
        $response = $this->sourcingService->getComparisonData($idPartida, $this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Registra una nueva cotización de proveedor.
     * POST /api/v1/sourcing/quotations
     */
    public function addQuotation(): string {
        $response = $this->sourcingService->storeQuotation($this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Endpoint para autorizar la cotización ganadora de un proceso de sourcing.
     * POST /api/v1/sourcing/quotations/{id}/select-winner
     *
     * @param int $id ID de la cotización (com_requisicion_cotizaciones)
     * @return string Respuesta JSON estandarizada.
     */
    public function selectWinner(int $id): string
    {
        // Preparamos el payload incluyendo el ID que viene de la URL
        $data = ['idcotizacion' => $id];

        // El Service se encarga de la transacción ACID y la sincronización de precios
        $response = $this->sourcingService->selectWinner(
            $data, 
            $this->request['auth_user']
        );
        
        return $this->apiResponse($response);
    }

    public function promoteToCatalog(): string {
        $response = $this->sourcingService->promoteToCatalog($this->request['auth_user']);
        return $this->apiResponse($response);
    }
}