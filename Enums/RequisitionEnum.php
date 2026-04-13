<?php

enum RequisitionEnum: string
{
    case BORRADOR = 'borrador';
    case PENDIENTE = 'pendiente';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
    case EN_COMPRA = 'en_compra';
    case CANCELADA = 'cancelada';
    case FINALIZADA = 'finalizada';
    case ELIMINADA = 'eliminada';
}