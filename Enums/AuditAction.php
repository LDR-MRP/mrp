<?php

/**
 * Catálogo estricto de acciones permitidas para la tabla log_audit.
 */
enum AuditAction: string
{
    // --- BÁSICOS ---
    case CREATED = 'creacion';
    case UPDATED = 'actualizacion';
    case DELETED = 'eliminacion_logica';
    case RESTORED = 'restauracion';
    
    // --- ESTADOS ERP ---
    case CANCELED = 'cancelacion';
    case REJECTED = 'rechazo';
    case FINALIZED = 'finalizacion';
    case SHIPPED = 'en_transito';
    case RECEIVED = 'recepcion_material'; // NUEVO: Para WMS
    
    // --- FLUJOS DE APROBACIÓN ---
    case APPROVED = 'aprobacion';
    case APPROVE_L1 = 'aprobacion_l1';
    case APPROVE_L2 = 'aprobacion_l2';
    
    // --- ACCIONES ESPECIALES ---
    case ITEMS_MOVED = 'partidas_movidas'; // NUEVO: Para el Split/Merge de Reqs
    case PDF_EXPORTED = 'exportacion_pdf'; // NUEVO: Para auditoría de descargas
    
    // --- SEGURIDAD (DEVSECOPS) ---
    case UPLOAD_FILE = 'carga_documento';
    case LOGIN_SUCCESS = 'login_exitoso';   // NUEVO
    case LOGIN_FAILED = 'intento_fallido_login';
    case LOGOUT = 'cierre_sesion';          // NUEVO
    case PASSWORD_CHANGED = 'cambio_password'; // NUEVO

    public function label(): string
    {
        return match($this) {
            self::CREATED => 'Creación de Registro',
            self::UPDATED => 'Actualización de Datos',
            self::APPROVE_L1 => 'Aprobación Nivel 1 (Compras)',
            self::APPROVE_L2 => 'Aprobación Nivel 2 (Finanzas)',
            self::REJECTED => 'Rechazado',
            self::ITEMS_MOVED => 'Movimiento de Partidas entre Folios',
            self::LOGIN_SUCCESS => 'Acceso Exitoso al Sistema',
            self::LOGIN_FAILED => 'Intento de Acceso Fallido',
            default => 'Acción del Sistema'
        };
    }
}

