<?php

class Inv_movimientosinventarioModel extends Mysql
{
    public $intidprecio;
    public $strClave;
    public $strCvePrecio;
    public $strFecha;
    public $intEstatus;
    public $strDescripcion;
    public $intImpuesto;


    public function __construct()
    {
        parent::__construct();
    }

    public function insertMovimiento(
        int $inventarioid,
        int $almacenid,
        int $concepmovid,
        string $referencia,
        float $cantidad,
        float $costo_cantidad,
        ?int $id_proveedor = null,
        ?string $lote = null
    ) {
        // 1. Signo
        $concepmovid = (int)$concepmovid;
        $concepto = $this->select(
            "SELECT signo FROM wms_conceptos_mov WHERE idconcepmov = $concepmovid"
        );
        if (!$concepto) return "Concepto inválido";

        $signo = (int)$concepto['signo'];

        // 2. Stock actual
        $inventarioid = (int)$inventarioid;
        $almacenid = (int)$almacenid;

        $row = $this->select(
            "SELECT existencia FROM wms_multialmacen
     WHERE inventarioid = $inventarioid
       AND almacenid = $almacenid"
        );
        // Regla 1: no existe stock y es salida
        if ($signo < 0 && !$row) {
            return "No existe stock del producto en este almacén";
        }

        $existencia_actual = $row ? (float)$row['existencia'] : 0;
        $nueva_existencia = $existencia_actual + ($cantidad * $signo);

        // Regla 2: stock insuficiente
        if ($nueva_existencia < 0) {
            return "Stock insuficiente";
        }

        // 3. Número movimiento
        $numero_movimiento = 'MOV-' . date('YmdHis') . '-' . rand(100,999);

        // 4. Insertar movimiento
        $this->insert(
            "INSERT INTO wms_movimientos_inventario
        (inventarioid, almacenid, numero_movimiento, concepmovid, id_proveedor,
         referencia, lote, cantidad, costo_cantidad, existencia, signo,
         fecha_movimiento, estado)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),2)",
            [
                $inventarioid,
                $almacenid,
                $numero_movimiento,
                $concepmovid,
                $id_proveedor,
                $referencia,
                $lote,
                $cantidad,
                $costo_cantidad,
                $nueva_existencia,
                $signo
            ]
        );

        // 5. Actualizar multialmacenes
        $this->insert(
            "INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
         VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE existencia=?",
            [$inventarioid, $almacenid, $nueva_existencia, $nueva_existencia]
        );

        return true;
    }


    public function selectMovimientos($almacen = 0, $concepto = 0, $fechaInicio = '', $fechaFin = '')
{
    $where = "WHERE m.estado = 2";

    if ($almacen > 0) {
        $where .= " AND m.almacenid = $almacen";
    }

    if ($concepto > 0) {
        $where .= " AND m.concepmovid = $concepto";
    }

    if (!empty($fechaInicio)) {
        $where .= " AND DATE(m.fecha_movimiento) >= '$fechaInicio'";
    }

    if (!empty($fechaFin)) {
        $where .= " AND DATE(m.fecha_movimiento) <= '$fechaFin'";
    }

    $sql = "SELECT 
        m.idmovinventario,
        m.numero_movimiento,
        m.almacenid,
        i.cve_articulo AS clave,
        i.descripcion AS producto,
        a.descripcion AS almacen,
        c.descripcion AS concepto,
        m.referencia,
        m.lote,
        (m.signo * m.cantidad) AS cantidad,
        m.existencia,
        m.fecha_movimiento
    FROM wms_movimientos_inventario m
    INNER JOIN wms_inventario i ON i.idinventario = m.inventarioid
    INNER JOIN wms_almacenes a ON a.idalmacen = m.almacenid
    INNER JOIN wms_conceptos_mov c ON c.idconcepmov = m.concepmovid
    $where
    ORDER BY m.idmovinventario DESC";

    return $this->select_all($sql);
}


    public function selectAlmacenes()
    {
        $sql = "SELECT idalmacen, descripcion, estado
            FROM wms_almacenes
            WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function selectInventario()
    {
        return $this->select_all("
        SELECT idinventario, descripcion
        FROM wms_inventario
        WHERE estado = 2
    ");
    }


    public function selectConceptos()
    {
        return $this->select_all("
        SELECT idconcepmov, descripcion
        FROM wms_conceptos_mov
        WHERE estado = 2
    ");
    }

    public function selectProveedores()
    {
        return $this->select_all("
        SELECT id_proveedor, COALESCE(NULLIF(nombre_comercial, ''), razon_social) AS nombre
        FROM prv_cat_proveedores
        WHERE estatus_operativo = 1
          AND deleted_at IS NULL
        ORDER BY nombre ASC
    ");
    }

    public function selectClientes()
    {
        // cli_clientes.estado: 0=Eliminado 1=Inactivo 2=Activo
        return $this->select_all("
        SELECT idcliente, codigo_cliente,
               COALESCE(NULLIF(nombre_comercial, ''), razon_social) AS nombre
        FROM cli_clientes
        WHERE estado = 2
        ORDER BY nombre ASC
    ");
    }

    public function selectInventarioPredictivo()
    {
        return $this->select_all("
        SELECT idinventario, cve_articulo, descripcion, tipo_elemento
        FROM wms_inventario
        WHERE estado = 2
    ");
    }

    /**
     * VINs que pueden salir de un almacén para un producto: estado 1 (Disponible)
     * o 2 (Tatuado), y que no estén comprometidos en un traslado activo.
     */
    public function selectVinesDisponibles(int $inventarioid, int $almacenid): array
    {
        return $this->select_all("
        SELECT ns.id_numeros_serie,
               ns.numero_serie,
               NULLIF(TRIM(va.color), '') AS color,
               va.numero_motor
        FROM wms_numeros_series ns
        LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
        WHERE ns.inventarioid = ?
          AND ns.almacenid = ?
          AND ns.estado IN ('1', '2')
          AND NOT EXISTS (
              SELECT 1
              FROM wms_traslados_unidades_detalle d
              INNER JOIN wms_traslados_unidades t ON t.idtraslado = d.trasladoid
              WHERE d.vinid = ns.id_numeros_serie
                AND t.estado IN (1,2,3)
          )
        ORDER BY ns.numero_serie
    ", [$inventarioid, $almacenid]);
    }

    public function insertMovimientoMasivo(
        int $almacenid,
        int $concepmovid,
        string $referencia,
        array $inventarios,
        array $cantidades,
        array $costos,
        ?int $id_proveedor = null,
        ?string $lote = null,
        ?int $id_cliente = null,
        ?array $series = null,
        ?int $usuarioid = null
    ) {
        /*
         * $series: VINs por partida, mismo índice que $inventarios
         * ([i] => [id_numeros_serie, ...]). Si viene null (carga masiva,
         * alta de inventario) se conserva el comportamiento anterior y no se
         * tocan VINs. Si viene un arreglo (form "Nuevo movimiento"), toda
         * SALIDA de un producto tipo Unidad (P) debe indicar sus VINs.
         */
        $concepto = $this->select("SELECT signo, cpn FROM wms_conceptos_mov WHERE idconcepmov = $concepmovid");
        if (!$concepto) return "Concepto inválido";

        $signo = (int)$concepto['signo'];

        // Si el concepto de movimiento es de tipo Proveedor (cpn = 'P'), el proveedor es obligatorio
        if ($concepto['cpn'] === 'P' && empty($id_proveedor)) {
            return "Debe seleccionar un proveedor para este concepto de movimiento";
        }

        // Si el concepto de movimiento es de tipo Cliente (cpn = 'C'), el cliente es obligatorio
        if ($concepto['cpn'] === 'C' && empty($id_cliente)) {
            return "Debe seleccionar un cliente para este concepto de movimiento";
        }

        // Cada movimiento solo guarda la contraparte que corresponde a su tipo
        if ($concepto['cpn'] !== 'P') $id_proveedor = null;
        if ($concepto['cpn'] !== 'C') $id_cliente = null;


        $pdo = $this->getConexion();

        try {
            $pdo->beginTransaction();

            $numero_movimiento = 'MOV-' . date('YmdHis') . '-' . rand(100,999);
            $vinesUsados = [];

            $insertados = 0;

            foreach ($inventarios as $i => $inventarioid) {

                if (empty($inventarioid) || empty($cantidades[$i])) continue;

                $cantidad = (float)$cantidades[$i];
                $costo    = (float)$costos[$i];

                // Regla: las VENTAS de unidades (tipo P) solo salen de Producto Terminado o de Tránsito
                if ($series !== null && $signo < 0 && $concepto['cpn'] === 'C') {
                    $tipo = $this->select("SELECT tipo_elemento FROM wms_inventario WHERE idinventario = ?", [(int)$inventarioid]);
                    if ($tipo && $tipo['tipo_elemento'] === 'P') {
                        $alm = $this->almacenVentaUnidades($almacenid);
                        if (!$alm['permitido']) {
                            throw new Exception("La unidad está en el almacén {$alm['nombre']}: solo se pueden vender unidades de almacenes de Producto Terminado o de Tránsito");
                        }
                    }
                }

                $row = $this->select("
                SELECT existencia 
                FROM wms_multialmacen
                WHERE inventarioid = $inventarioid 
                  AND almacenid = $almacenid
            ");

                $existencia_actual = $row ? (float)$row['existencia'] : 0;
                $nueva_existencia  = $existencia_actual + ($cantidad * $signo);

                if ($nueva_existencia < 0) {
                    throw new Exception("Stock insuficiente en producto ID $inventarioid");
                }

                // ---- VINs de la partida (solo salidas de unidades, tipo P) ----
                $vinesPartida = [];
                if ($series !== null && $signo < 0) {
                    $prod = $this->select(
                        "SELECT tipo_elemento, cve_articulo FROM wms_inventario WHERE idinventario = ?",
                        [(int)$inventarioid]
                    );
                    if ($prod && $prod['tipo_elemento'] === 'P') {
                        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($series[$i] ?? [])))));
                        if (floor($cantidad) != $cantidad || count($ids) !== (int)$cantidad) {
                            throw new Exception("La partida {$prod['cve_articulo']} es una unidad: debes seleccionar exactamente "
                                . (float)$cantidad . " VIN(s) (seleccionados: " . count($ids) . ")");
                        }
                        foreach ($ids as $idSerie) {
                            if (isset($vinesUsados[$idSerie])) {
                                throw new Exception("Un VIN está repetido en dos partidas del movimiento");
                            }
                            $vin = $this->select(
                                "SELECT id_numeros_serie, numero_serie, estado, inventarioid, almacenid
                                 FROM wms_numeros_series WHERE id_numeros_serie = ? FOR UPDATE",
                                [$idSerie]
                            );
                            if (!$vin || (int)$vin['inventarioid'] !== (int)$inventarioid) {
                                throw new Exception("El VIN seleccionado no corresponde al producto {$prod['cve_articulo']}");
                            }
                            if ((int)$vin['almacenid'] !== $almacenid) {
                                throw new Exception("El VIN {$vin['numero_serie']} no está en el almacén seleccionado");
                            }
                            // 1 = Disponible, 2 = Tatuado: ambos pueden facturarse. 3 = Facturado ya salió.
                            if (!in_array($vin['estado'], ['1', '2'], true)) {
                                throw new Exception("El VIN {$vin['numero_serie']} ya no está disponible en almacén (ya facturado o dado de baja)");
                            }
                            $enTraslado = $this->select(
                                "SELECT COUNT(*) total FROM wms_traslados_unidades_detalle d
                                 INNER JOIN wms_traslados_unidades t ON t.idtraslado = d.trasladoid
                                 WHERE d.vinid = ? AND t.estado IN (1,2,3)",
                                [$idSerie]
                            );
                            if ($enTraslado && (int)$enTraslado['total'] > 0) {
                                throw new Exception("El VIN {$vin['numero_serie']} está en un traslado activo");
                            }
                            $vin['estado_nuevo'] = '3';
                            $vinesUsados[$idSerie] = true;
                            $vinesPartida[] = $vin;
                        }
                    }
                }

                // ---- Devolución de unidades (entrada tipo Cliente, p. ej. "Devol. de venta") ----
                // El VIN debe estar Facturado (3); regresa como 1 = Disponible al
                // almacén de esta entrada.
                if ($series !== null && $signo > 0) {
                    $prod = $this->select(
                        "SELECT tipo_elemento, cve_articulo FROM wms_inventario WHERE idinventario = ?",
                        [(int)$inventarioid]
                    );
                    if ($prod && $prod['tipo_elemento'] === 'P') {
                        $ids = array_values(array_unique(array_filter(array_map('intval', (array)($series[$i] ?? [])))));
                        $requiere = ($concepto['cpn'] === 'C');
                        if (($requiere || !empty($ids)) && (floor($cantidad) != $cantidad || count($ids) !== (int)$cantidad)) {
                            throw new Exception("La devolución de {$prod['cve_articulo']} es de unidades: debe indicar exactamente "
                                . (float)$cantidad . " VIN(s) (indicados: " . count($ids) . "). Usa la carga masiva de devoluciones.");
                        }
                        foreach ($ids as $idSerie) {
                            if (isset($vinesUsados[$idSerie])) {
                                throw new Exception("Un VIN está repetido en dos partidas del movimiento");
                            }
                            $vin = $this->select(
                                "SELECT id_numeros_serie, numero_serie, estado, inventarioid, almacenid
                                 FROM wms_numeros_series WHERE id_numeros_serie = ? FOR UPDATE",
                                [$idSerie]
                            );
                            if (!$vin || (int)$vin['inventarioid'] !== (int)$inventarioid) {
                                throw new Exception("El VIN indicado no corresponde al producto {$prod['cve_articulo']}");
                            }
                            if ($vin['estado'] !== '3') {
                                throw new Exception("El VIN {$vin['numero_serie']} no está Facturado; no se puede devolver");
                            }
                            $vin['estado_nuevo'] = '1';
                            $vinesUsados[$idSerie] = true;
                            $vinesPartida[] = $vin;
                        }
                    }
                }

                // insertar movimiento
                $idmovinventario = $this->insert("
                INSERT INTO wms_movimientos_inventario
                (inventarioid, almacenid, numero_movimiento, concepmovid, id_proveedor, id_cliente,
                 referencia, lote, cantidad, costo_cantidad, existencia, signo,
                 fecha_movimiento, estado)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),2)
            ", [
                    $inventarioid,
                    $almacenid,
                    $numero_movimiento,
                    $concepmovid,
                    $id_proveedor,
                    $id_cliente,
                    $referencia,
                    $lote,
                    $cantidad,
                    $costo,
                    $nueva_existencia,
                    $signo
                ]);

                // actualizar stock
                $this->insert("
                INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE existencia=?
            ", [
                    $inventarioid,
                    $almacenid,
                    $nueva_existencia,
                    $nueva_existencia
                ]);

                // VINs: salida -> Facturado (3); devolución -> Disponible (1) y almacén
                // de la entrada. En ambos casos queda la trazabilidad.
                foreach ($vinesPartida as $vin) {
                    $this->update(
                        "UPDATE wms_numeros_series SET estado = ?, almacenid = ? WHERE id_numeros_serie = ?",
                        [$vin['estado_nuevo'], $almacenid, $vin['id_numeros_serie']]
                    );
                    $this->insert("
                    INSERT INTO wms_movimientos_inventario_series
                    (idmovinventario, numero_movimiento, id_numeros_serie, numero_serie,
                     almacenid, signo, estado_anterior, usuarioid, fecha)
                    VALUES (?,?,?,?,?,?,?,?,NOW())
                ", [
                        $idmovinventario,
                        $numero_movimiento,
                        $vin['id_numeros_serie'],
                        $vin['numero_serie'],
                        $almacenid,
                        $signo,
                        $vin['estado'],
                        $usuarioid
                    ]);
                }

                $insertados++;
            }

            if ($insertados == 0) {
                throw new Exception("No se insertó ninguna partida válida");
            }

            $pdo->commit();

            return [
                'numero_movimiento' => $numero_movimiento,
                'almacenid' => $almacenid
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return $e->getMessage();
        }
    }




    /**
     * ¿Se pueden vender unidades desde este almacén? Solo Producto Terminado
     * (la clave lleva "ALM-PT", p. ej. PL01-ALM-PT) y el almacén de Tránsito
     * (ALM-TRANSITO).
     */
    public function almacenVentaUnidades(int $almacenid): array
    {
        $r = $this->select(
            "SELECT cve_almacen, descripcion,
                    (UPPER(TRIM(cve_almacen)) REGEXP '(^|-)ALM-PT(-|$)'
                     OR UPPER(TRIM(cve_almacen)) = 'ALM-TRANSITO'
                     OR UPPER(TRIM(descripcion)) IN ('ALMACEN DE TRANSITO', 'ALMACÉN DE TRANSITO', 'ALMACÉN DE TRÁNSITO')) AS permitido
             FROM wms_almacenes WHERE idalmacen = ?",
            [$almacenid]
        );
        return [
            'permitido' => !empty($r) && (int) $r['permitido'] === 1,
            'nombre' => !empty($r) ? $r['descripcion'] : (string) $almacenid,
        ];
    }

    public function selectConceptoInfo($id)
    {
        $id = intval($id);
        return $this->select("SELECT cpn, signo
        FROM wms_conceptos_mov 
        WHERE idconcepmov = $id
    ");
    }

    public function getMovimientoReporte($numero, $almacenid)
{
    return $this->select("
        SELECT 
            m.numero_movimiento,
            a.descripcion AS almacen,
            c.descripcion AS concepto,
            m.referencia,
            m.lote,
            m.fecha_movimiento
        FROM wms_movimientos_inventario m
        INNER JOIN wms_almacenes a ON a.idalmacen = m.almacenid
        INNER JOIN wms_conceptos_mov c ON c.idconcepmov = m.concepmovid
        WHERE m.numero_movimiento = '$numero'
          AND m.almacenid = $almacenid
        LIMIT 1
    ");
}

public function getDetalleMovimientoReporte($numero, $almacenid)
{
    return $this->select_all("
        SELECT 
            i.cve_articulo,
            i.descripcion,
            m.cantidad,
            m.signo,
            m.costo_cantidad,
            (m.cantidad * m.signo * m.costo_cantidad) AS total,
            (SELECT GROUP_CONCAT(s.numero_serie ORDER BY s.numero_serie SEPARATOR ', ')
             FROM wms_movimientos_inventario_series s
             WHERE s.idmovinventario = m.idmovinventario) AS vines
        FROM wms_movimientos_inventario m
        INNER JOIN wms_inventario i ON i.idinventario = m.inventarioid
        WHERE m.numero_movimiento = '$numero'
          AND m.almacenid = $almacenid
    ");
}

}
