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


      public function orden($num_orden)
  {
    $num_orden = trim((string) $num_orden);

    if ($num_orden === '') {
      header("Location:" . base_url() . '/plan_planeacion');
      die(); 
    }
    
 

    if (isset($_GET['json']) && $_GET['json'] == '1') {
      header('Content-Type: application/json; charset=utf-8');

      $resp = $this->model->obtenerPlaneacion($num_orden);

      if (empty($resp)) {
        echo json_encode([
          'status' => false,
          'msg' => 'No se encontró la planeación'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }



      if (is_array($resp) && array_key_exists('status', $resp)) {
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'data' => [
          'header' => $resp
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();
    }


    $data['page_tag'] = $num_orden;
    $data['page_title'] = "Orden <small>de trabajo</small>";
    $data['page_name'] = "Orden de trabajo";
    $data['page_functions_js'] = "functions_orden.js";
    $data['arrOrdenDetalle'] = $this->model->obtenerPlaneacion($num_orden);

    // if (empty($data['arrOrdenDetalle'])) {
    //   header("Location:" . base_url() . '/plan_planeacion');
    //   die();
    // }

    $this->views->getView($this, "orden", $data);
  }

    public function Pla_productost()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Ordenes de Trabajo";
        $data['page_title'] = "Ordenes de Trabajo";
        $data['page_name'] = "Productos";
        $data['page_functions_js'] = "functions_pla_productost.js";
        $this->views->getView($this, "pla_productost", $data);
    }



    public function getTodas()
    {
        $arrData = $this->model->selectPlanTodas();

        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }



    // --------------------------------------------------------------------
    // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES PENDIENTGES
    // --------------------------------------------------------------------
    public function getPendientes()
    {



        $arrData = $this->model->selectPlanPendientes();
        // for ($i = 0; $i < count($arrData); $i++) {
        //     $btnView = '';
        //     $btnEdit = '';
        //     $btnDelete = '';
            // if ($arrData[$i]['estado_planeacion'] == 2) {
            //     $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
            // } else if ($arrData[$i]['estado_planeacion'] == 1) {
            //     $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
            // }
            // $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
            // $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';

            // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            // $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
        // }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();

    }



    // --------------------------------------------------------------------
    // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES FINALIZADAS
    // --------------------------------------------------------------------
    public function getFinalizadas()
    {

        $arrData = $this->model->selectPlanFinalizadas();
        // for ($i = 0; $i < count($arrData); $i++) {
        //     $btnView = '';
        //     $btnEdit = '';
        //     $btnDelete = '';

        //     if ($arrData[$i]['estado_planeacion'] == 2) {
        //         $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
        //     } else if ($arrData[$i]['estado_planeacion'] == 1) {
        //         $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
        //     }

        //     $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
        //     $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';



        //     // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        //     $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
        // }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();

    }


    // --------------------------------------------------------------------
    // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES CANCELADAS
    // --------------------------------------------------------------------
    public function getEnProceso()
    {

        $arrData = $this->model->selectPlanEnProceso();
        // for ($i = 0; $i < count($arrData); $i++) {
        //     $btnView = '';
        //     $btnEdit = '';
        //     $btnDelete = '';

        //     if ($arrData[$i]['estado_planeacion'] == 2) {
        //         $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
        //     } else if ($arrData[$i]['estado_planeacion'] == 1) {
        //         $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
        //     }

        //     $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
        //     $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';



        //     // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
        //     $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
        // }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

        die();

    }





}


?>