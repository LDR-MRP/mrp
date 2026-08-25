let tableEjecucion;
let rawDataEjecucion = [];
let filtroActual = 'pendientes'; // 'pendientes' o 'historico'

document.addEventListener('DOMContentLoaded', function () {
    initTableEjecucion();
});

function initTableEjecucion() {
    tableEjecucion = $('#tableEjecucion').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_ejecucion/getEnviosDespacho",
            "dataSrc": function (json) {
                rawDataEjecucion = json || [];
                actualizarMetricasEjecucion(rawDataEjecucion);
                return filtrarDatosPorPestana(rawDataEjecucion, filtroActual);
            }
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
                "data": null,
                "render": function(data, type, row) {
                    if (filtroActual === 'historico') {
                        const fReal = row.fecha_salida_real;
                        if (!fReal || fReal === 'null') return '<span class="text-muted fs-11">Sin fecha</span>';
                        return '<span class="fs-12 text-success fw-bold"><i class="ri-calendar-check-line me-1"></i>' + fReal.replace('T', ' ') + '</span>';
                    } else {
                        const fProg = row.fecha_programada || row.fecha_confirmada_recoleccion;
                        if (!fProg || fProg === 'null') return '<span class="text-muted fs-11">Por programar</span>';
                        return '<span class="fs-12 text-primary fw-medium"><i class="ri-calendar-event-line me-1"></i>' + fProg.replace('T', ' ') + '</span>';
                    }
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
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    if (parseInt(row.id_estado) === 3 || parseInt(row.id_estado) === 5) {
                        return `<div class="text-end pe-3">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm" onClick="fntDespachar(${data}, '${row.folio}', '${row.fecha_salida_real || ''}')" title="Mesa de Despacho">
                                        <i class="ri-truck-line me-1"></i> Despacho / Salida
                                    </button>
                                </div>`;
                    }
                    
                    return `<div class="text-end pe-3">
                                <a href="${base_url}/Lgs_evidencias" class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold" title="Ver Histórico y Evidencias">
                                    <i class="ri-file-list-3-line me-1"></i> Manifiesto / Evidencias
                                </a>
                            </div>`;
                }
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });
}

function filtrarDatosPorPestana(data, tipo) {
    if (!Array.isArray(data)) return [];
    if (tipo === 'historico') {
        return data.filter(e => parseInt(e.id_estado) === 6 || parseInt(e.id_estado) === 7);
    }
    // Pendientes por despachar (Aprobados 3 o Programados 5)
    return data.filter(e => parseInt(e.id_estado) === 3 || parseInt(e.id_estado) === 5);
}

function filtrarMesaDespacho(tipo) {
    filtroActual = tipo;
    const btnPend = document.getElementById('tab-btn-pendientes');
    const btnHist = document.getElementById('tab-btn-historico');
    const thFecha = document.getElementById('thFechaEjecucion');

    if (tipo === 'historico') {
        if (btnHist) { btnHist.classList.add('active', 'btn-primary'); btnHist.classList.remove('btn-outline-secondary'); }
        if (btnPend) { btnPend.classList.remove('active', 'btn-primary'); btnPend.classList.add('btn-outline-primary'); }
        if (thFecha) thFecha.innerText = 'Fecha Real Salida';
    } else {
        if (btnPend) { btnPend.classList.add('active', 'btn-primary'); btnPend.classList.remove('btn-outline-primary'); }
        if (btnHist) { btnHist.classList.remove('active', 'btn-primary'); btnHist.classList.add('btn-outline-secondary'); }
        if (thFecha) thFecha.innerText = 'Fecha Programada';
    }

    if (tableEjecucion) {
        tableEjecucion.clear();
        tableEjecucion.rows.add(filtrarDatosPorPestana(rawDataEjecucion, filtroActual));
        tableEjecucion.draw();
    }
}

function actualizarMetricasEjecucion(data) {
    if (!Array.isArray(data)) return;
    
    let pendientes = data.filter(e => parseInt(e.id_estado) === 3 || parseInt(e.id_estado) === 5).length;
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let historicoCount = data.filter(e => parseInt(e.id_estado) === 6 || parseInt(e.id_estado) === 7).length;
    let vinsEntregadosSum = data.reduce((acc, e) => acc + (parseInt(e.vins_entregados) || 0), 0);
    let completados = data.filter(e => parseInt(e.id_estado) === 7 || (parseInt(e.id_estado) === 6 && parseInt(e.vins_entregados) === parseInt(e.total_vins))).length;

    if (document.getElementById('cardDespPendientes')) document.getElementById('cardDespPendientes').innerText = pendientes;
    if (document.getElementById('cardDespTransito')) document.getElementById('cardDespTransito').innerText = transito;
    if (document.getElementById('cardVinsEntregados')) document.getElementById('cardVinsEntregados').innerText = vinsEntregadosSum;
    if (document.getElementById('cardDespCompletados')) document.getElementById('cardDespCompletados').innerText = completados;

    if (document.getElementById('badgeCountPendientes')) document.getElementById('badgeCountPendientes').innerText = pendientes;
    if (document.getElementById('badgeCountHistorico')) document.getElementById('badgeCountHistorico').innerText = historicoCount;
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
    
    // Cambiar vista en lugar de modal
    document.getElementById('view-index-ejecucion').classList.add('d-none');
    document.getElementById('section-despacho-planilla').classList.remove('d-none');
}

function cerrarDespachoPlanilla() {
    document.getElementById('section-despacho-planilla').classList.add('d-none');
    document.getElementById('view-index-ejecucion').classList.remove('d-none');
    tableEjecucion.ajax.reload(null, false);
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
                            let isConfirmed = (parseInt(vin.confirmado) === 1 || vin.estado_unidad_fisico === 'EN_ENTREGAS' || vin.estado_unidad_fisico === 'EN_RUTA' || vin.estado_unidad_fisico === 'ENTREGADO');

                            let statusConfirm = isConfirmed 
                                ? `<span class="badge bg-soft-success text-success border border-success px-3 py-2 rounded-pill fs-12"><i class="ri-check-line me-1"></i> En Madrina</span>` 
                                : `<span class="badge bg-soft-warning text-warning border border-warning px-3 py-2 rounded-pill fs-12"><i class="ri-time-line me-1"></i> En Patio</span>`;
                            
                            let btnConfirm = isConfirmed
                                ? `
                                <div class="d-flex flex-column gap-1 align-items-center">
                                    <span class="badge bg-soft-secondary text-muted px-3 py-1 rounded-pill mb-1"><i class="ri-check-double-line me-1"></i> Validado</span>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-info rounded-pill px-2" onclick="verEvidencias(${idEnvio}, ${vin.id_unidad}, '${vin.vin}');" title="Ver Evidencias">
                                            <i class="ri-image-line"></i> Ver
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2" onclick="revertirValidacion(${idEnvio}, ${vin.id_unidad}, '${vin.vin}');" title="Deshacer Validación">
                                            <i class="ri-arrow-go-back-line"></i> Revertir
                                        </button>
                                    </div>
                                </div>
                                `
                                : `<button class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" id="btn-val-${vin.id_unidad}" onclick="abrirInspeccionAdmin(${idEnvio}, ${vin.id_unidad}, '${vin.vin}');"><i class="ri-camera-lens-line me-1"></i> Evidencia / Validar</button>`;

                            let evidBadge = (parseInt(vin.total_evidencias) > 0)
                                ? `<span class="badge bg-soft-info text-info border border-info rounded-pill px-2 py-1 ms-1" title="Evidencias fotográficas registradas"><i class="ri-camera-lens-line me-1"></i>${vin.total_evidencias} fotos</span>`
                                : '';

                            htmlBody += `
                                <tr>
                                    <td class="text-center"><span class="badge bg-primary fs-11 px-2 py-1">Pos #${vin.posicion_acomodo || 1}</span></td>
                                    <td><strong class="text-primary fs-12 font-monospace">${vin.vin}</strong> ${evidBadge}</td>
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
                    cerrarDespachoPlanilla();
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
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    let isSuccess = (objData.status === 'success' || objData.status === true || objData.code === 200);
                    if (isSuccess) {
                        if (objData.data && objData.data.en_transito) {
                            Swal.fire("¡Todos los VINs cargados!", objData.message || objData.msg, "success");
                            cerrarDespachoPlanilla();
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
            } else {
                try {
                    let errObj = JSON.parse(request.responseText);
                    Swal.fire("Error", errObj.message || "Error al validar la carga del VIN.", "error");
                } catch (e) {
                    Swal.fire("Error", "Error al procesar la confirmación en el servidor.", "error");
                }
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

function fntResetPrueba(idEnvio) {
    idEnvio = idEnvio || document.getElementById('id_envio_despacho').value || 16;
    Swal.fire({
        title: '¿Reiniciar estatus para prueba?',
        text: 'El envío regresará a estado "Aprobado" y todas las unidades a "En Patio".',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reiniciar prueba',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(base_url + '/Lgs_ejecucion/resetPrueba/' + idEnvio)
                .then(r => r.json())
                .then(res => {
                    Swal.fire('¡Estatus Reiniciado!', res.message || 'Listo para probar el flujo de despacho nuevamente.', 'success');
                    cargarAcomodoPlanta(idEnvio);
                    tableEjecucion.ajax.reload();
                })
                .catch(() => {
                    Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
                });
        }
    });
}

function abrirInspeccionAdmin(idEnvio, idUnidad, vin) {
    document.getElementById('formInspeccionVin').reset();
    document.getElementById('chk_id_envio').value = idEnvio;
    document.getElementById('chk_id_unidad').value = idUnidad;
    document.getElementById('lblVinInspeccion').innerText = vin;
    document.getElementById('vin_confirmado').value = vin; // Para que coincida directamente en la validación
    
    // Ocultar previsualizaciones de imágenes obligatorias
    ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
        let imgEl = document.getElementById(`img_${pos}`);
        let icoEl = document.getElementById(`ico_${pos}`);
        if(imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
        if(icoEl) icoEl.classList.remove('d-none');
    });

    // Limpiar extras dinámicos
    document.getElementById('contenedor_extras_admin').innerHTML = '';
    window.extraEvidenciaCount = 0;

    const modal = new bootstrap.Modal(document.getElementById('modalInspeccionVin'));
    modal.show();
}

function triggerFile(idInput) {
    document.getElementById(idInput).click();
}

function previewImage(input, pos) {
    const img = document.getElementById(`img_${pos}`);
    const ico = document.getElementById(`ico_${pos}`);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.remove('d-none');
            ico.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function guardarInspeccionAdmin() {
    const vinConfirmado = document.getElementById('vin_confirmado').value.trim();
    const vinEsperado = document.getElementById('lblVinInspeccion').innerText.trim();

    if (vinConfirmado !== vinEsperado) {
        Swal.fire("Atención", "El VIN no coincide.", "error");
        return;
    }

    // Validar fotos obligatorias
    const inputsFotos = ['file_frente', 'file_atras', 'file_lateral_izq', 'file_lateral_der', 'file_odometro'];
    for(let i=0; i<inputsFotos.length; i++) {
        if (document.getElementById(inputsFotos[i]).files.length === 0) {
            Swal.fire("Atención", "Debe cargar todas las fotos de inspección obligatorias antes de guardar.", "warning");
            return;
        }
    }

    const form = document.getElementById('formInspeccionVin');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Guardando Inspección...',
        text: 'Subiendo evidencias al servidor',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(base_url + '/Lgs_ejecucion/guardarChecklistTrasladista', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success' || res.status === true) {
            Swal.fire("¡Evidencia Subida!", "Unidad verificada y cargada exitosamente.", "success");
            const modalEl = document.getElementById('modalInspeccionVin');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            
            // Refrescar planilla
            cargarAcomodoPlanta(document.getElementById('chk_id_envio').value);
            tableEjecucion.ajax.reload(null, false);
        } else {
            Swal.fire("Error", res.message || res.msg || "No se pudo guardar la inspección.", "error");
        }
    })
    .catch(err => {
        Swal.fire("Error", "Falla de red o de servidor.", "error");
    });
}

function agregarEvidenciaExtraAdmin() {
    window.extraEvidenciaCount = (window.extraEvidenciaCount || 0) + 1;
    let count = window.extraEvidenciaCount;
    let posId = 'extra_' + count;
    
    let html = `
        <div class="col-md-4 col-6" id="div_${posId}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fs-11 text-muted fw-bold">Adicional ${count}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 m-0 text-decoration-none fs-13" onclick="document.getElementById('div_${posId}').remove();"><i class="ri-close-circle-line"></i></button>
            </div>
            <div class="photo-preview text-muted" onclick="triggerFile('file_${posId}')">
                <img id="img_${posId}" class="d-none">
                <i id="ico_${posId}" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
            </div>
            <input type="file" id="file_${posId}" name="${posId}" accept="image/*" class="d-none" onchange="previewImage(this, '${posId}')">
        </div>
    `;
    
    document.getElementById('contenedor_extras_admin').insertAdjacentHTML('beforeend', html);
}

function revertirValidacion(idEnvio, idUnidad, vin) {
    Swal.fire({
        title: '¿Revertir validación?',
        text: `Se regresará el VIN ${vin} a estado "En Patio" y podrás tomar evidencias nuevamente. Las evidencias anteriores se mantendrán en el historial.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, revertir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('id_unidad', idUnidad);

            fetch(base_url + '/Lgs_ejecucion/revertirVin', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success' || res.status === true) {
                    Swal.fire("Revertido", res.message || "El VIN regresó a En Patio.", "success");
                    cargarAcomodoPlanta(idEnvio);
                    tableEjecucion.ajax.reload(null, false);
                } else {
                    Swal.fire("Error", res.message || "No se pudo revertir.", "error");
                }
            })
            .catch(() => Swal.fire("Error", "Problema de conexión", "error"));
        }
    });
}

function verEvidencias(idEnvio, idUnidad, vin) {
    document.getElementById('titleModalVerEvidencias').innerHTML = `Inspección de Unidad: <span class="text-primary font-monospace">${vin}</span>`;
    const subModal = document.getElementById('subModalVerEvidencias');
    if (subModal) subModal.innerText = `Envío #${idEnvio} · Fotografías y observaciones de inspección`;

    const contenedorObs = document.getElementById('contenedorObservacionesEvidencias');
    const grid = document.getElementById('gridVerEvidencias');
    
    if (contenedorObs) contenedorObs.innerHTML = '';
    grid.innerHTML = '<div class="text-center w-100 py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted fs-13">Cargando evidencias e inspección...</p></div>';
    
    let modal = new bootstrap.Modal(document.getElementById('modalVerEvidencias'));
    modal.show();

    fetch(base_url + '/Lgs_ejecucion/getEvidenciasUnidad/' + idEnvio + '/' + idUnidad)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === true) {
                const data = res.data || [];
                if (data.length === 0) {
                    if (contenedorObs) contenedorObs.innerHTML = '';
                    grid.innerHTML = '<div class="alert alert-info w-100 text-center py-4 rounded-4 shadow-sm"><i class="ri-information-line fs-2 d-block mb-2"></i>No hay evidencias ni fotografías registradas para este VIN.</div>';
                    return;
                }

                // 1. Mostrar Bloque de Observaciones / Comentarios
                const primerItemConComentarios = data.find(item => item.comentarios && item.comentarios.trim().length > 0);
                const comentarioTexto = primerItemConComentarios ? primerItemConComentarios.comentarios.trim() : '';

                if (contenedorObs) {
                    if (comentarioTexto) {
                        contenedorObs.innerHTML = `
                            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #FFFBEB; border-left: 5px solid #F59E0B !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="p-2 bg-warning text-white rounded-3 shadow-xs">
                                            <i class="ri-chat-1-line fs-20"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fs-12 fw-bold text-uppercase text-warning-emphasis" style="letter-spacing: 0.5px;">Observaciones / Comentarios</span>
                                            <p class="fs-14 fw-semibold text-dark mb-0 mt-1">${comentarioTexto}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        contenedorObs.innerHTML = `
                            <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                                <div class="card-body py-2 px-3 d-flex align-items-center gap-2 text-muted">
                                    <i class="ri-checkbox-circle-fill text-success fs-18"></i>
                                    <span class="fs-13 fw-semibold text-secondary">Sin observaciones ni comentarios reportados en la inspección.</span>
                                </div>
                            </div>
                        `;
                    }
                }

                // 2. Mapeo de tipos y títulos visibles
                const MAPA_FOTOS = {
                    'frente':       { orden: 1, titulo: '1. Frente',               icono: 'ri-car-line',              badge: 'bg-primary' },
                    'atras':        { orden: 2, titulo: '2. Atrás / Posterior',    icono: 'ri-car-line',              badge: 'bg-primary' },
                    'lateral_izq':  { orden: 3, titulo: '3. Lateral Izquierdo',    icono: 'ri-arrow-left-circle-line', badge: 'bg-primary' },
                    'lateral_der':  { orden: 4, titulo: '4. Lateral Derecho',      icono: 'ri-arrow-right-circle-line',badge: 'bg-primary' },
                    'odometro':     { orden: 5, titulo: '5. Odómetro / Km',        icono: 'ri-dashboard-3-line',      badge: 'bg-primary' }
                };

                const fotosPrincipales = [];
                const fotosExtras = [];

                data.forEach(ev => {
                    let tipo = (ev.tipo_foto || '').toLowerCase().trim();
                    
                    // Identificar si es foto principal de inspección
                    if (MAPA_FOTOS[tipo]) {
                        fotosPrincipales.push({
                            ...ev,
                            info: MAPA_FOTOS[tipo]
                        });
                    } else {
                        // Extra o foto adicional
                        let numExtra = tipo.replace('extra_', '');
                        let tituloExtra = tipo.startsWith('extra_') ? `Fotografía Adicional #${numExtra}` : (ev.observaciones || 'Fotografía Adicional');
                        fotosExtras.push({
                            ...ev,
                            info: {
                                orden: 99,
                                titulo: tituloExtra,
                                icono: 'ri-camera-lens-line',
                                badge: 'bg-warning text-dark'
                            }
                        });
                    }
                });

                // Ordenar fotos principales de 1 a 5
                fotosPrincipales.sort((a, b) => a.info.orden - b.info.orden);

                // Función auxiliar para renderizar cada tarjeta de foto
                const renderCardFoto = (item) => {
                    let pathLimpio = (item.ruta_archivo || '').replace(/^\/+/, '');
                    let baseUrlImg = pathLimpio.startsWith('http') ? pathLimpio : (base_url + '/' + pathLimpio);
                    
                    return `
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white" style="transition: transform 0.2s ease, box-shadow 0.2s ease;">
                                <div class="card-header bg-light border-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                    <span class="badge ${item.info.badge} px-2 py-1 fs-12 fw-bold rounded-pill">
                                        <i class="${item.info.icono} me-1"></i> ${item.info.titulo}
                                    </span>
                                    <small class="text-muted fs-11">${item.created_at ? item.created_at.split(' ')[1] || '' : ''}</small>
                                </div>
                                <div class="position-relative overflow-hidden group-img" style="height: 180px; background: #E2E8F0;">
                                    <a href="${baseUrlImg}" target="_blank" class="d-block w-100 h-100 text-decoration-none">
                                        <img src="${baseUrlImg}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='${base_url}/Assets/images/portada__2.jpg'" alt="${item.info.titulo}">
                                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-25 opacity-0 hover-opacity transition" style="transition: opacity 0.2s;">
                                            <span class="btn btn-sm btn-light rounded-pill fw-bold shadow"><i class="ri-zoom-in-line me-1"></i> Ampliar</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="card-footer bg-white border-0 py-2 px-3 text-center">
                                    <small class="text-muted fs-11">${item.created_at || ''}</small>
                                </div>
                            </div>
                        </div>
                    `;
                };

                let htmlContent = '';

                // Renderizar segmento de fotos principales
                if (fotosPrincipales.length > 0) {
                    htmlContent += `
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="ri-shield-check-line text-primary fs-18"></i>
                                <h6 class="fw-bold text-dark mb-0 fs-14 text-uppercase letter-spacing-1">Fotos Principales de Inspección (5 Puntos)</h6>
                                <span class="badge bg-soft-primary text-primary rounded-pill ms-auto">${fotosPrincipales.length} Registradas</span>
                            </div>
                            <div class="row g-3">
                                ${fotosPrincipales.map(renderCardFoto).join('')}
                            </div>
                        </div>
                    `;
                }

                // Renderizar segmento de fotos extras
                if (fotosExtras.length > 0) {
                    htmlContent += `
                        <div class="mt-4 pt-2">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="ri-camera-3-line text-warning fs-18"></i>
                                <h6 class="fw-bold text-dark mb-0 fs-14 text-uppercase letter-spacing-1">Fotografías Adicionales / Extras</h6>
                                <span class="badge bg-soft-warning text-warning-emphasis rounded-pill ms-auto">${fotosExtras.length} Extras</span>
                            </div>
                            <div class="row g-3">
                                ${fotosExtras.map(renderCardFoto).join('')}
                            </div>
                        </div>
                    `;
                }

                grid.innerHTML = htmlContent;
            } else {
                grid.innerHTML = '<div class="alert alert-danger w-100 text-center py-3">Error al cargar las evidencias del VIN.</div>';
            }
        })
        .catch(() => {
            grid.innerHTML = '<div class="alert alert-danger w-100 text-center py-3">Falla al comunicar con el servidor.</div>';
        });
}
