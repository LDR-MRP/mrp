const PurchaseOrderRead = {
    config: { apiBase: `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders` },
    state: { id: null, data: null },
    dom: {},

    init: function () {
        this.extractId();
        this.cacheDOM();
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
            'emitida': 'bg-soft-primary text-primary', 
            'en_transito': 'bg-soft-warning text-warning',
            'cerrada': 'bg-soft-success text-success',
            'cancelada': 'bg-soft-danger text-danger'
        };
        this.dom.$lblEstatus.removeClass().addClass(`badge ${clases[status] || 'bg-secondary'} ms-2`).text(status.toUpperCase());
    },

    renderItems: function (items) {
        this.dom.$tblItems.empty();
        items.forEach(item => {
            this.dom.$tblItems.append(`
                <tr>
                    <td class="ps-4"><b>${item.cve_articulo}</b><br><small>${item.descripcion}</small></td>
                    <td class="text-center">${parseFloat(item.cantidad)}</td>
                    <td class="text-end">${Sys_Core.Format.toCurrency(item.costo_unitario)}</td>
                    <td class="text-end text-danger">-${Sys_Core.Format.toCurrency(item.descuento_partida)}</td>
                    <td class="text-end fw-bold">${Sys_Core.Format.toCurrency(item.subtotal_partida)}</td>
                </tr>
            `);
        });
    },

    renderActions: function (status) {
        let html = `<button class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;
        html += `<button class="btn btn-outline-danger"><i class="ri-file-pdf-line"></i> PDF</button>`;

        if (status === 'emitida') {
            html += `<button class="btn btn-warning action-btn" data-action="transit"><i class="ri-truck-line"></i> Marcar En Tránsito</button>`;
            html += `<button class="btn btn-soft-danger action-btn" data-action="cancel"><i class="ri-close-line"></i> Cancelar OC</button>`;
        } else if (status === 'en_transito') {
            html += `<button class="btn btn-success" data-redirect="inv_recepcion/create?oc_id=${this.state.id}"><i class="ri-inbox-archive-line"></i> Recibir Mercancía</button>`;
        }

        this.dom.$actionContainer.html(html);
    }
};

$(document).ready(() => PurchaseOrderRead.init());