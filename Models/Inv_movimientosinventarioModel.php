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
        // 1️⃣ Obtener signo del concepto
        $sqlConcepto = "SELECT signo FROM wms_conceptos_mov 
                    WHERE idconcepmov = $concepmovid";
        $concepto = $this->select($sqlConcepto);

        if (empty($concepto)) return 0;

        $signo = intval($concepto['signo']);

        // 2️⃣ Calcular existencia actual desde movimientos
        $sqlExistencia = "
        SELECT IFNULL(SUM(cantidad * signo),0) AS existencia
        FROM wms_movimientos_inventario
        WHERE inventarioid = $inventarioid
        AND almacenid = $almacenid
        AND estado = 2
    ";
        $row = $this->select($sqlExistencia);

        $existencia_actual = floatval($row['existencia']);

        // 3️⃣ Calcular nueva existencia
        $nueva_existencia = $existencia_actual + ($cantidad * $signo);

        // 4️⃣ Validar salidas sin stock
        if ($nueva_existencia < 0) {
            return "stock";
        }
        $sqlNum = "
    SELECT IFNULL(MAX(numero_movimiento),0)+1 AS num
    FROM wms_movimientos_inventario
    WHERE almacenid = $almacenid
";
        $rowNum = $this->select($sqlNum);
        $numero_movimiento = intval($rowNum['num']);


        // 5️⃣ Insertar movimiento
        $sqlInsert = "INSERT INTO wms_movimientos_inventario
                                (
                                inventarioid,
                                almacenid,
                                numero_movimiento,
                                concepmovid,
                                referencia,
                                cantidad,
                                costo_cantidad,
                                existencia,
                                signo,
                                fecha_movimiento,
                                estado
                                )
                    VALUES (?,?,?,?,?,?,?,?,?,NOW(),2)";


        $arrData = [
            $inventarioid,
            $almacenid,
            $numero_movimiento, 
            $concepmovid,
            $referencia,
            $cantidad,
            $costo_cantidad,
            $nueva_existencia,
            $signo
        ];


        $insert = $this->insert($sqlInsert, $arrData);
        return $insert; // debe ser > 0 si insertó
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
}
