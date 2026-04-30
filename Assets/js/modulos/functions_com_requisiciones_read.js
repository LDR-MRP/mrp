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
        this.dom.$actionContainer.on('click', '#btn-export-pdf', () => this.printRequisition());
    },

    printRequisition: function() {
        const requisitionId = this.state.id;
        if (!requisitionId) return;

        Sys_Core.Net.downloadPdf({
            url: `${this.config.apiBase}/${requisitionId}/pdf`,
            filename: `Requisicion_${requisitionId}.pdf`
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
        this.dom.$lblDepto.text(d.departamento || 'No asignado');
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
                const progress = item.porcentaje_surtido;
                const barColor = progress >= 100 ? 'bg-success' : (progress > 0 ? 'bg-warning' : 'bg-light');
                const html = `
                    <tr>
                        <td style="width: 40%;">
                            <div class="fw-bold">${item.cve_articulo}</div>
                            <div class="text-muted fs-11 text-truncate" style="max-width: 250px;">${item.descripcion}</div>
                        </td>
                        <td class="text-center" style="width: 25%;">
                            <div class="d-flex justify-content-between mb-1 fs-11">
                                <span class="fw-medium">${item.qty_comprada} / ${item.cantidad}</span>
                                <span class="text-muted">${progress}%</span>
                            </div>
                            <div class="progress" style="height: 6px; background-color: #f0f0f0;">
                                <div class="progress-bar ${barColor}" role="progressbar" style="width: ${progress}%"></div>
                            </div>
                        </td>
                        <td class="text-end" style="width: 20%;">
                            <div class="fw-bold text-primary">${Sys_Core.Format.toCurrency(item.subtotal)}</div>
                            <div class="text-muted fs-10">${Sys_Core.Format.toCurrency(item.precio_unitario_estimado)} c/u</div>
                        </td>
                        <td class="text-center" style="width: 15%;">
                            ${item.notas ? `<i class="ri-information-line text-info cursor-pointer" title="${item.notas}"></i>` : '---'}
                        </td>
                    </tr>`;
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
                            <span class="badge bg-light">${po.estatus.toUpperCase()}</span>
                        </div>
                    </a>
                `);
            });
        }
    },

    getStatusBadge: function (status) {
        const strStatus = status ? status.toLowerCase() : '';
        const clases = {
            'borrador': 'badge text-bg-light',
            'pendiente': 'badge text-bg-warning',
            'aprobada': 'badge text-bg-success',
            'rechazada': 'badge text-bg-danger',
            'en compra': 'badge text-bg-info',
            'finalizada': 'badge text-bg-secondary',
            'cancelada': 'badge text-bg-danger',
            'eliminada': 'badge text-bg-danger'
        };
        const badgeClass = clases[strStatus] || 'bg-secondary';
        return `<span id="lbl-estatus" class="badge ${badgeClass} px-3 py-2 text-capitalize fs-13 shadow-sm ms-3">${status}</span>`;
    },

    renderContextualActions: function (status) {
        const canUpdate = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u');
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); 
        const strStatus = status ? status.toLowerCase() : '';

        // Botón Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón PDF
        html += `<button type="button" class="btn btn-outline-danger" id="btn-export-pdf"><i class="ri-file-pdf-line"></i> PDF</button>`;

        switch (strStatus) {
            case 'borrador':
                if (canUpdate) {
                    html += `<button type="button" class="btn btn-primary" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                }
                break;
            case 'pendiente':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-danger action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
            case 'en compra':
                // ¡LÓGICA DE COMPRAS!: Si está aprobada o en proceso, permitir generar/continuar la OC
                if (canApprove) {
                    const btnLabel = (strStatus === 'aprobada') ? 'Generar Orden de Compra' : 'Continuar con Compra';
                    html += `<button type="button" class="btn btn-primary shadow-sm" data-redirect="com_orden/create?req_id=${this.state.id}">
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