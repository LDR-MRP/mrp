<?php
/**
 * Cobranza de unidades: estatus de pago de cada VIN facturado.
 *
 * El estatus vive en vta_cobranza_unidades ligado a la FACTURACIÓN vigente del
 * VIN (wms_vin_facturacion, estatus 1 o 2) + VIN. La facturación ya no implica
 * salida de inventario, así que la unidad aparece aquí desde que se factura,
 * haya salido o no. Sin registro = Pendiente. Si la factura se cancela o la
 * unidad se devuelve, sale de la lista; si se vuelve a facturar, la nueva
 * factura arranca Pendiente. Cada cambio se registra en wms_vin_historial
 * (modulo = 'COBRANZA').
 *
 * fecha_pago (opcional) solo aplica a Parcialmente pagada / Pagada; para
 * Pendiente o Crédito se guarda NULL.
 */
class Inv_cobranzaunidadesModel extends Mysql
{
    public const ESTATUS = [
        0 => 'Pendiente',
        1 => 'Crédito',
        2 => 'Parcialmente pagada',
        3 => 'Pagada',
    ];

    /** Estatus que admiten fecha de pago. */
    public const ESTATUS_CON_FECHA = [2, 3];

    public function __construct()
    {
        parent::__construct();
    }

    /** VINs con facturación vigente (tipo P) con los datos de su factura y su estatus de pago. */
    private const SQL_BASE = "
        SELECT ns.id_numeros_serie,
               ns.numero_serie,
               i.cve_articulo,
               i.descripcion,
               COALESCE(va.numero_motor, '') AS numero_motor,
               COALESCE(a.descripcion, '') AS almacen_salida,
               f.idfacturacion,
               f.idmov_venta AS idmovinventario,
               COALESCE(m.numero_movimiento, '') AS folio_venta,
               f.factura,
               f.distribuidor,
               DATE_FORMAT(f.fecha_factura, '%Y-%m-%d') AS fecha_venta,
               COALESCE(cb.estatus_pago, 0) AS estatus_pago,
               DATE_FORMAT(cb.fecha_pago, '%Y-%m-%d') AS fecha_pago,
               DATE_FORMAT(cb.fecha_modifica, '%Y-%m-%d %H:%i') AS fecha_estatus,
               TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario_estatus
        FROM wms_vin_facturacion f
        INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = f.id_numeros_serie
        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
        LEFT JOIN wms_movimientos_inventario m ON m.idmovinventario = f.idmov_venta
        LEFT JOIN wms_almacenes a ON a.idalmacen = COALESCE(f.almacenid_salida, ns.almacenid)
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN vta_cobranza_unidades cb
               ON cb.idfacturacion = f.idfacturacion AND cb.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN usuarios u ON u.idusuario = cb.usuario_modifica
        WHERE f.estatus IN (1, 2)
          AND i.tipo_elemento = 'P'";

    /**
     * Listado con filtros opcionales: fechas de venta (Y-m-d), distribuidor (texto
     * exacto; '__SIN__' = vacío), modelo (descripción exacta) y estatus de pago.
     */
    public function selectUnidades(array $f = []): array
    {
        $sql = "SELECT * FROM (" . self::SQL_BASE . ") x WHERE 1 = 1";
        $p = [];
        if (!empty($f['desde'])) {
            $sql .= " AND x.fecha_venta >= ?";
            $p[] = $f['desde'];
        }
        if (!empty($f['hasta'])) {
            $sql .= " AND x.fecha_venta <= ?";
            $p[] = $f['hasta'];
        }
        if (isset($f['distribuidor']) && $f['distribuidor'] !== '') {
            if ($f['distribuidor'] === '__SIN__') {
                $sql .= " AND TRIM(x.distribuidor) = ''";
            } else {
                $sql .= " AND x.distribuidor = ?";
                $p[] = $f['distribuidor'];
            }
        }
        if (isset($f['modelo']) && $f['modelo'] !== '') {
            $sql .= " AND x.descripcion = ?";
            $p[] = $f['modelo'];
        }
        if (isset($f['estatus']) && $f['estatus'] !== '' && isset(self::ESTATUS[(int) $f['estatus']])) {
            $sql .= " AND x.estatus_pago = ?";
            $p[] = (int) $f['estatus'];
        }
        $sql .= " ORDER BY x.fecha_venta DESC, x.cve_articulo, x.numero_serie";
        return $this->select_all($sql, $p);
    }

    /** Conteo de unidades facturadas por estatus de pago (indicadores). */
    public function selectResumen(): array
    {
        $rows = $this->select_all(
            "SELECT x.estatus_pago, COUNT(*) AS n FROM (" . self::SQL_BASE . ") x GROUP BY x.estatus_pago"
        );
        $res = array_fill_keys(array_keys(self::ESTATUS), 0);
        foreach ($rows as $r) {
            $res[(int) $r['estatus_pago']] = (int) $r['n'];
        }
        return $res;
    }

    /** Datos actuales de VINs facturados como mapa [VIN en mayúsculas => fila]. */
    public function mapUnidadesPorVin(array $vines): array
    {
        $map = [];
        foreach (array_chunk(array_values(array_unique($vines)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            foreach ($this->select_all(self::SQL_BASE . " AND ns.numero_serie IN ($ph)", $bloque) as $r) {
                $map[strtoupper($r['numero_serie'])] = $r;
            }
        }
        return $map;
    }

    /** Una unidad facturada por id de VIN (para el cambio individual). */
    public function selectUnidad(int $idSerie): ?array
    {
        $row = $this->select(self::SQL_BASE . " AND ns.id_numeros_serie = ?", [$idSerie]);
        return $row ?: null;
    }

    /**
     * Guarda estatus y fecha de pago de la venta vigente del VIN y deja rastro en
     * wms_vin_historial. $fechaPago es el valor final (Y-m-d o null); para
     * estatus sin fecha se fuerza null. Devuelve false si no hubo cambio.
     */
    public function guardarEstatus(array $u, int $nuevo, ?string $fechaPago, ?int $usuarioid, string $origen): bool
    {
        if (!in_array($nuevo, self::ESTATUS_CON_FECHA, true)) {
            $fechaPago = null;
        }
        $anterior = (int) $u['estatus_pago'];
        $fechaAnt = !empty($u['fecha_pago']) ? (string) $u['fecha_pago'] : null;
        if ($anterior === $nuevo && $fechaAnt === $fechaPago) {
            return false;
        }
        $this->conexion->beginTransaction();
        try {
            $this->escribirEstatus($u, $anterior, $nuevo, $fechaAnt, $fechaPago, $usuarioid, $origen);
            $this->conexion->commit();
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
        return true;
    }

    private function escribirEstatus(array $u, int $anterior, int $nuevo, ?string $fechaAnt, ?string $fechaPago, ?int $usuarioid, string $origen): void
    {
        $this->insert(
            "INSERT INTO vta_cobranza_unidades (idfacturacion, idmovinventario, id_numeros_serie, estatus_pago, fecha_pago, usuario_modifica, fecha_modifica)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE estatus_pago = VALUES(estatus_pago),
                                     fecha_pago = VALUES(fecha_pago),
                                     usuario_modifica = VALUES(usuario_modifica),
                                     idmovinventario = VALUES(idmovinventario),
                                     fecha_modifica = NOW()",
            [(int) $u['idfacturacion'], !empty($u['idmovinventario']) ? (int) $u['idmovinventario'] : null,
             (int) $u['id_numeros_serie'], $nuevo, $fechaPago, $usuarioid]
        );
        $detalle = mb_substr('Factura ' . $u['factura'] . ($u['folio_venta'] !== '' ? ' · Venta ' . $u['folio_venta'] : ' · sin salida de inventario'), 0, 500);
        if ($anterior !== $nuevo) {
            $this->registrarHistorial($u, 'ESTATUS_PAGO', 'estatus_pago',
                self::ESTATUS[$anterior] ?? (string) $anterior, self::ESTATUS[$nuevo], $detalle, $origen, $usuarioid);
        }
        if ($fechaAnt !== $fechaPago) {
            $this->registrarHistorial($u, 'FECHA_PAGO', 'fecha_pago',
                $fechaAnt ?? '', $fechaPago ?? '', $detalle, $origen, $usuarioid);
        }
    }

    private function registrarHistorial(array $u, string $accion, string $campo, string $ant, string $nvo, string $detalle, string $origen, ?int $usuarioid): void
    {
        $this->insert(
            "INSERT INTO wms_vin_historial
             (id_numeros_serie, numero_serie, modulo, accion, campo, valor_anterior, valor_nuevo, detalle, origen, usuarioid, fecha)
             VALUES (?, ?, 'COBRANZA', ?, ?, ?, ?, ?, ?, ?, NOW())",
            [(int) $u['id_numeros_serie'], $u['numero_serie'], $accion, $campo, $ant, $nvo, $detalle, $origen, $usuarioid]
        );
    }

    /** Historial de estatus de pago de un VIN (todas sus ventas). */
    public function selectHistorial(int $idSerie): array
    {
        return $this->select_all(
            "SELECT DATE_FORMAT(h.fecha, '%Y-%m-%d %H:%i') AS fecha,
                    h.campo, h.valor_anterior, h.valor_nuevo, h.detalle, h.origen,
                    TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario
             FROM wms_vin_historial h
             LEFT JOIN usuarios u ON u.idusuario = h.usuarioid
             WHERE h.id_numeros_serie = ? AND h.modulo = 'COBRANZA'
             ORDER BY h.fecha DESC, h.id_historial DESC",
            [$idSerie]
        );
    }
}
