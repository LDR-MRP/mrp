/**
 * Controlador Frontend para Autenticación del Portal de Proveedores (SRM)
 * Arquitectura: Object Literal Pattern integrado con Sys_Core
 */

const SrmLogin = {
    
    // Selectores cacheados limpios (Eliminamos loaders y alerts redundantes)
    ui: {
        formLogin: '#formSrmLogin',
        formReset: '#formRecetPass',
        containerLogin: '#login-container',
        containerReset: '#reset-container',
        email: '#txtEmail',
        password: '#txtPassword',
        btnSubmit: '#btnLogin',
        btnTogglePass: '#password-addon'
    },

    init: function() {
        // Sincronizamos la URL base global de PHP con la configuración del motor
        Sys_Core.Config.baseUrl = base_url;
        this.bindEvents();
    },

    bindEvents: function() {
        // Evento de Login unificado
        $(this.ui.formLogin).on('submit', (e) => {
            e.preventDefault();
            this.authenticate();
        });

        // Evento de Recuperar Contraseña
        $(this.ui.formReset).on('submit', (e) => {
            e.preventDefault();
            this.recoverPassword();
        });

        // Alternar visibilidad de contraseña (Estilo Velzon)
        $(this.ui.btnTogglePass).on('click', () => {
            const passInput = $(this.ui.password);
            const isPassword = passInput.attr('type') === 'password';
            passInput.attr('type', isPassword ? 'text' : 'password');
        });
    },

    // Alternar dinámicamente entre contenedores usando clases de Bootstrap 5
    toggleForms: function() {
        $(this.ui.containerLogin).toggleClass('d-none');
        $(this.ui.containerReset).toggleClass('d-none');
    },

    authenticate: function() {
        // 1. Armamos el Payload inyectando el discriminador VENDOR
        const payload = {
            txtEmail: $(this.ui.email).val().trim(),
            txtPassword: $(this.ui.password).val(),
            login_type: 'VENDOR' // <-- Directiva de ruteo en el Backend
        };

        // 2. Despachamos la petición utilizando el motor Net de Sys_Core
        // El motor se encargará de:
        // - Deshabilitar el botón y ponerle el spinner de FontAwesome/Remix Icon de forma nativa.
        // - Atenuar la UI si usas la clase .page-content.
        // - Capturar y formatear errores automáticamente (401, 403, 422) mediante handleError().
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/login`,
            payload: payload,
            $btn: $(this.ui.btnSubmit),
            successMsg: 'Acceso autorizado. Redirigiendo...',
            onDone: (response) => {
                Sys_Core.UI.notify(response.message, 'success');

                // Guardamos el token bajo la firma exacta que tu Sys_Core.Net requiere para inyectar los headers en futuras llamadas: 'mrp_token'
                localStorage.setItem('mrp_token', response.data.access_token);
                
                // Redirección limpia utilizando el helper de tu core
                Sys_Core.Navigation.to(response.data.redirect_to);
            }
        });
    },

    recoverPassword: function() {
        // Explotamos el sistema de alertas de tu Core
        Sys_Core.UI.alert(
            "Módulo en Desarrollo", 
            "La recuperación de contraseña para cuentas SRM estará disponible próximamente.", 
            "info"
        );
    }
};

// Inicializador
$(document).ready(function() {
    SrmLogin.init();
});