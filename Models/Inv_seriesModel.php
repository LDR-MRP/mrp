<?php

class Inv_seriesModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function selectSeries()
    {
        $sql = "SELECT s.*, 
                   i.descripcion AS producto, 
                   a.descripcion AS almacen
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i 
                ON s.inventarioid = i.idinventario
            INNER JOIN wms_almacenes a 
                ON s.almacenid = a.idalmacen
            WHERE s.estado != 0";

        return $this->select_all($sql);
    }

    public function searchProductos()
    {
        $sql = "SELECT 
                i.idinventario,
                i.cve_articulo,
                i.descripcion,
                i.serie,
                p.idproducto,
                pl.num_orden
            FROM wms_inventario i
            INNER JOIN mrp_productos p 
                ON p.inventarioid = i.idinventario
            LEFT JOIN mrp_planeacion pl 
                ON pl.productoid = p.idproducto
            ORDER BY i.descripcion ASC";

        return $this->select_all($sql);
    }

    public function searchOrdenesTrabajo()
    {
        $sql = "SELECT 
                pl.idplaneacion,
                pl.num_orden,
                pl.cantidad,
                i.idinventario,
                i.descripcion AS producto
            FROM mrp_planeacion pl
            INNER JOIN mrp_productos p 
                ON pl.productoid = p.idproducto
            INNER JOIN wms_inventario i 
                ON p.inventarioid = i.idinventario
            ORDER BY pl.fecha_requerida DESC";

        return $this->select_all($sql);
    }


    public function selectAlmacenesSeries()
    {
        $sql = "SELECT idalmacen, cve_almacen, descripcion
            FROM wms_almacenes
            WHERE estado = 2
            ORDER BY descripcion ASC";

        return $this->select_all($sql);
    }


    public function validarSeries($inventarioid, $almacenid, $baseVin, $cantidad)
    {
        if ($cantidad <= 0) {
            return ["status" => false, "msg" => "Cantidad inválida"];
        }

        $baseVin = strtoupper(trim($baseVin));

        // 🔒 Obligatorio mínimo 11
        if (strlen($baseVin) < 11) {
            return ["status" => false, "msg" => "Los primeros 11 caracteres del VIN son obligatorios"];
        }

        // 🔒 Máximo 17
        if (strlen($baseVin) > 17) {
            return ["status" => false, "msg" => "El VIN no puede exceder 17 caracteres"];
        }

        // 🔒 Solo letras y números
        if (!preg_match('/^[A-Z0-9]+$/', $baseVin)) {
            return ["status" => false, "msg" => "El VIN solo puede contener letras y números"];
        }

        // 🔒 No permitir I O Q
        if (preg_match('/[IOQÑ]/', $baseVin)) {
            return ["status" => false, "msg" => "El VIN no puede contener I, O o Q"];
        }


        $parteFija = $baseVin;
        $contador = 1;

        if (strlen($baseVin) == 17) {

            if (preg_match('/(\d+)$/', $baseVin, $matches)) {
                $numeroBase = $matches[1];
                $longitudNumerica = strlen($numeroBase);
                $parteFija = substr($baseVin, 0, -$longitudNumerica);
                $contador = intval($numeroBase);
            } else {
                return ["status" => false, "msg" => "El VIN debe terminar en números"];
            }
        } else {
            $longitudNumerica = 17 - strlen($baseVin);
        }

        $generados = [];
        $repetidos = [];
        $disponibles = [];

        for ($i = 0; $i < $cantidad; $i++) {

            $nuevoNumero = str_pad($contador + $i, $longitudNumerica, "0", STR_PAD_LEFT);
            $vinFinal = $parteFija . $nuevoNumero;

            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vinFinal));

            $generados[] = $vinFinal;

            if (!empty($existente)) {
                $repetidos[] = $vinFinal;
            } else {
                $disponibles[] = $vinFinal;
            }
        }

        return [
            "status" => true,
            "generados" => $generados,
            "repetidos" => $repetidos,
            "disponibles" => $disponibles
        ];
    }


    public function insertarSeriesConfirmadas($lista, $inventarioid, $almacenid, $referencia, $costo, $modo)
    {
        $fecha = date('Y-m-d H:i:s');
        $insertados = 0;

        $cantidad = count($lista);

        // 🔥 base del VIN (sin consecutivo)
        $baseVin = substr($lista[0], 0, -6);

        // ============================
        // 🔒 VALIDAR ORDEN (AQUÍ VA)
        // ============================
        if ($modo === "orden") {
            $sql = "SELECT COUNT(*) as total 
            FROM wms_numeros_series 
            WHERE referencia = ?";

            $existe = $this->select($sql, [$referencia]);

            if ($existe['total'] > 0) {
                return [
                    "status" => false,
                    "msg" => "Esta orden ya tiene VIN generados"
                ];
            }
        }

        // ============================
        // 🔥 DEFINIR CONTADOR
        // ============================
        if ($modo === "lote") {
            // ✅ LOTE: continuar consecutivo
            $ultimo = $this->getUltimoConsecutivo($baseVin);
            $contador = $ultimo + 1;
        } else {
            // ❌ ORDEN: SIEMPRE iniciar desde 1
            $contador = 1;
        }

        for ($i = 0; $i < $cantidad; $i++) {

            $consecutivo = str_pad($contador + $i, 6, "0", STR_PAD_LEFT);
            $vin = $baseVin . $consecutivo;

            // validaciones
            if (strlen($vin) != 17) continue;
            if (preg_match('/[IOQÑ]/', $vin)) continue;
            if (!preg_match('/^[A-Z0-9]{17}$/', $vin)) continue;

            // 🔥 IMPORTANTE: en orden NO permitir duplicados
            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vin));

            if (!empty($existente)) {
                // 🔥 si es orden, error directo
                if ($modo === "orden") {
                    return [
                        "status" => false,
                        "msg" => "La orden ya tiene VIN generados o hay duplicados."
                    ];
                }
                continue;
            }

            $sql = "INSERT INTO wms_numeros_series
        (inventarioid, almacenid, numero_serie, referencia, costo, fecha, estado, tipo_generacion)
        VALUES (?,?,?,?,?,?,?,?)";

            $arrData = [
                $inventarioid,
                $almacenid,
                $vin,
                $referencia,
                $costo,
                $fecha,
                1,
                $modo
            ];

            $insert = $this->insert($sql, $arrData);

            if ($insert) {
                $insertados++;
            }
        }

        return [
            "status" => $insertados > 0,
            "msg" => $insertados > 0
                ? "$insertados VIN insertados correctamente"
                : "No se insertó ningún VIN"
        ];
    }


    public function getSerieByVin($vin)
    {
        $sql = "SELECT s.numero_serie,
                   s.referencia,
                   i.descripcion AS producto
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i 
                ON s.inventarioid = i.idinventario
            WHERE s.numero_serie = ?";

        return $this->select($sql, array($vin));
    }

    public function validarOrdenTrabajoPorOrden($num_orden)
    {
        $sql = "SELECT idplaneacion 
            FROM mrp_planeacion 
            WHERE num_orden = ?
            LIMIT 1";

        return $this->select($sql, array($num_orden));
    }

    public function getSeriesByOrden($orden)
    {
        $sql = "SELECT s.numero_serie,
                   s.referencia,
                   i.descripcion AS producto
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i 
                ON s.inventarioid = i.idinventario
            WHERE s.referencia = ?
            ORDER BY s.numero_serie ASC";

        return $this->select_all($sql, array($orden));
    }

    public function getUltimoConsecutivo($baseVin)
    {
        // separar partes
        $parte1 = substr($baseVin, 0, 8);  // antes del dígito 9
        $parte2 = substr($baseVin, 9);     // después del dígito 9

        // patrón con wildcard en posición 9
        $like = $parte1 . "_" . $parte2 . "%";

        $sql = "SELECT numero_serie 
            FROM wms_numeros_series 
            WHERE numero_serie LIKE ?
            ORDER BY numero_serie DESC 
            LIMIT 1";

        $result = $this->select($sql, array($like));

        if ($result) {
            $numero = substr($result['numero_serie'], -6);
            return intval($numero);
        }

        return 0;
    }
}
