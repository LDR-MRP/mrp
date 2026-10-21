<?php headerAdmin($data); ?>

<style>
/* Modern UI Tokens Scoped to Detalle de Envíos */
:root {
    --brand-primary: #C46623;
    --brand-primary-hover: #b0571c;
    --brand-dark: #0F172A;
    --surface-card: #FFFFFF;
    --surface-border: #E2E8F0;
    --surface-bg: #F8FAFC;
}

.envios-surface-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 18px;
    box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Smart Search Pill */
.search-pill-modern {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 30px;
    padding: 6px 14px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.search-pill-modern:focus-within {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(196, 102, 35, 0.15);
}
.search-pill-modern input {
    border: none;
    outline: none;
    box-shadow: none;
    font-size: 12.5px;
    background: transparent;
}

/* Segmented Pill Tabs */
.nav-segmented {
    background: #F1F5F9;
    padding: 4px;
    border-radius: 12px;
    display: inline-flex;
    border: none;
    gap: 4px;
}
.nav-segmented .nav-link {
    border: none;
    border-radius: 8px;
    padding: 7px 16px;
    color: #64748B;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
    background: transparent;
}
.nav-segmented .nav-link:hover {
    color: #0F172A;
}
.nav-segmented .nav-link.active {
    background: #FFFFFF;
    color: var(--brand-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

/* Drag & Drop Visual Enhancements */
.cursor-move { 
    cursor: grab; 
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.cursor-move:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px -2px rgba(15, 23, 42, 0.08) !important;
}
.cursor-move:active { 
    cursor: grabbing; 
}
.sortable-list { 
    background-color: #F8FAFC; 
    padding: 12px; 
    border-radius: 14px;
    transition: all 0.2s ease;
}
.sortable-ghost { 
    opacity: 0.45; 
    background-color: #FFF7ED !important; 
    border: 2px dashed var(--brand-primary) !important;
    transform: scale(0.98);
}
.sortable-chosen {
    box-shadow: 0 12px 24px -4px rgba(196, 102, 35, 0.2) !important;
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- HEADER -->
            <div class="row align-items-center mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/Lgs_envios">Envíos</a></li>
                                <li class="breadcrumb-item active text-primary fw-semibold">Acomodo de VINs</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TÍTULO -->
            <div class="row mb-4 align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-sm border border-light" style="background-color: var(--brand-primary) !important;">
                                <i class="ri-drag-move-2-line"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-dark text-uppercase ls-1">
                                Asignación y Acomodo (Envío #<?= $data['id_envio'] ?>)
                            </h3>
                            <p class="text-muted fs-13 mb-0">Arrastre los VINs desde el pool disponible hacia la Madrina/Chofer y ordénelos según su destino de entrega.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <?php 
                        $estadoEnvio = intval($data['envio']['id_estado'] ?? 1);
                        $estadoPlan = intval($data['estado_planeacion'] ?? 0);
                        
                        $enPlaneacionAbierta = ($estadoEnvio === 2 && $estadoPlan < 2);
                        $puedeEditar = ($estadoEnvio === 8 || $enPlaneacionAbierta);
                        
                        if ($estadoEnvio === 1): 
                    ?>
                        <button class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold" onclick="guardarAcomodo(true);" style="background-color: var(--brand-primary); border-color: var(--brand-primary);">
                            <i class="ri-check-double-line me-1"></i> Finalizar y Volver
                        </button>
                    <?php elseif ($puedeEditar): ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-light border rounded-pill px-4 py-2 shadow-sm me-2 fw-semibold text-secondary">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver
                        </a>
                        <button class="btn btn-warning rounded-pill px-4 py-2 shadow-sm fw-semibold" onclick="regresarABorrador();">
                            <i class="ri-edit-line me-1"></i> <?= $enPlaneacionAbierta ? 'Editar (Regresa a Borrador)' : 'Editar (Regresa a Borrador)' ?>
                        </button>
                    <?php else: ?>
                        <?php if ($estadoEnvio >= 3): ?>
                            <a href="<?= base_url(); ?>/Lgs_incidencias?envio=<?= $data['id_envio'] ?>" class="btn btn-soft-danger rounded-pill px-3 py-2 shadow-sm me-2 fw-medium" title="Reportar Incidencia Operativa">
                                <i class="ri-alarm-warning-line me-1"></i> Incidencia
                            </a>
                            <a href="<?= base_url(); ?>/Lgs_gastosadicionales?envio=<?= $data['id_envio'] ?>" class="btn btn-soft-success rounded-pill px-3 py-2 shadow-sm me-2 fw-medium" title="Registrar Gasto Adicional">
                                <i class="ri-money-dollar-circle-line me-1"></i> + Gasto Adicional
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-light border rounded-pill px-4 py-2 shadow-sm fw-semibold text-secondary">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver (Solo Lectura)
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- LEYENDA INFORMATIVA ORDEN DE CARGA Y LOGÍSTICA MULTI-ORIGEN / MULTI-DESTINO -->
            <div class="alert alert-soft-primary border-0 shadow-xs rounded-3 mb-4 d-flex align-items-center">
                <i class="ri-information-line fs-22 me-3 text-primary flex-shrink-0"></i>
                <div class="fs-13">
                    <strong class="text-dark">Ruta Multi-Origen y Multi-Destino:</strong>
                    Para cada unidad asignada a la madrina, defina el nodo de <span class="badge bg-success">🟢 Subida (Carga)</span> y el nodo de <span class="badge text-white" style="background-color: #fd7e14;">🟠 Descarga (Bajada)</span>. El sistema adicionará el factor volumétrico en las recolecciones y lo descontará en las entregas intermedias, calculando el costo exacto por tramo con tarifas o memoria de distancias $/km.
                </div>
            </div>

            <!-- RESUMEN EJECUTIVO DE LA RUTA Y KM -->
            <div class="row mb-4" id="card-resumen-ruta-envio">
                <div class="col-12">
                    <div class="envios-surface-card p-3">
                        <div class="row align-items-center g-3">
                            <div class="col-md-3 border-end border-light-subtle">
                                <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1"><i class="ri-map-pin-line text-danger me-1"></i> Origen Salida</span>
                                <strong class="fs-13 text-dark" id="lbl-resumen-origen">Cargando...</strong>
                            </div>
                            <div class="col-md-3 border-end border-light-subtle">
                                <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1"><i class="ri-route-line text-primary me-1"></i> Distancia Ruta Total</span>
                                <span class="badge bg-primary fs-12 px-2 py-1 rounded-pill" id="lbl-resumen-km-total">0 km</span>
                                <small class="text-muted fs-11 ms-1">(Tarifario)</small>
                            </div>
                            <div class="col-md-3 border-end border-light-subtle">
                                <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1"><i class="ri-flag-line text-success me-1"></i> Paradas del Viaje</span>
                                <div class="fs-13 text-dark" id="lbl-resumen-paradas">0 paradas</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1"><i class="ri-money-dollar-circle-line text-success me-1"></i> Costo Est. Envío</span>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <strong class="fs-18 text-success fw-bold" id="lbl-resumen-costo">$0.00</strong>
                                    <span id="badge-monto-abierto-detalle" class="badge bg-warning text-dark fs-11 d-none"><i class="ri-hand-coin-line me-1"></i>Monto Abierto</span>
                                </div>
                                <small class="text-muted fs-11 d-block mt-1" id="lbl-motivo-monto-abierto" style="display:none;"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENEDOR DE LA INFOGRAFÍA DE FACTORAJE Y TRAMOS -->
            <div id="panel-infografia-ruta" class="mb-4"></div>

            <?php 
                $audit = $data['auditoria_financiera'] ?? null;
                if ($estadoEnvio >= 3 && !empty($audit)): 
                    $totales = $audit['totales'] ?? [];
            ?>
            <!-- ── SECCIÓN: AUDITORÍA FINANCIERA, INCIDENCIAS Y COSTO REAL POR VIN ── -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 overflow-hidden">
                <div class="card-header bg-gradient bg-light border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                <i class="ri-scales-3-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                Auditoría de Costo Real por VIN & Eventos Operativos
                            </h5>
                            <small class="text-muted">
                                Comparativa de tarifa base aprobada vs costos reales acumulados por incidencias y gastos en tránsito/entrega.
                            </small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url(); ?>/Lgs_incidencias?envio=<?= $data['id_envio'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium">
                            <i class="ri-alarm-warning-line me-1"></i> Reportar Incidencia
                        </a>
                        <a href="<?= base_url(); ?>/Lgs_gastosadicionales?envio=<?= $data['id_envio'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-medium">
                            <i class="ri-money-dollar-circle-line me-1"></i> Asignar Gasto Adicional
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- 4 TARJETAS KPI RESUMEN FINANCIERO -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 rounded-3 bg-light border border-light">
                                <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Costo Base Tarifario</span>
                                <h4 class="mb-0 fw-bold text-secondary">$<?= number_format(floatval($totales['costo_planeado'] ?? 0), 2) ?></h4>
                                <small class="text-muted fs-11">Aprobado inicialmente</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-3 bg-soft-danger border border-danger-subtle">
                                <span class="text-danger fs-11 text-uppercase fw-bold d-block mb-1">Gastos Adicionales (Cargos)</span>
                                <h4 class="mb-0 fw-bold text-danger">+$<?= number_format(floatval($totales['total_cargos'] ?? 0), 2) ?></h4>
                                <small class="text-danger fs-11"><?= intval($totales['gastos_count'] ?? 0) ?> gastos registrados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-3 bg-soft-success border border-success-subtle">
                                <span class="text-success fs-11 text-uppercase fw-bold d-block mb-1">Costo Real Acumulado</span>
                                <h4 class="mb-0 fw-bold text-success">$<?= number_format(floatval($totales['costo_real'] ?? 0), 2) ?></h4>
                                <small class="text-success fs-11">Base + Cargos aplicados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded-3 bg-soft-warning border border-warning-subtle">
                                <span class="text-warning fs-11 text-uppercase fw-bold d-block mb-1">Incidencias en Ruta / Entrega</span>
                                <h4 class="mb-0 fw-bold text-dark"><?= intval($totales['incidencias_count'] ?? 0) ?></h4>
                                <small class="text-muted fs-11"><?= intval($totales['vins_count'] ?? 0) ?> VINs en el envío</small>
                            </div>
                        </div>
                    </div>

                    <!-- TABS INTERNAS SEGMENTADAS -->
                    <ul class="nav nav-segmented mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" href="#tab-audit-vins" role="tab">
                                <i class="ri-car-line me-1"></i> Costo Real por VIN (<?= count($audit['vins'] ?? []) ?>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#tab-audit-gastos" role="tab">
                                <i class="ri-money-dollar-circle-line me-1"></i> Gastos Adicionales (<?= count($audit['gastos'] ?? []) ?>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#tab-audit-incidencias" role="tab">
                                <i class="ri-alarm-warning-line me-1"></i> Incidencias Operativas (<?= count($audit['incidencias'] ?? []) ?>)
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-2">
                        <!-- TAB 1: VINS -->
                        <div class="tab-pane active" id="tab-audit-vins" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light fs-11 text-uppercase text-muted">
                                        <tr>
                                            <th>#</th>
                                            <th>VIN</th>
                                            <th class="text-end">Costo Base Planeado</th>
                                            <th class="text-end">Gastos Adicionales</th>
                                            <th class="text-end">Deducciones</th>
                                            <th class="text-end fw-bold text-dark">Costo Real Total</th>
                                            <th class="text-center">Gastos Activos</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-13">
                                        <?php if (!empty($audit['vins'])): ?>
                                            <?php foreach ($audit['vins'] as $idx => $v): ?>
                                                <tr>
                                                    <td><?= $idx + 1 ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark font-monospace fs-12 px-2 py-1 border"><?= htmlspecialchars($v['vin'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    </td>
                                                    <td class="text-end text-muted">$<?= number_format(floatval($v['costo_planeado_unidad']), 2) ?></td>
                                                    <td class="text-end text-danger fw-medium"><?= floatval($v['total_cargos_adicionales']) > 0 ? '+$' . number_format(floatval($v['total_cargos_adicionales']), 2) : '$0.00' ?></td>
                                                    <td class="text-end text-success fw-medium"><?= floatval($v['total_deducciones']) > 0 ? '-$' . number_format(floatval($v['total_deducciones']), 2) : '$0.00' ?></td>
                                                    <td class="text-end fw-bold text-success fs-14">$<?= number_format(floatval($v['costo_real_unidad']), 2) ?></td>
                                                    <td class="text-center">
                                                        <?php if (intval($v['total_gastos_activos']) > 0): ?>
                                                            <span class="badge bg-soft-warning text-warning rounded-pill"><?= $v['total_gastos_activos'] ?> gasto(s)</span>
                                                        <?php else: ?>
                                                            <span class="text-muted fs-11">Sin extras</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-3">No hay unidades cargadas en este envío.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: GASTOS -->
                        <div class="tab-pane" id="tab-audit-gastos" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light fs-11 text-uppercase text-muted">
                                        <tr>
                                            <th>Folio</th>
                                            <th>Incidencia</th>
                                            <th>Tipo / Concepto</th>
                                            <th class="text-end">Monto Total</th>
                                            <th>Responsable</th>
                                            <th>Comprobante</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-13">
                                        <?php if (!empty($audit['gastos'])): ?>
                                            <?php foreach ($audit['gastos'] as $g): 
                                                $estadoBadge = match(intval($g['id_estado'])) {
                                                    1 => '<span class="badge bg-soft-info text-info">Registrado</span>',
                                                    2 => '<span class="badge bg-soft-warning text-warning">En Revisión</span>',
                                                    3 => '<span class="badge bg-soft-success text-success">Aprobado</span>',
                                                    4 => '<span class="badge bg-soft-danger text-danger">Rechazado</span>',
                                                    5 => '<span class="badge bg-success text-white">Documentado</span>',
                                                    default => '<span class="badge bg-soft-secondary text-secondary">Cancelado</span>'
                                                };
                                            ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= base_url() ?>/Lgs_gastosadicionales" class="fw-bold text-primary"><?= htmlspecialchars($g['folio'], ENT_QUOTES, 'UTF-8') ?></a>
                                                    </td>
                                                    <td>
                                                        <?= !empty($g['folio_incidencia']) 
                                                            ? '<span class="badge bg-soft-danger text-danger">' . htmlspecialchars($g['folio_incidencia'], ENT_QUOTES, 'UTF-8') . '</span>'
                                                            : '<span class="badge bg-light text-muted border">Independiente</span>' ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($g['tipo_gasto'] ?? 'Sin tipo', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-end fw-bold text-dark">$<?= number_format(floatval($g['monto_final']), 2) ?></td>
                                                    <td><span class="badge bg-light text-secondary"><?= htmlspecialchars($g['responsable'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                                    <td>
                                                        <?= !empty($g['doc_folio'])
                                                            ? '<span class="badge bg-soft-primary text-primary">' . htmlspecialchars($g['doc_tipo'] . ': ' . $g['doc_folio'], ENT_QUOTES, 'UTF-8') . '</span>'
                                                            : '<span class="text-muted fs-11">Pendiente</span>' ?>
                                                    </td>
                                                    <td><?= $estadoBadge ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-3">No hay gastos adicionales registrados para este envío.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: INCIDENCIAS -->
                        <div class="tab-pane" id="tab-audit-incidencias" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light fs-11 text-uppercase text-muted">
                                        <tr>
                                            <th>Folio</th>
                                            <th>Tipo Incidencia</th>
                                            <th>Fecha Evento</th>
                                            <th>Dictamen / Absorción</th>
                                            <th>VINs Afectados</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-13">
                                        <?php if (!empty($audit['incidencias'])): ?>
                                            <?php foreach ($audit['incidencias'] as $inc): 
                                                $incBadge = match(intval($inc['id_estado'])) {
                                                    1 => '<span class="badge bg-soft-info text-info">Abierta</span>',
                                                    2 => '<span class="badge bg-soft-warning text-warning">En Investigación</span>',
                                                    3 => '<span class="badge bg-soft-primary text-primary">Dictaminada</span>',
                                                    4 => '<span class="badge bg-soft-secondary text-secondary">Con Gastos</span>',
                                                    5 => '<span class="badge bg-success text-white">Cerrada</span>',
                                                    default => '<span class="badge bg-soft-danger text-danger">Cancelada</span>'
                                                };
                                            ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= base_url() ?>/Lgs_incidencias" class="fw-bold text-danger"><?= htmlspecialchars($inc['folio'], ENT_QUOTES, 'UTF-8') ?></a>
                                                        <?= intval($inc['es_post_entrega']) === 1 ? '<span class="badge bg-soft-danger text-danger ms-1 fs-10">Reclamo Post-Entrega</span>' : '' ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($inc['tipo_incidencia'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-muted"><?= htmlspecialchars($inc['fecha_incidente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td>
                                                        <?= !empty($inc['absorcion']) 
                                                            ? '<span class="badge bg-soft-info text-info">' . htmlspecialchars($inc['absorcion'], ENT_QUOTES, 'UTF-8') . '</span>'
                                                            : '<span class="text-muted fs-11">Por Dictaminar</span>' ?>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark"><?= intval($inc['vins_afectados']) ?> VIN(s)</span></td>
                                                    <td><?= $incBadge ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No hay incidencias operativas registradas para este envío.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <input type="hidden" id="id_envio" value="<?= $data['id_envio'] ?>">

            <div class="row g-4">
                <!-- POOL DE VINS DISPONIBLES (Izquierda) -->
                <div class="col-lg-4">
                    <div class="envios-surface-card p-3 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                            <div>
                                <h6 class="card-title mb-0 fw-bold text-dark fs-14">
                                    <i class="ri-car-line text-primary me-1 align-middle"></i> VINs Disponibles
                                </h6>
                                <small class="text-muted fs-11">Unidades listas en el origen del envío</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary rounded-pill fs-11" id="badge-total-pool-vins">Pool</span>
                        </div>
                        
                        <!-- Buscador en tiempo real estilo Search Pill -->
                        <div class="search-pill-modern d-flex align-items-center w-100 my-2">
                            <i class="ri-search-line text-muted me-2"></i>
                            <input type="text" id="buscar-vin-pool" class="w-100" placeholder="Buscar por VIN, N/S o Modelo...">
                        </div>

                        <div class="flex-grow-1 overflow-auto mt-2" style="max-height: 600px;">
                            <!-- Lista Sortable -->
                            <ul id="vins-disponibles" class="list-group list-group-flush sortable-list" style="min-height: 400px; border: 2px dashed #CBD5E1;">
                                <!-- Se llena dinámicamente desde JS -->
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- CAMIONES / MADRINAS (Derecha) -->
                <div class="col-lg-8">
                    <div class="envios-surface-card p-3 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                            <div>
                                <h6 class="card-title mb-0 fw-bold text-dark fs-14">
                                    <i class="ri-truck-line text-primary me-1 align-middle"></i> Asignación y Estiba a Vehículos
                                </h6>
                                <small class="text-muted fs-11">Arrastra aquí para cargar (el de arriba se baja al final del trayecto)</small>
                            </div>
                            <!-- Botón para agregar más madrinas/choferes a este envío -->
                            <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs fw-semibold" id="btn-agregar-vehiculo" onclick="agregarVehiculo();" style="background-color: var(--brand-primary); border-color: var(--brand-primary);">
                                <i class="ri-add-line me-1"></i> Agregar Vehículo
                            </button>
                        </div>
                        <div class="flex-grow-1" id="contenedor-vehiculos">
                            <!-- Se inyecta dinámicamente las madrinas/choferes asignados -->
                            <div class="text-center text-muted py-5" id="empty-vehiculos-msg">
                                <div class="avatar-lg mx-auto mb-3">
                                    <span class="avatar-title bg-light text-muted rounded-circle fs-36 border">
                                        <i class="ri-truck-line opacity-50"></i>
                                    </span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Sin vehículos asignados</h6>
                                <p class="text-muted fs-12 mb-0">Haga clic en <strong>"Agregar Vehículo"</strong> para seleccionar del catálogo del trasladista.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL AGREGAR VEHÍCULO / MADRINA DEL PROVEEDOR -->
<div class="modal fade" id="modalAgregarVehiculo" tabindex="-1" aria-labelledby="modalVehiculoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light border-bottom-0 py-3">
                <h5 class="modal-title fw-bold text-dark fs-16" id="modalVehiculoLabel">
                    <i class="ri-truck-line me-2 text-primary"></i> Seleccionar Vehículo / Conductor del Trasladista
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-soft-primary border-0 shadow-xs mb-4">
                    <i class="ri-information-line me-1 fs-15 align-middle text-primary"></i> 
                    Empresa Trasladista: <strong id="lbl-trasladista-nombre" class="text-dark">Cargando...</strong>
                </div>

                <ul class="nav nav-segmented mb-3" role="tablist" id="modal-vehiculo-nav-tabs">
                    <li class="nav-item" id="nav-tab-madrinas">
                        <a class="nav-link active" id="link-tab-madrinas" data-bs-toggle="tab" href="#tab-madrinas" role="tab">
                            <i class="ri-truck-line me-1"></i> Madrinas del Catálogo
                        </a>
                    </li>
                    <li class="nav-item" id="nav-tab-choferes">
                        <a class="nav-link" id="link-tab-choferes" data-bs-toggle="tab" href="#tab-choferes" role="tab">
                            <i class="ri-steering-2-line me-1"></i> Choferes (Rodando)
                        </a>
                    </li>
                    <li class="nav-item" id="nav-tab-plataformas" style="display: none;">
                        <a class="nav-link" id="link-tab-plataformas" data-bs-toggle="tab" href="#tab-plataformas" role="tab">
                            <i class="ri-truck-line me-1"></i> Plataformas del Catálogo
                        </a>
                    </li>
                </ul>

                <div class="tab-content text-muted">
                    <!-- Pestaña Madrinas -->
                    <div class="tab-pane active" id="tab-madrinas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tblModalMadrinas">
                                <thead class="table-light fs-11 text-uppercase text-muted">
                                    <tr>
                                        <th>Económico</th>
                                        <th>Placas</th>
                                        <th>Capacidad</th>
                                        <th>Chofer Asignado</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalMadrinas"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Choferes -->
                    <div class="tab-pane" id="tab-choferes" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tblModalChoferes">
                                <thead class="table-light fs-11 text-uppercase text-muted">
                                    <tr>
                                        <th>Nombre del Conductor</th>
                                        <th>N° Licencia</th>
                                        <th>Tipo Licencia</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalChoferes"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Plataformas -->
                    <div class="tab-pane" id="tab-plataformas" role="tabpanel">
                        <!-- Botón y formulario para Plataformas Subcontratadas (Live Transfer) -->
                        <div class="p-3 mb-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fs-13 fw-bold text-dark mb-0"><i class="ri-truck-line text-info me-1"></i>Plataforma Subcontratada o Temporal</h6>
                                    <small class="text-muted">Para empresas como Live Transfer que subcontratan equipos y operadores por evento sin alta previa en catálogo.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-info text-white" onclick="toggleFormPlataformaTemporal()">
                                    <i class="ri-add-line me-1"></i>Capturar Temporal
                                </button>
                            </div>
                            
                            <div id="form-plataforma-temporal" class="mt-3 pt-3 border-top" style="display: none;">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label fs-11 text-muted mb-1">Económico / Ref *</label>
                                        <input type="text" class="form-control form-control-sm" id="tmp_plat_economico" placeholder="Ej. Subcontratada 1" value="Subcontratada">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fs-11 text-muted mb-1">Placas</label>
                                        <input type="text" class="form-control form-control-sm" id="tmp_plat_placas" placeholder="Ej. S/P o 123-AB-4">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fs-11 text-muted mb-1">Operador / Chofer</label>
                                        <input type="text" class="form-control form-control-sm" id="tmp_plat_chofer" placeholder="Ej. Operador por confirmar">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-11 text-muted mb-1">Capacidad *</label>
                                        <input type="number" class="form-control form-control-sm" id="tmp_plat_capacidad" min="1" max="10" value="1">
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <button type="button" class="btn btn-sm btn-light me-1" onclick="toggleFormPlataformaTemporal()">Cancelar</button>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="agregarPlataformaTemporal()"><i class="ri-check-line me-1"></i>Usar esta Plataforma</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tblModalPlataformas">
                                <thead class="table-light fs-11 text-uppercase text-muted">
                                    <tr>
                                        <th>Económico</th>
                                        <th>Placas</th>
                                        <th>Capacidad</th>
                                        <th>Chofer Asignado</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalPlataformas"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
    var ENVIO_READONLY = <?= ($estadoEnvio >= 2 && $estadoEnvio != 8) ? 'true' : 'false' ?>;
</script>
<?php footerAdmin($data); ?>
