<?php

class Com_requisicionFactory {
    public static function make(string $status): Com_requisicionProcessorInterface {
        return match ($status) {
            Com_requisicionModel::ESTATUS_APROBADA => new Com_requisicionApproveProcessor(),
            Com_requisicionModel::ESTATUS_RECHAZADA => new Com_requisicionRejectProcessor(),
            Com_requisicionModel::ESTATUS_CANCELADA => new Com_requisicionCancelProcessor(),
            Com_requisicionModel::ESTATUS_ELIMINADA => new Com_requisicionDestroyProcessor(),
            default   => throw new \InvalidArgumentException("Acción no soportada.", 422)
        };
    }
}