<?php

class Ing_transmisionesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertTransmision(array $data)
    {
        $sql = "SELECT id_transmision FROM ing_cat_transmision WHERE fabricante = ? AND modelo = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['fabricante'], $data['modelo']]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_cat_transmision
            (fabricante, modelo, tipo, numero_velocidades, descripcion, activo, created_by)
            VALUES (?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['fabricante'], $data['modelo'], $data['tipo'], $data['numero_velocidades'],
            $data['descripcion'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null,
        ]);
    }

    public function updateTransmision(int $id, array $data)
    {
        $sql = "SELECT id_transmision FROM ing_cat_transmision WHERE fabricante = ? AND modelo = ? AND id_transmision != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['fabricante'], $data['modelo'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_cat_transmision SET
            fabricante = ?, modelo = ?, tipo = ?, numero_velocidades = ?,
            descripcion = ?, activo = ?, updated_by = ?
            WHERE id_transmision = ?";

        return $this->update($sql, [
            $data['fabricante'], $data['modelo'], $data['tipo'], $data['numero_velocidades'],
            $data['descripcion'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null, $id,
        ]);
    }

    public function selectTransmisiones()
    {
        $sql = "SELECT * FROM ing_cat_transmision WHERE deleted_at IS NULL ORDER BY fabricante, modelo";
        return $this->select_all($sql);
    }

    public function selectTransmision(int $id)
    {
        $sql = "SELECT * FROM ing_cat_transmision WHERE id_transmision = ? AND deleted_at IS NULL";
        return $this->select($sql, [$id]);
    }

    public function selectOptionTransmisiones()
    {
        $sql = "SELECT id_transmision, fabricante, modelo FROM ing_cat_transmision WHERE activo = 1 AND deleted_at IS NULL ORDER BY fabricante, modelo";
        return $this->select_all($sql);
    }

    public function deleteTransmision(int $id)
    {
        $sql = "UPDATE ing_cat_transmision SET deleted_at = NOW(), activo = 0 WHERE id_transmision = ?";
        return $this->update($sql, [$id]);
    }
}
