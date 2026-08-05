let tableEvidencias;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Evidencias
    tableEvidencias = $('#tableEvidencias').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_evidencias/getEnviosEvidencias",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "folio" },
            { "data": "trasladista" },
            { "data": "origen" },
            { "data": "total_vins" },
            { 
                "data": "total_evidencias",
                "render": function (data, type, row) {
                    return `<span class="badge bg-info-subtle text-info border border-info px-2 py-1"><i class="ri-image-line me-1"></i>${data || 0} Archivos</span>`;
                }
            },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 6: badge = '<span class="badge bg-primary">En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-success">Entregado (Completado)</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    let btnText = row.id_estado == 6 ? 'Gestionar / Entregar' : 'Ver Evidencias';
                    let btnIcon = row.id_estado == 6 ? 'ri-camera-lens-line' : 'ri-eye-line';
                    let btnClass = row.id_estado == 6 ? 'btn-primary' : 'btn-outline-secondary';

                    return `<div class="text-center">
                                <button class="btn btn-sm ${btnClass}" onClick="fntAbrirEvidencias(${data}, '${row.folio}', ${row.id_estado})" title="${btnText}">
                                    <i class="${btnIcon}"></i> ${btnText}
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

function fntAbrirEvidencias(idEnvio, folio, idEstado) {
    document.getElementById('id_envio_evidencia').value = idEnvio;
    document.getElementById('lblFolioEvidencia').innerText = folio;
    document.querySelector("#formEvidencia").reset();

    // Setea fecha actual en el campo de fecha de llegada
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('fecha_llegada_real').value = now.toISOString().slice(0, 16);

    // Ocultar sección de cierre si ya está entregado
    if (parseInt(idEstado) === 7) {
        document.getElementById('cardCierreDestino').style.display = 'none';
    } else {
        document.getElementById('cardCierreDestino').style.display = 'block';
    }

    cargarEvidenciasLista(idEnvio);
    $('#modalEvidencias').modal('show');
}

function cargarEvidenciasLista(idEnvio) {
    document.getElementById('bodyListaEvidencias').innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando evidencias...</td></tr>';

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_evidencias/getEvidenciasEnvio/' + idEnvio;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                objData.data.forEach(ev => {
                    let tipoBadge = parseInt(ev.tipo_evidencia) === 1 
                        ? '<span class="badge bg-info">Salida Planta</span>' 
                        : '<span class="badge bg-success">Llegada Destino</span>';

                    htmlBody += `
                        <tr>
                            <td>${tipoBadge}</td>
                            <td><a href="${ev.ruta_archivo}" target="_blank" class="text-primary text-truncate d-inline-block" style="max-width: 250px;"><i class="ri-link me-1"></i>${ev.ruta_archivo}</a></td>
                            <td>${ev.observaciones || '-'}</td>
                            <td><small>${ev.created_at}</small></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger" onclick="borrarEvidencia(${ev.id_evidencia}, ${idEnvio});">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="5" class="text-center text-muted py-3">No hay evidencias registradas para este envío.</td></tr>';
            }
            
            document.getElementById('bodyListaEvidencias').innerHTML = htmlBody;
        }
    }
}

function guardarEvidencia() {
    const ruta = document.getElementById('ruta_archivo').value.trim();
    if (!ruta) {
        Swal.fire("Atención", "Ingrese la URL o ruta del archivo de evidencia.", "warning");
        return;
    }

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_evidencias/store';
    let formData = new FormData(document.querySelector("#formEvidencia"));

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                Swal.fire("¡Evidencia Registrada!", objData.msg, "success");
                let idEnvio = document.getElementById('id_envio_evidencia').value;
                document.getElementById('ruta_archivo').value = '';
                document.getElementById('observaciones_ev').value = '';
                cargarEvidenciasLista(idEnvio);
                tableEvidencias.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

function borrarEvidencia(idEvidencia, idEnvio) {
    Swal.fire({
        title: '¿Eliminar Evidencia?',
        text: "¿Realmente desea borrar este registro multimedia?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/Lgs_evidencias/delete/' + idEvidencia;

            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        Swal.fire("Eliminado", objData.msg, "success");
                        cargarEvidenciasLista(idEnvio);
                        tableEvidencias.ajax.reload();
                    } else {
                        Swal.fire("Error", objData.msg, "error");
                    }
                }
            }
        }
    });
}

function confirmarCierreFinal() {
    const idEnvio = document.getElementById('id_envio_evidencia').value;
    const fechaLlegada = document.getElementById('fecha_llegada_real').value;

    if (!fechaLlegada) {
        Swal.fire("Atención", "Ingrese la fecha y hora real de llegada a destino.", "warning");
        return;
    }

    Swal.fire({
        title: 'Confirmar Entrega Final',
        text: "¿Confirmar que el envío llegó a su destino y finalizar monitoreo?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, finalizar envío'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('fecha_llegada_real', fechaLlegada);

            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/Lgs_evidencias/confirmarEntrega';

            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        $('#modalEvidencias').modal('hide');
                        Swal.fire("¡Envío Completado!", objData.msg, "success");
                        tableEvidencias.ajax.reload();
                    } else {
                        Swal.fire("Error", objData.msg, "error");
                    }
                }
            }
        }
    });
}
