<?php

class Prv_proveedorModel extends Mysql
{
    use Auditable;

    public function getTableName(): string 
    {
        return "prv_proveedores";
    }

    public function findByCriteria(array $filters)
    {
        $sql = "SELECT
                idproveedor,
                clv_proveedor,
                tipo_persona,
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
                created_at,
                estatus
            FROM prv_proveedores
            WHERE true\n";

        if(array_key_exists('idproveedor', $filters)){ $sql .= "AND idproveedor = '{$filters['idproveedor']}'"; }
        if(array_key_exists('clv_proveedor', $filters)){ $sql .= "AND clv_proveedor = '{$filters['clv_proveedor']}'"; }
        if(array_key_exists('estado', $filters)){ $sql .= "AND estado = '{$filters['estado']}'"; }

        return $this->select_all($sql);
    }

    /**
     * Guarda o actualiza un proveedor.
     */
    public function save(array $data): int
    {
        $sql = "INSERT INTO prv_proveedores (
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
                    estatus
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $params = [
            $data['clv_proveedor'],
            $data['rfc'],
            $data['razon_social'],
            $data['nombre_comercial'],
            $data['contacto'],
            $data['correo_electronico'],
            $data['telefono'],
            trim($data['direccion_fiscal']),
            $data['limite_credito'],
            $data['dias_credito'],
            $data['metodo_pago_predeterminado'],
            $data['idmoneda_predeterminada'],
            $data['estatus'] ?? 1,
        ];

        return $this->insert($sql, $params);
    }

    public function updateData(array $data)
    {
        $sql = "UPDATE prv_proveedores
                SET 
                    razon_social = ?,
                    tipo_persona = ?,
                    clv_proveedor = ?,
                    nombre_comercial = ?, 
                    contacto = ?,
                    telefono = ?,
                    correo_electronico = ?,
                    direccion_fiscal = ?, 
                    idmoneda_predeterminada = ?,
                    limite_credito = ?,
                    dias_credito = ?,
                    metodo_pago_predeterminado = ?, 
                    estatus = ?
                WHERE idproveedor = ?";
        $params = [
            $data['razon_social'],
            $data['tipo_persona'],
            $data['clv_proveedor'],
            $data['nombre_comercial'],
            $data['contacto'],
            $data['telefono'],
            $data['correo_electronico'],
            trim($data['direccion_fiscal']),
            $data['idmoneda_predeterminada'],
            $data['limite_credito'],
            $data['dias_credito'],
            $data['metodo_pago_predeterminado'],
            $data['estatus'] == 'on' ? 2 : 1,
            $data['idproveedor'],
        ];
        return $this->update($sql, $params);
    }

    public function getKpi()
    {
        return $this->select_all(
            "SELECT 
                IFNULL(estatus, 'total') AS estatus,
                COUNT(idproveedor) AS cantidad
            FROM prv_proveedores
            GROUP BY estatus WITH ROLLUP;
            "
        );
    }
}