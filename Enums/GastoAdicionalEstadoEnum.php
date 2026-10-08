<?php

declare(strict_types=1);

enum GastoAdicionalEstadoEnum: int
{
    case CANCELADO   = 0;
    case REGISTRADO  = 1;
    case EN_REVISION = 2;
    case APROBADO    = 3;
    case RECHAZADO   = 4;
    case DOCUMENTADO = 5;

    public function getLabel(): string
    {
        return match($this) {
            self::CANCELADO   => 'Cancelado',
            self::REGISTRADO  => 'Registrado',
            self::EN_REVISION => 'En Revisión',
            self::APROBADO    => 'Aprobado',
            self::RECHAZADO   => 'Rechazado',
            self::DOCUMENTADO => 'Documentado',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::CANCELADO   => 'badge bg-soft-danger text-danger',
            self::REGISTRADO  => 'badge bg-soft-secondary text-secondary',
            self::EN_REVISION => 'badge bg-soft-warning text-warning',
            self::APROBADO    => 'badge bg-soft-primary text-primary',
            self::RECHAZADO   => 'badge bg-soft-danger text-danger',
            self::DOCUMENTADO => 'badge bg-soft-success text-success',
        };
    }

    public function esEditable(): bool
    {
        return match($this) {
            self::REGISTRADO, self::RECHAZADO => true,
            default => false,
        };
    }

    public function afectaCostoReal(): bool
    {
        return match($this) {
            self::APROBADO, self::DOCUMENTADO => true,
            default => false,
        };
    }
}
