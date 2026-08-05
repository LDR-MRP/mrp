<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-evidencias">
                <!-- BREADCRUMBS -->
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

                <!-- ENCABEZADO -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-camera-lens-line me-2"></i> Evidencias Multimedia y Cierre de Entrega
                        </h4>
                        <p class="text-muted fs-14 mb-0">Carga fotos y videos de recepción/llegada y confirma la entrega final en destino.</p>
                    </div>
                </div>

                <!-- DATATABLE EVIDENCIAS -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body px-4 py-4">
                                <table id="tableEvidencias" class="table table-hover table-striped align-middle table-nowrap mb-0 w-100">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Folio Envío</th>
                                            <th>Trasladista</th>
                                            <th>Origen</th>
                                            <th>Total VINs</th>
                                            <th>Total Evidencias</th>
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

<!-- MODAL GESTIÓN DE EVIDENCIAS Y CONFIRMACIÓN LLEGADA -->
<div class="modal fade" id="modalEvidencias" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary"><i class="ri-gallery-line me-1"></i> Evidencias de Envío <span id="lblFolioEvidencia"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <!-- AGREGAR EVIDENCIA -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-3"><i class="ri-upload-cloud-line me-1"></i> Cargar Nueva Evidencia</h6>
                        <form id="formEvidencia">
                            <input type="hidden" id="id_envio_evidencia" name="id_envio">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-medium text-secondary">Momento de Evidencia</label>
                                    <select class="form-select" id="tipo_evidencia" name="tipo_evidencia" required>
                                        <option value="1">1. Salida de Planta / Recepción</option>
                                        <option value="2">2. Llegada / Entrega en Destino</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-medium text-secondary">URL o Archivo Multimedia (Foto / Video)</label>
                                    <input type="text" class="form-control" id="ruta_archivo" name="ruta_archivo" placeholder="Ej: https://servidor.com/fotos/vin123-llegada.jpg" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium text-secondary">Observaciones</label>
                                    <input type="text" class="form-control" id="observaciones_ev" name="observaciones" placeholder="Ej: Rayón menor en fascia trasera">
                                </div>
                                <div class="col-md-12 text-end mt-3">
                                    <button type="button" class="btn btn-primary px-4" onclick="guardarEvidencia();">
                                        <i class="ri-add-circle-line me-1"></i> Adjuntar Evidencia
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- GALERÍA / LISTADO DE EVIDENCIAS -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-3"><i class="ri-image-line me-1"></i> Evidencias Registradas</h6>
                        <div class="table-responsive bg-white rounded border p-2">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Archivo / URL</th>
                                        <th>Observaciones</th>
                                        <th>Fecha Carga</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyListaEvidencias">
                                    <!-- Llenado por AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN CONFIRMAR ENTREGA FINAL -->
                <div class="card border-0 shadow-sm border-start border-4 border-success" id="cardCierreDestino">
                    <div class="card-body">
                        <h6 class="fw-bold text-success mb-2"><i class="ri-checkbox-circle-line me-1"></i> Confirmar Entrega Final en Destino</h6>
                        <p class="text-muted fs-13 mb-3">Marcar este envío como Entregado finalizará el monitoreo y registrará la hora real de llegada.</p>
                        <form id="formCierre">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Fecha y Hora Real de Llegada a Destino</label>
                                    <input type="datetime-local" class="form-control" id="fecha_llegada_real" name="fecha_llegada_real" required>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button type="button" class="btn btn-success px-4 rounded-pill shadow-sm" onclick="confirmarCierreFinal();">
                                        <i class="ri-check-double-line me-1"></i> Confirmar Entrega Final
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
