<?php

/**
 * Catálogo de tipos de transmisión (TM, AMT, AT, ...).
 * Tabla: ing_cat_tipo_transmision  (16-oct-2026)
 * Se administra desde la pestaña TIPOS del módulo Ing_transmisiones.
 * La "clave" es la que entra al SKU en Ing_configuraciones.
 */
class Ing_tipo_transmisionModel extends Mysql
{
    use Auditable;

    protected string $table = 'ing_cat_tipo_transmision';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function selectTipos()
    {
        $sql = "SELECT tt.*,
                    (SELECT COUNT(*) FROM ing_cat_transmision t
                      WHERE t.id_tipo_transmision = tt.id_tipo_transmision AND t.deleted_at IS NULL) AS total_transmisiones
                FROM ing_cat_tipo_transmision tt
                WHERE tt.deleted_at IS NULL
                ORDER BY tt.clave";
        return $this->select_all($sql);
    }

    public function selectTipo(int $id)
    {
        $sql = "SELECT * FROM ing_cat_tipo_transmision WHERE id_tipo_transmision = ? AND deleted_at IS NULL";
        return $this->select($sql, [$id]);
    }

    public function selectOptionTipos()
    {
        $sql = "SELECT id_tipo_transmision, clave, descripcion FROM ing_cat_tipo_transmision
                WHERE activo = 1 AND deleted_at IS NULL ORDER BY clave";
        return $this->select_all($sql);
    }

    public function insertTipo(array $data)
    {
        // La clave es UNIQUE en BD (aunque el registro esté eliminado lógicamente).
        $exist = $this->select("SELECT id_tipo_transmision, deleted_at FROM ing_cat_tipo_transmision WHERE clave = ?", [$data['clave']]);
        if (!empty($exist)) {
            if (empty($exist['deleted_at'])) {
                return "exist";
            }
            // Si estaba eliminado, se reactiva en lugar de duplicar.
            $sql = "UPDATE ing_cat_tipo_transmision SET descripcion = ?, activo = ?, deleted_at = NULL, updated_by = ?
                    WHERE id_tipo_transmision = ?";
            $this->update($sql, [$data['descripcion'], $data['activo'], $_SESSION['userData']['idusuario'] ?? null, $exist['id_tipo_transmision']]);
            return $exist['id_tipo_transmision'];
        }

        $sql = "INSERT INTO ing_cat_tipo_transmision (clave, descripcion, activo, created_by) VALUES (?,?,?,?)";
        return $this->insert($sql, [
            $data['clave'], $data['descripcion'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null,
        ]);
    }

    public function updateTipo(int $id, array $data)
    {
        $exist = $this->select("SELECT id_tipo_transmision FROM ing_cat_tipo_transmision WHERE clave = ? AND id_tipo_transmision != ?", [$data['clave'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_cat_tipo_transmision SET clave = ?, descripcion = ?, activo = ?, updated_by = ?
                WHERE id_tipo_transmision = ?";
        return $this->update($sql, [
            $data['clave'], $data['descripcion'], $data['activo'],
            $_SESSION['userData']['idusuario'] ?? null, $id,
        ]);
    }

    public function countTransmisionesTipo(int $id): int
    {
        $row = $this->select("SELECT COUNT(*) AS total FROM ing_cat_transmision WHERE id_tipo_transmision = ? AND deleted_at IS NULL", [$id]);
        return intval($row['total'] ?? 0);
    }

    public function deleteTipo(int $id)
    {
        $sql = "UPDATE ing_cat_tipo_transmision SET deleted_at = NOW(), activo = 0 WHERE id_tipo_transmision = ?";
        return $this->update($sql, [$id]);
    }
}
