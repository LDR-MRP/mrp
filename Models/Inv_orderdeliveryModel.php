<?php
/**
 * ORDER DELIVERY PLAN (unidades en tránsito).
 *
 * Tablas: wms_odp_cargas (historial de archivos) / wms_odp (renglones).
 * Migración: Docs/desarrollo/order_delivery/migracion_order_delivery_plan_20261112.sql
 *
 * Las cargas son INCREMENTALES: cada archivo agrega sus renglones a los que ya
 * hay (no borra nada). Cada renglón guarda el MES en que se subió (mes_carga =
 * día 1 del mes en curso) para poder segmentar. Para deshacer una carga
 * equivocada se elimina la carga completa.
 *
 * Cada renglón se liga a un producto (wms_inventario tipo P) y al almacén de
 * tránsito (ALM-TRANSITO). NO mueve inventario: no toca wms_multialmacen ni
 * wms_movimientos_inventario.
 */
class Inv_orderdeliveryModel extends Mysql
{
    public const CVE_ALMACEN_TRANSITO = 'ALM-TRANSITO';

    /** Columnas que se insertan (sin cargaid). */
    public const COLUMNAS = [
        'mes_carga', 'inventarioid', 'almacenid', 'sku', 'nombre_comercial',
        'contrato', 'modelo', 'qty', 'eta_pod', 'eta_pod_txt', 'status', 'fila_archivo',
    ];

    /** Columnas de la caja de búsqueda del listado. */
    private const BUSQUEDA = ['r.contrato', 'r.modelo', 'r.sku', 'r.nombre_comercial', 'r.status'];

    /** Nombre comercial de Ingeniería por producto (si no hay, la descripción del inventario). */
    private const JOIN_NOMBRE = "LEFT JOIN (SELECT id_inventario, MIN(nombre_comercial) AS nombre_comercial
                                          FROM ing_modelo_configuracion
                                          WHERE deleted_at IS NULL AND id_inventario IS NOT NULL
                                            AND nombre_comercial IS NOT NULL AND TRIM(nombre_comercial) <> ''
                                          GROUP BY id_inventario) ing ON ing.id_inventario = i.idinventario";

    public function __construct()
    {
        parent::__construct();
    }

    public function tablasListas(): bool
    {
        $row = $this->select(
            "SELECT COUNT(*) AS n FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN ('wms_odp', 'wms_odp_cargas')"
        );
        return (int) ($row['n'] ?? 0) === 2;
    }

    /** Almacén de tránsito (destino por defecto de cada renglón). */
    public function selectAlmacenTransito(): ?array
    {
        $r = $this->select(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes
             WHERE UPPER(TRIM(cve_almacen)) = ? AND estado <> 0
             ORDER BY idalmacen LIMIT 1",
            [self::CVE_ALMACEN_TRANSITO]
        );
        return $r ?: null;
    }

    /**
     * Catálogo de productos que se pueden elegir: wms_inventario tipo P activos,
     * con el nombre comercial de Ingeniería (o la descripción si no tiene).
     */
    public function selectProductos(): array
    {
        return $this->select_all(
            "SELECT i.idinventario,
                    TRIM(i.cve_articulo) AS sku,
                    TRIM(COALESCE(ing.nombre_comercial, i.descripcion)) AS nombre_comercial,
                    i.descripcion,
                    CASE WHEN ing.id_inventario IS NULL THEN 0 ELSE 1 END AS con_ingenieria
             FROM wms_inventario i
             " . self::JOIN_NOMBRE . "
             WHERE i.tipo_elemento = 'P' AND i.estado = '2'
             ORDER BY con_ingenieria DESC, nombre_comercial, sku"
        );
    }

    /** [SKU EN MAYÚSCULAS => producto] para resolver la columna PRODUCTO del archivo. */
    public function mapProductos(): array
    {
        $map = [];
        foreach ($this->selectProductos() as $p) {
            $map[mb_strtoupper($p['sku'])] = $p;
        }
        return $map;
    }

    /* ===============================================================
     * CARGA
     * =============================================================== */

    /**
     * Guarda una carga completa (todo o nada). Incremental: no borra nada.
     *
     * @return array ['idcarga','folio']
     */
    public function insertCarga(array $cab, array $filas, string $mes): array
    {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $unidades = array_sum(array_column($filas, 'qty'));
            $idcarga = (int) $this->insert(
                "INSERT INTO wms_odp_cargas
                    (folio, mes_carga, archivo, hoja, total_filas, total_cargadas, total_omitidas, total_unidades,
                     observaciones, usuarioid, estado)
                 VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 2)",
                [
                    'TMP-' . substr(uniqid(), -12), $mes, $cab['archivo'], $cab['hoja'], $cab['total_filas'],
                    $cab['total_omitidas'], $unidades, $cab['observaciones'], $cab['usuarioid'],
                ]
            );
            $folio = 'ODP-' . str_pad((string) $idcarga, 6, '0', STR_PAD_LEFT);

            $cols = array_merge(['cargaid'], self::COLUMNAS);
            $tupla = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            foreach (array_chunk($filas, 300) as $bloque) {
                $params = [];
                foreach ($bloque as $f) {
                    $params[] = $idcarga;
                    foreach (self::COLUMNAS as $col) {
                        $params[] = $col === 'mes_carga' ? $mes : ($f[$col] ?? null);
                    }
                }
                $db->prepare('INSERT INTO wms_odp (' . implode(',', $cols) . ') VALUES '
                    . implode(',', array_fill(0, count($bloque), $tupla)))->execute($params);
            }

            $this->update(
                "UPDATE wms_odp_cargas SET folio = ?, total_cargadas = ? WHERE idcarga = ?",
                [$folio, count($filas), $idcarga]
            );
            $db->commit();
            return ['idcarga' => $idcarga, 'folio' => $folio];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function selectCargas(): array
    {
        return $this->select_all(
            "SELECT c.idcarga,
                    c.folio,
                    DATE_FORMAT(c.mes_carga, '%Y-%m') AS mes,
                    COALESCE(c.archivo, '') AS archivo,
                    c.total_filas,
                    c.total_cargadas,
                    c.total_omitidas,
                    c.total_unidades,
                    c.filas_reemplazadas,
                    COALESCE(c.observaciones, '') AS observaciones,
                    DATE_FORMAT(c.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
                    TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario,
                    c.estado,
                    COALESCE(v.vigentes, 0) AS vigentes
             FROM wms_odp_cargas c
             LEFT JOIN usuarios u ON u.idusuario = c.usuarioid
             LEFT JOIN (SELECT cargaid, COUNT(*) AS vigentes FROM wms_odp GROUP BY cargaid) v
                    ON v.cargaid = c.idcarga
             ORDER BY c.idcarga DESC"
        );
    }

    public function selectCarga(int $idcarga): ?array
    {
        $row = $this->select("SELECT idcarga, folio, estado FROM wms_odp_cargas WHERE idcarga = ?", [$idcarga]);
        return $row ?: null;
    }

    /** Borra los renglones vigentes de esa carga y la marca como eliminada. */
    public function eliminarCarga(int $idcarga): int
    {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $st = $db->prepare("DELETE FROM wms_odp WHERE cargaid = ?");
            $st->execute([$idcarga]);
            $borrados = $st->rowCount();
            $this->update("UPDATE wms_odp_cargas SET estado = 0 WHERE idcarga = ?", [$idcarga]);
            $db->commit();
            return $borrados;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /* ===============================================================
     * CONSULTAS (siempre de UN mes: cada mes es una foto completa del plan)
     * =============================================================== */

    /** Meses con datos, del más reciente al más antiguo ('YYYY-MM'). */
    public function selectMeses(): array
    {
        return array_column($this->select_all(
            "SELECT DISTINCT DATE_FORMAT(mes_carga, '%Y-%m') AS v FROM wms_odp ORDER BY v DESC"
        ), 'v');
    }

    /**
     * Renglones ya cargados con la misma llave (producto | contrato | QTY | ETA) para avisar
     * de posibles duplicados (p. ej. el mismo archivo subido dos veces). [llave => folio]
     */
    public function selectLlavesExistentes(): array
    {
        $out = [];
        foreach ($this->select_all(
            "SELECT r.inventarioid, COALESCE(r.contrato, '') AS contrato, r.qty,
                    COALESCE(DATE_FORMAT(r.eta_pod, '%Y-%m-%d'), r.eta_pod_txt, '') AS eta, MIN(c.folio) AS folio
             FROM wms_odp r LEFT JOIN wms_odp_cargas c ON c.idcarga = r.cargaid
             GROUP BY r.inventarioid, contrato, r.qty, eta"
        ) as $r) {
            $out[$r['inventarioid'] . '|' . mb_strtoupper($r['contrato']) . '|' . $r['qty'] . '|' . mb_strtoupper($r['eta'])] = $r['folio'];
        }
        return $out;
    }

    /** Valores para los filtros ('' = todos los meses). */
    public function selectCatalogosFiltro(string $mes): array
    {
        $w = $mes !== '' ? 'mes_carga = ?' : '1=1';
        $p = $mes !== '' ? [$mes . '-01'] : [];
        $col = function (string $campo) use ($w, $p) {
            return array_column($this->select_all(
                "SELECT DISTINCT $campo AS v FROM wms_odp WHERE $w AND $campo IS NOT NULL AND $campo <> '' ORDER BY v",
                $p
            ), 'v');
        };
        return [
            'status' => $col('status'),
            'productos' => $this->select_all(
                "SELECT inventarioid AS id, MAX(sku) AS sku, MAX(nombre_comercial) AS nombre
                 FROM wms_odp WHERE $w GROUP BY inventarioid ORDER BY nombre, sku",
                $p
            ),
        ];
    }

    /** $f: mes (YYYY-MM o '' = todos), status, inventarioid, search */
    private function construirWhere(array $f, array &$params): string
    {
        $w = ['1=1'];
        if (($f['mes'] ?? '') !== '') {
            $w[] = 'r.mes_carga = ?';
            $params[] = $f['mes'] . '-01';
        }
        if (($f['status'] ?? '') !== '') {
            if ($f['status'] === '__vacio__') {
                $w[] = "(r.status IS NULL OR r.status = '')";
            } else {
                $w[] = 'r.status = ?';
                $params[] = $f['status'];
            }
        }
        if (!empty($f['inventarioid'])) {
            $w[] = 'r.inventarioid = ?';
            $params[] = (int) $f['inventarioid'];
        }
        if (($f['search'] ?? '') !== '') {
            $or = [];
            foreach (self::BUSQUEDA as $col) {
                $or[] = "$col LIKE ?";
                $params[] = '%' . $f['search'] . '%';
            }
            $w[] = '(' . implode(' OR ', $or) . ')';
        }
        return implode(' AND ', $w);
    }

    private function selectBase(): string
    {
        return "
        SELECT r.idodp AS id,
               DATE_FORMAT(r.mes_carga, '%Y-%m') AS mes,
               r.inventarioid, r.sku, r.nombre_comercial, r.contrato, r.modelo, r.qty,
               COALESCE(DATE_FORMAT(r.eta_pod, '%Y-%m-%d'), r.eta_pod_txt) AS eta_pod,
               r.status, r.fila_archivo,
               a.cve_almacen AS almacen,
               c.folio AS folio_carga
        FROM wms_odp r
        LEFT JOIN wms_almacenes a ON a.idalmacen = r.almacenid
        LEFT JOIN wms_odp_cargas c ON c.idcarga = r.cargaid";
    }

    /** Listado paginado (DataTables serverSide). $f['order_col'] viene de lista blanca. */
    public function selectPaginado(array $f): array
    {
        $total = (int) ($this->select("SELECT COUNT(*) AS n FROM wms_odp")['n'] ?? 0);
        $params = [];
        $where = $this->construirWhere($f, $params);
        $filtrados = (int) ($this->select("SELECT COUNT(*) AS n FROM wms_odp r WHERE $where", $params)['n'] ?? 0);

        $start = max(0, (int) $f['start']);
        $length = (int) $f['length'];
        $limit = $length > 0 ? ' LIMIT ' . $start . ', ' . min($length, 500) : ' LIMIT 500';
        $rows = $this->select_all(
            $this->selectBase() . " WHERE $where ORDER BY {$f['order_col']} {$f['order_dir']}, r.idodp" . $limit,
            $params
        );
        return ['recordsTotal' => $total, 'recordsFiltered' => $filtrados, 'data' => $rows];
    }

    /** Todos los renglones filtrados, en el orden del archivo (exportación). */
    public function selectExport(array $f): array
    {
        $params = [];
        $where = $this->construirWhere($f, $params);
        return $this->select_all($this->selectBase() . " WHERE $where ORDER BY r.idodp", $params);
    }

    public function selectKpis(array $f): array
    {
        $params = [];
        $where = $this->construirWhere($f, $params);
        $hoy = date('Y-m-d');
        $params2 = array_merge([$hoy, $hoy], $params);
        $r = $this->select(
            "SELECT COUNT(*) AS renglones,
                    COALESCE(SUM(r.qty), 0) AS unidades,
                    COUNT(DISTINCT NULLIF(r.contrato, '')) AS contratos,
                    COUNT(DISTINCT r.inventarioid) AS productos,
                    COALESCE(SUM(CASE WHEN r.eta_pod IS NOT NULL THEN r.qty END), 0) AS con_eta,
                    COALESCE(SUM(CASE WHEN r.eta_pod IS NOT NULL AND r.eta_pod >= ? THEN r.qty END), 0) AS eta_futura,
                    DATE_FORMAT(MIN(CASE WHEN r.eta_pod >= ? THEN r.eta_pod END), '%Y-%m-%d') AS proxima_eta
             FROM wms_odp r WHERE $where",
            $params2
        );
        return [
            'renglones' => (int) ($r['renglones'] ?? 0),
            'unidades' => (int) ($r['unidades'] ?? 0),
            'contratos' => (int) ($r['contratos'] ?? 0),
            'productos' => (int) ($r['productos'] ?? 0),
            'con_eta' => (int) ($r['con_eta'] ?? 0),
            'eta_futura' => (int) ($r['eta_futura'] ?? 0),
            'proxima_eta' => $r['proxima_eta'] ?? '',
        ];
    }

    /**
     * Unidades por producto y status del mes (para la matriz de resumen).
     * Regresa ['status' => [...], 'filas' => [['sku','nombre','contratos','total','proxima_eta','por_status'=>[status=>n]]]]
     */
    public function selectResumen(array $f): array
    {
        $params = [];
        $where = $this->construirWhere($f, $params);
        $rows = $this->select_all(
            "SELECT r.inventarioid, MAX(r.sku) AS sku, MAX(r.nombre_comercial) AS nombre,
                    COALESCE(NULLIF(r.status, ''), '(sin status)') AS status,
                    SUM(r.qty) AS n
             FROM wms_odp r WHERE $where
             GROUP BY r.inventarioid, status",
            $params
        );
        $extra = $this->select_all(
            "SELECT r.inventarioid, COUNT(DISTINCT NULLIF(r.contrato, '')) AS contratos,
                    DATE_FORMAT(MIN(r.eta_pod), '%Y-%m-%d') AS primera_eta
             FROM wms_odp r WHERE $where GROUP BY r.inventarioid",
            $params
        );
        $ext = [];
        foreach ($extra as $e) {
            $ext[$e['inventarioid']] = $e;
        }
        $status = [];
        $filas = [];
        foreach ($rows as $r) {
            $id = $r['inventarioid'];
            if (!isset($filas[$id])) {
                $filas[$id] = [
                    'sku' => $r['sku'], 'nombre' => $r['nombre'],
                    'contratos' => (int) ($ext[$id]['contratos'] ?? 0),
                    'primera_eta' => $ext[$id]['primera_eta'] ?? null,
                    'total' => 0, 'por_status' => [],
                ];
            }
            $filas[$id]['por_status'][$r['status']] = (int) $r['n'];
            $filas[$id]['total'] += (int) $r['n'];
            $status[$r['status']] = ($status[$r['status']] ?? 0) + (int) $r['n'];
        }
        arsort($status);
        $filas = array_values($filas);
        usort($filas, function ($a, $b) {
            return $b['total'] <=> $a['total'] ?: strcmp((string) $a['nombre'], (string) $b['nombre']);
        });
        return ['status' => array_keys($status), 'filas' => $filas];
    }
}
