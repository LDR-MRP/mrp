<?php

class Com_requisicionModel extends Mysql
{
    use Auditable;

    public const ESTATUS_PENDIENTE = "PENDIENTE";

    public const ESTATUS_APROBADA = "APROBADA";

    public const ESTATUS_RECHAZADA = "RECHAZADA";

    public const ESTATUS_CANCELADA = "CANCELADA";

    public const ESTATUS_ELIMINADA = "ELIMINADA";

    public function __construct()
    {
        parent::__construct();
    }

    public function getTableName(): string 
    {
        return "com_requisiciones";
    }

    public function create(array $data, int $createdBy)
    {
        return $this->insert(
            "INSERT INTO com_requisiciones
            (usuarioid,
            departamentoid,
            prioridad,
            comentarios,
            monto_estimado)
            VALUES
            (?,?,?,?,?)",
            [
                $createdBy,
                $data['departamentoid'],
                $data['prioridad'] ?? 'media',
                $data['comentarios'] ?? '',
                $data['monto_estimado'],
            ]
        );
    }

    public function requisition(int $id)
    {
        return $this->select(
            "SELECT 
                idrequisicion,
                usuarioid,
                departamentoid,
                prioridad,
                estatus,
                comentarios,
                modified_by,
                modified_at,
                date(created_at) as fecha,
                CONCAT(usuarios.nombres,' ',usuarios.apellidos) as solicitante
            FROM com_requisiciones
            LEFT JOIN usuarios
            ON usuarios.idusuario = com_requisiciones.usuarioid
            LEFT JOIN cli_departamentos
            ON cli_departamentos.id = com_requisiciones.departamentoid
            WHERE idrequisicion = ?;
            ",
            [$id]
        );
    }

    public function requisitions(array $filters = [])
    {
        $query ="SELECT 
                -- data requisición
                idrequisicion,
                prioridad,
                estatus,
                comentarios,
                monto_estimado,
                modified_by,
                modified_at,
                date(created_at) as fecha,
                -- data usuarios
                CONCAT(usuarios.nombres,' ',usuarios.apellidos) as solicitante,
                -- data departamentos
                cli_departamentos.nombre as departamento,
                cli_departamentos.descripcion as departamento_descripcion
            FROM com_requisiciones
            LEFT JOIN usuarios
                ON usuarios.idusuario = com_requisiciones.usuarioid
            LEFT JOIN cli_departamentos
                ON cli_departamentos.id = com_requisiciones.departamentoid
            WHERE true
            ";

        if(array_key_exists('estatus', $filters)) {
            $query .= "AND com_requisiciones.estatus = '{$filters['estatus']}'";
        }

        return $this->select_all($query);
    }

    public function approve(int $requisitionId, string $status, int $userId): int
    {
        return $this->update("UPDATE com_requisiciones
            SET estatus = ?,
                modified_by = ?,
                modified_at = current_timestamp()
            WHERE idrequisicion = ?;
            ",
            [
                $status,
                $userId,
                $requisitionId,
            ]
        );
    }

    public function reject(int $requisitionId, string $status, int $userId): int
    {
        return $this->update("UPDATE com_requisiciones
            SET estatus = ?,
                modified_by = ?
            WHERE idrequisicion = ?;
            ",
            [
                $status,
                $userId,
                $requisitionId,
            ]
        );
    }

    public function cancel(int $requisitionId, string $status, int $userId)
    {
        return $this->update("UPDATE com_requisiciones
            SET estatus = ?,
                modified_by = ?
            WHERE idrequisicion = ?;
            ",
            [
                $status,
                $userId,
                $requisitionId,
            ]
        );
    }

    public function destroy(int $requisitionId, string $status, int $userId)
    {
        return $this->update("UPDATE com_requisiciones
            SET estatus = ?,
                modified_by = ?,
                deleted_at = current_timestamp()
            WHERE idrequisicion = ?;
            ",
            [
                $status,
                $userId,
                $requisitionId,
            ]
        );
    }
}