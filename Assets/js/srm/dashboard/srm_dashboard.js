/**
 * Controlador de Resumen / Dashboard de Proveedores (SRM)
 * Utiliza e integra el motor central Sys_Core
 */

const SrmDashboard = {

    init: function() {
        Sys_Core.Auth.validateSession('VENDOR');

        this.renderUserData();
        this.loadMetrics();
    },

    /**
     * Dibuja la información del proveedor en la UI (Header / Saludo)
     */
    renderUserData: function() {
        // --- INICIO MODIFICACIÓN: El Core ya hidrata el TopBar de forma automática.
        // Aquí solo pintamos el saludo exclusivo de la vista principal del Dashboard.
        const payload = Sys_Core.Auth.decodeJWT();
        const user = payload ? payload.data : null;

        if (user && $('#lbl-welcome-user').length) {
            $('#lbl-welcome-user').text(user.nombre);
        }
        // --- FIN MODIFICACIÓN ---
    },

    /**
     * Consume la API utilizando el motor de red Sys_Core
     */
    loadMetrics: function() {
        // endpoint ficticio o tu controlador /api/v1/srm/dashboard
        Sys_Core.Net.get({
            url: base_url + '/api/v1/srm/dashboard/summary',
            silent: false,
            onSuccess: (response) => {
                if (response.status) {
                    const metrics = response.data;
                    
                    // Actualizamos KPIs con animación usando el motor de Sys_Core
                    Sys_Core.UI.Dashboard.animateCounter('kpi-ordenes', metrics.ordenes_activas);
                    Sys_Core.UI.Dashboard.animateCounter('kpi-facturas', metrics.facturas_proceso);
                    
                    // KPI Financiero formateado
                    $('#kpi-pagos').text(Sys_Core.Format.toCurrency(metrics.monto_pendiente));
                    
                    // KPI Compliance
                    this.renderComplianceKpi(metrics.compliance);
                    
                    // Renderizar actividad reciente
                    this.renderActivity(metrics.recientes);
                }
            },
            onError: () => {
                Sys_Core.UI.notify("Error al sincronizar indicadores en tiempo real.", "error");
            }
        });
    },

    renderComplianceKpi: function(compliance) {
        const $el = $('#kpi-compliance');
        const $icon = $('#kpi-compliance-icon');
        
        $el.text(compliance.status_text);
        
        if (compliance.status === 'ACTIVE') {
            $icon.removeClass().addClass('avatar-title bg-success-subtle text-success rounded-circle fs-3')
                 .html("<i class='ri-checkbox-circle-line'></i>");
        } else {
            $icon.removeClass().addClass('avatar-title bg-warning-subtle text-warning rounded-circle fs-3')
                 .html("<i class='ri-time-line'></i>");
        }
    },

    renderActivity: function(actividades) {
        const $list = $('#recent-activity-list');
        $list.empty();

        if (!actividades || actividades.length === 0) {
            $list.html("<div class='text-center text-muted py-4'>No hay actividades recientes registradas.</div>");
            return;
        }

        actividades.forEach(act => {
            const html = `
                <div class="acitivity-item d-flex mb-3">
                    <div class="flex-shrink-0">
                        <div class="acitivity-avatar">
                            <span class="avatar-title rounded-circle bg-soft-primary text-primary fs-14">
                                <i class="ri-notification-badge-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1 fs-13 text-dark">${act.evento}</h6>
                        <p class="text-muted fs-12 mb-0">${act.detalle}</p>
                        <small class="text-muted fs-10">${Sys_Core.Format.toDate(act.created_at)}</small>
                    </div>
                </div>`;
            $list.append(html);
        });
    }
};

$(document).ready(function() {
    SrmDashboard.init();
});