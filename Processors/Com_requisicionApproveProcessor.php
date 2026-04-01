<?php

class Com_requisicionApproveProcessor implements Com_requisicionProcessorInterface {
    public function execute($model, int $requisitionId, string $status, int $userId)
    {
        return $model->approve($requisitionId, $status, $userId);
    }

    public function getLogAction() { return AuditAction::APPROVE_L1; }
}