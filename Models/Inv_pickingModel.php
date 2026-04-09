<?php
class Inv_pickingModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    // 🔹 Obtener ordenes
    public function selectPickings()
    {
        $sql = "SELECT * FROM wms_picking ORDER BY fecha_creacion DESC";
        return $this->select_all($sql);
    }

    // 🔹 Detalle del picking
    public function selectDetalle($idPicking)
    {
        $sql = "SELECT 
                d.iddetalle,
                d.inventarioid,
                d.ubicacionid,
                i.cve_articulo AS codigo,
                i.descripcion,
                CONCAT(
                    'P', u.pasillo, '-', 
                    u.seccion, '-', 
                    'N', u.nivel, '-', 
                    u.lugar
                ) AS codigo_ubicacion,
                d.lote,
                d.cantidad_solicitada,
                IFNULL(ua.cantidad,0) AS cantidad_existente,
                d.cantidad_pickeada
            FROM wms_picking_detalle d
            INNER JOIN wms_inventario i 
                ON i.idinventario = d.inventarioid
            LEFT JOIN wms_ubicaciones_asignadas ua 
                ON ua.inventarioid = d.inventarioid 
                AND ua.ubicacionesid = d.ubicacionid
            LEFT JOIN wms_ubicaciones u 
                ON u.idubicaciones = d.ubicacionid
            WHERE d.pickingid = $idPicking";

        return $this->select_all($sql);
    }

    // 🔹 Actualizar picking
    public function updatePicking($iddetalle, $cantidad)
    {
        $sql = "UPDATE wms_picking_detalle 
                SET cantidad_pickeada = ?
                WHERE iddetalle = ?";
        return $this->update($sql, [$cantidad, $iddetalle]);
    }

    // 🔹 Descontar inventario
    public function descontarInventario($inventarioid, $ubicacionid, $cantidad)
    {
        $sql = "UPDATE wms_ubicaciones_asignadas
                SET cantidad = cantidad - ?
                WHERE inventarioid = ? AND ubicacionesid = ?";
        return $this->update($sql, [$cantidad, $inventarioid, $ubicacionid]);
    }

    public function insertPicking($folio, $pedido, $prioridad){

    $sql = "INSERT INTO wms_picking 
            (folio, pedido_cliente, fecha, prioridad, fecha_creacion)
            VALUES (?, ?, NOW(), ?, NOW())";

    return $this->insert($sql, [$folio, $pedido, $prioridad]);
}

public function insertDetalle($pickingid, $inventarioid, $ubicacionid, $cantidad){

    $sql = "INSERT INTO wms_picking_detalle
            (pickingid, inventarioid, ubicacionid, cantidad_solicitada)
            VALUES (?, ?, ?, ?)";

    return $this->insert($sql, [
        $pickingid,
        $inventarioid,
        $ubicacionid,
        $cantidad
    ]);
}
}
