const AdminDashboard = {
    init: function() {
        if (typeof Sys_Core !== 'undefined' && Sys_Core.Auth) {
            // Solo validar si la cookie existe para no chocar con la sesión PHP legacy
            if (document.cookie.includes('mrp_token=')) {
                Sys_Core.Auth.validateSession();
            }
        }
    }
};

// Inicialización
$(document).ready(() => AdminDashboard.init());