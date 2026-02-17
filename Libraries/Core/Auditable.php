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
     * @param string $action Nombre de la acción (Ej: APROBACIÓN).
     * @param string $comment Comentario explicativo.
     * @param int $userId Sesión de usuario activa.
     * @return bool
     */
    public function logAudit(int $resourceId, string $action, ?string $comment = 'Sin comentarios', int $userId): bool
    {
        $auditModel = new LogAuditModel();
        return $auditModel->register(
            [
                'resource_id' => $resourceId,
                'user_id' => $userId ?? 0,
                'table_name' => mb_strtolower($this->getTableName(), 'UTF-8'),
                'action' => mb_strtolower($action, 'UTF-8'),
                'comment' => mb_strtolower($comment, 'UTF-8'),
            ]
        );
    }
}