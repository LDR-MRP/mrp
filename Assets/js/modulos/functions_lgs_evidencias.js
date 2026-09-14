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
    
    const form = document.querySelector("#formEvidenciaEntrega");
    if (form) form.reset();

    // Limpiar previsualizaciones de fotos de entrega
    ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
        let imgEl = document.getElementById(`img_dest_${pos}`);
        let icoEl = document.getElementById(`ico_dest_${pos}`);
        if (imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
        if (icoEl) icoEl.classList.remove('d-none');
    });

    // Limpiar contenedor de extras
    const contExtras = document.getElementById('contenedor_extras_entrega');
    if (contExtras) contExtras.innerHTML = '';
    window.extraEntregaCount = 0;

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
            let html = '<option value="">-- Seleccionar Unidad a Entregar --</option>';
            const vins = res.data || [];
            vins.forEach(v => {
                let entregadoBadge = (v.estado_unidad_fisico === 'ENTREGADO') ? ' [Entregado]' : '';
                html += `<option value="${v.id_unidad}" data-vin="${v.vin}">${v.vin} (${v.modelo || 'Unidad'}) - Pos #${v.posicion_acomodo || 1}${entregadoBadge}</option>`;
            });
            select.innerHTML = html;
        })
        .catch(() => {
            select.innerHTML = '<option value="">-- Seleccionar Unidad a Entregar --</option>';
        });
}

function actualizarVinSeleccionado(select) {
    const selectedOption = select.options[select.selectedIndex];
    const vin = selectedOption ? (selectedOption.getAttribute('data-vin') || '') : '';
    const vinInput = document.getElementById('vin_confirmado_entrega');
    if (vinInput) vinInput.value = vin;
}

function triggerFileDest(idInput) {
    const el = document.getElementById(idInput);
    if (el) el.click();
}

function previewImageDest(input, pos) {
    const img = document.getElementById(`img_dest_${pos}`);
    const ico = document.getElementById(`ico_dest_${pos}`);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img) {
                img.src = e.target.result;
                img.classList.remove('d-none');
            }
            if (ico) ico.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function agregarExtraEntrega() {
    window.extraEntregaCount = (window.extraEntregaCount || 0) + 1;
    let count = window.extraEntregaCount;
    let posId = 'extra_dest_' + count;
    
    let html = `
        <div class="col-md-4 col-6" id="div_${posId}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fs-11 text-muted fw-bold">Adicional / Remisión ${count}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 m-0 text-decoration-none fs-13" onclick="document.getElementById('div_${posId}').remove();"><i class="ri-close-circle-line"></i></button>
            </div>
            <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_${posId}')">
                <img id="img_${posId}" class="d-none">
                <i id="ico_${posId}" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
            </div>
            <input type="file" id="file_${posId}" name="extra_${count}" accept="image/*,application/pdf" class="d-none" onchange="previewImageDest(this, '${posId}')">
        </div>
    `;
    
    const cont = document.getElementById('contenedor_extras_entrega');
    if (cont) cont.insertAdjacentHTML('beforeend', html);
}

function guardarInspeccionEntrega() {
    const idEnvio = document.getElementById('id_envio_evidencia').value;
    const selectUnidad = document.getElementById('select_evid_unidad');
    const idUnidad = selectUnidad ? selectUnidad.value : '';
    const vin = document.getElementById('vin_confirmado_entrega').value;

    if (!idEnvio) {
        Swal.fire("Atención", "No se ha seleccionado un envío válido.", "warning");
        return;
    }

    if (!idUnidad) {
        Swal.fire("Atención", "Por favor seleccione la unidad / VIN que está recibiendo o entregando.", "warning");
        return;
    }

    // Validar fotos obligatorias de entrega
    const inputsFotos = ['file_dest_frente', 'file_dest_atras', 'file_dest_lateral_izq', 'file_dest_lateral_der', 'file_dest_odometro'];
    let faltantes = 0;
    for(let i=0; i<inputsFotos.length; i++) {
        let el = document.getElementById(inputsFotos[i]);
        if (!el || el.files.length === 0) {
            faltantes++;
        }
    }

    if (faltantes > 0) {
        Swal.fire({
            title: "Fotos Incompletas",
            text: "Se recomienda adjuntar las 5 fotografías de inspección en destino (Frente, Atrás, Laterales y Odómetro). ¿Desea continuar de todos modos?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, guardar",
            cancelButtonText: "Completar fotos"
        }).then(res => {
            if (res.isConfirmed) {
                enviarInspeccionEntregaForm();
            }
        });
        return;
    }

    enviarInspeccionEntregaForm();
}

function enviarInspeccionEntregaForm() {
    const form = document.getElementById('formEvidenciaEntrega');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Guardando Evidencias...',
        text: 'Subiendo fotografías de entrega en destino',
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
            Swal.fire("¡Inspección de Entrega Registrada!", "Las evidencias de recepción y estado de la unidad fueron guardadas exitosamente.", "success");
            
            // Limpiar formulario
            form.reset();
            ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
                let imgEl = document.getElementById(`img_dest_${pos}`);
                let icoEl = document.getElementById(`ico_dest_${pos}`);
                if (imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
                if (icoEl) icoEl.classList.remove('d-none');
            });
            const contExtras = document.getElementById('contenedor_extras_entrega');
            if (contExtras) contExtras.innerHTML = '';

            const idEnvio = document.getElementById('id_envio_evidencia').value;
            cargarEvidenciasLista(idEnvio);
            cargarVinsEnSelector(idEnvio);
            if (tableEvidencias) tableEvidencias.ajax.reload();
        } else {
            Swal.fire("Error", res.message || res.msg || "No se pudieron guardar las evidencias.", "error");
        }
    })
    .catch(() => {
        Swal.fire("Error", "Falla de comunicación al guardar la inspección de entrega.", "error");
    });
}

function cargarEvidenciasLista(idEnvio) {
    const tbody = document.getElementById('bodyListaEvidencias');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando evidencias...</td></tr>';

    fetch(base_url + '/Lgs_evidencias/getEvidenciasEnvio/' + idEnvio)
        .then(response => response.json())
        .then(objData => {
            let htmlBody = '';
            const evidencias = objData.data || [];
            
            if (document.getElementById('badgeTotalEvidenciasModal')) {
                document.getElementById('badgeTotalEvidenciasModal').innerText = evidencias.length + ' Registros';
            }

            if (objData.status && evidencias.length > 0) {
                evidencias.forEach(ev => {
                    let tipoBadge = parseInt(ev.tipo_evidencia) === 1 
                        ? '<span class="badge bg-info-subtle text-info border border-info px-2 py-1"><i class="ri-login-box-line me-1"></i>Salida / Despacho</span>' 
                        : '<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="ri-checkbox-circle-fill me-1"></i>Recepción / Entrega</span>';

                    let vinBadge = ev.vin && ev.vin !== 'General de Envío'
                        ? `<span class="badge bg-light text-dark border font-monospace fs-12"><i class="ri-car-line me-1 text-primary"></i>${ev.vin}</span>`
                        : `<span class="badge bg-secondary-subtle text-muted fs-12">General Envío</span>`;

                    let fileLink = ev.ruta_archivo.startsWith('http') ? ev.ruta_archivo : (base_url + '/' + ev.ruta_archivo.replace(/^\/+/, ''));
                    
                    let previewBtn = '';
                    if (ev.id_unidad) {
                        previewBtn = `
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fs-12" onclick="verEvidenciasUnidad(${idEnvio}, ${ev.id_unidad}, '${ev.vin || ''}');" title="Ver Inspección Completa">
                                    <i class="ri-camera-lens-fill me-1"></i>Ver Inspección
                                </button>
                                <a href="${fileLink}" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 py-1 fs-12 text-muted" title="Ver Archivo">
                                    <i class="ri-external-link-line"></i>
                                </a>
                            </div>
                        `;
                    } else {
                        previewBtn = `<a href="${fileLink}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fs-12" title="Abrir archivo"><i class="ri-external-link-line me-1"></i>Ver Archivo</a>`;
                    }

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

function verEvidenciasUnidad(idEnvio, idUnidad, vin) {
    const titleEl = document.getElementById('titleModalVerEvidencias');
    const subEl = document.getElementById('subModalVerEvidencias');
    const gridEl = document.getElementById('gridVerEvidencias');
    const obsCont = document.getElementById('contenedorObservacionesEvidencias');

    if (titleEl) titleEl.innerHTML = `Inspección de Unidad: <span class="text-primary font-monospace">${vin}</span>`;
    if (subEl) subEl.innerText = `Envío #${idEnvio} · Fotografías y observaciones de inspección`;
    if (obsCont) obsCont.innerHTML = '';
    if (gridEl) gridEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando evidencias fotográficas...</p></div>';

    const modal = new bootstrap.Modal(document.getElementById('modalVerEvidencias'));
    modal.show();

    fetch(base_url + '/Lgs_ejecucion/getEvidenciasUnidad/' + idEnvio + '/' + idUnidad)
        .then(response => response.json())
        .then(res => {
            if (!res.status || !res.data) {
                gridEl.innerHTML = '<div class="alert alert-warning text-center">No se encontraron evidencias registradas para esta unidad.</div>';
                return;
            }

            const data = res.data;
            const checklist = data.checklist || {};
            const fotos = data.fotos || [];

            if (checklist.comentarios && checklist.comentarios.trim() !== '') {
                obsCont.innerHTML = `
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="ri-chat-1-line text-info fs-16"></i>
                            <strong class="text-dark fs-13">Observaciones de Inspección:</strong>
                        </div>
                        <p class="text-muted mb-0 fs-13 ps-4">${checklist.comentarios}</p>
                    </div>
                `;
            }

            const etiquetas = {
                'frente': { label: 'Frente', icon: 'ri-arrow-up-circle-line' },
                'atras': { label: 'Atrás / Posterior', icon: 'ri-arrow-down-circle-line' },
                'lateral_izq': { label: 'Lateral Izquierdo', icon: 'ri-arrow-left-circle-line' },
                'lateral_der': { label: 'Lateral Derecho', icon: 'ri-arrow-right-circle-line' },
                'odometro': { label: 'Odómetro / Tablero', icon: 'ri-dashboard-3-line' }
            };

            let htmlGrid = '<div class="row g-3">';
            if (fotos.length > 0) {
                fotos.forEach((foto, idx) => {
                    const info = etiquetas[foto.tipo_foto] || { label: 'Foto Adicional ' + (idx + 1), icon: 'ri-image-line' };
                    let imgUrl = foto.ruta_archivo.startsWith('http') ? foto.ruta_archivo : (base_url + '/' + foto.ruta_archivo.replace(/^\/+/, ''));

                    htmlGrid += `
                        <div class="col-md-4 col-sm-6">
                            <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fs-12 fw-bold text-dark"><i class="${info.icon} me-1 text-primary"></i> ${info.label}</span>
                                    <small class="text-muted fs-10">${foto.created_at || ''}</small>
                                </div>
                                <div class="position-relative" style="height: 180px; background: #000;">
                                    <img src="${imgUrl}" class="w-100 h-100 object-fit-cover" alt="${info.label}" style="cursor: pointer;" onclick="window.open('${imgUrl}', '_blank');">
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                htmlGrid += '<div class="col-12"><div class="alert alert-info text-center py-4 mb-0"><i class="ri-image-line fs-2 d-block mb-1"></i>No hay fotos registradas en esta inspección.</div></div>';
            }
            htmlGrid += '</div>';

            gridEl.innerHTML = htmlGrid;
        })
        .catch(() => {
            gridEl.innerHTML = '<div class="alert alert-danger text-center">Error al consultar las evidencias fotográficas.</div>';
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
