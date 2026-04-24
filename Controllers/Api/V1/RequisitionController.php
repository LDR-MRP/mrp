<?php
namespace Controllers\Api\V1;

use Services\RequisitionService;

class RequisitionController
{
    use \ApiResponser;

    protected \RequisitionService $requisitionService;

    public array $request = [];

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
        return $this->apiResponse(
            $this->requisitionService->getRequisitionWithDetails(
                (int)$id,
                $this->request['auth_user']
            )
        );
    }

    // POST /api/v1/requisitions
    public function store()
    {      
        return $this->apiResponse($this->requisitionService->store($this->request['auth_user']));
    }

    // PUT /api/v1/requisitions/{id}
    public function update(int $id)
    {  
        return $this->apiResponse($this->requisitionService->update((int)$id, $this->request['auth_user']));
    }

    // POST /api/v1/requisitions/{id}/items/move
    public function moveItems(int $id)
    {
        return $this->apiResponse($this->requisitionService->moveItems((int)$id, $this->request['auth_user']));
    }

    // DELETE /api/v1/requisitions/{id}/items/{item_id}
    public function deleteItem(int $id, int $itemId)
    {
        return $this->apiResponse($this->requisitionService->deleteItem((int)$id, (int)$itemId, $this->request['auth_user']));
    }

    // GET /api/v1/requisitions/kpis
    public function kpis()
    {
        return $this->apiResponse($this->requisitionService->getKpis($this->request['auth_user']));
    }

    // POST /api/v1/requisitions/{id}/approve
    public function approve(int $id)
    {
        $serviceResponse = $this->requisitionService->approve((int)$id, $this->request['auth_user']);
        return $this->apiResponse($serviceResponse);
    }

    // POST /api/v1/requisitions/{id}/reject
    public function reject(int $id)
    {
        $serviceResponse = $this->requisitionService->reject((int)$id, $this->request['auth_user']);
        return $this->apiResponse($serviceResponse);
    }

    // POST /api/v1/requisitions/{id}/cancel
    public function cancel(int $id)
    {
        $serviceResponse = $this->requisitionService->cancel((int)$id, $this->request['auth_user']);
        return $this->apiResponse($serviceResponse);
    }
    
    // POST /api/v1/requisitions/{id}/return-to-draft
    public function returnToDraft(int $id)
    {
        $serviceResponse = $this->requisitionService->returnToDraft((int)$id, $this->request['auth_user']);
        return $this->apiResponse($serviceResponse);
    }

    // DELETE /api/v1/requisitions/{id}
    public function destroy(int $id)
    {
        $serviceResponse = $this->requisitionService->destroy((int)$id, $this->request['auth_user']);
        return $this->apiResponse($serviceResponse);
    }

    // GET /api/v1/requisitions/{id}/pending-items
    public function getPendingItems(string $id)
    {
        $serviceResponse = $this->requisitionService->getPendingItemsToPurchase((int)$id, $this->request['auth_user']);        
        return $this->apiResponse($serviceResponse);
    }

    public function generatePdf(string $id)
    {
        $pdfContent = $this->requisitionService->generatePdf((int)$id, $this->request['auth_user']);

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