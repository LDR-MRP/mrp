<?php headerAdmin($data); ?>
<div id="contentAjax"></div>

<div class="main-content">
  <div class="page-content">
    <div class="container-fluid">

      <!-- start page title -->
      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>

            <div class="page-title-right">
              <ol class="breadcrumb m-0">
                <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
              </ol>
            </div>
          </div>
        </div>
      </div>
      <!-- end page title -->

      <?php
        $resp = $data['arrOrdenDetalle'] ?? [];

        // dep($resp);
        $ok   = !empty($resp) && (int)($resp['status'] ?? 0) === 1;
        $ot   = $ok ? ($resp['data'] ?? []) : [];
        $est  = $ot['estaciones'] ?? [];

        $h = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

        // Prioridad badge
        $prioridad = strtoupper((string)($ot['prioridad'] ?? ''));
        $badgePrioridad = 'badge bg-info-subtle text-info';
        if (in_array($prioridad, ['CRITICA','CRÍTICA','ALTA'])) $badgePrioridad = 'badge bg-danger-subtle text-danger';
        else if ($prioridad === 'MEDIA') $badgePrioridad = 'badge bg-warning-subtle text-warning';
        else if ($prioridad === 'BAJA')  $badgePrioridad = 'badge bg-success-subtle text-success';

        // Estado OT badge (ajusta si tu catálogo es distinto)
        $estado = (int)($ot['estado'] ?? 0);
        $badgeEstado = 'badge bg-secondary-subtle text-secondary';
        $txtEstado   = '—';
        if ($estado === 1){ $badgeEstado='badge bg-success-subtle text-success'; $txtEstado='Activa'; }
        if ($estado === 2){ $badgeEstado='badge bg-warning-subtle text-warning'; $txtEstado='En proceso'; }
        if ($estado === 3){ $badgeEstado='badge bg-danger-subtle text-danger'; $txtEstado='Detenida'; }
        if ($estado === 4){ $badgeEstado='badge bg-success-subtle text-success'; $txtEstado='Finalizada'; }

        $fmtDate = function($d){
          if(!$d) return '—';
          $t = strtotime($d);
          return $t ? date('d/m/Y', $t) : $d;
        };

        $joinNombres = function($arr){
          if(!is_array($arr) || empty($arr)) return '—';
          $names = [];
          foreach($arr as $x){
            $names[] = trim((string)($x['nombre_completo'] ?? ''));
          }
          $names = array_values(array_filter($names));
          return empty($names) ? '—' : implode(', ', $names);
        };

        // Estado estación -> colores Velzon
        // Ajusta si tu estado no es 1/2/3
        $estStyle = function($e){
          $st = (int)($e['estado'] ?? 0);

          // default pendiente
          $cls = 'bg-light text-primary';
          $badge = '<span class="badge bg-secondary-subtle text-secondary">Pendiente</span>';
          $icon = 'ri-time-line';

          if($st === 1){
            $cls = 'bg-success text-white';
            $badge = '<span class="badge bg-success-subtle text-success">Completada</span>';
            $icon = 'ri-checkbox-circle-line';
          } elseif($st === 2){
            $cls = 'bg-warning text-white';
            $badge = '<span class="badge bg-warning-subtle text-warning">En proceso</span>';
            $icon = 'ri-play-circle-line';
          } elseif($st === 3){
            $cls = 'bg-danger text-white';
            $badge = '<span class="badge bg-danger-subtle text-danger">Detenida</span>';
            $icon = 'ri-error-warning-line';
          }

          return [$cls,$badge,$icon];
        };

        $accId = 'accOT' . (int)($ot['idplaneacion'] ?? 0);

        // Para el reloj: fechas ISO que el JS pueda leer
        $fechaInicioISO    = $ot['fecha_inicio'] ?? '';      // "2026-01-07" o "2026-01-07 09:00:00"
        $fechaRequeridaISO = $ot['fecha_requerida'] ?? '';   // "2026-01-08" o "2026-01-08 18:00:00"
      ?>

      <style>
        /* Línea de tiempo vertical (Velzon-like) */
        .profile-timeline .accordion-item{ position: relative; }
        .profile-timeline .accordion-item:not(:last-child)::after{
          content:"";
          position:absolute;
          left: 21px;
          top: 46px;
          bottom: -18px;
          width: 2px;
          background: rgba(64,81,137,.18);
        }

        /* --- Layout: columna derecha sticky en desktop --- */
        @media (min-width: 992px){
          .right-sticky{
            position: sticky;
            top: 92px; /* ajusta según tu header/topbar velzon */
          }
        }

        /* --- Chat pro --- */
        .chat-wrap{
          border: 1px solid rgba(0,0,0,.08);
          border-radius: .75rem;
          overflow: hidden;
          background: #fff;
        }
        .chat-head{
          padding: .9rem 1rem;
          background: rgba(64,81,137,.05);
          border-bottom: 1px solid rgba(0,0,0,.06);
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:.75rem;
        }
        .chat-meta{
          display:flex;
          align-items:center;
          gap:.75rem;
        }
        .chat-meta .avatars{
          display:flex;
          align-items:center;
        }
        .chat-meta .avatars .avatar-xs{
          margin-left: -8px;
          border: 2px solid #fff;
          border-radius: 999px;
        }
        .chat-meta .title{ line-height: 1.1; }
        .chat-meta .title .name{ font-weight: 700; }
        .chat-meta .title .sub{
          font-size: .78rem;
          color: rgba(0,0,0,.55);
        }

        .chat-body{
          height: 320px;
          overflow:auto;
          padding: 1rem;
          background:
            radial-gradient(500px 260px at 15% 10%, rgba(37,160,226,.08), transparent 55%),
            radial-gradient(500px 260px at 90% 30%, rgba(0,189,157,.08), transparent 55%),
            #fff;
        }

        .msg-row{
          display:flex;
          gap:.6rem;
          margin-bottom: .9rem;
          align-items:flex-end;
        }
        .msg-row.me{ justify-content:flex-end; }
        .msg-avatar{ flex:0 0 auto; }
        .msg-bubble{
          max-width: 78%;
          border-radius: 14px;
          padding: .65rem .85rem;
          background: #f3f6f9;
          border: 1px solid rgba(0,0,0,.06);
        }
        .msg-row.me .msg-bubble{
          background: rgba(64,81,137,.12);
          border-color: rgba(64,81,137,.18);
        }
        .msg-top{
          display:flex;
          align-items:center;
          gap:.5rem;
          margin-bottom:.25rem;
        }
        .msg-name{
          font-weight:700;
          font-size:.85rem;
        }
        .msg-time{
          font-size:.75rem;
          color: rgba(0,0,0,.5);
        }
        .msg-text{
          font-size:.92rem;
          color: rgba(0,0,0,.78);
        }
        .msg-row.me .msg-text{ color: rgba(0,0,0,.85); }

        .chat-foot{
          padding: .75rem;
          border-top: 1px solid rgba(0,0,0,.06);
          background: #fff;
        }
        .chat-foot .form-control{ border-radius: .6rem; }

        @media print{ .no-print{ display:none !important; } }
      </style>

      <?php if(!$ok): ?>
        <div class="alert alert-danger">
          <div class="fw-bold">No se pudo cargar el detalle de la orden</div>
          <div class="small"><?= $h($resp['msg'] ?? 'Error') ?></div>
        </div>
      <?php else: ?>

        <!-- =========================
              HEADER / RESUMEN OT
        ========================== -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h5 class="card-title mb-0">
                    Orden de Trabajo: <span class="text-primary"><?= $h($ot['num_orden'] ?? '') ?></span>
                  </h5>
                  <div class="text-muted">
                    Planeación ID: <span class="fw-semibold"><?= $h($ot['idplaneacion'] ?? '') ?></span>
                    · Producto: <span class="fw-semibold"><?= $h($ot['cve_producto'] ?? '') ?></span>
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                  <span class="<?= $badgeEstado ?>"><?= $h($txtEstado) ?></span>
                  <span class="<?= $badgePrioridad ?>">Prioridad: <?= $h($prioridad ?: '—') ?></span>
                  <span class="badge bg-dark-subtle text-dark">Fase: <?= $h($ot['fase'] ?? '—') ?></span>
                </div>
              </div>

              <div class="card-body">
                <div class="row g-3">
                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-file-text-line me-1"></i>Descripción</div>
                      <div class="fw-semibold"><?= $h($ot['descripcion'] ?? '—') ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-user-3-line me-1"></i>Supervisor</div>
                      <div class="fw-semibold"><?= $h($ot['supervisor'] ?? '—') ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-2">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-hashtag me-1"></i>Cantidad</div>
                      <div class="fw-semibold"><?= $h($ot['cantidad'] ?? '—') ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-2">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-shopping-bag-3-line me-1"></i>Pedido</div>
                      <div class="fw-semibold"><?= $h(($ot['num_pedido'] ?? '') ?: '—') ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-calendar-check-line me-1"></i>Inicio producción</div>
                      <div class="fw-semibold"><?= $h($fmtDate($ot['fecha_inicio'] ?? '')) ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-calendar-event-line me-1"></i>Fecha requerida</div>
                      <div class="fw-semibold"><?= $h($fmtDate($ot['fecha_requerida'] ?? '')) ?></div>
                    </div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                      <div class="text-muted small"><i class="ri-sticky-note-line me-1"></i>Notas / Observaciones</div>
                      <div class="fw-semibold"><?= $h(($ot['notas'] ?? '') ?: '—') ?></div>
                    </div>
                  </div>

                </div><!-- row -->
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
              ROUTE TIMELINE + RIGHT COLUMN (RELOJ + CHAT STICKY)
        ========================== -->
        <div class="row">
          <!-- Timeline -->
          <div class="col-12 col-lg-7">
            <div class="card">
              <div class="card-header">
                <div class="d-sm-flex align-items-center">
                  <h5 class="card-title flex-grow-1 mb-0">
                    Ruta / Estado del proceso
                    <span class="badge bg-primary-subtle text-primary ms-2"><?= count($est) ?> estaciones</span>
                  </h5>
                  <div class="flex-shrink-0 mt-2 mt-sm-0">
                    <a href="javascript:void(0);" class="btn btn-soft-secondary btn-sm mt-2 mt-sm-0" onclick="window.print()">
                      <i class="ri-printer-line align-middle me-1"></i> Imprimir
                    </a>
                  </div>
                </div>
              </div>

              <div class="card-body">
                <?php if(empty($est)): ?>
                  <div class="alert alert-warning mb-0">No hay estaciones registradas para esta OT.</div>
                <?php else: ?>
                  <div class="profile-timeline">
                    <div class="accordion accordion-flush" id="<?= $h($accId) ?>">
                      <?php foreach($est as $i => $e): ?>
                        <?php
                          [$clsAvatar, $badgeE, $icon] = $estStyle($e);
                          $orden = $e['orden'] ?? ($i+1);
                          $enc = $joinNombres($e['encargados'] ?? []);
                          $ayu = $joinNombres($e['ayudantes'] ?? []);

                          $headingId = $accId . 'H' . $i;
                          $collapseId = $accId . 'C' . $i;
                          $show = ($i === 0) ? 'show' : '';
                        ?>
                        <div class="accordion-item border-0">
                          <div class="accordion-header" id="<?= $h($headingId) ?>">
                            <a class="accordion-button p-2 shadow-none" data-bs-toggle="collapse"
                               href="#<?= $h($collapseId) ?>" aria-expanded="true" aria-controls="<?= $h($collapseId) ?>">
                              <div class="d-flex align-items-center w-100">
                                <div class="flex-shrink-0 avatar-xs">
                                  <div class="avatar-title <?= $h($clsAvatar) ?> rounded-circle">
                                    <i class="<?= $h($icon) ?>"></i>
                                  </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                  <h6 class="fs-15 mb-0 fw-semibold">
                                    #<?= $h($orden) ?> · <?= $h($e['nombre_estacion'] ?? '—') ?>
                                    <span class="fw-normal text-muted">(<?= $h($e['cve_estacion'] ?? '—') ?>)</span>
                                  </h6>
                                  <div class="text-muted small">
                                    <i class="ri-settings-3-line me-1"></i><?= $h($e['proceso'] ?? '—') ?>
                                  </div>
                                </div>
                                <div class="ms-auto"><?= $badgeE ?></div>
                              </div>
                            </a>
                          </div>

                          <div id="<?= $h($collapseId) ?>" class="accordion-collapse collapse <?= $show ?>"
                               aria-labelledby="<?= $h($headingId) ?>" data-bs-parent="#<?= $h($accId) ?>">
                            <div class="accordion-body ms-2 ps-5 pt-0">
                              <div class="row g-2 mt-1">
                                <div class="col-12 col-md-6">
                                  <div class="p-2 border rounded">
                                    <div class="text-muted small mb-1">
                                      <i class="ri-user-star-line me-1"></i> Encargado(s)
                                    </div>
                                    <div class="fw-semibold"><?= $h($enc) ?></div>
                                  </div>
                                </div>

                                <div class="col-12 col-md-6">
                                  <div class="p-2 border rounded">
                                    <div class="text-muted small mb-1">
                                      <i class="ri-user-follow-line me-1"></i> Ayudante(s)
                                    </div>
                                    <div class="fw-semibold"><?= $h($ayu) ?></div>
                                  </div>
                                </div>

                                <div class="col-12">
                                  <div class="p-2 border rounded">
                                    <div class="text-muted small mb-1">
                                      <i class="ri-information-line me-1"></i> Detalle
                                    </div>
                                    <div class="text-muted">
                                      EstaciónID: <b><?= $h($e['estacionid'] ?? '—') ?></b>
                                      · PlaneaciónEstaciónID: <b><?= $h($e['id_planeacion_estacion'] ?? '—') ?></b>
                                      · Estado: <?= $badgeE ?>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: STICKY -->
          <div class="col-12 col-lg-5 no-print">
            <div class="right-sticky">

              <!-- RELOJ / TRACKING -->
              <div class="card mb-4"
                   id="timeTrackerCard"
                   data-fecha-inicio="<?= $h($fechaInicioISO) ?>"
                   data-fecha-requerida="<?= $h($fechaRequeridaISO) ?>"
                   data-ot="<?= $h($ot['num_orden'] ?? '') ?>"
                   data-planeacion="<?= $h($ot['idplaneacion'] ?? '') ?>">
                <div class="card-body text-center">
                  <h6 class="card-title mb-3 text-start">Seguimiento del tiempo</h6>

                  <div class="mb-2">
                    <lord-icon
                      src="https://cdn.lordicon.com/kbtmbyzy.json"
                      trigger="loop"
                      colors="primary:#25a0e2,secondary:#00bd9d"
                      style="width:90px;height:90px">
                    </lord-icon>
                  </div>

                  <h3 class="mb-1" id="ttMain">—</h3>
                  <h5 class="fs-14 mb-2 text-muted" id="ttSubtitle">Calculando tiempo…</h5>

                  <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                    <span class="badge bg-light text-dark">
                      <i class="ri-calendar-check-line me-1"></i>
                      Inicio: <span id="ttInicio"><?= $h($fmtDate($fechaInicioISO)) ?></span>
                    </span>
                    <span class="badge bg-light text-dark">
                      <i class="ri-calendar-event-line me-1"></i>
                      Requerida: <span id="ttReq"><?= $h($fmtDate($fechaRequeridaISO)) ?></span>
                    </span>
                  </div>

                  <div class="hstack gap-2 justify-content-center">
                    <button class="btn btn-primary btn-sm" id="ttStop" type="button">
                      <i class="ri-stop-circle-line align-bottom me-1"></i>Detener
                    </button>
                    <button class="btn btn-soft-secondary btn-sm" id="ttStart" type="button">
                      <i class="ri-play-circle-line align-bottom me-1"></i>Iniciar producción
                    </button>
                  </div>

                  <div class="text-muted mt-3 small">
                    <i class="ri-timer-line me-1"></i>
                    <span id="ttHint">El conteo se basa en fecha de inicio / requerida.</span>
                  </div>
                </div>
              </div>

              <!-- CHAT PRO -->
              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                  <h5 class="card-title mb-0">Chat de la OT</h5>
                  <span class="badge bg-success-subtle text-success">
                    <i class="ri-wifi-line me-1"></i>Conectado
                  </span>
                </div>

                <div class="card-body">
                  <div class="chat-wrap">

                    <div class="chat-head">
                      <div class="chat-meta">
                        <div class="avatars">
                          <div class="avatar-xs">
                            <div class="avatar-title rounded-circle bg-primary text-white">SH</div>
                          </div>
                          <div class="avatar-xs">
                            <div class="avatar-title rounded-circle bg-warning text-white">EG</div>
                          </div>
                          <div class="avatar-xs">
                            <div class="avatar-title rounded-circle bg-success text-white">TU</div>
                          </div>
                        </div>
                        <div class="title">
                          <div class="name">Orden: <?= $h($ot['num_orden'] ?? '') ?></div>
                          <div class="sub">Supervisor · Encargado · Ayudante</div>
                        </div>
                      </div>

                      <button type="button" class="btn btn-soft-secondary btn-sm">
                        <i class="ri-more-2-fill"></i>
                      </button>
                    </div>

                    <!-- <div class="chat-body" id="chatBody">
                      <div class="msg-row">
                        <div class="msg-avatar">
                          <div class="avatar-xs">
                            <div class="avatar-title rounded-circle bg-primary text-white">SH</div>
                          </div>
                        </div>
                        <div class="msg-bubble">
                          <div class="msg-top">
                            <div class="msg-name">Supervisor</div>
                            <div class="msg-time">Hoy 09:10</div>
                          </div>
                          <div class="msg-text">Revisen el proceso de la estación 1 antes de pasar a la 2.</div>
                        </div>
                      </div>

                      <div class="msg-row me">
                        <div class="msg-bubble">
                          <div class="msg-top justify-content-end">
                            <div class="msg-time">Hoy 09:11</div>
                            <div class="msg-name">Tú</div>
                          </div>
                          <div class="msg-text">Copiado, en cuanto termine la estación 1 aviso avance.</div>
                        </div>
                      </div>
                    </div> -->

                    <div class="chat-foot">
                      <div class="input-group">
                        <button class="btn btn-light" type="button" title="Adjuntar">
                          <i class="ri-attachment-2"></i>
                        </button>
                        <button class="btn btn-light" type="button" title="Emoji">
                          <i class="ri-emotion-line"></i>
                        </button>
                        <input type="text" class="form-control" placeholder="Escribe un mensaje..." />
                        <button class="btn btn-primary" type="button">
                          <i class="ri-send-plane-2-line me-1"></i>Enviar
                        </button>
                      </div>

                    </div>

                  </div>
                </div>
              </div>

            </div><!-- /right-sticky -->
          </div>
        </div><!-- row -->

      <?php endif; ?>

    </div><!-- container-fluid -->
  </div><!-- End Page-content -->

  <footer class="footer">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <script>document.write(new Date().getFullYear())</script> © LDR.
        </div>
        <div class="col-sm-6">
          <div class="text-sm-end d-none d-sm-block">
            LDR Solutions · MRP
          </div>
        </div>
      </div>
    </div>
  </footer>
</div>

<!-- LordIcon (requerido para que se vea el icono) -->
<script src="https://cdn.lordicon.com/lordicon.js"></script>



<?php footerAdmin($data); ?>
