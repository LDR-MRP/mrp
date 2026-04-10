<?php

class Inv_ubicacionesModel extends Mysql
{
    public function selectUbicaciones()
    {
        $sql = "SELECT 
                    u.idubicaciones,
                    s.cve_sede,
                    z.cve_zona,
                    u.pasillo,
                    u.seccion,
                    u.nivel,
                    u.lugar,
                    u.descripcion,
                    u.fecha_creacion,
                    u.estado
                FROM wms_ubicaciones u
                INNER JOIN wms_zonas z ON u.zonaid = z.idzona
                INNER JOIN wms_sedes s ON z.sedeid = s.idsede";

        return $this->select_all($sql);
    }

    public function insertUbicacion($data)
    {
        $sql = "INSERT INTO wms_ubicaciones
            (zonaid, pasillo, seccion, nivel, lugar, descripcion, estado, fecha_creacion)
            VALUES (?, ?, ?, ?, ?, ?, 2, ?)";

        $arrData = [
            $data['zonaid'],
            $data['pasillo'],
            $data['seccion'],
            $data['nivel'],
            $data['lugar'],
            $data['descripcion'],
            date("Y-m-d H:i:s")
        ];

        return $this->insert($sql, $arrData);
    }

    public function existeUbicacion($zonaid, $lugar)
    {
        $sql = "SELECT idubicaciones 
            FROM wms_ubicaciones 
            WHERE zonaid = ? AND lugar = ? AND estado = 2";

        $arrData = [$zonaid, $lugar];

        $result = $this->select($sql, $arrData);

        return !empty($result);
    }
}
