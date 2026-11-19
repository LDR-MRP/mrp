<?php

/* =====================================================
   CARGA MASIVA DE TRASLADOS DE UNIDADES (Excel)
   Colgada de Inv_traslados. El registro real del traslado
   lo hace Inv_trasladosModel::insertTraslado(); este modelo
   solo trae catálogos y precarga los datos de los VIN para
   validar el archivo completo con pocas consultas.
===================================================== */

class Inv_trascargamasivaModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Almacenes activos. Con $plantaid (usuario no administrador) solo
     * regresa los de su planta: mismo criterio que el almacén origen de
     * Nueva Solicitud en Inv_traslados.
     */
    public function selectAlmacenes($plantaid = null): array
    {
        $params = [];
        $sql = "SELECT idalmacen, descripcion
            FROM wms_almacenes
            WHERE estado = 2";

        if ($plantaid !== null) {
            $sql .= " AND plantaid = ?";
            $params[] = $plantaid;
        }

        $sql .= " ORDER BY descripcion";

        return $this->select_all($sql, $params);
    }

    /**
     * Unidades que hoy se pueden trasladar desde los almacenes indicados
     * (mismo criterio que Inv_trasladosModel::selectUnidadesPorAlmacen):
     * VIN tatuado, con asignación activa, tipo P y sin traslado activo.
     * Se usa solo como hoja de referencia dentro de la plantilla.
     */
    public function selectUnidadesDisponibles(array $almacenids): array
    {
        $almacenids = array_values(array_filter(array_map('intval', $almacenids)));
        if (empty($almacenids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($almacenids), '?'));

        $sql = "SELECT
                ns.numero_serie AS vin,
                i.descripcion AS unidad,
                a.descripcion AS almacen
            FROM mrp_vin_asignaciones va
            INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = va.numero_serie_id
            INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
            LEFT JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
            WHERE va.estado = 1
              AND ns.estado = '2'
              AND i.tipo_elemento = 'P'
              AND ns.almacenid IN ($placeholders)
              AND NOT EXISTS (
                  SELECT 1
                  FROM wms_traslados_unidades_detalle d
                  INNER JOIN wms_traslados_unidades t ON t.idtraslado = d.trasladoid
                  WHERE d.vinid = ns.id_numeros_serie
                    AND t.estado IN (1,2,3)
              )
            ORDER BY a.descripcion, ns.numero_serie";

        return $this->select_all($sql, $almacenids);
    }

    /**
     * Recibe los VIN del archivo y regresa un mapa
     * [VIN en mayúsculas => datos del VIN] con lo necesario para validar:
     * producto, almacén actual, si está tatuado y si ya está en un
     * traslado activo (folio).
     */
    public function mapVines(array $vines): array
    {
        $vines = array_values(array_unique(array_filter(array_map(function ($v) {
            return strtoupper(trim((string) $v));
        }, $vines))));

        if (empty($vines)) {
            return [];
        }

        $map = [];

        foreach (array_chunk($vines, 500) as $bloque) {
            $placeholders = implode(',', array_fill(0, count($bloque), '?'));

            $sql = "SELECT
                    ns.id_numeros_serie,
                    ns.numero_serie,
                    ns.inventarioid,
                    ns.almacenid,
                    ns.estado,
                    i.tipo_elemento,
                    i.descripcion AS unidad,
                    a.descripcion AS almacen,
                    (SELECT COUNT(*)
                       FROM mrp_vin_asignaciones va
                      WHERE va.numero_serie_id = ns.id_numeros_serie
                        AND va.estado = 1) AS asignaciones_activas,
                    (SELECT t.folio
                       FROM wms_traslados_unidades_detalle d
                       INNER JOIN wms_traslados_unidades t ON t.idtraslado = d.trasladoid
                      WHERE d.vinid = ns.id_numeros_serie
                        AND t.estado IN (1,2,3)
                      LIMIT 1) AS folio_traslado_activo
                FROM wms_numeros_series ns
                INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
                LEFT JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
                WHERE UPPER(ns.numero_serie) IN ($placeholders)";

            foreach ($this->select_all($sql, $bloque) as $row) {
                $clave = strtoupper(trim((string) $row['numero_serie']));
                if (!isset($map[$clave])) {
                    $map[$clave] = $row;
                }
            }
        }

        return $map;
    }

    /**
     * Llaves que hoy NO están disponibles (entregadas / en tránsito /
     * prestadas). Regresa un set ["vinid|tipo_llave" => true] para avisar
     * en el log antes de intentar registrar el traslado.
     */
    public function mapLlavesNoDisponibles(array $vinids): array
    {
        $vinids = array_values(array_unique(array_filter(array_map('intval', $vinids))));
        if (empty($vinids)) {
            return [];
        }

        $map = [];

        foreach (array_chunk($vinids, 500) as $bloque) {
            $placeholders = implode(',', array_fill(0, count($bloque), '?'));
            $sql = "SELECT vinid, tipo_llave
                FROM wms_llaves_inventario
                WHERE estado <> 1
                  AND vinid IN ($placeholders)";

            foreach ($this->select_all($sql, $bloque) as $row) {
                $map[(int) $row['vinid'] . '|' . $row['tipo_llave']] = true;
            }
        }

        return $map;
    }
}
