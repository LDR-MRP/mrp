let tableAprobaciones;
let tableAprobPreEnvios;
let solicitudesPreEnvioCache = [];

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Aprobaciones
    tableAprobaciones = $('#tableAprobaciones').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_aprobaciones/getPlaneacionesAprobacion",
            "dataSrc": ""
        },
        "columns": [
            {
                "data": "folio",
                "className": "ps-3",
                "render": function(data, type, row) {
                    if (type !== 'display') return `${data || ''} ${row.id_planeacion || ''}`;
                    return '<span class="badge bg-soft-primary text-primary border border-primary-subtle fs-12 fw-bold px-2 py-1">' + (data || ('PL-' + row.id_planeacion)) + '</span>';
                }
            },
            { 
                "data": "descripcion",
                "render": function(data) {
                    return data ? `<span class="fw-medium text-dark fs-13">${data}</span>` : '<span class="text-muted">-</span>';
                }
            },
            { 
                "data": "total_rutas",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-11">' + (data || 0) + ' Envío(s)</span>';
                }
            },
            { 
                "data": "km_total",
                "render": function(data) {
                    return (parseFloat(data) || 0).toFixed(1) + ' km';
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<strong class="text-success fs-13">$' + parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    const st = parseInt(data);
                    switch(st) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-11"><i class="ri-draft-line me-1"></i>Borrador / Reabierta</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-11"><i class="ri-time-line me-1"></i>Pendiente Aprobación</span>'; break;
                        case 3:
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-11"><i class="ri-close-circle-line me-1"></i>Rechazada</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-success text-success fs-11"><i class="ri-checkbox-circle-line me-1"></i>Aprobada</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-11"><i class="ri-restart-line me-1"></i>Solicitud Reapertura</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-11">Estado ' + data + '</span>'; break;
                    }
                    const obsAp = (row && row.obs_aprobador) ? String(row.obs_aprobador) : '';
                    if (st !== 6 && st !== 2) {
                        if (obsAp.includes('[Reapertura Aprobada]')) {
                            badge += '<div class="mt-1"><span class="badge bg-soft-success text-success fs-10"><i class="ri-lock-unlock-line me-1"></i>Reapertura Aprobada</span></div>';
                        } else if (obsAp.includes('[Reapertura Denegada]')) {
                            badge += '<div class="mt-1"><span class="badge bg-soft-danger text-danger fs-10"><i class="ri-lock-line me-1"></i>Reapertura Denegada</span></div>';
                        }
                    }
                    return badge;
                }
            },
            {
                "data": "id_planeacion",
                "className": "text-end pe-3",
                "orderable": false,
                "render": function (data, type, row) {
                    let btnAction = '';
                    let obsEsc = (row.obs_operador || '')
                        .replace(/\\/g, '\\\\')
                        .replace(/'/g, "\\'")
                        .replace(/"/g, '&quot;')
                        .replace(/\r?\n/g, ' ');
                    const st = parseInt(row.id_estado) || 0;
                    if (st === 6) {
                        btnAction = `<button type="button" class="btn btn-sm btn-warning text-dark btn-table-action shadow-xs" onClick="fntEvaluarPlan(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${obsEsc}', 6)" title="Evaluar Solicitud de Reapertura">
                                        <i class="ri-restart-line"></i> Evaluar Reapertura
                                     </button>`;
                    } else if (st === 2) {
                        btnAction = `<button type="button" class="btn btn-sm btn-primary btn-table-action shadow-xs" onClick="fntEvaluarPlan(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${obsEsc}', 2)" title="Evaluar Planeación">
                                        <i class="ri-search-eye-line"></i> Evaluar
                                     </button>`;
                    } else {
                        btnAction = `<button type="button" class="btn btn-sm btn-soft-primary btn-table-action" onClick="fntViewDetalle(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${obsEsc}', ${st})" title="Ver Detalle">
                                        <i class="ri-eye-line"></i> Detalle
                                     </button>`;
                    }
                    return `<div class="d-inline-flex align-items-center justify-content-end gap-1">${btnAction}</div>`;
                }
            }
        ],
        "responsive": false,
        "autoWidth": false,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasAprobaciones(settings.json || []);
        }
    });

    // Control de ajuste de DataTables al cambiar entre pestañas
    var tabGastosBtn = document.getElementById('tab-gastos-btn');
    if (tabGastosBtn) {
        tabGastosBtn.addEventListener('shown.bs.tab', function () {
            if (typeof tableGastos !== 'undefined' && tableGastos) {
                tableGastos.columns.adjust();
            }
        });
    }

    var tabPlaneacionesBtn = document.getElementById('tab-planeaciones-btn');
    if (tabPlaneacionesBtn) {
        tabPlaneacionesBtn.addEventListener('shown.bs.tab', function () {
            if (typeof tableAprobaciones !== 'undefined' && tableAprobaciones) {
                tableAprobaciones.columns.adjust();
            }
        });
    }

    // Inicializar DataTable Solicitudes Excepcionales Pre-envío -> Envío Oficial (Pestaña 3)
    const tablePreEl = document.getElementById('tableAprobPreEnvios');
    if (tablePreEl) {
        tableAprobPreEnvios = $('#tableAprobPreEnvios').DataTable({
            "aProcessing": true,
            "aServerSide": false,
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
            },
            "ajax": {
                "url": base_url + "/Lgs_aprobaciones/getSolicitudesPreEnvio",
                "dataSrc": function (json) {
                    solicitudesPreEnvioCache = Array.isArray(json) ? json : [];
                    return solicitudesPreEnvioCache;
                }
            },
            "searchCols": [
                null,
                null,
                null,
                null,
                null,
                null,
                { "search": "PENDIENTE" },
                null
            ],
            "columns": [
                {
                    "data": "folio_solicitud",
                    "render": function (data, type, row) {
                        if (type !== 'display') return data || '';
                        const fecha = row.solicitado_at ? String(row.solicitado_at).substring(0, 16) : '';
                        return `<div class="fw-bold text-dark">${escPreExcHtml(data || ('SOL-' + row.id_solicitud))}</div>`
                             + (fecha ? `<small class="text-muted">${escPreExcHtml(fecha)}</small>` : '');
                    }
                },
                {
                    "data": "folio_preenvio",
                    "render": function (data, type, row) {
                        const pe = escPreExcHtml(data || '-');
                        const en = row.folio_envio_generado
                            ? escPreExcHtml(row.folio_envio_generado)
                            : (row.folio_actual_envio && String(row.folio_actual_envio).startsWith('EN-') ? escPreExcHtml(row.folio_actual_envio) : '');
                        if (type !== 'display') return `${data || ''} ${row.folio_envio_generado || ''} ${row.folio_actual_envio || ''}`;
                        let html = `<span class="badge bg-soft-info text-info fw-semibold">${pe}</span>`;
                        if (en) {
                            html += ` <i class="ri-arrow-right-line text-muted mx-1"></i><span class="badge bg-soft-success text-success fw-semibold">${en}</span>`;
                        }
                        html += `<div class="mt-1"><a href="${base_url}/Lgs_envios/detalle/${parseInt(row.id_envio, 10) || 0}" target="_blank" class="fs-11 text-decoration-none"><i class="ri-external-link-line"></i> Ver Acomodo</a></div>`;
                        return html;
                    }
                },
                {
                    "data": "origen",
                    "render": function (data, type, row) {
                        if (type !== 'display') return `${data || ''} ${row.destino || ''} ${row.trasladista || ''} ${row.tipo_traslado || ''}`;
                        const ruta = `${escPreExcHtml(data || 'Origen')} &rarr; ${escPreExcHtml(row.destino || 'Destino')}`;
                        const meta = [row.tipo_traslado, row.trasladista].filter(Boolean).map(escPreExcHtml).join(' · ');
                        const costo = parseFloat(row.costo_total || 0);
                        return `<div class="fw-medium text-dark fs-12">${ruta}</div>`
                             + `<small class="text-muted d-block">${meta || 'Sin trasladista'}</small>`
                             + (costo > 0 ? `<small class="text-success fw-semibold">$${costo.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>` : '');
                    }
                },
                {
                    "data": "total_vins",
                    "render": function (data, type, row) {
                        const tot = parseInt(data || 0, 10);
                        const obs = parseInt(row.vins_con_observacion || 0, 10);
                        if (type !== 'display') return `${tot} ${obs}`;
                        return `<span class="badge bg-soft-primary text-primary fs-11">${tot} VIN(s)</span>`
                             + (obs > 0 ? `<div class="mt-1"><span class="badge bg-soft-danger text-danger fs-11">${obs} sin Fac./Pago/Aut.</span></div>` : '');
                    }
                },
                {
                    "data": "motivo_solicitud",
                    "render": function (data, type) {
                        const txt = String(data || '').trim();
                        if (type !== 'display') return txt;
                        if (!txt) return '<span class="text-muted fs-12">-</span>';
                        const corto = txt.length > 90 ? (txt.substring(0, 90) + '...') : txt;
                        return `<span class="fs-12 text-dark" title="${escPreExcHtml(txt)}">${escPreExcHtml(corto)}</span>`;
                    }
                },
                {
                    "data": "solicitante_nombre",
                    "render": function (data, type, row) {
                        if (type !== 'display') return `${data || ''} ${row.aprobador_nombre || ''}`;
                        let html = `<div class="fw-medium fs-12 text-dark">${escPreExcHtml(data || 'Usuario')}</div>`;
                        if (row.aprobador_nombre) {
                            html += `<small class="text-muted">Dictamen: ${escPreExcHtml(row.aprobador_nombre)}</small>`;
                        }
                        return html;
                    }
                },
                {
                    "data": "estatus",
                    "render": function (data, type) {
                        const est = String(data || 'PENDIENTE').toUpperCase();
                        if (type !== 'display') return est;
                        if (est === 'APROBADA') {
                            return '<span class="badge bg-soft-success text-success fs-12"><i class="ri-checkbox-circle-line me-1"></i>APROBADA</span>';
                        }
                        if (est === 'RECHAZADA') {
                            return '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>RECHAZADA</span>';
                        }
                        if (est === 'CANCELADA') {
                            return '<span class="badge bg-soft-secondary text-secondary fs-12">CANCELADA</span>';
                        }
                        return '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>PENDIENTE</span>';
                    }
                },
                {
                    "data": "id_solicitud",
                    "render": function (data, type, row) {
                        if (type !== 'display') return data;
                        const idSol = parseInt(data || 0, 10);
                        const est = String(row.estatus || 'PENDIENTE').toUpperCase();
                        const puedeDictaminar = (est === 'PENDIENTE' && (typeof ES_APROBADOR_GASTOS === 'undefined' || ES_APROBADOR_GASTOS));
                        if (puedeDictaminar) {
                            return `<div class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold shadow-sm" onclick="fntEvaluarSolicitudPreEnvio(${idSol}, false);">
                                    <i class="ri-shield-keyhole-line me-1"></i> Evaluar
                                </button>
                            </div>`;
                        }
                        return `<div class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold" onclick="fntEvaluarSolicitudPreEnvio(${idSol}, true);">
                                <i class="ri-eye-line me-1"></i> Detalle
                            </button>
                        </div>`;
                    }
                }
            ],
            "responsive": false,
            "bDestroy": true,
            "iDisplayLength": 10,
            "order": [],
            "drawCallback": function (settings) {
                const raw = Array.isArray(solicitudesPreEnvioCache)
                    ? solicitudesPreEnvioCache
                    : (settings.json || []);
                actualizarMetricasPreEnvioExc(raw);
            }
        });
    }

    var tabPreEnviosBtn = document.getElementById('tab-preenvios-exc-btn');
    if (tabPreEnviosBtn) {
        tabPreEnviosBtn.addEventListener('shown.bs.tab', function () {
            if (typeof tableAprobPreEnvios !== 'undefined' && tableAprobPreEnvios) {
                tableAprobPreEnvios.columns.adjust();
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'preenvios' || window.location.hash === '#tab-preenvios-exc') {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                bootstrap.Tab.getOrCreateInstance(tabPreEnviosBtn).show();
            } else if (typeof $ !== 'undefined' && typeof $(tabPreEnviosBtn).tab === 'function') {
                $(tabPreEnviosBtn).tab('show');
            }
        }
    }
});

function actualizarMetricasAprobaciones(data) {
    if (!Array.isArray(data)) return;
    
    let pendientes = data.filter(p => parseInt(p.id_estado) === 2 || parseInt(p.id_estado) === 6);
    let autorizadas = data.filter(p => parseInt(p.id_estado) === 5);

    let countPendientes = pendientes.length;
    let countAutorizadas = autorizadas.length;

    let montoPendiente = pendientes.reduce((acc, p) => acc + (parseFloat(p.costo_total) || 0), 0);
    let montoAutorizado = autorizadas.reduce((acc, p) => acc + (parseFloat(p.costo_total) || 0), 0);

    if (document.getElementById('cardAprobPendientes')) document.getElementById('cardAprobPendientes').innerText = countPendientes;
    if (document.getElementById('cardMontoPendiente')) document.getElementById('cardMontoPendiente').innerText = '$' + montoPendiente.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (document.getElementById('cardAprobAutorizadas')) document.getElementById('cardAprobAutorizadas').innerText = countAutorizadas;
    if (document.getElementById('cardMontoAutorizado')) document.getElementById('cardMontoAutorizado').innerText = '$' + montoAutorizado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Actualizar badge en la pestaña de Planeaciones
    let badgePlaneaciones = document.getElementById('badgePlaneacionesPendientes');
    if (badgePlaneaciones) {
        badgePlaneaciones.innerText = countPendientes;
    }
}

/**
 * Filtro por buscador de texto
 */
function filtrarTablaAprobaciones(term) {
    if (typeof tableAprobaciones !== "undefined" && tableAprobaciones) {
        tableAprobaciones.search(term).draw();
    }
}

/**
 * Filtra el DataTable por estado usando los chips de acceso rápido
 */
function filtrarAprobPorChip(filtro, btn) {
    document.querySelectorAll('.chip-aprob-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.replace('btn-secondary', 'btn-outline-secondary');
        b.classList.replace('btn-warning', 'btn-outline-warning');
        b.classList.replace('btn-success', 'btn-outline-success');
        b.classList.replace('btn-danger', 'btn-outline-danger');
    });
    
    if (btn) {
        btn.classList.add('active');
        if (btn.classList.contains('btn-outline-secondary')) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        if (btn.classList.contains('btn-outline-warning')) btn.classList.replace('btn-outline-warning', 'btn-warning');
        if (btn.classList.contains('btn-outline-success')) btn.classList.replace('btn-outline-success', 'btn-success');
        if (btn.classList.contains('btn-outline-danger')) btn.classList.replace('btn-outline-danger', 'btn-danger');
    }
    
    if (typeof tableAprobaciones !== "undefined" && tableAprobaciones) {
        tableAprobaciones.search(filtro).draw();
    }
}

/**
 * Permite filtrar la tabla haciendo clic en las cards de métricas
 */
function filtrarAprobPorCard(filtro, chipId) {
    const chipBtn = document.getElementById(chipId);
    filtrarAprobPorChip(filtro, chipBtn);
    
    const tableEl = document.getElementById('tableAprobaciones');
    if (tableEl) {
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function fntEvaluarPlan(idPlaneacion, folio, costo, km, obs, estadoPlan = 2) {
    // 1. Limpiar Modal
    window.g_estadoPlanEvaluacion = parseInt(estadoPlan) || 2;
    document.querySelector("#formAprobacion").reset();
    document.getElementById('id_planeacion').value = idPlaneacion;
    document.getElementById('lblFolioModal').innerText = folio || ('PL-' + idPlaneacion);
    document.getElementById('lblCostoModal').innerText = '$' + (parseFloat(costo) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('lblKmModal').innerText = (parseFloat(km) || 0).toFixed(2);
    document.getElementById('lblObsOperador').innerText = (obs == 'null' || !obs) ? 'Sin observaciones del operador.' : obs;
    
    const contEnvios = document.getElementById('vdp_contenedor_envios');
    if (contEnvios) contEnvios.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> <span class="ms-2">Cargando desglose de envíos...</span></div>';
    
    const btnApprove = document.getElementById('btnAprobarModal');
    const btnReject = document.getElementById('btnRechazarModal');
    if (btnApprove) {
        btnApprove.style.display = '';
        btnApprove.innerHTML = (window.g_estadoPlanEvaluacion === 6)
            ? '<i class="ri-lock-unlock-line me-1"></i> Aprobar Reapertura'
            : '<i class="ri-checkbox-circle-line me-1"></i> Autorizar para seguir';
    }
    if (btnReject) {
        btnReject.style.display = '';
        btnReject.innerHTML = (window.g_estadoPlanEvaluacion === 6)
            ? '<i class="ri-lock-line me-1"></i> Denegar Reapertura'
            : '<i class="ri-close-circle-line me-1"></i> Rechazar Plan';
    }
    
    let modalEl = document.getElementById('modalEvaluarPlan');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            m.show();
        } else if (typeof $ !== 'undefined') {
            $('#modalEvaluarPlan').modal('show');
        }
    }
    
    // 2. Cargar detalle de rutas (envíos)
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_aprobaciones/getDetallePlan/' + idPlaneacion;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    const contEnvios = document.getElementById('vdp_contenedor_envios');
                    if (contEnvios) contEnvios.innerHTML = '';
            
            if (objData.status && objData.data && objData.data.length > 0) {
                let calcCosto = 0;
                let calcKm = 0;

                objData.data.forEach(env => {
                    calcCosto += parseFloat(env.costo_total) || 0;
                    calcKm += parseFloat(env.km_total) || 0;

                    let rowsVins = '';
                    if (env.vins && env.vins.length > 0) {
                        env.vins.forEach(v => {
                            const pos = v.posicion_acomodo || 1;
                            const badgePos = (pos === 1)
                                ? '<span class="badge bg-success fs-11">1º en Cargar</span>'
                                : `<span class="badge bg-soft-primary text-primary fs-11">${pos}º en Cargar</span>`;

                            rowsVins += `
                                <tr>
                                    <td class="text-center">${badgePos}</td>
                                    <td>
                                        <strong class="text-primary fs-12">${v.vin}</strong>
                                        <small class="d-block text-muted fs-10">N/S: ${v.num_serie || 'N/A'}</small>
                                    </td>
                                    <td><span class="badge bg-soft-secondary text-dark">${v.modelo}</span></td>
                                    <td><i class="ri-map-pin-line text-danger me-1"></i>${v.destino_parada}</td>
                                    <td><i class="ri-truck-line text-info me-1"></i>${v.madrina}</td>
                                    <td class="text-end fw-bold text-success">$${(parseFloat(v.costo_unidad) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rowsVins = '<tr><td colspan="6" class="text-center text-muted py-3">Sin unidades asignadas a este envío.</td></tr>';
                    }

                    const cardEnvHtml = `
                        <div class="card border shadow-none rounded-3 mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary fs-12 fw-bold">${env.folio}</span>
                                    <span class="badge bg-soft-info text-info border">${env.tipo_traslado || 'Madrina'}</span>
                                    <span class="fs-12 text-dark fw-medium"><i class="ri-map-pin-range-line text-muted me-1"></i>${env.origen}</span>
                                    <span class="fs-12 text-muted">| ${env.trasladista || 'Sin Trasladista'}</span>
                                    ${parseInt(env.total_incidencias || 0) > 0 ? `<a href="${base_url}/Lgs_incidencias?envio=${env.id_envio}" target="_blank" class="badge bg-soft-danger text-danger border border-danger-subtle fs-11 text-decoration-none ms-1" title="Ver ${env.total_incidencias} incidencia(s)"><i class="ri-alarm-warning-line me-1"></i>${env.total_incidencias} Inc.</a>` : ''}
                                    ${parseFloat(env.total_gastos || 0) > 0 ? `<a href="${base_url}/Lgs_gastosadicionales?envio=${env.id_envio}" target="_blank" class="badge bg-soft-warning text-dark border border-warning-subtle fs-11 text-decoration-none ms-1" title="Gastos Adicionales"><i class="ri-money-dollar-circle-line me-1"></i>+$${parseFloat(env.total_gastos).toLocaleString('en-US', { minimumFractionDigits: 2 })}</a>` : ''}
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted"><i class="ri-car-line me-1"></i>${env.total_vins} unidad(es)</span>
                                    <strong class="fs-14 text-success">$${(parseFloat(env.costo_total) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead class="table-light fs-11 text-uppercase text-muted">
                                            <tr>
                                                <th class="text-center" style="width: 120px;">Secuencia</th>
                                                <th>VIN / N/S</th>
                                                <th>Modelo</th>
                                                <th>Parada / Destino</th>
                                                <th>Vehículo Asignado</th>
                                                <th class="text-end pe-3">Costo Est.</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fs-12">
                                            ${rowsVins}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                    if (contEnvios) contEnvios.innerHTML += cardEnvHtml;
                });

                // Si no venía costo o km en cabecera, actualizar con la suma de las rutas
                if (!costo || parseFloat(costo) === 0) {
                    document.getElementById('lblCostoModal').innerText = '$' + calcCosto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                if (!km || parseFloat(km) === 0) {
                    document.getElementById('lblKmModal').innerText = calcKm.toFixed(2);
                }
            } else {
                if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-soft-secondary text-center py-3">No hay envíos vinculados a esta planeación.</div>';
            }
        } catch (e) {
            const contEnvios = document.getElementById('vdp_contenedor_envios');
            if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-danger text-center py-3">Error al procesar la respuesta del servidor.</div>';
        }
    } else {
        const contEnvios = document.getElementById('vdp_contenedor_envios');
        if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-danger text-center py-3"><i class="ri-error-warning-line me-1"></i> No se pudo obtener el desglose de envíos del servidor.</div>';
    }
}
};
}

function fntViewDetalle(idPlaneacion, folio, costo, km, obs, estadoPlan = 0) {
    fntEvaluarPlan(idPlaneacion, folio, costo, km, obs, estadoPlan);
    const btnApprove = document.getElementById('btnAprobarModal');
    const btnReject = document.getElementById('btnRechazarModal');
    if (btnApprove) btnApprove.style.display = 'none';
    if (btnReject) btnReject.style.display = 'none';
}

function enviarDecision(decision) {
    const obs = document.getElementById('obs_aprobador').value.trim();
    const esReapertura = parseInt(window.g_estadoPlanEvaluacion) === 6;
    const decisionFinal = esReapertura
        ? (decision === 'aprobar' ? 'aprobar_reapertura' : 'denegar_reapertura')
        : decision;

    if ((decision === 'rechazar' || decisionFinal === 'denegar_reapertura') && obs === '') {
        Swal.fire("Atención", esReapertura
            ? "Para denegar la solicitud de reapertura es obligatorio ingresar el motivo."
            : "Para rechazar la planeación es obligatorio ingresar el motivo.", "warning");
        return;
    }

    let textoConfirm = '';
    if (esReapertura) {
        textoConfirm = decisionFinal === 'aprobar_reapertura'
            ? "¿Confirma APROBAR la reapertura? La planeación volverá a Borrador para editarse."
            : "¿Confirma DENEGAR la solicitud de reapertura? La planeación permanecerá Aprobada y bloqueada.";
    } else {
        textoConfirm = decision === 'aprobar'
            ? "¿Confirma APROBAR este presupuesto logístico?"
            : "¿Confirma RECHAZAR esta planeación?";
    }
    let iconConfirm = decision === 'aprobar' ? "question" : "warning";
    let btnColor = decision === 'aprobar' ? "#198754" : "#dc3545";

    Swal.fire({
        title: 'Confirmar Decisión',
        text: textoConfirm,
        icon: iconConfirm,
        showCancelButton: true,
        confirmButtonColor: btnColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('decision').value = decisionFinal;
            ejecutarAjaxDecision();
        }
    });
}

function ejecutarAjaxDecision() {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_aprobaciones/resolver';
    let formData = new FormData(document.querySelector("#formAprobacion"));

    Swal.fire({
        title: 'Procesando...',
        text: 'Registrando respuesta.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            Swal.close();
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status === true || objData.status === 'success') {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        let modalEl = document.getElementById('modalEvaluarPlan');
                        if (modalEl) {
                            let m = bootstrap.Modal.getInstance(modalEl);
                            if (m) m.hide();
                        }
                    } else if (typeof $ !== 'undefined') {
                        $('#modalEvaluarPlan').modal("hide");
                    }
                    Swal.fire("Resolución Exitosa", objData.msg || objData.message || "Operación realizada", "success");
                    if (typeof tableAprobaciones !== 'undefined' && tableAprobaciones) {
                        tableAprobaciones.ajax.reload();
                    }
                } else {
                    let msg = (objData && (objData.msg || objData.message)) ? (objData.msg || objData.message) : 'No se pudo procesar la resolución.';
                    Swal.fire("Atención", msg, "warning");
                }
            } catch (e) {
                console.error(e);
                Swal.fire("Error", "Error al procesar la respuesta del servidor.", "error");
            }
        }
    };
}

// =========================================================================
// PESTAÑA 3: AUTORIZACIÓN EXCEPCIONAL PRE-ENVÍO (PE-) -> ENVÍO OFICIAL (EN-)
// =========================================================================

function escPreExcHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function actualizarMetricasPreEnvioExc(data) {
    const lista = Array.isArray(data) ? data : [];
    const pendientes = lista.filter(r => String(r.estatus || '').toUpperCase() === 'PENDIENTE');
    const aprobadas  = lista.filter(r => String(r.estatus || '').toUpperCase() === 'APROBADA');
    const rechazadas = lista.filter(r => String(r.estatus || '').toUpperCase() === 'RECHAZADA');
    const vinsObs    = pendientes.reduce((acc, r) => acc + (parseInt(r.vins_con_observacion || 0, 10)), 0);

    const setTxt = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.innerText = String(val);
    };
    setTxt('kpiPreExcPendientes', pendientes.length);
    setTxt('kpiPreExcAprobadas', aprobadas.length);
    setTxt('kpiPreExcRechazadas', rechazadas.length);
    setTxt('kpiPreExcVinsObs', vinsObs);

    const badgePre = document.getElementById('badgePreEnviosPendientesAprob');
    if (badgePre) {
        badgePre.innerText = String(pendientes.length);
        badgePre.classList.toggle('d-none', pendientes.length === 0);
    }
}

function filtrarTablaPreExc(term) {
    if (typeof tableAprobPreEnvios !== 'undefined' && tableAprobPreEnvios) {
        tableAprobPreEnvios.search(term || '').draw();
    }
}

function filtrarPreEnvioExcPorChip(filtro, btn) {
    document.querySelectorAll('.chip-pe-exc-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.replace('btn-secondary', 'btn-outline-secondary');
        b.classList.replace('btn-warning', 'btn-outline-warning');
        b.classList.replace('btn-success', 'btn-outline-success');
        b.classList.replace('btn-danger', 'btn-outline-danger');
    });

    if (btn) {
        btn.classList.add('active');
        if (btn.classList.contains('btn-outline-secondary')) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        if (btn.classList.contains('btn-outline-warning')) btn.classList.replace('btn-outline-warning', 'btn-warning');
        if (btn.classList.contains('btn-outline-success')) btn.classList.replace('btn-outline-success', 'btn-success');
        if (btn.classList.contains('btn-outline-danger')) btn.classList.replace('btn-outline-danger', 'btn-danger');
    }

    if (typeof tableAprobPreEnvios !== 'undefined' && tableAprobPreEnvios) {
        tableAprobPreEnvios.column(6).search(filtro || '').draw();
    }
}

async function fntEvaluarSolicitudPreEnvio(idSolicitud, soloLectura = false) {
    const idSol = parseInt(idSolicitud || 0, 10);
    if (idSol <= 0) return;

    const modalEl = document.getElementById('modalEvaluarPreEnvioExc');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (typeof $ !== 'undefined') {
            $('#modalEvaluarPreEnvioExc').modal('show');
        }
    }

    const obsInput = document.getElementById('exc_obs_aprobador');
    const idInput  = document.getElementById('exc_id_solicitud');
    if (idInput) idInput.value = String(idSol);
    if (obsInput) {
        obsInput.value = '';
        obsInput.disabled = !!soloLectura;
    }

    const btnRech = document.getElementById('btnRechazarPreExcModal');
    const btnAprob = document.getElementById('btnAprobarPreExcModal');
    const puedeAprobar = (typeof ES_APROBADOR_GASTOS === 'undefined') ? true : !!ES_APROBADOR_GASTOS;
    if (btnRech) btnRech.style.display = (soloLectura || !puedeAprobar) ? 'none' : '';
    if (btnAprob) btnAprob.style.display = (soloLectura || !puedeAprobar) ? 'none' : '';

    const tbody = document.getElementById('tbodyVinsPreExc');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando detalle de solicitud...</td></tr>';
    }

    try {
        const res = await fetch(base_url + '/Lgs_aprobaciones/getDetalleSolicitudPreEnvio/' + idSol);
        const resp = await res.json();
        if (!(resp && (resp.status === 'success' || resp.status === true) && resp.data)) {
            throw new Error((resp && (resp.msg || resp.message)) || 'No se pudo obtener el detalle.');
        }

        const { solicitud, envio, nodos, vins } = resp.data;
        const setTxt = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        const folioGen = solicitud.folio_envio_generado
            ? ` -> ${solicitud.folio_envio_generado}`
            : '';
        setTxt('lblFolioSolPreExc', solicitud.folio_solicitud || ('SOL-' + idSol));
        setTxt('lblFolioPreEnvioExc', (solicitud.folio_preenvio || '-') + folioGen);

        let origenTxt = (envio && envio.origen_nombre) ? envio.origen_nombre : 'Origen';
        let destinoTxt = (envio && envio.destino_nombre_libre) ? envio.destino_nombre_libre : 'Destino';
        if (Array.isArray(nodos) && nodos.length > 0) {
            const n0 = nodos[0];
            const nL = nodos[nodos.length - 1];
            origenTxt = n0.ubicacion_nombre || n0.destino_nombre_libre || origenTxt;
            destinoTxt = nL.ubicacion_nombre || nL.destino_nombre_libre || destinoTxt;
        }
        setTxt('lblRutaPreExc', `${origenTxt} → ${destinoTxt}`);
        setTxt('lblTrasladistaPreExc', (envio && envio.trasladista_nombre) ? `${envio.tipo_traslado_nombre || ''} · ${envio.trasladista_nombre}` : 'Sin trasladista');
        setTxt('lblCostoPreExc', '$' + parseFloat((envio && envio.costo_total) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        setTxt('lblKmPreExc', parseFloat((envio && envio.km_total) || 0).toFixed(1) + ' km');
        setTxt('lblSolicitantePreExc', solicitud.solicitante_nombre || 'Solicitante');
        setTxt('lblMotivoPreExc', solicitud.motivo_solicitud || 'Sin justificación capturada.');

        const estActual = String(solicitud.estatus || 'PENDIENTE').toUpperCase();
        const bloquearEdicion = !!soloLectura || estActual !== 'PENDIENTE' || !puedeAprobar;
        if (btnRech) btnRech.style.display = bloquearEdicion ? 'none' : '';
        if (btnAprob) btnAprob.style.display = bloquearEdicion ? 'none' : '';
        if (obsInput) {
            obsInput.value = solicitud.obs_aprobador || '';
            obsInput.disabled = bloquearEdicion;
        }

        if (tbody) {
            const listaVins = Array.isArray(vins) ? vins : [];
            if (listaVins.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No se encontraron unidades registradas en esta solicitud.</td></tr>';
            } else {
                tbody.innerHTML = listaVins.map(v => {
                    const cFac  = parseInt(v.cumple_factura || 0, 10) === 1;
                    const cPag  = parseInt(v.cumple_pago || 0, 10) === 1;
                    const cAut  = parseInt(v.cumple_autorizacion || 0, 10) === 1;
                    const seEnv = parseInt(v.se_envia || 0, 10) === 1;

                    const badgeFac = cFac
                        ? `<span class="badge bg-soft-success text-success">Fac: ${escPreExcHtml(v.factura || 'OK')}</span>`
                        : '<span class="badge bg-soft-danger text-danger">Sin Factura</span>';

                    const pagoTxt = escPreExcHtml(v.estatus_pago_texto || (cPag ? 'Pagada / Crédito' : 'Pendiente'));
                    const badgePag = cPag
                        ? `<span class="badge bg-soft-success text-success">${pagoTxt}</span>`
                        : `<span class="badge bg-soft-warning text-warning">${pagoTxt}</span>`;

                    const badgeAut = cAut
                        ? '<span class="badge bg-soft-success text-success">Aut. Mov: SÍ</span>'
                        : '<span class="badge bg-soft-danger text-danger">Aut. Mov: NO</span>';

                    const badgeSeEnv = seEnv
                        ? '<span class="badge bg-success text-white">SÍ</span>'
                        : '<span class="badge bg-danger text-white">NO</span>';

                    const obsTxt = Array.isArray(v.faltantes) && v.faltantes.length > 0
                        ? v.faltantes.join(' · ')
                        : (v.estado_envio_motivo || v.estatus || 'Autorizada');

                    const clSede = [v.cliente_nombre, v.destino_sede].filter(Boolean).map(escPreExcHtml).join(' · ') || '<span class="text-muted">-</span>';

                    return `<tr class="${seEnv ? '' : 'table-warning bg-opacity-25'}">
                        <td><div class="fw-bold text-dark font-monospace">${escPreExcHtml(v.vin || '-')}</div><small class="text-muted">${escPreExcHtml(v.modelo || '')}</small></td>
                        <td><span class="fs-11">${clSede}</span></td>
                        <td>${badgeFac}</td>
                        <td>${badgePag}</td>
                        <td>${badgeAut}</td>
                        <td class="text-center">${badgeSeEnv}</td>
                        <td><small class="${seEnv ? 'text-success fw-semibold' : 'text-danger fw-semibold'}">${escPreExcHtml(obsTxt)}</small></td>
                    </tr>`;
                }).join('');
            }
        }
    } catch (err) {
        console.error(err);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3"><i class="ri-error-warning-line me-1"></i> ${escPreExcHtml(err.message || 'Error al cargar el detalle.')}</td></tr>`;
        }
    }
}

async function enviarDictamenPreExc(decision) {
    const idSolicitud = parseInt(document.getElementById('exc_id_solicitud')?.value || 0, 10);
    const obs         = (document.getElementById('exc_obs_aprobador')?.value || '').trim();
    if (idSolicitud <= 0) return;

    if (decision === 'rechazar' && obs === '') {
        Swal.fire('Atención', 'Para rechazar la solicitud excepcional es obligatorio capturar el motivo en los comentarios.', 'warning');
        return;
    }

    const esAprobar = (decision === 'aprobar');
    const conf = await Swal.fire({
        title: esAprobar ? 'Autorizar Paso a Envío Oficial' : 'Rechazar Solicitud Excepcional',
        html: esAprobar
            ? 'Al autorizar, el Pre-envío se convertirá inmediatamente en <strong>Envío Oficial (EN-)</strong> y sus unidades pasarán a logística asignada.'
            : 'El Pre-envío permanecerá como <strong>PE-</strong> y el solicitante verá el rechazo en Bandeja y Acomodo.',
        icon: esAprobar ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: esAprobar ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: esAprobar ? 'Sí, Autorizar y Pasar a EN-' : 'Sí, Rechazar',
        cancelButtonText: 'Cancelar'
    });

    if (!conf.isConfirmed) return;

    Swal.fire({
        title: 'Procesando dictamen...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const fd = new FormData();
        fd.append('id_solicitud', String(idSolicitud));
        fd.append('decision', decision);
        fd.append('obs_aprobador', obs);

        const res  = await fetch(base_url + '/Lgs_aprobaciones/resolverSolicitudPreEnvio', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        Swal.close();

        if (!(data && (data.status === 'success' || data.status === true))) {
            Swal.fire('Atención', (data && (data.msg || data.message)) || 'No se pudo dictaminar la solicitud.', 'warning');
            return;
        }

        const modalEl = document.getElementById('modalEvaluarPreEnvioExc');
        if (modalEl) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            } else if (typeof $ !== 'undefined') {
                $('#modalEvaluarPreEnvioExc').modal('hide');
            }
        }

        if (typeof tableAprobPreEnvios !== 'undefined' && tableAprobPreEnvios) {
            tableAprobPreEnvios.ajax.reload(null, false);
        }

        const folioGen = (data.data && data.data.folio_envio_generado) ? data.data.folio_envio_generado : '';
        if (esAprobar && folioGen) {
            const goResp = await Swal.fire({
                title: 'Autorizado y Convertido',
                html: `${escPreExcHtml(data.message || data.msg || 'Conversión exitosa.')}<br><small class="text-muted">Ya puedes consultarlo y planearlo en el módulo de Envíos.</small>`,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Ir a Envíos',
                cancelButtonText: 'Seguir en Aprobaciones'
            });
            if (goResp.isConfirmed) {
                window.location.href = base_url + '/Lgs_envios';
            }
        } else {
            Swal.fire('Dictamen Registrado', (data.message || data.msg || 'Operación completada.'), 'success');
        }
    } catch (err) {
        Swal.close();
        console.error(err);
        Swal.fire('Error', 'Error de comunicación al registrar el dictamen.', 'error');
    }
}
