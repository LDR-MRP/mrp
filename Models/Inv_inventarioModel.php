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
        float $ultimo_costo,
        int $lineaproductoid,
        string $tipo_elemento,
        float $factor_unidades,
        string $ubicacion,
        int $tiempo_surtido,
        float $peso,
        float $volumen,
        string $serie,
        string $lote,
        string $pedimiento
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
    ultimo_costo,
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
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),2)";

        return $this->insert($sql, [
            $cve_articulo,
            $descripcion,
            $unidad_entrada,
            $unidad_salida,
            $unidad_empaque,   // ✅
            $ultimo_costo,
            $lineaproductoid,
            $tipo_elemento,
            $factor_unidades,
            $ubicacion,
            $tiempo_surtido,
            $peso,
            $volumen,
            $serie,
            $lote,
            $pedimiento
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

        $sql = "SELECT i.*,
                   ca.cve_alterna,
                   ca.tipo AS tipo_clave
            FROM wms_inventario i
            LEFT JOIN wms_claves_alternas ca 
                   ON ca.inventarioid = i.idinventario
            WHERE i.idinventario = $idinventario";

        return $this->select_all($sql);
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
        float $ultimo_costo,
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
    ultimo_costo = ?,
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
            $ultimo_costo,
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
       IMPUESTOS
    =============================== */
    public function selectImpuestos()
    {
        $sql = "SELECT idimpuesto, cve_impuesto, descripcion
            FROM wms_impuestos
            WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function insertInventarioImpuesto(int $inventarioid, int $idimpuesto)
    {
        $sql = "INSERT INTO wms_inventario_impuestos
            (inventarioid, idimpuesto, estado)
            VALUES (?,?,2)";
        return $this->insert($sql, [$inventarioid, $idimpuesto]);
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

    //--------------------------------------------INVENTARIO MONEDAS
    public function setMonedaInventario(int $inventarioid, int $idmoneda)
    {
        $sql = "INSERT INTO wms_inventario_moneda (inventarioid,idmoneda,estado) 
            VALUES (?,?,2)";

        return $this->insert($sql, [$inventarioid, $idmoneda]);
    }

    public function insertInventarioMoneda($inventarioid, $idmoneda, $tipo, $fecha, $estado)
    {
        $sql = "INSERT INTO wms_inventario_moneda
            (inventarioid,idmoneda,tipo_cambio,fecha_creacion,estado)
            VALUES (?,?,?,?,?)";

        $arrData = [$inventarioid, $idmoneda, $tipo, $fecha, $estado];
        return $this->insert($sql, $arrData);
    }
    public function getMonedasAsignadas($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT im.idmoneda, COALESCE(m.descripcion, 'Sin descripción') as descripcion, im.tipo_cambio, im.estado
            FROM wms_inventario_moneda im
            LEFT JOIN wms_moneda m ON m.idmoneda = im.idmoneda
            WHERE im.inventarioid = $idinventario";

        return $this->select_all($sql);
    }





    //-------------------------------------------- INVENTARIO LINEA
    public function insertInventarioLinea($inventarioid, $idlineaproducto, $fecha, $estado)
    {
        // Convertimos a enteros los valores numéricos para seguridad
        $inventarioid = intval($inventarioid);
        $idlineaproducto = intval($idlineaproducto);
        $estado = intval($estado);

        // Escapamos solo el string de fecha manualmente
        $fecha = addslashes($fecha); // evita errores de comillas simples

        // Verificar si ya existe
        $exist = $this->select(
            "SELECT * FROM wms_inventario_linea WHERE inventarioid = $inventarioid AND estado = $estado"
        );

        // INSERT concatenando valores
        $sql = "INSERT INTO wms_inventario_linea (inventarioid, idlineaproducto, fecha_creacion, estado) 
            VALUES ($inventarioid, $idlineaproducto, '$fecha', $estado)";
        return $this->insert($sql, []); // tu core espera 2 parámetros
    }

    public function selectLineas()
    {
        $sql = "SELECT idlineaproducto AS idlinea, descripcion, estado 
            FROM wms_linea_producto";
        return $this->select_all($sql);
    }

    public function getLineasAsignadas($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT l.idlineaproducto AS idlinea, l.descripcion, il.fecha_creacion, il.estado
            FROM wms_linea_producto l
            INNER JOIN wms_inventario_linea il ON l.idlineaproducto = il.idlineaproducto
            WHERE il.inventarioid = $idinventario";

        return $this->select_all($sql);
    }


    //-------------------------------------------------Datos fiscales SAT
    // ===================== FISCAL =====================

    public function getFiscalByInventario(int $inventarioid)
    {
        $sql = "SELECT * FROM wms_inventario_fiscal 
            WHERE inventarioid = {$inventarioid}";
        return $this->select($sql);
    }


    public function insertFiscal($data)
    {
        $sql = "INSERT INTO wms_inventario_fiscal
    (inventarioid, clave_sat, desc_sat, 
     clave_unidad_sat, desc_unidad_sat,
     clave_fraccion_sat, desc_fraccion_sat,
     clave_aduana_sat, desc_aduana_sat, estado)
    VALUES (?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['inventarioid'],
            $data['clave_sat'],
            $data['desc_sat'],
            $data['clave_unidad_sat'],
            $data['desc_unidad_sat'],
            $data['clave_fraccion_sat'],
            $data['desc_fraccion_sat'],
            $data['clave_aduana_sat'],
            $data['desc_aduana_sat'],
            2
        ]);
    }



    public function updateFiscal($idfiscal, $data)
    {
        $sql = "UPDATE wms_inventario_fiscal SET
        clave_sat = ?,
        desc_sat = ?,
        clave_unidad_sat = ?,
        desc_unidad_sat = ?,
        clave_fraccion_sat = ?,
        desc_fraccion_sat = ?,
        clave_aduana_sat = ?,
        desc_aduana_sat = ?
        WHERE idfiscal = ?";

        return $this->update($sql, [
            $data['clave_sat'],
            $data['desc_sat'],
            $data['clave_unidad_sat'],
            $data['desc_unidad_sat'],
            $data['clave_fraccion_sat'],
            $data['desc_fraccion_sat'],
            $data['clave_aduana_sat'],
            $data['desc_aduana_sat'],
            $idfiscal
        ]);
    }

    public function updateFiscalParcial($idfiscal, $data)
    {
        if (empty($data)) return false;

        $set = [];
        $values = [];

        foreach ($data as $k => $v) {
            $set[] = "$k = ?";
            $values[] = $v;
        }

        $values[] = $idfiscal;

        $sql = "UPDATE wms_inventario_fiscal SET " . implode(',', $set) . " WHERE idfiscal = ?";

        return $this->update($sql, $values);
    }

    // ================= IMPUESTOS =================

    public function selectImpuestosCfg()
    {
        $sql = "SELECT idimpuesto, descripcion 
            FROM wms_impuestos 
            WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function insertInventarioImpuestoform($inventarioid, $idimpuesto, $estado)
{
    $inventarioid = intval($inventarioid);
    $idimpuesto   = intval($idimpuesto);

    // ✅ verificar si ya existe
    $sqlCheck = "SELECT idinvimpuesto 
                 FROM wms_inventario_impuestos 
                 WHERE inventarioid = $inventarioid 
                   AND idimpuesto = $idimpuesto";

    $exist = $this->select($sqlCheck);

    if (!empty($exist)) {
        return "exist";
    }

    // ✅ insertar
    $sql = "INSERT INTO wms_inventario_impuestos
            (inventarioid,idimpuesto,estado)
            VALUES (?,?,?)";

    return $this->insert($sql, [
        $inventarioid,
        $idimpuesto,
        $estado
    ]);
}


    public function getImpuestosAsignados($idinventario)
{
    $idinventario = intval($idinventario);

    $sql = "SELECT i.idimpuesto,
                   i.descripcion,
                   ii.estado
            FROM wms_impuestos i
            INNER JOIN wms_inventario_impuestos ii 
              ON i.idimpuesto = ii.idimpuesto
            WHERE ii.inventarioid = $idinventario";

    return $this->select_all($sql);
}


}
