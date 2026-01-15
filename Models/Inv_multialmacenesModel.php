<?php
class Inv_multialmacenesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }




    public function selectMultialmacenes()
    {
        $sql = "SELECT m.idmultialmacen,
                       i.descripcion AS inventario,
                       a.descripcion AS almacen,
                       m.existencia
                FROM wms_multialmacen m
                INNER JOIN wms_inventario i ON m.inventarioid=i.idinventario
                INNER JOIN wms_almacenes a ON m.almacenid=a.idalmacen";
        return $this->select_all($sql);
    }

    public function selectMultialmacen($id)
    {
        $sql = "SELECT m.idmultialmacen,
                       m.inventarioid,
                       m.almacenid,
                       m.existencia
                FROM wms_multialmacen m
                WHERE m.idmultialmacen=?";
        return $this->select($sql, [$id]);
    }

    public function selectInventariosPC_H()
    {
        $sql = "SELECT idinventario, cve_articulo, descripcion
            FROM wms_inventario
            WHERE tipo_elemento IN ('P','C','H') AND estado=2";
        return $this->select_all($sql);
    }

    public function selectOptionAlmacenes()
    {
        $sql = "SELECT idalmacen, descripcion FROM wms_almacenes WHERE estado=2";
        return $this->select_all($sql);
    }

    public function actualizarExistencia(
    int $inventarioid,
    int $almacenid,
    float $nuevaExistencia
) {
    $sql = "
        INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
        VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE
        existencia = ?
    ";
    return $this->insert($sql, [
        $inventarioid,
        $almacenid,
        $nuevaExistencia,
        $nuevaExistencia
    ]);
}

}
