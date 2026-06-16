/**
 * Admin Dashboard Controller
 * Desacoplado de PHP, utiliza Sys_Core para toda la lógica de red e hidratación.
 */
const AdminDashboard = {

    init: function() {
        // 1. Protección de ruta (Solo Staff interno)
        if (!Sys_Core.Auth.validateSession()) return;
    }
};

// Inicialización
$(document).ready(() => AdminDashboard.init());