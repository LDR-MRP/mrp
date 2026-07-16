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
        Sys_Core.Auth.validateSession();
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
            $lblFolio: $('#lbl-folio-breadcrumb'),
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
        this.dom.$actionContainer.on('click', '.action-btn', (e) => this.handleAction(e));
        this.dom.$tblPartidas.on('click', '.btn-comparativa', (e) => this.redirectToWorkspace(e));
    },

    redirectToWorkspace: function(e) {
         e.preventDefault();
    
        const $btn = $(e.currentTarget);
        const idPartida = $btn.data('id');
        const idEvento = parseInt($btn.data('event-id')) || 0;

        // ESCENARIO A: La partida ya tiene un Folio de Sourcing (SOUR-XXX)
        if (idEvento > 0) {
            Sys_Core.UI.notify('Accediendo al Workspace de Negociación...', 'info');
            // Redirigimos al detalle del evento global
            Sys_Core.Navigation.to(`com_sourcing/detail/${idEvento}?target=${idPartida}`);
        } 
        // ESCENARIO B: Partida aprobada pero aún no ha sido agrupada por un Comprador
        else {
            Sys_Core.UI.notify('Partida pendiente de agrupar. Abriendo Inbox...', 'warning');
            /**
             * Redirigimos al Inbox de Pendientes pasando el target en la URL.
             * Esto permitirá que el SourcingInbox.js resalte y auto-seleccione la fila.
             */
            Sys_Core.Navigation.to(`com_sourcing/inbox?target=${idPartida}`);
        }
    },

    printRequisition: function() {
        const requisitionId = this.state.id;
        if (!requisitionId) return;

        // Notificación de cortesía
        Sys_Core.UI.notify('Generando documento...', 'info');

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
        this.dom.$lblFolio.text(d.folio) || `REQ-${d.idrequisicion}`;
        this.dom.$lblTitulo.text(d.titulo || 'Sin título de referencia');
        this.dom.$lblSolicitante.text(d.solicitante || 'Usuario del Sistema');
        this.dom.$lblDepto.text(d.departamento || 'No asignado');
        this.dom.$lblFechaReq.text(Sys_Core.Format.toDate(d.fecha_requerida));
        this.dom.$lblCreacion.text(Sys_Core.Format.toDate(d.fecha));
        this.dom.$lblJustificacion.text(d.justificacion || 'Sin justificación proporcionada.');
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(d.monto_estimado));

        const prioColors = { 'baja': 'bg-info', 'media': 'bg-warning', 'alta': 'bg-danger', 'critica': 'bg-dark' };
        const prioColor = prioColors[d.prioridad?.toLowerCase()] || 'bg-secondary';
        
        this.dom.$lblPrioridad
            .removeClass()
            .addClass(`badge ${prioColor} text-uppercase fs-12 px-3 shadow-sm`)
            .text((d.prioridad || 'Normal')
            .toUpperCase());

        // FIX: Actualización de Badge sin destruir el elemento (Consistencia)
        const badgeClass = this.getStatusConfig(d.estatus);        
        this.dom.$lblEstatus
            .removeClass() 
            .addClass(`badge ${badgeClass} text-uppercase fs-12 px-3 shadow-sm`)
            .text(d.estatus);
  
        

        this.dom.$tblPartidas.empty();
        if (d.items && d.items.length > 0) {
            d.items.forEach(item => {
                const isSourcing = parseInt(item.es_sourcing) === 1;
                const progress = item.porcentaje_surtido || 0;
                const barColor = progress >= 100 ? 'bg-success' : (progress > 0 ? 'bg-warning' : 'bg-light');
                
                // Normalización de datos
                const sku = item.cve_articulo || (isSourcing ? 'SOURCING' : 'N/A');
                const desc = item.descripcion || 'Sin descripción';
                const price = parseFloat(item.precio_unitario_estimado) || 0;
                const qty = parseFloat(item.cantidad) || 0;
                const subtotal = parseFloat(item.subtotal) || (qty * price);

                // 1. Determinamos si ya tiene un evento asignado
                const hasEvent = (item.src_evento_sourcing_id && item.src_evento_sourcing_id > 0);

                // Configuramos el estilo basado en el estatus
                const sourcingConfig = {
                    class: hasEvent ? 'btn-soft-success' : 'btn-soft-warning',
                    icon:  hasEvent ? 'ri-scales-3-fill' : 'ri-scales-3-line',
                    text:  hasEvent ? `Ver Negociación (${item.folio_sourcing || 'En curso'})` : 'Pendiente de Sourcing. Clic para agrupar.'
                };

                const html = `
                    <tr class="${isSourcing ? 'border-start border-4 border-primary' : ''} align-middle">
                        <!-- 1. DESCRIPCIÓN -->
                        <td style="width: 35%;">
                            <div class="ps-2">
                                <div class="fw-bold text-dark fs-13">${sku}</div>
                                <div class="text-muted fs-11 text-truncate" style="max-width: 250px;">
                                    ${isSourcing ? '<i class="ri-auction-line text-primary me-1"></i>' : ''}${desc}
                                </div>
                            </div>
                        </td>

                        <!-- 2. CANTIDAD / ARRIBO -->
                        <td class="text-center" style="width: 20%;">
                            <div class="d-flex justify-content-between mb-1 fs-11">
                                <span class="fw-bold">${item.qty_comprada || 0} / ${qty}</span>
                                <span class="text-muted">${progress}%</span>
                            </div>
                            <div class="progress" style="height: 5px; background-color: #f0f0f0;">
                                <div class="progress-bar ${barColor}" style="width: ${progress}%"></div>
                            </div>
                        </td>

                        <!-- 3. PRECIO UNITARIO -->
                        <td class="text-end" style="width: 15%;">
                            <span class="text-muted fs-12">${Sys_Core.Format.toCurrency(price)}</span>
                        </td>

                        <!-- 4. SUBTOTAL (LA COLUMNA QUE FALTABA) -->
                        <td class="text-end" style="width: 15%;">
                            <span class="fw-bold text-primary fs-13">${Sys_Core.Format.toCurrency(subtotal)}</span>
                        </td>

                        <!-- 5. NOTAS / ACCIONES -->
                        <td class="text-center" style="width: 15%;">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                ${item.notas ? `<i class="ri-information-line text-info cursor-pointer fs-17" title="${item.notas}" data-bs-toggle="tooltip"></i>` : '---'}
                                
                                ${isSourcing ? `
                                    <button class="btn btn-sm ${sourcingConfig.class} p-1 lh-1 btn-comparativa shadow-none" 
                                            data-id="${item.idrequisicionarticulo}" 
                                            data-event-id="${item.src_evento_sourcing_id || 0}"
                                            title="${sourcingConfig.text}" 
                                            data-bs-toggle="tooltip">
                                        <i class="${sourcingConfig.icon} fs-14"></i>
                                    </button>` : ''
                                }
                            </div>
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

    // Cambia el nombre a getStatusConfig para mayor claridad semántica
    getStatusConfig: function (status) {
        const strStatus = status ? status.toLowerCase() : '';
        const config = {
            'borrador':   'bg-secondary',
            'pendiente':  'bg-warning',
            'aprobada':   'bg-success',
            'rechazada':  'bg-danger',
            'en compra':  'bg-info',
            'finalizada': 'bg-success',
            'cancelada':  'bg-dark'
        };
        return config[strStatus] || 'bg-secondary text-white';
    },

    renderContextualActions: function (status) {
        const d = this.state.data;
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); 
        const strStatus = status ? status.toLowerCase() : '';

        // Botón Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón PDF
        html += `<button type="button" class="btn btn-outline-danger" id="btn-export-pdf"><i class="ri-file-pdf-line"></i> PDF</button>`;

        // --- LÓGICA DE USABILIDAD STP ---
        if (strStatus === 'finalizada' && d.tipo_requisicion === 'spot_buy') {
            const lastPO = d.related_pos ? d.related_pos[0] : null;
            html += `<hr class="my-2 border-light">`;
            html += `<div class="alert alert-success border-0 shadow-sm mb-2 fs-11 p-2">
                        <i class="ri-checkbox-circle-fill me-1"></i> Proceso automatizado por <b>Sistema</b>.
                     </div>`;
            if (lastPO) {
                html += `<button type="button" class="btn btn-primary w-100 shadow-sm animate__animated animate__fadeInUp" 
                                 data-redirect="com_orden/read/${lastPO.idcompra}">
                            <i class="ri-external-link-line align-middle me-1"></i> Ver Orden de Compra #${lastPO.idcompra}
                         </button>`;
            }
            this.dom.$actionContainer.html(html);
            return; // Salir de la función, no necesitamos el resto
        }

        // --- FLUJO ESTÁNDAR ---
        switch (strStatus) {
            case 'borrador':
                html += `<button type="button" class="btn btn-primary" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                break;
            case 'pendiente':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-danger action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
            case 'en compra':
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