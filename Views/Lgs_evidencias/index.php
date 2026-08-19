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
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
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
<div class="modal fade" id="modalSubirEvidencia" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="titleModalEvidencia"><i class="ri-camera-lens-line me-1"></i> Cargar Evidencia de Entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEvidencia" enctype="multipart/form-data">
                    <input type="hidden" id="evid_id_envio" name="id_envio" value="">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Folio Envío</label>
                            <input type="text" class="form-control bg-light border-0 fw-bold" id="evid_folio" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Tipo de Documento / Evidencia</label>
                            <select class="form-select" id="evid_tipo" name="tipo_evidencia" required>
                                <option value="Foto Recepción">Foto Recepción en Patio</option>
                                <option value="Firma Conformidad">Firma de Conformidad</option>
                                <option value="Reporte Daños">Reporte / Hallazgo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Seleccionar Archivo (Imagen / PDF)</label>
                            <input type="file" class="form-control" id="evid_archivo" name="archivo" accept="image/*,application/pdf" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted fs-11 text-uppercase">Notas o Comentarios</label>
                            <textarea class="form-control" id="evid_notas" name="notas" rows="2" placeholder="Detalles sobre la recepción..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold shadow-sm" onclick="cerrarEntregaFinal();">
                    <i class="ri-checkbox-circle-line me-1"></i> Cerrar Entrega Definitiva
                </button>
                <div>
                    <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-soft-primary rounded-pill px-4 fw-semibold shadow-xs" onclick="subirArchivoEvidencia();">
                        <i class="ri-upload-2-line me-1"></i> Subir Archivo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
