<?php

class Ing_motoresModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertMotor(array $data)
    {
        $sql = "SELECT id_motor FROM ing_cat_motor WHERE fabricante = ? AND modelo_motor = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['fabricante'], $data['modelo_motor']]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_cat_motor
            (fabricante, modelo_motor, tipo_motor, cilindrada, numero_cilindros, potencia, unidad_potencia,
             torque, unidad_torque, tipo_combustible, tipo_admision, fabricante_bateria, tipo_bateria,
             capacidad_bateria, consumo, conector, proteccion_ip, sistema_electrico, activo, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['fabricante'], $data['modelo_motor'], $data['tipo_motor'], $data['cilindrada'],
            $data['numero_cilindros'], $data['potencia'], $data['unidad_potencia'], $data['torque'],
            $data['unidad_torque'], $data['tipo_combustible'], $data['tipo_admision'],
            $data['fabricante_bateria'], $data['tipo_bateria'], $data['capacidad_bateria'], $data['consumo'],
            $data['conector'], $data['proteccion_ip'], $data['sistema_electrico'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null,
        ]);
    }

    public function updateMotor(int $id, array $data)
    {
        $sql = "SELECT id_motor FROM ing_cat_motor WHERE fabricante = ? AND modelo_motor = ? AND id_motor != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['fabricante'], $data['modelo_motor'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_cat_motor SET
            fabricante = ?, modelo_motor = ?, tipo_motor = ?, cilindrada = ?, numero_cilindros = ?,
            potencia = ?, unidad_potencia = ?, torque = ?, unidad_torque = ?, tipo_combustible = ?,
            tipo_admision = ?, fabricante_bateria = ?, tipo_bateria = ?, capacidad_bateria = ?, consumo = ?,
            conector = ?, proteccion_ip = ?, sistema_electrico = ?, activo = ?, updated_by = ?
            WHERE id_motor = ?";

        return $this->update($sql, [
            $data['fabricante'], $data['modelo_motor'], $data['tipo_motor'], $data['cilindrada'],
            $data['numero_cilindros'], $data['potencia'], $data['unidad_potencia'], $data['torque'],
            $data['unidad_torque'], $data['tipo_combustible'], $data['tipo_admision'],
            $data['fabricante_bateria'], $data['tipo_bateria'], $data['capacidad_bateria'], $data['consumo'],
            $data['conector'], $data['proteccion_ip'], $data['sistema_electrico'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null, $id,
        ]);
    }

    public function selectMotores()
    {
        $sql = "SELECT * FROM ing_cat_motor WHERE deleted_at IS NULL ORDER BY fabricante, modelo_motor";
        return $this->select_all($sql);
    }

    public function selectMotor(int $id)
    {
        $sql = "SELECT * FROM ing_cat_motor WHERE id_motor = ? AND deleted_at IS NULL";
        return $this->select($sql, [$id]);
    }

    public function selectOptionMotores()
    {
        $sql = "SELECT id_motor, fabricante, modelo_motor FROM ing_cat_motor WHERE activo = 1 AND deleted_at IS NULL ORDER BY fabricante, modelo_motor";
        return $this->select_all($sql);
    }

    public function deleteMotor(int $id)
    {
        $sql = "UPDATE ing_cat_motor SET deleted_at = NOW(), activo = 0 WHERE id_motor = ?";
        return $this->update($sql, [$id]);
    }
}
