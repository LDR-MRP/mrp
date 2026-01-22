<?php
class Plan_planeacion extends Controllers
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
    getPermisos(MPPLANPRODUCCION);
  }

  public function Plan_planeacion()
  {
    if (empty($_SESSION['permisosMod']['r'])) {
      header("Location:" . base_url() . '/dashboard');
    }
    $data['page_tag'] = "Planeación";
    $data['page_title'] = "Plan de producción";
    $data['page_name'] = "Planeación";
    $data['page_functions_js'] = "functions_plan_planeacion.js";
    $this->views->getView($this, "plan_planeacion", $data);
  }


  public function getSelectProductos()
  {
    $htmlOptions = '<option value="" selected>--Seleccione--</option>';
    $arrData = $this->model->selectOptionProductos();
    if (count($arrData) > 0) {
      for ($i = 0; $i < count($arrData); $i++) {
        if ($arrData[$i]['estado'] == 2) {
          $htmlOptions .= '<option value="' . $arrData[$i]['idproducto'] . '">' . $arrData[$i]['cve_producto'] . ' - ' . $arrData[$i]['descripcion'] . '</option>';
        }
      }
    }
    echo $htmlOptions;
    die();
  }

  public function getSelectEstaciones($idproducto)
  {
    $arrData = $this->model->selectOptionEstacionesByProducto($idproducto);
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
  }


  public function getSelectOperadores()
  {

    $htmlOptions = '';
    $arrData = $this->model->selectOperadores();
    if (count($arrData) > 0) {
      for ($i = 0; $i < count($arrData); $i++) {
        if ($arrData[$i]['status'] == 1) {
          $email = htmlspecialchars((string) $arrData[$i]['email_user'], ENT_QUOTES, 'UTF-8');
          $htmlOptions .= '<option data-email="' . $email . '" value="' . $arrData[$i]['idusuario'] . '">' . $arrData[$i]['nombres'] . ' ' . $arrData[$i]['apellidos'] . '</option>';
        }
      }
    }
    echo $htmlOptions;
    die();
  }

  public function getSelectOperadoresAyudantes()
  {

    $htmlOptions = '';
    $arrData = $this->model->selectOperadoresAyudantes();
    if (count($arrData) > 0) {
      for ($i = 0; $i < count($arrData); $i++) {
        if ($arrData[$i]['status'] == 1) {
          $email = htmlspecialchars((string) $arrData[$i]['email_user'], ENT_QUOTES, 'UTF-8');
          $htmlOptions .= '<option data-email="' . $email . '" value="' . $arrData[$i]['idusuario'] . '">' . $arrData[$i]['nombres'] . ' ' . $arrData[$i]['apellidos'] . '</option>';
        }
      }
    }
    echo $htmlOptions;
    die();
  }


public function setPlaneacion()
{
  header('Content-Type: application/json');

  $fecha_notificacion = date('Y-m-d');

  $json = file_get_contents('php://input');
  $data = json_decode($json, true);

  if (!is_array($data)) {
    echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
    die();
  }

  $h = $data['header'] ?? [];

  $productoid       = (int)($h['productoid'] ?? 0);
  $pedido           = trim((string)($h['pedido'] ?? ''));
  $supervisor       = trim((string)($h['supervisor'] ?? ''));
  $prioridad        = trim((string)($h['prioridad'] ?? ''));
  $cantidad         = (int)($h['cantidad'] ?? 0);
  $fecha_inicio     = trim((string)($h['fecha_inicio'] ?? ''));
  $fecha_requerida  = trim((string)($h['fecha_requerida'] ?? ''));
  $notas            = trim((string)($h['notas'] ?? ''));

  if ($productoid <= 0) {
    echo json_encode(['status' => false, 'msg' => 'Falta producto']);
    die();
  }
  if ($prioridad === '') {
    echo json_encode(['status' => false, 'msg' => 'Falta prioridad']);
    die();
  }
  if ($cantidad <= 0) {
    echo json_encode(['status' => false, 'msg' => 'Cantidad inválida']);
    die();
  }
  if ($fecha_inicio === '' || $fecha_requerida === '') {
    echo json_encode(['status' => false, 'msg' => 'Faltan fechas']);
    die();
  }

  $asignaciones = $data['asignaciones'] ?? [];
  if (!is_array($asignaciones) || count($asignaciones) === 0) {
    echo json_encode(['status' => false, 'msg' => 'No hay asignaciones']);
    die();
  }


  $idsEncargados = [];
  $idsAyudantes  = [];

  foreach ($asignaciones as $a) {
    $estacionid = (int)($a['estacionid'] ?? 0);
    $orden      = (int)($a['orden'] ?? 0);
    $encargado  = (int)($a['encargado'] ?? 0);
    $ayudantes  = $a['ayudantes'] ?? [];

    if ($estacionid <= 0 || $orden <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Asignación inválida (estacionid/orden)']);
      die();
    }
    if ($encargado <= 0) {
      echo json_encode(['status' => false, 'msg' => "Falta encargado en estación orden {$orden}"]);
      die();
    }
    if (!is_array($ayudantes) || count($ayudantes) < 1) {
      echo json_encode(['status' => false, 'msg' => "Faltan ayudantes en estación orden {$orden}"]);
      die();
    }

    $idsEncargados[] = $encargado;

    foreach ($ayudantes as $uid) {
      $uid = (int)$uid;
      if ($uid > 0) $idsAyudantes[] = $uid;
    }
  }

  $idsEncargados = array_values(array_unique(array_map('intval', $idsEncargados)));
  $idsAyudantes  = array_values(array_unique(array_map('intval', $idsAyudantes)));


  $idsAyudantes = array_values(array_diff($idsAyudantes, $idsEncargados));


  $destEnc = [];
  $destAy  = [];

  if (!empty($idsEncargados)) {
    $arrEnc = $this->model->getEmailsUsuariosByIds($idsEncargados);
    foreach ($arrEnc as $u) {
      $email = trim((string)($u['email_user'] ?? ''));
      if ($email === '') continue;

      $nombre = trim((string)($u['nombres'] ?? ''));
      if ($nombre === '') $nombre = '—';

      $destEnc[] = [
        'idusuario' => (int)($u['idusuario'] ?? 0),
        'email'     => $email,
        'nombre'    => $nombre,
      ];
    }
  }

  if (!empty($idsAyudantes)) {
    $arrAy = $this->model->getEmailsUsuariosByIds($idsAyudantes);
    foreach ($arrAy as $u) {
      $email = trim((string)($u['email_user'] ?? ''));
      if ($email === '') continue;

      $nombre = trim((string)($u['nombres'] ?? ''));
      if ($nombre === '') $nombre = '—';

      $destAy[] = [
        'idusuario' => (int)($u['idusuario'] ?? 0),
        'email'     => $email,
        'nombre'    => $nombre,
      ];
    }
  }

  $emailsEnc = array_values(array_unique(array_column($destEnc, 'email')));
  $emailsAy  = array_values(array_unique(array_column($destAy, 'email')));
 
  try {

    $num_orden = $this->model->generarNumeroOrden();

    $request_CONFIGURACION = $this->model->insertPlaneacion(
      $num_orden,
      $productoid,
      $pedido,
      $supervisor,
      $prioridad,
      $cantidad,
      $fecha_inicio,
      $fecha_requerida,
      $notas
    );

    if ((int)$request_CONFIGURACION <= 0) {
      throw new Exception('No se pudo registrar la planeación (cabecera)');
    }

    $idplaneacion = (int)$request_CONFIGURACION;

    // ---------------------------------------------------------
    //  Producto (para correos)
    // ---------------------------------------------------------
    $cve_producto = '';
    $descripcion  = '';

    $request_Producto = $this->model->getProducto($productoid);
    if (is_array($request_Producto) && !empty($request_Producto)) {
      $cve_producto = (string)($request_Producto['cve_producto'] ?? '');
      $descripcion  = (string)($request_Producto['descripcion'] ?? '');
    }

    // ---------------------------------------------------------
    //  DETALLE: estaciones y operadores
    // ---------------------------------------------------------
    foreach ($asignaciones as $a) {
      $estacionid = (int)$a['estacionid']; 
      $orden      = (int)$a['orden'];
      $encargado  = (int)$a['encargado'];
      $ayudantes  = is_array($a['ayudantes']) ? $a['ayudantes'] : [];

      $id_planeacion_estacion = (int)$this->model->upsertPlaneacionEstacion(
        $idplaneacion,
        $estacionid,
        $orden
      );

      if ($id_planeacion_estacion <= 0) {
        throw new Exception("No se pudo guardar estación {$estacionid} en planeación");
      }

      for ($s = 1; $s <= $cantidad; $s++) {
        $num_orden_s = $num_orden . '-S' . str_pad((string)$s, 2, '0', STR_PAD_LEFT);

        $okOrd = $this->model->insertOrdenes($id_planeacion_estacion, $num_orden_s);

        if ((int)$okOrd <= 0) {
          throw new Exception("No se pudo insertar orden {$num_orden_s} para planeación_estación {$id_planeacion_estacion}");
        }
      }

      $this->model->clearOperadoresByPlaneacionEstacion($id_planeacion_estacion);

      // encargado
      $okEnc = $this->model->insertPlaneacionOperador(
        $id_planeacion_estacion,
        $encargado,
        'ENCARGADO'
      );
      if ((int)$okEnc <= 0) {
        throw new Exception("No se pudo guardar encargado en estación {$estacionid}");
      }

      // ayudantes (sin duplicados)
      $setAy = [];
      foreach ($ayudantes as $uid) {
        $uid = (int)$uid;
        if ($uid <= 0) continue;
        if (isset($setAy[$uid])) continue;
        $setAy[$uid] = true;

        $okAy = $this->model->insertPlaneacionOperador(
          $id_planeacion_estacion,
          $uid,
          'AYUDANTE'
        );
        if ((int)$okAy <= 0) {
          throw new Exception("No se pudo guardar ayudante {$uid} en estación {$estacionid}");
        }
      }
    }

    // ---------------------------------------------------------
    //  Base para correo
    // ---------------------------------------------------------
    $infoBase = [
      'idplaneacion'         => $idplaneacion,
      'num_orden'            => $num_orden,
      'productoid'           => $productoid,
      'pedido'               => $pedido,
      'prioridad'            => $prioridad,
      'cantidad'             => $cantidad,
      'fecha_inicio'         => $fecha_inicio,
      'fecha_requerida'      => $fecha_requerida,
      'fecha_inicio_txt'     => formatFechaLargaEs($fecha_inicio),
      'fecha_requerida_txt'  => formatFechaLargaEs($fecha_requerida),
      'supervisor'           => $supervisor,
      'notas'                => $notas,
      'cve_producto'         => $cve_producto,
      'descripcion'          => $descripcion,
      'fecha_notificacion'   => formatFechaLargaEs($fecha_notificacion),
    ];

    $mail = [
      'encargados' => ['status' => true, 'msg' => 'OK', 'to_count' => count($emailsEnc)],
      'ayudantes'  => ['status' => true, 'msg' => 'OK', 'to_count' => count($emailsAy)],
    ];

    $cc = 'carlos.cruz@ldrsolutions.com.mx';

    // ---------------------------------------------------------
    //  ENCARGADOS
    // ---------------------------------------------------------
    try {
      if (!empty($destEnc)) {
        foreach ($destEnc as $dest) {
          $dataMail = $infoBase;
          $dataMail['nombre'] = $dest['nombre'];
          $dataMail['email']  = $dest['email'];
          $dataMail['asunto'] = 'OT generada';

          sendMailLocalCron($dataMail, 'email_new_ot_encargado', $cc);
        }
      } else {
        $mail['encargados'] = ['status' => false, 'msg' => 'Sin correos válidos', 'to_count' => 0];
      }
    } catch (Exception $e1) {
      $mail['encargados'] = ['status' => false, 'msg' => $e1->getMessage(), 'to_count' => count($emailsEnc)];
    }

    // ---------------------------------------------------------
    //  AYUDANTES
    // ---------------------------------------------------------
    try {
      if (!empty($destAy)) {
        foreach ($destAy as $dest) {
          $dataMail = $infoBase;
          $dataMail['nombre'] = $dest['nombre'];
          $dataMail['email']  = $dest['email'];
          $dataMail['asunto'] = 'Asignación de OT';

          sendMailLocalCron($dataMail, 'email_new_ot_ayudante', $cc);
        }
      } else {
        $mail['ayudantes'] = ['status' => false, 'msg' => 'Sin correos válidos', 'to_count' => 0];
      }
    } catch (Exception $e2) {
      $mail['ayudantes'] = ['status' => false, 'msg' => $e2->getMessage(), 'to_count' => count($emailsAy)];
    }

    echo json_encode([
      'status'      => true,
      'msg'         => 'Planeación guardada correctamente',
      'idplaneacion'=> $idplaneacion,
      'mail'        => $mail,
      'num_planeacion' => $num_orden
    ]);
    die(); 

  } catch (Exception $e) {
    echo json_encode([
      'status' => false,
      'msg'    => $e->getMessage()
    ]);
    die();
  }
}




  // --------------------------------------------------------------------
  // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES PENDIENTGES
  // --------------------------------------------------------------------
  public function getPendientes()
  {



    $arrData = $this->model->selectPlanPendientes();
    for ($i = 0; $i < count($arrData); $i++) {
      $btnView = '';
      $btnEdit = '';
      $btnDelete = '';

      if ($arrData[$i]['estado_planeacion'] == 2) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
      } else if ($arrData[$i]['estado_planeacion'] == 1) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
      }

      $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
      $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';



      // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
      $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
    }
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

    die();

  }



  // --------------------------------------------------------------------
  // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES FINALIZADAS
  // --------------------------------------------------------------------
  public function getFinalizadas()
  {

    $arrData = $this->model->selectPlanFinalizadas();
    for ($i = 0; $i < count($arrData); $i++) {
      $btnView = '';
      $btnEdit = '';
      $btnDelete = '';

      if ($arrData[$i]['estado_planeacion'] == 2) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
      } else if ($arrData[$i]['estado_planeacion'] == 1) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
      }

      $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
      $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';



      // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
      $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
    }
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

    die();

  }


  // --------------------------------------------------------------------
  // FUNCIÓN PARA LISTAR TODAS LAS PLANEACIONES CANCELADAS
  // --------------------------------------------------------------------
  public function getCanceladas()
  {

    $arrData = $this->model->selectPlanCanceladas();
    for ($i = 0; $i < count($arrData); $i++) {
      $btnView = '';
      $btnEdit = '';
      $btnDelete = '';

      if ($arrData[$i]['estado_planeacion'] == 2) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-success">Activo</span>';
      } else if ($arrData[$i]['estado_planeacion'] == 1) {
        $arrData[$i]['estado_planeacion'] = '<span class="badge bg-danger">Inactivo</span>';
      }

      $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
      $btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idplaneacion'] . ')"><i class="ri-file-text-line me-1"></i></button>';



      // $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
      $arrData[$i]['options'] = '<div class="text-center">' . $btnReporte . ' ' . $btnEdit . '</div>';
    }
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);

    die();

  }

  public function validarExistencias()
  {
    header('Content-Type: application/json');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }

    $productoid = (int) ($data['productoid'] ?? 0);
    $cantidad = (int) ($data['cantidad'] ?? 0);
    $estaciones = $data['estaciones'] ?? [];

    if ($productoid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta productoid']);
      die();
    }
    if ($cantidad <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Cantidad inválida']);
      die();
    }
    if (!is_array($estaciones) || count($estaciones) === 0) {
      echo json_encode(['status' => false, 'msg' => 'No hay estaciones']);
      die();
    }

    $errores = [];

    foreach ($estaciones as $e) {
      $estacionid = (int) ($e['estacionid'] ?? 0);
      if ($estacionid <= 0)
        continue;

      $res = $this->model->consultarExistencias($productoid, $estacionid, $cantidad);


      if (!empty($res) && isset($res['status']) && (int) $res['status'] === 0 && !empty($res['data'])) {


        foreach ($res['data'] as $row) {
          $row['msg'] = $res['msg'] ?? 'Faltan componentes en inventario';
          $errores[] = $row;
        }
      }
    }

    if (count($errores) > 0) {
      echo json_encode([
        'status' => false,
        'msg' => 'Faltan componentes en inventario para una o más estaciones.',
        'errores' => $errores
      ]);
      die();
    }

    echo json_encode(['status' => true, 'msg' => 'OK']);
    die();
  }






  public function validarHerramientasExistencias()
  {
    header('Content-Type: application/json');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }

    $productoid = (int) ($data['productoid'] ?? 0);
    $cantidad = (int) ($data['cantidad'] ?? 0);
    $estaciones = $data['estaciones'] ?? [];

    if ($productoid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta productoid']);
      die();
    }
    if ($cantidad <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Cantidad inválida']);
      die();
    }
    if (!is_array($estaciones) || count($estaciones) === 0) {
      echo json_encode(['status' => false, 'msg' => 'No hay estaciones']);
      die();
    }

    $errores = [];

    foreach ($estaciones as $e) {
      $estacionid = (int) ($e['estacionid'] ?? 0);
      if ($estacionid <= 0)
        continue;


      $res = $this->model->consultarHerramientasExistencias($productoid, $estacionid, $cantidad);

      if (isset($res['status']) && ($res['status'] === false || $res['status'] === 0) && !empty($res['data'])) {
        // acumulamos
        foreach ($res['data'] as $row) {
          $errores[] = $row;
        }
      }
    }

    if (count($errores) > 0) {
      echo json_encode([
        'status' => false,
        'msg' => 'Faltan herramientas en inventario para una o más estaciones.',
        'errores' => $errores
      ]);
      die();
    }

    echo json_encode(['status' => true, 'msg' => 'OK']);
    die();
  }

  public function getDataPlaneacion($idplaneacion)
  {
    header('Content-Type: application/json; charset=utf-8');
    $idplaneacion = (int) $idplaneacion;
    if ($idplaneacion <= 0) {
      echo json_encode(['status' => false, 'msg' => 'ID de planeación inválido'], JSON_UNESCAPED_UNICODE);
      die();
    }
    $request_planeacion = $this->model->obtenerPlaneacion($idplaneacion);
    $arrResponse = array('status' => true, 'data' => $request_planeacion);

    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    die();
  }



  // FUNCIÓN PARA MNADAR A TRAER LA VISTA DE LA ORDEN DE TRABAJO
public function orden($num_orden)
{
  $num_orden = trim((string)$num_orden);

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
        'msg'    => 'No se encontró la planeación'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }



    if (is_array($resp) && array_key_exists('status', $resp)) {
      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();
    }

    echo json_encode([
      'status' => true,
      'data'   => [
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

  if (empty($data['arrOrdenDetalle'])) {
    header("Location:" . base_url() . '/plan_planeacion');
    die();
  }

  $this->views->getView($this, "orden", $data);
}




//FUNCIÓN PARA GUARDAr el co9mentari

public function setCommentario()
{
    header('Content-Type: application/json; charset=utf-8');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
        echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
        die();
    }


    $idorden     = isset($data['idorden']) ? trim((string)$data['idorden']) : '';
    $comentario  = isset($data['comentario']) ? trim((string)$data['comentario']) : '';

    if ($idorden === '') {
        echo json_encode(['status' => false, 'msg' => 'Falta idorden']);
        die();
    }

    $resp = $this->model->updateComentarioOrden($idorden,$comentario);



    echo json_encode([
        'status' => true,
        'msg'    => 'Comentario actualizado'
    ]);
    die();
}

public function startOT()
{
  header('Content-Type: application/json');

  $data = json_decode(file_get_contents('php://input'), true);
  if (!is_array($data)) { echo json_encode(['status'=>false,'msg'=>'JSON inválido']); die(); }

  $idorden      = (int)($data['idorden'] ?? 0);
  $fecha_inicio = trim((string)($data['fecha_inicio'] ?? ''));

  if ($idorden <= 0) { echo json_encode(['status'=>false,'msg'=>'Falta idorden']); die(); }
  if ($fecha_inicio === '') { echo json_encode(['status'=>false,'msg'=>'Falta fecha_inicio']); die(); }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $fecha_inicio)) {
    echo json_encode(['status'=>false,'msg'=>'Formato de fecha_inicio inválido']); die();
  }

  echo json_encode($this->model->startOT($idorden, $fecha_inicio));
  die();
}

public function finishOT()
{
  header('Content-Type: application/json');

  $data = json_decode(file_get_contents('php://input'), true);
  if (!is_array($data)) { echo json_encode(['status'=>false,'msg'=>'JSON inválido']); die(); }

  $idorden   = (int)($data['idorden'] ?? 0);
  $fecha_fin = trim((string)($data['fecha_fin'] ?? ''));

  if ($idorden <= 0) { echo json_encode(['status'=>false,'msg'=>'Falta idorden']); die(); }
  if ($fecha_fin === '') { echo json_encode(['status'=>false,'msg'=>'Falta fecha_fin']); die(); }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $fecha_fin)) {
    echo json_encode(['status'=>false,'msg'=>'Formato de fecha_fin inválido']); die();
  }

  echo json_encode($this->model->finishOT($idorden, $fecha_fin));
  die();
}




public function getStatusOT()
{
  header('Content-Type: application/json');

  $json = file_get_contents('php://input');
  $req  = json_decode($json, true);

  if (!is_array($req)) {
    echo json_encode(['status'=>false,'msg'=>'JSON inválido']);
    die();
  }


  $planeacionid = (int)($req['planeacionid'] ?? 0);
  $peid         = (int)($req['peid'] ?? 0);

  if ($planeacionid <= 0 && $peid <= 0) {
    echo json_encode(['status'=>false,'msg'=>'Falta planeacionid o peid']);
    die();
  }


  if ($peid > 0) {
    $rows = $this->model->getStatusOTByPeid($peid);

    echo json_encode([
      'status' => true,
      'scope'  => 'peid',
      'peid'   => $peid,
      'data'   => $rows
    ]);
    die();
  }


  $rows = $this->model->getStatusOTByPlaneacion($planeacionid);

  echo json_encode([
    'status'      => true,
    'scope'       => 'planeacionid',
    'planeacionid'=> $planeacionid,
    'data'        => $rows
  ]);
  die();
}


public function descargarOrden($num_orden){
  $num_orden = trim((string) $num_orden);
   $request= $this->model->obtenerPlaneacion($num_orden);
   echo json_encode($request, JSON_UNESCAPED_UNICODE);
        die();
}


public function getOrdenes()
{
  header('Content-Type: application/json; charset=utf-8');

  try {
    // (opcional) si requieres sesión/login
    // if (empty($_SESSION['login'])) {
    //   echo json_encode(['status' => false, 'msg' => 'No autorizado']);
    //   die();
    // }

    $rows = $this->model->selectOrdenesCalendar();

    echo json_encode([
      'status' => true,
      'data'   => $rows
    ], JSON_UNESCAPED_UNICODE);
    die();

  } catch (Exception $e) {
    echo json_encode([
      'status' => false,
      'msg' => 'Error al obtener órdenes',
      'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    die();
  }
}







/////////////////////////////////////////////////


public function getChatMessages()
{
  header('Content-Type: application/json');
  $d = json_decode(file_get_contents('php://input'), true);

  $subot = trim($d['subot'] ?? '');
  if ($subot === '') {
    echo json_encode(['status'=>false,'msg'=>'SubOT requerida']); return;
  }

  $rows = $this->model->getChatMessages(
    $subot,
    (int)($d['last_id'] ?? 0)
  );

  echo json_encode(['status'=>true,'data'=>$rows]);
}

public function sendChatMessage()
{
  header('Content-Type: application/json');
  $d = json_decode(file_get_contents('php://input'), true);

  $ok = $this->model->insertChatMessage([
    'subot'         => $d['subot'] ?? '',
    'estacionid'    => (int)($d['estacionid'] ?? 0),
    'planeacionid'  => (int)($d['planeacionid'] ?? 0),
    'message'       => trim($d['message'] ?? '')
  ]);

  echo json_encode($ok
    ? ['status'=>true]
    : ['status'=>false,'msg'=>'No se pudo guardar el mensaje']
  );
}





}


?>