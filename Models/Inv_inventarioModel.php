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
        // Validar duplicado
        $sql = "SELECT idinventario FROM wms_inventario WHERE cve_articulo = ?";
        $request = $this->select($sql, [$cve_articulo]);


        if (!empty($request)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_inventario
            (cve_articulo, descripcion, unidad_entrada, unidad_salida, lineaproductoid, tipo_elemento,
               factor_unidades, control_almacen, tiempo_surtido, peso, volumen, serie, lote, pedimiento, fecha_creacion, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)";

        return $this->insert($sql, [
            $cve_articulo,
            $descripcion,
            $unidad_entrada,
            $unidad_salida,
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
       SELECT ALL (DataTable)
    =============================== */
    public function selectInventarios()
    {
        $sql = "SELECT 
                    i.idinventario,
                    i.cve_articulo,
                    i.descripcion,
                    lp.descripcion AS linea,
                    i.tipo_elemento,
                    i.estado
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
        $sql = "SELECT * FROM wms_inventario WHERE idinventario = ?";
        return $this->select($sql, [$idinventario]);
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
        // Validar duplicado
        $sql = "SELECT idinventario FROM wms_inventario 
                WHERE cve_articulo = ? AND idinventario != ?";
        $request = $this->select($sql, [$cve_articulo, $idinventario]);

        if (!empty($request)) {
            return "exist";
        }

        $sql = "UPDATE wms_inventario SET
                    cve_articulo = ?,
                    descripcion = ?,
                    unidad_entrada = ?,
                    unidad_salida = ?,
                    lineaproductoid = ?,
                    tipo_elemento = ?,
                    factor_unidades = ?,
					ubicacion = ?,
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
       DELETE (lógico)
    =============================== */
    public function deleteInventario(int $idinventario)
    {
        $sql = "UPDATE wms_inventario SET estado = 0 WHERE idinventario = ?";
        return $this->update($sql, [$idinventario]);
    }

    /* ===============================
   INSERT CLAVE ALTERNA
=============================== */
    public function insertClaveAlterna(
        int $inventarioid,
        string $cve_alterna,
        string $tipo
    ) {
        // Evitar duplicados
        $sql = "SELECT idclavealterna
            FROM wms_claves_alternas
            WHERE inventarioid = ?
              AND cve_alterna = ?";
        $request = $this->select($sql, [$inventarioid, $cve_alterna]);

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

    public function buscarProductoKit(string $term)
    {

        $term = addslashes($term);

        $sql = "SELECT idinventario, cve_articulo, descripcion
        FROM wms_inventario
        WHERE cve_articulo LIKE '%{$term}%'
           OR descripcion LIKE '%{$term}%'
        LIMIT 20
    ";

        return $this->select_all($sql);
    }

    public function insertKitDetalle(
        int $kitid,
        int $productoId,
        float $cantidad,
        float $porcentaje
    ) {
        $sql = "INSERT INTO wms_kit_detalle
        (idkitconfig, producto_id, cantidad, porcentaje)
        VALUES (?, ?, ?, ?)
    ";

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


    public function insertMovimientoInventario(array $data)
    {
        $sql = "INSERT INTO wms_movimientos_inventario
        (
            inventarioid,
            almacenid,
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $arrData = [
            $data['inventarioid'],
            $data['almacenid'],
            $data['concepmovid'],
            $data['referencia'],
            $data['cantidad'],
            $data['costo_cantidad'],
            $data['precio'],
            $data['costo'],
            $data['existencia'],
            $data['signo'],
            $data['estado']
        ];

        return $this->insert($sql, $arrData);
    }
}
