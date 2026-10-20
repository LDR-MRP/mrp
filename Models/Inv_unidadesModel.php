<?php
/**
 * Inventario de unidades (productos tipo P): existencia por almacen, sublinea
 * (segmento), modelo, anio modelo (posicion 10 del VIN -> cat_anio_vin) y color.
 *
 * Unidad = VIN de un producto activo en estado 2 (no disponible: ya es una
 * unidad fisica). Los VIN en estado 1 son generados que aun no se usan.
 * Sublinea: la de Ingenieria (ing_modelo_configuracion); si el producto no
 * tiene configuracion, la de wms_inventario_linea.
 */
class Inv_unidadesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    private const PLANTA_SQL = "CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN LEFT(a.cve_almacen, 4) ELSE 'OTROS' END";

    /** Un renglon por VIN (unidad fisica). */
    public function selectUnidades(): array
    {
        $sql = "SELECT ns.id_numeros_serie,
                       ns.numero_serie,
                       NULLIF(TRIM(va.color), '') AS color,
                       ns.inventarioid,
                       i.cve_articulo,
                       i.descripcion,
                       COALESCE(sl.cve_sublinea_producto, 'SIN SUBLINEA') AS sublinea,
                       COALESCE(sl.descripcion, 'Sin sublínea') AS sublinea_desc,
                       ns.almacenid,
                       COALESCE(a.cve_almacen, 'SIN ALMACEN') AS cve_almacen,
                       COALESCE(a.descripcion, 'Sin almacén asignado') AS almacen,
                       " . self::PLANTA_SQL . " AS planta,
                       an.anio,
                       ns.referencia,
                       va.numero_motor,
                       va.vin_origen,
                       DATE_FORMAT(ns.fecha, '%Y-%m-%d') AS fecha
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                LEFT JOIN cat_anio_vin an  ON an.codigo = SUBSTRING(ns.numero_serie, 10, 1) COLLATE utf8mb4_general_ci
                LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
                LEFT JOIN (SELECT id_inventario, MIN(id_sublineaproducto) AS idsub
                           FROM ing_modelo_configuracion
                           WHERE deleted_at IS NULL AND id_inventario IS NOT NULL
                           GROUP BY id_inventario) cfg ON cfg.id_inventario = i.idinventario
                LEFT JOIN (SELECT inventarioid, MIN(sublineaproductoid) AS idsub
                           FROM wms_inventario_linea WHERE estado = 2
                           GROUP BY inventarioid) il ON il.inventarioid = i.idinventario
                LEFT JOIN wms_sublinea_producto sl ON sl.idsublineaproducto = COALESCE(cfg.idsub, il.idsub)
                WHERE i.tipo_elemento = 'P'
                  AND i.estado = '2'
                  AND ns.estado = '2'
                ORDER BY i.descripcion, a.cve_almacen, ns.numero_serie";
        return $this->select_all($sql);
    }
}
