<?php

class Pla_productostModel extends Mysql
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
    $rolId = 0;
    $userIdSes = 0;

    if (isset($_SESSION['rolid'])) {
      $rolId = (int) $_SESSION['rolid'];
    } elseif (isset($_SESSION['userData']['rolid'])) {
      $rolId = (int) $_SESSION['userData']['rolid'];
    }

    if (isset($_SESSION['idUser'])) {
      $userIdSes = (int) $_SESSION['idUser'];
    } elseif (isset($_SESSION['userData']['idusuario'])) {
      $userIdSes = (int) $_SESSION['userData']['idusuario'];
    }

    $isAdmin = in_array($rolId, [1, 5], true);

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
    $rolId = 0;
    $userIdSes = 0;

    if (isset($_SESSION['rolid'])) {
      $rolId = (int) $_SESSION['rolid'];
    } elseif (isset($_SESSION['userData']['rolid'])) {
      $rolId = (int) $_SESSION['userData']['rolid'];
    }

    if (isset($_SESSION['idUser'])) {
      $userIdSes = (int) $_SESSION['idUser'];
    } elseif (isset($_SESSION['userData']['idusuario'])) {
      $userIdSes = (int) $_SESSION['userData']['idusuario'];
    }

    // Admin y rol 5 ven todo
    $isAdmin = in_array($rolId, [1, 5], true);

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
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
                ON pla.productoid = pro.idproducto
            WHERE pla.fase = 2
            AND pla.estado != 0
            {$whereUser}
            ORDER BY pla.idplaneacion DESC";

    return $this->select_all($sql);
  }




  public function selectPlanFinalizadas()
  {
    $rolId = 0;
    $userIdSes = 0;

    if (isset($_SESSION['rolid'])) {
      $rolId = (int) $_SESSION['rolid'];
    } elseif (isset($_SESSION['userData']['rolid'])) {
      $rolId = (int) $_SESSION['userData']['rolid'];
    }

    if (isset($_SESSION['idUser'])) {
      $userIdSes = (int) $_SESSION['idUser'];
    } elseif (isset($_SESSION['userData']['idusuario'])) {
      $userIdSes = (int) $_SESSION['userData']['idusuario'];
    }

    // Administrador y rol 5 ven todo
    $isAdmin = in_array($rolId, [1, 5], true);

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
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
                ON pla.productoid = pro.idproducto
            WHERE pla.fase = 5
            AND pla.estado != 0
            {$whereUser}
            ORDER BY pla.idplaneacion DESC";

    return $this->select_all($sql);
  }


  public function selectPlanEnProceso()
  {
    $rolId = 0;
    $userIdSes = 0;

    if (isset($_SESSION['rolid'])) {
      $rolId = (int) $_SESSION['rolid'];
    } elseif (isset($_SESSION['userData']['rolid'])) {
      $rolId = (int) $_SESSION['userData']['rolid'];
    }

    if (isset($_SESSION['idUser'])) {
      $userIdSes = (int) $_SESSION['idUser'];
    } elseif (isset($_SESSION['userData']['idusuario'])) {
      $userIdSes = (int) $_SESSION['userData']['idusuario'];
    }

    // Administrador y rol 5 ven todo
    $isAdmin = in_array($rolId, [1, 5], true);

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
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
                ON pla.productoid = pro.idproducto
            WHERE pla.fase = 3
            AND pla.estado != 0
            {$whereUser}
            ORDER BY pla.idplaneacion DESC";

    return $this->select_all($sql);
  }


public function selectUnidadTerminadaPdf(string $num_unidad)
{
    $num_unidad = trim($num_unidad);

    $sql = "SELECT
                ut.idunidad,
                ut.clave,
                ut.num_unidad,
                ut.planeacionid,
                ut.plantaid,
                ut.fecha_creacion AS fecha_unidad_terminada,
                ut.estado AS estado_unidad,

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
                pla.fase,
                pla.estado AS estado_planeacion,

                pro.idproducto,
                pro.cve_producto,
                pro.descripcion AS producto,
                pro.lineaproductoid,

                pl.idplanta,
                pl.cve_planta,
                pl.nombre_planta,
                pl.direccion AS direccion_planta,

                vin.idasignacion,
                vin.orden_trabajo_id,
                vin.numero_serie_id,
                vin.numero_motor,
                vin.vin_origen,
                vin.numero_transmision,
                vin.usuario_id,
                vin.fecha_asignacion,
                vin.estado AS estado_vin,

                ns.id_numeros_serie,
                ns.numero_serie AS vin_asignado,
                ns.referencia AS referencia_vin,
                ns.costo AS costo_vin,
                ns.fecha AS fecha_vin,
                ns.estado AS estado_numero_serie

            FROM mrp_unidades_terminadas ut

            LEFT JOIN mrp_planeacion pla
                ON pla.idplaneacion = ut.planeacionid

            LEFT JOIN mrp_productos pro
                ON pro.idproducto = pla.productoid

            LEFT JOIN mrp_planta pl
                ON pl.idplanta = ut.plantaid

            LEFT JOIN mrp_vin_asignaciones vin
                ON vin.num_unidad COLLATE utf8mb4_unicode_ci = ut.num_unidad COLLATE utf8mb4_unicode_ci
                AND vin.estado != 0

            LEFT JOIN wms_numeros_series ns
                ON ns.id_numeros_serie = vin.numero_serie_id

            WHERE ut.num_unidad = '{$num_unidad}'

            LIMIT 1";

    return $this->select($sql);
}


public function selectUnidadTerminada(string $num_clave)
{
    $num_clave = trim($num_clave);

    // ==============================
    // DATOS GENERALES DE LA UNIDAD
    // ==============================
    $sqlUnidad = "SELECT
                    ut.idunidad,
                    ut.clave,
                    ut.num_unidad,
                    ut.planeacionid,
                    ut.plantaid,
                    ut.fecha_creacion AS fecha_unidad_terminada,
                    ut.estado AS estado_unidad,

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
                    pla.usuario_inicio,
                    pla.usuario_fin,
                    pla.fase,
                    pla.estado AS estado_planeacion,

                    CONCAT(sup.nombres, ' ', sup.apellidos) AS supervisor_nombre,
                    sup.email_usER AS supervisor_email,

                    CONCAT(uvin.nombres, ' ', uvin.apellidos) AS asignacion_vin_usuario,

                    pro.idproducto,
                    pro.cve_producto,
                    pro.descripcion AS producto,
                    pro.lineaproductoid,

                    inv.idinventario,
                    inv.cve_articulo,
                    inv.descripcion AS descripcion_inventario,
                    inv.serie,
                    inv.unidad_salida,
                    inv.tipo_elemento,

                    pl.idplanta,
                    pl.cve_planta,
                    pl.nombre_planta,
                    pl.direccion AS direccion_planta,

                    vin.idasignacion,
                    vin.orden_trabajo_id,
                    vin.numero_serie_id,
                    vin.numero_motor,
                    vin.numero_transmision,
                    vin.vin_origen,
                    vin.usuario_id AS usuario_asigno_vin,
                    vin.fecha_asignacion,
                    vin.estado AS estado_vin,

                    ns.id_numeros_serie,
                    ns.numero_serie AS vin_asignado,
                    ns.referencia AS referencia_vin,

                    otvin.idorden AS idorden_estampado_vin,
                    estvin.cve_estacion AS cve_estacion_vin,
                    estvin.nombre_estacion AS estacion_estampado_vin,
                    estvin.proceso AS proceso_estampado_vin


                    

                FROM mrp_unidades_terminadas ut

                LEFT JOIN mrp_planeacion pla
                    ON pla.idplaneacion = ut.planeacionid

                    LEFT JOIN usuarios sup
                      ON sup.idusuario = pla.supervisorid

                LEFT JOIN mrp_productos pro
                    ON pro.idproducto = pla.productoid

                LEFT JOIN wms_inventario inv
                    ON inv.idinventario = pro.inventarioid

                LEFT JOIN mrp_planta pl
                    ON pl.idplanta = ut.plantaid

                LEFT JOIN mrp_ordenes_trabajo otvin
                    ON otvin.num_sub_orden  = ut.num_unidad 
                    AND otvin.estampado = 2

                LEFT JOIN mrp_planeacion_estacion pevin
                    ON pevin.id_planeacion_estacion = otvin.planeacion_estacionid

                LEFT JOIN mrp_estacion estvin
                    ON estvin.idestacion = pevin.estacionid

                LEFT JOIN mrp_vin_asignaciones vin
                    ON vin.orden_trabajo_id = otvin.idorden
                    AND vin.estado != 0

                LEFT JOIN usuarios uvin
                    ON uvin.idusuario = vin.usuario_id

                LEFT JOIN wms_numeros_series ns
                    ON ns.id_numeros_serie = vin.numero_serie_id

                WHERE ut.clave = '{$num_clave}'
                LIMIT 1";

    $unidad = $this->select($sqlUnidad);

    if (empty($unidad)) {
        return [];
    }

    $numUnidad = $unidad['num_unidad'];

    // ==============================
    // RECORRIDO POR ESTACIONES
    // ==============================
    $sqlEstaciones = "SELECT
                        ot.idorden,
                        ot.planeacion_estacionid,
                        ot.num_sub_orden,
                        ot.fecha_inicio,
                        ot.fecha_fin,
                        ot.comentarios,
                        ot.estatus,
                        ot.calidad,
                        ot.estampado,
                        ot.operaciones,
                        ot.especificaciones_criticas,
                        ot.accion_produccion,
                        ot.accion_activa,

                        pe.id_planeacion_estacion,
                        pe.planeacionid,
                        pe.estacionid,
                        pe.orden,
                        pe.estado AS estado_planeacion_estacion,
                        pe.estampado AS estacion_requiere_estampado,
                        pe.calidad AS estacion_requiere_calidad,
                        pe.operaciones AS estacion_requiere_operaciones,
                        pe.especificaciones AS estacion_requiere_especificaciones,

                        est.idestacion,
                        est.cve_estacion,
                        est.nombre_estacion,
                        est.proceso,
                        est.estandar,
                        est.unidad_medida,
                        est.tiempo_ajuste,
                        est.descripcion AS descripcion_estacion,
                        est.herramientas,
                        est.tiene_subensamble,

                        TIMESTAMPDIFF(MINUTE, ot.fecha_inicio, ot.fecha_fin) AS tiempo_real_minutos,

                        CASE 
                            WHEN ot.fecha_inicio IS NOT NULL 
                             AND ot.fecha_fin IS NOT NULL
                            THEN CONCAT(
                                FLOOR(TIMESTAMPDIFF(MINUTE, ot.fecha_inicio, ot.fecha_fin) / 60),
                                ' h ',
                                MOD(TIMESTAMPDIFF(MINUTE, ot.fecha_inicio, ot.fecha_fin), 60),
                                ' min'
                            )
                            ELSE 'Sin finalizar'
                        END AS tiempo_real_formato,

                        CASE 
                            WHEN est.tiempo_ajuste IS NOT NULL
                            THEN CONCAT(est.tiempo_ajuste, ' ', est.unidad_medida)
                            ELSE 'N/A'
                        END AS tiempo_estandar_formato,

                        CASE 
                            WHEN ot.calidad = 2 THEN 'Sí requiere / se realizó inspección'
                            ELSE 'No aplica'
                        END AS texto_calidad,

                        CASE 
                            WHEN ot.estampado = 2 THEN 'Aquí se realizó estampado VIN'
                            ELSE 'No aplica'
                        END AS texto_estampado,

                        CASE 
                            WHEN ot.especificaciones_criticas = 2 THEN 'Sí se aplicaron especificaciones críticas'
                            ELSE 'No aplica'
                        END AS texto_especificaciones_criticas,

                        vin.idasignacion,
                        vin.numero_motor,
                        vin.numero_transmision,
                        vin.vin_origen,
                        vin.usuario_id AS usuario_asigno_vin,
                        vin.fecha_asignacion,

                        ns.numero_serie AS vin_asignado,

                        GROUP_CONCAT(
                            DISTINCT CASE 
                                WHEN ope.rol = 1 THEN CONCAT(u.nombres, ' ', u.apellidos)
                                ELSE NULL
                            END
                            SEPARATOR ', '
                        ) AS encargado,

                        GROUP_CONCAT(
                            DISTINCT CASE 
                                WHEN ope.rol = 2 THEN CONCAT(u.nombres, ' ', u.apellidos)
                                ELSE NULL
                            END
                            SEPARATOR ', '
                        ) AS ayudantes,

                        GROUP_CONCAT(
                            DISTINCT CONCAT(u.nombres, ' ', u.apellidos)
                            SEPARATOR ', '
                        ) AS operadores,

                        CASE
                            WHEN ot.estatus = 3 THEN 'Finalizada'
                            WHEN ot.estatus = 2 THEN 'En proceso'
                            WHEN ot.estatus = 1 THEN 'Pendiente'
                            ELSE 'Sin estado'
                        END AS estado_texto

                    FROM mrp_ordenes_trabajo ot

                    INNER JOIN mrp_planeacion_estacion pe
                        ON pe.id_planeacion_estacion = ot.planeacion_estacionid

                    INNER JOIN mrp_estacion est
                        ON est.idestacion = pe.estacionid

                    LEFT JOIN mrp_vin_asignaciones vin
                        ON vin.orden_trabajo_id = ot.idorden
                        AND ot.estampado = 2
                        AND vin.estado != 0

                    LEFT JOIN wms_numeros_series ns
                        ON ns.id_numeros_serie = vin.numero_serie_id

                    LEFT JOIN mrp_planeacion_estacion_operador ope
                        ON ope.planeacion_estacionid = pe.id_planeacion_estacion
                        AND ope.estado = 2

                    LEFT JOIN usuarios u
                        ON u.idusuario = ope.usuarioid

                    WHERE ot.num_sub_orden  = '{$numUnidad}' 

                    GROUP BY 
                        ot.idorden,
                        ot.planeacion_estacionid,
                        ot.num_sub_orden,
                        ot.fecha_inicio,
                        ot.fecha_fin,
                        ot.comentarios,
                        ot.estatus,
                        ot.calidad,
                        ot.estampado,
                        ot.operaciones,
                        ot.especificaciones_criticas,
                        ot.accion_produccion,
                        ot.accion_activa,
                        pe.id_planeacion_estacion,
                        est.idestacion,
                        vin.idasignacion,
                        ns.numero_serie

                    ORDER BY pe.orden ASC";

    $estaciones = $this->select_all($sqlEstaciones);

    // ==============================
    // EVENTOS IMPORTANTES
    // ==============================
    $eventos = [];

    foreach ($estaciones as $estacion) {
        if (!empty($estacion['fecha_inicio'])) {
            $eventos[] = [
                'tipo' => 'inicio_estacion',
                'titulo' => 'Inicio de estación',
                'descripcion' => 'Se inició la estación ' . $estacion['cve_estacion'] . ' - ' . $estacion['nombre_estacion'],
                'usuario' => $estacion['encargado'] ?? 'N/A',
                'fecha' => $estacion['fecha_inicio']
            ];
        }

        if ((int)$estacion['calidad'] === 2) {
            $eventos[] = [
                'tipo' => 'calidad',
                'titulo' => 'Inspección de calidad',
                'descripcion' => 'Se realizó inspección de calidad en la estación ' . $estacion['cve_estacion'],
                'usuario' => $estacion['encargado'] ?? 'N/A',
                'fecha' => $estacion['fecha_fin']
            ];
        }

        if ((int)$estacion['estampado'] === 2) {
            $eventos[] = [
                'tipo' => 'vin',
                'titulo' => 'Estampado VIN',
                'descripcion' => 'Se realizó el estampado del VIN ' . ($estacion['vin_asignado'] ?? 'N/A'),
                'usuario' => $estacion['encargado'] ?? 'N/A',
                'fecha' => $estacion['fecha_asignacion'] ?? $estacion['fecha_fin']
            ];
        }

        if (!empty($estacion['fecha_fin'])) {
            $eventos[] = [
                'tipo' => 'fin_estacion',
                'titulo' => 'Fin de estación',
                'descripcion' => 'Se finalizó la estación ' . $estacion['cve_estacion'] . ' - ' . $estacion['nombre_estacion'],
                'usuario' => $estacion['encargado'] ?? 'N/A',
                'fecha' => $estacion['fecha_fin']
            ];
        }
    }

    return [
        'unidad' => $unidad,
        'estaciones' => $estaciones,
        'eventos' => $eventos
    ];
}

}
