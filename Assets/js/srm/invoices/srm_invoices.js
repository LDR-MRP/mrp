/**
 * Controlador de Buzón de Facturación (Three-Way Match) para SRM
 * Utiliza e integra el motor central Sys_Core
 */

const SrmInvoices = {

    // Selectores del DOM mapeados estrictamente con la vista HTML
    ui: {
        form: '#formCargaFactura',
        selOc: '#sel-orden-compra',
        tblBody: '#tbl-body-invoices',
        btnSubmit: '#btnSubirFactura'
    },

    init: function() {
        // --- INICIO SEGURIDAD: Validación síncrona centralizada ---
        Sys_Core.Auth.validateSession('VENDOR');
        // --- FIN SEGURIDAD ---

        this.loadActivePOs();
        this.loadInvoiceHistory();
        this.bindEvents();
    },

    bindEvents: function() {
        $(this.ui.form).on('submit', (e) => {
            e.preventDefault();
            this.uploadInvoice();
        });
    },

    /**
     * Consume las OCs autorizadas del proveedor y las inyecta en el select
     */
    loadActivePOs: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/purchase-orders`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    // Filtramos por OCs que no estén cerradas/canceladas y tengan saldo facturable
                    const billablePOs = response.data.filter(oc => {
                        const status = oc.estatus.toUpperCase();
                        return status !== 'CERRADA' && status !== 'CANCELADA';
                    });
                    
                    // --- INTEGRACIÓN DE CORE: Llenado de SELECT dinámico ---
                    Sys_Core.UI.fillSelect(this.ui.selOc, billablePOs, {
                        valueField: 'idcompra', // Coincide con la PK de com_ordenes_compra
                        textField: 'idcompra',  // Mostramos el identificador # de la OC
                        placeholder: 'Seleccione la Orden de Compra asociada...'
                    });
                }
            }
        });
    },

    /**
     * Procesa la carga segura de XML + PDF concurrentes utilizando FormData
     */
    uploadInvoice: function() {
        const formElement = $(this.ui.form)[0];
        
        // Generamos el objeto binario multi-part
        const formData = new FormData(formElement); 

        // --- INTEGRACIÓN DE CORE: Envío de binarios (XML/PDF) ---
        // El motor Net.post de tu core detecta automáticamente si el payload es
        // FormData, configurando processData y contentType en false de forma interna.
        Sys_Core.Net.post({
            url: `${base_url}/api/v1/srm/invoices/upload`,
            payload: formData,
            $btn: $(this.ui.btnSubmit),
            successMsg: 'La factura ha pasado el validador del SAT y se registró en CxP.',
            onDone: (response) => {
                // Limpiamos el formulario con el helper nativo de tu Core
                Sys_Core.UI.clearForm(this.ui.form);
                this.loadActivePOs();      // Recargar OCs autorizadas (por si se consumió el saldo de alguna)
                this.loadInvoiceHistory(); // Recargar historial de facturas
            }
        });
    },

    /**
     * Carga el historial de facturas procesadas por este proveedor
     */
    loadInvoiceHistory: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/invoices`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderInvoiceTable(response.data);
                }
            },
            onComplete: () => {
                // Apagar el loader de la página usando tu core
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    /**
     * Renderiza el listado histórico en la tabla de Velzon
     */
    renderInvoiceTable: function(invoices) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty(); // Limpiar el spinner de carga inicial

        if (!invoices || invoices.length === 0) {
            $tbl.html('<tr><td colspan="6" class="text-center text-muted py-4">No tienes facturas cargadas en tu historial.</td></tr>');
            return;
        }

        invoices.forEach(inv => {
            let badge = '';
            const statusVal = parseInt(inv.estatus_validacion);

            // Mapeo de estatus de validación del validador SAT / CxP
            if (statusVal === 0) {
                badge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-11 fw-medium"><i class="ri-time-line align-middle me-1"></i> En Proceso</span>`;
            } else if (statusVal === 1) {
                badge = `<span class="badge bg-success-subtle text-success px-2 py-1 fs-11 fw-medium"><i class="ri-checkbox-circle-line align-middle me-1"></i> Validada (CxP)</span>`;
            } else if (statusVal === 2) {
                badge = `<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-11 fw-medium" title="${inv.motivo_rechazo || ''}"><i class="ri-close-circle-line align-middle me-1"></i> Rechazada</span>`;
            }

            // --- INTEGRACIÓN DE CORE: Formateadores globales de Moneda y Fecha ---
            const row = `
                <tr>
                    <td class="ps-4 fw-semibold text-dark">${inv.serie_folio}</td>
                    <td>${Sys_Core.Format.toDate(inv.created_at)}</td>
                    <td class="fw-semibold text-primary">#${inv.id_compra}</td>
                    <td class="text-end fw-bold text-dark">${Sys_Core.Format.toCurrency(inv.monto_total)}</td>
                    <td>${badge}</td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="${base_url}/${inv.url_xml}" target="_blank" class="btn btn-soft-success btn-icon btn-sm shadow-none"><i class="ri-code-s-slash-line"></i></a>
                            <a href="${base_url}/${inv.url_pdf}" target="_blank" class="btn btn-soft-danger btn-icon btn-sm shadow-none"><i class="ri-file-pdf-line"></i></a>
                        </div>
                    </td>
                </tr>`;
            $tbl.append(row);
        });
    }
};

$(document).ready(function() {
    SrmInvoices.init();
});