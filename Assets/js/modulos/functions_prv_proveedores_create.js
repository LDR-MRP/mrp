$(document).ready(function() {
    $('#formProveedor').on('submit', function(e) {
        e.preventDefault();
        
        const data = new FormData(this);
        const payload = Object.fromEntries(data.entries());
        payload.notificar_compras = $('#notificar_compras').is(':checked') ? 1 : 0;
        
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/registrarProveedor`,
            payload: payload,
            successMsg: 'El proveedor ha sido registrado y/o actualizado correctamente.',
            onDone: (res) => {
                setTimeout(() => {
                    Sys_Core.Navigation.to('prv_proveedor');
                }, 1500);
            }
        });
    });
});

const catalogos = [
    { url: 'Catalogo/condiciones_pago', selector: '[name="id_condicion_pago"]' },
    { url: 'Catalogo/cuentas_contables', selector: '[name="id_cuenta_contable"]' },
    { url: 'SatCatalogo/tipos_personas', selector: '[name="id_tipo_persona"]' }
];

catalogos.forEach(cat => {
    Sys_Core.Net.get({
        url: `${base_url}/${cat.url}`,
        silent: true,
        onSuccess: (res) => Sys_Core.UI.fillSelect(cat.selector, res.data)
    });
});

const cascade = {
    init: function() {
        this.events();
    },

    events: function() {
        $('#cp').on('keyup', function() {
            const cp = $(this).val();
            if (cp.length === 5) {
                cascade.buscarCP(cp);
            }
        });
        
        $('select[name="id_tipo_persona"]').on('change', function() {
            const tipoPersona = $(this).val();
            cascade.buscarRegimen(tipoPersona);
        })
    },

    buscarCP: function(cp) {
        Sys_Core.Net.get({
            url: `${base_url}/catalogo/codigos_postales/${cp}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status) {
                    $('#estado').val(res.data.estado);
                    $('#ciudad').val(res.data.ciudad);
                    $('#municipio').val(res.data.municipio);

                    Sys_Core.UI.fillSelect('#colonia', res.data.colonias, {
                        valueField: 'asentamiento',
                        textField: 'asentamiento',
                        placeholder: 'Seleccione colonia...'
                    });
                    
                    Sys_Core.UI.notify('Ubicación localizada', 'success');
                } else {
                    Sys_Core.UI.notify('Código Postal no encontrado', 'warning');
                }
            }
        });
    },

    buscarRegimen: function(tipoPersona) {
        Sys_Core.Net.get({
            url: `${base_url}/SatCatalogo/regimenes_fiscales/${tipoPersona}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status) {

                    Sys_Core.UI.fillSelect('#id_regimen_fiscal', res.data, {
                        valueField: 'id',
                        textField: 'nombre',
                        placeholder: 'Seleccione régimen...'
                    });
                    
                    Sys_Core.UI.notify('Régimen localizado', 'success');
                } else {
                    Sys_Core.UI.notify('Régimen no encontrado', 'warning');
                }
            }
        })
    }
};

$(document).ready(() => cascade.init());