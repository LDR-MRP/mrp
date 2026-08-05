<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-planeaciones">
                <!-- BREADCRUMBS -->
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

                <!-- ENCABEZADO Y BOTÓN NUEVA PLANEACIÓN -->
                <div class="row mb-4">
                    <div class="col-12 col-md-8">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-route-line me-2"></i> Mis Planeaciones
                        </h4>
                        <p class="text-muted fs-14 mb-0">Agrupa múltiples envíos y envíalos a revisión de la gerencia.</p>
                    </div>
                    <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary shadow-sm fw-semibold rounded-pill px-4" onclick="openModalPlan();">
                            <i class="ri-add-line me-1"></i> Agrupar Envíos (Nuevo Plan)
                        </button>
                    </div>
                </div>

                <!-- CARDS DE MÉTRICAS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-file-list-3-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Planeaciones</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardTotalPlaneaciones">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-time-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Pendientes Revisión</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardPlanPendientes">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-check-double-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Planeaciones Aprobadas</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardPlanAprobadas">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-money-dollar-circle-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Monto Total Planeado</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardPlanPresupuesto">$0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DATATABLE PLANEACIONES -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body px-4 py-4">
                                <table id="tablePlaneaciones" class="table table-hover table-striped align-middle table-nowrap mb-0 w-100">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Folio Plan</th>
                                            <th>Descripción</th>
                                            <th>Total Rutas</th>
                                            <th>Km Acum.</th>
                                            <th>Costo Total</th>
                                            <th>Fecha</th>
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

<!-- MODAL NUEVA PLANEACIÓN -->
<div class="modal fade" id="modalFormPlaneacion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModal"><i class="ri-file-list-3-line me-1"></i> Agrupar Envíos Disponibles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formPlaneacion" name="formPlaneacion">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-medium text-secondary">Descripción de la Planeación (Opcional)</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Ej: Planeación Norte y Bajío - Agosto">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-medium text-secondary">Observaciones del Operador</label>
                            <textarea class="form-control" id="obs_operador" name="obs_operador" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- TABLA DE ENVÍOS DISPONIBLES (ESTADO 1) -->
                    <h6 class="fw-bold text-dark"><i class="ri-truck-line me-1"></i> Selecciona las rutas a incluir en el plan:</h6>
                    <div class="table-responsive bg-white rounded border p-2" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle" id="tableEnviosDisponibles">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input shadow-none" type="checkbox" id="checkAllEnvios">
                                        </div>
                                    </th>
                                    <th>Folio Envío</th>
                                    <th>Origen</th>
                                    <th>Trasladista</th>
                                    <th>VINs</th>
                                    <th>Km</th>
                                    <th>Costo Est.</th>
                                </tr>
                            </thead>
                            <tbody id="bodyEnviosDisponibles">
                                <!-- Se llena por AJAX -->
                            </tbody>
                            <tfoot class="table-light fw-bold text-primary">
                                <tr>
                                    <td colspan="4" class="text-end">TOTALES SELECCIONADOS:</td>
                                    <td id="lblTotalVins">0</td>
                                    <td id="lblTotalKm">0.00</td>
                                    <td id="lblTotalCosto">$0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <input type="hidden" name="envios_ids" id="envios_ids">
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cancelar
                </button>
                <button type="button" id="btnActionForm" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="savePlaneacion();">
                    <i class="ri-send-plane-line me-1"></i> Enviar a Aprobación
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
