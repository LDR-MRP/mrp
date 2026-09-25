<?php

class Ing_modelosModel extends Mysql
{
    use Auditable;

    protected string $table = 'ing_modelo_detalle';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function selectModelos()
    {
        $sql = "SELECT
                    s.idsublineaproducto,
                    s.cve_sublinea_producto,
                    s.descripcion AS modelo,
                    l.idlineaproducto,
                    l.descripcion AS segmento,
                    d.id_modelo_detalle,
                    d.marca,
                    d.tipo_carroceria,
                    d.estado,
                    d.version,
                    d.fecha_inicio,
                    d.fecha_fin
                FROM wms_sublinea_producto s
                INNER JOIN wms_linea_producto l ON s.lineaproductoid = l.idlineaproducto
                LEFT JOIN ing_modelo_detalle d ON d.id_sublineaproducto = s.idsublineaproducto AND d.deleted_at IS NULL
                WHERE s.estado != 0
                ORDER BY l.descripcion, s.descripcion";

        return $this->select_all($sql);
    }

    public function selectModelo(int $idSublinea)
    {
        $sql = "SELECT
                    s.idsublineaproducto,
                    s.descripcion AS modelo,
                    l.descripcion AS segmento,
                    d.marca,
                    d.tipo_carroceria,
                    d.estado,
                    d.version,
                    d.fecha_inicio,
                    d.fecha_fin
                FROM wms_sublinea_producto s
                INNER JOIN wms_linea_producto l ON s.lineaproductoid = l.idlineaproducto
                LEFT JOIN ing_modelo_detalle d ON d.id_sublineaproducto = s.idsublineaproducto AND d.deleted_at IS NULL
                WHERE s.idsublineaproducto = ?";

        return $this->select($sql, [$idSublinea]);
    }

    public function upsertDetalle(int $idSublinea, array $data)
    {
        $sql = "SELECT id_modelo_detalle FROM ing_modelo_detalle WHERE id_sublineaproducto = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$idSublinea]);
        $idusuario = $_SESSION['userData']['idusuario'] ?? null;

        if (!empty($exist)) {
            $sql = "UPDATE ing_modelo_detalle SET
                marca = ?, tipo_carroceria = ?, estado = ?, version = ?, fecha_inicio = ?, fecha_fin = ?, updated_by = ?
                WHERE id_sublineaproducto = ?";

            return $this->update($sql, [
                $data['marca'], $data['tipo_carroceria'], $data['estado'], $data['version'],
                $data['fecha_inicio'], $data['fecha_fin'], $idusuario, $idSublinea,
            ]);
        }

        $sql = "INSERT INTO ing_modelo_detalle
            (id_sublineaproducto, marca, tipo_carroceria, estado, version, fecha_inicio, fecha_fin, created_by)
            VALUES (?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $idSublinea, $data['marca'], $data['tipo_carroceria'], $data['estado'], $data['version'],
            $data['fecha_inicio'], $data['fecha_fin'], $idusuario,
        ]);
    }
}
