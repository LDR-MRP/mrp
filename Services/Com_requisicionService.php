<?php

class Com_requisicionService
{
    public $model;

    protected $stockModel;

    protected $requisitionDetailModel;

    protected $requisitionRequest;

    protected $requisitionApproveRequest;

    public function __construct()
    {
        $this->stockModel = new Inv_inventarioModel;
        $this->requisitionDetailModel = new Com_requisicionDetalleModel;
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

        // cálculos
        $requisition['partidas'] = array_map(function ($item) {
            $item['total'] = $item['cantidad'] * $item['precio_unitario_estimado'];

            return $item;
        }, $partidas);

        $requisition['monto_total'] = array_sum(array_column($requisition['partidas'], 'total'));

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

            $this->requisitionRequest = new Com_requisicionRequest($data);

            $this->requisitionRequest->model = $this->model;

            $this->requisitionRequest->validate();

            $validated = $this->requisitionRequest->all();

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

    public function approve(array $data)
    {
        try {
            $this->requisitionApproveRequest = new Com_requisicionAprobarRequest($data);

            $this->requisitionApproveRequest->model = $this->model;

            $this->requisitionApproveRequest->validate();

            $validated = $this->requisitionApproveRequest->all();

            $db = $this->model->getConexion();

            $db->beginTransaction();

            $this->model->approve($requisitionId = (int) $validated['idrequisicion'], $this->model::ESTATUS_APROBADA, $_SESSION['idUser']);

            $db->commit();

            return ServiceResponse::success(
                [
                    'requisicion_id' => $requisitionId,
                ],
                'Requisición procesada. Nuevo estado: '.strtoupper($this->model::ESTATUS_APROBADA),
                200
            );
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }

            return ServiceResponse::error(
                code: ! is_int($e->getCode()) ? 500 : $e->getCode(),
                data: $e->getMessage()
            );
        }
    }

    public function reject(array $data)
    {
        $this->model->reject($requisitionId = (int) $_POST['idrequisicion'], $this->model::ESTATUS_RECHAZADA, $_SESSION['idUser']);

        return ServiceResponse::success(
            [
                'requisicion_id' => $requisitionId,
            ],
            'Requisición procesada. Nuevo estado: '.strtoupper($this->model::ESTATUS_RECHAZADA),
            200
        );
    }

    public function cancel(array $data)
    {
        $this->model->cancel($requisitionId = (int) $_POST['idrequisicion'], $this->model::ESTATUS_CANCELADA, $_SESSION['idUser']);

        return ServiceResponse::success(
            [
                'requisicion_id' => $requisitionId,
            ],
            'Requisición procesada. Nuevo estado: '.strtoupper($this->model::ESTATUS_CANCELADA),
            200
        );
    }

    public function destroy(array $data)
    {
        $requisition = $this->model->destroy($requisitionId = (int) $_POST['idrequisicion'], $this->model::ESTATUS_ELIMINADA, $_SESSION['idUser']);

        return ServiceResponse::success(
            [
                'requisicion_id' => $requisitionId,
            ],
            'Requisición procesada. Nuevo estado: '.strtoupper($this->model::ESTATUS_ELIMINADA),
            200
        );
    }
}
