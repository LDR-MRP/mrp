<?php
/**
 * Carga masiva de VENTAS / FACTURACIÓN de unidades (VINs).
 *
 * La facturación vive en wms_vin_facturacion y está SEPARADA del estatus
 * físico del VIN (wms_numeros_series.estado) y del inventario:
 *  - Facturar solo crea el registro (estatus 1 = Facturado, pendiente de
 *    salida). El VIN conserva su estado (1 Disponible / 2 Tatuado), así que
 *    producción lo puede seguir tatuando y la entrada de fin de ensamble
 *    ocurre normal. Se puede facturar exista o no la unidad físicamente.
 *  - La salida de inventario (movimiento de Ventas) se registrará cuando
 *    Logística tome la unidad; entonces la facturación pasa a estatus 2.
 *  - Devolver/cancelar deja la facturación en 0. Si ya hubo salida, además
 *    se registra la entrada "Devol. de venta".
 *
 * El layout trae los VINs (1 Disponible, 2 Tatuado) de los almacenes
 * marcados como facturables (wms_almacenes.facturable = 1) y todos los VINs
 * con facturación vigente.
 */
class Inv_movventasmasivaModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    private const PLANTA_SQL = "COALESCE(pl.nombre_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN CONCAT('Planta ', CAST(SUBSTRING(a.cve_almacen, 3, 2) AS UNSIGNED)) ELSE 'Sin planta asignada' END)";

    /** Facturación vigente del VIN (a lo más una, por el índice único vin_activo). */
    private const JOIN_FACT = "
                LEFT JOIN wms_vin_facturacion f
                       ON f.id_numeros_serie = ns.id_numeros_serie AND f.estatus IN (1, 2)
                LEFT JOIN wms_movimientos_inventario mv ON mv.idmovinventario = f.idmov_venta";

    /** Estatus que ve el usuario: Facturado si tiene facturación vigente; si no, el físico. */
    private const ESTADO_SQL = "CASE WHEN f.idfacturacion IS NOT NULL OR ns.estado = '3' THEN '3' ELSE ns.estado END";

    /** Histórico del layout: VINs de almacenes facturables + VINs con facturación vigente. */
    public function selectVinesLayout(): array
    {
        $sql = "SELECT ns.id_numeros_serie,
                       ns.numero_serie,
                       " . self::ESTADO_SQL . " AS estado,
                       i.cve_articulo,
                       i.descripcion,
                       COALESCE(a.descripcion, '') AS almacen,
                       " . self::PLANTA_SQL . " AS planta,
                       COALESCE(va.numero_motor, '') AS numero_motor,
                       COALESCE(f.distribuidor, '') AS distribuidor,
                       COALESCE(f.factura, '') AS factura,
                       COALESCE(DATE_FORMAT(f.fecha_factura, '%Y-%m-%d'), '') AS fecha_venta
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                LEFT JOIN mrp_planta pl    ON pl.idplanta = a.plantaid
                LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie"
                . self::JOIN_FACT . "
                WHERE i.tipo_elemento = 'P'
                  AND (f.idfacturacion IS NOT NULL
                       OR (ns.estado IN ('1', '2') AND a.estado = 2 AND a.facturable = 1))
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
                        " . self::ESTADO_SQL . " AS estado,
                        ns.estado AS estado_fisico,
                        ns.inventarioid,
                        ns.almacenid,
                        i.cve_articulo,
                        i.descripcion,
                        i.tipo_elemento,
                        COALESCE(a.descripcion, '') AS almacen,
                        COALESCE(a.facturable, 0) AS almacen_facturable,
                        COALESCE(va.numero_motor, '') AS numero_motor,
                        f.idfacturacion,
                        COALESCE(f.distribuidor, '') AS distribuidor,
                        COALESCE(f.factura, '') AS factura,
                        f.idmov_venta,
                        COALESCE(mv.numero_movimiento, '') AS folio_venta,
                        (SELECT COUNT(*) FROM wms_movimientos_inventario_series sc
                          WHERE sc.idmovinventario = f.idmov_venta) AS vines_en_venta
                 FROM wms_numeros_series ns
                 INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                 LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                 LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie"
                 . self::JOIN_FACT . "
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

    /** Concepto de Ventas (clave 51, tipo Cliente, salida). Se usará en la salida de Logística. */
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
     * Facturas ya usadas en facturaciones vigentes:
     * [FACTURA => [idfacturacion, ...]] (para poder excluir la propia al corregir).
     */
    public function facturasUsadas(array $facturas): array
    {
        $usadas = [];
        foreach (array_chunk(array_values(array_unique($facturas)), 500) as $bloque) {
            $ph = implode(',', array_fill(0, count($bloque), '?'));
            $rows = $this->select_all(
                "SELECT UPPER(TRIM(factura)) AS factura, idfacturacion
                 FROM wms_vin_facturacion
                 WHERE estatus IN (1, 2) AND UPPER(TRIM(factura)) IN ($ph)",
                $bloque
            );
            foreach ($rows as $r) {
                $usadas[$r['factura']][] = (int) $r['idfacturacion'];
            }
        }
        return $usadas;
    }

    /**
     * Registra la facturación de un VIN (estatus 1 = pendiente de salida).
     * No toca wms_numeros_series ni el inventario. Devuelve true o el motivo
     * del rechazo (string).
     */
    public function registrarFacturacion(array $bd, string $distribuidor, string $factura, ?int $usuarioid, int $fila)
    {
        $pdo = $this->getConexion();
        $pdo->beginTransaction();
        try {
            $vin = $this->select(
                "SELECT id_numeros_serie, numero_serie, estado FROM wms_numeros_series WHERE id_numeros_serie = ? FOR UPDATE",
                [(int) $bd['id_numeros_serie']]
            );
            if (!$vin || !in_array((string) $vin['estado'], ['1', '2'], true)) {
                $pdo->rollBack();
                return 'El VIN ya no está Disponible ni Tatuado (pudo cambiar desde que se descargó el layout)';
            }
            $vigente = $this->select(
                "SELECT idfacturacion, factura FROM wms_vin_facturacion
                 WHERE id_numeros_serie = ? AND estatus IN (1, 2) FOR UPDATE",
                [(int) $bd['id_numeros_serie']]
            );
            if ($vigente) {
                $pdo->rollBack();
                return "El VIN ya está facturado (factura {$vigente['factura']})";
            }
            $repetida = $this->select(
                "SELECT numero_serie FROM wms_vin_facturacion
                 WHERE estatus IN (1, 2) AND UPPER(TRIM(factura)) = UPPER(TRIM(?)) LIMIT 1",
                [$factura]
            );
            if ($repetida) {
                $pdo->rollBack();
                return "La factura {$factura} ya está registrada para el VIN {$repetida['numero_serie']}";
            }

            $this->insert(
                "INSERT INTO wms_vin_facturacion
                 (id_numeros_serie, numero_serie, distribuidor, factura, fecha_factura, estatus, origen, usuario_factura)
                 VALUES (?, ?, ?, ?, NOW(), 1, 'MASIVO', ?)",
                [(int) $vin['id_numeros_serie'], $vin['numero_serie'], mb_substr($distribuidor, 0, 200),
                 mb_substr($factura, 0, 50), $usuarioid]
            );

            (new Inv_vin_historialModel())->registrar([
                'id_numeros_serie' => $vin['id_numeros_serie'],
                'numero_serie' => $vin['numero_serie'],
                'modulo' => 'VENTAS',
                'accion' => 'FACTURACION',
                'campo' => 'facturacion',
                'valor_anterior' => 'Sin facturar',
                'valor_nuevo' => 'Facturado',
                'detalle' => "Factura {$factura} · {$distribuidor} (carga masiva, fila {$fila}; sin salida de inventario)",
                'origen' => 'MASIVO',
                'usuarioid' => $usuarioid,
            ]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Corrige distribuidor y/o factura de una facturación vigente. Si ya tiene
     * movimiento de Ventas (de una sola unidad) también se corrige ahí, para
     * que ambos queden iguales. Cada cambio queda en el historial del VIN.
     */
    public function corregirVenta(array $bd, ?string $distribuidor, ?string $factura, $usuarioid, int $fila): void
    {
        $pdo = $this->getConexion();
        $pdo->beginTransaction();
        try {
            $sets = [];
            $vals = [];
            $setsMov = [];
            $valsMov = [];
            $hist = [];
            if ($distribuidor !== null) {
                $d = mb_substr($distribuidor, 0, 200);
                $sets[] = 'distribuidor = ?';
                $vals[] = $d;
                $setsMov[] = 'distribuidor = ?';
                $valsMov[] = $d;
                $hist[] = ['distribuidor', $bd['distribuidor'], $distribuidor];
            }
            if ($factura !== null) {
                $sets[] = 'factura = ?';
                $vals[] = mb_substr($factura, 0, 50);
                $setsMov[] = 'referencia = ?';
                $valsMov[] = $factura;
                $hist[] = ['factura', $bd['factura'], $factura];
            }
            if (empty($sets)) {
                $pdo->rollBack();
                return;
            }
            $vals[] = (int) $bd['idfacturacion'];
            $this->update("UPDATE wms_vin_facturacion SET " . implode(', ', $sets) . " WHERE idfacturacion = ?", $vals);

            if (!empty($bd['idmov_venta']) && (int) $bd['vines_en_venta'] <= 1) {
                $valsMov[] = (int) $bd['idmov_venta'];
                $this->update("UPDATE wms_movimientos_inventario SET " . implode(', ', $setsMov) . " WHERE idmovinventario = ?", $valsMov);
            }

            $ref = $bd['folio_venta'] !== '' ? "venta {$bd['folio_venta']}" : 'sin salida de inventario';
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
                    'detalle' => "Corrección desde carga masiva de ventas ({$ref}, fila {$fila})",
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

    /**
     * Deja una facturación en 0 (Cancelado/Devuelto) y lo registra en el
     * historial. NO abre transacción: el llamador la controla (en la
     * devolución con salida va junto con la entrada de inventario).
     */
    public function cancelarFacturacion(array $bd, string $motivo, ?int $usuarioid): void
    {
        $n = $this->updateAffected(
            "UPDATE wms_vin_facturacion
             SET estatus = 0, fecha_cancelacion = NOW(), usuario_cancela = ?, motivo_cancelacion = ?
             WHERE idfacturacion = ? AND estatus IN (1, 2)",
            [$usuarioid, mb_substr($motivo, 0, 255), (int) $bd['idfacturacion']]
        );
        if ($n !== 1) {
            throw new \RuntimeException('La facturación del VIN ya no está vigente');
        }
        (new Inv_vin_historialModel())->registrar([
            'id_numeros_serie' => $bd['id_numeros_serie'],
            'numero_serie' => $bd['numero_serie'],
            'modulo' => 'VENTAS',
            'accion' => 'DEVOLUCION',
            'campo' => 'facturacion',
            'valor_anterior' => 'Facturado',
            'valor_nuevo' => 'Devuelto',
            'detalle' => "Factura {$bd['factura']} · {$motivo}",
            'origen' => 'MASIVO',
            'usuarioid' => $usuarioid,
        ]);
    }

    /* =================================================================
     * DEVOLUCIONES / CONSULTA: facturaciones vigentes (1 y 2)
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
               f.idfacturacion,
               f.estatus AS estatus_fact,
               f.idmov_venta,
               COALESCE(f.almacenid_salida, ns.almacenid) AS almacen_salidaid,
               COALESCE(a.descripcion, '') AS almacen_salida,
               " . self::PLANTA_SQL . " AS planta,
               COALESCE(m.numero_movimiento, '') AS folio_venta,
               f.factura,
               f.id_cliente,
               f.distribuidor AS distribuidor_texto,
               f.distribuidor,
               DATE_FORMAT(f.fecha_factura, '%Y-%m-%d') AS fecha_venta
        FROM wms_vin_facturacion f
        INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = f.id_numeros_serie
        INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        LEFT JOIN wms_movimientos_inventario m ON m.idmovinventario = f.idmov_venta
        LEFT JOIN wms_almacenes a ON a.idalmacen = COALESCE(f.almacenid_salida, ns.almacenid)
        LEFT JOIN mrp_planta pl ON pl.idplanta = a.plantaid
        WHERE f.estatus IN (1, 2)
          AND i.tipo_elemento = 'P'";

    /** VINs facturados para el layout de devoluciones (filtro opcional por fecha de factura). */
    public function selectVinesFacturados(?string $desde = null, ?string $hasta = null): array
    {
        $sql = self::SQL_FACTURADOS;
        $params = [];
        if ($desde) {
            $sql .= " AND DATE(f.fecha_factura) >= ?";
            $params[] = $desde;
        }
        if ($hasta) {
            $sql .= " AND DATE(f.fecha_factura) <= ?";
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
     * Totales para los indicadores de la pantalla: VINs de producto sin
     * facturar por estatus físico, facturados vigentes y facturados del mes.
     */
    public function selectResumen(): array
    {
        $row = $this->select(
            "SELECT SUM(ns.estado = '1' AND f.idfacturacion IS NULL) AS disponibles,
                    SUM(ns.estado = '2' AND f.idfacturacion IS NULL) AS tatuadas
             FROM wms_numeros_series ns
             INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
             LEFT JOIN wms_vin_facturacion f
                    ON f.id_numeros_serie = ns.id_numeros_serie AND f.estatus IN (1, 2)
             WHERE ns.estado IN ('1', '2')
               AND i.tipo_elemento = 'P'"
        ) ?: [];
        $fac = $this->select(
            "SELECT COUNT(*) AS facturadas,
                    SUM(x.fecha_venta >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS mes
             FROM (" . self::SQL_FACTURADOS . ") x"
        ) ?: [];

        return [
            'disponibles'    => (int) ($row['disponibles'] ?? 0),
            'tatuadas'       => (int) ($row['tatuadas'] ?? 0),
            'facturadas'     => (int) ($fac['facturadas'] ?? 0),
            'facturadas_mes' => (int) ($fac['mes'] ?? 0),
        ];
    }

    public function selectAlmacenesActivos(): array
    {
        return $this->select_all(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes WHERE estado = 2 ORDER BY descripcion"
        );
    }
}
