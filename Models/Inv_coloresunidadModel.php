<?php

class Inv_coloresunidadModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function selectColores()
    {
        $sql = "SELECT idcolor, nombre, descripcion, estado, fecha_creacion
            FROM wms_colores_unidad
            WHERE estado != 0
            ORDER BY nombre";
        return $this->select_all($sql);
    }

    public function selectColor(int $idcolor)
    {
        $sql = "SELECT idcolor, nombre, descripcion, estado, fecha_creacion
            FROM wms_colores_unidad
            WHERE idcolor = ? AND estado != 0";
        return $this->select($sql, [$idcolor]);
    }

    private function buscarPorNombre(string $nombre, int $excluirId = 0)
    {
        $sql = "SELECT idcolor, estado FROM wms_colores_unidad WHERE nombre = ? AND idcolor != ?";
        return $this->select($sql, [$nombre, $excluirId]);
    }

    public function insertColor(string $nombre, string $descripcion, int $estado)
    {
        $previo = $this->buscarPorNombre($nombre);
        if (!empty($previo)) {
            // Si se había eliminado (estado 0) se reactiva en lugar de duplicarlo
            if ((int) $previo['estado'] === 0) {
                $ok = $this->update(
                    "UPDATE wms_colores_unidad SET descripcion = ?, estado = ?, fecha_actualizacion = NOW() WHERE idcolor = ?",
                    [$descripcion, $estado, (int) $previo['idcolor']]
                );
                return $ok ? (int) $previo['idcolor'] : false;
            }
            return "exist";
        }

        $sql = "INSERT INTO wms_colores_unidad (nombre, descripcion, estado, fecha_creacion)
            VALUES (?,?,?,NOW())";
        return $this->insert($sql, [$nombre, $descripcion, $estado]);
    }

    public function updateColor(int $idcolor, string $nombre, string $descripcion, int $estado)
    {
        if (!empty($this->buscarPorNombre($nombre, $idcolor))) {
            return "exist";
        }

        $sql = "UPDATE wms_colores_unidad
            SET nombre = ?, descripcion = ?, estado = ?, fecha_actualizacion = NOW()
            WHERE idcolor = ?";
        return $this->update($sql, [$nombre, $descripcion, $estado, $idcolor]);
    }

    // Baja lógica (estado 0). Si una unidad del portal lo tiene asignado, el
    // portal deja de mostrarlo porque solo lee colores con estado 2.
    public function deleteColor(int $idcolor)
    {
        $sql = "UPDATE wms_colores_unidad SET estado = 0, fecha_actualizacion = NOW() WHERE idcolor = ?";
        return $this->update($sql, [$idcolor]);
    }
}
