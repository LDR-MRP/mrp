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
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12">Creado</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12">En Revisión</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-12">En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12">Entregado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data) {
                    return `<div class="text-end">
                                <button class="btn btn-sm btn-soft-info me-1" onClick="fntViewEnvio(${data})" title="Ver / Acomodar VINs">
                                    <i class="ri-truck-line me-1"></i> Acomodo
                                </button>
                                <button class="btn btn-sm btn-soft-danger" onClick="fntDelEnvio(${data})" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
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

/** Lee los destinos del catlogo embebido en el HTML */
function getCatalogoDestinos() {
    const el = document.getElementById('catalogoDestinos');
    if (!el) return [];
    try { return JSON.parse(el.textContent); } catch { return []; }
}

/** Construye el HTML de options para el select de destino de una parada */
function buildDestinoOptions(selectedId) {
    const destinos = getCatalogoDestinos();
    let html = '<option value="">Seleccione distribuidor / destino...</option>';
    destinos.forEach(d => {
        const sel = (d.id == selectedId) ? 'selected' : '';
        const addr = d.direccion ? ` — ${d.direccion}` : '';
        html += `<option value="${d.id}" data-direccion="${d.direccion || ''}" ${sel}>${d.nombre}${addr}</option>`;
    });
    return html;
}

let _paradaCounter = 0;

/** Agrega un nuevo bloque de parada al formulario */
function agregarParadaForm(data) {
    _paradaCounter++;
    const n = _paradaCounter;
    const msg = document.getElementById('msg-sin-paradas');
    if (msg) msg.style.display = 'none';

    const cont = document.getElementById('contenedor-paradas');
    if (!cont) return;

    const div = document.createElement('div');
    div.className = 'card border shadow-sm p-3 mb-0 parada-item';
    div.setAttribute('data-n', n);
    div.innerHTML = `
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-primary me-2">Parada <span class="num-parada">${cont.querySelectorAll('.parada-item').length + 1}</span></span>
            <span class="text-muted fs-11">Define el destino y los kilómetros de este tramo</span>
            <button type="button" class="btn btn-sm btn-soft-danger ms-auto" onclick="eliminarParada(this)">
                <i class="ri-delete-bin-line"></i> Quitar
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label fs-11 text-muted mb-1">Destino (Distribuidor / Cliente)</label>
                <select class="form-select form-select-sm parada-id-destino" onchange="recalcularRutaGoogleMaps(); serializarParadas();">
                    ${buildDestinoOptions(data ? data.id_destino_cat : '')}
                </select>
                <small class="text-muted fs-10 d-block mt-1 parada-direccion-info"></small>
            </div>
            <div class="col-md-4">
                <label class="form-label fs-11 text-muted mb-1">Nombre libre / Dirección manual</label>
                <input type="text" class="form-control form-control-sm parada-nombre-libre"
                    value="${data ? (data.destino_nombre_libre || '') : ''}"
                    placeholder="Ej: Av. Juárez 100, Puebla"
                    oninput="serializarParadas()">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-11 text-muted mb-1">Km tramo <small class="text-info">(Google Maps)</small></label>
                <input type="number" class="form-control form-control-sm parada-km" min="0" step="0.1"
                    value="${data ? (data.km_tramo || 0) : 0}"
                    oninput="serializarParadas()">
            </div>
        </div>`;
    cont.appendChild(div);
    actualizarNumerosParadas();
    serializarParadas();
    recalcularRutaGoogleMaps();
}

/** Elimina una parada y renumera */
function eliminarParada(btn) {
    const item = btn.closest('.parada-item');
    if (item) item.remove();
    actualizarNumerosParadas();
    serializarParadas();
    recalcularRutaGoogleMaps();
    const cont = document.getElementById('contenedor-paradas');
    const msg  = document.getElementById('msg-sin-paradas');
    if (msg && cont && cont.querySelectorAll('.parada-item').length === 0) {
        msg.style.display = '';
        const alertTotal = document.getElementById('badge-distancia-total-container');
        if (alertTotal) alertTotal.style.setProperty('display', 'none', 'important');
    }
}

/** Reasigna los números visuales de paradas */
function actualizarNumerosParadas() {
    const items = document.querySelectorAll('#contenedor-paradas .parada-item');
    items.forEach((el, idx) => {
        const badge = el.querySelector('.num-parada');
        if (badge) badge.textContent = idx + 1;
    });
}

/** Serializa las paradas al campo oculto paradas_json */
function serializarParadas() {
    const items = document.querySelectorAll('#contenedor-paradas .parada-item');
    const result = [];
    items.forEach((el, idx) => {
        const idDestCat  = el.querySelector('.parada-id-destino') ? el.querySelector('.parada-id-destino').value : '';
        const nombreLibre= el.querySelector('.parada-nombre-libre') ? el.querySelector('.parada-nombre-libre').value.trim() : '';
        const km         = el.querySelector('.parada-km') ? parseFloat(el.querySelector('.parada-km').value) || 0 : 0;
        result.push({
            orden: idx + 1,
            id_destino_cat: idDestCat || null,
            destino_nombre_libre: nombreLibre,
            km_tramo: km
        });
    });
    const campo = document.getElementById('paradas_json');
    if (campo) campo.value = JSON.stringify(result);
}

/**
 * Recalcula distancias de ruta en tiempo real llamando a Google Maps Service
 */
function recalcularRutaGoogleMaps() {
    const idOrigen = document.getElementById('id_origen') ? parseInt(document.getElementById('id_origen').value) || 0 : 0;
    const items = document.querySelectorAll('#contenedor-paradas .parada-item');
    
    if (items.length === 0) return;

    const paradasList = [];
    items.forEach((el, idx) => {
        const idDestCat   = el.querySelector('.parada-id-destino') ? el.querySelector('.parada-id-destino').value : '';
        const nombreLibre = el.querySelector('.parada-nombre-libre') ? el.querySelector('.parada-nombre-libre').value.trim() : '';
        const kmActual    = el.querySelector('.parada-km') ? parseFloat(el.querySelector('.parada-km').value) || 0 : 0;
        
        // Actualizar subtitulo de direccion
        const selObj = el.querySelector('.parada-id-destino');
        const optSelected = selObj && selObj.selectedIndex >= 0 ? selObj.options[selObj.selectedIndex] : null;
        const dir = optSelected ? optSelected.getAttribute('data-direccion') : '';
        const infoSpan = el.querySelector('.parada-direccion-info');
        if (infoSpan) {
            infoSpan.innerHTML = dir ? `<i class="ri-map-pin-line text-danger me-1"></i>${dir}` : '';
        }

        paradasList.push({
            orden: idx + 1,
            id_destino_cat: idDestCat || null,
            destino_nombre_libre: nombreLibre,
            km_tramo: kmActual
        });
    });

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/calcularDistanciaRuta';

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({
        id_origen: idOrigen,
        paradas: paradasList
    }));

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status && objData.data) {
                    const paradasRes = objData.data.paradas || [];
                    const kmTotal    = objData.data.km_total || 0;

                    // Actualizar inputs de km_tramo en la UI
                    items.forEach((el, idx) => {
                        if (paradasRes[idx] && typeof paradasRes[idx].km_tramo !== 'undefined') {
                            const inputKm = el.querySelector('.parada-km');
                            if (inputKm) inputKm.value = paradasRes[idx].km_tramo;
                        }
                    });

                    serializarParadas();

                    // Mostrar badge total
                    const alertTotal = document.getElementById('badge-distancia-total-container');
                    const spanVal    = document.getElementById('badge-km-total-val');
                    if (alertTotal && spanVal) {
                        alertTotal.style.setProperty('display', 'flex', 'important');
                        spanVal.innerText = kmTotal.toFixed(2) + ' km (Google Maps)';
                    }
                }
            } catch (e) {
                console.error("Error al recalcular ruta: ", e);
            }
        }
    };
}

function openModal() {
    document.querySelector('#id_envio').value = "";
    document.querySelector('#btnText').innerHTML = "Guardar Envío";
    document.querySelector('#form-envio-title').innerHTML = "Crear Solicitud de Traslado";
    document.querySelector("#formEnvio").reset();
    // Limpiar paradas
    const cont = document.getElementById('contenedor-paradas');
    if (cont) cont.innerHTML = '';
    const msg = document.getElementById('msg-sin-paradas');
    if (msg) msg.style.display = '';
    serializarParadas();
    fntSwitchView('form');
}

function saveEnvio() {
    let id_tipo_traslado = document.querySelector('#id_tipo_traslado').value;
    let id_motivo = document.querySelector('#id_motivo') ? document.querySelector('#id_motivo').value : '';
    let id_proveedor = document.querySelector('#id_proveedor').value;
    let id_origen = document.querySelector('#id_origen').value;

    // Serializar paradas antes de validar
    serializarParadas();
    const paradasJson = document.getElementById('paradas_json') ? document.getElementById('paradas_json').value : '[]';
    const paradas = JSON.parse(paradasJson);

    if (id_tipo_traslado == '' || id_motivo == '' || id_proveedor == '' || id_origen == '') {
        Swal.fire("Atención", "Todos los campos marcados con (*) son obligatorios.", "error");
        return false;
    }
    if (paradas.length === 0) {
        Swal.fire("Atención", "Debe agregar al menos una parada destino en la ruta.", "warning");
        return false;
    }
    // Validar que cada parada tenga al menos nombre o destino cat
    for (let i = 0; i < paradas.length; i++) {
        if (!paradas[i].id_destino_cat && !paradas[i].destino_nombre_libre) {
            Swal.fire("Atención", `La parada ${i + 1} debe tener un destino seleccionado o un nombre libre.`, "warning");
            return false;
        }
    }

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
                        Swal.fire("Envíos", objData.message || objData.msg || "Guardado exitosamente", "success");
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
    }
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
