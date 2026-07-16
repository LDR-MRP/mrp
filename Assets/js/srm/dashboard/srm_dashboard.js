/**
 * Controlador de Resumen / Dashboard de Proveedores (SRM)
 * Integrado con la respuesta enriquecida del SupplierService
 */
const SrmDashboard = {

    init: function() {
        Sys_Core.Auth.validateSession('VENDOR');
        this.renderUserData();
        this.loadMetrics();
    },

    renderUserData: function() {
        const payload = Sys_Core.Auth.decodeJWT();
        const user = payload ? payload.data : null;
        if (user && $('#lbl-welcome-user').length) {
            // Usamos el nombre del payload del JWT
            $('#lbl-welcome-user').text(user.nombre);
        }
    },

    loadMetrics: function() {
        Sys_Core.Net.get({
            url: base_url + '/api/v1/srm/dashboard/summary',
            onSuccess: (response) => {
                if (response.status === 'success') {
                    const metrics = response.data;
                    
                    // 1. KPIs Numéricos con animación
                    Sys_Core.UI.Dashboard.animateCounter('kpi-ordenes', metrics.ordenes_activas);
                    Sys_Core.UI.Dashboard.animateCounter('kpi-facturas', metrics.facturas_proceso);
                    
                    // 2. KPI Financiero
                    $('#kpi-pagos').text(Sys_Core.Format.toCurrency(metrics.monto_pendiente));
                    
                    // 3. KPI de Compliance (Expediente)
                    this.renderComplianceKpi(metrics.compliance);
                    
                    // 4. Actividad Reciente (Pendiente de implementar en el Backend)
                    if (metrics.recientes) {
                        this.renderActivity(metrics.recientes);
                    }
                }
            },
            onError: (err) => {
                Sys_Core.UI.notify("Error al sincronizar indicadores de tablero.", "error");
            }
        });
    },

    /**
     * Gestiona la visualización del estatus de Onboarding/Compliance
     */
    renderComplianceKpi: function(compliance) {
        if (!compliance) return;

        const $el = $('#kpi-compliance');
        const $icon = $('#kpi-compliance-icon');
        const percentage = compliance.expediente.porcentaje;

        // Texto dinámico: Mostramos el estatus de onboarding y el porcentaje
        $el.text(`${compliance.estatus_onboarding} (${percentage}%)`);
        
        // Limpiamos clases previas
        $icon.removeClass('bg-success-subtle text-success bg-warning-subtle text-warning bg-danger-subtle text-danger');

        /**
         * Lógica de semaforización basada en el porcentaje y estatus operativo
         */
        if (percentage === 100 && compliance.estatus_operativo === 1) {
            // Caso: Todo listo y aprobado
            $icon.addClass('bg-success-subtle text-success')
                 .html("<i class='ri-checkbox-circle-line'></i>");
        } else if (percentage > 0 || compliance.satelites.bancos === 'PENDIENTE') {
            // Caso: En proceso de carga o validación
            $icon.addClass('bg-warning-subtle text-warning')
                 .html("<i class='ri-time-line'></i>");
        } else {
            // Caso: Crítico / Sin iniciar
            $icon.addClass('bg-danger-subtle text-danger')
                 .html("<i class='ri-error-warning-line'></i>");
        }
    },

    renderActivity: function(actividades) {
        const $list = $('#recent-activity-list');
        $list.empty();

        if (!actividades || actividades.length === 0) {
            $list.html(`
                <div class="text-center text-muted py-4">
                    <i class="ri-inbox-line fs-1 display-5"></i>
                    <p class="mt-2">No hay actividades recientes.</p>
                </div>`);
            return;
        }

        // Renderizado de lista (Se asume estructura de bitácora)
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
                        <h6 class="mb-1 fs-13">${act.evento}</h6>
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