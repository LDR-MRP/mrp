<?php

class Inv_productossustitutosModel extends Mysql
{
	public function __construct()
	{
		parent::__construct();
	}
	public function getListas()
	{
		return $this->select_all("SELECT * FROM wms_claves_productos_sustitutos ORDER BY id_clave_lista DESC");
	}

	public function insertLista($nombre)
	{
		return parent::insert(
			"INSERT INTO wms_claves_productos_sustitutos(nombre_lista,activo) VALUES(?,1)",
			[$nombre]
		);
	}

	public function getProductosLista($idLista)
	{
		$sql = "SELECT 
                d.id_detalle,
                d.idinventario,
                i.cve_articulo,
                i.descripcion,
                i.tipo_elemento,
                CASE 
                    WHEN i.tipo_elemento = 'C' THEN 'Componente'
                    WHEN i.tipo_elemento = 'H' THEN 'Herramienta'
                    WHEN i.tipo_elemento = 'R' THEN 'Refacción'
                    ELSE 'Sin definir'
                END AS tipo_text,
                d.fecha_creacion
            FROM wms_claves_productos_sustitutos_det d
            INNER JOIN wms_inventario i ON i.idinventario = d.idinventario
            WHERE d.id_clave_lista = {$idLista}
              AND d.activo = 1";

		return $this->select_all($sql);
	}

	public function insertProductoLista($idLista, $idInventario)
	{
		return parent::insert(
			"INSERT INTO wms_claves_productos_sustitutos_det(id_clave_lista,idinventario,activo) VALUES(?,?,1)",
			[$idLista, $idInventario]
		);
	}

	public function getInventario($search, $tipo = '')
	{
		$whereTipo = "";

		if (!empty($tipo)) {
			$whereTipo = " AND tipo_elemento = '{$tipo}' ";
		}

		return $this->select_all("
        SELECT 
            idinventario AS id,
            cve_articulo,
            descripcion,
            tipo_elemento,
            CONCAT(cve_articulo, ' - ', descripcion) AS text,
            CASE 
                WHEN tipo_elemento = 'C' THEN 'Componente'
                WHEN tipo_elemento = 'H' THEN 'Herramienta'
                WHEN tipo_elemento = 'R' THEN 'Refacción'
                WHEN tipo_elemento = 'P' THEN 'Producto'
                ELSE 'Sin definir'
            END AS tipo_text
        FROM wms_inventario
        WHERE estado = '2'
          {$whereTipo}
          AND (
                cve_articulo LIKE '%{$search}%'
                OR descripcion LIKE '%{$search}%'
          )
        ORDER BY cve_articulo ASC
        LIMIT 20
    ");
	}

	public function existsLista($nombre)
	{
		$sql = "SELECT id_clave_lista 
            FROM wms_claves_productos_sustitutos
            WHERE UPPER(TRIM(nombre_lista)) = UPPER(TRIM('{$nombre}'))
            LIMIT 1";
		return $this->select($sql);
	}

	public function existsProductoLista($idLista, $idInventario)
	{
		$sql = "SELECT id_detalle
            FROM wms_claves_productos_sustitutos_det
            WHERE id_clave_lista = {$idLista}
              AND idinventario = {$idInventario}
              AND activo = 1
            LIMIT 1";
		return $this->select($sql);
	}

	public function getListaById(int $id)
	{
		$sql = "SELECT * 
            FROM wms_claves_productos_sustitutos
            WHERE id_clave_lista = {$id}
            LIMIT 1";
		return $this->select($sql);
	}

	public function existsListaUpdate($nombre, $id)
	{
		$sql = "SELECT id_clave_lista
            FROM wms_claves_productos_sustitutos
            WHERE UPPER(TRIM(nombre_lista)) = UPPER(TRIM('{$nombre}'))
              AND id_clave_lista != {$id}
            LIMIT 1";
		return $this->select($sql);
	}

	public function updateLista($id, $nombre)
	{
		return parent::update(
			"UPDATE wms_claves_productos_sustitutos
         SET nombre_lista = ?
         WHERE id_clave_lista = ?",
			[$nombre, $id]
		);
	}

	public function getTiposProductos(array $ids)
	{
		$ids = implode(',', array_map('intval', $ids));

		return $this->select_all("
        SELECT idinventario, tipo_elemento
        FROM wms_inventario
        WHERE idinventario IN ({$ids})
    ");
	}

	public function getTipoLista($idLista)
	{
		$sql = "SELECT i.tipo_elemento
            FROM wms_claves_productos_sustitutos_det d
            INNER JOIN wms_inventario i ON i.idinventario = d.idinventario
            WHERE d.id_clave_lista = {$idLista}
              AND d.activo = 1
            LIMIT 1";

		$row = $this->select($sql);

		return $row['tipo_elemento'] ?? null;
	}
	public function existsProductoOtraLista($idLista, $idInventario)
	{
		$sql = "SELECT id_detalle
            FROM wms_claves_productos_sustitutos_det
            WHERE idinventario = {$idInventario}
              AND id_clave_lista != {$idLista}
              AND activo = 1
            LIMIT 1";

		return $this->select($sql);
	}

	public function deleteProductoLista($idDetalle)
	{
		$sql = "DELETE FROM wms_claves_productos_sustitutos_det WHERE id_detalle = ?";
		return $this->update($sql, [$idDetalle]);
	}

	//movimientos entre listas pestaña 3 
	public function moverProductosLista($origen, $destino, array $productos)
	{
		$ids = implode(',', array_map('intval', $productos));

		$sql = "UPDATE wms_claves_productos_sustitutos_det
            SET id_clave_lista = {$destino}
            WHERE id_clave_lista = {$origen}
              AND idinventario IN ({$ids})
              AND activo = 1";

		return $this->update($sql, []);
	}

	public function getTipoListaById($idLista)
	{
		$sql = "SELECT i.tipo_elemento
            FROM wms_claves_productos_sustitutos_det d
            INNER JOIN wms_inventario i ON i.idinventario = d.idinventario
            WHERE d.id_clave_lista = {$idLista}
              AND d.activo = 1
            LIMIT 1";

		$row = $this->select($sql);

		return $row['tipo_elemento'] ?? null;
	}
}
