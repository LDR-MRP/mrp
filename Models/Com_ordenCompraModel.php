<?php
//namespace Models;

use Mysql; // Asumiendo que tu clase base está en el namespace global o ajusta según tu autoloader

class Com_ordenCompraModel extends Mysql {

    use Auditable;

    protected string $table = 'com_ordenes_compra';

    protected string $detailTable = 'com_ordenes_compra_detalle';

    public function __construct() {
        parent::__construct();
    }

    public function getTableName(): string 
    {
        return $this->table;
    }

    /**
     * Obtiene el listado de OCs con filtros dinámicos.
     */
    public function getAll(array $filters = []): array {
        $where = " WHERE oc.deleted_at IS NULL ";
        $params = [];

        // Filtro por Proveedor
        if (!empty($filters['proveedorid'])) {
            $where .= " AND oc.proveedorid = ? ";
            $params[] = $filters['proveedorid'];
        }

        // Filtro por Estatus
        if (!empty($filters['estatus'])) {
            $where .= " AND oc.estatus = ? ";
            $params[] = $filters['estatus'];
        }

        // Filtro por Rango de Fechas
        if (!empty($filters['fecha_desde']) && !empty($filters['fecha_hasta'])) {
            $where .= " AND oc.created_at BETWEEN ? AND ? ";
            $params[] = $filters['fecha_desde'] . " 00:00:00";
            $params[] = $filters['fecha_hasta'] . " 23:59:59";
        }

        $query = "SELECT 
                    oc.idcompra,
                    oc.requisicionid,
                    oc.estatus,
                    oc.total,
                    oc.moneda,
                    oc.created_at,
                    p.nombre_comercial AS proveedor_nombre,
                    u.nombres AS comprador_nombre
                  FROM com_ordenes_compra oc
                  LEFT JOIN prv_cat_proveedores p ON oc.proveedorid = p.id_proveedor
                  LEFT JOIN usuarios u ON oc.created_by = u.idusuario
                  $where
                  ORDER BY oc.idcompra DESC";

        return $this->select_all($query, $params);
    }

    /**
     * Crea la cabecera de la Orden de Compra.
     * @return int El ID de la nueva OC (idcompra).
     */
    public function createHeader(array $data): int {
        // Se ha eliminado 'usuarioid' de la lista de campos y se ha quitado un '?'
        $query = "INSERT INTO com_ordenes_compra 
                  (requisicionid, proveedorid, almacenid, estatus, moneda, tipo_cambio, observaciones, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Se ha eliminado $data['usuarioid'] del array de valores
        $values = [
            $data['requisicionid'],
            $data['proveedorid'],
            $data['almacenid'],
            $data['estatus'],
            $data['moneda'],
            $data['tipo_cambio'],
            $data['observaciones'],
            $data['created_by']
        ];

        return $this->insert($query, $values);
    }

    /**
     * Crea una partida (detalle) de la Orden de Compra.
     */
    public function createDetail(int $ocId, array $data): bool {
        $query = "INSERT INTO com_ordenes_compra_detalle 
                  (compraid, idrequisicionarticulo, inventarioid, cantidad, costo_unitario, porcentaje_descuento, descuento_partida, impuesto_partida, subtotal_partida) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $ocId,
            $data['idrequisicionarticulo'],
            $data['inventarioid'],
            $data['cantidad'],
            $data['costo_unitario'],
            $data['porcentaje_descuento'],
            $data['descuento_partida'],
            $data['impuesto_partida'],
            $data['subtotal_partida']
        ];

        // Usamos insert porque devuelve el ID, si es > 0 fue exitoso.
        return $this->insert($query, $values) > 0;
    }

    /**
     * Actualiza los totales financieros en la cabecera de la OC.
     */
    public function updateTotals(int $ocId, float $subtotal, float $iva, float $total): bool {
        $query = "UPDATE com_ordenes_compra 
                  SET subtotal = ?, iva = ?, total = ? 
                  WHERE idcompra = ?";
        
        return $this->update($query, [$subtotal, $iva, $total, $ocId]);
    }

    public function getById(int $id): ?array {
        $query = "SELECT oc.idcompra, oc.requisicionid, oc.proveedorid, oc.almacenid, oc.estatus, 
                         oc.moneda, oc.tipo_cambio, oc.subtotal, oc.iva, oc.total, oc.observaciones, oc.created_at,
                         p.nombre_comercial AS proveedor_nombre, a.cve_almacen AS almacen_nombre
                  FROM com_ordenes_compra oc
                  LEFT JOIN prv_cat_proveedores p ON oc.proveedorid = p.id_proveedor
                  LEFT JOIN wms_almacenes a ON oc.almacenid = a.idalmacen
                  WHERE oc.idcompra = ? LIMIT 1";
        return $this->select($query, [$id]);
    }

    public function getRelatedPOs(int $requisicionId, int $currentOcId): array {
        $query = "SELECT idcompra, total, estatus, created_at 
                  FROM com_ordenes_compra 
                  WHERE requisicionid = ? AND idcompra != ? AND deleted_at IS NULL";
        return $this->select_all($query, [$requisicionId, $currentOcId]);
    }

    public function getDetails(int $id): array {
        $query = "SELECT ocd.*, i.descripcion, i.cve_articulo
                  FROM com_ordenes_compra_detalle ocd
                  INNER JOIN wms_inventario i ON ocd.inventarioid = i.idinventario
                  WHERE ocd.compraid = ?";
        return $this->select_all($query, [$id]);
    }

    public function getPurchaseOrderForPrint(int $id): ?array
    {
        // Consulta de Cabecera con JOINs a Proveedor, Almacen y Planta
        $sql = "SELECT 
                    oc.*,
                    p.razon_social AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    a.descripcion AS almacen_nombre
                FROM com_ordenes_compra oc
                INNER JOIN prv_cat_proveedores p ON oc.proveedorid = p.id_proveedor
                INNER JOIN wms_almacenes a ON oc.almacenid = a.idalmacen
                WHERE oc.idcompra = ? AND oc.deleted_at IS NULL";

        $po = $this->select($sql, [$id]);
        if (!$po) return null;

        // Consulta de Detalles con JOIN a Inventario
        $sqlItems = "SELECT 
                        d.*, 
                        i.cve_articulo, 
                        i.descripcion, 
                        i.unidad_salida
                    FROM com_ordenes_compra_detalle d
                    INNER JOIN wms_inventario i ON d.inventarioid = i.idinventario
                    WHERE d.compraid = ? AND d.deleted_at IS NULL";
        
        $po['items'] = $this->select_all($sqlItems, [$id]);
        return $po;
    }

    /**
     * Obtiene la OC bloqueando la fila para actualización.
     */
    public function getPurchaseOrderForUpdate(int $id): ?array
    {
        $sql = "SELECT idcompra, estatus, requisicionid, proveedorid, plantaid, almacenid
                FROM com_ordenes_compra 
                WHERE idcompra = ? 
                AND deleted_at IS NULL 
                FOR UPDATE";
                
        return $this->select($sql, [$id]) ?: null;
    }

    /**
     * Actualiza el estatus de la Orden de Compra.
     */
    public function updateStatus(int $id, string $status, int $userId): bool
    {
        // Usamos el campo updated_by definido en tu DDL
        $sql = "UPDATE com_ordenes_compra 
                SET estatus = ?, 
                    updated_by = ? 
                WHERE idcompra = ? 
                AND deleted_at IS NULL";

        return $this->update($sql, [$status, $userId, $id]);
    }

    /**
     * Cuenta cuántas OCs activas (no canceladas) tiene una requisición.
     */
    public function countActiveOrdersByRequisition(int $requisicionId): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM com_ordenes_compra 
                WHERE requisicionid = ? 
                AND estatus != 'cancelada' 
                AND deleted_at IS NULL";
                
        $result = $this->select($sql, [$requisicionId]);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Calcula el saldo pendiente de recibir de una Orden de Compra.
     * Base para la HU #72 y HU #70 (Validación de excesos).
     */
    public function getPendingReceptionItems(int $idcompra): array
    {
        $query = "
            SELECT 
                ocd.idrequisicionarticulo,
                ocd.inventarioid,
                i.cve_articulo,
                i.descripcion,
                i.unidad_salida,
                ocd.costo_unitario AS precio_unitario_estimado,
                ocd.cantidad AS cantidad_comprada,
                -- Sumamos todas las recepciones previas de esta partida
                IFNULL(recepcionado.total_recibido, 0) AS cantidad_ya_recibida,
                -- Calculamos lo que falta
                (ocd.cantidad - IFNULL(recepcionado.total_recibido, 0)) AS saldo_pendiente
            FROM com_ordenes_compra_detalle ocd
            INNER JOIN wms_inventario i ON ocd.inventarioid = i.idinventario
            LEFT JOIN (
                SELECT 
                    rd.idrequisicionarticulo,
                    SUM(rd.cantidad_recibida) AS total_recibido
                FROM inv_recepcion_detalle rd
                INNER JOIN inv_recepciones r ON rd.recepcionid = r.idrecepcion
                WHERE r.idcompra = ?
                GROUP BY rd.idrequisicionarticulo
            ) AS recepcionado ON ocd.idrequisicionarticulo = recepcionado.idrequisicionarticulo
            WHERE ocd.compraid = ?
            HAVING saldo_pendiente > 0;
        ";

        return $this->select_all($query, [$idcompra, $idcompra]) ?: [];
    }

    /**
     * Obtiene la sumatoria de lo recibido físicamente para cada partida de una OC.
     */
    public function getReceivedBalancesByOC(int $ocId): array
    {
        $sql = "SELECT 
                    rd.idrequisicionarticulo, 
                    SUM(rd.cantidad_recibida) as total_recibido
                FROM inv_recepcion_detalle rd
                INNER JOIN inv_recepciones r ON rd.recepcionid = r.idrecepcion
                WHERE r.idcompra = ?
                GROUP BY rd.idrequisicionarticulo";

        return $this->select_all($sql, [$ocId]) ?: [];
    }
}
?>