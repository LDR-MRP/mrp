<?php

/**
 * Catálogo estricto de acciones permitidas para la tabla log_audit.
 */
enum AuditAction: string
{
    case CREATED = 'creacion';
    case UPDATED = 'actualizacion';
    case DELETED = 'eliminacion_logica';
    case RESTORED = 'restauracion';
    case CANCELED = 'cancelacion';
    case REJECTED = 'rechazo';
    case FINALIZED = 'finalización';
    
    // Flujos de Aprobación ERP (State Machine)
    case APPROVED = 'aprobacion';
    case APPROVE_L1 = 'aprobacion_l1';
    case APPROVE_L2 = 'aprobacion_l2';
    
    // Acciones específicas de DevSecOps
    case UPLOAD_FILE = 'carga_documento';
    case LOGIN_FAILED = 'intento_fallido_login';

    /**
     * Método de utilidad (opcional) para obtener una etiqueta legible para la UI.
     */
    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Creación de Registro',
            self::UPDATED => 'Actualización de Datos',
            self::APPROVE_L1 => 'Aprobación Nivel 1 (Compras)',
            self::APPROVE_L2 => 'Aprobación Nivel 2 (Finanzas)',
            self::REJECTED => 'Rechazado',
            default => 'Acción del Sistema'
        };
    }
}