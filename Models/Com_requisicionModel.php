<?php

class Com_requisicionModel extends Mysql
{
    use Auditable;

    protected $table = 'com_requisiciones';

    protected $detailTable = 'com_requisiciones_detalle';

    public const ESTATUS_BORRADOR = "borrador";

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
        return $this->table;
    }

    public function getRequisition(int $id): array|bool
    {
        return $this->select(
            "SELECT
                idrequisicion,
                id_empresa,
                usuarioid,
                departamentoid,
                id_centro_costo,
                titulo,
                fecha_requerida,
                prioridad,
                estatus,
                monto_estimado,
                justificacion
            FROM {$this->table}
            WHERE idrequisicion = ?",
            [
                $id
            ]
        );
    }

    public function getAllRequisitions(): array|bool
    {
        return $this->select(
            "SELECT
                idrequisicion,
                id_empresa,
                usuarioid,
                departamentoid,
                id_centro_costo,
                titulo,
                fecha_requerida,
                prioridad,
                estatus,
                monto_estimado,
                justificacion
            FROM {$this->table}
            "
        );
    }

    /**
     * Obtiene todas las partidas (detalles) asociadas a una requisición.
     * Devuelve un array vacío si no tiene partidas.
     */
    public function getRequisitionItems(int $requisicionId): array
    {
        $query = "SELECT 
                    idrequisicionarticulo,
                    inventarioid,
                    cantidad,
                    precio_unitario_estimado,
                    notas,
                    (cantidad * precio_unitario_estimado) as subtotal,
                    i.cve_articulo,
                    i.descripcion,
                    i.unidad_salida
                  FROM com_requisiciones_detalle r
                  INNER JOIN wms_inventario i
                  ON i.idinventario = r.inventarioid
                  WHERE requisicionid = ?";
        $result = $this->select_all($query, [$requisicionId]);
        return $result ?: [];
    }

    public function createHeader(array $data): ?int
    {
        return $this->insert(
            "INSERT INTO {$this->table}
            (
                usuarioid
                ,estatus
                ,titulo
                ,departamentoid
                ,fecha_requerida
                ,monto_estimado
                ,prioridad
                ,justificacion
            )
            VALUES
            (
                :usuarioid
                ,:estatus
                ,:titulo
                ,:departamentoid
                ,:fecha_requerida
                ,:monto_estimado
                ,:prioridad
                ,:justificacion
            )",
            [
                ':usuarioid' => $data['user_id'],
                ':estatus' => !empty($data['estatus']) ? mb_strtolower($data['estatus'], 'UTF-8') : 'borrador',
                ':titulo' => $data['titulo'],
                ':departamentoid' => $data['departamentoid'],
                ':fecha_requerida' => $data['fecha_requerida'],
                ':monto_estimado' => $data['monto_estimado'] ?: 0.000000,
                ':prioridad' => !empty($data['prioridad']) ? mb_strtolower($data['prioridad'], 'UTF-8') : 'media',
                ':justificacion' => $data['justificacion'] ?? '',
            ]
        ) ?? 0;
    }

    public function createDetail(int $requisitionId, array $item): ?bool
    {
        return $this->insert(
            "INSERT INTO {$this->detailTable}
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

    public function getItemQty(int $idrequisicionarticulo): ?float {
        $query = "SELECT cantidad FROM {$this->detailTable} WHERE idrequisicionarticulo = ? LIMIT 1;";
        $result = $this->select($query, [$idrequisicionarticulo]);
        return $result ? (float) $result['cantidad'] : null;
    }

    public function deleteItemFromRequisition(int $idrequisicionarticulo): bool {
        $query = "DELETE FROM {$this->detailTable} WHERE idrequisicionarticulo = ?;";
        return $this->update($query, [$idrequisicionarticulo]);
    }

    public function reduceItemQty(int $idrequisicionarticulo, float $quantityToReduce): bool {
        $query = "UPDATE {$this->detailTable} SET cantidad = cantidad - ? WHERE idrequisicionarticulo = ?;";
        return $this->update($query, [$quantityToReduce, $idrequisicionarticulo]);
    }
    
    public function getItemDetails(int $requisicionId, int $idrequisicionarticulo): ?array {
        $query = "SELECT inventarioid, cantidad, precio_unitario_estimado, notas 
                  FROM {$this->detailTable} 
                  WHERE requisicionid = ? AND idrequisicionarticulo = ? LIMIT 1;";
        $result = $this->select($query, [$requisicionId, $idrequisicionarticulo]);
        return $result ?: null;
    }

    public function findItemByInventarioId(int $requisicionid, int $inventarioid): ?int {
        $query = "SELECT idrequisicionarticulo FROM {$this->detailTable} 
                  WHERE requisicionid = ? AND inventarioid = ? LIMIT 1;";
        $result = $this->select($query, [$requisicionid, $inventarioid]);
        return $result ? (int) $result['idrequisicionarticulo'] : null;
    }

    public function increaseItemQty(int $idrequisicionarticulo, float $quantityToAdd): bool {
        $query = "UPDATE {$this->detailTable} SET cantidad = cantidad + ? WHERE idrequisicionarticulo = ?;";
        return $this->update($query, [$quantityToAdd, $idrequisicionarticulo]);
    }

    /**
     * Recalcula y actualiza el monto estimado de la cabecera 
     * basado en la suma de sus partidas actuales.
     */
    public function updateEstimatedAmount(int $requisicionId): bool {
        $query = "UPDATE {$this->table} cr
                  SET cr.monto_estimado = IFNULL((
                      SELECT SUM(cantidad * precio_unitario_estimado)
                      FROM {$this->detailTable}
                      WHERE requisicionid = cr.idrequisicion
                  ), 0.000000)
                  WHERE cr.idrequisicion = ?;";
                  
        return $this->update($query, [$requisicionId]);
    }

    /**
     * @deprecated since api-driven, use createDetail() instead.
     */
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

    /**
     * @deprecated since api-driven, use createHeader() instead.
     */
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

    /**
     * @deprecated since api-driven, use getRequisition() instead.
     */
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

    /**
     * @deprecated since api-driven, use getAllRequisitions() instead.
     */
    public function requisitions(array $filters = [])
    {
        $query ="SELECT 
                -- data requisición
                idrequisicion,
                id_empresa,
                fecha_requerida,
                prioridad,
                estatus,
                justificacion,
                monto_estimado,
                modified_by,
                modified_at,
                date(created_at) as fecha,
                -- data usuarios
                CONCAT(u_creator.nombres,' ',u_creator.apellidos) as solicitante,
                CONCAT(u_modifier.nombres,' ',u_modifier.apellidos) as aprobador,
                -- data departamentos
                d.nombre as departamento,
                d.descripcion as departamento_descripcion
            FROM com_requisiciones AS r
            LEFT JOIN usuarios AS u_creator
                ON u_creator.idusuario = r.usuarioid
			LEFT JOIN usuarios AS u_modifier
                ON u_modifier.idusuario = r.modified_by
            LEFT JOIN cli_departamentos AS d
                ON d.id = r.departamentoid
            WHERE true
            ";

        if(array_key_exists('estatus', $filters) && !is_array($filters['estatus'])) {
            $query .= " AND r.estatus = '{$filters['estatus']}'";
        }

        if(array_key_exists('estatus', $filters) && is_array($filters['estatus'])) {
            $query .= " AND r.estatus IN ('".implode("','", $filters['estatus'])."')";
        }

        if(array_key_exists('usuarioid', $filters)) {
            $query .= " AND r.usuarioid = '{$filters['usuarioid']}'";
        }

        if(array_key_exists('id_requisicion', $filters)) {
            $query .= " AND r.idrequisicion = '{$filters['id_requisicion']}'";
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