<?php
class Plan_planeacionv1 extends Controllers
{
  public function __construct()
  {
    parent::__construct();
    session_start();

    if (empty($_SESSION['login'])) {
      header('Location: ' . base_url() . '/login');
      die();
    }
    getPermisos(MPPLANPRODUCCION);
  }

  public function Plan_planeacionv1()
  {
    if (empty($_SESSION['permisosMod']['r'])) {
      header("Location:" . base_url() . '/dashboard');
    }
    $data['page_tag'] = "Planeación";
    $data['page_title'] = "Plan de producción";
    $data['page_name'] = "Planeación";
    $data['page_functions_js'] = "functions_plan_planeacionv1.js";
    $this->views->getView($this, "plan_planeacionv1", $data);
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


  public function getSelectSupervisor()
  {
    $htmlOptions = '<option value="" selected>--Seleccione--</option>';
    $arrData = $this->model->selectOptionSupervisores();
    if (count($arrData) > 0) {
      for ($i = 0; $i < count($arrData); $i++) {
        // if ($arrData[$i]['status'] != 0) {

        $email = htmlspecialchars((string) $arrData[$i]['email_user'], ENT_QUOTES, 'UTF-8');
        $htmlOptions .= '<option data-email="' . $email . '" value="' . $arrData[$i]['idusuario'] . '">' . $arrData[$i]['nombres'] . '  ' . $arrData[$i]['apellidos'] . '</option>';
        // }
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


  public function getSelectPersonalCalidad()
  {

    $htmlOptions = '';
    $arrData = $this->model->selectPersonalCalidad();
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

    // --------------------------------------------------------------------
    //  Datos de auditoría
    // --------------------------------------------------------------------
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    $fecha_notificacion = date('Y-m-d');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }

    // dep($data);
    // exit;

    $h = $data['header'] ?? [];

    $productoid = (int) ($h['productoid'] ?? 0);
    $pedido = trim((string) ($h['pedido'] ?? ''));
    $supervisor = (int) ($h['supervisor'] ?? 0);
    $prioridad = trim((string) ($h['prioridad'] ?? ''));
    $cantidad = (int) ($h['cantidad'] ?? 0);
    $fecha_inicio = trim((string) ($h['fecha_inicio'] ?? ''));
    $fecha_requerida = trim((string) ($h['fecha_requerida'] ?? ''));
    $notas = trim((string) ($h['notas'] ?? ''));

    if ($productoid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta producto']);
      die();
    }

    if ($supervisor <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta supervisor']);
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

    // =========================================================
    // ASIGNACIONES
    // =========================================================
    $asignacionesData = $data['asignaciones'] ?? [];
    $asignacionesEstaciones = $asignacionesData['estaciones'] ?? [];
    $asignacionesSubensambles = $asignacionesData['subensambles'] ?? [];

    // CALIDAD puede venir fuera de asignaciones o dentro de asignaciones.
    $asignacionesPDI = $data['calidad'] ?? ($asignacionesData['calidad'] ?? []);

    if (!is_array($asignacionesEstaciones) || count($asignacionesEstaciones) === 0) {
      echo json_encode(['status' => false, 'msg' => 'No hay asignaciones de estaciones']);
      die();
    }

    // =========================================================
    // SUPERVISOR OBTENEMOS NOMBRE Y CORREO
    // =========================================================
    $rowSup = $this->model->getSupervisorEmailById($supervisor);
    if (!$rowSup) {
      echo json_encode(['status' => false, 'msg' => 'Supervisor inválido o inactivo']);
      die();
    }

    $emailSupervisor = trim((string) ($rowSup['email_user'] ?? ''));
    $nombreSupervisor = trim((string) ($rowSup['nombres'] ?? ''));
    $apellidosSupervisor = trim((string) ($rowSup['apellidos'] ?? ''));
    $nombreCompletoSupervisor = trim($nombreSupervisor . ' ' . $apellidosSupervisor);

    if ($emailSupervisor === '') {
      echo json_encode(['status' => false, 'msg' => 'El supervisor no tiene correo configurado']);
      die();
    }

    $idsEncargados = [];
    $idsAyudantes = [];
    $idsCalidad = [];

    // =========================================================
    // VALIDAR ESTACIONES
    // =========================================================
    foreach ($asignacionesEstaciones as $a) {
      $estacionid = (int) ($a['estacionid'] ?? 0);
      $orden = (int) ($a['orden'] ?? 0);
      $encargado = (int) ($a['encargado'] ?? 0);
      $estampado = (int) ($a['estampado'] ?? 0);
      $calidad = (int) ($a['calidad'] ?? 0);
      $operaciones = (int) ($a['operaciones'] ?? 0);
      $especificaciones = (int) ($a['especificaciones'] ?? 0);
      $ayudantes = $a['ayudantes'] ?? [];

      if ($estacionid <= 0 || $orden <= 0) {
        echo json_encode(['status' => false, 'msg' => 'Asignación inválida (estacionid/orden)']);
        die();
      }

      if ($encargado <= 0) {
        echo json_encode(['status' => false, 'msg' => "Falta encargado en estación orden {$orden}"]);
        die();
      }

      $idsEncargados[] = $encargado;

      if (is_array($ayudantes)) {
        foreach ($ayudantes as $uid) {
          $uid = (int) $uid;
          if ($uid > 0) {
            $idsAyudantes[] = $uid;
          }
        }
      }
    }

    // =========================================================
    // VALIDAR SUBENSAMBLES
    // =========================================================
    foreach ($asignacionesSubensambles as $s) {
      $idsubensamble = (int) ($s['idsubensamble'] ?? 0);
      $estacionid = (int) ($s['estacionid'] ?? 0);
      $orden_sub = trim((string) ($s['orden_sub'] ?? ''));
      $encargado = (int) ($s['encargado'] ?? 0);
      $ayudantes = $s['ayudantes'] ?? [];

      if ($idsubensamble <= 0 || $estacionid <= 0 || $orden_sub === '') {
        echo json_encode(['status' => false, 'msg' => 'Asignación inválida en subensamble']);
        die();
      }

      if ($encargado <= 0) {
        echo json_encode(['status' => false, 'msg' => "Falta encargado en subensamble {$orden_sub}"]);
        die();
      }

      $idsEncargados[] = $encargado;

      if (is_array($ayudantes)) {
        foreach ($ayudantes as $uid) {
          $uid = (int) $uid;
          if ($uid > 0) {
            $idsAyudantes[] = $uid;
          }
        }
      }
    }

    // =========================================================
    // VALIDAR / PREPARAR CALIDAD PUNTOS CRÍTICOS Y PDI
    // =========================================================
    if (is_array($asignacionesPDI)) {
      foreach ($asignacionesPDI as $q) {
        $estacionid = (int) ($q['estacionid'] ?? 0);
        $orden = (int) ($q['orden'] ?? 0);

        $inspector_criticos = isset($q['inspector_criticos']) && $q['inspector_criticos'] !== null
          ? (int) $q['inspector_criticos']
          : 0;

        $inspector_pdi = isset($q['inspector_pdi']) && $q['inspector_pdi'] !== null
          ? (int) $q['inspector_pdi']
          : 0;

        if ($estacionid <= 0 || $orden <= 0) {
          continue;
        }

        if ($inspector_criticos > 0) {
          $idsCalidad[] = $inspector_criticos;
        }

        if ($inspector_pdi > 0) {
          $idsCalidad[] = $inspector_pdi;
        }
      }
    }

    $idsEncargados = array_values(array_unique(array_map('intval', $idsEncargados)));
    $idsAyudantes = array_values(array_unique(array_map('intval', $idsAyudantes)));
    $idsCalidad = array_values(array_unique(array_map('intval', $idsCalidad)));


    $idsAyudantes = array_values(array_diff($idsAyudantes, $idsEncargados));

    $destEnc = [];
    $destAy = [];
    $destCalidad = [];

    if (!empty($idsEncargados)) {
      $arrEnc = $this->model->getEmailsUsuariosByIds($idsEncargados);
      foreach ($arrEnc as $u) {
        $email = trim((string) ($u['email_user'] ?? ''));
        if ($email === '') {
          continue;
        }

        $nombre = trim((string) ($u['nombres'] ?? ''));
        $ape = trim((string) ($u['apellidos'] ?? ''));
        $full = trim($nombre . ' ' . $ape);
        if ($full === '') {
          $full = '—';
        }

        $destEnc[] = [
          'idusuario' => (int) ($u['idusuario'] ?? 0),
          'email' => $email,
          'nombre' => $full,
        ];
      }
    }

    if (!empty($idsAyudantes)) {
      $arrAy = $this->model->getEmailsUsuariosByIds($idsAyudantes);
      foreach ($arrAy as $u) {
        $email = trim((string) ($u['email_user'] ?? ''));
        if ($email === '') {
          continue;
        }

        $nombre = trim((string) ($u['nombres'] ?? ''));
        $ape = trim((string) ($u['apellidos'] ?? ''));
        $full = trim($nombre . ' ' . $ape);
        if ($full === '') {
          $full = '—';
        }

        $destAy[] = [
          'idusuario' => (int) ($u['idusuario'] ?? 0),
          'email' => $email,
          'nombre' => $full,
        ];
      }
    }

    if (!empty($idsCalidad)) {
      $arrCalidad = $this->model->getEmailsUsuariosByIds($idsCalidad);
      foreach ($arrCalidad as $u) {
        $email = trim((string) ($u['email_user'] ?? ''));
        if ($email === '') {
          continue;
        }

        $nombre = trim((string) ($u['nombres'] ?? ''));
        $ape = trim((string) ($u['apellidos'] ?? ''));
        $full = trim($nombre . ' ' . $ape);
        if ($full === '') {
          $full = '—';
        }

        $destCalidad[] = [
          'idusuario' => (int) ($u['idusuario'] ?? 0),
          'email' => $email,
          'nombre' => $full,
        ];
      }
    }

    $emailsEnc = array_values(array_unique(array_column($destEnc, 'email')));
    $emailsAy = array_values(array_unique(array_column($destAy, 'email')));
    $emailsCalidad = array_values(array_unique(array_column($destCalidad, 'email')));

    $mapUserNombre = [];
    $idsAll = array_values(array_unique(array_merge($idsEncargados, $idsAyudantes, $idsCalidad)));
    if (!empty($idsAll)) {
      $arrAll = $this->model->getNombresUsuariosByIds($idsAll);

      foreach ($arrAll as $u) {
        $id = (int) ($u['idusuario'] ?? 0);
        if ($id <= 0) {
          continue;
        }

        $nom = trim((string) ($u['nombres'] ?? ''));
        $ape = trim((string) ($u['apellidos'] ?? ''));
        $full = trim($nom . ' ' . $ape);
        $mapUserNombre[$id] = $full !== '' ? $full : '—';
      }
    }

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

      if ((int) $request_CONFIGURACION <= 0) {
        throw new Exception('No se pudo registrar la planeación (cabecera)');
      }

      $idplaneacion = (int) $request_CONFIGURACION;

      $this->model->insertAuditoria(
        MPPLANPRODUCCION,
        1,
        $idusuario,
        'mrp_planeacion',
        $idplaneacion,
        $fechaEvento,
        $ip,
        $detalle
      );

      $cve_producto = '';
      $descripcion = '';

      $request_Producto = $this->model->getProducto($productoid);
      if (is_array($request_Producto) && !empty($request_Producto)) {
        $cve_producto = (string) ($request_Producto['cve_producto'] ?? '');
        $descripcion = (string) ($request_Producto['descripcion'] ?? '');
      }

      $detalleAsignaciones = [];
      $detalleCalidad = [];
      $mapPlaneacionEstacion = [];

      // =========================================================
      // GUARDAR ESTACIONES
      // =========================================================
      foreach ($asignacionesEstaciones as $a) {
        $estacionid = (int) $a['estacionid'];
        $orden = (int) $a['orden'];
        $encargado = (int) $a['encargado'];
        $estampado = (int) ($a['estampado'] ?? 0);
        $calidad = (int) ($a['calidad'] ?? 0);
        $operaciones = (int) ($a['operaciones'] ?? 0);
        $especificaciones = (int) ($a['especificaciones'] ?? 0);
        $ayudantes = is_array($a['ayudantes']) ? $a['ayudantes'] : [];

        $id_planeacion_estacion = (int) $this->model->upsertPlaneacionEstacion(
          $idplaneacion,
          $estacionid,
          $orden,
          $estampado,
          $calidad,
          $operaciones,
          $especificaciones
        );

        if ($id_planeacion_estacion <= 0) {
          throw new Exception("No se pudo guardar estación {$estacionid} en planeación");
        }

        $mapPlaneacionEstacion[$estacionid] = $id_planeacion_estacion;

        $infoEst = $this->model->getEstacionInfoById($estacionid);
        $nombre_estacion = trim((string) ($infoEst['nombre_estacion'] ?? ''));
        $proceso = trim((string) ($infoEst['proceso'] ?? ''));
        $linea = trim((string) ($infoEst['linea'] ?? ''));

        $ayudantesInt = [];
        foreach ($ayudantes as $uid) {
          $uid = (int) $uid;
          if ($uid > 0) {
            $ayudantesInt[] = $uid;
          }
        }
        $ayudantesInt = array_values(array_unique($ayudantesInt));

        $ayudantesNombres = [];
        foreach ($ayudantesInt as $aid) {
          $ayudantesNombres[] = $mapUserNombre[$aid] ?? "ID {$aid}";
        }
        $ayudantesNombresTxt = !empty($ayudantesNombres) ? implode(', ', $ayudantesNombres) : '—';

        $detalleAsignaciones[] = [
          'tipo' => 'ESTACION',
          'orden' => $orden,
          'orden_label' => 'EST-' . $orden,
          'estacionid' => $estacionid,
          'idsubensamble' => 0,
          'nombre_estacion' => $nombre_estacion,
          'proceso' => $proceso,
          'linea' => $linea,
          'encargado' => $encargado,
          'encargado_nombre' => $mapUserNombre[$encargado] ?? "ID {$encargado}",
          'ayudantes' => $ayudantesInt,
          'ayudantes_nombres' => $ayudantesNombresTxt,
          'planeacion_estacionid' => $id_planeacion_estacion,
          'planeacion_subensambleid' => 0
        ];

        for ($s = 1; $s <= $cantidad; $s++) {
          $num_orden_s = $num_orden . '-U' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);

          $okOrd = $this->model->insertOrdenes($id_planeacion_estacion, $num_orden_s, $estampado, $calidad, $operaciones, $especificaciones);

          if ((int) $okOrd <= 0) {
            throw new Exception("No se pudo insertar orden {$num_orden_s} para planeación_estación {$id_planeacion_estacion}");
          }
        }

        $this->model->clearOperadoresByPlaneacionEstacion($id_planeacion_estacion);

        $okEnc = $this->model->insertPlaneacionOperador(
          $id_planeacion_estacion,
          $encargado,
          'ENCARGADO'
        );
        if ((int) $okEnc <= 0) {
          throw new Exception("No se pudo guardar encargado en estación {$estacionid}");
        }

        $setAy = [];
        foreach ($ayudantesInt as $uid) {
          $uid = (int) $uid;
          if ($uid <= 0) {
            continue;
          }
          if (isset($setAy[$uid])) {
            continue;
          }
          $setAy[$uid] = true;

          $okAy = $this->model->insertPlaneacionOperador(
            $id_planeacion_estacion,
            $uid,
            'AYUDANTE'
          );

          if ((int) $okAy <= 0) {
            throw new Exception("No se pudo guardar ayudante {$uid} en estación {$estacionid}");
          }
        }
      }

      if (is_array($asignacionesPDI)) {
        foreach ($asignacionesPDI as $q) {
          $estacionid = (int) ($q['estacionid'] ?? 0);
          $orden = (int) ($q['orden'] ?? 0);

          $inspector_criticos = isset($q['inspector_criticos']) && $q['inspector_criticos'] !== null
            ? (int) $q['inspector_criticos']
            : 0;

          $inspector_pdi = isset($q['inspector_pdi']) && $q['inspector_pdi'] !== null
            ? (int) $q['inspector_pdi']
            : 0;

          if ($estacionid <= 0) {
            continue;
          }

          $id_planeacion_estacion = (int) ($mapPlaneacionEstacion[$estacionid] ?? 0);
          if ($id_planeacion_estacion <= 0) {
            continue;
          }

          $infoEstCalidad = $this->model->getEstacionInfoById($estacionid);
          $nombre_estacion_calidad = trim((string) ($infoEstCalidad['nombre_estacion'] ?? ''));
          $proceso_calidad = trim((string) ($infoEstCalidad['proceso'] ?? ''));
          $linea_calidad = trim((string) ($infoEstCalidad['linea'] ?? ''));

          if ($inspector_criticos > 0) {
            $this->model->clearPlaneacionCalidadCriticos($id_planeacion_estacion);

            $okCriticos = $this->model->insertPlaneacionCalidadCriticos(
              $id_planeacion_estacion,
              $inspector_criticos,
              'INSPECTOR_CRITICOS'
            );

            if ((int) $okCriticos <= 0) {
              throw new Exception("No se pudo guardar inspector de puntos críticos en estación {$estacionid}");
            }

            $detalleCalidad[] = [
              'tipo' => 'PUNTOS CRITICOS',
              'tipo_texto' => 'Puntos críticos',
              'orden' => $orden,
              'estacionid' => $estacionid,
              'planeacion_estacionid' => $id_planeacion_estacion,
              'usuarioid' => $inspector_criticos,
              'usuario_nombre' => $mapUserNombre[$inspector_criticos] ?? "ID {$inspector_criticos}",
              'nombre_estacion' => $nombre_estacion_calidad,
              'proceso' => $proceso_calidad,
              'linea' => $linea_calidad,
            ];
          }

          if ($inspector_pdi > 0) {
            $this->model->clearPlaneacionCalidadPdi($id_planeacion_estacion);

            $okPdi = $this->model->insertPlaneacionCalidadPdi(
              $id_planeacion_estacion,
              $inspector_pdi,
              'INSPECTOR_PDI'
            );

            if ((int) $okPdi <= 0) {
              throw new Exception("No se pudo guardar inspector PDI en estación {$estacionid}");
            }

            $detalleCalidad[] = [
              'tipo' => 'PDI',
              'tipo_texto' => 'Inspección PDI',
              'orden' => $orden,
              'estacionid' => $estacionid,
              'planeacion_estacionid' => $id_planeacion_estacion,
              'usuarioid' => $inspector_pdi,
              'usuario_nombre' => $mapUserNombre[$inspector_pdi] ?? "ID {$inspector_pdi}",
              'nombre_estacion' => $nombre_estacion_calidad,
              'proceso' => $proceso_calidad,
              'linea' => $linea_calidad,
            ];
          }
        }
      }

      // =========================================================
      // GUARDAR SUBENSAMBLES
      // =========================================================
      foreach ($asignacionesSubensambles as $s) {
        $idsubensamble = (int) ($s['idsubensamble'] ?? 0);
        $estacionid = (int) ($s['estacionid'] ?? 0);
        $orden_sub = trim((string) ($s['orden_sub'] ?? ''));
        $encargado = (int) ($s['encargado'] ?? 0);
        $ayudantes = is_array($s['ayudantes'] ?? []) ? $s['ayudantes'] : [];

        $id_planeacion_estacion = (int) ($mapPlaneacionEstacion[$estacionid] ?? 0);
        if ($id_planeacion_estacion <= 0) {
          throw new Exception("No se encontró la estación planeada para el subensamble {$orden_sub}");
        }

        $id_planeacion_subensamble = (int) $this->model->upsertPlaneacionSubensamble(
          $idplaneacion,
          $id_planeacion_estacion,
          $estacionid,
          $idsubensamble,
          $orden_sub
        );

        if ($id_planeacion_subensamble <= 0) {
          throw new Exception("No se pudo guardar subensamble {$idsubensamble}");
        }

        $infoSub = $this->model->getSubensambleInfoById($idsubensamble);
        $nombre_subensamble = trim((string) ($infoSub['nombre_estacion'] ?? ''));
        $proceso_subensamble = trim((string) ($infoSub['proceso'] ?? ''));

        $ayudantesInt = [];
        foreach ($ayudantes as $uid) {
          $uid = (int) $uid;
          if ($uid > 0) {
            $ayudantesInt[] = $uid;
          }
        }
        $ayudantesInt = array_values(array_unique($ayudantesInt));

        $ayudantesNombres = [];
        foreach ($ayudantesInt as $aid) {
          $ayudantesNombres[] = $mapUserNombre[$aid] ?? "ID {$aid}";
        }
        $ayudantesNombresTxt = !empty($ayudantesNombres) ? implode(', ', $ayudantesNombres) : '—';

        $detalleAsignaciones[] = [
          'tipo' => 'SUBENSAMBLE',
          'orden' => 0,
          'orden_label' => $orden_sub,
          'estacionid' => $estacionid,
          'idsubensamble' => $idsubensamble,
          'nombre_estacion' => $nombre_subensamble,
          'proceso' => $proceso_subensamble,
          'linea' => '',
          'encargado' => $encargado,
          'encargado_nombre' => $mapUserNombre[$encargado] ?? "ID {$encargado}",
          'ayudantes' => $ayudantesInt,
          'ayudantes_nombres' => $ayudantesNombresTxt,
          'planeacion_estacionid' => $id_planeacion_estacion,
          'planeacion_subensambleid' => $id_planeacion_subensamble
        ];

        for ($i = 1; $i <= $cantidad; $i++) {
          $num_sub_orden = $num_orden . '-U' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

          $codigoScan = $num_sub_orden;

          $okSubOrd = $this->model->insertOrdenesSubensamble(
            $id_planeacion_subensamble,
            $num_sub_orden,
            $codigoScan
          );

          if ((int) $okSubOrd <= 0) {
            throw new Exception("No se pudo insertar orden {$num_sub_orden} para subensamble {$idsubensamble}");
          }
        }

        $this->model->clearOperadoresByPlaneacionSubensamble($id_planeacion_subensamble);

        $okEncSub = $this->model->insertPlaneacionSubensambleOperador(
          $id_planeacion_subensamble,
          $encargado,
          'ENCARGADO'
        );

        if ((int) $okEncSub <= 0) {
          throw new Exception("No se pudo guardar encargado en subensamble {$idsubensamble}");
        }

        $setAySub = [];
        foreach ($ayudantesInt as $uid) {
          $uid = (int) $uid;
          if ($uid <= 0) {
            continue;
          }
          if (isset($setAySub[$uid])) {
            continue;
          }
          $setAySub[$uid] = true;

          $okAySub = $this->model->insertPlaneacionSubensambleOperador(
            $id_planeacion_subensamble,
            $uid,
            'AYUDANTE'
          );

          if ((int) $okAySub <= 0) {
            throw new Exception("No se pudo guardar ayudante {$uid} en subensamble {$idsubensamble}");
          }
        }
      }

      $estacionIds = [];
      foreach ($asignacionesEstaciones as $a) {
        $eid = (int) ($a['estacionid'] ?? 0);
        if ($eid > 0) {
          $estacionIds[] = $eid;
        }
      }
      $estacionIds = array_values(array_unique($estacionIds));

      $subensambleIds = [];
      foreach ($asignacionesSubensambles as $s) {
        $sid = (int) ($s['idsubensamble'] ?? 0);
        if ($sid > 0) {
          $subensambleIds[] = $sid;
        }
      }
      $subensambleIds = array_values(array_unique($subensambleIds));

      $totalTiempoAjusteEstaciones = (float) $this->model->getTotalTiempoAjusteByEstaciones($estacionIds);
      $totalTiempoAjusteSubensambles = (float) $this->model->getTotalTiempoAjusteBySubensambles($subensambleIds);
      $minutosTotales = ($totalTiempoAjusteEstaciones + $totalTiempoAjusteSubensambles) * (float) $cantidad;

      $minPorDia = 540;
      $daysApprox = (int) ceil($minutosTotales / $minPorDia);

      $fromDate = substr($fecha_inicio, 0, 10);
      $toDateDT = new DateTime($fecha_inicio);
      $toDateDT->modify('+' . max(10, $daysApprox * 2) . ' days');
      $toDate = $toDateDT->format('Y-m-d');

      $festivosSet = $this->model->getFestivosBetween($fromDate, $toDate);

      $fecha_fin = $this->model->addWorkingMinutesToDatetimeWithHolidays(
        $fecha_inicio,
        $minutosTotales,
        [1, 2, 3, 4, 5],
        $festivosSet
      );

      $this->model->updateFechaFinPlaneacion($idplaneacion, $fecha_fin);

      // =========================================================
      // URL DETALLE
      // =========================================================
      $url_recovery = base_url() . '/plan_planeacionv1/orden/' . $num_orden;
      $reqMap = [];

      $componentes = $this->model->getComponentesByProducto($productoid);

      if (!empty($componentes)) {
        foreach ($componentes as $c) {
          $almacenid = (int) ($c['almacenid'] ?? 0);
          $inventarioid = (int) ($c['inventarioid'] ?? 0);
          $qtyUnit = (float) ($c['cantidad'] ?? 0);
          $estacionidComp = (int) ($c['estacionid'] ?? 0);

          if ($almacenid <= 0 || $inventarioid <= 0 || $qtyUnit <= 0 || $estacionidComp <= 0) {
            continue;
          }

          if (!in_array($estacionidComp, $estacionIds, true)) {
            continue;
          }

          $key = $almacenid . '|' . $inventarioid;

          if (!isset($reqMap[$key])) {
            $reqMap[$key] = [
              'almacenid' => $almacenid,
              'inventarioid' => $inventarioid,
              'qty_unit_total' => 0.0,
              'referencias' => []
            ];
          }

          $reqMap[$key]['qty_unit_total'] += $qtyUnit;
          $reqMap[$key]['referencias'][] = 'ESTACION ' . $estacionidComp;
        }
      }

      $componentesSub = $this->model->getComponentesBySubensambles($productoid, $subensambleIds);

      if (!empty($componentesSub)) {
        foreach ($componentesSub as $c) {
          $almacenid = (int) ($c['almacenid'] ?? 0);
          $inventarioid = (int) ($c['inventarioid'] ?? 0);
          $qtyUnit = (float) ($c['cantidad'] ?? 0);
          $subensambleidComp = (int) ($c['subensambleid'] ?? 0);

          if ($almacenid <= 0 || $inventarioid <= 0 || $qtyUnit <= 0 || $subensambleidComp <= 0) {
            continue;
          }

          if (!in_array($subensambleidComp, $subensambleIds, true)) {
            continue;
          }

          $key = $almacenid . '|' . $inventarioid;

          if (!isset($reqMap[$key])) {
            $reqMap[$key] = [
              'almacenid' => $almacenid,
              'inventarioid' => $inventarioid,
              'qty_unit_total' => 0.0,
              'referencias' => []
            ];
          }

          $reqMap[$key]['qty_unit_total'] += $qtyUnit;
          $reqMap[$key]['referencias'][] = 'SUBENSAMBLE ' . $subensambleidComp;
        }
      }

      if (!empty($reqMap)) {
        foreach ($reqMap as $it) {
          $almacenid = (int) $it['almacenid'];
          $inventarioid = (int) $it['inventarioid'];
          $qtySalida = (float) $it['qty_unit_total'] * (float) $cantidad;

          if ($qtySalida <= 0) {
            continue;
          }

          $okUpd = $this->model->updateExistenciaInventario($inventarioid, $almacenid, $qtySalida);
          if (!$okUpd) {
            throw new Exception("No se pudo actualizar existencia de inventario {$inventarioid}");
          }

          $referenciaTxt = 'Salida a fábrica';
          if (!empty($it['referencias'])) {
            $referenciaTxt .= ' [' . implode(', ', array_unique($it['referencias'])) . ']';
          }

          $mov = [
            'inventarioid' => $inventarioid,
            'almacenid' => $almacenid,
            'numero_movimiento' => '',
            'concepmovid' => 10,
            'referencia' => $referenciaTxt,
            'cantidad' => $qtySalida,
            'costo_cantidad' => '',
            'precio' => '',
            'costo' => '',
            'existencia' => '',
            'signo' => '-1',
            'fecha_movimiento' => date('Y-m-d H:i:s'),
            'estado' => 2,
          ];

          $okMov = $this->model->insertMovimientoInventario($mov);
          if ((int) $okMov <= 0) {
            throw new Exception("No se pudo insertar movimiento inventario {$inventarioid}");
          }
        }
      }

      // ---------------------------------------------------------
      // Base para correo
      // ---------------------------------------------------------
      $infoBase = [
        'idplaneacion' => $idplaneacion,
        'num_orden' => $num_orden,
        'productoid' => $productoid,
        'pedido' => $pedido,
        'prioridad' => $prioridad,
        'cantidad' => $cantidad,
        'fecha_inicio' => $fecha_inicio,
        'fecha_requerida' => $fecha_requerida,

        'fecha_inicio_txt' => formatFechaLargaEs($fecha_inicio, true),
        'fecha_requerida_txt' => formatFechaLargaEs($fecha_requerida, true),

        'supervisor' => $nombreCompletoSupervisor,
        'notas' => $notas,
        'cve_producto' => $cve_producto,
        'descripcion' => $descripcion,
        'fecha_notificacion' => formatFechaLargaEs($fecha_notificacion, false),
        'url_detalle' => $url_recovery,

        'asignaciones_detalle' => $detalleAsignaciones,
        'calidad_detalle' => $detalleCalidad,
      ];

      $mail = [
        'encargados' => ['status' => true, 'msg' => 'OK', 'to_count' => count($emailsEnc)],
        'ayudantes' => ['status' => true, 'msg' => 'OK', 'to_count' => count($emailsAy)],
        'calidad' => ['status' => true, 'msg' => 'OK', 'to_count' => count($emailsCalidad)],
        'supervisor' => ['status' => true, 'msg' => 'OK', 'to_count' => 1],
      ];

      $cc = 'carlos.cruz@ldrsolutions.com.mx';

      // ---------------------------------------------------------
      // SUPERVISOR
      // ---------------------------------------------------------
      try {
        $dataMail = $infoBase;
        $dataMail['nombre'] = $nombreCompletoSupervisor !== '' ? $nombreCompletoSupervisor : 'Supervisor';
        $dataMail['email'] = $emailSupervisor;
        $dataMail['asunto'] = 'Planeación de Producción Registrada';

        sendMailLocalCron($dataMail, 'email_new_ot_supervisor', $cc);
      } catch (Exception $eSup) {
        $mail['supervisor'] = ['status' => false, 'msg' => $eSup->getMessage(), 'to_count' => 1];
      }

      // ---------------------------------------------------------
      // ENCARGADOS
      // ---------------------------------------------------------
      try {
        if (!empty($destEnc)) {
          foreach ($destEnc as $dest) {
            $uid = (int) $dest['idusuario'];

            $asigUser = null;
            foreach ($detalleAsignaciones as $d) {
              if ((int) $d['encargado'] === $uid) {
                $asigUser = $d;
                break;
              }
            }

            $dataMail = $infoBase;
            $dataMail['nombre'] = $dest['nombre'];
            $dataMail['email'] = $dest['email'];
            $dataMail['asunto'] = 'Responsabilidad de Estación Asignada';

            $dataMail['estacion_asignada'] = $asigUser ? [
              'tipo' => $asigUser['tipo'],
              'orden' => $asigUser['tipo'] === 'SUBENSAMBLE' ? ($asigUser['orden_label'] ?? '') : ($asigUser['orden'] ?? 0),
              'nombre_estacion' => $asigUser['nombre_estacion'],
              'proceso' => $asigUser['proceso'],
              'linea' => $asigUser['linea'],
            ] : null;

            $dataMail['ayudantes_nombres'] = $asigUser['ayudantes_nombres'] ?? '—';

            sendMailLocalCron($dataMail, 'email_new_ot_encargado', $cc);
          }
        } else {
          $mail['encargados'] = ['status' => false, 'msg' => 'Sin correos válidos', 'to_count' => 0];
        }
      } catch (Exception $e1) {
        $mail['encargados'] = ['status' => false, 'msg' => $e1->getMessage(), 'to_count' => count($emailsEnc)];
      }

      // ---------------------------------------------------------
      // AYUDANTES
      // ---------------------------------------------------------
      try {
        if (!empty($destAy)) {
          foreach ($destAy as $dest) {
            $uid = (int) $dest['idusuario'];

            $asigUser = null;
            foreach ($detalleAsignaciones as $d) {
              if (in_array($uid, $d['ayudantes'], true)) {
                $asigUser = $d;
                break;
              }
            }

            $dataMail = $infoBase;
            $dataMail['nombre'] = $dest['nombre'];
            $dataMail['email'] = $dest['email'];
            $dataMail['asunto'] = 'Orden de Trabajo Asignada';

            $dataMail['estacion_asignada'] = $asigUser ? [
              'tipo' => $asigUser['tipo'],
              'orden' => $asigUser['tipo'] === 'SUBENSAMBLE' ? ($asigUser['orden_label'] ?? '') : ($asigUser['orden'] ?? 0),
              'nombre_estacion' => $asigUser['nombre_estacion'],
              'proceso' => $asigUser['proceso'],
              'linea' => $asigUser['linea'],
            ] : null;

            $dataMail['encargado_nombre'] = $asigUser['encargado_nombre'] ?? '—';

            // sendMailLocalCron($dataMail, 'email_new_ot_ayudante', $cc);
          }
        } else {
          $mail['ayudantes'] = ['status' => false, 'msg' => 'Sin correos válidos', 'to_count' => 0];
        }
      } catch (Exception $e2) {
        $mail['ayudantes'] = ['status' => false, 'msg' => $e2->getMessage(), 'to_count' => count($emailsAy)];
      }



      try {
        if (!empty($destCalidad)) {
          foreach ($destCalidad as $dest) {
            $uid = (int) $dest['idusuario'];

            $asignacionesUsuarioCalidad = [];

            foreach ($detalleCalidad as $d) {
              if ((int) $d['usuarioid'] === $uid) {
                $asignacionesUsuarioCalidad[] = [
                  'tipo' => $d['tipo'] ?? '',
                  'tipo_texto' => $d['tipo_texto'] ?? '',
                  'orden' => $d['orden'] ?? 0,
                  'estacionid' => $d['estacionid'] ?? 0,
                  'nombre_estacion' => $d['nombre_estacion'] ?? '—',
                  'proceso' => $d['proceso'] ?? '—',
                  'linea' => $d['linea'] ?? '—',
                  'planeacion_estacionid' => $d['planeacion_estacionid'] ?? 0,
                ];
              }
            }

            if (empty($asignacionesUsuarioCalidad)) {
              continue;
            }

            foreach ($asignacionesUsuarioCalidad as $asigCalidad) {
              $dataMail = $infoBase;
              $dataMail['nombre'] = $dest['nombre'];
              $dataMail['email'] = $dest['email'];
              $dataMail['asunto'] = 'Asignación de Calidad';

              $dataMail['calidad_asignada'] = $asigCalidad;

              sendMailLocalCron($dataMail, 'email_new_ot_calidad', $cc);
            }
          }
        } else {
          $mail['calidad'] = ['status' => false, 'msg' => 'Sin correos válidos', 'to_count' => 0];
        }
      } catch (Exception $eCal) {
        $mail['calidad'] = [
          'status' => false,
          'msg' => $eCal->getMessage(),
          'to_count' => count($emailsCalidad)
        ];
      }

      echo json_encode([
        'status' => true,
        'msg' => 'Planeación guardada correctamente',
        'idplaneacion' => $idplaneacion,
        'mail' => $mail,
        'num_planeacion' => $num_orden
      ]);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => $e->getMessage()
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
  public function getEnProceso()
  {

    $arrData = $this->model->selectPlanEnProceso();
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
    $subensambles = $data['subensambles'] ?? [];

    if ($productoid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta productoid']);
      die();
    }

    if ($cantidad <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Cantidad inválida']);
      die();
    }

    if (
      (!is_array($estaciones) || count($estaciones) === 0) &&
      (!is_array($subensambles) || count($subensambles) === 0)
    ) {
      echo json_encode(['status' => false, 'msg' => 'No hay estaciones o subensambles']);
      die();
    }

    $errores = [];

    // =========================================================
    // VALIDAR ESTACIONES
    // =========================================================
    if (is_array($estaciones)) {
      foreach ($estaciones as $e) {
        $estacionid = (int) ($e['estacionid'] ?? 0);
        if ($estacionid <= 0)
          continue;

        $res = $this->model->consultarExistencias($productoid, $estacionid, $cantidad);

        if (!empty($res) && isset($res['status']) && (int) $res['status'] === 0 && !empty($res['data'])) {
          foreach ($res['data'] as $row) {
            $row['tipo'] = 'ESTACION';
            $row['msg'] = $res['msg'] ?? 'Faltan componentes en inventario';
            $errores[] = $row;
          }
        }
      }
    }

    // =========================================================
    // VALIDAR SUBENSAMBLES
    // =========================================================
    if (is_array($subensambles)) {
      foreach ($subensambles as $s) {
        $idsubensamble = (int) ($s['idsubensamble'] ?? 0);
        $estacionid = (int) ($s['estacionid'] ?? 0);

        if ($idsubensamble <= 0)
          continue;

        $res = $this->model->consultarExistenciasSubensamble($productoid, $idsubensamble, $cantidad, $estacionid);

        if (!empty($res) && isset($res['status']) && (int) $res['status'] === 0 && !empty($res['data'])) {
          foreach ($res['data'] as $row) {
            $row['tipo'] = 'SUBENSAMBLE';
            $row['msg'] = $res['msg'] ?? 'Faltan componentes en inventario';
            $errores[] = $row;
          }
        }
      }
    }

    if (count($errores) > 0) {
      echo json_encode([
        'status' => false,
        'msg' => 'Faltan componentes en inventario para una o más estaciones/subensambles.',
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
  public function ordenv1($num_orden)
  {
    $num_orden = trim((string) $num_orden);

    if ($num_orden === '') {
      header("Location:" . base_url() . '/plan_planeacionv1');
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
    $data['page_functions_js'] = "functions_ordenv1.js";
    $data['arrOrdenDetalle'] = $this->model->obtenerPlaneacion($num_orden);

    if (empty($data['arrOrdenDetalle'])) {
      header("Location:" . base_url() . '/plan_planeacionv1');
      die();
    }

    $this->views->getView($this, "ordenv1", $data);
  }




  //FUNCIÓN PARA GUARDAr el co9mentari

  public function setCommentario()
  {
    header('Content-Type: application/json; charset=utf-8');
    // --------------------------------------------------------------------
    //  Datos de auditoría
    // --------------------------------------------------------------------
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }


    $idorden = isset($data['idorden']) ? trim((string) $data['idorden']) : '';
    $comentario = isset($data['comentario']) ? trim((string) $data['comentario']) : '';

    if ($idorden === '') {
      echo json_encode(['status' => false, 'msg' => 'Falta idorden']);
      die();
    }

    $resp = $this->model->updateComentarioOrden($idorden, $comentario);


    $this->model->insertAuditoria(
      MPPLANPRODUCCION,
      2,
      $idusuario,
      'mrp_ordenes_trabajo',
      $resp,
      $fechaEvento,
      $ip,
      $detalle
    );


    echo json_encode([
      'status' => true,
      'msg' => 'Comentario actualizado'
    ]);
    die();
  }

  public function startOT()
  {
    header('Content-Type: application/json');
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }

    $idorden = (int) ($data['idorden'] ?? 0);
    $fecha_inicio = trim((string) ($data['fecha_inicio'] ?? ''));

    if ($idorden <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta idorden']);
      die();
    }
    if ($fecha_inicio === '') {
      echo json_encode(['status' => false, 'msg' => 'Falta fecha_inicio']);
      die();
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $fecha_inicio)) {
      echo json_encode(['status' => false, 'msg' => 'Formato de fecha_inicio inválido']);
      die();
    }

    echo json_encode($this->model->startOT($idorden, $fecha_inicio));

    $this->model->insertAuditoria(
      MPPLANPRODUCCION,
      5,
      $idusuario,
      'mrp_ordenes_trabajo',
      $idorden,
      $fechaEvento,
      $ip,
      $detalle
    );



    die();
  }

  public function finishOT()
  {
    header('Content-Type: application/json');
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }

    $idorden = (int) ($data['idorden'] ?? 0);
    $fecha_fin = trim((string) ($data['fecha_fin'] ?? ''));

    if ($idorden <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta idorden']);
      die();
    }
    if ($fecha_fin === '') {
      echo json_encode(['status' => false, 'msg' => 'Falta fecha_fin']);
      die();
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $fecha_fin)) {
      echo json_encode(['status' => false, 'msg' => 'Formato de fecha_fin inválido']);
      die();
    }


    $inventarioid = (int) ($data['inventarioid'] ?? 0);
    if ($inventarioid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta inventario id']);
      die();
    }

    // dep($data);
    // exit;

    echo json_encode($this->model->finishOT($idorden, $fecha_fin, $inventarioid));
    $this->model->insertAuditoria(
      MPPLANPRODUCCION,
      6,
      $idusuario,
      'mrp_ordenes_trabajo',
      $idorden,
      $fechaEvento,
      $ip,
      $detalle
    );
    die();
  }




  public function getStatusOT()
  {
    header('Content-Type: application/json');

    $json = file_get_contents('php://input');
    $req = json_decode($json, true);

    if (!is_array($req)) {
      echo json_encode(['status' => false, 'msg' => 'JSON inválido']);
      die();
    }


    $planeacionid = (int) ($req['planeacionid'] ?? 0);
    $peid = (int) ($req['peid'] ?? 0);

    if ($planeacionid <= 0 && $peid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Falta planeacionid o peid']);
      die();
    }


    if ($peid > 0) {
      $rows = $this->model->getStatusOTByPeid($peid);

      echo json_encode([
        'status' => true,
        'scope' => 'peid',
        'peid' => $peid,
        'data' => $rows
      ]);
      die();
    }


    $rows = $this->model->getStatusOTByPlaneacion($planeacionid);

    echo json_encode([
      'status' => true,
      'scope' => 'planeacionid',
      'planeacionid' => $planeacionid,
      'data' => $rows
    ]);
    die();
  }


  public function descargarOrden($num_orden)
  {
    $num_orden = trim((string) $num_orden);
    $request = $this->model->obtenerPlaneacion($num_orden);
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
        'data' => $rows
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
      echo json_encode(['status' => false, 'msg' => 'SubOT requerida']);
      return;
    }

    $rows = $this->model->getChatMessages(
      $subot,
      (int) ($d['last_id'] ?? 0)
    );

    echo json_encode(['status' => true, 'data' => $rows]);
  }

  public function sendChatMessage()
  {
    header('Content-Type: application/json');

    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalleserver = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');
    $d = json_decode(file_get_contents('php://input'), true);

    $ok = $this->model->insertChatMessage([
      'subot' => $d['subot'] ?? '',
      'estacionid' => (int) ($d['estacionid'] ?? 0),
      'planeacionid' => (int) ($d['planeacionid'] ?? 0),
      'message' => trim($d['message'] ?? '')
    ]);

    $this->model->insertAuditoria(
      MPPLANPRODUCCION,
      1,
      $idusuario,
      'mrp_ot_chat',
      $ok,
      $fechaEvento,
      $ip,
      $detalleserver
    );

    echo json_encode(
      $ok
      ? ['status' => true]
      : ['status' => false, 'msg' => 'No se pudo guardar el mensaje']
    );
  }



  public function getDescriptiva()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      // $estacionid = (int) ($data['estacionid'] ?? 0);

      if ($productoid <= 0) {
        echo json_encode(['status' => false, 'msg' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
        die();
      }

      $rows = $this->model->selectDescriptivaByProducto($productoid);

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'productoid' => $productoid,
          'data' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Throwable $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al obtener la descriptiva.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


  public function getDocumentacion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);


      if ($productoid <= 0) {
        echo json_encode(['status' => false, 'msg' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
        die();
      }

      $rows = $this->model->selectDocumentacionByProducto($productoid);

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'productoid' => $productoid,
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Throwable $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al obtener documentación.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


  //CREAMOS LA FUN CIÓN PARA OBTENER LAS ESPECIFICACIONES CRITICAS POR ORDEN DE TRABAJO Y POR ESTACIONESs

  public function getEspecificaciones()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $origenid = (int) ($data['origenid'] ?? 0);
      $tipo = trim($data['tipo'] ?? 'estacion');
      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);
      $unidad_actual = trim($data['unidad_actual'] ?? '');

      if (
        $productoid <= 0 ||
        $origenid <= 0 ||
        $idordengeneral <= 0 ||
        !in_array($tipo, ['estacion', 'subensamble'])
      ) {
        echo json_encode([
          'status' => false,
          'msg' => 'Parámetros inválidos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo === 'subensamble') {

        $orden = $this->model->selectOrdenSubensambleById($idordengeneral);

        if (!$orden) {
          echo json_encode([
            'status' => false,
            'msg' => 'No se encontró la orden del subensamble.'
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        if ((int) $orden['operaciones'] !== 0) {
          echo json_encode([
            'status' => true,
            'msg' => 'Las operaciones de este subensamble ya fueron registradas.',
            'data' => [
              'rows' => []
            ]
          ], JSON_UNESCAPED_UNICODE);
          die();
        }


        $rows = $this->model->selectEspecificacionesSubensamblePendientes(
          $productoid,
          $origenid,
          $idordengeneral,
          $unidad_actual
        );

      } else {

        $orden = $this->model->selectOrdenEstacionById($idordengeneral);

        if (!$orden) {
          echo json_encode([
            'status' => false,
            'msg' => 'No se encontró la orden de trabajo.'
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        if ((int) $orden['operaciones'] !== 1) {
          echo json_encode([
            'status' => true,
            'msg' => 'Las operaciones de esta estación ya fueron registradas.',
            'data' => [
              'rows' => []
            ]
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        $rows = $this->model->selectEspecificacionesEstacionPendientes(
          $productoid,
          $origenid,
          $idordengeneral,
          $unidad_actual
        );
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al cargar especificaciones.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function getEspecificacionesCriticas()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $origenid = (int) ($data['origenid'] ?? 0);
      $tipo = trim($data['tipo'] ?? 'estacion');
      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);
      $unidad_actual = trim($data['unidad_actual'] ?? '');

      if (
        $productoid <= 0 ||
        $origenid <= 0 ||
        $idordengeneral <= 0 ||
        !in_array($tipo, ['estacion', 'subensamble'])
      ) {
        echo json_encode([
          'status' => false,
          'msg' => 'Parámetros inválidos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo === 'subensamble') {

        $orden = $this->model->selectEspecificacionesSubensambleById($idordengeneral);

        if (!$orden) {
          echo json_encode([
            'status' => false,
            'msg' => 'No se encontró la orden del subensamble.'
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        if ((int) $orden['especificaciones_criticas'] !== 0) {
          echo json_encode([
            'status' => true,
            'msg' => 'Las operaciones de este subensamble ya fueron registradas.',
            'data' => [
              'rows' => []
            ]
          ], JSON_UNESCAPED_UNICODE);
          die();
        }


        $rows = $this->model->selectEspecificacionesCSubensamblePendientes(
          $productoid,
          $origenid,
          $idordengeneral,
          $unidad_actual
        );

      } else {

        $orden = $this->model->selectEspecificacionesCEstacionById($idordengeneral);

        if (!$orden) {
          echo json_encode([
            'status' => false,
            'msg' => 'No se encontró la orden de trabajo.'
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        if ((int) $orden['especificaciones_criticas'] !== 1) {
          echo json_encode([
            'status' => true,
            'msg' => 'Las operaciones de esta estación ya fueron registradas.',
            'data' => [
              'rows' => []
            ]
          ], JSON_UNESCAPED_UNICODE);
          die();
        }

        $rows = $this->model->selectEspecificacionesCEstacionPendientes(
          $productoid,
          $origenid,
          $idordengeneral,
          $unidad_actual
        );
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al cargar especificaciones.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }




  public function getComponentes()
  {
    header('Content-Type: application/json; charset=utf-8');
    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $estacionid = (int) ($data['estacionid'] ?? 0);
      $tipo = trim((string) ($data['tipo'] ?? ''));

      if ($productoid <= 0 || $estacionid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Parámetros inválidos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo === 'estacion') {
        $rows = $this->model->selectComponentesByProductoEstacion($productoid, $estacionid);
      } elseif ($tipo === 'subensamble') {
        $rows = $this->model->selectComponentesByProductoSubensambles($productoid, $estacionid);
      } else {
        echo json_encode([
          'status' => false,
          'msg' => 'Tipo inválido. Debe ser "estacion" o "subensamble".'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'productoid' => $productoid,
          'estacionid' => $estacionid,
          'tipo' => $tipo,
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Throwable $e) {

      echo json_encode([
        'status' => false,
        'msg' => 'Error al obtener componentes.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();

    }
  }


  public function getHerramientas()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $estacionid = (int) ($data['estacionid'] ?? 0);
      $tipo = trim((string) ($data['tipo'] ?? ''));

      if ($productoid <= 0 || $estacionid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Parámetros inválidos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo === 'estacion') {
        $rows = $this->model->selectHerramientasByProductoEstacion($productoid, $estacionid);
      } elseif ($tipo === 'subensamble') {
        $rows = $this->model->selectHerramientasByProductoSubensamble($productoid, $estacionid);
      } else {
        echo json_encode([
          'status' => false,
          'msg' => 'Tipo inválido. Debe ser "estacion" o "subensamble".'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'productoid' => $productoid,
          'estacionid' => $estacionid,
          'tipo' => $tipo,
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Throwable $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al obtener herramientas.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function getAyudas()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $estacionid = (int) ($data['estacionid'] ?? 0);
      $tipo = trim((string) ($data['tipo'] ?? ''));

      if ($productoid <= 0 || $estacionid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Parámetros inválidos.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo === 'estacion') {
        $rows = $this->model->selectAyudasByProductoEstacion($productoid, $estacionid);
      } elseif ($tipo === 'subensamble') {
        $rows = $this->model->selectAyudasByProductoSubensamble($productoid, $estacionid);
      } else {
        echo json_encode([
          'status' => false,
          'msg' => 'Tipo inválido. Debe ser "estacion" o "subensamble".'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => [
          'productoid' => $productoid,
          'estacionid' => $estacionid,
          'tipo' => $tipo,
          'rows' => $rows
        ]
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Throwable $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al obtener herramientas.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function setInspeccionCalidad()
  {
    header('Content-Type: application/json');
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalleserver = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
      die();
    }

    $accion = isset($_POST['accion']) ? strtoupper(trim((string) $_POST['accion'])) : 'PAUSAR';
    $idorden = isset($_POST['idorden']) ? (int) $_POST['idorden'] : 0;
    $numot = isset($_POST['numot']) ? trim((string) $_POST['numot']) : '';
    $productoid = isset($_POST['productoid']) ? (int) $_POST['productoid'] : 0;
    $estacionid = isset($_POST['estacionid']) ? (int) $_POST['estacionid'] : 0;

    $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

    $detalleJson = isset($_POST['detalle']) ? (string) $_POST['detalle'] : '[]';
    $detalle = json_decode($detalleJson, true);
    if (!is_array($detalle))
      $detalle = [];

    if (!in_array($accion, ['PAUSAR', 'LIBERAR'], true))
      $accion = 'PAUSAR';

    if ($idorden <= 0 || $productoid <= 0 || $estacionid <= 0 || $usuarioid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Datos incompletos (idorden/productoid/estacionid/usuario).']);
      die();
    }

    if (!count($detalle)) {
      echo json_encode(['status' => false, 'msg' => 'No hay detalle de inspección.']);
      die();
    }


    if ($accion === 'LIBERAR') {
      foreach ($detalle as $d) {
        if (($d['resultado'] ?? '') !== 'OK') {
          echo json_encode(['status' => false, 'msg' => 'Para liberar, todo debe estar en OK.']);
          die();
        }
      }
    }


    if ($accion === 'PAUSAR') {
      foreach ($detalle as $d) {
        $res = $d['resultado'] ?? '';
        $eid = (int) ($d['especificacionid'] ?? 0);
        $com = trim((string) ($d['comentario'] ?? ''));

        if ($res === 'NO_OK') {
          if ($com === '') {
            echo json_encode(['status' => false, 'msg' => "Falta comentario en especificación {$eid}."]);
            die();
          }
          $key = "evidencia_{$eid}";
          $hasFiles = isset($_FILES[$key]) && !empty($_FILES[$key]['name'][0]);
          if (!$hasFiles) {
            echo json_encode(['status' => false, 'msg' => "Falta evidencia en especificación {$eid}."]);
            die();
          }
        }
      }
    }

    // -----------------------
    // Subida de evidencias
    // -----------------------
    $uploadDir = __DIR__ . "/../Assets/uploads/calidad_evidencias/";
    if (!is_dir($uploadDir)) {
      @mkdir($uploadDir, 0775, true);
    }

    $evidencias = [];

    foreach ($detalle as $d) {
      $especificacionid = (int) ($d['especificacionid'] ?? 0);
      if ($especificacionid <= 0)
        continue;

      $fileKey = "evidencia_{$especificacionid}";
      if (!isset($_FILES[$fileKey]))
        continue;

      $files = $this->normalizeFiles($_FILES[$fileKey]);

      foreach ($files as $f) {
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
          continue;

        $orig = $f['name'] ?? '';
        $tmp = $f['tmp_name'] ?? '';
        $mime = $f['type'] ?? '';
        $size = (int) ($f['size'] ?? 0);

        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true))
          continue;

        $newName = "cal_" . date('Ymd_His') . "_OT{$idorden}_ES{$especificacionid}_" . bin2hex(random_bytes(4)) . "." . $ext;
        $dest = rtrim($uploadDir, "/\\") . DIRECTORY_SEPARATOR . $newName;

        if (!move_uploaded_file($tmp, $dest))
          continue;

        $evidencias[$especificacionid][] = [
          'nombre_original' => $orig,
          'archivo' => $newName,
          'mime' => $mime,
          'size_bytes' => $size
        ];
      }
    }


    $estado = ($accion === 'LIBERAR') ? 2 : 1;

    // Guardar en modelo
    $resp = $this->model->saveInspeccionCalidad([
      'idorden' => $idorden,
      'numot' => $numot,
      'productoid' => $productoid,
      'estacionid' => $estacionid,
      'usuarioid' => $usuarioid,
      'estado' => $estado
    ], $detalle, $evidencias);

    $idinspeccion = (int) ($resp['data']['idinspeccion'] ?? 0);

    if (!empty($resp['status']) && $idinspeccion > 0 && $idusuario > 0) {

      $this->model->insertAuditoria(
        MPPLANPRODUCCION,
        1,
        $idusuario,
        'mrp_calidad_inspeccion',
        $idinspeccion,
        $fechaEvento,
        $ip,
        $detalleserver
      );
    }


    echo json_encode($resp);
    die();
  }



  public function guardarInspeccionCalidad()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode([
        'status' => false,
        'msg' => 'Método no permitido.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    $json = file_get_contents('php://input');
    $request = json_decode($json, true);

    if (!is_array($request)) {
      echo json_encode([
        'status' => false,
        'msg' => 'Formato JSON inválido.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    $productoid = intval($request['productoid'] ?? 0);
    $estacionid = intval($request['estacionid'] ?? 0);
    $idordengeneral = intval($request['idordengeneral'] ?? 0);
    $unidadActual = strClean($request['unidad_actual'] ?? '');
    $detalles = $request['detalles'] ?? [];

    $usuarioid = intval($_SESSION['userData']['idusuario'] ?? 0);

    if ($productoid <= 0 || $estacionid <= 0 || $idordengeneral <= 0 || empty($detalles)) {
      echo json_encode([
        'status' => false,
        'msg' => 'Datos incompletos para guardar la inspección.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    foreach ($detalles as $item) {
      $idpuntopdi = intval($item['idpuntopdi'] ?? 0);
      $resultado = intval($item['resultado'] ?? 0);
      $observacion = trim($item['observacion'] ?? '');

      if ($idpuntopdi <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Existe un punto de inspección inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if (!in_array($resultado, [1, 2])) {
        echo json_encode([
          'status' => false,
          'msg' => 'Existe un resultado inválido en la inspección.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($resultado === 2 && $observacion === '') {
        echo json_encode([
          'status' => false,
          'msg' => 'Las observaciones son obligatorias cuando un punto no es conforme.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }
    }

    $requestInsert = $this->model->insertInspeccionCalidad(
      $productoid,
      $estacionid,
      $idordengeneral,
      $unidadActual,
      $detalles,
      $usuarioid
    );

    if ($requestInsert <= 0) {
      echo json_encode([
        'status' => false,
        'msg' => 'No se pudo guardar la inspección de calidad.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    $requestUpdate = $this->model->updateCalidadOrdenTrabajo($idordengeneral);

    if (!$requestUpdate) {
      echo json_encode([
        'status' => false,
        'msg' => 'La inspección se guardó, pero no se pudo actualizar el estado de calidad de la orden de trabajo.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }

    echo json_encode([
      'status' => true,
      'msg' => 'La inspección de calidad fue guardada correctamente.'
    ], JSON_UNESCAPED_UNICODE);
    die();
  }

  private function normalizeFiles($file)
  {
    $out = [];
    if (!isset($file['name']))
      return $out;

    if (!is_array($file['name'])) {
      $out[] = $file;
      return $out;
    }

    $count = count($file['name']);
    for ($i = 0; $i < $count; $i++) {
      $out[] = [
        'name' => $file['name'][$i] ?? '',
        'type' => $file['type'][$i] ?? '',
        'tmp_name' => $file['tmp_name'][$i] ?? '',
        'error' => $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $file['size'][$i] ?? 0,
      ];
    }
    return $out;
  }



  public function getInspeccionCalidad()
  {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
      die();
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data))
      $data = [];

    $idorden = (int) ($data['idorden'] ?? 0);
    $estacionid = (int) ($data['estacionid'] ?? 0);

    if ($idorden <= 0 || $estacionid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Datos incompletos (idorden/estacionid).']);
      die();
    }

    $resp = $this->model->getInspeccionCalidad($idorden, $estacionid);
    echo json_encode($resp);
    die();
  }



  public function getViewInspeccionCalidad()
  {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => false, 'msg' => 'Método no permitido']);
      die();
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data))
      $data = [];

    $idorden = (int) ($data['idorden'] ?? 0);
    $estacionid = (int) ($data['estacionid'] ?? 0);

    if ($idorden <= 0 || $estacionid <= 0) {
      echo json_encode(['status' => false, 'msg' => 'Datos incompletos (idorden/estacionid).']);
      die();
    }

    $resp = $this->model->getViewInspeccionCalidad($idorden, $estacionid);
    echo json_encode($resp);
    die();
  }




  public function getSelectDates()
  {
    $arrData = $this->model->selectDatesDisponibles();
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
  }

  public function iniciarPlaneacion()
  {
    header('Content-Type: application/json');
    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');


    if (!isset($_SESSION['idUser']) || (int) $_SESSION['idUser'] <= 0) {
      echo json_encode(["status" => false, "msg" => "Sesión no válida."]);
      die();
    }

    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    $idplaneacion = isset($json['idplaneacion']) ? (int) $json['idplaneacion'] : 0;
    if ($idplaneacion <= 0) {
      echo json_encode(["status" => false, "msg" => "ID de planeación inválido."]);
      die();
    }

    $userId = (int) $_SESSION['idUser'];

    try {
      $ok = $this->model->iniciarPlaneacionModel($idplaneacion, $userId);

      $this->model->insertAuditoria(
        MPPLANPRODUCCION,
        7,
        $idusuario,
        'mrp_planeacion',
        $idplaneacion,
        $fechaEvento,
        $ip,
        $detalle
      );

      if (!$ok) {
        echo json_encode(["status" => false, "msg" => "No fue posible iniciar la producción (ya iniciada o no existe)."]);
        die();
      }

      echo json_encode([
        "status" => true,
        "msg" => "Producción iniciada correctamente.",
        "data" => [
          "idplaneacion" => $idplaneacion
        ]
      ]);
      die();

    } catch (Exception $e) {
      echo json_encode(["status" => false, "msg" => "Error: " . $e->getMessage()]);
      die();
    }
  }




  public function finalizarPlaneacion()
  {
    header('Content-Type: application/json');

    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    if (!isset($_SESSION['idUser']) || (int) $_SESSION['idUser'] <= 0) {

      echo json_encode([
        "status" => false,
        "msg" => "Sesión no válida."
      ]);
      die();
    }

    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    $idplaneacion = isset($json['idplaneacion'])
      ? (int) $json['idplaneacion']
      : 0;

    if ($idplaneacion <= 0) {

      echo json_encode([
        "status" => false,
        "msg" => "ID de planeación inválido."
      ]);
      die();
    }

    $userId = (int) $_SESSION['idUser'];

    try {

      $result = $this->model->finalizarPlaneacionModel(
        $idplaneacion,
        $userId
      );

      if (!$result['status']) {

        echo json_encode($result);
        die();
      }

      $this->model->insertAuditoria(
        MPPLANPRODUCCION,
        8,
        $idusuario,
        'mrp_planeacion',
        $idplaneacion,
        $fechaEvento,
        $ip,
        $detalle
      );

      echo json_encode([
        "status" => true,
        "msg" => "Producción finalizada correctamente.",
        "data" => [
          "idplaneacion" => $idplaneacion
        ]
      ]);
      die();

    } catch (Exception $e) {

      echo json_encode([
        "status" => false,
        "msg" => "Error: " . $e->getMessage()
      ]);
      die();
    }
  }
  public function getVinesDisponibles($referencia = '', $idorden = 0)
  {
    header('Content-Type: application/json; charset=utf-8');

    $args = func_get_args();

    if (isset($args[0])) {
      $parametro = trim((string) $args[0]);

      if (strpos($parametro, ',') !== false) {
        $partes = explode(',', $parametro);

        $referencia = trim($partes[0] ?? '');
        $idorden = intval($partes[1] ?? 0);
      } else {
        $referencia = trim($parametro);
        $idorden = isset($args[1]) ? intval($args[1]) : intval($idorden);
      }
    }

    if ($referencia === '') {
      echo json_encode([
        'status' => false,
        'msg' => 'Falta referencia',
        'data' => []
      ]);
      die();
    }

    if ($idorden <= 0) {
      echo json_encode([
        'status' => false,
        'msg' => 'Falta orden de trabajo',
        'debug' => [
          'args_recibidos' => $args,
          'referencia' => $referencia,
          'idorden' => $idorden
        ],
        'data' => []
      ]);
      die();
    }

    try {

      $estampado = $this->model->getEstampadoOrdenTrabajo($idorden);

      if ($estampado == 2) {
        echo json_encode([
          'status' => false,
          'msg' => 'Para esta unidad el VIN ya fue asignado.',
          'data' => []
        ]);
        die();
      }

      $rows = $this->model->getVinesDisponiblesByReferencia($referencia);

      $data = [];

      if (!empty($rows)) {
        foreach ($rows as $r) {
          $data[] = [
            'id' => (int) ($r['id_numeros_serie'] ?? 0),
            'vin' => (string) ($r['numero_serie'] ?? ''),
          ];
        }
      }

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => $data
      ]);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => $e->getMessage(),
        'data' => []
      ]);
      die();
    }
  }

  public function setVinAsignacion()
  {
    header('Content-Type: application/json; charset=utf-8');

    $idusuario = $_SESSION['userData']['idusuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fechaEvento = date('Y-m-d H:i:s');

    try {

      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
          'status' => false,
          'msg' => 'Método no permitido'
        ]);
        die();
      }

      $ordenId = isset($_POST['orden_trabajo_id'])
        ? intval($_POST['orden_trabajo_id'])
        : 0;

      $numeroSerieId = isset($_POST['numero_serie_id'])
        ? intval($_POST['numero_serie_id'])
        : 0;

      $numeroMotor = isset($_POST['numero_motor'])
        ? trim($_POST['numero_motor'])
        : '';

      // NUEVOS
      $vinOrigen = isset($_POST['vin_origen'])
        ? trim($_POST['vin_origen'])
        : '';

      $numeroTransmision = isset($_POST['numero_transmision'])
        ? trim($_POST['numero_transmision'])
        : '';


      if ($ordenId <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Orden de trabajo inválida'
        ]);
        die();
      }

      if ($numeroSerieId <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Número de serie inválido'
        ]);
        die();
      }

      if ($numeroMotor === '' || strlen($numeroMotor) < 3) {
        echo json_encode([
          'status' => false,
          'msg' => 'Número de motor obligatorio'
        ]);
        die();
      }

      if ($vinOrigen === '' || strlen($vinOrigen) < 3) {
        echo json_encode([
          'status' => false,
          'msg' => 'VIN origen obligatorio'
        ]);
        die();
      }

      if ($numeroTransmision === '' || strlen($numeroTransmision) < 3) {
        echo json_encode([
          'status' => false,
          'msg' => 'Número de transmisión obligatorio'
        ]);
        die();
      }

      $usuarioId = intval($_SESSION['userData']['idusuario'] ?? 0);

      if ($usuarioId <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión no válida'
        ]);
        die();
      }

      $fecha = date('Y-m-d');


      $insertId = $this->model->insertVinAsignacion(
        $ordenId,
        $numeroSerieId,
        $numeroMotor,
        $vinOrigen,
        $numeroTransmision,
        $usuarioId,
        $fecha
      );


      if ($insertId <= 0) {

        echo json_encode([
          'status' => false,
          'msg' => 'No se pudo guardar la asignación.'
        ]);
        die();
      }


      $this->model->insertAuditoria(
        MPPLANPRODUCCION,
        1,
        $idusuario,
        'mrp_vin_asignaciones',
        $insertId,
        $fechaEvento,
        $ip,
        $detalle
      );


      $okOT = $this->model->setEstatusEstampadoVin($ordenId);

      if (!$okOT) {

        echo json_encode([
          'status' => false,
          'msg' => 'Se asignó el VIN, pero no se pudo actualizar la OT.'
        ]);
        die();
      }


      $okSerie = $this->model->setEstadoNumeroSerie($numeroSerieId, 2);

      if (!$okSerie) {

        echo json_encode([
          'status' => false,
          'msg' => 'Se asignó el VIN, pero no se pudo actualizar el estado de serie.'
        ]);
        die();
      }


      echo json_encode([
        'status' => true,
        'msg' => 'VIN asignado correctamente.',
        'data' => [
          'idasignacion' => $insertId,
          'orden_trabajo_id' => $ordenId,
          'numero_serie_id' => $numeroSerieId,
          'numero_motor' => $numeroMotor,
          'vin_origen' => $vinOrigen,
          'numero_transmision' => $numeroTransmision,
          'usuario_id' => $usuarioId,
          'fecha_asignacion' => $fecha,
          'estado' => 1
        ]
      ]);
      die();

    } catch (Exception $e) {

      echo json_encode([
        'status' => false,
        'msg' => 'Error: ' . $e->getMessage()
      ]);
      die();
    }
  }

  public function getVinAsignado($idorden = 0)
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $idorden = intval($idorden);

      if ($idorden <= 0) {
        echo json_encode(['status' => false, 'msg' => 'OT inválida']);
        die();
      }

      $row = $this->model->getVinAsignadoPorOrden($idorden);

      if (empty($row)) {
        echo json_encode(['status' => false, 'msg' => 'No existe asignación de VIN para esta OT']);
        die();
      }

      echo json_encode(['status' => true, 'data' => $row]);
      die();

    } catch (Exception $e) {
      echo json_encode(['status' => false, 'msg' => 'Error: ' . $e->getMessage()]);
      die();
    }
  }


  ///////////////////////////////////////////////////////////////
  ////////// NUEVAS FUNCIONES PARA NUEVA INFRAESTRUCTURA ////////
  ///////////////////////////////////////////////////////////////


  public function getEstadoProduccion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $numOrden = trim((string) ($data['num_orden'] ?? ''));

      if ($numOrden === '') {
        echo json_encode([
          'status' => false,
          'msg' => 'Número de orden inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->obtenerPlaneacion($numOrden);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al consultar el estado de producción.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


  // función para Iniciar un subensamble 

  public function iniciarSubensamble()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      if (!is_array($data)) {
        echo json_encode([
          'status' => false,
          'msg' => 'Payload inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $idordenSubensamble = (int) ($data['idorden_subensamble'] ?? 0);
      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;


      if ($idordenSubensamble <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'El id de la orden de subensamble es inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($usuarioid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión inválida. No se identificó al usuario.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->iniciarOrdenSubensamble($idordenSubensamble, $usuarioid);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Ocurrió un error al iniciar el subensamble.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


  // función para finalizar un subensamble 

  public function finalizarSubensamble()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      if (!is_array($data)) {
        echo json_encode([
          'status' => false,
          'msg' => 'Payload inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $idordenSubensamble = (int) ($data['idorden_subensamble'] ?? 0);
      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;


      if ($idordenSubensamble <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'El id de la orden de subensamble es inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($usuarioid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión inválida. No se identificó al usuario.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->finalizarOrdenSubensamble($idordenSubensamble, $usuarioid);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Ocurrió un error al finalizar el subensamble.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }

  // función para Iniciar una estación 
  public function iniciarEstacion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      if (!is_array($data)) {
        echo json_encode([
          'status' => false,
          'msg' => 'Payload inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $idorden = (int) ($data['idorden'] ?? 0);
      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

      if ($idorden <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'El id de la orden de estación es inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($usuarioid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión inválida. No se identificó al usuario.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->iniciarOrdenEstacion($idorden, $usuarioid);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Ocurrió un error al iniciar la estación.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }




  public function finalizarEstacion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      // dep($data);
      // exit;

      if (!is_array($data)) {
        echo json_encode([
          'status' => false,
          'msg' => 'Payload inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $idorden = (int) ($data['idorden'] ?? 0);
      $inventarioid = (int) ($data['inventarioid'] ?? 0);
      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;


      if ($idorden <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'El id de la orden de estación es inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($usuarioid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión inválida. No se identificó al usuario.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->finalizarOrdenEstacion($idorden, $usuarioid, $inventarioid);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Ocurrió un error al finalizar la estación.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


  public function buscarUsuariosOperacion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $busqueda = trim($data['busqueda'] ?? '');

      if ($busqueda === '') {
        echo json_encode([
          'status' => false,
          'msg' => 'Ingresa número de empleado, nombre o correo.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $usuarios = $this->model->buscarUsuariosOperacion($busqueda);

      echo json_encode([
        'status' => true,
        'msg' => 'OK',
        'data' => $usuarios
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al buscar usuarios.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }

  public function registrarOperacionRealizadaVieja()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $origenid = (int) ($data['origenid'] ?? 0);
      $usuarioid = (int) ($data['usuarioid'] ?? 0);

      $tipo_origen = trim($data['tipo_origen'] ?? '');
      $idespecificacion = (int) ($data['idespecificacion'] ?? 0);
      $operacion_texto = trim($data['operacion_texto'] ?? '');

      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);

      if (
        $productoid <= 0 ||
        $origenid <= 0 ||
        $usuarioid <= 0 ||
        $idespecificacion <= 0 ||
        $operacion_texto === '' ||
        !in_array($tipo_origen, ['estacion', 'subensamble'])
      ) {
        echo json_encode([
          'status' => false,
          'msg' => 'Datos incompletos para registrar la operación.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $estacionid = null;
      $subensambleid = null;

      if ($tipo_origen === 'estacion') {
        $estacionid = $origenid;
      }

      if ($tipo_origen === 'subensamble') {
        $subensambleid = $origenid;
      }

      $resp = $this->model->insertOperacionRealizada([
        'productoid' => $productoid,
        'tipo_origen' => $tipo_origen,
        'estacionid' => $estacionid,
        'subensambleid' => $subensambleid,
        'idespecificacion' => $idespecificacion,
        'operacion_texto' => $operacion_texto,
        'usuarioid' => $usuarioid
      ]);

      if (!$resp) {
        echo json_encode([
          'status' => false,
          'msg' => 'No se pudo guardar el registro. Puede que esta operación ya esté registrada.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      echo json_encode([
        'status' => true,
        'msg' => 'Operación registrada correctamente.'
      ], JSON_UNESCAPED_UNICODE);
      if ($tipo_origen === 'subensamble') {

        $pendientes = $this->model->contarOperacionesPendientesSubensamble(
          $productoid,
          $subensambleid
        );

        if ($pendientes <= 0) {
          $this->model->actualizarOperacionesOrdenSubensamble($idordengeneral);
        }

      } else if ($tipo_origen === 'estacion') {

        $pendientes = $this->model->contarOperacionesPendientesEstacion(
          $productoid,
          $estacionid
        );

        if ($pendientes <= 0) {
          $this->model->actualizarOperacionesOrdenEstacion($idordengeneral);
        }
      }

      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al registrar operación.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function registrarOperacionRealizada()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $origenid = (int) ($data['origenid'] ?? 0);
      $usuarioid = (int) ($data['usuarioid'] ?? 0);

      $tipo_origen = trim($data['tipo_origen'] ?? '');
      $idespecificacion = (int) ($data['idespecificacion'] ?? 0);
      $operacion_texto = trim($data['operacion_texto'] ?? '');
      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);
      $unidad_actual = $data['unidad_actual'] ?? '';

      if (
        $productoid <= 0 ||
        $origenid <= 0 ||
        $usuarioid <= 0 ||
        $idespecificacion <= 0 ||
        $idordengeneral <= 0 ||
        $operacion_texto === '' ||
        !in_array($tipo_origen, ['estacion', 'subensamble'])
      ) {
        echo json_encode([
          'status' => false,
          'msg' => 'Datos incompletos para registrar la operación.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $estacionid = null;
      $subensambleid = null;

      if ($tipo_origen === 'estacion') {
        $estacionid = $origenid;
      }

      if ($tipo_origen === 'subensamble') {
        $subensambleid = $origenid;
      }

      $resp = $this->model->insertOperacionRealizada([
        'productoid' => $productoid,
        'tipo_origen' => $tipo_origen,
        'estacionid' => $estacionid,
        'subensambleid' => $subensambleid,
        'idespecificacion' => $idespecificacion,
        'operacion_texto' => $operacion_texto,
        'usuarioid' => $usuarioid,
        'unidad' => $unidad_actual
      ]);



      if (!$resp) {
        echo json_encode([
          'status' => false,
          'msg' => 'No se pudo guardar el registro. Puede que esta operación ya esté registrada.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($tipo_origen === 'subensamble') {

        $pendientes = $this->model->contarOperacionesNoRealizadasSubensamble(
          $productoid,
          $subensambleid,
          $unidad_actual
        );

        if ($pendientes <= 0) {
          $this->model->actualizarOperacionesOrdenSubensamble($idordengeneral);
        }

      } else if ($tipo_origen === 'estacion') {

        $pendientes = $this->model->contarOperacionesNoRealizadasEstacion(
          $productoid,
          $estacionid,
          $unidad_actual
        );

        if ($pendientes <= 0) {
          $this->model->actualizarOperacionesOrdenEstacion($idordengeneral);
        }
      }

      echo json_encode([
        'status' => true,
        'msg' => 'Operación registrada correctamente.'
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {
      echo json_encode([
        'status' => false,
        'msg' => 'Error al registrar operación.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function guardarEspecificacionesCriticas()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {

      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $origenid = (int) ($data['origenid'] ?? 0);
      $tipo_origen = trim($data['tipo_origen'] ?? '');
      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);
      $unidad_actual = trim($data['unidad_actual'] ?? '');

      $registros = $data['registros'] ?? [];



      if (
        $productoid <= 0 ||
        $origenid <= 0 ||
        $idordengeneral <= 0 ||
        empty($registros) ||
        !in_array($tipo_origen, ['estacion', 'subensamble'])
      ) {
        echo json_encode([
          'status' => false,
          'msg' => 'Datos incompletos para guardar las especificaciones críticas.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $estacionid = null;
      $subensambleid = null;

      if ($tipo_origen == 'estacion') {
        $estacionid = $origenid;
      }

      if ($tipo_origen == 'subensamble') {
        $subensambleid = $origenid;
      }

      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

      foreach ($registros as $item) {

        $this->model->insertOperacionCriticaRealizada([
          'productoid' => $productoid,
          'tipo_origen' => $tipo_origen,
          'estacionid' => $estacionid,
          'subensambleid' => $subensambleid,
          'idespecificacion' => (int) ($item['idespecificacion'] ?? 0),
          'operacion_texto' => trim($item['operacion_texto'] ?? ''),
          'usuarioid' => $usuarioid,
          'unidad' => $unidad_actual,
          'tipo_resultado' => (int) ($item['resultado'] ?? 1),
          'observaciones' => trim($item['observaciones'] ?? '')
        ]);
      }

      if ($tipo_origen == 'subensamble') {
        $this->model->actualizarEspecificacionesCriticasOrdenSubensamble($idordengeneral);
      } else if ($tipo_origen == 'estacion') {
        $this->model->actualizarEspecificacionesCriticasOrdenEstacion($idordengeneral);
      }

      echo json_encode([
        'status' => true,
        'msg' => 'Validaciones críticas registradas correctamente.'
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {

      echo json_encode([
        'status' => false,
        'msg' => 'Error al guardar especificaciones críticas.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }



  public function getPuntosInspeccion()
  {
    $request = json_decode(file_get_contents("php://input"), true);

    $productoid = intval($request['productoid'] ?? 0);
    $estacionid = intval($request['estacionid'] ?? 0);

    if (!$productoid || !$estacionid) {
      echo json_encode([
        'status' => false,
        'msg' => 'Datos inválidos'
      ]);
      die();
    }

    $rows = $this->model->getPuntosInspeccion(
      $productoid,
      $estacionid
    );

    echo json_encode([
      'status' => true,
      'data' => $rows
    ]);
    die();
  }




  public function registrarAccionProduccion()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {

      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $productoid = (int) ($data['productoid'] ?? 0);
      $estacionid = (int) ($data['estacionid'] ?? 0);
      $idordengeneral = (int) ($data['idordengeneral'] ?? 0);
      $unidad = trim($data['unidad_actual'] ?? '');

      $origen_accion = (int) ($data['origen_accion'] ?? 0);
      $tipo_accion = (int) ($data['tipo_accion'] ?? 0);

      $usuarioid = isset($_SESSION['idUser'])
        ? (int) $_SESSION['idUser']
        : 0;

      if (
        $productoid <= 0 ||
        $estacionid <= 0 ||
        $idordengeneral <= 0 ||
        $unidad === '' ||
        !in_array($origen_accion, [1, 2]) ||
        !in_array($tipo_accion, [1, 2, 3, 4, 5])
      ) {

        echo json_encode([
          'status' => false,
          'msg' => 'Datos incompletos para registrar la acción.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $idaccion = $this->model->insertAccionProduccion([
        'productoid' => $productoid,
        'estacionid' => $estacionid,
        'idordengeneral' => $idordengeneral,
        'unidad' => $unidad,
        'origen_accion' => $origen_accion,
        'tipo_accion' => $tipo_accion,
        'usuarioid' => $usuarioid
      ]);

      if (!$idaccion) {

        echo json_encode([
          'status' => false,
          'msg' => 'No se pudo registrar la acción de producción.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $this->model->actualizarAccionProduccionOrden(
        $idordengeneral,
        $tipo_accion
      );

      if ($tipo_accion == 2) {

        $this->model->insertUnidadFueraLinea([
          'accionid' => $idaccion,
          'productoid' => $productoid,
          'estacionid' => $estacionid,
          'idordengeneral' => $idordengeneral,
          'unidad' => $unidad,
          'usuario_salida' => $usuarioid
        ]);
      }

      if ($tipo_accion == 4 || $tipo_accion == 5) {

        $infoEstacion = $this->model->getInfoEstacion($estacionid);


        $infoSupervisor = $this->model->getInfoSupervisorByOrden($idordengeneral);


        // dep($infoSupervisor);

        if ($infoSupervisor) {

          $nombreSupervisor = trim(
            $infoSupervisor['nombres'] . ' ' .
            $infoSupervisor['apellidos']
          );

          $correoSupervisor = trim($infoSupervisor['email_user']);

          $usuarioDestino = (int) $infoSupervisor['idusuario'];

        } else {

          $nombreSupervisor = '';
          $correoSupervisor = '';
          $usuarioDestino = null;
        }

        $this->model->insertAccionNotificacion([
          'accionid' => $idaccion,
          'usuario_origen' => $usuarioid,
          'usuario_destino' => $usuarioDestino,
          'tipo_notificacion' => $tipo_accion == 4 ? 1 : 2
        ]);

        $nombreEstacion = $infoEstacion['nombre_estacion'] ?? '';
        $claveEstacion = $infoEstacion['cve_estacion'] ?? '';

        $tipoTexto = '';

        if ($tipo_accion == 4) {

          $tipoTexto = 'Solicitud de asistencia';
        }

        if ($tipo_accion == 5) {

          $tipoTexto = 'Solicitud de material';
        }

        $baseUrl = base_url();

        $urlDetalle = $baseUrl . '/plan_planeacionv1/ordenv1/' . $infoSupervisor['num_orden'];

        $dataMail = [
          'email' => $correoSupervisor,
          'asunto' => $tipoTexto,
          'tipo_accion' => $tipoTexto,
          'tipo_notificacion' => $tipo_accion == 4 ? 1 : 2,
          'nombreSupervisor' => $nombreSupervisor,
          'num_orden' => $infoSupervisor['num_orden'] ?? '',
          'prioridad' => $infoSupervisor['prioridad'] ?? '',
          'cantidad' => $infoSupervisor['cantidad'] ?? '',
          'fecha_requerida' => $infoSupervisor['fecha_requerida'] ?? '',
          'unidad' => $unidad,
          'estacion' => $claveEstacion . ' - ' . $nombreEstacion,
          'proceso' => $infoEstacion['proceso'] ?? '',
          'estandar' => !empty($infoEstacion['estandar'])
            ? $infoEstacion['estandar'] . ' ' . ($infoEstacion['unidad_medida'] ?? '')
            : 'No definido',
          'descripcion' => $infoEstacion['descripcion'] ?? '',
          // 'mensaje' => $mensaje,
          'fecha' => date('d/m/Y H:i:s'),
          'url_detalle' => $urlDetalle
        ];


        if (!empty($correoSupervisor)) {

          $cc = 'carlos.cruz@ldrsolutions.com.mx';

          // sendMailLocalCron($correoSupervisor,$dataMail,'email_solicitud_asistencia',$cc);
          sendMailLocalCron($dataMail, 'email_solicitud_asistencia', $cc);
        }
      }

      echo json_encode([
        'status' => true,
        'msg' => $this->getMensajeAccionProduccion($tipo_accion)
      ], JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {

      echo json_encode([
        'status' => false,
        'msg' => 'Error al registrar la acción de producción.'
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }

  public function getMensajeAccionProduccion($tipo_accion)
  {
    switch ((int) $tipo_accion) {
      case 1:
        return 'Paro momentáneo registrado correctamente.';
      case 2:
        return 'Unidad retirada de línea correctamente.';
      case 3:
        return 'Unidad marcada como alarmada correctamente.';
      case 4:
        return 'Solicitud de asistencia registrada correctamente.';
      case 5:
        return 'Falta de material registrada correctamente.';
      default:
        return 'Acción registrada correctamente.';
    }
  }


  public function reanudarParoMomentaneo()
  {
    header('Content-Type: application/json');

    if (!isset($_SESSION['idUser']) || (int) $_SESSION['idUser'] <= 0) {
      echo json_encode([
        "status" => false,
        "msg" => "Sesión no válida."
      ]);
      die();
    }

    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    $idordengeneral = isset($json['idordengeneral']) ? (int) $json['idordengeneral'] : 0;

    if ($idordengeneral <= 0) {
      echo json_encode([
        "status" => false,
        "msg" => "ID de orden inválido."
      ]);
      die();
    }

    $idusuario = (int) $_SESSION['idUser'];

    $request = $this->model->reanudarParoMomentaneoModel($idordengeneral, $idusuario);

    echo json_encode($request, JSON_UNESCAPED_UNICODE);
    die();
  }


  // funciones para reincoporar unidades a producción

  public function reincorporarUnidadFueraLinea()
  {
    header('Content-Type: application/json; charset=utf-8');

    try {

      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      // dep($data);
      // exit;


      $idfuera = (int) ($data['idfuera'] ?? 0);
      $usuarioid = isset($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : 0;

      if ($idfuera <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'ID de unidad fuera de línea inválido.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      if ($usuarioid <= 0) {
        echo json_encode([
          'status' => false,
          'msg' => 'Sesión inválida. No se identificó al usuario.'
        ], JSON_UNESCAPED_UNICODE);
        die();
      }

      $resp = $this->model->reincorporarUnidadFueraLinea($idfuera, $usuarioid);

      echo json_encode($resp, JSON_UNESCAPED_UNICODE);
      die();

    } catch (Exception $e) {

      echo json_encode([
        'status' => false,
        'msg' => 'Error al reincorporar la unidad fuera de línea.',
        'error' => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE);
      die();
    }
  }


}


?>