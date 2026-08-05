<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-envios">
                <!-- BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Envíos</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ENCABEZADO Y BOTÓN NUEVO -->
                <div class="row mb-4">
                    <div class="col-12 col-md-8">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-truck-line me-2"></i> Bandeja de Envíos
                        </h4>
                        <p class="text-muted fs-14 mb-0">Gestión de traslados físicos y asignación de unidades (Madrinas y Choferes).</p>
                    </div>
                    <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                        <!-- TODO: Validar permiso de crear envíos -->
                        <button type="button" class="btn btn-primary shadow-sm fw-semibold rounded-pill px-4" onclick="openModal();">
                            <i class="ri-add-line me-1"></i> Nuevo Envío
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
                                            <i class="ri-route-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Envíos</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardTotalEnvios">0</h4>
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
                                            <i class="ri-draft-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">En Borrador</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardEnviosCreados">0</h4>
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
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">En Tránsito</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardEnviosTransito">0</h4>
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
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-checkbox-circle-line"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Entregados</p>
                                        <h4 class="mb-0 text-dark fw-bold" id="cardEnviosEntregados">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DATATABLE -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body px-4 py-4">
                                <table id="tableEnvios" class="table table-hover table-striped align-middle table-nowrap mb-0 w-100">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>ID</th>
                                            <th>Folio</th>
                                            <th>Tipo</th>
                                            <th>Motivo</th>
                                            <th>Trasladista</th>
                                            <th>Origen</th>
                                            <th>VINs</th>
                                            <th>Costo Est.</th>
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

<!-- MODAL NUEVO ENVÍO -->
<div class="modal fade" id="modalFormEnvio" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModal"><i class="ri-add-line me-1"></i> Nuevo Envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEnvio" name="formEnvio">
                    <input type="hidden" id="id_envio" name="id_envio" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-secondary">Tipo de Traslado <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_tipo_traslado" name="id_tipo_traslado" required>
                                <option value="">Seleccione...</option>
                                <option value="1">Madrina</option>
                                <option value="2">Chofer (Rodando)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-secondary">Motivo <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_motivo" name="id_motivo" required>
                                <!-- Se llenará vía AJAX con getMotivos() o se renderiza con PHP -->
                                <option value="">Seleccione...</option>
                                <option value="1">Entrega a Distribuidor</option>
                                <option value="2">Traslado a Carrocería</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium text-secondary">Trasladista <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                                <!-- AJAX getProveedoresTrasladistas() -->
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium text-secondary">Origen <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_origen" name="id_origen" required>
                                <!-- AJAX getOrigenes() -->
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-medium text-secondary">Fecha Tentativa Salida</label>
                            <input type="date" class="form-control" id="fecha_tentativa_envio" name="fecha_tentativa_envio">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium text-secondary">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
                <button type="button" id="btnActionForm" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="saveEnvio();">
                    <i class="ri-save-line me-1"></i> <span id="btnText">Guardar Envío</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
