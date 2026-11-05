<?php

// Catálogo de colores del Portal Web: web_colores
// (antes wms_colores_unidad). Estado: 0 = eliminado, 1 = inactivo, 2 = activo.
class Inv_coloresunidadModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function selectColores()
    {
        $sql = "SELECT c.idcolor, c.nombre, c.codigo, c.codigo_hex, c.estado, c.fecha_creacion,
                (SELECT COUNT(*) FROM web_unidades_colores uc
                 WHERE uc.idcolor = c.idcolor AND uc.estado = 2) AS unidades
            FROM web_colores c
            WHERE c.estado != 0
            ORDER BY c.nombre";
        return $this->select_all($sql);
    }

    public function selectColor(int $idcolor)
    {
        $sql = "SELECT idcolor, nombre, codigo, codigo_hex, estado, fecha_creacion
            FROM web_colores
            WHERE idcolor = ? AND estado != 0";
        return $this->select($sql, [$idcolor]);
    }

    // Nombre y código son únicos en web_colores (incluye los eliminados,
    // estado 0), por eso se buscan sin filtrar estado.
    private function buscarPorNombre(string $nombre, int $excluirId = 0)
    {
        $sql = "SELECT idcolor, estado FROM web_colores WHERE nombre = ? AND idcolor != ?";
        return $this->select($sql, [$nombre, $excluirId]);
    }

    private function buscarPorCodigo(?string $codigo, int $excluirId = 0)
    {
        if ($codigo === null) {
            return null;
        }
        $sql = "SELECT idcolor, estado FROM web_colores WHERE codigo = ? AND idcolor != ?";
        return $this->select($sql, [$codigo, $excluirId]);
    }

    public function insertColor(string $nombre, ?string $codigo, ?string $hex, int $estado)
    {
        $previo = $this->buscarPorNombre($nombre);
        if (!empty($previo)) {
            // Si se había eliminado (estado 0) se reactiva en lugar de duplicarlo
            if ((int) $previo['estado'] === 0) {
                $idPrevio = (int) $previo['idcolor'];
                if (!empty($this->buscarPorCodigo($codigo, $idPrevio))) {
                    return "exist_codigo";
                }
                $ok = $this->update(
                    "UPDATE web_colores SET codigo = ?, codigo_hex = ?, estado = ? WHERE idcolor = ?",
                    [$codigo, $hex, $estado, $idPrevio]
                );
                return $ok ? $idPrevio : false;
            }
            return "exist";
        }

        if (!empty($this->buscarPorCodigo($codigo))) {
            return "exist_codigo";
        }

        $sql = "INSERT INTO web_colores (nombre, codigo, codigo_hex, estado) VALUES (?,?,?,?)";
        return $this->insert($sql, [$nombre, $codigo, $hex, $estado]);
    }

    public function updateColor(int $idcolor, string $nombre, ?string $codigo, ?string $hex, int $estado)
    {
        if (!empty($this->buscarPorNombre($nombre, $idcolor))) {
            return "exist";
        }
        if (!empty($this->buscarPorCodigo($codigo, $idcolor))) {
            return "exist_codigo";
        }

        $sql = "UPDATE web_colores
            SET nombre = ?, codigo = ?, codigo_hex = ?, estado = ?
            WHERE idcolor = ?";
        return $this->update($sql, [$nombre, $codigo, $hex, $estado, $idcolor]);
    }

    // Baja lógica (estado 0). Las asignaciones y fotos de las unidades se
    // conservan; el portal deja de mostrar el color porque solo lee estado 2.
    public function deleteColor(int $idcolor)
    {
        $sql = "UPDATE web_colores SET estado = 0 WHERE idcolor = ?";
        return $this->update($sql, [$idcolor]);
    }
}
