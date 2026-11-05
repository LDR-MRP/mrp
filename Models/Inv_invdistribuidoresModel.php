<?php
/**
 * RETAIL DE DISTRIBUIDORES.
 * Tablas: wms_dist_retail_cargas / wms_dist_retail
 * (ver Docs/desarrollo/inventario_distribuidores/migracion_retail_distribuidores_20261104.sql).
 *
 * Datos tal cual los manda el área (hoja "Retail"): NO se relacionan con
 * wms_numeros_series, wms_inventario ni cli_clientes.
 */
class Inv_invdistribuidoresModel extends Mysql
{
    /** Columnas de wms_dist_retail que llegan del archivo (orden del INSERT). */
    public const COLUMNAS = [
        'mes_retail', 'distribuidor', 'vin', 'modelo', 'anio_modelo', 'tipo', 'estatus', 'observaciones',
        'fecha_facturacion', 'fecha_facturacion_txt', 'folio_fiscal', 'razon_social', 'rfc', 'direccion',
        'codigo_postal', 'telefono', 'email', 'tipo_pago', 'institucion', 'enganche', 'plazo', 'vendedor',
        'modelo_gen', 'motor', 'fila_archivo',
    ];

    /** Columnas por las que busca la caja de búsqueda del listado. */
    private const COLUMNAS_BUSQUEDA = ['r.vin', 'r.distribuidor', 'r.modelo', 'r.modelo_gen', 'r.razon_social',
        'r.rfc', 'r.folio_fiscal', 'r.vendedor', 'r.estatus', 'r.motor'];

    public function __construct()
    {
        parent::__construct();
    }

    public function tablasListas(): bool
    {
        $row = $this->select(
            "SELECT COUNT(*) AS n FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN ('wms_dist_retail', 'wms_dist_retail_cargas')"
        );
        return (int) ($row['n'] ?? 0) === 2;
    }

    /* ===============================================================
     * CARGA
     * =============================================================== */

    /**
     * Guarda una carga completa (todo o nada). Antes de insertar BORRA los
     * renglones existentes de los meses que trae el archivo, así el archivo
     * acumulado que mandan cada mes reemplaza esos meses sin duplicar.
     *
     * @param array $filas  renglones con las llaves de self::COLUMNAS
     * @param array $meses  ['2025-01-01', ...]
     * @return array ['idcarga','folio','reemplazadas']
     */
    public function insertCarga(array $cab, array $filas, array $meses): array
    {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            sort($meses);
            $idcarga = (int) $this->insert(
                "INSERT INTO wms_dist_retail_cargas
                    (folio, archivo, hoja, total_filas, total_cargadas, total_omitidas, mes_desde, mes_hasta,
                     meses, observaciones, usuarioid, estado)
                 VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 2)",
                [
                    'TMP-' . uniqid('', true), $cab['archivo'], $cab['hoja'], $cab['total_filas'], $cab['total_omitidas'],
                    $meses ? reset($meses) : null, $meses ? end($meses) : null,
                    implode(',', array_map(function ($m) { return substr($m, 0, 7); }, $meses)),
                    $cab['observaciones'], $cab['usuarioid'],
                ]
            );
            $folio = 'RTD-' . str_pad((string) $idcarga, 6, '0', STR_PAD_LEFT);

            // 1) Reemplazo de meses
            $reemplazadas = 0;
            foreach (array_chunk($meses, 200) as $bloque) {
                $ph = implode(',', array_fill(0, count($bloque), '?'));
                $st = $db->prepare("DELETE FROM wms_dist_retail WHERE mes_retail IN ($ph)");
                $st->execute($bloque);
                $reemplazadas += $st->rowCount();
            }

            // 2) Inserción multi-renglón (300 por sentencia)
            $cols = array_merge(['cargaid'], self::COLUMNAS);
            $tupla = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            foreach (array_chunk($filas, 300) as $bloque) {
                $params = [];
                foreach ($bloque as $f) {
                    $params[] = $idcarga;
                    foreach (self::COLUMNAS as $c) {
                        $params[] = $f[$c] ?? null;
                    }
                }
                $sql = 'INSERT INTO wms_dist_retail (' . implode(',', $cols) . ') VALUES '
                    . implode(',', array_fill(0, count($bloque), $tupla));
                $db->prepare($sql)->execute($params);
            }

            $this->update(
                "UPDATE wms_dist_retail_cargas SET folio = ?, total_cargadas = ?, filas_reemplazadas = ? WHERE idcarga = ?",
                [$folio, count($filas), $reemplazadas, $idcarga]
            );
            $db->commit();
            return ['idcarga' => $idcarga, 'folio' => $folio, 'reemplazadas' => $reemplazadas];
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
                    COALESCE(c.archivo, '') AS archivo,
                    c.total_filas,
                    c.total_cargadas,
                    c.total_omitidas,
                    c.filas_reemplazadas,
                    DATE_FORMAT(c.mes_desde, '%Y-%m') AS mes_desde,
                    DATE_FORMAT(c.mes_hasta, '%Y-%m') AS mes_hasta,
                    COALESCE(c.observaciones, '') AS observaciones,
                    DATE_FORMAT(c.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
                    TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario,
                    c.estado,
                    COALESCE(v.vigentes, 0) AS vigentes
             FROM wms_dist_retail_cargas c
             LEFT JOIN usuarios u ON u.idusuario = c.usuarioid
             LEFT JOIN (SELECT cargaid, COUNT(*) AS vigentes FROM wms_dist_retail GROUP BY cargaid) v
                    ON v.cargaid = c.idcarga
             ORDER BY c.idcarga DESC"
        );
    }

    public function selectCarga(int $idcarga): ?array
    {
        $row = $this->select("SELECT idcarga, folio, estado FROM wms_dist_retail_cargas WHERE idcarga = ?", [$idcarga]);
        return $row ?: null;
    }

    /** Borra los renglones que siguen vigentes de esa carga y la marca como eliminada. */
    public function eliminarCarga(int $idcarga): int
    {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $st = $db->prepare("DELETE FROM wms_dist_retail WHERE cargaid = ?");
            $st->execute([$idcarga]);
            $borrados = $st->rowCount();
            $this->update("UPDATE wms_dist_retail_cargas SET estado = 0 WHERE idcarga = ?", [$idcarga]);
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
     * CONSULTAS
     * =============================================================== */

    /** Valores distintos para llenar los filtros. */
    public function selectCatalogosFiltro(): array
    {
        $col = function (string $campo) {
            return array_column($this->select_all(
                "SELECT DISTINCT $campo AS v FROM wms_dist_retail WHERE $campo IS NOT NULL AND $campo <> '' ORDER BY v"
            ), 'v');
        };
        return [
            'distribuidores' => $col('distribuidor'),
            'modelos' => $col('modelo_gen'),
            'estatus' => $col('estatus'),
            'tipos_pago' => $col('tipo_pago'),
            'meses' => array_column($this->select_all(
                "SELECT DISTINCT DATE_FORMAT(mes_retail, '%Y-%m') AS v FROM wms_dist_retail ORDER BY v DESC"
            ), 'v'),
        ];
    }

    /**
     * WHERE común para listado / exportación / resumen.
     * $f: distribuidor, modelo_gen, estatus, tipo_pago, mes_desde (YYYY-MM), mes_hasta (YYYY-MM), search
     */
    private function construirWhere(array $f, array &$params): string
    {
        $w = ['1=1'];
        foreach (['distribuidor', 'modelo_gen', 'estatus', 'tipo_pago'] as $c) {
            if (($f[$c] ?? '') !== '') {
                $w[] = "r.$c = ?";
                $params[] = $f[$c];
            }
        }
        if (($f['mes_desde'] ?? '') !== '') {
            $w[] = 'r.mes_retail >= ?';
            $params[] = $f['mes_desde'] . '-01';
        }
        if (($f['mes_hasta'] ?? '') !== '') {
            $w[] = 'r.mes_retail <= ?';
            $params[] = $f['mes_hasta'] . '-01';
        }
        if (($f['search'] ?? '') !== '') {
            $or = [];
            foreach (self::COLUMNAS_BUSQUEDA as $c) {
                $or[] = "$c LIKE ?";
                $params[] = '%' . $f['search'] . '%';
            }
            $w[] = '(' . implode(' OR ', $or) . ')';
        }
        return implode(' AND ', $w);
    }

    private const SELECT_RETAIL = "
        SELECT r.idretail,
               DATE_FORMAT(r.mes_retail, '%Y-%m') AS mes_retail,
               r.distribuidor, r.vin, r.modelo, r.anio_modelo, r.tipo, r.estatus, r.observaciones,
               COALESCE(DATE_FORMAT(r.fecha_facturacion, '%Y-%m-%d'), r.fecha_facturacion_txt) AS fecha_facturacion,
               r.folio_fiscal, r.razon_social, r.rfc, r.direccion, r.codigo_postal, r.telefono, r.email,
               r.tipo_pago, r.institucion, r.enganche, r.plazo, r.vendedor, r.modelo_gen, r.motor,
               c.folio AS folio_carga
        FROM wms_dist_retail r
        LEFT JOIN wms_dist_retail_cargas c ON c.idcarga = r.cargaid";

    /** Listado paginado para DataTables serverSide. */
    public function selectRetailPaginado(array $f): array
    {
        $total = (int) ($this->select("SELECT COUNT(*) AS n FROM wms_dist_retail")['n'] ?? 0);

        $params = [];
        $where = $this->construirWhere($f, $params);
        $filtrados = (int) ($this->select("SELECT COUNT(*) AS n FROM wms_dist_retail r WHERE $where", $params)['n'] ?? 0);

        $start = max(0, (int) $f['start']);
        $length = (int) $f['length'];
        $limit = $length > 0 ? ' LIMIT ' . $start . ', ' . min($length, 500) : ' LIMIT 500';

        $rows = $this->select_all(
            self::SELECT_RETAIL . " WHERE $where ORDER BY {$f['order_col']} {$f['order_dir']}, r.idretail" . $limit,
            $params
        );
        return ['recordsTotal' => $total, 'recordsFiltered' => $filtrados, 'data' => $rows];
    }

    /** Todos los renglones filtrados (exportación). */
    public function selectRetailExport(array $f): array
    {
        $params = [];
        $where = $this->construirWhere($f, $params);
        return $this->select_all(
            self::SELECT_RETAIL . " WHERE $where ORDER BY r.mes_retail, r.distribuidor, r.vin",
            $params
        );
    }

    /** KPIs con los mismos filtros del listado. */
    public function selectKpis(array $f): array
    {
        $params = [];
        $where = $this->construirWhere($f, $params);
        $row = $this->select(
            "SELECT COUNT(*) AS total,
                    COUNT(DISTINCT r.distribuidor) AS distribuidores,
                    COUNT(DISTINCT r.mes_retail) AS meses,
                    DATE_FORMAT(MAX(r.mes_retail), '%Y-%m') AS ultimo_mes
             FROM wms_dist_retail r WHERE $where",
            $params
        );
        $ultimo = $row['ultimo_mes'] ?? null;
        $enUltimo = 0;
        if ($ultimo) {
            $p2 = $params;
            $p2[] = $ultimo . '-01';
            $enUltimo = (int) ($this->select(
                "SELECT COUNT(*) AS n FROM wms_dist_retail r WHERE $where AND r.mes_retail = ?",
                $p2
            )['n'] ?? 0);
        }
        return [
            'total' => (int) ($row['total'] ?? 0),
            'distribuidores' => (int) ($row['distribuidores'] ?? 0),
            'meses' => (int) ($row['meses'] ?? 0),
            'ultimo_mes' => $ultimo ?? '',
            'en_ultimo_mes' => $enUltimo,
        ];
    }

    /**
     * Matriz de unidades por mes para un año: agrupado por distribuidor o por modelo_gen.
     * Regresa [['grupo' => 'X', 'm1'..'m12' => n, 'total' => n], ...]
     */
    public function selectResumenAnual(int $anio, string $agrupar, array $f): array
    {
        $campo = $agrupar === 'modelo_gen' ? "COALESCE(NULLIF(r.modelo_gen, ''), '(sin modelo)')" : 'r.distribuidor';
        $f['mes_desde'] = $anio . '-01';
        $f['mes_hasta'] = $anio . '-12';
        $params = [];
        $where = $this->construirWhere($f, $params);
        $sel = [];
        for ($m = 1; $m <= 12; $m++) {
            $sel[] = "SUM(MONTH(r.mes_retail) = $m) AS m$m";
        }
        return $this->select_all(
            "SELECT $campo AS grupo, " . implode(', ', $sel) . ", COUNT(*) AS total
             FROM wms_dist_retail r
             WHERE $where
             GROUP BY grupo
             ORDER BY total DESC, grupo",
            $params
        );
    }

    public function selectAniosDisponibles(): array
    {
        return array_map('intval', array_column($this->select_all(
            "SELECT DISTINCT YEAR(mes_retail) AS a FROM wms_dist_retail ORDER BY a DESC"
        ), 'a'));
    }
}
