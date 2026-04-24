<?php

declare(strict_types=1);

enum RoleEnum: int
{
    case ADMINISTRADOR     = 18;
    case GERENTE_COMPRAS   = 17;
    case COMPRADOR         = 16;
    case JEFE_DEPARTAMENTO = 14;
    case SOLICITANTE       = 15;

    /**
     * Define el alcance de visualización (Ojo)
     * 'propio' | 'depto' | 'total'
     */
    public function getScope(): string
    {
        return match($this) {
            self::SOLICITANTE       => 'propio',
            self::JEFE_DEPARTAMENTO => 'planta', // 'depto' es el deber ser pero el diseño actual de la DB no lo permite
            default                 => 'total', // Admin, Comprador, Gerente
        };
    }

    /**
     * Determina el nivel de firma para el flujo de aprobación
     */
    public function getApprovalLevel(): ?string
    {
        return match($this) {
            self::JEFE_DEPARTAMENTO => 'L1', // Firma departamental
            self::GERENTE_COMPRAS, 
            self::ADMINISTRADOR     => 'L2', // Firma global/finanzas
            default                 => null, // Solicitantes y Compradores no firman
        };
    }

    /**
     * Define si el rol puede editar/eliminar (Lápiz/Basura)
     */
    public function canMutate(bool $isOwner, bool $isSameDept): bool
    {
        return match($this) {
            self::ADMINISTRADOR, 
            self::GERENTE_COMPRAS   => true,
            self::JEFE_DEPARTAMENTO => $isSameDept,
            self::SOLICITANTE       => $isOwner,
            self::COMPRADOR         => false,
        };
    }
}