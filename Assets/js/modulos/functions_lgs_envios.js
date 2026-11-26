function esModoPreEnvioJS() {
    return Boolean(window.LGS_ENVIOS_CONFIG && window.LGS_ENVIOS_CONFIG.modoPreEnvio);
}

function fntSwitchView(view) {
    const secTabsWrap = document.querySelector("#tabsBandejaWrapper");
    const secGrid = document.querySelector("#view-index-envios");
    const secForm = document.querySelector("#view-form-envios");

    if (view === 'form') {
        if (secTabsWrap) {
            secTabsWrap.style.display = "none";
        } else if (secGrid) {
            secGrid.style.display = "none";
        }
        if (secForm) secForm.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        if (secForm) secForm.style.display = "none";
        if (secTabsWrap) {
            secTabsWrap.style.display = "block";
        } else if (secGrid) {
            secGrid.style.display = "block";
        }
        if (typeof tableEnvios !== 'undefined' && tableEnvios) {
            setTimeout(() => {
                try { tableEnvios.columns.adjust(); } catch (e) {}
            }, 80);
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function cancelFormEnvio() {
    document.querySelector("#formEnvio").reset();
    fntSwitchView('grid');
}

let tableEnvios;

document.addEventListener('DOMContentLoaded', function () {
    const isPreEnvio = esModoPreEnvioJS();

    // Inicializar DataTable
    tableEnvios = $('#tableEnvios').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_envios/getEnvios" + (isPreEnvio ? "?es_preenvio=1" : ""),
            "dataSrc": ""
        },
        "columns": [
            {
                "data": "folio",
                "className": "ps-3",
                "render": function(data, type, row) {
                    const folio = data || 'S/F';
                    const fechaRaw = (row && row.fecha_tentativa_envio && row.fecha_tentativa_envio !== 'null')
                        ? String(row.fecha_tentativa_envio).replace('T', ' ').substring(0, 16)
                        : '';
                    if (type !== 'display') {
                        return `${folio} ${row.id_envio || ''} ${fechaRaw} ${row.vins_list || ''}`;
                    }
                    const esPreRow = isPreEnvio || (row && parseInt(row.es_preenvio) === 1) || String(folio).startsWith('PE-');
                    const badgeCls = esPreRow ? 'bg-soft-info text-info border border-info-subtle' : 'bg-soft-primary text-primary border border-primary-subtle';
                    let html = `<div class="d-flex align-items-center gap-1">
                                    <span class="badge ${badgeCls} fs-12 fw-bold px-2 py-1">${folio}</span>
                                </div>`;
                    if (fechaRaw) {
                        html += `<div class="text-muted fs-11 mt-1"><i class="ri-calendar-event-line me-1"></i>${fechaRaw}</div>`;
                    } else {
                        html += `<div class="text-muted fs-11 mt-1">Sin fecha prog.</div>`;
                    }
                    return html;
                }
            },
            {
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-11"><i class="ri-draft-line me-1"></i>Borrador</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-11"><i class="ri-time-line me-1"></i>Por Aprobar</span>'; break;
                        case 3: badge = '<span class="badge bg-soft-primary text-primary fs-11"><i class="ri-checkbox-circle-line me-1"></i>Aprobado</span>'; break;
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-11"><i class="ri-close-circle-line me-1"></i>Rechazado</span>'; break;
                        case 5: badge = '<span class="badge fs-11" style="background-color: rgba(139, 92, 246, 0.15); color: #8B5CF6;"><i class="ri-calendar-check-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-primary text-primary border border-primary fs-11"><i class="ri-truck-line me-1"></i>En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-11"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        case 8: badge = '<span class="badge bg-soft-dark text-dark fs-11"><i class="ri-check-line me-1"></i>Finalizado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-11">Estado ' + data + '</span>'; break;
                    }
                    if (row && parseInt(row.is_pausado) === 1) {
                        badge = `<div class="d-flex flex-column gap-1 align-items-start">
                                    <span class="badge bg-danger text-white fs-10" title="Motivo: ${row.motivo_pausa || 'Pausa por Incidencia'}"><i class="ri-pause-circle-line me-1"></i>PAUSADO</span>
                                    ${badge}
                                 </div>`;
                    }
                    if (row && row.solicitud_estatus === 'PENDIENTE') {
                        badge += `<div class="mt-1"><span class="badge bg-soft-warning text-warning border border-warning-subtle fs-10" title="En espera de autorización en Aprobaciones"><i class="ri-shield-user-line me-1"></i>Aut. Pendiente</span></div>`;
                    } else if (row && row.solicitud_estatus === 'RECHAZADA') {
                        badge += `<div class="mt-1"><span class="badge bg-soft-danger text-danger border border-danger-subtle fs-10" title="${(row.solicitud_obs_aprobador || 'Rechazada en Aprobaciones').replace(/"/g, '&quot;')}"><i class="ri-close-circle-line me-1"></i>Aut. Rechazada</span></div>`;
                    }
                    return badge;
                }
            },
            {
                "data": "origen",
                "render": function(data, type, row) {
                    const orig = data || 'Sin Origen';
                    const dest = (row && row.destino) ? row.destino : 'Sin Destino';
                    const ruta = (row && row.paradas_list) ? row.paradas_list : '';
                    if (type !== 'display') {
                        return `${orig} ${dest} ${ruta}`;
                    }
                    return `<div class="lgs-route-cell" title="${(ruta || (orig + ' -> ' + dest)).replace(/"/g, '&quot;')}">
                                <span class="lgs-route-line fs-12 text-dark fw-semibold"><i class="ri-record-circle-line text-success me-1"></i>${orig}</span>
                                <span class="lgs-route-line fs-12 text-muted mt-1"><i class="ri-map-pin-2-line text-warning me-1"></i>${dest}</span>
                            </div>`;
                }
            },
            {
                "data": "total_vins",
                "render": function(data, type, row) {
                    const raw = (row && row.vins_list) ? String(row.vins_list).trim() : '';
                    if (type !== 'display') return raw || 'Sin VINs';
                    if (!raw) {
                        return '<span class="badge bg-light text-muted border fs-11">0 unidades</span>';
                    }
                    const vins = raw.split(', ').filter(v => v.trim());
                    const firstVin = vins[0] || '';
                    const extra = vins.length > 1 ? ` <span class="badge bg-soft-primary text-primary fs-10">+${vins.length - 1}</span>` : '';
                    return `<div title="${raw.replace(/"/g, '&quot;')}">
                                <span class="badge bg-soft-dark text-dark font-monospace fs-11 border">${firstVin}</span>${extra}
                                <div class="text-muted fs-11 mt-1">${vins.length} unidad(es)</div>
                            </div>`;
                }
            },
            {
                "data": "trasladista",
                "render": function(data, type, row) {
                    const prov = data || 'Sin trasladista';
                    const tipo = (row && row.tipo_traslado) ? row.tipo_traslado : '-';
                    const mod  = (row && row.modalidad_calculo) ? row.modalidad_calculo : '';
                    const mot  = (row && row.motivo) ? row.motivo : '';
                    if (type !== 'display') return `${prov} ${tipo} ${mod} ${mot}`;
                    const modTxt = mod === 'DIRECTO' ? ' · Directo' : (mod ? ' · Escalonado' : '');
                    return `<div style="max-width: 190px;">
                                <div class="fw-semibold text-dark fs-12 text-truncate" title="${prov.replace(/"/g, '&quot;')}">${prov}</div>
                                <div class="text-muted fs-11 text-truncate" title="${(tipo + modTxt + (mot ? ' · ' + mot : '')).replace(/"/g, '&quot;')}">
                                    <i class="ri-truck-line text-primary me-1"></i>${tipo}${modTxt}
                                </div>
                            </div>`;
                }
            },
            {
                "data": "km_total",
                "render": function(data, type, row) {
                    const kmVal = parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    const nParadas = (row && row.total_paradas) ? row.total_paradas : 1;
                    if (type !== 'display') return kmVal;
                    return `<span class="badge bg-soft-info text-info fs-11 fw-bold"><i class="ri-route-line me-1"></i>${kmVal} km</span>
                            <div class="text-muted fs-11 mt-1">${nParadas} parada(s)</div>`;
                }
            },
            {
                "data": "costo_total",
                "render": function (data, type, row) {
                    const val = parseFloat(data || 0);
                    if (type !== 'display') return val;
                    let formatted = '<span class="fw-bold text-success fs-13">$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span>';
                    if (row && parseInt(row.es_monto_abierto) === 1) {
                        formatted += '<span class="badge bg-soft-warning text-warning fs-10 d-block mt-1" title="' + (row.motivo_monto_abierto || 'Acuerdo Comercial') + '"><i class="ri-hand-coin-line me-1"></i>Monto Abierto</span>';
                    }
                    return formatted;
                }
            },
            {
                "data": "id_envio",
                "className": "text-end pe-3",
                "orderable": false,
                "render": function (data, type, row) {
                    const estado = parseInt(row.id_estado);
                    const esPreRow = isPreEnvio || (row && parseInt(row.es_preenvio) === 1) || String(row.folio || '').startsWith('PE-');
                    const btns = [];

                    // 1. Acción Principal: Acomodo (en borrador/finalizado) o Ver Envío (en tránsito/aprobado)
                    if (estado === 1 || estado === 8) {
                        btns.push(`<button type="button" class="btn btn-sm ${esPreRow ? 'btn-soft-info' : 'btn-soft-primary'} btn-table-action" onClick="fntViewEnvio(${data})" title="Asignar Madrina/Chofer y acomodar VINs">
                                    <i class="ri-truck-line"></i> Acomodo
                                   </button>`);
                    } else {
                        btns.push(`<button type="button" class="btn btn-sm btn-soft-primary btn-table-action" onClick="fntViewEnvio(${data})" title="Ver detalle y seguimiento del envío">
                                    <i class="ri-eye-line"></i> Ver Envío
                                   </button>`);
                    }

                    // 2. En Pre-envíos: botón directo Pasar a Envío (EN-)
                    if (esPreRow) {
                        btns.push(`<button type="button" class="btn btn-sm btn-success btn-table-action" onClick="fntConvertirPreEnvio(${data}, '${row.folio || ''}')" title="Validar Factura/Pago/Aut. y pasar a Envío Oficial (EN-)">
                                    <i class="ri-exchange-line"></i> Pasar a EN-
                                   </button>`);
                    }

                    // 3. Reanudar si está pausado
                    if (!esPreRow && [1, 2, 3, 5, 6].includes(estado) && parseInt(row.is_pausado) === 1) {
                        btns.push(`<button type="button" class="btn btn-sm btn-soft-success btn-table-action" onClick="fntReanudarEnvio(${data}, '${row.folio}')" title="Reanudar Envío">
                                    <i class="ri-play-circle-line"></i> Reanudar
                                   </button>`);
                    }

                    // 4. Reabrir si está en Por Aprobar (2) o Rechazado (4)
                    if (estado === 2 || estado === 4) {
                        btns.push(`<button type="button" class="btn btn-soft-warning btn-icon-lgs" onClick="fntReabrirEnvio(${data})" title="Reabrir Envío a Borrador">
                                    <i class="ri-restart-line"></i>
                                   </button>`);
                    }

                    // 5. Editar / Ver Ruta (icono compacto uniforme)
                    if (estado === 1) {
                        btns.push(`<button type="button" class="btn btn-light border text-secondary btn-icon-lgs" onClick="fntEditRuta(${data})" title="Editar Ruta y Paradas">
                                    <i class="ri-edit-line"></i>
                                   </button>`);
                        btns.push(`<button type="button" class="btn btn-soft-danger btn-icon-lgs" onClick="fntDelEnvio(${data})" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                   </button>`);
                    } else {
                        btns.push(`<button type="button" class="btn btn-light border text-secondary btn-icon-lgs" onClick="fntEditRuta(${data})" title="Ver Configuración de Ruta">
                                    <i class="ri-route-line"></i>
                                   </button>`);
                    }

                    return `<div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">${btns.join('')}</div>`;
                }
            }
        ],
        "responsive": false,
        "autoWidth": false,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasEnvios(settings.json || []);
        }
    });

    tableEnvios.on('search.dt', function() {
        if (tableEnvios) {
            const dataFiltrada = tableEnvios.rows({ search: 'applied' }).data().toArray();
            actualizarMetricasEnvios(dataFiltrada);
        }
    });

    // Cargar Catálogos Iniciales
    cargarProveedoresTrasladistas();

    // Auto-apertura si viene parámetro ?edit=ID en URL (Navegación directa desde Acomodo o alertas)
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        setTimeout(() => {
            fntEditRuta(parseInt(editId));
        }, 300);
    }
});

/**
 * Filtra el DataTable de envíos en tiempo real.
 * Busca en columnas de folio, VINs, origen, destino.
 */
function filtrarTablaPorVin(term) {
    if (tableEnvios) {
        tableEnvios.search(term).draw();
    }
}

/**
 * Filtra el DataTable por estado usando los chips de acceso rápido
 */
function filtrarPorEstadoChip(filtro, btn) {
    document.querySelectorAll('.filter-chip-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    
    if (typeof tableEnvios !== "undefined" && tableEnvios) {
        if (!filtro) {
            tableEnvios.search('').draw();
        } else {
            tableEnvios.search(filtro).draw();
        }
    }
}

/**
 * Permite filtrar la tabla haciendo clic en las cards de KPIs superiores
 */
function filtrarPorEstadoCard(filtro, chipId) {
    const chipBtn = document.getElementById(chipId);
    filtrarPorEstadoChip(filtro, chipBtn);
    
    // Desplazamiento suave hacia la tabla para comodidad del usuario
    const tablaCard = document.getElementById('tableEnvios');
    if (tablaCard) {
        tablaCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

let filtroPeriodoEnviosActual = 'todos';
let filtroFechaInicioEnvios = null;
let filtroFechaFinEnvios = null;

// Hook de búsqueda personalizada en DataTables para tableEnvios por período
if (typeof $ !== "undefined" && $.fn && $.fn.dataTable && $.fn.dataTable.ext) {
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.sTableId !== 'tableEnvios') return true;
        if (filtroPeriodoEnviosActual === 'todos') return true;

        const row = (settings.aoData && settings.aoData[dataIndex]) ? settings.aoData[dataIndex]._aData : null;
        if (!row) return true;

        const fechaStr = row.fecha_tentativa_envio || row.created_at;
        if (!fechaStr || fechaStr === 'null') return false;

        const fParts = fechaStr.split(' ')[0].split('T')[0];
        if (!fParts) return false;

        const rowDate = new Date(fParts + 'T00:00:00');
        if (isNaN(rowDate.getTime())) return false;

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (filtroPeriodoEnviosActual === 'semana') {
            const dayOfWeek = today.getDay();
            const diffToMon = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
            const monday = new Date(today.getFullYear(), today.getMonth(), diffToMon);
            const sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 6, 23, 59, 59, 999);
            return rowDate >= monday && rowDate <= sunday;
        } else if (filtroPeriodoEnviosActual === 'mes') {
            return rowDate.getFullYear() === now.getFullYear() && rowDate.getMonth() === now.getMonth();
        } else if (filtroPeriodoEnviosActual === 'anio') {
            return rowDate.getFullYear() === now.getFullYear();
        } else if (filtroPeriodoEnviosActual === 'abierta') {
            if (filtroFechaInicioEnvios) {
                const dInicio = new Date(filtroFechaInicioEnvios + 'T00:00:00');
                if (rowDate < dInicio) return false;
            }
            if (filtroFechaFinEnvios) {
                const dFin = new Date(filtroFechaFinEnvios + 'T23:59:59');
                if (rowDate > dFin) return false;
            }
            return true;
        }

        return true;
    });
}

/**
 * Permite cambiar el período temporal de envíos (todos, semana, mes, año, abierta)
 */
function cambiarPeriodoEnvios(periodo, btn) {
    filtroPeriodoEnviosActual = periodo;
    document.querySelectorAll('.btn-periodo-envios').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.add('active');
        btn.classList.remove('btn-outline-secondary');
    }

    const boxAbierta = document.getElementById('boxFiltroFechaAbiertaEnvios');
    if (periodo === 'abierta') {
        if (boxAbierta) boxAbierta.classList.remove('d-none');
    } else {
        if (boxAbierta) boxAbierta.classList.add('d-none');
        filtroFechaInicioEnvios = null;
        filtroFechaFinEnvios = null;
        if (tableEnvios) tableEnvios.draw();
    }
}

/**
 * Aplica el rango de fechas abiertas
 */
function aplicarFiltroFechaAbiertaEnvios() {
    const dIni = document.getElementById('filtroEnvioFechaInicio')?.value;
    const dFin = document.getElementById('filtroEnvioFechaFin')?.value;
    filtroFechaInicioEnvios = dIni || null;
    filtroFechaFinEnvios = dFin || null;
    if (tableEnvios) tableEnvios.draw();
}

/**
 * Limpia el filtro de fechas abiertas
 */
function limpiarFiltroFechasEnvios() {
    if (document.getElementById('filtroEnvioFechaInicio')) document.getElementById('filtroEnvioFechaInicio').value = '';
    if (document.getElementById('filtroEnvioFechaFin')) document.getElementById('filtroEnvioFechaFin').value = '';
    filtroFechaInicioEnvios = null;
    filtroFechaFinEnvios = null;
    if (tableEnvios) tableEnvios.draw();
}

/**
 * Calcula y actualiza las métricas y monto total (sin IVA) en tiempo real
 */
function actualizarMetricasEnvios(data) {
    if (!Array.isArray(data)) return;
    
    let total = data.length;
    let creados = data.filter(e => parseInt(e.id_estado) === 1).length;
    let porAprobar = data.filter(e => parseInt(e.id_estado) === 2).length;
    let aprobados = data.filter(e => parseInt(e.id_estado) === 3).length;
    let programados = data.filter(e => parseInt(e.id_estado) === 5).length;
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let pausados = data.filter(e => parseInt(e.is_pausado) === 1).length;
    let entregados = data.filter(e => parseInt(e.id_estado) === 7 || parseInt(e.id_estado) === 8).length;

    // Calcular monto total de envíos visibles / filtrados
    let totalCosto = 0;
    let enviosFiltrados = 0;
    if (typeof tableEnvios !== "undefined" && tableEnvios && tableEnvios.rows) {
        try {
            const rowsVisibles = tableEnvios.rows({ search: 'applied' }).data().toArray();
            enviosFiltrados = rowsVisibles.length;
            rowsVisibles.forEach(e => {
                totalCosto += parseFloat(e.costo_total || 0);
            });
        } catch (err) {
            enviosFiltrados = data.length;
            data.forEach(e => { totalCosto += parseFloat(e.costo_total || 0); });
        }
    } else {
        enviosFiltrados = data.length;
        data.forEach(e => { totalCosto += parseFloat(e.costo_total || 0); });
    }

    if (document.getElementById('cardTotalEnvios')) document.getElementById('cardTotalEnvios').innerText = total;
    if (document.getElementById('badgeTotalPreEnviosTab')) document.getElementById('badgeTotalPreEnviosTab').innerText = total;
    if (document.getElementById('cardEnviosCreados')) document.getElementById('cardEnviosCreados').innerText = creados;
    if (document.getElementById('cardEnviosPorAprobar')) document.getElementById('cardEnviosPorAprobar').innerText = porAprobar;
    if (document.getElementById('cardEnviosAprobados')) document.getElementById('cardEnviosAprobados').innerText = aprobados;
    if (document.getElementById('cardEnviosProgramados')) document.getElementById('cardEnviosProgramados').innerText = programados;
    if (document.getElementById('cardEnviosTransito')) document.getElementById('cardEnviosTransito').innerText = transito;
    if (document.getElementById('cardEnviosPausados')) document.getElementById('cardEnviosPausados').innerText = pausados;
    if (document.getElementById('cardEnviosEntregados')) document.getElementById('cardEnviosEntregados').innerText = entregados;

    if (document.getElementById('cardMontoTotalEnvios')) {
        document.getElementById('cardMontoTotalEnvios').innerText = '$' + totalCosto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (document.getElementById('badgeEnviosFiltradosCount')) {
        document.getElementById('badgeEnviosFiltradosCount').innerText = `${enviosFiltrados} viaje${enviosFiltrados === 1 ? '' : 's'}`;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// PARADAS MULTI-DESTINO
// ──────────────────────────────────────────────────────────────────────────────

/** Lee el catálogo unificado de ubicaciones embebido en el HTML */
function getCatalogoUbicaciones() {
    const elUbi = document.getElementById('catalogoUbicaciones');
    if (elUbi) {
        try {
            const arr = JSON.parse(elUbi.textContent);
            if (Array.isArray(arr) && arr.length > 0) return arr;
        } catch (e) {}
    }
    const elDest = document.getElementById('catalogoDestinos');
    if (elDest) {
        try { return JSON.parse(elDest.textContent); } catch (e) { return []; }
    }
    return [];
}

/** Lee el catálogo de distribuidores embebido en el HTML */
function getCatalogoDistribuidores() {
    const el = document.getElementById('catalogoDistribuidores');
    if (el) {
        try {
            const arr = JSON.parse(el.textContent);
            if (Array.isArray(arr)) return arr;
        } catch (e) {}
    }
    return [];
}

/** Lee el catálogo estructurado de Plantas y Subalmacenes PT (wms_almacenes.pt = 1) */
function getCatalogoPlantasWms() {
    const el = document.getElementById('catalogoPlantasWms');
    if (el) {
        try {
            const arr = JSON.parse(el.textContent);
            if (Array.isArray(arr) && arr.length > 0) return arr;
        } catch (e) {}
    }
    // Fallback derivando desde catalogoUbicaciones si no estuviera embebido catalogoPlantasWms
    const map = {};
    getCatalogoUbicaciones().forEach(u => {
        if (parseInt(u.id_almacen_wms || 0, 10) > 0 && parseInt(u.almacen_pt || 0, 10) === 1) {
            const pid = parseInt(u.id_planta_mrp || 0, 10);
            if (!map[pid]) {
                map[pid] = {
                    id_planta: pid,
                    cve_planta: u.cve_planta || (pid > 0 ? 'PL' + pid : 'PT'),
                    nombre_planta: u.nombre_planta || (pid > 0 ? 'Planta #' + pid : 'Almacenes PT Generales'),
                    subalmacenes: []
                };
            }
            map[pid].subalmacenes.push({
                id_ubicacion: parseInt(u.id || u.id_ubicacion, 10),
                id_almacen_wms: parseInt(u.id_almacen_wms, 10),
                cve_almacen: u.cve_almacen || '',
                nombre: u.nombre || '',
                direccion: u.direccion || '',
                pt: 1
            });
        }
    });
    return Object.values(map);
}

/** Construye las opciones de Plantas WMS con almacenes PT */
function buildPlantasWmsOptions(selectedPlantaId) {
    const plantas = getCatalogoPlantasWms();
    let html = '<option value="">Seleccione planta (WMS)...</option>';
    plantas.forEach(p => {
        const pid = String(p.id_planta);
        const sel = (selectedPlantaId !== undefined && selectedPlantaId !== null && selectedPlantaId !== '' && pid === String(selectedPlantaId)) ? 'selected' : '';
        const cnt = Array.isArray(p.subalmacenes) ? p.subalmacenes.length : 0;
        const cve = p.cve_planta ? `${p.cve_planta} · ` : '';
        const badgeAlm = cnt > 0 ? ` (${cnt} almacén PT)` : '';
        html += `<option value="${escapeHtmlEnvios(pid)}" ${sel}>${escapeHtmlEnvios(cve + (p.nombre_planta || 'Planta'))}${escapeHtmlEnvios(badgeAlm)}</option>`;
    });
    return html;
}

/** Evita duplicar "Nombre — Nombre" cuando la dirección coincide con el nombre de la sede */
function formatearEtiquetaUbicacion(nombre, direccion, prefijo = '') {
    const nom = String(nombre || '').trim();
    const dir = String(direccion || '').trim();
    const norm = (v) => v.toLowerCase().replace(/\s+/g, ' ').trim();
    const mostrarDir = dir !== '' && norm(dir) !== norm(nom);
    return prefijo + nom + (mostrarDir ? ` — ${dir}` : '');
}

/** Sincroniza el select interno oculto .nodo-select-ubicacion con la ubicación elegida */
function setUbicacionNodoHidden(card, idUbi, triggerChange = true) {
    const selectUbi = card.querySelector('.nodo-select-ubicacion');
    const infoSpan  = card.querySelector('.nodo-direccion-preview');
    const legacyHint = card.querySelector('.nodo-legacy-hint');
    if (legacyHint) legacyHint.style.display = 'none';

    if (!selectUbi) return;

    const valStr = idUbi ? String(idUbi) : '';
    if (valStr && !Array.from(selectUbi.options).some(o => String(o.value) === valStr)) {
        const ubiObj = getCatalogoUbicaciones().find(u => String(u.id || u.id_ubicacion) === valStr);
        const opt = document.createElement('option');
        opt.value = valStr;
        opt.textContent = ubiObj ? ubiObj.nombre : ('Ubicación #' + valStr);
        opt.setAttribute('data-direccion', ubiObj ? (ubiObj.direccion || '') : '');
        opt.setAttribute('data-nombre', ubiObj ? (ubiObj.nombre || '') : '');
        selectUbi.appendChild(opt);
    }
    selectUbi.value = valStr;

    const optSel = selectUbi.selectedIndex >= 0 ? selectUbi.options[selectUbi.selectedIndex] : null;
    const dir = optSel ? String(optSel.getAttribute('data-direccion') || '').trim() : '';
    const nom = optSel ? String(optSel.getAttribute('data-nombre') || optSel.textContent || '').trim() : '';
    const mostrarDir = dir !== '' && dir.toLowerCase().replace(/\s+/g, ' ') !== nom.toLowerCase().replace(/\s+/g, ' ');
    if (infoSpan) {
        infoSpan.innerHTML = valStr && mostrarDir ? `<i class="ri-map-pin-line text-danger me-1"></i>${escapeHtmlEnvios(dir)}` : '';
    }

    if (triggerChange) {
        if (valStr) {
            alCambiarUbicacionNodo(selectUbi);
        } else {
            serializarNodos();
        }
    }
}

/** Puebla los subalmacenes PT (wms_almacenes.pt = 1) de una planta seleccionada */
function poblarSubalmacenesWms(card, idPlanta, selectedUbiId = '', triggerChange = true) {
    const selSub = card.querySelector('.nodo-select-subalmacen');
    if (!selSub) return;

    if (idPlanta === undefined || idPlanta === null || String(idPlanta) === '') {
        selSub.innerHTML = '<option value="">Primero seleccione planta...</option>';
        selSub.disabled = true;
        setUbicacionNodoHidden(card, '', triggerChange);
        return;
    }

    const planta = getCatalogoPlantasWms().find(p => String(p.id_planta) === String(idPlanta));
    const subs = planta && Array.isArray(planta.subalmacenes) ? planta.subalmacenes : [];
    if (subs.length === 0) {
        selSub.innerHTML = '<option value="">Sin almacén PT activo en esta planta</option>';
        selSub.disabled = true;
        setUbicacionNodoHidden(card, '', triggerChange);
        return;
    }

    let chosenId = selectedUbiId ? String(selectedUbiId) : '';
    if (!chosenId && subs.length === 1) {
        chosenId = String(subs[0].id_ubicacion);
    }

    let opts = subs.length > 1 ? '<option value="">Seleccione subalmacén PT...</option>' : '';
    subs.forEach(s => {
        const sid = String(s.id_ubicacion);
        const sel = (chosenId && sid === chosenId) ? 'selected' : '';
        const cve = s.cve_almacen ? `${s.cve_almacen} · ` : '';
        const label = formatearEtiquetaUbicacion(s.nombre || '', s.direccion || '', cve);
        opts += `<option value="${escapeHtmlEnvios(sid)}" data-direccion="${escapeHtmlEnvios(s.direccion || '')}" data-nombre="${escapeHtmlEnvios(s.nombre || '')}" ${sel}>${escapeHtmlEnvios(label)}</option>`;
    });
    selSub.innerHTML = opts;
    selSub.disabled = false;

    setUbicacionNodoHidden(card, chosenId, triggerChange);
}

/** Evento al cambiar la planta WMS en un nodo */
function onPlantaWmsChange(selPlanta) {
    const card = selPlanta.closest('.nodo-item');
    if (!card) return;
    poblarSubalmacenesWms(card, selPlanta.value, '', true);
}

/** Evento al cambiar el subalmacén PT en un nodo */
function onSubalmacenWmsChange(selSub) {
    const card = selSub.closest('.nodo-item');
    if (!card) return;
    setUbicacionNodoHidden(card, selSub.value || '', true);
}

/** Construye el HTML de options agrupadas para el select de ubicación de cualquier nodo */
function buildUbicacionOptions(selectedId) {
    const ubicaciones = getCatalogoUbicaciones();
    let html = '<option value="">Seleccione ubicación (Planta, Almacén, Distribuidor)...</option>';

    // Categorizar según id_tipo_destino
    // 5 = Planta, 4 = Almacén, 1 = Distribuidor, 2 = Carrocero, 3 = Cliente Final, 6 = Otro
    const grupos = {
        'Plantas y Centros de Ensamble (Carga)': [],
        'Almacenes y Patios de Maniobra': [],
        'Distribuidores y Agencias (Entrega)': [],
        'Carroceros y Adaptaciones': [],
        'Otras Ubicaciones': []
    };

    ubicaciones.forEach(u => {
        const tipo = parseInt(u.id_tipo_destino || 0);
        if (tipo === 5) {
            grupos['Plantas y Centros de Ensamble (Carga)'].push(u);
        } else if (tipo === 4) {
            grupos['Almacenes y Patios de Maniobra'].push(u);
        } else if (tipo === 1) {
            grupos['Distribuidores y Agencias (Entrega)'].push(u);
        } else if (tipo === 2) {
            grupos['Carroceros y Adaptaciones'].push(u);
        } else {
            grupos['Otras Ubicaciones'].push(u);
        }
    });

    Object.keys(grupos).forEach(catName => {
        if (grupos[catName].length > 0) {
            html += `<optgroup label="${catName}">`;
            grupos[catName].forEach(u => {
                const sel = (String(u.id || u.id_ubicacion) === String(selectedId)) ? 'selected' : '';
                const uId = u.id || u.id_ubicacion;
                const label = formatearEtiquetaUbicacion(u.nombre || '', u.direccion || '');
                html += `<option value="${uId}" data-direccion="${escapeHtmlEnvios(u.direccion || '')}" data-nombre="${escapeHtmlEnvios(u.nombre || '')}" ${sel}>${escapeHtmlEnvios(label)}</option>`;
            });
            html += `</optgroup>`;
        }
    });

    return html;
}

/** Formatea etiqueta visible de distribuidor agregando grupo comercial si aporta contexto */
function formatearEtiquetaDistribuidor(d) {
    const nombre = String((d && d.nombre) || '').trim();
    const grupo = String((d && d.grupo_comercial) || '').trim();
    if (grupo && !nombre.toUpperCase().includes(grupo.toUpperCase())) {
        return `${nombre} (${grupo})`;
    }
    return nombre;
}

/** Construye el HTML de options para el select de distribuidores */
function buildDistribuidorOptions(selectedDistribuidorId) {
    const distribuidores = getCatalogoDistribuidores();
    let html = '<option value="">Seleccione distribuidor...</option>';
    distribuidores.forEach(d => {
        const sel = (String(d.id_distribuidor) === String(selectedDistribuidorId)) ? 'selected' : '';
        const sedesTxt = parseInt(d.total_sedes) > 1 ? ` (${d.total_sedes} sedes)` : '';
        const label = formatearEtiquetaDistribuidor(d) + sedesTxt;
        html += `<option value="${escapeHtmlEnvios(String(d.id_distribuidor))}" ${sel}>${escapeHtmlEnvios(label)}</option>`;
    });
    return html;
}

/** Maneja el cambio del select de distribuidor para cargar sedes dinámicamente */
function onDistribuidorChange(selectDistribuidor) {
    const card = selectDistribuidor.closest('.nodo-item');
    if (!card) return;
    const isAbierto = (card.getAttribute('data-destino-abierto') === '1');
    const selectSede = card.querySelector('.nodo-select-sede');
    const selectUbi = card.querySelector('.nodo-select-ubicacion');
    const infoSpan = card.querySelector('.nodo-direccion-preview');
    const idDistribuidor = selectDistribuidor.value;

    if (!idDistribuidor) {
        if (selectSede) {
            selectSede.innerHTML = '<option value="">Primero seleccione distribuidor</option>';
            selectSede.disabled = true;
        }
        if (!isAbierto) {
            if (selectUbi) selectUbi.value = '';
            if (infoSpan) infoSpan.innerHTML = '';
        }
        serializarNodos();
        return;
    }

    // Buscar sedes del distribuidor o cliente en el catálogo local de ubicaciones
    const distribuidores = getCatalogoDistribuidores();
    const distObj = distribuidores.find(d => String(d.id_distribuidor) === String(idDistribuidor));
    const distClienteId = distObj && distObj.id_cliente ? String(distObj.id_cliente) : '';
    const ubicaciones = getCatalogoUbicaciones();
    const sedes = ubicaciones.filter(u =>
        (u.activo === undefined || u.activo == 1) && (
            String(u.id_distribuidor) === String(idDistribuidor)
            || (distClienteId !== '' && String(u.id_cliente || '') === distClienteId)
        )
    );

    if (selectSede) {
        if (sedes.length === 1) {
            // Auto-seleccionar la única sede
            const s0 = sedes[0];
            const s0Id = s0.id || s0.id_ubicacion;
            const s0Label = formatearEtiquetaUbicacion(s0.nombre || '', s0.direccion || '');
            selectSede.innerHTML = `<option value="${escapeHtmlEnvios(String(s0Id))}" data-direccion="${escapeHtmlEnvios(s0.direccion || '')}" data-nombre="${escapeHtmlEnvios(s0.nombre || '')}" selected>${escapeHtmlEnvios(s0Label)}</option>`;
            selectSede.disabled = false;
            if (!isAbierto) {
                setUbicacionNodoHidden(card, s0Id, false);
                actualizarOpcionesOrigenReferencia();
            }
        } else if (sedes.length > 1) {
            let opts = '<option value="">Seleccione sede...</option>';
            sedes.forEach(s => {
                const sLabel = formatearEtiquetaUbicacion(s.nombre || '', s.direccion || '');
                opts += `<option value="${escapeHtmlEnvios(String(s.id || s.id_ubicacion))}" data-direccion="${escapeHtmlEnvios(s.direccion || '')}" data-nombre="${escapeHtmlEnvios(s.nombre || '')}">${escapeHtmlEnvios(sLabel)}</option>`;
            });
            selectSede.innerHTML = opts;
            selectSede.disabled = false;
            if (!isAbierto) {
                setUbicacionNodoHidden(card, '', false);
                if (infoSpan) infoSpan.innerHTML = `<span class="text-info"><i class="ri-building-2-line me-1"></i>${sedes.length} sedes disponibles</span>`;
            }
        } else {
            // Sin sedes en catálogo local, intentar AJAX
            selectSede.innerHTML = '<option value="">Cargando sedes...</option>';
            selectSede.disabled = true;
            let request = new XMLHttpRequest();
            request.open('GET', base_url + '/Lgs_envios/getSedesByDistribuidor?id=' + idDistribuidor, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState == 4 && request.status == 200) {
                    try {
                        let res = JSON.parse(request.responseText);
                        if (res.status && res.data && res.data.length > 0) {
                            let opts = res.data.length === 1 ? '' : '<option value="">Seleccione sede...</option>';
                            res.data.forEach(s => {
                                const sLabel = formatearEtiquetaUbicacion(s.nombre || '', s.direccion || '');
                                const sel = res.data.length === 1 ? 'selected' : '';
                                opts += `<option value="${escapeHtmlEnvios(String(s.id))}" data-direccion="${escapeHtmlEnvios(s.direccion || '')}" data-nombre="${escapeHtmlEnvios(s.nombre || '')}" ${sel}>${escapeHtmlEnvios(sLabel)}</option>`;
                            });
                            selectSede.innerHTML = opts;
                            selectSede.disabled = false;
                            if (!isAbierto && res.data.length === 1) {
                                setUbicacionNodoHidden(card, res.data[0].id, true);
                                actualizarOpcionesOrigenReferencia();
                            }
                        } else {
                            selectSede.innerHTML = '<option value="">Sin sedes registradas</option>';
                        }
                    } catch(e) {
                        selectSede.innerHTML = '<option value="">Error al cargar sedes</option>';
                    }
                    serializarNodos();
                }
            };
        }
    }

    serializarNodos();
}

/** Maneja el cambio del select de sede */
function onSedeChange(selectSede) {
    const card = selectSede.closest('.nodo-item');
    if (!card) return;
    setUbicacionNodoHidden(card, selectSede.value || '', true);
}

/** Formatea una fecha/hora para inputs de tipo datetime-local (YYYY-MM-DDTHH:mm) */
function formatDateTimeLocal(val) {
    if (!val) return '';
    let s = String(val).trim();
    // Rechazar fechas cero de MySQL
    if (s.startsWith('0000') || s === '' || s === 'null' || s === 'NULL') return '';
    if (s.length === 10) { // YYYY-MM-DD solo
        return s + 'T00:00';
    }
    return s.replace(' ', 'T').substring(0, 16);
}

let _nodoCounter = 0;

/** Obtiene los puntos de partida / carga disponibles en el itinerario */
function getOrigenesDisponibles() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    const origenes = [];
    items.forEach((card, idx) => {
        const esPrimero = (idx === 0);
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        if (esPrimero || isCarga) {
            const selUbi = card.querySelector('.nodo-select-ubicacion');
            const inpLibre = card.querySelector('.nodo-nombre-libre');
            const esAbierto = card.getAttribute('data-fuente-nodo') === 'abierto' || card.getAttribute('data-destino-abierto') === '1';
            let nombre = 'Punto ' + (idx + 1);
            let ubiId = selUbi ? selUbi.value : '';
            if (esAbierto && inpLibre && inpLibre.value.trim()) {
                nombre = 'Punto ' + (idx + 1) + ' (' + inpLibre.value.trim() + ')';
            } else if (selUbi && selUbi.selectedIndex >= 0 && selUbi.value) {
                nombre = 'Punto ' + (idx + 1) + ' (' + selUbi.options[selUbi.selectedIndex].text.split('—')[0].trim() + ')';
            }
            const idNodo = card.getAttribute('data-id-nodo-bd') || String(idx);
            origenes.push({
                index: idx,
                idNodo: idNodo,
                idUbicacion: ubiId,
                nombre: nombre
            });
        }
    });
    return origenes;
}

/** Construye las opciones para el select de origen de salida por destino */
function buildOrigenReferenciaOptions(selId) {
    const origenes = getOrigenesDisponibles();
    if (origenes.length === 0) {
        return '<option value="0">Punto 1 (Salida)</option>';
    }
    let html = '';
    origenes.forEach((orig, i) => {
        const val = orig.idNodo || String(orig.index);
        const isSelected = (selId !== null && selId !== undefined && selId !== '' && (String(val) === String(selId) || String(orig.idUbicacion) === String(selId) || String(orig.index) === String(selId))) || ((selId === null || selId === undefined || selId === '') && i === 0);
        html += `<option value="${val}" ${isSelected ? 'selected' : ''}>${orig.nombre}</option>`;
    });
    return html;
}

/** Actualiza las opciones del selector de origen en todos los nodos de descarga */
function actualizarOpcionesOrigenReferencia() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    const origenes = getOrigenesDisponibles();
    const esDirecto = (document.getElementById('modalidad_calculo') && document.getElementById('modalidad_calculo').value === 'DIRECTO');

    items.forEach((card, idx) => {
        const esPrimero = (idx === 0);
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        const secOrigenRef = card.querySelector('.seccion-origen-referencia');
        const colUbi = card.querySelector('.col-ubi-wrapper');

        if (!esPrimero && !isCarga) {
            if (secOrigenRef) {
                secOrigenRef.style.display = esDirecto ? 'block' : 'none';
                const sel = secOrigenRef.querySelector('.nodo-select-origen-ref');
                if (sel) {
                    const currentVal = sel.value;
                    let opts = '';
                    origenes.forEach((orig, i) => {
                        const val = orig.idNodo || String(orig.index);
                        const isSelected = (currentVal !== null && currentVal !== undefined && currentVal !== '' && (String(val) === String(currentVal) || String(orig.idUbicacion) === String(currentVal) || String(orig.index) === String(currentVal))) || ((currentVal === null || currentVal === undefined || currentVal === '') && i === 0);
                        opts += `<option value="${val}" ${isSelected ? 'selected' : ''}>${orig.nombre}</option>`;
                    });
                    sel.innerHTML = opts;
                }
            }
            if (colUbi) {
                colUbi.className = esDirecto ? 'col-md-4 col-ubi-wrapper' : 'col-md-7 col-ubi-wrapper';
            }
        } else {
            if (secOrigenRef) secOrigenRef.style.display = 'none';
        }
    });
}

/**
 * Detecta si el tipo de traslado corresponde a Madrina (1) o Plataforma (3)
 */
function esMadrinaOPlataforma(idTipoTraslado) {
    const sel = document.getElementById('id_tipo_traslado');
    const id = (idTipoTraslado !== undefined && idTipoTraslado !== null && idTipoTraslado !== '')
        ? String(idTipoTraslado)
        : (sel ? String(sel.value) : '');

    if (id === '1' || id === '3') return true;
    if (id === '2') return false; // Chofer (Rodando)
    if (sel && id) {
        const opt = sel.querySelector(`option[value="${id}"]`);
        if (opt) {
            const text = (opt.text || '').toLowerCase();
            if (text.includes('madrina') || text.includes('plataforma')) return true;
        }
    }
    return false;
}

/**
 * Detecta si el proveedor seleccionado corresponde a TEROMO
 */
function esProveedorTeromo(idProveedor) {
    const sel = document.getElementById('id_proveedor');
    const id = (idProveedor !== undefined && idProveedor !== null && idProveedor !== '')
        ? String(idProveedor)
        : (sel ? String(sel.value) : '');

    if (!id) return false;
    if (id === '1000000202') return true;

    if (sel) {
        const opt = sel.querySelector(`option[value="${id}"]`) || (sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null);
        if (opt) {
            const dataNom = (opt.getAttribute('data-nombre') || '').toUpperCase();
            const text = (opt.text || '').toUpperCase();
            if (dataNom.includes('TEROMO') || text.includes('TEROMO')) return true;
        }
    }
    return false;
}

/**
 * Aplica la modalidad de cálculo por defecto según la regla de negocio:
 * - Solo aplica para Plataformas (3) y Madrinas (1):
 *     * Teromo => ESCALONADO por default.
 *     * Todos los demás trasladistas => DIRECTO (Directo con escalas por origen) por default.
 * - Para Chofer (Rodando) y otros tipos => ESCALONADO por default.
 * - Permite al usuario cambiarlo libremente en cualquier momento.
 * @param {boolean} forzar Si es true, sobrescribe el valor actual (ej. al cambiar de proveedor)
 */
function aplicarDefaultModalidadCalculo(forzar = false) {
    const selTipo = document.getElementById('id_tipo_traslado');
    const selProv = document.getElementById('id_proveedor');
    const selMod  = document.getElementById('modalidad_calculo');
    if (!selMod) return;

    const idTipo = selTipo ? selTipo.value : '';
    let modalidadSugerida = 'ESCALONADO';

    if (esMadrinaOPlataforma(idTipo)) {
        let modConfigurada = '';
        if (selProv && selProv.value) {
            const opt = selProv.querySelector(`option[value="${selProv.value}"]`) || (selProv.selectedIndex >= 0 ? selProv.options[selProv.selectedIndex] : null);
            if (opt && opt.getAttribute('data-modalidad')) {
                modConfigurada = opt.getAttribute('data-modalidad').toUpperCase();
            }
        }
        if (modConfigurada === 'ESCALONADO' || modConfigurada === 'DIRECTO') {
            modalidadSugerida = modConfigurada;
        } else {
            const idProv = selProv ? selProv.value : '';
            const isTeromo = idProv ? esProveedorTeromo(idProv) : false;
            modalidadSugerida = isTeromo ? 'ESCALONADO' : 'DIRECTO';
        }
    } else {
        // En la operación de Logística, los traslados y repartos miden la distancia desde el origen de recolección al destino final
        modalidadSugerida = 'DIRECTO';
    }

    if (forzar || !window.g_modalidadModificadaManualmente) {
        if (selMod.value !== modalidadSugerida) {
            selMod.value = modalidadSugerida;
            handleModalidadCalculoChange(false);
        }
    }
}

/** Maneja el cambio de proveedor trasladista */
function handleProveedorChange() {
    window.g_modalidadModificadaManualmente = false;
    aplicarDefaultModalidadCalculo(true);
    verificarYCalcularRuta();
}

/** Sincroniza visualmente las tarjetas interactivas de Modalidad */
function syncModalidadCardsUI(val) {
    const cardDirecto = document.getElementById('card-modalidad-directo');
    const cardEscalonado = document.getElementById('card-modalidad-escalonado');
    if (!cardDirecto || !cardEscalonado) return;

    if (val === 'DIRECTO') {
        cardDirecto.classList.add('active');
        cardEscalonado.classList.remove('active');
    } else {
        cardEscalonado.classList.add('active');
        cardDirecto.classList.remove('active');
    }
}

/** Maneja la selección desde las tarjetas interactivas de Modalidad */
function seleccionarModalidadCard(val) {
    const sel = document.getElementById('modalidad_calculo');
    if (sel) {
        sel.value = val;
        handleModalidadCalculoChange(true);
    }
    syncModalidadCardsUI(val);
}

/** Maneja el cambio de modalidad de cálculo */
function handleModalidadCalculoChange(esCambioManual = false) {
    if (esCambioManual) {
        window.g_modalidadModificadaManualmente = true;
    }
    const sel = document.getElementById('modalidad_calculo');
    const modalidad = sel ? sel.value : 'ESCALONADO';
    
    // Sincronizar tarjetas de UI
    syncModalidadCardsUI(modalidad);

    const helpTxt = document.getElementById('helpModalidadCalculo');
    if (helpTxt) {
        if (modalidad === 'DIRECTO') {
            helpTxt.innerHTML = '<span class="text-primary fw-medium"><i class="ri-direction-line me-1"></i>Directo con escalas por origen de referencia.</span>';
        } else {
            helpTxt.innerHTML = '<span class="text-muted"><i class="ri-route-line me-1"></i>Prorrateo escalonado tradicional por tramo.</span>';
        }
    }
    actualizarSecuenciaNodos();
    verificarYCalcularRuta();
}

/** Escapa caracteres HTML para prevenir inyecciones XSS en nombres y atributos */
function escapeHtmlEnvios(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/** Agrega un nuevo nodo / punto al recorrido de la ruta */
function agregarNodoRuta(data, isCarga = false) {
    const idTipoTraslado = document.getElementById('id_tipo_traslado') ? document.getElementById('id_tipo_traslado').value : '';
    if (idTipoTraslado == '2' && !data) {
        const contCheck = document.getElementById('contenedor-nodos-ruta');
        if (contCheck && contCheck.querySelectorAll('.nodo-item').length >= 2) {
            Swal.fire("Atención", "El tipo de traslado 'Chofer (Rodando)' solo permite un Origen y un Destino.", "warning");
            return;
        }
    }

    _nodoCounter++;
    const cont = document.getElementById('contenedor-nodos-ruta');
    if (!cont) return;

    const msg = document.getElementById('msg-sin-nodos');
    if (msg) msg.style.display = 'none';

    const index = cont.querySelectorAll('.nodo-item').length;
    const esOrigen = (index === 0);

    const div = document.createElement('div');
    div.className = 'card border shadow-sm p-3 mb-0 nodo-item';
    div.setAttribute('data-nodo-id', _nodoCounter);
    if (data && data.id_nodo) {
        div.setAttribute('data-id-nodo-bd', data.id_nodo);
    }

    const selUbicacionId = data ? (data.id_ubicacion || data.id_destino_cat || '') : '';
    const selOrigenRefId = data ? (data.id_origen_referencia || '') : '';
    const nombreLibre = data ? (data.destino_nombre_libre || '') : '';
    const kmTramo = data ? parseFloat(data.km_tramo || data.km_tramo_anterior || 0) : 0;
    const obs = data ? (data.observaciones || '') : '';
    const fechaEstimadaVal = data && data.fecha_estimada ? formatDateTimeLocal(data.fecha_estimada) : '';

    let isCargaVal = isCarga;
    if (data) {
        if (data.tipo_nodo) {
            isCargaVal = (data.tipo_nodo === 'carga');
        } else if (data.id_ubicacion || data.id_destino_cat) {
            const ubicaciones = getCatalogoUbicaciones();
            const ubi = ubicaciones.find(u => String(u.id || u.id_ubicacion) === String(selUbicacionId));
            if (ubi && parseInt(ubi.id_tipo_destino) === 5) {
                isCargaVal = true;
            }
        }
    }
    div.setAttribute('data-tipo-nodo', isCargaVal ? 'carga' : 'entrega');
    div.style.borderLeft = esOrigen ? '4px solid #0ab39c' : (isCargaVal ? '4px solid #0d6efd' : '4px solid #fd7e14');

    const esDirecto = (document.getElementById('modalidad_calculo') && document.getElementById('modalidad_calculo').value === 'DIRECTO');
    const esAbiertoInicial = (!selUbicacionId && Boolean(nombreLibre));
    if (esAbiertoInicial) {
        div.setAttribute('data-destino-abierto', '1');
    }

    // Determinar si la ubicación seleccionada es un distribuidor/cliente o una planta/almacén PT (WMS)
    let selDistribuidorId = '';
    if (data && data.id_distribuidor) {
        selDistribuidorId = data.id_distribuidor;
    } else if (data && data.id_cliente) {
        const distObjCli = getCatalogoDistribuidores().find(d => String(d.id_cliente || '') === String(data.id_cliente));
        if (distObjCli) {
            selDistribuidorId = distObjCli.id_distribuidor;
        }
    } else if (selUbicacionId) {
        const ubicaciones = getCatalogoUbicaciones();
        const ubiSel = ubicaciones.find(u => String(u.id || u.id_ubicacion) === String(selUbicacionId));
        if (ubiSel && ubiSel.id_distribuidor) {
            selDistribuidorId = ubiSel.id_distribuidor;
        } else if (ubiSel && ubiSel.id_cliente) {
            const distObjCli = getCatalogoDistribuidores().find(d => String(d.id_cliente || '') === String(ubiSel.id_cliente));
            if (distObjCli) selDistribuidorId = distObjCli.id_distribuidor;
        }
    }

    let selPlantaWmsId = '';
    let matchSubalmacenWms = false;
    if (selUbicacionId) {
        const plantasWms = getCatalogoPlantasWms();
        for (const pWms of plantasWms) {
            const subMatch = (pWms.subalmacenes || []).find(s => String(s.id_ubicacion) === String(selUbicacionId)
                || (data && data.id_almacen_wms && String(s.id_almacen_wms) === String(data.id_almacen_wms)));
            if (subMatch) {
                selPlantaWmsId = String(pWms.id_planta);
                matchSubalmacenWms = true;
                break;
            }
        }
    }

    let fuenteInicial = (esOrigen || isCargaVal) ? 'wms' : 'cli';
    if (esAbiertoInicial) {
        fuenteInicial = 'abierto';
    } else if (matchSubalmacenWms) {
        fuenteInicial = 'wms';
    } else if (selDistribuidorId) {
        fuenteInicial = 'cli';
    }

    const ubiHistorica = (selUbicacionId && !esAbiertoInicial && !matchSubalmacenWms && !selDistribuidorId)
        ? ((data && data.nombre) || ((getCatalogoUbicaciones().find(u => String(u.id || u.id_ubicacion) === String(selUbicacionId)) || {}).nombre) || ('Ubicación #' + selUbicacionId))
        : '';

    div.setAttribute('data-fuente-nodo', fuenteInicial);

    const placeholderAbierto = esOrigen
        ? 'Ej. Expo Transporte Guadalajara / Patio Temporal'
        : (isCargaVal
            ? 'Ej. Expo Transporte Guadalajara, Stand D-12 / Patio Temporal'
            : 'Ej. Bodega Particular El Roble, Sucursal Tlalnepantla, Carr. México-Puebla Km 45...');
    const lblAbierto = esOrigen
        ? 'Nombre / Dirección de Salida Libre'
        : (isCargaVal ? 'Nombre / Dirección de Recolección Libre' : 'Destino Real de Entrega / Dónde va físicamente');
    const hintAbierto = (!esOrigen && !isCargaVal)
        ? '<i class="ri-truck-line me-1 text-success"></i>Facturado al distribuidor de la izquierda (opcional); destino físico real donde se entrega la unidad.'
        : '<i class="ri-information-line me-1 text-primary"></i>Punto fuera de catálogo. Se calculará usando KM manuales sin grabarlo en la memoria general.';
    const tituloCabeceraUbi = esOrigen
        ? '<i class="ri-map-pin-user-line text-success me-1"></i>Origen / Carga'
        : (isCargaVal
            ? '<i class="ri-map-pin-add-line text-primary me-1"></i>Punto de Carga'
            : '<i class="ri-map-pin-2-line text-warning me-1"></i>Punto de Descarga');

    const clsBtnWms = fuenteInicial === 'wms' ? 'btn-primary text-white active' : 'btn-outline-secondary';
    const clsBtnCli = fuenteInicial === 'cli' ? 'btn-info text-white active' : 'btn-outline-secondary';
    const clsBtnAbr = fuenteInicial === 'abierto' ? 'btn-warning text-dark active' : 'btn-outline-secondary';

    const showWms = fuenteInicial === 'wms';
    const showCliDist = fuenteInicial === 'cli' || (fuenteInicial === 'abierto' && !esOrigen && !isCargaVal);
    const showCliSede = fuenteInicial === 'cli';
    const showAbr = fuenteInicial === 'abierto';
    const colAbr = (!esOrigen && !isCargaVal) ? 'col-md-7' : 'col-12';
    const lblDist = (!esOrigen && !isCargaVal)
        ? '<i class="ri-file-text-line text-primary me-1"></i>Cliente / Distribuidor <span class="badge bg-soft-primary text-primary fs-10 fw-semibold ms-1">CLI</span>'
        : '<i class="ri-building-2-line text-primary me-1"></i>Cliente / Distribuidor <span class="badge bg-soft-primary text-primary fs-10 fw-semibold ms-1">CLI</span>';
    const lblSede = (!esOrigen && !isCargaVal)
        ? '<i class="ri-store-2-line text-warning me-1"></i>Sede / Dirección de Entrega <span class="text-danger">*</span>'
        : '<i class="ri-store-2-line text-primary me-1"></i>Sede / Sucursal de Recolección <span class="text-danger">*</span>';

    const htmlUbicacion = `
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <span class="fs-11 text-muted fw-bold">${tituloCabeceraUbi}</span>
            <div class="btn-group btn-group-sm nodo-fuente-tabs" role="group" aria-label="Fuente de ubicación">
                <button type="button" class="btn py-0 px-2 fs-11 ${clsBtnWms}" data-fuente="wms" onclick="setFuenteNodo(this, 'wms')" title="Plantas MRP y Almacenes de Producto Terminado (pt = 1)">
                    <i class="ri-building-4-line me-1"></i>Planta / Almacén PT
                </button>
                <button type="button" class="btn py-0 px-2 fs-11 ${clsBtnCli}" data-fuente="cli" onclick="setFuenteNodo(this, 'cli')" title="Clientes / Distribuidores y sus Sedes">
                    <i class="ri-store-2-line me-1"></i>Cliente / Distribuidor
                </button>
                <button type="button" class="btn py-0 px-2 fs-11 ${clsBtnAbr}" data-fuente="abierto" onclick="setFuenteNodo(this, 'abierto')" title="Ubicación fuera de catálogo con KM manuales">
                    <i class="ri-edit-line me-1"></i>Abierto
                </button>
            </div>
            <input type="checkbox" class="d-none nodo-check-abierto" id="check_abierto_${_nodoCounter}" ${esAbiertoInicial ? 'checked' : ''} onchange="toggleNodoDestinoAbierto(this)">
        </div>

        <!-- Bloque WMS: Planta -> Subalmacén PT -->
        <div class="row g-2 mb-1 nodo-bloque-wms" style="${showWms ? '' : 'display:none;'}">
            <div class="col-md-5">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">
                    <i class="ri-building-4-line text-success me-1"></i>Planta (MRP / WMS) <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-sm nodo-select-planta" onchange="onPlantaWmsChange(this)">
                    ${buildPlantasWmsOptions(selPlantaWmsId)}
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">
                    <i class="ri-archive-drawer-line text-success me-1"></i>Subalmacén PT (<code>pt = 1</code>) <span class="text-danger">*</span>
                </label>
                <select class="form-select form-select-sm nodo-select-subalmacen" onchange="onSubalmacenWmsChange(this)" ${selPlantaWmsId ? '' : 'disabled'}>
                    <option value="">Primero seleccione planta...</option>
                </select>
            </div>
        </div>

        <!-- Bloque CLI / Abierto compartido para Cliente -> Sede -->
        <div class="row g-2 mb-1 nodo-bloque-cli-row" style="${(showCliDist || showCliSede || showAbr) ? '' : 'display:none;'}">
            <div class="col-md-5 nodo-bloque-cli-dist" style="${showCliDist ? '' : 'display:none;'}">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">${lblDist}</label>
                <select class="form-select form-select-sm nodo-select-distribuidor" onchange="onDistribuidorChange(this)">
                    ${buildDistribuidorOptions(selDistribuidorId)}
                </select>
            </div>
            <div class="col-md-7 nodo-bloque-cli-sede" style="${showCliSede ? '' : 'display:none;'}">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">${lblSede}</label>
                <select class="form-select form-select-sm nodo-select-sede" onchange="onSedeChange(this)" ${selDistribuidorId ? '' : 'disabled'}>
                    <option value="">Primero seleccione distribuidor</option>
                </select>
            </div>
            <div class="${colAbr} nodo-bloque-abierto" style="${showAbr ? '' : 'display:none;'}">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">
                    <i class="ri-map-pin-user-line text-danger me-1"></i>${lblAbierto} <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control form-control-sm nodo-nombre-libre"
                    value="${escapeHtmlEnvios(nombreLibre)}"
                    placeholder="${placeholderAbierto}"
                    oninput="alCambiarNombreLibreNodo(this)">
                <small class="text-muted fs-10 d-block mt-1">${hintAbierto}</small>
            </div>
        </div>

        <!-- Select oculto de compatibilidad para id_ubicacion y envíos históricos -->
        <select class="form-select form-select-sm nodo-select-ubicacion d-none" onchange="alCambiarUbicacionNodo(this)">
            ${buildUbicacionOptions(selUbicacionId)}
        </select>
        ${ubiHistorica ? `<div class="alert alert-light border py-1 px-2 mt-1 mb-0 fs-10 text-muted nodo-legacy-hint"><i class="ri-history-line me-1 text-info"></i>Ubicación histórica conservada: <strong>${escapeHtmlEnvios(ubiHistorica)}</strong> (puede elegir Planta/Almacén PT o Cliente para actualizarla).</div>` : ''}`;

    div.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge ${esOrigen ? 'bg-success' : 'text-white'} badge-nodo-tipo px-2 py-1 fs-11" style="${esOrigen ? '' : (isCargaVal ? 'background-color: #0d6efd !important;' : 'background-color: #fd7e14 !important;')}">
                    <i class="${esOrigen ? 'ri-map-pin-user-line' : (isCargaVal ? 'ri-map-pin-add-line' : 'ri-map-pin-line')} me-1"></i>
                    <span class="nodo-tipo-txt">${esOrigen ? 'PUNTO 1 (PUNTO DE PARTIDA / CARGA)' : (isCargaVal ? 'PUNTO ' + (index + 1) + ' (CARGA ADICIONAL)' : 'PUNTO ' + (index + 1) + ' (BAJADA / DESCARGA)')}</span>
                </span>
                <span class="badge nodo-servicio-badge d-none fs-10 px-2 py-1"></span>
                <span class="text-muted fs-11 nodo-desc-txt">
                    ${esOrigen ? 'Lugar de salida de la madrina o chofer' : (isCargaVal ? 'Punto de recolección o carga de unidades' : 'Punto de entrega o descarga de unidades')}
                </span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-light border btn-reordenar-up" onclick="moverNodoRuta(this, -1)" title="Mover arriba">
                    <i class="ri-arrow-up-s-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light border btn-reordenar-down" onclick="moverNodoRuta(this, 1)" title="Mover abajo">
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <button type="button" class="btn btn-sm btn-soft-danger ms-1" onclick="eliminarNodoRuta(this)" title="Quitar este punto">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
        <div class="row g-2 align-items-center">
            <div class="${esOrigen ? 'col-md-9' : (esDirecto && !isCargaVal ? 'col-md-4' : 'col-md-7')} col-ubi-wrapper">
                ${htmlUbicacion}
                <small class="text-muted fs-10 d-block mt-1 nodo-direccion-preview"></small>
            </div>
            <!-- SECCIÓN ORIGEN DE SALIDA (MODALIDAD DIRECTA) -->
            <div class="col-md-3 seccion-origen-referencia" style="display: ${(!esOrigen && !isCargaVal && esDirecto) ? 'block' : 'none'};">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">
                    <i class="ri-map-pin-user-line text-success me-1"></i>Punto de Salida
                </label>
                <select class="form-select form-select-sm nodo-select-origen-ref" onchange="serializarNodos(); verificarYCalcularRuta();">
                    ${buildOrigenReferenciaOptions(selOrigenRefId)}
                </select>
                <small class="text-muted fs-10 d-block mt-1">Origen para cálculo directo</small>
            </div>
            <div class="col-md-3 seccion-fecha-estimada">
                <label class="form-label fs-11 text-muted mb-1 fw-bold nodo-lbl-fecha-estimada">
                    <i class="ri-calendar-event-line me-1" style="color: ${esOrigen ? '#0ab39c' : (isCargaVal ? '#0d6efd' : '#fd7e14')};"></i>${esOrigen ? 'Fecha/Hora Salida <span class="text-danger">*</span>' : (isCargaVal ? 'Est. Recolección' : 'Est. Descarga')}
                </label>
                <input type="datetime-local" class="form-control form-control-sm nodo-fecha-estimada" 
                    value="${fechaEstimadaVal}" 
                    oninput="serializarNodos()">
            </div>
            <div class="col-md-2 seccion-km-tramo" style="${esOrigen ? 'display: none;' : ''}">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">
                    <i class="ri-route-line text-primary me-1"></i>Distancia
                </label>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm nodo-km-tramo" min="0" step="0.1"
                        value="${kmTramo > 0 ? kmTramo : ''}" 
                        placeholder="${esAbiertoInicial ? 'KM manual' : '0.0'}"
                        oninput="serializarNodos(); recalcularKmTotalVisual();">
                    <span class="input-group-text bg-light text-muted fs-11 px-1">km</span>
                </div>
                <small class="text-muted fs-10 d-block mt-1 nodo-memoria-badge">
                    ${esAbiertoInicial ? '<span class="text-warning"><i class="ri-pencil-line me-1"></i>Manual (Solo este envío)</span>' : '<i class="ri-history-line me-1"></i>Memoria'}
                </small>
            </div>
        </div>`;

    if (!data && isCargaVal && !esOrigen) {
        const nodosActuales = Array.from(cont.querySelectorAll('.nodo-item'));
        let insertado = false;
        for (let i = nodosActuales.length - 1; i >= 0; i--) {
            const tipo = nodosActuales[i].getAttribute('data-tipo-nodo');
            if (i === 0 || tipo === 'carga') {
                nodosActuales[i].insertAdjacentElement('afterend', div);
                insertado = true;
                break;
            }
        }
        if (!insertado) {
            cont.appendChild(div);
        }
    } else {
        cont.appendChild(div);
    }

    if (fuenteInicial === 'wms' && selPlantaWmsId) {
        const selPlanta = div.querySelector('.nodo-select-planta');
        if (selPlanta) {
            selPlanta.value = selPlantaWmsId;
            poblarSubalmacenesWms(div, selPlantaWmsId, selUbicacionId, false);
        }
    } else if (selDistribuidorId && (fuenteInicial === 'cli' || fuenteInicial === 'abierto')) {
        const selDist = div.querySelector('.nodo-select-distribuidor');
        if (selDist) {
            selDist.value = String(selDistribuidorId);
            onDistribuidorChange(selDist);
            const selSede = div.querySelector('.nodo-select-sede');
            if (selSede && selUbicacionId && fuenteInicial === 'cli') {
                selSede.value = selUbicacionId;
                onSedeChange(selSede);
            }
        }
    }

    if (selUbicacionId && !esAbiertoInicial) {
        setUbicacionNodoHidden(div, selUbicacionId, false);
    }

    if (!data) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            icon: 'success',
            title: isCargaVal ? 'Punto de Carga añadido' : 'Punto de Descarga añadido'
        });
    }

    actualizarSecuenciaNodos();
}

/** Elimina un nodo del recorrido */
function eliminarNodoRuta(btn) {
    const item = btn.closest('.nodo-item');
    if (item) item.remove();
    actualizarSecuenciaNodos();
    const cont = document.getElementById('contenedor-nodos-ruta');
    if (cont && cont.querySelectorAll('.nodo-item').length === 0) {
        const msg = document.getElementById('msg-sin-nodos');
        if (msg) msg.style.display = '';
        const badge = document.getElementById('badge-distancia-total-container');
        if (badge) badge.style.setProperty('display', 'none', 'important');
    }
}

/** Reordena un nodo subiéndolo o bajándolo */
function moverNodoRuta(btn, dir) {
    const item = btn.closest('.nodo-item');
    if (!item) return;
    const parent = item.parentNode;
    if (dir === -1 && item.previousElementSibling) {
        parent.insertBefore(item, item.previousElementSibling);
    } else if (dir === 1 && item.nextElementSibling) {
        parent.insertBefore(item.nextElementSibling, item);
    }
    actualizarSecuenciaNodos();
    verificarYCalcularRuta();
}

/** Al cambiar la ubicación en el select de un nodo */
function alCambiarUbicacionNodo(selObj) {
    const card = selObj.closest('.nodo-item');
    if (!card) return;
    const optSelected = selObj.selectedIndex >= 0 ? selObj.options[selObj.selectedIndex] : null;
    const dir = optSelected ? optSelected.getAttribute('data-direccion') : '';
    const infoSpan = card.querySelector('.nodo-direccion-preview');
    if (infoSpan) {
        infoSpan.innerHTML = dir ? `<i class="ri-map-pin-line text-danger me-1"></i>${dir}` : '';
    }

    // Validar que no sea idéntico al punto anterior o siguiente
    const items = Array.from(document.querySelectorAll('#contenedor-nodos-ruta .nodo-item'));
    const currIdx = items.indexOf(card);
    if (currIdx > 0 && selObj.value) {
        const prevSel = items[currIdx - 1].querySelector('.nodo-select-ubicacion');
        if (prevSel && prevSel.value === selObj.value) {
            const nom = optSelected ? optSelected.text.split('—')[0].trim() : 'la misma ubicación';
            Swal.fire({
                title: "Ubicación duplicada",
                text: `El Punto #${currIdx + 1} no puede ser la misma ubicación que el Punto #${currIdx} (${nom}). Por favor seleccione un destino diferente.`,
                icon: "warning"
            });
            selObj.value = "";
            const subSel = card.querySelector('.nodo-select-subalmacen');
            const sedeSel = card.querySelector('.nodo-select-sede');
            if (subSel && card.getAttribute('data-fuente-nodo') === 'wms') subSel.value = "";
            if (sedeSel && card.getAttribute('data-fuente-nodo') === 'cli') sedeSel.value = "";
            if (infoSpan) infoSpan.innerHTML = "";
            serializarNodos();
            return;
        }
    }
    if (currIdx < items.length - 1 && selObj.value) {
        const nextSel = items[currIdx + 1].querySelector('.nodo-select-ubicacion');
        if (nextSel && nextSel.value === selObj.value) {
            const nom = optSelected ? optSelected.text.split('—')[0].trim() : 'la misma ubicación';
            Swal.fire({
                title: "Ubicación duplicada",
                text: `El Punto #${currIdx + 1} no puede ser la misma ubicación que el Punto #${currIdx + 2} (${nom}). Por favor seleccione un destino diferente.`,
                icon: "warning"
            });
            selObj.value = "";
            const subSel = card.querySelector('.nodo-select-subalmacen');
            const sedeSel = card.querySelector('.nodo-select-sede');
            if (subSel && card.getAttribute('data-fuente-nodo') === 'wms') subSel.value = "";
            if (sedeSel && card.getAttribute('data-fuente-nodo') === 'cli') sedeSel.value = "";
            if (infoSpan) infoSpan.innerHTML = "";
            serializarNodos();
            return;
        }
    }

    actualizarOpcionesOrigenReferencia();
    serializarNodos();
    verificarYCalcularRuta();
}

/** Cambia la fuente de ubicación de cualquier nodo entre WMS (Planta->Subalmacén PT), CLI (Cliente->Sede) o Abierto */
function setFuenteNodo(el, fuente) {
    const card = el.closest('.nodo-item');
    if (!card) return;
    const prevFuente = card.getAttribute('data-fuente-nodo') || '';
    if (prevFuente === fuente) return;

    const cont = document.getElementById('contenedor-nodos-ruta');
    const items = cont ? Array.from(cont.querySelectorAll('.nodo-item')) : [];
    const idx = items.indexOf(card);
    const esOrigen = (idx === 0);
    const isCargaVal = card.getAttribute('data-tipo-nodo') === 'carga';

    card.setAttribute('data-fuente-nodo', fuente);

    // Actualizar apariencia de pestañas
    card.querySelectorAll('.nodo-fuente-tabs button').forEach(btn => {
        const bf = btn.getAttribute('data-fuente');
        btn.classList.remove('btn-primary', 'btn-info', 'btn-warning', 'btn-outline-secondary', 'text-white', 'text-dark', 'active');
        if (bf === fuente) {
            if (bf === 'wms') btn.classList.add('btn-primary', 'text-white', 'active');
            else if (bf === 'cli') btn.classList.add('btn-info', 'text-white', 'active');
            else btn.classList.add('btn-warning', 'text-dark', 'active');
        } else {
            btn.classList.add('btn-outline-secondary');
        }
    });

    const bloqueWms    = card.querySelector('.nodo-bloque-wms');
    const rowCli       = card.querySelector('.nodo-bloque-cli-row');
    const colCliDist   = card.querySelector('.nodo-bloque-cli-dist');
    const colCliSede   = card.querySelector('.nodo-bloque-cli-sede');
    const colAbr       = card.querySelector('.nodo-bloque-abierto');
    const chkAbierto   = card.querySelector('.nodo-check-abierto');
    const inpLibre     = card.querySelector('.nodo-nombre-libre');
    const selPlanta    = card.querySelector('.nodo-select-planta');
    const selSub       = card.querySelector('.nodo-select-subalmacen');
    const selDist      = card.querySelector('.nodo-select-distribuidor');
    const selSede      = card.querySelector('.nodo-select-sede');
    const inpKm        = card.querySelector('.nodo-km-tramo');
    const badgeKm      = card.querySelector('.nodo-memoria-badge');

    const showWms     = (fuente === 'wms');
    const showCliDist = (fuente === 'cli') || (fuente === 'abierto' && !esOrigen && !isCargaVal);
    const showCliSede = (fuente === 'cli');
    const showAbr     = (fuente === 'abierto');

    if (bloqueWms) bloqueWms.style.display = showWms ? '' : 'none';
    if (rowCli)    rowCli.style.display = (showCliDist || showCliSede || showAbr) ? '' : 'none';
    if (colCliDist) colCliDist.style.display = showCliDist ? '' : 'none';
    if (colCliSede) colCliSede.style.display = showCliSede ? '' : 'none';
    if (colAbr) {
        colAbr.className = `${(!esOrigen && !isCargaVal) ? 'col-md-7' : 'col-12'} nodo-bloque-abierto`;
        colAbr.style.display = showAbr ? '' : 'none';
    }

    if (fuente === 'abierto') {
        card.setAttribute('data-destino-abierto', '1');
        if (chkAbierto) chkAbierto.checked = true;
        setUbicacionNodoHidden(card, '', false);
        if (inpKm) inpKm.placeholder = 'KM manual';
        if (badgeKm) badgeKm.innerHTML = '<span class="text-warning"><i class="ri-pencil-line me-1"></i>Manual (Solo este envío)</span>';
        if (inpLibre) inpLibre.focus();
    } else {
        card.removeAttribute('data-destino-abierto');
        if (chkAbierto) chkAbierto.checked = false;
        if (inpLibre) inpLibre.value = '';
        if (prevFuente === 'abierto' && inpKm) {
            inpKm.placeholder = '0.0';
            inpKm.value = '';
        }
        if (badgeKm) badgeKm.innerHTML = '<i class="ri-history-line me-1"></i>Memoria';

        if (fuente === 'wms') {
            if (selDist) selDist.value = '';
            if (selSede) {
                selSede.innerHTML = '<option value="">Primero seleccione distribuidor</option>';
                selSede.disabled = true;
            }
            if (selPlanta && selPlanta.value) {
                poblarSubalmacenesWms(card, selPlanta.value, '', false);
            } else {
                if (selSub) {
                    selSub.innerHTML = '<option value="">Primero seleccione planta...</option>';
                    selSub.disabled = true;
                }
                setUbicacionNodoHidden(card, '', false);
            }
        } else {
            if (selPlanta) selPlanta.value = '';
            if (selSub) {
                selSub.innerHTML = '<option value="">Primero seleccione planta...</option>';
                selSub.disabled = true;
            }
            if (selDist && selDist.value) {
                onDistribuidorChange(selDist);
            } else {
                setUbicacionNodoHidden(card, '', false);
            }
        }
    }

    actualizarOpcionesOrigenReferencia();
    serializarNodos();
    recalcularKmTotalVisual();
    verificarYCalcularRuta();
}

/** Alterna entre selección de catálogo y captura de destino abierto (fuera de catálogo) */
function toggleNodoDestinoAbierto(chk) {
    const card = chk.closest('.nodo-item');
    if (!card) return;
    if (chk.checked) {
        setFuenteNodo(chk, 'abierto');
    } else {
        const cont = document.getElementById('contenedor-nodos-ruta');
        const items = cont ? Array.from(cont.querySelectorAll('.nodo-item')) : [];
        const idx = items.indexOf(card);
        const esCarga = idx === 0 || card.getAttribute('data-tipo-nodo') === 'carga';
        setFuenteNodo(chk, esCarga ? 'wms' : 'cli');
    }
}

/** Evento al modificar el nombre libre del destino fuera de catálogo */
function alCambiarNombreLibreNodo(inp) {
    serializarNodos();
}

/** Clasifica el tipo de servicio de un tramo según KM y modo seleccionado */
function clasificarServicioPorKmEnvio(km, modoEnvio = 'AUTOMATICO') {
    const modo = String(modoEnvio || 'AUTOMATICO').toUpperCase().trim();
    const validos = ['AUTOMATICO', 'SLC', 'SLL', 'FORANEO'];
    const modoNorm = validos.includes(modo) ? modo : 'AUTOMATICO';
    const kmNum = parseFloat(km) || 0;

    if (kmNum <= 0 && modoNorm === 'AUTOMATICO') {
        return 'PENDIENTE';
    }
    if (modoNorm !== 'AUTOMATICO') {
        return modoNorm;
    }
    if (kmNum <= 40) {
        return 'SLC';
    }
    if (kmNum <= 80) {
        return 'SLL';
    }
    return 'FORANEO';
}

/** Aplica clases y texto descriptivo a un badge de servicio usando solo textContent */
function aplicarEstiloBadgeServicioEnvio(el, codigo, km = 0) {
    if (!el) return;
    const cod = String(codigo || 'PENDIENTE').toUpperCase();
    const kmNum = parseFloat(km) || 0;

    el.className = 'badge fs-10 px-2 py-1';
    if (el.classList.contains('badge-servicio-grande')) {
        el.className = 'badge badge-servicio-grande fs-12 ms-1';
    }

    if (cod === 'SLC') {
        el.classList.add('bg-soft-success', 'text-success', 'border', 'border-success');
        el.textContent = kmNum > 0 ? `SLC · Local Corto (${kmNum.toFixed(1)} km)` : 'SLC · Local Corto (0-40 km)';
    } else if (cod === 'SLL') {
        el.classList.add('bg-soft-warning', 'text-warning', 'border', 'border-warning');
        el.textContent = kmNum > 0 ? `SLL · Local Largo (${kmNum.toFixed(1)} km)` : 'SLL · Local Largo (41-80 km)';
    } else if (cod === 'FORANEO') {
        el.classList.add('bg-soft-primary', 'text-primary', 'border', 'border-primary');
        el.textContent = kmNum > 0 ? `FORÁNEO (${kmNum.toFixed(1)} km)` : 'FORÁNEO (>80 km)';
    } else if (cod === 'MIXTO') {
        el.classList.add('bg-soft-info', 'text-info', 'border', 'border-info');
        el.textContent = 'MIXTO / CAMPECHANEADO';
    } else {
        el.classList.add('d-none');
        el.textContent = '';
    }
}

/** Actualiza en tiempo real la detección automática de rangos por parada y las alertas de regla local */
function actualizarDeteccionAutomaticaRutaUI(resumenBackend = null) {
    const selModo = document.getElementById('tipo_servicio');
    const modoEnvio = selModo ? String(selModo.value || 'AUTOMATICO').toUpperCase() : 'AUTOMATICO';
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');

    const conteoEntregas = { SLC: 0, SLL: 0, FORANEO: 0 };
    const conteoTodos = { SLC: 0, SLL: 0, FORANEO: 0 };
    let hayEntregasConKm = false;

    items.forEach((card, idx) => {
        const badgeNodo = card.querySelector('.nodo-servicio-badge');
        if (idx === 0) {
            if (badgeNodo) {
                badgeNodo.className = 'badge nodo-servicio-badge d-none fs-10 px-2 py-1';
                badgeNodo.textContent = '';
            }
            return;
        }

        const inpKm = card.querySelector('.nodo-km-tramo');
        const km = inpKm ? (parseFloat(inpKm.value) || 0) : 0;
        const servAttr = card.getAttribute('data-tipo-servicio-tramo');
        const codigo = (modoEnvio === 'AUTOMATICO' && servAttr && ['SLC', 'SLL', 'FORANEO'].includes(servAttr) && km > 0)
            ? servAttr
            : clasificarServicioPorKmEnvio(km, modoEnvio);

        if (badgeNodo) {
            if (codigo === 'PENDIENTE') {
                badgeNodo.className = 'badge nodo-servicio-badge d-none fs-10 px-2 py-1';
                badgeNodo.textContent = '';
            } else {
                badgeNodo.className = 'badge nodo-servicio-badge fs-10 px-2 py-1';
                if (codigo === 'SLC') {
                    badgeNodo.classList.add('bg-soft-success', 'text-success', 'border', 'border-success');
                    badgeNodo.textContent = km > 0 ? `SLC · Local Corto (${km.toFixed(1)} km)` : 'SLC · Local Corto (0-40 km)';
                } else if (codigo === 'SLL') {
                    badgeNodo.classList.add('bg-soft-warning', 'text-warning', 'border', 'border-warning');
                    badgeNodo.textContent = km > 0 ? `SLL · Local Largo (${km.toFixed(1)} km)` : 'SLL · Local Largo (41-80 km)';
                } else {
                    badgeNodo.classList.add('bg-soft-primary', 'text-primary', 'border', 'border-primary');
                    badgeNodo.textContent = km > 0 ? `FORÁNEO (${km.toFixed(1)} km)` : 'FORÁNEO (>80 km)';
                }
            }
        }

        if (km > 0 && ['SLC', 'SLL', 'FORANEO'].includes(codigo)) {
            conteoTodos[codigo]++;
            if (card.getAttribute('data-tipo-nodo') !== 'carga') {
                conteoEntregas[codigo]++;
                hayEntregasConKm = true;
            }
        }
    });

    const conteoFinal = (resumenBackend && resumenBackend.conteo && modoEnvio === 'AUTOMATICO')
        ? resumenBackend.conteo
        : (hayEntregasConKm ? conteoEntregas : conteoTodos);

    const unicos = ['SLC', 'SLL', 'FORANEO'].filter(k => (conteoFinal[k] || 0) > 0);
    const esMixto = unicos.length > 1;
    const tieneLocales = ((conteoFinal.SLC || 0) > 0 || (conteoFinal.SLL || 0) > 0);
    const clasifGlobal = esMixto ? 'MIXTO' : (unicos.length === 1 ? unicos[0] : (modoEnvio !== 'AUTOMATICO' ? modoEnvio : 'PENDIENTE'));

    // Actualizar badge en barra de distancia total
    const badgeRuta = document.getElementById('badge-servicio-ruta-val');
    if (badgeRuta) {
        if (clasifGlobal === 'PENDIENTE') {
            badgeRuta.className = 'badge fs-12 d-none';
            badgeRuta.textContent = '';
        } else {
            badgeRuta.className = 'badge badge-servicio-grande fs-12 ms-1';
            aplicarEstiloBadgeServicioEnvio(badgeRuta, clasifGlobal, 0);
            if (esMixto) {
                const resumenParts = unicos.map(k => `${k}: ${conteoFinal[k]}`).join(' | ');
                badgeRuta.textContent = `MIXTO / CAMPECHANEADO (${resumenParts})`;
            }
        }
    }

    // Actualizar resumen bajo el select Tipo de Servicio
    const contForm = document.getElementById('badge-deteccion-servicio-form');
    if (contForm) {
        contForm.replaceChildren();
        if (clasifGlobal !== 'PENDIENTE') {
            const lbl = document.createElement('span');
            lbl.className = 'fs-11 text-muted me-1';
            lbl.textContent = 'Detectado:';
            contForm.appendChild(lbl);

            const pill = document.createElement('span');
            aplicarEstiloBadgeServicioEnvio(pill, clasifGlobal, 0);
            if (esMixto) {
                pill.textContent = `MIXTO (${unicos.map(k => `${conteoFinal[k]} ${k}`).join(' + ')})`;
            }
            contForm.appendChild(pill);
        }
    }

    // Actualizar alerta preventiva de reglas locales
    const alertBox = document.getElementById('alert-regla-local-envio');
    if (alertBox) {
        if (!tieneLocales && !esMixto) {
            alertBox.className = 'col-md-12 mt-2 d-none';
            alertBox.replaceChildren();
        } else {
            const idTipoTraslado = document.getElementById('id_tipo_traslado') ? String(document.getElementById('id_tipo_traslado').value || '') : '';
            const esMadrina = (idTipoTraslado === '1');
            const esChofer = (idTipoTraslado === '2');
            const esPlataforma = (idTipoTraslado === '3');

            const box = document.createElement('div');
            box.className = `alert ${esMadrina ? 'alert-soft-warning border border-warning' : 'alert-soft-info border border-info'} py-2 px-3 mb-0 rounded-3 d-flex align-items-start gap-2 fs-12`;

            const icon = document.createElement('i');
            icon.className = `${esMadrina ? 'ri-alert-line text-warning' : 'ri-information-line text-info'} fs-16 mt-1 flex-shrink-0`;
            box.appendChild(icon);

            const body = document.createElement('div');
            const partes = [];
            if (esMixto) {
                partes.push('Envío campechaneado detectado: cada parada se cotizará con su clasificación correspondiente (SLC 0-40 km / SLL 41-80 km / Foráneo >80 km).');
            }
            if (tieneLocales) {
                if (esMadrina) {
                    partes.push('Regla Madrina Local: las paradas SLC/SLL requieren al menos 3 unidades a bordo en la madrina. Para 1 o 2 unidades en trayecto local utilice Plataforma o Chofer (Rodando).');
                } else if (esPlataforma) {
                    partes.push('Plataforma Local detectada: se aplicará tarifa plana SLC/SLL según cantidad de unidades (1 ud o 2+ uds; Lowboy aplica tarifa local o $/km según convenio).');
                } else if (esChofer) {
                    partes.push('Chofer (Rodando) Local detectado: se aplicará tarifa plana SLC/SLL por segmento de vehículo.');
                } else {
                    partes.push('Ruta con paradas locales (SLC/SLL): seleccione el tipo de traslado para validar la regla aplicable.');
                }
            }
            body.textContent = partes.join(' ');
            box.appendChild(body);

            alertBox.className = 'col-md-12 mt-2';
            alertBox.replaceChildren(box);
        }
    }
}

/** Recalcula y actualiza visualmente la suma de kilómetros en la tarjeta superior */
function recalcularKmTotalVisual() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    let total = 0;
    let tieneAbierto = false;

    items.forEach((card, idx) => {
        if (idx > 0) {
            const inp = card.querySelector('.nodo-km-tramo');
            const km = inp ? (parseFloat(inp.value) || 0) : 0;
            total += km;
            card.removeAttribute('data-tipo-servicio-tramo');
            const chkAbierto = card.querySelector('.nodo-check-abierto');
            if ((chkAbierto && chkAbierto.checked) || card.getAttribute('data-destino-abierto') === '1') {
                tieneAbierto = true;
            }
        }
    });

    const alertTotal = document.getElementById('badge-distancia-total-container');
    const spanVal = document.getElementById('badge-km-total-val');
    if (alertTotal && spanVal) {
        if (total > 0) {
            alertTotal.style.setProperty('display', 'flex', 'important');
            spanVal.innerText = total.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + (tieneAbierto ? ' km (Incluye KM manuales)' : ' km (Memoria Progresiva)');
        }
    }
    actualizarDeteccionAutomaticaRutaUI();
}

/** Actualiza numeración, badges visuales y botones de los nodos */
function actualizarSecuenciaNodos() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    const esDirecto = (document.getElementById('modalidad_calculo') && document.getElementById('modalidad_calculo').value === 'DIRECTO');

    items.forEach((card, idx) => {
        const esPrimero = (idx === 0);
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        const badge = card.querySelector('.badge-nodo-tipo');
        const txtTipo = card.querySelector('.nodo-tipo-txt');
        const descTxt = card.querySelector('.nodo-desc-txt');
        const colUbi   = card.querySelector('.col-ubi-wrapper');
        const secOrigenRef = card.querySelector('.seccion-origen-referencia');
        const secFecha = card.querySelector('.seccion-fecha-estimada');
        const lblFecha = card.querySelector('.nodo-lbl-fecha-estimada');
        const secKm    = card.querySelector('.seccion-km-tramo');

        if (esPrimero) {
            if (colUbi) colUbi.className = 'col-md-9 col-ubi-wrapper';
            if (secOrigenRef) secOrigenRef.style.display = 'none';
            if (secFecha) secFecha.style.display = '';
            if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line text-success me-1"></i>Fecha/Hora Salida <span class="text-danger">*</span>';
            if (secKm) secKm.style.display = 'none';
            card.style.borderLeft = '4px solid #0ab39c';
            if (badge) {
                badge.className = 'badge bg-success badge-nodo-tipo px-2 py-1 fs-11';
                badge.style.removeProperty('background-color');
                badge.innerHTML = '<i class="ri-map-pin-user-line me-1"></i><span class="nodo-tipo-txt">PUNTO 1 (PUNTO DE PARTIDA / CARGA)</span>';
            }
            if (descTxt) descTxt.innerText = 'Lugar de salida de la madrina o chofer';
        } else {
            if (secFecha) secFecha.style.display = '';
            if (secKm) secKm.style.display = '';

            if (isCarga) {
                if (colUbi) colUbi.className = 'col-md-7 col-ubi-wrapper';
                if (secOrigenRef) secOrigenRef.style.display = 'none';
                card.style.borderLeft = '4px solid #0d6efd';
                if (badge) {
                    badge.className = 'badge text-white badge-nodo-tipo px-2 py-1 fs-11';
                    badge.style.backgroundColor = '#0d6efd';
                    badge.innerHTML = `<i class="ri-map-pin-add-line me-1"></i><span class="nodo-tipo-txt">PUNTO ${idx + 1} (CARGA ADICIONAL)</span>`;
                }
                if (descTxt) descTxt.innerText = 'Punto de recolección o carga de unidades';
                if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line me-1" style="color: #0d6efd;"></i>Est. Recolección';
            } else {
                if (colUbi) colUbi.className = esDirecto ? 'col-md-4 col-ubi-wrapper' : 'col-md-7 col-ubi-wrapper';
                if (secOrigenRef) secOrigenRef.style.display = esDirecto ? 'block' : 'none';
                card.style.borderLeft = '4px solid #fd7e14';
                if (badge) {
                    badge.className = 'badge text-white badge-nodo-tipo px-2 py-1 fs-11';
                    badge.style.backgroundColor = '#fd7e14';
                    badge.innerHTML = `<i class="ri-map-pin-line me-1"></i><span class="nodo-tipo-txt">PUNTO ${idx + 1} (BAJADA / DESCARGA)</span>`;
                }
                if (descTxt) descTxt.innerText = 'Punto de entrega o descarga de unidades';
                if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line me-1" style="color: #fd7e14;"></i>Est. Descarga';
            }

            // Mantener apariencia de Punto Abierto si está activo en cualquier parada/nodo con tramo
            const chkAbierto = card.querySelector('.nodo-check-abierto');
            const esAbierto = (chkAbierto && chkAbierto.checked) || card.getAttribute('data-destino-abierto') === '1';
            const inpKm = card.querySelector('.nodo-km-tramo');
            const badgeKm = card.querySelector('.nodo-memoria-badge');
            if (esAbierto) {
                if (inpKm) inpKm.placeholder = 'KM manual';
                if (badgeKm) badgeKm.innerHTML = '<span class="text-warning"><i class="ri-pencil-line me-1"></i>Manual (Solo este envío)</span>';
            }
        }
    });

    actualizarOpcionesOrigenReferencia();
    serializarNodos();
    actualizarDeteccionAutomaticaRutaUI();
}

/** Serializa los nodos en los campos ocultos del formulario */
function serializarNodos() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    const nodos = [];
    const paradasCompat = [];

    items.forEach((card, idx) => {
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        const tipoNodo = (idx === 0) ? 'origen' : (isCarga ? 'carga' : 'entrega');

        const fuenteNodo = card.getAttribute('data-fuente-nodo') || '';
        const chkAbierto = card.querySelector('.nodo-check-abierto');
        const esAbierto = fuenteNodo === 'abierto' || (chkAbierto && chkAbierto.checked) || card.getAttribute('data-destino-abierto') === '1';

        let idUbi = null;
        let nombreLibre = '';
        if (esAbierto) {
            idUbi = null;
            const inpLibre = card.querySelector('.nodo-nombre-libre');
            nombreLibre = inpLibre ? inpLibre.value.trim() : '';
        } else {
            const selUbi = card.querySelector('.nodo-select-ubicacion');
            idUbi = selUbi && selUbi.value ? parseInt(selUbi.value) : null;
            nombreLibre = '';
        }

        const selDist = card.querySelector('.nodo-select-distribuidor');
        const idDist = (fuenteNodo !== 'wms' && selDist && selDist.value) ? parseInt(selDist.value) : null;

        const selOrigenRef = card.querySelector('.nodo-select-origen-ref');
        let idOrigenRef = null;
        if (selOrigenRef && selOrigenRef.value !== '' && selOrigenRef.value !== null) {
            idOrigenRef = selOrigenRef.value;
        }
        const kmVal  = card.querySelector('.nodo-km-tramo') ? (parseFloat(card.querySelector('.nodo-km-tramo').value) || 0) : 0;
        const fechaEst = card.querySelector('.nodo-fecha-estimada') ? card.querySelector('.nodo-fecha-estimada').value : '';

        nodos.push({
            id_nodo: card.getAttribute('data-id-nodo-bd') ? parseInt(card.getAttribute('data-id-nodo-bd')) : null,
            orden: idx,
            tipo_nodo: tipoNodo,
            id_ubicacion: idUbi,
            id_distribuidor: idDist,
            id_origen_referencia: idOrigenRef,
            destino_nombre_libre: nombreLibre,
            destino_abierto: esAbierto,
            km_tramo: (idx === 0) ? 0 : kmVal,
            fecha_estimada: fechaEst
        });

        if (idx > 0) {
            paradasCompat.push({
                orden: idx,
                tipo_nodo: tipoNodo,
                id_destino_cat: idUbi,
                id_distribuidor: idDist,
                id_origen_referencia: idOrigenRef,
                destino_nombre_libre: nombreLibre,
                destino_abierto: esAbierto,
                km_tramo: kmVal,
                fecha_estimada: fechaEst
            });
        }
    });

    const campoNodos = document.getElementById('nodos_json');
    if (campoNodos) campoNodos.value = JSON.stringify(nodos);

    const campoParadas = document.getElementById('paradas_json');
    if (campoParadas) campoParadas.value = JSON.stringify(paradasCompat);

    // Compatibilidad para campos viejos
    const campoOrigen = document.getElementById('id_origen');
    if (campoOrigen && nodos.length > 0) {
        campoOrigen.value = nodos[0].id_ubicacion || '';
    }
    const campoDestino = document.getElementById('id_destino');
    if (campoDestino && nodos.length > 1) {
        campoDestino.value = nodos[nodos.length - 1].id_ubicacion || '';
    }
    const campoDestinoLibre = document.getElementById('destino_nombre_libre');
    if (campoDestinoLibre && nodos.length > 1) {
        campoDestinoLibre.value = nodos[nodos.length - 1].destino_nombre_libre || '';
    }

    // Sincronizar fechas maestras de salida (Punto 1) y llegada (Último Punto)
    const campoFechaEnvio = document.getElementById('fecha_tentativa_envio');
    if (campoFechaEnvio && nodos.length > 0) {
        campoFechaEnvio.value = nodos[0].fecha_estimada || '';
    }
    const campoFechaLlegada = document.getElementById('fecha_tentativa_llegada');
    if (campoFechaLlegada && nodos.length > 1) {
        campoFechaLlegada.value = nodos[nodos.length - 1].fecha_estimada || '';
    }
}

/**
 * Consulta la memoria progresiva de distancias en el backend
 */
function verificarYCalcularRuta(callbackOnSuccess, isManual = false) {
    serializarNodos();
    const nodosRaw = document.getElementById('nodos_json') ? document.getElementById('nodos_json').value : '[]';
    const nodos = JSON.parse(nodosRaw);

    if (!Array.isArray(nodos) || nodos.length < 2) {
        return;
    }

    // Verificar si todos los nodos tienen ubicación seleccionada o destino abierto capturado
    for (let i = 0; i < nodos.length; i++) {
        if (!nodos[i].id_ubicacion && !nodos[i].destino_nombre_libre) {
            Swal.fire("Atención", "Por favor seleccione una ubicación o ingrese el nombre del destino abierto para todos los puntos de la ruta.", "warning");
            return;
        }
    }

    // Si hay tramos consecutivos idénticos, avisar al usuario
    for (let i = 1; i < nodos.length; i++) {
        if (nodos[i].id_ubicacion && nodos[i - 1].id_ubicacion && nodos[i].id_ubicacion === nodos[i - 1].id_ubicacion) {
            Swal.fire("Ubicación duplicada", `El Punto #${i + 1} y el Punto #${i} tienen la misma ubicación. Corrija esto para poder verificar las distancias.`, "warning");
            return;
        }
    }

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/verificarDistanciasRuta';

    const modCalc = (document.getElementById('modalidad_calculo') ? document.getElementById('modalidad_calculo').value : 'DIRECTO') || 'DIRECTO';
    const tipoServ = (document.getElementById('tipo_servicio') ? document.getElementById('tipo_servicio').value : 'AUTOMATICO') || 'AUTOMATICO';
    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({ nodos: nodos, modalidad_calculo: modCalc, tipo_servicio: tipoServ }));

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let res = JSON.parse(request.responseText);
                if (res.status && res.data) {
                    const data = res.data;
                    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');

                    // Actualizar distancias conocidas y clasificación detectada en la UI
                    if (Array.isArray(data.tramos)) {
                        data.tramos.forEach(t => {
                            const nodoCard = items[t.tramo_indice];
                            if (nodoCard) {
                                const inpKm = nodoCard.querySelector('.nodo-km-tramo');
                                if (inpKm && t.km > 0) {
                                    inpKm.value = t.km;
                                }
                                if (t.tipo_servicio) {
                                    nodoCard.setAttribute('data-tipo-servicio-tramo', String(t.tipo_servicio));
                                } else {
                                    nodoCard.removeAttribute('data-tipo-servicio-tramo');
                                }
                            }
                        });
                    }

                    serializarNodos();
                    actualizarDeteccionAutomaticaRutaUI(data.resumen_servicio || null);

                    // Mostrar resumen de KM total
                    const alertTotal = document.getElementById('badge-distancia-total-container');
                    const spanVal = document.getElementById('badge-km-total-val');
                    if (alertTotal && spanVal) {
                        alertTotal.style.setProperty('display', 'flex', 'important');
                        const tieneAbierto = nodos.some(n => !n.id_ubicacion && Boolean(n.destino_nombre_libre));
                        spanVal.innerText = (data.km_total || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + (tieneAbierto ? ' km (Incluye KM manuales)' : ' km (Memoria Progresiva)');
                    }

                    // Si faltan distancias y se requería para guardar o continuar
                    if (!data.completo && data.faltantes && data.faltantes.length > 0) {
                        abrirModalDistanciasFaltantes(data.faltantes, callbackOnSuccess);
                    } else {
                        if (isManual) {
                            Swal.fire({
                                title: "Ruta Verificada",
                                text: "Todas las distancias están calculadas y en memoria.",
                                icon: "success",
                                toast: true,
                                position: "top-end",
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                        if (typeof callbackOnSuccess === 'function') {
                            callbackOnSuccess();
                        }
                    }
                }
            } catch (e) {
                console.error("Error al verificar distancias: ", e);
            }
        }
    };
}

let _callbackDistanciasPendiente = null;

/** Abre el modal para que el usuario capture distancias que no están en memoria */
function abrirModalDistanciasFaltantes(faltantes, callbackOnSuccess) {
    _callbackDistanciasPendiente = callbackOnSuccess;
    const tbody = document.getElementById('tbodyDistanciasFaltantes');
    if (!tbody || !Array.isArray(faltantes)) return;

    // Filtrar tramos inválidos donde origen y destino sean idénticos en catálogo, pero permitir tramos abiertos
    faltantes = faltantes.filter(f => {
        if (f.es_abierto) return true;
        return (f.id_ubicacion_a !== f.id_ubicacion_b) && (f.id_ubicacion_a > 0 || f.id_ubicacion_b > 0);
    });

    if (faltantes.length === 0) {
        if (typeof callbackOnSuccess === 'function') {
            callbackOnSuccess();
        }
        return;
    }

    let html = '';
    faltantes.forEach((f, idx) => {
        const badgeAbierto = f.es_abierto 
            ? '<span class="badge bg-warning-subtle text-warning fs-10 ms-1"><i class="ri-pencil-line me-1"></i>Punto Abierto</span>' 
            : '';
        html += `
        <tr data-id-a="${f.id_ubicacion_a || 0}" data-id-b="${f.id_ubicacion_b || 0}" data-tramo-idx="${f.tramo_indice}" data-es-abierto="${f.es_abierto ? 1 : 0}">
            <td class="fw-bold text-primary">T${f.tramo_indice}</td>
            <td><strong class="text-dark">${escapeHtmlEnvios(f.nombre_a)}</strong></td>
            <td class="text-center text-muted">➔</td>
            <td><strong class="text-dark">${escapeHtmlEnvios(f.nombre_b)}</strong> ${badgeAbierto}</td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm input-modal-km" min="1" step="0.1" placeholder="Ej: 145.5" required>
                    <span class="input-group-text">km</span>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    const modalEl = document.getElementById('modalDistanciasFaltantes');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }
}

/** Guarda las distancias capturadas en el modal en lgs_distancias y actualiza la ruta */
function guardarDistanciasFaltantesModal() {
    const rows = document.querySelectorAll('#tbodyDistanciasFaltantes tr');
    const distanciasParaGuardar = [];
    let valido = true;
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');

    rows.forEach(tr => {
        const idA = parseInt(tr.getAttribute('data-id-a')) || 0;
        const idB = parseInt(tr.getAttribute('data-id-b')) || 0;
        const tramoIdx = parseInt(tr.getAttribute('data-tramo-idx')) || 0;
        const esAbierto = tr.getAttribute('data-es-abierto') === '1' || idA <= 0 || idB <= 0;
        const inp = tr.querySelector('.input-modal-km');
        const km  = inp ? parseFloat(inp.value) : 0;

        if (!km || km <= 0) {
            valido = false;
            if (inp) inp.classList.add('is-invalid');
        } else {
            if (inp) inp.classList.remove('is-invalid');

            // Asignar los kilómetros directamente en la tarjeta del nodo correspondiente
            if (items[tramoIdx]) {
                const cardKm = items[tramoIdx].querySelector('.nodo-km-tramo');
                if (cardKm) {
                    cardKm.value = km;
                }
            }

            // Solo los pares del catálogo se guardan en lgs_distancias (memoria progresiva global)
            if (!esAbierto && idA > 0 && idB > 0) {
                distanciasParaGuardar.push({
                    id_ubicacion_a: idA,
                    id_ubicacion_b: idB,
                    km: km
                });
            }
        }
    });

    if (!valido) {
        Swal.fire("Atención", "Por favor ingresa los kilómetros para todos los tramos solicitados.", "warning");
        return;
    }

    // Cerrar modal
    const modalEl = document.getElementById('modalDistanciasFaltantes');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }

    // Si hay distancias de catálogo que registrar en la memoria progresiva
    if (distanciasParaGuardar.length > 0) {
        let request = new XMLHttpRequest();
        let ajaxUrl = base_url + '/Lgs_envios/guardarDistanciasFaltantes';

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-Type", "application/json");
        request.send(JSON.stringify({ distancias: distanciasParaGuardar }));

        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                try {
                    let res = JSON.parse(request.responseText);
                    if (res.status) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Distancias aprendidas en memoria progresiva',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        serializarNodos();
                        recalcularKmTotalVisual();
                        verificarYCalcularRuta(_callbackDistanciasPendiente);
                    } else {
                        Swal.fire("Error", res.message || "No se pudieron guardar las distancias", "error");
                    }
                } catch (e) {
                    Swal.fire("Error", "Error al procesar la respuesta del servidor", "error");
                }
            }
        };
    } else {
        // Eran destinos abiertos; se asignaron los KM directamente a las tarjetas del envío
        serializarNodos();
        recalcularKmTotalVisual();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Kilómetros manuales asignados a la ruta',
            showConfirmButton: false,
            timer: 2000
        });
        verificarYCalcularRuta(_callbackDistanciasPendiente);
    }
}

function actualizarEstatusLateralEnvio(idEstado) {
    const lbl = document.getElementById('lbl-estatus-envio-lateral');
    const iconCont = document.getElementById('icon-estatus-envio-lateral');
    const desc = document.getElementById('desc-estatus-envio-lateral');
    if (!lbl) return;

    let nombreEstado = 'Creado';
    let icono = '<i class="ri-draft-line text-white fs-24 opacity-50"></i>';
    let descripcion = '<i class="ri-information-line me-1"></i> Las subidas y bajadas de VINs por tramo se configuran en la siguiente pantalla.';

    switch(parseInt(idEstado || 1)) {
        case 1:
            nombreEstado = 'Creado';
            icono = '<i class="ri-draft-line text-white fs-24 opacity-75"></i>';
            descripcion = '<i class="ri-information-line me-1"></i> Envío creado, pendiente de configuración de ruta y acomodo.';
            break;
        case 2:
            nombreEstado = 'En Aprobación';
            icono = '<i class="ri-time-line text-warning fs-24"></i>';
            descripcion = '<i class="ri-time-line me-1 text-warning"></i> Pendiente de aprobación por logística o administración.';
            break;
        case 3:
            nombreEstado = 'Aprobado';
            icono = '<i class="ri-checkbox-circle-line text-info fs-24"></i>';
            descripcion = '<i class="ri-checkbox-circle-line me-1 text-info"></i> Aprobado para asignación y programación de salida.';
            break;
        case 4:
            nombreEstado = 'Rechazado';
            icono = '<i class="ri-close-circle-line text-danger fs-24"></i>';
            descripcion = '<i class="ri-close-circle-line me-1 text-danger"></i> La planeación fue rechazada.';
            break;
        case 5:
            nombreEstado = 'Programado';
            icono = '<i class="ri-calendar-check-line text-info fs-24"></i>';
            descripcion = '<i class="ri-calendar-check-line me-1 text-info"></i> Salida programada con unidades y chofer asignados.';
            break;
        case 6:
            nombreEstado = 'Ejecutado';
            icono = '<i class="ri-truck-line text-info fs-24"></i>';
            descripcion = '<i class="ri-truck-line me-1 text-info"></i> Madrina o chofer en ruta hacia los destinos.';
            break;
        case 7:
            nombreEstado = 'Entregado';
            icono = '<i class="ri-check-double-line text-success fs-24"></i>';
            descripcion = '<i class="ri-check-double-line me-1 text-success"></i> Todas las unidades han sido entregadas.';
            break;
        case 8:
            nombreEstado = 'Confirmado';
            icono = '<i class="ri-checkbox-circle-fill text-success fs-24"></i>';
            descripcion = '<i class="ri-checkbox-circle-fill me-1 text-success"></i> Envío confirmado, listo para incluir en planeación.';
            break;
        default:
            nombreEstado = 'Estado ' + idEstado;
            icono = '<i class="ri-route-line text-white fs-24 opacity-50"></i>';
            descripcion = '<i class="ri-information-line me-1"></i> Estado del traslado.';
            break;
    }

    lbl.innerHTML = nombreEstado;
    if (iconCont) iconCont.innerHTML = icono;
    if (desc) desc.innerHTML = descripcion;
}

function toggleMontoAbierto(activo) {
    const inputEsAbierto = document.getElementById('es_monto_abierto');
    const camposCont     = document.getElementById('campos_monto_abierto');
    const helpInactivo   = document.getElementById('help_monto_abierto_inactivo');

    if (inputEsAbierto) inputEsAbierto.value = activo ? '1' : '0';
    if (camposCont) camposCont.style.display = activo ? 'block' : 'none';
    if (helpInactivo) helpInactivo.style.display = activo ? 'none' : 'block';

    if (activo) {
        actualizarCostoEstimadoPorMontoAbierto();
    } else {
        if (typeof verificarYCalcularRuta === 'function') {
            verificarYCalcularRuta(null, false);
        }
    }
}

function actualizarCostoEstimadoPorMontoAbierto() {
    const inputEsAbierto = document.getElementById('es_monto_abierto');
    if (inputEsAbierto && inputEsAbierto.value === '1') {
        const inputMonto = document.getElementById('monto_pactado');
        const val = inputMonto ? parseFloat(inputMonto.value || 0) : 0;
        const badgeCosto = document.getElementById('lbl-resumen-costo');
        if (badgeCosto) {
            badgeCosto.innerHTML = '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}

function openModal() {
    window.g_editEnvioTipoTrasladoOriginal = null;
    window.g_editEnvioTieneVins = false;

    const isPre = esModoPreEnvioJS();
    document.querySelector('#id_envio').value = "";
    document.querySelector('#btnText').innerHTML = isPre ? "Guardar Pre-envío" : "Guardar Envío";
    document.querySelector('#form-envio-title').innerHTML = isPre ? "Crear Pre-envío / Posible Envío (PE-)" : "Crear Solicitud de Traslado";
    if (document.querySelector('#breadcrumb-form-envio')) {
        document.querySelector('#breadcrumb-form-envio').innerHTML = isPre ? "Nuevo Pre-envío" : "Nuevo Envío";
    }
    document.querySelector("#formEnvio").reset();
    if (document.querySelector('#es_preenvio')) {
        document.querySelector('#es_preenvio').value = isPre ? '1' : '0';
    }
    actualizarEstatusLateralEnvio(1);

    window.g_modalidadModificadaManualmente = false;

    if (document.querySelector('#modalidad_calculo')) {
        document.querySelector('#modalidad_calculo').value = 'DIRECTO';
        handleModalidadCalculoChange(false);
    }

    if (document.querySelector('#tipo_servicio')) {
        document.querySelector('#tipo_servicio').value = 'AUTOMATICO';
    }
    const badgeDetForm = document.getElementById('badge-deteccion-servicio-form');
    if (badgeDetForm) badgeDetForm.replaceChildren();
    const alertReglaLocal = document.getElementById('alert-regla-local-envio');
    if (alertReglaLocal) {
        alertReglaLocal.replaceChildren();
        alertReglaLocal.classList.add('d-none');
    }
    const badgeServRuta = document.getElementById('badge-servicio-ruta-val');
    if (badgeServRuta) {
        badgeServRuta.classList.add('d-none');
        badgeServRuta.textContent = '';
    }
    const alertTotalClear = document.getElementById('badge-distancia-total-container');
    if (alertTotalClear) alertTotalClear.style.setProperty('display', 'none', 'important');
    const badgeServClear = document.getElementById('badge-servicio-ruta-val');
    if (badgeServClear) {
        badgeServClear.className = 'badge fs-12 d-none';
        badgeServClear.textContent = '';
    }
    const contFormClear = document.getElementById('badge-deteccion-servicio-form');
    if (contFormClear) contFormClear.replaceChildren();
    const alertLocalClear = document.getElementById('alert-regla-local-envio');
    if (alertLocalClear) {
        alertLocalClear.className = 'col-md-12 mt-2 d-none';
        alertLocalClear.replaceChildren();
    }

    // Resetear Monto Abierto
    if (document.getElementById('check_monto_abierto')) {
        document.getElementById('check_monto_abierto').checked = false;
    }
    toggleMontoAbierto(false);
    if (document.getElementById('monto_pactado')) document.getElementById('monto_pactado').value = '';
    if (document.getElementById('motivo_monto_abierto')) document.getElementById('motivo_monto_abierto').value = '';

    // Resetear botones de acción
    const btnAction = document.getElementById('btnActionForm');
    const btnReabrir = document.getElementById('btnReabrirEditar');
    const btnAcomodo = document.getElementById('btnIrAcomodoDirecto');
    if (btnAction) btnAction.style.display = '';
    if (btnReabrir) btnReabrir.style.display = 'none';
    if (btnAcomodo) btnAcomodo.style.display = 'none';

    // Re-habilitar campos que pudieran estar deshabilitados de una vista previa
    document.querySelectorAll('#formEnvio select, #formEnvio input:not([type=hidden]), #formEnvio textarea').forEach(el => el.disabled = false);
    document.querySelectorAll('#btnAgregarDescarga, #btnAgregarCarga').forEach(el => el.style.display = '');

    // Limpiar contenedor de nodos
    const cont = document.getElementById('contenedor-nodos-ruta');
    if (cont) cont.innerHTML = '';

    // Iniciar con 2 nodos por defecto (Punto de Partida y Primer Destino)
    agregarNodoRuta();
    agregarNodoRuta();

    actualizarSecuenciaNodos();
    handleTipoTrasladoEnvio();
    fntSwitchView('form');
}

function saveEnvio() {
    serializarNodos();

    let id_tipo_traslado = document.querySelector('#id_tipo_traslado').value;
    let id_motivo        = document.querySelector('#id_motivo') ? document.querySelector('#id_motivo').value : '';
    let id_proveedor     = document.querySelector('#id_proveedor').value;
    let fecha_tentativa_envio = document.querySelector('#fecha_tentativa_envio') ? document.querySelector('#fecha_tentativa_envio').value : '';
    let is_lowboy = (document.getElementById('is_lowboy') && document.getElementById('is_lowboy').checked) ? 1 : 0;

    let es_monto_abierto = (document.getElementById('check_monto_abierto') && document.getElementById('check_monto_abierto').checked) ? 1 : 0;
    let monto_pactado    = document.getElementById('monto_pactado') ? parseFloat(document.getElementById('monto_pactado').value || 0) : 0;
    let motivo_monto_abierto = document.getElementById('motivo_monto_abierto') ? document.getElementById('motivo_monto_abierto').value.trim() : '';

    if (es_monto_abierto === 1) {
        if (!monto_pactado || monto_pactado <= 0) {
            Swal.fire("Atención", "Al activar Monto Abierto, debe ingresar el Monto Total Pactado mayor a cero.", "warning");
            return false;
        }
        if (!motivo_monto_abierto) {
            Swal.fire("Atención", "Al activar Monto Abierto, debe ingresar el motivo o justificación del acuerdo comercial.", "warning");
            return false;
        }
    }

    const nodosRaw = document.getElementById('nodos_json') ? document.getElementById('nodos_json').value : '[]';
    const nodos = JSON.parse(nodosRaw);

    if (!id_tipo_traslado || !id_motivo || !id_proveedor) {
        Swal.fire("Atención", "Todos los campos marcados con (*) son obligatorios.", "error");
        return false;
    }

    if (!fecha_tentativa_envio) {
        Swal.fire("Atención", "Debe ingresar la Fecha y Hora de Salida en el Punto 1 (Punto de Partida).", "warning");
        return false;
    }

    if (nodos.length < 2) {
        Swal.fire("Atención", "Debe agregar al menos 2 puntos en el recorrido (Punto de Partida y al menos un Destino).", "warning");
        return false;
    }

    for (let i = 0; i < nodos.length; i++) {
        if (!nodos[i].id_ubicacion && !nodos[i].destino_nombre_libre) {
            Swal.fire("Atención", `El Punto #${i + 1} debe tener una ubicación seleccionada o un nombre de punto abierto.`, "warning");
            return false;
        }
        if (!nodos[i].id_ubicacion && nodos[i].destino_nombre_libre && i > 0) {
            if (!nodos[i].km_tramo || parseFloat(nodos[i].km_tramo) <= 0) {
                Swal.fire("Atención", `El Punto #${i + 1} (${nodos[i].destino_nombre_libre}) es un punto abierto y requiere que ingrese los kilómetros del tramo.`, "warning");
                return false;
            }
        }
    }

    // Si el Punto 1 (Origen) es abierto, validar que el tramo hacia el Punto 2 tenga kilómetros
    if (nodos.length > 1 && !nodos[0].id_ubicacion && nodos[0].destino_nombre_libre) {
        if (!nodos[1].km_tramo || parseFloat(nodos[1].km_tramo) <= 0) {
            Swal.fire("Atención", `El Punto #1 (${nodos[0].destino_nombre_libre}) es un origen abierto. Debe ingresar los kilómetros del primer tramo hacia el Punto #2.`, "warning");
            return false;
        }
    }

    // Validar que no haya tramos consecutivos con la misma ubicación
    for (let i = 1; i < nodos.length; i++) {
        if (nodos[i].id_ubicacion && nodos[i - 1].id_ubicacion && nodos[i].id_ubicacion === nodos[i - 1].id_ubicacion) {
            Swal.fire("Ubicación duplicada", `El Punto #${i + 1} no puede ser la misma ubicación que el Punto #${i}. Una ruta no puede tener un tramo con origen y destino idénticos.`, "warning");
            return false;
        }
    }

    // Validar cronología de fechas estimadas (la descarga/siguiente nodo no sea menor a la carga/nodo anterior)
    let ultimaFecha = null;
    let indexUltimaFecha = 0;
    for (let i = 0; i < nodos.length; i++) {
        if (nodos[i].fecha_estimada) {
            let fechaActual = new Date(nodos[i].fecha_estimada);
            if (ultimaFecha && fechaActual < ultimaFecha) {
                Swal.fire("Fechas Inválidas", `La fecha/hora del Punto #${i + 1} no puede ser anterior a la fecha del Punto #${indexUltimaFecha + 1} (La descarga o llegada no puede ser menor a la salida).`, "warning");
                return false;
            }
            ultimaFecha = fechaActual;
            indexUltimaFecha = i;
        }
    }

    // Verificar si hay tramos sin distancia en la memoria progresiva antes de enviar
    verificarYCalcularRuta(function () {
        ejecutarGuardadoEnvioFinal();
    });
}

function ejecutarGuardadoEnvioFinal() {
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
                        const idEnvioGuardado = (objData.data && objData.data.id_envio) ? objData.data.id_envio : (objData.id_envio || null);
                        const esPreGuardado   = (objData.data && parseInt(objData.data.es_preenvio) === 1) || esModoPreEnvioJS();
                        document.querySelector("#formEnvio").reset();
                        if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                        if (typeof tableBandeja !== 'undefined' && tableBandeja) tableBandeja.ajax.reload();
                        if (typeof cargarUnidadesPTGlobales === 'function') cargarUnidadesPTGlobales(false);
                        
                        const msgBase = objData.message || objData.msg || "Guardado exitosamente con ruta multi-nodo.";
                        const recWarn = (objData.data && objData.data.recalculo_warning) ? `\n\nNota de cálculo: ${objData.data.recalculo_warning}` : '';
                        Swal.fire({
                            title: esPreGuardado ? "¡Pre-envío Guardado!" : "¡Envío Guardado!",
                            text: msgBase + recWarn,
                            icon: "success",
                            showCancelButton: true,
                            confirmButtonColor: esPreGuardado ? "#0284C7" : "#C46623",
                            cancelButtonColor: "#64748B",
                            confirmButtonText: '<i class="ri-dashboard-line me-1"></i> Ir al Acomodo de VINs',
                            cancelButtonText: esPreGuardado ? '<i class="ri-list-check me-1"></i> Ir al Listado de Pre-envíos' : '<i class="ri-list-check me-1"></i> Ir al Listado de Envíos'
                        }).then((result) => {
                            if (result.isConfirmed && idEnvioGuardado) {
                                window.location.href = base_url + '/Lgs_envios/detalle/' + idEnvioGuardado;
                            } else {
                                fntSwitchView('grid');
                                if (esPreGuardado && typeof irATabPreEnvios === 'function') {
                                    irATabPreEnvios();
                                }
                            }
                        });
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
    };
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
                        const modDef = p.modalidad_envio_default || 'DIRECTO';
                        htmlProv += `<option value="${p.id}" data-modalidad="${modDef}" data-nombre="${p.nombre}">${p.nombre}</option>`;
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

                    if (objData.data.ubicaciones && document.getElementById('catalogoUbicaciones')) {
                        document.getElementById('catalogoUbicaciones').textContent = JSON.stringify(objData.data.ubicaciones);
                    }
                    if (objData.data.plantas_wms && document.getElementById('catalogoPlantasWms')) {
                        document.getElementById('catalogoPlantasWms').textContent = JSON.stringify(objData.data.plantas_wms);
                    }
                    if (objData.data.destinos && document.getElementById('catalogoDestinos')) {
                        document.getElementById('catalogoDestinos').textContent = JSON.stringify(objData.data.destinos);
                    }
                    if (objData.data.distribuidores && document.getElementById('catalogoDistribuidores')) {
                        document.getElementById('catalogoDistribuidores').textContent = JSON.stringify(objData.data.distribuidores);
                    }
                }
            } catch(e) {}
        }
    }
}

function fntEditRuta(idEnvio) {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/getDetalleEnvioData/' + idEnvio;

    Swal.fire({
        title: 'Cargando Itinerario...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            Swal.close();
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status && objData.data) {
                    const envio = objData.data.envio;
                    const nodos = objData.data.nodos || [];
                    const existentes = objData.data.existentes || [];

                    window.g_editEnvioTipoTrasladoOriginal = envio.id_tipo_traslado || '';
                    window.g_editEnvioTieneVins = (existentes.length > 0);

                    const esPreEdit = parseInt(envio.es_preenvio) === 1 || String(envio.folio || '').startsWith('PE-') || esModoPreEnvioJS();
                    document.querySelector('#id_envio').value = envio.id_envio;
                    if (document.querySelector('#es_preenvio')) {
                        document.querySelector('#es_preenvio').value = esPreEdit ? '1' : '0';
                    }
                    document.querySelector('#btnText').innerHTML = esPreEdit ? "Actualizar Pre-envío" : "Actualizar Envío";
                    document.querySelector('#form-envio-title').innerHTML = (esPreEdit ? "Editar Itinerario de Pre-envío: " : "Editar Itinerario de Envío: ") + (envio.folio || ('#' + envio.id_envio));
                    if (document.querySelector('#breadcrumb-form-envio')) {
                        document.querySelector('#breadcrumb-form-envio').innerHTML = esPreEdit ? ('Editar ' + (envio.folio || 'Pre-envío')) : ('Editar ' + (envio.folio || 'Envío'));
                    }
                    actualizarEstatusLateralEnvio(envio.id_estado);

                    if (document.querySelector('#id_tipo_traslado')) document.querySelector('#id_tipo_traslado').value = envio.id_tipo_traslado || '';
                    if (document.querySelector('#tipo_servicio')) document.querySelector('#tipo_servicio').value = envio.tipo_servicio || 'AUTOMATICO';
                    handleTipoTrasladoEnvio();
                    if (document.querySelector('#id_motivo')) document.querySelector('#id_motivo').value = envio.id_motivo || '';
                    if (document.querySelector('#id_proveedor')) document.querySelector('#id_proveedor').value = envio.id_proveedor || '';

                    if (document.querySelector('#modalidad_calculo')) {
                        document.querySelector('#modalidad_calculo').value = envio.modalidad_calculo || 'ESCALONADO';
                        window.g_modalidadModificadaManualmente = true;
                        handleModalidadCalculoChange(false);
                    }

                    if (document.querySelector('#fecha_tentativa_envio')) document.querySelector('#fecha_tentativa_envio').value = formatDateTimeLocal(envio.fecha_tentativa_envio);
                    if (document.querySelector('#fecha_tentativa_llegada')) document.querySelector('#fecha_tentativa_llegada').value = formatDateTimeLocal(envio.fecha_tentativa_llegada);
                    if (document.querySelector('#observaciones')) document.querySelector('#observaciones').value = envio.observaciones || '';

                    // Cargar Monto Abierto si aplica
                    const esAbierto = (parseInt(envio.es_monto_abierto) === 1);
                    if (document.getElementById('check_monto_abierto')) {
                        document.getElementById('check_monto_abierto').checked = esAbierto;
                    }
                    toggleMontoAbierto(esAbierto);
                    if (document.getElementById('monto_pactado')) {
                        document.getElementById('monto_pactado').value = envio.monto_pactado ? parseFloat(envio.monto_pactado) : '';
                    }
                    if (document.getElementById('motivo_monto_abierto')) {
                        document.getElementById('motivo_monto_abierto').value = envio.motivo_monto_abierto || '';
                    }

                    // Cargar Telemetría y Pre-confirmación de Cita con Cliente
                    const tieneTelemetria = (parseInt(envio.madrina_con_telemetria) === 1);
                    if (document.getElementById('madrina_con_telemetria')) {
                        document.getElementById('madrina_con_telemetria').checked = tieneTelemetria;
                    }
                    const citaConfirmada = (parseInt(envio.confirmacion_cita_cliente) === 1);
                    if (document.getElementById('confirmacion_cita_cliente')) {
                        document.getElementById('confirmacion_cita_cliente').checked = citaConfirmada;
                        if (document.getElementById('divContactoConfirmacion')) {
                            document.getElementById('divContactoConfirmacion').style.display = citaConfirmada ? 'block' : 'none';
                        }
                    }
                    if (document.getElementById('contacto_confirmacion_cliente')) {
                        document.getElementById('contacto_confirmacion_cliente').value = envio.contacto_confirmacion_cliente || '';
                    }

                    // Llenar los nodos
                    const cont = document.getElementById('contenedor-nodos-ruta');
                    if (cont) cont.innerHTML = '';

                    if (nodos.length > 0) {
                        nodos.forEach((n, nIdx) => {
                            const isCarga = n.tipo_nodo ? (n.tipo_nodo === 'carga') : (parseInt(n.id_tipo_destino) === 5);
                            if (!n.fecha_estimada) {
                                if (nIdx === 0 && envio.fecha_tentativa_envio) {
                                    n.fecha_estimada = envio.fecha_tentativa_envio;
                                } else if (nIdx === nodos.length - 1 && envio.fecha_tentativa_llegada) {
                                    n.fecha_estimada = envio.fecha_tentativa_llegada;
                                }
                            }
                            agregarNodoRuta(n, isCarga);
                        });
                    } else {
                        agregarNodoRuta({ id_ubicacion: envio.id_origen, fecha_estimada: envio.fecha_tentativa_envio });
                        agregarNodoRuta({ id_ubicacion: envio.id_destino, destino_nombre_libre: envio.destino_nombre_libre, fecha_estimada: envio.fecha_tentativa_llegada });
                    }

                    actualizarSecuenciaNodos();
                    verificarYCalcularRuta();

                    // Proteger formulario si el envío ya está confirmado o en un estado posterior
                    const estadoEnvio = parseInt(envio.id_estado);
                    const btnAction = document.getElementById('btnActionForm');
                    const btnReabrirEditar = document.getElementById('btnReabrirEditar');
                    
                    if (estadoEnvio === 1 || estadoEnvio === 4) {
                        // Estado 1 (Creado) o Estado 4 (Regresado / Rechazado para ajuste): edición habilitada
                        if (btnAction) {
                            btnAction.style.display = '';
                            if (document.querySelector('#btnText')) {
                                document.querySelector('#btnText').innerHTML = (estadoEnvio === 4) ? "Actualizar y Reajustar Envío" : "Actualizar Envío";
                            }
                        }
                        if (btnReabrirEditar) btnReabrirEditar.style.display = 'none';
                        document.querySelectorAll('#formEnvio select, #formEnvio input:not([type=hidden]), #formEnvio textarea').forEach(el => el.disabled = false);
                        document.querySelectorAll('#btnAgregarDescarga, #btnAgregarCarga').forEach(el => el.style.display = '');
                    } else if (estadoEnvio === 8) {
                        // Confirmado: ofrecer reabrir para editar
                        if (btnAction) btnAction.style.display = 'none';
                        if (btnReabrirEditar) {
                            btnReabrirEditar.style.display = '';
                            btnReabrirEditar.setAttribute('data-id-envio', envio.id_envio);
                        }
                        // Deshabilitar inputs
                        document.querySelectorAll('#formEnvio select, #formEnvio input:not([type=hidden]), #formEnvio textarea').forEach(el => el.disabled = true);
                        document.querySelectorAll('#contenedor-nodos-ruta .btn-close, #contenedor-nodos-ruta .btn-outline-danger, #contenedor-nodos-ruta .btn-outline-primary').forEach(el => el.style.display = 'none');
                        document.querySelectorAll('#btnAgregarDescarga, #btnAgregarCarga').forEach(el => el.style.display = 'none');
                    } else {
                        // Estado 2+ (En Aprobación, Aprobado, Ejecutado, etc.): solo lectura sin opción de reabrir
                        if (btnAction) btnAction.style.display = 'none';
                        if (btnReabrirEditar) btnReabrirEditar.style.display = 'none';
                        document.querySelectorAll('#formEnvio select, #formEnvio input:not([type=hidden]), #formEnvio textarea').forEach(el => el.disabled = true);
                        document.querySelectorAll('#contenedor-nodos-ruta .btn-close, #contenedor-nodos-ruta .btn-outline-danger, #contenedor-nodos-ruta .btn-outline-primary').forEach(el => el.style.display = 'none');
                        document.querySelectorAll('#btnAgregarDescarga, #btnAgregarCarga').forEach(el => el.style.display = 'none');
                    }

                    const btnAcomodo = document.getElementById('btnIrAcomodoDirecto');
                    if (btnAcomodo) {
                        btnAcomodo.style.display = '';
                        btnAcomodo.setAttribute('data-id-envio', envio.id_envio);
                    }

                    fntSwitchView('form');
                }
            } catch (e) {
                console.error("Error en fntEditRuta:", e);
                Swal.fire("Error", "No se pudo cargar la información del envío: " + (e.message || ''), "error");
            }
        }
    };
}

function irAcomodoDesdeFormulario() {
    const btn = document.getElementById('btnIrAcomodoDirecto');
    const id = btn ? btn.getAttribute('data-id-envio') : null;
    if (id) {
        window.location.href = base_url + '/Lgs_envios/detalle/' + id;
    }
}

function fntViewEnvio(idEnvio) {
    window.location.href = base_url + '/Lgs_envios/detalle/' + idEnvio;
}

function fntReabrirParaEditar() {
    const btnReabrir = document.getElementById('btnReabrirEditar');
    const idEnvio = btnReabrir ? btnReabrir.getAttribute('data-id-envio') : null;
    if (!idEnvio) return;

    Swal.fire({
        title: '¿Reabrir envío para editar?',
        html: '<p class="mb-1">El envío regresará al estado <strong>Creado</strong> y deberá ser confirmado nuevamente.</p><p class="text-muted small mb-0">Esto también lo removerá de cualquier planeación pendiente.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-lock-unlock-line me-1"></i> Sí, reabrir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Habilitar campos y mostrar botón guardar
            document.querySelectorAll('#formEnvio select, #formEnvio input:not([type=hidden]), #formEnvio textarea').forEach(el => el.disabled = false);
            document.querySelectorAll('#btnAgregarDescarga, #btnAgregarCarga').forEach(el => el.style.display = '');
            document.querySelectorAll('#contenedor-nodos-ruta .btn-close, #contenedor-nodos-ruta .btn-outline-danger, #contenedor-nodos-ruta .btn-outline-primary').forEach(el => el.style.display = '');
            
            const btnAction = document.getElementById('btnActionForm');
            if (btnAction) {
                btnAction.style.display = '';
                document.querySelector('#btnText').innerHTML = "Actualizar Envío";
            }
            btnReabrir.style.display = 'none';
            
            actualizarEstatusLateralEnvio(1);
            Swal.fire({
                title: 'Envío desbloqueado',
                text: 'Ahora puede editar la ruta. Al guardar, el envío regresará a estado Creado.',
                icon: 'info',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
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
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_envios/delete';
            let formData = new FormData();
            formData.append('id_envio', idEnvio);

            Swal.fire({
                title: 'Eliminando...',
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
                            if (objData.status === 'success' || objData.code === 200) {
                                Swal.fire('Eliminado!', objData.message || 'El registro ha sido eliminado.', 'success');
                                if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                            } else {
                                Swal.fire("Error", objData.message || "Error al eliminar el envío", "error");
                            }
                        } catch (e) {
                            Swal.fire("Error", "Respuesta no válida del servidor.", "error");
                        }
                    } else {
                        try {
                            let objData = JSON.parse(request.responseText);
                            Swal.fire("Error (" + request.status + ")", objData.message || "Ocurrió un error en el servidor.", "error");
                        } catch (e) {
                            Swal.fire("Error (" + request.status + ")", "Error en el servidor al procesar la solicitud.", "error");
                        }
                    }
                }
            }
        }
    });
}

function fntReabrirEnvio(idEnvio) {
    Swal.fire({
        title: '¿Reabrir / Desbloquear Envío?',
        text: 'El envío regresará a estado En Planeación para que puedas editar su acomodo, paradas y costos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-restart-line me-1"></i> Sí, reabrir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reabriendo Envío...',
                text: 'Por favor espere.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() }
            });

            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_envios/reabrir';
            let formData = new FormData();
            formData.append('id_envio', idEnvio);

            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    try {
                        let objData = JSON.parse(request.responseText);
                        if (objData.status === 'success' || objData.status === true || objData.code === 200) {
                            Swal.fire("¡Envío Reabierto!", objData.message || objData.msg || "Envío reabierto y desbloqueado exitosamente.", "success");
                            if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                        } else {
                            Swal.fire("Error", objData.message || objData.msg || "No se pudo reabrir el envío", "error");
                        }
                    } catch (e) {
                        Swal.fire("Error", "Error al procesar la respuesta del servidor", "error");
                    }
                }
            };
        }
    });
}


function handleTipoTrasladoEnvio() {
    const idTipoTraslado = document.getElementById('id_tipo_traslado') ? document.getElementById('id_tipo_traslado').value : '';

    // Candado: Si el envío ya cuenta con unidades (VINs) asignadas, no permitir cambiar el tipo de traslado
    if (window.g_editEnvioTieneVins && window.g_editEnvioTipoTrasladoOriginal && idTipoTraslado != window.g_editEnvioTipoTrasladoOriginal) {
        document.getElementById('id_tipo_traslado').value = window.g_editEnvioTipoTrasladoOriginal;
        Swal.fire({
            title: "Cambio no permitido",
            text: "No es posible cambiar el tipo de traslado (Madrina / Plataforma / Chofer) porque este envío ya cuenta con unidades asignadas. Debe desasignar los VINs en la pantalla de Acomodo antes de cambiar de modalidad.",
            icon: "warning"
        });
        return;
    }

    const btnAddCarga = document.querySelector('button[onclick="agregarNodoRuta(null, true)"]');
    const btnAddDescarga = document.querySelector('button[onclick="agregarNodoRuta(null, false)"]');
    
    if (idTipoTraslado == '2') {
        if (btnAddCarga) btnAddCarga.style.display = 'none';
        if (btnAddDescarga) btnAddDescarga.style.display = 'none';
        
        // Remove extra nodes if there are more than 2
        const cont = document.getElementById('contenedor-nodos-ruta');
        if (cont) {
            const items = cont.querySelectorAll('.nodo-item');
            if (items.length > 2) {
                for (let i = 2; i < items.length; i++) {
                    items[i].remove();
                }
                actualizarSecuenciaNodos();
            }
        }
    }
    
    if (idTipoTraslado != '2') {
        if (btnAddCarga) btnAddCarga.style.display = '';
        if (btnAddDescarga) btnAddDescarga.style.display = '';
    }

    const divCheckLowboy = document.getElementById('divCheckLowboy');
    if (divCheckLowboy) {
        if (idTipoTraslado == '3') { // Plataforma
            divCheckLowboy.style.display = 'block';
        } else {
            divCheckLowboy.style.display = 'none';
            document.getElementById('is_lowboy').checked = false;
        }
    }

    // Regla de negocio: Modalidad por defecto solo para Plataformas (3) y Madrinas (1)
    if (idTipoTraslado == '2') {
        window.g_modalidadModificadaManualmente = false;
        aplicarDefaultModalidadCalculo(true);
    } else {
        aplicarDefaultModalidadCalculo(false);
    }
    actualizarDeteccionAutomaticaRutaUI();
}

/**
 * Pausa un envío por incidencia y notifica automáticamente al área comercial
 */
function fntPausarEnvio(idEnvio, folio) {
    Swal.fire({
        title: `Pausar Envío ${folio}`,
        html: `
            <p class="text-muted fs-13 mb-3 text-start">
                Indique el motivo o la incidencia ocurrida en ruta para pausar el envío. 
                <br><span class="text-danger fw-semibold"><i class="ri-notification-3-line me-1"></i> Se enviará una alerta automática al equipo comercial.</span>
            </p>
            <textarea id="swal_motivo_pausa" class="form-control fs-13" rows="3" placeholder="Ej: Falla mecánica en tractocamión, bloqueo en autopista km 140, demora por trámites aduanales..."></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-pause-circle-line me-1"></i> Confirmar y Notificar Pausa',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const motivo = document.getElementById('swal_motivo_pausa') ? document.getElementById('swal_motivo_pausa').value.trim() : '';
            if (!motivo) {
                Swal.showValidationMessage('Debe ingresar el motivo de la pausa.');
                return false;
            }
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Pausando Envío...',
                text: 'Notificando al área comercial y actualizando estado...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('motivo_pausa', result.value);

            fetch(base_url + '/Lgs_envios/pausarEnvio', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    Swal.fire("¡Envío Pausado!", objData.message || "El envío ha sido pausado y se notificó al área comercial.", "success");
                    if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo pausar el envío.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de red al pausar el envío.", "error");
            });
        }
    });
}

/**
 * Reanuda un envío previamente pausado y notifica al área comercial
 */
function fntReanudarEnvio(idEnvio, folio) {
    Swal.fire({
        title: `Reanudar Envío ${folio}`,
        html: `
            <p class="text-muted fs-13 mb-3 text-start">
                ¿Confirmar la reanudación del viaje? 
                <br><span class="text-success fw-semibold"><i class="ri-notification-3-line me-1"></i> Se notificará al equipo comercial que el envío continúa su ruta normal.</span>
            </p>
            <textarea id="swal_notas_reanudacion" class="form-control fs-13" rows="3" placeholder="Observaciones de reanudación (opcional): Ej. Se resolvió la falla y continúa hacia destino..."></textarea>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-play-circle-line me-1"></i> Reanudar y Notificar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const notas = document.getElementById('swal_notas_reanudacion') ? document.getElementById('swal_notas_reanudacion').value.trim() : '';

            Swal.fire({
                title: 'Reanudando Envío...',
                text: 'Notificando al área comercial y restableciendo operación...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('notas_reanudacion', notas);

            fetch(base_url + '/Lgs_envios/reanudarEnvio', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    Swal.fire("¡Envío Reanudado!", objData.message || "El viaje ha sido reanudado y se notificó al área comercial.", "success");
                    if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo reanudar el envío.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de red al reanudar el envío.", "error");
            });
        }
    });
}

function _escExcepHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function fntMostrarModalSolicitudAutorizacion(idEnvio, folio, dataAuth) {
    const observados = Array.isArray(dataAuth.vins_observados) ? dataAuth.vins_observados : [];
    const solPend    = dataAuth.solicitud_pendiente || null;
    const ultRech    = dataAuth.ultima_rechazada || null;

    const rowsHtml = observados.map(w => {
        const facBadge = parseInt(w.cumple_factura || 0, 10) === 1
            ? `<span class="badge bg-soft-success text-success">SÍ (${_escExcepHtml(w.factura)})</span>`
            : '<span class="badge bg-soft-danger text-danger">Sin Factura</span>';
        const pagBadge = parseInt(w.cumple_pago || 0, 10) === 1
            ? `<span class="badge bg-soft-success text-success">${_escExcepHtml(w.estatus_pago_texto)}</span>`
            : `<span class="badge bg-soft-warning text-dark">${_escExcepHtml(w.estatus_pago_texto || 'Pendiente')}</span>`;
        const autBadge = parseInt(w.cumple_autorizacion || 0, 10) === 1
            ? '<span class="badge bg-soft-success text-success">Autorizada</span>'
            : '<span class="badge bg-soft-danger text-danger">Sin Aut. Mov.</span>';
        return `<tr>
            <td class="text-start font-monospace fw-bold fs-11">${_escExcepHtml(w.vin)}<div class="text-muted fw-normal">${_escExcepHtml(w.modelo || '')}</div></td>
            <td>${facBadge}</td>
            <td>${pagBadge}</td>
            <td>${autBadge}</td>
        </tr>`;
    }).join('');

    let estadoPrevHtml = '';
    if (ultRech && !solPend) {
        estadoPrevHtml = `<div class="alert alert-soft-danger border border-danger-subtle text-start fs-12 py-2 px-3 mb-2">
            <strong><i class="ri-close-circle-line me-1"></i>Solicitud previa rechazada (${_escExcepHtml(ultRech.folio_solicitud || '')}):</strong>
            ${_escExcepHtml(ultRech.obs_aprobador || 'Sin comentarios')}
        </div>`;
    }

    if (solPend) {
        Swal.fire({
            title: 'Solicitud Pendiente de Aprobación',
            width: '720px',
            icon: 'info',
            html: `
                <div class="text-start fs-13 mb-2">
                    El registro <strong>${_escExcepHtml(folio || ('#' + idEnvio))}</strong> tiene unidades sin Factura, Pago o Autorización de Movimiento y ya cuenta con la solicitud
                    <span class="badge bg-warning text-dark">${_escExcepHtml(solPend.folio_solicitud)}</span> pendiente de dictamen en <strong>Aprobaciones</strong>.
                </div>
                <div class="p-2 bg-light border rounded text-start fs-12 mb-3">
                    <strong>Justificación enviada:</strong> ${_escExcepHtml(solPend.motivo_solicitud || '')}
                </div>
                <div class="table-responsive border rounded" style="max-height:220px; overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0 fs-11">
                        <thead class="table-light"><tr><th class="text-start">VIN / Modelo</th><th>Factura</th><th>Pago</th><th>Aut. Mov.</th></tr></thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#C46623',
            cancelButtonColor: '#64748B',
            confirmButtonText: '<i class="ri-shield-check-line me-1"></i> Ir a Aprobaciones',
            cancelButtonText: 'Cerrar'
        }).then((r) => {
            if (r.isConfirmed) {
                window.location.href = base_url + '/Lgs_aprobaciones?tab=preenvios';
            }
        });
        return;
    }

    Swal.fire({
        title: 'Validación de Factura / Pago / Autorización',
        width: '760px',
        icon: 'warning',
        html: `
            ${estadoPrevHtml}
            <p class="text-start fs-13 mb-2">
                No es posible pasar <strong>${_escExcepHtml(folio || ('#' + idEnvio))}</strong> directamente a Envío Oficial (<code>EN-</code>) porque las siguientes unidades aún no cumplen Factura, Pago/Crédito y Autorización de Movimiento:
            </p>
            <div class="table-responsive border rounded mb-3" style="max-height:220px; overflow-y:auto;">
                <table class="table table-sm align-middle mb-0 fs-11">
                    <thead class="table-light"><tr><th class="text-start">VIN / Modelo</th><th>Factura</th><th>Pago</th><th>Aut. Mov.</th></tr></thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
            <div class="text-start">
                <label class="form-label fs-12 fw-bold text-dark mb-1">
                    <i class="ri-send-plane-line text-warning me-1"></i> Forzar bajo solicitud (Justificación para Aprobaciones) <span class="text-danger">*</span>
                </label>
                <textarea id="swal_motivo_excepcion_envio" class="form-control fs-12" rows="3" placeholder="Ej. Cliente autorizado por Dirección Comercial; factura en proceso de emisión / pago confirmado por tesorería..."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-shield-user-line me-1"></i> Solicitar Autorización en Aprobaciones',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const motivo = (document.getElementById('swal_motivo_excepcion_envio')?.value || '').trim();
            if (motivo.length < 5) {
                Swal.showValidationMessage('Ingrese el motivo o justificación (mínimo 5 caracteres).');
                return false;
            }
            return motivo;
        }
    }).then((conf) => {
        if (!conf.isConfirmed) return;
        Swal.fire({ title: 'Enviando Solicitud...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const fd = new FormData();
        fd.append('id_envio', idEnvio);
        fd.append('motivo_solicitud', conf.value);
        fetch(base_url + '/Lgs_envios/solicitarAutorizacionConversion', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (!resp || (resp.status !== 'success' && resp.status !== true && resp.code !== 200)) {
                    throw new Error(resp?.message || 'No se pudo enviar la solicitud.');
                }
                if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                Swal.fire({
                    title: 'Solicitud Enviada a Aprobaciones',
                    text: resp.message || 'Un aprobador debe autorizar la excepción para pasarlo a Envío Oficial.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#C46623',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: '<i class="ri-shield-check-line me-1"></i> Ir a Aprobaciones',
                    cancelButtonText: 'Seguir aquí'
                }).then((go) => {
                    if (go.isConfirmed) {
                        window.location.href = base_url + '/Lgs_aprobaciones?tab=preenvios';
                    }
                });
            })
            .catch(err => Swal.fire('Error', err.message || 'Error de comunicación con el servidor.', 'error'));
    });
}

function fntConvertirPreEnvio(idEnvio, folio) {
    Swal.fire({
        title: `¿Pasar ${folio || 'Pre-envío'} a Envío Oficial (EN-)?`,
        text: 'Se validará ruta, acomodo con Madrina/Chofer, Factura, Pago y Autorización de Movimiento de cada unidad.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-exchange-line me-1"></i> Validar y Pasar a Envío',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Validando Pre-envío...',
            text: 'Verificando factura, cobranza, autorización y generando folio EN-...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const formData = new FormData();
        formData.append('id_envio', idEnvio);

        fetch(base_url + '/Lgs_envios/convertirAEnvioOficial', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(objData => {
            if (!objData || (objData.status !== 'success' && objData.status !== true && objData.code !== 200)) {
                throw new Error(objData?.message || 'No se pudo convertir el pre-envío.');
            }
            if (objData.data && objData.data.requires_authorization) {
                fntMostrarModalSolicitudAutorizacion(idEnvio, folio, objData.data);
                return;
            }
            if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
            if (typeof cargarUnidadesPTGlobales === 'function') cargarUnidadesPTGlobales(false);

            Swal.fire({
                title: '¡Convertido a Envío Oficial!',
                text: objData.message || 'El pre-envío pasó correctamente al módulo de Envíos.',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#C46623',
                cancelButtonColor: '#64748B',
                confirmButtonText: '<i class="ri-truck-line me-1"></i> Pasar al Módulo de Envíos',
                cancelButtonText: 'Seguir aquí'
            }).then((go) => {
                if (go.isConfirmed) {
                    window.location.href = base_url + '/Lgs_envios';
                }
            });
        })
        .catch(err => Swal.fire('Validación', err.message || 'Error de comunicación con el servidor.', 'warning'));
    });
}

