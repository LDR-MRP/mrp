function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-envios");
    const secForm = document.querySelector("#view-form-envios");

    if (view === 'form') {
        if (secGrid) secGrid.style.display = "none";
        if (secForm) secForm.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        if (secForm) secForm.style.display = "none";
        if (secGrid) secGrid.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function cancelFormEnvio() {
    document.querySelector("#formEnvio").reset();
    fntSwitchView('grid');
}

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
            { 
                "data": "folio",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || 'S/F') + '</span>';
                }
            },
            { "data": "tipo_traslado" },
            { "data": "motivo" },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || '-') + '</span>';
                }
            },
            { "data": "origen" },
            { 
                "data": "destino",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || 'Sin Destino') + '</span>';
                }
            },
            { 
                "data": "total_vins",
                "render": function(data) {
                    return '<span class="badge bg-primary fs-12">' + (data || 0) + ' VINs</span>';
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<span class="fw-bold text-success">$' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12">Creado</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12">En Revisión</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-12">En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12">Entregado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data) {
                    return `<div class="text-end">
                                <button class="btn btn-sm btn-soft-info me-1" onClick="fntViewEnvio(${data})" title="Ver / Acomodar VINs">
                                    <i class="ri-truck-line me-1"></i> Acomodo
                                </button>
                                <button class="btn btn-sm btn-soft-danger" onClick="fntDelEnvio(${data})" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>`;
                }
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasEnvios(settings.json || []);
        }
    });

    // Cargar Catálogos Iniciales
    cargarProveedoresTrasladistas();
});

function actualizarMetricasEnvios(data) {
    if (!Array.isArray(data)) return;
    
    let total = data.length;
    let creados = data.filter(e => parseInt(e.id_estado) === 1).length;
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let entregados = data.filter(e => parseInt(e.id_estado) === 7).length;

    if (document.getElementById('cardTotalEnvios')) document.getElementById('cardTotalEnvios').innerText = total;
    if (document.getElementById('cardEnviosCreados')) document.getElementById('cardEnviosCreados').innerText = creados;
    if (document.getElementById('cardEnviosTransito')) document.getElementById('cardEnviosTransito').innerText = transito;
    if (document.getElementById('cardEnviosEntregados')) document.getElementById('cardEnviosEntregados').innerText = entregados;
}

function openModal() {
    document.querySelector('#id_envio').value = "";
    document.querySelector('#btnText').innerHTML = "Guardar Envío";
    document.querySelector('#form-envio-title').innerHTML = "Crear Solicitud de Traslado";
    document.querySelector("#formEnvio").reset();
    fntSwitchView('form');
}

function saveEnvio() {
    let id_tipo_traslado = document.querySelector('#id_tipo_traslado').value;
    let id_motivo = document.querySelector('#id_motivo').value;
    let id_proveedor = document.querySelector('#id_proveedor').value;
    let id_origen = document.querySelector('#id_origen').value;
    let id_destino = document.querySelector('#id_destino') ? document.querySelector('#id_destino').value : '';

    if (id_tipo_traslado == '' || id_motivo == '' || id_proveedor == '' || id_origen == '' || id_destino == '') {
        Swal.fire("Atención", "Todos los campos marcados con (*) son obligatorios.", "error");
        return false;
    }

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/store';
    let formData = new FormData(document.querySelector("#formEnvio"));

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
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === 'success' || objData.status === true || objData.code === 200) {
                        document.querySelector("#formEnvio").reset();
                        Swal.fire("Envíos", objData.message || objData.msg || "Guardado exitosamente", "success");
                        if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                        fntSwitchView('grid');
                    } else {
                        Swal.fire("Error", objData.message || objData.msg || "Error al guardar el envío", "error");
                    }
                } catch (e) {
                    Swal.fire("Error", "Respuesta no válida del servidor.", "error");
                }
            } else {
                try {
                    let objData = JSON.parse(request.responseText);
                    Swal.fire("Error (" + request.status + ")", objData.message || objData.msg || "Ocurrió un error en el servidor.", "error");
                } catch (e) {
                    Swal.fire("Error (" + request.status + ")", "Error en el servidor al procesar la solicitud.", "error");
                }
            }
        }
    }
}

function cargarProveedoresTrasladistas() {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/getCatalogos';
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    let htmlProv = '<option value="">Seleccione Trasladista...</option>';
                    objData.data.proveedores.forEach(p => {
                        htmlProv += `<option value="${p.id}">${p.nombre}</option>`;
                    });
                    if (document.getElementById('id_proveedor')) document.getElementById('id_proveedor').innerHTML = htmlProv;

                    let htmlOrig = '<option value="">Seleccione Origen...</option>';
                    objData.data.origenes.forEach(o => {
                        htmlOrig += `<option value="${o.id}">${o.nombre}</option>`;
                    });
                    if (document.getElementById('id_origen')) document.getElementById('id_origen').innerHTML = htmlOrig;

                    let htmlTipos = '<option value="">Seleccione Tipo...</option>';
                    objData.data.tipos_traslado.forEach(t => {
                        htmlTipos += `<option value="${t.id}">${t.nombre}</option>`;
                    });
                    if (document.getElementById('id_tipo_traslado')) document.getElementById('id_tipo_traslado').innerHTML = htmlTipos;

                    let htmlMotivos = '<option value="">Seleccione Motivo...</option>';
                    objData.data.motivos.forEach(m => {
                        htmlMotivos += `<option value="${m.id}">${m.nombre}</option>`;
                    });
                    if (document.getElementById('id_motivo')) document.getElementById('id_motivo').innerHTML = htmlMotivos;

                    if (objData.data.destinos) {
                        let htmlDest = '<option value="">Seleccione Destino...</option>';
                        objData.data.destinos.forEach(d => {
                            htmlDest += `<option value="${d.id}">${d.nombre}</option>`;
                        });
                        if (document.getElementById('id_destino')) document.getElementById('id_destino').innerHTML = htmlDest;
                    }
                }
            } catch(e) {}
        }
    }
}

function fntViewEnvio(idEnvio) {
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
            Swal.fire('Eliminado!', 'El registro ha sido eliminado.', 'success');
        }
    });
}
