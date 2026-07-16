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
                    COALESCE(SUM(rd.cantidad_recibida),0) AS cantidad_recibida,
                    (d.cantidad - COALESCE(SUM(rd.cantidad_recibida),0)) AS cantidad_pendiente,
                    '' AS observaciones
                FROM com_ordenes_compra_detalle d
                INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
                LEFT JOIN wms_recepcion r ON r.compraid = d.compraid
                LEFT JOIN wms_recepcion_detalle rd 
                    ON rd.recepcionid = r.idrecepcion 
                    AND rd.inventarioid = d.inventarioid
                WHERE d.compraid = $idCompra
                AND d.deleted_at IS NULL
                GROUP BY d.iddetalle, d.inventarioid, i.cve_articulo, i.descripcion, i.unidad_entrada, d.cantidad
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
<<<<<<< HEAD
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
=======
                    oc.idcompra,
                    CONCAT('OC-', oc.idcompra) AS folio,
                    IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
                FROM com_ordenes_compra oc
                LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
                LEFT JOIN wms_recepcion r ON r.compraid = oc.idcompra
                WHERE oc.deleted_at IS NULL
                AND (r.estatus IS NULL OR r.estatus = 'abierta')
                ORDER BY oc.created_at DESC";
>>>>>>> 328e9fd126c8f2c36104dbe966640de6ef62e47f

        return $this->select_all($sql);
    }

    public function selectOrdenesParciales()
    {
        $sql = "SELECT 
<<<<<<< HEAD
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
=======
                    oc.idcompra,
                    CONCAT('OC-', oc.idcompra) AS folio,
                    IFNULL(p.razon_social, 'Sin proveedor') AS proveedor
                FROM com_ordenes_compra oc
                LEFT JOIN prv_cat_proveedores p ON p.id_proveedor = oc.proveedorid
                INNER JOIN wms_recepcion r ON r.compraid = oc.idcompra
                WHERE r.estatus = 'parcial'
                AND oc.deleted_at IS NULL
                ORDER BY r.updated_at DESC";

        return $this->select_all($sql);
    }

    /**
     * Registra la cabecera de una nueva recepción de mercancía.
     * 
     * @param array $data { idcompra, plantaid, usuarioid, num_remision, observaciones, created_by }
     * @return int ID de la recepción generada.
     */
    public function insertHeader(array $data): int
    {
        $sql = "INSERT INTO inv_recepciones (
                    idcompra, 
                    plantaid, 
                    usuarioid, 
                    num_remision, 
                    observaciones, 
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $params = [
            (int)$data['idcompra'],
            (int)$data['plantaid'],
            (int)$data['usuarioid'],
            $data['num_remision'],
            $data['observaciones'] ?? '',
            (int)$data['created_by']
        ];

        return $this->insert($sql, $params) ?? 0;
    }

    /**
     * Registra el detalle físico de una partida recibida.
     * 
     * @param int   $recepcionId ID de la cabecera (inv_recepciones).
     * @param array $item { idrequisicionarticulo, inventarioid, cantidad_recibida }
     * @return int ID del detalle generado.
     */
    public function insertDetail(int $recepcionId, array $item): int
    {
        $sql = "INSERT INTO inv_recepcion_detalle (
                    recepcionid, 
                    idrequisicionarticulo, 
                    inventarioid, 
                    cantidad_recibida
                ) VALUES (?, ?, ?, ?)";

        $params = [
            $recepcionId,
            (int)$item['idrequisicionarticulo'],
            (int)$item['inventarioid'],
            (float)$item['cantidad_recibida']
        ];

        return $this->insert($sql, $params) ?? 0;
    }
>>>>>>> 328e9fd126c8f2c36104dbe966640de6ef62e47f
}
