<?php
class Inv_multialmacenesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }


    public function insertMultialmacen($inventarioid, $almacenid, $existencia, $stockmin, $stockmax)
    {
        $sql = "INSERT INTO wms_multialmacen
            (inventarioid, almacenid, existencia, stock_minimo, stock_maximo)
            VALUES (?,?,?,?,?)";

        try {

            return $this->insert($sql, [
                $inventarioid,
                $almacenid,
                $existencia,
                $stockmin,
                $stockmax
            ]);
        } catch (Exception $e) {

            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                return "exist";
            }

            return 0;
        }
    }


    public function updateMultialmacen($id, $inventarioid, $almacenid, $existencia, $stockmin, $stockmax)
    {
        $sql = "UPDATE wms_multialmacen SET
            inventarioid = ?,
            almacenid = ?,
            existencia = ?,
            stock_minimo = ?,
            stock_maximo = ?
            WHERE idmultialmacen = ?";

        return $this->update($sql, [
            $inventarioid,
            $almacenid,
            $existencia,
            $stockmin,
            $stockmax,
            $id
        ]);
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
            WHERE m.idmultialmacen = {$id}";
        return $this->select($sql);
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
        $sql = "INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
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

    public function searchInventarios(string $term)
    {
        $sql = "SELECT idinventario, cve_articulo, descripcion
            FROM wms_inventario
            WHERE estado=2 
              AND tipo_elemento IN ('P','C','H')
              AND (cve_articulo LIKE '%{$term}%' 
              OR descripcion LIKE '%{$term}%')
            LIMIT 10";
        return $this->select_all($sql);
    }

    public function buscarExistencias($q)
{
    $sql = "SELECT 
              m.idmultialmacen,
              i.cve_articulo,
              i.descripcion,
              a.descripcion AS almacen,
              m.existencia,
              m.stock_minimo,
              m.stock_maximo
            FROM wms_multialmacen m
            INNER JOIN wms_inventario i ON m.inventarioid = i.idinventario
            INNER JOIN wms_almacenes a ON m.almacenid = a.idalmacen
            WHERE i.cve_articulo LIKE '%{$q}%'
               OR i.descripcion LIKE '%{$q}%'
            LIMIT 50";

    return $this->select_all($sql);
}

public function updateStockMinMax($id, $min, $max)
{
    $sql = "UPDATE wms_multialmacen 
            SET stock_minimo = ?, stock_maximo = ?
            WHERE idmultialmacen = ?";

    return $this->update($sql, [$min, $max, $id]);
}

}
