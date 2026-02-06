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
        $numRow = $this->select(
            "SELECT IFNULL(MAX(numero_movimiento),0)+1 AS num
     FROM wms_movimientos_inventario
     WHERE almacenid = $almacenid"
        );
        $num = (int)$numRow['num'];


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
                $num,
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


    public function selectMovimientos()
    {
        $sql = "SELECT 
            m.idmovinventario,
            i.descripcion AS producto,
            a.descripcion AS almacen,
            c.descripcion AS concepto,
            m.referencia,
            CONCAT(m.signo * m.cantidad) AS cantidad,
            m.existencia,
            m.fecha_movimiento
        FROM wms_movimientos_inventario m
        INNER JOIN wms_inventario i ON i.idinventario = m.inventarioid
        INNER JOIN wms_almacenes a ON a.idalmacen = m.almacenid
        INNER JOIN wms_conceptos_mov c ON c.idconcepmov = m.concepmovid
        WHERE m.estado = 2
        ORDER BY m.idmovinventario DESC
    ";
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
){
    $concepto = $this->select("SELECT signo FROM wms_conceptos_mov WHERE idconcepmov = $concepmovid");
    if(!$concepto) return "Concepto inválido";

    $signo = (int)$concepto['signo'];


    try {

        $numRow = $this->select("
            SELECT IFNULL(MAX(numero_movimiento),0)+1 AS num
            FROM wms_movimientos_inventario
            WHERE almacenid = $almacenid
        ");
        $num = (int)$numRow['num'];

        $insertados = 0;

        foreach($inventarios as $i => $inventarioid){

            if(empty($inventarioid) || empty($cantidades[$i])) continue;

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

            if($nueva_existencia < 0){
                throw new Exception("Stock insuficiente en producto ID $inventarioid");
            }

            // insertar movimiento
            $this->insert("
                INSERT INTO wms_movimientos_inventario
                (inventarioid, almacenid, numero_movimiento, concepmovid,
                 referencia, cantidad, costo_cantidad, existencia, signo,
                 fecha_movimiento, estado)
                VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)
            ",[
                $inventarioid,
                $almacenid,
                $num,
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
            ",[
                $inventarioid,
                $almacenid,
                $nueva_existencia,
                $nueva_existencia
            ]);

            $insertados++;
        }

        if($insertados == 0){
            throw new Exception("No se insertó ninguna partida válida");
        }

        return true;

    } catch(Exception $e){
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

    
}
