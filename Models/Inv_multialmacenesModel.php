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


    /* =================================================================
     * MATRIZ DE EXISTENCIAS: articulo x almacen (todo el inventario activo)
     * ================================================================= */

    /**
     * Almacenes que forman columnas de la matriz: los activos y cualquier
     * otro que tenga existencia de articulos activos (para no esconderla).
     * existencia/articulos cuentan solo articulos activos, igual que la matriz.
     * planta = prefijo de la clave (PL01, PL02, PL03) u OTROS.
     */
    public function selectAlmacenesMatriz(): array
    {
        $sql = "SELECT a.idalmacen, a.cve_almacen, a.descripcion, a.estado,
                       CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN LEFT(a.cve_almacen, 4) ELSE 'OTROS' END AS planta,
                       COALESCE(x.existencia, 0) AS existencia,
                       COALESCE(x.articulos, 0) AS articulos
                FROM wms_almacenes a
                LEFT JOIN (SELECT m.almacenid, SUM(m.existencia) AS existencia,
                                  SUM(m.existencia <> 0) AS articulos
                           FROM wms_multialmacen m
                           JOIN wms_inventario i ON i.idinventario = m.inventarioid AND i.estado = 2
                           GROUP BY m.almacenid) x ON x.almacenid = a.idalmacen
                WHERE a.estado = 2 OR x.almacenid IS NOT NULL
                ORDER BY planta, a.cve_almacen";
        return $this->select_all($sql);
    }

    /**
     * Pagina de la matriz para DataTables server-side.
     * $p: start, length, search, tipo, solo_existencia (bool),
     *     almacenes (int[] = almacenes del alcance; el total se calcula solo con ellos),
     *     order_key ('cve_articulo' | 'descripcion' | 'tipo_elemento' | 'total' | 'a_<id>'),
     *     order_dir, sin_limite (bool, para exportar).
     */
    public function selectMatrizPaginada(array $p): array
    {
        $ids = array_values(array_unique(array_map('intval', $p['almacenes'] ?? [])));
        $ids = array_values(array_filter($ids, fn($id) => $id > 0));

        // Columnas pivote: existencia y minimo por almacen (ids enteros validados)
        $cols = [];
        foreach ($ids as $id) {
            $cols[] = "SUM(CASE WHEN almacenid = $id THEN existencia END) AS a_$id";
            $cols[] = "MAX(CASE WHEN almacenid = $id THEN stock_minimo END) AS m_$id";
        }
        $inScope  = $ids ? implode(',', $ids) : '0';
        $cols[]   = "SUM(CASE WHEN almacenid IN ($inScope) THEN existencia ELSE 0 END) AS total";
        $pivot    = "SELECT inventarioid, " . implode(",\n ", $cols) . "
                     FROM wms_multialmacen GROUP BY inventarioid";

        $where  = ["i.estado = 2"];
        $params = [];

        if (!empty($p['tipo'])) {
            $where[]  = "i.tipo_elemento = ?";
            $params[] = $p['tipo'];
        }
        if (($p['search'] ?? '') !== '') {
            $like     = '%' . $p['search'] . '%';
            $where[]  = "(i.cve_articulo LIKE ? OR i.descripcion LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($p['solo_existencia'])) {
            $where[] = "COALESCE(x.total, 0) <> 0";
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $from     = "FROM wms_inventario i LEFT JOIN ($pivot) x ON x.inventarioid = i.idinventario";

        $total = $this->select("SELECT COUNT(*) AS total FROM wms_inventario i WHERE i.estado = 2");

        // Resumen del conjunto filtrado (no solo de la pagina)
        $res = $this->select(
            "SELECT COUNT(*) AS articulos,
                    SUM(COALESCE(x.total, 0) <> 0) AS con_existencia,
                    COALESCE(SUM(x.total), 0) AS unidades
             $from $whereSql",
            $params
        );

        // Orden: solo llaves conocidas
        $orderMap = [
            'cve_articulo'  => 'i.cve_articulo',
            'descripcion'   => 'i.descripcion',
            'tipo_elemento' => 'i.tipo_elemento',
            'total'         => 'COALESCE(x.total, 0)',
        ];
        foreach ($ids as $id) {
            $orderMap["a_$id"] = "COALESCE(x.a_$id, 0)";
        }
        $orderExpr = $orderMap[$p['order_key'] ?? ''] ?? 'i.cve_articulo';
        $orderDir  = (($p['order_dir'] ?? '') === 'DESC') ? 'DESC' : 'ASC';

        $limit = '';
        if (empty($p['sin_limite'])) {
            $start  = max(0, (int)($p['start'] ?? 0));
            $length = (int)($p['length'] ?? 25);
            $length = ($length > 0) ? min($length, 500) : 500;
            $limit  = "LIMIT $length OFFSET $start";
        }

        $sel = ["i.idinventario", "i.cve_articulo", "i.descripcion", "i.tipo_elemento", "COALESCE(x.total, 0) AS total"];
        foreach ($ids as $id) {
            $sel[] = "x.a_$id";
            $sel[] = "x.m_$id";
        }

        $data = $this->select_all(
            "SELECT " . implode(', ', $sel) . "
             $from $whereSql
             ORDER BY $orderExpr $orderDir, i.idinventario $orderDir
             $limit",
            $params
        );

        return [
            'recordsTotal'    => (int)($total['total'] ?? 0),
            'recordsFiltered' => (int)($res['articulos'] ?? 0),
            'resumen'         => [
                'articulos'      => (int)($res['articulos'] ?? 0),
                'con_existencia' => (int)($res['con_existencia'] ?? 0),
                'unidades'       => (float)($res['unidades'] ?? 0),
            ],
            'data'            => $data,
        ];
    }

}
