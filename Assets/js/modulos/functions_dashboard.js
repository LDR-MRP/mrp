/**
 * Admin Dashboard Controller
 * Desacoplado de PHP, utiliza Sys_Core para toda la lógica de red e hidratación.
 */
const AdminDashboard = {

    init: function() {
        Sys_Core.Auth.validateSession()
    }
};

// Inicialización
$(document).ready(() => AdminDashboard.init());