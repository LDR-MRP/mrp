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

    public function insertarSeriesConfirmadas($lista, $inventarioid, $almacenid, $referencia, $costo)
    {
        $fecha = date('Y-m-d H:i:s');
        $insertados = 0;

        foreach ($lista as $vin) {

            $vin = strtoupper(trim($vin));

            // 🔒 VALIDACIONES VIN OFICIALES
            if (strlen($vin) != 17) {
                continue;
            }

            if (preg_match('/[IOQ]/', $vin)) {
                continue;
            }

            // 🔒 Protección contra concurrencia
            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vin));

            if (!empty($existente)) {
                continue;
            }

            if (strlen($vin) != 17) {
                continue;
            }

            if (!preg_match('/^[A-Z0-9]{17}$/', $vin)) {
                continue;
            }

            if (preg_match('/[IOQÑ]/', $vin)) {
                continue;
            }


            $sql = "INSERT INTO wms_numeros_series
            (inventarioid, almacenid, numero_serie, referencia, costo, fecha, estado)
            VALUES (?,?,?,?,?,?,?)";

            $arrData = array(
                $inventarioid,
                $almacenid,
                $vin,
                $referencia,
                $costo,
                $fecha,
                1
            );

            $insert = $this->insert($sql, $arrData);

            if ($insert) {
                $insertados++;
            }
        }

        return [
            "status" => true,
            "msg" => $insertados . " VIN insertados correctamente"
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
}
