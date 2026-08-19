<?php headerAdmin($data); ?>

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
                                    Gestión ejecutiva de planeaciones de traslado. Revise los costos y autorice las rutas.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
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

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
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

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
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
                        <div class="card card-animate border-0 shadow-sm rounded-3">
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

                <!-- 4. DATATABLE CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Aprobaciones sincronizadas en tiempo real
                            </small>
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

                <!-- Tabla de Envíos/Rutas Agrupadas -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-truck-line me-1 text-primary"></i> Envíos y Madrinas Incluidas en este Plan</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light fs-11 text-uppercase text-muted">
                            <tr>
                                <th>Folio Envío</th>
                                <th>Modalidad</th>
                                <th>Origen</th>
                                <th>Trasladista</th>
                                <th class="text-center">Total VINs</th>
                                <th>Costo Envío</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetalleRutas" class="fs-12">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
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

<?php footerAdmin($data); ?>
