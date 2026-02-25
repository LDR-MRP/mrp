<?php

class Inv_reportesModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getProductosEtiquetas($data)
{
    $where = [];

    // 🔹 Rango de claves
    if($data['desde'] != "" && $data['hasta'] != ""){
        $where[] = "i.cve_articulo BETWEEN '{$data['desde']}' 
                    AND '{$data['hasta']}'";
    }

    // 🔹 Filtro por almacén (sin multiplicar registros)
    if(!empty($data['almacen'])){
        $where[] = "EXISTS (
                        SELECT 1 
                        FROM wms_multialmacen ma
                        WHERE ma.inventarioid = i.idinventario
                        AND ma.almacenid = {$data['almacen']}
                    )";
    }

    // 🔹 Filtro por lote
    if(!empty($data['lote'])){
        $where[] = "EXISTS (
                        SELECT 1
                        FROM wms_ltpd l
                        WHERE l.inventarioid = i.idinventario
                        AND l.lote = '{$data['lote']}'
                    )";
    }

    // 🔹 Filtro por pedimento
    if(!empty($data['pedimento'])){
        $where[] = "EXISTS (
                        SELECT 1
                        FROM wms_ltpd l
                        WHERE l.inventarioid = i.idinventario
                        AND l.pedimento = '{$data['pedimento']}'
                    )";
    }

    // 🔹 Filtro por línea
    if(!empty($data['linea'])){
        $where[] = "i.lineaproductoid = {$data['linea']}";
    }

    $whereSql = "";
    if(count($where) > 0){
        $whereSql = "WHERE " . implode(" AND ", $where);
    }

    $sql = "SELECT 
            i.idinventario,
            i.cve_articulo,
            i.descripcion,
            lp.descripcion AS linea
        FROM wms_inventario i
        LEFT JOIN wms_linea_producto lp 
            ON lp.idlineaproducto = i.lineaproductoid
        $whereSql
        ORDER BY i.cve_articulo ASC
    ";

    return $this->select_all($sql);
}

}
