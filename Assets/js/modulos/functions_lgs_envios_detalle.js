let g_madrinasProveedor = [];
let g_choferesProveedor = [];
let g_envioData = null;
let modalVehiculoBs = null;

document.addEventListener('DOMContentLoaded', function () {
    const elModal = document.getElementById('modalAgregarVehiculo');
    if (elModal && typeof bootstrap !== 'undefined') {
        modalVehiculoBs = new bootstrap.Modal(elModal);
    }
    
    cargarDatosDetalle();
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

                    // 1. Mostrar nombre de la empresa trasladista y personalizar botón
                    const lblProv = document.getElementById('lbl-trasladista-nombre');
                    if (lblProv) {
                        lblProv.innerText = g_envioData.trasladista || 'Sin Trasladista Asignado';
                    }

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

    if (container) {
        const badge = container.querySelector('.badge');
        if (badge) {
            badge.innerHTML = items.length + ' / ' + cap + ' VINs';
        }
    }

    items.forEach((li, idx) => {
        li.classList.remove('border-primary');
        li.classList.add('border-success');

        const vin = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
        const numSerie = li.getAttribute('data-num-serie') || '';
        const modelo = li.getAttribute('data-modelo') || 'Unidad';
        const origen = li.getAttribute('data-origen') || 'Origen';
        const destino = li.getAttribute('data-destino') || 'Destino';

        const posIndex = idx + 1;
        let badgeSecuencia = (posIndex === 1)
            ? `<span class="badge bg-success px-2 py-1 fs-11 me-1"><i class="ri-number-1 me-1"></i>1º EN CARGAR</span>`
            : `<span class="badge bg-info px-2 py-1 fs-11 me-1"><i class="ri-truck-line me-1"></i>${posIndex}º EN CARGAR</span>`;

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
                <p class="text-dark mb-0 fs-11 fw-semibold">
                    <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                    <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                    <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
                </p>
                <small class="text-muted fs-11">N/S: ${numSerie || 'N/A'}</small>
            </div>
        </div>`;
    });
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
            if (idUnidad) {
                asignaciones.push({
                    id_unidad: idUnidad,
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
                        Swal.fire("¡Acomodo Guardado!", `Las unidades han sido asignadas correctamente. Costo total estimado del envío: ${costoTxt}`, "success");
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
