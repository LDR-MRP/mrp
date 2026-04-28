<?php
namespace Controllers\Api\V1;

use Services\RequisitionService;
use Services\RequisitionPrintService;

class RequisitionController
{
    use \ApiResponser;

    protected \RequisitionService $requisitionService;

    protected readonly \RequisitionPrintService $requisitionPrintService;

    public array $request = [];

    public function __construct()
    {
        $this->requisitionService = new \RequisitionService;
        $this->requisitionPrintService = new \RequisitionPrintService;
    }
    
    // GET /api/v1/requisitions
    public function index()
    {
        // Capturamos los filtros de la URL (Query Params)
        $filters = [
            'estatus'     => $_GET['estatus'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        return $this->apiResponse($this->requisitionService->index($filters, $this->request['auth_user']));
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

    /**
     * Genera y descarga el PDF de la requisición.
     * GET /api/v1/requisitions/{id}/pdf
     */
    public function generatePdf(int $id)
    {
        // 1. Llamamos al service pasándole el contexto del usuario (JWT)
        $serviceResponse = $this->requisitionPrintService->generatePdf((int)$id, $this->request['auth_user']);

        // 2. Si el Service falló (IDOR, 403, 404), devolvemos JSON estándar
        if (!$serviceResponse) {
            return $this->apiResponse($serviceResponse);
        }

        // 3. Si tuvo éxito, extraemos el binario y los headers
        $pdfData = $serviceResponse->data;

        // Limpiar buffers para evitar basura en el PDF
        if (ob_get_length()) ob_end_clean();

        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"{$pdfData['filename']}\"");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        echo $pdfData['content'];
        exit;
    }
}