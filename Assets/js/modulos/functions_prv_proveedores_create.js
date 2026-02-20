$(document).ready(function() {
    $('#formProveedor').on('submit', function(e) {
        e.preventDefault();
        
        const data = new FormData(this);
        
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/store`,
            method: 'POST',
            payload: data,
            successMsg: 'El proveedor ha sido registrado y/o actualizado correctamente.',
            onDone: (res) => {
                setTimeout(() => {
                    Sys_Core.Navigation.to('prv_proveedor');
                }, 1500);
            }
        });
    });
});