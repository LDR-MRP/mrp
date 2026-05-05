const PurchaseOrderRead = {
    config: { apiBase: `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders` },
    state: { id: null, data: null },
    dom: {},

    init: function () {
        this.extractId();
        this.cacheDOM();
        this.bindEvents();
        this.loadData();
    },

    extractId: function () {
        // Obtenemos el ID de la URL. Ej: /compras/create/8 -> id = 8
        const pathSegments = window.location.pathname.split('/');
        this.state.id = pathSegments[pathSegments.length - 1];
    },

    cacheDOM: function () {
        this.dom = {
            $lblId: $('#lbl-idcompra'),
            $lblEstatus: $('#lbl-estatus'),
            $lblReqId: $('#lbl-req-id'),
            $lblProveedor: $('#lbl-proveedor'),
            $lblAlmacen: $('#lbl-almacen'),
            $lblObs: $('#lbl-observaciones'),
            $lblSubtotal: $('#lbl-subtotal'),
            $lblIva: $('#lbl-iva'),
            $lblTotal: $('#lbl-total'),
            $lblMoneda: $('#lbl-moneda'),
            $tblItems: $('#tbl-items'),
            $actionContainer: $('#action-buttons-container')
        };
    },

    bindEvents: function () {
        /** 
         * Usamos delegación de eventos porque los botones se inyectan 
         * dinámicamente en renderActions()
         */
        this.dom.$actionContainer.on('click', '#btn-export-pdf', (e) => {
            e.preventDefault();
            this.printPurchaseOrder();
        });

        // Eventos para otras acciones (Tránsito, Cancelar)
        this.dom.$actionContainer.on('click', '.action-btn', (e) => {
            const action = $(e.currentTarget).data('action');
            this.handleStatusChange(action);
        });
    },

    /**
     * Maneja el cambio de estado solicitando un comentario (Consistencia con Index)
     */
    handleStatusChange: function (action) {
        const config = {
            transit: { 
                title: '¿Confirmar Tránsito?', 
                text: 'Marcar como enviada por el proveedor.', 
                url: 'transit', 
                icon: 'info',
                placeholder: 'Ej: Guía de rastreo o confirmación telefónica...'
            },
            cancel: { 
                title: '¿Anular Orden de Compra?', 
                text: 'Esta acción es irreversible y liberará los saldos.', 
                url: 'cancel', 
                icon: 'warning',
                placeholder: 'Indique obligatoriamente el motivo de la anulación...'
            }
        };

        const color = action === 'transit' ? '#ffbc0a' : '#dc3545';

        const active = config[action];
        if (!active) return;

        // Usamos Swal directamente para aprovechar el campo 'input'
        Swal.fire({
            title: active.title,
            text: active.text,
            input: 'textarea', // <--- Inyectamos el campo de texto en el modal
            icon: active.icon,
            inputPlaceholder: active.placeholder,            
            showCancelButton: true,
            confirmButtonColor: color,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const comentario = result.value;

                Sys_Core.Net.post({
                    url: `${this.config.apiBase}/${this.state.id}/${active.url}`,
                    payload: { comentario: comentario },
                    $btn: $(`.action-btn[data-action="${action}"]`),
                    onDone: (res) => {
                        Sys_Core.UI.notify(res.message, 'success');
                        this.loadData(); // Refrescar UI
                    }
                });
            }
        });
    },

    printPurchaseOrder: function() {
        const purchaseOrderId = this.state.id;
        if (!purchaseOrderId) return;

        // Notificación de cortesía
        Sys_Core.UI.notify('Generando documento oficial...', 'info');

        Sys_Core.Net.downloadPdf({
            url: `${this.config.apiBase}/${purchaseOrderId}/pdf`,
            filename: `Orden_Compra_${purchaseOrderId}.pdf`
        });
    },

    loadData: function () {
        Sys_Core.Net.get({
            url: `${this.config.apiBase}/${this.state.id}`,
            onSuccess: (res) => {
                this.state.data = res.data;
                this.renderUI();
            }
        });
    },

    renderUI: function () {
        const d = this.state.data;
        this.dom.$lblId.text(d.idcompra);
        this.dom.$lblReqId.text(`#${d.requisicionid}`);
        this.dom.$lblProveedor.text(d.proveedor_nombre);
        this.dom.$lblAlmacen.text(d.almacen_nombre);
        this.dom.$lblObs.text(d.observaciones || 'Sin observaciones.');
        
        this.dom.$lblSubtotal.text(Sys_Core.Format.toCurrency(d.subtotal));
        this.dom.$lblIva.text(Sys_Core.Format.toCurrency(d.iva));
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(d.total));
        this.dom.$lblMoneda.text(`${d.moneda} (T.C. ${d.tipo_cambio})`);

        // 2. Lógica de OCs Relacionadas (Trazabilidad)
        const $cardRelated = $('#card-related-pos');
        const $listRelated = $('#list-related-pos');

        this.renderStatus(d.estatus);
        this.renderItems(d.items);
        this.renderActions(d.estatus);

        if (d.related_pos && d.related_pos.length > 0) {
            $cardRelated.show();
            $listRelated.empty();
            
            d.related_pos.forEach(pos => {
                $listRelated.append(`
                    <a href="${Sys_Core.Config.baseUrl}/com_orden/read/${pos.idcompra}" class="list-group-item list-group-item-action border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-primary">OC #${pos.idcompra}</div>
                                <small class="text-muted">${Sys_Core.Format.toDate(pos.created_at)}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-dark">${Sys_Core.Format.toCurrency(pos.total)}</div>
                                <span class="badge bg-soft-secondary text-muted fs-10">${pos.estatus.toUpperCase()}</span>
                            </div>
                        </div>
                    </a>
                `);
            });
        } else {
            $cardRelated.hide();
        }
    },

    renderStatus: function (status) {
        const clases = { 
            'emitida': 'text-bg-primary', 
            'en_transito': 'text-bg-warning',
            'cerrada': 'text-bg-success',
            'cancelada': 'text-bg-danger'
        };
        this.dom.$lblEstatus.removeClass().addClass(`badge ${clases[status] || 'bg-secondary'} ms-3 text-capitalize`).text(status.replace('_', ' '));
    },

    renderItems: function (items) {
        this.dom.$tblItems.empty();
        items.forEach(item => {
            const progress = item.progreso_recepcion;
            const barColor = progress >= 100 ? 'bg-success' : (progress > 0 ? 'bg-warning' : 'bg-light');

            this.dom.$tblItems.append(`
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold text-dark">${item.cve_articulo}</div>
                        <small class="text-muted">${item.descripcion}</small>
                    </td>
                    <td class="text-center" style="width: 200px;">
                        <div class="d-flex justify-content-between mb-1 fs-11">
                            <span class="fw-bold">${item.cantidad_recibida} / ${item.cantidad}</span>
                            <span class="text-muted">${progress}%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar ${barColor}" style="width: ${progress}%"></div>
                        </div>
                    </td>
                    <td class="text-end">${Sys_Core.Format.toCurrency(item.costo_unitario)}</td>
                    <td class="text-end text-danger">-${Sys_Core.Format.toCurrency(item.descuento_partida)}</td>
                    <td class="text-end fw-bold pe-4">${Sys_Core.Format.toCurrency(item.subtotal_partida)}</td>
                </tr>
            `);
        });
    },

    renderActions: function (status) {
        let html = `<button class="btn btn-light" data-redirect="com_orden"><i class="ri-arrow-left-line"></i> Volver</button>`;
        html += `<button class="btn btn-outline-danger" id="btn-export-pdf"><i class="ri-file-pdf-line"></i> PDF</button>`;

        if (status === 'emitida') {
            html += `<button class="btn btn-warning action-btn" data-action="transit"><i class="ri-truck-line"></i> Marcar En Tránsito</button>`;
            html += `<button class="btn btn-soft-danger action-btn" data-action="cancel"><i class="ri-close-line"></i> Cancelar OC</button>`;
        } else if (status === 'en_transito' || status === 'recibida_parcial') {
            html += `<button class="btn btn-success" data-redirect="inv_recepcion/create?oc_id=${this.state.id}"><i class="ri-inbox-archive-line"></i> Recibir Mercancía</button>`;
        }

        this.dom.$actionContainer.html(html);
    }
};

$(document).ready(() => PurchaseOrderRead.init());