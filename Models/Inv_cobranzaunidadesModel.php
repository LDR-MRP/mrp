<?php
/**
 * Cobranza de unidades: estatus de pago de cada VIN facturado.
 *
 * El estatus vive en vta_cobranza_unidades ligado a la VENTA vigente del VIN
 * (último movimiento de salida en wms_movimientos_inventario_series) + VIN.
 * Sin registro = Pendiente. Si el VIN se devuelve deja de estar Facturado y
 * sale de la lista; si se vuelve a vender, la nueva venta arranca Pendiente.
 * Cada cambio se registra en wms_vin_historial (modulo = 'COBRANZA').
 */
class Inv_cobranzaunidadesModel extends Mysql
{
    public const ESTATUS = [
        0 => 'Pendiente',
        1 => 'Crédito',
        2 => 'Parcialmente pagada',
        3 => 'Pagada',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /** VINs Facturados (3) de producto con los datos de su venta vigente y su estatus de pago. */
    private const SQL_BASE = "
        SELECT ns.id_numeros_serie,
               ns.numero_serie,
               i.cve_articulo,
               i.descripcion,
               COALESCE(va.numero_motor, '') AS numero_motor,
               COALESCE(a.descripcion, '') AS almacen_salida,
               s.idmovinventario,
               COALESCE(m.numero_movimiento, s.numero_movimiento, '') AS folio_venta,
               COALESCE(m.referencia, '') AS factura,
               COALESCE(NULLIF(TRIM(m.distribuidor), ''),
                        CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
                        COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social), '') AS distribuidor,
               DATE_FORMAT(COALESCE(m.fecha_movimiento, s.fecha), '%Y-%m-%d') AS fecha_venta,
               COALESCE(cb.estatus_pago, 0) AS estatus_pago,
               DATE_FORMAT(cb.fecha_modifica, '%Y-%m-%d %H:%i') AS fecha_estatus,
               TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario_estatus
        FROM wms_numeros_series ns
        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
        INNER JOIN wms_movimientos_inventario_series s
               ON s.idmovserie = (SELECT MAX(s2.idmovserie) FROM wms_movimientos_inventario_series s2
                                  WHERE s2.id_numeros_serie = ns.id_numeros_serie AND s2.signo < 0)
        LEFT JOIN wms_movimientos_inventario m ON m.idmovinventario = s.idmovinventario
        LEFT JOIN cli_clientes c ON c.idcliente = m.id_cliente
        LEFT JOIN wms_almacenes a ON a.idalmacen = COALESCE(s.almacenid, ns.almacenid)
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN vta_cobranza_unidades cb
               ON cb.idmovinventario = s.idmovinventario AND cb.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN usuarios u ON u.idusuario = cb.usuario_modifica
        WHERE ns.estado = '3'
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
     * Guarda el estatus de pago de la venta vigente del VIN y deja rastro en
     * wms_vin_historial. Devuelve false si no hubo cambio.
     */
    public function guardarEstatus(array $u, int $nuevo, ?int $usuarioid, string $origen): bool
    {
        $anterior = (int) $u['estatus_pago'];
        if ($anterior === $nuevo) {
            return false;
        }
        $this->conexion->beginTransaction();
        try {
            $this->escribirEstatus($u, $anterior, $nuevo, $usuarioid, $origen);
            $this->conexion->commit();
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $e;
        }
        return true;
    }

    private function escribirEstatus(array $u, int $anterior, int $nuevo, ?int $usuarioid, string $origen): void
    {
        $this->insert(
            "INSERT INTO vta_cobranza_unidades (idmovinventario, id_numeros_serie, estatus_pago, usuario_modifica, fecha_modifica)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE estatus_pago = VALUES(estatus_pago),
                                     usuario_modifica = VALUES(usuario_modifica),
                                     fecha_modifica = NOW()",
            [(int) $u['idmovinventario'], (int) $u['id_numeros_serie'], $nuevo, $usuarioid]
        );
        $this->insert(
            "INSERT INTO wms_vin_historial
             (id_numeros_serie, numero_serie, modulo, accion, campo, valor_anterior, valor_nuevo, detalle, origen, usuarioid, fecha)
             VALUES (?, ?, 'COBRANZA', 'ESTATUS_PAGO', 'estatus_pago', ?, ?, ?, ?, ?, NOW())",
            [
                (int) $u['id_numeros_serie'],
                $u['numero_serie'],
                self::ESTATUS[$anterior] ?? (string) $anterior,
                self::ESTATUS[$nuevo],
                mb_substr('Venta ' . $u['folio_venta'] . ' · Factura ' . $u['factura'], 0, 500),
                $origen,
                $usuarioid,
            ]
        );
    }

    /** Historial de estatus de pago de un VIN (todas sus ventas). */
    public function selectHistorial(int $idSerie): array
    {
        return $this->select_all(
            "SELECT DATE_FORMAT(h.fecha, '%Y-%m-%d %H:%i') AS fecha,
                    h.valor_anterior, h.valor_nuevo, h.detalle, h.origen,
                    TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario
             FROM wms_vin_historial h
             LEFT JOIN usuarios u ON u.idusuario = h.usuarioid
             WHERE h.id_numeros_serie = ? AND h.modulo = 'COBRANZA'
             ORDER BY h.fecha DESC, h.id_historial DESC",
            [$idSerie]
        );
    }
}
