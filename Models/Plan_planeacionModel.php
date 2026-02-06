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

    



    public function selectOptionSupervisores()
{
    $sql = "SELECT * 
            FROM usuarios 
            WHERE rolid IN (2,4) 
              AND status = 1";

    return $this->select_all($sql);
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
        $sql = "INSERT INTO mrp_planeacion (num_orden, productoid, num_pedido, supervisorid, prioridad, cantidad, fecha_inicio, fecha_requerida, notas, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 2)";

        $arrData = [$num_orden, $productoid, $pedido, $supervisor, $prioridad, $cantidad, $fecha_inicio, $fecha_requerida, $notas];

        return $this->insert($sql, $arrData);
    }

    public function upsertPlaneacionEstacion($planeacionid, $estacionid, $orden)
    {

        $sqlFind = "SELECT pl.id_planeacion_estacion, es.nombre_estacion, es.proceso
              FROM mrp_planeacion_estacion AS pl
              INNER JOIN  mrp_estacion AS es 
              ON pl.estacionid = es.idestacion
              WHERE pl.planeacionid = $planeacionid AND pl.estacionid = $estacionid AND pl.estado = 2
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

    public function getEstacionInfoById($estacionid)
{
    $sql = "SELECT es.idestacion, es.nombre_estacion, es.proceso, lin.nombre_linea AS linea
            FROM mrp_estacion AS es 
            INNER JOIN  mrp_linea AS lin
            ON es.lineaid = lin.idlinea
            WHERE es.idestacion = $estacionid
            LIMIT 1";
    return $this->select($sql);
}

public function getNombresUsuariosByIds(array $ids)
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (empty($ids)) return [];

    $in = implode(',', $ids); 
    $sql = "SELECT idusuario, nombres, apellidos
            FROM usuarios
            WHERE idusuario IN ($in)";
    return $this->select_all($sql);
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
    $rolId      = isset($_SESSION['rolid']) ? (int)$_SESSION['rolid'] : 0;
    $userIdSes  = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    // Admin y rol 5 ven todo
    $isAdmin = in_array($rolId, [1, 5]);

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



    public function consultarExistenciasss(int $productoid, int $estacionid, int $cantidadPlaneada)
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

            $sqlExist ="";
    

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
            'msg'    => 'Sin componentes configurados para validar',
            'data'   => []
        ];
    }

    foreach ($componentes as $c) {
        $inventarioid   = (int)   ($c['inventarioid'] ?? 0);
        $almacenid      = (int)   ($c['almacenid'] ?? 0);
        $cantPorUnidad  = (float) ($c['cantidad_por_unidad'] ?? 0);


        $requerido = (float)$cantidadPlaneada * (float)$cantPorUnidad;


        $sqlExist = "SELECT 
                        COALESCE(m.existencia, 0) AS existencia,
                        inv.descripcion,
                        al.descripcion AS descripcion_almacen
                     FROM wms_inventario inv
                     INNER JOIN wms_almacenes al 
                        ON al.idalmacen = $almacenid
                     LEFT JOIN wms_multialmacen m
                        ON m.inventarioid = inv.idinventario
                       AND m.almacenid   = al.idalmacen
                     WHERE inv.idinventario = $inventarioid
                     LIMIT 1";

        $rowExist = $this->select($sqlExist);

        $existencia          = isset($rowExist['existencia']) ? (float)$rowExist['existencia'] : 0;
        $descripcion         = isset($rowExist['descripcion']) ? (string)$rowExist['descripcion'] : '';
        $descripcion_almacen = isset($rowExist['descripcion_almacen']) ? (string)$rowExist['descripcion_almacen'] : '';

      
        if ($descripcion === '') {
            $rowInv = $this->select("SELECT descripcion FROM wms_inventario WHERE idinventario = $inventarioid LIMIT 1");
            $descripcion = isset($rowInv['descripcion']) ? (string)$rowInv['descripcion'] : '';
        }

        if ($descripcion_almacen === '') {
            $rowAlm = $this->select("SELECT descripcion FROM wms_almacenes WHERE idalmacen = $almacenid LIMIT 1");
            $descripcion_almacen = isset($rowAlm['descripcion']) ? (string)$rowAlm['descripcion'] : '';
        }


        if ($requerido > $existencia) {
            $faltante = $requerido - $existencia;

            $faltantes[] = [
                'productoid'           => $productoid,
                'estacionid'           => $estacionid,
                'almacenid'            => $almacenid,
                'inventarioid'         => $inventarioid,
                'descripcion'          => $descripcion,
                'descripcion_almacen'  => $descripcion_almacen,

                'cantidad_planeada'    => (float)$cantidadPlaneada,
                'cantidad_por_unidad'  => (float)$cantPorUnidad,
                'requerido'            => (float)$requerido,
                'existencia'           => (float)$existencia,
                'faltante'             => (float)$faltante
            ];
        }
    }

    if (!empty($faltantes)) {
        return [
            'status' => 0,
            'msg'    => 'Faltan componentes en inventario',
            'data'   => $faltantes
        ];
    }

    return [
        'status' => true,
        'msg'    => 'Existencias OK',
        'data'   => []
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


    public function getSupervisorEmailById(int $idusuario)
{
    $sql = "SELECT 
                idusuario,
                email_user,
                nombres,
                apellidos
            FROM usuarios
            WHERE idusuario = $idusuario
              AND status = 1
            LIMIT 1";

    return $this->select($sql);
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

   
        $idsPE = array_map(fn($r) => (int) $r['id_planeacion_estacion'], $estaciones);
        $idsPE = array_values(array_filter($idsPE, fn($v) => $v > 0));

        $in = implode(',', $idsPE);

     
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






    public function obtenerPlaneacion($num_orden)
{

    $num_orden = trim((string) $num_orden);
    $key = preg_replace('/[^A-Za-z0-9]/', '', $num_orden);


    // $sqlPla = "SELECT pla.*, pr.cve_producto, pr.descripcion
    //           FROM mrp_planeacion AS pla
    //           INNER JOIN mrp_productos AS pr ON pla.productoid = pr.idproducto
    //           WHERE REPLACE(pla.num_orden,'-','') = '{$key}'
    //           LIMIT 1";

              $sqlPla = "SELECT 
              pla.*,
              pr.cve_producto,
              pr.descripcion,
              CONCAT(us.nombres, ' ', us.apellidos) AS supervisor
          FROM mrp_planeacion AS pla
          INNER JOIN mrp_productos AS pr 
              ON pla.productoid = pr.idproducto
          INNER JOIN usuarios AS us
              ON pla.supervisorid = us.idusuario
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


    $isAdmin = isset($_SESSION['rolid']) && in_array((int)$_SESSION['rolid'], [1, 5]);

    $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

    
    if (!$isAdmin && $userIdSes <= 0) {
        return ['status' => false, 'msg' => 'Sesión inválida (sin usuario)', 'data' => []];
    }

    $whereUserEst = "";
    if (!$isAdmin) {

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


    if (empty($estaciones)) {
        $planeacion['estaciones'] = [];
        return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }


    $idsPE = array_map(fn($r) => (int)$r['id_planeacion_estacion'], $estaciones);
    $idsPE = array_values(array_filter($idsPE, fn($v) => $v > 0));

    if (empty($idsPE)) {
        $planeacion['estaciones'] = [];
        return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }

    $in = implode(',', $idsPE);

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
        $peid = (int)($op['planeacion_estacionid'] ?? 0);
        if ($peid <= 0) continue;
        $opsByPE[$peid][] = $op;
    }


    $sqlOT = "SELECT ot.idorden,
                     ot.planeacion_estacionid,
                     ot.num_sub_orden,
                     ot.fecha_inicio,
                     ot.fecha_fin,
                     ot.comentarios,
                     ot.estatus,
                     ot.calidad,
                     CAST(SUBSTRING_INDEX(ot.num_sub_orden, 'S', -1) AS UNSIGNED) AS ord_s
              FROM mrp_ordenes_trabajo ot
              WHERE ot.planeacion_estacionid IN ({$in})
              ORDER BY ot.planeacion_estacionid ASC, ord_s ASC";

    $ots = $this->select_all($sqlOT);


    $otsByPE = [];
    foreach ($ots as $ot) {
        $peid = (int)($ot['planeacion_estacionid'] ?? 0);
        if ($peid <= 0) continue;
        $otsByPE[$peid][] = $ot;
    }


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
                'calidad'               => (string)($ot['calidad'] ?? ''),
            ];
        }

        $outEstaciones[] = $item;
    }

    $planeacion['estaciones'] = $outEstaciones;

    return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
}

//Y ENTONCES PARA ESTA CONSULTA COMO QUEDAR+IA SI TABIEN QUIERO AGREGAR EL IDROL=5


 

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

  $fecha_inicio = trim((string)$fecha_inicio);
  if ($fecha_inicio === '') {
    $fecha_inicio = date('Y-m-d H:i:s');
  }

  // -------------------------------------------------------
  // 1) Traer Sub-OT actual
  // -------------------------------------------------------
  $sql = "SELECT 
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.calidad,                     -- ✅ (se usa para reglas)
            pe.id_planeacion_estacion,
            pe.planeacionid,               
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

  $peid   = (int)($cur['planeacion_estacionid'] ?? 0);
  $idpla  = (int)($cur['planeacionid'] ?? 0);
  $estOrd = (int)($cur['estacion_orden'] ?? 0);
  $subot  = trim((string)($cur['num_sub_orden'] ?? ''));

  if ($peid <= 0 || $idpla <= 0 || $estOrd <= 0 || $subot === '') {
    return ['status'=>false,'msg'=>'Datos incompletos para iniciar (peid/planeacionid/orden/subot)','data'=>[
      'peid'=>$peid,'planeacionid'=>$idpla,'orden'=>$estOrd,'subot'=>$subot
    ]];
  }

  // -------------------------------------------------------
  // 2) Parsear Sxx
  // -------------------------------------------------------
  $snum = 0;
  if (preg_match('/-S(\d+)\s*$/i', $subot, $m)) {
    $snum = (int)$m[1];
  }
  if ($snum <= 0) {
    return ['status'=>false,'msg'=>'Sub-OT inválida (no se detectó Sxx)','data'=>[]];
  }

  $base = preg_replace('/-S\d+\s*$/i', '', $subot);
  $subotSql = addslashes($subot);

  // -------------------------------------------------------
  // 3) REGLA: no permitir si hay otra "en proceso" activa
  //    PERO: si la que está en proceso tiene calidad=4 (pausada),
  //    entonces NO bloquea.
  // -------------------------------------------------------
  $sqlBusy = "SELECT COUNT(*) AS c
              FROM mrp_ordenes_trabajo
              WHERE planeacion_estacionid = {$peid}
                AND estatus = 2
                AND (calidad IS NULL OR calidad <> 4)";  // ✅ clave

  $busy = $this->select($sqlBusy);

  if ((int)($busy['c'] ?? 0) > 0) {
    return [
      'status' => false,
      'msg'    => 'No puedes iniciar: existe una Sub-OT en proceso activa (no pausada por calidad) en esta estación',
      'data'   => []
    ];
  }

  // -------------------------------------------------------
  // 4) REGLA: Sub anterior dentro de la MISMA estación
  //    - OK si está finalizada (3)
  //    - OK si está en proceso (2) pero calidad=4 (pausada)
  // -------------------------------------------------------
  if ($snum > 1) {
    $prevSub = $base . '-S' . str_pad((string)($snum - 1), 2, '0', STR_PAD_LEFT);
    $prevSubSql = addslashes($prevSub);

    $sqlPrev = "SELECT estatus, calidad
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

$prevEstatus = (int)($prev['estatus'] ?? 0);
$prevCalidad = (int)($prev['calidad'] ?? 0);

// ✅ NUEVO: también permitir si está pendiente (1) pero calidad en 3 o 4
$prevOk =
  ($prevEstatus === 3) ||
  ($prevEstatus === 2 && $prevCalidad === 4) ||
  ($prevEstatus === 1 && in_array($prevCalidad, [3, 4], true));

if (!$prevOk) {
  return [
    'status'=>false,
    'msg'=>"Primero finaliza {$prevSub} en esta estación (o debe estar pausada por calidad)",
    'data'=>[
      'prevSub'=>$prevSub,
      'prevEstatus'=>$prevEstatus,
      'prevCalidad'=>$prevCalidad
    ]
  ];
}

  }

  // -------------------------------------------------------
  // 5) REGLA: Estación anterior (si aplica) debe estar finalizada (3)
  //     (Aquí NO me pediste excepción por calidad, así lo dejo igual)
  // -------------------------------------------------------
  if ($estOrd > 1) {
    $prevOrden = $estOrd - 1;

    $sqlPrevStation = "SELECT ot.estatus
                       FROM mrp_planeacion_estacion pe2
                       INNER JOIN mrp_ordenes_trabajo ot
                         ON ot.planeacion_estacionid = pe2.id_planeacion_estacion
                       WHERE pe2.planeacionid = {$idpla}
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

  // -------------------------------------------------------
  // 6) Actualizar a "en proceso"
  // -------------------------------------------------------
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
            ot.calidad,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE ot.planeacion_estacionid = {$peid}
          ORDER BY pe.orden ASC, ot.num_sub_orden ASC";

  return $this->select_all($sql); 
}

public function getStatusOTByPlaneacion(int $planeacionid)
{
  $planeacionid = (int)$planeacionid;

  $sql = "SELECT
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.calidad,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE pe.planeacionid = {$planeacionid}
          ORDER BY ot.num_sub_orden ASC";

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
            pla.supervisorid,
            CONCAT(us.nombres, ' ', us.apellidos) AS supervisor,
            pla.prioridad,
            pla.cantidad,
            pla.fecha_requerida,
            pla.fecha_inicio,
            pla.notas,
            pla.estado,
            pla.fase
        FROM mrp_planeacion pla
        INNER JOIN usuarios AS us
            ON pla.supervisorid = us.idusuario
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


  if ($subot === '') return [];


  $where = "WHERE c.subot = '{$subot}'";


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


public function getComponentesByProducto(int $productoid){
  $sql = "SELECT idcomponente, almacenid, productoid, estacionid, inventarioid, cantidad
          FROM mrp_estacion_componentes
          WHERE productoid = $productoid AND estado = 2
          ORDER BY estacionid ASC, idcomponente ASC";


    // $sql = "SELECT * FROM usuarios 
		// 			WHERE status != 0 AND rolid=2 ";
        $request = $this->select_all($sql);
        return $request;

}

public function insertMovimientoInventario(array $m)
{
  $sql = "INSERT INTO wms_movimientos_inventario
    (inventarioid, almacenid, numero_movimiento, concepmovid, referencia, cantidad,
     costo_cantidad, precio, costo, existencia, signo, fecha_movimiento, estado)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

  $params = [
    $m['inventarioid'],
    $m['almacenid'],
    $m['numero_movimiento'],
    $m['concepmovid'],
    $m['referencia'],
    $m['cantidad'],
    $m['costo_cantidad'],
    $m['precio'],
    $m['costo'],
    $m['existencia'],
    $m['signo'],
    $m['fecha_movimiento'],
    $m['estado'],
  ];

  return $this->insert($sql, $params);
}


public function updateExistenciaInventario($inventarioid, $almacenid, $cantidad)
{

    $row = $this->select("SELECT existencia FROM wms_multialmacen WHERE inventarioid = $inventarioid AND almacenid = $almacenid");

    if (!$row) return false;

    $nuevaExistencia = $row['existencia'] - $cantidad;
    if ($nuevaExistencia < 0) $nuevaExistencia = 0;

    $sql = "UPDATE wms_multialmacen 
            SET existencia = ? 
            WHERE inventarioid = $inventarioid AND almacenid = $almacenid";

    return $this->update($sql, [$nuevaExistencia]);

}




public function selectDescriptivaByProducto(int $productoid): array
{
    $sql = "SELECT * FROM mrp_productos_descriptiva
            WHERE productoid = $productoid
              AND estado = 2";

    return $this->select_all($sql); 
}


public function selectDocumentacionByProducto(int $productoid): array
{
    $sql = "SELECT 
                iddocumento,
                productoid,
                tipo_documento,
                descripcion,
                ruta,
                fecha_creacion
            FROM mrp_productos_documentos
            WHERE productoid = $productoid
              AND estado = 2
            ORDER BY fecha_creacion DESC";

    return $this->select_all($sql); 
}


public function selectEspecificacionesByProductoEstacion(int $productoid, int $estacionid): array
{
    $sql = "SELECT
              idespecificacion,
              productoid,
              estacionid,
              especificacion,
              fecha_creacion
            FROM mrp_estacion_especificaciones
            WHERE productoid = $productoid
              AND estacionid = $estacionid
              AND estado = 2
            ORDER BY fecha_creacion DESC";

    return $this->select_all($sql);
}

public function selectComponentesByProductoEstacion(int $productoid, int $estacionid): array
{
    $sql = "SELECT
              c.idcomponente,
              c.almacenid,
              c.productoid,
              c.estacionid,
              c.inventarioid,
              c.cantidad,
              c.estado,
              c.fecha_creacion,
              inv.descripcion as componente
            FROM mrp_estacion_componentes AS c
            INNER JOIN wms_inventario AS inv
            ON c.inventarioid = inv.idinventario
            WHERE c.productoid = $productoid
              AND c.estacionid = $estacionid
              AND c.estado = 2
            ORDER BY c.fecha_creacion DESC";

    return $this->select_all($sql);
}



public function selectHerramientasByProductoEstacion(int $productoid, int $estacionid): array
{
    $sql = "SELECT
              h.idherramienta,
              h.almacenid,
              h.productoid,
              h.estacionid,
              h.inventarioid,
              h.cantidad,
              h.estado,
              h.fecha_creacion,
              inv.descripcion as herramienta
            FROM mrp_estacion_herramientas AS h
            INNER JOIN wms_inventario AS inv
            ON h.inventarioid = inv.idinventario
            WHERE h.productoid = $productoid
              AND h.estacionid = $estacionid
              AND h.estado = 2
            ORDER BY h.fecha_creacion DESC";

    return $this->select_all($sql);
}

public function saveInspeccionCalidadv1($h, $detalle, $evidencias)
{
  $idorden    = (int)$h['idorden'];
  $numot      = (string)$h['numot'];
  $productoid = (int)$h['productoid'];
  $estacionid = (int)$h['estacionid'];
  $usuarioid  = (int)$h['usuarioid'];
  $estado     = (int)$h['estado']; // 1 pausada, 2 liberada


  $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
  $row = $this->select($sqlFind);
  $idinspeccion = isset($row['idinspeccion']) ? (int)$row['idinspeccion'] : 0;

  if ($idinspeccion <= 0) {
    $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
    $idinspeccion = (int)$this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

    if ($idinspeccion <= 0) {
      return ['status' => false, 'msg' => 'No se pudo crear la inspección.'];
    }
  } else {
    $sqlUp = "UPDATE mrp_calidad_inspeccion
              SET estado = ?, usuarioid = ?
              WHERE idinspeccion = $idinspeccion";

                  $arrData = array($estado,$usuarioid);
    $this->update($sqlUp,$arrData );

    if ($estado === 2) {
                 
 $fecha_hora = date('Y-m-d H:i:s');

$sqlFecha = "UPDATE mrp_calidad_inspeccion
             SET fecha_cierre = ?
             WHERE idinspeccion = $idinspeccion";

$arrFecha = array($fecha_hora );
$this->update($sqlFecha, $arrFecha);
    }



  }


  foreach ($detalle as $d) {
    $especificacionid = (int)($d['especificacionid'] ?? 0);
    $resultado        = (string)($d['resultado'] ?? '');
    $comentarioUI     = trim((string)($d['comentario'] ?? ''));

    if ($especificacionid <= 0) continue;
    if ($resultado !== 'OK' && $resultado !== 'NO_OK') continue;


    $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
    $rd = $this->select($sqlDetFind);
    $iddetalle = isset($rd['iddetalle']) ? (int)$rd['iddetalle'] : 0;
    $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string)$rd['comentario_no_ok']) : '';

    if ($iddetalle <= 0) {
     
      $comentario_no_ok   = ($resultado === 'NO_OK') ? $comentarioUI : null;
      $accion_correctiva  = ($resultado === 'OK') ? $comentarioUI : null; // opcional

      $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
      $iddetalle = (int)$this->insert($sqlDetIns, [
        $idinspeccion,
        $especificacionid,
        $resultado,
        $comentario_no_ok,
        $accion_correctiva
      ]);

    } else {
 
      if ($resultado === 'NO_OK') {


        $nuevoMotivo = $comentarioUI;

        if ($comentarioNoOkPrev !== '') {

          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        } else {
 
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, comentario_no_ok = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $nuevoMotivo, $iddetalle]);
        }

      } else {

        if ($comentarioUI !== '') {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, accion_correctiva = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $comentarioUI, $iddetalle]);
        } else {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        }
      }
    }

    if ($iddetalle <= 0) continue;

  
    if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
      foreach ($evidencias[$especificacionid] as $ev) {
        $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
        $this->insert($sqlEv, [
          $iddetalle,
          (string)($ev['nombre_original'] ?? ''),
          (string)($ev['archivo'] ?? ''),
          (string)($ev['mime'] ?? ''),
          (int)($ev['size_bytes'] ?? 0)
        ]);
      }
    }
  }

  return [
    'status' => true,
    'msg' => ($estado === 2) ? 'Inspección guardada y estación liberada.' : 'Inspección guardada (pausada).',
    'data' => [
      'idinspeccion' => $idinspeccion,
      'estado' => $estado
    ]
  ];
}




public function saveInspeccionCalidadv2($h, $detalle, $evidencias)
{
  $idorden    = (int)$h['idorden'];
  $numot      = (string)$h['numot'];
  $productoid = (int)$h['productoid'];
  $estacionid = (int)$h['estacionid'];
  $usuarioid  = (int)$h['usuarioid'];
  $estado     = (int)$h['estado']; // 1 pausada, 2 liberada


  $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
  $row = $this->select($sqlFind);
  $idinspeccion = isset($row['idinspeccion']) ? (int)$row['idinspeccion'] : 0;

  if ($idinspeccion <= 0) {

    $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
    $idinspeccion = (int)$this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

    if ($idinspeccion <= 0) {
      return ['status' => false, 'msg' => 'No se pudo crear la inspección.'];
    }

   
    if ($estado === 1 || $estado === 2) {
      $calidadOT = ($estado === 1) ? 4 : 5;

      $sqlOtUp = "UPDATE mrp_ordenes_trabajo
                  SET calidad = ?
                  WHERE idorden = ?";
      $this->update($sqlOtUp, [$calidadOT, $idorden]);
    }

  } else {

    $sqlUp = "UPDATE mrp_calidad_inspeccion
              SET estado = ?, usuarioid = ?
              WHERE idinspeccion = $idinspeccion";

    $arrData = array($estado, $usuarioid);
    $this->update($sqlUp, $arrData);


    if ($estado === 1 || $estado === 2) {
      $calidadOT = ($estado === 1) ? 4 : 5;

      $sqlOtUp = "UPDATE mrp_ordenes_trabajo
                  SET calidad = ?
                  WHERE idorden = ?";
      $this->update($sqlOtUp, [$calidadOT, $idorden]);
    }

    if ($estado === 2) {

      $fecha_hora = date('Y-m-d H:i:s');

      $sqlFecha = "UPDATE mrp_calidad_inspeccion
                   SET fecha_cierre = ?
                   WHERE idinspeccion = $idinspeccion";

      $arrFecha = array($fecha_hora);
      $this->update($sqlFecha, $arrFecha);
    }
  }


  foreach ($detalle as $d) {
    $especificacionid = (int)($d['especificacionid'] ?? 0);
    $resultado        = (string)($d['resultado'] ?? '');
    $comentarioUI     = trim((string)($d['comentario'] ?? ''));

    if ($especificacionid <= 0) continue;
    if ($resultado !== 'OK' && $resultado !== 'NO_OK') continue;


    $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
    $rd = $this->select($sqlDetFind);
    $iddetalle = isset($rd['iddetalle']) ? (int)$rd['iddetalle'] : 0;
    $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string)$rd['comentario_no_ok']) : '';

    if ($iddetalle <= 0) {
   
      $comentario_no_ok   = ($resultado === 'NO_OK') ? $comentarioUI : null;
      $accion_correctiva  = ($resultado === 'OK') ? $comentarioUI : null; // opcional

      $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
      $iddetalle = (int)$this->insert($sqlDetIns, [
        $idinspeccion,
        $especificacionid,
        $resultado,
        $comentario_no_ok,
        $accion_correctiva
      ]);

    } else {
     
      if ($resultado === 'NO_OK') {

       
        $nuevoMotivo = $comentarioUI;

        if ($comentarioNoOkPrev !== '') {
        
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        } else {
        
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, comentario_no_ok = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $nuevoMotivo, $iddetalle]);
        }

      } else {

        if ($comentarioUI !== '') {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, accion_correctiva = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $comentarioUI, $iddetalle]);
        } else {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        }
      }
    }

    if ($iddetalle <= 0) continue;

  
    if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
      foreach ($evidencias[$especificacionid] as $ev) {
        $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
        $this->insert($sqlEv, [
          $iddetalle,
          (string)($ev['nombre_original'] ?? ''),
          (string)($ev['archivo'] ?? ''),
          (string)($ev['mime'] ?? ''),
          (int)($ev['size_bytes'] ?? 0)
        ]);
      }
    }
  }

  return [
    'status' => true,
    'msg' => ($estado === 2) ? 'Inspección guardada y estación liberada.' : 'Inspección guardada (pausada).',
    'data' => [
      'idinspeccion' => $idinspeccion,
      'estado' => $estado
    ]
  ];
}

public function saveInspeccionCalidad($h, $detalle, $evidencias)
{
  $idorden    = (int)$h['idorden'];
  $numot      = (string)$h['numot'];
  $productoid = (int)$h['productoid'];
  $estacionid = (int)$h['estacionid'];
  $usuarioid  = (int)$h['usuarioid'];
  $estado     = (int)$h['estado'];


  $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
  $row = $this->select($sqlFind);
  $idinspeccion = isset($row['idinspeccion']) ? (int)$row['idinspeccion'] : 0;

  if ($idinspeccion <= 0) {

    $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
    $idinspeccion = (int)$this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

    if ($idinspeccion <= 0) {
      return ['status' => false, 'msg' => 'No se pudo crear la inspección.'];
    }


    if ($estado === 1 || $estado === 2) {
      $calidadOT = ($estado === 1) ? 4 : 5;

      $sqlOtUp = "UPDATE mrp_ordenes_trabajo
                  SET calidad = ?
                  WHERE idorden = ?";
      $this->update($sqlOtUp, [$calidadOT, $idorden]);
    }

 
    if ($estado === 1) {
      $sqlOtSecuencia = "UPDATE mrp_ordenes_trabajo
                         SET calidad = 3
                         WHERE num_sub_orden = ?
                           AND idorden > ?";
      $this->update($sqlOtSecuencia, [$numot, $idorden]);
    }

    
    if ($estado === 2) {
      $sqlOtPend = "UPDATE mrp_ordenes_trabajo
                    SET calidad = 1
                    WHERE num_sub_orden = ?
                      AND idorden > ?";
      $this->update($sqlOtPend, [$numot, $idorden]);
    }

  } else {

    $sqlUp = "UPDATE mrp_calidad_inspeccion
              SET estado = ?, usuarioid = ?
              WHERE idinspeccion = $idinspeccion";

    $arrData = array($estado, $usuarioid);
    $this->update($sqlUp, $arrData);

 
    if ($estado === 1 || $estado === 2) {
      $calidadOT = ($estado === 1) ? 4 : 5;

      $sqlOtUp = "UPDATE mrp_ordenes_trabajo
                  SET calidad = ?
                  WHERE idorden = ?";
      $this->update($sqlOtUp, [$calidadOT, $idorden]);
    }

    
    if ($estado === 1) {
      $sqlOtSecuencia = "UPDATE mrp_ordenes_trabajo
                         SET calidad = 3
                         WHERE num_sub_orden = ?
                           AND idorden > ?";
      $this->update($sqlOtSecuencia, [$numot, $idorden]);
    }

    if ($estado === 2) {

      $fecha_hora = date('Y-m-d H:i:s');

      $sqlFecha = "UPDATE mrp_calidad_inspeccion
                   SET fecha_cierre = ?
                   WHERE idinspeccion = $idinspeccion";

      $arrFecha = array($fecha_hora);
      $this->update($sqlFecha, $arrFecha);

   
      $sqlOtPend = "UPDATE mrp_ordenes_trabajo
                    SET calidad = 1
                    WHERE num_sub_orden = ?
                      AND idorden > ?";
      $this->update($sqlOtPend, [$numot, $idorden]);
    }
  }


  foreach ($detalle as $d) {
    $especificacionid = (int)($d['especificacionid'] ?? 0);
    $resultado        = (string)($d['resultado'] ?? '');
    $comentarioUI     = trim((string)($d['comentario'] ?? ''));

    if ($especificacionid <= 0) continue;
    if ($resultado !== 'OK' && $resultado !== 'NO_OK') continue;


    $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
    $rd = $this->select($sqlDetFind);
    $iddetalle = isset($rd['iddetalle']) ? (int)$rd['iddetalle'] : 0;
    $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string)$rd['comentario_no_ok']) : '';

    if ($iddetalle <= 0) {
    
      $comentario_no_ok   = ($resultado === 'NO_OK') ? $comentarioUI : null;
      $accion_correctiva  = ($resultado === 'OK') ? $comentarioUI : null; // opcional

      $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
      $iddetalle = (int)$this->insert($sqlDetIns, [
        $idinspeccion,
        $especificacionid,
        $resultado,
        $comentario_no_ok,
        $accion_correctiva
      ]);

    } else {
 
      if ($resultado === 'NO_OK') {

        $nuevoMotivo = $comentarioUI;

        if ($comentarioNoOkPrev !== '') {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        } else {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, comentario_no_ok = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $nuevoMotivo, $iddetalle]);
        }

      } else {

        if ($comentarioUI !== '') {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?, accion_correctiva = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $comentarioUI, $iddetalle]);
        } else {
          $sqlDetUp = "UPDATE mrp_calidad_inspeccion_detalle
                       SET resultado = ?
                       WHERE iddetalle = ?";
          $this->update($sqlDetUp, [$resultado, $iddetalle]);
        }
      }
    }

    if ($iddetalle <= 0) continue;

    
    if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
      foreach ($evidencias[$especificacionid] as $ev) {
        $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
        $this->insert($sqlEv, [
          $iddetalle,
          (string)($ev['nombre_original'] ?? ''),
          (string)($ev['archivo'] ?? ''),
          (string)($ev['mime'] ?? ''),
          (int)($ev['size_bytes'] ?? 0)
        ]);
      }
    }
  }

  return [
    'status' => true,
    'msg' => ($estado === 2) ? 'Inspección guardada y estación liberada.' : 'Inspección guardada (pausada).',
    'data' => [
      'idinspeccion' => $idinspeccion,
      'estado' => $estado
    ]
  ];
}








public function getInspeccionCalidad($idorden, $estacionid)
{
  $idorden    = (int)$idorden;
  $estacionid = (int)$estacionid;


  $sqlIns = "SELECT idinspeccion, estado, fecha_cierre, usuarioid
             FROM mrp_calidad_inspeccion
             WHERE idorden = $idorden
               AND estacionid = $estacionid
             ORDER BY idinspeccion DESC
             LIMIT 1";
  $ins = $this->select($sqlIns);

  if (empty($ins) || empty($ins['idinspeccion'])) {
    return [
      'status' => true,
      'msg' => 'Sin inspección previa.',
      'data' => [
        'idinspeccion' => 0,
        'estado' => 0,
        'detalle' => []
      ]
    ];
  }

  $idinspeccion = (int)$ins['idinspeccion'];


  $sqlDet = "SELECT iddetalle, especificacionid, resultado,
                    comentario_no_ok, accion_correctiva
             FROM mrp_calidad_inspeccion_detalle
             WHERE idinspeccion = $idinspeccion";
  $det = $this->select_all($sqlDet);


  $sqlEv = "SELECT iddetalle, nombre_original, archivo, mime, size_bytes
            FROM mrp_calidad_inspeccion_evidencia
            WHERE iddetalle IN (
              SELECT iddetalle
              FROM mrp_calidad_inspeccion_detalle
              WHERE idinspeccion = $idinspeccion
            )";
  $evs = $this->select_all($sqlEv);


  $evByDet = [];
  if (is_array($evs)) {
    foreach ($evs as $e) {
      $idd = (int)($e['iddetalle'] ?? 0);
      if ($idd <= 0) continue;
      if (!isset($evByDet[$idd])) $evByDet[$idd] = [];
      $evByDet[$idd][] = [
        'nombre_original' => (string)($e['nombre_original'] ?? ''),
        'archivo' => (string)($e['archivo'] ?? ''),
        'mime' => (string)($e['mime'] ?? ''),
        'size_bytes' => (int)($e['size_bytes'] ?? 0),
      ];
    }
  }

  $outDet = [];
  if (is_array($det)) {
    foreach ($det as $d) {
      $iddetalle = (int)($d['iddetalle'] ?? 0);
      $resultado = (string)($d['resultado'] ?? '');
      $comentario = '';

      if ($resultado === 'NO_OK') {
        $comentario = trim((string)($d['comentario_no_ok'] ?? ''));
      } else if ($resultado === 'OK') {
        $comentario = trim((string)($d['accion_correctiva'] ?? ''));
      }

      $outDet[] = [
        'iddetalle' => $iddetalle,
        'especificacionid' => (int)($d['especificacionid'] ?? 0),
        'resultado' => $resultado,
        'comentario' => $comentario,
        'evidencias' => $evByDet[$iddetalle] ?? []
      ];
    }
  }

  return [
    'status' => true,
    'msg' => 'OK',
    'data' => [
      'idinspeccion' => $idinspeccion,
      'estado' => (int)($ins['estado'] ?? 0),
      'detalle' => $outDet
    ]
  ];
}

public function getViewInspeccionCalidad($idorden, $estacionid)
{
  $idorden    = (int)$idorden;
  $estacionid = (int)$estacionid;

  $sqlIns = "SELECT 
                ci.idinspeccion,
                ci.idorden,
                ci.numot,
                ci.productoid,
                ci.estacionid,
                ci.usuarioid,
                ci.estado,
                ci.fecha_creacion,
                ci.fecha_cierre,
                us.nombres,
                us.apellidos,
                us.email_user
             FROM mrp_calidad_inspeccion AS ci
             INNER JOIN usuarios AS us
               ON ci.usuarioid = us.idusuario
             WHERE ci.idorden = $idorden
               AND ci.estacionid = $estacionid
             ORDER BY ci.idinspeccion DESC
             LIMIT 1";
  $ins = $this->select($sqlIns);

  if (empty($ins) || empty($ins['idinspeccion'])) {
    return [
      'status' => true,
      'msg' => 'Sin inspección previa.',
      'data' => [
        'header' => [
          'idinspeccion'   => 0,
          'idorden'        => $idorden,
          'numot'          => '',
          'productoid'     => 0,
          'estacionid'     => $estacionid,
          'estado'         => 0,
          'fecha_creacion' => null,
          'fecha_cierre'   => null,
          'usuarioid'      => 0,
          'nombres'        => '',
          'apellidos'      => '',
          'email_user'     => ''
        ],
        'detalle' => []
      ]
    ];
  }

  $idinspeccion = (int)$ins['idinspeccion'];
  $productoid   = (int)($ins['productoid'] ?? 0);


  $sqlDet = "SELECT 
                d.iddetalle,
                d.especificacionid,
                e.especificacion,
                e.fecha_creacion AS fecha_especificacion,
                d.resultado,
                d.comentario_no_ok,
                d.accion_correctiva
             FROM mrp_calidad_inspeccion_detalle AS d
             INNER JOIN mrp_estacion_especificaciones AS e
               ON e.idespecificacion = d.especificacionid
              AND e.estacionid = $estacionid
              AND e.productoid = $productoid
             WHERE d.idinspeccion = $idinspeccion";
  $det = $this->select_all($sqlDet);

 
  $sqlEv = "SELECT 
              iddetalle, 
              nombre_original, 
              archivo, 
              mime, 
              size_bytes
            FROM mrp_calidad_inspeccion_evidencia
            WHERE iddetalle IN (
              SELECT iddetalle
              FROM mrp_calidad_inspeccion_detalle
              WHERE idinspeccion = $idinspeccion
            )";
  $evs = $this->select_all($sqlEv);


  $evByDet = [];
  if (is_array($evs)) {
    foreach ($evs as $e) {
      $idd = (int)($e['iddetalle'] ?? 0);
      if ($idd <= 0) continue;

      if (!isset($evByDet[$idd])) $evByDet[$idd] = [];
      $evByDet[$idd][] = [
        'nombre_original' => (string)($e['nombre_original'] ?? ''),
        'archivo'         => (string)($e['archivo'] ?? ''),
        'mime'            => (string)($e['mime'] ?? ''),
        'size_bytes'      => (int)($e['size_bytes'] ?? 0),
      ];
    }
  }


  $outDet = [];
  if (is_array($det)) {
    foreach ($det as $d) {
      $iddetalle = (int)($d['iddetalle'] ?? 0);
      $resultado = (string)($d['resultado'] ?? '');

      $comentarioNoOk = trim((string)($d['comentario_no_ok'] ?? ''));
      $accionCorr     = trim((string)($d['accion_correctiva'] ?? ''));

     
      $comentarioUI = '';
      if ($resultado === 'NO_OK') {
        $comentarioUI = $comentarioNoOk;
      } elseif ($resultado === 'OK') {
        $comentarioUI = $accionCorr;
      }

      $outDet[] = [
        'iddetalle'            => $iddetalle,
        'especificacionid'     => (int)($d['especificacionid'] ?? 0),

     
        'especificacion'       => (string)($d['especificacion'] ?? ''),
        'fecha_especificacion' => (string)($d['fecha_especificacion'] ?? ''),

        'resultado'            => $resultado,


        'comentario_no_ok'     => $comentarioNoOk,
        'accion_correctiva'    => $accionCorr,

  
        'comentario_ui'        => $comentarioUI,

        'evidencias'           => $evByDet[$iddetalle] ?? []
      ];
    }
  }


  $header = [
    'idinspeccion'   => $idinspeccion,
    'idorden'        => (int)($ins['idorden'] ?? 0),
    'numot'          => (string)($ins['numot'] ?? ''),
    'productoid'     => $productoid,
    'estacionid'     => (int)($ins['estacionid'] ?? 0),

    'estado'         => (int)($ins['estado'] ?? 0),
    'fecha_creacion' => (string)($ins['fecha_creacion'] ?? ''),
    'fecha_cierre'   => (string)($ins['fecha_cierre'] ?? ''),

    'usuarioid'      => (int)($ins['usuarioid'] ?? 0),
    'nombres'        => (string)($ins['nombres'] ?? ''),
    'apellidos'      => (string)($ins['apellidos'] ?? ''),
    'email_user'     => (string)($ins['email_user'] ?? ''),
  ];

  return [
    'status' => true,
    'msg' => 'OK',
    'data' => [
      'header' => $header,
      'detalle' => $outDet
    ]
  ];
}





}
?>