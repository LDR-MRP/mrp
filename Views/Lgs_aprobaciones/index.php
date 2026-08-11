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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModalEvaluar"><i class="ri-shield-user-line me-1"></i> Evaluar Planeación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEvaluarPlan">
                    <input type="hidden" id="eval_id_planeacion" name="id_planeacion" value="">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Folio de la Planeación</label>
                            <input type="text" class="form-control bg-light border-0 fw-bold" id="eval_folio" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Costo Total Estimado</label>
                            <input type="text" class="form-control bg-light border-0 fw-bold text-success fs-15" id="eval_costo" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Descripción / Concepto</label>
                            <input type="text" class="form-control bg-light border-0" id="eval_descripcion" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Comentarios u Observaciones de la Evaluación</label>
                            <textarea class="form-control" id="eval_comentarios" name="comentarios" rows="3" placeholder="Ingrese comentarios justificativos de la aprobación o rechazo..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-danger px-4 shadow-sm" onclick="resolverPlaneacion(3);">
                    <i class="ri-close-circle-line me-1"></i> Rechazar
                </button>
                <div>
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success px-4 shadow-sm" onclick="resolverPlaneacion(5);">
                        <i class="ri-checkbox-circle-line me-1"></i> Autorizar Planeación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
