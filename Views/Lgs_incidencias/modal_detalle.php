<!-- Modal Detalle Completo de Incidencia -->
<div class="modal fade" id="modalDetalleIncidencia" tabindex="-1" aria-labelledby="modalDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0" id="modalDetalleLabel">
                        <span id="detFolio">IN-000000</span>
                    </h5>
                    <span id="detBadgeEstado" class="badge">Estado</span>
                    <span id="detBadgePostEntrega" class="badge bg-danger-subtle text-danger d-none">Post-Entrega</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnCrearGastoDesdeDetalle" class="btn btn-sm btn-success rounded-pill px-3">
                        <i class="ri-money-dollar-circle-line me-1"></i> Asignar Gasto Adicional
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                
                <!-- TABS DE NAVEGACIÓN -->
                <ul class="nav nav-tabs nav-tabs-custom nav-success p-3 pb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-info-general" role="tab">
                            <i class="ri-file-text-line me-1"></i> Información General
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-vins-afectados" role="tab">
                            <i class="ri-car-line me-1"></i> VINs Afectados (<span id="detCountVins">0</span>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-evidencias" role="tab">
                            <i class="ri-image-line me-1"></i> Evidencias (<span id="detCountEvidencias">0</span>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-gastos-ligados" role="tab">
                            <i class="ri-money-dollar-box-line me-1"></i> Gastos Ligados (<span id="detCountGastos">0</span>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-bitacora" role="tab">
                            <i class="ri-history-line me-1"></i> Bitácora
                        </a>
                    </li>
                </ul>

                <div class="tab-content p-4">
                    
                    <!-- TAB 1: INFORMACIÓN GENERAL -->
                    <div class="tab-pane active" id="tab-info-general" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Envío y Traslado</h6>
                                    <p class="mb-1"><strong>Envío:</strong> <span id="detEnvioFolio">-</span></p>
                                    <p class="mb-1"><strong>Trasladista:</strong> <span id="detTrasladista">-</span></p>
                                    <p class="mb-0"><strong>Fecha Incidente:</strong> <span id="detFechaIncidente">-</span></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Clasificación Operativa</h6>
                                    <p class="mb-1"><strong>Tipo:</strong> <span id="detTipo">-</span></p>
                                    <p class="mb-1"><strong>Origen Reporte:</strong> <span id="detOrigenReporte">-</span></p>
                                    <p class="mb-0"><strong>Severidad:</strong> <span id="detSeveridad">-</span></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Dictamen de Absorción</h6>
                                    <div id="detContenedorDictamen">
                                        <p class="text-muted fs-12 mb-2">Aún no cuenta con dictamen de absorción.</p>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnDictaminarDesdeTab" onclick="abrirModalDictamenActual();">
                                            <i class="ri-scales-3-line me-1"></i> Emitir Dictamen
                                        </button>
                                    </div>
                                    <div id="detDictamenInfo" class="d-none">
                                        <p class="mb-1"><strong>Absorbe:</strong> <span id="detAbsorbeNombre" class="badge bg-primary">-</span></p>
                                        <p class="mb-1 fs-12"><strong>Dictaminado por:</strong> <span id="detDictaminadoPor">-</span></p>
                                        <p class="mb-0 fs-12 text-muted fst-italic" id="detDictamenNotas">-</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Ubicación y Tramo</h6>
                                    <p class="mb-0 fs-13" id="detUbicacion">-</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Descripción del Percance</h6>
                                    <p class="mb-0 fs-13" id="detDescripcion" style="white-space: pre-line;">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: VINS AFECTADOS -->
                    <div class="tab-pane" id="tab-vins-afectados" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>VIN</th>
                                        <th>Posición / Unidad</th>
                                        <th>Estado al Momento</th>
                                        <th>Costo Unitario Planeado</th>
                                    </tr>
                                </thead>
                                <tbody id="detTbodyVins"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: EVIDENCIAS -->
                    <div class="tab-pane" id="tab-evidencias" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold text-body mb-0">Archivos y Evidencias Adjuntas</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalSubirEvidencia();">
                                <i class="ri-upload-cloud-line me-1"></i> Subir Nueva Evidencia
                            </button>
                        </div>
                        <div class="row g-3" id="detContenedorEvidencias">
                            <!-- Render dinámico de evidencias -->
                        </div>
                    </div>

                    <!-- TAB 4: GASTOS LIGADOS -->
                    <div class="tab-pane" id="tab-gastos-ligados" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold text-body mb-0">Gastos Adicionales Generados</h6>
                            <button type="button" class="btn btn-sm btn-success" onclick="crearGastoParaEstaIncidencia();">
                                <i class="ri-add-line me-1"></i> Generar Gasto
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio Gasto</th>
                                        <th>Concepto</th>
                                        <th>Tipo</th>
                                        <th>Naturaleza</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="detTbodyGastos"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 5: BITÁCORA -->
                    <div class="tab-pane" id="tab-bitacora" role="tabpanel">
                        <ul class="list-group list-group-flush fs-13" id="detListaLogs"></ul>
                    </div>

                </div>

            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
