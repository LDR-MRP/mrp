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

    /** VINs en inventario (Disponible / Tatuado) para el layout. */
    public function selectVinesLayout(): array
    {
        $sql = "SELECT ns.id_numeros_serie,
                       ns.numero_serie,
                       ns.estado,
                       i.cve_articulo,
                       i.descripcion,
                       COALESCE(a.descripcion, '') AS almacen,
                       " . self::PLANTA_SQL . " AS planta,
                       COALESCE(va.numero_motor, '') AS numero_motor
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                LEFT JOIN mrp_planta pl    ON pl.idplanta = a.plantaid
                LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
                WHERE ns.estado IN ('1', '2')
                  AND i.tipo_elemento = 'P'
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
                        COALESCE(va.numero_motor, '') AS numero_motor
                 FROM wms_numeros_series ns
                 INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                 LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                 LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
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

    /** Facturas (referencias) ya usadas en movimientos de Ventas activos. */
    public function facturasUsadas(int $concepmovid, array $facturas): array
    {
        $usadas = [];
        foreach (array_chunk(array_values(array_unique($facturas)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            $rows = $this->select_all(
                "SELECT DISTINCT referencia
                 FROM wms_movimientos_inventario
                 WHERE concepmovid = ? AND estado = 2 AND referencia IN ($ph)",
                array_merge([$concepmovid], $bloque)
            );
            foreach ($rows as $r) {
                $usadas[strtoupper(trim($r['referencia']))] = true;
            }
        }
        return $usadas;
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
               COALESCE(m.referencia, '') AS factura,
               m.id_cliente,
               COALESCE(CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
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

    public function selectAlmacenesActivos(): array
    {
        return $this->select_all(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes WHERE estado = 2 ORDER BY descripcion"
        );
    }
}
