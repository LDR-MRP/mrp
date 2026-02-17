<?php 

	class Rpt_mrp_planeacionModel extends Mysql
	{


		public function __construct()
		{
			parent::__construct();
		}

          public function getPlaneacionesDisponibles()
  {
    $sql = "SELECT
              p.idplaneacion AS planeacionid,
              p.num_orden,
              pr.descripcion AS producto
            FROM mrp_planeacion p
            INNER JOIN mrp_productos pr ON pr.idproducto = p.productoid
            ORDER BY p.idplaneacion DESC
            LIMIT 200";
    return $this->select_all($sql);
  }

  private function whereFilters($f)
  {
    $where = " WHERE 1=1 ";

    if (!empty($f['planeacionid'])) {
      $pid = (int)$f['planeacionid'];
      $where .= " AND planeacionid = {$pid} ";
    }

    if (!empty($f['fecha_ini'])) {
      $fi = $this->escape($f['fecha_ini']);
      $where .= " AND DATE(COALESCE(fecha_inicio_real, fecha_inicio_planeada)) >= '{$fi}' ";
    }
    if (!empty($f['fecha_fin'])) {
      $ff = $this->escape($f['fecha_fin']);
      $where .= " AND DATE(COALESCE(fecha_inicio_real, fecha_inicio_planeada)) <= '{$ff}' ";
    }

    if (!empty($f['q'])) {
      $q = $this->escapeLike($f['q']);
      $where .= " AND (
        CAST(num_sub_orden AS CHAR) LIKE '%{$q}%'
        OR cve_estacion LIKE '%{$q}%'
        OR nombre_estacion LIKE '%{$q}%'
        OR proceso LIKE '%{$q}%'
        OR encargado_nombre LIKE '%{$q}%'
        OR ayudante_nombre LIKE '%{$q}%'
      ) ";
    }

    return $where;
  }


  private function escape($s){ return str_replace(["\\","'"], ["\\\\","\\'"], $s); }
  private function escapeLike($s){ return $this->escape($s); }

  public function getKpis($f)
  {
    $where = $this->whereFilters($f);

    $sql = "SELECT
              COUNT(DISTINCT num_sub_orden) AS subot,


              AVG(
                CASE
                  WHEN cerrada=1 AND duracion_real_min IS NOT NULL AND duracion_real_min>0
                       AND estandar_min IS NOT NULL AND estandar_min>0
                  THEN (estandar_min / duracion_real_min) * 100
                  ELSE NULL
                END
              ) AS eficiencia_prom,

  
              (SUM(CASE WHEN cerrada=1 AND en_tiempo=1 THEN 1 ELSE 0 END) /
               NULLIF(SUM(CASE WHEN cerrada=1 THEN 1 ELSE 0 END),0)
              ) * 100 AS pct_en_tiempo,

              SUM(CASE WHEN calidad=4 THEN 1 ELSE 0 END) AS rechazos
            FROM w_ot_kpi_base
            {$where}";

    $row = $this->select($sql);
    if (empty($row)) {
      return ['subot'=>0,'eficiencia_prom'=>null,'pct_en_tiempo'=>null,'rechazos'=>0];
    }
    return $row;
  }

  public function getDetalle($f)
  {
    $where = $this->whereFilters($f);

    $sql = "SELECT
              idorden, num_sub_orden,
              planeacionid, num_orden, productoid, supervisorid, prioridad,
              estacionid, cve_estacion, nombre_estacion, proceso, orden_estacion,
              encargado_id, encargado_nombre, ayudante_id, ayudante_nombre,
              estandar_min, fecha_inicio_real, fecha_fin_real, duracion_real_min,
              estatus, calidad, cerrada, en_tiempo, eficiencia_pct_base
            FROM w_ot_kpi_base
            {$where}
            ORDER BY num_sub_orden ASC, orden_estacion ASC, idorden ASC";
    return $this->select_all($sql);
  }

  public function getResumenSubOt($f)
  {
    $where = $this->whereFilters($f);

    $sql = "SELECT
              num_sub_orden,
              SUM(COALESCE(estandar_min,0)) AS std_total,
              SUM(COALESCE(duracion_real_min,0)) AS real_total,

              CASE
                WHEN SUM(COALESCE(duracion_real_min,0))>0 AND SUM(COALESCE(estandar_min,0))>0
                THEN (SUM(COALESCE(estandar_min,0)) / SUM(COALESCE(duracion_real_min,0))) * 100
                ELSE NULL
              END AS eficiencia,

              (SUM(CASE WHEN cerrada=1 AND en_tiempo=1 THEN 1 ELSE 0 END) /
               NULLIF(SUM(CASE WHEN cerrada=1 THEN 1 ELSE 0 END),0)
              ) * 100 AS pct_en_tiempo,

              SUM(CASE WHEN calidad=4 THEN 1 ELSE 0 END) AS rechazos,
              MAX(estatus) AS ultimo_estatus,
              MAX(calidad) AS ultima_calidad
            FROM w_ot_kpi_base
            {$where}
            GROUP BY num_sub_orden
            ORDER BY num_sub_orden ASC";
    return $this->select_all($sql);
  }

  public function getEncargados($f)
  {
    $where = $this->whereFilters($f);

    $sql = "SELECT
              encargado_id,
              encargado_nombre,
              COUNT(*) AS registros,
              SUM(COALESCE(duracion_real_min,0)) AS real_total,
              AVG(CASE WHEN cerrada=1 AND eficiencia_pct_base IS NOT NULL THEN eficiencia_pct_base ELSE NULL END) AS eficiencia_prom,
              (SUM(CASE WHEN cerrada=1 AND en_tiempo=1 THEN 1 ELSE 0 END) /
               NULLIF(SUM(CASE WHEN cerrada=1 THEN 1 ELSE 0 END),0)
              ) * 100 AS pct_en_tiempo,
              SUM(CASE WHEN calidad=4 THEN 1 ELSE 0 END) AS rechazos
            FROM w_ot_kpi_base
            {$where}
            GROUP BY encargado_id, encargado_nombre
            ORDER BY real_total DESC, eficiencia_prom DESC";
    return $this->select_all($sql);
  }

  public function getCalidadEstacion($f)
  {
    $where = $this->whereFilters($f);

    $sql = "SELECT
              estacionid,
              cve_estacion,
              nombre_estacion,
              SUM(CASE WHEN calidad=1 THEN 1 ELSE 0 END) AS c1,
              SUM(CASE WHEN calidad=2 THEN 1 ELSE 0 END) AS c2,
              SUM(CASE WHEN calidad=3 THEN 1 ELSE 0 END) AS c3,
              SUM(CASE WHEN calidad=4 THEN 1 ELSE 0 END) AS c4,
              SUM(CASE WHEN calidad=5 THEN 1 ELSE 0 END) AS c5,
              COUNT(*) AS total
            FROM w_ot_kpi_base
            {$where}
            GROUP BY estacionid, cve_estacion, nombre_estacion
            ORDER BY total DESC";
    return $this->select_all($sql);
  }

      public function getCostoTotalPlaneacionold(int $planeacionid): array
    {
        $planeacionid = intval($planeacionid);

        $sql = "
            SELECT
                p.idplaneacion AS planeacionid,
                p.num_orden,
                p.productoid,
                SUM(
                    (p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo, 0)
                ) AS costo_total_planeacion
            FROM mrp_planeacion p
            INNER JOIN mrp_estacion_componentes ec
                ON ec.productoid = p.productoid
               AND ec.estado = 2
            INNER JOIN wms_inventario inv
                ON inv.idinventario = ec.inventarioid
            WHERE p.idplaneacion = {$planeacionid}
            GROUP BY p.idplaneacion, p.num_orden, p.productoid
            LIMIT 1
        ";

        $row = $this->select($sql);
        if (empty($row)) {
            return [
                'planeacionid' => $planeacionid,
                'costo_total_planeacion' => 0
            ];
        }

        $row['costo_total_planeacion'] = floatval($row['costo_total_planeacion'] ?? 0);
        return $row;
    }


    public function getCostosPorEstacionConCalidad(int $planeacionid): array
    {
        $planeacionid = intval($planeacionid);

        $sql = "
            SELECT
                t.planeacionid,
                t.estacionid,
                t.cve_estacion,
                t.nombre_estacion,
                t.proceso,
                t.costo_total_estacion,

               
                MAX(k.encargado_nombre) AS encargado_nombre,
                MAX(k.ayudante_nombre)  AS ayudante_nombre,

             
                SUM(CASE WHEN k.calidad = 1 THEN 1 ELSE 0 END) AS c1,
                SUM(CASE WHEN k.calidad = 2 THEN 1 ELSE 0 END) AS c2,
                SUM(CASE WHEN k.calidad = 3 THEN 1 ELSE 0 END) AS c3,
                SUM(CASE WHEN k.calidad = 4 THEN 1 ELSE 0 END) AS c4,
                SUM(CASE WHEN k.calidad = 5 THEN 1 ELSE 0 END) AS c5,
                COUNT(k.idorden) AS total_registros
            FROM (
                SELECT
                    p.idplaneacion AS planeacionid,
                    ec.estacionid,
                    e.cve_estacion,
                    e.nombre_estacion,
                    e.proceso,
                    SUM(
                        (p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo,0)
                    ) AS costo_total_estacion
                FROM mrp_planeacion p
                INNER JOIN mrp_estacion_componentes ec
                    ON ec.productoid = p.productoid
                   AND ec.estado = 2
                INNER JOIN wms_inventario inv
                    ON inv.idinventario = ec.inventarioid
                LEFT JOIN mrp_estacion e
                    ON e.idestacion = ec.estacionid
                WHERE p.idplaneacion = {$planeacionid}
                GROUP BY p.idplaneacion, ec.estacionid, e.cve_estacion, e.nombre_estacion, e.proceso
            ) t
            LEFT JOIN w_ot_kpi_base k
                ON k.planeacionid = t.planeacionid
               AND k.estacionid   = t.estacionid
            GROUP BY
                t.planeacionid, t.estacionid, t.cve_estacion, t.nombre_estacion, t.proceso, t.costo_total_estacion
            ORDER BY t.estacionid
        ";

        $rows = $this->select_all($sql);
    
        foreach ($rows as &$r) {
            $r['costo_total_estacion'] = floatval($r['costo_total_estacion'] ?? 0);
            $r['c1'] = intval($r['c1'] ?? 0);
            $r['c2'] = intval($r['c2'] ?? 0);
            $r['c3'] = intval($r['c3'] ?? 0);
            $r['c4'] = intval($r['c4'] ?? 0);
            $r['c5'] = intval($r['c5'] ?? 0);
            $r['total_registros'] = intval($r['total_registros'] ?? 0);
        }
        return $rows;
    }


    public function getCostosDetalleComponentes(int $planeacionid): array
    {
        $planeacionid = intval($planeacionid);

        $sql = "
            SELECT
                p.idplaneacion AS planeacionid,
                p.num_orden,
                ec.estacionid,
                e.cve_estacion,
                e.nombre_estacion,
                e.proceso,

                ec.inventarioid,
                inv.cve_articulo,
                inv.descripcion,

                p.cantidad AS cantidad_planeada,
                ec.cantidad AS cantidad_por_producto,
                (p.cantidad * ec.cantidad) AS cantidad_total_requerida,

                inv.ultimo_costo,
                ((p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo,0)) AS costo_total_articulo
            FROM mrp_planeacion p
            INNER JOIN mrp_estacion_componentes ec
                ON ec.productoid = p.productoid
               AND ec.estado = 2
            INNER JOIN wms_inventario inv
                ON inv.idinventario = ec.inventarioid
            LEFT JOIN mrp_estacion e
                ON e.idestacion = ec.estacionid
            WHERE p.idplaneacion = {$planeacionid}
            ORDER BY ec.estacionid, inv.descripcion
        ";

        $rows = $this->select_all($sql);
        foreach ($rows as &$r) {
            $r['cantidad_planeada'] = floatval($r['cantidad_planeada'] ?? 0);
            $r['cantidad_por_producto'] = floatval($r['cantidad_por_producto'] ?? 0);
            $r['cantidad_total_requerida'] = floatval($r['cantidad_total_requerida'] ?? 0);
            $r['ultimo_costo'] = floatval($r['ultimo_costo'] ?? 0);
            $r['costo_total_articulo'] = floatval($r['costo_total_articulo'] ?? 0);
        }
        return $rows;
    }


public function getCostoTotalPlaneacion($f): array
{
  $pid = (int)($f['planeacionid'] ?? 0);

  $sql = "
    SELECT
      p.idplaneacion AS planeacionid,
      SUM((p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo,0)) AS costo_total_planeacion
    FROM mrp_planeacion p
    INNER JOIN mrp_estacion_componentes ec
      ON ec.productoid = p.productoid
     AND ec.estado = 2
    INNER JOIN wms_inventario inv
      ON inv.idinventario = ec.inventarioid
    WHERE p.idplaneacion = {$pid}
    GROUP BY p.idplaneacion
    LIMIT 1
  ";

  $row = $this->select($sql);
  return [
    'costo_total_planeacion' => floatval($row['costo_total_planeacion'] ?? 0)
  ];
}

public function getCostosEstacion($f): array
{
  $pid = (int)($f['planeacionid'] ?? 0);


  $qSql = "";
  if (!empty($f['q'])) {
    $q = $this->escapeLike($f['q']);
    $qSql = " AND (
      k.encargado_nombre LIKE '%{$q}%'
      OR k.ayudante_nombre LIKE '%{$q}%'
      OR t.cve_estacion LIKE '%{$q}%'
      OR t.nombre_estacion LIKE '%{$q}%'
      OR t.proceso LIKE '%{$q}%'
    ) ";
  }

  $sql = "
    SELECT
      t.planeacionid,
      t.estacionid,
      t.cve_estacion,
      t.nombre_estacion,
      t.proceso,
      t.costo_total_estacion,

      MAX(k.encargado_nombre) AS encargado_nombre,
      MAX(k.ayudante_nombre)  AS ayudante_nombre,

      SUM(CASE WHEN k.calidad = 1 THEN 1 ELSE 0 END) AS c1,
      SUM(CASE WHEN k.calidad = 2 THEN 1 ELSE 0 END) AS c2,
      SUM(CASE WHEN k.calidad = 3 THEN 1 ELSE 0 END) AS c3,
      SUM(CASE WHEN k.calidad = 4 THEN 1 ELSE 0 END) AS c4,
      SUM(CASE WHEN k.calidad = 5 THEN 1 ELSE 0 END) AS c5,
      COUNT(k.idorden) AS total_registros

    FROM (
      SELECT
        p.idplaneacion AS planeacionid,
        ec.estacionid,
        e.cve_estacion,
        e.nombre_estacion,
        e.proceso,
        SUM((p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo,0)) AS costo_total_estacion
      FROM mrp_planeacion p
      INNER JOIN mrp_estacion_componentes ec
        ON ec.productoid = p.productoid
       AND ec.estado = 2
      INNER JOIN wms_inventario inv
        ON inv.idinventario = ec.inventarioid
      LEFT JOIN mrp_estacion e
        ON e.idestacion = ec.estacionid
      WHERE p.idplaneacion = {$pid}
      GROUP BY p.idplaneacion, ec.estacionid, e.cve_estacion, e.nombre_estacion, e.proceso
    ) t
    LEFT JOIN w_ot_kpi_base k
      ON k.planeacionid = t.planeacionid
     AND k.estacionid   = t.estacionid
    WHERE 1=1
    {$qSql}
    GROUP BY
      t.planeacionid, t.estacionid, t.cve_estacion, t.nombre_estacion, t.proceso, t.costo_total_estacion
    ORDER BY t.estacionid
  ";

  $rows = $this->select_all($sql);
  foreach ($rows as &$r) {
    $r['costo_total_estacion'] = floatval($r['costo_total_estacion'] ?? 0);
    $r['c1'] = intval($r['c1'] ?? 0);
    $r['c2'] = intval($r['c2'] ?? 0);
    $r['c3'] = intval($r['c3'] ?? 0);
    $r['c4'] = intval($r['c4'] ?? 0);
    $r['c5'] = intval($r['c5'] ?? 0);
    $r['total_registros'] = intval($r['total_registros'] ?? 0);
  }
  return $rows;
}

public function getCostosDetalle($f): array
{
  $pid = (int)($f['planeacionid'] ?? 0);

  $qSql = "";
  if (!empty($f['q'])) {
    $q = $this->escapeLike($f['q']);
    $qSql = " AND (
      k.encargado_nombre LIKE '%{$q}%'
      OR k.ayudante_nombre LIKE '%{$q}%'
      OR e.cve_estacion LIKE '%{$q}%'
      OR e.nombre_estacion LIKE '%{$q}%'
      OR e.proceso LIKE '%{$q}%'
      OR inv.cve_articulo LIKE '%{$q}%'
      OR inv.descripcion LIKE '%{$q}%'
    ) ";
  }

  $sql = "
    SELECT
      p.idplaneacion AS planeacionid,
      p.num_orden,

      ec.estacionid,
      e.cve_estacion,
      e.nombre_estacion,
      e.proceso,

      -- opcional: para mostrar responsable en detalle (y filtrar por Eduardo)
      MAX(k.encargado_nombre) AS encargado_nombre,
      MAX(k.ayudante_nombre)  AS ayudante_nombre,

      ec.inventarioid,
      inv.cve_articulo,
      inv.descripcion,

      p.cantidad AS cantidad_planeada,
      ec.cantidad AS cantidad_por_producto,
      (p.cantidad * ec.cantidad) AS cantidad_total_requerida,

      inv.ultimo_costo,
      ((p.cantidad * ec.cantidad) * IFNULL(inv.ultimo_costo,0)) AS costo_total_articulo

    FROM mrp_planeacion p
    INNER JOIN mrp_estacion_componentes ec
      ON ec.productoid = p.productoid
     AND ec.estado = 2
    INNER JOIN wms_inventario inv
      ON inv.idinventario = ec.inventarioid
    LEFT JOIN mrp_estacion e
      ON e.idestacion = ec.estacionid
    LEFT JOIN w_ot_kpi_base k
      ON k.planeacionid = p.idplaneacion
     AND k.estacionid   = ec.estacionid

    WHERE p.idplaneacion = {$pid}
    {$qSql}

    GROUP BY
      p.idplaneacion, p.num_orden,
      ec.estacionid, e.cve_estacion, e.nombre_estacion, e.proceso,
      ec.inventarioid, inv.cve_articulo, inv.descripcion,
      p.cantidad, ec.cantidad, inv.ultimo_costo

    ORDER BY ec.estacionid, inv.descripcion
  ";

  $rows = $this->select_all($sql);
  foreach ($rows as &$r) {
    $r['cantidad_planeada'] = floatval($r['cantidad_planeada'] ?? 0);
    $r['cantidad_por_producto'] = floatval($r['cantidad_por_producto'] ?? 0);
    $r['cantidad_total_requerida'] = floatval($r['cantidad_total_requerida'] ?? 0);
    $r['ultimo_costo'] = floatval($r['ultimo_costo'] ?? 0);
    $r['costo_total_articulo'] = floatval($r['costo_total_articulo'] ?? 0);
  }
  return $rows;
}




	}
 ?>