<?php

class Plan_planeacionv1Model extends Mysql
{

  public $intidProducto;
  public $intIdPlaneacion;

  //////////////////////
  //AUDITORIA

  public $intModulo;
  public $intAccion;
  public $intIdUsuario;
  public $strTabla;
  public $intIdregistro;
  public $strfecha_creacion;
  public $strip;
  public $strDetalle;
  public $strNorma;

  public function __construct()
  {
    parent::__construct();
  }

  public function insertAuditoria($modulo, $accion, $id_usuario, $tabla, $idregistro, $fecha_creacion, $ip, $detalle)
  {


    $return = 0;
    $this->intModulo = $modulo;
    $this->intAccion = $accion;
    $this->intIdUsuario = $id_usuario;
    $this->strTabla = $tabla;
    $this->intIdregistro = $idregistro;
    $this->strfecha_creacion = $fecha_creacion;
    $this->strip = $ip;
    $this->strDetalle = $detalle;

    $query_insert = "INSERT INTO mrp_auditoria(moduloid,accionid,usuarioid,tabla_afectada,id_registro,fecha_hora,ip,navegador) VALUES(?,?,?,?,?,?,?,?)";
    $arrData = array(
      $this->intModulo,
      $this->intAccion,
      $this->intIdUsuario,
      $this->strTabla,
      $this->intIdregistro,
      $this->strfecha_creacion,
      $this->strip,
      $this->strDetalle
    );
    $request_insert = $this->insert($query_insert, $arrData);
    $return = $request_insert;

    return $return;

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
    $plantaid = $_SESSION['userData']['plantaid'];
    $sql = "SELECT * FROM  mrp_productos 
					WHERE estado = 2 AND plantaid = $plantaid";
    $request = $this->select_all($sql);
    return $request;
  }





  public function selectOptionSupervisores()
  {
    $plantaid = $_SESSION['userData']['plantaid'];
    $sql = "SELECT * 
            FROM usuarios 
            WHERE rolid IN (4) 
              AND status = 1 AND plantaid = $plantaid";

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
                    es.proceso,
                    es.tiene_subensamble,
                    em.idmantenimiento,
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
                ORDER BY d.orden ASC";

      $detalle = $this->select_all($sqlDetalle);
      $detalle = is_array($detalle) ? $detalle : [];

      // Agregar subensambles a cada estación si aplica
      foreach ($detalle as &$item) {
        $item['subensambles'] = [];

        $idEstacion = (int) ($item['estacionid'] ?? 0);
        $tieneSubensamble = (int) ($item['tiene_subensamble'] ?? 0);

        if ($idEstacion > 0 && $tieneSubensamble === 1) {
          $sqlSubensambles = "SELECT 
                                        se.idsubensamble,
                                        se.estacionid,
                                        se.nombre_estacion,
                                        se.proceso,
                                        se.estandar,
                                        se.tiempo_ajuste,
                                        se.fecha_creacion,
                                        se.herramientas,
                                        se.estado
                                    FROM mrp_estacion_subensamble AS se
                                    WHERE se.estado = 2
                                      AND se.estacionid = {$idEstacion}
                                    ORDER BY se.idsubensamble ASC";

          $subensambles = $this->select_all($sqlSubensambles);
          $item['subensambles'] = is_array($subensambles) ? $subensambles : [];
        }
      }
      unset($item);

      $r['detalle'] = $detalle;
      $out[] = $r;
    }

    return $out;
  }


  public function selectOperadores()
  {
    $plantaid = $_SESSION['userData']['plantaid'];
    $sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=2 AND plantaid = $plantaid";
    $request = $this->select_all($sql);
    return $request;
  }


  public function selectOperadoresAyudantes()
  {
    $plantaid = $_SESSION['userData']['plantaid'];
    $sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=3 AND plantaid = $plantaid";
    $request = $this->select_all($sql);
    return $request;
  }

  public function selectPersonalCalidad()
  {
    $plantaid = $_SESSION['userData']['plantaid'];
    $sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=5 AND plantaid = $plantaid";
    $request = $this->select_all($sql);
    return $request;
  }



  public function insertPlaneacion($num_orden, $productoid, $pedido, $supervisor, $prioridad, $cantidad, $fecha_inicio, $fecha_requerida, $notas, $plantaid)
  {
    $estado=2;
    $sql = "INSERT INTO mrp_planeacion (num_orden, productoid, num_pedido, supervisorid, prioridad, cantidad, fecha_inicio, fecha_requerida, notas, estado, plantaid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $arrData = [$num_orden, $productoid, $pedido, $supervisor, $prioridad, $cantidad, $fecha_inicio, $fecha_requerida, $notas, $estado, $plantaid];

    return $this->insert($sql, $arrData);
  }

  public function upsertPlaneacionEstacion($planeacionid, $estacionid, $orden, $estampado, $calidad, $operaciones, $especificaciones)
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
               SET orden = ?, estampado=?, calidad=?, operaciones=?, especificaciones=?
               WHERE id_planeacion_estacion = $id";


      $arrData = array($orden, $estampado, $calidad, $operaciones, $especificaciones);

      $request = $this->update($sqlUpd, $arrData);

      return $request;
    }


    $sqlIns = "INSERT INTO mrp_planeacion_estacion
              (planeacionid, estacionid, orden, estado, estampado, calidad, operaciones, especificaciones)
            VALUES (?,?,?,2,?,?,?,?)";
    return $this->insert($sqlIns, [$planeacionid, $estacionid, $orden, $estampado, $calidad, $operaciones, $especificaciones]);
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
    if (empty($ids))
      return [];

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

      $sqlExist = "";


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


  public function consultarExistenciasSubensamble(int $productoid, int $idsubensamble, int $cantidadPlaneada, int $estacionid = 0)
  {
    $faltantes = [];

    $sqlComp = "SELECT 
                  c.idsubcomponente,
                  c.almacenid,
                  c.productoid,
                  c.subensambleid,
                  c.inventarioid,
                  c.cantidad AS cantidad_por_unidad
              FROM mrp_subensamble_componentes c
              WHERE c.estado = 2
                AND c.productoid = $productoid
                AND c.subensambleid = $idsubensamble";

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
          'idsubensamble' => $idsubensamble,
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
        'msg' => 'Faltan componentes en inventario para el subensamble',
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


  // {
//     $num_orden = trim((string) $num_orden);
//     $key = preg_replace('/[^A-Za-z0-9]/', '', $num_orden);

  //     $sqlPla = "SELECT 
//                     pr.inventarioid AS inventarioid,
//                     pla.*,
//                     pr.cve_producto,
//                     pr.descripcion,
//                     CONCAT(us.nombres, ' ', us.apellidos) AS supervisor
//                FROM mrp_planeacion AS pla
//                INNER JOIN mrp_productos AS pr 
//                     ON pla.productoid = pr.idproducto
//                INNER JOIN usuarios AS us
//                     ON pla.supervisorid = us.idusuario
//                WHERE REPLACE(pla.num_orden,'-','') = '{$key}'
//                LIMIT 1";

  //     $planeacion = $this->select($sqlPla);

  //     if (empty($planeacion)) {
//         return ['status' => false, 'msg' => 'No existe la planeación', 'data' => []];
//     }

  //     $planeacionid = (int)($planeacion['idplaneacion'] ?? 0);
//     $productoid   = (int)($planeacion['productoid'] ?? 0);

  //     if ($planeacionid <= 0) {
//         return ['status' => false, 'msg' => 'Planeación inválida', 'data' => []];
//     }

  //     $isAdmin = isset($_SESSION['rolid']) && in_array((int)$_SESSION['rolid'], [1, 5, 4]);
//     $userIdSes = isset($_SESSION['idUser']) ? (int)$_SESSION['idUser'] : 0;

  //     if (!$isAdmin && $userIdSes <= 0) {
//         return ['status' => false, 'msg' => 'Sesión inválida (sin usuario)', 'data' => []];
//     }

  //     $whereUserEst = "";

  //     if (!$isAdmin) {
//         $whereUserEst = " AND (
//                             pe.id_planeacion_estacion IN (
//                                 SELECT o2.planeacion_estacionid
//                                 FROM mrp_planeacion_estacion_operador o2
//                                 WHERE o2.estado = 2
//                                   AND o2.usuarioid = {$userIdSes}
//                             )
//                             OR pe.id_planeacion_estacion IN (
//                                 SELECT ps.planeacion_estacionid
//                                 FROM mrp_planeacion_subensamble ps
//                                 INNER JOIN mrp_planeacion_subensamble_operador pso
//                                     ON pso.planeacion_subensambleid = ps.id_planeacion_subensamble
//                                 WHERE ps.estado = 2
//                                   AND pso.estado = 2
//                                   AND pso.usuarioid = {$userIdSes}
//                             )
//                         )";
//     }

  //     /**
//      * ESTACIONES
//      */
//     $sqlEst = "SELECT 
//                     pe.id_planeacion_estacion,
//                     pe.planeacionid,
//                     pe.estacionid,
//                     pe.orden,
//                     pe.estado,
//                     pe.estampado,
//                     pe.calidad,

  //                     est.idestacion,
//                     est.cve_estacion,
//                     est.plantaid,
//                     est.lineaid,
//                     est.nombre_estacion,
//                     est.proceso,
//                     est.estandar,
//                     est.unidad_medida,
//                     est.tiempo_ajuste,
//                     est.mxn,
//                     est.descripcion,
//                     est.fecha_creacion,
//                     est.herramientas,
//                     est.tiene_subensamble,
//                     est.estado AS estado_estacion,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_estacion_especificaciones mee
//                         WHERE mee.productoid = {$productoid}
//                           AND mee.estacionid = pe.estacionid
//                           AND mee.estado = 2
//                     ) AS total_especificaciones,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_estacion_especificaciones_criticas mec
//                         WHERE mec.productoid = {$productoid}
//                           AND mec.estacionid = pe.estacionid
//                           AND mec.estado = 2
//                     ) AS total_especificaciones_criticas,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_estacion_ayudas_visuales mav
//                         WHERE mav.productoid = {$productoid}
//                           AND mav.estacionid = pe.estacionid
//                           AND mav.estado = 2
//                     ) AS total_ayudas_visuales

  //                FROM mrp_planeacion_estacion pe
//                INNER JOIN mrp_estacion AS est
//                     ON pe.estacionid = est.idestacion
//                WHERE pe.planeacionid = {$planeacionid}
//                  AND pe.estado = 2
//                  {$whereUserEst}
//                ORDER BY pe.orden ASC";

  //     $estaciones = $this->select_all($sqlEst);

  //     if (empty($estaciones)) {
//         $planeacion['estaciones'] = [];
//         return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
//     }

  //     $idsPE = array_map(function ($r) {
//         return (int)($r['id_planeacion_estacion'] ?? 0);
//     }, $estaciones);

  //     $idsPE = array_values(array_filter($idsPE, function ($v) {
//         return $v > 0;
//     }));

  //     if (empty($idsPE)) {
//         $planeacion['estaciones'] = [];
//         return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
//     }

  //     $inPE = implode(',', $idsPE);

  //     /**
//      * ESPECIFICACIONES DE ESTACIÓN
//      */
//     $sqlEspEst = "SELECT
//                         mee.idespecificacion,
//                         mee.productoid,
//                         mee.estacionid,
//                         mee.especificacion,
//                         mee.fecha_creacion,
//                         mee.asignado,
//                         mee.estado
//                   FROM mrp_estacion_especificaciones mee
//                   INNER JOIN mrp_planeacion_estacion pe
//                         ON pe.estacionid = mee.estacionid
//                   WHERE pe.id_planeacion_estacion IN ({$inPE})
//                     AND mee.productoid = {$productoid}
//                     AND mee.estado = 2
//                   ORDER BY mee.idespecificacion ASC";

  //     $espEst = $this->select_all($sqlEspEst);
//     $espEstByEstacion = [];

  //     foreach ($espEst as $esp) {
//         $idestacion = (int)($esp['estacionid'] ?? 0);
//         if ($idestacion <= 0) continue;

  //         $espEstByEstacion[$idestacion][] = [
//             'idespecificacion' => (int)($esp['idespecificacion'] ?? 0),
//             'productoid'       => (int)($esp['productoid'] ?? 0),
//             'estacionid'       => (int)($esp['estacionid'] ?? 0),
//             'especificacion'   => (string)($esp['especificacion'] ?? ''),
//             'fecha_creacion'   => (string)($esp['fecha_creacion'] ?? ''),
//             'asignado'         => (string)($esp['asignado'] ?? ''),
//             'estado'           => (int)($esp['estado'] ?? 0),
//         ];
//     }

  //     /**
//      * ESPECIFICACIONES CRÍTICAS DE ESTACIÓN
//      */
//     $sqlEspCritEst = "SELECT
//                             mec.idespecificacion,
//                             mec.productoid,
//                             mec.estacionid,
//                             mec.especificacion,
//                             mec.fecha_creacion,
//                             mec.estado
//                       FROM mrp_estacion_especificaciones_criticas mec
//                       INNER JOIN mrp_planeacion_estacion pe
//                             ON pe.estacionid = mec.estacionid
//                       WHERE pe.id_planeacion_estacion IN ({$inPE})
//                         AND mec.productoid = {$productoid}
//                         AND mec.estado = 2
//                       ORDER BY mec.idespecificacion ASC";

  //     $espCritEst = $this->select_all($sqlEspCritEst);
//     $espCritEstByEstacion = [];

  //     foreach ($espCritEst as $esp) {
//         $idestacion = (int)($esp['estacionid'] ?? 0);
//         if ($idestacion <= 0) continue;

  //         $espCritEstByEstacion[$idestacion][] = [
//             'idespecificacion' => (int)($esp['idespecificacion'] ?? 0),
//             'productoid'       => (int)($esp['productoid'] ?? 0),
//             'estacionid'       => (int)($esp['estacionid'] ?? 0),
//             'especificacion'   => (string)($esp['especificacion'] ?? ''),
//             'fecha_creacion'   => (string)($esp['fecha_creacion'] ?? ''),
//             'estado'           => (int)($esp['estado'] ?? 0),
//         ];
//     }

  //     /**
//      * AYUDAS VISUALES DE ESTACIÓN
//      */
//     $sqlAyudasEst = "SELECT
//                             mav.idayuda,
//                             mav.productoid,
//                             mav.estacionid,
//                             mav.titulo,
//                             mav.tipo,
//                             mav.archivo,
//                             mav.estado,
//                             mav.fecha_creacion
//                      FROM mrp_estacion_ayudas_visuales mav
//                      INNER JOIN mrp_planeacion_estacion pe
//                             ON pe.estacionid = mav.estacionid
//                      WHERE pe.id_planeacion_estacion IN ({$inPE})
//                        AND mav.productoid = {$productoid}
//                        AND mav.estado = 2
//                      ORDER BY mav.idayuda ASC";

  //     $ayudasEst = $this->select_all($sqlAyudasEst);
//     $ayudasEstByEstacion = [];

  //     foreach ($ayudasEst as $ayuda) {
//         $idestacion = (int)($ayuda['estacionid'] ?? 0);
//         if ($idestacion <= 0) continue;

  //         $ayudasEstByEstacion[$idestacion][] = [
//             'idayuda'        => (int)($ayuda['idayuda'] ?? 0),
//             'productoid'     => (int)($ayuda['productoid'] ?? 0),
//             'estacionid'     => (int)($ayuda['estacionid'] ?? 0),
//             'titulo'         => (string)($ayuda['titulo'] ?? ''),
//             'tipo'           => (string)($ayuda['tipo'] ?? ''),
//             'archivo'        => (string)($ayuda['archivo'] ?? ''),
//             'estado'         => (int)($ayuda['estado'] ?? 0),
//             'fecha_creacion' => (string)($ayuda['fecha_creacion'] ?? ''),
//         ];
//     }

  //     /**
//      * OPERADORES DE ESTACIÓN
//      */
//     $sqlOp = "SELECT 
//                     o.planeacion_estacionid,
//                     o.usuarioid,
//                     UPPER(TRIM(o.rol)) AS rol,
//                     o.estado,
//                     CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
//               FROM mrp_planeacion_estacion_operador o
//               INNER JOIN usuarios u
//                     ON u.idusuario = o.usuarioid
//               WHERE o.estado = 2
//                 AND o.planeacion_estacionid IN ({$inPE})
//               ORDER BY o.planeacion_estacionid ASC, o.id ASC";

  //     $ops = $this->select_all($sqlOp);
//     $opsByPE = [];

  //     foreach ($ops as $op) {
//         $peid = (int)($op['planeacion_estacionid'] ?? 0);
//         if ($peid <= 0) continue;
//         $opsByPE[$peid][] = $op;
//     }

  //     /**
//      * ENCARGADOS PDI POR ESTACIÓN
//      */
//     $sqlEncargadoPdi = "SELECT
//                             pdi.id,
//                             pdi.planeacion_estacionid,
//                             pdi.usuarioid,
//                             UPPER(TRIM(pdi.rol)) AS rol,
//                             pdi.estado,
//                             CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
//                         FROM mrp_planeacion_estacion_calidadpdi pdi
//                         INNER JOIN usuarios u
//                             ON u.idusuario = pdi.usuarioid
//                         WHERE pdi.estado = 2
//                           AND pdi.planeacion_estacionid IN ({$inPE})
//                         ORDER BY pdi.planeacion_estacionid ASC, pdi.id ASC";

  //     $encargadosPdi = $this->select_all($sqlEncargadoPdi);
//     $encargadosPdiByPE = [];

  //     foreach ($encargadosPdi as $pdi) {
//         $peid = (int)($pdi['planeacion_estacionid'] ?? 0);
//         if ($peid <= 0) continue;

  //         $encargadosPdiByPE[$peid][] = [
//             'id'                      => (int)($pdi['id'] ?? 0),
//             'planeacion_estacionid'   => $peid,
//             'usuarioid'               => (int)($pdi['usuarioid'] ?? 0),
//             'rol'                     => (string)($pdi['rol'] ?? ''),
//             'nombre_completo'         => (string)($pdi['nombre_completo'] ?? ''),
//             'estado'                  => (int)($pdi['estado'] ?? 0),
//         ];
//     }

  //     /**
//      * ENCARGADOS DE PUNTOS CRÍTICOS POR ESTACIÓN
//      */
//     $sqlEncargadoPuntoCritico = "SELECT
//                                     pc.id,
//                                     pc.planeacion_estacionid,
//                                     pc.usuarioid,
//                                     UPPER(TRIM(pc.rol)) AS rol,
//                                     pc.estado,
//                                     CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
//                                 FROM mrp_planeacion_estacion_calidadpuntoscriticos pc
//                                 INNER JOIN usuarios u
//                                     ON u.idusuario = pc.usuarioid
//                                 WHERE pc.estado = 2
//                                   AND pc.planeacion_estacionid IN ({$inPE})
//                                 ORDER BY pc.planeacion_estacionid ASC, pc.id ASC";

  //     $encargadosPuntosCriticos = $this->select_all($sqlEncargadoPuntoCritico);
//     $encargadosPuntosCriticosByPE = [];

  //     foreach ($encargadosPuntosCriticos as $pc) {
//         $peid = (int)($pc['planeacion_estacionid'] ?? 0);
//         if ($peid <= 0) continue;

  //         $encargadosPuntosCriticosByPE[$peid][] = [
//             'id'                      => (int)($pc['id'] ?? 0),
//             'planeacion_estacionid'   => $peid,
//             'usuarioid'               => (int)($pc['usuarioid'] ?? 0),
//             'rol'                     => (string)($pc['rol'] ?? ''),
//             'nombre_completo'         => (string)($pc['nombre_completo'] ?? ''),
//             'estado'                  => (int)($pc['estado'] ?? 0),
//         ];
//     }

  //     /**
//      * ÓRDENES DE TRABAJO DE ESTACIÓN
//      */
//     $sqlOT = "SELECT 
//                     ot.idorden,
//                     ot.planeacion_estacionid,
//                     ot.num_sub_orden,
//                     ot.fecha_inicio,
//                     ot.fecha_fin,
//                     ot.comentarios,
//                     ot.estatus,
//                     ot.calidad,
//                     ot.estampado,
//                     ot.operaciones,
//                     ot.especificaciones_criticas,
//                     ot.accion_produccion,
//                     ot.accion_activa,
//                     CAST(SUBSTRING_INDEX(ot.num_sub_orden, 'U', -1) AS UNSIGNED) AS ord_s
//               FROM mrp_ordenes_trabajo ot
//               WHERE ot.planeacion_estacionid IN ({$inPE})
//               ORDER BY ot.planeacion_estacionid ASC, ord_s ASC";

  //     $ots = $this->select_all($sqlOT);
//     $otsByPE = [];

  //     foreach ($ots as $ot) {
//         $peid = (int)($ot['planeacion_estacionid'] ?? 0);
//         if ($peid <= 0) continue;
//         $otsByPE[$peid][] = $ot;
//     }

  //     /**
//      * SUBENSAMBLES LIGADOS A LAS ESTACIONES
//      */
//     $sqlSub = "SELECT 
//                     ps.id_planeacion_subensamble,
//                     ps.planeacionid,
//                     ps.planeacion_estacionid,
//                     ps.estacionid,
//                     ps.subensambleid,
//                     ps.orden_sub,
//                     ps.estado,
//                     ps.fecha_creacion,

  //                     sub.idsubensamble,
//                     sub.nombre_estacion,
//                     sub.proceso,
//                     sub.estandar,
//                     sub.tiempo_ajuste,
//                     sub.herramientas,
//                     sub.fecha_creacion AS fecha_creacion_catalogo,
//                     sub.estado AS estado_subensamble_catalogo,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_subensamble_especificaciones mse
//                         WHERE mse.productoid = {$productoid}
//                           AND mse.subensambleid = ps.subensambleid
//                           AND mse.estado = 2
//                     ) AS total_especificaciones,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_subensamble_especificaciones_criticas msec
//                         WHERE msec.productoid = {$productoid}
//                           AND msec.subensambleid = ps.subensambleid
//                           AND msec.estado = 2
//                     ) AS total_especificaciones_criticas,

  //                     (
//                         SELECT COUNT(*)
//                         FROM mrp_subensamble_ayudas_visuales msav
//                         WHERE msav.productoid = {$productoid}
//                           AND msav.subensambleid = ps.subensambleid
//                           AND msav.estado = 2
//                     ) AS total_ayudas_visuales

  //                FROM mrp_planeacion_subensamble ps
//                INNER JOIN mrp_estacion_subensamble sub
//                     ON ps.subensambleid = sub.idsubensamble
//                WHERE ps.planeacionid = {$planeacionid}
//                  AND ps.estado = 2
//                  AND ps.planeacion_estacionid IN ({$inPE})
//                ORDER BY ps.planeacion_estacionid ASC, ps.orden_sub ASC";

  //     $subensambles = $this->select_all($sqlSub);

  //     $subsByPE = [];
//     $idsPS = [];
//     $idsSubensambleCatalogo = [];

  //     foreach ($subensambles as $sub) {
//         $peid = (int)($sub['planeacion_estacionid'] ?? 0);
//         $psid = (int)($sub['id_planeacion_subensamble'] ?? 0);
//         $idsubensamble = (int)($sub['idsubensamble'] ?? 0);

  //         if ($peid <= 0 || $psid <= 0) continue;

  //         $subsByPE[$peid][] = $sub;
//         $idsPS[] = $psid;

  //         if ($idsubensamble > 0) {
//             $idsSubensambleCatalogo[] = $idsubensamble;
//         }
//     }

  //     $idsPS = array_values(array_unique(array_filter($idsPS)));
//     $idsSubensambleCatalogo = array_values(array_unique(array_filter($idsSubensambleCatalogo)));

  //     $subOpsByPS = []; 
//     $espSubBySub = [];
//     $espCritSubBySub = [];
//     $ayudasSubBySub = [];

  //     if (!empty($idsPS)) {
//         $inPS = implode(',', $idsPS);

  //         /**
//          * OPERADORES DE SUBENSAMBLE
//          */
//         $sqlSubOp = "SELECT 
//                         pso.planeacion_subensambleid,
//                         pso.usuarioid,
//                         UPPER(TRIM(pso.rol)) AS rol,
//                         pso.estado,
//                         CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
//                      FROM mrp_planeacion_subensamble_operador pso
//                      INNER JOIN usuarios u
//                         ON u.idusuario = pso.usuarioid
//                      WHERE pso.estado = 2
//                        AND pso.planeacion_subensambleid IN ({$inPS})
//                      ORDER BY pso.planeacion_subensambleid ASC, pso.id_planeacion_subensamble_operador ASC";

  //         $subOps = $this->select_all($sqlSubOp);

  //         foreach ($subOps as $op) {
//             $psid = (int)($op['planeacion_subensambleid'] ?? 0);
//             if ($psid <= 0) continue;
//             $subOpsByPS[$psid][] = $op;
//         }

  //         /**
//          * ÓRDENES DE TRABAJO DE SUBENSAMBLE
//          */
//         $sqlSubOT = "SELECT 
//                         ots.idorden_subensamble,
//                         ots.planeacion_subensambleid,
//                         ots.num_sub_orden,
//                         ots.codigo_scan,
//                         ots.estado,
//                         ots.fecha_inicio_real,
//                         ots.fecha_fin_real,
//                         ots.fecha_creacion,
//                         ots.operaciones,
//                         CAST(SUBSTRING_INDEX(ots.num_sub_orden, 'U', -1) AS UNSIGNED) AS ord_s
//                      FROM mrp_ordenes_trabajo_subensamble ots
//                      WHERE ots.planeacion_subensambleid IN ({$inPS})
//                      ORDER BY ots.planeacion_subensambleid ASC, ord_s ASC, ots.idorden_subensamble ASC";

  //         $subOTs = $this->select_all($sqlSubOT);

  //         foreach ($subOTs as $ot) {
//             $psid = (int)($ot['planeacion_subensambleid'] ?? 0);
//             if ($psid <= 0) continue;
//             $subOTsByPS[$psid][] = $ot;
//         }

  //         if (!empty($idsSubensambleCatalogo)) {
//             $inSubCatalogo = implode(',', $idsSubensambleCatalogo);

  //             /**
//              * ESPECIFICACIONES DE SUBENSAMBLE
//              */
//             $sqlEspSub = "SELECT
//                                 mse.idespecificacionsubensamble,
//                                 mse.productoid,
//                                 mse.subensambleid,
//                                 mse.especificacion,
//                                 mse.fecha_creacion,
//                                 mse.asignado,
//                                 mse.estado
//                           FROM mrp_subensamble_especificaciones mse
//                           WHERE mse.subensambleid IN ({$inSubCatalogo})
//                             AND mse.productoid = {$productoid}
//                             AND mse.estado = 2
//                           ORDER BY mse.idespecificacionsubensamble ASC";

  //             $espSub = $this->select_all($sqlEspSub);

  //             foreach ($espSub as $esp) {
//                 $idsub = (int)($esp['subensambleid'] ?? 0);
//                 if ($idsub <= 0) continue;

  //                 $espSubBySub[$idsub][] = [
//                     'idespecificacionsubensamble' => (int)($esp['idespecificacionsubensamble'] ?? 0),
//                     'productoid'                  => (int)($esp['productoid'] ?? 0),
//                     'subensambleid'               => (int)($esp['subensambleid'] ?? 0),
//                     'especificacion'              => (string)($esp['especificacion'] ?? ''),
//                     'fecha_creacion'              => (string)($esp['fecha_creacion'] ?? ''),
//                     'asignado'                    => (string)($esp['asignado'] ?? ''),
//                     'estado'                      => (int)($esp['estado'] ?? 0),
//                 ];
//             }

  //             /**
//              * ESPECIFICACIONES CRÍTICAS DE SUBENSAMBLE
//              */
//             $sqlEspCritSub = "SELECT
//                                     msec.idespecificacionsubensamble,
//                                     msec.productoid,
//                                     msec.subensambleid,
//                                     msec.especificacion,
//                                     msec.fecha_creacion,
//                                     msec.estado
//                               FROM mrp_subensamble_especificaciones_criticas msec
//                               WHERE msec.subensambleid IN ({$inSubCatalogo})
//                                 AND msec.productoid = {$productoid}
//                                 AND msec.estado = 2
//                               ORDER BY msec.idespecificacionsubensamble ASC";

  //             $espCritSub = $this->select_all($sqlEspCritSub);

  //             foreach ($espCritSub as $esp) {
//                 $idsub = (int)($esp['subensambleid'] ?? 0);
//                 if ($idsub <= 0) continue;

  //                 $espCritSubBySub[$idsub][] = [
//                     'idespecificacionsubensamble' => (int)($esp['idespecificacionsubensamble'] ?? 0),
//                     'productoid'                  => (int)($esp['productoid'] ?? 0),
//                     'subensambleid'               => (int)($esp['subensambleid'] ?? 0),
//                     'especificacion'              => (string)($esp['especificacion'] ?? ''),
//                     'fecha_creacion'              => (string)($esp['fecha_creacion'] ?? ''),
//                     'estado'                      => (int)($esp['estado'] ?? 0),
//                 ];
//             }

  //             /**
//              * AYUDAS VISUALES DE SUBENSAMBLE
//              */
//             $sqlAyudasSub = "SELECT
//                                     msav.idaysubayuda,
//                                     msav.productoid,
//                                     msav.subensambleid,
//                                     msav.titulo,
//                                     msav.tipo,
//                                     msav.archivo,
//                                     msav.estado,
//                                     msav.fecha_creacion
//                               FROM mrp_subensamble_ayudas_visuales msav
//                               WHERE msav.subensambleid IN ({$inSubCatalogo})
//                                 AND msav.productoid = {$productoid}
//                                 AND msav.estado = 2
//                               ORDER BY msav.idaysubayuda ASC";

  //             $ayudasSub = $this->select_all($sqlAyudasSub);

  //             foreach ($ayudasSub as $ayuda) {
//                 $idsub = (int)($ayuda['subensambleid'] ?? 0);
//                 if ($idsub <= 0) continue;

  //                 $ayudasSubBySub[$idsub][] = [
//                     'idaysubayuda'   => (int)($ayuda['idaysubayuda'] ?? 0),
//                     'productoid'      => (int)($ayuda['productoid'] ?? 0),
//                     'subensambleid'   => (int)($ayuda['subensambleid'] ?? 0),
//                     'titulo'          => (string)($ayuda['titulo'] ?? ''),
//                     'tipo'            => (string)($ayuda['tipo'] ?? ''),
//                     'archivo'         => (string)($ayuda['archivo'] ?? ''),
//                     'estado'          => (int)($ayuda['estado'] ?? 0),
//                     'fecha_creacion'  => (string)($ayuda['fecha_creacion'] ?? ''),
//                 ];
//             }
//         }
//     }

  //     /**
//      * ARMADO DE RESPUESTA FINAL
//      */
//     $outEstaciones = [];

  //     foreach ($estaciones as $e) {
//         $peid = (int)$e['id_planeacion_estacion'];
//         $idestacion = (int)($e['idestacion'] ?? 0);

  //         $item = [
//             'id_planeacion_estacion' => $peid,
//             'planeacionid'           => (int)($e['planeacionid'] ?? 0),
//             'estacionid'             => (int)($e['estacionid'] ?? 0),
//             'orden'                  => (int)($e['orden'] ?? 0),
//             'estado'                 => (int)($e['estado'] ?? 0),
//             'estampado'              => (int)($e['estampado'] ?? 0),
//             'calidad'                => (int)($e['calidad'] ?? 0),

  //             'idestacion'             => $idestacion,
//             'cve_estacion'           => (string)($e['cve_estacion'] ?? ''),
//             'plantaid'               => (int)($e['plantaid'] ?? 0),
//             'lineaid'                => (int)($e['lineaid'] ?? 0),
//             'nombre_estacion'        => (string)($e['nombre_estacion'] ?? ''),
//             'proceso'                => (string)($e['proceso'] ?? ''),
//             'estandar'               => (string)($e['estandar'] ?? ''),
//             'unidad_medida'          => (string)($e['unidad_medida'] ?? ''),
//             'tiempo_ajuste'          => (string)($e['tiempo_ajuste'] ?? ''),
//             'mxn'                    => (string)($e['mxn'] ?? ''),
//             'descripcion'            => (string)($e['descripcion'] ?? ''),
//             'fecha_creacion'         => (string)($e['fecha_creacion'] ?? ''),
//             'herramientas'           => (string)($e['herramientas'] ?? ''),
//             'tiene_subensamble'      => (int)($e['tiene_subensamble'] ?? 0),
//             'estado_estacion'        => (int)($e['estado_estacion'] ?? 0),

  //             'tiene_especificaciones' => ((int)($e['total_especificaciones'] ?? 0) > 0) ? 'si' : 'no',
//             'tiene_especificaciones_criticas' => ((int)($e['total_especificaciones_criticas'] ?? 0) > 0) ? 'si' : 'no',
//             'tiene_ayudas_visuales' => ((int)($e['total_ayudas_visuales'] ?? 0) > 0) ? 'si' : 'no',

  //             'especificaciones' => $espEstByEstacion[$idestacion] ?? [],
//             'especificaciones_criticas' => $espCritEstByEstacion[$idestacion] ?? [],
//             'ayudas_visuales' => $ayudasEstByEstacion[$idestacion] ?? [],

  //             'encargados'             => [],
//             'ayudantes'              => [],

  //             'encargado_pdi'          => $encargadosPdiByPE[$peid] ?? [],
//             'encargado_punto_critico'=> $encargadosPuntosCriticosByPE[$peid] ?? [],

  //             'ordenes_trabajo'        => [],
//             'subensambles'           => [],
//         ];

  //         $listaOps = $opsByPE[$peid] ?? [];

  //         foreach ($listaOps as $op) {
//             $rol = (string)($op['rol'] ?? '');

  //             $objOper = [
//                 'usuarioid'        => (int)($op['usuarioid'] ?? 0),
//                 'rol'              => $rol,
//                 'nombre_completo'  => (string)($op['nombre_completo'] ?? ''),
//             ];

  //             if ($rol === 'ENCARGADO') {
//                 $item['encargados'][] = $objOper;
//             } elseif ($rol === 'AYUDANTE') {
//                 $item['ayudantes'][] = $objOper;
//             }
//         }

  //         $listaOT = $otsByPE[$peid] ?? [];

  //         foreach ($listaOT as $ot) {
//             $item['ordenes_trabajo'][] = [
//                 'idorden'                => (int)($ot['idorden'] ?? 0),
//                 'planeacion_estacionid'  => (int)($ot['planeacion_estacionid'] ?? 0),
//                 'num_sub_orden'          => (string)($ot['num_sub_orden'] ?? ''),
//                 'fecha_inicio'           => (string)($ot['fecha_inicio'] ?? ''),
//                 'fecha_fin'              => (string)($ot['fecha_fin'] ?? ''),
//                 'comentarios'            => (string)($ot['comentarios'] ?? ''),
//                 'estatus'                => (string)($ot['estatus'] ?? ''),
//                 'calidad'                => (string)($ot['calidad'] ?? ''),
//                 'estampado'              => (string)($ot['estampado'] ?? ''),
//                 'operaciones'            => (string)($ot['operaciones'] ?? ''),
//                 'especificaciones_criticas'            => (string)($ot['especificaciones_criticas'] ?? ''),
//                 'accion_produccion'            => (string)($ot['accion_produccion'] ?? ''),
//                 'accion_activa'            => (string)($ot['accion_activa'] ?? ''),
//             ];
//         }

  //         $listaSubs = $subsByPE[$peid] ?? [];

  //         foreach ($listaSubs as $sub) {
//             $psid = (int)($sub['id_planeacion_subensamble'] ?? 0);
//             $idsubensamble = (int)($sub['idsubensamble'] ?? 0);

  //             $subItem = [
//                 'id_planeacion_subensamble'    => $psid,
//                 'planeacionid'                 => (int)($sub['planeacionid'] ?? 0),
//                 'planeacion_estacionid'        => (int)($sub['planeacion_estacionid'] ?? 0),
//                 'estacionid'                   => (int)($sub['estacionid'] ?? 0),
//                 'subensambleid'                => (int)($sub['subensambleid'] ?? 0),
//                 'orden_sub'                    => (int)($sub['orden_sub'] ?? 0),
//                 'estado'                       => (int)($sub['estado'] ?? 0),
//                 'fecha_creacion'               => (string)($sub['fecha_creacion'] ?? ''),

  //                 'idsubensamble'                => $idsubensamble,
//                 'nombre_estacion'              => (string)($sub['nombre_estacion'] ?? ''),
//                 'proceso'                      => (string)($sub['proceso'] ?? ''),
//                 'estandar'                     => (string)($sub['estandar'] ?? ''),
//                 'tiempo_ajuste'                => (string)($sub['tiempo_ajuste'] ?? ''),
//                 'herramientas'                 => (string)($sub['herramientas'] ?? ''),
//                 'fecha_creacion_catalogo'      => (string)($sub['fecha_creacion_catalogo'] ?? ''),
//                 'estado_subensamble_catalogo'  => (int)($sub['estado_subensamble_catalogo'] ?? 0),

  //                 'tiene_especificaciones' => ((int)($sub['total_especificaciones'] ?? 0) > 0) ? 'si' : 'no',
//                 'tiene_especificaciones_criticas' => ((int)($sub['total_especificaciones_criticas'] ?? 0) > 0) ? 'si' : 'no',
//                 'tiene_ayudas_visuales' => ((int)($sub['total_ayudas_visuales'] ?? 0) > 0) ? 'si' : 'no',

  //                 'especificaciones' => $espSubBySub[$idsubensamble] ?? [],
//                 'especificaciones_criticas' => $espCritSubBySub[$idsubensamble] ?? [],
//                 'ayudas_visuales' => $ayudasSubBySub[$idsubensamble] ?? [],

  //                 'encargados'                   => [],
//                 'ayudantes'                    => [],
//                 'ordenes_trabajo'              => [],
//             ];

  //             $listaSubOps = $subOpsByPS[$psid] ?? [];

  //             foreach ($listaSubOps as $op) {
//                 $rol = (string)($op['rol'] ?? '');

  //                 $objOper = [
//                     'usuarioid'       => (int)($op['usuarioid'] ?? 0),
//                     'rol'             => $rol,
//                     'nombre_completo' => (string)($op['nombre_completo'] ?? ''),
//                 ];

  //                 if ($rol === 'ENCARGADO') {
//                     $subItem['encargados'][] = $objOper;
//                 } elseif ($rol === 'AYUDANTE') {
//                     $subItem['ayudantes'][] = $objOper;
//                 }
//             }

  //             $listaSubOT = $subOTsByPS[$psid] ?? [];

  //             foreach ($listaSubOT as $ot) {
//                 $subItem['ordenes_trabajo'][] = [
//                     'idorden_subensamble'      => (int)($ot['idorden_subensamble'] ?? 0),
//                     'planeacion_subensambleid' => (int)($ot['planeacion_subensambleid'] ?? 0),
//                     'num_sub_orden'            => (string)($ot['num_sub_orden'] ?? ''),
//                     'codigo_scan'              => (string)($ot['codigo_scan'] ?? ''),
//                     'estado'                   => (string)($ot['estado'] ?? ''),
//                     'fecha_inicio_real'        => (string)($ot['fecha_inicio_real'] ?? ''),
//                     'fecha_fin_real'           => (string)($ot['fecha_fin_real'] ?? ''),
//                     'fecha_creacion'           => (string)($ot['fecha_creacion'] ?? ''),
//                     'operaciones'              => (string)($ot['operaciones'] ?? ''),
//                 ];
//             }

  //             $item['subensambles'][] = $subItem;
//         }

  //         $outEstaciones[] = $item;
//     }

  //     $planeacion['estaciones'] = $outEstaciones;

  //     return [
//         'status' => true,
//         'msg'    => 'OK',
//         'data'   => $planeacion
//     ];
// }

  public function obtenerPlaneacion($num_orden)
  {
    $num_orden = trim((string) $num_orden);
    $key = preg_replace('/[^A-Za-z0-9]/', '', $num_orden);

    $sqlPla = "SELECT 
                    pr.inventarioid AS inventarioid,
                    pla.*,
                    pr.cve_producto,
                    pr.descripcion,
                    CONCAT(us.nombres, ' ', us.apellidos) AS supervisor,
                    us.email_user
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
    $productoid = (int) ($planeacion['productoid'] ?? 0);

    if ($planeacionid <= 0) {
      return ['status' => false, 'msg' => 'Planeación inválida', 'data' => []];
    }

    $isAdmin = isset($_SESSION['rolid']) && in_array((int) $_SESSION['rolid'], [1, 5, 4]);
    $userIdSes = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

    if (!$isAdmin && $userIdSes <= 0) {
      return ['status' => false, 'msg' => 'Sesión inválida (sin usuario)', 'data' => []];
    }

    $whereUserEst = "";


    // if (!$isAdmin) {
    //     $whereUserEst = " AND (
    //                         pe.id_planeacion_estacion IN (
    //                             SELECT o2.planeacion_estacionid
    //                             FROM mrp_planeacion_estacion_operador o2
    //                             WHERE o2.estado = 2
    //                               AND o2.usuarioid = {$userIdSes}
    //                         )
    //                         OR pe.id_planeacion_estacion IN (
    //                             SELECT ps.planeacion_estacionid
    //                             FROM mrp_planeacion_subensamble ps
    //                             INNER JOIN mrp_planeacion_subensamble_operador pso
    //                                 ON pso.planeacion_subensambleid = ps.id_planeacion_subensamble
    //                             WHERE ps.estado = 2
    //                               AND pso.estado = 2
    //                               AND pso.usuarioid = {$userIdSes}
    //                         )
    //                     )";
    // }

    $sqlEst = "SELECT 
                    pe.id_planeacion_estacion,
                    pe.planeacionid,
                    pe.estacionid,
                    pe.orden,
                    pe.estado,
                    pe.estampado,
                    pe.calidad,

                    est.idestacion,
                    est.cve_estacion,
                    est.plantaid,
                    est.lineaid,
                    est.nombre_estacion,
                    est.proceso,
                    est.estandar,
                    est.unidad_medida,
                    est.tiempo_ajuste,
                    est.mxn,
                    est.descripcion,
                    est.fecha_creacion,
                    est.herramientas,
                    est.tiene_subensamble,
                    est.estado AS estado_estacion,

                    (
                        SELECT COUNT(*)
                        FROM mrp_estacion_especificaciones mee
                        WHERE mee.productoid = {$productoid}
                          AND mee.estacionid = pe.estacionid
                          AND mee.estado = 2
                    ) AS total_especificaciones,

                    (
                        SELECT COUNT(*)
                        FROM mrp_estacion_especificaciones_criticas mec
                        WHERE mec.productoid = {$productoid}
                          AND mec.estacionid = pe.estacionid
                          AND mec.estado = 2
                    ) AS total_especificaciones_criticas,

                    (
                        SELECT COUNT(*)
                        FROM mrp_estacion_ayudas_visuales mav
                        WHERE mav.productoid = {$productoid}
                          AND mav.estacionid = pe.estacionid
                          AND mav.estado = 2
                    ) AS total_ayudas_visuales

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

    $idsPE = array_map(function ($r) {
      return (int) ($r['id_planeacion_estacion'] ?? 0);
    }, $estaciones);

    $idsPE = array_values(array_filter($idsPE, function ($v) {
      return $v > 0;
    }));

    if (empty($idsPE)) {
      $planeacion['estaciones'] = [];
      return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
    }

    $inPE = implode(',', $idsPE);

    $sqlEspEst = "SELECT
                        mee.idespecificacion,
                        mee.productoid,
                        mee.estacionid,
                        mee.especificacion,
                        mee.fecha_creacion,
                        mee.asignado,
                        mee.estado
                  FROM mrp_estacion_especificaciones mee
                  INNER JOIN mrp_planeacion_estacion pe
                        ON pe.estacionid = mee.estacionid
                  WHERE pe.id_planeacion_estacion IN ({$inPE})
                    AND mee.productoid = {$productoid}
                    AND mee.estado = 2
                  ORDER BY mee.idespecificacion ASC";

    $espEst = $this->select_all($sqlEspEst);
    $espEstByEstacion = [];

    foreach ($espEst as $esp) {
      $idestacion = (int) ($esp['estacionid'] ?? 0);
      if ($idestacion <= 0)
        continue;

      $espEstByEstacion[$idestacion][] = [
        'idespecificacion' => (int) ($esp['idespecificacion'] ?? 0),
        'productoid' => (int) ($esp['productoid'] ?? 0),
        'estacionid' => (int) ($esp['estacionid'] ?? 0),
        'especificacion' => (string) ($esp['especificacion'] ?? ''),
        'fecha_creacion' => (string) ($esp['fecha_creacion'] ?? ''),
        'asignado' => (string) ($esp['asignado'] ?? ''),
        'estado' => (int) ($esp['estado'] ?? 0),
      ];
    }

    $sqlEspCritEst = "SELECT
                            mec.idespecificacion,
                            mec.productoid,
                            mec.estacionid,
                            mec.especificacion,
                            mec.fecha_creacion,
                            mec.estado
                      FROM mrp_estacion_especificaciones_criticas mec
                      INNER JOIN mrp_planeacion_estacion pe
                            ON pe.estacionid = mec.estacionid
                      WHERE pe.id_planeacion_estacion IN ({$inPE})
                        AND mec.productoid = {$productoid}
                        AND mec.estado = 2
                      ORDER BY mec.idespecificacion ASC";

    $espCritEst = $this->select_all($sqlEspCritEst);
    $espCritEstByEstacion = [];

    foreach ($espCritEst as $esp) {
      $idestacion = (int) ($esp['estacionid'] ?? 0);
      if ($idestacion <= 0)
        continue;

      $espCritEstByEstacion[$idestacion][] = [
        'idespecificacion' => (int) ($esp['idespecificacion'] ?? 0),
        'productoid' => (int) ($esp['productoid'] ?? 0),
        'estacionid' => (int) ($esp['estacionid'] ?? 0),
        'especificacion' => (string) ($esp['especificacion'] ?? ''),
        'fecha_creacion' => (string) ($esp['fecha_creacion'] ?? ''),
        'estado' => (int) ($esp['estado'] ?? 0),
      ];
    }

    $sqlAyudasEst = "SELECT
                            mav.idayuda,
                            mav.productoid,
                            mav.estacionid,
                            mav.titulo,
                            mav.tipo,
                            mav.archivo,
                            mav.estado,
                            mav.fecha_creacion
                     FROM mrp_estacion_ayudas_visuales mav
                     INNER JOIN mrp_planeacion_estacion pe
                            ON pe.estacionid = mav.estacionid
                     WHERE pe.id_planeacion_estacion IN ({$inPE})
                       AND mav.productoid = {$productoid}
                       AND mav.estado = 2
                     ORDER BY mav.idayuda ASC";

    $ayudasEst = $this->select_all($sqlAyudasEst);
    $ayudasEstByEstacion = [];

    foreach ($ayudasEst as $ayuda) {
      $idestacion = (int) ($ayuda['estacionid'] ?? 0);
      if ($idestacion <= 0)
        continue;

      $ayudasEstByEstacion[$idestacion][] = [
        'idayuda' => (int) ($ayuda['idayuda'] ?? 0),
        'productoid' => (int) ($ayuda['productoid'] ?? 0),
        'estacionid' => (int) ($ayuda['estacionid'] ?? 0),
        'titulo' => (string) ($ayuda['titulo'] ?? ''),
        'tipo' => (string) ($ayuda['tipo'] ?? ''),
        'archivo' => (string) ($ayuda['archivo'] ?? ''),
        'estado' => (int) ($ayuda['estado'] ?? 0),
        'fecha_creacion' => (string) ($ayuda['fecha_creacion'] ?? ''),
      ];
    }

    $sqlOp = "SELECT 
                    o.planeacion_estacionid,
                    o.usuarioid,
                    UPPER(TRIM(o.rol)) AS rol,
                    o.estado,
                    CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
              FROM mrp_planeacion_estacion_operador o
              INNER JOIN usuarios u
                    ON u.idusuario = o.usuarioid
              WHERE o.estado = 2
                AND o.planeacion_estacionid IN ({$inPE})
              ORDER BY o.planeacion_estacionid ASC, o.id ASC";

    $ops = $this->select_all($sqlOp);
    $opsByPE = [];

    foreach ($ops as $op) {
      $peid = (int) ($op['planeacion_estacionid'] ?? 0);
      if ($peid <= 0)
        continue;
      $opsByPE[$peid][] = $op;
    }

    $sqlEncargadoPdi = "SELECT
                            pdi.id,
                            pdi.planeacion_estacionid,
                            pdi.usuarioid,
                            UPPER(TRIM(pdi.rol)) AS rol,
                            pdi.estado,
                            CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
                        FROM mrp_planeacion_estacion_calidadpdi pdi
                        INNER JOIN usuarios u
                            ON u.idusuario = pdi.usuarioid
                        WHERE pdi.estado = 2
                          AND pdi.planeacion_estacionid IN ({$inPE})
                        ORDER BY pdi.planeacion_estacionid ASC, pdi.id ASC";

    $encargadosPdi = $this->select_all($sqlEncargadoPdi);
    $encargadosPdiByPE = [];

    foreach ($encargadosPdi as $pdi) {
      $peid = (int) ($pdi['planeacion_estacionid'] ?? 0);
      if ($peid <= 0)
        continue;

      $encargadosPdiByPE[$peid][] = [
        'id' => (int) ($pdi['id'] ?? 0),
        'planeacion_estacionid' => $peid,
        'usuarioid' => (int) ($pdi['usuarioid'] ?? 0),
        'rol' => (string) ($pdi['rol'] ?? ''),
        'nombre_completo' => (string) ($pdi['nombre_completo'] ?? ''),
        'estado' => (int) ($pdi['estado'] ?? 0),
      ];
    }

    $sqlEncargadoPuntoCritico = "SELECT
                                    pc.id,
                                    pc.planeacion_estacionid,
                                    pc.usuarioid,
                                    UPPER(TRIM(pc.rol)) AS rol,
                                    pc.estado,
                                    CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
                                FROM mrp_planeacion_estacion_calidadpuntoscriticos pc
                                INNER JOIN usuarios u
                                    ON u.idusuario = pc.usuarioid
                                WHERE pc.estado = 2
                                  AND pc.planeacion_estacionid IN ({$inPE})
                                ORDER BY pc.planeacion_estacionid ASC, pc.id ASC";

    $encargadosPuntosCriticos = $this->select_all($sqlEncargadoPuntoCritico);
    $encargadosPuntosCriticosByPE = [];

    foreach ($encargadosPuntosCriticos as $pc) {
      $peid = (int) ($pc['planeacion_estacionid'] ?? 0);
      if ($peid <= 0)
        continue;

      $encargadosPuntosCriticosByPE[$peid][] = [
        'id' => (int) ($pc['id'] ?? 0),
        'planeacion_estacionid' => $peid,
        'usuarioid' => (int) ($pc['usuarioid'] ?? 0),
        'rol' => (string) ($pc['rol'] ?? ''),
        'nombre_completo' => (string) ($pc['nombre_completo'] ?? ''),
        'estado' => (int) ($pc['estado'] ?? 0),
      ];
    }

    $sqlOT = "SELECT 
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
                    CAST(SUBSTRING_INDEX(ot.num_sub_orden, 'U', -1) AS UNSIGNED) AS ord_s
              FROM mrp_ordenes_trabajo ot
              WHERE ot.planeacion_estacionid IN ({$inPE})
              ORDER BY ot.planeacion_estacionid ASC, ord_s ASC";

    $ots = $this->select_all($sqlOT);
    $otsByPE = [];
    $idsOrdenTrabajo = [];

    foreach ($ots as $ot) {
      $peid = (int) ($ot['planeacion_estacionid'] ?? 0);
      $idorden = (int) ($ot['idorden'] ?? 0);

      if ($peid <= 0)
        continue;

      $otsByPE[$peid][] = $ot;

      if ($idorden > 0) {
        $idsOrdenTrabajo[] = $idorden;
      }
    }

    $idsOrdenTrabajo = array_values(array_unique(array_filter($idsOrdenTrabajo)));

    $accionesProduccionByOrden = [];
    $unidadesFueraLineaByOrden = [];
    $notificacionesByOrden = [];

    if (!empty($idsOrdenTrabajo)) {
      $inOrdenes = implode(',', $idsOrdenTrabajo);

      $sqlAccionesProduccion = "SELECT
                                        ap.idaccion,
                                        ap.productoid,
                                        ap.estacionid,
                                        ap.idordengeneral,
                                        ap.unidad,
                                        ap.origen_accion,
                                        ap.tipo_accion,
                                        ap.fecha_inicio,
                                        ap.fecha_fin,
                                        ap.minutos_total,
                                        ap.usuarioid,
                                        ap.estado,
                                        CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS usuario_nombre
                                  FROM mrp_acciones_produccion ap
                                  LEFT JOIN usuarios u
                                        ON u.idusuario = ap.usuarioid
                                  WHERE ap.idordengeneral IN ({$inOrdenes})
                                  ORDER BY ap.idaccion DESC";

      $accionesProduccion = $this->select_all($sqlAccionesProduccion);
      $idsAccionesProduccion = [];

      foreach ($accionesProduccion as $accion) {
        $idorden = (int) ($accion['idordengeneral'] ?? 0);
        $idaccion = (int) ($accion['idaccion'] ?? 0);

        if ($idorden <= 0)
          continue;

        if ($idaccion > 0) {
          $idsAccionesProduccion[] = $idaccion;
        }

        $origenAccion = (int) ($accion['origen_accion'] ?? 0);
        $tipoAccion = (int) ($accion['tipo_accion'] ?? 0);
        $estadoAccion = (int) ($accion['estado'] ?? 0);

        $origenAccionTexto = 'Sin origen';
        if ($origenAccion === 1) {
          $origenAccionTexto = 'No conformidad de puntos críticos';
        } elseif ($origenAccion === 2) {
          $origenAccionTexto = 'Paro manual';
        }

        $tipoAccionTexto = 'Sin acción';
        if ($tipoAccion === 1) {
          $tipoAccionTexto = 'Paro momentáneo';
        } elseif ($tipoAccion === 2) {
          $tipoAccionTexto = 'Retiro AGV';
        } elseif ($tipoAccion === 3) {
          $tipoAccionTexto = 'Unidad alarmada';
        } elseif ($tipoAccion === 4) {
          $tipoAccionTexto = 'Solicitud asistencia';
        } elseif ($tipoAccion === 5) {
          $tipoAccionTexto = 'Falta material';
        }

        $estadoAccionTexto = 'Sin estado';
        if ($estadoAccion === 1) {
          $estadoAccionTexto = 'Pendiente';
        } elseif ($estadoAccion === 2) {
          $estadoAccionTexto = 'Activo';
        } elseif ($estadoAccion === 3) {
          $estadoAccionTexto = 'Cerrado';
        } elseif ($estadoAccion === 4) {
          $estadoAccionTexto = 'Cancelado';
        }

        $accionesProduccionByOrden[$idorden][] = [
          'idaccion' => $idaccion,
          'productoid' => (int) ($accion['productoid'] ?? 0),
          'estacionid' => (int) ($accion['estacionid'] ?? 0),
          'idordengeneral' => $idorden,
          'unidad' => (string) ($accion['unidad'] ?? ''),
          'origen_accion' => $origenAccion,
          'origen_accion_texto' => $origenAccionTexto,
          'tipo_accion' => $tipoAccion,
          'tipo_accion_texto' => $tipoAccionTexto,
          'fecha_inicio' => (string) ($accion['fecha_inicio'] ?? ''),
          'fecha_fin' => (string) ($accion['fecha_fin'] ?? ''),
          'minutos_total' => (string) ($accion['minutos_total'] ?? ''),
          'usuarioid' => (int) ($accion['usuarioid'] ?? 0),
          'usuario_nombre' => (string) ($accion['usuario_nombre'] ?? ''),
          'estado' => $estadoAccion,
          'estado_texto' => $estadoAccionTexto,
        ];
      }

      $sqlFueraLinea = "SELECT
                                fl.idfuera,
                                fl.accionid,
                                fl.productoid,
                                fl.estacionid,
                                fl.idordengeneral,
                                fl.unidad,
                                fl.fecha_salida,
                                fl.usuario_salida,
                                fl.fecha_reincorporacion,
                                fl.usuario_reincorporacion,
                                fl.estado,
                                CONCAT(TRIM(us.nombres), ' ', TRIM(us.apellidos)) AS usuario_salida_nombre,
                                CONCAT(TRIM(ur.nombres), ' ', TRIM(ur.apellidos)) AS usuario_reincorporacion_nombre
                          FROM mrp_unidades_fuera_linea fl
                          LEFT JOIN usuarios us
                                ON us.idusuario = fl.usuario_salida
                          LEFT JOIN usuarios ur
                                ON ur.idusuario = fl.usuario_reincorporacion
                          WHERE fl.idordengeneral IN ({$inOrdenes})
                          ORDER BY fl.idfuera DESC";

      $fueraLinea = $this->select_all($sqlFueraLinea);

      foreach ($fueraLinea as $fl) {
        $idorden = (int) ($fl['idordengeneral'] ?? 0);
        if ($idorden <= 0)
          continue;

        $estadoFuera = (int) ($fl['estado'] ?? 0);

        $estadoFueraTexto = 'Sin estado';
        if ($estadoFuera === 1) {
          $estadoFueraTexto = 'Fuera de línea';
        } elseif ($estadoFuera === 2) {
          $estadoFueraTexto = 'Reincorporada';
        } elseif ($estadoFuera === 3) {
          $estadoFueraTexto = 'Cancelada';
        }

        $unidadesFueraLineaByOrden[$idorden][] = [
          'idfuera' => (int) ($fl['idfuera'] ?? 0),
          'accionid' => (int) ($fl['accionid'] ?? 0),
          'productoid' => (int) ($fl['productoid'] ?? 0),
          'estacionid' => (int) ($fl['estacionid'] ?? 0),
          'idordengeneral' => $idorden,
          'unidad' => (string) ($fl['unidad'] ?? ''),
          'fecha_salida' => (string) ($fl['fecha_salida'] ?? ''),
          'usuario_salida' => (int) ($fl['usuario_salida'] ?? 0),
          'usuario_salida_nombre' => (string) ($fl['usuario_salida_nombre'] ?? ''),
          'fecha_reincorporacion' => (string) ($fl['fecha_reincorporacion'] ?? ''),
          'usuario_reincorporacion' => (int) ($fl['usuario_reincorporacion'] ?? 0),
          'usuario_reincorporacion_nombre' => (string) ($fl['usuario_reincorporacion_nombre'] ?? ''),
          'estado' => $estadoFuera,
          'estado_texto' => $estadoFueraTexto,
        ];
      }

      if (!empty($idsAccionesProduccion)) {
        $idsAccionesProduccion = array_values(array_unique(array_filter($idsAccionesProduccion)));
        $inAcciones = implode(',', $idsAccionesProduccion);

        $sqlNotificaciones = "SELECT
                                        n.idnotificacion,
                                        n.accionid,
                                        n.usuario_origen,
                                        n.usuario_destino,
                                        n.tipo_notificacion,
                                        n.enviado_correo,
                                        n.fecha_envio,
                                        n.estado,
                                        ap.idordengeneral,
                                        CONCAT(TRIM(uo.nombres), ' ', TRIM(uo.apellidos)) AS usuario_origen_nombre,
                                        CONCAT(TRIM(ud.nombres), ' ', TRIM(ud.apellidos)) AS usuario_destino_nombre
                                  FROM mrp_acciones_notificaciones n
                                  INNER JOIN mrp_acciones_produccion ap
                                        ON ap.idaccion = n.accionid
                                  LEFT JOIN usuarios uo
                                        ON uo.idusuario = n.usuario_origen
                                  LEFT JOIN usuarios ud
                                        ON ud.idusuario = n.usuario_destino
                                  WHERE n.accionid IN ({$inAcciones})
                                  ORDER BY n.idnotificacion DESC";

        $notificaciones = $this->select_all($sqlNotificaciones);

        foreach ($notificaciones as $n) {
          $idorden = (int) ($n['idordengeneral'] ?? 0);
          if ($idorden <= 0)
            continue;

          $tipoNotificacion = (int) ($n['tipo_notificacion'] ?? 0);
          $enviadoCorreo = (int) ($n['enviado_correo'] ?? 0);
          $estadoNotificacion = (int) ($n['estado'] ?? 0);

          $tipoNotificacionTexto = 'Sin tipo';
          if ($tipoNotificacion === 1) {
            $tipoNotificacionTexto = 'Solicitud de asistencia';
          } elseif ($tipoNotificacion === 2) {
            $tipoNotificacionTexto = 'Falta de material';
          }

          $enviadoCorreoTexto = $enviadoCorreo === 2 ? 'Sí' : 'No';

          $estadoNotificacionTexto = 'Sin estado';
          if ($estadoNotificacion === 1) {
            $estadoNotificacionTexto = 'Pendiente';
          } elseif ($estadoNotificacion === 2) {
            $estadoNotificacionTexto = 'Enviada';
          } elseif ($estadoNotificacion === 3) {
            $estadoNotificacionTexto = 'Leída';
          } elseif ($estadoNotificacion === 4) {
            $estadoNotificacionTexto = 'Atendida';
          }

          $notificacionesByOrden[$idorden][] = [
            'idnotificacion' => (int) ($n['idnotificacion'] ?? 0),
            'accionid' => (int) ($n['accionid'] ?? 0),
            'usuario_origen' => (int) ($n['usuario_origen'] ?? 0),
            'usuario_origen_nombre' => (string) ($n['usuario_origen_nombre'] ?? ''),
            'usuario_destino' => (int) ($n['usuario_destino'] ?? 0),
            'usuario_destino_nombre' => (string) ($n['usuario_destino_nombre'] ?? ''),
            'tipo_notificacion' => $tipoNotificacion,
            'tipo_notificacion_texto' => $tipoNotificacionTexto,
            'enviado_correo' => $enviadoCorreo,
            'enviado_correo_texto' => $enviadoCorreoTexto,
            'fecha_envio' => (string) ($n['fecha_envio'] ?? ''),
            'estado' => $estadoNotificacion,
            'estado_texto' => $estadoNotificacionTexto,
          ];
        }
      }
    }

    $sqlSub = "SELECT 
                    ps.id_planeacion_subensamble,
                    ps.planeacionid,
                    ps.planeacion_estacionid,
                    ps.estacionid,
                    ps.subensambleid,
                    ps.orden_sub,
                    ps.estado,
                    ps.fecha_creacion,

                    sub.idsubensamble,
                    sub.nombre_estacion,
                    sub.proceso,
                    sub.estandar,
                    sub.tiempo_ajuste,
                    sub.herramientas,
                    sub.fecha_creacion AS fecha_creacion_catalogo,
                    sub.estado AS estado_subensamble_catalogo,

                    (
                        SELECT COUNT(*)
                        FROM mrp_subensamble_especificaciones mse
                        WHERE mse.productoid = {$productoid}
                          AND mse.subensambleid = ps.subensambleid
                          AND mse.estado = 2
                    ) AS total_especificaciones,

                    (
                        SELECT COUNT(*)
                        FROM mrp_subensamble_especificaciones_criticas msec
                        WHERE msec.productoid = {$productoid}
                          AND msec.subensambleid = ps.subensambleid
                          AND msec.estado = 2
                    ) AS total_especificaciones_criticas,

                    (
                        SELECT COUNT(*)
                        FROM mrp_subensamble_ayudas_visuales msav
                        WHERE msav.productoid = {$productoid}
                          AND msav.subensambleid = ps.subensambleid
                          AND msav.estado = 2
                    ) AS total_ayudas_visuales

               FROM mrp_planeacion_subensamble ps
               INNER JOIN mrp_estacion_subensamble sub
                    ON ps.subensambleid = sub.idsubensamble
               WHERE ps.planeacionid = {$planeacionid}
                 AND ps.estado = 2
                 AND ps.planeacion_estacionid IN ({$inPE})
               ORDER BY ps.planeacion_estacionid ASC, ps.orden_sub ASC";

    $subensambles = $this->select_all($sqlSub);

    $subsByPE = [];
    $idsPS = [];
    $idsSubensambleCatalogo = [];

    foreach ($subensambles as $sub) {
      $peid = (int) ($sub['planeacion_estacionid'] ?? 0);
      $psid = (int) ($sub['id_planeacion_subensamble'] ?? 0);
      $idsubensamble = (int) ($sub['idsubensamble'] ?? 0);

      if ($peid <= 0 || $psid <= 0)
        continue;

      $subsByPE[$peid][] = $sub;
      $idsPS[] = $psid;

      if ($idsubensamble > 0) {
        $idsSubensambleCatalogo[] = $idsubensamble;
      }
    }

    $idsPS = array_values(array_unique(array_filter($idsPS)));
    $idsSubensambleCatalogo = array_values(array_unique(array_filter($idsSubensambleCatalogo)));

    $subOpsByPS = [];
    $espSubBySub = [];
    $espCritSubBySub = [];
    $ayudasSubBySub = [];
    $subOTsByPS = [];

    if (!empty($idsPS)) {
      $inPS = implode(',', $idsPS);

      $sqlSubOp = "SELECT 
                        pso.planeacion_subensambleid,
                        pso.usuarioid,
                        UPPER(TRIM(pso.rol)) AS rol,
                        pso.estado,
                        CONCAT(TRIM(u.nombres), ' ', TRIM(u.apellidos)) AS nombre_completo
                     FROM mrp_planeacion_subensamble_operador pso
                     INNER JOIN usuarios u
                        ON u.idusuario = pso.usuarioid
                     WHERE pso.estado = 2
                       AND pso.planeacion_subensambleid IN ({$inPS})
                     ORDER BY pso.planeacion_subensambleid ASC, pso.id_planeacion_subensamble_operador ASC";

      $subOps = $this->select_all($sqlSubOp);

      foreach ($subOps as $op) {
        $psid = (int) ($op['planeacion_subensambleid'] ?? 0);
        if ($psid <= 0)
          continue;
        $subOpsByPS[$psid][] = $op;
      }

      $sqlSubOT = "SELECT 
                        ots.idorden_subensamble,
                        ots.planeacion_subensambleid,
                        ots.num_sub_orden,
                        ots.codigo_scan,
                        ots.estado,
                        ots.fecha_inicio_real,
                        ots.fecha_fin_real,
                        ots.fecha_creacion,
                        ots.operaciones,
                        CAST(SUBSTRING_INDEX(ots.num_sub_orden, 'U', -1) AS UNSIGNED) AS ord_s
                     FROM mrp_ordenes_trabajo_subensamble ots
                     WHERE ots.planeacion_subensambleid IN ({$inPS})
                     ORDER BY ots.planeacion_subensambleid ASC, ord_s ASC, ots.idorden_subensamble ASC";

      $subOTs = $this->select_all($sqlSubOT);

      foreach ($subOTs as $ot) {
        $psid = (int) ($ot['planeacion_subensambleid'] ?? 0);
        if ($psid <= 0)
          continue;
        $subOTsByPS[$psid][] = $ot;
      }

      if (!empty($idsSubensambleCatalogo)) {
        $inSubCatalogo = implode(',', $idsSubensambleCatalogo);

        $sqlEspSub = "SELECT
                                mse.idespecificacionsubensamble,
                                mse.productoid,
                                mse.subensambleid,
                                mse.especificacion,
                                mse.fecha_creacion,
                                mse.asignado,
                                mse.estado
                          FROM mrp_subensamble_especificaciones mse
                          WHERE mse.subensambleid IN ({$inSubCatalogo})
                            AND mse.productoid = {$productoid}
                            AND mse.estado = 2
                          ORDER BY mse.idespecificacionsubensamble ASC";

        $espSub = $this->select_all($sqlEspSub);

        foreach ($espSub as $esp) {
          $idsub = (int) ($esp['subensambleid'] ?? 0);
          if ($idsub <= 0)
            continue;

          $espSubBySub[$idsub][] = [
            'idespecificacionsubensamble' => (int) ($esp['idespecificacionsubensamble'] ?? 0),
            'productoid' => (int) ($esp['productoid'] ?? 0),
            'subensambleid' => (int) ($esp['subensambleid'] ?? 0),
            'especificacion' => (string) ($esp['especificacion'] ?? ''),
            'fecha_creacion' => (string) ($esp['fecha_creacion'] ?? ''),
            'asignado' => (string) ($esp['asignado'] ?? ''),
            'estado' => (int) ($esp['estado'] ?? 0),
          ];
        }

        $sqlEspCritSub = "SELECT
                                    msec.idespecificacionsubensamble,
                                    msec.productoid,
                                    msec.subensambleid,
                                    msec.especificacion,
                                    msec.fecha_creacion,
                                    msec.estado
                              FROM mrp_subensamble_especificaciones_criticas msec
                              WHERE msec.subensambleid IN ({$inSubCatalogo})
                                AND msec.productoid = {$productoid}
                                AND msec.estado = 2
                              ORDER BY msec.idespecificacionsubensamble ASC";

        $espCritSub = $this->select_all($sqlEspCritSub);

        foreach ($espCritSub as $esp) {
          $idsub = (int) ($esp['subensambleid'] ?? 0);
          if ($idsub <= 0)
            continue;

          $espCritSubBySub[$idsub][] = [
            'idespecificacionsubensamble' => (int) ($esp['idespecificacionsubensamble'] ?? 0),
            'productoid' => (int) ($esp['productoid'] ?? 0),
            'subensambleid' => (int) ($esp['subensambleid'] ?? 0),
            'especificacion' => (string) ($esp['especificacion'] ?? ''),
            'fecha_creacion' => (string) ($esp['fecha_creacion'] ?? ''),
            'estado' => (int) ($esp['estado'] ?? 0),
          ];
        }

        $sqlAyudasSub = "SELECT
                                    msav.idaysubayuda,
                                    msav.productoid,
                                    msav.subensambleid,
                                    msav.titulo,
                                    msav.tipo,
                                    msav.archivo,
                                    msav.estado,
                                    msav.fecha_creacion
                              FROM mrp_subensamble_ayudas_visuales msav
                              WHERE msav.subensambleid IN ({$inSubCatalogo})
                                AND msav.productoid = {$productoid}
                                AND msav.estado = 2
                              ORDER BY msav.idaysubayuda ASC";

        $ayudasSub = $this->select_all($sqlAyudasSub);

        foreach ($ayudasSub as $ayuda) {
          $idsub = (int) ($ayuda['subensambleid'] ?? 0);
          if ($idsub <= 0)
            continue;

          $ayudasSubBySub[$idsub][] = [
            'idaysubayuda' => (int) ($ayuda['idaysubayuda'] ?? 0),
            'productoid' => (int) ($ayuda['productoid'] ?? 0),
            'subensambleid' => (int) ($ayuda['subensambleid'] ?? 0),
            'titulo' => (string) ($ayuda['titulo'] ?? ''),
            'tipo' => (string) ($ayuda['tipo'] ?? ''),
            'archivo' => (string) ($ayuda['archivo'] ?? ''),
            'estado' => (int) ($ayuda['estado'] ?? 0),
            'fecha_creacion' => (string) ($ayuda['fecha_creacion'] ?? ''),
          ];
        }
      }
    }

    $outEstaciones = [];

    foreach ($estaciones as $e) {
      $peid = (int) $e['id_planeacion_estacion'];
      $idestacion = (int) ($e['idestacion'] ?? 0);

      $item = [
        'id_planeacion_estacion' => $peid,
        'planeacionid' => (int) ($e['planeacionid'] ?? 0),
        'estacionid' => (int) ($e['estacionid'] ?? 0),
        'orden' => (int) ($e['orden'] ?? 0),
        'estado' => (int) ($e['estado'] ?? 0),
        'estampado' => (int) ($e['estampado'] ?? 0),
        'calidad' => (int) ($e['calidad'] ?? 0),

        'idestacion' => $idestacion,
        'cve_estacion' => (string) ($e['cve_estacion'] ?? ''),
        'plantaid' => (int) ($e['plantaid'] ?? 0),
        'lineaid' => (int) ($e['lineaid'] ?? 0),
        'nombre_estacion' => (string) ($e['nombre_estacion'] ?? ''),
        'proceso' => (string) ($e['proceso'] ?? ''),
        'estandar' => (string) ($e['estandar'] ?? ''),
        'unidad_medida' => (string) ($e['unidad_medida'] ?? ''),
        'tiempo_ajuste' => (string) ($e['tiempo_ajuste'] ?? ''),
        'mxn' => (string) ($e['mxn'] ?? ''),
        'descripcion' => (string) ($e['descripcion'] ?? ''),
        'fecha_creacion' => (string) ($e['fecha_creacion'] ?? ''),
        'herramientas' => (string) ($e['herramientas'] ?? ''),
        'tiene_subensamble' => (int) ($e['tiene_subensamble'] ?? 0),
        'estado_estacion' => (int) ($e['estado_estacion'] ?? 0),

        'tiene_especificaciones' => ((int) ($e['total_especificaciones'] ?? 0) > 0) ? 'si' : 'no',
        'tiene_especificaciones_criticas' => ((int) ($e['total_especificaciones_criticas'] ?? 0) > 0) ? 'si' : 'no',
        'tiene_ayudas_visuales' => ((int) ($e['total_ayudas_visuales'] ?? 0) > 0) ? 'si' : 'no',

        'especificaciones' => $espEstByEstacion[$idestacion] ?? [],
        'especificaciones_criticas' => $espCritEstByEstacion[$idestacion] ?? [],
        'ayudas_visuales' => $ayudasEstByEstacion[$idestacion] ?? [],

        'encargados' => [],
        'ayudantes' => [],

        'encargado_pdi' => $encargadosPdiByPE[$peid] ?? [],
        'encargado_punto_critico' => $encargadosPuntosCriticosByPE[$peid] ?? [],

        'ordenes_trabajo' => [],
        'subensambles' => [],
      ];

      $listaOps = $opsByPE[$peid] ?? [];

      foreach ($listaOps as $op) {
        $rol = (string) ($op['rol'] ?? '');

        $objOper = [
          'usuarioid' => (int) ($op['usuarioid'] ?? 0),
          'rol' => $rol,
          'nombre_completo' => (string) ($op['nombre_completo'] ?? ''),
        ];

        if ($rol === 'ENCARGADO') {
          $item['encargados'][] = $objOper;
        } elseif ($rol === 'AYUDANTE') {
          $item['ayudantes'][] = $objOper;
        }
      }

      $listaOT = $otsByPE[$peid] ?? [];

      foreach ($listaOT as $ot) {
        $idordenActual = (int) ($ot['idorden'] ?? 0);
        $estatusActual = (int) ($ot['estatus'] ?? 0);
        $accionProduccion = (int) ($ot['accion_produccion'] ?? 0);
        $accionActiva = (int) ($ot['accion_activa'] ?? 1);

        $estatusTexto = 'Sin estado';
        if ($estatusActual === 1) {
          $estatusTexto = 'Pendiente';
        } elseif ($estatusActual === 2) {
          $estatusTexto = 'En proceso';
        } elseif ($estatusActual === 3) {
          $estatusTexto = 'Finalizada';
        }

        $accionProduccionTexto = 'Sin acción';
        if ($accionProduccion === 1) {
          $accionProduccionTexto = 'Paro momentáneo';
        } elseif ($accionProduccion === 2) {
          $accionProduccionTexto = 'Retiro AGV';
        } elseif ($accionProduccion === 3) {
          $accionProduccionTexto = 'Unidad alarmada';
        } elseif ($accionProduccion === 4) {
          $accionProduccionTexto = 'Solicitud asistencia';
        } elseif ($accionProduccion === 5) {
          $accionProduccionTexto = 'Falta material';
        }

        $item['ordenes_trabajo'][] = [
          'idorden' => $idordenActual,
          'planeacion_estacionid' => (int) ($ot['planeacion_estacionid'] ?? 0),
          'num_sub_orden' => (string) ($ot['num_sub_orden'] ?? ''),
          'fecha_inicio' => (string) ($ot['fecha_inicio'] ?? ''),
          'fecha_fin' => (string) ($ot['fecha_fin'] ?? ''),
          'comentarios' => (string) ($ot['comentarios'] ?? ''),
          'estatus' => (string) ($ot['estatus'] ?? ''),
          'estatus_texto' => $estatusTexto,
          'calidad' => (string) ($ot['calidad'] ?? ''),
          'estampado' => (string) ($ot['estampado'] ?? ''),
          'operaciones' => (string) ($ot['operaciones'] ?? ''),
          'especificaciones_criticas' => (string) ($ot['especificaciones_criticas'] ?? ''),
          'accion_produccion' => $accionProduccion,
          'accion_produccion_texto' => $accionProduccionTexto,
          'accion_activa' => $accionActiva,
          'accion_activa_texto' => ($accionActiva === 2) ? 'Sí' : 'No',

          'acciones_produccion' => $accionesProduccionByOrden[$idordenActual] ?? [],
          'unidades_fuera_linea' => $unidadesFueraLineaByOrden[$idordenActual] ?? [],
          'notificaciones' => $notificacionesByOrden[$idordenActual] ?? [],
        ];
      }

      $listaSubs = $subsByPE[$peid] ?? [];

      foreach ($listaSubs as $sub) {
        $psid = (int) ($sub['id_planeacion_subensamble'] ?? 0);
        $idsubensamble = (int) ($sub['idsubensamble'] ?? 0);

        $subItem = [
          'id_planeacion_subensamble' => $psid,
          'planeacionid' => (int) ($sub['planeacionid'] ?? 0),
          'planeacion_estacionid' => (int) ($sub['planeacion_estacionid'] ?? 0),
          'estacionid' => (int) ($sub['estacionid'] ?? 0),
          'subensambleid' => (int) ($sub['subensambleid'] ?? 0),
          'orden_sub' => (int) ($sub['orden_sub'] ?? 0),
          'estado' => (int) ($sub['estado'] ?? 0),
          'fecha_creacion' => (string) ($sub['fecha_creacion'] ?? ''),

          'idsubensamble' => $idsubensamble,
          'nombre_estacion' => (string) ($sub['nombre_estacion'] ?? ''),
          'proceso' => (string) ($sub['proceso'] ?? ''),
          'estandar' => (string) ($sub['estandar'] ?? ''),
          'tiempo_ajuste' => (string) ($sub['tiempo_ajuste'] ?? ''),
          'herramientas' => (string) ($sub['herramientas'] ?? ''),
          'fecha_creacion_catalogo' => (string) ($sub['fecha_creacion_catalogo'] ?? ''),
          'estado_subensamble_catalogo' => (int) ($sub['estado_subensamble_catalogo'] ?? 0),

          'tiene_especificaciones' => ((int) ($sub['total_especificaciones'] ?? 0) > 0) ? 'si' : 'no',
          'tiene_especificaciones_criticas' => ((int) ($sub['total_especificaciones_criticas'] ?? 0) > 0) ? 'si' : 'no',
          'tiene_ayudas_visuales' => ((int) ($sub['total_ayudas_visuales'] ?? 0) > 0) ? 'si' : 'no',

          'especificaciones' => $espSubBySub[$idsubensamble] ?? [],
          'especificaciones_criticas' => $espCritSubBySub[$idsubensamble] ?? [],
          'ayudas_visuales' => $ayudasSubBySub[$idsubensamble] ?? [],

          'encargados' => [],
          'ayudantes' => [],
          'ordenes_trabajo' => [],
        ];

        $listaSubOps = $subOpsByPS[$psid] ?? [];

        foreach ($listaSubOps as $op) {
          $rol = (string) ($op['rol'] ?? '');

          $objOper = [
            'usuarioid' => (int) ($op['usuarioid'] ?? 0),
            'rol' => $rol,
            'nombre_completo' => (string) ($op['nombre_completo'] ?? ''),
          ];

          if ($rol === 'ENCARGADO') {
            $subItem['encargados'][] = $objOper;
          } elseif ($rol === 'AYUDANTE') {
            $subItem['ayudantes'][] = $objOper;
          }
        }

        $listaSubOT = $subOTsByPS[$psid] ?? [];

        foreach ($listaSubOT as $ot) {
          $subItem['ordenes_trabajo'][] = [
            'idorden_subensamble' => (int) ($ot['idorden_subensamble'] ?? 0),
            'planeacion_subensambleid' => (int) ($ot['planeacion_subensambleid'] ?? 0),
            'num_sub_orden' => (string) ($ot['num_sub_orden'] ?? ''),
            'codigo_scan' => (string) ($ot['codigo_scan'] ?? ''),
            'estado' => (string) ($ot['estado'] ?? ''),
            'fecha_inicio_real' => (string) ($ot['fecha_inicio_real'] ?? ''),
            'fecha_fin_real' => (string) ($ot['fecha_fin_real'] ?? ''),
            'fecha_creacion' => (string) ($ot['fecha_creacion'] ?? ''),
            'operaciones' => (string) ($ot['operaciones'] ?? ''),
          ];
        }

        $item['subensambles'][] = $subItem;
      }

      $outEstaciones[] = $item;
    }

    $planeacion['estaciones'] = $outEstaciones;

    return [
      'status' => true,
      'msg' => 'OK',
      'data' => $planeacion
    ];
  }





  public function insertOrdenes26(int $id_planeacion_estacion, string $num_orden_s)
  {
    $sql = "INSERT INTO mrp_ordenes_trabajo (planeacion_estacionid, num_sub_orden)
          VALUES (?, ?)";
    return $this->insert($sql, [$id_planeacion_estacion, $num_orden_s]);
  }

  public function insertOrdenes(int $id_planeacion_estacion, string $num_orden_s, int $estampado = 0, int $calidad = 0, int $operaciones, int $especificaciones)
  {


    $sql = "INSERT INTO mrp_ordenes_trabajo (planeacion_estacionid, num_sub_orden, estampado, calidad, operaciones, especificaciones_criticas)
          VALUES (?, ?, ?, ?, ?, ?)";
    return $this->insert($sql, [$id_planeacion_estacion, $num_orden_s, $estampado, $calidad, $operaciones, $especificaciones]);
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
    $idorden = (int) $idorden;

    $fecha_inicio = trim((string) $fecha_inicio);
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
            ot.calidad,              
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
      return ['status' => false, 'msg' => 'No existe la Sub-OT', 'data' => []];
    }

    $estatus = (int) ($cur['estatus'] ?? 0);
    if ($estatus !== 1) {
      return [
        'status' => false,
        'msg' => 'No puedes iniciar: la Sub-OT no está pendiente',
        'data' => [
          'estatus_actual' => $estatus
        ]
      ];
    }

    $peid = (int) ($cur['planeacion_estacionid'] ?? 0);
    $idpla = (int) ($cur['planeacionid'] ?? 0);
    $estOrd = (int) ($cur['estacion_orden'] ?? 0);
    $subot = trim((string) ($cur['num_sub_orden'] ?? ''));

    if ($peid <= 0 || $idpla <= 0 || $estOrd <= 0 || $subot === '') {
      return [
        'status' => false,
        'msg' => 'Datos incompletos para iniciar (peid/planeacionid/orden/subot)',
        'data' => [
          'peid' => $peid,
          'planeacionid' => $idpla,
          'orden' => $estOrd,
          'subot' => $subot
        ]
      ];
    }

    $snum = 0;
    if (preg_match('/-S(\d+)\s*$/i', $subot, $m)) {
      $snum = (int) $m[1];
    }
    if ($snum <= 0) {
      return ['status' => false, 'msg' => 'Sub-OT inválida (no se detectó Sxx)', 'data' => []];
    }

    $base = preg_replace('/-S\d+\s*$/i', '', $subot);
    $subotSql = addslashes($subot);


    $sqlBusy = "SELECT COUNT(*) AS c
              FROM mrp_ordenes_trabajo
              WHERE planeacion_estacionid = {$peid}
                AND estatus = 2
                AND (calidad IS NULL OR calidad <> 4)";

    $busy = $this->select($sqlBusy);

    if ((int) ($busy['c'] ?? 0) > 0) {
      return [
        'status' => false,
        'msg' => 'No puedes iniciar: existe una Sub-OT en proceso activa (no pausada por calidad) en esta estación',
        'data' => []
      ];
    }


    if ($snum > 1) {
      $prevSub = $base . '-U' . str_pad((string) ($snum - 1), 2, '0', STR_PAD_LEFT);
      $prevSubSql = addslashes($prevSub);

      $sqlPrev = "SELECT estatus, calidad
                FROM mrp_ordenes_trabajo
                WHERE planeacion_estacionid = {$peid}
                  AND num_sub_orden = '{$prevSubSql}'
                LIMIT 1";
      $prev = $this->select($sqlPrev);

      if (empty($prev)) {
        return [
          'status' => false,
          'msg' => "No se encontró {$prevSub} en esta estación (validación)",
          'data' => [
            'planeacion_estacionid' => $peid,
            'prevSub' => $prevSub
          ]
        ];
      }

      $prevEstatus = (int) ($prev['estatus'] ?? 0);
      $prevCalidad = (int) ($prev['calidad'] ?? 0);


      $prevOk =
        ($prevEstatus === 3) ||
        ($prevEstatus === 2 && $prevCalidad === 4) ||
        ($prevEstatus === 1 && in_array($prevCalidad, [3, 4], true));

      if (!$prevOk) {
        return [
          'status' => false,
          'msg' => "Primero finaliza {$prevSub} en esta estación (o debe estar pausada por calidad)",
          'data' => [
            'prevSub' => $prevSub,
            'prevEstatus' => $prevEstatus,
            'prevCalidad' => $prevCalidad
          ]
        ];
      }

    }

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
        return [
          'status' => false,
          'msg' => 'No se encontró la Sub-OT en la estación anterior (validación)',
          'data' => [
            'planeacionid' => $idpla,
            'orden_anterior' => $prevOrden,
            'subot' => $subot
          ]
        ];
      }

      if ((int) ($prevStation['estatus'] ?? 0) !== 3) {
        return ['status' => false, 'msg' => 'No puedes iniciar este proceso porque aún no está finalizado en la estación anterior.', 'data' => []];
      }
    }


    $sqlUpd = "UPDATE mrp_ordenes_trabajo
             SET fecha_inicio = ?, estatus = 2
             WHERE idorden = {$idorden} AND estatus = 1";

    $arrData = [$fecha_inicio];

    $ok = $this->update($sqlUpd, $arrData);

    if (!$ok) {
      return ['status' => false, 'msg' => 'No se pudo iniciar', 'data' => []];
    }

    return [
      'status' => true,
      'msg' => 'Proceso iniciado',
      'data' => [
        'idorden' => $idorden,
        'fecha_inicio' => $fecha_inicio,
        'estatus' => 2
      ]
    ];
  }




  public function finishOT(int $idorden, string $fecha_fin, int $idinventario)
  {
    $idorden = (int) $idorden;

    $CONCEPMOVID = 3;

    $inventarioid = $idinventario;
    $almacenid = 6;

    $sql = "SELECT idorden, estatus, num_sub_orden
          FROM mrp_ordenes_trabajo
          WHERE idorden = {$idorden}
          LIMIT 1";
    $cur = $this->select($sql);

    if (empty($cur)) {
      return ['status' => false, 'msg' => 'No existe la Sub-OT', 'data' => []];
    }

    $estatus = (int) ($cur['estatus'] ?? 0);
    if ($estatus !== 2) {
      return [
        'status' => false,
        'msg' => 'No puedes finalizar: la Sub-OT no está en proceso',
        'data' => [
          'estatus_actual' => $estatus
        ]
      ];
    }

    $subot = trim((string) ($cur['num_sub_orden'] ?? ''));
    if ($subot === '') {
      return ['status' => false, 'msg' => 'La Sub-OT no tiene num_sub_orden', 'data' => []];
    }


    $numero_movimiento = preg_replace('/-S\d+$/', '', $subot);

    $sqlUpd = "UPDATE mrp_ordenes_trabajo
             SET fecha_fin = ?, estatus = 3
             WHERE idorden = {$idorden} AND estatus = 2";
    $ok = $this->update($sqlUpd, [$fecha_fin]);

    if (!$ok) {
      return ['status' => false, 'msg' => 'No se pudo finalizar', 'data' => []];
    }


    $sqlPend = "SELECT COUNT(*) AS pendientes
              FROM mrp_ordenes_trabajo
              WHERE num_sub_orden = ?
                AND estatus <> 3";
    $rowPend = $this->select($sqlPend, [$subot]);
    $pendientes = (int) ($rowPend['pendientes'] ?? 0);

    $movimiento_insertado = false;
    $multialmacen_insertado = false;

    if ($pendientes === 0) {


      $sqlYa = "SELECT COUNT(*) AS ya
              FROM wms_movimientos_inventario
              WHERE numero_movimiento = ?
                AND referencia = ?
              LIMIT 1";
      $rowYa = $this->select($sqlYa, [$numero_movimiento, $subot]);
      $ya = (int) ($rowYa['ya'] ?? 0);

      if ($ya === 0) {

        $sqlIns = "INSERT INTO wms_movimientos_inventario
        (inventarioid, almacenid, numero_movimiento, concepmovid, referencia,
         cantidad, costo_cantidad, precio, costo, existencia, signo, fecha_movimiento, estado)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $arrIns = [
          $inventarioid,
          $almacenid,
          $numero_movimiento,
          $CONCEPMOVID,
          $subot,
          1,
          0,
          0,
          0,
          1,
          1,
          2
        ];

        if (method_exists($this, 'insert')) {
          $okIns = $this->insert($sqlIns, $arrIns);
        } else {
          $okIns = $this->update($sqlIns, $arrIns);
        }

        if (!$okIns) {
          return [
            'status' => true,
            'msg' => 'Proceso finalizado, pero NO se pudo insertar movimiento de inventario',
            'data' => [
              'idorden' => $idorden,
              'fecha_fin' => $fecha_fin,
              'estatus' => 3,
              'subot' => $subot,
              'numero_movimiento' => $numero_movimiento,
              'pendientes_misma_subot' => $pendientes,
              'movimiento_insertado' => false,
              'multialmacen_insertado' => false
            ]
          ];
        }

        $movimiento_insertado = true;
      }


      $sqlEx = "SELECT idmultialmacen, existencia
              FROM wms_multialmacen
              WHERE inventarioid = ? AND almacenid = ?
              LIMIT 1";
      $rowEx = $this->select($sqlEx, [$inventarioid, $almacenid]);

      if (!empty($rowEx)) {

        $idmultialmacen = (int) ($rowEx['idmultialmacen'] ?? 0);

        if ($idmultialmacen > 0) {
          $sqlUpdMA = "UPDATE wms_multialmacen
                     SET existencia = existencia + 1
                     WHERE idmultialmacen = {$idmultialmacen}
                     LIMIT 1";
          $okMA = $this->update($sqlUpdMA, []);

          if ($okMA) {
            $multialmacen_insertado = true;
          }
        }
      } else {

        $sqlInsMA = "INSERT INTO wms_multialmacen
        (inventarioid, almacenid, control_almacen, existencia, stock_minimo, stock_maximo, compras_x_recibir, pendiente_surtir)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)";

        $arrMA = [
          $inventarioid,
          $almacenid,
          '',
          1,
          0,
          0,
          0,
          0
        ];

        if (method_exists($this, 'insert')) {
          $okInsMA = $this->insert($sqlInsMA, $arrMA);
        } else {
          $okInsMA = $this->update($sqlInsMA, $arrMA);
        }

        if ($okInsMA) {
          $multialmacen_insertado = true;
        }
      }
    }

    return [
      'status' => true,
      'msg' => 'Proceso finalizado',
      'data' => [
        'idorden' => $idorden,
        'fecha_fin' => $fecha_fin,
        'estatus' => 3,
        'subot' => $subot,
        'numero_movimiento' => $numero_movimiento,
        'pendientes_misma_subot' => $pendientes,
        'movimiento_insertado' => $movimiento_insertado,
        'multialmacen_insertado' => $multialmacen_insertado
      ]
    ];
  }



  public function getStatusOTByPeid(int $peid)
  {
    $peid = (int) $peid;

    $sql = "SELECT
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.calidad,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid,
            ot.estampado
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE ot.planeacion_estacionid = {$peid}
          ORDER BY pe.orden ASC, ot.num_sub_orden ASC";

    return $this->select_all($sql);
  }

  public function getStatusOTByPlaneacion(int $planeacionid)
  {
    $planeacionid = (int) $planeacionid;

    $sql = "SELECT
            ot.idorden,
            ot.planeacion_estacionid,
            ot.num_sub_orden,
            ot.estatus,
            ot.calidad,
            ot.fecha_inicio,
            ot.fecha_fin,
            pe.orden AS estacion_orden,
            pe.planeacionid,
            ot.estampado
          FROM mrp_ordenes_trabajo ot
          INNER JOIN mrp_planeacion_estacion pe
            ON pe.id_planeacion_estacion = ot.planeacion_estacionid
          WHERE pe.planeacionid = {$planeacionid}
          ORDER BY ot.num_sub_orden ASC";

    return $this->select_all($sql);
  }





  public function selectOrdenesCalendar()
  {
    $rolId = isset($_SESSION['rolid']) ? (int) $_SESSION['rolid'] : 0;
    $isAdmin = in_array($rolId, [1, 5]); // 👈 ahora 1 y 5 ven todo
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
            pla.fecha_fin,
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
    $numorden = addslashes(trim((string) $numorden));
    $subot = addslashes(trim((string) $subot));

    $productoid = (int) $productoid;
    $estacionid = (int) $estacionid;
    $planeacionid = (int) $planeacionid;
    $after_id = (int) $after_id;

    $limit = (int) $limit;
    if ($limit <= 0)
      $limit = 200;
    if ($limit > 500)
      $limit = 500;


    if ($subot === '')
      return [];


    $where = "WHERE c.subot = '{$subot}'";


    if ($numorden !== '')
      $where .= " AND c.numorden = '{$numorden}'";

    if ($productoid > 0)
      $where .= " AND c.productoid = {$productoid}";
    if ($estacionid > 0)
      $where .= " AND c.estacionid = {$estacionid}";
    if ($planeacionid > 0)
      $where .= " AND c.planeacionid = {$planeacionid}";
    if ($after_id > 0)
      $where .= " AND c.idchat > {$after_id}";

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

    $sql = "INSERT INTO mrp_ot_chat
          (subot, user_id, user_name, message, created_at)
            VALUES (?, ?, ?, ?, NOW())";

    $arrData = [$subot, $user_id, $user_name, $message];


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


  public function getComponentesByProducto(int $productoid)
  {
    $sql = "SELECT idcomponente, almacenid, productoid, estacionid, inventarioid, cantidad
          FROM mrp_estacion_componentes
          WHERE productoid = $productoid AND estado = 2
          ORDER BY estacionid ASC, idcomponente ASC";

    $request = $this->select_all($sql);
    return $request;

  }



  public function getComponentesBySubensambles(int $productoid, array $subensambleIds)
  {
    $productoid = (int) $productoid;

    $subensambleIds = array_values(array_unique(array_map('intval', $subensambleIds)));
    $subensambleIds = array_filter($subensambleIds, fn($x) => $x > 0);

    if ($productoid <= 0 || empty($subensambleIds)) {
      return [];
    }

    $in = implode(',', $subensambleIds);

    $sql = "SELECT 
          idsubcomponente,
            almacenid,
            productoid,
            subensambleid,
            inventarioid,
            cantidad
          FROM mrp_subensamble_componentes
          WHERE productoid = {$productoid}
            AND subensambleid IN ({$in})
            AND estado = 2
          ORDER BY subensambleid ASC,idsubcomponente ASC";

    return $this->select_all($sql);
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

  //VAMOS A CREAR UNA FUNCIÓN PARA SABER LOS DÍAS FESTIVOS.
  public function getFestivosBetween(string $fromDate, string $toDate): array
  {
    // fromDate/toDate en Y-m-d
    $fromDate = substr(trim($fromDate), 0, 10);
    $toDate = substr(trim($toDate), 0, 10);

    $sql = "SELECT fecha
            FROM mrp_dias_festivos
            WHERE estado = 2
              AND fecha BETWEEN $fromDate AND $toDate
            ORDER BY fecha ASC";

    $rows = $this->select_all($sql);

    $set = [];
    foreach ($rows as $r) {
      $d = substr((string) ($r['fecha'] ?? ''), 0, 10);
      if ($d !== '')
        $set[$d] = true;
    }
    return $set;
  }

  public function addWorkingMinutesToDatetimeWithHolidays(
    string $fecha_inicio,
    float $minutes,
    array $workdays = [1, 2, 3, 4, 5],
    array $festivosSet = []
  ): string {
    $fecha_inicio = trim($fecha_inicio);
    if ($fecha_inicio === '')
      $fecha_inicio = date('Y-m-d H:i:s');

    $remaining = (int) round($minutes);
    $dt = new DateTime($fecha_inicio);

    $make = function (DateTime $base, string $hhmm): DateTime {
      $d = clone $base;
      [$h, $m] = array_map('intval', explode(':', $hhmm));
      $d->setTime($h, $m, 0);
      return $d;
    };

    $isHoliday = function (DateTime $d) use ($festivosSet): bool {
      $key = $d->format('Y-m-d');
      return isset($festivosSet[$key]);
    };

    $getBlocks = function (DateTime $base) use ($make) {
      $w1s = $make($base, '08:30');
      $w1e = $make($base, '13:30');
      $w2s = $make($base, '14:30');
      $w2e = $make($base, '18:30');
      return [
        [$w1s, $w1e],
        [$w2s, $w2e],
      ];
    };

    $moveToNextWorkdayStart = function (DateTime $base) use ($make, $workdays, $isHoliday) {
      $d = clone $base;
      do {
        $d->modify('+1 day');
      } while (!in_array((int) $d->format('N'), $workdays, true) || $isHoliday($d));
      return $make($d, '08:30');
    };

    $normalize = function (DateTime $base) use ($getBlocks, $moveToNextWorkdayStart, $workdays, $make, $isHoliday) {
      $d = clone $base;


      if (!in_array((int) $d->format('N'), $workdays, true) || $isHoliday($d)) {
        return $moveToNextWorkdayStart($d);
      }

      $blocks = $getBlocks($d);

      if ($d < $blocks[0][0])
        return $blocks[0][0];

      $lunchStart = $make($d, '13:30');
      $lunchEnd = $make($d, '14:30');
      if ($d >= $lunchStart && $d < $lunchEnd)
        return $lunchEnd;

      if ($d >= $blocks[1][1])
        return $moveToNextWorkdayStart($d);

      return $d;
    };

    $dt = $normalize($dt);

    while ($remaining > 0) {

      if (!in_array((int) $dt->format('N'), $workdays, true) || $isHoliday($dt)) {
        $dt = $moveToNextWorkdayStart($dt);
      }

      $blocks = $getBlocks($dt);
      $moved = false;

      foreach ($blocks as [$start, $end]) {
        if ($dt >= $end)
          continue;
        if ($dt < $start)
          $dt = clone $start;

        $avail = (int) floor(($end->getTimestamp() - $dt->getTimestamp()) / 60);
        if ($avail <= 0)
          continue;

        if ($remaining <= $avail) {
          $dt->modify('+' . $remaining . ' minutes');
          $remaining = 0;
          $moved = true;
          break;
        } else {
          $dt = clone $end;
          $remaining -= $avail;
          $moved = true;
        }
      }

      if ($remaining <= 0)
        break;


      if (!$moved || $dt >= $blocks[1][1]) {
        $dt = $moveToNextWorkdayStart($dt);
      } else {
        $dt = $normalize($dt);
      }
    }

    return $dt->format('Y-m-d H:i:s');
  }



  public function getTotalTiempoAjusteByEstaciones(array $estacionIds): float
  {
    if (empty($estacionIds))
      return 0;

    $estacionIds = array_values(array_unique(array_map('intval', $estacionIds)));
    $estacionIds = array_filter($estacionIds, fn($x) => $x > 0);
    if (empty($estacionIds))
      return 0;

    $in = implode(',', $estacionIds);

    $sql = "SELECT COALESCE(SUM(COALESCE(estandar,0)),0) AS total
            FROM mrp_estacion
            WHERE idestacion IN ($in)";

    $row = $this->select($sql);
    return (float) ($row['total'] ?? 0);
  }

  public function updateFechaFinPlaneacion(int $idplaneacion, string $fecha_fin): bool
  {
    $idplaneacion = (int) $idplaneacion;
    $fecha_fin = trim($fecha_fin);

    $sql = "UPDATE mrp_planeacion
            SET fecha_fin = ?
            WHERE idplaneacion = {$idplaneacion}
            LIMIT 1";

    return (bool) $this->update($sql, [$fecha_fin]);
  }





  public function updateExistenciaInventario($inventarioid, $almacenid, $cantidad)
  {

    $row = $this->select("SELECT existencia FROM wms_multialmacen WHERE inventarioid = $inventarioid AND almacenid = $almacenid");

    if (!$row)
      return false;

    $nuevaExistencia = $row['existencia'] - $cantidad;
    if ($nuevaExistencia < 0)
      $nuevaExistencia = 0;

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

  public function selectEspecificacionesByProductoSubensamble(int $productoid, int $estacionid): array
  {
    $sql = "SELECT
              idespecificacionsubensamble,
              productoid,
              subensambleid,
              especificacion,
              fecha_creacion
            FROM mrp_subensamble_especificaciones
            WHERE productoid = $productoid
              AND subensambleid = $estacionid
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

  public function selectComponentesByProductoSubensambles(int $productoid, int $estacionid): array
  {
    $sql = "SELECT
              c.idsubcomponente ,
              c.almacenid,
              c.productoid,
              c.subensambleid,
              c.inventarioid,
              c.cantidad,
              c.estado,
              c.fecha_creacion,
              inv.descripcion as componente
            FROM mrp_subensamble_componentes AS c
            INNER JOIN wms_inventario AS inv
            ON c.inventarioid = inv.idinventario
            WHERE c.productoid = $productoid
              AND c.subensambleid = $estacionid
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

  public function selectHerramientasByProductoSubensamble(int $productoid, int $estacionid): array
  {
    $sql = "SELECT
              h.idsubherramienta,
              h.almacenid,
              h.productoid,
              h.subensambleid,
              h.inventarioid,
              h.cantidad,
              h.estado,
              h.fecha_creacion,
              inv.descripcion as herramienta
            FROM mrp_subensamble_herramientas AS h
            INNER JOIN wms_inventario AS inv
            ON h.inventarioid = inv.idinventario
            WHERE h.productoid = $productoid
              AND h.subensambleid = $estacionid
              AND h.estado = 2
            ORDER BY h.fecha_creacion DESC";

    return $this->select_all($sql);
  }


  public function selectAyudasByProductoEstacion(int $productoid, int $estacionid): array
  {
    $sql = "SELECT*
            FROM mrp_estacion_ayudas_visuales AS h
            WHERE h.productoid = $productoid
              AND h.estacionid = $estacionid
              AND h.estado = 2
            ORDER BY h.fecha_creacion DESC";

    return $this->select_all($sql);
  }

  public function selectAyudasByProductoSubensamble(int $productoid, int $estacionid): array
  {
    $sql = "SELECT * FROM mrp_subensamble_ayudas_visuales AS h
            WHERE h.productoid = $productoid
              AND h.subensambleid = $estacionid
              AND h.estado = 2
            ORDER BY h.fecha_creacion DESC";

    return $this->select_all($sql);
  }

  public function saveInspeccionCalidadv1($h, $detalle, $evidencias)
  {
    $idorden = (int) $h['idorden'];
    $numot = (string) $h['numot'];
    $productoid = (int) $h['productoid'];
    $estacionid = (int) $h['estacionid'];
    $usuarioid = (int) $h['usuarioid'];
    $estado = (int) $h['estado']; // 1 pausada, 2 liberada


    $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
    $row = $this->select($sqlFind);
    $idinspeccion = isset($row['idinspeccion']) ? (int) $row['idinspeccion'] : 0;

    if ($idinspeccion <= 0) {
      $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
      $idinspeccion = (int) $this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

      if ($idinspeccion <= 0) {
        return ['status' => false, 'msg' => 'No se pudo crear la inspección.'];
      }
    } else {
      $sqlUp = "UPDATE mrp_calidad_inspeccion
              SET estado = ?, usuarioid = ?
              WHERE idinspeccion = $idinspeccion";

      $arrData = array($estado, $usuarioid);
      $this->update($sqlUp, $arrData);

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
      $especificacionid = (int) ($d['especificacionid'] ?? 0);
      $resultado = (string) ($d['resultado'] ?? '');
      $comentarioUI = trim((string) ($d['comentario'] ?? ''));

      if ($especificacionid <= 0)
        continue;
      if ($resultado !== 'OK' && $resultado !== 'NO_OK')
        continue;


      $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
      $rd = $this->select($sqlDetFind);
      $iddetalle = isset($rd['iddetalle']) ? (int) $rd['iddetalle'] : 0;
      $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string) $rd['comentario_no_ok']) : '';

      if ($iddetalle <= 0) {

        $comentario_no_ok = ($resultado === 'NO_OK') ? $comentarioUI : null;
        $accion_correctiva = ($resultado === 'OK') ? $comentarioUI : null; // opcional

        $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
        $iddetalle = (int) $this->insert($sqlDetIns, [
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

      if ($iddetalle <= 0)
        continue;


      if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
        foreach ($evidencias[$especificacionid] as $ev) {
          $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
          $this->insert($sqlEv, [
            $iddetalle,
            (string) ($ev['nombre_original'] ?? ''),
            (string) ($ev['archivo'] ?? ''),
            (string) ($ev['mime'] ?? ''),
            (int) ($ev['size_bytes'] ?? 0)
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
    $idorden = (int) $h['idorden'];
    $numot = (string) $h['numot'];
    $productoid = (int) $h['productoid'];
    $estacionid = (int) $h['estacionid'];
    $usuarioid = (int) $h['usuarioid'];
    $estado = (int) $h['estado'];


    $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
    $row = $this->select($sqlFind);
    $idinspeccion = isset($row['idinspeccion']) ? (int) $row['idinspeccion'] : 0;

    if ($idinspeccion <= 0) {

      $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
      $idinspeccion = (int) $this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

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
      $especificacionid = (int) ($d['especificacionid'] ?? 0);
      $resultado = (string) ($d['resultado'] ?? '');
      $comentarioUI = trim((string) ($d['comentario'] ?? ''));

      if ($especificacionid <= 0)
        continue;
      if ($resultado !== 'OK' && $resultado !== 'NO_OK')
        continue;


      $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
      $rd = $this->select($sqlDetFind);
      $iddetalle = isset($rd['iddetalle']) ? (int) $rd['iddetalle'] : 0;
      $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string) $rd['comentario_no_ok']) : '';

      if ($iddetalle <= 0) {

        $comentario_no_ok = ($resultado === 'NO_OK') ? $comentarioUI : null;
        $accion_correctiva = ($resultado === 'OK') ? $comentarioUI : null; // opcional

        $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
        $iddetalle = (int) $this->insert($sqlDetIns, [
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

      if ($iddetalle <= 0)
        continue;


      if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
        foreach ($evidencias[$especificacionid] as $ev) {
          $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
          $this->insert($sqlEv, [
            $iddetalle,
            (string) ($ev['nombre_original'] ?? ''),
            (string) ($ev['archivo'] ?? ''),
            (string) ($ev['mime'] ?? ''),
            (int) ($ev['size_bytes'] ?? 0)
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

    $idorden = (int) $h['idorden'];
    $numot = (string) $h['numot'];
    $productoid = (int) $h['productoid'];
    $estacionid = (int) $h['estacionid'];
    $usuarioid = (int) $h['usuarioid'];
    $estado = (int) $h['estado'];


    $sqlFind = "SELECT idinspeccion
              FROM mrp_calidad_inspeccion
              WHERE idorden = $idorden
                AND estacionid = $estacionid
              ORDER BY idinspeccion DESC
              LIMIT 1";
    $row = $this->select($sqlFind);
    $idinspeccion = isset($row['idinspeccion']) ? (int) $row['idinspeccion'] : 0;

    if ($idinspeccion <= 0) {

      $sqlIns = "INSERT INTO mrp_calidad_inspeccion (idorden, numot, productoid, estacionid, usuarioid, estado)
               VALUES (?, ?, ?, ?, ?, ?)";
      $idinspeccion = (int) $this->insert($sqlIns, [$idorden, $numot, $productoid, $estacionid, $usuarioid, $estado]);

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
      $especificacionid = (int) ($d['especificacionid'] ?? 0);
      $resultado = (string) ($d['resultado'] ?? '');
      $comentarioUI = trim((string) ($d['comentario'] ?? ''));

      if ($especificacionid <= 0)
        continue;
      if ($resultado !== 'OK' && $resultado !== 'NO_OK')
        continue;


      $sqlDetFind = "SELECT iddetalle, comentario_no_ok
                   FROM mrp_calidad_inspeccion_detalle
                   WHERE idinspeccion = $idinspeccion
                     AND especificacionid = $especificacionid
                   LIMIT 1";
      $rd = $this->select($sqlDetFind);
      $iddetalle = isset($rd['iddetalle']) ? (int) $rd['iddetalle'] : 0;
      $comentarioNoOkPrev = isset($rd['comentario_no_ok']) ? trim((string) $rd['comentario_no_ok']) : '';

      if ($iddetalle <= 0) {

        $comentario_no_ok = ($resultado === 'NO_OK') ? $comentarioUI : null;
        $accion_correctiva = ($resultado === 'OK') ? $comentarioUI : null; // opcional

        $sqlDetIns = "INSERT INTO mrp_calidad_inspeccion_detalle
                      (idinspeccion, especificacionid, resultado, comentario_no_ok, accion_correctiva)
                    VALUES (?, ?, ?, ?, ?)";
        $iddetalle = (int) $this->insert($sqlDetIns, [
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

      if ($iddetalle <= 0)
        continue;


      if (!empty($evidencias[$especificacionid]) && is_array($evidencias[$especificacionid])) {
        foreach ($evidencias[$especificacionid] as $ev) {
          $sqlEv = "INSERT INTO mrp_calidad_inspeccion_evidencia
                  (iddetalle, nombre_original, archivo, mime, size_bytes)
                  VALUES (?, ?, ?, ?, ?)";
          $this->insert($sqlEv, [
            $iddetalle,
            (string) ($ev['nombre_original'] ?? ''),
            (string) ($ev['archivo'] ?? ''),
            (string) ($ev['mime'] ?? ''),
            (int) ($ev['size_bytes'] ?? 0)
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
    $idorden = (int) $idorden;
    $estacionid = (int) $estacionid;


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

    $idinspeccion = (int) $ins['idinspeccion'];


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
        $idd = (int) ($e['iddetalle'] ?? 0);
        if ($idd <= 0)
          continue;
        if (!isset($evByDet[$idd]))
          $evByDet[$idd] = [];
        $evByDet[$idd][] = [
          'nombre_original' => (string) ($e['nombre_original'] ?? ''),
          'archivo' => (string) ($e['archivo'] ?? ''),
          'mime' => (string) ($e['mime'] ?? ''),
          'size_bytes' => (int) ($e['size_bytes'] ?? 0),
        ];
      }
    }

    $outDet = [];
    if (is_array($det)) {
      foreach ($det as $d) {
        $iddetalle = (int) ($d['iddetalle'] ?? 0);
        $resultado = (string) ($d['resultado'] ?? '');
        $comentario = '';

        if ($resultado === 'NO_OK') {
          $comentario = trim((string) ($d['comentario_no_ok'] ?? ''));
        } else if ($resultado === 'OK') {
          $comentario = trim((string) ($d['accion_correctiva'] ?? ''));
        }

        $outDet[] = [
          'iddetalle' => $iddetalle,
          'especificacionid' => (int) ($d['especificacionid'] ?? 0),
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
        'estado' => (int) ($ins['estado'] ?? 0),
        'detalle' => $outDet
      ]
    ];
  }

  public function getViewInspeccionCalidad($idorden, $estacionid)
  {
    $idorden = (int) $idorden;
    $estacionid = (int) $estacionid;

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
            'idinspeccion' => 0,
            'idorden' => $idorden,
            'numot' => '',
            'productoid' => 0,
            'estacionid' => $estacionid,
            'estado' => 0,
            'fecha_creacion' => null,
            'fecha_cierre' => null,
            'usuarioid' => 0,
            'nombres' => '',
            'apellidos' => '',
            'email_user' => ''
          ],
          'detalle' => []
        ]
      ];
    }

    $idinspeccion = (int) $ins['idinspeccion'];
    $productoid = (int) ($ins['productoid'] ?? 0);


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
        $idd = (int) ($e['iddetalle'] ?? 0);
        if ($idd <= 0)
          continue;

        if (!isset($evByDet[$idd]))
          $evByDet[$idd] = [];
        $evByDet[$idd][] = [
          'nombre_original' => (string) ($e['nombre_original'] ?? ''),
          'archivo' => (string) ($e['archivo'] ?? ''),
          'mime' => (string) ($e['mime'] ?? ''),
          'size_bytes' => (int) ($e['size_bytes'] ?? 0),
        ];
      }
    }


    $outDet = [];
    if (is_array($det)) {
      foreach ($det as $d) {
        $iddetalle = (int) ($d['iddetalle'] ?? 0);
        $resultado = (string) ($d['resultado'] ?? '');

        $comentarioNoOk = trim((string) ($d['comentario_no_ok'] ?? ''));
        $accionCorr = trim((string) ($d['accion_correctiva'] ?? ''));


        $comentarioUI = '';
        if ($resultado === 'NO_OK') {
          $comentarioUI = $comentarioNoOk;
        } elseif ($resultado === 'OK') {
          $comentarioUI = $accionCorr;
        }

        $outDet[] = [
          'iddetalle' => $iddetalle,
          'especificacionid' => (int) ($d['especificacionid'] ?? 0),


          'especificacion' => (string) ($d['especificacion'] ?? ''),
          'fecha_especificacion' => (string) ($d['fecha_especificacion'] ?? ''),

          'resultado' => $resultado,


          'comentario_no_ok' => $comentarioNoOk,
          'accion_correctiva' => $accionCorr,


          'comentario_ui' => $comentarioUI,

          'evidencias' => $evByDet[$iddetalle] ?? []
        ];
      }
    }


    $header = [
      'idinspeccion' => $idinspeccion,
      'idorden' => (int) ($ins['idorden'] ?? 0),
      'numot' => (string) ($ins['numot'] ?? ''),
      'productoid' => $productoid,
      'estacionid' => (int) ($ins['estacionid'] ?? 0),

      'estado' => (int) ($ins['estado'] ?? 0),
      'fecha_creacion' => (string) ($ins['fecha_creacion'] ?? ''),
      'fecha_cierre' => (string) ($ins['fecha_cierre'] ?? ''),

      'usuarioid' => (int) ($ins['usuarioid'] ?? 0),
      'nombres' => (string) ($ins['nombres'] ?? ''),
      'apellidos' => (string) ($ins['apellidos'] ?? ''),
      'email_user' => (string) ($ins['email_user'] ?? ''),
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

  public function selectDatesDisponibles()
  {
    $sql = "SELECT * FROM  mrp_planeacion";
    $request = $this->select_all($sql);
    return $request;
  }


  public function iniciarPlaneacionModel(int $idplaneacion, int $usuarioid)
  {

    $ESTADO_EN_PRODUCCION = 3;


    $sqlCheck = "SELECT idplaneacion, fase
               FROM mrp_planeacion
               WHERE idplaneacion = ?
               LIMIT 1";
    $row = $this->select($sqlCheck, [$idplaneacion]);

    if (empty($row))
      return false;

    if ((int) $row['fase'] == $ESTADO_EN_PRODUCCION) {
      return false;
    }


    $sqlUp = "UPDATE mrp_planeacion
            SET fase = ?,
                fecha_inicio_real = NOW(),
                usuario_inicio = ?
            WHERE idplaneacion = ?
            LIMIT 1";

    $res = $this->update($sqlUp, [$ESTADO_EN_PRODUCCION, $usuarioid, $idplaneacion]);

    return ($res !== false);
  }


  public function finalizarPlaneacionModel(int $idplaneacion, int $usuarioid)
  {
    $FASE_FINALIZADA = 5;

    $sqlPlaneacion = "SELECT idplaneacion, fase
                      FROM mrp_planeacion
                      WHERE idplaneacion = ?
                      LIMIT 1";

    $planeacion = $this->select($sqlPlaneacion, [$idplaneacion]);

    if (empty($planeacion)) {
      return [
        "status" => false,
        "msg" => "La planeación no existe."
      ];
    }

    if ((int) $planeacion['fase'] === $FASE_FINALIZADA) {
      return [
        "status" => false,
        "msg" => "Esta producción ya se encuentra finalizada."
      ];
    }

    $sqlPendientes = "SELECT COUNT(*) AS total
                      FROM mrp_ordenes_trabajo ot
                      INNER JOIN mrp_planeacion_estacion pe
                          ON pe.id_planeacion_estacion = ot.planeacion_estacionid
                      WHERE pe.planeacionid = ?
                      AND ot.estatus <> 3";

    $pendientes = $this->select($sqlPendientes, [$idplaneacion]);

    $totalPendientes = (int) ($pendientes['total'] ?? 0);

    if ($totalPendientes > 0) {
      return [
        "status" => false,
        "msg" => "Aún tienes unidades pendientes por finalizar en esta orden de producción, no la puedes finalizar ahora. Verifica."
      ];
    }

    $sqlUpdate = "UPDATE mrp_planeacion
                  SET fase = ?,
                      fecha_fin_real = NOW(),
                      usuario_fin = ?
                  WHERE idplaneacion = ?
                  LIMIT 1";

    $update = $this->update($sqlUpdate, [
      $FASE_FINALIZADA,
      $usuarioid,
      $idplaneacion
    ]);

    if ($update === false) {
      return [
        "status" => false,
        "msg" => "No fue posible finalizar la producción."
      ];
    }

    return [
      "status" => true,
      "msg" => "Producción finalizada correctamente."
    ];
  }

  public function getEstampadoOrdenTrabajo(int $idorden): int
  {
    $sql = "SELECT estampado
            FROM mrp_ordenes_trabajo
            WHERE idorden = ?
            LIMIT 1";

    $request = $this->select($sql, [$idorden]);

    if (empty($request)) {
      return 0;
    }

    return intval($request['estampado'] ?? 0);
  }



  public function getVinesDisponiblesByReferencia(string $referencia)
  {
    $sql = "SELECT id_numeros_serie, numero_serie
          FROM wms_numeros_series
          WHERE referencia = ?
            AND estado = 1
          ORDER BY id_numeros_serie DESC";

    return $this->select_all($sql, [$referencia]);
  }


  public function insertVinAsignacion(
    int $ordenId,
    string $numUnidad,
    int $numeroSerieId,
    string $numeroMotor,
    string $vinOrigen,
    string $numeroTransmision,
    int $usuarioId,
    string $fecha
  ) { 

    $sql = "INSERT INTO mrp_vin_asignaciones
                (
                    orden_trabajo_id,
                    num_unidad,
                    numero_serie_id,
                    numero_motor,
                    vin_origen,
                    numero_transmision,
                    usuario_id,
                    fecha_asignacion,
                    estado
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, 1)";

    return $this->insert($sql, [
      $ordenId,
      $numUnidad,
      $numeroSerieId,
      $numeroMotor,
      $vinOrigen,
      $numeroTransmision,
      $usuarioId,
      $fecha
    ]);
  }



  public function setEstatusEstampadoVin(int $idorden)
  {

    $sql = "UPDATE mrp_ordenes_trabajo
            SET estampado = ?
            WHERE idorden = $idorden";

    $arrData = array(2);
    $req = $this->update($sql, $arrData);
    return $req;
  }

  public function setEstadoNumeroSerie(int $numeroSerieId, int $estado): bool
  {

    $sql = "UPDATE wms_numeros_series
            SET estado = ?
            WHERE id_numeros_serie = ?
            LIMIT 1";
    $req = $this->update($sql, [$estado, $numeroSerieId]);
    return ($req > 0);
  }

  public function existeAsignacionActiva(int $ordenId): bool
  {
    $sql = "SELECT estampado
            FROM mrp_ordenes_trabajo
            WHERE idorden = $ordenId
            AND estampado=2
            LIMIT 1";

    $request = $this->select($sql);

    return !empty($request);
  }

  public function getVinAsignadoPorOrden(int $idorden)
  {
    $sql = "SELECT
            a.idasignacion,
            a.orden_trabajo_id,
            a.numero_serie_id,
            a.numero_motor,
            a.fecha_asignacion,
            a.estado AS estado_asignacion,

            ot.idorden,
            ot.num_sub_orden,
            ot.planeacion_estacionid,
            ot.estampado,

            ns.id_numeros_serie,
            ns.numero_serie AS vin,
            ns.referencia,

            u.idusuario,
            CONCAT(u.nombres,' ',u.apellidos) AS usuario_asigno,
            u.numcolaborador

        FROM mrp_vin_asignaciones a
        INNER JOIN mrp_ordenes_trabajo ot
            ON ot.idorden = a.orden_trabajo_id
        INNER JOIN wms_numeros_series ns
            ON ns.id_numeros_serie = a.numero_serie_id
        INNER JOIN usuarios u
            ON u.idusuario = a.usuario_id

        WHERE a.orden_trabajo_id =$idorden 
          AND a.estado = 1
        ORDER BY a.idasignacion DESC
        LIMIT 1
    ";

    return $this->select($sql, );
  }


  //nuevas funciones de subensamble

  public function getSubensambleInfoById(int $idsubensamble)
  {
    $idsubensamble = (int) $idsubensamble;

    $sql = "SELECT 
            se.idsubensamble,
            se.estacionid,
            se.nombre_estacion,
            se.proceso,
            se.estandar
          FROM mrp_estacion_subensamble AS se
          WHERE se.idsubensamble = {$idsubensamble}
            AND se.estado = 2
          LIMIT 1";

    return $this->select($sql);
  }

  public function upsertPlaneacionSubensamble($planeacionid, $planeacionEstacionId, $estacionid, $subensambleid, $ordenSub)
  {
    $planeacionid = (int) $planeacionid;
    $planeacionEstacionId = (int) $planeacionEstacionId;
    $estacionid = (int) $estacionid;
    $subensambleid = (int) $subensambleid;
    $ordenSub = trim((string) $ordenSub);

    $sqlFind = "SELECT id_planeacion_subensamble
              FROM mrp_planeacion_subensamble
              WHERE planeacionid = {$planeacionid}
                AND subensambleid = {$subensambleid}
                AND estado = 2
              LIMIT 1";

    $row = $this->select($sqlFind);

    if (!empty($row['id_planeacion_subensamble'])) {
      $id = (int) $row['id_planeacion_subensamble'];

      $sqlUpd = "UPDATE mrp_planeacion_subensamble
               SET planeacion_estacionid = ?,
                   estacionid = ?,
                   orden_sub = ?
               WHERE id_planeacion_subensamble = {$id}";

      $request = $this->update($sqlUpd, [
        $planeacionEstacionId,
        $estacionid,
        $ordenSub
      ]);

      return $id > 0 ? $id : 0;
    }

    $sqlIns = "INSERT INTO mrp_planeacion_subensamble
            (planeacionid, planeacion_estacionid, estacionid, subensambleid, orden_sub, estado)
            VALUES (?,?,?,?,?,2)";

    return $this->insert($sqlIns, [
      $planeacionid,
      $planeacionEstacionId,
      $estacionid,
      $subensambleid,
      $ordenSub
    ]);
  }
  public function clearOperadoresByPlaneacionSubensamble($planeacionSubensambleId)
  {
    $planeacionSubensambleId = (int) $planeacionSubensambleId;

    $sql = "UPDATE mrp_planeacion_subensamble_operador
          SET estado = 0
          WHERE planeacion_subensambleid = {$planeacionSubensambleId}";
    return $this->update($sql, []);
  }


  public function insertPlaneacionSubensambleOperador($planeacionSubensambleId, $usuarioid, $rol)
  {
    $sql = "INSERT INTO mrp_planeacion_subensamble_operador
          (planeacion_subensambleid, usuarioid, rol, estado)
          VALUES (?,?,?,2)";
    return $this->insert($sql, [$planeacionSubensambleId, $usuarioid, $rol]);
  }

  public function insertOrdenesSubensamble(int $planeacionSubensambleId, string $numSubOrden, string $codigoScan = '')
  {
    $sql = "INSERT INTO mrp_ordenes_trabajo_subensamble
          (planeacion_subensambleid, num_sub_orden, codigo_scan)
          VALUES (?, ?, ?)";

    return $this->insert($sql, [
      $planeacionSubensambleId,
      $numSubOrden,
      $codigoScan
    ]);
  }

  public function getTotalTiempoAjusteBySubensambles(array $subensambleIds): float
  {
    if (empty($subensambleIds))
      return 0;

    $subensambleIds = array_values(array_unique(array_map('intval', $subensambleIds)));
    $subensambleIds = array_filter($subensambleIds, fn($x) => $x > 0);
    if (empty($subensambleIds))
      return 0;

    $in = implode(',', $subensambleIds);

    $sql = "SELECT COALESCE(SUM(COALESCE(estandar,0)),0) AS total
          FROM mrp_estacion_subensamble
          WHERE idsubensamble IN ($in)";

    $row = $this->select($sql);
    return (float) ($row['total'] ?? 0);
  }






  ///////////////////////////////////////////////////////////////
  ////////// NUEVAS FUNCIONES PARA NUEVA INFRAESTRUCTURA ////////
  //////////////////////////////////////////////////////////////

  public function iniciarOrdenSubensamble(int $idordenSubensamble, int $usuarioid)
  {
    $idordenSubensamble = (int) $idordenSubensamble;
    $usuarioid = (int) $usuarioid;

    if ($idordenSubensamble <= 0) {
      return [
        'status' => false,
        'msg' => 'Id de orden de subensamble inválido.'
      ];
    }

    if ($usuarioid <= 0) {
      return [
        'status' => false,
        'msg' => 'Usuario inválido.'
      ];
    }

    $orden = $this->getDetalleOrdenSubensamble($idordenSubensamble);

    if (empty($orden)) {
      return [
        'status' => false,
        'msg' => 'No se encontró la orden de subensamble.'
      ];
    }

    $fase = (int) ($orden['fase'] ?? 0);
    if ($fase !== 3) {
      return [
        'status' => false,
        'msg' => 'El supervisor debe iniciar toda la producción para poder iniciar este proceso.'
      ];
    }

    $estadoActual = (int) ($orden['estado'] ?? 0);

    if ($estadoActual === 2) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya se encuentra en proceso.'
      ];
    }

    if (in_array($estadoActual, [3, 4], true)) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue trabajada previamente.'
      ];
    }

    if ($estadoActual !== 1) {
      return [
        'status' => false,
        'msg' => 'La unidad no se encuentra en un estado válido para iniciar.'
      ];
    }

    $planeacionSubensambleid = (int) ($orden['planeacion_subensambleid'] ?? 0);
    $numSubOrden = (string) ($orden['num_sub_orden'] ?? '');

    if ($planeacionSubensambleid <= 0 || $numSubOrden === '') {
      return [
        'status' => false,
        'msg' => 'La orden de subensamble no tiene información suficiente para validarse.'
      ];
    }

    $existeEnProceso = $this->existeUnidadEnProcesoSubensamble($planeacionSubensambleid, $idordenSubensamble);
    if ($existeEnProceso) {
      return [
        'status' => false,
        'msg' => 'Ya existe una unidad en proceso en este subensamble. Debes finalizarla antes de iniciar otra.'
      ];
    }


    $ordenAnterior = $this->getOrdenAnteriorSubensamble($planeacionSubensambleid, $numSubOrden);

    if (!empty($ordenAnterior)) {
      $estadoAnterior = (int) ($ordenAnterior['estado'] ?? 0);

      if (!in_array($estadoAnterior, [3, 4], true)) {
        return [
          'status' => false,
          'msg' => 'Debes respetar el orden de las unidades antes de iniciar este procesoooo.'
        ];
      }
    }

        $fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');


    // $sqlUpdate = "UPDATE mrp_ordenes_trabajo_subensamble
    //               SET estado = ?, fecha_inicio_real = NOW()
    //               WHERE idorden_subensamble = ? AND estado = 1";

        $sqlUpdate = "UPDATE mrp_ordenes_trabajo_subensamble
                  SET estado = ?, fecha_inicio_real = ?
                  WHERE idorden_subensamble = ? AND estado = 1";

    // $arrUpdate = [2, $idordenSubensamble];

     $arrUpdate = [
        2,
        $fechaMexico,
        $idordenSubensamble
    ];
    $updated = $this->update($sqlUpdate, $arrUpdate);

    if (!$updated) {
      return [
        'status' => false,
        'msg' => 'No fue posible iniciar la orden de subensamble.'
      ];
    }


    $observacion = 'Inicio de subensamble ' . $numSubOrden;

    $evento = $this->registrarEventoProduccion([
      'tipo_origen' => 'SUBENSAMBLE',
      'orden_trabajoid' => null,
      'orden_subensambleid' => $idordenSubensamble,
      'planeacion_estacionid' => null,
      'planeacion_subensambleid' => $planeacionSubensambleid,
      'usuarioid' => $usuarioid,
      'accion' => 'INICIAR',
      'observaciones' => $observacion,
      'estado' => 2
    ]);

    if (!$evento) {
      return [
        'status' => false,
        'msg' => 'La orden se inició, pero no se pudo registrar el evento de producción.'
      ];
    }


    $ordenActualizada = $this->getDetalleOrdenSubensamble($idordenSubensamble);

    return [
      'status' => true,
      'msg' => 'Subensamble iniciado correctamente.',
      'data' => [
        'orden' => $ordenActualizada
      ]
    ];
  }



  private function getDetalleOrdenSubensamble(int $idordenSubensamble)
  {
    $idordenSubensamble = (int) $idordenSubensamble;

    $sql = "SELECT 
                ots.idorden_subensamble,
                ots.planeacion_subensambleid,
                ots.num_sub_orden,
                ots.codigo_scan,
                ots.estado,
                ots.fecha_inicio_real,
                ots.fecha_fin_real,
                ots.fecha_creacion,

                ps.id_planeacion_subensamble,
                ps.planeacionid,
                ps.planeacion_estacionid,
                ps.estacionid,
                ps.subensambleid,
                ps.orden_sub,
                ps.estado AS estado_planeacion_subensamble,

                pla.num_orden,
                pla.fase,
                pla.productoid,
                pla.supervisorid,
                pla.cantidad,

                sub.nombre_estacion AS nombre_subensamble,
                sub.proceso AS proceso_subensamble,
                sub.estandar,
                sub.tiempo_ajuste
            FROM mrp_ordenes_trabajo_subensamble ots
            INNER JOIN mrp_planeacion_subensamble ps
                ON ps.id_planeacion_subensamble = ots.planeacion_subensambleid
            INNER JOIN mrp_planeacion pla
                ON pla.idplaneacion = ps.planeacionid
            LEFT JOIN mrp_estacion_subensamble sub
                ON sub.idsubensamble = ps.subensambleid
            WHERE ots.idorden_subensamble = {$idordenSubensamble}
            LIMIT 1";

    return $this->select($sql);
  }



  private function existeUnidadEnProcesoSubensamble(int $planeacionSubensambleid, int $excludeId = 0)
  {
    $planeacionSubensambleid = (int) $planeacionSubensambleid;
    $excludeId = (int) $excludeId;

    $whereExclude = $excludeId > 0 ? " AND idorden_subensamble <> {$excludeId}" : "";

    $sql = "SELECT idorden_subensamble
            FROM mrp_ordenes_trabajo_subensamble
            WHERE planeacion_subensambleid = {$planeacionSubensambleid}
              AND estado = 2
              {$whereExclude}
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }


  private function getOrdenAnteriorSubensamble(int $planeacionSubensambleid, string $numSubOrden)
  {
    $planeacionSubensambleid = (int) $planeacionSubensambleid;
    $numSubOrden = trim($numSubOrden);

    if ($planeacionSubensambleid <= 0 || $numSubOrden === '') {
      return [];
    }

    $sql = "SELECT 
                idorden_subensamble,
                planeacion_subensambleid,
                num_sub_orden,
                estado,
                CAST(SUBSTRING_INDEX(num_sub_orden, 'U', -1) AS UNSIGNED) AS orden_unidad
            FROM mrp_ordenes_trabajo_subensamble
            WHERE planeacion_subensambleid = {$planeacionSubensambleid}
              AND CAST(SUBSTRING_INDEX(num_sub_orden, 'U', -1) AS UNSIGNED) <
                  CAST(SUBSTRING_INDEX('{$numSubOrden}', 'U', -1) AS UNSIGNED)
            ORDER BY orden_unidad DESC
            LIMIT 1";

    return $this->select($sql);
  }


  private function registrarEventoProduccion(array $data)
  {
    $tipo_origen = trim((string) ($data['tipo_origen'] ?? ''));
    $orden_trabajoid = isset($data['orden_trabajoid']) ? (int) $data['orden_trabajoid'] : null;
    $orden_subensambleid = isset($data['orden_subensambleid']) ? (int) $data['orden_subensambleid'] : null;
    $planeacion_estacionid = isset($data['planeacion_estacionid']) ? (int) $data['planeacion_estacionid'] : null;
    $planeacion_subensambleid = isset($data['planeacion_subensambleid']) ? (int) $data['planeacion_subensambleid'] : null;
    $usuarioid = (int) ($data['usuarioid'] ?? 0);
    $accion = trim((string) ($data['accion'] ?? ''));
    $observaciones = trim((string) ($data['observaciones'] ?? ''));
    $estado = (int) ($data['estado'] ?? 2);

    if ($tipo_origen === '' || $usuarioid <= 0 || $accion === '') {
      return false;
    }

    $sql = "INSERT INTO mrp_produccion_evento
            (
                tipo_origen,
                orden_trabajoid,
                orden_subensambleid,
                planeacion_estacionid,
                planeacion_subensambleid,
                usuarioid,
                accion,
                observaciones,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $arrData = [
      $tipo_origen,
      $orden_trabajoid,
      $orden_subensambleid,
      $planeacion_estacionid,
      $planeacion_subensambleid,
      $usuarioid,
      $accion,
      $observaciones,
      $estado
    ];

    return $this->insert($sql, $arrData);
  }

  // FUNCIÓN PARA FINALIZAR UN SUBENSAMBLE 

  public function finalizarOrdenSubensamble(int $idordenSubensamble, int $usuarioid)
  {
    $idordenSubensamble = (int) $idordenSubensamble;
    $usuarioid = (int) $usuarioid;

    if ($idordenSubensamble <= 0) {
      return [
        'status' => false,
        'msg' => 'Id de orden de subensamble inválido.'
      ];
    }

    if ($usuarioid <= 0) {
      return [
        'status' => false,
        'msg' => 'Usuario inválido.'
      ];
    }


    $orden = $this->getDetalleOrdenSubensamble($idordenSubensamble);

    if (empty($orden)) {
      return [
        'status' => false,
        'msg' => 'No se encontró la orden de subensamble.'
      ];
    }


    $fase = (int) ($orden['fase'] ?? 0);
    if ($fase !== 3) {
      return [
        'status' => false,
        'msg' => 'El supervisor debe iniciar toda la producción para poder finalizar este proceso.'
      ];
    }


    $estadoActual = (int) ($orden['estado'] ?? 0);

    if ($estadoActual === 1) {
      return [
        'status' => false,
        'msg' => 'No puedes finalizar este subensamble porque aún no ha sido iniciado.'
      ];
    }

    if ($estadoActual === 4) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue entregada a la estación.'
      ];
    }

    if ($estadoActual === 3) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue finalizada previamente.'
      ];
    }

    if ($estadoActual !== 2) {
      return [
        'status' => false,
        'msg' => 'La unidad no se encuentra en un estado válido para finalizar.'
      ];
    }

    $planeacionSubensambleid = (int) ($orden['planeacion_subensambleid'] ?? 0);
    $numSubOrden = (string) ($orden['num_sub_orden'] ?? '');

     $fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');


    $sqlUpdate = "UPDATE mrp_ordenes_trabajo_subensamble
                  SET estado = ?, fecha_fin_real = ?
                  WHERE idorden_subensamble = ? AND estado = 2";

    // $arrUpdate = [4, $idordenSubensamble];

       $arrUpdate = [
        4,
        $fechaMexico,
        $idordenSubensamble
    ];


    $updated = $this->update($sqlUpdate, $arrUpdate);

    if (!$updated) {
      return [
        'status' => false,
        'msg' => 'No fue posible finalizar la orden de subensamble.'
      ];
    }


    $observacion = 'Finalización y entrega de subensamble ' . $numSubOrden;

    $evento = $this->registrarEventoProduccion([
      'tipo_origen' => 'SUBENSAMBLE',
      'orden_trabajoid' => null,
      'orden_subensambleid' => $idordenSubensamble,
      'planeacion_estacionid' => null,
      'planeacion_subensambleid' => $planeacionSubensambleid,
      'usuarioid' => $usuarioid,
      'accion' => 'FINALIZAR',
      'observaciones' => $observacion,
      'estado' => 4
    ]);

    if (!$evento) {
      return [
        'status' => false,
        'msg' => 'La orden se finalizó, pero no se pudo registrar el evento de producción.'
      ];
    }


    $ordenActualizada = $this->getDetalleOrdenSubensamble($idordenSubensamble);

    return [
      'status' => true,
      'msg' => 'Subensamble finalizado y entregado correctamente.',
      'data' => [
        'orden' => $ordenActualizada
      ]
    ];
  }



  public function iniciarOrdenEstacion(int $idorden, int $usuarioid)
  {
    $idorden = (int) $idorden;
    $usuarioid = (int) $usuarioid;

    if ($idorden <= 0) {
      return [
        'status' => false,
        'msg' => 'Id de orden de estación inválido.'
      ];
    }

    if ($usuarioid <= 0) {
      return [
        'status' => false,
        'msg' => 'Usuario inválido.'
      ];
    }


    $orden = $this->getDetalleOrdenEstacion($idorden);

    if (empty($orden)) {
      return [
        'status' => false,
        'msg' => 'No se encontró la orden de estación.'
      ];
    }


    $fase = (int) ($orden['fase'] ?? 0);
    if ($fase !== 3) {
      return [
        'status' => false,
        'msg' => 'El supervisor debe iniciar toda la producción para poder iniciar este proceso.'
      ];
    }


    $estatusActual = (int) ($orden['estatus'] ?? 0);

    if ($estatusActual === 2) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya se encuentra en proceso en esta estación.'
      ];
    }

    if ($estatusActual === 3) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue finalizada previamente en esta estación.'
      ];
    }

    if ($estatusActual !== 1) {
      return [
        'status' => false,
        'msg' => 'La unidad no se encuentra en un estado válido para iniciar.'
      ];
    }

    $planeacionEstacionid = (int) ($orden['planeacion_estacionid'] ?? 0);
    $planeacionid = (int) ($orden['planeacionid'] ?? 0);
    $ordenRuta = (int) ($orden['orden_ruta'] ?? 0);
    $numSubOrden = (string) ($orden['num_sub_orden'] ?? '');

    if ($planeacionEstacionid <= 0 || $planeacionid <= 0 || $numSubOrden === '') {
      return [
        'status' => false,
        'msg' => 'La orden de estación no tiene información suficiente para validarse.'
      ];
    }


    $existeEnProceso = $this->existeUnidadEnProcesoEstacion($planeacionEstacionid, $idorden);

    if ($existeEnProceso) {
      return [
        'status' => false,
        'msg' => 'Ya existe una unidad en proceso en esta estación. Debes finalizarla antes de iniciar otra.'
      ];
    }



    $ordenAnteriorMismaEstacion = $this->getOrdenAnteriorEstacionMismaRuta(
      $planeacionEstacionid,
      $numSubOrden
    );

    if (!empty($ordenAnteriorMismaEstacion)) {

      $unidadAnterior = (string) ($ordenAnteriorMismaEstacion['num_sub_orden'] ?? '');
      $estatusAnterior = (int) ($ordenAnteriorMismaEstacion['estatus'] ?? 0);

      $unidadAnteriorFlujoEspecial =
        $this->unidadFueraLineaOReincorporadaEnRutaAnterior(
          $planeacionid,
          $unidadAnterior,
          $ordenRuta
        );

      if (!$unidadAnteriorFlujoEspecial && $estatusAnterior !== 3) {
        return [
          'status' => false,
          'msg' => 'Debes respetar el orden de las unidades antes de iniciar este proceso.'
        ];
      }
    }


    $estacionAnterior = $this->getPlaneacionEstacionAnterior($planeacionid, $ordenRuta);

    if (!empty($estacionAnterior)) {
      $planeacionEstacionAnteriorid = (int) ($estacionAnterior['id_planeacion_estacion'] ?? 0);

      $unidadFinalizadaAnterior = $this->isUnidadFinalizadaEnEstacionAnterior(
        $planeacionEstacionAnteriorid,
        $numSubOrden
      );

      if (!$unidadFinalizadaAnterior) {
        return [
          'status' => false,
          'msg' => 'No puedes iniciar esta estación porque la estación anterior aún no ha liberado la unidad.'
        ];
      }
    }


    $tieneSubensamble = $this->estacionTieneSubensamblePlaneado($planeacionEstacionid);

    if ($tieneSubensamble) {
      $subensambleEntregado = $this->isUnidadEntregadaPorSubensamble(
        $planeacionEstacionid,
        $numSubOrden
      );

      if (!$subensambleEntregado) {
        return [
          'status' => false,
          'msg' => 'No puedes iniciar esta estación porque el subensamble aún no ha entregado la unidad.'
        ];
      }
    }

$fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');
    $sqlUpdate = "UPDATE mrp_ordenes_trabajo
                  SET estatus = ?, fecha_inicio = ?
                  WHERE idorden = ? AND estatus = 1";

    // $arrUpdate = [2, $idorden];

     $arrUpdate = [
        2,
        $fechaMexico,
        $idorden
    ];
    $updated = $this->update($sqlUpdate, $arrUpdate);

    if (!$updated) {
      return [
        'status' => false,
        'msg' => 'No fue posible iniciar la orden de estación.'
      ];
    }


    $observacion = 'Inicio de estación ' . ($orden['nombre_estacion'] ?? '') . ' unidad ' . $numSubOrden;

    $evento = $this->registrarEventoProduccion([
      'tipo_origen' => 'ESTACION',
      'orden_trabajoid' => $idorden,
      'orden_subensambleid' => null,
      'planeacion_estacionid' => $planeacionEstacionid,
      'planeacion_subensambleid' => null,
      'usuarioid' => $usuarioid,
      'accion' => 'INICIAR',
      'observaciones' => $observacion,
      'estado' => 2
    ]);

    if (!$evento) {
      return [
        'status' => false,
        'msg' => 'La orden se inició, pero no se pudo registrar el evento de producción.'
      ];
    }


    $ordenActualizada = $this->getDetalleOrdenEstacion($idorden);

    return [
      'status' => true,
      'msg' => 'Estación iniciada correctamente.',
      'data' => [
        'orden' => $ordenActualizada
      ]
    ];
  }


  private function getDetalleOrdenEstacion(int $idorden)
  {

    $idorden = (int) $idorden;

    $sql = "SELECT
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
                pe.orden AS orden_ruta,
                pe.estado AS estado_planeacion_estacion,
                pe.estampado AS estampado_planeacion,
                pe.calidad AS calidad_planeacion,

                pla.idplaneacion,
                pla.num_orden,
                pla.productoid,
                pla.fase,
                pla.cantidad,
                pla.supervisorid,
                pla.plantaid,

                est.idestacion,
                est.cve_estacion,
                est.nombre_estacion,
                est.proceso,
                est.estandar,
                est.unidad_medida,
                est.tiempo_ajuste,
                est.tiene_subensamble,
                est.estado AS estado_estacion
            FROM mrp_ordenes_trabajo ot
            INNER JOIN mrp_planeacion_estacion pe
                ON pe.id_planeacion_estacion = ot.planeacion_estacionid
            INNER JOIN mrp_planeacion pla
                ON pla.idplaneacion = pe.planeacionid
            INNER JOIN mrp_estacion est
                ON est.idestacion = pe.estacionid
            WHERE ot.idorden = {$idorden}
            LIMIT 1";

    return $this->select($sql);
  }


  private function existeUnidadEnProcesoEstacion(int $planeacionEstacionid, int $excludeId = 0)
  {
    $planeacionEstacionid = (int) $planeacionEstacionid;
    $excludeId = (int) $excludeId;

    $whereExclude = $excludeId > 0
      ? " AND idorden <> {$excludeId}"
      : "";

    $sql = "SELECT idorden
            FROM mrp_ordenes_trabajo
            WHERE planeacion_estacionid = {$planeacionEstacionid}
              AND estatus = 2

              AND NOT (
                    accion_produccion = 2
                    AND accion_activa = 2
              )

              {$whereExclude}
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }


  private function getOrdenAnteriorEstacionMismaRuta(int $planeacionEstacionid, string $numSubOrden)
  {
    $planeacionEstacionid = (int) $planeacionEstacionid;
    $numSubOrden = trim($numSubOrden);

    if ($planeacionEstacionid <= 0 || $numSubOrden === '') {
      return [];
    }

    $numSubOrdenSafe = strClean($numSubOrden);

    $sql = "SELECT
                idorden,
                planeacion_estacionid,
                num_sub_orden,
                estatus,
                accion_produccion,
                accion_activa,
                CAST(SUBSTRING_INDEX(num_sub_orden, 'U', -1) AS UNSIGNED) AS orden_unidad
            FROM mrp_ordenes_trabajo
            WHERE planeacion_estacionid = {$planeacionEstacionid}
              AND CAST(SUBSTRING_INDEX(num_sub_orden, 'U', -1) AS UNSIGNED) <
                  CAST(SUBSTRING_INDEX('{$numSubOrdenSafe}', 'U', -1) AS UNSIGNED)

              AND NOT (
                    accion_produccion = 2
                    AND accion_activa = 2
              )

            ORDER BY orden_unidad DESC
            LIMIT 1";

    return $this->select($sql);
  }


  private function unidadFueraLineaOReincorporadaEnRutaAnterior(
    int $planeacionid,
    string $numSubOrden,
    int $ordenRutaActual
  ) {
    $planeacionid = (int) $planeacionid;
    $ordenRutaActual = (int) $ordenRutaActual;
    $numSubOrden = strClean(trim($numSubOrden));

    if ($planeacionid <= 0 || $ordenRutaActual <= 0 || $numSubOrden === '') {
      return false;
    }

    $sql = "SELECT ot.idorden
            FROM mrp_ordenes_trabajo ot
            INNER JOIN mrp_planeacion_estacion pe
                ON pe.id_planeacion_estacion = ot.planeacion_estacionid
            WHERE pe.planeacionid = {$planeacionid}
              AND pe.orden < {$ordenRutaActual}
              AND ot.num_sub_orden = '{$numSubOrden}'
              AND ot.accion_produccion = 2
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }

  private function getPlaneacionEstacionAnterior(int $planeacionid, int $ordenActual)
  {
    $planeacionid = (int) $planeacionid;
    $ordenActual = (int) $ordenActual;

    if ($planeacionid <= 0 || $ordenActual <= 1) {
      return [];
    }

    $sql = "SELECT
                id_planeacion_estacion,
                planeacionid,
                estacionid,
                orden
            FROM mrp_planeacion_estacion
            WHERE planeacionid = {$planeacionid}
              AND estado = 2
              AND orden < {$ordenActual}
            ORDER BY orden DESC
            LIMIT 1";

    return $this->select($sql);
  }

  private function isUnidadFinalizadaEnEstacionAnterior(int $planeacionEstacionAnteriorid, string $numSubOrden)
  {
    $planeacionEstacionAnteriorid = (int) $planeacionEstacionAnteriorid;
    $numSubOrden = trim($numSubOrden);

    if ($planeacionEstacionAnteriorid <= 0 || $numSubOrden === '') {
      return false;
    }

    $numSubOrdenSafe = strClean($numSubOrden);

    $sql = "SELECT idorden
            FROM mrp_ordenes_trabajo
            WHERE planeacion_estacionid = {$planeacionEstacionAnteriorid}
              AND num_sub_orden = '{$numSubOrdenSafe}'
              AND estatus = 3
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }

  private function estacionTieneSubensamblePlaneado(int $planeacionEstacionid)
  {
    $planeacionEstacionid = (int) $planeacionEstacionid;

    if ($planeacionEstacionid <= 0) {
      return false;
    }

    $sql = "SELECT id_planeacion_subensamble
            FROM mrp_planeacion_subensamble
            WHERE planeacion_estacionid = {$planeacionEstacionid}
              AND estado = 2
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }

  private function isUnidadEntregadaPorSubensamble(int $planeacionEstacionid, string $numSubOrden)
  {
    $planeacionEstacionid = (int) $planeacionEstacionid;
    $numSubOrden = trim($numSubOrden);

    if ($planeacionEstacionid <= 0 || $numSubOrden === '') {
      return false;
    }

    $numSubOrdenSafe = strClean($numSubOrden);

    $sql = "SELECT ots.idorden_subensamble
            FROM mrp_planeacion_subensamble ps
            INNER JOIN mrp_ordenes_trabajo_subensamble ots
                ON ots.planeacion_subensambleid = ps.id_planeacion_subensamble
            WHERE ps.planeacion_estacionid = {$planeacionEstacionid}
              AND ps.estado = 2
              AND ots.num_sub_orden = '{$numSubOrdenSafe}'
              AND ots.estado = 4
            LIMIT 1";

    $row = $this->select($sql);
 
    return !empty($row);
  }

  private function generarClaveUnidad(int $longitud = 15): string
{
   
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';

    $clave = '';

    for ($i = 0; $i < $longitud; $i++) {
        $indice = random_int(0, strlen($caracteres) - 1);
        $clave .= $caracteres[$indice];
    }

    return $clave;
}



  public function finalizarOrdenEstacion(int $idorden, int $usuarioid, int $inventarioid)
  {
    $idorden = (int) $idorden;
    $usuarioid = (int) $usuarioid;

    if ($idorden <= 0) {
      return [
        'status' => false,
        'msg' => 'Id de orden de estación inválido.'
      ];
    }

    if ($usuarioid <= 0) {
      return [
        'status' => false,
        'msg' => 'Usuario inválido.'
      ];
    }


    $orden = $this->getDetalleOrdenEstacion($idorden);

    if (empty($orden)) {
      return [
        'status' => false,
        'msg' => 'No se encontró la orden de estación.'
      ];
    }


    $fase = (int) ($orden['fase'] ?? 0);

    if ($fase !== 3) {
      return [
        'status' => false,
        'msg' => 'El supervisor debe iniciar toda la producción para poder finalizar este proceso.'
      ];
    }


    $estatusActual = (int) ($orden['estatus'] ?? 0);

    $accionProduccion = (int) ($orden['accion_produccion'] ?? 0);
    $accionActiva = (int) ($orden['accion_activa'] ?? 0);

     

    if ($accionProduccion === 2 && $accionActiva === 2) {
      return [
        'status' => false,
        'msg' => 'Esta unidad fue retirada de la línea. No puede finalizarse hasta que sea reincorporada.'
      ];
    }

    if ($estatusActual === 1) {
      return [
        'status' => false,
        'msg' => 'No puedes finalizar esta estación porque aún no ha sido iniciada.'
      ];
    }

    if ($estatusActual === 3) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue finalizada previamente en esta estación.'
      ];
    }

    if ($estatusActual !== 2) {
      return [
        'status' => false,
        'msg' => 'La unidad no se encuentra en un estado válido para finalizar.'
      ];
    }

    $planeacionEstacionid = (int) ($orden['planeacion_estacionid'] ?? 0);
    $numSubOrden = (string) ($orden['num_sub_orden'] ?? '');

    if ($planeacionEstacionid <= 0 || $numSubOrden === '') {
      return [
        'status' => false,
        'msg' => 'La orden de estación no tiene información suficiente para finalizarse.'
      ];
    }

    // 4) Validar estampado VIN
    $estampado = (int) ($orden['estampado'] ?? 0);

    if ($estampado === 1) {
      return [
        'status' => false,
        'msg' => 'Para finalizar esta unidad tienes que asignar el VIN correspondiente.'
      ];
    }

    // 5) Validar especificaciones críticas
    $especificacionesCriticas = (int) ($orden['especificaciones_criticas'] ?? 0);

    if ($especificacionesCriticas === 1) {
      return [
        'status' => false,
        'msg' => 'Para finalizar esta unidad tienes que validar las especificaciones críticas pendientes.'
      ];
    }


     $fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');

    $sqlUpdate = "UPDATE mrp_ordenes_trabajo
                  SET estatus = ?, fecha_fin = ?
                  WHERE idorden = ? AND estatus = 2";

    // $arrUpdate = [3, $idorden];

     $arrUpdate = [
        3,
        $fechaMexico,
        $idorden
    ];
    $updated = $this->update($sqlUpdate, $arrUpdate);

    if (!$updated) {
      return [
        'status' => false,
        'msg' => 'No fue posible finalizar la orden de estación.'
      ];
    }


    $observacion = 'Finalización de estación ' . ($orden['nombre_estacion'] ?? '') . ' unidad ' . $numSubOrden;

    $evento = $this->registrarEventoProduccion([
      'tipo_origen' => 'ESTACION',
      'orden_trabajoid' => $idorden,
      'orden_subensambleid' => null,
      'planeacion_estacionid' => $planeacionEstacionid,
      'planeacion_subensambleid' => null,
      'usuarioid' => $usuarioid,
      'accion' => 'FINALIZAR',
      'observaciones' => $observacion,
      'estado' => 3
    ]);

    if (!$evento) {
      return [
        'status' => false,
        'msg' => 'La orden se finalizó, pero no se pudo registrar el evento de producción.'
      ];
    }



    // =====================================================
// VALIDAR SI TODAS LAS UNIDADES DE LA OT YA FINALIZARON
// =====================================================

    $movimiento_insertado = false;
    $multialmacen_insertado = false;

    $subot = trim($numSubOrden);

    // OT260604-001-U03 -> OT260604-001
    $numero_movimiento = preg_replace('/-U\d+$/', '', $subot);

    $almacenid = 4;
    $CONCEPMOVID = 3;

    $sqlPendientes = "SELECT COUNT(*) AS pendientes
    FROM mrp_ordenes_trabajo
    WHERE num_sub_orden = ?
    AND estatus <> 3
";

    $rowPendientes = $this->select(
      $sqlPendientes,
      [$subot]
    );

    $idplaneacion = (int) ($orden['idplaneacion'] ?? 0);
    $plantaid = (int) ($orden['plantaid'] ?? 0);

    $pendientes = (int) ($rowPendientes['pendientes'] ?? 0);

    if ($pendientes === 0) {


          // ==========================================
    // REGISTRAR UNIDAD TERMINADA
    // ==========================================

    $sqlExisteUnidad = "SELECT idunidad
                        FROM mrp_unidades_terminadas
                        WHERE num_unidad = ?
                        LIMIT 1";

    $rowUnidad = $this->select(
        $sqlExisteUnidad,
        [$subot]
    );

    if (empty($rowUnidad)) {

         $clave = $this->generarClaveUnidad();

        $sqlUnidadTerminada = "INSERT INTO mrp_unidades_terminadas
        (   clave,
            num_unidad,
            planeacionid,
            plantaid,
            fecha_creacion,
            estado
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )";

        $arrUnidadTerminada = [
            $clave,
            $subot,
            $idplaneacion,
            $plantaid,
            $fechaMexico,
            2
        ];

        if (method_exists($this, 'insert')) {

            $this->insert(
                $sqlUnidadTerminada,
                $arrUnidadTerminada
            );

        } else {

            $this->update(
                $sqlUnidadTerminada,
                $arrUnidadTerminada
            );
        }
    }


      // ==========================================
      // EVITAR DUPLICAR MOVIMIENTO
      // ==========================================

      $sqlYaExiste = "SELECT COUNT(*) AS total
        FROM wms_movimientos_inventario
        WHERE referencia = ?
        LIMIT 1
    ";

      $rowExiste = $this->select(
        $sqlYaExiste,
        [$numSubOrden]
      );

      $yaExiste = (int) ($rowExiste['total'] ?? 0);

      if ($yaExiste === 0) {

        $sqlMovimiento = "INSERT INTO wms_movimientos_inventario
            (
                inventarioid,
                almacenid,
                numero_movimiento,
                concepmovid,
                referencia,
                cantidad,
                costo_cantidad,
                precio,
                costo,
                existencia,
                signo,
                fecha_movimiento,
                estado
            )
            VALUES
            (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
        ";

        $arrMovimiento = [
          $inventarioid,
          $almacenid,
          $numero_movimiento,
          $CONCEPMOVID,
          $numSubOrden,
          1,
          0,
          0,
          0,
          1,
          1,
          $fechaMexico,
          2
        ];

        if (method_exists($this, 'insert')) {
          $movimiento_insertado = $this->insert(
            $sqlMovimiento,
            $arrMovimiento
          );
        } else {
          $movimiento_insertado = $this->update(
            $sqlMovimiento,
            $arrMovimiento
          );
        }
      }

      // ==========================================
      // ACTUALIZAR EXISTENCIA
      // ==========================================

      $sqlExistencia = "SELECT idmultialmacen
        FROM wms_multialmacen
        WHERE inventarioid = ?
        AND almacenid = ?
        LIMIT 1
    ";

      $rowExistencia = $this->select(
        $sqlExistencia,
        [$inventarioid, $almacenid]
      );

      if (!empty($rowExistencia)) {

        $idmultialmacen = (int) $rowExistencia['idmultialmacen'];

        $sqlUpdateExistencia = "UPDATE wms_multialmacen
            SET existencia = existencia + 1
            WHERE idmultialmacen = ?
        ";

        $multialmacen_insertado = $this->update(
          $sqlUpdateExistencia,
          [$idmultialmacen]
        );

      } else {

        $sqlInsertExistencia = "INSERT INTO wms_multialmacen
            (
                inventarioid,
                almacenid,
                control_almacen,
                existencia,
                stock_minimo,
                stock_maximo,
                compras_x_recibir,
                pendiente_surtir
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $arrExistencia = [
          $inventarioid,
          $almacenid,
          '',
          1,
          0,
          0,
          0,
          0
        ];

        if (method_exists($this, 'insert')) {

          $multialmacen_insertado = $this->insert(
            $sqlInsertExistencia,
            $arrExistencia
          );

        } else {

          $multialmacen_insertado = $this->update(
            $sqlInsertExistencia,
            $arrExistencia
          );
        }
      }
    }









    $ordenActualizada = $this->getDetalleOrdenEstacion($idorden);

    return [
      'status' => true,
      'msg' => 'Estación finalizada correctamente. La unidad quedó liberada al siguiente proceso.',
      'data' => [
        'orden' => $ordenActualizada
      ]
    ];
  }




  public function buscarUsuariosOperacion($busqueda)
  {
    $search = '%' . $busqueda . '%';

    $sql = "SELECT 
                idusuario,
                numcolaborador,
                nombres,
                apellidos,
                email_user,
                CONCAT(nombres, ' ', apellidos) AS nombre_completo
            FROM usuarios
            WHERE status = 1
            AND (
                numcolaborador LIKE ?
                OR nombres LIKE ?
                OR apellidos LIKE ?
                OR email_user LIKE ?
                OR CONCAT(nombres, ' ', apellidos) LIKE ?
            )
            ORDER BY 
              CASE 
                WHEN numcolaborador = ? THEN 1
                WHEN email_user = ? THEN 2
                WHEN nombres LIKE ? THEN 3
                ELSE 4
              END ASC,
              nombres ASC
            LIMIT 8";

    return $this->select_all($sql, [
      $search,
      $search,
      $search,
      $search,
      $search,
      $busqueda,
      $busqueda,
      $search
    ]);
  }



  public function operacionAsignada_subensamble($idespecificacion)
  {
    $sql = "UPDATE mrp_subensamble_especificaciones
            SET asignado = ?
            WHERE idespecificacionsubensamble = ?";

    return $this->update($sql, [1, $idespecificacion]);
  }



  public function operacionAsignada_estacion($idespecificacion)
  {
    $sql = "UPDATE mrp_estacion_especificaciones
            SET asignado = ?
            WHERE idespecificacion = ?";

    return $this->update($sql, [1, $idespecificacion]);
  }

  public function contarOperacionesPendientesSubensamble($productoid, $subensambleid)
  {
    $sql = "SELECT COUNT(*) AS total
            FROM mrp_subensamble_especificaciones
            WHERE productoid = ?
            AND subensambleid = ?
            AND asignado = 0";

    $request = $this->select($sql, [$productoid, $subensambleid]);

    return (int) ($request['total'] ?? 0);
  }



  public function contarOperacionesPendientesEstacion($productoid, $estacionid)
  {
    $sql = "SELECT COUNT(*) AS total
            FROM mrp_estacion_especificaciones
            WHERE productoid = ?
            AND estacionid = ?
            AND asignado = 0";

    $request = $this->select($sql, [$productoid, $estacionid]);

    return (int) ($request['total'] ?? 0);
  }






  public function selectEspecificacionesEstacionPendientes($productoid, $estacionid, $idorden, $unidad_actual)
  {
    $productoid = (int) $productoid;
    $estacionid = (int) $estacionid;
    $idorden = (int) $idorden;
    $unidad_actual = strClean($unidad_actual);

    $sql = "SELECT 
                ee.idespecificacion,
                ee.productoid,
                ee.estacionid,
                ee.especificacion
            FROM mrp_estacion_especificaciones ee
            WHERE ee.productoid = $productoid
            AND ee.estacionid = $estacionid
            AND ee.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_realizadas mor
                WHERE mor.productoid = ee.productoid
                -- AND mor.origenid = ee.estacionid
                AND mor.tipo_origen = 'estacion'
                -- AND mor.idordengeneral = $idorden
                AND mor.idespecificacion = ee.idespecificacion
                AND mor.unidad = '$unidad_actual'
            )
            ORDER BY ee.idespecificacion ASC";

    return $this->select_all($sql);
  }




  public function selectEspecificacionesSubensamblePendientes($productoid, $subensambleid, $idorden_subensamble, $unidad_actual)
  {
    $productoid = (int) $productoid;
    $subensambleid = (int) $subensambleid;
    $idorden_subensamble = (int) $idorden_subensamble;
    $unidad_actual = strClean($unidad_actual);

    $sql = "SELECT 
                se.idespecificacionsubensamble,
                se.productoid,
                se.subensambleid,
                se.especificacion
            FROM mrp_subensamble_especificaciones se
            WHERE se.productoid = $productoid
            AND se.subensambleid = $subensambleid
            AND se.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_realizadas mor
                WHERE mor.productoid = se.productoid
                -- AND mor.origenid = se.subensambleid
                AND mor.tipo_origen = 'subensamble'
                -- AND mor.idordengeneral = $idorden_subensamble
                AND mor.idespecificacion = se.idespecificacionsubensamble
                AND mor.unidad = '$unidad_actual'
            )
            ORDER BY se.idespecificacionsubensamble ASC";

    return $this->select_all($sql);
  }


  public function selectEspecificacionesCSubensamblePendientes($productoid, $subensambleid, $idorden_subensamble, $unidad_actual)
  {
    $productoid = (int) $productoid;
    $subensambleid = (int) $subensambleid;
    $idorden_subensamble = (int) $idorden_subensamble;
    $unidad_actual = strClean($unidad_actual);

    $sql = "SELECT 
                se.idespecificacionsubensamble,
                se.productoid,
                se.subensambleid,
                se.especificacion
            FROM mrp_subensamble_especificaciones_criticas se
            WHERE se.productoid = $productoid
            AND se.subensambleid = $subensambleid
            AND se.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_criticas_realizadas mor
                WHERE mor.productoid = se.productoid
                -- AND mor.origenid = se.subensambleid
                AND mor.tipo_origen = 'subensamble'
                -- AND mor.idordengeneral = $idorden_subensamble
                AND mor.idespecificacion = se.idespecificacionsubensamble
                AND mor.unidad = '$unidad_actual'
            )
            ORDER BY se.idespecificacionsubensamble ASC";

    return $this->select_all($sql);
  }


  public function selectEspecificacionesCEstacionPendientes($productoid, $estacionid, $idorden, $unidad_actual)
  {
    $productoid = (int) $productoid;
    $estacionid = (int) $estacionid;
    $idorden = (int) $idorden;
    $unidad_actual = strClean($unidad_actual);

    $sql = "SELECT  
                ee.idespecificacion,
                ee.productoid,
                ee.estacionid,
                ee.especificacion
            FROM mrp_estacion_especificaciones_criticas ee
            WHERE ee.productoid = $productoid
            AND ee.estacionid = $estacionid
            AND ee.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_realizadas mor
                WHERE mor.productoid = ee.productoid
                -- AND mor.origenid = ee.estacionid
                AND mor.tipo_origen = 'estacion'
                -- AND mor.idordengeneral = $idorden
                AND mor.idespecificacion = ee.idespecificacion
                AND mor.unidad = '$unidad_actual'
            )
            ORDER BY ee.idespecificacion ASC";

    return $this->select_all($sql);
  }


  public function selectOrdenSubensambleById($idorden_subensamble)
  {
    $sql = "SELECT 
                idorden_subensamble,
                operaciones
            FROM mrp_ordenes_trabajo_subensamble
            WHERE idorden_subensamble = $idorden_subensamble
            LIMIT 1";

    return $this->select($sql);
  }

  public function selectOrdenEstacionById($idorden)
  {
    $sql = "SELECT 
                idorden,
                operaciones
            FROM mrp_ordenes_trabajo
            WHERE idorden = $idorden
            LIMIT 1";

    return $this->select($sql);
  }


  public function selectEspecificacionesCEstacionById($idorden)
  {
    $sql = "SELECT 
                idorden,
                especificaciones_criticas
            FROM mrp_ordenes_trabajo
            WHERE idorden = $idorden
            LIMIT 1";

    return $this->select($sql);
  }

  public function selectEspecificacionesSubensambleById($idorden_subensamble)
  {
    $sql = "SELECT 
                idorden_subensamble,
                especificaciones_criticas
            FROM mrp_ordenes_trabajo_subensamble
            WHERE idorden_subensamble = $idorden_subensamble
            LIMIT 1";

    return $this->select($sql);
  }

  public function selectEspecificacionesEstacionById($idorden)
  {
    $sql = "SELECT 
                idorden,
                especificaciones_criticas
            FROM mrp_ordenes_trabajo
            WHERE idorden = $idorden
            LIMIT 1";

    return $this->select($sql);
  }



  public function insertOperacionRealizada($data)
  {
    $usuario = $this->select(
      "SELECT numcolaborador 
         FROM usuarios 
         WHERE idusuario = ? 
         LIMIT 1",
      [$data['usuarioid']]
    );

    $numcolaborador = '250761';

    $sql = "INSERT INTO mrp_operaciones_realizadas
            (
                productoid,
                tipo_origen,
                estacionid,
                subensambleid,
                idespecificacion,
                operacion_texto,
                usuarioid,
                numcolaborador,
                unidad,
                fecha_registro,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 2)";

    return $this->insert($sql, [
      $data['productoid'],
      $data['tipo_origen'],
      $data['estacionid'],
      $data['subensambleid'],
      $data['idespecificacion'],
      $data['operacion_texto'],
      $data['usuarioid'],
      $numcolaborador,
      $data['unidad']
    ]);
  }

  public function contarOperacionesNoRealizadasEstacion($productoid, $estacionid, $unidad)
  {
    $sql = "SELECT COUNT(*) AS total
            FROM mrp_estacion_especificaciones ee
            WHERE ee.productoid = $productoid
            AND ee.estacionid = $estacionid
            AND ee.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_realizadas mor
                WHERE mor.productoid = ee.productoid
                AND mor.tipo_origen = 'estacion'
                AND mor.estacionid = ee.estacionid
                AND mor.idespecificacion = ee.idespecificacion
                AND mor.unidad = '$unidad'
                AND mor.estado = 2
            )";

    $request = $this->select($sql);

    return (int) ($request['total'] ?? 0);
  }


  public function contarOperacionesNoRealizadasSubensamble($productoid, $subensambleid, $unidad)
  {
    $sql = "SELECT COUNT(*) AS total
            FROM mrp_subensamble_especificaciones se
            WHERE se.productoid = $productoid
            AND se.subensambleid = $subensambleid
            AND se.estado = 2
            AND NOT EXISTS (
                SELECT 1
                FROM mrp_operaciones_realizadas mor
                WHERE mor.productoid = se.productoid
                AND mor.tipo_origen = 'subensamble'
                AND mor.subensambleid = se.subensambleid
                AND mor.idespecificacion = se.idespecificacionsubensamble
                AND mor.unidad = '$unidad'
                AND mor.estado = 2
            )";

    $request = $this->select($sql);

    return (int) ($request['total'] ?? 0);
  }

  public function actualizarOperacionesOrdenSubensamble($idorden_subensamble)
  {
    $sql = "UPDATE mrp_ordenes_trabajo_subensamble
            SET operaciones = ?
            WHERE idorden_subensamble = ?";

    return $this->update($sql, [2, $idorden_subensamble]);
  }

  public function actualizarOperacionesOrdenEstacion($idorden)
  {
    $sql = "UPDATE mrp_ordenes_trabajo
            SET operaciones = ?
            WHERE idorden = ?";

    return $this->update($sql, [2, $idorden]);
  }


  public function insertOperacionCriticaRealizada($data)
  {
    $sql = "INSERT INTO mrp_operaciones_criticas_realizadas
    (
        productoid,
        tipo_origen,
        estacionid,
        subensambleid,
        idespecificacion,
        operacion_texto,
        usuarioid,
        -- numcolaborador,
        unidad,
        resultado,
        observaciones,
        fecha_registro,
        estado
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,NOW(), 2)";

    return $this->insert($sql, [

      $data['productoid'],
      $data['tipo_origen'],
      $data['estacionid'],
      $data['subensambleid'],
      $data['idespecificacion'],
      $data['operacion_texto'],
      $data['usuarioid'],
      // $data['numcolaborador'],
      $data['unidad'],
      $data['tipo_resultado'],
      $data['observaciones'],

    ]);
  }


  public function actualizarEspecificacionesCriticasOrdenSubensamble($idorden_subensamble)
  {
    $sql = "UPDATE mrp_ordenes_trabajo_subensamble
            SET especificaciones_criticas = ?
            WHERE idorden_subensamble = $idorden_subensamble";

    $arrData = array(2);

    $request = $this->update($sql, $arrData);

    return $request;

    // return $this->update($sql, [2, $idorden_subensamble]);
  }

  public function actualizarEspecificacionesCriticasOrdenEstacion($idorden_subensamble)
  {
    $sql = "UPDATE mrp_ordenes_trabajo
            SET especificaciones_criticas = ?
            WHERE idorden = $idorden_subensamble";

    $arrData = array(2);

    $request = $this->update($sql, $arrData);

    return $request;

    // return $this->update($sql, [2, $idorden_subensamble]);
  }



  public function getPuntosInspeccion($productoid, $estacionid)
  {
    $sql = "
        SELECT
            pdi.idpdi,
            pdi.titulo,
            pdi.descripcion,

            z.idzona,
            z.nombre_zona,
            z.referencia,
            z.orden AS orden_zona,

            pt.idpuntopdi,
            pt.punto,
            pt.orden AS orden_punto,

            pt.check_china,
            pt.check_mexico,
            pt.check_i1,
            pt.check_i2,
            pt.check_i3,
            pt.check_i4

        FROM mrp_estacion_pdi pdi

        INNER JOIN mrp_estacion_pdi_zona z
            ON z.pdiid = pdi.idpdi
            AND z.estado = 2

        INNER JOIN mrp_estacion_pdi_punto pt
            ON pt.zonaid = z.idzona
            AND pt.estado = 2

        WHERE pdi.productoid = $productoid
        AND pdi.estacionid = $estacionid
        AND pdi.estado = 2

        ORDER BY z.orden ASC, pt.orden ASC
    ";

    return $this->select_all($sql);
  }



  public function clearPlaneacionCalidadCriticos($planeacionEstacionId)
  {
    $planeacionEstacionId = (int) $planeacionEstacionId;

    $sql = "UPDATE mrp_planeacion_estacion_calidadpuntoscriticos
          SET estado = 0
          WHERE planeacion_estacionid = {$planeacionEstacionId}";

    return $this->update($sql, []);
  }

  public function insertPlaneacionCalidadCriticos($planeacionEstacionId, $usuarioid, $rol)
  {
    $sql = "INSERT INTO mrp_planeacion_estacion_calidadpuntoscriticos
          (
            planeacion_estacionid,
            usuarioid,
            rol,
            estado
          )
          VALUES (?,?,?,2)";

    return $this->insert($sql, [
      $planeacionEstacionId,
      $usuarioid,
      $rol
    ]);
  }

  public function clearPlaneacionCalidadPdi($planeacionEstacionId)
  {
    $planeacionEstacionId = (int) $planeacionEstacionId;

    $sql = "UPDATE mrp_planeacion_estacion_calidadpdi
          SET estado = 0
          WHERE planeacion_estacionid = {$planeacionEstacionId}";

    return $this->update($sql, []);
  }

  public function insertPlaneacionCalidadPdi($planeacionEstacionId, $usuarioid, $rol)
  {
    $sql = "INSERT INTO mrp_planeacion_estacion_calidadpdi
          (
            planeacion_estacionid,
            usuarioid,
            rol,
            estado
          )
          VALUES (?,?,?,2)";

    return $this->insert($sql, [
      $planeacionEstacionId,
      $usuarioid,
      $rol
    ]);
  }

  public function insertInspeccionCalidad(
    int $productoid,
    int $estacionid,
    int $idordengeneral,
    string $unidadActual,
    array $detalles,
    int $usuarioid
  ) {
    $insertados = 0;
    $fechaCreacion = date('Y-m-d H:i:s');

    foreach ($detalles as $item) {

      $idpuntopdi = intval($item['idpuntopdi'] ?? 0);
      $resultado = intval($item['resultado'] ?? 0);
      $observacion = trim($item['observacion'] ?? '');

      $tipoCheck = 'check_mexico';

      if ($idpuntopdi <= 0 || !in_array($resultado, [1, 2])) {
        continue;
      }

      $sql = "
            INSERT INTO mrp_estacion_pdi_resultado (
                productoid,
                estacionid,
                idordengeneral,
                unidad_actual,
                idpuntopdi,
                tipo_check,
                resultado,
                observacion,
                usuarioid,
                fecha_creacion,
                estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

      $arrData = [
        $productoid,
        $estacionid,
        $idordengeneral,
        $unidadActual,
        $idpuntopdi,
        $tipoCheck,
        $resultado,
        $observacion,
        $usuarioid,
        $fechaCreacion,
        2
      ];

      $requestInsert = $this->insert($sql, $arrData);

      if ($requestInsert > 0) {
        $insertados++;
      }
    }

    return $insertados;
  }

  public function updateCalidadOrdenTrabajo(int $idordengeneral)
  {
    $sql = "
        UPDATE mrp_ordenes_trabajo
        SET calidad = ?
        WHERE idorden = ?
    ";

    $arrData = [
      2,
      $idordengeneral
    ];

    return $this->update($sql, $arrData);
  }

  public function getInfoEstacion($idestacion)
  {
    $sql = "SELECT 
                idestacion,
                cve_estacion,
                nombre_estacion,
                proceso,
                estandar,
                unidad_medida,
                descripcion,
                tiempo_ajuste,
                mxn
            FROM mrp_estacion
            WHERE idestacion = $idestacion
            LIMIT 1";

    return $this->select($sql);
  }

  public function getInfoSupervisorByOrden($idordengeneral)
  {
    $sql = "SELECT 
                p.idplaneacion,
                p.num_orden,
                p.supervisorid,
                p.prioridad,
                p.cantidad,
                p.fecha_requerida,

                pe.id_planeacion_estacion,
                pe.estacionid,

                u.idusuario,
                u.nombres,
                u.apellidos,
                u.email_user,
                u.telefono

            FROM mrp_ordenes_trabajo ot

            INNER JOIN mrp_planeacion_estacion pe
                ON pe.id_planeacion_estacion = ot.planeacion_estacionid

            INNER JOIN mrp_planeacion p
                ON p.idplaneacion = pe.planeacionid

            INNER JOIN usuarios u
                ON u.idusuario = p.supervisorid

            WHERE ot.idorden = ?
            LIMIT 1";

    return $this->select($sql, [$idordengeneral]);
  }

  public function insertAccionProduccion($data)
  {


      $fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');
    $sql = "INSERT INTO mrp_acciones_produccion
            (
                productoid,
                estacionid,
                idordengeneral,
                unidad,
                origen_accion,
                tipo_accion,
                fecha_inicio,
                fecha_fin,
                minutos_total,
                usuarioid,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, 2)";

    return $this->insert($sql, [
      $data['productoid'],
      $data['estacionid'],
      $data['idordengeneral'],
      $data['unidad'],
      $data['origen_accion'],
      $data['tipo_accion'],
      $fechaMexico,
      $data['usuarioid']
    ]);
  }
  public function insertUnidadFueraLinea($data)
  {
    $sql = "INSERT INTO mrp_unidades_fuera_linea
            (
                accionid,
                productoid,
                estacionid,
                idordengeneral,
                unidad,
                fecha_salida,
                usuario_salida,
                fecha_reincorporacion,
                usuario_reincorporacion,
                estado
            )
            VALUES (?, ?, ?, ?, ?, NOW(), ?, NULL, NULL, 1)";

    return $this->insert($sql, [
      $data['accionid'],
      $data['productoid'],
      $data['estacionid'],
      $data['idordengeneral'],
      $data['unidad'],
      $data['usuario_salida']
    ]);
  }
  public function insertAccionNotificacion($data)
  {
    $sql = "INSERT INTO mrp_acciones_notificaciones
            (
                accionid,
                usuario_origen,
                usuario_destino,
                tipo_notificacion,
                enviado_correo,
                fecha_envio,
                estado
            )
            VALUES (?, ?, ?, ?, 1, NULL, 1)";

    return $this->insert($sql, [
      $data['accionid'],
      $data['usuario_origen'],
      $data['usuario_destino'],
      $data['tipo_notificacion']
    ]);
  }


  public function actualizarAccionProduccionOrden($idorden, $tipo_accion)
  {
    $idorden = (int) $idorden;
    $tipo_accion = (int) $tipo_accion;

    if ($idorden <= 0 || $tipo_accion <= 0) {
      return false;
    }

    $sql = "UPDATE mrp_ordenes_trabajo
            SET accion_produccion = ?,
                accion_activa = ?
            WHERE idorden = ?";

    return $this->update($sql, [
      $tipo_accion,
      2,
      $idorden
    ]);
  }



  public function reanudarParoMomentaneoModel(int $idordengeneral, int $idusuario)
  {
    try {


      $sqlOrden = "SELECT idorden
                     FROM mrp_ordenes_trabajo
                     WHERE idorden = $idordengeneral
                     LIMIT 1";

      $orden = $this->select($sqlOrden);

      if (empty($orden)) {


        return [
          "status" => false,
          "msg" => "No se encontró la orden de trabajo."
        ];
      }

      $sqlAccion = "SELECT 
                            idaccion,
                            fecha_inicio
                       FROM mrp_acciones_produccion
                       WHERE idordengeneral = $idordengeneral
                       AND estado = 2
                       ORDER BY idaccion DESC
                       LIMIT 1";

      $accion = $this->select($sqlAccion);

      if (empty($accion)) {


        return [
          "status" => false,
          "msg" => "No existe un paro momentáneo activo."
        ];
      }

      $idaccion = (int) $accion['idaccion'];
      $sqlUpdateOrden = "UPDATE mrp_ordenes_trabajo
                           SET 
                               accion_produccion = 0,
                               accion_activa = 1
                           WHERE idorden = ?";

      $updateOrden = $this->update($sqlUpdateOrden, [
        $idordengeneral
      ]);

       $fechaMexico = (new DateTime('now', new DateTimeZone('America/Mexico_City')))
        ->format('Y-m-d H:i:s');


      // $sqlCerrarParo = "UPDATE mrp_acciones_produccion
      //                     SET
      //                         fecha_fin = NOW(),
      //                         minutos_total = TIMESTAMPDIFF(
      //                             MINUTE,
      //                             fecha_inicio,
      //                             NOW()
      //                         ),
      //                         usuarioidfin = ?,
      //                         estado = 3
      //                     WHERE idaccion = ?";

      // $updateParo = $this->update($sqlCerrarParo, [
      //   $idusuario,
      //   $idaccion
      // ]);

      $sqlCerrarParo = "UPDATE mrp_acciones_produccion
                  SET
                      fecha_fin = ?,
                      minutos_total = TIMESTAMPDIFF(
                          MINUTE,
                          fecha_inicio,
                          ?
                      ),
                      usuarioidfin = ?,
                      estado = 3
                  WHERE idaccion = ?";

$updateParo = $this->update($sqlCerrarParo, [
    $fechaMexico,  
    $fechaMexico,  
    $idusuario,    // usuario que finaliza
    $idaccion      // acción a cerrar
]);

      return [
        "status" => true,
        "msg" => "El paro momentáneo fue reanudado correctamente.",
        "data" => [
          "idaccion" => $idaccion,
          "idordengeneral" => $idordengeneral
        ]
      ];

    } catch (Exception $e) {
      return [
        "status" => false,
        "msg" => "Error al reanudar el paro: " . $e->getMessage()
      ];
    }
  }




  public function reincorporarUnidadFueraLinea(int $idfuera, int $usuarioid)
  {
    $idfuera = (int) $idfuera;
    $usuarioid = (int) $usuarioid;

    if ($idfuera <= 0 || $usuarioid <= 0) {
      return [
        'status' => false,
        'msg' => 'Datos inválidos para reincorporar la unidad.'
      ];
    }

    $unidadFuera = $this->getUnidadFueraLineaById($idfuera);

    if (empty($unidadFuera)) {
      return [
        'status' => false,
        'msg' => 'No se encontró la unidad fuera de línea.'
      ];
    }

    if ((int) $unidadFuera['estado'] !== 1) {
      return [
        'status' => false,
        'msg' => 'Esta unidad ya fue reincorporada o no está activa fuera de línea.'
      ];
    }

    $idorden = (int) $unidadFuera['idordengeneral'];
    $planeacionEstacionid = (int) $unidadFuera['planeacion_estacionid'];
    $unidad = $unidadFuera['unidad'];
    $nombreEstacion = $unidadFuera['nombre_estacion'] ?? 'la estación';

    $estacionTrabajando = $this->existeUnidadTrabajandoEnEstacionParaReincorporar(
      $planeacionEstacionid,
      $idorden
    );

    if ($estacionTrabajando) {
      return [
        'status' => false,
        'msg' => "Actualmente la estación {$nombreEstacion} está trabajando en otra unidad. Para reincorporar {$unidad}, la estación debe estar libre."
      ];
    }

    $sqlFuera = "UPDATE mrp_unidades_fuera_linea
                 SET estado = 2,
                     fecha_reincorporacion = NOW(),
                     usuario_reincorporacion = ?
                 WHERE idfuera = ?
                   AND estado = 1";

    $updateFuera = $this->update($sqlFuera, [
      $usuarioid,
      $idfuera
    ]);

    if (!$updateFuera) {
      return [
        'status' => false,
        'msg' => 'No fue posible actualizar el registro de unidad fuera de línea.'
      ];
    }

    $sqlOrden = "UPDATE mrp_ordenes_trabajo
                 SET accion_activa = 1
                 WHERE idorden = ?
                   AND accion_produccion = 2
                   AND accion_activa = 2";

    $this->update($sqlOrden, [
      $idorden
    ]);

    $this->registrarEventoProduccion([
      'tipo_origen' => 'ESTACION',
      'orden_trabajoid' => $idorden,
      'orden_subensambleid' => null,
      'planeacion_estacionid' => $planeacionEstacionid,
      'planeacion_subensambleid' => null,
      'usuarioid' => $usuarioid,
      'accion' => 'REINCORPORAR',
      'observaciones' => 'Reincorporación de unidad ' . $unidad . ' en estación ' . $nombreEstacion,
      'estado' => 2
    ]);

    return [
      'status' => true,
      'msg' => "La unidad {$unidad} fue reincorporada correctamente en {$nombreEstacion}."
    ];
  }
  private function getUnidadFueraLineaById(int $idfuera)
  {
    $idfuera = (int) $idfuera;

    $sql = "SELECT
                ufl.idfuera,
                ufl.accionid,
                ufl.productoid,
                ufl.estacionid,
                ufl.idordengeneral,
                ufl.unidad,
                ufl.fecha_salida,
                ufl.usuario_salida,
                ufl.fecha_reincorporacion,
                ufl.usuario_reincorporacion,
                ufl.estado,
                ot.idorden,
                ot.planeacion_estacionid,
                ot.estatus,
                ot.accion_produccion,
                ot.accion_activa,
                pe.id_planeacion_estacion,
                pe.planeacionid,
                pe.estacionid AS estacion_planeadaid,
                e.nombre_estacion
            FROM mrp_unidades_fuera_linea ufl
            INNER JOIN mrp_ordenes_trabajo ot
                ON ot.idorden = ufl.idordengeneral
            INNER JOIN mrp_planeacion_estacion pe
                ON pe.id_planeacion_estacion = ot.planeacion_estacionid
            LEFT JOIN mrp_estacion e
                ON e.idestacion = pe.estacionid
            WHERE ufl.idfuera = {$idfuera}
            LIMIT 1";

    return $this->select($sql);
  }
  private function existeUnidadTrabajandoEnEstacionParaReincorporar(
    int $planeacionEstacionid,
    int $idordenReincorporar
  ) {
    $planeacionEstacionid = (int) $planeacionEstacionid;
    $idordenReincorporar = (int) $idordenReincorporar;

    $sql = "SELECT idorden
            FROM mrp_ordenes_trabajo
            WHERE planeacion_estacionid = {$planeacionEstacionid}
              AND idorden <> {$idordenReincorporar}
              AND estatus = 2
              AND NOT (
                    accion_produccion = 2
                    AND accion_activa = 2
              )
            LIMIT 1";

    $row = $this->select($sql);

    return !empty($row);
  }


}
?>