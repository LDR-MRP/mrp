<?php
/**
 * Inventario de distribuidores (conteo reportado).
 * Tablas: wms_inv_dist_cortes / wms_inv_dist_detalle (ver Docs/desarrollo/inventario_distribuidores).
 * No mueve existencias: las unidades ya salieron del MRP con su venta (VIN 3 = Facturado).
 */
class Inv_invdistribuidoresModel extends Mysql
{
    public const CVE_ALMACEN = 'ALM-DISTRIBUIDORES';

    public function __construct()
    {
        parent::__construct();
    }

    /* ===============================================================
     * CATÁLOGOS
     * =============================================================== */

    public function selectAlmacenDistribuidores(): ?array
    {
        $row = $this->select(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes
             WHERE cve_almacen = ? AND estado = 2 LIMIT 1",
            [self::CVE_ALMACEN]
        );
        return $row ?: null;
    }

    /** Distribuidores activos (cli_tipos_cliente.nombre = 'Distribuidor'). */
    public function selectDistribuidores(): array
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

    /* ===============================================================
     * VINs FACTURADOS (estatus 3) con el cliente de su última venta.
     * Misma lógica que Inv_movventasmasivaModel::SQL_FACTURADOS.
     * =============================================================== */

    private const SQL_VIN_BASE = "
        SELECT ns.id_numeros_serie,
               ns.numero_serie,
               ns.estado,
               ns.inventarioid,
               i.cve_articulo,
               i.descripcion,
               COALESCE(va.numero_motor, '') AS numero_motor,
               COALESCE(va.color, '') AS color,
               COALESCE(m.referencia, '') AS factura,
               m.id_cliente,
               DATE_FORMAT(COALESCE(m.fecha_movimiento, s.fecha), '%Y-%m-%d') AS fecha_venta
        FROM wms_numeros_series ns
        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN wms_movimientos_inventario_series s
               ON s.idmovserie = (SELECT MAX(s2.idmovserie) FROM wms_movimientos_inventario_series s2
                                  WHERE s2.id_numeros_serie = ns.id_numeros_serie AND s2.signo < 0)
        LEFT JOIN wms_movimientos_inventario m ON m.idmovinventario = s.idmovinventario";

    /** Datos de los VINs del archivo (cualquier estatus distinto de 0) como [VIN => fila]. */
    public function mapVines(array $vines): array
    {
        $map = [];
        foreach (array_chunk(array_values(array_unique($vines)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            $rows = $this->select_all(
                self::SQL_VIN_BASE . " WHERE ns.estado <> '0' AND ns.numero_serie IN ($ph)",
                $bloque
            );
            foreach ($rows as $r) {
                $map[strtoupper($r['numero_serie'])] = $r;
            }
        }
        return $map;
    }

    /* ===============================================================
     * CORTES
     * =============================================================== */

    /**
     * Inserta un corte con sus VINs (todo o nada).
     * $vines: [['id_numeros_serie','numero_serie','inventarioid','ubicacion','observaciones','fila'], ...]
     * Regresa el folio o lanza excepción.
     */
    public function insertCorte(int $clienteid, int $almacenid, string $fechaCorte, ?string $observaciones,
                                ?string $archivo, ?int $usuarioid, array $vines): string
    {
        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $idcorte = (int) $this->insert(
                "INSERT INTO wms_inv_dist_cortes
                    (folio, clienteid, almacenid, fecha_corte, observaciones, archivo, total_vines, usuarioid, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 0, ?, 2)",
                ['TMP-' . uniqid('', true), $clienteid, $almacenid, $fechaCorte, $observaciones, $archivo, $usuarioid]
            );
            $folio = 'CID-' . str_pad((string) $idcorte, 6, '0', STR_PAD_LEFT);

            $sqlDet = "INSERT INTO wms_inv_dist_detalle
                          (corteid, clienteid, id_numeros_serie, numero_serie, inventarioid, estatus_distribuidor, ubicacion, observaciones, fila_archivo)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            foreach ($vines as $v) {
                $this->insert($sqlDet, [
                    $idcorte,
                    $clienteid,
                    (int) $v['id_numeros_serie'],
                    $v['numero_serie'],
                    (int) $v['inventarioid'],
                    $v['estatus'],
                    $v['ubicacion'] !== '' ? $v['ubicacion'] : null,
                    $v['observaciones'] !== '' ? $v['observaciones'] : null,
                    $v['fila'] ?? null,
                ]);
            }

            $this->update(
                "UPDATE wms_inv_dist_cortes SET folio = ?, total_vines = ? WHERE idcorte = ?",
                [$folio, count($vines), $idcorte]
            );
            $db->commit();
            return $folio;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function selectCortes(): array
    {
        return $this->select_all(
            "SELECT co.idcorte,
                    co.folio,
                    co.clienteid,
                    COALESCE(CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
                             COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social), '') AS distribuidor,
                    DATE_FORMAT(co.fecha_corte, '%Y-%m-%d') AS fecha_corte,
                    co.total_vines,
                    COALESCE(co.observaciones, '') AS observaciones,
                    COALESCE(co.archivo, '') AS archivo,
                    DATE_FORMAT(co.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
                    TRIM(CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, ''))) AS usuario,
                    co.estado,
                    (co.idcorte = v.idvigente) AS vigente
             FROM wms_inv_dist_cortes co
             LEFT JOIN cli_clientes c ON c.idcliente = co.clienteid
             LEFT JOIN usuarios u ON u.idusuario = co.usuarioid
             LEFT JOIN (" . self::SQL_VIGENTES . ") v ON v.clienteid = co.clienteid
             ORDER BY co.idcorte DESC"
        );
    }

    public function selectCorte(int $idcorte): ?array
    {
        $row = $this->select(
            "SELECT idcorte, folio, clienteid, estado FROM wms_inv_dist_cortes WHERE idcorte = ?",
            [$idcorte]
        );
        return $row ?: null;
    }

    public function selectDetalleCorte(int $idcorte): array
    {
        return $this->select_all(
            "SELECT d.numero_serie AS vin,
                    i.cve_articulo,
                    i.descripcion,
                    COALESCE(va.numero_motor, '') AS numero_motor,
                    d.estatus_distribuidor,
                    COALESCE(d.ubicacion, '') AS ubicacion,
                    COALESCE(d.observaciones, '') AS observaciones
             FROM wms_inv_dist_detalle d
             INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
             LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = d.id_numeros_serie
             WHERE d.corteid = ?
             ORDER BY i.cve_articulo, d.numero_serie",
            [$idcorte]
        );
    }

    public function cancelarCorte(int $idcorte): bool
    {
        return $this->updateAffected(
            "UPDATE wms_inv_dist_cortes SET estado = 0 WHERE idcorte = ? AND estado = 2",
            [$idcorte]
        ) > 0;
    }

    /* ===============================================================
     * INVENTARIO VIGENTE Y CONCILIACIÓN
     * Corte vigente = último corte activo del distribuidor
     * (mayor fecha_corte; si empata, el más reciente en subirse).
     * =============================================================== */

    private const SQL_VIGENTES = "
        SELECT x.clienteid,
               (SELECT y.idcorte FROM wms_inv_dist_cortes y
                WHERE y.clienteid = x.clienteid AND y.estado = 2
                ORDER BY y.fecha_corte DESC, y.idcorte DESC LIMIT 1) AS idvigente
        FROM wms_inv_dist_cortes x
        WHERE x.estado = 2
        GROUP BY x.clienteid";

    /** VINs del corte vigente de cada distribuidor, con su estatus actual de facturación. */
    public function selectInventarioVigente(?int $clienteid = null): array
    {
        $sql = "SELECT d.numero_serie AS vin,
                       d.clienteid,
                       COALESCE(CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
                                COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social), '') AS distribuidor,
                       vb.cve_articulo,
                       vb.descripcion,
                       vb.numero_motor,
                       vb.color,
                       vb.factura,
                       vb.fecha_venta,
                       d.estatus_distribuidor,
                       COALESCE(d.ubicacion, '') AS ubicacion,
                       COALESCE(d.observaciones, '') AS observaciones,
                       co.folio,
                       DATE_FORMAT(co.fecha_corte, '%Y-%m-%d') AS fecha_corte,
                       CASE WHEN vb.estado = '3' AND vb.id_cliente = d.clienteid THEN 1 ELSE 0 END AS conciliado
                FROM (" . self::SQL_VIGENTES . ") v
                INNER JOIN wms_inv_dist_cortes co ON co.idcorte = v.idvigente
                INNER JOIN wms_inv_dist_detalle d ON d.corteid = co.idcorte
                INNER JOIN (" . self::SQL_VIN_BASE . "
                            WHERE ns.id_numeros_serie IN (SELECT dd.id_numeros_serie FROM wms_inv_dist_detalle dd)) vb
                        ON vb.id_numeros_serie = d.id_numeros_serie
                LEFT JOIN cli_clientes c ON c.idcliente = d.clienteid";
        $params = [];
        if ($clienteid) {
            $sql .= " WHERE d.clienteid = ?";
            $params[] = $clienteid;
        }
        $sql .= " ORDER BY distribuidor, vb.cve_articulo, d.numero_serie";
        return $this->select_all($sql, $params);
    }

    /**
     * Todos los VINs facturados (estatus 3) a distribuidores, marcando si
     * aparecen en el corte vigente de ese mismo distribuidor.
     */
    public function selectFacturadosConciliacion(?int $clienteid = null): array
    {
        $sql = "SELECT vb.numero_serie AS vin,
                       vb.id_cliente AS clienteid,
                       COALESCE(CONCAT(NULLIF(c.codigo_cliente, ''), ' - ', COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social)),
                                COALESCE(NULLIF(c.nombre_comercial, ''), c.razon_social), '') AS distribuidor,
                       vb.cve_articulo,
                       vb.descripcion,
                       vb.numero_motor,
                       vb.factura,
                       vb.fecha_venta,
                       co.folio,
                       DATE_FORMAT(co.fecha_corte, '%Y-%m-%d') AS fecha_corte,
                       COALESCE(d.estatus_distribuidor, '') AS estatus_distribuidor,
                       CASE WHEN d.iddetalle IS NULL THEN 0 ELSE 1 END AS reportado
                FROM (" . self::SQL_VIN_BASE . " WHERE ns.estado = '3' AND i.tipo_elemento = 'P') vb
                INNER JOIN cli_clientes c ON c.idcliente = vb.id_cliente
                INNER JOIN cli_tipos_cliente t ON t.id = c.idtipo_cliente AND t.nombre = 'Distribuidor'
                LEFT JOIN (" . self::SQL_VIGENTES . ") v ON v.clienteid = vb.id_cliente
                LEFT JOIN wms_inv_dist_cortes co ON co.idcorte = v.idvigente
                LEFT JOIN wms_inv_dist_detalle d ON d.corteid = co.idcorte AND d.id_numeros_serie = vb.id_numeros_serie";
        $params = [];
        if ($clienteid) {
            $sql .= " WHERE vb.id_cliente = ?";
            $params[] = $clienteid;
        }
        $sql .= " ORDER BY distribuidor, reportado, vb.cve_articulo, vb.numero_serie";
        return $this->select_all($sql, $params);
    }
}
