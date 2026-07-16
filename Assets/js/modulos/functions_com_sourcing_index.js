/**
 * MRP System - Panel de Negociaciones (Real Data Integration)
 * @module SourcingIndex
 */

const SourcingIndex = {

    config: {
        endpoints: {
            base: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/events`,
            // El endpoint de pendientes lo usamos para el KPI de "Items sin Negociar"
            pending: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/pending-items`
        }
    },

    state: {
        events: []
    },

    dom: {},

    init: function () {
        Sys_Core.Auth.validateSession();
        this.cacheDOM();
        this.bindEvents();
        this.loadData();
    },

    cacheDOM: function () {
        this.dom = {
            $tbody: $('#tbodyNegociaciones'),
            $btnNew: $('#btn-nueva-negociacion'),
            // Referencias a los contenedores de KPIs para animación
            $kpiActivas: 'kpi-activas',
            $kpiPendientes: 'kpi-pendientes',
            $kpiAhorro: 'kpi-ahorro',
            $kpiDictamen: 'kpi-dictamen'
        };
    },

    bindEvents: function () {
        const self = this;
        
        // Redirección al Inbox (Picker)
        this.dom.$btnNew.on('click', () => {
            Sys_Core.Navigation.to('com_sourcing/inbox');
        });

        // Ver detalle de una negociación
        this.dom.$tbody.on('click', '.btn-view-event', function() {
            const id = $(this).data('id');
            Sys_Core.Navigation.to(`com_sourcing/detail/${id}`);
        });
        
        // Filtro rápido por estatus
        $('#filter-status').on('change', function() {
            const status = $(this).val();
            self.renderTable(status);
        });
    },

    /**
     * Carga de datos reales desde la API
     */
    loadData: function () {
        const self = this;
        
        // 1. Cargar Negociaciones
        Sys_Core.Net.get({
            url: this.config.endpoints.base,
            onSuccess: function (res) {
                self.state.events = res.data;
                self.renderTable();
                self.calculateAndRenderKPIs();
            }
        });

        // 2. Cargar Pendientes (Solo para alimentar el contador del KPI)
        Sys_Core.Net.get({
            url: this.config.endpoints.pending,
            onSuccess: function (res) {
                Sys_Core.UI.Dashboard.animateCounter(self.dom.$kpiPendientes, res.data.length);
            }
        });
    },

    /**
     * Calcula métricas basadas en el set de datos actual
     */
    calculateAndRenderKPIs: function() {
        const activeCount = this.state.events.filter(e => e.estatus_evento === 'ABIERTO').length;
        const dictamenCount = this.state.events.filter(e => e.estatus_evento === 'DICTAMEN').length;
        
        // Animamos los contadores
        Sys_Core.UI.Dashboard.animateCounter(this.dom.$kpiActivas, activeCount);
        Sys_Core.UI.Dashboard.animateCounter(this.dom.$kpiDictamen, dictamenCount);
        
        // El ahorro mensual vendría de un endpoint de reporte, por ahora lo dejamos en 0 
        // o lo animamos si tuvieras el dato en el JSON.
        Sys_Core.UI.Dashboard.animateCounter(this.dom.$kpiAhorro, 0, true);
    },

    /**
     * Renderizado de la tabla con lógica de Compliance
     */
    renderTable: function (filterStatus = '') {
        let html = '';
        const filteredData = filterStatus 
            ? this.state.events.filter(e => e.estatus_evento === filterStatus) 
            : this.state.events;

        if (filteredData.length === 0) {
            html = `<tr><td colspan="7" class="text-center p-5 text-muted opacity-50">
                        <i class="ri-search-line fs-1 d-block mb-2"></i>
                        No se encontraron negociaciones con los filtros aplicados.
                    </td></tr>`;
        } else {
            filteredData.forEach(ev => {
                // Lógica de Compliance: Semáforo de ofertas (ADN Senior)
                const complianceClass = ev.total_cotizaciones >= 3 ? 'text-success' : 'text-warning';
                const complianceIcon = ev.total_cotizaciones >= 3 ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill';
                
                // Barra de progreso (Meta: 3 cotizaciones mínimo)
                const progressPct = Math.min((ev.total_cotizaciones / 3) * 100, 100);

                // Iniciales para el avatar del comprador
                const initials = ev.comprador_nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

                html += `
                <tr class="animate__animated animate__fadeIn">
                    <td class="ps-4">
                        <div class="d-flex align-items-center">
                            <i class="ri-checkbox-blank-circle-fill fs-8 me-2 text-primary"></i>
                            <a href="javascript:void(0);" class="fw-bold btn-view-event" data-id="${ev.id}">${ev.folio}</a>
                        </div>
                    </td>
                    <td>
                        <span class="fw-semibold text-body">${ev.titulo}</span>
                        <br><small class="text-muted fs-11">Abierto hace ${ev.dias_abierto} días</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xxs flex-shrink-0 me-2">
                                <span class="avatar-title rounded-circle bg-light text-muted border border-light-subtle fs-10 fw-bold">
                                    ${initials}
                                </span>
                            </div>
                            <span class="fw-medium text-muted">${ev.comprador_nombre}</span>
                        </div>
                    </td>
                    <td class="text-center" style="min-width: 130px;">
                        <div class="d-flex align-items-center gap-2 mb-1 justify-content-center">
                            <i class="${complianceIcon} ${complianceClass} fs-14"></i>
                            <span class="fw-bold fs-12">${ev.total_cotizaciones}</span>
                            <span class="text-muted fs-11">/ ${ev.total_partidas} Partidas</span>
                        </div>
                        <div class="progress progress-sm" style="height: 4px; width: 80px; margin: 0 auto;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: ${progressPct}%"></div>
                        </div>
                    </td>
                    <td class="text-muted fs-12">
                        <i class="ri-calendar-event-line align-middle me-1"></i>${Sys_Core.Format.toDate(ev.created_at)}
                    </td>
                    <td>${this.renderStatusBadge(ev.estatus_evento)}</td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-dark waves-effect waves-light btn-view-event" data-id="${ev.id}">
                            <i class="ri-external-link-line align-bottom"></i> Gestionar
                        </button>
                    </td>
                </tr>`;
            });
        }
        this.dom.$tbody.html(html);
    },

    renderStatusBadge: function (status) {
        const config = {
            'ABIERTO':   'bg-primary-subtle text-primary',
            'DICTAMEN':  'bg-warning-subtle text-warning',
            'ADJUDICADO': 'bg-success-subtle text-success',
            'CANCELADO': 'bg-danger-subtle text-danger'
        };
        const className = config[status] || 'bg-secondary-subtle text-secondary';
        return `<span class="badge ${className} px-3 py-1 rounded-pill text-uppercase fs-10 ls-1">${status}</span>`;
    }
};

$(document).ready(() => SourcingIndex.init());