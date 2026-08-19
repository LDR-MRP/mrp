<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-ejecucion">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Mesa de Despacho</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-ship-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Mesa de Despacho</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión de salida física de unidades aprobadas, control de asignación y despacho en planta.
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes Salida</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespPendientes">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En patio</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Tránsito Activo</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespTransito">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">En trayecto</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">VINs Entregados</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardVinsEntregados">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Despachados</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-car-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Completados Hoy</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespCompletados">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Finalizados</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-checkbox-circle-line"></i>
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
                            <table id="tableEjecucion" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Chofer / Madrina</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total VINs</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Despacho sincronizado en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- MODAL DESPACHO / PLANILLA DE ACOMODO -->
<div class="modal fade" id="modalDespachoPlanilla" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModalDespacho">
                    <i class="ri-ship-line me-1"></i> Despacho y Entrega a Trasladista: <span id="lblFolioDespacho" class="badge bg-primary fs-14"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formDespacho">
                    <input type="hidden" id="id_envio_despacho" name="id_envio" value="">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Fecha y Hora Real de Salida de Patio <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control fw-bold text-primary" id="fecha_salida_real" name="fecha_salida_real" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Observaciones de Patio / Inspección</label>
                            <input type="text" class="form-control" id="desp_observaciones" name="observaciones" placeholder="Odómetro inicial, condiciones climatológicas, etc.">
                        </div>
                    </div>
                </form>

                <!-- Planilla de Verificación de VINs para Carga -->
                <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-checkbox-multiple-line me-1 text-primary"></i> Verificación y Carga de VINs en Madrina</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light fs-11 text-uppercase text-muted">
                            <tr>
                                <th class="text-center" style="width: 130px;">Acomodo</th>
                                <th>VIN / Chasis</th>
                                <th>Modelo</th>
                                <th>Color</th>
                                <th>Estatus en Patio</th>
                                <th class="text-center" style="width: 200px;">Acción Patio</th>
                            </tr>
                        </thead>
                        <tbody id="bodyAcomodoPlanta" class="fs-12">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" onclick="guardarDespacho();">
                    <i class="ri-truck-line me-1"></i> Confirmar Salida y Poner en Tránsito
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PROGRAMAR RECOLECCIÓN (ADMINISTRATIVO) -->
<div class="modal fade" id="modalProgramarRecoleccion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="ri-calendar-event-line me-1"></i> Programar Día de Recolección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formProgramarRecoleccion">
                    <input type="hidden" id="rec_id_envio" name="id_envio" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted fs-11 text-uppercase">Fecha y Hora Pactada de Recolección <span class="text-danger">*</span></label>
                        <input type="date" class="form-control fw-bold text-primary" id="fecha_recoleccion" name="fecha_recoleccion" required>
                        <small class="text-muted d-block mt-2">Al confirmar, el traslado pasará a estado <strong>Confirmado Recolección</strong> y las unidades se marcarán como listas para entregar en el patio de origen.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold me-2" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" onclick="guardarProgramacionRecoleccion();">Confirmar Programación</button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
