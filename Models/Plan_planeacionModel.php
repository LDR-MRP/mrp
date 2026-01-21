<?php

class Plan_planeacionModel extends Mysql
{

    public $intidProducto;
    public $intIdPlaneacion;

    public function __construct()
    {
        parent::__construct();
    }

    public function generarNumeroOrden()
    {
        date_default_timezone_set('America/Mexico_City');


        $fecha = date('ymd');


        $sql = "SELECT num_orden
            FROM mrp_planeacion
            WHERE estado = 2
              AND num_orden LIKE 'OT%'
            ORDER BY CAST(SUBSTRING_INDEX(num_orden, '-', -1) AS UNSIGNED) DESC
            LIMIT 1";

        $result = $this->select($sql);

        $numero = 1;

        if (!empty($result)) {
            $ultimaClave = $result['num_orden'];
            $ultimoNumero = (int) substr($ultimaClave, strrpos($ultimaClave, '-') + 1);
            $numero = $ultimoNumero + 1;
        }


        return 'OT' . $fecha . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }




    public function selectOptionProductos()
    {
        $sql = "SELECT * FROM  mrp_productos 
					WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionEstacionesByProducto($idproducto)
    {
        $this->intidProducto = (int) $idproducto;

        $sqlRutas = "SELECT pr.*
                FROM mrp_producto_ruta AS pr
                WHERE pr.estado = 2
                  AND pr.productoid = {$this->intidProducto}";

        $rutas = $this->select_all($sqlRutas);

        if (empty($rutas)) {
            return [];
        }

        $out = [];

        foreach ($rutas as $r) {

            $idRuta = (int) ($r['idruta_producto'] ?? 0);

            if ($idRuta <= 0) {
                $r['detalle'] = [];
                $out[] = $r;
                continue;
            }


            $sqlDetalle = "SELECT 
                d.*,
                es.nombre_estacion,
				em.idmantenimiento,
                es.proceso,

             
                COALESCE(em.mantenimiento, 1) AS mantenimiento,

       
                CASE COALESCE(em.mantenimiento, 1)
                    WHEN 1 THEN 'Sin mantenimiento'
                    WHEN 2 THEN 'Programado'
                    WHEN 3 THEN 'En proceso'
                    WHEN 4 THEN 'Finalizado'
                    WHEN 5 THEN 'Cancelado'
                    ELSE 'Sin mantenimiento'
                END AS mantenimiento_texto

            FROM mrp_producto_ruta_detalle AS d
            INNER JOIN mrp_estacion AS es
                ON d.estacionid = es.idestacion

  
            LEFT JOIN (
                SELECT em1.idmantenimiento, em1.estacionid, em1.mantenimiento
                FROM mrp_estacion_mantenimiento em1
                INNER JOIN (
                    SELECT estacionid, MAX(idmantenimiento) AS max_id
                    FROM mrp_estacion_mantenimiento
                    WHERE estado = 2
                    GROUP BY estacionid
                ) em2
                  ON em2.estacionid = em1.estacionid
                 AND em2.max_id     = em1.idmantenimiento
            ) em
                ON em.estacionid = es.idestacion

            WHERE d.estado = 2
              AND d.ruta_productoid = {$idRuta}
            ORDER BY d.orden ASC
        ";

            $detalle = $this->select_all($sqlDetalle);

            $r['detalle'] = is_array($detalle) ? $detalle : [];
            $out[] = $r;
        }

        return $out;
    }


    public function selectOperadores()
    {
        $sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=2 ";
        $request = $this->select_all($sql);
        return $request;
    }


    public function selectOperadoresAyudantes()
    {
        $sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=3 ";
        $request = $this->select_all($sql);
        return $request;
    }



    public function insertPlaneacion($num_orden, $productoid, $pedido, $supervisor, $prioridad, $cantidad, $fecha_inicio, $fecha_requerida, $notas)
    {
        $sql = "INSERT INTO mrp_planeacion (num_orden, productoid, num_pedido, supervisor, prioridad, cantidad, fecha_inicio, fecha_requerida, notas, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2)";

        $arrData = [$num_orden, $productoid, $pedido, $supervisor, $prioridad, $cantidad, $fecha_inicio, $fecha_requerida, $notas];

        return $this->insert($sql, $arrData);
    }

    public function upsertPlaneacionEstacion($planeacionid, $estacionid, $orden)
    {

        $sqlFind = "SELECT id_planeacion_estacion
              FROM mrp_planeacion_estacion
              WHERE planeacionid = $planeacionid AND estacionid = $estacionid AND estado = 2
              LIMIT 1";
        $row = $this->select($sqlFind);

        if (!empty($row['id_planeacion_estacion'])) {
            $id = (int) $row['id_planeacion_estacion'];


            $sqlUpd = "UPDATE mrp_planeacion_estacion
               SET orden = ?
               WHERE id_planeacion_estacion = $id";


            $arrData = array($orden);

            $request = $this->update($sqlUpd, $arrData);

            return $request;
        }


        $sqlIns = "INSERT INTO mrp_planeacion_estacion
              (planeacionid, estacionid, orden, estado)
            VALUES (?,?,?,2)";
        return $this->insert($sqlIns, [$planeacionid, $estacionid, $orden]);
    }


    public function clearOperadoresByPlaneacionEstacion($planeacionEstacionId)
    {

        $sql = "UPDATE mrp_planeacion_estacion_operador
          SET estado = ?
          WHERE planeacion_estacionid = $planeacionEstacionId";


        $arrData = array(0);

        $request = $this->update($sql, $arrData);

        return $request;
    }

    public function insertPlaneacionOperador($planeacionEstacionId, $usuarioid, $rol)
    {
        $sql = "INSERT INTO mrp_planeacion_estacion_operador
            (planeacion_estacionid, usuarioid, rol, estado)
          VALUES (?,?,?,2)";
        return $this->insert($sql, [$planeacionEstacionId, $usuarioid, $rol]);
    }



    public function selectPlanPendientes()
{

    $isAdmin   = isset($_SESSION['rolid']) && (int)$_SESSION['rolid'] === 1;
    $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    if (!$isAdmin && $userIdSes <= 0) {
        return [];
    }


    $whereUser = "";
    if (!$isAdmin) {
        $whereUser = " AND pla.idplaneacion IN (
                        SELECT DISTINCT pe.planeacionid
                        FROM mrp_planeacion_estacion pe
                        INNER JOIN mrp_planeacion_estacion_operador o
                          ON o.planeacion_estacionid = pe.id_planeacion_estacion
                        WHERE pe.estado = 2
                          AND o.estado  = 2
                          AND o.usuarioid = {$userIdSes}
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

    $isAdmin   = isset($_SESSION['rolid']) && (int)$_SESSION['rolid'] === 1;
    $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    if (!$isAdmin && $userIdSes <= 0) {
        return [];
    }

  
    $whereUser = "";
    if (!$isAdmin) {
        $whereUser = " AND pla.idplaneacion IN (
                        SELECT DISTINCT pe.planeacionid
                        FROM mrp_planeacion_estacion pe
                        INNER JOIN mrp_planeacion_estacion_operador o
                          ON o.planeacion_estacionid = pe.id_planeacion_estacion
                        WHERE pe.estado = 2
                          AND o.estado  = 2
                          AND o.usuarioid = {$userIdSes}
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


public function selectPlanCanceladas()
{

    $isAdmin   = isset($_SESSION['rolid']) && (int)$_SESSION['rolid'] === 1;
    $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    if (!$isAdmin && $userIdSes <= 0) {
        return [];
    }

  
    $whereUser = "";
    if (!$isAdmin) {
        $whereUser = " AND pla.idplaneacion IN (
                        SELECT DISTINCT pe.planeacionid
                        FROM mrp_planeacion_estacion pe
                        INNER JOIN mrp_planeacion_estacion_operador o
                          ON o.planeacion_estacionid = pe.id_planeacion_estacion
                        WHERE pe.estado = 2
                          AND o.estado  = 2
                          AND o.usuarioid = {$userIdSes}
                      )";
    }


    $sql = "SELECT pla.*,
                   pla.estado AS estado_planeacion,
                   pro.cve_producto,
                   pro.descripcion AS descripcion_producto
            FROM mrp_planeacion AS pla
            INNER JOIN mrp_productos AS pro
              ON pla.productoid = pro.idproducto
            WHERE pla.fase = 6
              AND pla.estado != 0
              {$whereUser};";

    return $this->select_all($sql);
}






    public function consultarExistenciasSSS(int $productoid, int $estacionid, int $cantidadPlaneada)
    {
        $faltantes = [];


        $sqlComp = "SELECT 
                    c.idcomponente,
                    c.almacenid,
                    c.productoid,
                    c.estacionid,
                    c.inventarioid,
                    c.cantidad AS cantidad_por_unidad
                FROM mrp_estacion_componentes c
                WHERE c.estado = 2
                  AND c.productoid = $productoid
                  AND c.estacionid = $estacionid";

        $componentes = $this->select_all($sqlComp);

        if (empty($componentes)) {
            return [
                'status' => true,
                'msg' => 'Sin componentes configurados para validar',
                'data' => []
            ];
        }


        foreach ($componentes as $c) {
            $inventarioid = (int) ($c['inventarioid'] ?? 0);
            $almacenid = (int) ($c['almacenid'] ?? 0);

            $cantPorUnidad = (float) ($c['cantidad_por_unidad'] ?? 0);

            // requerid = cantidadPlaeada * cantidad_por_unidad
            $requerido = (float) $cantidadPlaneada * (float) $cantPorUnidad;


            $sqlExist = "SELECT m.existencia
                     FROM wms_movimientos_inventario m
                     WHERE m.estado = 2
                       AND m.inventarioid = $inventarioid
                       AND m.almacenid = $almacenid
                     ORDER BY m.fecha_movimiento DESC, m.idmovinventario DESC
                     LIMIT 1";

            $rowExist = $this->select($sqlExist);

            $existencia = isset($rowExist['existencia']) ? (float) $rowExist['existencia'] : 0;


            if ($requerido > $existencia) {
                $faltante = $requerido - $existencia;

                $faltantes[] = [
                    'productoid' => $productoid,
                    'estacionid' => $estacionid,
                    'almacenid' => $almacenid,
                    'inventarioid' => $inventarioid,
                    'requerido' => $requerido,
                    'existencia' => $existencia,
                    'faltante' => $faltante
                ];
            }
        }


        if (!empty($faltantes)) {
            return [
                'status' => 0,
                'msg' => 'Faltan componentes en inventario',
                'data' => $faltantes
            ];
        }

        return [
            'status' => true,
            'msg' => 'Existencias OK',
            'data' => []
        ];
    }



    public function consultarExistencias(int $productoid, int $estacionid, int $cantidadPlaneada)
    {
        $faltantes = [];

        $sqlComp = "SELECT 
                    c.idcomponente,
                    c.almacenid,
                    c.productoid,
                    c.estacionid,
                    c.inventarioid,
                    c.cantidad AS cantidad_por_unidad
                FROM mrp_estacion_componentes c
                WHERE c.estado = 2
                  AND c.productoid = $productoid
                  AND c.estacionid = $estacionid";

        $componentes = $this->select_all($sqlComp);

        if (empty($componentes)) {
            return [
                'status' => true,
                'msg' => 'Sin componentes configurados para validar',
                'data' => []
            ];
        }

        foreach ($componentes as $c) {
            $inventarioid = (int) ($c['inventarioid'] ?? 0);
            $almacenid = (int) ($c['almacenid'] ?? 0);
            $cantPorUnidad = (float) ($c['cantidad_por_unidad'] ?? 0);


            $requerido = (float) $cantidadPlaneada * (float) $cantPorUnidad;

            $sqlExist = "SELECT 
                        m.existencia,
                        inv.descripcion,
                        al.descripcion as descripcion_almacen
                     FROM wms_movimientos_inventario m
                     INNER JOIN wms_inventario inv 
                        ON inv.idinventario = m.inventarioid
                     INNER JOIN wms_almacenes al
                        ON al.idalmacen = m.almacenid
                     WHERE m.estado = 2
                       AND m.inventarioid = $inventarioid
                       AND m.almacenid = $almacenid
                     ORDER BY m.fecha_movimiento DESC, m.idmovinventario DESC
                     LIMIT 1";

            $rowExist = $this->select($sqlExist);

            $existencia = isset($rowExist['existencia']) ? (float) $rowExist['existencia'] : 0;
            $descripcion = isset($rowExist['descripcion']) ? (string) $rowExist['descripcion'] : '';
            $descripcion_almacen = isset($rowExist['descripcion_almacen']) ? (string) $rowExist['descripcion_almacen'] : '';


            if ($descripcion === '') {
                $rowInv = $this->select("SELECT descripcion FROM wms_inventario WHERE idinventario = $inventarioid LIMIT 1");
                $descripcion = isset($rowInv['descripcion']) ? (string) $rowInv['descripcion'] : '';
            }


            if ($descripcion_almacen === '') {
                $rowAlm = $this->select("SELECT descripcion FROM wms_almacenes WHERE idalmacen = $almacenid LIMIT 1");
                $descripcion_almacen = isset($rowAlm['descripcion']) ? (string) $rowAlm['descripcion'] : '';
            }


            if ($requerido > $existencia) {
                $faltante = $requerido - $existencia;

                $faltantes[] = [
                    'productoid' => $productoid,
                    'estacionid' => $estacionid,
                    'almacenid' => $almacenid,
                    'inventarioid' => $inventarioid,
                    'descripcion' => $descripcion,
                    'descripcion_almacen' => $descripcion_almacen,


                    'cantidad_planeada' => (float) $cantidadPlaneada,
                    'cantidad_por_unidad' => (float) $cantPorUnidad,
                    'requerido' => (float) $requerido,
                    'existencia' => (float) $existencia,
                    'faltante' => (float) $faltante
                ];
            }
        }

        if (!empty($faltantes)) {
            return [
                'status' => 0,
                'msg' => 'Faltan componentes en inventario',
                'data' => $faltantes
            ];
        }

        return [
            'status' => true,
            'msg' => 'Existencias OK',
            'data' => []
        ];
    }



    public function consultarHerramientasExistencias(int $productoid, int $estacionid, int $cantidadPlaneada)
    {
        $faltantes = [];

        $sqlHer = "SELECT 
                  h.idherramienta,
                  h.almacenid,
                  h.productoid,
                  h.estacionid,
                  h.inventarioid,
                  h.cantidad AS cantidad_por_unidad
              FROM mrp_estacion_herramientas h
              WHERE h.estado = 2
                AND h.productoid = $productoid
                AND h.estacionid = $estacionid";

        $herramientas = $this->select_all($sqlHer);

        if (empty($herramientas)) {
            return [
                'status' => true,
                'msg' => 'Sin herramientas configuradas para validar',
                'data' => []
            ];
        }

        foreach ($herramientas as $h) {
            $inventarioid = (int) ($h['inventarioid'] ?? 0);
            $almacenid = (int) ($h['almacenid'] ?? 0);
            $cantPorUnidad = (float) ($h['cantidad_por_unidad'] ?? 0);


            $requerido = (float) $cantidadPlaneada * (float) $cantPorUnidad;

            $sqlExist = "SELECT 
                        m.existencia,
                        inv.descripcion,
                        al.descripcion AS descripcion_almacen
                     FROM wms_movimientos_inventario m
                     INNER JOIN wms_inventario inv 
                        ON inv.idinventario = m.inventarioid
                     INNER JOIN wms_almacenes al
                        ON al.idalmacen = m.almacenid
                     WHERE m.estado = 2
                       AND m.inventarioid = $inventarioid
                       AND m.almacenid = $almacenid
                     ORDER BY m.fecha_movimiento DESC, m.idmovinventario DESC
                     LIMIT 1";

            $rowExist = $this->select($sqlExist);

            $existencia = isset($rowExist['existencia']) ? (float) $rowExist['existencia'] : 0;
            $descripcion = isset($rowExist['descripcion']) ? (string) $rowExist['descripcion'] : '';
            $descripcion_almacen = isset($rowExist['descripcion_almacen']) ? (string) $rowExist['descripcion_almacen'] : '';


            if ($descripcion === '') {
                $rowInv = $this->select("SELECT descripcion FROM wms_inventario WHERE idinventario = $inventarioid LIMIT 1");
                $descripcion = isset($rowInv['descripcion']) ? (string) $rowInv['descripcion'] : '';
            }


            if ($descripcion_almacen === '') {
                $rowAlm = $this->select("SELECT descripcion FROM wms_almacenes WHERE idalmacen = $almacenid LIMIT 1");
                $descripcion_almacen = isset($rowAlm['descripcion']) ? (string) $rowAlm['descripcion'] : '';
            }

            if ($requerido > $existencia) {
                $faltante = $requerido - $existencia;

                $faltantes[] = [
                    'productoid' => $productoid,
                    'estacionid' => $estacionid,
                    'almacenid' => $almacenid,
                    'inventarioid' => $inventarioid,
                    'descripcion' => $descripcion,
                    'descripcion_almacen' => $descripcion_almacen,


                    'cantidad_planeada' => (float) $cantidadPlaneada,
                    'cantidad_por_unidad' => (float) $cantPorUnidad,
                    'requerido' => (float) $requerido,
                    'existencia' => (float) $existencia,
                    'faltante' => (float) $faltante
                ];
            }
        }

        if (!empty($faltantes)) {
            return [
                'status' => 0,
                'msg' => 'Faltan herramientas en inventario',
                'data' => $faltantes
            ];
        }

        return [
            'status' => true,
            'msg' => 'Existencias OK',
            'data' => []
        ];
    }


    public function getEmailsUsuariosByIds(array $ids)
    {
        if (empty($ids))
            return [];

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn($x) => $x > 0);
        if (empty($ids))
            return [];

        $in = implode(',', $ids);

        $sql = "SELECT idusuario, nombres, apellidos, email_user
            FROM usuarios
            WHERE idusuario IN ($in) AND status = 1";

        return $this->select_all($sql);
    }

    public function getProducto(int $idproducto)
    {
        $idproducto = (int) $idproducto;

        $sql = "SELECT cve_producto, descripcion
          FROM mrp_productos
          WHERE idproducto = {$idproducto}
          LIMIT 1";

        return $this->select($sql);
    }

    public function getEstacionesByIds(array $ids)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids))
            return [];

        $in = implode(',', $ids);


        $sql = "SELECT idestacion, nombre_estacion, proceso
          FROM mrp_estacion
          WHERE idestacion IN ($in)";

        return $this->select_all($sql);
    }

    public function obtenerPlaneacionV($num_orden)
    {


        $num_orden = trim((string) $num_orden);
        $key = preg_replace('/[^A-Za-z0-9]/', '', $num_orden); // OT260106001

        $sqlPla = "SELECT pla.*, pr.cve_producto, pr.descripcion
             FROM mrp_planeacion AS pla
             INNER JOIN mrp_productos AS pr ON pla.productoid = pr.idproducto
             WHERE REPLACE(pla.num_orden,'-','') = '{$key}'
             LIMIT 1";

        $planeacion = $this->select($sqlPla);

        if (empty($planeacion)) {
            return ['status' => false, 'msg' => 'No existe la planeación', 'data' => []];
        }

        $planeacionid = (int) ($planeacion['idplaneacion'] ?? 0);
        if ($planeacionid <= 0) {
            return ['status' => false, 'msg' => 'Planeación inválida', 'data' => []];
        }

        $sqlEst = "SELECT pe.id_planeacion_estacion, pe.planeacionid, pe.estacionid, pe.orden, pe.estado,
                    est.cve_estacion, est.nombre_estacion, est.proceso
             FROM mrp_planeacion_estacion pe
             INNER JOIN mrp_estacion AS est
               ON pe.estacionid = est.idestacion
             WHERE pe.planeacionid = {$planeacionid}
               AND pe.estado = 2
             ORDER BY pe.orden ASC";

        $estaciones = $this->select_all($sqlEst);

        if (empty($estaciones)) {
            $planeacion['estaciones'] = [];
            return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
        }

        // ---------------------------------------------------------
        // IDs de planeacion_estacion
        // ---------------------------------------------------------
        $idsPE = array_map(fn($r) => (int) $r['id_planeacion_estacion'], $estaciones);
        $idsPE = array_values(array_filter($idsPE, fn($v) => $v > 0));

        $in = implode(',', $idsPE);

        // ---------------------------------------------------------
        // Operadores (encargados/ayudantes)
        // ---------------------------------------------------------
        $sqlOp = "SELECT o.planeacion_estacionid,
                   o.usuarioid,
                   UPPER(TRIM(o.rol)) AS rol,
                   o.estado,
                   CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
            FROM mrp_planeacion_estacion_operador o
            INNER JOIN usuarios u
              ON u.idusuario = o.usuarioid
            WHERE o.estado = 2
              AND o.planeacion_estacionid IN ({$in})
            ORDER BY o.planeacion_estacionid ASC";

        $ops = $this->select_all($sqlOp);

        $opsByPE = [];
        foreach ($ops as $op) {
            $peid = (int) ($op['planeacion_estacionid'] ?? 0);
            if ($peid <= 0)
                continue;
            $opsByPE[$peid][] = $op;
        }

        $sqlOT = "SELECT ot.idorden,
                   ot.planeacion_estacionid,
                   ot.num_sub_orden,
                   ot.fecha_inicio,
                   ot.fecha_fin,
                   ot.comentarios,
                   ot.estatus,
                   CAST(SUBSTRING_INDEX(ot.num_sub_orden, 'S', -1) AS UNSIGNED) AS ord_s
            FROM mrp_ordenes_trabajo ot
            WHERE ot.planeacion_estacionid IN ({$in})
            ORDER BY ot.planeacion_estacionid ASC, ord_s ASC";

        $ots = $this->select_all($sqlOT);

        $otsByPE = [];
        foreach ($ots as $ot) {
            $peid = (int) ($ot['planeacion_estacionid'] ?? 0);
            if ($peid <= 0)
                continue;
            $otsByPE[$peid][] = $ot;
        }

        // ---------------------------------------------------------
        // Construcción de salida
        // ---------------------------------------------------------
        $outEstaciones = [];

        foreach ($estaciones as $e) {
            $peid = (int) $e['id_planeacion_estacion'];

            $item = [
                'id_planeacion_estacion' => $peid,
                'planeacionid' => (int) $e['planeacionid'],
                'estacionid' => (int) $e['estacionid'],
                'orden' => (int) $e['orden'],
                'estado' => (int) $e['estado'],
                'cve_estacion' => $e['cve_estacion'],
                'nombre_estacion' => $e['nombre_estacion'],
                'proceso' => $e['proceso'],
                'encargados' => [],
                'ayudantes' => [],

                'ordenes_trabajo' => [],
            ];

            // operadores
            $lista = $opsByPE[$peid] ?? [];
            foreach ($lista as $op) {
                $rol = (string) ($op['rol'] ?? '');

                $objOper = [
                    'usuarioid' => (int) ($op['usuarioid'] ?? 0),
                    'rol' => $rol,
                    'nombre_completo' => (string) ($op['nombre_completo'] ?? ''),
                ];

                if ($rol === 'ENCARGADO') {
                    $item['encargados'][] = $objOper;
                } else if ($rol === 'AYUDANTE') {
                    $item['ayudantes'][] = $objOper;
                }
            }


            $listaOT = $otsByPE[$peid] ?? [];
            foreach ($listaOT as $ot) {
                $item['ordenes_trabajo'][] = [
                    'idorden' => (int) ($ot['idorden'] ?? 0),
                    'planeacion_estacionid' => (int) ($ot['planeacion_estacionid'] ?? 0),
                    'num_sub_orden' => (string) ($ot['num_sub_orden'] ?? ''),
                    'fecha_inicio' => (string) ($ot['fecha_inicio'] ?? ''),
                    'fecha_fin' => (string) ($ot['fecha_fin'] ?? ''),
                    'comentarios' => (string) ($ot['comentarios'] ?? ''),
                    'estatus' => (string) ($ot['estatus'] ?? ''),
                ];
            }

            $outEstaciones[] = $item;
        }

        $planeacion['estaciones'] = $outEstaciones;

        return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }





//con esta función ya nos muestra las solicitudes que son asignadas a cada operador
    public function obtenerPlaneacion($num_orden)
{
    // =========================================================
    // 1) Normalizar num_orden (ej: OT260106-001 -> OT260106001)
    // =========================================================
    $num_orden = trim((string) $num_orden);
    $key = preg_replace('/[^A-Za-z0-9]/', '', $num_orden);

    // =========================================================
    // 2) Traer la planeación (header)
    // =========================================================
    $sqlPla = "SELECT pla.*, pr.cve_producto, pr.descripcion
              FROM mrp_planeacion AS pla
              INNER JOIN mrp_productos AS pr ON pla.productoid = pr.idproducto
              WHERE REPLACE(pla.num_orden,'-','') = '{$key}'
              LIMIT 1";

    $planeacion = $this->select($sqlPla);

    if (empty($planeacion)) {
        return ['status' => false, 'msg' => 'No existe la planeación', 'data' => []];
    }

    $planeacionid = (int) ($planeacion['idplaneacion'] ?? 0);
    if ($planeacionid <= 0) {
        return ['status' => false, 'msg' => 'Planeación inválida', 'data' => []];
    }

    // =========================================================
    // 3) Seguridad por usuario:
    //    - Admin (rolid=1): ve todo
    //    - No admin: solo ve estaciones donde esté asignado (operador)
    // =========================================================
    $isAdmin   = isset($_SESSION['rolid']) && (int)$_SESSION['rolid'] === 1;
    $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    // Si NO es admin y no hay idusuario en sesión, no podemos mostrar nada
    if (!$isAdmin && $userIdSes <= 0) {
        return ['status' => false, 'msg' => 'Sesión inválida (sin usuario)', 'data' => []];
    }

    // =========================================================
    // 4) Traer estaciones activas (estado=2) PERO:
    //    - Admin: todas las estaciones activas de esa planeación
    //    - No admin: solo estaciones donde el usuario esté asignado en
    //      mrp_planeacion_estacion_operador (estado=2)
    // =========================================================
    $whereUserEst = "";
    if (!$isAdmin) {
        // Solo estaciones donde el usuario está asignado
        $whereUserEst = " AND pe.id_planeacion_estacion IN (
                            SELECT o2.planeacion_estacionid
                            FROM mrp_planeacion_estacion_operador o2
                            WHERE o2.estado = 2
                              AND o2.usuarioid = {$userIdSes}
                         )";
    }

    $sqlEst = "SELECT pe.id_planeacion_estacion, pe.planeacionid, pe.estacionid, pe.orden, pe.estado,
                      est.cve_estacion, est.nombre_estacion, est.proceso
               FROM mrp_planeacion_estacion pe
               INNER JOIN mrp_estacion AS est
                  ON pe.estacionid = est.idestacion
               WHERE pe.planeacionid = {$planeacionid}
                 AND pe.estado = 2
                 {$whereUserEst}
               ORDER BY pe.orden ASC";

    $estaciones = $this->select_all($sqlEst);

    // Si no hay estaciones visibles para este usuario, regresamos OK pero vacío
    if (empty($estaciones)) {
        $planeacion['estaciones'] = [];
        return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }

    // =========================================================
    // 5) Sacar IDs de planeacion_estacion para armar IN(...)
    // =========================================================
    $idsPE = array_map(fn($r) => (int)$r['id_planeacion_estacion'], $estaciones);
    $idsPE = array_values(array_filter($idsPE, fn($v) => $v > 0));

    if (empty($idsPE)) {
        $planeacion['estaciones'] = [];
        return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }

    $in = implode(',', $idsPE);

    // =========================================================
    // 6) Operadores (encargados/ayudantes) de esas estaciones
    //    Nota: aquí NO filtramos por userId, porque el usuario
    //    necesita ver el equipo asignado a la estación (encargados
    //    y ayudantes), aunque él sea solo uno de ellos.
    // =========================================================
    $sqlOp = "SELECT o.planeacion_estacionid,
                     o.usuarioid,
                     UPPER(TRIM(o.rol)) AS rol,
                     o.estado,
                     CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
              FROM mrp_planeacion_estacion_operador o
              INNER JOIN usuarios u
                 ON u.idusuario = o.usuarioid
              WHERE o.estado = 2
                AND o.planeacion_estacionid IN ({$in})
              ORDER BY o.planeacion_estacionid ASC";

    $ops = $this->select_all($sqlOp);

    // Indexar operadores por PEID
    $opsByPE = [];
    foreach ($ops as $op) {
        $peid = (int)($op['planeacion_estacionid'] ?? 0);
        if ($peid <= 0) continue;
        $opsByPE[$peid][] = $op;
    }

    // =========================================================
    // 7) Órdenes de trabajo (subórdenes) SOLO de las estaciones visibles
    //    - Admin: todas las OT de esas estaciones
    //    - No admin: aquí realmente NO hace falta filtrar más,
    //      porque las estaciones ya están filtradas por asignación.
    // =========================================================
    $sqlOT = "SELECT ot.idorden,
                     ot.planeacion_estacionid,
                     ot.num_sub_orden,
                     ot.fecha_inicio,
                     ot.fecha_fin,
                     ot.comentarios,
                     ot.estatus,
                     CAST(SUBSTRING_INDEX(ot.num_sub_orden, 'S', -1) AS UNSIGNED) AS ord_s
              FROM mrp_ordenes_trabajo ot
              WHERE ot.planeacion_estacionid IN ({$in})
              ORDER BY ot.planeacion_estacionid ASC, ord_s ASC";

    $ots = $this->select_all($sqlOT);

    // Indexar OTs por PEID
    $otsByPE = [];
    foreach ($ots as $ot) {
        $peid = (int)($ot['planeacion_estacionid'] ?? 0);
        if ($peid <= 0) continue;
        $otsByPE[$peid][] = $ot;
    }

    // =========================================================
    // 8) Construcción final del JSON de salida
    // =========================================================
    $outEstaciones = [];

    foreach ($estaciones as $e) {
        $peid = (int)$e['id_planeacion_estacion'];

        $item = [
            'id_planeacion_estacion' => $peid,
            'planeacionid'           => (int)$e['planeacionid'],
            'estacionid'             => (int)$e['estacionid'],
            'orden'                  => (int)$e['orden'],
            'estado'                 => (int)$e['estado'],
            'cve_estacion'           => (string)$e['cve_estacion'],
            'nombre_estacion'        => (string)$e['nombre_estacion'],
            'proceso'                => (string)$e['proceso'],

            // Listas de operadores
            'encargados'             => [],
            'ayudantes'              => [],

            // Subórdenes (OT)
            'ordenes_trabajo'        => [],
        ];

        // -------- Operadores por estación
        $listaOps = $opsByPE[$peid] ?? [];
        foreach ($listaOps as $op) {
            $rol = (string)($op['rol'] ?? '');

            $objOper = [
                'usuarioid'        => (int)($op['usuarioid'] ?? 0),
                'rol'              => $rol,
                'nombre_completo'  => (string)($op['nombre_completo'] ?? ''),
            ];

            if ($rol === 'ENCARGADO') {
                $item['encargados'][] = $objOper;
            } else if ($rol === 'AYUDANTE') {
                $item['ayudantes'][] = $objOper;
            }
        }

        // -------- OTs por estación
        $listaOT = $otsByPE[$peid] ?? [];
        foreach ($listaOT as $ot) {
            $item['ordenes_trabajo'][] = [
                'idorden'               => (int)($ot['idorden'] ?? 0),
                'planeacion_estacionid' => (int)($ot['planeacion_estacionid'] ?? 0),
                'num_sub_orden'         => (string)($ot['num_sub_orden'] ?? ''),
                'fecha_inicio'          => (string)($ot['fecha_inicio'] ?? ''),
                'fecha_fin'             => (string)($ot['fecha_fin'] ?? ''),
                'comentarios'           => (string)($ot['comentarios'] ?? ''),
                'estatus'               => (string)($ot['estatus'] ?? ''),
            ];
        }

        $outEstaciones[] = $item;
    }

    $planeacion['estaciones'] = $outEstaciones;

    return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
}


 

    public function insertOrdenes(int $id_planeacion_estacion, string $num_orden_s)
    {
        $sql = "INSERT INTO mrp_ordenes_trabajo (planeacion_estacionid, num_sub_orden)
          VALUES (?, ?)";
        return $this->insert($sql, [$id_planeacion_estacion, $num_orden_s]);
    }



    public function updateComentarioOrden($idorden, $comentario)
    {

        $sqlUpd = "UPDATE mrp_ordenes_trabajo
            SET comentarios = ?
            WHERE idorden = $idorden
            LIMIT 1";

        $arrData = array($comentario);

        $request = $this->update($sqlUpd, $arrData);
        return $request;


    }




public function startOT(int $idorden, string $fecha_inicio)
{
  $idorden = (int)$idorden;

  // Si no te mandan fecha (o quieres estandarizar), genera aquí
  $fecha_inicio = trim((string)$fecha_inicio);
  if ($fecha_inicio === '') {
    $fecha_inicio = date('Y-m-d H:i:s');
  }

  // ---------------------------------------------------
  // 1) Traer Sub-OT + datos de planeación estación
  //    ✅ FIX: incluir pe.planeacionid
  // ---------------------------------------------------
  $sql = "SELECT 
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            pe.id_planeacion_estacion,
            pe.planeacionid,                 -- ✅ FIX
            pe.orden AS estacion_orden
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE ot.idorden = {$idorden}
          LIMIT 1";

  $cur = $this->select($sql);

  if (empty($cur)) {
    return ['status'=>false,'msg'=>'No existe la Sub-OT','data'=>[]];
  }

  $estatus = (int)($cur['estatus'] ?? 0);
  if ($estatus !== 1) {
    return ['status'=>false,'msg'=>'No puedes iniciar: la Sub-OT no está pendiente','data'=>[
      'estatus_actual'=>$estatus
    ]];
  }

  // ✅ FIX: planeacionid correcto
  $peid   = (int)($cur['planeacion_estacionid'] ?? 0);
  $idpla  = (int)($cur['planeacionid'] ?? 0);
  $estOrd = (int)($cur['estacion_orden'] ?? 0);
  $subot  = trim((string)($cur['num_sub_orden'] ?? ''));

  if ($peid <= 0 || $idpla <= 0 || $estOrd <= 0 || $subot === '') {
    return ['status'=>false,'msg'=>'Datos incompletos para iniciar (peid/planeacionid/orden/subot)','data'=>[
      'peid'=>$peid,'planeacionid'=>$idpla,'orden'=>$estOrd,'subot'=>$subot
    ]];
  }

  // ---------------------------------------------------
  // 2) Detectar Sxx (sub-OT secuencial dentro de estación)
  // ---------------------------------------------------
  $snum = 0;
  if (preg_match('/-S(\d+)\s*$/i', $subot, $m)) {
    $snum = (int)$m[1];
  }
  if ($snum <= 0) {
    return ['status'=>false,'msg'=>'Sub-OT inválida (no se detectó Sxx)','data'=>[]];
  }

  $base = preg_replace('/-S\d+\s*$/i', '', $subot);
  $subotSql = addslashes($subot);

  // ---------------------------------------------------
  // 3) Regla: no puede haber otra Sub-OT en proceso en la misma estación
  // ---------------------------------------------------
  $sqlBusy = "SELECT COUNT(*) AS c
              FROM mrp_ordenes_trabajo
              WHERE planeacion_estacionid = {$peid}
                AND estatus = 2";
  $busy = $this->select($sqlBusy);

  if ((int)($busy['c'] ?? 0) > 0) {
    return ['status'=>false,'msg'=>'Ya existe una Sub-OT en proceso en esta estación','data'=>[]];
  }

  // ---------------------------------------------------
  // 4) Regla: si Sxx > 1, la anterior en esta MISMA estación debe estar finalizada (estatus=3)
  // ---------------------------------------------------
  if ($snum > 1) {
    $prevSub = $base . '-S' . str_pad((string)($snum - 1), 2, '0', STR_PAD_LEFT);
    $prevSubSql = addslashes($prevSub);

    $sqlPrev = "SELECT estatus
                FROM mrp_ordenes_trabajo
                WHERE planeacion_estacionid = {$peid}
                  AND num_sub_orden = '{$prevSubSql}'
                LIMIT 1";
    $prev = $this->select($sqlPrev);

    if (empty($prev)) {
      return ['status'=>false,'msg'=>"No se encontró {$prevSub} en esta estación (validación)",'data'=>[
        'planeacion_estacionid'=>$peid,
        'prevSub'=>$prevSub
      ]];
    }

    if ((int)($prev['estatus'] ?? 0) !== 3) {
      return ['status'=>false,'msg'=>"Primero finaliza {$prevSub} en esta estación",'data'=>[]];
    }
  }

  // ---------------------------------------------------
  // 5) Regla: si NO es la primera estación, la misma Sub-OT (Sxx) en la estación anterior debe estar finalizada
  //    ✅ FIX: comparar por pe2.planeacionid (NO por id_planeacion_estacion)
  // ---------------------------------------------------
  if ($estOrd > 1) {
    $prevOrden = $estOrd - 1;

    $sqlPrevStation = "SELECT ot.estatus
                       FROM mrp_planeacion_estacion pe2
                       INNER JOIN mrp_ordenes_trabajo ot
                         ON ot.planeacion_estacionid = pe2.id_planeacion_estacion
                       WHERE pe2.planeacionid = {$idpla}   -- ✅ FIX CLAVE
                         AND pe2.orden = {$prevOrden}
                         AND ot.num_sub_orden = '{$subotSql}'
                       LIMIT 1";

    $prevStation = $this->select($sqlPrevStation);

    if (empty($prevStation)) {
      return ['status'=>false,'msg'=>'No se encontró la Sub-OT en la estación anterior (validación)','data'=>[
        'planeacionid'=>$idpla,
        'orden_anterior'=>$prevOrden,
        'subot'=>$subot
      ]];
    }

    if ((int)($prevStation['estatus'] ?? 0) !== 3) {
      return ['status'=>false,'msg'=>'No puedes iniciar este proceso porque aún no está finalizado en la estación anterior.','data'=>[]];
    }
  }

  // ---------------------------------------------------
  // 6) Actualizar a En proceso
  // ---------------------------------------------------
  $sqlUpd = "UPDATE mrp_ordenes_trabajo
             SET fecha_inicio = ?, estatus = 2
             WHERE idorden = {$idorden} AND estatus = 1";

  $arrData = [$fecha_inicio];

  $ok = $this->update($sqlUpd, $arrData);

  if (!$ok) {
    return ['status'=>false,'msg'=>'No se pudo iniciar','data'=>[]];
  }

  return ['status'=>true,'msg'=>'Proceso iniciado','data'=>[
    'idorden'=>$idorden,
    'fecha_inicio'=>$fecha_inicio,
    'estatus'=>2
  ]];
}





public function finishOT(int $idorden, string $fecha_fin)
{
  $idorden = (int)$idorden;

  $sql = "SELECT idorden, estatus
          FROM mrp_ordenes_trabajo
          WHERE idorden = {$idorden}
          LIMIT 1";
  $cur = $this->select($sql);

  if (empty($cur)) {
    return ['status'=>false,'msg'=>'No existe la Sub-OT','data'=>[]];
  }

  $estatus = (int)($cur['estatus'] ?? 0);
  if ($estatus !== 2) {
    return ['status'=>false,'msg'=>'No puedes finalizar: la Sub-OT no está en proceso','data'=>[
      'estatus_actual'=>$estatus
    ]];
  }

  $sqlUpd = "UPDATE mrp_ordenes_trabajo
             SET fecha_fin = ?, estatus = 3
             WHERE idorden = {$idorden} AND estatus = 2";

  $arrData = [$fecha_fin];

  $ok = $this->update($sqlUpd, $arrData);

  if (!$ok) {
    return ['status'=>false,'msg'=>'No se pudo finalizar','data'=>[]];
  }

  return ['status'=>true,'msg'=>'Proceso finalizado','data'=>[
    'idorden'=>$idorden,
    'fecha_fin'=>$fecha_fin,
    'estatus'=>3
  ]];
}






public function getStatusOTByPeid(int $peid)
{
  $peid = (int)$peid;

  $sql = "SELECT
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE ot.planeacion_estacionid = {$peid}";

  return $this->select_all($sql); // usa tu método para traer varios
}

public function getStatusOTByPlaneacion(int $planeacionid)
{
  $planeacionid = (int)$planeacionid;

  $sql = "SELECT
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE pe.planeacionid = {$planeacionid}";

  return $this->select_all($sql);
}



public function selectOrdenesCalendar()
{
  $isAdmin   = isset($_SESSION['rolid']) && (int)$_SESSION['rolid'] === 1;
  $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

  if (!$isAdmin && $userIdSes <= 0) {

    return [];
  }

  $whereUser = "";
  if (!$isAdmin) {
  
    $whereUser = " AND pla.idplaneacion IN (
                    SELECT DISTINCT pe.planeacionid
                    FROM mrp_planeacion_estacion pe
                    INNER JOIN mrp_planeacion_estacion_operador o
                      ON o.planeacion_estacionid = pe.id_planeacion_estacion
                    WHERE pe.estado = 2
                      AND o.estado  = 2
                      AND o.usuarioid = {$userIdSes}
                  )";
  }

  $sql = "SELECT 
            pla.idplaneacion,
            pla.num_orden,
            pla.productoid,
            pla.num_pedido,
            pla.supervisor,
            pla.prioridad,
            pla.cantidad,
            pla.fecha_requerida,
            pla.fecha_inicio,
            pla.notas,
            pla.estado,
            pla.fase
          FROM mrp_planeacion pla
          WHERE pla.fecha_inicio IS NOT NULL
            AND pla.estado != 0
            {$whereUser}
          ORDER BY pla.fecha_inicio DESC";

  return $this->select_all($sql);
}









////////////////////////////////////


public function selectChatMessages($numorden, $subot, $productoid, $estacionid, $planeacionid, $after_id = 0, $limit = 200)
{
  $numorden = addslashes(trim((string)$numorden));
  $subot    = addslashes(trim((string)$subot));

  $productoid   = (int)$productoid;
  $estacionid   = (int)$estacionid;
  $planeacionid = (int)$planeacionid;
  $after_id     = (int)$after_id;

  $limit = (int)$limit;
  if ($limit <= 0) $limit = 200;
  if ($limit > 500) $limit = 500;

  // ✅ REGLA: sin subot no hay chat
  if ($subot === '') return [];

  // ✅ SIEMPRE por subot (chat aislado)
  $where = "WHERE c.subot = '{$subot}'";

  // numorden opcional (por si lo quieres reforzar)
  if ($numorden !== '') $where .= " AND c.numorden = '{$numorden}'";

  if ($productoid > 0)   $where .= " AND c.productoid = {$productoid}";
  if ($estacionid > 0)   $where .= " AND c.estacionid = {$estacionid}";
  if ($planeacionid > 0) $where .= " AND c.planeacionid = {$planeacionid}";
  if ($after_id > 0)     $where .= " AND c.idchat > {$after_id}";

  $sql = "SELECT
            c.idchat,
            c.numorden,
            c.subot,
            c.productoid,
            c.estacionid,
            c.planeacionid,
            c.user_id,
            c.user_name,
            c.message,
            DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') AS created_at
          FROM mrp_ot_chat c
          {$where}
          ORDER BY c.idchat ASC
          LIMIT {$limit}";

  $rows = $this->select_all($sql);
  return is_array($rows) ? $rows : [];
}


public function insertChatMessagse($numorden, $subot, $productoid, $estacionid, $planeacionid, $userId, $userName, $message)
{
//   $numorden   = trim((string)$numorden);
//   $subot      = trim((string)$subot);
//   $productoid = (int)$productoid;
//   $estacionid = (int)$estacionid;
//   $planeacionid = (int)$planeacionid;
//   $userId     = (int)$userId;

//   $userName = trim((string)$userName);
//   $message  = trim((string)$message);

//   if ($subot === '' || $message === '') return false;

//   $sql = "INSERT INTO mrp_ot_chat
//             (numorden, subot, productoid, estacionid, planeacionid, user_id, user_name, message, created_at)
//           VALUES
//             ('{$numorden}', '{$subot}', {$productoid}, {$estacionid}, {$planeacionid}, {$userId}, '{$userName}', '{$message}', NOW())";

//   $request = $this->insert($sql);
//   return ($request > 0);



          $sql = "INSERT INTO mrp_ot_chat(numorden, subot, productoid, estacionid, planeacionid, user_id, user_name, message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $arrData = [$numorden, $subot, $productoid, $estacionid, $planeacionid, $userId, $userName, $message];

        return $this->insert($sql, $arrData);

}





public function getChatMessssages($subot, $last_id = 0)
{
//   $subot   = $this->db->real_escape_string($subot);
//   $last_id = (int)$last_id;

  $sql = "SELECT
            idchat,
            subot,
            user_name,
            message,
            created_at
          FROM mrp_ot_chat
          WHERE subot = '$subot'";

  if ($last_id > 0) {
    $sql .= " AND idchat > $last_id";
  }

  $sql .= " ORDER BY idchat ASC";

  return $this->select_all($sql);
}

public function insertChatMessasge($subot, $user_id, $user_name, $message)
{


//   $sql = "INSERT INTO mrp_ot_chat
//           (subot, user_id, user_name, message, created_at)
//           VALUES
//           ('$subot', $user_id, '$user_name', '$message', NOW())";

//   return $this->insert($sql);

   


        $sql = "INSERT INTO mrp_ot_chat
          (subot, user_id, user_name, message, created_at)
            VALUES (?, ?, ?, ?, NOW())";

        $arrData = [$subot, $user_id, $user_name, $message];


        // return $this->insert($sql, $arrData);
            $request_insert = $this->insert($sql, $arrData);
            return $request_insert;

}

//CREAME UN INNER $joinNombres

public function getChatMessages(string $subot, int $lastId = 0)
{
  $sql = "SELECT c.idchat, c.user_name, c.message, c.created_at,u.avatar_file as user_avatar
          FROM mrp_ot_chat AS c
          INNER JOIN usuarios AS u
          ON u.idusuario = c.user_id
          WHERE c.subot = '$subot'";

  if ($lastId > 0) {
    $sql .= " AND idchat > $lastId";
  }

  $sql .= " ORDER BY idchat ASC LIMIT 200";
  return $this->select_all($sql);
}

public function insertChatMessage(array $d)
{
  $sql = "INSERT INTO mrp_ot_chat
          (subot, estacionid, planeacionid, user_id, user_name, message, created_at)
          VALUES (?,?,?,?,?,?,NOW())";

  return $this->insert($sql, [
    $d['subot'],
    $d['estacionid'],
    $d['planeacionid'],
    $_SESSION['idUser'],
    $_SESSION['userData']['nombres'],
    $d['message']
  ]);
}













}
?>