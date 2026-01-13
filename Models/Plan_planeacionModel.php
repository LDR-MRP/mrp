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
					WHERE status != 0 AND rolid=14 ";
		$request = $this->select_all($sql);
		return $request;
	}


	public function selectOperadoresAyudantes()
	{
		$sql = "SELECT * FROM usuarios 
					WHERE status != 0 AND rolid=8 ";
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

		$sql = "SELECT pla.*,
		       pla.estado AS estado_planeacion, 
               pro.cve_producto,
               pro.descripcion AS descripcion_producto
        FROM  mrp_planeacion AS pla
        INNER JOIN mrp_productos AS pro ON pla.productoid = pro.idproducto
        WHERE pla.fase = 2;";
		$request = $this->select_all($sql);
		return $request;

	}


	public function selectPlanFinalizadas()
	{

		$sql = "SELECT pla.*,
		       pla.estado AS estado_planeacion, 
               pro.cve_producto,
               pro.descripcion AS descripcion_producto
        FROM  mrp_planeacion AS pla
        INNER JOIN mrp_productos AS pro ON pla.productoid = pro.idproducto
        WHERE pla.fase = 5;";
		$request = $this->select_all($sql);
		return $request;

	}


	public function selectPlanCanceladas()
	{

		$sql = "SELECT pla.*,
		       pla.estado AS estado_planeacion, 
               pro.cve_producto,
               pro.descripcion AS descripcion_producto
        FROM  mrp_planeacion AS pla
        INNER JOIN mrp_productos AS pro ON pla.productoid = pro.idproducto
        WHERE pla.fase = 6;";
		$request = $this->select_all($sql);
		return $request;

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
            'msg'    => 'Sin componentes configurados para validar',
            'data'   => []
        ];
    }


    foreach ($componentes as $c) {
        $inventarioid = (int)($c['inventarioid'] ?? 0);
        $almacenid    = (int)($c['almacenid'] ?? 0);

        $cantPorUnidad = (float)($c['cantidad_por_unidad'] ?? 0);

        // requerid = cantidadPlaeada * cantidad_por_unidad
        $requerido = (float)$cantidadPlaneada * (float)$cantPorUnidad;


        $sqlExist = "SELECT m.existencia
                     FROM wms_movimientos_inventario m
                     WHERE m.estado = 2
                       AND m.inventarioid = $inventarioid
                       AND m.almacenid = $almacenid
                     ORDER BY m.fecha_movimiento DESC, m.idmovinventario DESC
                     LIMIT 1";

        $rowExist = $this->select($sqlExist);

        $existencia = isset($rowExist['existencia']) ? (float)$rowExist['existencia'] : 0;

  
        if ($requerido > $existencia) {
            $faltante = $requerido - $existencia;

            $faltantes[] = [
                'productoid'   => $productoid,
                'estacionid'   => $estacionid,
                'almacenid'    => $almacenid,
                'inventarioid' => $inventarioid,
                'requerido'    => $requerido,
                'existencia'   => $existencia,
                'faltante'     => $faltante
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
        $inventarioid  = (int)($c['inventarioid'] ?? 0);
        $almacenid     = (int)($c['almacenid'] ?? 0);
        $cantPorUnidad = (float)($c['cantidad_por_unidad'] ?? 0);

     
        $requerido = (float)$cantidadPlaneada * (float)$cantPorUnidad;

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
                'productoid'            => $productoid,
                'estacionid'            => $estacionid,
                'almacenid'             => $almacenid,
                'inventarioid'          => $inventarioid,
                'descripcion'           => $descripcion,
                'descripcion_almacen'   => $descripcion_almacen,

             
                'cantidad_planeada'     => (float)$cantidadPlaneada,
                'cantidad_por_unidad'   => (float)$cantPorUnidad,
                'requerido'             => (float)$requerido,
                'existencia'            => (float)$existencia,
                'faltante'              => (float)$faltante
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
            'msg'    => 'Sin herramientas configuradas para validar',
            'data'   => []
        ];
    }

    foreach ($herramientas as $h) {
        $inventarioid  = (int)($h['inventarioid'] ?? 0);
        $almacenid     = (int)($h['almacenid'] ?? 0);
        $cantPorUnidad = (float)($h['cantidad_por_unidad'] ?? 0);


        $requerido = (float)$cantidadPlaneada * (float)$cantPorUnidad;

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
                'productoid'            => $productoid,
                'estacionid'            => $estacionid,
                'almacenid'             => $almacenid,
                'inventarioid'          => $inventarioid,
                'descripcion'           => $descripcion,
                'descripcion_almacen'   => $descripcion_almacen,

               
                'cantidad_planeada'     => (float)$cantidadPlaneada,
                'cantidad_por_unidad'   => (float)$cantPorUnidad,
                'requerido'             => (float)$requerido,
                'existencia'            => (float)$existencia,
                'faltante'              => (float)$faltante
            ];
        }
    }

    if (!empty($faltantes)) {
        return [
            'status' => 0,
            'msg'    => 'Faltan herramientas en inventario',
            'data'   => $faltantes
        ];
    }

    return [
        'status' => true,
        'msg'    => 'Existencias OK',
        'data'   => []
    ];
}


public function getEmailsUsuariosByIds(array $ids)
{
    if (empty($ids)) return [];

    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, fn($x) => $x > 0);
    if (empty($ids)) return [];

    $in = implode(',', $ids);

    $sql = "SELECT idusuario, nombres, apellidos, email_user
            FROM usuarios
            WHERE idusuario IN ($in) AND status = 1";

    return $this->select_all($sql);
}

public function getProducto(int $idproducto)
{
  $idproducto = (int)$idproducto;

  $sql = "SELECT cve_producto, descripcion
          FROM mrp_productos
          WHERE idproducto = {$idproducto}
          LIMIT 1";

  return $this->select($sql);
}

public function getEstacionesByIds(array $ids)
{
  $ids = array_values(array_unique(array_map('intval', $ids)));
  if (empty($ids)) return [];

  $in = implode(',', $ids);


  $sql = "SELECT idestacion, nombre_estacion, proceso
          FROM mrp_estacion
          WHERE idestacion IN ($in)";

  return $this->select_all($sql); 
}

public function obtenerPlaneacion($num_orden)
{

  $num_orden = trim((string)$num_orden);
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

  $planeacionid = (int)($planeacion['idplaneacion'] ?? 0);
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


  $idsPE = array_map(fn($r) => (int)$r['id_planeacion_estacion'], $estaciones);
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
    $peid = (int)($op['planeacion_estacionid'] ?? 0);
    if ($peid <= 0) continue;
    $opsByPE[$peid][] = $op;
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
      'cve_estacion'           => $e['cve_estacion'],
      'nombre_estacion'        => $e['nombre_estacion'],
      'proceso'                => $e['proceso'],
      'encargados'             => [],
      'ayudantes'              => [],
    ];

    $lista = $opsByPE[$peid] ?? [];

    foreach ($lista as $op) {
      $rol = (string)($op['rol'] ?? '');

      $objOper = [
        'usuarioid'       => (int)($op['usuarioid'] ?? 0),
        'rol'             => $rol,
        'nombre_completo' => (string)($op['nombre_completo'] ?? ''),
      ];

      if ($rol === 'ENCARGADO') {
        $item['encargados'][] = $objOper;
      } else if ($rol === 'AYUDANTE') {
        $item['ayudantes'][] = $objOper;
      }
    }

    $outEstaciones[] = $item;
  }


  $planeacion['estaciones'] = $outEstaciones;

  return ['status' => true, 'msg' => 'OK', 'data' => $planeacion];
}























}
?>