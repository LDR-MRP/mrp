<?php

class Com_requisicionModel extends Mysql
{
    use Auditable;

    protected string $table = 'com_requisiciones';

    protected string $detailTable = 'com_requisiciones_detalle';

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
                created_at AS fecha,
                -- data usuarios
                CONCAT(u.nombres,' ',u.apellidos) as solicitante,
                -- data departamentos
                d.nombre AS departamento,
                d.descripcion AS departamento_descripcion
            FROM {$this->table} r
            LEFT JOIN cli_departamentos d
            ON d.id = r.departamentoid
            LEFT JOIN usuarios u
            ON u.idusuario = r.usuarioid
            WHERE r.idrequisicion = ?",
            [
                $id
            ]
        );
    }

    public function getAllRequisitions(array $filters = []): array|bool
    {
        $where = " WHERE TRUE ";
        $params = [];

        // Filtro por Usuario
        if (!empty($filters['usuarioid'])) {
            $where .= " AND r.usuarioid = ? ";
            $params[] = $filters['usuarioid'];
        }

        // Filtro por Planta
        if (array_key_exists('plantaid', $filters)) {
            $where .= " AND r.plantaid = ? ";
            $params[] = $filters['plantaid'];
        }

        // Filtro por Estatus
        if (!empty($filters['estatus'])) {
            $where .= " AND r.estatus = ? ";
            $params[] = $filters['estatus'];
        }

        // Filtro por Rango de Fechas
        if (!empty($filters['fecha_desde']) && !empty($filters['fecha_hasta'])) {
            $where .= " AND r.created_at BETWEEN ? AND ? ";
            $params[] = $filters['fecha_desde'] . " 00:00:00";
            $params[] = $filters['fecha_hasta'] . " 23:59:59";
        }

        $query = "SELECT
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
            $where
            ORDER BY r.idrequisicion DESC
            ";

        return $this->select_all($query, $params);
    }

    /**
     * Obtiene todas las partidas de la requisición, incluyendo artículos de catálogo
     * y artículos especiales de sourcing.
     */
    public function getRequisitionItems(int $requisicionId): array
    {
        $query = "
            SELECT 
                rd.idrequisicionarticulo,
                rd.requisicionid,
                rd.inventarioid,
                rd.cantidad,
                rd.precio_unitario_estimado,
                rd.notas,
                -- Si inventarioid es NULL, traemos los datos de la tabla de sourcing
                IFNULL(i.cve_articulo, 'SOURCING') AS cve_articulo,
                IFNULL(i.descripcion, n.categoria) AS descripcion,
                IFNULL(i.unidad_salida, 'PZA') AS unidad_salida,
                -- Flags para el Frontend
                (CASE WHEN rd.inventarioid IS NULL THEN 1 ELSE 0 END) AS es_sourcing,
                n.precio_objetivo,
                n.fecha_limite_acuerdo
                
            FROM com_requisiciones_detalle rd
            
            -- LEFT JOIN 1: Catálogo Maestro (Para artículos que SÍ existen)
            LEFT JOIN wms_inventario i 
                ON rd.inventarioid = i.idinventario
                
            -- LEFT JOIN 2: Especificaciones Especiales (Para artículos nuevos)
            LEFT JOIN com_requisicion_items_nuevos n 
                ON rd.idrequisicionarticulo = n.idrequisicionarticulo
                
            WHERE rd.requisicionid = ? 
            AND rd.deleted_at IS NULL
        ";

        return $this->select_all($query, [$requisicionId]) ?: [];
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

    public function createDetail(int $requisitionId, array $item): ?int
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
        ) ?? 0;
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
     * Retorna todas las partidas de la requisición con sus saldos calculados.
     * Ideal para el renderizado de la UI (Dashboard de Requisición).
     */
    public function getRequisitionBalances(int $requisicionId): array
    {
        $query = "
            SELECT 
                rd.idrequisicionarticulo,
                rd.inventarioid,
                rd.notas,
                rd.cantidad AS cantidad_solicitada,
                rd.precio_unitario_estimado,
                -- Detalles adicionales para no tener que hacer más joins en el service
                i.cve_articulo,
                i.descripcion,
                i.unidad_salida,
                
                -- Cálculo de lo comprado (Excluyendo canceladas)
                IFNULL(oc_comprado.total_comprado, 0) AS cantidad_ya_comprada,
                
                -- Saldo pendiente
                (rd.cantidad - IFNULL(oc_comprado.total_comprado, 0)) AS cantidad_pendiente
                
            FROM com_requisiciones_detalle rd
            INNER JOIN wms_inventario i ON rd.inventarioid = i.idinventario
            LEFT JOIN (
                SELECT 
                    ocd.idrequisicionarticulo, 
                    SUM(ocd.cantidad) AS total_comprado
                FROM com_ordenes_compra_detalle ocd
                INNER JOIN com_ordenes_compra oc ON ocd.compraid = oc.idcompra
                WHERE oc.estatus != 'cancelada'
                GROUP BY ocd.idrequisicionarticulo
            ) AS oc_comprado ON rd.idrequisicionarticulo = oc_comprado.idrequisicionarticulo
            
            WHERE rd.requisicionid = ?
            AND rd.deleted_at IS NULL;
            -- Quitamos el HAVING para que en la UI veamos también lo que ya se completó.
        ";

        return $this->select_all($query, [$requisicionId]) ?: [];
    }

    /**
     * 
     */
    public function getKpi(array $filters)
    {
        $where = " WHERE (
            (estatus != 'finalizada')
            OR
            (estatus = 'finalizada' AND MONTH(fecha_requerida) = MONTH(current_date) AND YEAR(fecha_requerida) = YEAR(current_date))
        )    
        ";
        $params = [];

        // Filtro por Usuario
        if (!empty($filters['usuarioid'])) {
            $where .= " AND usuarioid = ? ";
            $params[] = $filters['usuarioid'];
        }

        // Filtro por Planta
        if (array_key_exists('plantaid', $filters)) {
            $where .= " AND plantaid = ? ";
            $params[] = $filters['plantaid'];
        }

        $query = "SELECT 
                estatus,
                count(idrequisicion) as cantidad
            FROM com_requisiciones
            $where
            GROUP BY estatus;
            ";

        return $this->select_all($query, $params);
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
     * Inserta o actualiza las especificaciones de un artículo nuevo.
     */
    public function upsertItemSpecs(array $data): bool
    {
        $sql = "INSERT INTO com_requisicion_items_nuevos (
                    idrequisicionarticulo, justificacion_proyecto, categoria, descripcion_sourcing, especificaciones_tecnicas, 
                    dimensiones_principales, normas_requeridas, volumen_anual, 
                    precio_objetivo, fecha_inicio_negociacion, fecha_limite_acuerdo
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    justificacion_proyecto = VALUES(justificacion_proyecto),
                    categoria = VALUES(categoria),
                    descripcion_sourcing = VALUES(descripcion_sourcing),
                    especificaciones_tecnicas = VALUES(especificaciones_tecnicas),
                    dimensiones_principales = VALUES(dimensiones_principales),
                    normas_requeridas = VALUES(normas_requeridas),
                    volumen_anual = VALUES(volumen_anual),
                    precio_objetivo = VALUES(precio_objetivo),
                    fecha_inicio_negociacion = VALUES(fecha_inicio_negociacion),
                    fecha_limite_acuerdo = VALUES(fecha_limite_acuerdo)";

        // MAPEO QUIRÚRGICO: Solo mandamos los 9 que el SQL espera y en el orden correcto
        $params = [
            (int)$data['idrequisicionarticulo'], // Forzamos a entero
            $data['justificacion_proyecto'], // <--- Nuevo campo
            $data['categoria'],
            $data['descripcion_sourcing'],
            $data['especificaciones_tecnicas'],
            $data['dimensiones_principales'],
            $data['normas_requeridas'],
            $data['volumen_anual'],
            (float)$data['precio_objetivo'],
            $data['fecha_inicio_negociacion'],
            $data['fecha_limite_acuerdo']
        ];

        return $this->insert($sql, $params) >= 1;
    }

    /**
     * Recupera la ficha técnica vinculada a una partida.
     */
    public function getItemSpecs(int $idReqArticulo): ?array
    {
        $sql = "SELECT * FROM com_requisicion_items_nuevos WHERE idrequisicionarticulo = ?";
        return $this->select($sql, [$idReqArticulo]) ?: null;
    }

    /**
     * Actualiza el precio de una partida tras la negociación de sourcing.
     */
    public function updatePartidaPrice(int $idReqArt, float $nuevoPrecio): bool
    {
        $sql = "UPDATE com_requisiciones_detalle 
                SET precio_unitario_estimado = ?, 
                    updated_at = NOW() 
                WHERE idrequisicionarticulo = ?";
        return $this->update($sql, [$nuevoPrecio, $idReqArt]);
    }

    public function linkOfficialInventoryItem(int $idReqArt, int $idInv): bool {
        $sql = "UPDATE com_requisiciones_detalle SET inventarioid = ? WHERE idrequisicionarticulo = ?";
        return $this->update($sql, [$idInv, $idReqArt]);
    }

    /**
     * Calcula y retorna las partidas de una requisición que aún no han sido compradas en su totalidad,
     * detectando automáticamente si tienen un proveedor ganador asignado vía Sourcing.
     */
    public function getPendingItemsWithSourcing(int $requisicionId): array
    {
        $query = "
            SELECT 
                rd.idrequisicionarticulo,
                rd.inventarioid,
                rd.notas,
                rd.cantidad AS cantidad_solicitada,
                rd.precio_unitario_estimado,
                
                -- IDENTIDAD DEL ARTÍCULO (Catálogo o Sourcing)
                IFNULL(i.cve_articulo, 'SOURCING') AS cve_articulo,
                IFNULL(i.descripcion, (SELECT categoria FROM com_requisicion_items_nuevos WHERE idrequisicionarticulo = rd.idrequisicionarticulo LIMIT 1)) AS descripcion,
                IFNULL(i.unidad_salida, 'PZA') AS unidad_salida,

                -- LÓGICA DE SOURCING (Ganador detectado)
                cot.id_proveedor AS id_proveedor_ganador,
                p.razon_social AS proveedor_nombre_ganador,
                IFNULL(cot.precio_unitario * cot.tipo_cambio, rd.precio_unitario_estimado) AS precio_pactado,

                -- SUMATORIA DE COMPRAS PREVIAS
                IFNULL(oc_comprado.total_comprado, 0) AS cantidad_ya_comprada,
                
                -- CÁLCULO DE SALDO PENDIENTE
                (rd.cantidad - IFNULL(oc_comprado.total_comprado, 0)) AS cantidad_pendiente
                
            FROM com_requisiciones_detalle rd
            
            -- Join 1: Inventario (Si ya fue promovido o existía)
            LEFT JOIN wms_inventario i ON rd.inventarioid = i.idinventario
            
            -- Join 2: Sourcing (Buscamos si hay una cotización marcada como ganadora)
            LEFT JOIN com_requisicion_cotizaciones cot 
                ON rd.idrequisicionarticulo = cot.idrequisicionarticulo AND cot.es_ganadora = 1 AND cot.deleted_at IS NULL
                
            -- Join 3: Datos del Proveedor Ganador
            LEFT JOIN prv_cat_proveedores p ON cot.id_proveedor = p.id_proveedor
            
            -- Join 4: Subconsulta de Compras Reales
            LEFT JOIN (
                SELECT 
                    ocd.idrequisicionarticulo, 
                    SUM(ocd.cantidad) AS total_comprado
                FROM com_ordenes_compra_detalle ocd
                INNER JOIN com_ordenes_compra oc ON ocd.compraid = oc.idcompra
                WHERE oc.estatus != 'cancelada' AND ocd.deleted_at IS NULL
                GROUP BY ocd.idrequisicionarticulo
            ) AS oc_comprado ON rd.idrequisicionarticulo = oc_comprado.idrequisicionarticulo
            
            WHERE rd.requisicionid = ? 
            AND rd.deleted_at IS NULL
            
            -- Filtro de integridad: Solo lo que falta por comprar
            HAVING cantidad_pendiente > 0;
        ";

        $result = $this->select_all($query, [$requisicionId]);
        
        return $result ?: [];
    }

    /**
     * Performs a soft delete on all child data linked to a requisition item.
     * This ensures FK constraints are satisfied and no orphan data remains.
     *
     * @param int $itemId The idrequisicionarticulo to clean up.
     * @param int $userId The ID of the user performing the deletion.
     */
    public function deleteSourcingDataByItem(int $itemId, int $userId): void
    {
        // 1. Mark Technical Specs as deleted
        $sqlSpecs = "UPDATE com_requisicion_items_nuevos 
                    SET deleted_at = NOW() 
                    WHERE idrequisicionarticulo = ? 
                    AND deleted_at IS NULL";
        $this->update($sqlSpecs, [$itemId]);

        // 2. Mark Vendor Quotations as deleted
        $sqlQuotes = "UPDATE com_requisicion_cotizaciones 
                    SET deleted_at = NOW() 
                    WHERE idrequisicionarticulo = ? 
                    AND deleted_at IS NULL";
        $this->update($sqlQuotes, [$itemId]);
    }

    /**
     * Transfiere la propiedad de los datos de sourcing de una partida a otra.
     * Esto evita violaciones de integridad referencial (FK Constraints).
     */
    public function transferSourcingData(int $oldItemId, int $newItemId): void
    {
        // 1. Transferir Ficha Técnica (Specs)
        $sqlSpecs = "UPDATE com_requisicion_items_nuevos 
                     SET idrequisicionarticulo = ? 
                     WHERE idrequisicionarticulo = ?";
        $this->update($sqlSpecs, [$newItemId, $oldItemId]);

        // 2. Transferir Cotizaciones de Proveedores
        $sqlQuotes = "UPDATE com_requisicion_cotizaciones 
                      SET idrequisicionarticulo = ? 
                      WHERE idrequisicionarticulo = ?";
        $this->update($sqlQuotes, [$newItemId, $oldItemId]);
    }
}