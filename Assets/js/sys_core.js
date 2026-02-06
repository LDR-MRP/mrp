/**
 * Global System Core - Brand Agnostic (HU-10)
 * Motor central de utilidades para el ecosistema MRP.
 */
const Sys_Core = {

    Config: {
        brandName: 'System',
        baseUrl: '',
        defaultLocale: 'es-MX',
        defaultCurrency: 'MXN'
    },

    /**
     * @namespace Format
     * @description Utilidades para la transformación de datos y strings.
     */
    Format: {
        /**
         * @param {number|string} amount 
         * @param {string} [locale] 
         * @param {string} [currency] 
         * @returns {string}
         */
        toCurrency: function(amount, locale = Sys_Core.Config.defaultLocale, currency = Sys_Core.Config.defaultCurrency) {
            const num = parseFloat(amount) || 0;
            return new Intl.NumberFormat(locale, { style: 'currency', currency: currency }).format(num);
        },

        /**
         * @param {string} dateString 
         * @returns {string}
         */
        toDate: function(dateString) {
            if (!dateString) return '---';
            return new Date(dateString).toLocaleDateString(Sys_Core.Config.defaultLocale);
        }
    },

    /**
     * @namespace UI
     * @description Gestión de la capa de presentación, notificaciones y estados visuales.
     */
    UI: {
        /**
         * @param {string} message 
         * @param {string} [type='info'] 
         */
        notify: function(message, type = 'info') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({ icon: type, title: `${Sys_Core.Config.brandName}: ${message}` });
        },

        /**
         * @param {string} title 
         * @param {string} message 
         * @param {string} [type='info'] 
         * @returns {Promise}
         */
        alert: function(title, message, type = 'info') {
            return Swal.fire({
                title: title,
                html: message,
                icon: type,
                confirmButtonColor: 'var(--brand-primary, #0056b3)',
                confirmButtonText: 'Entendido'
            });
        },

        /**
         * @param {Object} options 
         * @param {string} options.title
         * @param {string} options.text
         * @param {string} [options.icon='warning']
         * @param {string} [options.confirmText='Sí, confirmar']
         * @returns {Promise}
         */
        confirm: function(options) {
            return Swal.fire({
                title: options.title || '¿Está seguro?',
                text: options.text || "Esta acción no se puede deshacer.",
                icon: options.icon || 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--brand-primary, #0056b3)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: options.confirmText || 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            });
        },

        /**
         * @param {string} [selector='.page-content'] 
         * @param {boolean} [isLoading=true] 
         */
        toggleLoader: function(selector = '.page-content', isLoading = true) {
            const $el = $(selector);
            if (isLoading) {
                $el.css({ 'opacity': '0.5', 'pointer-events': 'none' });
            } else {
                $el.css({ 'opacity': '1', 'pointer-events': 'auto' });
            }
        },

        /**
         * @param {jQuery} $btn 
         * @param {string} originalHtml 
         */
        resetState: function($btn, originalHtml) {
            if ($btn && originalHtml) {
                $btn.prop('disabled', false).html(originalHtml);
            }
            Sys_Core.UI.toggleLoader('.page-content', false);
        },

        /**
         * @param {string} formSelector 
         */
        clearForm: function(formSelector) {
            const $form = $(formSelector);
            $form[0].reset();
            $form.find('select').val('').trigger('change');
            $form.find('.is-invalid').removeClass('is-invalid');
        }
    },

    /**
     * @namespace Net
     * @description Motor de comunicaciones asíncronas.
     */
    Net: {
        /**
         * @param {Object} options 
         * @param {string} options.url
         * @param {any} options.payload
         * @param {string} options.successMsg
         * @param {function} [options.onDone]
         * @param {string} [options.contentType]
         * @param {boolean} [options.processData]
         */
        ajaxRequest: function(options) {
            const { url, payload, successMsg, onDone } = options;
            const $btn = $('button[type="submit"]:focus').length ? $('button[type="submit"]:focus') : $('button[type="submit"]');
            const originalHtml = $btn.html();

            let config = {
                url: url,
                method: 'POST',
                data: payload,
                contentType: options.contentType,
                processData: options.processData ?? true
            };

            if (payload instanceof FormData) {
                config.contentType = false;
                config.processData = false;
            } else if (typeof payload === 'object' && payload !== null && !options.contentType) {
                config.data = JSON.stringify(payload);
                config.contentType = 'application/json';
            }

            $.ajax({
                ...config,
                dataType: 'json',
                beforeSend: function() {
                    Sys_Core.UI.toggleLoader('.page-content', true);
                    $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
                },
                success: function(res) {
                    if (res.status === 'success' || res.status === true) {
                        Sys_Core.UI.notify(successMsg, 'success');
                        if (onDone) onDone(res);
                        Sys_Core.UI.resetState($btn, originalHtml);
                    } else {
                        Sys_Core.UI.alert('Operación Fallida', res.message, 'warning');
                        Sys_Core.UI.resetState($btn, originalHtml);
                    }
                },
                error: function(xhr) {
                    Sys_Core.Net.handleError(xhr);
                    Sys_Core.UI.resetState($btn, originalHtml);
                }
            });
        },

        /**
         * @param {Object} xhr 
         */
        handleError: function(xhr) {
            if (xhr.status === 422) {
                const res = xhr.responseJSON;
                let html = `<div class="text-left small"><p>${res.message || 'Errores detectados:'}</p><ul>`;
                if (res.errors) {
                    $.each(res.errors, (key, msg) => html += `<li>${msg}</li>`);
                }
                html += '</ul></div>';
                Sys_Core.UI.alert('Datos Inválidos', html, 'error');
            } else {
                Sys_Core.UI.alert('Error de Sistema', `El servidor respondió con código ${xhr.status}`, 'error');
            }
        }
    }
};