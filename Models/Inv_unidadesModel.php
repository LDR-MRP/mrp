<?php
/**
 * Inventario de unidades (productos tipo P): existencia por almacen, sublinea
 * (segmento), modelo, anio modelo (posicion 10 del VIN -> cat_anio_vin) y color.
 *
 * Unidad = VIN de un producto activo en estado 2 (Tatuado: unidad fisica), mas
 * los VIN en estado 1 (Disponible) del almacen de transito (ALM-TRANSITO) o
 * cuyo ultimo movimiento con VIN fue una entrada (devolucion de venta), que
 * tienen existencia y se pueden facturar aunque no esten fisicamente; se
 * agrupan aparte como planta 'TRANSITO' ("En transito"). Los demas VIN en
 * estado 1 son generados que aun no se usan.
 * Sublinea: la de Ingenieria (ing_modelo_configuracion); si el producto no
 * tiene configuracion, la de wms_inventario_linea.
 * Planta: la del almacen (wms_almacenes.plantaid -> mrp_planta). Mientras un
 * almacen no tenga planta capturada se usa el prefijo de su clave (PL01-...)
 * como respaldo; si tampoco lo tiene queda como 'SIN' (Sin planta asignada).
 */
class Inv_unidadesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /** Clave de planta: FK primero, prefijo de la clave como respaldo, 'SIN' si no hay ninguno. */
    private const PLANTA_SQL = "COALESCE(pl.cve_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN LEFT(a.cve_almacen, 4) ELSE 'SIN' END)";
    /** Almacén de tránsito (clave ALM-TRANSITO): sus VINs se muestran aunque no estén tatuados. */
    private const TRANSITO_SQL = "(UPPER(TRIM(a.cve_almacen)) = 'ALM-TRANSITO' OR UPPER(TRIM(a.descripcion)) IN ('ALMACEN DE TRANSITO', 'ALMACÉN DE TRANSITO', 'ALMACÉN DE TRÁNSITO'))";
    private const PLANTA_NOMBRE_SQL = "COALESCE(pl.nombre_planta, CASE WHEN a.cve_almacen REGEXP '^PL[0-9]{2}-' THEN CONCAT('Planta ', CAST(SUBSTRING(a.cve_almacen, 3, 2) AS UNSIGNED)) ELSE 'Sin planta asignada' END)";

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
                       CASE WHEN " . self::TRANSITO_SQL . " THEN 'TRANSITO' ELSE " . self::PLANTA_SQL . " END AS planta,
                       CASE WHEN " . self::TRANSITO_SQL . " THEN 'En tránsito' ELSE " . self::PLANTA_NOMBRE_SQL . " END AS planta_nombre,
                       CASE WHEN " . self::TRANSITO_SQL . " THEN 1 ELSE 0 END AS en_transito,
                       ns.estado,
                       an.anio,
                       ns.referencia,
                       va.numero_motor,
                       va.vin_origen,
                       DATE_FORMAT(ns.fecha, '%Y-%m-%d') AS fecha
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a  ON a.idalmacen = ns.almacenid
                LEFT JOIN mrp_planta pl    ON pl.idplanta = a.plantaid
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
                  -- Tatuados (2) en cualquier almacén + Disponibles (1) del almacén de tránsito
                  -- o cuyo último movimiento con VIN fue una entrada (p. ej. una devolución)
                  AND (ns.estado = '2'
                       OR (ns.estado = '1' AND (" . self::TRANSITO_SQL . "
                           OR EXISTS (SELECT 1 FROM wms_movimientos_inventario_series ms
                                      WHERE ms.id_numeros_serie = ns.id_numeros_serie
                                        AND ms.signo > 0
                                        AND ms.idmovserie = (SELECT MAX(ms2.idmovserie)
                                                             FROM wms_movimientos_inventario_series ms2
                                                             WHERE ms2.id_numeros_serie = ns.id_numeros_serie)))))
                ORDER BY i.descripcion, a.cve_almacen, ns.numero_serie";
        return $this->select_all($sql);
    }

    /** JOIN de sublínea (Ingeniería primero, luego wms_inventario_linea) para el alias i = wms_inventario. */
    private const SUBLINEA_JOIN = "LEFT JOIN (SELECT id_inventario, MIN(id_sublineaproducto) AS idsub
                           FROM ing_modelo_configuracion
                           WHERE deleted_at IS NULL AND id_inventario IS NOT NULL
                           GROUP BY id_inventario) cfg ON cfg.id_inventario = i.idinventario
                LEFT JOIN (SELECT inventarioid, MIN(sublineaproductoid) AS idsub
                           FROM wms_inventario_linea WHERE estado = 2
                           GROUP BY inventarioid) il ON il.inventarioid = i.idinventario
                LEFT JOIN wms_sublinea_producto sl ON sl.idsublineaproducto = COALESCE(cfg.idsub, il.idsub)";

    /**
     * Unidades en tránsito del Order delivery plan (wms_odp) por producto y mes de carga.
     * No tienen entrada al inventario; regresa [] si el módulo aún no tiene tablas.
     */
    public function selectTransitoODP(): array
    {
        $t = $this->select("SELECT COUNT(*) AS n FROM information_schema.tables
                            WHERE table_schema = DATABASE() AND table_name = 'wms_odp'");
        if ((int) ($t['n'] ?? 0) === 0) {
            return [];
        }
        return $this->select_all(
            "SELECT o.inventarioid,
                    i.cve_articulo,
                    i.descripcion,
                    COALESCE(sl.cve_sublinea_producto, 'SIN SUBLINEA') AS sublinea,
                    COALESCE(sl.descripcion, 'Sin sublínea') AS sublinea_desc,
                    DATE_FORMAT(o.mes_carga, '%Y-%m') AS mes,
                    SUM(o.qty) AS qty
             FROM wms_odp o
             INNER JOIN wms_inventario i ON i.idinventario = o.inventarioid
             " . self::SUBLINEA_JOIN . "
             GROUP BY o.inventarioid, i.cve_articulo, i.descripcion, sublinea, sublinea_desc, mes"
        );
    }

    /** Renglones del Order delivery plan agrupados por contrato (detalle de la vista En tránsito). */
    public function selectTransitoContratos(): array
    {
        $t = $this->select("SELECT COUNT(*) AS n FROM information_schema.tables
                            WHERE table_schema = DATABASE() AND table_name = 'wms_odp'");
        if ((int) ($t['n'] ?? 0) === 0) {
            return [];
        }
        return $this->select_all(
            "SELECT o.inventarioid,
                    COALESCE(o.contrato, '') AS contrato,
                    COALESCE(o.modelo, '') AS modelo,
                    COALESCE(DATE_FORMAT(o.eta_pod, '%Y-%m-%d'), o.eta_pod_txt, '') AS eta,
                    COALESCE(o.status, '') AS status,
                    DATE_FORMAT(o.mes_carga, '%Y-%m') AS mes,
                    SUM(o.qty) AS qty
             FROM wms_odp o
             GROUP BY o.inventarioid, contrato, modelo, eta, status, mes
             ORDER BY mes, contrato"
        );
    }

    /** [inventarioid => nombre comercial de Ingeniería] de los productos tipo P. */
    public function selectNombresComerciales(): array
    {
        $out = [];
        foreach ($this->select_all(
            "SELECT id_inventario, MIN(nombre_comercial) AS nombre
             FROM ing_modelo_configuracion
             WHERE deleted_at IS NULL AND id_inventario IS NOT NULL
               AND nombre_comercial IS NOT NULL AND TRIM(nombre_comercial) <> ''
             GROUP BY id_inventario"
        ) as $r) {
            $out[(int) $r['id_inventario']] = trim($r['nombre']);
        }
        return $out;
    }
}
