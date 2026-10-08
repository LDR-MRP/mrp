<!-- Modal Documentar Gasto con Factura / Nota Aparte -->
<div class="modal fade" id="modalDocumentarGasto" tabindex="-1" aria-labelledby="modalDocumentarLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title fw-bold" id="modalDocumentarLabel">
                    <i class="ri-file-text-line text-primary me-1"></i> Documentar Financieramente (Comprobante Aparte)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDocumentarGasto" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" id="doc_id_gasto" name="id_gasto" value="">
                <div class="modal-body p-4">
                    
                    <div class="alert alert-info py-2 px-3 mb-3 fs-13">
                        <div class="fw-semibold" id="doc_info_gasto">GA-000000 - Monto: $0.00</div>
                        <div class="text-muted small">La factura original del envío permanece intacta. Este gasto se respalda mediante comprobante fiscal independiente.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Tipo de Documento <span class="text-danger">*</span></label>
                        <select id="doc_tipo" name="doc_tipo" class="form-select" required>
                            <option value="FACTURA">Factura de Proveedor (Complementaria / Cargo)</option>
                            <option value="NOTA_CARGO">Nota de Cargo / Deducción al Proveedor</option>
                            <option value="POLIZA_INTERNA">Póliza / Registro Contable Interno</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Folio de Comprobante / Factura <span class="text-danger">*</span></label>
                            <input type="text" id="doc_folio" name="doc_folio" class="form-control" required placeholder="Ej. FAC-48291 / NC-102">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Fecha de Emisión <span class="text-danger">*</span></label>
                            <input type="date" id="doc_fecha" name="doc_fecha" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">UUID / Folio Fiscal Digital (SAT)</label>
                        <input type="text" id="doc_uuid" name="doc_uuid" class="form-control font-monospace" placeholder="Ej. 4A5B6C7D-8E9F-0A1B-2C3D-4E5F6A7B8C9D">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Archivo PDF o XML del Comprobante (Opcional)</label>
                        <input type="file" id="doc_archivo" name="archivo_documento" class="form-control" accept="application/pdf,text/xml,image/*">
                        <div class="form-text fs-11">Permitido: PDF, XML CFDI, JPG, PNG (máx. 10MB).</div>
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarDocumentoGasto" class="btn btn-primary px-4">
                        <i class="ri-check-double-line me-1"></i> Documentar y Finalizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
