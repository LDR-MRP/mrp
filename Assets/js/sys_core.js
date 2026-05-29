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
     * @namespace Auth
     * @description Gestión de seguridad y validación de permisos por rol/módulo.
     */
    Auth: {
         /**
         * Helper interno para recuperar una cookie por su nombre.
         * @param {string} name - Nombre de la cookie (ej: 'mrp_token')
         * @returns {string|null}
         */
        getCookie: function(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },

        /**
         * Consulta si el usuario cuenta con un permiso específico.
         * @param {number} moduleId - ID del módulo (ej: MODS.COM_REQUISICIONES)
         * @param {string} action - 'r', 'w', 'u', 'd', 'a'
         * @returns {boolean}
         */
        hasPermissions: function(moduleId, action = 'r') {
            if (typeof USER_PERMS === 'undefined' || !USER_PERMS[moduleId]) return false;
            return !!(USER_PERMS[moduleId][action] == 1);
        },

        /**
         * Proceso automático de limpieza de la interfaz.
         * Escanea el DOM y elimina elementos basados en atributos data-permiso.
         */
        applyUIPermissions: function() {
            $('[data-permiso]').each(function() {
                const [modKey, action] = $(this).data('permiso').split('|');
                const moduleId = MODS[modKey];

                // Usamos el nuevo nombre de la función internamente
                if (!Sys_Core.Auth.hasPermissions(moduleId, action)) {
                    $(this).remove(); 
                }
            });
        },

        // ==============================================================================
        // --- REFACTORIZADO: GESTIÓN DE SESIÓN VÍA COOKIES (SSO COMPATIBLE) ---
        // ==============================================================================
        
        /**
         * Descifra el payload del JWT almacenado en COOKIES.
         * @returns {Object|null} Payload decodificado o null si el token no existe.
         */
        decodeJWT: function() {
            // MIGRACIÓN: Ahora leemos de la cookie 'mrp_token'
            const token = Sys_Core.Auth.getCookie('mrp_token');
            
            if (!token) return null;
            try {
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                return JSON.parse(jsonPayload);
            } catch (e) {
                console.error("Error decodificando JWT de la cookie:", e);
                return null;
            }
        },

        /**
         * Valida síncronamente que la sesión esté activa, no haya expirado y pertenezca al rol.
         */
        validateSession: function(roleRequired = null) {
            const payload = Sys_Core.Auth.decodeJWT();
            
            // Detectar si estamos en el subdirectorio de SRM para el redirect
            const isSrm = window.location.pathname.includes('/srm');
            const redirectPath = isSrm ? '/srm/login' : '/login';

            // A. Si no existe token en la cookie, expulsar
            if (!payload || !payload.exp) {
                Sys_Core.Auth.logout(redirectPath);
                return false;
            }

            // B. Validación de expiración (Client-side sync)
            const now = Math.floor(Date.now() / 1000);
            if (payload.exp < now) {
                Sys_Core.Auth.logout(redirectPath);
                return false;
            }

            // C. Validar correspondencia de rol
            const userRole = payload.data?.rol || payload.data?.role;
            if (roleRequired && userRole !== roleRequired) {
                Sys_Core.Auth.logout(redirectPath);
                return false;
            }

            return true;
        },

        /**
         * Destruye la sesión eliminando cookies y limpiando almacenamiento.
         * @param {string} [redirectPath='/login'] - Ruta destino
         */
        logout: function(redirectPath = '/login') {
            // 1. ELIMINAR COOKIES: Para borrar una cookie en JS, se expira en el pasado.
            // Es vital incluir el path y el domain para que el navegador la identifique.
            
            // Extraer el dominio base para limpiar la wildcard cookie (.ldrhumanresources.local/com)
            const host = window.location.hostname;
            const baseDomain = host.includes('ldrhumanresources') 
                               ? host.substring(host.lastIndexOf(".", host.lastIndexOf(".") - 1)) 
                               : host;

            // Borrar mrp_token
            document.cookie = `mrp_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${baseDomain}`;
            
            // Borrar mrp_forced_logout (si existiera)
            document.cookie = `mrp_forced_logout=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${baseDomain}`;

            // 2. LIMPIEZA PREVENTIVA: Borramos restos de localStorage por si acaso
            localStorage.removeItem('mrp_token');

            // 3. REDIRECCIÓN
            window.location.href = `${Sys_Core.Config.baseUrl}${redirectPath}`;
        }
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
         * Convierte "$ 1,234,567.89" a 1234567.89 (Float nativo)
         * @param {string|number} formattedString 
         * @returns {number}
         */
        toNumber: function(formattedString) {
            if (!formattedString) return 0;
            if (typeof formattedString === 'number') return formattedString;
            // Quita todo lo que no sea número, punto o signo negativo
            const cleaned = formattedString.toString().replace(/[^0-9.-]+/g, "");
            return parseFloat(cleaned) || 0;
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
         * @namespace Dashboard
         * @description Gestión de indicadores y widgets visuales.
         */
        Dashboard: {
            /**
             * Anima un contador numérico de 0 a X.
             * @param {string} id - ID del elemento HTML.
             * @param {number} value - Valor final.
             */
            animateCounter: function(id, value) {
                const $el = $(`#${id}`);
                const startValue = parseInt($el.text()) || 0;
                if (startValue === value) return;

                $({ countNum: startValue }).animate({ countNum: value }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() { $el.text(Math.ceil(this.countNum)); },
                    complete: function() { $el.text(this.countNum); }
                });
            },

            /**
             * Actualiza un set de KPIs basado en un mapeo de estatus.
             * @param {string} url - Endpoint de datos.
             * @param {Object} mapping - Relación {'estatus_db': 'id_html'}.
             * @param {boolean} [recurrent=false] - Si debe repetirse.
             */
            refreshKPIs: function(url, mapping, recurrent = false) {
                Sys_Core.Net.get({
                    url: url,
                    recurrent: recurrent,
                    silent: true,
                    onSuccess: (res) => {
                        Object.keys(mapping).forEach(key => {
                            const row = res.data.find(item => item.estatus.toLowerCase() === key.toLowerCase());
                            const finalValue = row ? row.cantidad : 0;
                            Sys_Core.UI.Dashboard.animateCounter(mapping[key], finalValue);
                        });
                    }
                });
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
        },
        
        /**
         * 
         * @param {*} selector 
         * @param {*} data 
         * @param {*} options 
         */
        fillSelect: function(selector, data, options = {}) {
            const { 
                valueField = 'id', 
                textField = 'nombre', 
                placeholder = 'Seleccione una opción...',
                selectedValue = null 
            } = options;
            
            const $select = $(selector);
            $select.empty().append(`<option value="">${placeholder}</option>`);
            
            if (Array.isArray(data)) {
                data.forEach(item => {
                    const selected = (selectedValue && item[valueField] == selectedValue) ? 'selected' : '';
                    $select.append(`<option value="${item[valueField]}" ${selected}>${item[textField]}</option>`);
                });
            }
            $select.trigger('change');
        },

        /**
         * Rellena automáticamente un formulario a partir de un objeto JSON.
         * @param {string} formSelector - El ID o clase del formulario (ej: '#formProveedor')
         * @param {Object} data - El objeto JSON con los datos
         */
        fillForm: function(formSelector, data) {
            const $form = $(formSelector);
            if (!$form.length || !data) return;

            Object.entries(data).forEach(([key, value]) => {
                const $el = $form.find(`[name="${key}"]`);
                if ($el.length) {
                    if ($el.is(':radio') || $el.is(':checkbox')) {
                        // Marca el radio/checkbox si coincide el valor
                        $el.filter(`[value="${String(value)}"]`).prop('checked', true).trigger('change');
                    } else {
                        // Rellena inputs y selects, y dispara change para plugins
                        $el.val(value).trigger('change');
                    }
                }
            });
        },
    },

    /**
     * @namespace Net
     * @description Motor de comunicaciones asíncronas.
     */
    Net: {
        /**
         * Petición GET con soporte para recursividad.
         * @param {Object} options 
         * @param {string} options.url
         * @param {function} [options.onSuccess] - Callback en caso de éxito (200 OK).
         * @param {function} [options.onComplete] - Callback que se ejecuta SIEMPRE (éxito o error).
         * @param {boolean} [options.recurrent=false]
         * @param {number} [options.interval=30000]
         * @param {boolean} [options.silent=false]
         */
        get: function(options) {
            const { url, onSuccess, onComplete, recurrent, interval = 30000, silent } = options;
            const token = Sys_Core.Auth.getCookie('mrp_token');
            
            const execute = () => {
                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        // --- INYECCIÓN AUTOMÁTICA DEL TOKEN ---
                        'Authorization': token ? `Bearer ${token}` : ''
                    },
                    success: (res) => { if (onSuccess) onSuccess(res); },
                    error: (xhr) => { if (!silent) Sys_Core.Net.handleError(xhr); },
                    complete: (xhr) => {
                        if (onComplete) onComplete(xhr);
                        if (recurrent) setTimeout(execute, interval);
                    }
                });
            };
            execute();
        },

        /**
         * @param {Object} options 
         * @param {string} options.url
         * @param {any} options.payload
         * @param {string} [options.method='POST'] - Verbo HTTP (POST, PUT, DELETE, PATCH)
         * @param {jQuery} [options.$btn] - Botón que disparó la acción (para el spinner)
         * @param {string} [options.successMsg]
         * @param {function} [options.onDone]
         * @param {string} [options.contentType]
         * @param {boolean} [options.processData]
         */
        post: function(options) {
            const { url, payload, successMsg, onDone } = options;
            const token = Sys_Core.Auth.getCookie('mrp_token');
            
            // 1. SOPORTE RESTful: Si no mandan method, asumimos POST por retrocompatibilidad
            const httpMethod = (options.method || 'POST').toUpperCase();

            // 2. MEJORA UI: Permitimos pasar el botón exacto. Si no, usamos el activeElement o submit.
            let $btn = options.$btn;
            if (!$btn || !$btn.length) {
                const $active = $(document.activeElement);
                $btn = $active.is('button') ? $active : $('button[type="submit"]:focus');
            }
            
            const originalHtml = $btn.length ? $btn.html() : '';

            let config = {
                url: url,
                method: httpMethod, // Inyectamos POST, PUT o DELETE
                data: payload,
                headers: {
                    'Authorization': token ? `Bearer ${token}` : ''
                },
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
                    if ($btn.length) $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Procesando...');
                },
                success: function(res) {
                    if (res.status === 'success' || res.status === true) {
                        // Usamos el successMsg de la BD si viene, si no, el del frontend
                        Sys_Core.UI.notify(res.message || successMsg || 'Operación exitosa', 'success');
                        if (onDone) onDone(res);
                    } else {
                        Sys_Core.UI.alert('Operación Fallida', res.message, 'warning');
                    }
                },
                error: function(xhr) {
                    Sys_Core.Net.handleError(xhr);
                },
                complete: function() {
                    Sys_Core.UI.toggleLoader('.page-content', false);
                    if ($btn.length) $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },

        downloadPdf: function(options) {
            const { url, filename } = options;
            const token = Sys_Core.Auth.getCookie('mrp_token');
            Sys_Core.UI.toggleLoader('.page-content', true);

            fetch(url, {
                method: 'GET',
                headers: { 'Authorization': `Bearer ${token}` }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                
                // Caso A: El servidor devolvió un error en formato JSON
                if (contentType && contentType.includes('application/json')) {
                    const errData = await response.json();
                    throw new Error(errData.message || 'Error desconocido al generar PDF');
                }

                // Caso B: Todo bien, procesamos el binario
                if (!response.ok) throw new Error('Error en la comunicación con el servidor');
                return response.blob();
            })
            .then(blob => {
                const urlBlob = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = urlBlob;
                a.download = filename || 'Requisicion.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(urlBlob);
                Sys_Core.UI.notify('PDF generado correctamente', 'success');
            })
            .catch(error => {
                Sys_Core.UI.alert('Error de Impresión', error.message, 'error');
            })
            .finally(() => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            });
        },

        /**
         * Manejador global de errores AJAX. 
         * Diseñado para atrapar la estructura anidada de "ServiceResponse" y "FormRequest".
         * 
         * @param {Object} xhr El objeto XMLHttpRequest devuelto por jQuery.
         */
        handleError: function(xhr) {
            let res = {};
            try { 
                res = xhr.responseJSON || JSON.parse(xhr.responseText); 
            } catch (e) { 
                res = {}; 
            }

            const status = xhr.status;
            let title = `Error (${status})`;
            let icon = 'error';
            let html = res.message || res.error || "Ocurrió un error inesperado.";

            switch (status) {
                case 401: // UNAUTHORIZED (Sesión Expirada vs Credenciales Incorrectas)
                    // Detectamos si la petición falló estando en las vistas de Login
                    const isLoginPage = window.location.pathname.toLowerCase().includes('login');
                    
                    if (isLoginPage) {
                        title = 'Acceso Denegado';
                        icon = 'warning';
                        html = res.message || 'El usuario o la contraseña es incorrecto.';
                        // Rompemos el switch para que use el alert global al final sin recargar la página
                        break; 
                    } else {
                        title = 'Sesión Expirada';
                        icon = 'info';
                        html = 'Tu identidad no pudo ser validada. Por seguridad, ingresa nuevamente.';
                        Sys_Core.UI.alert(title, html, icon).then(() => {
                            localStorage.removeItem('mrp_token');
                            // --- INICIO AJUSTE: Redirección defensiva y retrocompatible ---
                            // 1. Resolvemos la URL base de forma segura (ERP vs Core)
                            const rootUrl = typeof base_url !== 'undefined' ? base_url : Sys_Core.Config.baseUrl;
                            
                            // 2. Identificamos si expiró en el SRM o en el ERP Interno
                            const isSrmPage = window.location.pathname.toLowerCase().includes('srm');
                            const redirectPath = isSrmPage ? '/srm/login' : '/login';
                            
                            // 3. Forzamos redirección física inmediata al login correspondiente
                            window.location.href = rootUrl + redirectPath;
                            // --- FIN AJUSTE -
                        });
                        return; // Retornamos temprano para evitar doble modal
                    }

                case 403: // FORBIDDEN (The PM's concern)
                    title = 'Acceso Restringido';
                    icon = 'warning';
                    html = `
                        <div class="text-center">
                            <i class="ri-shield-user-line fs-1 text-warning mb-3 d-block"></i>
                            <p class="fw-bold mb-1">${res.message || 'Privilegios insuficientes.'}</p>
                            <p class="text-muted small">Esta acción está limitada por tu perfil actual. Contacta a tu jefe directo para revisar tus permisos.</p>
                        </div>`;
                    break;

                case 404: // NOT FOUND
                    title = 'No Encontrado';
                    icon = 'question';
                    html = res.message || 'El recurso solicitado no existe o pertenece a otra planta.';
                    break;                
                case 409: // REGLA DE NEGOCIO / CONFLICTO
                    title = 'Validación de Proceso';
                    icon = 'info'; // Icono de información (Azul) para que sea menos agresivo
                    html = `
                        <div class="text-center">
                            <i class="ri-git-repository-commits-line fs-1 text-info mb-3 d-block"></i>
                            <p class="fw-bold mb-1">Requisito Incumplido</p>
                            <p class="text-muted">${res.message || 'El estado actual del proceso no permite continuar.'}</p>
                            <hr class="border-light">
                            <p class="small text-primary">Consulte el manual de procedimientos de Sourcing.</p>
                        </div>`;
                    break;
                case 422: // VALIDATION
                    title = 'Datos Inválidos';
                    icon = 'warning';
                    // Llamamos al método interno de este mismo objeto
                    html = this._extractValidationHtml(res);
                    break;

                case 500: // SERVER ERROR
                    title = 'Falla de Sistema';
                    icon = 'error';
                    html = `
                        <div class="text-start">
                            <p class="fw-bold">El servidor no pudo procesar la solicitud.</p>
                            <p class="small text-muted mb-0">ID de Auditoría: <span class="font-monospace">${Date.now()}</span></p>
                        </div>`;
                    break;
            }

            Sys_Core.UI.alert(title, html, icon);
        },

        /**
         * MÉTODO INTERNO (Helper): Aplanar errores de validación.
         * Se mantiene dentro de Net para no dejar "funciones sueltas".
         * @private
         */
        _extractValidationHtml: function(res) {
            let rawErrors = res.errors?.errors || res.errors || {};
            
            if (typeof rawErrors === 'string') {
                try { 
                    rawErrors = JSON.parse(rawErrors).errors || JSON.parse(rawErrors);
                } catch(e) { rawErrors = {}; }
            }

            if (Object.keys(rawErrors).length === 0) return res.message || 'Revise el formulario.';

            let list = '<div class="text-start small"><ul class="mb-0">';
            $.each(rawErrors, (campo, mensaje) => {
                if (campo === 'status') return;
                const text = Array.isArray(mensaje) ? mensaje[0] : mensaje;
                list += `<li><b>${campo.replace('_', ' ')}:</b> ${text}</li>`;
            });
            return list + '</ul></div>';
        }
    },

    /**
     * Gestión de URLs y Navegación local
     */
    URL: {
        getParam: function(param) {
            return new URLSearchParams(window.location.search).get(param);
        }
    },

    /**
     * @namespace Navigation
     * @description Utilidades para el control de flujo y redireccionamiento.
     */
    Navigation: {
        /**
         * Redirige a una ruta interna del sistema utilizando la baseUrl.
         * @param {string} path - Ruta relativa (ej: 'com_requisicion/nueva')
         */
        to: function(path) {
            if (!path) return;
            // Limpiar slashes duplicados si el path trae uno al inicio
            const cleanPath = path.startsWith('/') ? path.substring(1) : path;
            window.location.href = `${Sys_Core.Config.baseUrl}/${cleanPath}`;
        }
    }
};

/**
 * Event Listeners Globales
 * Manejo de eventos delegados para atributos de datos Sys_Core.
 */
// --- INICIO AGREGADO: Auto-hidratación de Cabecera Global (JWT) ---
$(document).ready(function() {
    Sys_Core.Auth.applyUIPermissions();
    
    const payload = Sys_Core.Auth.decodeJWT();
    if (payload && payload.data) {
        const user = payload.data;
        
        // Hidratar nombre de usuario en cabecera si el elemento existe en el DOM
        if ($('#lbl-user-name').length) {
            $('#lbl-user-name').text(user.nombre);
        }
        
        // Hidratar iniciales del avatar si el elemento existe en el DOM
        if ($('#lbl-user-avatar').length) {
            const iniciales = user.nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            $('#lbl-user-avatar').text(iniciales);
        }
    }
});
// --- FIN AGREGADO ---

$(document).on('click', '[data-redirect]', function(e) {
    e.preventDefault();
    const target = $(this).data('redirect');
    Sys_Core.Navigation.to(target);
});