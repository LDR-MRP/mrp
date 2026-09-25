<?php

class Ing_especificacionesModel extends Mysql
{
    use Auditable;

    protected string $table = 'ing_cat_especificacion';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function insertEspecificacion(array $data)
    {
        $sql = "SELECT id_especificacion FROM ing_cat_especificacion WHERE categoria = ? AND clave = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['categoria'], $data['clave']]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_cat_especificacion (categoria, clave, unidad, desglose_facturacion, orden, activo) VALUES (?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['categoria'], $data['clave'], $data['unidad'], $data['desglose_facturacion'], $data['orden'], $data['activo'],
        ]);
    }

    public function updateEspecificacion(int $id, array $data)
    {
        $sql = "SELECT id_especificacion FROM ing_cat_especificacion WHERE categoria = ? AND clave = ? AND id_especificacion != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['categoria'], $data['clave'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_cat_especificacion SET categoria = ?, clave = ?, unidad = ?, desglose_facturacion = ?, orden = ?, activo = ? WHERE id_especificacion = ?";

        return $this->update($sql, [
            $data['categoria'], $data['clave'], $data['unidad'], $data['desglose_facturacion'], $data['orden'], $data['activo'], $id,
        ]);
    }

    public function selectEspecificaciones()
    {
        $sql = "SELECT * FROM ing_cat_especificacion WHERE deleted_at IS NULL ORDER BY categoria, orden, clave";
        return $this->select_all($sql);
    }

    public function selectEspecificacion(int $id)
    {
        $sql = "SELECT * FROM ing_cat_especificacion WHERE id_especificacion = ? AND deleted_at IS NULL";
        return $this->select($sql, [$id]);
    }

    public function selectPorCategoria(string $categoria)
    {
        $sql = "SELECT * FROM ing_cat_especificacion WHERE categoria = ? AND activo = 1 AND deleted_at IS NULL ORDER BY orden, clave";
        return $this->select_all($sql, [$categoria]);
    }

    public function deleteEspecificacion(int $id)
    {
        $sql = "UPDATE ing_cat_especificacion SET deleted_at = NOW(), activo = 0 WHERE id_especificacion = ?";
        return $this->update($sql, [$id]);
    }

    // ------------------------------------------------------------------
    // CATEGORÍAS (ing_cat_categoria_especificacion)
    // ------------------------------------------------------------------
    public function selectOptionCategorias()
    {
        $sql = "SELECT id_categoria, nombre FROM ing_cat_categoria_especificacion
                WHERE activo = 1 AND deleted_at IS NULL ORDER BY orden, nombre";
        return $this->select_all($sql);
    }

    public function insertCategoria(string $nombre)
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return false;
        }

        $sql = "SELECT id_categoria FROM ing_cat_categoria_especificacion WHERE nombre = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$nombre]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_cat_categoria_especificacion (nombre) VALUES (?)";
        return $this->insert($sql, [$nombre]);
    }

    // Listado completo (activas e inactivas) para el modal "Gestionar
    // categorías" de la pantalla Especificaciones — incluye cuántas
    // especificaciones ya usan cada categoría, para avisar antes de
    // desactivar una que está en uso.
    public function selectCategorias()
    {
        $sql = "SELECT c.id_categoria, c.nombre, c.orden, c.activo,
                       (SELECT COUNT(*) FROM ing_cat_especificacion e
                        WHERE e.categoria = c.nombre AND e.deleted_at IS NULL) AS total_especificaciones
                FROM ing_cat_categoria_especificacion c
                WHERE c.deleted_at IS NULL
                ORDER BY c.orden, c.nombre";
        return $this->select_all($sql);
    }

    // Actualiza nombre/orden/activo de una categoría. Como
    // ing_cat_especificacion.categoria guarda el nombre como texto libre
    // (sin FK a esta tabla), si el nombre cambia se propaga el nuevo nombre
    // a todas las especificaciones que ya usaban el nombre anterior, para
    // que no queden "huérfanas" agrupando bajo un nombre que ya no existe.
    public function updateCategoria(int $id, array $data)
    {
        $nombre = trim($data['nombre']);
        if ($nombre === '') {
            return false;
        }

        $sql = "SELECT id_categoria FROM ing_cat_categoria_especificacion WHERE nombre = ? AND id_categoria != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$nombre, $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "SELECT nombre FROM ing_cat_categoria_especificacion WHERE id_categoria = ? AND deleted_at IS NULL";
        $actual = $this->select($sql, [$id]);
        if (empty($actual)) {
            return false;
        }
        $nombreAnterior = $actual['nombre'];

        $pdo = $this->getConexion();
        $pdo->beginTransaction();
        try {
            $sql = "UPDATE ing_cat_categoria_especificacion SET nombre = ?, orden = ?, activo = ? WHERE id_categoria = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $data['orden'], $data['activo'], $id]);

            if ($nombreAnterior !== $nombre) {
                $sqlCascade = "UPDATE ing_cat_especificacion SET categoria = ? WHERE categoria = ? AND deleted_at IS NULL";
                $stmtCascade = $pdo->prepare($sqlCascade);
                $stmtCascade->execute([$nombre, $nombreAnterior]);
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
