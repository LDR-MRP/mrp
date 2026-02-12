<?php

class Com_requisicionDestroyProcessor implements Com_requisicionProcessorInterface {
    public function execute($model, int $requisitionId, string $status, int $userId)
    {
        return $model->destroy($requisitionId, $status, $userId);
    }

    public function getLogAction() { return Com_requisicionModel::ESTATUS_ELIMINADA; }
}