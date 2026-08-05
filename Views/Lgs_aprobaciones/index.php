<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-aprobaciones">
                <!-- BREADCRUMBS -->
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

                <!-- ENCABEZADO -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-checkbox-circle-line me-2"></i> Panel de Aprobaciones
                        </h4>
                        <p class="text-muted fs-14 mb-0">Gestión ejecutiva de planeaciones de traslado. Revise los costos y autorice las rutas.</p>
                    </div>
                </div>

                <!-- DATATABLE APROBACIONES -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body px-4 py-4">
                                <table id="tableAprobaciones" class="table table-hover table-striped align-middle table-nowrap mb-0 w-100">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Folio Plan</th>
                                            <th>Solicitante</th>
                                            <th>Total Rutas</th>
                                            <th>Costo Total</th>
                                            <th>Fecha Solicitud</th>
                                            <th>Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- DataTables -->
                                    </tbody>
                                </table>
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
                <h5 class="modal-title fw-bold text-primary"><i class="ri-search-eye-line me-1"></i> Detalle de Planeación <span id="lblFolioModal"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <!-- Resumen Financiero -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-info">
                            <small class="text-muted d-block mb-1">Costo Total Solicitado</small>
                            <h4 class="mb-0 text-dark fw-bold" id="lblCostoModal">$0.00</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-warning">
                            <small class="text-muted d-block mb-1">Total Km a Recorrer</small>
                            <h4 class="mb-0 text-dark fw-bold" id="lblKmModal">0.00</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-secondary h-100">
                            <small class="text-muted d-block mb-1">Justificación del Operador</small>
                            <p class="mb-0 text-dark fs-13" id="lblObsOperador">-</p>
                        </div>
                    </div>
                </div>

                <!-- Detalle de las Rutas (Envíos) -->
                <h6 class="fw-bold text-dark"><i class="ri-truck-line me-1"></i> Rutas incluidas en este presupuesto:</h6>
                <div class="table-responsive bg-white rounded border p-2 mb-4" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle" id="tableDetalleRutas">
                        <thead class="table-light">
                            <tr>
                                <th>Folio Envío</th>
                                <th>Tipo</th>
                                <th>Origen</th>
                                <th>Trasladista</th>
                                <th>VINs</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDetalleRutas">
                            <!-- Llenado por AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Formulario de Decisión -->
                <form id="formAprobacion" name="formAprobacion">
                    <input type="hidden" id="id_planeacion" name="id_planeacion" value="">
                    <input type="hidden" id="decision" name="decision" value="">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-medium text-secondary">Comentarios o Motivo de Rechazo (Opcional si se aprueba)</label>
                            <textarea class="form-control" id="obs_aprobador" name="obs_aprobador" rows="2" placeholder="Escriba aquí sus observaciones..."></textarea>
                        </div>
                    </div>
                </form>

            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
                <div>
                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm me-2" onclick="enviarDecision('rechazar');">
                        <i class="ri-close-circle-line me-1"></i> Rechazar Plan
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="enviarDecision('aprobar');">
                        <i class="ri-check-double-line me-1"></i> Aprobar Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
