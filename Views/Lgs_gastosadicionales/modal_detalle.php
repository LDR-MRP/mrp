<!-- Modal Detalle Completo de Gasto Adicional -->
<div class="modal fade" id="modalDetalleGasto" tabindex="-1" aria-labelledby="modalDetalleGastoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0" id="modalDetalleGastoLabel">
                        <span id="gdetFolio">GA-000000</span>
                    </h5>
                    <span id="gdetBadgeEstado" class="badge">Estado</span>
                    <span id="gdetBadgeNaturaleza" class="badge">CARGO</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <!-- Botones de Transición de Estados -->
                    <button type="button" id="btnAccionEnviarRevision" class="btn btn-sm btn-outline-warning d-none" onclick="accionGasto('enviarRevision');">
                        <i class="ri-send-plane-line me-1"></i> Enviar a Revisión
                    </button>
                    <button type="button" id="btnAccionAprobar" class="btn btn-sm btn-success d-none" onclick="accionGasto('aprobar');">
                        <i class="ri-check-line me-1"></i> Aprobar Gasto
                    </button>
                    <button type="button" id="btnAccionRechazar" class="btn btn-sm btn-outline-danger d-none" onclick="accionGasto('rechazar');">
                        <i class="ri-close-line me-1"></i> Rechazar
                    </button>
                    <button type="button" id="btnAccionDocumentar" class="btn btn-sm btn-primary d-none" onclick="abrirModalDocumentarActual();">
                        <i class="ri-file-text-line me-1"></i> Documentar Financieramente
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                
                <ul class="nav nav-tabs nav-tabs-custom nav-success p-3 pb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#gtab-info" role="tab">
                            <i class="ri-file-list-3-line me-1"></i> Información del Gasto
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#gtab-reparto" role="tab">
                            <i class="ri-pie-chart-line me-1"></i> Reparto por VIN (<span id="gdetCountVins">0</span>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#gtab-documentos" role="tab">
                            <i class="ri-attachment-line me-1"></i> Comprobantes y Soportes (<span id="gdetCountDocs">0</span>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#gtab-bitacora" role="tab">
                            <i class="ri-history-line me-1"></i> Bitácora
                        </a>
                    </li>
                </ul>

                <div class="tab-content p-4">
                    
                    <!-- TAB 1: INFORMACIÓN GENERAL -->
                    <div class="tab-pane active" id="gtab-info" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Envío y Proveedor</h6>
                                    <p class="mb-1"><strong>Envío:</strong> <span id="gdetEnvio">-</span></p>
                                    <p class="mb-1"><strong>Proveedor:</strong> <span id="gdetProveedor">-</span></p>
                                    <p class="mb-0"><strong>Origen:</strong> <span id="gdetOrigen">-</span></p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Concepto y Tipo</h6>
                                    <p class="mb-1"><strong>Tipo de Gasto:</strong> <span id="gdetTipoGasto">-</span></p>
                                    <p class="mb-1"><strong>Motivo:</strong> <span id="gdetMotivo">-</span></p>
                                    <p class="mb-0"><strong>Fecha:</strong> <span id="gdetFecha">-</span></p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Importes Financieros</h6>
                                    <p class="mb-1"><strong>Monto Calculado:</strong> <span id="gdetMontoCalculado">$0.00</span></p>
                                    <h4 class="fw-bold text-primary mb-1" id="gdetMontoFinal">$0.00</h4>
                                    <small class="text-muted" id="gdetTarifaInfo">-</small>
                                </div>
                            </div>

                            <!-- DETALLES COMPLEMENTARIOS -->
                            <div class="col-12" id="divGdetJustificacionIndep">
                                <div class="p-3 border rounded border-warning bg-warning-subtle">
                                    <h6 class="fw-semibold text-warning-emphasis fs-11 text-uppercase mb-1">Justificación de Gasto Independiente</h6>
                                    <p class="mb-0 fs-13" id="gdetJustificacionIndepTexto">-</p>
                                </div>
                            </div>

                            <div class="col-12" id="divGdetAjuste">
                                <div class="p-3 border rounded border-info bg-info-subtle">
                                    <h6 class="fw-semibold text-info-emphasis fs-11 text-uppercase mb-1">Justificación de Ajuste Manual de Monto</h6>
                                    <p class="mb-0 fs-13" id="gdetJustificacionAjusteTexto">-</p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <h6 class="fw-semibold text-muted fs-11 text-uppercase mb-2">Descripción del Concepto</h6>
                                    <p class="mb-0 fs-13" id="gdetDescripcion" style="white-space: pre-line;">-</p>
                                </div>
                            </div>

                            <!-- INFORMACIÓN DEL COMPROBANTE FISCAL APARTE SI YA ESTÁ DOCUMENTADO -->
                            <div class="col-12 d-none" id="divGdetComprobanteFiscal">
                                <div class="p-3 border rounded border-success bg-success-subtle">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-semibold text-success-emphasis fs-12 text-uppercase mb-1">
                                                <i class="ri-check-double-line me-1"></i> Comprobante Financiero Aparte Registrado
                                            </h6>
                                            <div class="fs-13">
                                                <strong>Tipo:</strong> <span id="gdetDocTipo">-</span> | 
                                                <strong>Folio:</strong> <span id="gdetDocFolio">-</span> | 
                                                <strong>UUID CFDI:</strong> <span id="gdetDocUuid">-</span>
                                            </div>
                                        </div>
                                        <span class="badge bg-success">Conciliado</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 2: REPARTO POR VIN -->
                    <div class="tab-pane" id="gtab-reparto" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>VIN</th>
                                        <th>Posición</th>
                                        <th>Costo Original Unidad</th>
                                        <th>% Prorrateo</th>
                                        <th class="text-end">Monto Asignado</th>
                                    </tr>
                                </thead>
                                <tbody id="gdetTbodyReparto"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: DOCUMENTOS -->
                    <div class="tab-pane" id="gtab-documentos" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-semibold text-body mb-0">Soportes y Facturas</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalSubirDocGasto();">
                                <i class="ri-upload-cloud-line me-1"></i> Adjuntar Documento
                            </button>
                        </div>
                        <div class="row g-3" id="gdetContenedorDocumentos"></div>
                    </div>

                    <!-- TAB 4: BITÁCORA -->
                    <div class="tab-pane" id="gtab-bitacora" role="tabpanel">
                        <ul class="list-group list-group-flush fs-13" id="gdetListaLogs"></ul>
                    </div>

                </div>

            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
