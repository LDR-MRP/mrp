/**
 * Controlador de Buzón de Facturación (Three-Way Match) para SRM
 * Utiliza e integra el motor central Sys_Core
 */

const SrmInvoices = {

    ui: {
        form: '#formCargaFactura',
        selOc: '#sel-orden-compra',
        tblBody: '#tbl-body-invoices',
        btnSubmit: '#btnSubirFactura'
    },

    init: function() {
        this.validateSession();
        this.loadActivePOs();
        this.loadInvoiceHistory();
        this.bindEvents();
    },

    validateSession: function() {
        const token = localStorage.getItem('mrp_token');
        if (!token) window.location.href = base_url + '/srm_login';
    },

    bindEvents: function() {
        $(this.ui.form).on('submit', (e) => {
            e.preventDefault();
            this.uploadInvoice();
        });
    },

    /**
     * Sincroniza el select con las OCs autorizadas del proveedor usando fillSelect
     */
    loadActivePOs: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/purchase-orders`,
            silent: false,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    // Filtramos por OCs que no estén cerradas
                    const billablePOs = response.data.filter(oc => oc.estatus.toUpperCase() !== 'CERRADA');
                    
                    // Explotamos el helper de tu Sys_Core para rellenar el SELECT de forma premium
                    Sys_Core.UI.fillSelect(this.ui.selOc, billablePOs, {
                        valueField: 'id_compra',
                        textField: 'codigo_oc',
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
        const formData = new FormData(formElement); // Obligatorio para subir archivos binarios

        // El motor Net.post de tu core detecta automáticamente si el payload es
        // FormData, configurando processData y contentType en false al vuelo.
        Sys_Core.Net.post({
            url: `${base_url}/api/v1/srm/invoices/upload`,
            payload: formData,
            $btn: $(this.ui.btnSubmit),
            successMsg: 'La factura ha pasado el validador del SAT y se registró en CxP.',
            onDone: (response) => {
                // Limpiamos el formulario con el helper nativo de tu Core
                Sys_Core.UI.clearForm(this.ui.form);
                this.loadActivePOs(); // Recargar OCs (por si se consumió el saldo de alguna)
                this.loadInvoiceHistory(); // Recargar historial
            }
        });
    },

    /**
     * Carga el historial de facturas procesadas
     */
    loadInvoiceHistory: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/invoices`,
            silent: false,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderInvoiceTable(response.data);
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    renderInvoiceTable: function(invoices) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty();

        if (!invoices || invoices.length === 0) {
            $tbl.html('<tr><td colspan="6" class="text-center text-muted py-4">No tienes facturas cargadas en tu historial.</td></tr>');
            return;
        }

        invoices.forEach(inv => {
            let badge = '';
            const statusVal = parseInt(inv.estatus_validacion);

            // Mapeo de estatus de validación del validador SAT / CxP
            if (statusVal === 0) {
                badge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-11 fw-medium"><i class="ri-time-line align-middle me-1"></i> En Proceso (SAT)</span>`;
            } else if (statusVal === 1) {
                badge = `<span class="badge bg-success-subtle text-success px-2 py-1 fs-11 fw-medium"><i class="ri-checkbox-circle-line align-middle me-1"></i> Validada (CxP)</span>`;
            } else if (statusVal === 2) {
                badge = `<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-11 fw-medium" title="${inv.motivo_rechazo || ''}"><i class="ri-close-circle-line align-middle me-1"></i> Rechazada</span>`;
            }

            const row = `
                <tr>
                    <td class="ps-4 fw-semibold text-dark">${inv.serie_folio}</td>
                    <td>${Sys_Core.Format.toDate(inv.fecha_creacion)}</td>
                    <td class="fw-semibold text-primary">#${inv.codigo_oc}</td>
                    <td class="text-end fw-bold text-dark">${Sys_Core.Format.toCurrency(inv.monto_total)}</td>
                    <td>${badge}</td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="${base_url}/${inv.url_xml}" target="_blank" class="btn btn-soft-success btn-icon btn-sm"><i class="ri-code-s-slash-line"></i></a>
                            <a href="${base_url}/${inv.url_pdf}" target="_blank" class="btn btn-soft-danger btn-icon btn-sm"><i class="ri-file-pdf-line"></i></a>
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