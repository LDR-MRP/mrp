<?php
class Rpt_mrp_planeacion extends Controllers
{
  public function __construct()
  {
    parent::__construct();
    session_start();
    if (empty($_SESSION['login'])) {
      header('Location: ' . base_url() . '/login');
      die();
    }
  }

  public function Rpt_mrp_planeacion()
  {
    if (empty($_SESSION['permisosMod']['r'])) {
      header("Location:" . base_url() . '/dashboard');
      die();
    }
    $data['page_tag'] = "Reportes";
    $data['page_title'] = "Reportes";
    $data['page_name'] = "Reportes";
    $data['page_functions_js'] = "functions_rpt_mrp_planeacion.js";
    $this->views->getView($this, "rpt_mrp_planeacion", $data);
  }

  private function json($status, $msg, $data = [])
  {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    die();
  }

  private function getFilters()
  {
    $planeacionid = isset($_GET['planeacionid']) ? (int) $_GET['planeacionid'] : 0;
    $fecha_ini = isset($_GET['fecha_ini']) ? trim($_GET['fecha_ini']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    return [
      'planeacionid' => $planeacionid,
      'fecha_ini' => $fecha_ini,
      'fecha_fin' => $fecha_fin,
      'q' => $q
    ];
  }

  // ====== ENDPOINTS EXISTENTES ======
  public function getPlaneaciones()
  {
    $data = $this->model->getPlaneacionesDisponibles();
    $this->json(true, 'OK', $data);
  }

  public function getKpis()
  {
    $f = $this->getFilters();
    $data = $this->model->getKpis($f);
    $this->json(true, 'OK', $data);
  }

  public function getDetalle()
  {
    $f = $this->getFilters();
    $data = $this->model->getDetalle($f);
    $this->json(true, 'OK', $data);
  }

  public function getResumenSubOt()
  {
    $f = $this->getFilters();
    $data = $this->model->getResumenSubOt($f);
    $this->json(true, 'OK', $data);
  }

  public function getEncargados()
  {
    $f = $this->getFilters();
    $data = $this->model->getEncargados($f);
    $this->json(true, 'OK', $data);
  }

  public function getCalidadEstacion()
  {
    $f = $this->getFilters();
    $data = $this->model->getCalidadEstacion($f);
    $this->json(true, 'OK', $data);
  }

  // ====== NUEVOS ENDPOINTS (COSTOS) ======
  public function getCostoTotalPlaneacion()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $row = $this->model->getCostoTotalPlaneacion($f);
    $this->json(true, 'OK', $row);
  }

  public function getCostosEstacion()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $rows = $this->model->getCostosEstacion($f);
    $this->json(true, 'OK', $rows);
  }

  public function getCostosDetalle()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $rows = $this->model->getCostosDetalle($f);
    $this->json(true, 'OK', $rows);
  }
}
?>