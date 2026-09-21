<?php

class Inv_movcargamasivaModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Conceptos de movimiento tipo Proveedor (cpn = 'P'), activos.
     * Esta carga masiva solo admite conceptos de este tipo.
     */
    public function selectConceptosProveedor(): array
    {
        $sql = "SELECT idconcepmov, descripcion
            FROM wms_conceptos_mov
            WHERE cpn = 'P' AND estado = 2
            ORDER BY descripcion";
        return $this->select_all($sql);
    }

    public function selectAlmacenesActivos(): array
    {
        $sql = "SELECT idalmacen, descripcion
            FROM wms_almacenes
            WHERE estado = 2
            ORDER BY descripcion";
        return $this->select_all($sql);
    }

    public function selectProveedoresActivos(): array
    {
        $sql = "SELECT id_proveedor, COALESCE(NULLIF(nombre_comercial, ''), razon_social) AS nombre
            FROM prv_cat_proveedores
            WHERE estatus_operativo = 1
              AND deleted_at IS NULL
            ORDER BY nombre";
        return $this->select_all($sql);
    }

    /**
     * Recibe un arreglo de claves (cve_articulo) y regresa un mapa
     * [cve_articulo => idinventario] solo con productos ACTIVOS.
     */
    public function mapInventarioPorClaves(array $claves): array
    {
        if (empty($claves)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($claves), '?'));
        $sql = "SELECT idinventario, cve_articulo
            FROM wms_inventario
            WHERE estado = 2
              AND cve_articulo IN ($placeholders)";
        $rows = $this->select_all($sql, $claves);

        $map = [];
        foreach ($rows as $row) {
            $map[$row['cve_articulo']] = (int) $row['idinventario'];
        }
        return $map;
    }
}
