<?php

class Com_requisicionModel extends Mysql
{
    use Auditable;

    public const ESTATUS_PENDIENTE = "pendiente";

    public const ESTATUS_APROBADA = "aprobada";

    public const ESTATUS_RECHAZADA = "rechazada";

    public const ESTATUS_CANCELADA = "cancelada";

    public const ESTATUS_ELIMINADA = "eliminada";

    public const ESTATUS_EN_COMPRA = "en compra";

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
            titulo,
            departamentoid,
            fecha_requerida,
            monto_estimado,
            prioridad,
            estatus,
            justificacion)
            VALUES
            (?,?,?,?,?,?,?,?)",
            [
                $createdBy,
                $data['titulo'],
                $data['departamentoid'],
                $data['fecha_requerida'],
                $data['monto_estimado'],
                mb_strtolower($data['prioridad'], 'UTF-8') ?? 'media',
                mb_strtolower($data['estatus'], 'UTF-8') ?? 'pendiente',
                $data['justificacion'] ?? '',
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
                justificacion,
                monto_estimado,
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
                justificacion,
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

        if(array_key_exists('estatus', $filters) && !is_array($filters['estatus'])) {
            $query .= "AND com_requisiciones.estatus = '{$filters['estatus']}'";
        }

        if(array_key_exists('estatus', $filters) && is_array($filters['estatus'])) {
            $query .= "AND com_requisiciones.estatus IN ('".implode("','", $filters['estatus'])."')";
        }

        if(array_key_exists('usuarioid', $filters)) {
            $query .= "AND com_requisiciones.usuarioid = '{$filters['usuarioid']}'";
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
                mb_strtolower($status, 'UTF-8'),
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
                mb_strtolower($status, 'UTF-8'),
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
                mb_strtolower($status, 'UTF-8'),
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
                mb_strtolower($status, 'UTF-8'),
                $userId,
                $requisitionId,
            ]
        );
    }

    public function changeStatus(int $requisitionId, string $status, int $userId)
    {
        return $this->update("UPDATE com_requisiciones
            SET estatus = ?,
                modified_by = ?
            WHERE idrequisicion = ?;
            ",
            [
                mb_strtolower($status, 'UTF-8'),
                $userId,
                $requisitionId,
            ]
        );
    }

    public function getKpi()
    {
        return $this->select_all(
            "SELECT 
                estatus,
                count(idrequisicion) as cantidad
            FROM com_requisiciones
            WHERE (estatus != 'finalizada')
            or (estatus = 'finalizada' AND MONTH(fecha_requerida) = MONTH(current_date) AND YEAR(fecha_requerida) = YEAR(current_date))
            GROUP BY estatus;
            "
        );
    }

    public function detailCreate(int $requisitionId, array $item)
    {
         return $this->insert(
            "INSERT INTO com_requisiciones_detalle
            (requisicionid,
            inventarioid,
            cantidad,
            precio_unitario_estimado,
            notas)
            VALUES
            (?,?,?,?,?)",
            [
                $requisitionId,
                $item['inventarioid'],
                $item['cantidad'],
                $item['precio_unitario_estimado'],
                $item['notas'] ?? '',
            ]
        );
    }

    public function details(?int $requisitionId = null)
    {
         return $this->select_all(
            "SELECT * FROM com_requisiciones_detalle
            LEFT JOIN wms_inventario
            ON wms_inventario.idinventario = com_requisiciones_detalle.inventarioid
            WHERE requisicionid = ?;",
            [
                $requisitionId,
            ]
        );
    }
}