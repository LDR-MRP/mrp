<?php
class Inv_pickingModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    // =========================
    // OCs pendientes por surtir
    // =========================
    public function selectOrdenesCompraPendientes()
    {
        $sql = "SELECT 
                oc.idcompra,
                CONCAT('OC-', oc.idcompra) AS folio,
                oc.estatus,
                IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
            FROM com_ordenes_compra oc
            LEFT JOIN prv_cat_proveedores p 
                ON p.id_proveedor = oc.proveedorid
            WHERE oc.estatus IN ('emitida','en_transito','recibida_parcial')
            AND oc.deleted_at IS NULL
            ORDER BY oc.created_at DESC";

        return $this->select_all($sql);
    }

    // =========================
    // Detalle de OC + stock
    // =========================
    public function selectDetalleOC($idCompra)
    {
        $sql = "SELECT
                d.iddetalle,
                d.inventarioid,
                i.cve_articulo AS codigo,
                i.descripcion,
                i.unidad_entrada AS unidad,
                
                '' AS lote,
                d.cantidad AS cantidad_solicitada,
                0 AS cantidad_recibida,
                d.cantidad AS cantidad_pendiente,

                a.idalmacen AS almacen_destino,
                a.descripcion AS almacen_nombre,

                '' AS ubicacion_sugerida,
                '' AS observaciones

            FROM com_ordenes_compra_detalle d
            INNER JOIN wms_inventario i 
                ON i.idinventario = d.inventarioid
            INNER JOIN com_ordenes_compra oc
                ON oc.idcompra = d.compraid
            LEFT JOIN wms_almacenes a
                ON a.idalmacen = oc.almacenid
            WHERE d.compraid = $idCompra
            AND d.deleted_at IS NULL
            ORDER BY i.descripcion ASC";

        return $this->select_all($sql);
    }

    // =========================
    // Buscar picking existente
    // =========================
    public function getPickingByCompra($idCompra)
    {
        $sql = "SELECT * FROM wms_picking 
                WHERE compraid = $idCompra
                LIMIT 1";
        return $this->select($sql);
    }

    // =========================
    // Crear picking automático
    // =========================
    public function insertPicking($idCompra, $pedidoCliente, $prioridad, $observaciones, $usuarioId)
    {
        $folio = 'PK-' . str_pad($idCompra, 6, "0", STR_PAD_LEFT);

        $sql = "INSERT INTO wms_picking
            (
                compraid,
                folio,
                pedido_cliente,
                fecha,
                prioridad,
                estatus,
                observaciones,
                fecha_creacion,
                estado,
                usuario_id,
                fecha_inicio,
                fecha_fin
            )
            VALUES (?, ?, ?, NOW(), ?, 1, ?, NOW(), 1, ?, NOW(), NOW())";

        return $this->insert($sql, [
            $idCompra,
            $folio,
            $pedidoCliente,
            $prioridad,
            $observaciones,
            $usuarioId
        ]);
    }

    // =========================
    // Insert detalle picking
    // =========================
    public function insertDetallePicking($pickingid, $inventarioid, $ubicacionid, $lote, $cantidadSolicitada, $cantidadPickeada, $observaciones)
    {
        $sql = "INSERT INTO wms_picking_detalle
            (
                pickingid,
                inventarioid,
                ubicacionid,
                lote,
                cantidad_solicitada,
                cantidad_pickeada,
                estatus,
                observaciones
            )
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)";

        return $this->insert($sql, [
            $pickingid,
            $inventarioid,
            $ubicacionid,
            $lote,
            $cantidadSolicitada,
            $cantidadPickeada,
            $observaciones
        ]);
    }

    // =========================
    // Ingresar inventario (recepción)
    // =========================
    public function ingresarInventario($inventarioid, $ubicacionid, $cantidad)
    {
        // validar si ya existe producto en ubicación
        $sql = "SELECT id
            FROM wms_ubicaciones_asignadas
            WHERE inventarioid = ?
            AND ubicacionesid = ?
            LIMIT 1";

        $existe = $this->select($sql, [$inventarioid, $ubicacionid]);

        if (!empty($existe)) {
            $sqlUpdate = "UPDATE wms_ubicaciones_asignadas
                      SET cantidad = cantidad + ?
                      WHERE inventarioid = ?
                      AND ubicacionesid = ?";

            return $this->update($sqlUpdate, [$cantidad, $inventarioid, $ubicacionid]);
        } else {
            $sqlInsert = "INSERT INTO wms_ubicaciones_asignadas
                      (inventarioid, ubicacionesid, cantidad)
                      VALUES (?, ?, ?)";

            return $this->insert($sqlInsert, [$inventarioid, $ubicacionid, $cantidad]);
        }
    }

    // =========================
    // Actualizar estatus OC
    // =========================
    public function updateOrdenCompra($idCompra, $estatus = 'cerrada')
    {
        $sql = "UPDATE com_ordenes_compra
                SET estatus = ?
                WHERE idcompra = ?";
        return $this->update($sql, [$estatus, $idCompra]);
    }

    public function selectHeaderOC($idCompra)
{
    $sql = "SELECT
            oc.idcompra,
            CONCAT('OC-', oc.idcompra) AS orden_origen,
            CONCAT('RC-', oc.idcompra) AS orden_destino,

            DATE_FORMAT(oc.created_at, '%d/%m/%Y') AS fecha,


            a.descripcion AS almacen_destino,

            IFNULL(p.razon_social, 'Sin proveedor') AS cliente,
            IFNULL(oc.requisicionid, '') AS pedido_cliente

        FROM com_ordenes_compra oc
        LEFT JOIN prv_cat_proveedores p
            ON p.id_proveedor = oc.proveedorid
        LEFT JOIN wms_almacenes a
            ON a.idalmacen = oc.almacenid
        WHERE oc.idcompra = $idCompra";

    return $this->select($sql);
}
}
