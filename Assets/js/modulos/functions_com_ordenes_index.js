const PurchaseOrderIndex = {
    config: {
        endpoint: `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders`,
        // Acciones RESTful para la OC
        actionDictionary: {
            'cancel':  { titulo: 'Cancelar OC', clase: 'danger', icon: 'ri-close-line', method: 'POST', suffix: '/cancel' },
            'transit': { titulo: 'En Tránsito', clase: 'warning', icon: 'ri-truck-line', method: 'POST', suffix: '/transit' }
        }
    },

    state: { dataTable: null },
    dom: {},

    init: function () {
        this.cacheDOM();
        this.initDataTable();
        this.bindEvents();
    },

    cacheDOM: function () {
        this.dom = {
            $table: $('#tblOrders'),
            $filterForm: $('#formFiltrosOC')
        };
    },

    bindEvents: function () {
        // Filtros
        this.dom.$filterForm.on('submit', (e) => {
            e.preventDefault();
            this.state.dataTable.ajax.reload();
        });

        // Acciones Inline
        this.dom.$table.on('click', '.action-inline', (e) => this.showInlineAction(e));
        this.dom.$table.on('click', '.btn-confirmar-inline', (e) => this.submitInlineAction(e));
        this.dom.$table.on('click', '.btn-cancelar-inline', () => $('.fila-accion-inline').remove());
    },

    initDataTable: function () {
        const token = localStorage.getItem('mrp_token');

        this.state.dataTable = this.dom.$table.DataTable({
            ajax: {
                url: this.config.endpoint,
                type: 'GET',
                data: (d) => {
                    // Inyectamos los filtros del formulario a la petición de la API
                    const formData = this.dom.$filterForm.serializeArray();
                    formData.forEach(item => d[item.name] = item.value);
                },
                dataSrc: "data",
                beforeSend: function (request) {
                    if (token) {
                        request.setRequestHeader("Authorization", `Bearer ${token}`);
                    }
                },
            },
            columns: [
                { data: "idcompra", render: (d) => `<span class="fw-bold">#${d}</span>` },
                { data: "created_at", render: (d) => Sys_Core.Format.toDate(d) },
                { data: "proveedor_nombre", render: (d) => `<span class="text-primary fw-medium">${d}</span>` },
                { data: "requisicionid", render: (d) => `<a href="${Sys_Core.Config.baseUrl}/com_requisicion/read/${d}" class="badge bg-soft-info text-info">REQ #${d}</a>` },
                { data: "total", className: "text-end fw-bold", render: (d) => Sys_Core.Format.toCurrency(d) },
                { data: "estatus", className: "text-center", render: (d) => this.renderStatusBadge(d) },
                { data: null, className: "text-end", render: (d, t, row) => this.renderActions(row) }
            ],
            order: [[1, 'desc']],
            responsive: true
        });
    },

    renderStatusBadge: function (status) {
        const clases = { 
            'emitida': 'text-bg-primary', 
            'en_transito': 'text-bg-warning',
            'cerrada': 'text-bg-success',
            'cancelada': 'text-bg-danger'
        };
        return `<span class="badge ${clases[status] || 'bg-secondary'} px-3 py-2 text-capitalize">${status.replace('_', ' ')}</span>`;
    },

    renderActions: function (row) {
        let html = `
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm" data-redirect="com_orden/read/${row.idcompra}"><i class="ri-eye-line"></i> Ver</button>
        `;

        if (row.estatus === 'emitida') {
            html += `
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><button class="dropdown-item action-inline" data-id="${row.idcompra}" data-accion="transit"><i class="ri-truck-line text-warning"></i> Marcar en Tránsito</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger action-inline" data-id="${row.idcompra}" data-accion="cancel"><i class="ri-close-line"></i> Cancelar OC</button></li>
                </ul>
            `;
        }
        html += `</div>`;
        return html;
    },

    /**
     * Muestra el formulario de comentario inline (Copiado de RequisitionIndex)
     */
    showInlineAction: function (e) {
        const $btn = $(e.currentTarget);
        const id = $btn.data('id');
        const actionKey = $btn.data('accion');
        const actionConfig = this.config.actionDictionary[actionKey];

        // Limpiar filas abiertas previas
        $('.fila-accion-inline').remove();

        const html = `
            <tr class="fila-accion-inline bg-soft-${actionConfig.clase} animate__animated animate__fadeIn">
                <td colspan="7" class="p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-${actionConfig.clase} text-white rounded-circle fs-20">
                                    <i class="${actionConfig.icon}"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold text-dark">${actionConfig.titulo} - OC #${id}</h6>
                            <input type="text" class="form-control form-control-sm txt-comentario-inline border-${actionConfig.clase}" 
                                   placeholder="Escriba un comentario para el historial de auditoría...">
                        </div>
                        <div class="flex-shrink-0 ms-3 d-flex gap-2">
                            <button class="btn btn-${actionConfig.clase} btn-sm fw-bold btn-confirmar-inline shadow-sm" 
                                    data-id="${id}" data-accion="${actionKey}">
                                <i class="ri-check-line align-middle"></i> Confirmar
                            </button>
                            <button class="btn btn-light btn-sm btn-cancelar-inline">
                                <i class="ri-close-line align-middle"></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `;

        $btn.closest('tr').after(html);
        $('.txt-comentario-inline').focus();
    },

    /**
     * Ejecuta la acción vía API
     */
    submitInlineAction: function (e) {
        const $btn = $(e.currentTarget);
        const id = $btn.data('id');
        const actionKey = $btn.data('accion');
        const actionConfig = this.config.actionDictionary[actionKey];
        const $filaInline = $btn.closest('.fila-accion-inline');
        const comentario = $filaInline.find('.txt-comentario-inline').val();

        // Construcción de URL RESTful
        const targetUrl = `${this.config.endpoint}/${id}${actionConfig.suffix}`;

        Sys_Core.Net.post({
            url: targetUrl,
            method: actionConfig.method,
            payload: { comentario: comentario },
            $btn: $btn,
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'success');
                $filaInline.remove();
                
                // Recargar el DataTable manteniendo la posición
                this.state.dataTable.ajax.reload(null, false);
                
                // Si tienes KPIs de compra, aquí los refrescarías
                // this.initKPIs(); 
            }
        });
    }
};

$(document).ready(() => PurchaseOrderIndex.init());