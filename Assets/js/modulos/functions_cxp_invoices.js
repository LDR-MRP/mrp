/**
 * Controlador de la Bandeja de Conciliación de Facturas (CxP) - ERP Interno
 * Utiliza e integra el motor central Sys_Core
 */

const CxpInvoices = {

    ui: {
        tblBody: '#tbl-body-cxp-invoices',
        kpiCongeladas: 'kpi-congeladas',
        kpiAprobadas: 'kpi-aprobadas',
        kpiRechazadas: 'kpi-rechazadas',
        kpiVencidas: 'kpi-vencidas'
    },

    init: function() {
        // --- INICIO SEGURIDAD: Centralización de sesión administrativa ---
        Sys_Core.Auth.validateSession(); // Valida sesión interna (ERP)
        // --- FIN SEGURIDAD ---

        this.loadDashboardKpis();
        this.loadInvoicesList();
    },

    /**
     * Sincroniza los 4 KPIs del Dashboard de CxP llamando a la API
     */
    loadDashboardKpis: function() {
        // --- INICIO MODIFICACIÓN: Consumo del endpoint AccountsPayable ---
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/accounts-payable/invoices/kpis`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    const kpis = response.data;
                    
                    // Animamos los contadores numéricos usando el motor nativo de tu Core
                    Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiCongeladas, kpis.congeladas);
                    Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiAprobadas, kpis.aprobadas);
                    Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiRechazadas, kpis.rechazadas);
                    Sys_Core.UI.Dashboard.animateCounter(this.ui.kpiVencidas, kpis.vencidas);
                }
            }
        });
        // --- FIN MODIFICACIÓN ---
    },

    /**
     * Carga el listado de facturas en la tabla de CxP
     */
    loadInvoicesList: function() {
        Sys_Core.UI.toggleLoader('.page-content', true);

        // --- INICIO MODIFICACIÓN: Consumo del listado AccountsPayable ---
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/accounts-payable/invoices`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderInvoicesTable(response.data);
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
        // --- FIN MODIFICACIÓN ---
    },

    /**
     * Dibuja dinámicamente las filas de la tabla de facturas
     */
    renderInvoicesTable: function(invoices) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty();

        if (!invoices || invoices.length === 0) {
            $tbl.html('<tr><td colspan="7" class="text-center text-muted py-4">No hay facturas cargadas en el sistema.</td></tr>');
            return;
        }

        invoices.forEach(inv => {
            let badge = '';
            let actionBtn = '';
            const statusVal = parseInt(inv.estatus_validacion);

            // Mapeo de estatus de validación con clases nativas de Velzon
            if (statusVal === 0) {
                badge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-11 fw-medium"><i class="ri-time-line align-middle me-1"></i> Retenida (3-Way)</span>`;
                // Si está congelada, habilitamos el botón de aprobación manual (Override)
                actionBtn = `
                    <button type="button" onclick="CxpInvoices.openOverrideModal(${inv.id}, '${inv.serie_folio}')" class="btn btn-soft-success btn-sm fw-medium shadow-none">
                        <i class="ri-check-line align-middle"></i> Liberar Pago
                    </button>`;
            } else if (statusVal === 1) {
                badge = `<span class="badge bg-success-subtle text-success px-2 py-1 fs-11 fw-medium"><i class="ri-checkbox-circle-line align-middle me-1"></i> Autorizada</span>`;
            } else if (statusVal === 2) {
                badge = `<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-11 fw-medium" title="${inv.motivo_rechazo || ''}"><i class="ri-close-circle-line align-middle me-1"></i> Rechazada</span>`;
            }

            const row = `
                <tr>
                    <td class="ps-4 fw-semibold">${inv.serie_folio}</td>
                    <td>${inv.proveedor_nombre}<br><small class="text-muted fs-11">${inv.proveedor_rfc}</small></td>
                    <td>${Sys_Core.Format.toDate(inv.created_at)}</td>
                    <td class="fw-semibold text-primary">#${inv.codigo_oc}</td>
                    <td class="text-end fw-bold">${Sys_Core.Format.toCurrency(inv.monto_total)}</td>
                    <td>${badge}</td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-2 justify-content-end align-items-center">
                            ${actionBtn}
                            <a href="${base_url}/${inv.url_xml}" target="_blank" class="btn btn-soft-success btn-icon btn-sm shadow-none"><i class="ri-code-s-slash-line"></i></a>
                            <a href="${base_url}/${inv.url_pdf}" target="_blank" class="btn btn-soft-danger btn-icon btn-sm shadow-none"><i class="ri-file-pdf-line"></i></a>
                        </div>
                    </td>
                </tr>`;
            $tbl.append(row);
        });
    },

    /**
     * Despliega la confirmación SweetAlert para liberar manualmente la factura
     */
    openOverrideModal: function(idFactura, folio) {
        Sys_Core.UI.confirm({
            title: `Liberar Factura ${folio}`,
            text: "Esta acción anula el bloqueo automático del 3-Way Match. Ingrese una justificación contable para autorizar el pago:",
            icon: "warning",
            confirmText: "Sí, autorizar pago"
        }).then((result) => {
            if (result.isConfirmed) {
                // Solicitar justificación en un input modal de SweetAlert
                Swal.fire({
                    title: 'Justificación Requerida',
                    input: 'textarea',
                    inputPlaceholder: 'Escriba aquí los motivos de la liberación manual...',
                    inputAttributes: { 'aria-label': 'Escriba la justificación' },
                    showCancelButton: true,
                    confirmButtonText: 'Guardar y Autorizar',
                    cancelButtonText: 'Cancelar'
                }).then((textResult) => {
                    if (textResult.isConfirmed && textResult.value) {
                        this.executeOverride(idFactura, textResult.value);
                    } else if (textResult.isConfirmed && !textResult.value) {
                        Sys_Core.UI.notify("La justificación es obligatoria para autorizar.", "error");
                    }
                });
            }
        });
    },

    /**
     * Ejecuta el Override en el Backend
     */
    executeOverride: function(idFactura, comentarios) {
        // --- INICIO MODIFICACIÓN: Petición de liberación a AccountsPayable ---
        Sys_Core.Net.post({
            url: `${base_url}/api/v1/accounts-payable/invoices/override`,
            payload: {
                id_factura: idFactura,
                comentarios: comentarios
            },
            successMsg: "La factura ha sido liberada para pago.",
            onDone: () => {
                this.loadDashboardKpis(); // Recargar contadores
                this.loadInvoicesList();  // Recargar tabla
            }
        });
        // --- FIN MODIFICACIÓN ---
    }
};

$(document).ready(function() {
    CxpInvoices.init();
});