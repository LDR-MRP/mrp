/**
 * Controlador de la Bandeja de Programación de Pagos (Accounts Payable) - ERP Interno
 * Utiliza e integra el motor central Sys_Core (SSO / Cookie Compatible)
 */

// --- INICIO MODIFICACIÓN: Objeto renombrado a inglés puro ---
const AccountsPayablePayments = {
// --- FIN MODIFICACIÓN ---

    ui: {
        tblBody: '#tbl-body-cxp-payments',
        checkAll: '#check-all-payments',
        btnLayout: '#btn-generar-layout',
        lblCount: '#lbl-count-selected',
        selBanco: '#sel-banco-origen'
    },

    state: {
        selectedInvoices: []
    },

    init: function() {
        // --- INICIO MODIFICACIÓN: Validación Stateless vía Cookies ---
        // validateSession() ahora busca automáticamente la cookie 'mrp_token' en el Core
        Sys_Core.Auth.validateSession();
        // --- FIN MODIFICACIÓN ---
        
        this.loadPendingPayments();
        this.bindEvents();
    },

    bindEvents: function() {
        // Delegación de checkboxes individuales
        $(this.ui.tblBody).on('change', '.check-payment', () => {
            this.updateSelectionState();
        });

        // Checkbox maestro (Seleccionar todo)
        $(this.ui.checkAll).on('change', (e) => {
            const isChecked = $(e.currentTarget).prop('checked');
            $('.check-payment').prop('checked', isChecked);
            this.updateSelectionState();
        });

        // Botón de generación de Layout
        $(this.ui.btnLayout).on('click', () => {
            this.generateLayout();
        });
    },

    /**
     * Carga el listado de facturas autorizadas listas para pago
     */
    loadPendingPayments: function() {
        Sys_Core.UI.toggleLoader('.page-content', true);

        // NOTA: Sys_Core.Net.get automáticamente inyecta la cookie 'mrp_token' en las cabeceras
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/accounts-payable/payments/pending`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderPaymentsTable(response.data);
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    renderPaymentsTable: function(payments) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty();
        $(this.ui.checkAll).prop('checked', false);
        this.updateSelectionState();

        if (!payments || payments.length === 0) {
            $tbl.html('<tr><td colspan="7" class="text-center text-muted py-4">No hay facturas aprobadas pendientes de pago en este momento.</td></tr>');
            return;
        }

        payments.forEach(pay => {
            // Evaluamos si el pago ya venció hoy o antes para marcar el renglón de advertencia de forma semántica
            const today = new Date().toISOString().split('T')[0];
            const isVencida = pay.fecha_vencimiento < today;
            const textClass = isVencida ? 'text-danger fw-bold' : 'text-body';

            const row = `
                <tr>
                    <td class="ps-4">
                        <div class="form-check">
                            <input class="form-check-input check-payment" type="checkbox" value="${pay.id_factura}">
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">${pay.serie_folio}<br><small class="text-muted font-monospace">${pay.uuid.substring(0, 8)}...</small></td>
                    <td>${pay.proveedor_nombre}<br><small class="text-muted fs-11">${pay.proveedor_rfc}</small></td>
                    <td class="${textClass}">${Sys_Core.Format.toDate(pay.fecha_vencimiento)}</td>
                    <td class="fw-medium">${pay.nombre_banco || 'B. Extranjero'}</td>
                    <td class="font-monospace fs-13 text-muted">${pay.cuenta_clabe}</td>
                    <td class="text-end fw-bold text-dark pe-4">${Sys_Core.Format.toCurrency(pay.monto_total)}</td>
                </tr>`;
            $tbl.append(row);
        });
    },

    /**
     * Calcula cuántas facturas se han seleccionado para dispersar y activa/desactiva el botón
     */
    updateSelectionState: function() {
        const selected = [];
        $('.check-payment:checked').each(function() {
            selected.push(parseInt($(this).val()));
        });

        this.state.selectedInvoices = selected;
        $(this.ui.lblCount).text(selected.length);

        if (selected.length > 0) {
            $(this.ui.btnLayout).prop('disabled', false);
        } else {
            $(this.ui.btnLayout).prop('disabled', true);
        }
    },

    /**
     * Consume la API y dispara la descarga del archivo plano .txt directamente en el navegador
     */
    generateLayout: function() {
        const invoiceIds = this.state.selectedInvoices;
        const bancoSeleccionado = $(this.ui.selBanco).val();
        const $btn = $(this.ui.btnLayout);
        const originalHtml = $btn.html();
        
        // --- INICIO MODIFICACIÓN: Extraer el token de la cookie SSO del Core ---
        const token = Sys_Core.Auth.getCookie('mrp_token');
        // --- FIN MODIFICACIÓN ---

        Sys_Core.UI.toggleLoader('.page-content', true);
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Generando...');

        // Ejecutar descarga física de texto plano con Fetch API (Estándar de Seguridad)
        fetch(`${base_url}/api/v1/accounts-payable/payments/generate-layout`, {
            method: 'POST',
            headers: { 
                'Authorization': token ? `Bearer ${token}` : '', // <-- Inyección segura desde Cookie [3]
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                invoice_ids: invoiceIds,
                bank_origin: bancoSeleccionado
            })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok || !data.status) {
                throw new Error(data.message || 'Error al procesar el archivo bancario.');
            }

            // Descarga nativa de binario/texto en navegador
            const blob = new Blob([data.data.content], { type: 'text/plain;charset=utf-8' });
            const urlBlob = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = urlBlob;
            a.download = data.data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(urlBlob);

            Sys_Core.UI.notify(data.message, 'success');
            
            // Recargar la bandeja para remover las facturas ya programadas
            this.loadPendingPayments();
        })
        .catch(error => {
            Sys_Core.UI.alert('Falla en Dispersión', error.message, 'error');
        })
        .finally(() => {
            Sys_Core.UI.toggleLoader('.page-content', false);
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
};

// --- INICIO MODIFICACIÓN: Inicialización del objeto renombrado ---
$(document).ready(() => AccountsPayablePayments.init());
// --- FIN MODIFICACIÓN ---