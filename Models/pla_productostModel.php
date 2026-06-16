<?php 

	class pla_productostModel extends Mysql
	{


		public function __construct()
		{
			parent::__construct();
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