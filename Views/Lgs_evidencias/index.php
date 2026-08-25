<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-evidencias">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Evidencias y Cierre</li>
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
                                    <i class="ri-camera-lens-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Evidencias y Cierre de Entrega</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Carga multimedia de recepción/llegada y confirmación de entrega final en destino.
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Rutas en Tránsito</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidTransito">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">En curso</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Archivos Adjuntos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidTotalArchivos">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Evidencias cargadas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-camera-lens-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Entregas Cerradas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidEntregadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Finalizadas</span>
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

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Cobertura Evidencias</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2" id="cardEvidCobertura">0%</h4>
                                        <span class="badge bg-soft-secondary text-secondary fw-medium mb-0 px-2 py-1">Completes</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fs-3">
                                            <i class="ri-shield-check-line"></i>
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
                            <table id="tableEvidencias" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total VINs</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Evidencias Adjuntas</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Evidencias respaldadas y sincronizadas
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- MODAL EVIDENCIAS Y CIERRE -->
<div class="modal fade" id="modalEvidencias" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-0" id="titleModalEvidencia">
                        <i class="ri-camera-lens-line me-1"></i> Evidencias del Envío <span id="lblFolioEvidencia" class="badge bg-primary text-white fs-13 ms-2"></span>
                    </h5>
                    <small class="text-muted">Registro unificado de evidencias (Patio, Chofer Móvil y Recepción en Destino)</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- FORMULARIO DE CARGA DE EVIDENCIA -->
                <div class="card border rounded-3 p-3 mb-4 bg-light">
                    <h6 class="fw-bold fs-13 text-dark mb-3"><i class="ri-upload-cloud-line me-1 text-primary"></i> Subir Nueva Evidencia</h6>
                    <form id="formEvidencia" enctype="multipart/form-data">
                        <input type="hidden" id="id_envio_evidencia" name="id_envio" value="">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Unidad / VIN (Opcional)</label>
                                <select class="form-select fs-13" id="select_evid_unidad" name="id_unidad">
                                    <option value="">General del Envío (Todos)</option>
                                    <!-- Inyectado dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Tipo de Evidencia</label>
                                <select class="form-select fs-13" id="evid_tipo" name="tipo_evidencia" required>
                                    <option value="1">1. Salida / Inspección en Patio</option>
                                    <option value="2">2. Llegada / Entrega en Destino</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Archivo (Imagen / PDF)</label>
                                <input type="file" class="form-control fs-13" id="evid_archivo" name="archivo" accept="image/*,application/pdf" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Notas u Observaciones</label>
                                <div class="input-group">
                                    <input type="text" class="form-control fs-13" id="observaciones_ev" name="observaciones" placeholder="Descripción de la evidencia...">
                                    <button type="button" class="btn btn-primary px-4 fw-semibold fs-13" onclick="guardarEvidencia();">
                                        <i class="ri-upload-2-line me-1"></i> Subir Evidencia
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABLA DE EVIDENCIAS EXISTENTES -->
                <h6 class="fw-bold fs-13 text-dark mb-2"><i class="ri-gallery-line me-1 text-primary"></i> Evidencias Registradas</h6>
                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-hover align-middle mb-0 fs-13">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 140px;">Momento</th>
                                <th style="width: 180px;">Unidad / VIN</th>
                                <th>Archivo / Vista Previa</th>
                                <th>Observaciones</th>
                                <th style="width: 150px;">Fecha</th>
                                <th class="text-center" style="width: 80px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="bodyListaEvidencias">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- CIERRE DE ENTREGA FINAL (SI ESTÁ EN TRÁNSITO) -->
                <div id="cardCierreDestino" class="card border border-success bg-soft-success p-3 rounded-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-success mb-1"><i class="ri-checkbox-circle-line me-1"></i> Cierre Definitivo de Entrega</h6>
                            <small class="text-muted">Marcar el envío como completado y cerrar el viaje.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="datetime-local" class="form-control form-control-sm" id="fecha_llegada_real">
                            <button type="button" class="btn btn-sm btn-success px-4 rounded-pill fw-bold text-white shadow-sm" onclick="confirmarCierreFinal();">
                                <i class="ri-check-double-line me-1"></i> Finalizar Envío
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-2">
                <button type="button" class="btn btn-light border px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
