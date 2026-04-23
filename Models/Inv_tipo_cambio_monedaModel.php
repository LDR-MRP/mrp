<?php

class Inv_tipo_cambio_monedaModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function insertarTipoCambio($monedaid, $tipo_cambio, $fecha)
    {
        $fechaCompleta = $fecha . " " . date("H:i:s");

        $sql = "INSERT INTO wms_tipo_cambio_moneda 
        (monedaid, tipo_cambio, fecha_creacion, estado)
        VALUES (?, ?, ?, 2)";

        return $this->insert($sql, [$monedaid, $tipo_cambio, $fechaCompleta]);
    }

    public function consultarTipoCambio($moneda, $desde, $hasta)
    {
        $sql = "SELECT 
            t.fecha_creacion, 
            t.tipo_cambio, 
            CONCAT(m.cve_moneda, ' - ', m.descripcion) AS moneda
        FROM wms_tipo_cambio_moneda t
        INNER JOIN wms_moneda m ON m.idmoneda = t.monedaid
        WHERE t.estado = 2";

        $params = [];

        if (!empty($moneda)) {
            $sql .= " AND t.monedaid = ?";
            $params[] = $moneda;
        }

        if (!empty($desde) && !empty($hasta)) {
            $sql .= " AND DATE(t.fecha_creacion) BETWEEN ? AND ?";
            $params[] = $desde;
            $params[] = $hasta;
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";

        return $this->select_all($sql, $params);
    }

    public function existeTipoCambio($monedaid, $fecha)
    {
        $sql = "SELECT idtipocambio 
            FROM wms_tipo_cambio_moneda
            WHERE monedaid = ? 
            AND DATE(fecha_creacion) = ?";

        $result = $this->select($sql, [$monedaid, $fecha]);

        return !empty($result);
    }
}
