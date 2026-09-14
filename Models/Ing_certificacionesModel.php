<?php

class Ing_certificacionesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertCertificacion(array $data)
    {
        $sql = "SELECT id_certificacion FROM ing_cat_certificacion WHERE codigo = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['codigo']]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_cat_certificacion
            (codigo, nombre, descripcion, autoridad, tipo, requiere_documento, requiere_vigencia, activo, created_by)
            VALUES (?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['codigo'], $data['nombre'], $data['descripcion'], $data['autoridad'], $data['tipo'],
            $data['requiere_documento'], $data['requiere_vigencia'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null,
        ]);
    }

    public function updateCertificacion(int $id, array $data)
    {
        $sql = "SELECT id_certificacion FROM ing_cat_certificacion WHERE codigo = ? AND id_certificacion != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['codigo'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_cat_certificacion SET
            codigo = ?, nombre = ?, descripcion = ?, autoridad = ?, tipo = ?,
            requiere_documento = ?, requiere_vigencia = ?, activo = ?, updated_by = ?
            WHERE id_certificacion = ?";

        return $this->update($sql, [
            $data['codigo'], $data['nombre'], $data['descripcion'], $data['autoridad'], $data['tipo'],
            $data['requiere_documento'], $data['requiere_vigencia'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null, $id,
        ]);
    }

    public function selectCertificaciones()
    {
        $sql = "SELECT * FROM ing_cat_certificacion WHERE deleted_at IS NULL ORDER BY codigo";
        return $this->select_all($sql);
    }

    public function selectCertificacion(int $id)
    {
        $sql = "SELECT * FROM ing_cat_certificacion WHERE id_certificacion = ? AND deleted_at IS NULL";
        return $this->select($sql, [$id]);
    }

    public function selectOptionCertificaciones()
    {
        $sql = "SELECT id_certificacion, codigo, nombre FROM ing_cat_certificacion WHERE activo = 1 AND deleted_at IS NULL ORDER BY codigo";
        return $this->select_all($sql);
    }

    public function deleteCertificacion(int $id)
    {
        $sql = "UPDATE ing_cat_certificacion SET deleted_at = NOW(), activo = 0 WHERE id_certificacion = ?";
        return $this->update($sql, [$id]);
    }
}
