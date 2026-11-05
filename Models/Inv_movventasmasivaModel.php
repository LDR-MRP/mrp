<?php
/**
 * Carga masiva de VENTAS / FACTURACIÓN de unidades (VINs).
 *
 * El layout se DESCARGA con todos los VINs que siguen en inventario
 * (estado 1 = Disponible, 2 = Tatuado) de todas las plantas y almacenes.
 * Al subirlo solo se ACTUALIZA: el único cambio permitido es
 * 2 = Tatuado -> 3 = Facturado, con cliente y factura; cada VIN facturado
 * genera su propio movimiento de Ventas (una unidad por factura), que baja
 * wms_multialmacen y deja la trazabilidad en wms_movimientos_inventario_series.
 */
class Inv_movventasmasivaModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    private const PLANTA_SQL = "COALESCE(pl.nombre_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN CONCAT('Planta ', CAST(SUBSTRING(a.cve_almacen, 3, 2) AS UNSIGNED)) ELSE 'Sin planta asignada' END)";

    /** Distribuidor de la última venta: texto capturado o, si no hay, el cliente del catálogo. */
    private const DIST_VENTA_SQL = "COALESCE(NULLIF(TRIM(mv.distribuidor), ''), CONCAT(NULLIF(cv.codigo_cliente, ''), ' - ', COALESCE(NULLIF(cv.nombre_comercial, ''), cv.razon_social)), COALESCE(NULLIF(cv.nombre_comercial, ''), cv.razon_social), '')";

    /** Última venta (salida con VIN) de un VIN Facturado. */
    private const JOIN_ULTIMA_VENTA = "
                LEFT JOIN wms_movimientos_inventario_series sv
                       ON ns.estado = '3'
                      AND sv.idmovserie = (SELECT MAX(sv2.idmovserie) FROM wms_movimientos_inventario_series sv2
                                           WHERE sv2.id_numeros_serie = ns.id_numeros_serie AND sv2.signo < 0)
                LEFT JOIN wms_movimientos_inventario mv ON mv.idmovinventario = sv.idmovinventario
                LEFT JOIN cli_clientes cv ON cv.idcliente = mv.id_cliente";

    /**
     * Almacenes desde los que se pueden vender unidades: Producto Terminado
     * (clave con "ALM-PT", p. ej. PL01-ALM-PT) y Tránsito. Misma regla que
     * Inv_movimientosinventarioModel::almacenVentaUnidades().
     */
    private const ALMACEN_VENTA_SQL = "(UPPER(TRIM(a.cve_almacen)) REGEXP '(^|-)ALM-PT(-|$)'
                     OR UPPER(TRIM(a.cve_almacen)) = 'ALM-TRANSITO'
                     OR UPPER(TRIM(a.descripcion)) IN ('ALMACEN DE TRANSITO', 'ALMACÉN DE TRANSITO', 'ALMACÉN DE TRÁNSITO'))";

    /** Histórico general del layout: VINs (1 Disponible, 2 Tatuado, 3 Facturado) en almacenes de PT o Tránsito. */
    public function selectVinesLayout(): array
    {
        $sql = "SELECT ns.id_numeros_serie,
                       ns.numero_serie,
                       ns.estado,
                       i.cve_articulo,
                       i.descripcion,
                       COALESCE(a.descripcion, '') AS almacen,
                       " . self::PLANTA_SQL . " AS planta,
                       COALESCE(va.numero_motor, '') AS numero_motor,
                       " . self::DIST_VENTA_SQL . " AS distribuidor,
                       COALESCE(mv.referencia, '') AS factura,
                       COALESCE(DATE_FORMAT(mv.fecha_movimiento, '%Y-%m-%d'), '') AS fecha_venta
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                LEFT JOIN mrp_planta pl    ON pl.idplanta = a.plantaid
                LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie"
                . self::JOIN_ULTIMA_VENTA . "
                WHERE ns.estado IN ('1', '2', '3')
                  AND i.tipo_elemento = 'P'
                  AND " . self::ALMACEN_VENTA_SQL . "
                ORDER BY planta, almacen, i.cve_articulo, ns.numero_serie";
        return $this->select_all($sql);
    }

    /**
     * Datos actuales de los VINs del archivo (cualquier estado distinto de 0),
     * como mapa [VIN en mayúsculas => fila]. Una consulta por bloque de 500.
     */
    public function mapVinesPorNumero(array $vines): array
    {
        $map = [];
        foreach (array_chunk(array_values(array_unique($vines)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            $rows = $this->select_all(
                "SELECT ns.id_numeros_serie,
                        ns.numero_serie,
                        ns.estado,
                        ns.inventarioid,
                        ns.almacenid,
                        i.cve_articulo,
                        i.descripcion,
                        i.tipo_elemento,
                        i.ultimo_costo,
                        COALESCE(a.descripcion, '') AS almacen,
                        COALESCE(va.numero_motor, '') AS numero_motor,
                        " . self::DIST_VENTA_SQL . " AS distribuidor,
                        COALESCE(mv.distribuidor, '') AS distribuidor_texto,
                        COALESCE(mv.referencia, '') AS factura,
                        mv.idmovinventario AS idmov_venta,
                        mv.numero_movimiento AS folio_venta,
                        (SELECT COUNT(*) FROM wms_movimientos_inventario_series sc
                          WHERE sc.idmovinventario = mv.idmovinventario) AS vines_en_venta
                 FROM wms_numeros_series ns
                 INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                 LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                 LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie"
                 . self::JOIN_ULTIMA_VENTA . "
                 WHERE ns.estado <> '0'
                   AND ns.numero_serie IN ($ph)",
                $bloque
            );
            foreach ($rows as $r) {
                $map[strtoupper($r['numero_serie'])] = $r;
            }
        }
        return $map;
    }

    /**
     * Distribuidores activos: clientes cuyo tipo (cli_tipos_cliente) es
     * "Distribuidor" (cli_clientes.estado: 0=Eliminado 1=Inactivo 2=Activo).
     */
    public function selectClientesActivos(): array
    {
        return $this->select_all(
            "SELECT c.idcliente,
                    c.codigo_cliente,
                    COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social) AS nombre
             FROM cli_clientes c
             INNER JOIN cli_tipos_cliente t ON t.id = c.idtipo_cliente
             WHERE c.estado = 2
               AND t.estado = 2
               AND t.nombre = 'Distribuidor'
             ORDER BY nombre"
        );
    }

    /** Concepto de Ventas (clave 51, tipo Cliente, salida). */
    public function selectConceptoVentas(): ?array
    {
        $row = $this->select(
            "SELECT idconcepmov, descripcion
             FROM wms_conceptos_mov
             WHERE estado = 2 AND cpn = 'C' AND signo = -1
             ORDER BY (cve_concep_mov = '51') DESC, (descripcion = 'Ventas') DESC, idconcepmov
             LIMIT 1"
        );
        return $row ?: null;
    }

    /**
     * Facturas (referencias) ya usadas en movimientos de Ventas activos:
     * [FACTURA => [idmovinventario, ...]] (para poder excluir la propia venta al corregir).
     */
    public function facturasUsadas(int $concepmovid, array $facturas): array
    {
        $usadas = [];
        foreach (array_chunk(array_values(array_unique($facturas)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            $rows = $this->select_all(
                "SELECT referencia, idmovinventario
                 FROM wms_movimientos_inventario
                 WHERE concepmovid = ? AND estado = 2 AND referencia IN ($ph)",
                array_merge([$concepmovid], $bloque)
            );
            foreach ($rows as $r) {
                $usadas[strtoupper(trim($r['referencia']))][] = (int) $r['idmovinventario'];
            }
        }
        return $usadas;
    }

    /**
     * Corrige distribuidor (texto) y/o factura de la venta de un VIN facturado
     * y deja cada cambio en el historial del VIN.
     */
    public function corregirVenta(array $bd, ?string $distribuidor, ?string $factura, $usuarioid, int $fila): void
    {
        $pdo = $this->getConexion();
        $pdo->beginTransaction();
        try {
            $sets = [];
            $vals = [];
            $hist = [];
            if ($distribuidor !== null) {
                $sets[] = 'distribuidor = ?';
                $vals[] = mb_substr($distribuidor, 0, 200);
                $hist[] = ['distribuidor', $bd['distribuidor'], $distribuidor];
            }
            if ($factura !== null) {
                $sets[] = 'referencia = ?';
                $vals[] = $factura;
                $hist[] = ['factura', $bd['factura'], $factura];
            }
            if (empty($sets)) {
                $pdo->rollBack();
                return;
            }
            $vals[] = (int) $bd['idmov_venta'];
            $this->update("UPDATE wms_movimientos_inventario SET " . implode(', ', $sets) . " WHERE idmovinventario = ?", $vals);

            $historial = new Inv_vin_historialModel(); // Models/ se carga con el autoload
            foreach ($hist as [$campo, $antes, $despues]) {
                $historial->registrar([
                    'id_numeros_serie' => $bd['id_numeros_serie'],
                    'numero_serie' => $bd['numero_serie'],
                    'modulo' => 'VENTAS',
                    'accion' => 'CORRECCION',
                    'campo' => $campo,
                    'valor_anterior' => $antes,
                    'valor_nuevo' => $despues,
                    'detalle' => "Corrección desde carga masiva de ventas (venta {$bd['folio_venta']}, fila {$fila})",
                    'origen' => 'MASIVO',
                    'usuarioid' => $usuarioid,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /* =================================================================
     * DEVOLUCIONES: VINs Facturados (3) con los datos de su última venta
     * ================================================================= */

    private const SQL_FACTURADOS = "
        SELECT ns.id_numeros_serie,
               ns.numero_serie,
               ns.estado,
               ns.inventarioid,
               i.cve_articulo,
               i.descripcion,
               i.ultimo_costo,
               COALESCE(va.numero_motor, '') AS numero_motor,
               COALESCE(s.almacenid, ns.almacenid) AS almacen_salidaid,
               COALESCE(a.descripcion, '') AS almacen_salida,
               " . self::PLANTA_SQL . " AS planta,
               COALESCE(m.numero_movimiento, '') AS folio_venta,
               COALESCE(m.referencia, '') AS factura,
               m.id_cliente,
               COALESCE(m.distribuidor, '') AS distribuidor_texto,
               COALESCE(NULLIF(TRIM(m.distribuidor), ''),
                        CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
                        COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social), '') AS distribuidor,
               DATE_FORMAT(COALESCE(m.fecha_movimiento, s.fecha), '%Y-%m-%d') AS fecha_venta,
               COALESCE(s.estado_anterior, '2') AS estado_regreso
        FROM wms_numeros_series ns
        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN wms_movimientos_inventario_series s
               ON s.idmovserie = (SELECT MAX(s2.idmovserie) FROM wms_movimientos_inventario_series s2
                                  WHERE s2.id_numeros_serie = ns.id_numeros_serie AND s2.signo < 0)
        LEFT JOIN wms_movimientos_inventario m ON m.idmovinventario = s.idmovinventario
        LEFT JOIN cli_clientes c ON c.idcliente = m.id_cliente
        LEFT JOIN wms_almacenes a ON a.idalmacen = COALESCE(s.almacenid, ns.almacenid)
        LEFT JOIN mrp_planta pl ON pl.idplanta = a.plantaid
        WHERE ns.estado = '3'
          AND i.tipo_elemento = 'P'";

    /** VINs facturados para el layout de devoluciones (filtro opcional por fecha de venta). */
    public function selectVinesFacturados(?string $desde = null, ?string $hasta = null): array
    {
        $sql = self::SQL_FACTURADOS;
        $params = [];
        if ($desde) {
            $sql .= " AND DATE(COALESCE(m.fecha_movimiento, s.fecha)) >= ?";
            $params[] = $desde;
        }
        if ($hasta) {
            $sql .= " AND DATE(COALESCE(m.fecha_movimiento, s.fecha)) <= ?";
            $params[] = $hasta;
        }
        $sql .= " ORDER BY fecha_venta DESC, i.cve_articulo, ns.numero_serie";
        return $this->select_all($sql, $params);
    }

    /** Mismos datos que el layout, como mapa [VIN => fila], para validar la carga. */
    public function mapVinesFacturados(array $vines): array
    {
        $map = [];
        foreach (array_chunk(array_values(array_unique($vines)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            foreach ($this->select_all(self::SQL_FACTURADOS . " AND ns.numero_serie IN ($ph)", $bloque) as $r) {
                $map[strtoupper($r['numero_serie'])] = $r;
            }
        }
        return $map;
    }

    /** Concepto de devolución de venta (clave 2, tipo Cliente, entrada). */
    public function selectConceptoDevolucion(): ?array
    {
        $row = $this->select(
            "SELECT idconcepmov, descripcion
             FROM wms_conceptos_mov
             WHERE estado = 2 AND cpn = 'C' AND signo = 1
             ORDER BY (cve_concep_mov = '2') DESC, (descripcion LIKE 'Devol%') DESC, idconcepmov
             LIMIT 1"
        );
        return $row ?: null;
    }

    /**
     * Totales para los indicadores de la pantalla: VINs de producto por
     * estatus y facturados con venta en el mes en curso.
     */
    public function selectResumen(): array
    {
        $row = $this->select(
            "SELECT SUM(ns.estado = '1') AS disponibles,
                    SUM(ns.estado = '2') AS tatuadas,
                    SUM(ns.estado = '3') AS facturadas
             FROM wms_numeros_series ns
             INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
             WHERE ns.estado IN ('1', '2', '3')
               AND i.tipo_elemento = 'P'"
        ) ?: [];
        $mes = $this->select(
            "SELECT COUNT(*) AS n
             FROM (" . self::SQL_FACTURADOS . ") f
             WHERE f.fecha_venta >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        ) ?: [];

        return [
            'disponibles'    => (int) ($row['disponibles'] ?? 0),
            'tatuadas'       => (int) ($row['tatuadas'] ?? 0),
            'facturadas'     => (int) ($row['facturadas'] ?? 0),
            'facturadas_mes' => (int) ($mes['n'] ?? 0),
        ];
    }

    public function selectAlmacenesActivos(): array
    {
        return $this->select_all(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes WHERE estado = 2 ORDER BY descripcion"
        );
    }
}
