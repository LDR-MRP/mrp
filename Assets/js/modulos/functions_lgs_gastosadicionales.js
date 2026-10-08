let tableGastos;
let currentGastoDetalle = null;
let vinsDisponiblesEnvio = [];
let calcTimeout = null;

document.addEventListener('DOMContentLoaded', function () {
    initTableGastos();
    initFiltrosEvents();
    initForms();
    revisarParametroUrl();
});

/**
 * Inicializa el DataTable principal
 */
function initTableGastos() {
    tableGastos = $('#tableGastos').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_gastosadicionales/getGastos",
            "data": function (d) {
                d.id_estado = $('#filtro_estado').val();
                d.id_tipo_gasto = $('#filtro_tipo').val();
                d.id_proveedor = $('#filtro_proveedor').val();
                d.es_independiente = $('#filtro_independiente').val();
            },
            "dataSrc": function (json) {
                let data = json || [];
                actualizarKpisGastos(data);
                return data;
            }
        },
        "columns": [
            {
                "data": "folio",
                "render": function (data, type, row) {
                    return `<a href="javascript:void(0);" onclick="verDetalleGasto(${row.id_gasto});" class="fw-bold text-primary">${escapeHtml(data)}</a>`;
                }
            },
            {
                "data": "id_incidencia",
                "render": function (data, type, row) {
                    if (data && row.folio_incidencia) {
                        return `<a href="${base_url}/Lgs_incidencias" class="badge bg-soft-info text-info border">
                                    <i class="ri-alert-line me-1"></i>${escapeHtml(row.folio_incidencia)}
                                </a>`;
                    }
                    return `<span class="badge bg-soft-warning text-warning border">
                                <i class="ri-shield-check-line me-1"></i>Independiente
                            </span>`;
                }
            },
            {
                "data": "folio_envio",
                "render": function (data) {
                    return `<span class="fw-medium">${escapeHtml(data || 'N/A')}</span>`;
                }
            },
            {
                "data": "tipo_gasto",
                "render": function (data, type, row) {
                    return `<div>
                                <span class="fw-semibold text-dark fs-12">${escapeHtml(data)}</span>
                                <div class="fs-10 text-muted">${escapeHtml(row.categoria)}</div>
                            </div>`;
                }
            },
            {
                "data": "naturaleza",
                "render": function (data) {
                    if (data === 'CARGO') {
                        return '<span class="badge bg-soft-danger text-danger"><i class="ri-arrow-up-circle-line me-1"></i>Cargo</span>';
                    }
                    return '<span class="badge bg-soft-success text-success"><i class="ri-arrow-down-circle-line me-1"></i>Deducción</span>';
                }
            },
            {
                "data": "trasladista",
                "render": function (data) {
                    return `<span class="text-truncate d-inline-block" style="max-width: 140px;">${escapeHtml(data || 'N/A')}</span>`;
                }
            },
            {
                "data": "monto_final",
                "render": function (data) {
                    let m = parseFloat(data) || 0;
                    return `<strong class="text-dark">${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(m)}</strong>`;
                }
            },
            {
                "data": "total_vins_reparto",
                "render": function (data) {
                    let c = parseInt(data) || 0;
                    return `<span class="badge bg-light text-dark border">${c} VIN${c === 1 ? '' : 's'}</span>`;
                }
            },
            {
                "data": "id_estado",
                "render": function (data) {
                    return renderEstadoGastoBadge(parseInt(data));
                }
            },
            {
                "data": "doc_folio",
                "render": function (data, type, row) {
                    if (data) {
                        return `<span class="badge bg-soft-success text-success" title="UUID: ${escapeHtml(row.doc_uuid || 'N/A')}">
                                    <i class="ri-check-line me-1"></i>${escapeHtml(data)}
                                </span>`;
                    }
                    return `<span class="text-muted fs-11">Pendiente</span>`;
                }
            },
            {
                "data": null,
                "className": "text-end",
                "orderable": false,
                "render": function (data, type, row) {
                    let id = row.id_gasto;
                    let est = parseInt(row.id_estado);

                    let btnRevision = (est === 1) ? `<li><a class="dropdown-item text-warning" href="javascript:void(0);" onclick="enviarRevisionGasto(${id});"><i class="ri-send-plane-line me-2"></i> Enviar a Revisión</a></li>` : '';
                    let btnAprobar = (est === 1 || est === 2) ? `<li><a class="dropdown-item text-success" href="javascript:void(0);" onclick="aprobarGastoDirecto(${id});"><i class="ri-check-line me-2"></i> Aprobar Gasto</a></li>` : '';
                    let btnRechazar = (est === 1 || est === 2) ? `<li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="rechazarGastoDirecto(${id});"><i class="ri-close-line me-2"></i> Rechazar Gasto</a></li>` : '';
                    let btnDocumentar = (est === 3) ? `<li><a class="dropdown-item text-primary" href="javascript:void(0);" onclick="abrirModalDocumentar(${id});"><i class="ri-file-text-line me-2"></i> Documentar Financieramente</a></li>` : '';

                    return `
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0);" onclick="verDetalleGasto(${id});"><i class="ri-eye-line me-2 text-muted"></i> Ver Detalle</a></li>
                                ${btnRevision}
                                ${btnAprobar}
                                ${btnRechazar}
                                ${btnDocumentar}
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        "order": [[0, "desc"]]
    });
}

function renderEstadoGastoBadge(estado) {
    switch (estado) {
        case 1: return '<span class="badge bg-soft-secondary text-secondary"><i class="ri-draft-line me-1"></i>Registrado</span>';
        case 2: return '<span class="badge bg-soft-warning text-warning"><i class="ri-time-line me-1"></i>En Revisión</span>';
        case 3: return '<span class="badge bg-soft-primary text-primary"><i class="ri-checkbox-circle-line me-1"></i>Aprobado</span>';
        case 4: return '<span class="badge bg-soft-danger text-danger"><i class="ri-close-circle-line me-1"></i>Rechazado</span>';
        case 5: return '<span class="badge bg-soft-success text-success"><i class="ri-check-double-line me-1"></i>Documentado</span>';
        case 0: return '<span class="badge bg-soft-danger text-danger"><i class="ri-prohibited-line me-1"></i>Cancelado</span>';
        default: return '<span class="badge bg-light text-dark">N/A</span>';
    }
}

function actualizarKpisGastos(data) {
    let pendientes = 0;
    let porDocumentar = 0;
    let totalCargos = 0;
    let totalDeducciones = 0;

    data.forEach(item => {
        let est = parseInt(item.id_estado);
        let monto = parseFloat(item.monto_final) || 0;

        if (est === 1 || est === 2) pendientes++;
        if (est === 3) porDocumentar++;

        if (est === 3 || est === 5) {
            if (item.naturaleza === 'CARGO') totalCargos += monto;
            if (item.naturaleza === 'DEDUCCION') totalDeducciones += monto;
        }
    });

    $('#kpiPendientes').text(pendientes);
    $('#kpiPorDocumentar').text(porDocumentar);
    $('#kpiTotalCargos').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalCargos));
    $('#kpiTotalDeducciones').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalDeducciones));
}

function initFiltrosEvents() {
    $('#filtro_estado, #filtro_tipo, #filtro_proveedor, #filtro_independiente').on('change', function () {
        tableGastos.ajax.reload();
    });
}

function limpiarFiltros() {
    $('#filtro_estado').val('');
    $('#filtro_tipo').val('');
    $('#filtro_proveedor').val('');
    $('#filtro_independiente').val('');
    tableGastos.ajax.reload();
}

/**
 * Revisa si viene preseleccionada una incidencia en la URL (?incidencia=IN-XXXXXX)
 */
function revisarParametroUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const folioInc = urlParams.get('incidencia');
    const idEnvio = parseInt(urlParams.get('envio') || 0);
    if (folioInc) {
        openModalGasto(folioInc);
    } else if (idEnvio > 0) {
        openModalGastoIndependiente(idEnvio);
    }
}

/**
 * Abre el modal de creación de gasto adicional preseleccionando modo independiente para un envío
 */
function openModalGastoIndependiente(idEnvio = 0) {
    $('#formGasto')[0].reset();
    $('#id_gasto').val('');
    $('#id_envio_indep').val('');
    $('#infoEnvioIndepSeleccionado').addClass('d-none');
    $('#boxEnvioIndepVacio').removeClass('d-none');
    $('#modo_independiente').prop('checked', true);
    onOrigenModoChange();

    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#fecha_gasto').val(now.toISOString().slice(0, 16));

    $('#panelCalculoDinamico').empty();
    $('#vinsGastoCheckboxList').empty();
    $('#vinsGastoEmpty').removeClass('d-none');
    $('#lblVinsAsignadosCount').text('0');
    $('#lblCuotaPorVin').text('$0.00');

    if (idEnvio > 0) {
        cargarYSeleccionarEnvioPorId(idEnvio, 'gasto_independiente');
    }

    $('#modalFormGasto').modal('show');
}

/**
 * Abre el modal de creación de gasto adicional
 */
function openModalGasto(folioIncidenciaPrevia = null) {
    $('#formGasto')[0].reset();
    $('#id_gasto').val('');
    $('#id_incidencia').val('');
    $('#id_envio_incidencia').val('');
    $('#id_incidencia').data('envio', 0);
    $('#infoIncidenciaSeleccionada').addClass('d-none');
    $('#boxIncidenciaVacia').removeClass('d-none');

    $('#id_envio_indep').val('');
    $('#infoEnvioIndepSeleccionado').addClass('d-none');
    $('#boxEnvioIndepVacio').removeClass('d-none');
    $('#modo_ligado').prop('checked', true);
    onOrigenModoChange();

    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    $('#fecha_gasto').val(now.toISOString().slice(0, 16));

    $('#panelCalculoDinamico').empty();
    $('#vinsGastoCheckboxList').empty();
    $('#vinsGastoEmpty').removeClass('d-none');
    $('#lblVinsAsignadosCount').text('0');
    $('#lblCuotaPorVin').text('$0.00');

    if (folioIncidenciaPrevia && typeof cargarYSeleccionarIncidenciaPorFolio === 'function') {
        cargarYSeleccionarIncidenciaPorFolio(folioIncidenciaPrevia);
    }

    $('#modalFormGasto').modal('show');
}

function onOrigenModoChange() {
    let modo = $('input[name="origen_modo"]:checked').val();
    if (modo === 'LIGADO') {
        $('#secIncidencia').removeClass('d-none');
        $('#secIndependiente').addClass('d-none');
        $('#id_incidencia').prop('required', true);
        $('#id_envio_indep').prop('required', false);
        $('#justificacion_independiente').prop('required', false);
    } else {
        $('#secIncidencia').addClass('d-none');
        $('#secIndependiente').removeClass('d-none');
        $('#id_incidencia').prop('required', false);
        $('#id_envio_indep').prop('required', true);
        $('#justificacion_independiente').prop('required', true);
    }
}

let catalogoIncidenciasParaSelect = [];

function cargarIncidenciasSelect(preseleccionarFolio = null) {
    let $select = $('#id_incidencia');
    $select.html('<option value="">Cargando incidencias disponibles...</option>');

    fetch(`${base_url}/Lgs_gastosadicionales/buscarIncidencias`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                catalogoIncidenciasParaSelect = res.data;
                renderOpcionesIncidencias(res.data, preseleccionarFolio);
            } else {
                catalogoIncidenciasParaSelect = [];
                $select.html('<option value="">No hay incidencias disponibles</option>');
            }
        });
}

function renderOpcionesIncidencias(lista, preseleccionarFolio = null) {
    let $select = $('#id_incidencia');
    let html = '<option value="">Seleccione una incidencia...</option>';
    lista.forEach(inc => {
        let sel = (preseleccionarFolio && inc.folio === preseleccionarFolio) ? 'selected' : '';
        html += `<option value="${inc.id_incidencia}" 
                    data-folio="${escapeHtml(inc.folio)}"
                    data-envio="${inc.id_envio}"
                    data-folio-envio="${escapeHtml(inc.folio_envio)}"
                    data-tipo="${escapeHtml(inc.tipo_incidencia)}"
                    data-clave-tipo="${escapeHtml(inc.clave_tipo_incidencia)}"
                    data-descripcion="${escapeHtml(inc.descripcion)}"
                    data-trasladista="${escapeHtml(inc.trasladista)}"
                    data-sugerido="${inc.id_tipo_gasto_sugerido || ''}"
                    data-absorcion="${escapeHtml(inc.absorcion_nombre || '')}"
                    ${sel}>
                    ${escapeHtml(inc.folio)} - Envío ${escapeHtml(inc.folio_envio)} (${escapeHtml(inc.tipo_incidencia)})
                 </option>`;
    });
    $select.html(html);
    if (preseleccionarFolio) {
        onIncidenciaSeleccionada();
    }
}

function filtrarOpcionesIncidencias(term) {
    term = (term || '').trim().toLowerCase();
    if (!term) {
        renderOpcionesIncidencias(catalogoIncidenciasParaSelect);
        return;
    }
    let filtradas = catalogoIncidenciasParaSelect.filter(inc => {
        return (inc.folio && inc.folio.toLowerCase().includes(term)) ||
               (inc.folio_envio && inc.folio_envio.toLowerCase().includes(term)) ||
               (inc.tipo_incidencia && inc.tipo_incidencia.toLowerCase().includes(term)) ||
               (inc.trasladista && inc.trasladista.toLowerCase().includes(term)) ||
               (inc.descripcion && inc.descripcion.toLowerCase().includes(term));
    });
    renderOpcionesIncidencias(filtradas);
}

function cargarEnviosIndepSelect(selectedId = 0) {
    let $select = $('#id_envio_indep');
    fetch(`${base_url}/Lgs_incidencias/getEnviosElegibles`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                let html = '<option value="">Seleccione un envío...</option>';
                res.data.forEach(e => {
                    let sel = (selectedId && parseInt(e.id_envio) === parseInt(selectedId)) ? 'selected' : '';
                    html += `<option value="${e.id_envio}" ${sel}>
                                ${escapeHtml(e.folio)} - ${escapeHtml(e.origen)} ➔ ${escapeHtml(e.destino)} (${escapeHtml(e.trasladista)})
                             </option>`;
                });
                $select.html(html);
                if (selectedId) {
                    onEnvioIndepSeleccionado();
                }
            }
        });
}

function onIncidenciaSeleccionada() {
    let $opt = $('#id_incidencia option:selected');
    let idInc = parseInt($('#id_incidencia').val());

    if (!idInc) {
        $('#infoIncidenciaSeleccionada').addClass('d-none');
        $('#vinsGastoCheckboxList').empty();
        $('#vinsGastoEmpty').removeClass('d-none');
        return;
    }

    $('#txtIncFolio').text(`${$opt.data('folio')} - Envío ${$opt.data('folio-envio')}`);
    $('#txtIncTipo').text($opt.data('tipo')).removeClass().addClass('badge bg-soft-info text-info');
    $('#txtIncDescripcion').text($opt.data('descripcion'));

    if ($opt.data('absorcion')) {
        $('#txtIncDictamen').html(`<strong>Dictamen:</strong> <span class="badge bg-soft-primary text-primary">${$opt.data('absorcion')}</span>`);
    } else {
        $('#txtIncDictamen').empty();
    }
    $('#infoIncidenciaSeleccionada').removeClass('d-none');

    // Auto-seleccionar tipo de gasto sugerido si aplica
    let sugerido = $opt.data('sugerido');
    if (sugerido) {
        $('#id_tipo_gasto').val(sugerido);
        onTipoGastoChange();
    }

    // Cargar VINs del envío
    let idEnvio = parseInt($opt.data('envio'));
    cargarVinsParaGasto(idEnvio, idInc);
}

function onEnvioIndepChange() {
    let idEnvio = parseInt($('#id_envio_indep').val());
    if (idEnvio > 0) {
        cargarVinsParaGasto(idEnvio);
    } else {
        $('#vinsGastoCheckboxList').empty();
        $('#vinsGastoEmpty').removeClass('d-none');
    }
}

function cargarVinsParaGasto(idEnvio, idIncidenciaOrigen = null) {
    $('#vinsGastoEmpty').addClass('d-none');
    $('#vinsGastoCheckboxList').html('<div class="col-12 text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

    fetch(`${base_url}/Lgs_incidencias/getVinsEnvio/${idEnvio}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                vinsDisponiblesEnvio = res.data;
                renderCheckboxesVinsGasto(res.data);
                calcularPreviewGasto();
            } else {
                $('#vinsGastoCheckboxList').empty();
                $('#vinsGastoEmpty').text('Sin unidades disponibles.').removeClass('d-none');
            }
        });
}

function renderCheckboxesVinsGasto(vins) {
    let html = '';
    vins.forEach(v => {
        let isABordo = v.estado_al_incidente === 'A_BORDO';
        html += `
            <div class="col-md-6">
                <div class="border rounded p-2 d-flex align-items-center justify-content-between bg-white vin-card-gasto">
                    <div class="form-check mb-0">
                        <input class="form-check-input chk-vin-gasto" type="checkbox" 
                               value="${v.id_envio_vin}" 
                               id="chk_gvin_${v.id_envio_vin}"
                               data-vin="${escapeHtml(v.vin)}"
                               data-unidad="${v.id_unidad}"
                               data-estado="${v.estado_al_incidente}"
                               ${isABordo ? 'checked' : ''} 
                               onchange="calcularPreviewGasto();">
                        <label class="form-check-label fs-12 fw-medium text-dark ms-1 cursor-pointer" for="chk_gvin_${v.id_envio_vin}">
                            <strong>${escapeHtml(v.vin)}</strong>
                            <div class="fs-10 text-muted">${escapeHtml(v.modelo)} - Pos ${v.posicion_acomodo || 'N/A'}</div>
                        </label>
                    </div>
                    <span class="fs-11 fw-semibold text-primary" id="lblCuotaVin_${v.id_envio_vin}">$0.00</span>
                </div>
            </div>
        `;
    });
    $('#vinsGastoCheckboxList').html(html);
}

function seleccionarVinsGasto(modo) {
    $('.chk-vin-gasto').each(function () {
        if (modo === 'TODOS') $(this).prop('checked', true);
        if (modo === 'NINGUNO') $(this).prop('checked', false);
        if (modo === 'ABORDOS') $(this).prop('checked', $(this).data('estado') === 'A_BORDO');
    });
    calcularPreviewGasto();
}

/**
 * Al cambiar el tipo de gasto, ajusta los inputs dinámicos de cálculo
 */
function onTipoGastoChange() {
    let $opt = $('#id_tipo_gasto option:selected');
    let modo = $opt.data('calculo'); // KM, CANTIDAD_X_UNITARIO, MONTO_LIBRE
    let $panel = $('#panelCalculoDinamico');
    $panel.empty();

    if (!modo) return;

    if (modo === 'KM') {
        $panel.html(`
            <div class="p-3 border rounded bg-light-subtle">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fs-12 fw-medium text-muted">KM Adicionales Recorridos <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="km_adicionales" name="km_adicionales" class="form-control" min="0.1" step="0.1" required oninput="calcularPreviewGasto();" placeholder="Ej. 25.0">
                            <span class="input-group-text">KM</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info py-2 px-3 mb-0 fs-12" id="boxInfoKmTarifa">
                            <i class="ri-information-line me-1"></i> El sistema aplicará la tarifa $/km por unidad y evaluará posibles brincos de clasificación (SLC/SLL a Foráneo).
                        </div>
                    </div>
                </div>
            </div>
        `);
    } else if (modo === 'CANTIDAD_X_UNITARIO') {
        $panel.html(`
            <div class="p-3 border rounded bg-light-subtle">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fs-12 fw-medium text-muted">Cantidad (Días / Eventos) <span class="text-danger">*</span></label>
                        <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" step="0.5" value="1" required oninput="calcularPreviewGasto();">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-12 fw-medium text-muted">Precio Unitario ($) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="precio_unitario" name="precio_unitario" class="form-control" min="0" step="0.5" required oninput="calcularPreviewGasto();" placeholder="Ej. 1500.00">
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="fs-12 text-muted mt-3">Subtotal: <strong id="lblSubtotalCantUnit" class="text-dark">$0.00</strong></div>
                    </div>
                </div>
            </div>
        `);
    } else {
        // MONTO_LIBRE
        $panel.html(`
            <div class="p-3 border rounded bg-light-subtle">
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label fs-12 fw-medium text-muted">Monto Total a Asignar ($ MXN) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="monto_final_input" name="monto_final" class="form-control" min="0.01" step="0.01" required oninput="calcularPreviewGasto();" placeholder="Ej. 3500.00">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fs-12 fw-medium text-muted">Justificación del Monto Autorizado</label>
                        <input type="text" id="justificacion_ajuste" name="justificacion_ajuste" class="form-control" placeholder="Criterio de cotización / presupuesto...">
                    </div>
                </div>
            </div>
        `);
    }

    calcularPreviewGasto();
}

/**
 * Llama al endpoint de previsualización para obtener cuotas y desglose en partes iguales
 */
function calcularPreviewGasto() {
    clearTimeout(calcTimeout);
    calcTimeout = setTimeout(function () {
        ejecutarCalculoPreview();
    }, 250);
}

function ejecutarCalculoPreview() {
    let vinsSeleccionados = [];
    $('.chk-vin-gasto:checked').each(function () {
        vinsSeleccionados.push({
            id_envio_vin: parseInt($(this).val()),
            vin: $(this).data('vin'),
            id_unidad: $(this).data('unidad')
        });
    });

    let countVins = vinsSeleccionados.length;
    $('#lblVinsAsignadosCount').text(countVins);

    let cant = parseFloat($('#cantidad').val()) || 0;
    let pUnit = parseFloat($('#precio_unitario').val()) || 0;
    if ($('#lblSubtotalCantUnit').length) {
        let subtotal = cant * pUnit;
        $('#lblSubtotalCantUnit').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(subtotal));
    }

    let idTipoGasto = parseInt($('#id_tipo_gasto').val());
    if (!idTipoGasto || countVins === 0) {
        $('#lblCuotaPorVin').text('$0.00');
        $('.chk-vin-gasto').each(function () {
            $(`#lblCuotaVin_${$(this).val()}`).text('$0.00');
        });
        return;
    }

    let modoOrigen = $('input[name="origen_modo"]:checked').val();
    let idEnvio = 0;
    if (modoOrigen === 'LIGADO') {
        idEnvio = parseInt($('#id_incidencia').data('envio')) || parseInt($('#id_envio_incidencia').val()) || 0;
    } else {
        idEnvio = parseInt($('#id_envio_indep').val()) || 0;
    }

    if (!idEnvio) return;

    let formData = new FormData($('#formGasto')[0]);
    formData.append('id_envio', idEnvio);
    formData.append('vins_afectados', JSON.stringify(vinsSeleccionados));

    fetch(`${base_url}/Lgs_gastosadicionales/previsualizar`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status && res.data) {
            let d = res.data;
            let cuotaPromedio = countVins > 0 ? (d.monto_final / countVins) : 0;
            let formattedCuota = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cuotaPromedio);
            $('#lblCuotaPorVin').text(`${formattedCuota} / unidad (Total: ${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(d.monto_final)})`);

            if (d.reparto) {
                d.reparto.forEach(r => {
                    let mStr = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(r.monto);
                    $(`#lblCuotaVin_${r.id_envio_vin}`).text(mStr);
                });
            }

            if (d.km_info && d.km_info.tarifa_aplicada) {
                let saltosTxt = (d.km_info.tipo_servicio_original !== d.km_info.tipo_servicio_nuevo) 
                    ? ` (Reclasificado: ${d.km_info.tipo_servicio_original} ➔ ${d.km_info.tipo_servicio_nuevo})` : '';
                let fmtTarifa = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(parseFloat(d.km_info.tarifa_aplicada) || 0);
                let fmtMontoCalc = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(parseFloat(d.monto_calculado) || 0);
                $('#boxInfoKmTarifa').html(`<strong>Tarifa aplicada:</strong> ${fmtTarifa} / km${saltosTxt}. Monto total sugerido: <strong>${fmtMontoCalc}</strong>.`);
            }
        }
    });
}

function initForms() {
    $('#formGasto').on('submit', function (e) {
        e.preventDefault();

        let vinsSeleccionados = [];
        $('.chk-vin-gasto:checked').each(function () {
            vinsSeleccionados.push({
                id_envio_vin: parseInt($(this).val()),
                vin: $(this).data('vin'),
                id_unidad: $(this).data('unidad')
            });
        });

        if (vinsSeleccionados.length === 0) {
            Swal.fire('Atención', 'Debe seleccionar al menos una unidad para asignar el gasto.', 'warning');
            return;
        }

        let modoOrigen = $('input[name="origen_modo"]:checked').val();
        let idEnvio = 0;
        if (modoOrigen === 'LIGADO') {
            let idInc = parseInt($('#id_incidencia').val());
            if (!idInc) {
                Swal.fire('Atención', 'Debe buscar y seleccionar una incidencia operativa afectada.', 'warning');
                return;
            }
            idEnvio = parseInt($('#id_incidencia').data('envio')) || parseInt($('#id_envio_incidencia').val()) || 0;
        } else {
            idEnvio = parseInt($('#id_envio_indep').val()) || 0;
        }

        if (!idEnvio) {
            Swal.fire('Atención', 'Debe especificar el envío afectado.', 'warning');
            return;
        }

        let formData = new FormData(this);
        formData.append('id_envio', idEnvio);
        formData.append('vins_afectados', JSON.stringify(vinsSeleccionados));

        $('#btnGuardarGasto').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        fetch(`${base_url}/Lgs_gastosadicionales/store`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            $('#btnGuardarGasto').prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Gasto');
            if (res.status) {
                $('#modalFormGasto').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Gasto Registrado!',
                    text: res.message || 'El gasto adicional se guardó correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
                tableGastos.ajax.reload();
            } else {
                Swal.fire('Error', res.message || 'No se pudo guardar el gasto.', 'error');
            }
        })
        .catch(() => {
            $('#btnGuardarGasto').prop('disabled', false).html('<i class="ri-save-line me-1"></i> Guardar Gasto');
            Swal.fire('Error', 'Ocurrió un error al guardar el gasto.', 'error');
        });
    });

    // Form Documentar Gasto
    $('#formDocumentarGasto').on('submit', function (e) {
        e.preventDefault();
        let idGasto = $('#doc_id_gasto').val();
        let formData = new FormData(this);

        $('#btnGuardarDocumentoGasto').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

        fetch(`${base_url}/Lgs_gastosadicionales/documentar/${idGasto}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            $('#btnGuardarDocumentoGasto').prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Documentar y Finalizar');
            if (res.status) {
                $('#modalDocumentarGasto').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: '¡Gasto Documentado!',
                    text: 'El comprobante ha sido registrado y el gasto quedó concluido.',
                    timer: 2000,
                    showConfirmButton: false
                });
                tableGastos.ajax.reload();
                if ($('#modalDetalleGasto').hasClass('show')) {
                    verDetalleGasto(idGasto);
                }
            } else {
                Swal.fire('Error', res.message || 'No se pudo documentar el gasto.', 'error');
            }
        })
        .catch(() => {
            $('#btnGuardarDocumentoGasto').prop('disabled', false).html('<i class="ri-check-double-line me-1"></i> Documentar y Finalizar');
            Swal.fire('Error', 'Error de comunicación al documentar gasto.', 'error');
        });
    });
}

/**
 * Ver Detalle Completo del Gasto Adicional
 */
function verDetalleGasto(idGasto) {
    fetch(`${base_url}/Lgs_gastosadicionales/getDetalle/${idGasto}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                renderModalDetalleGasto(res.data);
            } else {
                Swal.fire('Error', res.message || 'No se encontró el gasto.', 'error');
            }
        });
}

function renderModalDetalleGasto(data) {
    currentGastoDetalle = data;

    $('#gdetFolio').text(data.folio);
    $('#gdetBadgeEstado').html(renderEstadoGastoBadge(parseInt(data.id_estado)));
    $('#gdetBadgeNaturaleza').text(data.naturaleza).removeClass().addClass('badge ' + (data.naturaleza === 'CARGO' ? 'bg-danger' : 'bg-success'));

    // Botones de acción según estado
    let est = parseInt(data.id_estado);
    $('#btnAccionEnviarRevision').toggleClass('d-none', est !== 1);
    $('#btnAccionAprobar').toggleClass('d-none', est !== 1 && est !== 2);
    $('#btnAccionRechazar').toggleClass('d-none', est !== 1 && est !== 2);
    $('#btnAccionDocumentar').toggleClass('d-none', est !== 3);

    // Tab 1
    $('#gdetEnvio').text(data.folio_envio || 'N/A');
    $('#gdetProveedor').text(data.trasladista || 'N/A');
    if (data.id_incidencia) {
        $('#gdetOrigen').html(`<span class="badge bg-soft-info text-info">Incidencia: ${escapeHtml(data.folio_incidencia)}</span>`);
    } else {
        $('#gdetOrigen').html('<span class="badge bg-soft-warning text-warning">Gasto Independiente</span>');
    }

    $('#gdetTipoGasto').text(data.tipo_gasto);
    $('#gdetMotivo').text(data.motivo_descripcion || 'No especificado');
    $('#gdetFecha').text(data.fecha_gasto ? data.fecha_gasto.substring(0, 16) : '-');

    let mCalculado = parseFloat(data.monto_calculado) || 0;
    let mFinal = parseFloat(data.monto_final) || 0;
    $('#gdetMontoCalculado').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(mCalculado));
    $('#gdetMontoFinal').text(new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(mFinal));
    $('#gdetTarifaInfo').text(data.tarifa_aplicada ? `Tarifa aplicada: ${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(parseFloat(data.tarifa_aplicada) || 0)}` : '');

    // Justificaciones
    if (data.justificacion_independiente) {
        $('#divGdetJustificacionIndep').removeClass('d-none');
        $('#gdetJustificacionIndepTexto').text(data.justificacion_independiente);
    } else {
        $('#divGdetJustificacionIndep').addClass('d-none');
    }

    if (data.justificacion_ajuste) {
        $('#divGdetAjuste').removeClass('d-none');
        $('#gdetJustificacionAjusteTexto').text(data.justificacion_ajuste);
    } else {
        $('#divGdetAjuste').addClass('d-none');
    }

    $('#gdetDescripcion').text(data.descripcion);

    // Comprobante fiscal
    if (data.doc_folio) {
        $('#divGdetComprobanteFiscal').removeClass('d-none');
        $('#gdetDocTipo').text(data.doc_tipo);
        $('#gdetDocFolio').text(data.doc_folio);
        $('#gdetDocUuid').text(data.doc_uuid || 'N/A');
    } else {
        $('#divGdetComprobanteFiscal').addClass('d-none');
    }

    // Tab 2: Reparto
    let reparto = data.reparto_vins || [];
    $('#gdetCountVins').text(reparto.length);
    let htmlReparto = '';
    reparto.forEach((r, idx) => {
        let m = parseFloat(r.monto) || 0;
        let cOrig = parseFloat(r.costo_planeado_unidad) || 0;
        htmlReparto += `
            <tr>
                <td>${idx + 1}</td>
                <td><strong class="text-primary">${escapeHtml(r.vin)}</strong></td>
                <td>Posición ${r.posicion_acomodo || 'N/A'}</td>
                <td>${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cOrig)}</td>
                <td>${r.porcentaje}%</td>
                <td class="text-end fw-bold">${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(m)}</td>
            </tr>
        `;
    });
    $('#gdetTbodyReparto').html(htmlReparto || '<tr><td colspan="6" class="text-center text-muted">Sin unidades.</td></tr>');

    // Tab 3: Documentos
    let docs = data.documentos || [];
    $('#gdetCountDocs').text(docs.length);
    let htmlDocs = '';
    docs.forEach(doc => {
        let icon = (doc.mime === 'application/pdf') ? 'ri-file-pdf-line' : ((doc.mime && doc.mime.startsWith('image/')) ? 'ri-image-line' : 'ri-file-text-line');
        htmlDocs += `
            <div class="col-md-4">
                <div class="card border mb-0 shadow-none">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="${icon} fs-24 text-primary me-2"></i>
                            <div class="overflow-hidden">
                                <h6 class="fs-13 text-truncate mb-0">${escapeHtml(doc.nombre_original)}</h6>
                                <small class="text-muted fs-11">${doc.tipo} - ${Math.round(doc.tamano_bytes / 1024)} KB</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                            <small class="text-muted fs-11">${doc.subido_por || 'Sistema'}</small>
                            <div>
                                <a href="${base_url}/Lgs_gastosadicionales/descargarDocumento/${doc.id_documento}" class="btn btn-sm btn-ghost-primary" title="Descargar">
                                    <i class="ri-download-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    $('#gdetContenedorDocumentos').html(htmlDocs || '<div class="col-12 text-center py-4 text-muted">No hay comprobantes o documentos adjuntos.</div>');

    // Tab 4: Logs
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
    $('#gdetListaLogs').html(htmlLogs || '<li class="list-group-item px-0 text-muted">Sin registros.</li>');

    $('#modalDetalleGasto').modal('show');
}

/**
 * Transición de estados
 */
function accionGasto(accion) {
    if (!currentGastoDetalle) return;
    let id = currentGastoDetalle.id_gasto;

    if (accion === 'enviarRevision') {
        enviarRevisionGasto(id);
    } else if (accion === 'aprobar') {
        aprobarGastoDirecto(id);
    } else if (accion === 'rechazar') {
        rechazarGastoDirecto(id);
    }
}

function enviarRevisionGasto(idGasto) {
    Swal.fire({
        title: '¿Enviar a revisión?',
        text: 'El gasto quedará pendiente de aprobación por el área autorizadora.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then(res => {
        if (res.isConfirmed) {
            fetch(`${base_url}/Lgs_gastosadicionales/enviarRevision/${idGasto}`, { method: 'POST' })
                .then(r => r.json())
                .then(r => {
                    if (r.status) {
                        Swal.fire('Enviado', 'El gasto ha sido enviado a revisión.', 'success');
                        tableGastos.ajax.reload();
                        verDetalleGasto(idGasto);
                    } else {
                        Swal.fire('Error', r.message || 'No se pudo enviar a revisión.', 'error');
                    }
                });
        }
    });
}

function aprobarGastoDirecto(idGasto) {
    Swal.fire({
        title: '¿Aprobar Gasto Adicional?',
        text: 'Se autorizará el importe y quedará listo para documentar con factura o nota de cargo.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then(res => {
        if (res.isConfirmed) {
            fetch(`${base_url}/Lgs_gastosadicionales/aprobar/${idGasto}`, { method: 'POST' })
                .then(r => r.json())
                .then(r => {
                    if (r.status) {
                        Swal.fire('Aprobado', 'El gasto ha sido autorizado exitosamente.', 'success');
                        tableGastos.ajax.reload();
                        verDetalleGasto(idGasto);
                    } else {
                        Swal.fire('Error', r.message || 'No se pudo aprobar el gasto.', 'error');
                    }
                });
        }
    });
}

function rechazarGastoDirecto(idGasto) {
    Swal.fire({
        title: 'Rechazar Gasto',
        text: 'Indique el motivo del rechazo:',
        input: 'textarea',
        inputPlaceholder: 'Escriba el motivo...',
        showCancelButton: true,
        confirmButtonText: 'Rechazar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) return 'El motivo del rechazo es obligatorio.';
        }
    }).then(res => {
        if (res.isConfirmed) {
            let formData = new FormData();
            formData.append('motivo_rechazo', res.value);

            fetch(`${base_url}/Lgs_gastosadicionales/rechazar/${idGasto}`, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(r => {
                if (r.status) {
                    Swal.fire('Rechazado', 'El gasto ha sido rechazado.', 'info');
                    tableGastos.ajax.reload();
                    verDetalleGasto(idGasto);
                } else {
                    Swal.fire('Error', r.message || 'No se pudo rechazar el gasto.', 'error');
                }
            });
        }
    });
}

function abrirModalDocumentar(idGasto) {
    fetch(`${base_url}/Lgs_gastosadicionales/getDetalle/${idGasto}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                let d = res.data;
                $('#formDocumentarGasto')[0].reset();
                $('#doc_id_gasto').val(d.id_gasto);
                let montoStr = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(d.monto_final);
                $('#doc_info_gasto').text(`${d.folio} - ${d.tipo_gasto} - Importe: ${montoStr}`);

                let today = new Date().toISOString().split('T')[0];
                $('#doc_fecha').val(today);

                if (d.naturaleza === 'DEDUCCION') {
                    $('#doc_tipo').val('NOTA_CARGO');
                } else {
                    $('#doc_tipo').val('FACTURA');
                }

                $('#modalDocumentarGasto').modal('show');
            }
        });
}

function abrirModalDocumentarActual() {
    if (currentGastoDetalle) {
        abrirModalDocumentar(currentGastoDetalle.id_gasto);
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
