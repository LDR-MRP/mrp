<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-ejecucion">
                <!-- BREADCRUMBS -->
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

                <!-- ENCABEZADO -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-ship-line me-2"></i> Mesa de Despacho y Entrega a Trasladistas
                        </h4>
                        <p class="text-muted fs-14 mb-0">Gestiona la salida física de unidades aprobadas, evidencias fotográficas y entrega en planta.</p>
                    </div>
                </div>

                <!-- DATATABLE DESPACHOS -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-body px-4 py-4">
                                <table id="tableEjecucion" class="table table-hover table-striped align-middle table-nowrap mb-0 w-100">
                                    <thead class="table-light text-muted">
                                        <tr>
                                            <th>Folio Envío</th>
                                            <th>Tipo Traslado</th>
                                            <th>Origen</th>
                                            <th>Trasladista</th>
                                            <th>Avance Entregas</th>
                                            <th>Fecha Salida Real</th>
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

<!-- MODAL GESTIÓN DE DESPACHO Y PLANILLA DE ACOMODO -->
<div class="modal fade" id="modalDespachoPlanilla" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary"><i class="ri-clipboard-line me-1"></i> Despacho y Planilla de Salida <span id="lblFolioDespacho"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                <!-- REGISTRO DE SALIDA Y EVIDENCIAS -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-dark mb-3"><i class="ri-time-line me-1"></i> 1. Registro de Despacho y Evidencias</h6>
                        <form id="formDespacho">
                            <input type="hidden" id="id_envio_despacho" name="id_envio">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-medium text-secondary">Fecha/Hora Real de Salida</label>
                                    <input type="datetime-local" class="form-control" id="fecha_salida_real" name="fecha_salida_real" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-medium text-secondary">URL o Ref. Evidencias de Salida (Fotos/Video)</label>
                                    <input type="text" class="form-control" id="evidencias_json" name="evidencias_json" placeholder="Ej: https://cloud.com/evidencia-salida-01.jpg">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary w-100" onclick="guardarDespacho();">
                                        <i class="ri-save-line me-1"></i> Guardar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABLA DE ACOMODO Y CONFIRMACIÓN EN PLANTA -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="ri-numbers-line me-1"></i> 2. Orden de Acomodo de VINs (Instrucción para Entregas)</h6>
                                <small class="text-muted">Las unidades deben entregarse al chofer en este orden exacto (el de la posición 1 sube al final para descargarse primero en destino).</small>
                            </div>
                        </div>

                        <div class="table-responsive bg-white rounded border p-2">
                            <table class="table table-hover table-sm align-middle" id="tableAcomodoPlanta">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 90px;" class="text-center">Orden</th>
                                        <th>VIN</th>
                                        <th>Modelo</th>
                                        <th>Color</th>
                                        <th>Confirmación de Salida</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyAcomodoPlanta">
                                    <!-- Carga por AJAX -->
                                </tbody>
                            </table>
                        </div>
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
