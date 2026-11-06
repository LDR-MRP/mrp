<?php headerAdmin($data); ?>

<script>
    const ES_APROBADOR_GASTOS = <?= !empty($data['es_aprobador']) ? 'true' : 'false' ?>;
    const CURRENT_USER_ID = <?= intval($data['id_user'] ?? 0) ?>;
    const CURRENT_USER_ROLE = <?= intval($data['id_rol'] ?? 0) ?>;
</script>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-aprobaciones">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Aprobaciones</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y BADGE DE IDENTIDAD -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-checkbox-circle-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Panel de Aprobaciones</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión ejecutiva de planeaciones de traslado y gastos directos o extraordinarios. Revise los costos y autorice las operaciones.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. NAVEGACIÓN PRINCIPAL: PESTAÑAS DE APROBACIÓN -->
                <ul class="nav nav-pills nav-customs card-header-pills mb-4 gap-2" id="pillsAprobaciones" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active px-4 py-2 fs-14 fw-bold rounded-pill shadow-sm" id="tab-planeaciones-btn" data-bs-toggle="pill" data-bs-target="#tab-planeaciones" type="button" role="tab">
                            <i class="ri-route-line me-1"></i> Planeaciones de Traslado
                            <span class="badge bg-soft-warning text-warning ms-1" id="badgePlaneacionesPendientes">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-4 py-2 fs-14 fw-bold rounded-pill shadow-sm" id="tab-gastos-btn" data-bs-toggle="pill" data-bs-target="#tab-gastos" type="button" role="tab">
                            <i class="ri-money-dollar-circle-line me-1"></i> Aprobación de Gastos Directos y Adicionales
                            <span class="badge bg-danger ms-1 d-none" id="badgeGastosPendientesAprob">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="pillsAprobacionesContent">
                    <!-- PESTAÑA 1: PLANEACIONES DE TRASLADO -->
                    <div class="tab-pane fade show active" id="tab-planeaciones" role="tabpanel">
                        <!-- BLOQUE DE KPIS DE PLANEACIONES -->
                        <div class="row mb-4 g-3">
                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarAprobPorCard('Pendiente Aprobación', 'chip-aprob-pendiente');" title="Filtrar pendientes por revisar">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Por Aprobar</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardAprobPendientes">0</span></h4>
                                                <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Requieren firma</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                                    <i class="ri-alarm-warning-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Monto Solicitado</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2" id="cardMontoPendiente">$0.00</h4>
                                                <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Por autorizar</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                                    <i class="ri-hand-coin-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarAprobPorCard('Aprobada', 'chip-aprob-aprobada');" title="Filtrar autorizadas">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Autorizadas</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardAprobAutorizadas">0</span></h4>
                                                <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Aprobadas</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                                    <i class="ri-thumb-up-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Monto Autorizado</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2" id="cardMontoAutorizado">$0.00</h4>
                                                <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Aprobado acumulado</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                                    <i class="ri-wallet-3-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DATATABLE CARD PLANEACIONES CON CHIPS -->
                        <div class="card border-0 shadow-xl rounded-3 overflow-hidden">
                            <div class="bg-primary" style="height: 4px;"></div>
                            <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                    <!-- Buscador -->
                                    <div class="d-flex align-items-center w-100" style="max-width: 360px;">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                                            <input type="text" id="buscarAprobInput" class="form-control bg-light border-start-0" placeholder="Buscar folio, descripción..." oninput="filtrarTablaAprobaciones(this.value);">
                                            <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="document.getElementById('buscarAprobInput').value=''; filtrarTablaAprobaciones('');" title="Limpiar"><i class="ri-close-line"></i></button>
                                        </div>
                                    </div>

                                    <!-- Chips de Filtro Rápido -->
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="fs-11 text-muted text-uppercase fw-bold me-1 d-none d-xl-inline"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fs-12 active chip-aprob-btn" id="chip-aprob-todas" onclick="filtrarAprobPorChip('', this);">
                                            📌 Todas
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 fs-12 chip-aprob-btn" id="chip-aprob-pendiente" onclick="filtrarAprobPorChip('Pendiente Aprobación', this);">
                                            ⏳ Por Aprobar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fs-12 chip-aprob-btn" id="chip-aprob-aprobada" onclick="filtrarAprobPorChip('Aprobada', this);">
                                            ✅ Autorizadas
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fs-12 chip-aprob-btn" id="chip-aprob-rechazada" onclick="filtrarAprobPorChip('Rechazada', this);">
                                            ❌ Rechazadas
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="tableAprobaciones" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Plan</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Descripción</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total Envíos</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Km Totales</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Costo Est.</th>
                                                <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                                <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer border-top-0 py-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted fw-medium">
                                        <i class="ri-shield-check-line text-success me-1"></i> Aprobaciones de planeación sincronizadas en tiempo real
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA 2: GASTOS DIRECTOS Y ADICIONALES -->
                    <div class="tab-pane fade" id="tab-gastos" role="tabpanel">
                        <!-- BLOQUE DE KPIS DE GASTOS DIRECTOS Y ADICIONALES -->
                        <div class="row mb-4 g-3">
                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarGastosPorChip('pendientes', document.getElementById('chip-gasto-pendientes'));" title="Filtrar pendientes por autorizar">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes de Aprobación</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2"><span id="kpiPendientes">0</span></h4>
                                                <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Requieren dictamen</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                                    <i class="ri-time-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarGastosPorChip('aprobados', document.getElementById('chip-gasto-aprobados'));" title="Filtrar autorizados por documentar">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Aprobados por Documentar</p>
                                                <h4 class="fs-22 fw-bold text-body mb-2"><span id="kpiPorDocumentar">0</span></h4>
                                                <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Listos para facturar</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                                    <i class="ri-file-text-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Cargos Adicionales (Mes)</p>
                                                <h4 class="fs-22 fw-bold text-danger mb-2" id="kpiTotalCargos">$0.00</h4>
                                                <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Sobrecostos</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                                    <i class="ri-arrow-up-circle-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Deducciones por Recuperar</p>
                                                <h4 class="fs-22 fw-bold text-success mb-2" id="kpiTotalDeducciones">$0.00</h4>
                                                <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Recuperables</span>
                                            </div>
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                                    <i class="ri-arrow-down-circle-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DATATABLE CARD GASTOS DIRECTOS / ADICIONALES -->
                        <div class="card border-0 shadow-xl rounded-3 overflow-hidden">
                            <div class="bg-warning" style="height: 4px;"></div>
                            <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                    <!-- Chips de Filtro Rápido para Gastos -->
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <span class="fs-11 text-muted text-uppercase fw-bold me-1"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fs-12 chip-gasto-btn" id="chip-gasto-todos" onclick="filtrarGastosPorChip('', this);">
                                            📌 Todos
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 fs-12 active chip-gasto-btn" id="chip-gasto-pendientes" onclick="filtrarGastosPorChip('pendientes', this);">
                                            ⏳ Por Aprobar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 fs-12 chip-gasto-btn" id="chip-gasto-directos" onclick="filtrarGastosPorChip('directos', this);">
                                            ⚡ Gastos Directos / Independientes
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fs-12 chip-gasto-btn" id="chip-gasto-incidencias" onclick="filtrarGastosPorChip('incidencias', this);">
                                            ⚠️ Ligados a Incidencia
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fs-12 chip-gasto-btn" id="chip-gasto-aprobados" onclick="filtrarGastosPorChip('aprobados', this);">
                                            ✅ Aprobados
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fs-12 chip-gasto-btn" id="chip-gasto-rechazados" onclick="filtrarGastosPorChip('rechazados', this);">
                                            ❌ Rechazados
                                        </button>
                                    </div>

                                    <!-- Acciones Rápidas -->
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="limpiarFiltros();" title="Recargar y limpiar">
                                            <i class="ri-refresh-line me-1"></i> Recargar
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="openModalGasto();">
                                            <i class="ri-add-line align-middle me-1"></i> Nuevo Gasto
                                        </button>
                                    </div>
                                </div>

                                <!-- Inputs ocultos para soportar AJAX de tableGastos -->
                                <input type="hidden" id="filtro_estado" value="pendientes">
                                <input type="hidden" id="filtro_tipo" value="">
                                <input type="hidden" id="filtro_proveedor" value="">
                                <input type="hidden" id="filtro_independiente" value="">
                            </div>
                            <div class="card-body p-3">
                                <div class="table-responsive">
                                    <table id="tableGastos" class="table table-hover align-middle table-nowrap mb-0 w-100">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Folio</th>
                                                <th>Origen</th>
                                                <th>Envío</th>
                                                <th>Tipo de Gasto</th>
                                                <th>Naturaleza</th>
                                                <th>Proveedor</th>
                                                <th>Monto Final</th>
                                                <th>Unidades</th>
                                                <th>Estado</th>
                                                <th>Documento</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer border-top-0 py-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted fw-medium">
                                        <i class="ri-shield-check-line text-success me-1"></i> Aprobación con segregación de funciones activa (1 solo nivel: Solicitante ➔ Aprobador)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- MODAL EVALUAR PLANEACIÓN -->
<div class="modal fade" id="modalEvaluarPlan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModalEvaluar">
                    <i class="ri-shield-user-line me-1"></i> Evaluar y Autorizar Planeación: <span id="lblFolioModal" class="badge bg-primary fs-14"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Resumen de Costos y KM -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-soft-success border-0 rounded-3 p-3 text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold"><i class="ri-money-dollar-circle-line me-1"></i> Costo Total Solicitado</span>
                            <h3 class="fw-bold text-success mb-0 mt-1" id="lblCostoModal">$0.00</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-soft-primary border-0 rounded-3 p-3 text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold"><i class="ri-route-line me-1"></i> Distancia Acumulada</span>
                            <h3 class="fw-bold text-primary mb-0 mt-1"><span id="lblKmModal">0</span> km</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-soft-secondary border-0 rounded-3 p-3">
                            <span class="text-muted fs-11 text-uppercase fw-bold"><i class="ri-chat-1-line me-1"></i> Nota del Planeador</span>
                            <p class="fs-12 text-dark mb-0 mt-1" id="lblObsOperador">Sin observaciones.</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Envíos/Rutas Agrupadas con Detalle de VINs -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-truck-line me-1 text-primary"></i> Desglose de Envíos, Madrinas y VINs Asignados</h6>
                <div id="vdp_contenedor_envios" class="mb-4">
                    <!-- Inyectado dinámicamente -->
                </div>

                <!-- Formulario de Dictamen -->
                <form id="formAprobacion">
                    <input type="hidden" id="id_planeacion" name="id_planeacion" value="">
                    <input type="hidden" id="decision" name="decision" value="">
                    
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted fs-11 text-uppercase">
                            <i class="ri-edit-line me-1"></i> Observaciones del Dictamen / Motivo (Requerido en caso de rechazo)
                        </label>
                        <textarea class="form-control" id="obs_aprobador" name="obs_aprobador" rows="3" placeholder="Ingrese comentarios para el operador de logística..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                <button type="button" id="btnRechazarModal" class="btn btn-sm btn-soft-danger rounded-pill px-4 fw-semibold shadow-sm" onclick="enviarDecision('rechazar');">
                    <i class="ri-close-circle-line me-1"></i> Rechazar Plan
                </button>
                <div>
                    <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold me-2" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" id="btnAprobarModal" class="btn btn-sm btn-success px-4 rounded-pill fw-semibold shadow-sm" onclick="enviarDecision('aprobar');">
                        <i class="ri-checkbox-circle-line me-1"></i> Autorizar para seguir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- INCLUSIÓN DE MODALES DE GASTOS ADICIONALES -->
<?php require_once(dirname(__DIR__) . "/Lgs_gastosadicionales/modal_detalle.php"); ?>
<?php require_once(dirname(__DIR__) . "/Lgs_gastosadicionales/modal_documentar.php"); ?>
<?php require_once(dirname(__DIR__) . "/Lgs_gastosadicionales/modal_gasto.php"); ?>
<?php require_once(dirname(__DIR__) . "/Lgs_gastosadicionales/modal_buscador_incidencias.php"); ?>
<?php require_once(dirname(__DIR__) . "/Lgs_incidencias/modal_buscador_envios.php"); ?>

<?php footerAdmin($data); ?>
