<?php

class Inv_movimientosalmacenesModel extends Mysql
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
        float $costo_cantidad
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
        $folio = 'MOV-' . date('YmdHis') . '-' . rand(100, 999);


        // 4. Insertar movimiento
        $this->insert(
            "INSERT INTO wms_movimientos_inventario
        (inventarioid, almacenid, numero_movimiento, concepmovid,
         referencia, cantidad, costo_cantidad, existencia, signo,
         fecha_movimiento, estado)
        VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)",
            [
                $inventarioid,
                $almacenid,
                $folio,
                $concepmovid,
                $referencia,
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
        $where = "WHERE m.estado = 2 AND m.concepmovid IN (16,17)";

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
        AND idconcepmov IN (16,17)
    ");
    }

    public function selectInventarioPredictivo()
    {
        return $this->select_all("
        SELECT idinventario, cve_articulo, descripcion
        FROM wms_inventario
        WHERE estado = 2
    ");
    }

    public function insertMovimientoMasivo(
        int $almacenid,
        int $concepmovid,
        string $referencia,
        array $inventarios,
        array $cantidades,
        array $costos
    ) {
        $concepto = $this->select("SELECT signo FROM wms_conceptos_mov WHERE idconcepmov = $concepmovid");
        if (!$concepto) return "Concepto inválido";

        $signo = (int)$concepto['signo'];


        try {

            $folio = 'MOV-' . date('YmdHis') . '-' . rand(100, 999);

            $insertados = 0;

            foreach ($inventarios as $i => $inventarioid) {

                if (empty($inventarioid) || empty($cantidades[$i])) continue;

                $cantidad = (float)$cantidades[$i];
                $costo    = (float)$costos[$i];

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

                // insertar movimiento
                $this->insert("
                INSERT INTO wms_movimientos_inventario
                (inventarioid, almacenid, numero_movimiento, concepmovid,
                 referencia, cantidad, costo_cantidad, existencia, signo,
                 fecha_movimiento, estado)
                VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)
            ", [
                    $inventarioid,
                    $almacenid,
                    $folio,
                    $concepmovid,
                    $referencia,
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

                $insertados++;
            }

            if ($insertados == 0) {
                throw new Exception("No se insertó ninguna partida válida");
            }

            return [
                'numero_movimiento' => $folio,
                'almacenid' => $almacenid
            ];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }




    public function selectConceptoInfo($id)
    {
        return $this->select("SELECT cpn 
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
            i.descripcion,
            m.cantidad,
            m.signo,
            m.costo_cantidad,
            (m.cantidad * m.signo * m.costo_cantidad) AS total
        FROM wms_movimientos_inventario m
        INNER JOIN wms_inventario i ON i.idinventario = m.inventarioid
        WHERE m.numero_movimiento = '$numero'
          AND m.almacenid = $almacenid
    ");
    }

    public function insertTransferencia(
        int $almacen_origenid,
        int $almacen_destinoid,
        string $referencia,
        array $inventarios,
        array $cantidades,
        array $costos
    ) {

        // 🔥 GENERAR FOLIO STRING
        $folio = 'TRF-' . date('YmdHis') . '-' . rand(100, 999);

        // Insertar encabezado
        $movimientoid = $this->insert(
            "INSERT INTO wms_movimientos_almacenes
        (folio, almacen_origenid, almacen_destinoid, referencia, fecha, estado)
        VALUES (?,?,?,?,NOW(),2)",
            [$folio, $almacen_origenid, $almacen_destinoid, $referencia]
        );

        foreach ($inventarios as $i => $inventarioid) {

            if (empty($inventarioid) || empty($cantidades[$i])) continue;

            $cantidad = (float)$cantidades[$i];
            $costo    = (float)$costos[$i];

            // STOCK ORIGEN
            $rowOrigen = $this->select("
            SELECT existencia 
            FROM wms_multialmacen
            WHERE inventarioid = $inventarioid
            AND almacenid = $almacen_origenid
        ");

            if (!$rowOrigen || $rowOrigen['existencia'] < $cantidad) {
                return "Stock insuficiente en almacén origen";
            }

            $nuevoOrigen = $rowOrigen['existencia'] - $cantidad;

            // STOCK DESTINO
            $rowDestino = $this->select("
            SELECT existencia 
            FROM wms_multialmacen
            WHERE inventarioid = $inventarioid
            AND almacenid = $almacen_destinoid
        ");

            $existDestino = $rowDestino ? $rowDestino['existencia'] : 0;
            $nuevoDestino = $existDestino + $cantidad;

            // Insertar detalle
            $this->insert("
            INSERT INTO wms_movimientos_almacenes_detalle
            (movimientoalmacenid, inventarioid, cantidad, costo_unitario)
            VALUES (?,?,?,?)
        ", [$movimientoid, $inventarioid, $cantidad, $costo]);

            // Actualizar stocks
            $this->insert("
            INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE existencia=?
        ", [$inventarioid, $almacen_origenid, $nuevoOrigen, $nuevoOrigen]);

            $this->insert("
            INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE existencia=?
        ", [$inventarioid, $almacen_destinoid, $nuevoDestino, $nuevoDestino]);

            // SALIDA (16)
            $this->insert("
            INSERT INTO wms_movimientos_inventario
            (inventarioid, almacenid, numero_movimiento, concepmovid,
             referencia, cantidad, costo_cantidad, existencia, signo,
             fecha_movimiento, estado)
            VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)
        ", [
                $inventarioid,
                $almacen_origenid,
                $folio,
                16,
                $referencia,
                $cantidad,
                $costo,
                $nuevoOrigen,
                -1
            ]);

            // ENTRADA (17)
            $this->insert("
            INSERT INTO wms_movimientos_inventario
            (inventarioid, almacenid, numero_movimiento, concepmovid,
             referencia, cantidad, costo_cantidad, existencia, signo,
             fecha_movimiento, estado)
            VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)
        ", [
                $inventarioid,
                $almacen_destinoid,
                $folio,
                17,
                $referencia,
                $cantidad,
                $costo,
                $nuevoDestino,
                1
            ]);
        }

        return $folio;
    }
}
