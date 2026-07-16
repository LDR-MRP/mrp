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
     * Obtiene la lista global de eventos de negociación para la bandeja principal.
     * GET /api/v1/sourcing/events
     */
    public function index(): string 
    {
        $response = $this->sourcingService->getActiveEvents($this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Recupera el espacio de trabajo completo de una negociación (Header + Items + Comparativa Inicial).
     * GET /api/v1/sourcing/events/{id}/workspace
     * 
     * @param int $id ID del evento de sourcing.
     */
    public function showWorkspace(int $id): string
    {
        // El SourcingService orquestará la unión de modelos
        $response = $this->sourcingService->getEventWorkspace($id, $this->request['auth_user']);
        
        return $this->apiResponse($response);
    }

    /**
     * Obtiene la ficha técnica y todas las cotizaciones de una partida.
     * GET /api/v1/sourcing/comparison/{id_partida}
     */
    public function getComparison(int $idPartida): string {
        $response = $this->sourcingService->getComparisonData($idPartida);
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

    public function deleteQuotation(int $id): string {
        $response = $this->sourcingService->deleteQuotation($id, $this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Obtiene las partidas de requisiciones aprobadas pendientes de asignar a un evento.
     * GET /api/v1/sourcing/pending-items
     */
    public function getPendingItems(): string
    {
        // Se asume que el Service filtra por plantaid del usuario
        $response = $this->sourcingService->getPendingSourcingItems($this->request['auth_user']);
        return $this->apiResponse($response);
    }

    /**
     * Crea un nuevo evento de negociación y agrupa las partidas seleccionadas.
     * POST /api/v1/sourcing/events
     */
    public function createEvent(): string
    {
        // El payload llega sanitizado desde el Router/Middleware al array $request
        $response = $this->sourcingService->createEvent($this->request['auth_user']);
        return $this->apiResponse($response);
    }
}