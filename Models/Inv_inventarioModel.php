<?php

class Inv_inventarioModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ===============================
       INSERT
    =============================== */
    public function insertInventario(
        string $cve_articulo,
        string $descripcion,
        string $unidad_entrada,
        string $unidad_salida,
        string $unidad_empaque, // ✅ NUEVO
        int $lineaproductoid,
        string $tipo_elemento,
        float $factor_unidades,
        string $ubicacion,
        int $tiempo_surtido,
        float $peso,
        float $volumen,
        string $serie,
        string $lote,
        string $pedimiento,
        int $estado
    ) {
        // ==== VALIDAR DUPLICADO (SIN ?)
        $cve_articulo = addslashes($cve_articulo);

        $sql = "SELECT idinventario
        FROM wms_inventario
        WHERE cve_articulo = '{$cve_articulo}'";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        // ==== INSERT (CON ?)
        $sql = "INSERT INTO wms_inventario
(
    cve_articulo,
    descripcion,
    unidad_entrada,
    unidad_salida,
    unidad_empaque,      -- ✅
    lineaproductoid,
    tipo_elemento,
    factor_unidades,
    control_almacen,
    tiempo_surtido,
    peso,
    volumen,
    serie,
    lote,
    pedimiento,
    fecha_creacion,
    estado
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)";

        return $this->insert($sql, [
            $cve_articulo,
            $descripcion,
            $unidad_entrada,
            $unidad_salida,
            $unidad_empaque,   // ✅
            $lineaproductoid,
            $tipo_elemento,
            $factor_unidades,
            $ubicacion,
            $tiempo_surtido,
            $peso,
            $volumen,
            $serie,
            $lote,
            $pedimiento,
            $estado
        ]);
    }

    /* ===============================
   WMS MULTIALMACÉN
=============================== */
    public function inicializarMultiAlmacen(
        int $inventarioid,
        int $almacenid,
        float $cantidadInicial
    ): bool {
        $sql = "INSERT INTO wms_multialmacen
            (inventarioid, almacenid, existencia)
            VALUES (?, ?, ?)";
        return $this->insert($sql, [$inventarioid, $almacenid, $cantidadInicial]);
    }




    /* ===============================
       SELECT ALL
    =============================== */
    public function selectInventarios()
    {
        $sql = "SELECT 
                    i.idinventario,
                    i.cve_articulo,
                    i.descripcion,
                    lp.descripcion AS linea,
                    i.tipo_elemento,
                    i.estado,
                    i.ultimo_costo
                FROM wms_inventario i
                LEFT JOIN wms_linea_producto lp 
                    ON i.lineaproductoid = lp.idlineaproducto
                WHERE i.estado != 0";

        return $this->select_all($sql);
    }

    /* ===============================
       SELECT ONE
    =============================== */
    public function selectInventario(int $idinventario)
    {
        $idinventario = (int)$idinventario;

        $sql = "SELECT *
                FROM wms_inventario
                WHERE idinventario = $idinventario";

        return $this->select($sql);
    }

    /* ===============================
       UPDATE
    =============================== */
    public function updateInventario(
        int $idinventario,
        string $cve_articulo,
        string $descripcion,
        string $unidad_entrada,
        string $unidad_salida,
        string $unidad_empaque, // ✅ NUEVO
        int $lineaproductoid,
        string $tipo_elemento,
        float $factor_unidades,
        string $ubicacion,
        int $tiempo_surtido,
        float $peso,
        float $volumen,
        string $serie,
        string $lote,
        string $pedimiento,
        int $estado
    ) {
        // ==== VALIDAR DUPLICADO (SIN ?)
        $idinventario = (int)$idinventario;
        $cve_articulo = addslashes($cve_articulo);

        $sql = "SELECT idinventario
        FROM wms_inventario
        WHERE cve_articulo = '{$cve_articulo}'
          AND idinventario != {$idinventario}";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        // ==== UPDATE (CON ?)
        $sql = "UPDATE wms_inventario SET
    cve_articulo = ?,
    descripcion = ?,
    unidad_entrada = ?,
    unidad_salida = ?,
    unidad_empaque = ?,      -- ✅
    lineaproductoid = ?,
    tipo_elemento = ?,
    factor_unidades = ?,
    control_almacen = ?,
    tiempo_surtido = ?,
    peso = ?,
    volumen = ?,
    serie = ?,
    lote = ?,
    pedimiento = ?,
    estado = ?
WHERE idinventario = ?";


        return $this->update($sql, [
            $cve_articulo,
            $descripcion,
            $unidad_entrada,
            $unidad_salida,
            $unidad_empaque, // ✅
            $lineaproductoid,
            $tipo_elemento,
            $factor_unidades,
            $ubicacion,
            $tiempo_surtido,
            $peso,
            $volumen,
            $serie,
            $lote,
            $pedimiento,
            $estado,
            $idinventario
        ]);
    }

    /* ===============================
       DELETE (LÓGICO)
    =============================== */
    public function deleteInventario(int $idinventario)
    {
        $sql = "UPDATE wms_inventario
                SET estado = 0
                WHERE idinventario = ?";

        return $this->update($sql, [$idinventario]);
    }

    /* ===============================
       CLAVES ALTERNAS
    =============================== */
    public function insertClaveAlterna(
        int $inventarioid,
        string $cve_alterna,
        string $tipo
    ) {
        $inventarioid = (int)$inventarioid;
        $cve_alterna = addslashes($cve_alterna);

        $sql = "SELECT idclavealterna
        FROM wms_claves_alternas
        WHERE inventarioid = {$inventarioid}
          AND cve_alterna = '{$cve_alterna}'";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_claves_alternas
                (inventarioid, cve_alterna, tipo)
                VALUES (?, ?, ?)";

        return $this->insert($sql, [
            $inventarioid,
            $cve_alterna,
            $tipo
        ]);
    }

    /* ===============================
       BUSCADOR KIT
    =============================== */
    public function buscarProductoKit(string $term)
    {
        $term = addslashes($term);

        $sql = "SELECT idinventario, cve_articulo, descripcion
            FROM wms_inventario
            WHERE cve_articulo LIKE '%{$term}%'
               OR descripcion LIKE '%{$term}%'
            LIMIT 20";

        return $this->select_all($sql);
    }


    /* ===============================
       KIT
    =============================== */
    public function insertKitDetalle(
        int $kitid,
        int $productoId,
        float $cantidad,
        float $porcentaje
    ) {
        $sql = "INSERT INTO wms_kit_detalle
                (idkitconfig, producto_id, cantidad, porcentaje)
                VALUES (?, ?, ?, ?)";

        return $this->insert($sql, [
            $kitid,
            $productoId,
            $cantidad,
            $porcentaje
        ]);
    }

    public function insertKitConfig(
        int $inventarioid,
        float $precio,
        string $descripcion
    ) {
        $sql = "INSERT INTO wms_kit_config
                (inventarioid, precio, descripcion, estado, fecha_creacion)
                VALUES (?, ?, ?, 2, NOW())";

        return $this->insert($sql, [
            $inventarioid,
            $precio,
            $descripcion
        ]);
    }

    public function selectInventariosPC_H()
    {
        $sql = "SELECT idinventario, cve_articulo, descripcion 
            FROM inv_inventario 
            WHERE tipo_elemento IN ('P','C','H') 
            AND estado != 0";
        return $this->select_all($sql);
    }
}
