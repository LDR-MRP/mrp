let tableEjecucion;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Ejecución
    tableEjecucion = $('#tableEjecucion').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_ejecucion/getEnviosDespacho",
            "dataSrc": ""
        },
        "columns": [
            { "data": "folio" },
            { "data": "tipo_traslado" },
            { "data": "origen" },
            { "data": "trasladista" },
            { 
                "data": null,
                "render": function (data, type, row) {
                    let total = parseInt(row.total_vins) || 0;
                    let entregados = parseInt(row.vins_entregados) || 0;
                    let porcentaje = total > 0 ? Math.round((entregados / total) * 100) : 0;
                    
                    let bgClass = porcentaje === 100 ? 'bg-success' : 'bg-info';

                    return `<div>
                                <small class="fw-semibold">${entregados} / ${total} VINs (${porcentaje}%)</small>
                                <div class="progress progress-sm mt-1">
                                    <div class="progress-bar ${bgClass}" role="progressbar" style="width: ${porcentaje}%"></div>
                                </div>
                            </div>`;
                }
            },
            { 
                "data": "fecha_salida_real",
                "render": function (data, type, row) {
                    return data ? data : '<span class="text-muted italic">Pendiente de salida</span>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 3: badge = '<span class="badge bg-success">Aprobado (Listo para Despacho)</span>'; break;
                        case 6: badge = '<span class="badge bg-primary">En Tránsito</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    return `<div class="text-center">
                                <button class="btn btn-sm btn-primary" onClick="fntDespachar(${data}, '${row.folio}', '${row.fecha_salida_real || ''}')" title="Mesa de Despacho">
                                    <i class="ri-truck-line"></i> Despachar / Entregas
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
});

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
    document.getElementById('bodyAcomodoPlanta').innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando planilla de acomodo...</td></tr>';

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                objData.data.forEach(vin => {
                    let statusConfirm = vin.confirmado == 1 
                        ? `<span class="badge bg-success-subtle text-success border border-success"><i class="ri-check-line me-1"></i> Entregado (${vin.fecha_confirmacion || 'Ok'})</span>` 
                        : `<span class="badge bg-warning-subtle text-warning border border-warning"><i class="ri-time-line me-1"></i> En Espera</span>`;
                    
                    let btnConfirm = vin.confirmado == 1
                        ? `<button class="btn btn-sm btn-light text-muted" disabled><i class="ri-check-double-line"></i> Confirmado</button>`
                        : `<button class="btn btn-sm btn-outline-success" onclick="confirmarEntregaVin(${idEnvio}, ${vin.id_unidad});"><i class="ri-check-line"></i> Entregar a Trasladista</button>`;

                    htmlBody += `
                        <tr>
                            <td class="text-center"><span class="badge bg-primary rounded-circle px-2 py-1 fs-13">Pos #${vin.posicion_acomodo}</span></td>
                            <td class="fw-bold text-dark">${vin.vin}</td>
                            <td>${vin.modelo || 'N/A'}</td>
                            <td>${vin.color || 'N/A'}</td>
                            <td>${statusConfirm}</td>
                            <td class="text-center">${btnConfirm}</td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="6" class="text-center text-muted py-3">No hay VINs registrados en este envío.</td></tr>';
            }
            
            document.getElementById('bodyAcomodoPlanta').innerHTML = htmlBody;
        }
    }
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
        title: 'Guardando Despacho...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                Swal.fire("¡Despacho Registrado!", objData.msg, "success");
                tableEjecucion.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
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
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                if (objData.data && objData.data.en_transito) {
                    Swal.fire("¡Envío En Tránsito!", objData.msg, "success");
                    $('#modalDespachoPlanilla').modal('hide');
                } else {
                    Swal.fire("VIN Entregado", objData.msg, "success");
                    cargarAcomodoPlanta(idEnvio);
                }
                tableEjecucion.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}
