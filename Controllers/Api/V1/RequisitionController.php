<?php
namespace Controllers\Api\V1;

use ServiceResponse;
use Services\RequisitionService;

class RequisitionController
{
    use \ApiResponser;

    protected \RequisitionService $requisitionService;

    public function __construct()
    {
        $this->requisitionService = new \RequisitionService;
    }
    
    // GET /api/v1/requisitions
    public function index()
    {
        return $this->apiResponse($this->requisitionService->index());
    }

    // GET /api/v1/requisitions/{id}
    public function show(int $id)
    {
        // TODO: En un futuro, esto vendrá del AuthMiddleware
        $authenticatedUserId = 19;
        return $this->apiResponse($this->requisitionService->getRequisitionWithDetails((int)$id, $authenticatedUserId));
    }

    // POST /api/v1/requisitions
    public function store()
    {
        $authenticatedUserId = 199; // TODO: Extraer del Middleware JWT        
        return $this->apiResponse($this->requisitionService->store((int)$authenticatedUserId));
    }

    // PUT /api/v1/requisitions/{id}
    public function update(int $id)
    {
        $authenticatedUserId = 19; // TODO: Extraer del Middleware JWT        
        return $this->apiResponse($this->requisitionService->update((int)$id, (int)$authenticatedUserId));
    }

    // POST /api/v1/requisitions/{id}/items/move
    public function moveItems(int $id)
    {
        $authenticatedUserId = 19; // TODO: Extraer del Middleware JWT        
        return $this->apiResponse($this->requisitionService->moveItems((int)$id, $authenticatedUserId));
    }

    // DELETE /api/v1/requisitions/{id}/items/{item_id}
    public function deleteItem(int $id, int $itemId)
    {
        $authenticatedUserId = 19; // TODO: Extraer del Middleware JWT        
        return $this->apiResponse($this->requisitionService->deleteItem((int)$id, (int)$itemId, (int)$authenticatedUserId));
    }

    // GET /api/v1/requisitions/kpis
    public function kpis()
    {
        $authenticatedUserId = 19; // TODO: Extraer del Middleware JWT        
        return $this->apiResponse($this->requisitionService->getKpis((int)$authenticatedUserId));
    }

    // POST /api/v1/requisitions/{id}/approve
    public function approve(int $id)
    {
        $authenticatedUserId = 1; // TODO: JWT
        $serviceResponse = $this->requisitionService->approve((int)$id, $authenticatedUserId);
        return $this->apiResponse($serviceResponse);
    }

    // POST /api/v1/requisitions/{id}/reject
    public function reject(int $id)
    {
        $authenticatedUserId = 19; // TODO: JWT
        $serviceResponse = $this->requisitionService->reject((int)$id, $authenticatedUserId);
        return $this->apiResponse($serviceResponse);
    }
    
    // POST /api/v1/requisitions/{id}/return-to-draft
    public function returnToDraft(int $id)
    {
        $authenticatedUserId = 19; // TODO: JWT
        $serviceResponse = $this->requisitionService->returnToDraft((int)$id, $authenticatedUserId);
        return $this->apiResponse($serviceResponse);
    }

    // DELETE /api/v1/requisitions/{id}
    public function destroy(int $id)
    {
        $authenticatedUserId = 19; // TODO: JWT
        $serviceResponse = $this->requisitionService->destroy((int)$id, $authenticatedUserId);
        return $this->apiResponse($serviceResponse);
    }

    public function generatePdf(string $id)
    {
        $authenticatedUserId = 1; // TODO: JWT

        // El servicio orquesta la obtención de datos y la generación
        $pdfContent = $this->requisitionService->generatePdf((int)$id, $authenticatedUserId);

        // Si el servicio devuelve false o lanza excepción, lo manejas.
        // Si devuelve el contenido binario del PDF, lo escupes con los headers:
        
        ob_clean(); // Limpiar cualquier output previo
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Requisicion_REQ-'.str_pad($id, 5, '0', STR_PAD_LEFT).'.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdfContent;
        exit;
    }
}