<?php

class Inv_lotespedimentosModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ==========================================
       LISTAR LOTES Y PEDIMENTOS (DataTable)
    ========================================== */
    public function selectLotesPedimentos()
    {
        $sql = "SELECT 
                    l.id_ltpd,
                    i.cve_articulo,
                    i.descripcion,
                    l.lote,
                    l.pedimento,
                    a.descripcion AS almacen,
                    l.nombre_aduana,
                    l.cantidad,
                    l.ciudad,
                    l.frontera,
                    l.gln,
                    l.fecha_caducidad,
                    l.fecha_creacion,
                    l.estado
                FROM wms_ltpd l
                INNER JOIN wms_inventario i ON i.idinventario = l.inventarioid
                LEFT JOIN wms_almacenes a ON a.idalmacen = l.almacenid
                WHERE l.estado != 0";

        return $this->select_all($sql);
    }

    /* ==========================================
       INSERTAR LOTE
    ========================================== */
    public function insertLote(array $data)
    {
        $sql = "INSERT INTO wms_ltpd (
        inventarioid,
        almacenid,
        lote,
        pedimento,
        fecha_produccion_lote,
        fecha_caducidad,
        cantidad,
        ciudad,
        frontera,
        gln,
        nombre_aduana,
        fecha_aduana,
        cve_observacion,
        estado
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $arrData = [
            $data['inventarioid'],
            $data['almacenid'],
            $data['lote'],
            null,                               // pedimento
            $data['fecha_produccion_lote'],
            $data['fecha_caducidad'],
            $data['cantidad'],
            null,                               // ciudad
            null,                               // frontera
            null,                               // gln
            null,                               // nombre_aduana
            null,                               // fecha_aduana
            $data['cve_observacion'],
            $data['estado']
        ];

        return $this->insert($sql, $arrData);
    }


    /* ==========================================
       INSERTAR PEDIMENTO
    ========================================== */
    public function insertPedimento(array $data)
    {
        $sql = "INSERT INTO wms_ltpd (
                inventarioid,
                almacenid,
                pedimento,
                pedimento_SAT,
                fecha_produccion_lote,
                fecha_caducidad,
                fecha_aduana,
                nombre_aduana,
                ciudad,
                frontera,
                gln,
                cantidad,
                cve_observacion,
                estado
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $arrData = [
            $data['inventarioid'],
            $data['almacenid'],
            $data['pedimento'],
            $data['pedimento_SAT'],
            $data['fecha_produccion_lote'],
            $data['fecha_caducidad'],
            $data['fecha_aduana'],
            $data['nombre_aduana'],
            $data['ciudad'],
            $data['frontera'],
            $data['gln'],
            $data['cantidad'],
            $data['cve_observacion'],
            $data['estado']
        ];

        return $this->insert($sql, $arrData);
    }
    /* ==========================================
       SELECT PRODUCTOS POR ALMACÉN
    ========================================== */

    public function selectProductosPorAlmacen($almacenid)
    {
        $sql = "SELECT DISTINCT
                i.idinventario,
                i.cve_articulo,
                i.descripcion,
                i.lote,
                i.pedimiento AS pedimento
            FROM wms_movimientos_inventario m
            INNER JOIN wms_inventario i 
                ON i.idinventario = m.inventarioid
            WHERE m.almacenid = $almacenid
              AND m.estado = 2";

        return $this->select_all($sql);
    }

    /* ==========================================
   OBTENER LOTE / PEDIMENTO POR ID
========================================== */
    public function getLotePedimento(int $id)
{
    $sql = "
        SELECT 
            id_ltpd,
            inventarioid,
            almacenid,
            lote,
            pedimento,
            cantidad,
            fecha_caducidad,
            fecha_aduana,
            fecha_produccion_lote,
            nombre_aduana,
            ciudad,
            frontera,
            gln,
            pedimento_SAT,
            cve_observacion,
            estado
        FROM wms_ltpd
        WHERE id_ltpd = $id
    ";
    return $this->select($sql);
}


    /* ==========================================
   ELIMINAR (LÓGICO) LOTE / PEDIMENTO
========================================== */
    public function delLotePedimento(int $id)
    {
        $sql = "UPDATE wms_ltpd 
            SET estado = 0 
            WHERE id_ltpd = ?";

        $arrData = [$id];

        return $this->update($sql, $arrData);
    }

    public function updateLotePedimento(int $id, array $data)
    {
        $sql = "UPDATE wms_ltpd SET
        almacenid = ?,
        lote = ?,
        pedimento = ?,
        fecha_produccion_lote = ?,
        fecha_caducidad = ?,
        fecha_aduana = ?,
        nombre_aduana = ?,
        ciudad = ?,
        frontera = ?,
        gln = ?,
        cantidad = ?,
        cve_observacion = ?
    WHERE id_ltpd = ?";

        $arrData = [
            $data['almacenid'],
            $data['lote'] ?? null,
            $data['pedimento'] ?? null,
            $data['fecha_produccion_lote'] ?? null,
            $data['fecha_caducidad'] ?? null,
            $data['fecha_aduana'] ?? null,
            $data['nombre_aduana'] ?? null,
            $data['ciudad'] ?? null,
            $data['frontera'] ?? null,
            $data['gln'] ?? null,
            $data['cantidad'],
            $data['cve_observacion'] ?? null,
            $id
        ];

        return $this->update($sql, $arrData);
    }

public function getLtpdAsignados($idinventario)
{
    $idinventario = intval($idinventario);

    $sql = "
        SELECT 
            CASE 
                WHEN l.lote IS NOT NULL AND l.lote <> '' THEN 'L'
                ELSE 'P'
            END AS tipo,
            a.descripcion AS almacen,
            COALESCE(l.lote, l.pedimento) AS clave,
            l.cantidad,
            l.fecha_produccion_lote,
            l.fecha_caducidad,
            l.estado
        FROM wms_ltpd l
        INNER JOIN wms_almacenes a ON a.idalmacen = l.almacenid
        WHERE l.inventarioid = $idinventario
        ORDER BY l.id_ltpd DESC
    ";

    return $this->select_all($sql);
}



}
