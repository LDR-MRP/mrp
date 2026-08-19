let tableEjecucion;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Ejecución
    tableEjecucion = $('#tableEjecucion').DataTable({
        "aProcessing": true,
        "aServerSide": false,        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_ejecucion/getEnviosDespacho",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_envio" },
            { 
                "data": "folio",
                "render": function(data) {
                    return '<strong class="text-primary">' + data + '</strong>';
                }
            },
            { "data": "origen" },
            { "data": "trasladista" },
            { 
                "data": "tipo_traslado",
                "render": function(data) {
                    return '<span class="badge bg-soft-info text-info border">' + (data || 'Madrina') + '</span>';
                }
            },
            { 
                "data": null,
                "render": function (data, type, row) {
                    let total = parseInt(row.total_vins) || 0;
                    let entregados = parseInt(row.vins_entregados) || 0;
                    let porcentaje = total > 0 ? Math.round((entregados / total) * 100) : 0;
                    
                    let bgClass = porcentaje === 100 ? 'bg-success' : 'bg-primary';

                    return `<div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="fw-semibold text-dark">${entregados}/${total} VINs</small>
                                    <small class="text-muted">${porcentaje}%</small>
                                </div>
                                <div class="progress progress-sm" style="height: 6px;">
                                    <div class="progress-bar ${bgClass}" role="progressbar" style="width: ${porcentaje}%"></div>
                                </div>
                            </div>`;
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 3: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-checkbox-check-line me-1"></i>Envío Aprobado</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-calendar-event-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-primary text-primary fs-12"><i class="ri-truck-line me-1"></i>En Tránsito</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    if (parseInt(row.id_estado) === 3) {
                        return `<div class="text-end pe-3">
                                    <button class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold" onClick="fntProgramarRecoleccion(${data})" title="Programar Recolección">
                                        <i class="ri-calendar-event-line me-1"></i> Programar Recolección
                                    </button>
                                </div>`;
                    }
                    
                    return `<div class="text-end pe-3">
                                <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm" onClick="fntDespachar(${data}, '${row.folio}', '${row.fecha_salida_real || ''}')" title="Mesa de Despacho">
                                    <i class="ri-truck-line me-1"></i> Despacho / Salida
                                </button>
                            </div>`;
                }
            }
        ],
        "respose": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasEjecucion(settings.json || []);
        }
    });
});

function actualizarMetricasEjecucion(data) {
    if (!Array.isArray(data)) return;
    
    let pendientes = data.filter(e => parseInt(e.id_estado) === 3).length;
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let vinsEntregadosSum = data.reduce((acc, e) => acc + (parseInt(e.vins_entregados) || 0), 0);
    let completados = data.filter(e => parseInt(e.id_estado) === 6 && parseInt(e.vins_entregados) === parseInt(e.total_vins)).length;

    if (document.getElementById('cardDespPendientes')) document.getElementById('cardDespPendientes').innerText = pendientes;
    if (document.getElementById('cardDespTransito')) document.getElementById('cardDespTransito').innerText = transito;
    if (document.getElementById('cardVinsEntregados')) document.getElementById('cardVinsEntregados').innerText = vinsEntregadosSum;
    if (document.getElementById('cardDespCompletados')) document.getElementById('cardDespCompletados').innerText = completados;
}

function fntDespachar(idEnvio, folio, fechaSalida) {
    document.getElementById('id_envio_despacho').value = idEnvio;
    document.getElementById('lblFolioDespacho').innerText = folio;
    
    // Setea fecha salida si ya existe o la fecha actual
    if (fechaSalida && fechaSalida !== '') {
        document.getElementById('fecha_salida_real').value = fechaSalida.replace(' ', 'T');
    } else {
        let now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha_salida_real').value = now.toISOString().slice(0, 16);
    }
    
    cargarAcomodoPlanta(idEnvio);
    $('#modalDespachoPlanilla').modal('show');
}

function cargarAcomodoPlanta(idEnvio) {
    const bodyEl = document.getElementById('bodyAcomodoPlanta');
    if (!bodyEl) return;
    bodyEl.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando planilla de acomodo...</td></tr>';

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    let htmlBody = '';
                    let isSuccess = (objData.status === 'success' || objData.status === true || objData.code === 200);
                    let vins = (isSuccess && Array.isArray(objData.data)) ? objData.data : [];
                    
                    if (vins.length > 0) {
                        vins.forEach(vin => {
                            let statusConfirm = vin.confirmado == 1 
                                ? `<span class="badge bg-soft-success text-success border border-success px-3 py-2 rounded-pill fs-12"><i class="ri-check-line me-1"></i> Entregado (${vin.fecha_confirmacion || 'Ok'})</span>` 
                                : `<span class="badge bg-soft-warning text-warning border border-warning px-3 py-2 rounded-pill fs-12"><i class="ri-time-line me-1"></i> En Patio</span>`;
                            
                            let btnConfirm = vin.confirmado == 1
                                ? `<span class="badge bg-soft-secondary text-muted px-3 py-2 rounded-pill"><i class="ri-check-double-line me-1"></i> En Madrina</span>`
                                : `<button class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" onclick="confirmarEntregaVin(${idEnvio}, ${vin.id_unidad});"><i class="ri-checkbox-circle-line me-1"></i> Validar Carga</button>`;

                            htmlBody += `
                                <tr>
                                    <td class="text-center"><span class="badge bg-primary fs-11 px-2 py-1">Pos #${vin.posicion_acomodo || 1}</span></td>
                                    <td><strong class="text-primary fs-12">${vin.vin}</strong></td>
                                    <td><span class="badge bg-soft-secondary text-dark">${vin.modelo || 'Unidad'}</span></td>
                                    <td>${vin.color || 'Blanco'}</td>
                                    <td>${statusConfirm}</td>
                                    <td class="text-center">${btnConfirm}</td>
                                </tr>
                            `;
                        });
                    } else {
                        htmlBody = '<tr><td colspan="6" class="text-center text-muted py-3"><i class="ri-information-line me-1"></i> No hay VINs pendientes de validación en este envío.</td></tr>';
                    }
                    
                    bodyEl.innerHTML = htmlBody;
                } catch (e) {
                    console.error(e);
                    bodyEl.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al procesar la respuesta del servidor.</td></tr>';
                }
            } else {
                bodyEl.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al consultar el servidor (Código ' + request.status + ').</td></tr>';
            }
        }
    };
}

function guardarDespacho() {
    const idEnvio = document.getElementById('id_envio_despacho').value;
    const fecha = document.getElementById('fecha_salida_real').value;

    if (!fecha) {
        Swal.fire("Atención", "Ingrese la fecha y hora real de salida.", "warning");
        return;
    }

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_ejecucion/registrarDespacho';
    let formData = new FormData(document.querySelector("#formDespacho"));

    Swal.fire({
        title: 'Registrando Salida a Ruta...',
        text: 'Actualizando estatus del envío a En Tránsito',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                let isSuccess = (objData.status === 'success' || objData.status === true || objData.code === 200);
                if (isSuccess) {
                    $('#modalDespachoPlanilla').modal('hide');
                    Swal.fire("¡Despacho Registrado!", objData.message || objData.msg || "El envío ahora se encuentra En Tránsito", "success");
                    tableEjecucion.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || objData.msg || "Error al registrar el despacho", "error");
                }
            } catch (e) {
                Swal.fire("Error", "Respuesta inesperada del servidor", "error");
            }
        }
    };
}

function confirmarEntregaVin(idEnvio, idUnidad) {
    let formData = new FormData();
    formData.append('id_envio', idEnvio);
    formData.append('id_unidad', idUnidad);

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_ejecucion/confirmarVin';

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                let isSuccess = (objData.status === 'success' || objData.status === true || objData.code === 200);
                if (isSuccess) {
                    if (objData.data && objData.data.en_transito) {
                        Swal.fire("¡Todos los VINs cargados!", objData.message || objData.msg, "success");
                        $('#modalDespachoPlanilla').modal('hide');
                    } else {
                        cargarAcomodoPlanta(idEnvio);
                    }
                    tableEjecucion.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || objData.msg || "Error al confirmar", "error");
                }
            } catch (e) {
                console.error(e);
            }
        }
    };
}

function fntProgramarRecoleccion(idEnvio) {
    document.getElementById('rec_id_envio').value = idEnvio;
    document.getElementById('fecha_recoleccion').value = new Date().toISOString().split('T')[0];
    $('#modalProgramarRecoleccion').modal('show');
}

function guardarProgramacionRecoleccion() {
    const idEnvio = document.getElementById('rec_id_envio').value;
    const fecha = document.getElementById('fecha_recoleccion').value;

    if (!fecha) {
        Swal.fire("Atención", "Seleccione la fecha de recolección.", "warning");
        return;
    }

    let formData = new FormData();
    formData.append('id_envio', idEnvio);
    formData.append('fecha_recoleccion', fecha);

    Swal.fire({
        title: 'Programando Recolección...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(base_url + '/Lgs_ejecucion/confirmarRecoleccion', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success' || res.status === true) {
            Swal.fire("¡Programado!", "Fecha confirmada. Las unidades ahora se preparan en el área de entregas del patio origen.", "success");
            $('#modalProgramarRecoleccion').modal('hide');
            tableEjecucion.ajax.reload();
        } else {
            Swal.fire("Error", res.message || "No se pudo registrar la recolección.", "error");
        }
    })
    .catch(err => {
        Swal.fire("Error", "Error de comunicación con el servidor.", "error");
    });
}
