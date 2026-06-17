<?php

class pla_productostModel extends Mysql
{


    public function __construct()
    {
        parent::__construct();
    }


public function obtenerPlaneacion($num_orden)
{
    $num_orden = trim((string) $num_orden);

    if ($num_orden === '') {
        return [];
    }

    // ==============================
    // HEADER DE LA ORDEN
    // ==============================

$sqlHeader = "SELECT 
                pla.idplaneacion,
                pla.num_orden,
                pla.productoid,
                pla.num_pedido,
                pla.supervisorid,
                pla.prioridad,
                pla.cantidad,
                pla.fecha_requerida,
                pla.fecha_inicio,
                pla.fecha_fin,
                pla.fecha_inicio_real,
                pla.fecha_fin_real,
                pla.notas,
                pla.estado,
                pla.plantaid,
                pla.fase,

                pl.idplanta,
                pl.cve_planta,
                pl.nombre_planta,
                pl.direccion AS direccion_planta,

                COALESCE(
                    CONCAT(u.nombres, ' ', u.apellidos),
                    CONCAT('Supervisor ID: ', pla.supervisorid)
                ) AS supervisor_nombre

            FROM mrp_planeacion pla

            INNER JOIN mrp_planta pl
                ON pl.idplanta = pla.plantaid

            LEFT JOIN usuarios u 
                ON u.idusuario = pla.supervisorid

            WHERE pla.num_orden = ?
            LIMIT 1";

    $header = $this->select($sqlHeader, [$num_orden]);

    if (empty($header)) {
        return [];
    }

    // ==============================
    // UNIDADES FINALIZADAS
    // ==============================

    $sqlFinalizadas = "SELECT 
                        ot.num_sub_orden,

                        MIN(ot.fecha_inicio) AS fecha_inicio_produccion,
                        MAX(ot.fecha_fin) AS fecha_fin_produccion,

                        TIMESTAMPDIFF(
                            MINUTE,
                            MIN(ot.fecha_inicio),
                            MAX(ot.fecha_fin)
                        ) AS minutos_armado,

                        COUNT(ot.idorden) AS total_estaciones,
                        SUM(CASE WHEN ot.estatus = 3 THEN 1 ELSE 0 END) AS estaciones_finalizadas,
                        SUM(CASE WHEN ot.estatus <> 3 THEN 1 ELSE 0 END) AS estaciones_pendientes,

                        otvin.idorden AS idorden_estampado,

                        va.idasignacion,
                        va.orden_trabajo_id,
                        va.numero_serie_id,
                        va.numero_motor,
                        va.vin_origen,
                        va.numero_transmision,
                        va.usuario_id,
                        va.fecha_asignacion,
                        va.estado AS estado_vin,

                        ns.id_numeros_serie,
                        ns.inventarioid,
                        ns.almacenid,
                        ns.numero_serie,
                        ns.referencia,
                        ns.costo,
                        ns.fecha AS fecha_serie,
                        ns.estado AS estado_serie,
                        ns.tipo_generacion,

                        COALESCE(
                            CONCAT(uva.nombres, ' ', uva.apellidos),
                            CONCAT('Usuario ID: ', va.usuario_id)
                        ) AS usuario_vin

                    FROM mrp_ordenes_trabajo ot

                    INNER JOIN mrp_planeacion_estacion pe
                        ON pe.id_planeacion_estacion = ot.planeacion_estacionid

                    INNER JOIN mrp_planeacion pla
                        ON pla.idplaneacion = pe.planeacionid

                    LEFT JOIN mrp_ordenes_trabajo otvin
                        ON otvin.num_sub_orden = ot.num_sub_orden
                        AND otvin.estampado = 2

                    LEFT JOIN mrp_vin_asignaciones va
                        ON va.orden_trabajo_id = otvin.idorden

                    LEFT JOIN wms_numeros_series ns
                        ON ns.id_numeros_serie = va.numero_serie_id

                    LEFT JOIN usuarios uva
                        ON uva.idusuario = va.usuario_id

                    WHERE pla.num_orden = ?

                    GROUP BY ot.num_sub_orden

                    HAVING estaciones_pendientes = 0

                 
                    ORDER BY fecha_fin_produccion DESC";

    $unidadesFinalizadas = $this->select_all($sqlFinalizadas, [$num_orden]);

    // ==============================
    // UNIDADES PENDIENTES / EN PROCESO
    // ==============================

$sqlPendientes = "SELECT 
                    ot.num_sub_orden,

                    MIN(ot.fecha_inicio) AS fecha_inicio_produccion,
                    MAX(ot.fecha_fin) AS ultima_fecha_fin,

                    COUNT(ot.idorden) AS total_estaciones,
                    SUM(CASE WHEN ot.estatus = 3 THEN 1 ELSE 0 END) AS estaciones_finalizadas,
                    SUM(CASE WHEN ot.estatus = 2 THEN 1 ELSE 0 END) AS estaciones_en_proceso,
                    SUM(CASE WHEN ot.estatus = 1 THEN 1 ELSE 0 END) AS estaciones_pendientes,

                    MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) AS accion_produccion_activa,
                    MAX(ot.accion_activa) AS accion_activa,

                    CASE 
                        WHEN MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) = 1 
                            THEN 'Paro momentáneo'
                        WHEN MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) = 2 
                            THEN 'Retiro AGV'
                        WHEN MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) = 3 
                            THEN 'Unidad alarmada'
                        WHEN MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) = 4 
                            THEN 'Solicitud asistencia'
                        WHEN MAX(CASE WHEN ot.accion_activa = 2 THEN ot.accion_produccion ELSE 0 END) = 5 
                            THEN 'Falta materia'
                        ELSE 'Sin acción'
                    END AS accion_actual,

                    CASE 
                        WHEN SUM(CASE WHEN ot.estatus = 2 THEN 1 ELSE 0 END) > 0 
                            THEN 'EN PROCESO'
                        ELSE 'PENDIENTE'
                    END AS estado_unidad

                FROM mrp_ordenes_trabajo ot

                INNER JOIN mrp_planeacion_estacion pe
                    ON pe.id_planeacion_estacion = ot.planeacion_estacionid

                INNER JOIN mrp_planeacion pla
                    ON pla.idplaneacion = pe.planeacionid

                WHERE pla.num_orden = ?

                GROUP BY ot.num_sub_orden

                HAVING SUM(CASE WHEN ot.estatus <> 3 THEN 1 ELSE 0 END) > 0

                ORDER BY CAST(SUBSTRING_INDEX(ot.num_sub_orden, '-U', -1) AS UNSIGNED) ASC";

    $unidadesPendientes = $this->select_all($sqlPendientes, [$num_orden]);

    $header['total_finalizadas'] = count($unidadesFinalizadas);
    $header['total_pendientes'] = count($unidadesPendientes);
    $header['total_unidades_detectadas'] = count($unidadesFinalizadas) + count($unidadesPendientes);

    return [
        'header' => $header,
        'unidades_finalizadas' => $unidadesFinalizadas,
        'unidades_pendientes' => $unidadesPendientes
    ];
}

    public function selectPlanTodas()
    {
        $rolId = isset($_SESSION['rolid']) ? (int) $_SESSION['rolid'] : 0;
        $userIdSes = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

        $isAdmin = in_array($rolId, [1, 5]);

        if (!$isAdmin && $userIdSes <= 0) {
            return [];
        }

        $whereUser = "";

        if (!$isAdmin) {
            $whereUser = " AND (
                pla.supervisorid = {$userIdSes}
                OR pla.idplaneacion IN (
                    SELECT DISTINCT pe.planeacionid
                    FROM mrp_planeacion_estacion pe
                    INNER JOIN mrp_planeacion_estacion_operador o
                        ON o.planeacion_estacionid = pe.id_planeacion_estacion
                    WHERE pe.estado = 2
                    AND o.estado = 2
                    AND o.usuarioid = {$userIdSes}
                )
            )";
        }

        $sql = "SELECT
                pla.*,
                pla.estado AS estado_planeacion,
                pro.cve_producto,
                pro.descripcion AS descripcion_producto
            FROM mrp_planeacion pla
            INNER JOIN mrp_productos pro
                ON pro.idproducto = pla.productoid
            WHERE pla.estado != 0
            {$whereUser}
            ORDER BY pla.idplaneacion DESC";

        return $this->select_all($sql);
    }

    public function selectPlanPendientes()
    {
        $rolId = isset($_SESSION['rolid']) ? (int) $_SESSION['rolid'] : 0;
        $userIdSes = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

        // Admin y rol 5 ven todo
        $isAdmin = in_array($rolId, [1, 5]);

        if (!$isAdmin && $userIdSes <= 0) {
            return [];
        }

        $whereUser = "";
        if (!$isAdmin) {
            $whereUser = " AND (
            pla.supervisorid = {$userIdSes}
            OR pla.idplaneacion IN (
                SELECT DISTINCT pe.planeacionid
                FROM mrp_planeacion_estacion pe
                INNER JOIN mrp_planeacion_estacion_operador o
                  ON o.planeacion_estacionid = pe.id_planeacion_estacion
                WHERE pe.estado = 2
                  AND o.estado  = 2
                  AND o.usuarioid = {$userIdSes}
            )
        )";
        }

        $sql = "SELECT pla.*,
                   pla.estado AS estado_planeacion,
                   pro.cve_producto,
                   pro.descripcion AS descripcion_producto
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
              ON pla.productoid = pro.idproducto
            WHERE pla.fase = 2
              AND pla.estado != 0
              {$whereUser};";

        return $this->select_all($sql);
    }





    public function selectPlanFinalizadas()
    {
        $isAdmin = isset($_SESSION['rolid']) && (int) $_SESSION['rolid'] === 1;
        $userIdSes = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

        if (!$isAdmin && $userIdSes <= 0) {
            return [];
        }

        $whereUser = "";
        if (!$isAdmin) {
            $whereUser = " AND (
            pla.supervisorid = {$userIdSes}
            OR pla.idplaneacion IN (
                SELECT DISTINCT pe.planeacionid
                FROM mrp_planeacion_estacion pe
                INNER JOIN mrp_planeacion_estacion_operador o
                  ON o.planeacion_estacionid = pe.id_planeacion_estacion
                WHERE pe.estado = 2
                  AND o.estado  = 2
                  AND o.usuarioid = {$userIdSes}
            )
        )";
        }

        $sql = "SELECT pla.*,
                   pla.estado AS estado_planeacion,
                   pro.cve_producto,
                   pro.descripcion AS descripcion_producto
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
              ON pla.productoid = pro.idproducto
            WHERE pla.fase = 5
              AND pla.estado != 0
              {$whereUser};";

        return $this->select_all($sql);
    }


    public function selectPlanEnProceso()
    {
        $isAdmin = isset($_SESSION['rolid']) && (int) $_SESSION['rolid'] === 1;
        $userIdSes = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

        if (!$isAdmin && $userIdSes <= 0) {
            return [];
        }

        $whereUser = "";
        if (!$isAdmin) {
            $whereUser = " AND (
            pla.supervisorid = {$userIdSes}
            OR pla.idplaneacion IN (
                SELECT DISTINCT pe.planeacionid
                FROM mrp_planeacion_estacion pe
                INNER JOIN mrp_planeacion_estacion_operador o
                  ON o.planeacion_estacionid = pe.id_planeacion_estacion
                WHERE pe.estado = 2
                  AND o.estado  = 2
                  AND o.usuarioid = {$userIdSes}
            )
        )";
        }

        $sql = "SELECT pla.*,
                   pla.estado AS estado_planeacion,
                   pro.cve_producto,
                   pro.descripcion AS descripcion_producto
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
              ON pla.productoid = pro.idproducto
            WHERE pla.fase = 3
              AND pla.estado != 0
              {$whereUser};";

        return $this->select_all($sql);
    }






}
?>