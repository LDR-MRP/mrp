<?php
class Inv_recepcionModel extends Mysql
{
    /**
     * Estatus de OC que pueden recibirse en almacén.
     * - en_transito: Compras la marcó en tránsito.
     * - recibida_parcial: ya tiene al menos una recepción parcial.
     * La OC pasa a 'cerrada' únicamente cuando la recepción se completa aquí.
     */
    const OC_RECIBIBLES = ['en_transito', 'recibida_parcial'];

    public function __construct()
    {
        parent::__construct();
    }

    /* =========================================================
       LISTADO DE ÓRDENES PARA RECEPCIÓN
       grupo: pendiente (por recibir) | parcial | cerrada
    ========================================================= */
    public function selectOrdenesRecepcion()
    {
        $sql = "SELECT
                    oc.idcompra,
                    CONCAT('OC-', oc.idcompra) AS folio,
                    IFNULL(p.razon_social, 'Sin proveedor') AS proveedor,
                    IFNULL(a.descripcion, 'Sin almacén') AS almacen,
                    oc.estatus AS estatus_oc,
                    DATE_FORMAT(oc.created_at, '%d/%m/%Y') AS fecha_oc,
                    r.idrecepcion,
                    r.folio AS folio_recepcion,
                    r.estatus AS estatus_recepcion,
                    DATE_FORMAT(COALESCE(r.updated_at, r.created_at), '%d/%m/%Y %H:%i') AS fecha_recepcion,
                    COALESCE(s.solicitado, 0) AS solicitado,
                    COALESCE(rc.recibido, 0) AS recibido,
                    COALESCE(s.partidas, 0) AS partidas,
                    CASE
                        WHEN oc.estatus = 'cerrada' THEN 'cerrada'
                        WHEN oc.estatus = 'recibida_parcial' THEN 'parcial'
                        ELSE 'pendiente'
                    END AS grupo
                FROM com_ordenes_compra oc
                LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
                LEFT JOIN wms_almacenes a ON a.idalmacen = oc.almacenid
                LEFT JOIN wms_recepcion r ON r.compraid = oc.idcompra
                LEFT JOIN (
                    SELECT compraid, SUM(cantidad) AS solicitado, COUNT(*) AS partidas
                    FROM com_ordenes_compra_detalle
                    WHERE deleted_at IS NULL
                    GROUP BY compraid
                ) s ON s.compraid = oc.idcompra
                LEFT JOIN (
                    SELECT r2.compraid, SUM(rd2.cantidad_recibida) AS recibido
                    FROM wms_recepcion r2
                    INNER JOIN wms_recepcion_detalle rd2 ON rd2.recepcionid = r2.idrecepcion
                    GROUP BY r2.compraid
                ) rc ON rc.compraid = oc.idcompra
                WHERE oc.deleted_at IS NULL
                AND (
                    oc.estatus IN ('en_transito', 'recibida_parcial')
                    -- Cerradas: solo las que se cerraron desde la recepción
                    OR (oc.estatus = 'cerrada' AND r.estatus = 'cerrada')
                )
                ORDER BY oc.created_at DESC";

        return $this->select_all($sql);
    }

    public function getOrdenCompra($idCompra)
    {
        $sql = "SELECT idcompra, estatus, almacenid
                FROM com_ordenes_compra
                WHERE idcompra = ?
                AND deleted_at IS NULL";

        return $this->select($sql, [$idCompra]);
    }

    public function updateOrdenCompraEstatus($idCompra, $estatus, $usuarioId)
    {
        $sql = "UPDATE com_ordenes_compra
                SET estatus = ?, updated_by = ?
                WHERE idcompra = ?
                AND deleted_at IS NULL";

        return $this->update($sql, [$estatus, $usuarioId, $idCompra]);
    }

    public function selectDetalleOC($idCompra)
    {
        $idCompra = intval($idCompra);

        $sql = "SELECT
                    d.iddetalle,
                    d.inventarioid,
                    i.cve_articulo AS codigo,
                    i.descripcion,
                    i.unidad_entrada AS unidad,
                    '' AS lote,
                    d.cantidad AS cantidad_solicitada,
                    COALESCE(rc.recibido, 0) AS cantidad_recibida,
                    GREATEST(d.cantidad - COALESCE(rc.recibido, 0), 0) AS cantidad_pendiente,
                    COALESCE(ev.total, 0) AS total_evidencias
                FROM com_ordenes_compra_detalle d
                INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
                LEFT JOIN (
                    SELECT rd.inventarioid, SUM(rd.cantidad_recibida) AS recibido
                    FROM wms_recepcion r
                    INNER JOIN wms_recepcion_detalle rd ON rd.recepcionid = r.idrecepcion
                    WHERE r.compraid = ?
                    GROUP BY rd.inventarioid
                ) rc ON rc.inventarioid = d.inventarioid
                LEFT JOIN (
                    SELECT e.inventarioid, COUNT(*) AS total
                    FROM wms_recepcion_evidencias e
                    INNER JOIN wms_recepcion r ON r.idrecepcion = e.recepcionid
                    WHERE r.compraid = ?
                    GROUP BY e.inventarioid
                ) ev ON ev.inventarioid = d.inventarioid
                WHERE d.compraid = ?
                AND d.deleted_at IS NULL
                ORDER BY i.descripcion ASC";

        return $this->select_all($sql, [$idCompra, $idCompra, $idCompra]);
    }

    public function selectHistorialRecepcion($idCompra)
    {
        $sql = "SELECT
                    rd.iddetalle,
                    DATE_FORMAT(rd.created_at, '%d/%m/%Y %H:%i') AS fecha,
                    i.cve_articulo AS codigo,
                    i.descripcion,
                    i.unidad_entrada AS unidad,
                    rd.lote,
                    rd.cantidad_recibida,
                    rd.observaciones
                FROM wms_recepcion r
                INNER JOIN wms_recepcion_detalle rd ON rd.recepcionid = r.idrecepcion
                INNER JOIN wms_inventario i ON i.idinventario = rd.inventarioid
                WHERE r.compraid = ?
                ORDER BY rd.created_at DESC, rd.iddetalle DESC";

        return $this->select_all($sql, [intval($idCompra)]);
    }

    public function getRecepcionByCompra($idCompra)
    {
        $sql = "SELECT * FROM wms_recepcion WHERE compraid = ? LIMIT 1";
        return $this->select($sql, [intval($idCompra)]);
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
        $sql = "UPDATE wms_recepcion SET estatus = ?, updated_at = NOW() WHERE idrecepcion = ?";
        return $this->update($sql, [$estatus, $recepcionid]);
    }

    public function selectHeaderOC($idCompra)
    {
        $sql = "SELECT
                    oc.idcompra,
                    CONCAT('OC-', oc.idcompra) AS orden_origen,
                    COALESCE(r.folio, CONCAT('RC-', LPAD(oc.idcompra, 6, '0'))) AS orden_destino,
                    DATE_FORMAT(oc.created_at, '%d/%m/%Y') AS fecha,
                    IFNULL(a.descripcion, 'Sin almacén') AS almacen_destino,
                    IFNULL(p.razon_social, 'Sin proveedor') AS proveedor,
                    oc.estatus AS estatus_oc,
                    r.idrecepcion,
                    r.estatus AS estatus_recepcion,
                    r.observaciones
                FROM com_ordenes_compra oc
                LEFT JOIN wms_almacenes a ON a.idalmacen = oc.almacenid
                LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
                LEFT JOIN wms_recepcion r ON r.compraid = oc.idcompra
                WHERE oc.idcompra = ?";

        return $this->select($sql, [intval($idCompra)]);
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

    /* =========================================================
       Compatibilidad: endpoints anteriores por pestaña
    ========================================================= */
    private function filtrarGrupo($grupo)
    {
        return array_values(array_filter(
            $this->selectOrdenesRecepcion(),
            fn($o) => $o['grupo'] === $grupo
        ));
    }

    public function selectOrdenesCompraPendientes() { return $this->filtrarGrupo('pendiente'); }
    public function selectOrdenesAbiertas()         { return $this->filtrarGrupo('pendiente'); }
    public function selectOrdenesParciales()        { return $this->filtrarGrupo('parcial'); }
    public function selectOrdenesCerradas()         { return $this->filtrarGrupo('cerrada'); }

    public function selectOrdenesActivas()
    {
        return array_values(array_filter(
            $this->selectOrdenesRecepcion(),
            fn($o) => $o['grupo'] !== 'cerrada'
        ));
    }

    /* =========================================================
       DOCUMENTOS Y EVIDENCIAS
    ========================================================= */
    public function insertDocumentoRecepcion($recepcionid, $nombre, $ruta)
    {
        $sql = "INSERT INTO wms_recepcion_documentos
            (recepcionid, nombre, ruta)
            VALUES (?, ?, ?)";

        return $this->insert($sql, [$recepcionid, $nombre, $ruta]);
    }

    public function insertEvidenciaRecepcion($recepcionid, $inventarioid, $detalleid, $nombre, $tipo, $ruta)
    {
        $sql = "INSERT INTO wms_recepcion_evidencias
            (recepcionid, inventarioid, detalleid, nombre, tipo, ruta)
            VALUES (?, ?, ?, ?, ?, ?)";

        return $this->insert($sql, [$recepcionid, $inventarioid, $detalleid, $nombre, $tipo, $ruta]);
    }

    public function getEvidenciasProducto($recepcionid, $inventarioid)
    {
        $sql = "SELECT idevidencia, nombre, ruta, tipo,
                       DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha
            FROM wms_recepcion_evidencias
            WHERE recepcionid = ?
            AND inventarioid = ?
            ORDER BY idevidencia DESC";

        return $this->select_all($sql, [$recepcionid, $inventarioid]);
    }

    public function getDocumentosRecepcion($recepcionid)
    {
        $sql = "SELECT iddocumento, nombre, ruta,
                       DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha
            FROM wms_recepcion_documentos
            WHERE recepcionid = ?
            ORDER BY iddocumento DESC";

        return $this->select_all($sql, [$recepcionid]);
    }

    /* =========================================================
       AFECTACIÓN DE INVENTARIO
    ========================================================= */
    public function getDetalleCompraProducto($idCompra, $inventarioid)
    {
        $sql = "SELECT costo_unitario
            FROM com_ordenes_compra_detalle
            WHERE compraid = ?
            AND inventarioid = ?
            AND deleted_at IS NULL
            LIMIT 1";

        return $this->select($sql, [$idCompra, $inventarioid]);
    }

    public function getAlmacenCompra($idCompra)
    {
        $sql = "SELECT almacenid FROM com_ordenes_compra WHERE idcompra = ?";
        return $this->select($sql, [$idCompra]);
    }

    public function getMultiAlmacen($inventarioid, $almacenid)
    {
        $sql = "SELECT * FROM wms_multialmacen WHERE inventarioid = ? AND almacenid = ?";
        return $this->select($sql, [$inventarioid, $almacenid]);
    }

    public function updateExistenciaMultiAlmacen($idmultialmacen, $cantidad)
    {
        $sql = "UPDATE wms_multialmacen
            SET existencia = existencia + ?
            WHERE idmultialmacen = ?";

        return $this->update($sql, [$cantidad, $idmultialmacen]);
    }

    public function insertMultiAlmacen($inventarioid, $almacenid, $cantidad)
    {
        $producto = $this->select(
            "SELECT stock_minimo, stock_maximo FROM wms_inventario WHERE idinventario = ?",
            [$inventarioid]
        );

        $sql = "INSERT INTO wms_multialmacen
            (inventarioid, almacenid, existencia, stock_minimo, stock_maximo)
            VALUES (?, ?, ?, ?, ?)";

        return $this->insert($sql, [
            $inventarioid,
            $almacenid,
            $cantidad,
            $producto['stock_minimo'] ?? 0,
            $producto['stock_maximo'] ?? 0
        ]);
    }

    public function getExistenciaActual($inventarioid, $almacenid)
    {
        $sql = "SELECT existencia FROM wms_multialmacen WHERE inventarioid = ? AND almacenid = ?";
        return $this->select($sql, [$inventarioid, $almacenid]);
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
                inventarioid, almacenid, numero_movimiento, concepmovid, referencia,
                cantidad, costo_cantidad, precio, costo, existencia, signo,
                fecha_movimiento, estado
            )
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, 1, NOW(), 2)";

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
