<?php

class Wms_monedasModel extends Mysql{
    
public function selectMonedas()
    {
        $sql = "SELECT
                idmoneda,
                descripcion,
                simbolo,
                tipo_cambio,
                fecha_ult_cambio,
                cve_moneda,
                estado
            FROM wms_moneda
            WHERE estado = 2;";

        return $this->select_all($sql);
    }
}