<?php
/**
 * RETAIL E INVENTARIO DE DISTRIBUIDORES.
 *
 * Dos bases independientes con la misma mecánica (cargas por archivo que
 * reemplazan los meses que traen):
 *  - retail:     wms_dist_retail_cargas / wms_dist_retail       (hoja "Retail", MES RETAIL)
 *  - inventario: wms_dist_inventario_cargas / wms_dist_inventario (hoja "Inventarios", AÑO R + MES R)
 *
 * Migraciones: Docs/desarrollo/inventario_distribuidores/
 *
 * Datos tal cual los manda el área: NO se relacionan con
 * wms_numeros_series, wms_inventario ni cli_clientes.
 */
class Inv_invdistribuidoresModel extends Mysql
{
    /**
     * Configuración por base. Todos los identificadores que se interpolan en SQL
     * salen de aquí (nunca del request).
     */
    public const TIPOS = [
        'retail' => [
            'tabla' => 'wms_dist_retail',
            'cargas' => 'wms_dist_retail_cargas',
            'pk' => 'idretail',
            'mes' => 'mes_retail',
            'folio' => 'RTD',
            /** Columnas que llegan del archivo (orden del INSERT). */
            'columnas' => [
                'mes_retail', 'distribuidor', 'vin', 'modelo', 'anio_modelo', 'tipo', 'estatus', 'observaciones',
                'fecha_facturacion', 'fecha_facturacion_txt', 'folio_fiscal', 'razon_social', 'rfc', 'direccion',
                'codigo_postal', 'telefono', 'email', 'tipo_pago', 'institucion', 'enganche', 'plazo', 'vendedor',
                'modelo_gen', 'motor', 'fila_archivo',
            ],
            /** Columnas por las que busca la caja de búsqueda del listado. */
            'busqueda' => ['r.vin', 'r.distribuidor', 'r.modelo', 'r.modelo_gen', 'r.razon_social',
                'r.rfc', 'r.folio_fiscal', 'r.vendedor', 'r.estatus', 'r.motor'],
            /** Filtros de igualdad que acepta. */
            'filtros' => ['distribuidor', 'modelo_gen', 'estatus', 'tipo_pago'],
            'select_extra' => "r.telefono, r.email, r.motor, DATE_FORMAT(r.mes_retail, '%Y-%m') AS mes_retail",
        ],
        'inventario' => [
            'tabla' => 'wms_dist_inventario',
            'cargas' => 'wms_dist_inventario_cargas',
            'pk' => 'idinventario',
            'mes' => 'mes_inventario',
            'folio' => 'IND',
            'columnas' => [
                'mes_inventario', 'distribuidor', 'vin', 'modelo', 'anio_modelo', 'tipo', 'estatus', 'observaciones',
                'fecha_facturacion', 'fecha_facturacion_txt', 'folio_fiscal', 'razon_social', 'rfc', 'direccion',
                'codigo_postal', 'tipo_pago', 'institucion', 'enganche', 'plazo', 'vendedor', 'modelo_gen',
                'fila_archivo',
            ],
            'busqueda' => ['r.vin', 'r.distribuidor', 'r.modelo', 'r.modelo_gen', 'r.razon_social',
                'r.rfc', 'r.folio_fiscal', 'r.vendedor', 'r.estatus', 'r.tipo', 'r.observaciones'],
            'filtros' => ['distribuidor', 'modelo_gen', 'estatus', 'tipo'],
            'select_extra' => "YEAR(r.mes_inventario) AS anio_r, MONTH(r.mes_inventario) AS mes_r",
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    private function cfg(string $tipo): array
    {
        return self::TIPOS[$tipo] ?? self::TIPOS['retail'];
    }

    public function tablasListas(string $tipo = 'retail'): bool
    {
        $c = $this->cfg($tipo);
        $row = $this->select(
            "SELECT COUNT(*) AS n FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN (?, ?)",
            [$c['tabla'], $c['cargas']]
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
     * @param array $filas  renglones con las llaves de 'columnas'
     * @param array $meses  ['2025-01-01', ...]
     * @return array ['idcarga','folio','reemplazadas']
     */
    public function insertCarga(string $tipo, array $cab, array $filas, array $meses): array
    {
        $c = $this->cfg($tipo);
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            sort($meses);
            $idcarga = (int) $this->insert(
                "INSERT INTO {$c['cargas']}
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
            $folio = $c['folio'] . '-' . str_pad((string) $idcarga, 6, '0', STR_PAD_LEFT);

            // 1) Reemplazo de meses
            $reemplazadas = 0;
            foreach (array_chunk($meses, 200) as $bloque) {
                $ph = implode(',', array_fill(0, count($bloque), '?'));
                $st = $db->prepare("DELETE FROM {$c['tabla']} WHERE {$c['mes']} IN ($ph)");
                $st->execute($bloque);
                $reemplazadas += $st->rowCount();
            }

            // 2) Inserción multi-renglón (300 por sentencia)
            $cols = array_merge(['cargaid'], $c['columnas']);
            $tupla = '(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            foreach (array_chunk($filas, 300) as $bloque) {
                $params = [];
                foreach ($bloque as $f) {
                    $params[] = $idcarga;
                    foreach ($c['columnas'] as $col) {
                        $params[] = $f[$col] ?? null;
                    }
                }
                $sql = "INSERT INTO {$c['tabla']} (" . implode(',', $cols) . ') VALUES '
                    . implode(',', array_fill(0, count($bloque), $tupla));
                $db->prepare($sql)->execute($params);
            }

            $this->update(
                "UPDATE {$c['cargas']} SET folio = ?, total_cargadas = ?, filas_reemplazadas = ? WHERE idcarga = ?",
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

    public function selectCargas(string $tipo): array
    {
        $c = $this->cfg($tipo);
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
             FROM {$c['cargas']} c
             LEFT JOIN usuarios u ON u.idusuario = c.usuarioid
             LEFT JOIN (SELECT cargaid, COUNT(*) AS vigentes FROM {$c['tabla']} GROUP BY cargaid) v
                    ON v.cargaid = c.idcarga
             ORDER BY c.idcarga DESC"
        );
    }

    public function selectCarga(string $tipo, int $idcarga): ?array
    {
        $c = $this->cfg($tipo);
        $row = $this->select("SELECT idcarga, folio, estado FROM {$c['cargas']} WHERE idcarga = ?", [$idcarga]);
        return $row ?: null;
    }

    /** Borra los renglones que siguen vigentes de esa carga y la marca como eliminada. */
    public function eliminarCarga(string $tipo, int $idcarga): int
    {
        $c = $this->cfg($tipo);
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $st = $db->prepare("DELETE FROM {$c['tabla']} WHERE cargaid = ?");
            $st->execute([$idcarga]);
            $borrados = $st->rowCount();
            $this->update("UPDATE {$c['cargas']} SET estado = 0 WHERE idcarga = ?", [$idcarga]);
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
    public function selectCatalogosFiltro(string $tipo): array
    {
        $c = $this->cfg($tipo);
        $col = function (string $campo) use ($c) {
            return array_column($this->select_all(
                "SELECT DISTINCT $campo AS v FROM {$c['tabla']} WHERE $campo IS NOT NULL AND $campo <> '' ORDER BY v"
            ), 'v');
        };
        return [
            'distribuidores' => $col('distribuidor'),
            'modelos' => $col('modelo_gen'),
            'estatus' => $col('estatus'),
            // 4º filtro: tipo de pago (retail) o tipo (inventario)
            'filtro4' => $col($c['filtros'][3]),
            'tipos_pago' => in_array('tipo_pago', $c['filtros'], true) ? $col('tipo_pago') : [],
            'meses' => array_column($this->select_all(
                "SELECT DISTINCT DATE_FORMAT({$c['mes']}, '%Y-%m') AS v FROM {$c['tabla']} ORDER BY v DESC"
            ), 'v'),
        ];
    }

    /**
     * WHERE común para listado / exportación / resumen.
     * $f: filtros de cfg['filtros'], mes_desde (YYYY-MM), mes_hasta (YYYY-MM), search
     */
    private function construirWhere(array $c, array $f, array &$params): string
    {
        $w = ['1=1'];
        foreach ($c['filtros'] as $campo) {
            if (($f[$campo] ?? '') !== '') {
                $w[] = "r.$campo = ?";
                $params[] = $f[$campo];
            }
        }
        if (($f['mes_desde'] ?? '') !== '') {
            $w[] = "r.{$c['mes']} >= ?";
            $params[] = $f['mes_desde'] . '-01';
        }
        if (($f['mes_hasta'] ?? '') !== '') {
            $w[] = "r.{$c['mes']} <= ?";
            $params[] = $f['mes_hasta'] . '-01';
        }
        if (($f['search'] ?? '') !== '') {
            $or = [];
            foreach ($c['busqueda'] as $col) {
                $or[] = "$col LIKE ?";
                $params[] = '%' . $f['search'] . '%';
            }
            $w[] = '(' . implode(' OR ', $or) . ')';
        }
        return implode(' AND ', $w);
    }

    private function selectBase(array $c): string
    {
        return "
        SELECT r.{$c['pk']} AS id,
               DATE_FORMAT(r.{$c['mes']}, '%Y-%m') AS mes,
               r.distribuidor, r.vin, r.modelo, r.anio_modelo, r.tipo, r.estatus, r.observaciones,
               COALESCE(DATE_FORMAT(r.fecha_facturacion, '%Y-%m-%d'), r.fecha_facturacion_txt) AS fecha_facturacion,
               r.folio_fiscal, r.razon_social, r.rfc, r.direccion, r.codigo_postal,
               r.tipo_pago, r.institucion, r.enganche, r.plazo, r.vendedor, r.modelo_gen,
               {$c['select_extra']},
               c.folio AS folio_carga
        FROM {$c['tabla']} r
        LEFT JOIN {$c['cargas']} c ON c.idcarga = r.cargaid";
    }

    /** Listado paginado para DataTables serverSide. $f['order_col'] viene de una lista blanca del controlador. */
    public function selectPaginado(string $tipo, array $f): array
    {
        $c = $this->cfg($tipo);
        $total = (int) ($this->select("SELECT COUNT(*) AS n FROM {$c['tabla']}")['n'] ?? 0);

        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        $filtrados = (int) ($this->select("SELECT COUNT(*) AS n FROM {$c['tabla']} r WHERE $where", $params)['n'] ?? 0);

        $start = max(0, (int) $f['start']);
        $length = (int) $f['length'];
        $limit = $length > 0 ? ' LIMIT ' . $start . ', ' . min($length, 500) : ' LIMIT 500';

        $rows = $this->select_all(
            $this->selectBase($c) . " WHERE $where ORDER BY {$f['order_col']} {$f['order_dir']}, r.{$c['pk']}" . $limit,
            $params
        );
        return ['recordsTotal' => $total, 'recordsFiltered' => $filtrados, 'data' => $rows];
    }

    /** Todos los renglones filtrados (exportación). */
    public function selectExport(string $tipo, array $f): array
    {
        $c = $this->cfg($tipo);
        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        return $this->select_all(
            $this->selectBase($c) . " WHERE $where ORDER BY r.{$c['mes']}, r.distribuidor, r.vin",
            $params
        );
    }

    /** KPIs con los mismos filtros del listado. */
    public function selectKpis(string $tipo, array $f): array
    {
        $c = $this->cfg($tipo);
        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        $row = $this->select(
            "SELECT COUNT(*) AS total,
                    COUNT(DISTINCT r.distribuidor) AS distribuidores,
                    COUNT(DISTINCT r.{$c['mes']}) AS meses,
                    DATE_FORMAT(MAX(r.{$c['mes']}), '%Y-%m') AS ultimo_mes
             FROM {$c['tabla']} r WHERE $where",
            $params
        );
        $ultimo = $row['ultimo_mes'] ?? null;
        $enUltimo = 0;
        $distUltimo = 0;
        if ($ultimo) {
            $p2 = $params;
            $p2[] = $ultimo . '-01';
            $u = $this->select(
                "SELECT COUNT(*) AS n, COUNT(DISTINCT r.distribuidor) AS d
                 FROM {$c['tabla']} r WHERE $where AND r.{$c['mes']} = ?",
                $p2
            );
            $enUltimo = (int) ($u['n'] ?? 0);
            $distUltimo = (int) ($u['d'] ?? 0);
        }
        return [
            'total' => (int) ($row['total'] ?? 0),
            'distribuidores' => (int) ($row['distribuidores'] ?? 0),
            'meses' => (int) ($row['meses'] ?? 0),
            'ultimo_mes' => $ultimo ?? '',
            'en_ultimo_mes' => $enUltimo,
            'distribuidores_ultimo_mes' => $distUltimo,
        ];
    }

    /**
     * Matriz de unidades por mes para un año: agrupado por distribuidor o por modelo_gen.
     * Regresa [['grupo' => 'X', 'm1'..'m12' => n, 'total' => n], ...]
     */
    public function selectResumenAnual(string $tipo, int $anio, string $agrupar, array $f): array
    {
        $c = $this->cfg($tipo);
        $campo = $agrupar === 'modelo_gen' ? "COALESCE(NULLIF(r.modelo_gen, ''), '(sin modelo)')" : 'r.distribuidor';
        $f['mes_desde'] = $anio . '-01';
        $f['mes_hasta'] = $anio . '-12';
        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        $sel = [];
        for ($m = 1; $m <= 12; $m++) {
            $sel[] = "SUM(MONTH(r.{$c['mes']}) = $m) AS m$m";
        }
        return $this->select_all(
            "SELECT $campo AS grupo, " . implode(', ', $sel) . ", COUNT(*) AS total
             FROM {$c['tabla']} r
             WHERE $where
             GROUP BY grupo
             ORDER BY total DESC, grupo",
            $params
        );
    }

    /**
     * Serie mensual (últimos $meses meses con datos) con los filtros del listado:
     * [['mes' => 'YYYY-MM', 'n' => unidades, 'distribuidores' => n], ...] en orden ascendente.
     */
    public function selectTendencia(string $tipo, array $f, int $meses = 36): array
    {
        $c = $this->cfg($tipo);
        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        $rows = $this->select_all(
            "SELECT DATE_FORMAT(r.{$c['mes']}, '%Y-%m') AS mes, COUNT(*) AS n, COUNT(DISTINCT r.distribuidor) AS distribuidores
             FROM {$c['tabla']} r
             WHERE $where
             GROUP BY r.{$c['mes']}
             ORDER BY r.{$c['mes']} DESC
             LIMIT " . max(1, min($meses, 120)),
            $params
        );
        return array_reverse(array_map(function ($r) {
            return ['mes' => $r['mes'], 'n' => (int) $r['n'], 'distribuidores' => (int) $r['distribuidores']];
        }, $rows));
    }

    /** Distribuidores con más unidades en un mes ('YYYY-MM'), con los filtros del listado. */
    public function selectTopDistribuidores(string $tipo, array $f, string $mes, int $limite = 8): array
    {
        $c = $this->cfg($tipo);
        $params = [];
        $where = $this->construirWhere($c, $f, $params);
        $params[] = $mes . '-01';
        return array_map(function ($r) {
            return ['distribuidor' => $r['distribuidor'], 'n' => (int) $r['n']];
        }, $this->select_all(
            "SELECT r.distribuidor, COUNT(*) AS n
             FROM {$c['tabla']} r
             WHERE $where AND r.{$c['mes']} = ?
             GROUP BY r.distribuidor
             ORDER BY n DESC, r.distribuidor
             LIMIT " . max(1, min($limite, 50)),
            $params
        ));
    }

    public function selectAniosDisponibles(string $tipo): array
    {
        $c = $this->cfg($tipo);
        return array_map('intval', array_column($this->select_all(
            "SELECT DISTINCT YEAR({$c['mes']}) AS a FROM {$c['tabla']} ORDER BY a DESC"
        ), 'a'));
    }
}
