let tableEnvios;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable
    tableEnvios = $('#tableEnvios').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_envios/getEnvios",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_envio" },
            { "data": "folio" },
            { "data": "tipo_traslado" },
            { "data": "motivo" },
            { "data": "trasladista" },
            { "data": "origen" },
            { "data": "total_vins" },
            { 
                "data": "costo_total",
                "render": function (data, type, row) {
                    if (data == null) return '$0.00';
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-secondary">Creado</span>'; break;
                        case 2: badge = '<span class="badge bg-warning text-dark">En Revisión</span>'; break;
                        case 6: badge = '<span class="badge bg-primary">En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-success">Entregado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    // Botón para entrar al detalle/acomodo de VINs
                    return `<div class="text-center">
                                <button class="btn btn-sm btn-outline-info" onClick="fntViewEnvio(${data})" title="Ver / Acomodar VINs">
                                    <i class="ri-truck-line"></i> Acomodo
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onClick="fntDelEnvio(${data})" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>`;
                }
            }
        ],
        "respose": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // Cargar Catálogos Iniciales
    cargarProveedoresTrasladistas();
    cargarOrigenes();
});

function openModal() {
    document.querySelector('#id_envio').value = "";
    document.querySelector('.modal-header').classList.replace("headerUpdate", "headerRegister");
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML = "Guardar Envío";
    document.querySelector('#titleModal').innerHTML = "Nuevo Envío";
    document.querySelector("#formEnvio").reset();
    $('#modalFormEnvio').modal('show');
}

function saveEnvio() {
    let id_tipo_traslado = document.querySelector('#id_tipo_traslado').value;
    let id_motivo = document.querySelector('#id_motivo').value;
    let id_proveedor = document.querySelector('#id_proveedor').value;
    let id_origen = document.querySelector('#id_origen').value;

    if (id_tipo_traslado == '' || id_motivo == '' || id_proveedor == '' || id_origen == '') {
        Swal.fire("Atención", "Todos los campos marcados con (*) son obligatorios.", "error");
        return false;
    }

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_envios/store';
    let formData = new FormData(document.querySelector("#formEnvio"));

    // Mostrar loader
    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading()
        }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                $('#modalFormEnvio').modal("hide");
                document.querySelector("#formEnvio").reset();
                Swal.fire("Envíos", objData.msg, "success");
                tableEnvios.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

// Helpers para Selects alimentados por el backend
function cargarProveedoresTrasladistas() {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_envios/getCatalogos';
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                // Llenar Trasladistas
                let htmlProv = '<option value="">Seleccione Trasladista...</option>';
                objData.data.proveedores.forEach(p => {
                    htmlProv += `<option value="${p.id}">${p.nombre}</option>`;
                });
                document.getElementById('id_proveedor').innerHTML = htmlProv;

                // Llenar Orígenes
                let htmlOrig = '<option value="">Seleccione Origen...</option>';
                objData.data.origenes.forEach(o => {
                    htmlOrig += `<option value="${o.id}">${o.nombre}</option>`;
                });
                document.getElementById('id_origen').innerHTML = htmlOrig;

                // Llenar Tipos Traslado
                let htmlTipos = '<option value="">Seleccione Tipo...</option>';
                objData.data.tipos_traslado.forEach(t => {
                    htmlTipos += `<option value="${t.id}">${t.nombre}</option>`;
                });
                document.getElementById('id_tipo_traslado').innerHTML = htmlTipos;

                // Llenar Motivos
                let htmlMotivos = '<option value="">Seleccione Motivo...</option>';
                objData.data.motivos.forEach(m => {
                    htmlMotivos += `<option value="${m.id}">${m.nombre}</option>`;
                });
                document.getElementById('id_motivo').innerHTML = htmlMotivos;
            }
        }
    }
}

function cargarOrigenes() {
    // Ya agrupado en cargarProveedoresTrasladistas arriba
}

function fntViewEnvio(idEnvio) {
    // Redirigir a la vista de detalle para arrastrar y soltar VINs
    window.location.href = base_url + '/Lgs_envios/detalle/' + idEnvio;
}

function fntDelEnvio(idEnvio) {
    Swal.fire({
        title: '¿Eliminar Envío?',
        text: "¿Realmente desea eliminar este registro?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Petición AJAX para borrar (Soft Delete)
            Swal.fire('Eliminado!', 'El registro ha sido eliminado (Simulación).', 'success');
        }
    });
}
