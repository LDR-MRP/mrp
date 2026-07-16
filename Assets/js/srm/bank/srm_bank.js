/**
 * Controlador de Cuentas Bancarias para SRM (Proveedores)
 * Utiliza e integra el motor central Sys_Core
 */

const SrmBank = {

    ui: {
        form: '#formCargaBanco',
        tblBody: '#tbl-body-bancos-srm',
        btnSubmit: '#btnGuardarBanco'
    },

    init: function() {
        Sys_Core.Auth.validateSession('VENDOR');
        this.loadCatalogs();
        this.loadBankAccounts();
        this.bindEvents();
    },

    bindEvents: function() {
        $(this.ui.form).on('submit', (e) => {
            e.preventDefault();
            this.storeBankAccount();
        });
    },

    /**
     * Carga el catálogo de bancos oficiales del SAT
     */
    loadCatalogs: function() {
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/Catalogo/bancos`,
            silent: true,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect('#id_banco', res.data, {
                    valueField: 'id_banco',
                    textField: 'nombre_corto',
                    placeholder: 'Selecciona un banco...'
                });
            }
        });

        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/currencies`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect('#id_moneda_banco', res.data, { 
                    valueField: 'cve_moneda', // Ej: 'MXN', 'USD'
                    textField: 'cve_moneda',
                    selectedValue: 'MXN' // Pre-seleccionar Moneda Nacional
                });
                // Disparar el cambio manualmente para bloquear el input de TC si es MXN
                $('#id_moneda_banco').trigger('change');
            }
        });
    },

    /**
     * Guarda la nueva cuenta bancaria
     */
    storeBankAccount: function() {
        const formElement = $(this.ui.form)[0];
        const formData = new FormData(formElement); // Obligatorio para el PDF

        Sys_Core.Net.post({
            url: `${base_url}/api/v1/srm/bank-accounts`,
            payload: formData,
            $btn: $(this.ui.btnSubmit),
            successMsg: "La cuenta bancaria ha sido registrada y enviada a revisión de Finanzas L2.",
            onDone: () => {
                Sys_Core.UI.clearForm(this.ui.form);
                this.loadBankAccounts(); // Recargar historial
            }
        });
    },

    /**
     * Carga el historial de cuentas registradas de este proveedor
     */
    loadBankAccounts: function() {
        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/bank-accounts`,
            silent: true,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderBanksTable(response.data);
                }
            }
        });
    },

    renderBanksTable: function(accounts) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty();

        if (!accounts || accounts.length === 0) {
            $tbl.html('<tr><td colspan="4" class="text-center text-muted py-4">No tienes cuentas bancarias registradas.</td></tr>');
            return;
        }

        accounts.forEach(acc => {
            let badge = '';
            const status = acc.estatus_aprobacion.toUpperCase();

            // Mapeo de estatus de validación de Velzon
            if (status === 'PENDIENTE') {
                badge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-11 fw-medium">Pendiente</span>`;
            } else if (status === 'APROBADO') {
                badge = `<span class="badge bg-success-subtle text-success px-2 py-1 fs-11 fw-medium">Aprobado</span>`;
            } else if (status === 'RECHAZADO') {
                badge = `<span class="badge bg-danger-subtle text-danger px-2 py-1 fs-11 fw-medium">Rechazado</span>`;
            }

            // Marcar si es cuenta principal
            const isPrincipal = parseInt(acc.es_principal) === 1 
                ? `<span class="badge bg-soft-info text-info ms-2">Principal</span>` 
                : '';

            // --- INICIO ADICIÓN: Previsualización de PDF para el Proveedor ---
            // Si la cuenta tiene un PDF registrado, le pintamos un icono rojo discreto para que pueda revisarlo [4]
            const pdfIcon = acc.url_pdf 
                ? `<a href="${base_url}/${acc.url_pdf}" target="_blank" class="text-danger ms-2" title="Ver PDF de la Carátula Bancaria"><i class="ri-file-pdf-line align-middle fs-16"></i></a>` 
                : '';
            // --- FIN ADICIÓN ---

            const row = `
                <tr>
                    <td class="fw-semibold text-dark">${acc.nombre_banco || 'Banco'}</td>
                    <td>
                        <span class="font-monospace fs-13 text-muted">${acc.clabe || acc.cuenta}</span>
                        ${isPrincipal}
                        ${pdfIcon}
                    </td>
                    <td class="fw-bold text-body">${acc.id_moneda}</td>
                    <td>${badge}</td>
                </tr>`;
            $tbl.append(row);
        });
    }
};

$(document).ready(() => SrmBank.init());