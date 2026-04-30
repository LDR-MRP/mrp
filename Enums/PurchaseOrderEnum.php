<?php

enum PurchaseOrderEnum: string
{
    case EMITIDA          = 'emitida';
    case EN_TRANSITO      = 'en_transito';
    case RECIBIDA_PARCIAL = 'recibida_parcial';
    case CERRADA          = 'cerrada'; // Surtida al 100%
    case CANCELADA        = 'cancelada';

    /**
     * Ejemplo de utilidad: Define si la OC puede ser modificada o no.
     */
    public function canBeEdited(): bool
    {
        return $this === self::EMITIDA;
    }
}