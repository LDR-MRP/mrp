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
        $sql = "SELECT idinventario, cve_articulo, descripcion, serie
            FROM wms_inventario";

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

    public function generarSeries($inventarioid, $almacenid, $baseVin, $cantidad, $costo, $referencia)
    {
        if($cantidad <= 0){
    return ["status" => false, "msg" => "Cantidad inválida"];
}

        $fecha = date('Y-m-d H:i:s');
        $baseVin = strtoupper(trim($baseVin));

        if (strlen($baseVin) > 17) {
            return ["status" => false, "msg" => "VIN inválido"];
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
                return ["status" => false, "msg" => "El VIN completo debe terminar en números"];
            }
        } else {
            $longitudNumerica = 17 - strlen($baseVin);
        }

        $insertados = 0;
        $duplicados = [];

        for ($i = 0; $i < $cantidad; $i++) {

            $nuevoNumero = str_pad($contador + $i, $longitudNumerica, "0", STR_PAD_LEFT);
            $vinFinal = $parteFija . $nuevoNumero;

            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vinFinal));

            if (!empty($existente)) {
                $duplicados[] = $vinFinal;
                continue;
            }

            $sql = "INSERT INTO wms_numeros_series
                (inventarioid, almacenid, numero_serie, referencia, costo, fecha, estado)
                VALUES (?,?,?,?,?,?,?)";

            $arrData = array(
                $inventarioid,
                $almacenid,
                $vinFinal,
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
            "insertados" => $insertados,
            "duplicados" => $duplicados
        ];
    }


    public function validarSeries($inventarioid, $almacenid, $baseVin, $cantidad)
    {
        if ($cantidad <= 0) {
            return ["status" => false, "msg" => "Cantidad inválida"];
        }

        $baseVin = strtoupper(trim($baseVin));

        if (strlen($baseVin) > 17) {
            return ["status" => false, "msg" => "VIN inválido"];
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

        // 🔥 Protección contra concurrencia
        $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
        $existente = $this->select($sqlCheck, array($vin));

        if (!empty($existente)) {
            continue; // ya existe, lo saltamos
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


    
}
