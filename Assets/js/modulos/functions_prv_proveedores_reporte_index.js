/**
 * Controlador de Reporte Analítico de Onboarding (CEO)
 * Integrado al motor central Sys_Core
 */

const SrmReporteCEO = {

    ui: {
        tblBody: '#tbodyReporteCEO',
        formFiltro: '#formFiltrosReporte',
        selPlanta: '#sel-planta-reporte',
        btnLimpiar: '#btn-limpiar-reporte',
        kpiCompletos: 'kpi-completos',
        kpiOnboarding: 'kpi-onboarding-total',
        kpiPendientesL2: 'kpi-pendientes-l2',
        kpiIncompletos: 'kpi-incompletos'
    },

    init: function() {
        Sys_Core.Auth.validateSession();
        this.loadPlantsCatalog();
        this.loadReportData();
        this.bindEvents();
    },

    bindEvents: function() {
        // Evento Submit para aplicar filtros
        $(this.ui.formFiltro).on('submit', (e) => {
            e.preventDefault();
            this.loadReportData();
        });

        // Evento Reset para limpiar filtros y recargar
        $(this.ui.btnLimpiar).on('click', () => {
            Sys_Core.UI.clearForm(this.ui.formFiltro);
            this.loadReportData();
        });
    },

    /**
     * Carga de forma dinámica el catálogo de plantas de LDR usando el core
     */
    loadPlantsCatalog: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/catalogs/plants`, // Asegura apuntar a tu catálogo real
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    Sys_Core.UI.fillSelect(this.ui.selPlanta, response.data, {
                        valueField: 'idplanta',
                        textField: 'nombre_planta', // ej. "Tlajomulco 1", "Lagos de Moreno"
                        placeholder: '— Ver todas las plantas de LDR —'
                    });
                }
            }
        });
    },

    /**
     * Consume la API utilizando el motor de red Sys_Core
     */
    loadReportData: function() {
        Sys_Core.UI.toggleLoader('.page-content', true);

        // Extraemos los filtros del formulario
        const plantaid = $(this.ui.selPlanta).val() || '';
        const targetUrl = `${base_url}/api/v1/suppliers/reports/onboarding?plantaid=${plantaid}`;

        Sys_Core.Net.get({
            url: targetUrl,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.calculateKpiMetrics(response.data);
                    this.renderReportTable(response.data);
                }
            },
            onError: () => {
                Sys_Core.UI.notify("Error al sincronizar el reporte en tiempo real.", "error");
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    /**
     * Calcula de forma síncrona en el cliente las métricas para los 4 KPIs
     */
    calculateKpiMetrics: function(data) {
        let completos = 0;
        let onboarding = 0;
        let pendientesL2 = 0;
        let incompletos = 0;

        data.forEach(p => {
            const status = p.estatus_onboarding.toUpperCase();
            const exp = p.expediente;
            const sat = p.satelites;

            // 1. Contador de Completados (Aprobado al 100%)
            if (status === 'APROBADO' && exp.porcentaje === 100) {
                completos++;
            }

            // 2. Contador de En Onboarding
            if (status === 'PROSPECTO' || status === 'EN REVISION') {
                onboarding++;
            }

            // 3. Pendientes de Auditoría L2
            if (status === 'EN REVISION') {
                pendientesL2++;
            }

            // 4. Incompletos (Falta alguna tabla satélite crítica)
            if (!sat.bancos || sat.bancos === 'SIN_REGISTRAR' || !sat.direccion || !sat.contacto) {
                incompletos++;
            }
        });

        // Animamos los contadores usando el motor nativo de tu Core
        Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiCompletos, completos);
        Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiOnboarding, onboarding);
        Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiPendientesL2, pendientesL2);
        Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiIncompletos, incompletos);
    },

    /**
     * Dibuja dinámicamente las filas de la tabla ejecutiva
     */
    renderReportTable: function(data) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty();

        if (!data || data.length === 0) {
            $tbl.html('<tr><td colspan="8" class="text-center text-muted py-4">No hay información de proveedores registrada.</td></tr>');
            return;
        }

        data.forEach(p => {
            // A. Barra de progreso del Expediente Digital (n/n + Progress Bar)
            const exp = p.expediente;
            const progressColor = exp.porcentaje === 100 ? 'bg-success' : (exp.porcentaje > 40 ? 'bg-warning' : 'bg-danger');
            
            const progressBarHtml = `
                <div class="d-flex align-items-center gap-2">
                    <div class="progress progress-sm flex-grow-1 bg-light" style="height: 6px;">
                        <div class="progress-bar ${progressColor}" role="progressbar" style="width: ${exp.porcentaje}%"></div>
                    </div>
                    <span class="fs-12 fw-bold text-body">${exp.aprobados}/${exp.requeridos} (${exp.porcentaje}%)</span>
                </div>
            `;

            // B. Mapeo de Badges para Datos Satélite (Anti-fuga de modo oscuro)
            const badgesSatelite = {
                bancos: this.getBankBadge(p.satelites.bancos),
                direccion: p.satelites.direccion 
                    ? `<span class="badge bg-success-subtle text-success fs-11">Registrada</span>` 
                    : `<span class="badge bg-danger-subtle text-danger fs-11">Faltante</span>`,
                contacto: p.satelites.contacto 
                    ? `<span class="badge bg-success-subtle text-success fs-11">Registrado</span>` 
                    : `<span class="badge bg-danger-subtle text-danger fs-11">Faltante</span>`,
                config: p.satelites.config_financiera 
                    ? `<span class="badge bg-success-subtle text-success fs-11">Configurada</span>` 
                    : `<span class="badge bg-warning-subtle text-warning fs-11">Pendiente</span>`
            };

            // C. Estatus de Onboarding General
            let statusBadge = '';
            const statusUpper = p.estatus_onboarding.toUpperCase();
            if (statusUpper === 'PROSPECTO') {
                statusBadge = `<span class="badge bg-light text-muted px-3 py-2 fs-11 text-capitalize">${p.estatus_onboarding}</span>`;
            } else if (statusUpper === 'EN REVISION') {
                statusBadge = `<span class="badge bg-warning-subtle text-warning px-3 py-2 fs-11 text-capitalize">${p.estatus_onboarding}</span>`;
            } else if (statusUpper === 'APROBADO') {
                statusBadge = `<span class="badge bg-success-subtle text-success px-3 py-2 fs-11 text-capitalize">${p.estatus_onboarding}</span>`;
            } else if (statusUpper === 'RECHAZADO') {
                statusBadge = `<span class="badge bg-danger-subtle text-danger px-3 py-2 fs-11 text-capitalize">${p.estatus_onboarding}</span>`;
            }

            const row = `
                <tr class="align-middle">
                    <td class="ps-4">
                        <h6 class="fs-13 mb-0 fw-bold text-body">${p.razon_social}</h6>
                        <small class="text-muted">${p.nombre_comercial}</small>
                    </td>
                    <td class="font-monospace fs-12 text-muted">${p.rfc}</td>
                    <td>${progressBarHtml}</td>
                    <td class="text-center">${badgesSatelite.bancos}</td>
                    <td class="text-center">${badgesSatelite.direccion}</td>
                    <td class="text-center">${badgesSatelite.contacto}</td>
                    <td class="text-center">${badgesSatelite.config}</td>
                    <td class="text-center pe-4">${statusBadge}</td>
                </tr>`;
            
            $tbl.append(row);
        });
    },

    getBankBadge: function(status) {
        if (!status || status === 'SIN_REGISTRAR') {
            return `<span class="badge bg-danger-subtle text-danger fs-11">Faltante</span>`;
        }
        if (status === 'PENDIENTE') {
            return `<span class="badge bg-warning-subtle text-warning fs-11">En Auditoría</span>`;
        }
        return `<span class="badge bg-success-subtle text-success fs-11">Aprobada</span>`;
    }
};

$(document).ready(() => SrmReporteCEO.init());