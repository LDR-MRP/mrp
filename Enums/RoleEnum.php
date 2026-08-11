<?php

declare(strict_types=1);

enum RoleEnum: int
{
    case ADMINISTRADOR         = COMPRAS_ADMINISTRADOR;
    case GERENTE               = COMPRAS_GERENTE;
    case COMPRADOR             = COMPRAS_COMPRADOR;
    case JEFE_DEPARTAMENTO     = COMPRAS_JEFE_DEPARTAMENTO;
    case SOLICITANTE           = COMPRAS_SOLICITANTE;
    case SYS_ADMIN             = RADMINISTRADOR;
    case DIRECTOR              = COMPRAS_DIRECTOR;
    case DIRECTOR_CORPORATIVO  = COMPRAS_DIRECTOR_CORPORATIVO;
    case CONTADOR              = COMPRAS_CONTADOR;
    case TESORERO              = COMPRAS_TESORERO;

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
            self::GERENTE, 
            self::SYS_ADMIN,
            self::DIRECTOR,
            self::DIRECTOR_CORPORATIVO,
            self::CONTADOR          => 'L2',
            self::TESORERO          => 'L2',
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
            self::SYS_ADMIN, 
            self::ADMINISTRADOR, 
            self::GERENTE   => true,
            self::JEFE_DEPARTAMENTO => $isSameDept,
            self::SOLICITANTE       => $isOwner,
            self::COMPRADOR         => false,
        };
    }

    /**
     * Traduce el scope del rol en filtros reales para el modelo.
     */
    public function getSQLFilters(array $userContext): array|bool
    {
        return match($this->getScope()) {
            'propio' => ['usuarioid' => (int)$userContext['id']],
            'planta' => ['plantaid' => (int)$userContext['plantaid']],
            'total'  => [], // Sin filtros, ve todo
            default  => false // Acceso denegado
        };
    }

    /**
     * Valida si el usuario tiene permiso de ver un registro específico (IDOR Protection)
     */
    public function canView(array $userContext, array $record): bool
    {
        return match($this->getScope()) {
            'propio' => (int)$record['usuarioid'] === (int)$userContext['id'],
            'planta' => (int)$record['plantaid'] === (int)$userContext['plantaid'],
            'total'  => true,
            default  => false
        };
    }

    /**
     * Determina el flujo de estados basado en el nivel de firma.
     * Útil para estandarizar 'approve' en cualquier Service.
     */
    public function getTransitionConfig(string $entityType): array
    {
        $level = $this->getApprovalLevel();
        
        return match($entityType) {
            'requisition' => match($level) {
                'L1' => ['from' => ['pendiente'], 'to' => 'pendiente_l2'],
                'L2' => ['from' => ['pendiente', 'pendiente_l2'], 'to' => 'aprobada'],
                default => []
            },
            'bank_account' => match($level) {
                'L1' => ['from' => ['revision_pendiente'], 'to' => 'validada_tesoreria'],
                'L2' => ['from' => ['validada_tesoreria'], 'to' => 'activa'],
                default => []
            },
            default => []
        };
    }
}