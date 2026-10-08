<?php

declare(strict_types=1);

enum IncidenciaEstadoEnum: int
{
    case CANCELADA          = 0;
    case ABIERTA            = 1;
    case EN_INVESTIGACION   = 2;
    case DICTAMINADA        = 3;
    case CON_GASTOS         = 4;
    case RESUELTA_SIN_COSTO = 5;
    case CERRADA            = 6;

    public function getLabel(): string
    {
        return match($this) {
            self::CANCELADA          => 'Cancelada',
            self::ABIERTA            => 'Abierta',
            self::EN_INVESTIGACION   => 'En Investigación',
            self::DICTAMINADA        => 'Dictaminada',
            self::CON_GASTOS         => 'Con Gastos',
            self::RESUELTA_SIN_COSTO => 'Resuelta sin costo',
            self::CERRADA            => 'Cerrada',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::CANCELADA          => 'badge bg-soft-danger text-danger',
            self::ABIERTA            => 'badge bg-soft-warning text-warning',
            self::EN_INVESTIGACION   => 'badge bg-soft-info text-info',
            self::DICTAMINADA        => 'badge bg-soft-primary text-primary',
            self::CON_GASTOS         => 'badge bg-soft-secondary text-secondary',
            self::RESUELTA_SIN_COSTO => 'badge bg-soft-success text-success',
            self::CERRADA            => 'badge bg-soft-dark text-dark',
        };
    }

    public function permiteNuevosGastos(): bool
    {
        return match($this) {
            self::ABIERTA,
            self::EN_INVESTIGACION,
            self::DICTAMINADA,
            self::CON_GASTOS => true,
            default => false,
        };
    }
}
