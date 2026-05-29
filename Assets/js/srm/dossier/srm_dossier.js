/**
 * Controlador de Expediente Digital del Proveedor (SRM)
 * Conectado 100% a la API y al motor de red Sys_Core
 */

// Desactivar auto-discover global para poder instanciar un Dropzone independiente por tarjeta
Dropzone.autoDiscover = false;

const SrmDossier = {
    
    // Selectores del DOM reutilizables
    ui: {
        grid: '#dossier-grid',
        barProgress: '#bar-progress',
        lblProgress: '#lbl-progress-text',
        alertStatus: '#alert-status'
    },

    init: function() {
        Sys_Core.Auth.validateSession('VENDOR');
        this.loadDossier();
    },

    /**
     * Obtiene el expediente real del proveedor desde la API usando Sys_Core.Net.get
     */
    loadDossier: function() {
        Sys_Core.UI.toggleLoader('.page-content', true);

        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/dossier`,
            silent: false,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    const data = response.data;
                    
                    // 1. Sincronizar Barra de Progreso Superior
                    this.updateProgressBar(data.progress);

                    // 2. Renderizar dinámicamente las tarjetas de los documentos
                    this.renderDocumentCards(data.documents);
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    /**
     * Sincroniza la barra de progreso y el banner de estatus general
     */
    updateProgressBar: function(progress) {
        const percent = parseInt(progress) || 0;
        $(this.ui.barProgress).css('width', percent + '%');
        $(this.ui.lblProgress).text(percent + '%');
        
        const $alert = $(this.ui.alertStatus);
        if (percent === 100) {
            $alert.removeClass('alert-warning').addClass('alert-success')
                  .html('<i class="ri-checkbox-circle-line me-3 align-middle fs-20 text-success"></i><strong>¡Felicidades!</strong> Tu expediente está completo y validado al 100% por LDR Solutions.');
        } else {
            $alert.removeClass('alert-success').addClass('alert-warning')
                  .html('<i class="ri-alert-line me-3 align-middle fs-20 text-warning"></i><strong>Acción Requerida:</strong> Tienes documentos pendientes por subir. Tu cuenta está en fase de <b>ONBOARDING</b>.');
        }
    },

    /**
     * Cicla e inyecta las tarjetas de documentos con sus respectivos estados de Velzon
     */
    renderDocumentCards: function(documents) {
        const $grid = $(this.ui.grid);
        $grid.empty(); // Limpiar el spinner de carga inicial

        Object.entries(documents).forEach(([key, doc]) => {
            let statusBadge = '';
            let bodyContent = '';
            let borderClass = '';

            // ESTADO 1: El documento NO ha sido subido en absoluto
            if (!doc.uploaded) {
                statusBadge = `<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-12 fw-medium"><i class="ri-close-circle-line align-middle me-1"></i> Falta Subir</span>`;
                bodyContent = `
                    <div class="dropzone p-4 text-center border-dashed rounded" id="dz-${key}" style="min-height: 150px; background-color: #f8f9fa; cursor: pointer;">
                        <div class="dz-message needsclick my-2">
                            <i class="ri-upload-cloud-2-line display-5 text-muted opacity-75 mb-2 d-block"></i>
                            <h5 class="fs-13 fw-semibold mb-0">Arrastra el archivo aquí o haz clic.</h5>
                            <p class="text-muted fs-11 mb-0 mt-1">Formato: PDF • Máx: 5MB</p>
                        </div>
                    </div>`;
            } else {
                const file = doc.file_data;
                const statusVal = parseInt(file.estatus_validacion);

                // ESTADO 2: Subido y pendiente de validación (Estatus 0)
                if (statusVal === 0) {
                    borderClass = 'border-top border-3 border-warning';
                    statusBadge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-12 fw-medium"><i class="ri-time-line align-middle me-1"></i> En Revisión</span>`;
                    bodyContent = `
                        <div class="text-center p-3 bg-light rounded">
                            <i class="ri-file-pdf-fill text-danger display-4 mb-2 d-block"></i>
                            <h6 class="fs-13 fw-semibold text-truncate mb-1" title="${doc.name}">${doc.name}.pdf</h6>
                            <p class="text-muted fs-11 mb-3">Subido el: ${Sys_Core.Format.toDate(file.created_at)}</p>
                            <button type="button" onclick="SrmDossier.viewFile('${base_url}/${file.url_archivo}')" class="btn btn-soft-primary btn-sm fw-medium"><i class="ri-eye-line align-middle me-1"></i> Ver Archivo</button>
                        </div>`;
                } 
                // ESTADO 3: Validado y aprobado por Finanzas (Estatus 1)
                else if (statusVal === 1) {
                    borderClass = 'border-top border-3 border-success bg-success-subtle';
                    statusBadge = `<span class="badge bg-success px-2 py-1 fs-12 fw-medium"><i class="ri-checkbox-circle-line align-middle me-1"></i> Aprobado</span>`;
                    bodyContent = `
                        <div class="text-center p-3 bg-white rounded shadow-sm">
                            <i class="ri-shield-check-fill text-success display-4 mb-2 d-block"></i>
                            <h6 class="fs-13 fw-semibold text-success mb-1">Documento Validado</h6>
                            <p class="text-muted fs-11 mb-3">Verificado por Finanzas</p>
                            <button type="button" onclick="SrmDossier.viewFile('${base_url}/${file.url_archivo}')" class="btn btn-soft-success btn-sm fw-medium"><i class="ri-eye-line align-middle me-1"></i> Ver PDF</button>
                        </div>`;
                } 
                // ESTADO 4: Rechazado por Finanzas (Estatus 2)
                else if (statusVal === 2) {
                    borderClass = 'border-top border-3 border-danger bg-danger-subtle';
                    statusBadge = `<span class="badge bg-danger px-2 py-1 fs-12 fw-medium"><i class="ri-error-warning-line align-middle me-1"></i> Rechazado</span>`;
                    bodyContent = `
                        <div class="alert alert-danger p-2 fs-12 mb-3" role="alert">
                            <i class="ri-information-line align-middle me-1"></i> <b>Motivo:</b> ${file.motivo_rechazo || 'Archivo incorrecto o ilegible.'}
                        </div>
                        <div class="dropzone p-3 text-center border-dashed rounded" id="dz-${key}" style="min-height: 120px; background-color: #f8f9fa; cursor: pointer;">
                            <div class="dz-message needsclick my-1">
                                <i class="ri-refresh-line display-6 text-danger d-block mb-1"></i>
                                <h5 class="fs-12 fw-semibold mb-0">Reemplazar Archivo</h5>
                            </div>
                        </div>`;
                }
            }

            // Inyección de la plantilla HTML nativa de Velzon
            const cardHtml = `
                <div class="col-xxl-4 col-lg-6 mb-2">
                    <div class="card shadow-sm border-0 rounded-3 h-100 ${borderClass}">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0 fw-bold text-dark fs-15">${doc.name}</h5>
                                ${statusBadge}
                            </div>
                            <p class="text-muted fs-12 mb-0">Obligatorio • Formato PDF • Máx: 5MB</p>
                        </div>
                        <div class="card-body">
                            ${bodyContent}
                        </div>
                    </div>
                </div>`;

            $grid.append(cardHtml);

            // Inicializar Dropzone solo si el documento está ausente o rechazado (para permitir carga)
            if (!doc.uploaded || (doc.file_data && parseInt(doc.file_data.estatus_validacion) === 2)) {
                this.bindDropzone(key);
            }
        });
    },

    /**
     * Instancia de forma segura un objeto Dropzone por tarjeta
     */
    bindDropzone: function(key) {
        const token = Sys_Core.Auth.getCookie('mrp_token');
        const selector = `#dz-${key}`;

        new Dropzone(selector, {
            url: `${base_url}/api/v1/srm/dossier/upload`,
            method: "POST",
            paramName: "archivo",
            maxFilesize: 5,
            acceptedFiles: "application/pdf",
            headers: {
                'Authorization': token ? `Bearer ${token}` : ''
            },
            sending: function(file, xhr, formData) {
                // Inyectamos dinámicamente la clave del Enum requerida por el backend
                formData.append('tipo_documento', key);
            },
            success: (file, response) => {
                Sys_Core.UI.notify("Documento cargado correctamente.", "success");
                // Recargar listado completo para recalcular el progreso general de inmediato
                this.loadDossier();
            },
            error: (file, response) => {
                const message = typeof response === 'object' ? response.message : response;
                Sys_Core.UI.alert("Error de Carga", message || "No se pudo subir el archivo.", "error");
                this.loadDossier();
            }
        });
    },

    /**
     * Abre los PDFs validados en una nueva pestaña de forma segura
     */
    viewFile: function(url) {
        window.open(url, '_blank');
    }
};

$(document).ready(function() {
    SrmDossier.init();
});