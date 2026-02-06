<?php

class Wms_proveedoresModel extends Mysql{
    
public function selectProveedores()
    {
        $sql = "SELECT
                idproveedor,
                clv_proveedor,
                rfc,
                razon_social,
                nombre_comercial,
                contacto,
                correo_electronico,
                telefono,
                direccion_fiscal,
                limite_credito,
                dias_credito,
                metodo_pago_predeterminado,
                idmoneda_predeterminada,
                fecha_registro,
                estado
            FROM wms_proveedores
            WHERE estado = 2;";

        return $this->select_all($sql);
    }
}