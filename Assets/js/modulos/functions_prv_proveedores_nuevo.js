$(document).ready(function() {
    $('#formProveedor').on('submit', function(e) {
        e.preventDefault();
        
        const data = $(this).serialize();
        
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/store`,
            method: 'POST',
            payload: data,
            successMsg: 'El proveedor ha sido registrado y auditado correctamente.',
            onDone: (res) => {
                // Regresar al listado tras éxito
                Sys_Core.Navigation.to('prv_proveedor');
            }
        });
    });
});