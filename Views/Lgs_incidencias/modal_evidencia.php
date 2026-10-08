<!-- Modal Subir Evidencia Adicional -->
<div class="modal fade" id="modalSubirEvidencia" tabindex="-1" aria-labelledby="modalSubirEvidenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title fw-bold" id="modalSubirEvidenciaLabel">
                    <i class="ri-upload-cloud-line text-primary me-1"></i> Adjuntar Evidencia Multimedia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSubirEvidencia" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" id="evidencia_id_incidencia" name="id_incidencia" value="">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Tipo de Evidencia <span class="text-danger">*</span></label>
                        <select id="evidencia_tipo" name="tipo" class="form-select" required>
                            <option value="FOTO">Fotografía en Sitio</option>
                            <option value="VIDEO">Video</option>
                            <option value="DICTAMEN">Dictamen / Nota Técnica</option>
                            <option value="REPORTE_CLIENTE">Reporte de Distribuidor</option>
                            <option value="OTRO">Otro Documento</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Asociar a un VIN Específico (Opcional)</label>
                        <select id="evidencia_id_envio_vin" name="id_envio_vin" class="form-select">
                            <option value="">Afecta a toda la incidencia / general</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Seleccionar Archivo <span class="text-danger">*</span></label>
                        <input type="file" id="evidencia_archivo" name="archivo" class="form-control" required accept="image/*,video/mp4,application/pdf">
                        <div class="form-text fs-11">Permitido: JPG, PNG, WEBP, PDF, MP4 (máx. 10MB / video 25MB).</div>
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnSubirEvidencia" class="btn btn-primary px-4">
                        <i class="ri-upload-line me-1"></i> Subir Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
