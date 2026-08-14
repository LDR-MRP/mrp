<?php
class Inv_recepcionModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }


    public function selectOrdenesCompraPendientes()
    {
        $sql = "SELECT 
                    oc.idcompra,
                    CONCAT('OC-', oc.idcompra) AS folio,
                    IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
                FROM com_ordenes_compra oc
                LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
                WHERE oc.estatus IN ('cerrada')
                AND oc.deleted_at IS NULL
                ORDER BY oc.created_at DESC";

        return $this->select_all($sql);
    }

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

                (
                    SELECT COALESCE(SUM(rd2.cantidad_recibida),0)
                    FROM wms_recepcion r2
                    INNER JOIN wms_recepcion_detalle rd2
                        ON rd2.recepcionid = r2.idrecepcion
                    WHERE r2.compraid = d.compraid
                    AND rd2.inventarioid = d.inventarioid
                ) AS cantidad_recibida,

                (
                    d.cantidad -
                    (
                        SELECT COALESCE(SUM(rd3.cantidad_recibida),0)
                        FROM wms_recepcion r3
                        INNER JOIN wms_recepcion_detalle rd3
                            ON rd3.recepcionid = r3.idrecepcion
                        WHERE r3.compraid = d.compraid
                        AND rd3.inventarioid = d.inventarioid
                    )
                ) AS cantidad_pendiente,
                COALESCE((
                        SELECT rd.observaciones
                        FROM wms_recepcion_detalle rd
                        INNER JOIN wms_recepcion r ON r.idrecepcion = rd.recepcionid
                        WHERE r.compraid = d.compraid
                        AND rd.inventarioid = d.inventarioid
                        ORDER BY rd.iddetalle DESC
                        LIMIT 1
                    ), '') AS observaciones,

                (
                    SELECT COUNT(*)
                    FROM wms_recepcion_evidencias e2
                    WHERE e2.inventarioid = d.inventarioid
                ) AS total_evidencias

            FROM com_ordenes_compra_detalle d
            INNER JOIN wms_inventario i
                ON i.idinventario = d.inventarioid

            WHERE d.compraid = $idCompra
            AND d.deleted_at IS NULL

            ORDER BY i.descripcion ASC";

        return $this->select_all($sql);
    }

    public function getRecepcionByCompra($idCompra)
    {
        $sql = "SELECT * FROM wms_recepcion WHERE compraid = $idCompra LIMIT 1";
        return $this->select($sql);
    }

    public function insertRecepcion($idCompra, $observaciones, $usuarioId)
    {
        $folio = 'RC-' . str_pad($idCompra, 6, "0", STR_PAD_LEFT);

        $sql = "INSERT INTO wms_recepcion
                (compraid, folio, fecha_recepcion, usuarioid, estatus, observaciones)
                VALUES (?, ?, NOW(), ?, 'abierta', ?)";

        return $this->insert($sql, [$idCompra, $folio, $usuarioId, $observaciones]);
    }

    public function insertDetalleRecepcion($recepcionid, $inventarioid, $codigo, $lote, $esperada, $recibida, $obs)
    {
        $sql = "INSERT INTO wms_recepcion_detalle
                (recepcionid, inventarioid, codigo_barras, lote, cantidad_esperada, cantidad_recibida, escaneado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)";

        return $this->insert($sql, [$recepcionid, $inventarioid, $codigo, $lote, $esperada, $recibida, $obs]);
    }

    public function updateRecepcionStatus($recepcionid, $estatus)
    {
        $sql = "UPDATE wms_recepcion SET estatus = ? WHERE idrecepcion = ?";
        return $this->update($sql, [$estatus, $recepcionid]);
    }

    public function selectHeaderOC($idCompra)
    {
        $sql = "SELECT
                    CONCAT('OC-', oc.idcompra) AS orden_origen,
                    CONCAT('RC-', oc.idcompra) AS orden_destino,
                    DATE_FORMAT(oc.created_at, '%d/%m/%Y') AS fecha,
                    a.descripcion AS almacen_destino
                FROM com_ordenes_compra oc
                LEFT JOIN wms_almacenes a ON a.idalmacen = oc.almacenid
                WHERE oc.idcompra = $idCompra";

        return $this->select($sql);
    }

    public function updateRecepcionHeader($recepcionid, $observaciones)
    {
        $sql = "UPDATE wms_recepcion 
            SET observaciones = ?, updated_at = NOW()
            WHERE idrecepcion = ?";

        return $this->update($sql, [$observaciones, $recepcionid]);
    }

    public function recepcionCompleta($idCompra)
    {
        $sql = "SELECT COUNT(*) AS pendientes
            FROM (
                SELECT 
                    d.inventarioid,
                    d.cantidad AS solicitado,
                    COALESCE(SUM(rd.cantidad_recibida), 0) AS recibido
                FROM com_ordenes_compra_detalle d
                LEFT JOIN wms_recepcion r 
                    ON r.compraid = d.compraid
                LEFT JOIN wms_recepcion_detalle rd 
                    ON rd.recepcionid = r.idrecepcion
                    AND rd.inventarioid = d.inventarioid
                WHERE d.compraid = ?
                AND d.deleted_at IS NULL
                GROUP BY d.inventarioid, d.cantidad
                HAVING recibido < solicitado
            ) t";

        $result = $this->select($sql, [$idCompra]);
        return intval($result['pendientes']) === 0;
    }

    public function selectOrdenesActivas()
    {
        $sql = "SELECT 
                oc.idcompra,
                CONCAT('OC-', oc.idcompra) AS folio,
                r.estatus,
                IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
            FROM com_ordenes_compra oc
            LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
            LEFT JOIN wms_recepcion r ON r.compraid = oc.idcompra
            WHERE oc.deleted_at IS NULL
            AND (r.estatus IS NULL OR r.estatus IN ('abierta','parcial'))
            ORDER BY oc.created_at DESC";

        return $this->select_all($sql);
    }

    public function selectOrdenesCerradas()
    {
        $sql = "SELECT 
                oc.idcompra,
                CONCAT('OC-', oc.idcompra) AS folio,
                r.estatus,
                IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
            FROM com_ordenes_compra oc
            LEFT JOIN prv_cat_proveedores p 
                ON p.id_proveedor = oc.proveedorid
            INNER JOIN wms_recepcion r 
                ON r.compraid = oc.idcompra
            WHERE r.estatus = 'cerrada'
            AND oc.estatus = 'cerrada'
            AND oc.deleted_at IS NULL
            ORDER BY r.updated_at DESC";

        return $this->select_all($sql);
    }

    public function selectOrdenesAbiertas()
    {
        $sql = "SELECT 
                oc.idcompra,
                CONCAT('OC-', oc.idcompra) AS folio,
                IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
            FROM com_ordenes_compra oc
            LEFT JOIN prv_cat_proveedores p 
                ON p.id_proveedor = oc.proveedorid
            LEFT JOIN wms_recepcion r 
                ON r.compraid = oc.idcompra
            WHERE oc.deleted_at IS NULL
            AND oc.estatus = 'cerrada'
            AND (r.estatus IS NULL OR r.estatus = 'abierta')
            ORDER BY oc.created_at DESC";

        return $this->select_all($sql);
    }

    public function selectOrdenesParciales()
    {
        $sql = "SELECT 
                oc.idcompra,
                CONCAT('OC-', oc.idcompra) AS folio,
                IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
            FROM com_ordenes_compra oc
            LEFT JOIN prv_cat_proveedores p 
                ON p.id_proveedor = oc.proveedorid
            INNER JOIN wms_recepcion r 
                ON r.compraid = oc.idcompra
            WHERE r.estatus = 'parcial'
            AND oc.estatus = 'cerrada'
            AND oc.deleted_at IS NULL
            ORDER BY r.updated_at DESC";

        return $this->select_all($sql);
    }

    public function insertDocumentoRecepcion($recepcionid, $nombre, $ruta)
    {
        $sql = "INSERT INTO wms_recepcion_documentos
            (recepcionid, nombre, ruta)
            VALUES (?, ?, ?)";

        return $this->insert($sql, [$recepcionid, $nombre, $ruta]);
    }

    public function insertEvidenciaRecepcion(
        $recepcionid,
        $inventarioid,
        $detalleid,
        $nombre,
        $tipo,
        $ruta
    ) {
        $sql = "INSERT INTO wms_recepcion_evidencias
            (
                recepcionid,
                inventarioid,
                detalleid,
                nombre,
                tipo,
                ruta
            )
            VALUES (?, ?, ?, ?, ?, ?)";

        return $this->insert($sql, [
            $recepcionid,
            $inventarioid,
            $detalleid,
            $nombre,
            $tipo,
            $ruta
        ]);
    }

    public function getEvidenciasProducto($recepcionid, $inventarioid)
    {
        $sql = "SELECT
                idevidencia,
                nombre,
                ruta,
                tipo,
                created_at
            FROM wms_recepcion_evidencias
            WHERE recepcionid = ?
            AND inventarioid = ?
            ORDER BY idevidencia DESC";

        return $this->select_all($sql, [
            $recepcionid,
            $inventarioid
        ]);
    }

    public function getDocumentosRecepcion($recepcionid)
    {
        $sql = "SELECT
                iddocumento,
                nombre,
                ruta
            FROM wms_recepcion_documentos
            WHERE recepcionid = ?
            ORDER BY iddocumento DESC";

        return $this->select_all($sql, [$recepcionid]);
    }

    public function getDetalleCompraProducto($idCompra, $inventarioid)
    {
        $sql = "SELECT
                costo_unitario
            FROM com_ordenes_compra_detalle
            WHERE compraid = ?
            AND inventarioid = ?
            LIMIT 1";

        return $this->select($sql, [
            $idCompra,
            $inventarioid
        ]);
    }

    public function getAlmacenCompra($idCompra)
    {
        $sql = "SELECT almacenid
            FROM com_ordenes_compra
            WHERE idcompra = ?";

        return $this->select($sql, [$idCompra]);
    }

    public function getMultiAlmacen(
        $inventarioid,
        $almacenid
    ) {
        $sql = "SELECT *
            FROM wms_multialmacen
            WHERE inventarioid = ?
            AND almacenid = ?";

        return $this->select($sql, [
            $inventarioid,
            $almacenid
        ]);
    }

    public function updateExistenciaMultiAlmacen(
        $idmultialmacen,
        $cantidad
    ) {
        $sql = "UPDATE wms_multialmacen
            SET existencia = existencia + ?
            WHERE idmultialmacen = ?";

        return $this->update($sql, [
            $cantidad,
            $idmultialmacen
        ]);
    }

    public function insertMultiAlmacen($inventarioid, $almacenid, $cantidad)
    {
        $producto = $this->select(
            "SELECT stock_minimo, stock_maximo
         FROM wms_inventario
         WHERE idinventario = ?",
            [$inventarioid]
        );

        $sql = "INSERT INTO wms_multialmacen
            (inventarioid, almacenid, existencia, stock_minimo, stock_maximo)
            VALUES (?, ?, ?, ?, ?)";

        return $this->insert($sql, [
            $inventarioid,
            $almacenid,
            $cantidad,
            $producto['stock_minimo'],
            $producto['stock_maximo']
        ]);
    }

    public function getExistenciaActual(
        $inventarioid,
        $almacenid
    ) {
        $sql = "SELECT existencia
            FROM wms_multialmacen
            WHERE inventarioid = ?
            AND almacenid = ?";

        return $this->select($sql, [
            $inventarioid,
            $almacenid
        ]);
    }

    public function insertMovimientoInventario(
        $inventarioid,
        $almacenid,
        $numeroMovimiento,
        $referencia,
        $cantidad,
        $costoUnitario,
        $existencia
    ) {
        $sql = "INSERT INTO wms_movimientos_inventario
            (
                inventarioid,
                almacenid,
                numero_movimiento,
                concepmovid,
                referencia,
                cantidad,
                costo_cantidad,
                precio,
                costo,
                existencia,
                signo,
                fecha_movimiento,
                estado
            )
            VALUES
            (
                ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, 1,
                NOW(), 2
            )";

        return $this->insert($sql, [
            $inventarioid,
            $almacenid,
            $numeroMovimiento,
            $referencia,
            $cantidad,
            $cantidad * $costoUnitario,
            $costoUnitario,
            $costoUnitario,
            $existencia
        ]);
    }
}
