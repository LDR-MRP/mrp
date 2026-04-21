/**
 * MRP System - Requisition Read-Only View
 * @module RequisitionRead
 * @description Vista de expediente de requisición con acciones dinámicas según estado.
 */

const RequisitionRead = {
    config: {
        apiBase: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`
    },

    state: {
        id: null,
        data: null 
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
            $actionContainer: $('#action-buttons-container'),
            $relatedPOsCard: $('#card-related-pos'), // Tarjeta lateral para ver las OCs hijas
            $relatedPOsList: $('#list-related-pos')
        };
    },

    bindEvents: function () {
        this.dom.$actionContainer.on('click', '.action-btn', (e) => this.handleAction(e));
        this.dom.$actionContainer.on('click', '#btn-export-pdf', () => this.downloadPDF());
    },

    downloadPDF: function () {
        const $btn = $('#btn-export-pdf');
        const originalHtml = $btn.html();

        $.ajax({
            url: `${this.config.apiBase}/${this.state.id}/pdf`,
            method: 'GET',
            xhrFields: { responseType: 'blob' },
            beforeSend: () => {
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Generando...');
                Sys_Core.UI.toggleLoader('.page-content', true);
            },
            success: (blob, status, xhr) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                let filename = `Requisicion_${this.state.id}.pdf`;
                const disposition = xhr.getResponseHeader('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                    if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
                }
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            },
            error: (xhr) => {
                Sys_Core.Net.handleError(xhr);
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
            }
        });
        Sys_Core.UI.toggleLoader('.page-content', false);
    },

    renderUI: function () {
        const d = this.state.data;

        this.dom.$lblId.text(d.idrequisicion);
        this.dom.$lblTitulo.text(d.titulo || 'Sin título de referencia');
        this.dom.$lblSolicitante.text(d.solicitante || 'Usuario del Sistema');
        this.dom.$lblDepto.text(d.departamento_descripcion || 'No asignado');
        this.dom.$lblFechaReq.text(Sys_Core.Format.toDate(d.fecha_requerida));
        this.dom.$lblCreacion.text(Sys_Core.Format.toDate(d.fecha));
        this.dom.$lblJustificacion.text(d.justificacion || 'Sin justificación proporcionada.');
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(d.monto_estimado));

        this.dom.$lblEstatus.replaceWith(this.getStatusBadge(d.estatus));
        
        const prioColors = { 'baja': 'bg-info', 'media': 'bg-warning', 'alta': 'bg-danger', 'critica': 'bg-dark' };
        const prioColor = prioColors[d.prioridad?.toLowerCase()] || 'bg-secondary';
        this.dom.$lblPrioridad.removeClass().addClass(`badge ${prioColor} fs-12 px-3 py-1 shadow-sm`).text((d.prioridad || 'Normal').toUpperCase());

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

            d.items.forEach(item => {
                // Si el backend nos manda cantidad_comprada (debería), lo mostramos
                const qtySolicitada = parseFloat(item.cantidad);
                const qtyComprada = parseFloat(item.cantidad_comprada || 0);
                const pendiente = qtySolicitada - qtyComprada;
                
                const badgeColor = pendiente <= 0 ? 'success' : (qtyComprada > 0 ? 'warning' : 'secondary');
                const badgeText = pendiente <= 0 ? 'Completado' : (qtyComprada > 0 ? `Faltan ${pendiente}` : 'Pendiente');

                const html = `
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">${item.cve_articulo || 'N/A'}</div>
                            <div class="small text-muted">${item.descripcion || 'Artículo'}</div>
                        </td>
                        <td class="text-center">
                            <span class="fw-medium">${qtySolicitada.toString()}</span><br>
                            <span class="badge bg-soft-${badgeColor} text-${badgeColor} fs-10">${badgeText}</span>
                        </td>
                        <td class="text-end">${Sys_Core.Format.toCurrency(item.precio_unitario_estimado)}</td>
                        <td class="text-end fw-bold text-primary">${Sys_Core.Format.toCurrency(item.subtotal)}</td>
                        <td class="small text-muted">${item.notas || '-'}</td>
                    </tr>
                `;
                this.dom.$tblPartidas.append(html);
            });
        } else {
            this.dom.$tblPartidas.html('<tr><td colspan="5" class="text-center py-4 text-muted">No hay partidas registradas.</td></tr>');
        }

        if (d.related_pos && d.related_pos.length > 0) {
            this.renderRelatedPOs(d.related_pos);
        }

        this.renderContextualActions(d.estatus);
    },

    renderRelatedPOs: function (pos) {
        const $card = $('#card-related-pos'); // Asegúrate de tener este ID en tu HTML
        const $list = $('#list-related-pos');
        
        if ($card.length) {
            $card.show();
            $list.empty();
            pos.forEach(po => {
                $list.append(`
                    <a href="${Sys_Core.Config.baseUrl}/com_orden/read/${po.idcompra}" class="list-group-item list-group-item-action border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-primary">OC #${po.idcompra}</div>
                                <small class="text-muted">${Sys_Core.Format.toDate(po.created_at)}</small>
                            </div>
                            <span class="badge bg-light text-dark">${po.estatus.toUpperCase()}</span>
                        </div>
                    </a>
                `);
            });
        }
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
        const canUpdate = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u');
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); 
        const strStatus = status ? status.toLowerCase() : '';

        // Botón Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón PDF
        html += `<button type="button" class="btn btn-outline-danger ms-2" id="btn-export-pdf"><i class="ri-file-pdf-2-line"></i> Exportar PDF</button>`;

        switch (strStatus) {
            case 'borrador':
                if (canUpdate) {
                    html += `<button type="button" class="btn btn-primary ms-2" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                }
                break;
            case 'pendiente':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-danger ms-2 action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success ms-2 action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
            case 'en compra':
                // ¡LÓGICA DE COMPRAS!: Si está aprobada o en proceso, permitir generar/continuar la OC
                if (canApprove) {
                    const btnLabel = (strStatus === 'aprobada') ? 'Generar Orden de Compra' : 'Continuar con Compra';
                    html += `<button type="button" class="btn btn-primary ms-2 shadow-sm" data-redirect="com_orden/create?req_id=${this.state.id}">
                                <i class="ri-shopping-cart-2-line align-middle me-1"></i> ${btnLabel}
                             </button>`;
                }
                break;
        }

        this.dom.$actionContainer.html(html);
    },

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
            url: `${this.config.apiBase}/${this.state.id}/${accion}`,
            payload: { comentario: comentario },
            successMsg: `Solicitud procesada correctamente.`,
            onDone: () => {
                this.loadData(); // Recarga asíncrona de la UI
            }
        });
    }
};

$(document).ready(function () {
    RequisitionRead.init();
});