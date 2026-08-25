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
            "dataSrc": ""
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
                    let btnClass = row.id_estado == 6 ? 'btn-primary rounded-pill px-3 fw-semibold shadow-sm' : 'btn-soft-primary rounded-pill px-3 fw-semibold';

                    return `<div class="text-end pe-3">
                                <button class="btn btn-sm ${btnClass}" onClick="fntAbrirEvidencias(${data}, '${row.folio}', ${row.id_estado})" title="${btnText}">
                                    <i class="${btnIcon} me-1"></i> ${btnText}
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
            actualizarMetricasEvidencias(settings.json || []);
        }
    });
});

function actualizarMetricasEvidencias(data) {
    if (!Array.isArray(data)) return;
    
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let entregadas = data.filter(e => parseInt(e.id_estado) === 7).length;
    let totalArchivos = data.reduce((acc, e) => acc + (parseInt(e.total_evidencias) || 0), 0);
    
    let total = data.length;
    let conEvidencia = data.filter(e => parseInt(e.total_evidencias) > 0).length;
    let cobertura = total > 0 ? Math.round((conEvidencia / total) * 100) : 0;

    if (document.getElementById('cardEvidTransito')) document.getElementById('cardEvidTransito').innerText = transito;
    if (document.getElementById('cardEvidTotalArchivos')) document.getElementById('cardEvidTotalArchivos').innerText = totalArchivos;
    if (document.getElementById('cardEvidEntregadas')) document.getElementById('cardEvidEntregadas').innerText = entregadas;
    if (document.getElementById('cardEvidCobertura')) document.getElementById('cardEvidCobertura').innerText = cobertura + '%';
}

function fntAbrirEvidencias(idEnvio, folio, idEstado) {
    document.getElementById('id_envio_evidencia').value = idEnvio;
    document.getElementById('lblFolioEvidencia').innerText = folio;
    document.querySelector("#formEvidencia").reset();

    // Setea fecha actual en el campo de fecha de llegada
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const inputFecha = document.getElementById('fecha_llegada_real');
    if (inputFecha) inputFecha.value = now.toISOString().slice(0, 16);

    // Ocultar sección de cierre si ya está entregado
    const cardCierre = document.getElementById('cardCierreDestino');
    if (cardCierre) {
        if (parseInt(idEstado) === 7) {
            cardCierre.style.display = 'none';
        } else {
            cardCierre.style.display = 'block';
        }
    }

    // Cargar selector de VINs del envío
    cargarVinsEnSelector(idEnvio);

    // Cargar evidencias asociadas
    cargarEvidenciasLista(idEnvio);
    $('#modalEvidencias').modal('show');
}

function cargarVinsEnSelector(idEnvio) {
    const select = document.getElementById('select_evid_unidad');
    if (!select) return;

    select.innerHTML = '<option value="">Cargando VINs...</option>';

    fetch(base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio)
        .then(response => response.json())
        .then(res => {
            let html = '<option value="">General del Envío (Todos)</option>';
            const vins = res.data || [];
            vins.forEach(v => {
                html += `<option value="${v.id_unidad}">${v.vin} (${v.modelo || 'Unidad'}) - Pos #${v.posicion_acomodo || 1}</option>`;
            });
            select.innerHTML = html;
        })
        .catch(() => {
            select.innerHTML = '<option value="">General del Envío (Todos)</option>';
        });
}

function cargarEvidenciasLista(idEnvio) {
    const tbody = document.getElementById('bodyListaEvidencias');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando evidencias...</td></tr>';

    fetch(base_url + '/Lgs_evidencias/getEvidenciasEnvio/' + idEnvio)
        .then(response => response.json())
        .then(objData => {
            let htmlBody = '';
            const evidencias = objData.data || [];
            
            if (objData.status && evidencias.length > 0) {
                evidencias.forEach(ev => {
                    let tipoBadge = parseInt(ev.tipo_evidencia) === 1 
                        ? '<span class="badge bg-info-subtle text-info border border-info px-2 py-1"><i class="ri-login-box-line me-1"></i>Salida / Patio</span>' 
                        : '<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i>Llegada / Destino</span>';

                    let vinBadge = ev.vin && ev.vin !== 'General de Envío'
                        ? `<span class="badge bg-light text-dark border font-monospace fs-12"><i class="ri-car-line me-1 text-primary"></i>${ev.vin}</span>`
                        : `<span class="badge bg-secondary-subtle text-muted fs-12">General Envío</span>`;

                    // Enlace / vista previa
                    let fileLink = ev.ruta_archivo.startsWith('http') ? ev.ruta_archivo : (base_url + '/' + ev.ruta_archivo.replace(/^\/+/, ''));
                    let previewBtn = `<a href="${fileLink}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fs-12" title="Abrir archivo"><i class="ri-external-link-line me-1"></i>Ver Archivo</a>`;

                    htmlBody += `
                        <tr>
                            <td>${tipoBadge}</td>
                            <td>${vinBadge}</td>
                            <td>${previewBtn}</td>
                            <td><span class="text-dark">${ev.observaciones || '-'}</span></td>
                            <td><small class="text-muted">${ev.created_at || '-'}</small></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="borrarEvidencia(${ev.id_evidencia}, ${idEnvio});" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="ri-image-line fs-2 d-block mb-1 opacity-50"></i>No hay evidencias registradas para este envío.</td></tr>';
            }
            
            tbody.innerHTML = htmlBody;
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al consultar evidencias.</td></tr>';
        });
}

function guardarEvidencia() {
    const fileInput = document.getElementById('evid_archivo');
    const idEnvio = document.getElementById('id_envio_evidencia').value;

    if (!idEnvio) {
        Swal.fire("Atención", "No se ha seleccionado un envío válido.", "warning");
        return;
    }

    if (!fileInput || fileInput.files.length === 0) {
        Swal.fire("Atención", "Debe seleccionar un archivo (imagen o PDF) para subir.", "warning");
        return;
    }

    const form = document.getElementById('formEvidencia');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Subiendo Evidencia...',
        text: 'Guardando archivo en el servidor',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(base_url + '/Lgs_evidencias/store', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(objData => {
        if (objData.status === 'success' || objData.status === true) {
            Swal.fire("¡Evidencia Registrada!", objData.message || "Archivo subido correctamente.", "success");
            
            // Limpiar inputs
            fileInput.value = '';
            const obs = document.getElementById('observaciones_ev');
            if (obs) obs.value = '';

            cargarEvidenciasLista(idEnvio);
            if (tableEvidencias) tableEvidencias.ajax.reload();
        } else {
            Swal.fire("Error", objData.message || objData.msg || "No se pudo registrar la evidencia.", "error");
        }
    })
    .catch(() => {
        Swal.fire("Error", "Falla de comunicación al subir la evidencia.", "error");
    });
}

function borrarEvidencia(idEvidencia, idEnvio) {
    Swal.fire({
        title: '¿Eliminar Evidencia?',
        text: "¿Realmente desea borrar este registro multimedia?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(base_url + '/Lgs_evidencias/delete/' + idEvidencia, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    Swal.fire("Eliminado", objData.message || "Evidencia eliminada correctamente.", "success");
                    cargarEvidenciasLista(idEnvio);
                    if (tableEvidencias) tableEvidencias.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo eliminar la evidencia.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de red al eliminar la evidencia.", "error");
            });
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
        confirmButtonText: 'Sí, finalizar envío',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('fecha_llegada_real', fechaLlegada);

            fetch(base_url + '/Lgs_evidencias/confirmarEntrega', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    $('#modalEvidencias').modal('hide');
                    Swal.fire("¡Envío Completado!", objData.message || "El envío ha sido completado exitosamente.", "success");
                    if (tableEvidencias) tableEvidencias.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo confirmar la entrega.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Falla de red al confirmar entrega final.", "error");
            });
        }
    });
}
