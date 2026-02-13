<?php

class Com_compraService{

    public $model;

    protected $compraStoreRequest;

    protected $requisitionModel;

    protected $requisitionDetailModel;

    protected $currencyModel;

    protected $requisitionService;

    public function __construct()
    {
        $this->requisitionModel = new Com_requisicionModel;
        $this->currencyModel = new Inv_monedaModel;
    }

    public function index(): array
    {
        return $this->model->selectCompras();
    }

    public function store(string $data): ServiceResponse
    {
        $db = $this->model->getConexion();
        $db->beginTransaction();

        try {
            if(!($userId = $_SESSION['idUser'])) {
                throw new \Exception("No hay una sesión de usuario activa.");
            }

            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El cuerpo de la petición no es un JSON válido.', 400);
            }

            // request
            $this->compraStoreRequest = new Com_compraStoreRequest($data);
            $this->compraStoreRequest->requisitionModel = $this->requisitionModel;
            $this->compraStoreRequest->currencyModel = $this->currencyModel;
            $this->compraStoreRequest->validate();
            $validated = $this->compraStoreRequest->all();

            // models
            $requisition = $this->requisitionModel->requisition($validated['requisicionid']);
            $currency = current($this->currencyModel->all(['idmoneda' => $validated['monedaid']]));

            // prepare data
            $code = "OC-" . date('Y') . "-" . str_pad($reqId = $requisition['idrequisicion'], 4, '0', STR_PAD_LEFT);
            $requisition['iva'] = $iva = $validated['iva'] ? .16 : 0;
            $requisition['total'] = $iva ? $requisition['monto_estimado'] + ($requisition['monto_estimado'] * $iva) : $requisition['monto_estimado'];

            // data insert
            $POId = $this->model->create(
                array_merge($validated, $requisition, $currency),
                $userId
            );

            $this->requisitionModel->changeStatus($reqId, strtolower(Com_requisicionModel::ESTATUS_EN_COMPRA), $userId);
       
            $this->requisitionModel->logAudit($reqId, Com_requisicionModel::ESTATUS_EN_COMPRA, 'La PO se ha generado exitosamente', $userId);

            $db->commit();

            // response
            return ServiceResponse::success(
                [
                    'compraid' => $POId,
                    'requisicionid' => $POId,
                    'codigo' => $code,
                ],
                'Órden de compra generada existosamente.',
                201
            );

        } catch (InvalidArgumentException $e) {
            return ServiceResponse::validation($e->getMessage());
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }

            return ServiceResponse::error(
                message: $e->getMessage(),
                code: is_int($e->getCode()) ? $e->getCode() : 500
            );
        }
    }

    public function suppliers()
    {
        return ServiceResponse::success($this->model->suppliers());
    }
}