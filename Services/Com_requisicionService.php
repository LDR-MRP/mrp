<?php

class Com_requisicionService
{
    public $model;

    protected $stockModel;

    protected $requisitionDetailModel;

    protected $logAuditModel;

    protected $requisitionStoreRequest;

    protected $requisitionStatusRequest;

    public function __construct()
    {
        $this->stockModel = new Inv_inventarioModel;
        $this->requisitionDetailModel = new Com_requisicionDetalleModel;
        $this->logAuditModel = new LogAuditModel;
    }

    public function index(array $filters = [])
    {
        return ServiceResponse::success(
            $this->model->requisitions($filters),
            'Datos obtenidos correctamente.',
            200
        );
    }

    public function requisition(int $id)
    {
        return ServiceResponse::success(
            $this->model->requisition($id),
            'Datos obtenidos correctamente.',
            200
        );
    }

    public function detail(int $id)
    {
        $requisition = $this->model->requisition($id);
        $partidas = $this->requisitionDetailModel->details($id);
        $bitacora = $this->logAuditModel->list(['nombre_tabla' => $this->model->getTableName(), 'resource_id' => $id]);

        // cálculos
        $requisition['partidas'] = array_map(function ($item) {
            $item['total'] = $item['cantidad'] * $item['precio_unitario_estimado'];

            return $item;
        }, $partidas);

        $requisition['monto_total'] = array_sum(array_column($requisition['partidas'], 'total'));

        $requisition['bitacora'] = $bitacora;

        return ServiceResponse::success(
            $requisition,
            'Datos obtenidos correctamente.',
            200
        );
    }

    public function store(string $data): ServiceResponse
    {
        try {
            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El cuerpo de la petición no es un JSON válido.', 400);
            }

            $this->requisitionStoreRequest = new Com_requisicionStoreRequest($data);

            $this->requisitionStoreRequest->validate();

            $validated = $this->requisitionStoreRequest->all();

            $db = $this->model->getConexion();

            $db->beginTransaction();

            $requisitionId = $this->model->create($validated, $_SESSION['idUser']);

            if ($requisitionId <= 0) {
                throw new Exception('Error al registrar la cabecera de la requisición.', 500);
            }

            $detail = $validated['articulos'];

            foreach ($detail as $item) {
                $stockItem = current($this->stockModel->selectInventario($item['inventarioid']));

                $item['precio_unitario_estimado'] = $stockItem['ultimo_costo'];

                $this->requisitionDetailModel->detailCreate($requisitionId, $item);
            }

            $db->commit();

            return ServiceResponse::success(
                [
                    'requisicion_id' => $requisitionId,
                ],
                'Requisición creada',
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

    public function changeStatus(array $data, string $status)
    {
        $db = $this->model->getConexion();
        $db->beginTransaction();

        try {
            if(!($userId = $_SESSION['idUser'])) {
                throw new \Exception("No hay una sesión de usuario activa.");
            }

            $this->requisitionStatusRequest = new Com_requisicionStatusRequest($data);
            $this->requisitionStatusRequest->model = $this->model;
            $this->requisitionStatusRequest->validate();
            $validated = $this->requisitionStatusRequest->all();

            $processor = Com_requisicionFactory::make($status);
            $requisition = $processor->execute(
                $this->model,
                $requisitionId = $validated['idrequisicion'],
                $status,
                $userId
            );

            if (!$requisition) {
                throw new \Exception("La operación de {$processor->getLogAction()} falló en el modelo.");
            }

            $this->model->logAudit($requisitionId, $processor->getLogAction(), $validated['comentario'], $userId);

            $db->commit();

            return ServiceResponse::success(
                [
                    'requisicion_id' => $requisitionId,
                ],
                'Requisición procesada. Nuevo estado: '.strtoupper($this->model::ESTATUS_APROBADA),
                200
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
}
