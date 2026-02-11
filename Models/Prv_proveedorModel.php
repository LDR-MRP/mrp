<?php

class Prv_proveedorModel extends Mysql
{
    use Auditable;

    public function getTableName(): string 
    {
        return "prv_proveedores";
    }

    public function suppliers(array $filters)
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
            WHERE true";

        if(array_key_exists('clv_proveedor', $filters)){ $sql .= "AND clv_proveedor = '{$filters['clv_proveedor']}'"; }
        if(array_key_exists('estado', $filters)){ $sql .= "AND estado = '{$filters['estado']}'"; }

        return $this->select_all($sql);
    }

    /**
     * Guarda o actualiza un proveedor.
     */
    public function save(array $data, int $id = null): int|bool
    {
        if ($id) {
            $sql = "UPDATE prv_proveedores SET 
                        clv_proveedor = ?, rfc = ?, razon_social = ?, nombre_comercial = ?, 
                        contacto = ?, correo_electronico = ?, telefono = ?, direccion_fiscal = ?, 
                        limite_credito = ?, dias_credito = ?, metodo_pago_predeterminado = ?, 
                        idmoneda_predeterminada = ? 
                    WHERE idproveedor = ?";
            $params = [...array_values($data), $id];
            return $this->update($sql, $params);
        } else {
            $sql = "INSERT INTO prv_proveedores (
                        clv_proveedor, rfc, razon_social, nombre_comercial, contacto, 
                        correo_electronico, telefono, direccion_fiscal, limite_credito, 
                        dias_credito, metodo_pago_predeterminado, idmoneda_predeterminada
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            return $this->insert($sql, array_values($data));
        }
    }

    public function updateStatus(int $id, int $status): bool
    {
        $sql = "UPDATE prv_proveedores SET estado = ? WHERE idproveedor = ?";
        return $this->update($sql, [$status, $id]);
    }
}