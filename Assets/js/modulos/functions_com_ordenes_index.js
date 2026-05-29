/**
 * Controlador de Bandeja de Órdenes de Compra (ERP Interno)
 * Integrado al motor central Sys_Core
 */

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
        this.initKPIs(); // --- INICIO AGREGADO: Animación de KPIs en carga ---
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
            this.initKPIs(); // --- INICIO AGREGADO: Sincronizar KPIs al aplicar filtros ---
        });

        // Acciones Inline
        this.dom.$table.on('click', '.action-inline', (e) => this.showInlineAction(e));
        this.dom.$table.on('click', '.btn-confirmar-inline', (e) => this.submitInlineAction(e));
        this.dom.$table.on('click', '.btn-cancelar-inline', () => $('.fila-accion-inline').remove());
    },

    // --- INICIO AGREGADO: Integración de KPIs Dinámicos con Sys_Core ---
    initKPIs: function() {
        const mapping = {
            'emitida': 'kpi-emitidas',
            'en_transito': 'kpi-transito',
            'recibida_parcial': 'kpi-parciales',
            'cerrada': 'kpi-cerradas'
        };

        // El motor Net.get y el refrescador del core calculan y animan todo
        Sys_Core.UI.Dashboard.refreshKPIs(
            `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders/kpis`, // Endpoint de KPIs
            mapping
        );
    },
    // --- FIN AGREGADO ---

    initDataTable: function () {
        const token = Sys_Core.Auth.getCookie('mrp_token');

        this.state.dataTable = this.dom.$table.DataTable({
            ajax: {
                url: this.config.endpoint,
                type: 'GET',
                data: (d) => {
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
                // 1. CORRECCIÓN: Forzamos ordenamiento numérico usando el parámetro 'type'
                { 
                    data: "idcompra", 
                    type: "num", // Forzar tipo numérico nativo en el motor de ordenación
                    render: function (d, type) {
                        if (type === 'display') {
                            return `<span class="fw-bold">#${d}</span>`;
                        }
                        return d; // Retorna el entero crudo para ordenación y filtros
                    }
                },
                { data: "created_at", render: (d) => Sys_Core.Format.toDate(d) },
                { data: "proveedor_nombre", render: (d) => `<span class="text-primary fw-medium">${d}</span>` },
                // 2. CORRECCIÓN PREVENTIVA: Aplicamos la misma regla para el folio de la requisición
                { 
                    data: "requisicionid", 
                    type: "num",
                    render: function (d, type) {
                        if (type === 'display') {
                            return `<a href="${Sys_Core.Config.baseUrl}/com_requisicion/read/${d}" class="badge bg-soft-info text-info">REQ #${d}</a>`;
                        }
                        return d;
                    }
                },
                { data: "total", className: "text-end fw-bold", render: (d) => Sys_Core.Format.toCurrency(d) },
                { data: "estatus", className: "text-center", render: (d) => this.renderStatusBadge(d) },
                { data: null, className: "text-end", render: (d, t, row) => this.renderActions(row) }
            ],
            order: [[0, 'desc']], // CORRECCIÓN: Ordenar por ID de compra (desc) de forma idéntica
            responsive: true
        });
    },

    renderStatusBadge: function (status) {
        const clases = { 
            'emitida': 'bg-primary-subtle text-primary', 
            'en_transito': 'bg-warning-subtle text-warning',
            'recibida_parcial': 'bg-info-subtle text-info',
            'cerrada': 'bg-success-subtle text-success',
            'cancelada': 'bg-danger-subtle text-danger'
        };
        return `<span class="badge ${clases[status] || 'bg-secondary'} px-3 py-2 text-capitalize fs-12">${status.replace('_', ' ')}</span>`;
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
     * Muestra el formulario de comentario inline
     */
    showInlineAction: function (e) {
        const $btn = $(e.currentTarget);
        const id = $btn.data('id');
        const actionKey = $btn.data('accion');
        const actionConfig = this.config.actionDictionary[actionKey];

        $('.fila-accion-inline').remove();

        // --- INICIO MODIFICACIÓN: Soporte de Modo Oscuro en Plantillas Inline ---
        // Reemplazamos la clase text-dark por text-body para asegurar legibilidad en Dark Mode
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
                            <h6 class="mb-1 fw-bold text-body">${actionConfig.titulo} - OC #${id}</h6>
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
        // --- FIN MODIFICACIÓN ---

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

        const targetUrl = `${this.config.endpoint}/${id}${actionConfig.suffix}`;

        Sys_Core.Net.post({
            url: targetUrl,
            method: actionConfig.method,
            payload: { comentario: comentario },
            $btn: $btn,
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'success');
                $filaInline.remove();
                
                this.state.dataTable.ajax.reload(null, false);
                this.initKPIs(); // --- INICIO MODIFICACIÓN: Refrescar KPIs tras acción exitosa ---
            }
        });
    }
};

$(document).ready(() => PurchaseOrderIndex.init());