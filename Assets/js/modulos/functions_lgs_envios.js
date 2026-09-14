function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-envios");
    const secForm = document.querySelector("#view-form-envios");

    if (view === 'form') {
        if (secGrid) secGrid.style.display = "none";
        if (secForm) secForm.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        if (secForm) secForm.style.display = "none";
        if (secGrid) secGrid.style.display = "block";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function cancelFormEnvio() {
    document.querySelector("#formEnvio").reset();
    fntSwitchView('grid');
}

let tableEnvios;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable
    tableEnvios = $('#tableEnvios').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_envios/getEnvios",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_envio" },
            { 
                "data": "folio",
                "render": function(data, type, row) {
                    let html = '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || 'S/F') + '</span>';
                    if (row.vins_list) {
                        const vins = row.vins_list.split(', ');
                        html += '<div class="mt-1">' + vins.map(v => '<span class="badge bg-soft-secondary text-secondary me-1 fs-10">' + v + '</span>').join('') + '</div>';
                    }
                    return html;
                }
            },
            { "data": "tipo_traslado" },
            { "data": "motivo" },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || '-') + '</span>';
                }
            },
            { "data": "origen" },
            { 
                "data": "destino",
                "render": function(data, type, row) {
                    let html = '<span class="fw-medium text-dark">' + (data || 'Sin Destino') + '</span>';
                    if (row.paradas_list) {
                        html += '<div class="mt-1 fs-11 text-muted"><i class="ri-route-line text-info me-1"></i>Ruta: ' + row.paradas_list + '</div>';
                    }
                    return html;
                }
            },
            { 
                "data": "km_total",
                "render": function(data, type, row) {
                    const kmVal = parseFloat(data || 0).toFixed(1);
                    const nParadas = row.total_paradas || 1;
                    return '<span class="badge bg-soft-info text-info fs-12 fw-bold"><i class="ri-route-line me-1"></i>' + kmVal + ' km</span>' +
                           '<div class="text-muted fs-11 mt-1">' + nParadas + ' parada(s)</div>';
                }
            },
            { 
                "data": "total_vins",
                "render": function(data, type, row) {
                    if (!row.vins_list || !row.vins_list.trim()) {
                        return '<span class="badge bg-soft-secondary text-muted fs-12">Sin VINs</span>';
                    }
                    const vins = row.vins_list.split(', ').filter(v => v.trim());
                    let html = vins.map(v => '<span class="badge bg-primary me-1 fs-10" style="font-size:10px;">' + v + '</span>').join('');
                    html += '<div class="text-muted fs-11 mt-1">' + vins.length + ' unidad(es)</div>';
                    return html;
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<span class="fw-bold text-success">$' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            {
                "data": "fecha_tentativa_envio",
                "render": function (data) {
                    if (!data || data === 'null') return '<span class="text-muted fs-11">No definida</span>';
                    return '<span class="fs-12 text-dark fw-medium"><i class="ri-calendar-event-line text-primary me-1"></i>' + data.replace('T', ' ') + '</span>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12"><i class="ri-draft-line me-1"></i>Borrador</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>En Revisión</span>'; break;
                        case 3: badge = '<span class="badge bg-soft-primary text-primary fs-12"><i class="ri-checkbox-circle-line me-1"></i>Envío Aprobado</span>'; break;
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>Planeación Rechazada</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-calendar-check-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-truck-line me-1"></i>En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        case 8: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-line me-1"></i>Envío Confirmado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    let btnReabrir = '';
                    let estado = parseInt(row.id_estado);
                    
                    if (estado === 4 || estado === 2) {
                        btnReabrir = `<button class="btn btn-sm btn-soft-warning rounded-pill px-3 fw-semibold me-1" onClick="fntReabrirEnvio(${data})" title="Reabrir / Desbloquear Envío">
                                        <i class="ri-restart-line me-1"></i> Reabrir
                                      </button>`;
                    }
                    
                    let btnRuta = '';
                    let btnAcomodo = '';
                    let btnEliminar = '';

                    if (estado === 1 || estado === 8) {
                        btnRuta = `<button class="btn btn-sm btn-soft-secondary rounded-pill px-3 fw-semibold me-1" onClick="fntEditRuta(${data})" title="Editar Itinerario / Ruta">
                                    <i class="ri-edit-line me-1"></i> Ruta
                                </button>`;
                        btnAcomodo = `<button class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold me-1" onClick="fntViewEnvio(${data})" title="Ver / Acomodar VINs">
                                    <i class="ri-truck-line me-1"></i> Acomodo
                                </button>`;
                        btnEliminar = `<button class="btn btn-sm btn-soft-danger rounded-pill px-3 fw-semibold" onClick="fntDelEnvio(${data})" title="Eliminar">
                                    <i class="ri-delete-bin-line me-1"></i> Eliminar
                                </button>`;
                    } else {
                        btnRuta = `<button class="btn btn-sm btn-soft-secondary rounded-pill px-3 fw-semibold me-1" onClick="fntEditRuta(${data})" title="Ver Itinerario / Ruta">
                                    <i class="ri-eye-line me-1"></i> Ver Ruta
                                </button>`;
                        btnAcomodo = `<button class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold me-1" onClick="fntViewEnvio(${data})" title="Ver Detalles del Acomodo">
                                    <i class="ri-eye-line me-1"></i> Detalles
                                </button>`;
                    }

                    return `<div class="text-end">
                                ${btnReabrir}
                                ${btnRuta}
                                ${btnAcomodo}
                                ${btnEliminar}
                            </div>`;
                }
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasEnvios(settings.json || []);
        }
    });

    // Cargar Catálogos Iniciales
    cargarProveedoresTrasladistas();
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

function actualizarMetricasEnvios(data) {
    if (!Array.isArray(data)) return;
    
    let total = data.length;
    let creados = data.filter(e => parseInt(e.id_estado) === 1).length;
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let entregados = data.filter(e => parseInt(e.id_estado) === 7).length;

    if (document.getElementById('cardTotalEnvios')) document.getElementById('cardTotalEnvios').innerText = total;
    if (document.getElementById('cardEnviosCreados')) document.getElementById('cardEnviosCreados').innerText = creados;
    if (document.getElementById('cardEnviosTransito')) document.getElementById('cardEnviosTransito').innerText = transito;
    if (document.getElementById('cardEnviosEntregados')) document.getElementById('cardEnviosEntregados').innerText = entregados;
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
                const dir = u.direccion ? ` — ${u.direccion}` : '';
                const uId = u.id || u.id_ubicacion;
                html += `<option value="${uId}" data-direccion="${u.direccion || ''}" data-nombre="${u.nombre}" ${sel}>${u.nombre}${dir}</option>`;
            });
            html += `</optgroup>`;
        }
    });

    return html;
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

/** Agrega un nuevo nodo / punto al recorrido de la ruta */
function agregarNodoRuta(data, isCarga = false) {
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

    const selUbicacionId = data ? (data.id_ubicacion || data.id_destino_cat || '') : '';
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

    div.innerHTML = `
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge ${esOrigen ? 'bg-success' : (isCargaVal ? 'bg-info' : 'bg-primary')} badge-nodo-tipo px-2 py-1 fs-11">
                    <i class="${esOrigen ? 'ri-map-pin-user-line' : (isCargaVal ? 'ri-map-pin-add-line' : 'ri-map-pin-line')} me-1"></i>
                    <span class="nodo-tipo-txt">${esOrigen ? 'PUNTO 1 (PUNTO DE PARTIDA)' : (isCargaVal ? 'PUNTO ' + (index + 1) + ' (CARGA)' : 'PUNTO ' + (index + 1) + ' (PARADA/ENTREGA)')}</span>
                </span>
                <span class="text-muted fs-11 nodo-desc-txt">
                    ${esOrigen ? 'Lugar de salida de la madrina o chofer' : (isCargaVal ? 'Punto de recolección o carga de unidades' : 'Parada intermedia o destino de entrega')}
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
            <div class="${esOrigen ? 'col-md-9' : 'col-md-7'} col-ubi-wrapper">
                <label class="form-label fs-11 text-muted mb-1 fw-bold">Ubicación del Catálogo <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm nodo-select-ubicacion" onchange="alCambiarUbicacionNodo(this)">
                    ${buildUbicacionOptions(selUbicacionId)}
                </select>
                <small class="text-muted fs-10 d-block mt-1 nodo-direccion-preview"></small>
            </div>
            <!-- Input oculto para conservar la propiedad en el objeto al guardar, si fuera necesario, o simplemente se manda vacío -->
            <input type="hidden" class="nodo-nombre-libre" value="${nombreLibre}">
            <div class="col-md-3 seccion-fecha-estimada">
                <label class="form-label fs-11 text-muted mb-1 fw-bold nodo-lbl-fecha-estimada">
                    <i class="ri-calendar-event-line ${isCargaVal ? 'text-info' : 'text-primary'} me-1"></i>${esOrigen ? 'Llegada est. Origen' : (isCargaVal ? 'Est. Recolección' : 'Est. Entrega')}
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
                        placeholder="0.0"
                        oninput="serializarNodos()">
                    <span class="input-group-text bg-light text-muted fs-11 px-1">km</span>
                </div>
                <small class="text-muted fs-10 d-block mt-1 nodo-memoria-badge">
                    <i class="ri-history-line me-1"></i>Memoria
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

    if (!data) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            icon: 'success',
            title: isCargaVal ? 'Punto de Carga añadido' : 'Punto de Entrega añadido'
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
            if (infoSpan) infoSpan.innerHTML = "";
            serializarNodos();
            return;
        }
    }

    serializarNodos();
    verificarYCalcularRuta();
}

/** Actualiza numeración, badges visuales y botones de los nodos */
function actualizarSecuenciaNodos() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    items.forEach((card, idx) => {
        const esPrimero = (idx === 0);
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        const badge = card.querySelector('.badge-nodo-tipo');
        const txtTipo = card.querySelector('.nodo-tipo-txt');
        const descTxt = card.querySelector('.nodo-desc-txt');
        const colUbi   = card.querySelector('.col-ubi-wrapper');
        const secFecha = card.querySelector('.seccion-fecha-estimada');
        const lblFecha = card.querySelector('.nodo-lbl-fecha-estimada');
        const secKm    = card.querySelector('.seccion-km-tramo');

        if (esPrimero) {
            if (colUbi) colUbi.className = 'col-md-9 col-ubi-wrapper';
            if (secFecha) secFecha.style.display = '';
            if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line text-primary me-1"></i>Llegada est. Origen';
            if (secKm) secKm.style.display = 'none';
            if (badge) {
                badge.className = 'badge bg-success badge-nodo-tipo px-2 py-1 fs-11';
                badge.innerHTML = '<i class="ri-map-pin-user-line me-1"></i><span class="nodo-tipo-txt">PUNTO 1 (PUNTO DE PARTIDA)</span>';
            }
            if (descTxt) descTxt.innerText = 'Lugar de salida de la madrina o chofer';
        } else {
            if (colUbi) colUbi.className = 'col-md-7 col-ubi-wrapper';
            if (secFecha) secFecha.style.display = '';
            if (secKm) secKm.style.display = '';

            if (isCarga) {
                if (badge) {
                    badge.className = 'badge bg-info badge-nodo-tipo px-2 py-1 fs-11';
                    badge.innerHTML = `<i class="ri-map-pin-add-line me-1"></i><span class="nodo-tipo-txt">PUNTO ${idx + 1} (CARGA)</span>`;
                }
                if (descTxt) descTxt.innerText = 'Punto de recolección o carga de unidades';
                if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line text-info me-1"></i>Est. Recolección';
            } else {
                if (badge) {
                    badge.className = 'badge bg-primary badge-nodo-tipo px-2 py-1 fs-11';
                    badge.innerHTML = `<i class="ri-map-pin-line me-1"></i><span class="nodo-tipo-txt">PUNTO ${idx + 1} (PARADA / ENTREGA)</span>`;
                }
                if (descTxt) descTxt.innerText = 'Parada intermedia o destino final';
                if (lblFecha) lblFecha.innerHTML = '<i class="ri-calendar-event-line text-primary me-1"></i>Est. Entrega';
            }
        }
    });

    serializarNodos();
}

/** Serializa los nodos en los campos ocultos del formulario */
function serializarNodos() {
    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');
    const nodos = [];
    const paradasCompat = [];

    items.forEach((card, idx) => {
        const isCarga = card.getAttribute('data-tipo-nodo') === 'carga';
        const tipoNodo = (idx === 0) ? 'origen' : (isCarga ? 'carga' : 'entrega');
        const selUbi = card.querySelector('.nodo-select-ubicacion');
        const idUbi  = selUbi && selUbi.value ? parseInt(selUbi.value) : null;
        const nombreLibre = card.querySelector('.nodo-nombre-libre') ? card.querySelector('.nodo-nombre-libre').value.trim() : '';
        const kmVal  = card.querySelector('.nodo-km-tramo') ? (parseFloat(card.querySelector('.nodo-km-tramo').value) || 0) : 0;
        const fechaEst = card.querySelector('.nodo-fecha-estimada') ? card.querySelector('.nodo-fecha-estimada').value : '';

        nodos.push({
            orden: idx,
            tipo_nodo: tipoNodo,
            id_ubicacion: idUbi,
            destino_nombre_libre: nombreLibre,
            km_tramo: (idx === 0) ? 0 : kmVal,
            fecha_estimada: fechaEst
        });

        if (idx > 0) {
            paradasCompat.push({
                orden: idx,
                tipo_nodo: tipoNodo,
                id_destino_cat: idUbi,
                destino_nombre_libre: nombreLibre,
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

    // Verificar si todos los nodos tienen ubicación seleccionada
    for (let i = 0; i < nodos.length; i++) {
        if (!nodos[i].id_ubicacion && !nodos[i].destino_nombre_libre) {
            Swal.fire("Atención", "Por favor seleccione una ubicación para todos los puntos de la ruta antes de verificar.", "warning");
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

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({ nodos: nodos }));

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let res = JSON.parse(request.responseText);
                if (res.status && res.data) {
                    const data = res.data;
                    const items = document.querySelectorAll('#contenedor-nodos-ruta .nodo-item');

                    // Actualizar distancias conocidas en la UI
                    if (Array.isArray(data.tramos)) {
                        data.tramos.forEach(t => {
                            const nodoCard = items[t.tramo_indice];
                            if (nodoCard) {
                                const inpKm = nodoCard.querySelector('.nodo-km-tramo');
                                if (inpKm && t.km > 0) {
                                    inpKm.value = t.km;
                                }
                            }
                        });
                    }

                    serializarNodos();

                    // Mostrar resumen de KM total
                    const alertTotal = document.getElementById('badge-distancia-total-container');
                    const spanVal = document.getElementById('badge-km-total-val');
                    if (alertTotal && spanVal) {
                        alertTotal.style.setProperty('display', 'flex', 'important');
                        spanVal.innerText = (data.km_total || 0).toFixed(1) + ' km (Memoria Progresiva)';
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

    // Filtrar cualquier tramo inválido donde origen y destino sean idénticos
    faltantes = faltantes.filter(f => f.id_ubicacion_a !== f.id_ubicacion_b && (f.id_ubicacion_a > 0 || f.id_ubicacion_b > 0));
    if (faltantes.length === 0) {
        if (typeof callbackOnSuccess === 'function') {
            callbackOnSuccess();
        }
        return;
    }

    let html = '';
    faltantes.forEach((f, idx) => {
        html += `
        <tr data-id-a="${f.id_ubicacion_a}" data-id-b="${f.id_ubicacion_b}" data-tramo-idx="${f.tramo_indice}">
            <td class="fw-bold text-primary">T${f.tramo_indice}</td>
            <td><strong class="text-dark">${f.nombre_a}</strong></td>
            <td class="text-center text-muted">➔</td>
            <td><strong class="text-dark">${f.nombre_b}</strong></td>
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
    const distancias = [];
    let valido = true;

    rows.forEach(tr => {
        const idA = parseInt(tr.getAttribute('data-id-a')) || 0;
        const idB = parseInt(tr.getAttribute('data-id-b')) || 0;
        const inp = tr.querySelector('.input-modal-km');
        const km  = inp ? parseFloat(inp.value) : 0;

        if (!km || km <= 0) {
            valido = false;
            if (inp) inp.classList.add('is-invalid');
        } else {
            if (inp) inp.classList.remove('is-invalid');
            distancias.push({
                id_ubicacion_a: idA,
                id_ubicacion_b: idB,
                km: km
            });
        }
    });

    if (!valido || distancias.length === 0) {
        Swal.fire("Atención", "Por favor ingresa los kilómetros para todos los tramos solicitados.", "warning");
        return;
    }

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/guardarDistanciasFaltantes';

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({ distancias: distancias }));

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let res = JSON.parse(request.responseText);
                if (res.status) {
                    // Cerrar modal
                    const modalEl = document.getElementById('modalDistanciasFaltantes');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                    }

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Distancias aprendidas en memoria progresiva',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    // Re-verificar la ruta para que actualice la UI
                    verificarYCalcularRuta(_callbackDistanciasPendiente);
                } else {
                    Swal.fire("Error", res.message || "No se pudieron guardar las distancias", "error");
                }
            } catch (e) {
                Swal.fire("Error", "Error al procesar la respuesta del servidor", "error");
            }
        }
    };
}

function openModal() {
    document.querySelector('#id_envio').value = "";
    document.querySelector('#btnText').innerHTML = "Guardar Envío";
    document.querySelector('#form-envio-title').innerHTML = "Crear Solicitud de Traslado";
    document.querySelector("#formEnvio").reset();

    // Limpiar contenedor de nodos
    const cont = document.getElementById('contenedor-nodos-ruta');
    if (cont) cont.innerHTML = '';

    // Iniciar con 2 nodos por defecto (Punto de Partida y Primer Destino)
    agregarNodoRuta();
    agregarNodoRuta();

    actualizarSecuenciaNodos();
    fntSwitchView('form');
}

function saveEnvio() {
    let id_tipo_traslado = document.querySelector('#id_tipo_traslado').value;
    let id_motivo        = document.querySelector('#id_motivo') ? document.querySelector('#id_motivo').value : '';
    let id_proveedor     = document.querySelector('#id_proveedor').value;
    let fecha_tentativa_envio = document.querySelector('#fecha_tentativa_envio') ? document.querySelector('#fecha_tentativa_envio').value : '';

    serializarNodos();
    const nodosRaw = document.getElementById('nodos_json') ? document.getElementById('nodos_json').value : '[]';
    const nodos = JSON.parse(nodosRaw);

    if (!id_tipo_traslado || !id_motivo || !id_proveedor || !fecha_tentativa_envio) {
        Swal.fire("Atención", "Todos los campos marcados con (*) son obligatorios, incluyendo la Fecha/Hora Programada de Salida.", "error");
        return false;
    }

    if (nodos.length < 2) {
        Swal.fire("Atención", "Debe agregar al menos 2 puntos en el recorrido (Punto de Partida y al menos un Destino).", "warning");
        return false;
    }

    for (let i = 0; i < nodos.length; i++) {
        if (!nodos[i].id_ubicacion && !nodos[i].destino_nombre_libre) {
            Swal.fire("Atención", `El Punto #${i + 1} debe tener una ubicación seleccionada o un nombre libre.`, "warning");
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
                        document.querySelector("#formEnvio").reset();
                        Swal.fire("Envíos", objData.message || objData.msg || "Guardado exitosamente con ruta multi-nodo", "success");
                        if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                        fntSwitchView('grid');
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
                        htmlProv += `<option value="${p.id}">${p.nombre}</option>`;
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

                    document.querySelector('#id_envio').value = envio.id_envio;
                    document.querySelector('#btnText').innerHTML = "Actualizar Envío";
                    document.querySelector('#form-envio-title').innerHTML = "Editar Itinerario de Envío: " + (envio.folio || ('#' + envio.id_envio));

                    if (document.querySelector('#id_tipo_traslado')) document.querySelector('#id_tipo_traslado').value = envio.id_tipo_traslado || '';
                    if (document.querySelector('#id_motivo')) document.querySelector('#id_motivo').value = envio.id_motivo || '';
                    if (document.querySelector('#id_proveedor')) document.querySelector('#id_proveedor').value = envio.id_proveedor || '';

                    if (document.querySelector('#fecha_tentativa_envio')) document.querySelector('#fecha_tentativa_envio').value = formatDateTimeLocal(envio.fecha_tentativa_envio);
                    if (document.querySelector('#fecha_tentativa_llegada')) document.querySelector('#fecha_tentativa_llegada').value = formatDateTimeLocal(envio.fecha_tentativa_llegada);
                    if (document.querySelector('#observaciones')) document.querySelector('#observaciones').value = envio.observaciones || '';

                    // Llenar los nodos
                    const cont = document.getElementById('contenedor-nodos-ruta');
                    if (cont) cont.innerHTML = '';

                    if (nodos.length > 0) {
                        nodos.forEach(n => {
                            const isCarga = (n.tipo_nodo === 'carga' || parseInt(n.id_tipo_destino) === 5);
                            agregarNodoRuta(n, isCarga);
                        });
                    } else {
                        agregarNodoRuta({ id_ubicacion: envio.id_origen });
                        agregarNodoRuta({ id_ubicacion: envio.id_destino, destino_nombre_libre: envio.destino_nombre_libre });
                    }

                    actualizarSecuenciaNodos();
                    verificarYCalcularRuta();
                    fntSwitchView('form');
                }
            } catch (e) {
                Swal.fire("Error", "No se pudo cargar la información del envío.", "error");
            }
        }
    };
}

function fntViewEnvio(idEnvio) {
    window.location.href = base_url + '/Lgs_envios/detalle/' + idEnvio;
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
                        if (objData.status) {
                            Swal.fire("¡Envío Reabierto!", objData.msg, "success");
                            if (typeof tableEnvios !== 'undefined' && tableEnvios) tableEnvios.ajax.reload();
                        } else {
                            Swal.fire("Error", objData.msg || "No se pudo reabrir el envío", "error");
                        }
                    } catch (e) {
                        Swal.fire("Error", "Error al procesar la respuesta del servidor", "error");
                    }
                }
            };
        }
    });
}
