let tableIncidencias;
let currentIncidenciaDetalle = null;
let vinsActualesEnvio = [];

document.addEventListener('DOMContentLoaded', function () {
    initTableIncidencias();
    initFiltrosEvents();
    initForms();
    revisarParametroUrl();
});

/**
 * Inicializa el DataTable principal
 */
function initTableIncidencias() {
    tableIncidencias = $('#tableIncidencias').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_incidencias/getIncidencias",
            "data": function (d) {
                d.id_estado = $('#filtro_estado').val();
                d.id_tipo_incidencia = $('#filtro_tipo').val();
                d.id_proveedor = $('#filtro_proveedor').val();
                d.es_post_entrega = $('#filtro_post_entrega').val();
            },
            "dataSrc": function (json) {
                let data = json || [];
                actualizarKpis(data);
                return data;
            }
        },
        "columns": [
            {
                "data": "folio",
                "render": function (data, type, row) {
                    return `<a href="javascript:void(0);" onclick="verDetalleIncidencia(${row.id_incidencia});" class="fw-bold text-primary">${escapeHtml(data)}</a>`;
                }
            },
            {
                "data": "folio_envio",
                "render": function (data, type, row) {
                    let postBadge = parseInt(row.es_post_entrega) === 1 
                        ? '<span class="badge bg-soft-danger text-danger ms-1 fs-10" title="Reportada tras la entrega"><i class="ri-history-line"></i> Reclamo</span>' 
                        : '';
                    return `<div>
                                <span class="fw-medium">${escapeHtml(data || 'Sin Folio')}</span>
                                ${postBadge}
                            </div>`;
                }
            },
            {
                "data": "tipo_incidencia",
                "render": function (data) {
                    return `<span class="badge bg-soft-secondary text-secondary fs-12">${escapeHtml(data)}</span>`;
                }
            },
            {
                "data": "severidad",
                "render": function (data) {
                    let badge = 'bg-soft-info text-info';
                    if (data === 'MEDIA') badge = 'bg-soft-warning text-warning';
                    if (data === 'ALTA') badge = 'bg-soft-danger text-danger';
                    return `<span class="badge ${badge} fs-11">${escapeHtml(data)}</span>`;
                }
            },
            {
                "data": "trasladista",
                "render": function (data) {
                    return `<span class="text-truncate d-inline-block" style="max-width: 150px;">${escapeHtml(data || 'N/A')}</span>`;
                }
            },
            {
                "data": "total_vins_afectados",
                "render": function (data, type, row) {
                    let total = parseInt(data) || 0;
                    let list = row.vins_afectados_list ? escapeHtml(row.vins_afectados_list) : '';
                    return `<span class="badge bg-light text-dark border" title="${list}">
                                <i class="ri-car-line me-1"></i> ${total} VIN${total === 1 ? '' : 's'}
                            </span>`;
                }
            },
            {
                "data": "fecha_incidente",
                "render": function (data) {
                    if (!data) return '-';
                    return `<small class="text-muted">${data.substring(0, 16)}</small>`;
                }
            },
            {
                "data": "id_estado",
                "render": function (data) {
                    return renderEstadoBadge(parseInt(data));
                }
            },
            {
                "data": "total_gastos_ligados",
                "render": function (data, type, row) {
                    let count = parseInt(data) || 0;
                    if (count === 0) {
                        return `<span class="text-muted fs-11">Sin gastos</span>`;
                    }
                    let monto = parseFloat(row.monto_total_gastos) || 0;
                    let formatted = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(monto);
                    return `<div>
                                <span class="badge bg-soft-success text-success fs-11">${count} gasto${count === 1 ? '' : 's'}</span>
                                <div class="fs-11 fw-semibold text-dark mt-1">${formatted}</div>
                            </div>`;
                }
            },
            {
                "data": null,
                "className": "text-end",
                "orderable": false,
                "render": function (data, type, row) {
                    let id = row.id_incidencia;
                    let est = parseInt(row.id_estado);
                    let requiereDictamen = parseInt(row.requiere_dictamen) === 1;
                    let tieneDictamen = !!row.id_absorcion;

                    let btnDictamen = '';
                    if (requiereDictamen && !tieneDictamen && est !== 0 && est !== 6) {
                        btnDictamen = `<li><a class="dropdown-item text-primary" href="javascript:void(0);" onclick="abrirModalDictamen(${id});"><i class="ri-scales-3-line me-2"></i> Emitir Dictamen</a></li>`;
                    }

                    let btnGasto = '';
                    if (est !== 0 && est !== 5 && est !== 6) {
                        btnGasto = `<li><a class="dropdown-item text-success" href="${base_url}/Lgs_gastosadicionales?incidencia=${encodeURIComponent(row.folio)}"><i class="ri-money-dollar-circle-line me-2"></i> Asignar Gasto</a></li>`;
                    }

                    return `
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0);" onclick="verDetalleIncidencia(${id});"><i class="ri-eye-line me-2 text-muted"></i> Ver Detalle</a></li>
                                ${btnDictamen}
                                <li><a class="dropdown-item" href="javascript:void(0);" onclick="abrirModalSubirEvidencia(${id});"><i class="ri-upload-cloud-line me-2 text-muted"></i> Adjuntar Evidencia</a></li>
                                ${btnGasto}
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        "order": [[0, "desc"]]
    });
}

function renderEstadoBadge(estado) {
    switch (estado) {
        case 1: return '<span class="badge bg-soft-warning text-warning"><i class="ri-time-line me-1"></i>Abierta</span>';
        case 2: return '<span class="badge bg-soft-info text-info"><i class="ri-search-eye-line me-1"></i>En Investigación</span>';
        case 3: return '<span class="badge bg-soft-primary text-primary"><i class="ri-scales-3-line me-1"></i>Dictaminada</span>';
        case 4: return '<span class="badge bg-soft-secondary text-secondary"><i class="ri-money-dollar-box-line me-1"></i>Con Gastos</span>';
        case 5: return '<span class="badge bg-soft-success text-success"><i class="ri-check-double-line me-1"></i>Resuelta s/costo</span>';
        case 6: return '<span class="badge bg-soft-dark text-dark"><i class="ri-lock-line me-1"></i>Cerrada</span>';
        case 0: return '<span class="badge bg-soft-danger text-danger"><i class="ri-close-circle-line me-1"></i>Cancelada</span>';
        default: return '<span class="badge bg-light text-dark">Desconocido</span>';
    }
}

function actualizarKpis(data) {
    let abiertas = 0;
    let investigacion = 0;
    let conGastos = 0;
    let postEntrega = 0;

    data.forEach(item => {
        let st = parseInt(item.id_estado);
        if (st === 1) abiertas++;
        if (st === 2) investigacion++;
        if (st === 4) conGastos++;
        if (parseInt(item.es_post_entrega) === 1) postEntrega++;
    });

    $('#kpiAbiertas').text(abiertas);
    $('#kpiInvestigacion').text(investigacion);
    $('#kpiConGastos').text(conGastos);
    $('#kpiPostEntrega').text(postEntrega);
}

function initFiltrosEvents() {
    $('#filtro_estado, #filtro_tipo, #filtro_proveedor, #filtro_post_entrega').on('change', function () {
        tableIncidencias.ajax.reload();
    });
}

function limpiarFiltros() {
    $('#filtro_estado').val('');
    $('#filtro_tipo').val('');
    $('#filtro_proveedor').val('');
    $('#filtro_post_entrega').val('');
    tableIncidencias.ajax.reload();
}

function revisarParametroUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const idEnvio = parseInt(urlParams.get('envio') || 0);
    if (idEnvio > 0) {
        openModalIncidencia(idEnvio);
    }
}

/**
 * Abre el modal para registrar una nueva incidencia
 */
function openModalIncidencia(selectedEnvioId = 0) {
    $('#formIncidencia')[0].reset();
    $('#id_incidencia').val('');
    $('#id_envio').val('');
    $('#modalFormIncidenciaLabel').html('<i class="ri-alert-fill text-danger me-1"></i> Registrar Incidencia Operativa');
    $('#infoEnvioSeleccionado').addClass('d-none');
    $('#boxEnvioVacio').removeClass('d-none');
    $('#vinsCheckboxList').empty();
    $('#vinsEmptyMsg').removeClass('d-none');
    $('#lblTotalVinsSeleccionados').text('0');
    $('#lblWarningEntregados').text('');
    $('#alertaTipoIncidencia').addClass('d-none');
    $('#divArchivoInicial').removeClass('d-none');
    $('#divTipoArchivoInicial').removeClass('d-none');

    // Poner fecha y hora actual local
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#fecha_incidente').val(now.toISOString().slice(0, 16));

    if (selectedEnvioId > 0) {
        cargarYSeleccionarEnvioPorId(selectedEnvioId, 'incidencia');
    }

    $('#modalFormIncidencia').modal('show');
}

/**
 * Evento al cambiar o asignar de envío en el modal de incidencia
 */
function cargarVinsDeEnvio() {
    let idEnvio = parseInt($('#id_envio').val());

    if (!idEnvio) {
        $('#infoEnvioSeleccionado').addClass('d-none');
        $('#boxEnvioVacio').removeClass('d-none');
        $('#vinsCheckboxList').empty();
        $('#vinsEmptyMsg').removeClass('d-none');
        $('#lblTotalVinsSeleccionados').text('0');
        return;
    }

    // Cargar VINs del envío
    let fecha = $('#fecha_incidente').val();
    $('#loadingVins').removeClass('d-none');
    $('#vinsEmptyMsg').addClass('d-none');
    $('#vinsCheckboxList').empty();

    fetch(`${base_url}/Lgs_incidencias/getVinsEnvio/${idEnvio}?fecha=${encodeURIComponent(fecha)}`)
        .then(res => res.json())
        .then(res => {
            $('#loadingVins').addClass('d-none');
            if (res.status && res.data) {
                vinsActualesEnvio = res.data;
                renderCheckboxesVins(res.data);
            } else {
                $('#vinsEmptyMsg').text('No se encontraron VINs asignados a este envío.').removeClass('d-none');
            }
        })
        .catch(() => {
            $('#loadingVins').addClass('d-none');
            $('#vinsEmptyMsg').text('Error al obtener los VINs del envío.').removeClass('d-none');
        });
}

function renderCheckboxesVins(vins) {
    let html = '';
    vins.forEach((v, idx) => {
        let isABordo = v.estado_al_incidente === 'A_BORDO';
        let badgeClase = isABordo ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning';
        let badgeTexto = isABordo ? 'A Bordo' : 'Entregado Previamente';

        html += `
            <div class="col-md-6">
                <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-white vin-card ${!isABordo ? 'opacity-75' : ''}">
                    <div class="form-check mb-0">
                        <input class="form-check-input chk-vin" type="checkbox" 
                               value="${v.id_envio_vin}" 
                               id="chk_vin_${v.id_envio_vin}"
                               data-vin="${escapeHtml(v.vin)}"
                               data-estado="${v.estado_al_incidente}"
                               ${isABordo ? 'checked' : ''} 
                               onchange="onVinCheckChange(this);">
                        <label class="form-check-label fs-12 fw-medium text-dark ms-1 cursor-pointer" for="chk_vin_${v.id_envio_vin}">
                            <strong>${escapeHtml(v.vin)}</strong>
                            <div class="fs-11 text-muted">${escapeHtml(v.modelo)} - ${escapeHtml(v.num_serie)}</div>
                            <div class="fs-10 text-muted">${escapeHtml(v.destino_vin)}</div>
                        </label>
                    </div>
                    <span class="badge ${badgeClase} fs-10">${badgeTexto}</span>
                </div>
            </div>
        `;
    });

    $('#vinsCheckboxList').html(html);
    actualizarContadorVinsSeleccionados();
}

function onVinCheckChange(el) {
    if (el.checked && $(el).data('estado') === 'ENTREGADO') {
        Swal.fire({
            icon: 'warning',
            title: 'Unidad entregada previamente',
            text: `La unidad ${$(el).data('vin')} tiene registro de entrega previo a la fecha del incidente. Marque esta unidad únicamente si se trata de un reclamo post-entrega.`,
            confirmButtonText: 'Entendido'
        });
    }
    actualizarContadorVinsSeleccionados();
}

function seleccionarVins(modo) {
    $('.chk-vin').each(function () {
        if (modo === 'TODOS') {
            $(this).prop('checked', true);
        } else if (modo === 'NINGUNO') {
            $(this).prop('checked', false);
        } else if (modo === 'ABORDOS') {
            let isABordo = $(this).data('estado') === 'A_BORDO';
            $(this).prop('checked', isABordo);
        }
    });
    actualizarContadorVinsSeleccionados();
}

function actualizarContadorVinsSeleccionados() {
    let checkedCount = $('.chk-vin:checked').length;
    $('#lblTotalVinsSeleccionados').text(checkedCount);

    let entregadosCount = $('.chk-vin:checked').filter(function () {
        return $(this).data('estado') === 'ENTREGADO';
    }).length;

    if (entregadosCount > 0) {
        $('#lblWarningEntregados').text(`(${entregadosCount} entregados previamente)`);
    } else {
        $('#lblWarningEntregados').text('');
    }
}

function onTipoIncidenciaChange() {
    let $opt = $('#id_tipo_incidencia option:selected');
    let reqDictamen = parseInt($opt.data('dictamen')) === 1;

    if (reqDictamen) {
        $('#alertaTipoIncidencia').removeClass('d-none');
    } else {
        $('#alertaTipoIncidencia').addClass('d-none');
    }
}

/**
 * Inicialización de formularios y envío vía AJAX
 */
function initForms() {
    // Formulario Registro Incidencia
    $('#formIncidencia').on('submit', function (e) {
        e.preventDefault();

        let idEnvio = parseInt($('#id_envio').val());
        if (!idEnvio) {
            Swal.fire('Atención', 'Debe seleccionar un envío válido.', 'warning');
            return;
        }

        let vinsSeleccionados = [];
        $('.chk-vin:checked').each(function () {
            vinsSeleccionados.push({
                id_envio_vin: parseInt($(this).val()),
                vin: $(this).data('vin')
            });
        });

        if (vinsSeleccionados.length === 0) {
            Swal.fire('Atención', 'Debe seleccionar al menos un VIN afectado.', 'warning');
            return;
        }

        let formData = new FormData(this);
        formData.append('vins_afectados', JSON.stringify(vinsSeleccionados));

        let idInc = $('#id_incidencia').val();
        let url = idInc ? `${base_url}/Lgs_incidencias/update/${idInc}` : `${base_url}/Lgs_incidencias/store`;

        $('#btnGuardarIncidencia').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            $('#btnGuardarIncidencia').prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Incidencia');
            if (res.status) {
                $('#modalFormIncidencia').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Operación Exitosa!',
                    text: res.message || 'Incidencia guardada correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                tableIncidencias.ajax.reload();
            } else {
                Swal.fire('Error', res.message || 'No se pudo guardar la incidencia.', 'error');
            }
        })
        .catch(err => {
            $('#btnGuardarIncidencia').prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Incidencia');
            Swal.fire('Error', 'Ocurrió un error en la comunicación con el servidor.', 'error');
        });
    });

    // Formulario Dictamen
    $('#formDictamen').on('submit', function (e) {
        e.preventDefault();
        let idInc = $('#dictamen_id_incidencia').val();
        let formData = new FormData(this);

        $('#btnGuardarDictamen').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        fetch(`${base_url}/Lgs_incidencias/dictaminar/${idInc}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            $('#btnGuardarDictamen').prop('disabled', false).html('<i class="ri-check-line me-1"></i> Guardar Dictamen');
            if (res.status) {
                $('#modalDictamenIncidencia').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Dictamen Registrado',
                    text: res.message || 'El dictamen ha sido registrado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                tableIncidencias.ajax.reload();
                if ($('#modalDetalleIncidencia').hasClass('show')) {
                    verDetalleIncidencia(idInc);
                }
            } else {
                Swal.fire('Error', res.message || 'No se pudo guardar el dictamen.', 'error');
            }
        })
        .catch(() => {
            $('#btnGuardarDictamen').prop('disabled', false).html('<i class="ri-check-line me-1"></i> Guardar Dictamen');
            Swal.fire('Error', 'Ocurrió un error al procesar el dictamen.', 'error');
        });
    });

    // Formulario Subir Evidencia
    $('#formSubirEvidencia').on('submit', function (e) {
        e.preventDefault();
        let idInc = $('#evidencia_id_incidencia').val();
        let formData = new FormData(this);

        $('#btnSubirEvidencia').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Subiendo...');

        fetch(`${base_url}/Lgs_incidencias/uploadEvidencia/${idInc}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            $('#btnSubirEvidencia').prop('disabled', false).html('<i class="ri-upload-line me-1"></i> Subir Archivo');
            if (res.status) {
                $('#modalSubirEvidencia').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Evidencia Subida',
                    text: 'El archivo se adjuntó correctamente a la incidencia.',
                    timer: 1800,
                    showConfirmButton: false
                });
                tableIncidencias.ajax.reload();
                if ($('#modalDetalleIncidencia').hasClass('show')) {
                    verDetalleIncidencia(idInc);
                }
            } else {
                Swal.fire('Error', res.message || 'No se pudo subir la evidencia.', 'error');
            }
        })
        .catch(() => {
            $('#btnSubirEvidencia').prop('disabled', false).html('<i class="ri-upload-line me-1"></i> Subir Archivo');
            Swal.fire('Error', 'Ocurrió un error al subir el archivo.', 'error');
        });
    });
}

/**
 * Ver Detalle Completo de la Incidencia
 */
function verDetalleIncidencia(idIncidencia) {
    fetch(`${base_url}/Lgs_incidencias/getDetalle/${idIncidencia}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                renderModalDetalle(res.data);
            } else {
                Swal.fire('Error', res.message || 'No se pudo obtener el detalle de la incidencia.', 'error');
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Error de conexión al obtener el detalle.', 'error');
        });
}

function renderModalDetalle(data) {
    currentIncidenciaDetalle = data;

    $('#detFolio').text(data.folio);
    $('#detBadgeEstado').html(renderEstadoBadge(parseInt(data.id_estado)));
    if (parseInt(data.es_post_entrega) === 1) {
        $('#detBadgePostEntrega').removeClass('d-none');
    } else {
        $('#detBadgePostEntrega').addClass('d-none');
    }

    // Tab 1
    $('#detEnvioFolio').text(data.folio_envio || 'N/A');
    $('#detTrasladista').text(data.trasladista || 'N/A');
    $('#detFechaIncidente').text(data.fecha_incidente ? data.fecha_incidente.substring(0, 16) : '-');
    $('#detTipo').text(data.tipo_incidencia || '-');
    $('#detOrigenReporte').text(data.origen || '-');
    $('#detSeveridad').text(data.severidad || '-');
    $('#detUbicacion').text(data.ubicacion_texto || 'No especificada');
    $('#detDescripcion').text(data.descripcion || '');

    // Dictamen
    if (data.id_absorcion) {
        $('#detContenedorDictamen').addClass('d-none');
        $('#detDictamenInfo').removeClass('d-none');
        $('#detAbsorbeNombre').text(data.absorcion_nombre || 'N/A');
        $('#detDictaminadoPor').text(data.dictaminado_por || 'N/A');
        $('#detDictamenNotas').text('"' + (data.dictamen_notas || 'Sin notas') + '"');
    } else {
        $('#detContenedorDictamen').removeClass('d-none');
        $('#detDictamenInfo').addClass('d-none');
    }

    // Tab 2: VINs
    let vins = data.vins_afectados || [];
    $('#detCountVins').text(vins.length);
    let htmlVins = '';
    vins.forEach((v, idx) => {
        let isABordo = v.estado_vin_al_incidente === 'A_BORDO';
        let badgeClase = isABordo ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning';
        let badgeTexto = isABordo ? 'A Bordo' : 'Entregado';
        let costo = parseFloat(v.costo_unidad) || 0;
        let formattedCosto = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(costo);

        htmlVins += `
            <tr>
                <td>${idx + 1}</td>
                <td><strong class="text-primary">${escapeHtml(v.vin)}</strong></td>
                <td>Posición ${v.posicion_acomodo || 'N/A'}</td>
                <td><span class="badge ${badgeClase}">${badgeTexto}</span></td>
                <td>${formattedCosto}</td>
            </tr>
        `;
    });
    $('#detTbodyVins').html(htmlVins || '<tr><td colspan="5" class="text-center text-muted">Sin VINs registrados.</td></tr>');

    // Tab 3: Evidencias
    let evs = data.evidencias || [];
    $('#detCountEvidencias').text(evs.length);
    let htmlEvs = '';
    evs.forEach(ev => {
        let isImg = ev.mime && ev.mime.startsWith('image/');
        let isPdf = ev.mime === 'application/pdf';
        let isVideo = ev.mime && ev.mime.startsWith('video/');
        let icon = isImg ? 'ri-image-line' : (isPdf ? 'ri-file-pdf-line' : (isVideo ? 'ri-video-line' : 'ri-file-line'));

        htmlEvs += `
            <div class="col-md-4">
                <div class="card border mb-0 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="${icon} fs-24 text-primary me-2"></i>
                            <div class="overflow-hidden">
                                <h6 class="fs-13 text-truncate mb-0">${escapeHtml(ev.nombre_original)}</h6>
                                <small class="text-muted fs-11">${ev.tipo} - ${Math.round(ev.tamano_bytes / 1024)} KB</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <small class="text-muted fs-11">${ev.subido_por || 'Sistema'}</small>
                            <div>
                                <a href="${base_url}/Lgs_incidencias/descargarEvidencia/${ev.id_evidencia}" class="btn btn-sm btn-ghost-primary" title="Descargar">
                                    <i class="ri-download-line"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-ghost-danger" onclick="eliminarEvidencia(${ev.id_evidencia}, ${data.id_incidencia});" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    $('#detContenedorEvidencias').html(htmlEvs || '<div class="col-12 text-center py-4 text-muted">No hay evidencias adjuntas a esta incidencia.</div>');

    // Tab 4: Gastos Ligados
    let gastos = data.gastos_vinculados || [];
    $('#detCountGastos').text(gastos.length);
    let htmlGastos = '';
    gastos.forEach(g => {
        let monto = parseFloat(g.monto_final) || 0;
        let formattedMonto = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(monto);
        let badgeNat = g.naturaleza === 'CARGO' ? 'bg-soft-danger text-danger' : 'bg-soft-success text-success';

        htmlGastos += `
            <tr>
                <td><strong class="text-primary">${escapeHtml(g.folio)}</strong></td>
                <td>${escapeHtml(g.descripcion)}</td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(g.tipo_gasto)}</span></td>
                <td><span class="badge ${badgeNat}">${g.naturaleza}</span></td>
                <td class="fw-bold">${formattedMonto}</td>
                <td>${renderEstadoGastoBadge(parseInt(g.id_estado))}</td>
                <td class="text-end">
                    <a href="${base_url}/Lgs_gastosadicionales" class="btn btn-sm btn-ghost-secondary" title="Ver en Gastos">
                        <i class="ri-external-link-line"></i>
                    </a>
                </td>
            </tr>
        `;
    });
    $('#detTbodyGastos').html(htmlGastos || '<tr><td colspan="7" class="text-center text-muted">Aún no se han generado gastos para esta incidencia.</td></tr>');

    // Tab 5: Bitácora
    let logs = data.logs || [];
    let htmlLogs = '';
    logs.forEach(l => {
        htmlLogs += `
            <li class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-dark">${escapeHtml(l.usuario_nombre || 'Sistema')}</span>
                    <small class="text-muted">${l.created_at ? l.created_at.substring(0, 16) : ''}</small>
                </div>
                <div class="text-muted fs-12">${escapeHtml(l.comentario || 'Actualización de estado')}</div>
            </li>
        `;
    });
    $('#detListaLogs').html(htmlLogs || '<li class="list-group-item px-0 text-muted">Sin registros en bitácora.</li>');

    // Botón superior de crear gasto
    let est = parseInt(data.id_estado);
    if (est === 0 || est === 5 || est === 6) {
        $('#btnCrearGastoDesdeDetalle').addClass('d-none');
    } else {
        $('#btnCrearGastoDesdeDetalle').removeClass('d-none').attr('onclick', `crearGastoParaEstaIncidencia();`);
    }

    $('#modalDetalleIncidencia').modal('show');
}

function renderEstadoGastoBadge(estado) {
    switch (estado) {
        case 1: return '<span class="badge bg-soft-secondary text-secondary">Registrado</span>';
        case 2: return '<span class="badge bg-soft-warning text-warning">En Revisión</span>';
        case 3: return '<span class="badge bg-soft-primary text-primary">Aprobado</span>';
        case 4: return '<span class="badge bg-soft-danger text-danger">Rechazado</span>';
        case 5: return '<span class="badge bg-soft-success text-success">Documentado</span>';
        case 0: return '<span class="badge bg-soft-danger text-danger">Cancelado</span>';
        default: return '<span class="badge bg-light text-dark">N/A</span>';
    }
}

/**
 * Dictamen
 */
function abrirModalDictamen(idIncidencia) {
    fetch(`${base_url}/Lgs_incidencias/getDetalle/${idIncidencia}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                let d = res.data;
                $('#formDictamen')[0].reset();
                $('#dictamen_id_incidencia').val(d.id_incidencia);
                $('#dictamen_info_incidencia').text(`${d.folio} - Envío ${d.folio_envio}`);
                $('#dictamen_info_tipo').text(`${d.tipo_incidencia} (${d.vins_afectados.length} VINs afectados)`);

                if (d.id_absorcion) {
                    $('#dictamen_id_absorcion').val(d.id_absorcion);
                    $('#dictamen_notas').val(d.dictamen_notas);
                    if (d.porcentaje_proveedor) {
                        $('#dictamen_porcentaje_proveedor').val(d.porcentaje_proveedor);
                    }
                    onAbsorcionChange();
                } else {
                    $('#divCompartido').addClass('d-none');
                    $('#alertaImprocedente').addClass('d-none');
                }

                $('#modalDictamenIncidencia').modal('show');
            }
        });
}

function abrirModalDictamenActual() {
    if (currentIncidenciaDetalle) {
        abrirModalDictamen(currentIncidenciaDetalle.id_incidencia);
    }
}

function onAbsorcionChange() {
    let $opt = $('#dictamen_id_absorcion option:selected');
    let clave = $opt.data('clave');
    let genera = parseInt($opt.data('genera'));

    if (clave === 'COMPARTIDO') {
        $('#divCompartido').removeClass('d-none');
        $('#dictamen_porcentaje_proveedor').prop('required', true);
    } else {
        $('#divCompartido').addClass('d-none');
        $('#dictamen_porcentaje_proveedor').prop('required', false);
    }

    if (genera === 0) {
        $('#alertaImprocedente').removeClass('d-none');
    } else {
        $('#alertaImprocedente').addClass('d-none');
    }
}

/**
 * Subir evidencia
 */
function abrirModalSubirEvidencia(idIncidencia = null) {
    let id = idIncidencia || (currentIncidenciaDetalle ? currentIncidenciaDetalle.id_incidencia : null);
    if (!id) return;

    $('#formSubirEvidencia')[0].reset();
    $('#evidencia_id_incidencia').val(id);

    // Llenar select de VINs si tenemos detalle
    let $selectVin = $('#evidencia_id_envio_vin');
    $selectVin.html('<option value="">Afecta a toda la incidencia / general</option>');

    if (currentIncidenciaDetalle && currentIncidenciaDetalle.id_incidencia == id && currentIncidenciaDetalle.vins_afectados) {
        currentIncidenciaDetalle.vins_afectados.forEach(v => {
            $selectVin.append(`<option value="${v.id_envio_vin}">${escapeHtml(v.vin)}</option>`);
        });
    }

    $('#modalSubirEvidencia').modal('show');
}

function eliminarEvidencia(idEvidencia, idIncidencia) {
    Swal.fire({
        title: '¿Eliminar evidencia?',
        text: 'El archivo será eliminado de forma permanente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${base_url}/Lgs_incidencias/deleteEvidencia/${idEvidencia}`, { method: 'POST' })
                .then(res => res.json())
                .then(res => {
                    if (res.status) {
                        Swal.fire('Eliminado', 'La evidencia ha sido eliminada.', 'success');
                        tableIncidencias.ajax.reload();
                        if ($('#modalDetalleIncidencia').hasClass('show')) {
                            verDetalleIncidencia(idIncidencia);
                        }
                    } else {
                        Swal.fire('Error', res.message || 'No se pudo eliminar la evidencia.', 'error');
                    }
                });
        }
    });
}

/**
 * Redirecciona al módulo de Gastos Adicionales con la incidencia preseleccionada
 */
function crearGastoParaEstaIncidencia() {
    if (currentIncidenciaDetalle) {
        window.location.href = `${base_url}/Lgs_gastosadicionales?incidencia=${encodeURIComponent(currentIncidenciaDetalle.folio)}`;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
