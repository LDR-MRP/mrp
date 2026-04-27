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
                centro_costo,
                titulo,
                fecha_requerida,
                prioridad,
                estatus,
                monto_estimado,
                justificacion,
                created_at AS fecha
            FROM {$this->table}
            WHERE idrequisicion = ?",
            [
                $id
            ]
        );
    }

    public function getRequisitionForUpdate(int $id): array|bool
    {
        return $this->select(
            "SELECT
                idrequisicion,
                id_empresa,
                usuarioid,
                departamentoid,
                centro_costo,
                titulo,
                fecha_requerida,
                prioridad,
                estatus,
                monto_estimado,
                justificacion,
                created_at AS fecha
            FROM {$this->table}
            WHERE idrequisicion = ?
            FOR UPDATE",
            [
                $id
            ]
        );
    }

    public function getAllRequisitions(): array|bool
    {
        return $this->select_all(
            "SELECT
                idrequisicion,
                id_empresa,
                usuarioid,
                departamentoid,
                centro_costo,
                titulo,
                fecha_requerida,
                prioridad,
                estatus,
                monto_estimado,
                justificacion,
                created_at AS fecha,
                -- data usuarios
                CONCAT(u_creator.nombres,' ',u_creator.apellidos) as solicitante,
                CONCAT(u_modifier.nombres,' ',u_modifier.apellidos) as aprobador,
                -- data departamentos
                d.nombre AS departamento,
                d.descripcion AS departamento_descripcion
            FROM {$this->table} AS r
            LEFT JOIN usuarios AS u_creator
                ON u_creator.idusuario = r.usuarioid
			LEFT JOIN usuarios AS u_modifier
                ON u_modifier.idusuario = r.modified_by
            LEFT JOIN cli_departamentos AS d
                ON d.id = r.departamentoid
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
                -- ,plantaid
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
                -- ,1
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
                //':plantaid' => $data['´planta_id'],
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
    
    /**
     * Obtiene los detalles de una partida bloqueando la fila para la transacción actual.
     * Previne Race Conditions durante el proceso de movimiento de ítems.
     */
    public function getItemDetailsForUpdate(int $requisicionId, int $idrequisicionarticulo): ?array {
        $query = "SELECT inventarioid, cantidad, precio_unitario_estimado, notas 
                  FROM {$this->detailTable} 
                  WHERE requisicionid = ? AND idrequisicionarticulo = ? LIMIT 1 FOR UPDATE;";
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
     * Actualiza los datos principales de la cabecera.
     */
    public function updateHeader(int $requisicionId, array $data): bool {
        $query = "UPDATE com_requisiciones SET 
                    estatus = ?, titulo = ?, departamentoid = ?, 
                    fecha_requerida = ?, prioridad = ?, justificacion = ?
                  WHERE idrequisicion = ?";
        
        $params = [
            $data['estatus'],
            $data['titulo'],
            $data['departamentoid'],
            $data['fecha_requerida'],
            $data['prioridad'],
            $data['justificacion'],
            $requisicionId
        ];
        
        return $this->update($query, $params);
    }

    /**
     * Actualiza una partida existente.
     */
    public function updateDetail(int $idrequisicionarticulo, array $itemData): bool {
        $query = "UPDATE com_requisiciones_detalle SET 
                    inventarioid = ?, cantidad = ?, precio_unitario_estimado = ?, notas = ?
                  WHERE idrequisicionarticulo = ?";
                  
        $params = [
            $itemData['inventarioid'],
            $itemData['cantidad'],
            $itemData['precio_unitario_estimado'],
            $itemData['notas'],
            $idrequisicionarticulo
        ];
        
        return $this->update($query, $params);
    }

    /**
     * Elimina todas las partidas de una requisición.
     */
    public function deleteAllItems(int $requisicionId): bool {
        $query = "DELETE FROM com_requisiciones_detalle WHERE requisicionid = ?";
        return $this->update($query, [$requisicionId]);
    }

     /**
     * Elimina las partidas de una requisición que NO estén en la lista de IDs proveída.
     * Útil para sincronizar la BD con lo que el frontend manda.
     */
    public function deleteMissingItems(int $requisicionId, array $keepItemIds): bool {
        // Creamos los placeholders (?, ?, ?) dinámicamente según la cantidad de IDs
        $placeholders = implode(',', array_fill(0, count($keepItemIds), '?'));
        
        $query = "DELETE FROM com_requisiciones_detalle 
                  WHERE requisicionid = ? AND idrequisicionarticulo NOT IN ($placeholders)";
        
        // Unimos el ID de la requisición con el array de IDs a mantener
        $params = array_merge([$requisicionId], $keepItemIds);
        
        return $this->update($query, $params);
    }
    
    /**
     * Calcula y retorna las partidas de una requisición que aún no han sido compradas en su totalidad.
     *
     * @param int $requisicionId
     * @return array Lista de partidas con su saldo pendiente.
     */
    public function calculatePendingItems(int $requisicionId): array
    {
        // La consulta maestra de saldos
        $query = "
            SELECT 
                rd.idrequisicionarticulo,
                rd.inventarioid,
                rd.notas,
                -- Datos originales solicitados
                rd.cantidad AS cantidad_solicitada,
                rd.precio_unitario_estimado,
                
                -- Sumamos lo que ya se compró en todas las OCs (si no hay OCs, es 0)
                IFNULL(oc_comprado.total_comprado, 0) AS cantidad_ya_comprada,
                
                -- La resta matemática: Lo que falta por comprar
                (rd.cantidad - IFNULL(oc_comprado.total_comprado, 0)) AS cantidad_pendiente
                
            FROM com_requisiciones_detalle rd
            
            -- Subconsulta: Agrupamos todas las OCs previas por partida de requisición
            LEFT JOIN (
                SELECT 
                    ocd.idrequisicionarticulo, 
                    SUM(ocd.cantidad) AS total_comprado
                FROM com_ordenes_compra_detalle ocd
                INNER JOIN com_ordenes_compra oc ON ocd.compraid = oc.idcompra
                -- Opcional: Solo sumar OCs que no estén canceladas
                WHERE oc.estatus != 'cancelada'
                GROUP BY ocd.idrequisicionarticulo
            ) AS oc_comprado ON rd.idrequisicionarticulo = oc_comprado.idrequisicionarticulo
            
            WHERE rd.requisicionid = ?
            
            -- EL FILTRO CRÍTICO: Solo devolver partidas donde aún falte comprar algo
            HAVING cantidad_pendiente > 0;
        ";

        $result = $this->select_all($query, [$requisicionId]);
        
        return $result ?: [];
    }

    /**
     * 
     */
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

    /**
     * Actualiza el estado de una requisición y registra quién lo modificó.
     */
    public function updateStatus(int $requisicionId, string $newStatus, int $modifiedByUserId): bool {
        $query = "UPDATE com_requisiciones 
                  SET estatus = ?, modified_by = ?, modified_at = NOW() 
                  WHERE idrequisicion = ?";
        
        return $this->update($query, [$newStatus, $modifiedByUserId, $requisicionId]);
    }

    /**
     * Realiza un borrado lógico (Soft Delete).
     */
    public function softDelete(int $requisicionId): bool {
        // En lugar de DELETE, cambiamos el estatus y ponemos fecha de borrado
        $query = "UPDATE com_requisiciones 
                  SET estatus = 'eliminada', deleted_at = NOW() 
                  WHERE idrequisicion = ?";
        return $this->update($query, [$requisicionId]);
    }

    /**
     * Obtiene la data completa de una requisición para impresión.
     * Resuelve nombres de usuarios, departamentos y descripción de artículos.
     */
    public function getRequisitionForPrint(int $id): ?array
    {
        $sql = "SELECT 
                    r.idrequisicion,
                    r.titulo,
                    r.fecha_requerida,
                    r.estatus,
                    r.prioridad,
                    r.justificacion,
                    r.monto_estimado,
                    r.created_at AS fecha,
                    r.plantaid,
                    r.usuarioid,
                    r.departamentoid,
                    r.centro_costo,
                    CONCAT(u.nombres, ' ', u.apellidos) AS solicitante_nombre,
                    d.nombre AS departamento_nombre
                FROM com_requisiciones r
                INNER JOIN usuarios u ON r.usuarioid = u.idusuario
                INNER JOIN cli_departamentos d ON r.departamentoid = d.id
                WHERE r.idrequisicion = ? AND r.deleted_at IS NULL";

        $requisition = $this->select($sql, [$id]);
        
        if (!$requisition) return null;

        // Consultar partidas con detalles de inventario
        $sqlItems = "SELECT 
                        d.cantidad,
                        d.precio_unitario_estimado,
                        d.notas,
                        i.cve_articulo,
                        i.descripcion
                     FROM com_requisiciones_detalle d
                     INNER JOIN wms_inventario i ON d.inventarioid = i.idinventario
                     WHERE d.requisicionid = ? AND d.deleted_at IS NULL";
        
        $requisition['items'] = $this->select_all($sqlItems, [$id]);

        return $requisition;
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
}