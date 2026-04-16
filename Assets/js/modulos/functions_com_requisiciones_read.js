/**
 * MRP System - Requisition Read-Only View
 * @module RequisitionRead
 */

const RequisitionRead = {
    config: {
        apiBase: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`
    },

    state: {
        id: null,
        data: null // Guardamos la info de la requisición
    },

    dom: {},

    init: function () {
        this.extractId();
        if (!this.state.id) {
            Sys_Core.Navigation.to('com_requisicion');
            return;
        }
        this.cacheDOM();
        this.bindEvents();
        this.loadData();
    },

    extractId: function () {
        const pathSegments = window.location.pathname.split('/');
        const possibleId = pathSegments[pathSegments.length - 1];
        if (!isNaN(possibleId) && possibleId > 0) {
            this.state.id = parseInt(possibleId);
        }
    },

    cacheDOM: function () {
        this.dom = {
            $lblId: $('#lbl-idrequisicion'),
            $lblEstatus: $('#lbl-estatus'),
            $lblTitulo: $('#lbl-titulo'),
            $lblSolicitante: $('#lbl-solicitante'),
            $lblDepto: $('#lbl-departamento'),
            $lblFechaReq: $('#lbl-fecha-requerida'),
            $lblPrioridad: $('#lbl-prioridad'),
            $lblCreacion: $('#lbl-fecha-creacion'),
            $lblJustificacion: $('#lbl-justificacion'),
            $lblTotal: $('#lbl-total-monto'),
            $tblPartidas: $('#tbl-read-partidas'),
            $actionContainer: $('#action-buttons-container')
        };
    },

    bindEvents: function () {
        // Escuchar clics en los botones dinámicos de acción (Aprobar, Rechazar, etc.)
        this.dom.$actionContainer.on('click', '.action-btn', (e) => this.handleAction(e));

        // Listener para el PDF
        this.dom.$actionContainer.on('click', '#btn-export-pdf', () => this.downloadPDF());
    },

    // Descarga segura de archivos binarios vía API
    downloadPDF: function () {
        const $btn = $('#btn-export-pdf');
        const originalHtml = $btn.html();

        $.ajax({
            url: `${this.config.apiBase}/${this.state.id}/pdf`,
            method: 'GET',
            xhrFields: {
                responseType: 'blob' // CRÍTICO: Le decimos a jQuery que esperamos un archivo, no JSON
            },
            beforeSend: () => {
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Generando...');
                Sys_Core.UI.toggleLoader('.page-content', true);
            },
            success: (blob, status, xhr) => {
                // 1. Crear una URL local temporal para el archivo binario
                const url = window.URL.createObjectURL(blob);
                
                // 2. Crear un enlace <a> invisible
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                
                // 3. Extraer el nombre del archivo de los headers del backend (Opcional pero elegante)
                let filename = `Requisicion_${this.state.id}.pdf`;
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                }
                
                a.download = filename;
                
                // 4. Simular el clic para descargar y limpiar la memoria
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            },
            error: (xhr) => {
                // Si el backend devuelve un JSON con error (ej. 404 o 403) en lugar del PDF
                if (xhr.responseType === 'blob') {
                    // Convertir el blob de error a texto para leer el JSON
                    const reader = new FileReader();
                    reader.onload = function() {
                        try {
                            const err = JSON.parse(reader.result);
                            Sys_Core.UI.alert('Error', err.message || 'No se pudo generar el PDF.', 'error');
                        } catch (e) {
                            Sys_Core.UI.alert('Error', 'Ocurrió un error al generar el documento.', 'error');
                        }
                    };
                    reader.readAsText(xhr.response);
                } else {
                    Sys_Core.Net.handleError(xhr);
                }
            },
            complete: () => {
                $btn.prop('disabled', false).html(originalHtml);
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    loadData: function () {
        Sys_Core.UI.toggleLoader('.page-content', true);

        Sys_Core.Net.get({
            url: `${this.config.apiBase}/${this.state.id}`,
            onSuccess: (res) => {
                if (res.status) {
                    this.state.data = res.data;
                    this.renderUI();
                } else {
                    Sys_Core.UI.alert('Error', 'No se pudo cargar el expediente.', 'error');
                }
            },
            silent: false
        });
        
        Sys_Core.UI.toggleLoader('.page-content', false);
    },

    renderUI: function () {
        const d = this.state.data;

        // 1. Cabecera y Textos
        this.dom.$lblId.text(d.idrequisicion);
        this.dom.$lblTitulo.text(d.titulo || 'Sin título de referencia');
        this.dom.$lblSolicitante.text(d.solicitante || 'Usuario del Sistema');
        this.dom.$lblDepto.text(d.departamento_descripcion || 'No asignado');
        this.dom.$lblFechaReq.text(Sys_Core.Format.toDate(d.fecha_requerida));
        this.dom.$lblCreacion.text(Sys_Core.Format.toDate(d.fecha));
        this.dom.$lblJustificacion.text(d.justificacion || 'Sin justificación proporcionada.');
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(d.monto_estimado));

        // 2. Badges de Estatus y Prioridad
        this.dom.$lblEstatus.replaceWith(this.getStatusBadge(d.estatus));
        
        const prioColors = { 'baja': 'bg-info', 'media': 'bg-warning', 'alta': 'bg-danger', 'critica': 'bg-dark' };
        const prioColor = prioColors[d.prioridad?.toLowerCase()] || 'bg-secondary';
        this.dom.$lblPrioridad.removeClass('bg-light text-dark').addClass(prioColor).text((d.prioridad || 'Normal').toUpperCase());

        // 3. Tabla de Partidas
        this.dom.$tblPartidas.empty();
        if (d.items && d.items.length > 0) {
            d.items.forEach(item => {
                const html = `
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">${item.cve_articulo || 'N/A'}</div>
                            <div class="small text-muted">${item.descripcion || 'Artículo'}</div>
                        </td>
                        <td class="text-center fw-medium">${parseFloat(item.cantidad).toString()} <span class="small text-muted">${item.unidad_salida || 'PZA'}</span></td>
                        <td class="text-end">${Sys_Core.Format.toCurrency(item.precio_unitario_estimado)}</td>
                        <td class="text-end fw-bold text-primary">${Sys_Core.Format.toCurrency(item.subtotal)}</td>
                        <td class="small text-muted">${item.notas || '-'}</td>
                    </tr>
                `;
                this.dom.$tblPartidas.append(html);
            });
        } else {
            this.dom.$tblPartidas.html('<tr><td colspan="5" class="text-center py-4 text-muted">No hay partidas registradas en esta solicitud.</td></tr>');
        }

        // 4. Inyectar Botones de Acción según la máquina de estados
        this.renderContextualActions(d.estatus);
    },

    getStatusBadge: function (status) {
        const strStatus = status ? status.toLowerCase() : '';
        const clases = {
            'borrador': 'badge-draft', 'pendiente': 'badge-review', 'aprobada': 'badge-approved',
            'rechazada': 'badge-rejected', 'en compra': 'badge-purchasing',
            'finalizada': 'bg-secondary', 'cancelada': 'bg-secondary', 'eliminada': 'bg-danger'
        };
        const badgeClass = clases[strStatus] || 'bg-secondary';
        return `<span id="lbl-estatus" class="badge ${badgeClass} px-3 py-2 text-capitalize fs-13 shadow-sm">${status}</span>`;
    },

    renderContextualActions: function (status) {
        // Misma lógica de la máquina de estados del Index
        const canUpdate = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u');
        const canDelete = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'd');
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r');
        const strStatus = status ? status.toLowerCase() : '';

        // Botón de Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón de PDF (Siempre visible)
        html += `<button type="button" class="btn btn-outline-danger ms-2" id="btn-export-pdf">
                    <i class="ri-file-pdf-2-line align-middle me-1"></i> Exportar PDF
                 </button>`;

        switch (strStatus) {
            case 'borrador':
                if (canUpdate) {
                    html += `<button type="button" class="btn btn-primary" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                }
                break;
            case 'pendiente':
                if (canApprove) {
                    // Estos botones lanzarán una alerta SweetAlert para pedir el comentario antes de procesar
                    html += `<button type="button" class="btn btn-danger action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-primary" data-redirect="com_ordenes/create?req_id=${this.state.id}"><i class="ri-file-list-3-line"></i> Generar O.C.</button>`;
                }
                break;
        }

        this.dom.$actionContainer.html(html);
    },

    // Lógica para procesar Aprobar/Rechazar desde la vista de lectura usando SweetAlert
    handleAction: function (e) {
        const accion = $(e.currentTarget).data('accion');
        const title = accion === 'approve' ? 'Aprobar Solicitud' : 'Rechazar Solicitud';
        const color = accion === 'approve' ? '#28a745' : '#dc3545';

        Swal.fire({
            title: title,
            text: "Ingrese un comentario obligatorio para esta acción:",
            input: 'textarea',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            preConfirm: (comentario) => {
                if (!comentario || comentario.trim() === '') {
                    Swal.showValidationMessage('El comentario es obligatorio.');
                }
                return comentario;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.submitAction(accion, result.value);
            }
        });
    },

    submitAction: function (accion, comentario) {
        Sys_Core.Net.post({
            url: `${this.config.apiBase}/${this.state.id}/${accion}`, // Ej: POST /api/v1/requisitions/8/approve
            payload: { comentario: comentario },
            successMsg: `Solicitud procesada correctamente.`,
            onDone: () => {
                // Recargamos los datos para que la UI se actualice al nuevo estado sin refrescar la página
                this.loadData();
            }
        });
    }
};

$(document).ready(function () {
    RequisitionRead.init();
});