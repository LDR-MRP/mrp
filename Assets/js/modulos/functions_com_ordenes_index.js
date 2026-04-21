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
        this.state.dataTable = this.dom.$table.DataTable({
            ajax: {
                url: this.config.endpoint,
                type: 'GET',
                data: (d) => {
                    // Inyectamos los filtros del formulario a la petición de la API
                    const formData = this.dom.$filterForm.serializeArray();
                    formData.forEach(item => d[item.name] = item.value);
                },
                dataSrc: "data"
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
            order: [[0, 'desc']],
            responsive: true
        });
    },

    renderStatusBadge: function (status) {
        const clases = { 
            'emitida': 'bg-soft-primary text-primary', 
            'en_transito': 'bg-soft-warning text-warning',
            'cerrada': 'bg-soft-success text-success',
            'cancelada': 'bg-soft-danger text-danger'
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

    // Reutilizamos la lógica de showInlineAction y submitInlineAction del RequisitionIndex...
    // (Ajustando las URLs al config.endpoint de este objeto)
    // ...
};

$(document).ready(() => PurchaseOrderIndex.init());