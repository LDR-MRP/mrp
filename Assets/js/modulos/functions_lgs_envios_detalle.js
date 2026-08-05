document.addEventListener('DOMContentLoaded', function () {
    initSortables();
});

function initSortables() {
    // 1. Contenedor de VINs Disponibles
    const pool = document.getElementById('vins-disponibles');
    if (pool) {
        new Sortable(pool, {
            group: 'shared', // set both lists to same group
            animation: 150,
            ghostClass: 'sortable-ghost'
        });
    }

    // 2. Contenedores de Madrinas (pueden ser varias)
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    vehiculos.forEach(v => {
        new Sortable(v, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: function (evt) {
                // Evento cuando un VIN es soltado en esta madrina
                let item = evt.item;
                console.log('VIN agregado a la madrina: ', item.getAttribute('data-id-unidad'));
                actualizarConteo(evt.to);
            },
            onRemove: function (evt) {
                // Evento cuando un VIN es quitado de esta madrina
                actualizarConteo(evt.from);
            },
            onSort: function (evt) {
                // Evento cuando cambia el orden interno
                console.log('El orden ha cambiado');
            }
        });
    });
}

function actualizarConteo(listaUl) {
    // Busca el badge de conteo en el DOM relativo al UL
    const container = listaUl.closest('div.border');
    if(container) {
        const badge = container.querySelector('.badge');
        const items = listaUl.querySelectorAll('li').length;
        if(badge) {
            badge.innerHTML = items + ' VINs';
        }
    }
}

function agregarVehiculo() {
    // TODO: Lógica para abrir modal y seleccionar un chofer o madrina adicional
    // para inyectar su caja HTML y hacerla Sortable.
    Swal.fire('Info', 'Aquí se abrirá un modal para elegir la Madrina/Chofer del catálogo.', 'info');
}

function guardarAcomodo() {
    const idEnvio = document.getElementById('id_envio').value;
    const asignaciones = [];

    // Recorrer las madrinas
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    vehiculos.forEach(v => {
        const idMadrina = v.getAttribute('data-id-madrina');
        const items = v.querySelectorAll('li');
        
        let posicion = 1;
        items.forEach(li => {
            asignaciones.push({
                id_unidad: li.getAttribute('data-id-unidad'),
                id_madrina: idMadrina,
                posicion_acomodo: posicion
                // id_destino se tomaría del data-* del VIN (si tuvieran distinto destino)
            });
            posicion++;
        });
    });

    console.log("Payload a guardar: ", asignaciones);

    if (asignaciones.length === 0) {
        Swal.fire("Atención", "No has asignado ningún VIN a las madrinas.", "warning");
        return;
    }

    // Aquí iría el AJAX (fetch / XMLHttpRequest) hacia Lgs_envios/storeAsignaciones
    Swal.fire({
        title: 'Guardando...',
        text: 'Registrando asignaciones y calculando costos.',
        icon: 'info',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        Swal.fire("¡Éxito!", "Se han asignado los VINs correctamente.", "success");
    });
}
