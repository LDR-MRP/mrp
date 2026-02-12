<?php

class Com_requisicionCancelProcessor implements Com_requisicionProcessorInterface {
    public function execute($model, int $requisitionId, string $status, int $userId)
    {
        return $model->cancel($requisitionId, $status, $userId);
    }

    public function getLogAction() { return Com_requisicionModel::ESTATUS_CANCELADA; }
}