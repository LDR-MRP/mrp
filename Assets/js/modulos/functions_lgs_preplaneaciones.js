/**
 * functions_lgs_preplaneaciones.js
 * Módulo de Preplaneación para Planta y Confirmación Preventiva de Unidades — Logística MRP
 */

let tablePreplaneaciones;
let catalogoVinsDisponibles = [];
let catalogoVinsByVin = new Map();
let vinsSeleccionadosPreplanMap = new Map();
let ultimosFiltradosPreplan = [];
let preplaneacionActualDetalle = null;

document.addEventListener('DOMContentLoaded', function () {
    initDataTablePreplaneaciones();
});

/**
 * Inicializa la tabla principal de Preplaneaciones
 */
function initDataTablePreplaneaciones() {
    tablePreplaneaciones = $('#tablePreplaneaciones').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/Lgs_preplaneaciones/getPreplaneaciones",
            "dataSrc": ""
        },
        "columns": [
            {
                "data": "folio",
                "render": function (data, type, row) {
                    return `<strong class="text-dark fs-13">${escapeHtmlPre(data)}</strong>
                            <small class="text-muted d-block fs-11">${row.created_at ? row.created_at.substring(0, 10) : ''}</small>`;
                }
            },
            {
                "data": "planta_nombre",
                "render": function (data) {
                    return `<span class="badge bg-soft-info text-info fs-12 px-2 py-1"><i class="ri-building-line me-1"></i>${escapeHtmlPre(data)}</span>`;
                }
            },
            {
                "data": "fecha_tentativa_recoleccion",
                "render": function (data) {
                    if (!data) return '<span class="text-muted fs-12">-</span>';
                    return `<span class="text-dark fs-12 fw-medium"><i class="ri-time-line text-muted me-1"></i>${data.substring(0, 16)}</span>`;
                }
            },
            {
                "data": "total_vins_solicitados",
                "render": function (data) {
                    return `<span class="badge bg-light text-dark border fs-12 px-2 py-1">${data} unidad(es)</span>`;
                }
            },
            {
                "data": "total_vins_confirmados",
                "render": function (data, type, row) {
                    let solicitados = parseInt(row.total_vins_solicitados) || 0;
                    let confirmados = parseInt(data) || 0;
                    let rechazados = parseInt(row.total_vins_rechazados) || 0;
                    let estado = parseInt(row.id_estado);

                    if (estado === 1) {
                        return `<span class="badge bg-warning-subtle text-warning fs-11"><i class="ri-hourglass-line me-1"></i>En espera de Planta</span>`;
                    }

                    return `
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success fs-12 fw-bold">${confirmados} Sí</span>
                            ${rechazados > 0 ? `<span class="badge bg-danger-subtle text-danger fs-11">${rechazados} No</span>` : ''}
                        </div>
                    `;
                }
            },
            {
                "data": "id_estado",
                "render": function (data) {
                    return obtenerBadgeEstadoPreplan(data);
                }
            },
            {
                "data": "folio_planeacion_generada",
                "render": function (data, type, row) {
                    if (data) {
                        return `<a href="${base_url}/Lgs_planeaciones" class="badge bg-soft-primary text-primary fs-12 px-2 py-1" style="background-color: rgba(196, 102, 35, 0.12) !important; color: #C46623 !important;">
                                    <i class="ri-roadster-line me-1"></i>${escapeHtmlPre(data)}
                                </a>`;
                    }
                    return `<span class="text-muted fs-11">Pendiente</span>`;
                }
            },
            {
                "data": "id_preplaneacion",
                "render": function (data, type, row) {
                    let estado = parseInt(row.id_estado);
                    let confirmados = parseInt(row.total_vins_confirmados) || 0;

                    let btnConfirmar = '';
                    if (estado === 1) {
                        btnConfirmar = `<button class="btn btn-sm btn-soft-success rounded-pill px-2 py-1 fw-semibold me-1" onclick="fntAbrirConfirmacion(${data});" title="Registrar Confirmación de Planta">
                                            <i class="ri-phone-check-line me-1"></i> Confirmar
                                        </button>`;
                    } else if (estado === 2) {
                        btnConfirmar = `<button class="btn btn-sm btn-soft-secondary rounded-pill px-2 py-1 fs-11 me-1" onclick="fntAbrirConfirmacion(${data});" title="Editar Confirmación">
                                            <i class="ri-edit-line"></i> Reajustar
                                        </button>`;
                    }

                    let btnConvertir = '';
                    if (estado === 2 && confirmados > 0) {
                        btnConvertir = `<button class="btn btn-sm btn-primary rounded-pill px-2 py-1 fw-bold me-1 shadow-xs" onclick="fntConvertirAPlaneacion(${data}, '${escapeHtmlPre(row.folio)}');" style="background-color: #0284C7; border-color: #0284C7;" title="Generar Planeación Definitiva">
                                            <i class="ri-magic-line me-1"></i> Crear Plan
                                        </button>`;
                    }

                    let btnVer = `<button class="btn btn-sm btn-light border rounded-circle p-1" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" onclick="fntVerDetalle(${data});" title="Ver Detalle">
                                    <i class="ri-eye-line text-dark"></i>
                                  </button>`;

                    return `<div class="d-flex align-items-center justify-content-end">${btnConfirmar}${btnConvertir}${btnVer}</div>`;
                }
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function (settings) {
            actualizarKpisPreplan(settings.json || []);
        }
    });
}

/**
 * Retorna el badge de estado de la preplaneación
 */
function obtenerBadgeEstadoPreplan(estado) {
    estado = parseInt(estado);
    switch (estado) {
        case 1:
            return '<span class="badge bg-warning text-dark fs-11 fw-semibold"><i class="ri-time-line me-1"></i>En Espera de Planta</span>';
        case 2:
            return '<span class="badge bg-success fs-11 fw-semibold"><i class="ri-checkbox-circle-line me-1"></i>Confirmada</span>';
        case 3:
            return '<span class="badge text-white fs-11 fw-semibold" style="background-color: #C46623;"><i class="ri-check-double-line me-1"></i>Convertida a Plan</span>';
        case 4:
            return '<span class="badge bg-secondary fs-11">Cancelada</span>';
        default:
            return '<span class="badge bg-light text-muted fs-11">Desconocido</span>';
    }
}

/**
 * Actualiza los contadores KPIs superiores
 */
function actualizarKpisPreplan(lista) {
    if (!Array.isArray(lista)) return;

    let total = lista.length;
    let espera = lista.filter(x => parseInt(x.id_estado) === 1).length;
    let confirmadas = lista.filter(x => parseInt(x.id_estado) === 2).length;
    let convertidas = lista.filter(x => parseInt(x.id_estado) === 3).length;

    const elTotal = document.getElementById('kpiTotalPreplan');
    const elEspera = document.getElementById('kpiEnEspera');
    const elConf = document.getElementById('kpiConfirmadas');
    const elConv = document.getElementById('kpiConvertidas');

    if (elTotal) elTotal.innerText = total;
    if (elEspera) elEspera.innerText = espera;
    if (elConf) elConf.innerText = confirmadas;
    if (elConv) elConv.innerText = convertidas;
}

/**
 * Filtro por buscador de texto en preplaneaciones
 */
function filtrarTablaPreplan(term) {
    if (typeof tablePreplaneaciones !== "undefined" && tablePreplaneaciones) {
        tablePreplaneaciones.search(term).draw();
    }
}

/**
 * Filtra el DataTable por estado usando los chips de acceso rápido
 */
function filtrarPreplanPorChip(filtro, btn) {
    document.querySelectorAll('.chip-preplan-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.replace('btn-secondary', 'btn-outline-secondary');
        b.classList.replace('btn-warning', 'btn-outline-warning');
        b.classList.replace('btn-success', 'btn-outline-success');
        b.classList.replace('btn-primary', 'btn-outline-primary');
    });
    
    if (btn) {
        btn.classList.add('active');
        if (btn.classList.contains('btn-outline-secondary')) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        if (btn.classList.contains('btn-outline-warning')) btn.classList.replace('btn-outline-warning', 'btn-warning');
        if (btn.classList.contains('btn-outline-success')) btn.classList.replace('btn-outline-success', 'btn-success');
        if (btn.classList.contains('btn-outline-primary')) btn.classList.replace('btn-outline-primary', 'btn-primary');
    }
    
    if (typeof tablePreplaneaciones !== "undefined" && tablePreplaneaciones) {
        tablePreplaneaciones.search(filtro).draw();
    }
}

/**
 * Permite filtrar la tabla haciendo clic en las cards superiores
 */
function filtrarPreplanPorCard(filtro, chipId) {
    const chipBtn = document.getElementById(chipId);
    filtrarPreplanPorChip(filtro, chipBtn);
    
    const tableEl = document.getElementById('tablePreplaneaciones');
    if (tableEl) {
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Crea una celda <td> con mensaje centrado para estados vacíos o de carga
 */
function renderizarMensajeTablaPreplan(tbody, texto, extraClass = 'text-muted', conSpinner = false) {
    if (!tbody) return;
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 5;
    td.className = `text-center py-4 ${extraClass}`;
    if (conSpinner) {
        const sp = document.createElement('div');
        sp.className = 'spinner-border spinner-border-sm text-primary me-2';
        sp.setAttribute('role', 'status');
        td.appendChild(sp);
    }
    td.appendChild(document.createTextNode(texto));
    tr.appendChild(td);
    tbody.replaceChildren(tr);
}

/**
 * Abre el modal para crear una nueva preplaneación
 */
function abrirModalNuevaPreplaneacion() {
    const form = document.getElementById('formNuevaPreplan');
    if (form) form.reset();

    vinsSeleccionadosPreplanMap.clear();
    ultimosFiltradosPreplan = [];

    const inpPlanta = document.getElementById('nueva_id_planta');
    if (inpPlanta) inpPlanta.value = '0';

    const lblOrigen = document.getElementById('lblOrigenInformativoPreplan');
    const boxOrigen = document.getElementById('boxOrigenInformativoPreplan');
    if (lblOrigen) lblOrigen.textContent = 'Se mostrará al buscar o seleccionar un VIN...';
    if (boxOrigen) boxOrigen.className = 'form-control bg-light border rounded-3 d-flex align-items-center text-muted fs-12';

    const checkTodos = document.getElementById('checkTodosVinsPreplan');
    if (checkTodos) checkTodos.checked = false;

    const lbl = document.getElementById('lblCountVinsSeleccionados');
    if (lbl) lbl.textContent = '0 seleccionado(s)';

    const modalEl = document.getElementById('modalNuevaPreplan');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        setTimeout(() => {
            document.getElementById('buscarVinPreplan')?.focus();
        }, 250);
    }

    cargarVinsDisponiblesParaPreplan();
}

/**
 * Consulta todos los VINs disponibles con su almacén y planta de origen (informativo)
 */
function cargarVinsDisponiblesParaPreplan() {
    const tbody = document.getElementById('tbodyVinsSeleccionPreplan');
    if (!tbody) return;

    renderizarMensajeTablaPreplan(tbody, 'Cargando unidades disponibles...', 'text-muted', true);

    fetch(`${base_url}/Lgs_preplaneaciones/getVinsDisponibles`)
        .then(res => res.json())
        .then(res => {
            if (res.status && Array.isArray(res.data) && res.data.length > 0) {
                catalogoVinsDisponibles = res.data;
                catalogoVinsByVin = new Map();
                catalogoVinsDisponibles.forEach(u => {
                    if (u.vin) catalogoVinsByVin.set(u.vin, u);
                });
                filtrarTablaVinsPreplan();
            } else {
                catalogoVinsDisponibles = [];
                catalogoVinsByVin = new Map();
                ultimosFiltradosPreplan = [];
                renderizarMensajeTablaPreplan(tbody, 'No hay unidades pendientes o terminadas disponibles.');
                actualizarResumenOrigenInformativo([]);
            }
        })
        .catch(() => {
            catalogoVinsDisponibles = [];
            catalogoVinsByVin = new Map();
            ultimosFiltradosPreplan = [];
            renderizarMensajeTablaPreplan(tbody, 'Error al cargar unidades. Intente nuevamente.', 'text-danger');
        });
}

function crearElementoBadgeOrigen(u) {
    const frag = document.createDocumentFragment();
    const cveAlm = (u.cve_almacen || '').trim();
    const almNombre = (u.almacen_nombre || '').trim();
    const plantaNombre = (u.planta_nombre || '').trim();
    const tieneAlmacenReal = cveAlm !== '' && !['SIN-ALM', 'LGS', 'PT'].includes(cveAlm);

    if (tieneAlmacenReal) {
        const line1 = document.createElement('div');
        line1.className = 'd-flex align-items-center flex-wrap gap-1';

        const badge = document.createElement('span');
        badge.className = 'badge bg-info-subtle text-info border border-info-subtle font-monospace fs-10';
        const ico = document.createElement('i');
        ico.className = 'ri-store-2-line me-1';
        badge.appendChild(ico);
        badge.appendChild(document.createTextNode(cveAlm));
        line1.appendChild(badge);

        if (plantaNombre && plantaNombre !== 'Sin planta asignada') {
            const spPlanta = document.createElement('span');
            spPlanta.className = 'text-dark fw-medium fs-11';
            spPlanta.textContent = plantaNombre;
            line1.appendChild(spPlanta);
        }
        frag.appendChild(line1);

        if (almNombre) {
            const smAlm = document.createElement('small');
            smAlm.className = 'text-muted d-block fs-10';
            smAlm.textContent = almNombre;
            frag.appendChild(smAlm);
        }
    } else {
        const badge = document.createElement('span');
        badge.className = 'badge bg-light text-secondary border fs-11';
        const ico = document.createElement('i');
        ico.className = 'ri-map-pin-line me-1';
        badge.appendChild(ico);
        badge.appendChild(document.createTextNode(u.origen_informativo || plantaNombre || 'Planta / Almacén'));
        frag.appendChild(badge);
    }

    return frag;
}

function renderizarTablaSeleccionVins(lista) {
    const tbody = document.getElementById('tbodyVinsSeleccionPreplan');
    if (!tbody) return;

    if (!Array.isArray(lista) || lista.length === 0) {
        renderizarMensajeTablaPreplan(tbody, 'No se encontraron unidades con ese filtro.');
        const checkTodos = document.getElementById('checkTodosVinsPreplan');
        if (checkTodos) checkTodos.checked = false;
        actualizarConteoVinsSeleccionados();
        return;
    }

    const LIMITE_FILAS = 150;
    const visibles = lista.slice(0, LIMITE_FILAS);
    const frag = document.createDocumentFragment();
    let todosVisiblesMarcados = visibles.length > 0;

    visibles.forEach(u => {
        const estaMarcado = vinsSeleccionadosPreplanMap.has(u.vin);
        if (!estaMarcado) todosVisiblesMarcados = false;

        const tr = document.createElement('tr');
        if (estaMarcado) tr.className = 'table-primary bg-opacity-10';

        // 1. Checkbox
        const tdCheck = document.createElement('td');
        tdCheck.className = 'text-center';
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'form-check-input check-vin-preplan';
        cb.value = String(u.id_unidad || 0);
        cb.dataset.vin = u.vin || '';
        cb.checked = estaMarcado;
        cb.addEventListener('change', function () {
            onToggleVinPreplan(u, this.checked);
        });
        tdCheck.appendChild(cb);
        tr.appendChild(tdCheck);

        // 2. VIN
        const tdVin = document.createElement('td');
        const stVin = document.createElement('strong');
        stVin.className = 'text-dark font-monospace';
        stVin.textContent = u.vin || '';
        tdVin.appendChild(stVin);
        tr.appendChild(tdVin);

        // 3. Modelo / Serie
        const tdMod = document.createElement('td');
        const spMod = document.createElement('span');
        spMod.className = 'text-secondary d-block';
        spMod.textContent = u.modelo || 'Unidad Terminada';
        const smSer = document.createElement('small');
        smSer.className = 'text-muted fs-10';
        smSer.textContent = `Serie: ${u.num_serie || '-'}`;
        tdMod.appendChild(spMod);
        tdMod.appendChild(smSer);
        tr.appendChild(tdMod);

        // 4. Almacén / Origen (Informativo)
        const tdOrigen = document.createElement('td');
        tdOrigen.appendChild(crearElementoBadgeOrigen(u));
        tr.appendChild(tdOrigen);

        // 5. Destino Sugerido
        const tdDest = document.createElement('td');
        const badgeDest = document.createElement('span');
        badgeDest.className = 'badge bg-light text-dark border fs-11';
        badgeDest.textContent = u.destino_sugerido || 'Por Asignar';
        tdDest.appendChild(badgeDest);
        tr.appendChild(tdDest);

        frag.appendChild(tr);
    });

    if (lista.length > LIMITE_FILAS) {
        const trInfo = document.createElement('tr');
        const tdInfo = document.createElement('td');
        tdInfo.colSpan = 5;
        tdInfo.className = 'text-center py-2 bg-light text-muted fs-11';
        tdInfo.textContent = `Mostrando ${LIMITE_FILAS} de ${lista.length} unidades disponibles. Busque por VIN para filtrar resultados específicos.`;
        trInfo.appendChild(tdInfo);
        frag.appendChild(trInfo);
    }

    tbody.replaceChildren(frag);

    const checkTodos = document.getElementById('checkTodosVinsPreplan');
    if (checkTodos) checkTodos.checked = todosVisiblesMarcados;

    actualizarConteoVinsSeleccionados();
}

function onToggleVinPreplan(unidad, checked) {
    if (!unidad || !unidad.vin) return;
    if (checked) {
        vinsSeleccionadosPreplanMap.set(unidad.vin, unidad);
    } else {
        vinsSeleccionadosPreplanMap.delete(unidad.vin);
    }

    const row = document.querySelector(`.check-vin-preplan[data-vin="${CSS.escape(unidad.vin)}"]`)?.closest('tr');
    if (row) {
        row.className = checked ? 'table-primary bg-opacity-10' : '';
    }

    const visibles = document.querySelectorAll('.check-vin-preplan');
    const todosMarcados = visibles.length > 0 && Array.from(visibles).every(cb => cb.checked);
    const checkTodos = document.getElementById('checkTodosVinsPreplan');
    if (checkTodos) checkTodos.checked = todosMarcados;

    actualizarConteoVinsSeleccionados();
    actualizarResumenOrigenInformativo(ultimosFiltradosPreplan);
}

function actualizarResumenOrigenInformativo(filtrados) {
    const lblOrigen = document.getElementById('lblOrigenInformativoPreplan');
    const boxOrigen = document.getElementById('boxOrigenInformativoPreplan');
    const inpPlanta = document.getElementById('nueva_id_planta');
    if (!lblOrigen || !boxOrigen || !inpPlanta) return;

    const term = (document.getElementById('buscarVinPreplan')?.value || '').trim();

    if (vinsSeleccionadosPreplanMap.size > 0) {
        const seleccionados = Array.from(vinsSeleccionadosPreplanMap.values());
        const origenesUnicos = [...new Set(
            seleccionados.map(u => (u.origen_informativo || u.planta_nombre || '').trim()).filter(Boolean)
        )];
        const primerPlanta = seleccionados.find(u => parseInt(u.id_planta, 10) > 0);
        inpPlanta.value = primerPlanta ? String(primerPlanta.id_planta) : '0';

        if (origenesUnicos.length === 1) {
            lblOrigen.textContent = origenesUnicos[0];
        } else if (origenesUnicos.length > 1) {
            lblOrigen.textContent = `${origenesUnicos[0]} (+${origenesUnicos.length - 1} más)`;
        } else {
            lblOrigen.textContent = 'Almacén / Origen identificado';
        }
        lblOrigen.title = origenesUnicos.join(' | ');
        boxOrigen.className = 'form-control bg-info-subtle bg-opacity-25 border border-info-subtle rounded-3 d-flex align-items-center text-dark fw-medium fs-12';
        return;
    }

    if (term !== '') {
        if (Array.isArray(filtrados) && filtrados.length > 0) {
            const origenesMatch = [...new Set(
                filtrados.map(u => (u.origen_informativo || u.planta_nombre || '').trim()).filter(Boolean)
            )];
            const primerPlanta = filtrados.find(u => parseInt(u.id_planta, 10) > 0);
            inpPlanta.value = (origenesMatch.length === 1 && primerPlanta) ? String(primerPlanta.id_planta) : '0';

            if (origenesMatch.length === 1) {
                lblOrigen.textContent = origenesMatch[0];
            } else {
                lblOrigen.textContent = `${origenesMatch.length} orígenes encontrados (${origenesMatch.slice(0, 2).join(' · ')})`;
            }
            lblOrigen.title = origenesMatch.join(' | ');
            boxOrigen.className = 'form-control bg-light border border-info-subtle rounded-3 d-flex align-items-center text-dark fw-medium fs-12';
        } else {
            inpPlanta.value = '0';
            lblOrigen.textContent = 'Sin coincidencias para el VIN buscado';
            lblOrigen.title = '';
            boxOrigen.className = 'form-control bg-light border rounded-3 d-flex align-items-center text-muted fs-12';
        }
        return;
    }

    inpPlanta.value = '0';
    lblOrigen.textContent = 'Se mostrará al buscar o seleccionar un VIN...';
    lblOrigen.title = '';
    boxOrigen.className = 'form-control bg-light border rounded-3 d-flex align-items-center text-muted fs-12';
}

function filtrarTablaVinsPreplan() {
    const term = (document.getElementById('buscarVinPreplan')?.value || '').trim().toLowerCase();

    let filtrados = !term
        ? [...catalogoVinsDisponibles]
        : catalogoVinsDisponibles.filter(u => {
            const hay = [
                u.vin,
                u.modelo,
                u.num_serie,
                u.planta_nombre,
                u.cve_almacen,
                u.almacen_nombre,
                u.origen_informativo
            ].filter(Boolean).join(' ').toLowerCase();
            return hay.includes(term);
        });

    filtrados.sort((a, b) => {
        const aSel = vinsSeleccionadosPreplanMap.has(a.vin) ? 0 : 1;
        const bSel = vinsSeleccionadosPreplanMap.has(b.vin) ? 0 : 1;
        return aSel - bSel;
    });

    ultimosFiltradosPreplan = filtrados;
    renderizarTablaSeleccionVins(filtrados);
    actualizarResumenOrigenInformativo(filtrados);
}

function toggleSeleccionarTodosVins(checked) {
    document.querySelectorAll('.check-vin-preplan').forEach(cb => {
        cb.checked = checked;
        const vin = cb.dataset.vin || '';
        const u = catalogoVinsByVin.get(vin);
        if (u) {
            if (checked) {
                vinsSeleccionadosPreplanMap.set(vin, u);
            } else {
                vinsSeleccionadosPreplanMap.delete(vin);
            }
        }
        const row = cb.closest('tr');
        if (row) row.className = checked ? 'table-primary bg-opacity-10' : '';
    });
    actualizarConteoVinsSeleccionados();
    actualizarResumenOrigenInformativo(ultimosFiltradosPreplan);
}

function actualizarConteoVinsSeleccionados() {
    const marcados = vinsSeleccionadosPreplanMap.size;
    const lbl = document.getElementById('lblCountVinsSeleccionados');
    if (lbl) lbl.textContent = `${marcados} seleccionado(s)`;
}

/**
 * Guarda la nueva preplaneación
 */
function guardarNuevaPreplaneacion() {
    const fecha = document.getElementById('nueva_fecha_tentativa')?.value;
    if (!fecha) {
        Swal.fire('Atención', 'Debe indicar la Fecha y Hora Tentativa de Recolección.', 'warning');
        return;
    }

    if (vinsSeleccionadosPreplanMap.size === 0) {
        Swal.fire('Atención', 'Debe seleccionar al menos un VIN para emitir el aviso de preplaneación.', 'warning');
        return;
    }

    const seleccionados = Array.from(vinsSeleccionadosPreplanMap.values());
    const vins = seleccionados.map(u => ({
        id_unidad: u.id_unidad || 0,
        vin: u.vin,
        num_serie: u.num_serie || '',
        modelo: u.modelo || 'Unidad',
        id_planta: u.id_planta || 0,
        planta_nombre: u.planta_nombre || '',
        origen_almacen: u.origen_informativo || u.planta_nombre || 'Planta / Almacén',
        destino_sugerido: u.destino_sugerido || 'Por Asignar'
    }));

    let idPlanta = parseInt(document.getElementById('nueva_id_planta')?.value || '0', 10) || 0;
    if (idPlanta <= 0) {
        const conPlanta = vins.find(v => parseInt(v.id_planta, 10) > 0);
        if (conPlanta) idPlanta = parseInt(conPlanta.id_planta, 10);
    }

    const formData = new FormData();
    formData.append('id_planta', String(idPlanta));
    formData.append('fecha_tentativa_recoleccion', fecha);
    formData.append('vins_json', JSON.stringify(vins));

    Swal.fire({
        title: 'Emitiendo Aviso a Planta...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`${base_url}/Lgs_preplaneaciones/guardar`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status) {
            Swal.fire('Preplaneación Registrada', res.message || 'Aviso generado exitosamente.', 'success');
            const modalEl = document.getElementById('modalNuevaPreplan');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            if (tablePreplaneaciones) tablePreplaneaciones.ajax.reload();
        } else {
            Swal.fire('Error', res.message || 'No se pudo registrar la preplaneación.', 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
    });
}

/**
 * Abre el modal para capturar la confirmación acordada con Planta
 */
function fntAbrirConfirmacion(idPreplan) {
    Swal.fire({
        title: 'Cargando preplaneación...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`${base_url}/Lgs_preplaneaciones/getDetalle/${idPreplan}`)
        .then(res => res.json())
        .then(res => {
            Swal.close();
            if (res.status && res.data) {
                const pre = res.data.preplaneacion;
                const vins = res.data.vins || [];

                document.getElementById('conf_id_preplaneacion').value = pre.id_preplaneacion;
                document.getElementById('subFolioPreplan').textContent = `Folio: ${pre.folio} · Almacén / Origen: ${pre.planta_nombre}`;
                document.getElementById('conf_contacto_planta').value = pre.contacto_planta || '';
                document.getElementById('conf_notas_comunicacion').value = pre.notas_comunicacion || '';

                const tbody = document.getElementById('tbodyVinsConfirmacion');
                const frag = document.createDocumentFragment();
                const motivos = [
                    { value: 'Falta de componente / Pieza faltante', label: 'Falta de componente / Pieza' },
                    { value: 'En línea de ensamble / Proceso', label: 'En línea de ensamble' },
                    { value: 'Bloqueo de Calidad / PDI pendiente', label: 'Calidad / PDI pendiente' },
                    { value: 'Retrabajo en patio', label: 'Retrabajo en patio' },
                    { value: 'Otro motivo', label: 'Otro motivo' }
                ];

                vins.forEach((v, idx) => {
                    const esDisp = (v.disponible_planta === null || parseInt(v.disponible_planta, 10) === 1);
                    const tr = document.createElement('tr');
                    tr.className = 'fila-confirmar-vin';
                    tr.dataset.id = String(v.id);
                    tr.dataset.vin = v.vin || '';

                    const tdVin = document.createElement('td');
                    const stVin = document.createElement('strong');
                    stVin.className = 'text-dark font-monospace';
                    stVin.textContent = v.vin || '';
                    tdVin.appendChild(stVin);

                    if (v.origen_almacen) {
                        const smOrg = document.createElement('small');
                        smOrg.className = 'text-info d-block fs-10';
                        smOrg.textContent = `Origen: ${v.origen_almacen}`;
                        tdVin.appendChild(smOrg);
                    }
                    if (v.destino_sugerido) {
                        const smDest = document.createElement('small');
                        smDest.className = 'text-muted d-block';
                        smDest.textContent = `Destino: ${v.destino_sugerido}`;
                        tdVin.appendChild(smDest);
                    }
                    tr.appendChild(tdVin);

                    const tdMod = document.createElement('td');
                    const spMod = document.createElement('span');
                    spMod.className = 'text-secondary';
                    spMod.textContent = v.modelo || '';
                    tdMod.appendChild(spMod);
                    tr.appendChild(tdMod);

                    const tdSw = document.createElement('td');
                    tdSw.className = 'text-center';
                    const divSw = document.createElement('div');
                    divSw.className = 'form-check form-switch d-inline-block';
                    const inpSw = document.createElement('input');
                    inpSw.className = 'form-check-input switch-disp-vin';
                    inpSw.type = 'checkbox';
                    inpSw.setAttribute('role', 'switch');
                    inpSw.id = `disp_${idx}`;
                    inpSw.checked = esDisp;
                    inpSw.addEventListener('change', function () {
                        toggleMotivoRechazoVin(idx, this.checked);
                    });
                    const lblSw = document.createElement('label');
                    lblSw.className = `form-check-label fs-11 fw-bold text-${esDisp ? 'success' : 'danger'}`;
                    lblSw.id = `lbl_disp_${idx}`;
                    lblSw.textContent = esDisp ? 'Sí Llega' : 'No Llega';
                    divSw.appendChild(inpSw);
                    divSw.appendChild(lblSw);
                    tdSw.appendChild(divSw);
                    tr.appendChild(tdSw);

                    const tdMot = document.createElement('td');
                    const selMot = document.createElement('select');
                    selMot.className = 'form-select form-select-sm select-motivo-vin';
                    selMot.id = `motivo_${idx}`;
                    selMot.style.display = esDisp ? 'none' : 'block';
                    motivos.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.value;
                        opt.textContent = m.label;
                        if (v.motivo_no_disponible === m.value) opt.selected = true;
                        selMot.appendChild(opt);
                    });
                    tdMot.appendChild(selMot);
                    tr.appendChild(tdMot);

                    frag.appendChild(tr);
                });

                tbody.replaceChildren(frag);

                const modalEl = document.getElementById('modalConfirmarPlanta');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            } else {
                Swal.fire('Error', 'No se pudo cargar la información de la preplaneación.', 'error');
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Error al comunicarse con el servidor.', 'error');
        });
}

function toggleMotivoRechazoVin(idx, isChecked) {
    const lbl = document.getElementById(`lbl_disp_${idx}`);
    const sel = document.getElementById(`motivo_${idx}`);

    if (lbl) {
        lbl.textContent = isChecked ? 'Sí Llega' : 'No Llega';
        lbl.className = `form-check-label fs-11 fw-bold text-${isChecked ? 'success' : 'danger'}`;
    }

    if (sel) {
        sel.style.display = isChecked ? 'none' : 'block';
    }
}

function marcarTodosDisponibles(disp) {
    document.querySelectorAll('.fila-confirmar-vin').forEach((fila, idx) => {
        const sw = fila.querySelector('.switch-disp-vin');
        if (sw) {
            sw.checked = disp;
            toggleMotivoRechazoVin(idx, disp);
        }
    });
}

/**
 * Guarda la confirmación de Planta
 */
function guardarConfirmacionPlanta() {
    const idPreplan = document.getElementById('conf_id_preplaneacion')?.value;
    const contacto = document.getElementById('conf_contacto_planta')?.value.trim();
    const notas = document.getElementById('conf_notas_comunicacion')?.value.trim();

    if (!contacto) {
        Swal.fire("Atención", "Debe ingresar el nombre del contacto en Planta con quien acordó la disponibilidad.", "warning");
        return;
    }

    const unidades = [];
    document.querySelectorAll('.fila-confirmar-vin').forEach((fila, idx) => {
        const idDet = fila.getAttribute('data-id');
        const vin = fila.getAttribute('data-vin');
        const sw = fila.querySelector('.switch-disp-vin');
        const sel = fila.querySelector('.select-motivo-vin');

        const esDisp = sw ? sw.checked : true;
        const motivo = (!esDisp && sel) ? sel.value : null;

        unidades.push({
            id: idDet,
            vin: vin,
            disponible: esDisp,
            motivo: motivo
        });
    });

    const formData = new FormData();
    formData.append('id_preplaneacion', idPreplan);
    formData.append('contacto_planta', contacto);
    formData.append('notas_comunicacion', notas);
    formData.append('unidades_json', JSON.stringify(unidades));

    Swal.fire({
        title: 'Guardando Confirmación...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`${base_url}/Lgs_preplaneaciones/registrarConfirmacion`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status) {
            Swal.fire("Confirmación Guardada", res.message || "La disponibilidad de unidades ha sido registrada.", "success");
            const modalEl = document.getElementById('modalConfirmarPlanta');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            if (tablePreplaneaciones) tablePreplaneaciones.ajax.reload();
        } else {
            Swal.fire("Error", res.message || "No se pudo guardar la confirmación.", "error");
        }
    })
    .catch(() => {
        Swal.fire("Error", "Error de comunicación con el servidor.", "error");
    });
}

/**
 * Convierte los VINs confirmados a Planeación Definitiva
 */
function fntConvertirAPlaneacion(idPreplan, folio) {
    Swal.fire({
        title: `¿Convertir ${folio} a Planeación Definitiva?`,
        html: `Se generará automáticamente una nueva <strong>Planeación de Embarque</strong> en el sistema de Logística utilizando <strong>únicamente los VINs confirmados como disponibles</strong> por Planta.<br><br><small class='text-muted'>Los VINs no disponibles quedarán descartados para este viaje.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0284C7',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-check-line me-1"></i> Sí, Generar Planeación',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Generando Planeación Definitiva...',
                text: 'Por favor espere.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('id_preplaneacion', idPreplan);

            fetch(`${base_url}/Lgs_preplaneaciones/convertirAPlaneacion`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status && res.data) {
                    Swal.fire({
                        title: "¡Planeación Generada!",
                        html: `Se ha creado exitosamente la planeación oficial <strong>${res.data.folio_planeacion}</strong> con <strong>${res.data.total_vins_convertidos} unidad(es)</strong> listas.<br><br>¿Desea ir a la pantalla de Planeaciones ahora para asignar transportista y ruta?`,
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonColor: '#C46623',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="ri-external-link-line me-1"></i> Ir a Mis Planeaciones',
                        cancelButtonText: 'Permanecer aquí'
                    }).then((go) => {
                        if (tablePreplaneaciones) tablePreplaneaciones.ajax.reload();
                        if (go.isConfirmed) {
                            window.location.href = `${base_url}/Lgs_planeaciones`;
                        }
                    });
                } else {
                    Swal.fire("No se pudo convertir", res.message || "Ocurrió un error al convertir la preplaneación.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de comunicación con el servidor.", "error");
            });
        }
    });
}

function crearBadgeEstadoPreplanEl(estado) {
    const span = document.createElement('span');
    const ico = document.createElement('i');
    const st = parseInt(estado, 10);
    if (st === 1) {
        span.className = 'badge bg-warning text-dark fs-11 fw-semibold';
        ico.className = 'ri-time-line me-1';
        span.appendChild(ico);
        span.appendChild(document.createTextNode('En Espera de Planta'));
    } else if (st === 2) {
        span.className = 'badge bg-success fs-11 fw-semibold';
        ico.className = 'ri-checkbox-circle-line me-1';
        span.appendChild(ico);
        span.appendChild(document.createTextNode('Confirmada'));
    } else if (st === 3) {
        span.className = 'badge text-white fs-11 fw-semibold';
        span.style.backgroundColor = '#C46623';
        ico.className = 'ri-check-double-line me-1';
        span.appendChild(ico);
        span.appendChild(document.createTextNode('Convertida a Plan'));
    } else if (st === 4) {
        span.className = 'badge bg-secondary fs-11';
        span.textContent = 'Cancelada';
    } else {
        span.className = 'badge bg-light text-muted fs-11';
        span.textContent = 'Desconocido';
    }
    return span;
}

/**
 * Muestra el modal de detalle completo de una preplaneación
 */
function fntVerDetalle(idPreplan) {
    fetch(`${base_url}/Lgs_preplaneaciones/getDetalle/${idPreplan}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && res.data) {
                const pre = res.data.preplaneacion;
                const vins = res.data.vins || [];

                document.getElementById('detFolioPreplan').textContent = `Preplaneación: ${pre.folio}`;
                document.getElementById('detPlantaFecha').textContent = `Almacén / Origen: ${pre.planta_nombre} · Fecha Tentativa: ${pre.fecha_tentativa_recoleccion}`;

                const container = document.getElementById('contentDetallePreplan');
                const frag = document.createDocumentFragment();

                // Cabecera resumen
                const rowMeta = document.createElement('div');
                rowMeta.className = 'row g-2 mb-3 fs-12 text-muted';

                const colEstado = document.createElement('div');
                colEstado.className = 'col-md-4';
                const stEst = document.createElement('strong');
                stEst.textContent = 'Estado: ';
                colEstado.appendChild(stEst);
                colEstado.appendChild(crearBadgeEstadoPreplanEl(pre.id_estado));
                rowMeta.appendChild(colEstado);

                const colContacto = document.createElement('div');
                colContacto.className = 'col-md-4';
                const stCon = document.createElement('strong');
                stCon.textContent = 'Contacto Planta: ';
                const spCon = document.createElement('span');
                spCon.className = 'text-dark';
                spCon.textContent = pre.contacto_planta || 'No registrado aún';
                colContacto.appendChild(stCon);
                colContacto.appendChild(spCon);
                rowMeta.appendChild(colContacto);

                const colCreador = document.createElement('div');
                colCreador.className = 'col-md-4';
                const stCre = document.createElement('strong');
                stCre.textContent = 'Creado por: ';
                const spCre = document.createElement('span');
                spCre.className = 'text-dark';
                spCre.textContent = pre.usuario_creador || 'Logística';
                colCreador.appendChild(stCre);
                colCreador.appendChild(spCre);
                rowMeta.appendChild(colCreador);

                frag.appendChild(rowMeta);

                if (pre.notas_comunicacion) {
                    const divNotas = document.createElement('div');
                    divNotas.className = 'p-2 mb-3 bg-light rounded-3 fs-12 text-dark';
                    const stNot = document.createElement('strong');
                    stNot.textContent = 'Notas de Comunicación: ';
                    divNotas.appendChild(stNot);
                    divNotas.appendChild(document.createTextNode(pre.notas_comunicacion));
                    frag.appendChild(divNotas);
                }

                // Tabla de unidades
                const wrapTbl = document.createElement('div');
                wrapTbl.className = 'border rounded-3 overflow-hidden';
                const tbl = document.createElement('table');
                tbl.className = 'table table-sm table-hover align-middle mb-0 fs-12';

                const thead = document.createElement('thead');
                thead.className = 'table-light';
                const trHead = document.createElement('tr');
                ['VIN', 'Modelo', 'Almacén / Origen', 'Destino Proyectado', 'Disponibilidad'].forEach(colTxt => {
                    const th = document.createElement('th');
                    th.textContent = colTxt;
                    trHead.appendChild(th);
                });
                thead.appendChild(trHead);
                tbl.appendChild(thead);

                const tbody = document.createElement('tbody');
                let confirmados = 0;

                vins.forEach(v => {
                    const tr = document.createElement('tr');

                    const tdVin = document.createElement('td');
                    const stVin = document.createElement('strong');
                    stVin.className = 'font-monospace text-dark fs-12';
                    stVin.textContent = v.vin || '';
                    tdVin.appendChild(stVin);
                    tr.appendChild(tdVin);

                    const tdMod = document.createElement('td');
                    const spMod = document.createElement('span');
                    spMod.className = 'text-secondary fs-12';
                    spMod.textContent = v.modelo || '';
                    tdMod.appendChild(spMod);
                    tr.appendChild(tdMod);

                    const tdOrg = document.createElement('td');
                    const spOrg = document.createElement('span');
                    spOrg.className = 'badge bg-info-subtle text-info border border-info-subtle fs-11';
                    const icoOrg = document.createElement('i');
                    icoOrg.className = 'ri-store-2-line me-1';
                    spOrg.appendChild(icoOrg);
                    spOrg.appendChild(document.createTextNode(v.origen_almacen || pre.planta_nombre || 'Planta / Almacén'));
                    tdOrg.appendChild(spOrg);
                    tr.appendChild(tdOrg);

                    const tdDest = document.createElement('td');
                    const spDest = document.createElement('span');
                    spDest.className = 'badge bg-light text-dark border fs-11';
                    spDest.textContent = v.destino_sugerido || 'Por Asignar';
                    tdDest.appendChild(spDest);
                    tr.appendChild(tdDest);

                    const tdDisp = document.createElement('td');
                    if (v.disponible_planta === null) {
                        const b = document.createElement('span');
                        b.className = 'badge bg-warning-subtle text-warning fs-11';
                        b.textContent = 'Pendiente de Revisión';
                        tdDisp.appendChild(b);
                    } else if (parseInt(v.disponible_planta, 10) === 1) {
                        const b = document.createElement('span');
                        b.className = 'badge bg-success-subtle text-success fs-11 fw-bold';
                        const ico = document.createElement('i');
                        ico.className = 'ri-check-line me-1';
                        b.appendChild(ico);
                        b.appendChild(document.createTextNode('Sí Llega'));
                        tdDisp.appendChild(b);
                        confirmados++;
                    } else {
                        const b = document.createElement('span');
                        b.className = 'badge bg-danger-subtle text-danger fs-11';
                        const ico = document.createElement('i');
                        ico.className = 'ri-close-line me-1';
                        b.appendChild(ico);
                        b.appendChild(document.createTextNode('No Llega'));
                        const sm = document.createElement('small');
                        sm.className = 'text-danger d-block fs-10 mt-1';
                        sm.textContent = v.motivo_no_disponible || 'No disponible';
                        tdDisp.appendChild(b);
                        tdDisp.appendChild(sm);
                    }
                    tr.appendChild(tdDisp);

                    tbody.appendChild(tr);
                });

                tbl.appendChild(tbody);
                wrapTbl.appendChild(tbl);
                frag.appendChild(wrapTbl);
                container.replaceChildren(frag);

                const btnAccion = document.getElementById('btnDetalleAccionConvertir');
                if (btnAccion) {
                    if (parseInt(pre.id_estado, 10) === 2 && confirmados > 0) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-primary rounded-pill px-4 fw-bold shadow-sm';
                        btn.style.backgroundColor = '#0284C7';
                        btn.style.borderColor = '#0284C7';
                        btn.addEventListener('click', () => fntConvertirAPlaneacion(pre.id_preplaneacion, pre.folio));
                        const ico = document.createElement('i');
                        ico.className = 'ri-magic-line me-1';
                        btn.appendChild(ico);
                        btn.appendChild(document.createTextNode(` Generar Planeación Definitiva (${confirmados} VINs)`));
                        btnAccion.replaceChildren(btn);
                    } else if (parseInt(pre.id_estado, 10) === 3) {
                        const sp = document.createElement('span');
                        sp.className = 'badge fs-12 text-white p-2';
                        sp.style.backgroundColor = '#C46623';
                        const ico = document.createElement('i');
                        ico.className = 'ri-check-double-line me-1';
                        sp.appendChild(ico);
                        sp.appendChild(document.createTextNode(`Convertida a ${pre.folio_planeacion_generada || 'Planeación'}`));
                        btnAccion.replaceChildren(sp);
                    } else {
                        btnAccion.replaceChildren();
                    }
                }

                const modalEl = document.getElementById('modalDetallePreplan');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            }
        });
}

function escapeHtmlPre(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

