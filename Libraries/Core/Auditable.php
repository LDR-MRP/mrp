<?php

/**
 * Trait para estandarizar el registro de auditoría en los modelos.
 * Permite que cualquier modelo registre acciones de forma nativa.
 */
trait Auditable
{
    /**
     * Registra una acción en la tabla de bitácora.
     * * @param int $resourceId ID del registro afectado.
     * @param AuditAction $action Nombre de la acción (Ej: APROBACIÓN).
     * @param string $comment Comentario explicativo.
     * @param int $userId Sesión de usuario activa.
     * @return bool
     */
    public function logAudit(
        int $resourceId,
        AuditAction $action,
        ?string $comment = 'Sin comentarios',
        ?int $userId = null): bool
    {
        $auditModel = new LogAuditModel();
        
        return $auditModel->register(
            [
                'resource_id' => $resourceId,
                'user_id'     => $userId ?? 0,
                'table_name'  => mb_strtolower($this->getTableName(), 'UTF-8'),
                'action'      => $action->value, 
                'comment'     => htmlspecialchars(trim($comment ?? ''), ENT_QUOTES, 'UTF-8'),
            ]
        );
    }

    /**
     * Firma abstracta: Obliga a la clase que use este Trait a tener este método.
     */
    abstract public function getTableName(): string;
}