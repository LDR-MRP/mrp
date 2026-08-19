<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-planeaciones">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Planeaciones</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN PRINCIPAL -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-file-list-3-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Mis Planeaciones</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Agrupación centralizada de envíos para revisión, estimación de costos y autorización.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="openModalPlan();">
                            <i class="ri-add-line align-middle fs-16 me-1"></i> Agrupar Envíos (Nuevo Plan)
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Planeaciones</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardTotalPlaneaciones">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registradas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-file-list-3-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes Revisión</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardPlanPendientes">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En Proceso</span>
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

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Planeaciones Aprobadas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardPlanAprobadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Autorizadas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-check-double-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Monto Planeado</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2" id="cardPlanMontoTotal">$0.00</h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Estimado</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-money-dollar-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATATABLE CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablePlaneaciones" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Plan</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Título / Descripción</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total Envíos</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Costo Total Est.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Creación</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Planeaciones actualizadas en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>


            <!-- ── SECCIÓN 2: VISTA FORMULARIO SEPARADO ────────────────────── -->
            <section id="view-form-planeaciones" style="display: none;">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelFormPlan();">Planeaciones</a></li>
                                    <li class="breadcrumb-item active text-primary" id="breadcrumb-form-plan">Nueva Planeación</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER FORMULARIO -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-warning text-white rounded-circle fs-3 shadow-lg">
                                    <i class="ri-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05" id="form-plan-title">Agrupar Envíos en Nueva Planeación</h4>
                                <p class="text-muted mb-0 fs-13">Seleccione los envíos individuales para consolidar su envío a gerencia.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FORMULARIO ASIMÉTRICO (2 COLUMNAS) -->
                <form id="formPlan" name="formPlan" autocomplete="off">
                    <input type="hidden" id="id_planeacion" name="id_planeacion" value="">

                    <div class="row">
                        <!-- COLUMNA PRINCIPAL (70%) -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Datos de la Planeación</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Título de la Planeación <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-lg bg-light border-0 fw-bold" id="titulo_plan" name="titulo" required placeholder="Ej. Traslados Planta Tlajomulco - Semana 32">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Seleccionar Envíos Disponibles <span class="text-danger">*</span></label>
                                            <div id="containerEnviosDisponibles" class="p-3 bg-light rounded border border-light">
                                                <small class="text-muted">Cargando envíos en borrador...</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA LATERAL (30%) -->
                        <div class="col-lg-4">
                            <!-- Card Acciones -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btnActionFormPlan" class="btn btn-primary rounded-pill py-2 shadow-sm fw-semibold" onclick="savePlaneacion();">
                                            <i class="ri-send-plane-fill align-middle me-1"></i> <span id="btnTextPlan">Enviar a Revisión</span>
                                        </button>
                                        <button type="button" class="btn btn-light border rounded-pill py-2 fw-semibold" onclick="cancelFormPlan();">
                                            <i class="ri-arrow-go-back-line align-middle fs-16 me-1"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Métrica: Costo Total -->
                            <div class="card border-0 shadow-lg mb-4 text-white" style="border-radius: 14px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-white-subtle text-white rounded-circle fs-3" style="background: rgba(255,255,255,0.15) !important;">
                                                <i class="ri-money-dollar-circle-line"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="text-white-50 text-uppercase fw-bold fs-11 mb-1 ls-1">Costo Estimado Plan</p>
                                            <h3 class="text-white fw-bold mb-0" id="lblTotalCostoForm">$0.00</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Métrica: Kilómetros Totales -->
                            <div class="card border-0 shadow-lg mb-4 text-white" style="border-radius: 14px; background: linear-gradient(135deg, #C46623 0%, #E07A2C 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title bg-white-subtle text-white rounded-circle fs-3" style="background: rgba(255,255,255,0.15) !important;">
                                                <i class="ri-map-pin-distance-line"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <p class="text-white-50 text-uppercase fw-bold fs-11 mb-1 ls-1">Distancia Total Plan</p>
                                            <h3 class="text-white fw-bold mb-0" id="lblTotalKmForm">0 km</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<!-- MODAL VER DETALLE DE PLANEACIÓN (SOLO LECTURA / AUDITORÍA) -->
<div class="modal fade" id="modalViewDetallePlan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                            <i class="ri-file-list-3-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-body mb-0"><span id="vdp_folio">PL-000</span></h5>
                        <div class="mt-1" id="vdp_estado_badge">
                            <span class="badge bg-soft-secondary text-secondary fs-12">Estado</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- 1. KPIs del Expediente -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Costo Total</span>
                            <h4 class="fw-bold text-success mb-0" id="vdp_costo_total">$0.00</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Distancia Total</span>
                            <h4 class="fw-bold text-primary mb-0"><span id="vdp_km_total">0</span> km</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Total Envíos</span>
                            <h4 class="fw-bold text-dark mb-0" id="vdp_total_envios">0</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Total VINs</span>
                            <h4 class="fw-bold text-dark mb-0" id="vdp_total_vins">0</h4>
                        </div>
                    </div>
                </div>

                <!-- 2. Metadatos de Creación y Dictamen -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fs-12 text-muted fw-bold text-uppercase"><i class="ri-user-line me-1"></i>Creado por:</span>
                                <strong class="fs-12 text-dark" id="vdp_creador">Operador</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fs-12 text-muted fw-bold text-uppercase"><i class="ri-calendar-line me-1"></i>Fecha:</span>
                                <span class="fs-12 text-dark" id="vdp_fecha">-</span>
                            </div>
                            <hr class="my-2">
                            <span class="fs-11 text-muted fw-bold text-uppercase d-block mb-1">Observaciones de Operador:</span>
                            <p class="fs-12 text-dark mb-0 fst-italic" id="vdp_obs_operador">Sin observaciones.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="fs-11 text-muted fw-bold text-uppercase d-block mb-1"><i class="ri-shield-check-line me-1 text-primary"></i>Dictamen / Observaciones Aprobador:</span>
                            <p class="fs-12 text-dark mb-0 fst-italic" id="vdp_obs_aprobador">Pendiente de dictamen.</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Desglose de Envíos y Unidades -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-car-line me-1 text-primary"></i> Desglose de Envíos, Madrinas y VINs Asignados</h6>
                <div id="vdp_contenedor_envios" class="d-flex flex-column gap-3">
                    <!-- Inyectado dinámicamente -->
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
                <div id="vdp_modal_footer_actions">
                    <a href="<?= base_url(); ?>/Lgs_aprobaciones" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm">
                        <i class="ri-shield-check-line me-1"></i> Ir al Panel de Aprobaciones
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
