<?php headerAdmin($data);
// dep($_SESSION);
?>


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
        // dep($ot);
        $est  = $ot['estaciones'] ?? [];

        $h = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

        
        $prioridad = strtoupper((string)($ot['prioridad'] ?? ''));
        $badgePrioridad = 'badge bg-info-subtle text-info';
        if (in_array($prioridad, ['CRITICA','CRÍTICA','ALTA'])) $badgePrioridad = 'badge bg-danger-subtle text-danger';
        else if ($prioridad === 'MEDIA') $badgePrioridad = 'badge bg-warning-subtle text-warning';
        else if ($prioridad === 'BAJA')  $badgePrioridad = 'badge bg-success-subtle text-success';

   
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


$fmtDT = function($d){
  $d = trim((string)$d);
  if ($d === '' || $d === '0000-00-00 00:00:00' || $d === '0000-00-00') return '—';


  if (preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) {
    $t = strtotime($d);
    return $t ? date('Y-m-d H:i:s', $t) : $d;
  }


  $dt = DateTime::createFromFormat('d/m/Y H:i', $d);
  if ($dt) return $dt->format('Y-m-d H:i:s');

  $dt2 = DateTime::createFromFormat('d/m/Y H:i:s', $d);
  if ($dt2) return $dt2->format('Y-m-d H:i:s');


  $t = strtotime($d);
  return $t ? date('Y-m-d H:i:s', $t) : $d;
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


        $estStyle = function($e){
          $st = (int)($e['estado'] ?? 0);

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


        $badgeEstatusOT = function($estatus){
          $st = (int)$estatus;
          $cls = 'badge bg-secondary-subtle text-secondary';
          $txt = 'Pendiente';

          if($st === 1){ $cls = 'badge bg-secondary-subtle text-secondary'; $txt = 'Pendiente'; }
          if($st === 2){ $cls = 'badge bg-warning-subtle text-warning';   $txt = 'En proceso'; }
          if($st === 3){ $cls = 'badge bg-success-subtle text-success';   $txt = 'Finalizada'; }
          if($st === 4){ $cls = 'badge bg-danger-subtle text-danger';     $txt = 'Detenida'; }

          return '<span class="'.$cls.'">'.$txt.'</span>';
        };

        $accId = 'accOT' . (int)($ot['idplaneacion'] ?? 0);

        $fechaInicioISO    = $ot['fecha_inicio'] ?? '';
        $fechaRequeridaISO = $ot['fecha_requerida'] ?? '';

   
        $pdfUrl = base_url() . '/plan_planeacion/descargarOrden/' . urlencode((string)($ot['num_orden'] ?? ''));
      ?>

      <style>
  
        :root{
          --mrp-surface: var(--vz-card-bg, var(--bs-body-bg));
          --mrp-surface-2: rgba(64,81,137,.06);
          --mrp-border: var(--vz-border-color, rgba(0,0,0,.12));
          --mrp-text: var(--vz-body-color, var(--bs-body-color));

          --mrp-muted: var(--vz-text-muted, rgba(0,0,0,.55));
       
          --mrp-head-text: rgba(0,0,0,.65);
        }


        html[data-bs-theme="dark"], body[data-layout-mode="dark"]{
          --mrp-surface-2: rgba(255,255,255,.05);
          --mrp-muted: rgba(255,255,255,.65);
          --mrp-head-text: rgba(255,255,255,.72);
        }


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
        html[data-bs-theme="dark"] .profile-timeline .accordion-item:not(:last-child)::after,
        body[data-layout-mode="dark"] .profile-timeline .accordion-item:not(:last-child)::after{
          background: rgba(255,255,255,.12);
        }


        @media (min-width: 992px){
          .right-sticky{
            position: sticky;
            top: 92px;
          }
        }

        .kpi-box{
          background: var(--mrp-surface);
          border: 1px solid var(--mrp-border) !important;
          border-radius: .85rem;
        }

  
        .chat-wrap{
          border: 1px solid var(--mrp-border);
          border-radius: .85rem;
          overflow: hidden;
          background: var(--mrp-surface);
        }
        .chat-head{
          padding: .9rem 1rem;
          background: var(--mrp-surface-2);
          border-bottom: 1px solid var(--mrp-border);
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:.75rem;
        }
        .chat-meta{ display:flex; align-items:center; gap:.75rem; }
        .chat-meta .avatars{ display:flex; align-items:center; }
        .chat-meta .avatars .avatar-xs{
          margin-left: -8px;
          border: 2px solid var(--mrp-surface);
          border-radius: 999px;
        }
        .chat-meta .title{ line-height: 1.1; }
        .chat-meta .title .name{ font-weight: 700; color: var(--mrp-text); }
        .chat-meta .title .sub{ font-size: .78rem; color: var(--mrp-muted); }

        .chat-body{
          height: 320px;
          overflow:auto;
          padding: 1rem;
          background: var(--mrp-surface);
        }
        html:not([data-bs-theme="dark"]) .chat-body{
          background:
            radial-gradient(500px 260px at 15% 10%, rgba(37,160,226,.08), transparent 55%),
            radial-gradient(500px 260px at 90% 30%, rgba(0,189,157,.08), transparent 55%),
            var(--mrp-surface);
        }
        html[data-bs-theme="dark"] .chat-body,
        body[data-layout-mode="dark"] .chat-body{
          background: var(--mrp-surface);
        }

        .chat-foot{
          padding: .75rem;
          border-top: 1px solid var(--mrp-border);
          background: var(--mrp-surface);
        }
        .chat-foot .form-control{ border-radius: .6rem; }


        .subot-table{
          border: 1px solid var(--mrp-border);
          border-radius: .85rem;
          overflow: hidden;
          background: var(--mrp-surface);
        }
        .subot-table .table{ margin-bottom: 0; }
        .subot-table .table thead th{
          font-size: .78rem;
          letter-spacing: .02em;
          text-transform: uppercase;

          color: var(--mrp-head-text);
          background: var(--mrp-surface-2);
          border-bottom: 1px solid var(--mrp-border);
          vertical-align: middle;
        }
        .subot-table .table td{
          border-color: var(--mrp-border);
          vertical-align: middle;
        }
        .subot-actions .btn{ white-space: nowrap; }

        .subot-table tbody tr:hover{
          background: rgba(64,81,137,.04);
        }
        html[data-bs-theme="dark"] .subot-table tbody tr:hover,
        body[data-layout-mode="dark"] .subot-table tbody tr:hover{
          background: rgba(255,255,255,.04);
        }

  
        .btn-pdf-cta{
          border-radius: .75rem;
          padding: .55rem .9rem;
          box-shadow: 0 8px 18px rgba(0,0,0,.12);
        }

   
        .comment-textarea{
          min-height: 120px;
          resize: vertical;
        }

        @media print{ .no-print{ display:none !important; } }
      </style>

      <?php if(!$ok): ?>
        <div class="alert alert-danger">
          <div class="fw-bold">No se pudo cargar el detalle de la orden</div>
          <div class="small"><?= $h($resp['msg'] ?? 'Error') ?></div>
        </div>
      <?php else: ?>

        <!-- HEADER / RESUMEN OT + ACCIONES -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <h5 class="card-title mb-0">
                    Orden de Trabajo: <span class="text-primary"><?= $h($ot['num_orden'] ?? '') ?></span>
                  </h5>
                  <div class="text-muted">
                   Producto: <span class="fw-semibold"><?= $h($ot['cve_producto'] ?? '') ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                  <span class="<?= $badgeEstado ?>"><?= $h($txtEstado) ?></span>
                  <span class="<?= $badgePrioridad ?>">Prioridad: <?= $h($prioridad ?: '—') ?></span>
                  

                  <div class="vr d-none d-md-block"></div>

                  <button type="button" class="btn btn-soft-secondary btn-sm" onclick="history.back()">
                    <i class="ri-arrow-left-line me-1"></i>Volver
                  </button>

         <button
  type="button"
  class="btn btn-danger btn-sm btn-pdf-cta btnPdfOT"
  data-numorden="<?= $h($ot['num_orden'] ?? '') ?>">
  <i class="ri-file-pdf-2-line me-1"></i>Ver OT en PDF
</button>

                </div>
              </div>

              <div class="card-body">
                <div class="row g-3">

                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-4">
                          <i class="ri-file-text-line text-primary"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Descripción</div>
                        <div class="fw-semibold"><?= $h($ot['descripcion'] ?? '—') ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-4">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-4">
                          <i class="ri-user-3-line text-success"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Supervisor</div>
                        <div class="fw-semibold"><?= $h($ot['supervisor'] ?? '—') ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-2">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded fs-4">
                          <i class="ri-hashtag text-warning"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Cantidad</div>
                        <div class="fw-semibold"><?= $h($ot['cantidad'] ?? '—') ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-2">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-4">
                          <i class="ri-shopping-bag-3-line text-info"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Pedido</div>
                        <div class="fw-semibold"><?= $h(($ot['num_pedido'] ?? '') ?: '—') ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-4">
                          <i class="ri-calendar-check-line text-primary"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Inicio producción</div>
                        <div class="fw-semibold"><?= $h($fmtDate($ot['fecha_inicio'] ?? '')) ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded fs-4">
                          <i class="ri-calendar-event-line text-danger"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Fecha requerida</div>
                        <div class="fw-semibold"><?= $h($fmtDate($ot['fecha_requerida'] ?? '')) ?></div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-lg-6">
                    <div class="kpi-box p-3 h-100 d-flex gap-3 align-items-start">
                      <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-secondary-subtle rounded fs-4">
                          <i class="ri-sticky-note-line text-secondary"></i>
                        </span>
                      </div>
                      <div>
                        <div class="text-muted small">Notas / Observaciones</div>
                        <div class="fw-semibold"><?= $h(($ot['notas'] ?? '') ?: '—') ?></div>
                      </div>
                    </div>
                  </div>

                </div><!-- row -->
              </div>
            </div>
          </div>
        </div>

       
        <div class="row">
          <!-- Timeline -->
          <div class="col-12 col-lg-8">
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
                      <?php  foreach($est as $i => $e): ?>
                        <?php
                          [$clsAvatar, $badgeE, $icon] = $estStyle($e);
                          $orden = $e['orden'] ?? ($i+1);
                          $enc = $joinNombres($e['encargados'] ?? []);
                          $ayu = $joinNombres($e['ayudantes'] ?? []);
                          



                          $headingId = $accId . 'H' . $i;
                          $collapseId = $accId . 'C' . $i;
                          $show = ($i === 0) ? 'show' : '';

                          $ots = $e['ordenes_trabajo'] ?? [];

                        
                          $peid = (int)($e['id_planeacion_estacion'] ?? 0);
                        ?>
                        <div class="accordion-item border-0" data-est-orden="<?= $estacion['orden']; ?>">
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
                                  <div class="kpi-box p-2">
                                    <div class="text-muted small mb-1">
                                      <i class="ri-user-star-line me-1"></i> Encargado(s)
                                    </div>
                                    <div class="fw-semibold"><?= $h($enc) ?></div>
                                  </div>
                                </div>

                                <div class="col-12 col-md-6">
                                  <div class="kpi-box p-2">
                                    <div class="text-muted small mb-1">
                                      <i class="ri-user-follow-line me-1"></i> Ayudante(s)
                                    </div>
                                    <div class="fw-semibold"><?= $h($ayu) ?></div>
                                  </div>
                                </div>

                                <!-- SUB-ORDENES -->
                                <div class="col-12">
                                  <div class="kpi-box p-2">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                      <div class="text-muted small">
                                        <i class="ri-task-line me-1"></i> Órdenes de trabajo (sub-OT)
                                      </div>
                                      <span class="badge bg-primary-subtle text-primary">
                                        <?= is_array($ots) ? count($ots) : 0 ?> registros
                                      </span>
                                    </div>

                                    <?php if(empty($ots)): ?>
                                      <div class="alert alert-warning mb-0 py-2">
                                        No hay órdenes de trabajo registradas para esta estación.
                                      </div>
                                    <?php else: ?>
                                      <div class="subot-table">
                                        <div class="table-responsive">
                                          <table class="table table-sm align-middle">
                                            <thead>
                                              <tr>
                                                <th style="width:170px;">Sub OT</th>
                                                <th style="width:120px;">Estatus</th>
                                                <th style="width:170px;">Inicio</th>
                                                <th style="width:170px;">Fin</th>
                                                <th class="text-end" style="width:280px;">Acciones</th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <?php foreach($ots as $otRow): ?>
                                                <?php
                                                  $idorden  = (int)($otRow['idorden'] ?? 0);
                                                  $subot    = (string)($otRow['num_sub_orden'] ?? '');
                                                  $inicio   = $fmtDT($otRow['fecha_inicio'] ?? '');
                                                  $fin      = $fmtDT($otRow['fecha_fin'] ?? '');
                                                  $estatus  = (int)($otRow['estatus'] ?? 0);
                                                  $badgeOT  = $badgeEstatusOT($estatus);

                                              
                                                  $coment   = (string)($otRow['comentarios'] ?? '');

                                                  $disableStart  = ($estatus === 2 || $estatus === 3) ? 'disabled' : '';
                                                  $disableFinish = ($estatus === 1 || $estatus === 3) ? 'disabled' : '';
                                                ?>
                                                <tr
                                                  data-idorden="<?= $h($idorden) ?>"
                                                  data-peid="<?= $h($peid) ?>"
                                                  data-subot="<?= $h($subot) ?>"
                                                  data-coment="<?= $h($coment) ?>"
                                                  data-estatus="<?= $h($estatus) ?>"

                                                  
                                                >
                                                  <td class="fw-semibold text-primary"><?= $h($subot) ?></td>
                                                  <td><?= $badgeOT ?></td>
                                                  <td><span class="text-muted"><?= $h($inicio) ?></span></td>
                                                  <td><span class="text-muted"><?= $h($fin) ?></span></td>
                                                  <td class="text-end subot-actions">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                      <button
                                                        type="button"
                                                        class="btn btn-soft-primary btnStartOT"
                                                        <?= $disableStart ?>
                                                        data-idorden="<?= $h($idorden) ?>"
                                                        data-peid="<?= $h($peid) ?>"
                                                        data-subot="<?= $h($subot) ?>"
                                                        data-estatus="<?= $h($estatus) ?>"
                                                        data-est-orden="<?= $h($orden) ?>"
                                                      >
                                                        <i class="ri-play-circle-line me-1"></i>Iniciar
                                                      </button>

                                                      <button
                                                        type="button"
                                                        class="btn btn-soft-success btnFinishOT"
                                                        <?= $disableFinish ?>
                                                        data-idorden="<?= $h($idorden) ?>"
                                                        data-peid="<?= $h($peid) ?>"
                                                        data-subot="<?= $h($subot) ?>"
                                                      >
                                                        <i class="ri-checkbox-circle-line me-1"></i>Finalizar
                                                      </button>

                                              
                                                      <button
                                                        type="button"
                                                        class="btn btn-soft-secondary btnCommentOT"
                                                        data-idorden="<?= $h($idorden) ?>"
                                                        data-peid="<?= $h($peid) ?>"
                                                        data-subot="<?= $h($subot) ?>"
                                                      >
                                                        <i class="ri-chat-3-line me-1"></i>Comentarios
                                                      </button>

                                                      <!-- <button class="btn btn-soft-primary btnChatOT"
  data-planeacionid="90"
  data-subotkey="OT260115-004-S03"
  data-estacionid="2">
  <i class="ri-message-3-line me-1"></i>Chat
</button> -->

<!-- <button type="button"
  class="btn btn-soft-info btnChatOT"
  data-numorden="OT260116-002"
  data-subot="OT260116-002-S01"
  data-productoid="57"
  data-estacionid="13"
  data-planeacionid="39">
  <i class="ri-message-3-line me-1"></i>Chat
</button> -->

<!-- <button type="button"
  class="btn btn-sm btn-soft-info btnChatOT"
  data-subot="<?= $otRow['num_sub_orden']; ?>">
  <i class="ri-message-3-line me-1"></i>Chat
</button> -->

<button type="button"
  class="btn btn-soft-primary btn-sm btnChatOT"

  data-subot="<?= $otRow['num_sub_orden'] ?>"
  data-estacionid="<?= $e['estacionid'] ?>"
  data-planeacionid="<?= $e['planeacionid'] ?>"
  data-productoid="<?= $ot['productoid'] ?>">
  <i class="ri-message-3-line me-1"></i> Chat
</button>








                                                    </div>
                                                  </td>
                                                </tr>
                                              <?php endforeach; ?>
                                            </tbody>
                                          </table>
                                        </div>
                                      </div>
                                    <?php endif; ?>

                                  </div>
                                </div>
                                <!-- /SUB-ORDENES -->

                              </div><!-- row -->
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

          <!-- RIGHT COLUMN -->
          <div class="col-12 col-lg-4 no-print">
            <div class="right-sticky">

           
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

                  <div class="text-muted mt-3 small">
                    <i class="ri-timer-line me-1"></i>
                    <span id="ttHint">El conteo se basa en fecha de inicio / requerida.</span>
                  </div>
                </div>
              </div>

              <!-- CHAT -->
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

    
        <div class="modal fade" id="modalOTComment" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="ri-chat-3-line me-1 text-primary"></i>
                  Comentarios · <span id="mSubOT" class="text-primary">—</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="modal-body">
                <input type="text" id="mIdOrden" value="">
                <input type="text" id="mPeid" value="">

                <div class="alert alert-info py-2 mb-3">
                  <div class="small mb-0">
                    Aquí puedes registrar observaciones del proceso.
                  </div>
                </div>

                <label class="form-label fw-semibold">Comentario</label>
                <textarea
                  id="mComentario"
                  class="form-control comment-textarea"
                  placeholder="Escribe el comentario..."
                  rows="6"
                ></textarea>

                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="text-muted small" id="mHint">
                    <i class="ri-information-line me-1"></i>Se guardará ligado a la Sub-OT seleccionada.
                  </div>
               
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-soft-secondary" data-bs-dismiss="modal">
                  <i class="ri-close-line me-1"></i>Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveOTComment">
                  <i class="ri-save-3-line me-1"></i>Guardar comentario
                </button>
              </div>
            </div>
          </div>
        </div>



<!-- ===========================
  MODAL CHAT OT
=========================== -->

<div class="modal fade" id="modalChatOT" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title mb-1">Chat Sub-OT: <span id="chatSubotTitle">—</span></h5>
          <small class="text-muted">Comunicación por estación / sub-OT</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

  
      <div class="modal-body p-0">
        <div class="v-chat-wrap">

          <!-- Body -->
          <div class="v-chat-body" id="chatMessages"   style="
    background-image: url('<?= media(); ?>/minimal/images/chat-bg-pattern.png');
    background-repeat: repeat;
    background-size: auto;
    background-position: center;
  ">
            <!-- mensajes -->
          </div>
 
          <!-- Footer -->
          <div class="v-chat-footer">
            <input type="hidden" id="chat_subot" value="">

            <div class="input-group">
              <input type="text" class="form-control" id="chatInput" placeholder="Escribe tu mensaje..." autocomplete="off">
              <button class="btn btn-success" type="button" id="chatSendBtn">
                <i class="ri-send-plane-2-fill"></i>
              </button>
            </div>

            <div class="d-flex justify-content-between mt-2">
              <!-- <small class="text-muted" id="chatStatusHint">Listo</small> -->
              <small class="text-muted">Enter para enviar</small>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>





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

<script src="https://cdn.lordicon.com/lordicon.js"></script>

<script>

</script>

<?php footerAdmin($data); ?>
