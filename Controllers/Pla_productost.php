<?php
class Pla_productost extends Controllers
{
  public function __construct()
  {
    parent::__construct();
    session_start();
    //session_regenerate_id(true); 
    if (empty($_SESSION['login'])) {
      header('Location: ' . base_url() . '/login');
      die();
    }
    getPermisos(MPPRODUCTOSTERMINADOS);
  }

    public function Pla_productost()
  {
    $data['page_tag'] = "Ordenes de Trabajo";
    $data['page_title'] = "Orden <small>de trabajo</small>";
    $data['page_name'] = "Orden de trabajo";
    $data['page_functions_js'] = "functions_pla_productost.js";

    $this->views->getView($this, "pla_productost", $data);
  }

  public function orden($num_orden)
  {
    $num_orden = trim((string) $num_orden); 

    if ($num_orden === '') {
      header("Location:" . base_url() . '/plan_planeacion');
      die();
    }

    $resp = $this->model->obtenerPlaneacion($num_orden);

    if (isset($_GET['json']) && $_GET['json'] == '1') {
      header('Content-Type: application/json; charset=utf-8');

      if (empty($resp)) {
        echo json_encode([
          'status' => false,
          'msg' => 'No se encontró la orden de trabajo'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'data' => $resp
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    $data['page_tag'] = $num_orden;
    $data['page_title'] = "Orden <small>de trabajo</small>";
    $data['page_name'] = "Orden de trabajo";
    $data['page_functions_js'] = "functions_pla_productost_orden.js";
    $data['arrOrdenDetalle'] = $resp;

    $this->views->getView($this, "orden", $data);
  }

  public function getTodas()
  {
    $arrData = $this->model->selectPlanTodas();

    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
  }

  public function getPendientes()
  {
    $arrData = $this->model->selectPlanPendientes();
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
  }

  // --------------------------------------------------------------------
  // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES FINALIZADAS
  // --------------------------------------------------------------------
  public function getFinalizadas()
  {
    $arrData = $this->model->selectPlanFinalizadas();
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();

  }

  // --------------------------------------------------------------------
  // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES CANCELADAS
  // --------------------------------------------------------------------
  public function getEnProceso()
  {

    $arrData = $this->model->selectPlanEnProceso();
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
  }


public function getUnidadPdf()
{
    $num_unidad = trim($_POST['num_unidad'] ?? '');

    if ($num_unidad === '') {
        echo json_encode([
            'status' => false,
            'msg' => 'Unidad inválida.'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    $unidad = $this->model->selectUnidadTerminadaPdf($num_unidad);

    // dep($unidad['clave']);
    // exit;

    if (empty($unidad)) {
        echo json_encode([
            'status' => false,
            'msg' => 'No se encontró información de la unidad.'
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    echo json_encode([
        'status' => true,
        'data' => $unidad,
        'url_qr' => base_url() . "/pla_productost/verUnidad/" . urlencode($unidad['clave'])
    ], JSON_UNESCAPED_UNICODE);
    die();
}


public function verUnidad($num_clave)
{
    $num_clave = trim((string)$num_clave);

    if ($num_clave === '') {
        die("Unidad inválida.");
    }

    echo "La clave generada es: ", $num_clave;

    // $unidad = $this->model->selectUnidadTerminadaPdf($num_clave);

    if (empty($unidad)) {
        die("No se encontró información de la unidad.");
    }

    $data['page_tag'] = "Detalle unidad";
    $data['page_title'] = "Detalle unidad terminada";
    $data['unidad'] = $unidad;

    // $this->views->getView($this, "ver_unidad", $data);
}


}


?>