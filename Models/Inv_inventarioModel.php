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
                   ca.tipo AS tipo_clave,
                   i.ultimo_costo
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
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            cantidad = VALUES(cantidad),
            porcentaje = VALUES(porcentaje)";

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
            FROM wms_inventario
            WHERE tipo_elemento IN ('P','C','H') 
            AND estado != 0";
        return $this->select_all($sql);
    }

    public function selectKitCompleto(int $inventarioid)
    {
        $sql = "SELECT kc.idkitconfig, kc.precio, kc.descripcion
            FROM wms_kit_config kc
            WHERE kc.inventarioid = $inventarioid
            AND kc.estado = 2
            LIMIT 1";

        $config = $this->select($sql);

        if (empty($config)) return [];

        $kitid = $config['idkitconfig'];

        $sqlDetalle = "SELECT kd.producto_id, kd.cantidad, kd.porcentaje,
                          i.cve_articulo, i.descripcion
                   FROM wms_kit_detalle kd
                   INNER JOIN wms_inventario i 
                        ON i.idinventario = kd.producto_id
                   WHERE kd.idkitconfig = $kitid";

        $detalle = $this->select_all($sqlDetalle);

        return [
            "config" => $config,
            "detalle" => $detalle
        ];
    }


    public function updateKitConfig(int $kitid, float $precio, string $descripcion)
    {
        $sql = "UPDATE wms_kit_config 
            SET precio = ?, descripcion = ?
            WHERE idkitconfig = ?";
        return $this->update($sql, [$precio, $descripcion, $kitid]);
    }

    public function deleteKitDetalleExcepto(int $kitid, array $ids)
{
    $kitid = (int)$kitid;

    if (empty($ids)) {
        return $this->delete("DELETE FROM wms_kit_detalle WHERE idkitconfig = $kitid");
    }

    $ids = array_map('intval', $ids);
    $idsStr = implode(',', $ids);

    $sql = "DELETE FROM wms_kit_detalle
            WHERE idkitconfig = $kitid
            AND producto_id NOT IN ($idsStr)";

    return $this->delete($sql);
}

public function selectKitConfigByInventario(int $inventarioid)
{
    $sql = "SELECT idkitconfig
            FROM wms_kit_config
            WHERE inventarioid = $inventarioid
            AND estado = 2
            LIMIT 1";

    return $this->select($sql);
}

public function insertImagenInventario($inventarioid, $nombre)
{
    $sql = "INSERT INTO wms_fotos_inventario (inventarioid, foto) VALUES (?, ?)";
    $arrData = array($inventarioid, $nombre);
    return $this->insert($sql, $arrData);
}

public function selectImagenesInventario(int $inventarioid)
{
    $sql = "SELECT foto 
            FROM wms_fotos_inventario 
            WHERE inventarioid = $inventarioid";

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

    //--------------------------------------------INVENTARIO PRECIOS
    public function setPrecioInventario(int $inventarioid, int $idprecio)
    {
        $sql = "INSERT INTO wms_inventario_precios (inventarioid,idprecio,estado) 
            VALUES (?,?,2)";

        return $this->insert($sql, [$inventarioid, $idprecio]);
    }

    public function insertInventarioPrecio($inventarioid, $idprecio, $fecha, $estado)
    {
        // evitar duplicados
        $sql = "SELECT * FROM wms_inventario_precios
            WHERE inventarioid = $inventarioid
            AND idprecio = $idprecio";
        $existe = $this->select($sql);

        if (!empty($existe)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_inventario_precios
            (inventarioid, idprecio, fecha_creacion, estado)
            VALUES (?,?,?,?)";

        return $this->insert($sql, [$inventarioid, $idprecio, $fecha, $estado]);
    }

    public function getPreciosAsignados($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT ip.idprecio,
                   p.cve_precio,
                   p.descripcion,
                   ip.estado
            FROM wms_inventario_precios ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.inventarioid = $idinventario";

        return $this->select_all($sql);
    }






    //-------------------------------------------- INVENTARIO LINEA
    public function insertInventarioLinea($inventarioid, $sublineaproductoid, $fecha, $estado)
    {
        $inventarioid = intval($inventarioid);
        $sublineaproductoid = intval($sublineaproductoid);

        $exist = $this->select("
        SELECT id_inv_linea 
        FROM wms_inventario_linea 
        WHERE inventarioid = $inventarioid 
        AND estado = 2
    ");

        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_inventario_linea 
        (inventarioid, sublineaproductoid, fecha_creacion, estado) 
        VALUES ($inventarioid, $sublineaproductoid, '$fecha', $estado)";

        return $this->insert($sql, []);
    }

    public function updateInventarioLinea($id_inv_linea, $sublineaproductoid)
    {
        $sql = "UPDATE wms_inventario_linea 
        SET sublineaproductoid = $sublineaproductoid
        WHERE id_inv_linea = $id_inv_linea";

        return $this->update($sql, []);
    }



    public function selectLineas()
    {
        $sql = "SELECT idlineaproducto AS idlinea, descripcion, estado 
            FROM wms_linea_producto";
        return $this->select_all($sql);
    }

    public function selectSublineas()
    {
        $sql = "SELECT 
        sl.idsublineaproducto,
        sl.descripcion AS sublinea,
        lp.descripcion AS linea
    FROM wms_sublinea_producto sl
    INNER JOIN wms_linea_producto lp 
        ON sl.lineaproductoid = lp.idlineaproducto
    WHERE sl.estado = 2";

        return $this->select_all($sql);
    }

    public function getLineasAsignadas($idinventario)
    {
        $sql = "SELECT 
        il.id_inv_linea,
        sl.idsublineaproducto,
        sl.lineaproductoid AS idlinea, -- 🔥 AGREGA ESTO
        sl.descripcion AS sublinea,
        lp.descripcion AS linea,
        il.fecha_creacion,
        il.estado
    FROM wms_inventario_linea il
    INNER JOIN wms_sublinea_producto sl 
        ON il.sublineaproductoid = sl.idsublineaproducto
    INNER JOIN wms_linea_producto lp 
        ON sl.lineaproductoid = lp.idlineaproducto
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

    public function items(array $filters = []): array
    {
        $query = "SELECT
                    -- data inventario 
                    wms_inventario.idinventario,
                    wms_inventario.cve_articulo,
                    wms_inventario.descripcion,
                    wms_inventario.tipo_elemento,
                    wms_inventario.estado,
                    wms_inventario.ultimo_costo,
                    wms_inventario.unidad_salida,
                    -- data liea de producto
                    wms_linea_producto.descripcion AS descripcion_linea
                FROM wms_inventario
                LEFT JOIN wms_linea_producto
                    ON wms_linea_producto.idlineaproducto = wms_inventario.lineaproductoid
                WHERE true
            ";

        if (array_key_exists('id', $filters)) {
            $query .= "AND wms_inventario.idinventario = '{$filters['id']}'";
        }

        if (array_key_exists('estado', $filters)) {
            $query .= "AND wms_inventario.estado = '{$filters['estado']}'";
        }

        if (array_key_exists('sku', $filters)) {
            $query .= "AND wms_inventario.cve_articulo LIKE '%{$filters['sku']}%'";
        }

        return $this->select_all($query);
    }

    //----------------------------- UBICACIONES INVENTARIO

    public function insertInventarioUbicacion($inventarioid, $ubicacionid, $fecha, $estado)
    {
        $inventarioid = intval($inventarioid);
        $ubicacionid = intval($ubicacionid);

        // 🔹 VALIDAR DUPLICADO
        $exist = $this->select("
        SELECT idubicacionasignada 
        FROM wms_ubicaciones_asignadas
        WHERE inventarioid = $inventarioid 
        AND ubicacionesid = $ubicacionid
    ");

        if (!empty($exist)) {
            return "exist";
        }

        // 🔹 VALIDAR SI ESTÁ OCUPADA
        $ubicacion = $this->select("
        SELECT estado 
        FROM wms_ubicaciones 
        WHERE idubicaciones = $ubicacionid
    ");

        if (!empty($ubicacion) && $ubicacion['estado'] != 2) {
            return "ocupada";
        }

        // 🔹 INSERT
        $sql = "INSERT INTO wms_ubicaciones_asignadas 
        (inventarioid, ubicacionesid, fecha_creacion) 
        VALUES ($inventarioid, $ubicacionid, '$fecha')";

        $insert = $this->insert($sql, []);

        if ($insert > 0) {

            // 🔥 CAMBIAR A OCUPADA
            $sqlUpdate = "UPDATE wms_ubicaciones 
                      SET estado = 1 
                      WHERE idubicaciones = $ubicacionid";

            $this->update($sqlUpdate, []);

            return $insert;
        }

        return 0;
    }


    // 🔹 SELECT PARA DROPDOWN (TEXTO COMPLETO)
    public function selectUbicacionesFull()
    {
        $sql = "SELECT 
        u.idubicaciones,
        CONCAT(
            s.descripcion, ' - ',
            z.descripcion, ' - ',
            'P', u.pasillo, ' N', u.nivel, ' ', u.lugar
        ) AS nombre
    FROM wms_ubicaciones u
    INNER JOIN wms_zonas z ON u.zonaid = z.idzona
    INNER JOIN wms_sedes s ON z.sedeid = s.idsede
    WHERE u.estado = 2
    ORDER BY s.descripcion, z.descripcion";

        return $this->select_all($sql);
    }


    // 🔹 TABLA
    public function getUbicacionesAsignadas($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT 
        ua.idubicacionasignada,
        CONCAT(
            s.descripcion, ' - ',
            z.descripcion, ' - ',
            'P', u.pasillo, ' N', u.nivel, ' ', u.lugar
        ) AS ubicacion,
        ua.fecha_creacion
    FROM wms_ubicaciones_asignadas ua
    INNER JOIN wms_ubicaciones u ON ua.ubicacionesid = u.idubicaciones
    INNER JOIN wms_zonas z ON u.zonaid = z.idzona
    INNER JOIN wms_sedes s ON z.sedeid = s.idsede
    WHERE ua.inventarioid = $idinventario";

        return $this->select_all($sql);
    }
}
