let g_madrinasProveedor = [];
let g_choferesProveedor = [];
let g_envioData = null;
let g_paradasEnvio = [];
let modalVehiculoBs = null;

document.addEventListener('DOMContentLoaded', function () {
    const elModal = document.getElementById('modalAgregarVehiculo');
    if (elModal && typeof bootstrap !== 'undefined') {
        modalVehiculoBs = new bootstrap.Modal(elModal);
    }
    
    cargarDatosDetalle();

    const inputBuscar = document.getElementById('buscar-vin-pool');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function (e) {
            const term = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll('#vins-disponibles li');
            items.forEach(item => {
                const vin = (item.getAttribute('data-vin') || '').toLowerCase();
                const numSerie = (item.getAttribute('data-num-serie') || '').toLowerCase();
                const modelo = (item.getAttribute('data-modelo') || '').toLowerCase();
                if (vin.includes(term) || numSerie.includes(term) || modelo.includes(term)) {
                    item.style.setProperty('display', '', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });
    }
});

function cargarDatosDetalle() {
    const idEnvio = document.getElementById('id_envio') ? document.getElementById('id_envio').value : 0;
    if (!idEnvio) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/getDetalleEnvioData/' + idEnvio;

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    g_envioData = objData.data.envio || {};
                    g_madrinasProveedor = objData.data.madrinas || [];
                    g_choferesProveedor = objData.data.choferes || [];
                    g_paradasEnvio = objData.data.paradas || [];

                    // 1. Mostrar nombre de la empresa trasladista y actualizar resumen
                    const lblProv = document.getElementById('lbl-trasladista-nombre');
                    if (lblProv) {
                        lblProv.innerText = g_envioData.trasladista || 'Sin Trasladista Asignado';
                    }

                    // Llenar tarjeta de resumen de ruta y KM
                    let acumRuta = 0;
                    let desgloseTramos = [];
                    (g_paradasEnvio || []).forEach(p => {
                        const kmT = parseFloat(p.km_tramo || 0);
                        acumRuta += kmT;
                        p.km_acumulado = acumRuta; // Guardar km acumulado desde el origen
                        desgloseTramos.push(`P${p.orden} (${p.destino_nombre || 'Parada'}: +${kmT.toFixed(1)}km)`);
                    });

                    const lblOrig = document.getElementById('lbl-resumen-origen');
                    const lblKm   = document.getElementById('lbl-resumen-km-total');
                    const lblPar  = document.getElementById('lbl-resumen-paradas');
                    const lblCost = document.getElementById('lbl-resumen-costo');

                    if (lblOrig) lblOrig.innerText = g_envioData.origen || '-';
                    if (lblKm)   lblKm.innerText   = (parseFloat(g_envioData.km_total || acumRuta).toFixed(1)) + ' km Total';
                    if (lblPar)  lblPar.innerHTML  = `<strong>${g_paradasEnvio ? g_paradasEnvio.length : 0} paradas</strong><small class="d-block text-muted fs-10 mt-1">${desgloseTramos.join(' ➔ ')}</small>`;
                    if (lblCost) lblCost.innerText = g_envioData.costo_total ? '$' + parseFloat(g_envioData.costo_total).toFixed(2) : '$0.00';

                    const idTipoTraslado = parseInt(g_envioData.id_tipo_traslado || 1);
                    const btnAdd = document.getElementById('btn-agregar-vehiculo');
                    if (btnAdd) {
                        if (idTipoTraslado === 1) {
                            btnAdd.innerHTML = '<i class="ri-truck-line me-1"></i> Agregar Madrina';
                        } else {
                            btnAdd.innerHTML = '<i class="ri-steering-2-line me-1"></i> Seleccionar Chofer (Rodando)';
                        }
                    }

                    // 2. Renderizar VINs Disponibles en el pool izquierdo
                    renderPoolVins(objData.data.vins || []);

                    // 3. Renderizar asignaciones existentes si las hay
                    renderAcomodoExistente(objData.data.existentes || []);

                    // 4. Inicializar Sortables
                    initSortables();
                }
            } catch (e) {
                console.error("Error al procesar datos de detalle: ", e);
            }
        }
    }
}

function renderPoolVins(vins) {
    const poolUl = document.getElementById('vins-disponibles');
    if (!poolUl) return;

    if (!Array.isArray(vins) || vins.length === 0) {
        poolUl.innerHTML = `<li class="list-group-item text-center text-muted py-4 border-0">
            <i class="ri-car-line fs-2 d-block mb-1 opacity-50"></i>
            No hay VINs disponibles en este origen.
        </li>`;
        return;
    }

    let html = '';
    vins.forEach(v => {
        const mod = v.modelo || 'Unidad';
        const orig = v.origen || 'Origen';
        const dest = v.destino || 'Destino';
        html += `
        <li class="list-group-item cursor-move shadow-sm mb-2 rounded border-start border-3 border-primary bg-white" 
            data-id-unidad="${v.id_unidad}"
            data-vin="${v.vin}"
            data-num-serie="${v.num_serie || ''}"
            data-modelo="${mod}"
            data-origen="${orig}"
            data-destino="${dest}">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <i class="ri-draggable fs-18 text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fs-13 text-primary fw-bold">${v.vin}</h6>
                        <span class="badge bg-soft-info text-info fs-11">${mod}</span>
                    </div>
                    <p class="text-dark mb-0 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${orig} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${dest}
                    </p>
                    <small class="text-muted fs-11">N/S: ${v.num_serie || 'N/A'}</small>
                </div>
            </div>
        </li>`;
    });
    poolUl.innerHTML = html;
}

function renderAcomodoExistente(existentes) {
    if (!Array.isArray(existentes) || existentes.length === 0) return;

    // Agrupar por Madrina o Chofer
    const grupos = {};
    existentes.forEach(item => {
        let key = item.id_madrina ? 'madrina_' + item.id_madrina : 'chofer_' + item.id_chofer;
        if (!grupos[key]) {
            grupos[key] = {
                id_madrina: item.id_madrina,
                id_chofer: item.id_chofer,
                nombre: item.madrina_nombre ? 'Madrina: ' + item.madrina_nombre : 'Chofer: ' + (item.chofer_nombre || 'Rodando'),
                vins: []
            };
        }
        grupos[key].vins.push(item);
    });

    Object.values(grupos).forEach(grupo => {
        if (grupo.id_madrina) {
            let m = g_madrinasProveedor.find(x => x.id_madrina == grupo.id_madrina);
            inyectarContenedorVehiculo({
                id_madrina: grupo.id_madrina,
                id_chofer: null,
                titulo: grupo.nombre + (m && m.placas ? ' (' + m.placas + ')' : ''),
                capacidad: m ? m.capacidad_vehiculos : 99
            }, grupo.vins);
        } else if (grupo.id_chofer) {
            inyectarContenedorVehiculo({
                id_madrina: null,
                id_chofer: grupo.id_chofer,
                titulo: grupo.nombre,
                capacidad: 1
            }, grupo.vins);
        }
    });
}

function initSortables() {
    // 1. Contenedor de VINs Disponibles
    const pool = document.getElementById('vins-disponibles');
    if (pool && !pool.getAttribute('data-sortable-init')) {
        pool.setAttribute('data-sortable-init', 'true');
        new Sortable(pool, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: function (evt) {
                actualizarConteoYSecuencia(evt.from);
                limpiarBadgesPool(evt.item);
            }
        });
    }

    // 2. Contenedores de Madrinas / Choferes
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    vehiculos.forEach(v => {
        if (!v.getAttribute('data-sortable-init')) {
            v.setAttribute('data-sortable-init', 'true');
            new Sortable(v, {
                group: 'shared',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onAdd: function (evt) {
                    actualizarConteoYSecuencia(evt.to);
                },
                onRemove: function (evt) {
                    actualizarConteoYSecuencia(evt.from);
                },
                onEnd: function (evt) {
                    actualizarConteoYSecuencia(evt.to);
                }
            });
        }
    });

    document.querySelectorAll('.vehiculo-list').forEach(actualizarConteoYSecuencia);
}

function actualizarConteoYSecuencia(listaUl) {
    if (!listaUl || listaUl.id === 'vins-disponibles') return;

    const container = listaUl.closest('div.border');
    const items = listaUl.querySelectorAll('li');
    const cap = listaUl.getAttribute('data-capacidad') || 99;

    // Contar cuántos VINs van a cada parada en este vehículo para detectar paradas compartidas
    const paradasCountMap = {};
    items.forEach(li => {
        const pId = li.getAttribute('data-id-parada');
        if (pId) {
            paradasCountMap[pId] = (paradasCountMap[pId] || 0) + 1;
        }
    });

    items.forEach((li, idx) => {
        li.classList.remove('border-primary');
        li.classList.add('border-success');

        const vin = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
        const numSerie = li.getAttribute('data-num-serie') || '';
        const modelo = li.getAttribute('data-modelo') || 'Unidad';
        const origen = li.getAttribute('data-origen') || 'Origen';
        const destino = li.getAttribute('data-destino') || 'Destino';
        const paradaActual = li.getAttribute('data-id-parada') || '';

        const posIndex = idx + 1;
        let badgeSecuencia = (posIndex === 1)
            ? `<span class="badge bg-success px-2 py-1 fs-11 me-1"><i class="ri-number-1 me-1"></i>1º EN CARGAR</span>`
            : `<span class="badge bg-info px-2 py-1 fs-11 me-1"><i class="ri-truck-line me-1"></i>${posIndex}º EN CARGAR</span>`;

        let paradaAutoselect = paradaActual;
        if (!paradaAutoselect && g_paradasEnvio.length > 0 && destino) {
            const destinoLower = destino.toLowerCase().trim();
            let bestMatch = null;
            let bestScore = 0;
            g_paradasEnvio.forEach(p => {
                const nombreParada = (p.destino_nombre || p.destino_nombre_libre || '').toLowerCase().trim();
                if (!nombreParada) return;
                let score = 0;
                if (nombreParada === destinoLower) score = 100;
                else if (nombreParada.includes(destinoLower) || destinoLower.includes(nombreParada)) score = 60;
                else {
                    const words = destinoLower.split(/\s+/);
                    words.forEach(w => { if (w.length > 3 && nombreParada.includes(w)) score += 20; });
                }
                if (score > bestScore) { bestScore = score; bestMatch = p; }
            });
            if (bestMatch && bestScore >= 20) paradaAutoselect = String(bestMatch.id_parada);
        }

        const objParadaSel = (g_paradasEnvio || []).find(p => String(p.id_parada) === String(paradaAutoselect));
        const kmTramo = objParadaSel ? parseFloat(objParadaSel.km_tramo || 0).toFixed(1) : null;
        const kmAcum = objParadaSel ? parseFloat(objParadaSel.km_acumulado || objParadaSel.km_tramo || 0).toFixed(1) : null;
        const isCompartida = paradaAutoselect && (paradasCountMap[paradaAutoselect] || 0) > 1;

        let opts = '<option value="">-- Sin parada --</option>';
        if (g_paradasEnvio && g_paradasEnvio.length > 0) {
            g_paradasEnvio.forEach(p => {
                const sel = (String(p.id_parada) === String(paradaAutoselect)) ? 'selected' : '';
                const matchBadge = (!paradaActual && String(p.id_parada) === String(paradaAutoselect)) ? ' 🎯' : '';
                const kmTxt = p.km_tramo ? ` (+${parseFloat(p.km_tramo).toFixed(1)}km)` : '';
                opts += `<option value="${p.id_parada}" ${sel}>Parada ${p.orden}: ${p.destino_nombre || p.destino_nombre_libre || 'Sin Nombre'}${kmTxt}${matchBadge}</option>`;
            });
        }

        li.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0 me-2">
                <i class="ri-draggable fs-18 text-muted"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="mb-0 fs-13 text-success fw-bold">VIN: ${vin}</h6>
                    <div>
                        ${badgeSecuencia}
                        <span class="badge bg-soft-secondary text-dark fs-11">${modelo}</span>
                    </div>
                </div>
                <p class="text-dark mb-1 fs-11 fw-semibold">
                    <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                    <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                    <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
                </p>
                ${ g_paradasEnvio.length > 0 ? `
                <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                    <i class="ri-route-line text-primary fs-12"></i>
                    <select class="form-select form-select-sm py-0 parada-vin-select" 
                            style="max-width:230px; font-size:11px;"
                            data-vin-key="${vin}"
                            onchange="li_setParada(this)">
                        ${opts}
                    </select>
                    <span class="badge bg-soft-info text-info border border-info fs-11 fw-bold vin-km-tramo-badge" style="${kmTramo ? '' : 'display:none;'}">
                        <i class="ri-map-pin-distance-line me-1"></i>+${kmTramo || 0} km (Total: ${kmAcum || 0} km)
                    </span>
                    ${isCompartida ? '<span class="badge bg-soft-secondary text-dark fs-10" title="Misma parada que otra unidad en este vehículo (sin duplicar kms de viaje)"><i class="ri-user-shared-line text-primary me-1"></i>Parada Compartida</span>' : ''}
                    ${paradaAutoselect && !paradaActual ? '<small class="text-muted fs-10"><i class="ri-magic-line text-info me-1"></i>Auto desde pedido</small>' : ''}
                </div>` : ''}
                <small class="text-muted fs-11">N/S: ${numSerie || 'N/A'}</small>
            </div>
        </div>`;

        if (paradaAutoselect) li.setAttribute('data-id-parada', paradaAutoselect);
        const sel = li.querySelector('.parada-vin-select');
        if (sel) sel.addEventListener('change', () => {
            li.setAttribute('data-id-parada', sel.value);
        });
    });

    // Calcular la ruta real del vehículo (suma de tramos de las paradas recorridas en orden hasta el máximo destino)
    if (container) {
        const badge = container.querySelector('.badge');
        if (badge) {
            let maxOrdenVisitado = 0;
            let paradasUnicasSet = new Set();
            items.forEach(li => {
                const pId = li.getAttribute('data-id-parada');
                const pObj = (g_paradasEnvio || []).find(p => String(p.id_parada) === String(pId));
                if (pObj) {
                    paradasUnicasSet.add(pObj.id_parada);
                    const ord = parseInt(pObj.orden || 0);
                    if (ord > maxOrdenVisitado) maxOrdenVisitado = ord;
                }
            });

            // Sumar los tramos de la ruta continuada hasta la parada de mayor orden visitada por este vehículo
            let kmRecorridoRealVehiculo = 0;
            (g_paradasEnvio || []).forEach(p => {
                if (parseInt(p.orden || 0) <= maxOrdenVisitado) {
                    kmRecorridoRealVehiculo += parseFloat(p.km_tramo || 0);
                }
            });

            const nParadasUnicas = paradasUnicasSet.size;
            const kmTxt = kmRecorridoRealVehiculo > 0 
                ? ` | 🛣️ ${kmRecorridoRealVehiculo.toFixed(1)} km (${nParadasUnicas} parada${nParadasUnicas > 1 ? 's' : ''} única${nParadasUnicas > 1 ? 's' : ''})` 
                : '';
            badge.innerHTML = items.length + ' / ' + cap + ' VINs' + kmTxt;
        }
    }
}

function limpiarBadgesPool(li) {
    if (!li) return;
    li.classList.remove('border-success');
    li.classList.add('border-primary');

    const vin = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
    const numSerie = li.getAttribute('data-num-serie') || '';
    const modelo = li.getAttribute('data-modelo') || 'Unidad';
    const origen = li.getAttribute('data-origen') || 'Origen';
    const destino = li.getAttribute('data-destino') || 'Destino';

    li.innerHTML = `
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-2">
            <i class="ri-draggable fs-18 text-muted"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0 fs-13 text-primary fw-bold">${vin}</h6>
                <span class="badge bg-soft-info text-info fs-11">${modelo}</span>
            </div>
            <p class="text-dark mb-0 fs-11 fw-semibold">
                <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
            </p>
            <small class="text-muted fs-11">N/S: ${numSerie || 'N/A'}</small>
        </div>
    </div>`;
}

/** Cuando el usuario cambia la parada de un VIN desde el select dentro del acomodo */
function li_setParada(selectEl) {
    const li = selectEl.closest('li');
    if (!li) return;
    const idParada = selectEl.value;
    li.setAttribute('data-id-parada', idParada);

    const objParadaSel = (g_paradasEnvio || []).find(p => String(p.id_parada) === String(idParada));
    const kmSpan = li.querySelector('.vin-km-tramo-badge');
    if (kmSpan) {
        if (objParadaSel && typeof objParadaSel.km_tramo !== 'undefined') {
            const kmTramo = parseFloat(objParadaSel.km_tramo || 0).toFixed(1);
            const kmAcum  = parseFloat(objParadaSel.km_acumulado || objParadaSel.km_tramo || 0).toFixed(1);
            kmSpan.innerHTML = `<i class="ri-map-pin-distance-line me-1"></i>+${kmTramo} km (Total: ${kmAcum} km)`;
            kmSpan.style.display = 'inline-block';
        } else {
            kmSpan.style.display = 'none';
        }
    }

    const ul = li.closest('ul');
    if (ul) actualizarConteoYSecuencia(ul);
}

function limpiarBadgesPool(li) {
    if (!li) return;
    li.classList.remove('border-success');
    li.classList.add('border-primary');

    const vin = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
    const numSerie = li.getAttribute('data-num-serie') || '';
    const modelo = li.getAttribute('data-modelo') || 'Unidad';
    const origen = li.getAttribute('data-origen') || 'Origen';
    const destino = li.getAttribute('data-destino') || 'Destino';

    li.innerHTML = `
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-2">
            <i class="ri-draggable fs-18 text-muted"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0 fs-13 text-primary fw-bold">${vin}</h6>
                <span class="badge bg-soft-info text-info fs-11">${modelo}</span>
            </div>
            <p class="text-dark mb-0 fs-11 fw-semibold">
                <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
            </p>
            <small class="text-muted fs-11">N/S: ${numSerie || 'N/A'}</small>
        </div>
    </div>`;
}

function agregarVehiculo() {
    // Llenar tabla de Madrinas en el Modal
    const tbodyM = document.getElementById('tbodyModalMadrinas');
    if (tbodyM) {
        if (g_madrinasProveedor.length === 0) {
            tbodyM.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No hay madrinas registradas para este trasladista.</td></tr>`;
        } else {
            let htmlM = '';
            g_madrinasProveedor.forEach(m => {
                htmlM += `
                <tr>
                    <td><strong class="text-primary">${m.numero_economico || 'S/N'}</strong></td>
                    <td>${m.placas || '-'} ${m.placa_caja ? '/ Caja: ' + m.placa_caja : ''}</td>
                    <td><span class="badge bg-soft-info text-info fs-12">${m.capacidad_vehiculos || 0} Vehículos</span></td>
                    <td>${m.chofer_asignado || '<span class="text-muted">Sin Chofer Asignado</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-success px-3" onclick="seleccionarMadrina(${m.id_madrina})">
                            <i class="ri-check-line me-1"></i> Asignar
                        </button>
                    </td>
                </tr>`;
            });
            tbodyM.innerHTML = htmlM;
        }
    }

    // Llenar tabla de Choferes en el Modal
    const tbodyC = document.getElementById('tbodyModalChoferes');
    if (tbodyC) {
        if (g_choferesProveedor.length === 0) {
            tbodyC.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay choferes registrados para este trasladista.</td></tr>`;
        } else {
            let htmlC = '';
            g_choferesProveedor.forEach(c => {
                htmlC += `
                <tr>
                    <td><strong class="text-dark">${c.nombre_completo}</strong></td>
                    <td>${c.num_licencia || '-'}</td>
                    <td>${c.tipo_licencia || '-'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary px-3" onclick="seleccionarChofer(${c.id_chofer})">
                            <i class="ri-check-line me-1"></i> Seleccionar (Rodando)
                        </button>
                    </td>
                </tr>`;
            });
            tbodyC.innerHTML = htmlC;
        }
    }

    // Filtrar pestañas según Tipo de Traslado (1 = Madrina, 2 = Chofer Rodando)
    const idTipoTraslado = parseInt(g_envioData ? g_envioData.id_tipo_traslado : 1);
    const tabMadrinasNav = document.getElementById('nav-tab-madrinas');
    const tabChoferesNav = document.getElementById('nav-tab-choferes');
    const linkMadrinas  = document.getElementById('link-tab-madrinas');
    const linkChoferes  = document.getElementById('link-tab-choferes');
    const paneMadrinas  = document.getElementById('tab-madrinas');
    const paneChoferes  = document.getElementById('tab-choferes');
    const modalTitle    = document.getElementById('modalVehiculoLabel');

    if (idTipoTraslado === 1) {
        // Es Traslado en Madrina: Mostrar solo pestaña de Madrinas
        if (tabMadrinasNav) tabMadrinasNav.style.display = 'block';
        if (tabChoferesNav) tabChoferesNav.style.display = 'none';

        if (linkMadrinas) linkMadrinas.classList.add('active');
        if (linkChoferes) linkChoferes.classList.remove('active');
        if (paneMadrinas) paneMadrinas.classList.add('show', 'active');
        if (paneChoferes) paneChoferes.classList.remove('show', 'active');

        if (modalTitle) modalTitle.innerHTML = '<i class="ri-truck-line me-2"></i> Seleccionar Madrina del Trasladista';
    } else {
        // Es Traslado por Chofer (Rodando): Mostrar solo pestaña de Choferes
        if (tabMadrinasNav) tabMadrinasNav.style.display = 'none';
        if (tabChoferesNav) tabChoferesNav.style.display = 'block';

        if (linkChoferes) linkChoferes.classList.add('active');
        if (linkMadrinas) linkMadrinas.classList.remove('active');
        if (paneChoferes) paneChoferes.classList.add('show', 'active');
        if (paneMadrinas) paneMadrinas.classList.remove('show', 'active');

        if (modalTitle) modalTitle.innerHTML = '<i class="ri-steering-2-line me-2"></i> Seleccionar Conductor (Rodando) del Trasladista';
    }

    // Abrir Modal
    const elModal = document.getElementById('modalAgregarVehiculo');
    if (elModal) {
        modalVehiculoBs = modalVehiculoBs || new bootstrap.Modal(elModal);
        modalVehiculoBs.show();
    }
}

function seleccionarMadrina(idMadrina) {
    const madrina = g_madrinasProveedor.find(m => m.id_madrina == idMadrina);
    if (!madrina) return;

    // Verificar si ya fue agregada
    if (document.querySelector(`.vehiculo-list[data-id-madrina="${idMadrina}"]`)) {
        Swal.fire("Atención", "Esta Madrina ya ha sido agregada a la lista.", "warning");
        return;
    }

    inyectarContenedorVehiculo({
        id_madrina: idMadrina,
        id_chofer: null,
        titulo: `Madrina ${madrina.numero_economico} (Placas: ${madrina.placas || 'S/P'}) - Chofer: ${madrina.chofer_asignado || 'Sin asignar'}`,
        capacidad: madrina.capacidad_vehiculos || 99
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}

function seleccionarChofer(idChofer) {
    const chofer = g_choferesProveedor.find(c => c.id_chofer == idChofer);
    if (!chofer) return;

    if (document.querySelector(`.vehiculo-list[data-id-chofer="${idChofer}"]`)) {
        Swal.fire("Atención", "Este Chofer ya ha sido agregado a la lista.", "warning");
        return;
    }

    inyectarContenedorVehiculo({
        id_madrina: null,
        id_chofer: idChofer,
        titulo: `Chofer Rodando: ${chofer.nombre_completo} (Licencia: ${chofer.num_licencia || 'S/L'})`,
        capacidad: 1
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}

function inyectarContenedorVehiculo(vehiculo, vinsIniciales = []) {
    const msgEmpty = document.getElementById('empty-vehiculos-msg');
    if (msgEmpty) msgEmpty.style.display = 'none';

    const contenedor = document.getElementById('contenedor-vehiculos');
    if (!contenedor) return;

    const divCard = document.createElement('div');
    divCard.className = 'border rounded p-3 mb-4 bg-light shadow-sm';
    
    let htmlVins = '';
    vinsIniciales.forEach((v, idx) => {
        const mod = v.modelo || 'Unidad';
        const orig = v.origen || 'Origen';
        const dest = v.destino || 'Destino';
        const posIndex = idx + 1;
        const badgeSecuencia = (posIndex === 1) 
            ? `<span class="badge bg-success px-2 py-1 fs-11 me-1"><i class="ri-number-1 me-1"></i>1º EN CARGAR</span>`
            : `<span class="badge bg-info px-2 py-1 fs-11 me-1"><i class="ri-truck-line me-1"></i>${posIndex}º EN CARGAR</span>`;

        htmlVins += `
        <li class="list-group-item cursor-move shadow-sm mb-2 rounded border-start border-3 border-success bg-white" 
            data-id-unidad="${v.id_unidad}"
            data-vin="${v.vin}"
            data-num-serie="${v.num_serie || ''}"
            data-modelo="${mod}"
            data-origen="${orig}"
            data-destino="${dest}">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <i class="ri-draggable fs-18 text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fs-13 text-success fw-bold">VIN: ${v.vin}</h6>
                        <div>
                            ${badgeSecuencia}
                            <span class="badge bg-soft-secondary text-dark fs-11">${mod}</span>
                        </div>
                    </div>
                    <p class="text-dark mb-0 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${orig} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${dest}
                    </p>
                    <small class="text-muted fs-11">N/S: ${v.num_serie || 'N/A'}</small>
                </div>
            </div>
        </li>`;
    });

    let attrMadrina = vehiculo.id_madrina ? `data-id-madrina="${vehiculo.id_madrina}"` : '';
    let attrChofer  = vehiculo.id_chofer ? `data-id-chofer="${vehiculo.id_chofer}"` : '';

    divCard.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-dark mb-0 fs-14">
                <i class="${vehiculo.id_madrina ? 'ri-truck-line text-primary' : 'ri-steering-2-line text-warning'} me-2"></i>
                ${vehiculo.titulo}
            </h6>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary rounded-pill me-2">${vinsIniciales.length} / ${vehiculo.capacidad} VINs</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerContenedorVehiculo(this)" title="Quitar Vehículo">
                    <i class="ri-close-circle-fill fs-18"></i>
                </button>
            </div>
        </div>
        <ul class="list-group sortable-list vehiculo-list" ${attrMadrina} ${attrChofer} data-capacidad="${vehiculo.capacidad}" style="min-height: 100px; border: 2px dashed #bbb; border-radius: 8px;">
            ${htmlVins}
        </ul>
    `;

    contenedor.appendChild(divCard);
    initSortables();
}

function removerContenedorVehiculo(btn) {
    const card = btn.closest('div.border');
    if (!card) return;

    // Regresar VINs al pool disponible antes de remover
    const listUl = card.querySelector('.vehiculo-list');
    if (listUl) {
        const poolUl = document.getElementById('vins-disponibles');
        const items = listUl.querySelectorAll('li');
        items.forEach(li => {
            if (poolUl) poolUl.appendChild(li);
        });
    }

    card.remove();

    const contenedor = document.getElementById('contenedor-vehiculos');
    if (contenedor && contenedor.querySelectorAll('div.border').length === 0) {
        const msgEmpty = document.getElementById('empty-vehiculos-msg');
        if (msgEmpty) msgEmpty.style.display = 'block';
    }
}

function guardarAcomodo() {
    const idEnvio = document.getElementById('id_envio') ? document.getElementById('id_envio').value : 0;
    if (!idEnvio) {
        Swal.fire("Error", "No se encontró el ID del envío.", "error");
        return;
    }

    const asignaciones = [];
    const vehiculos = document.querySelectorAll('.vehiculo-list');

    vehiculos.forEach(v => {
        const idMadrina = v.getAttribute('data-id-madrina') || null;
        const idChofer  = v.getAttribute('data-id-chofer') || null;
        const items     = v.querySelectorAll('li');

        let posicion = 1;
        items.forEach(li => {
            let idUnidad = li.getAttribute('data-id-unidad');
            let idParada = li.getAttribute('data-id-parada') || null;
            // Si no viene del atributo, intentar leerlo del select
            const sel = li.querySelector('.parada-vin-select');
            if (sel && sel.value) idParada = sel.value;

            if (idUnidad) {
                asignaciones.push({
                    id_unidad: idUnidad,
                    id_parada: idParada,
                    id_madrina: idMadrina,
                    id_chofer: idChofer,
                    posicion_acomodo: posicion
                });
                posicion++;
            }
        });
    });

    if (asignaciones.length === 0) {
        Swal.fire("Atención", "Arrastre al menos un VIN desde el pool disponible hacia una Madrina o Chofer.", "warning");
        return;
    }

    Swal.fire({
        title: 'Guardando Acomodo...',
        text: 'Por favor espere mientras se asignan los VINs y se recalculan los costos del envío.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/storeAcomodo';

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({
        id_envio: idEnvio,
        asignaciones: asignaciones
    }));

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === 'success' || objData.code === 200) {
                        let costoTxt = objData.data && objData.data.costo_total ? '$' + parseFloat(objData.data.costo_total).toFixed(2) : '$0.00';
                        Swal.fire({
                            title: "¡Acomodo Guardado!",
                            text: `Las unidades han sido asignadas correctamente. Costo total estimado del envío: ${costoTxt}`,
                            icon: "success",
                            confirmButtonText: "Aceptar y Volver a la Bandeja"
                        }).then((result) => {
                            window.location.href = base_url + '/Lgs_envios';
                        });
                    } else {
                        Swal.fire("Error", objData.message || "Error al guardar el acomodo.", "error");
                    }
                } catch (e) {
                    Swal.fire("Error", "Respuesta no válida del servidor.", "error");
                }
            } else {
                try {
                    let objData = JSON.parse(request.responseText);
                    Swal.fire("Error (" + request.status + ")", objData.message || "Error en el servidor.", "error");
                } catch (e) {
                    Swal.fire("Error (" + request.status + ")", "Error en el servidor al guardar el acomodo.", "error");
                }
            }
        }
    }
}
