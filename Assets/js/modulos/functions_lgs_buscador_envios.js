/**
 * functions_lgs_buscador_envios.js
 * Buscador Avanzado de Envíos y Planeaciones para Logística MRP
 * Soporta búsqueda ágil por Folio, Planeación, VIN, Trasladista o Ruta.
 */

let catalogoEnviosBuscador = [];
let buscadorEnvioContexto = 'incidencia'; // 'incidencia' o 'gasto_independiente'

/**
 * Abre el modal del buscador de envíos
 * @param {string} contexto 'incidencia' | 'gasto_independiente'
 */
function abrirBuscadorEnvios(contexto = 'incidencia') {
    buscadorEnvioContexto = contexto;
    
    // Abrir modal con Bootstrap 5
    const modalEl = document.getElementById('modalBuscadorEnvios');
    if (!modalEl) return;
    
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();

    // Enfocar input de búsqueda tras abrir
    setTimeout(() => {
        const input = document.getElementById('inputBuscarEnvioModal');
        if (input) {
            input.focus();
            input.select();
        }
    }, 400);

    // Si aún no hemos cargado registros, recargar desde el servidor
    if (catalogoEnviosBuscador.length === 0) {
        recargarEnviosServidor();
    } else {
        filtrarEnviosEnMemoria();
    }
}

/**
 * Consulta envíos elegibles en el servidor con filtros multi-parámetro
 */
function recargarEnviosServidor() {
    const spinner = document.getElementById('spinnerCargandoEnvios');
    if (spinner) spinner.classList.remove('d-none');

    const q = (document.getElementById('inputBuscarEnvioModal')?.value || '').trim();
    const idEstado = document.getElementById('modalFiltroEstadoEnvio')?.value || '';
    const idProveedor = document.getElementById('modalFiltroProveedorEnvio')?.value || '';
    const folioPlaneacion = (document.getElementById('modalFiltroPlaneacion')?.value || '').trim();
    const vin = (document.getElementById('modalFiltroVin')?.value || '').trim();

    // Actualizar badge de filtros activos
    actualizarBadgeFiltros(idEstado, idProveedor, folioPlaneacion, vin);

    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (idEstado) params.append('id_estado', idEstado);
    if (idProveedor) params.append('id_proveedor', idProveedor);
    if (folioPlaneacion) params.append('folio_planeacion', folioPlaneacion);
    if (vin) params.append('vin', vin);

    fetch(`${base_url}/Lgs_incidencias/getEnviosElegibles?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (spinner) spinner.classList.add('d-none');
            if (res.status && Array.isArray(res.data)) {
                catalogoEnviosBuscador = res.data;
                renderizarTablaBuscadorEnvios(catalogoEnviosBuscador);
            } else {
                catalogoEnviosBuscador = [];
                renderizarTablaBuscadorEnvios([]);
            }
        })
        .catch(() => {
            if (spinner) spinner.classList.add('d-none');
            catalogoEnviosBuscador = [];
            renderizarTablaBuscadorEnvios([]);
        });
}

/**
 * Filtro rápido en memoria del lado del cliente
 */
function filtrarEnviosEnMemoria() {
    const input = document.getElementById('inputBuscarEnvioModal');
    if (!input) return;
    const term = input.value.trim().toLowerCase();

    if (!term) {
        renderizarTablaBuscadorEnvios(catalogoEnviosBuscador);
        return;
    }

    const filtrados = catalogoEnviosBuscador.filter(e => {
        if (e.folio && e.folio.toLowerCase().includes(term)) return true;
        if (e.folio_planeacion && e.folio_planeacion.toLowerCase().includes(term)) return true;
        if (e.planeacion_desc && e.planeacion_desc.toLowerCase().includes(term)) return true;
        if (e.trasladista && e.trasladista.toLowerCase().includes(term)) return true;
        if (e.origen && e.origen.toLowerCase().includes(term)) return true;
        if (e.destino && e.destino.toLowerCase().includes(term)) return true;
        if (e.vins_resumen && e.vins_resumen.toLowerCase().includes(term)) return true;
        return false;
    });

    renderizarTablaBuscadorEnvios(filtrados);
}

/**
 * Dibuja las filas de envíos en la tabla del modal
 */
function renderizarTablaBuscadorEnvios(lista) {
    const tbody = document.getElementById('tbodyBuscadorEnviosModal');
    const msgSin = document.getElementById('msgSinResultadosEnvios');
    const lblCount = document.getElementById('lblCountEnviosModal');

    if (lblCount) lblCount.textContent = lista.length;

    if (!tbody) return;
    tbody.innerHTML = '';

    if (!lista || lista.length === 0) {
        if (msgSin) msgSin.classList.remove('d-none');
        return;
    }

    if (msgSin) msgSin.classList.add('d-none');

    let html = '';
    lista.forEach(e => {
        let idEstado = parseInt(e.id_estado);
        let estTexto = 'Aprobado';
        let estBadge = 'bg-soft-success text-success';
        if (idEstado === 6) {
            estTexto = 'En Tránsito';
            estBadge = 'bg-soft-primary text-primary';
        } else if (idEstado === 7) {
            estTexto = 'Entregado';
            estBadge = 'bg-soft-secondary text-secondary';
        }

        let planHtml = e.id_planeacion && parseInt(e.id_planeacion) > 0
            ? `<span class="badge bg-soft-info text-info fs-12"><i class="ri-calendar-todo-line me-1"></i>${escapeHtmlBuscador(e.folio_planeacion || 'PLN-' + e.id_planeacion)}</span>`
            : `<span class="badge bg-light text-muted border fs-11">Sin Planeación</span>`;

        // Tooltip o desglose de VINs
        let totalVins = parseInt(e.total_vins || 0);
        let vinsHtml = `<span class="badge bg-primary-subtle text-primary fs-12 mb-1">${totalVins} VIN(s)</span>`;
        if (e.vins_resumen) {
            let vinsSnippet = e.vins_resumen.length > 35 ? e.vins_resumen.substring(0, 35) + '...' : e.vins_resumen;
            vinsHtml += `<div class="fs-11 font-monospace text-muted text-truncate" style="max-width: 220px;" title="${escapeHtmlBuscador(e.vins_resumen)}">${escapeHtmlBuscador(vinsSnippet)}</div>`;
        }

        let fechaTexto = e.fecha_salida_real || e.fecha_tentativa_envio || '-';
        if (fechaTexto !== '-' && fechaTexto.length >= 10) {
            fechaTexto = fechaTexto.substring(0, 10);
        }

        html += `
            <tr>
                <td>
                    <strong class="text-dark fs-13 d-block">${escapeHtmlBuscador(e.folio)}</strong>
                    <span class="badge ${estBadge} fs-11">${estTexto}</span>
                </td>
                <td>
                    ${planHtml}
                    ${e.planeacion_desc ? `<div class="fs-11 text-muted text-truncate" style="max-width: 140px;" title="${escapeHtmlBuscador(e.planeacion_desc)}">${escapeHtmlBuscador(e.planeacion_desc)}</div>` : ''}
                </td>
                <td>
                    <div class="fs-12 text-dark">
                        <i class="ri-map-pin-line text-danger me-1"></i>${escapeHtmlBuscador(e.origen)} 
                        <span class="text-muted mx-1">➔</span> 
                        <i class="ri-flag-line text-success me-1"></i>${escapeHtmlBuscador(e.destino)}
                    </div>
                    ${e.km_total ? `<small class="text-muted fs-11">${e.km_total} km</small>` : ''}
                </td>
                <td>
                    <span class="fw-medium text-dark fs-12">${escapeHtmlBuscador(e.trasladista)}</span>
                </td>
                <td>
                    ${vinsHtml}
                </td>
                <td>
                    <small class="text-muted fs-12">${escapeHtmlBuscador(fechaTexto)}</small>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="seleccionarEnvioDesdeBuscador(${e.id_envio});">
                        <i class="ri-check-line me-1"></i> Seleccionar
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

/**
 * Acción al presionar 'Seleccionar' en una fila del buscador
 */
function seleccionarEnvioDesdeBuscador(idEnvio) {
    idEnvio = parseInt(idEnvio);
    const envio = catalogoEnviosBuscador.find(x => parseInt(x.id_envio) === idEnvio);

    if (!envio) {
        // Si no está en memoria, consultar y seleccionar
        cargarYSeleccionarEnvioPorId(idEnvio, buscadorEnvioContexto);
        return;
    }

    let idEstado = parseInt(envio.id_estado);
    let estTexto = idEstado === 3 ? 'Aprobado' : (idEstado === 6 ? 'En Tránsito' : 'Entregado');
    let estBadge = idEstado === 3 ? 'bg-soft-success text-success' : (idEstado === 6 ? 'bg-soft-primary text-primary' : 'bg-soft-secondary text-secondary');

    if (buscadorEnvioContexto === 'incidencia') {
        // Asignar al formulario de Incidencia
        document.getElementById('id_envio').value = envio.id_envio;
        document.getElementById('txtEnvioFolio').textContent = envio.folio;
        
        const planEl = document.getElementById('txtEnvioPlaneacion');
        if (planEl) {
            planEl.innerHTML = `<i class="ri-calendar-todo-line me-1"></i>Plan: ${escapeHtmlBuscador(envio.folio_planeacion || 'Sin Planeación')}`;
        }

        const estEl = document.getElementById('txtEnvioEstado');
        if (estEl) {
            estEl.textContent = estTexto;
            estEl.className = `badge ${estBadge} fs-12`;
        }

        document.getElementById('txtEnvioRuta').textContent = `${envio.origen} ➔ ${envio.destino}`;
        document.getElementById('txtEnvioTrasladista').textContent = envio.trasladista;
        document.getElementById('txtEnvioTotalVins').textContent = `${envio.total_vins} unidad(es)`;
        
        const vinsResEl = document.getElementById('txtEnvioVinsResumen');
        if (vinsResEl) {
            vinsResEl.textContent = envio.vins_resumen || 'Sin VINs especificados';
        }

        // Alternar visualización de estado vacío vs seleccionado
        const boxVacio = document.getElementById('boxEnvioVacio');
        const boxInfo = document.getElementById('infoEnvioSeleccionado');
        if (boxVacio) boxVacio.classList.add('d-none');
        if (boxInfo) boxInfo.classList.remove('d-none');

        // Cargar los checkboxes de VINs afectados
        if (typeof cargarVinsDeEnvio === 'function') {
            cargarVinsDeEnvio();
        }

    } else if (buscadorEnvioContexto === 'gasto_independiente') {
        // Asignar al formulario de Gasto Adicional Independiente
        document.getElementById('id_envio_indep').value = envio.id_envio;
        document.getElementById('txtEnvioIndepFolio').textContent = envio.folio;
        
        const planEl = document.getElementById('txtEnvioIndepPlaneacion');
        if (planEl) {
            planEl.textContent = `Plan: ${envio.folio_planeacion || 'Sin Planeación'}`;
        }

        const estEl = document.getElementById('txtEnvioIndepEstado');
        if (estEl) {
            estEl.textContent = estTexto;
            estEl.className = `badge ${estBadge}`;
        }

        document.getElementById('txtEnvioIndepRuta').textContent = `${envio.origen} ➔ ${envio.destino}`;
        document.getElementById('txtEnvioIndepTrasladista').textContent = envio.trasladista;
        document.getElementById('txtEnvioIndepTotalVins').textContent = `${envio.total_vins} unidades`;

        const boxVacio = document.getElementById('boxEnvioIndepVacio');
        const boxInfo = document.getElementById('infoEnvioIndepSeleccionado');
        if (boxVacio) boxVacio.classList.add('d-none');
        if (boxInfo) boxInfo.classList.remove('d-none');

        // Cargar unidades para el prorrateo del gasto
        if (typeof cargarVinsParaGasto === 'function') {
            cargarVinsParaGasto(envio.id_envio);
        }
    }

    // Cerrar modal del buscador
    const modalEl = document.getElementById('modalBuscadorEnvios');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
}

/**
 * Consulta un envío específico por ID y lo selecciona automáticamente
 */
function cargarYSeleccionarEnvioPorId(idEnvio, contexto = 'incidencia') {
    buscadorEnvioContexto = contexto;
    fetch(`${base_url}/Lgs_incidencias/getEnviosElegibles?q=${idEnvio}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && Array.isArray(res.data) && res.data.length > 0) {
                const encontrado = res.data.find(x => parseInt(x.id_envio) === parseInt(idEnvio)) || res.data[0];
                if (!catalogoEnviosBuscador.some(x => parseInt(x.id_envio) === parseInt(encontrado.id_envio))) {
                    catalogoEnviosBuscador.unshift(encontrado);
                }
                seleccionarEnvioDesdeBuscador(encontrado.id_envio);
            }
        });
}

function limpiarBuscadorEnviosRapido() {
    const input = document.getElementById('inputBuscarEnvioModal');
    if (input) {
        input.value = '';
        input.focus();
    }
    filtrarEnviosEnMemoria();
}

function limpiarTodosFiltrosModal() {
    const elEstado = document.getElementById('modalFiltroEstadoEnvio');
    const elProv = document.getElementById('modalFiltroProveedorEnvio');
    const elPlan = document.getElementById('modalFiltroPlaneacion');
    const elVin = document.getElementById('modalFiltroVin');

    if (elEstado) elEstado.value = '';
    if (elProv) elProv.value = '';
    if (elPlan) elPlan.value = '';
    if (elVin) elVin.value = '';

    const input = document.getElementById('inputBuscarEnvioModal');
    if (input) input.value = '';

    actualizarBadgeFiltros('', '', '', '');
    recargarEnviosServidor();
}

function actualizarBadgeFiltros(idEstado, idProveedor, folioPlaneacion, vin) {
    let count = 0;
    if (idEstado) count++;
    if (idProveedor) count++;
    if (folioPlaneacion) count++;
    if (vin) count++;

    const badge = document.getElementById('badgeFiltrosActivos');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

function escapeHtmlBuscador(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Control de apilamiento de modales (Stacked Modals en Bootstrap 5)
document.addEventListener('DOMContentLoaded', function () {
    const modalBuscadorEl = document.getElementById('modalBuscadorEnvios');
    if (modalBuscadorEl) {
        modalBuscadorEl.addEventListener('show.bs.modal', function () {
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    backdrops[backdrops.length - 1].style.zIndex = '1060';
                }
            }, 10);
        });

        modalBuscadorEl.addEventListener('hidden.bs.modal', function () {
            setTimeout(() => {
                const openModals = document.querySelectorAll('.modal.show');
                if (openModals.length > 0) {
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                }
            }, 100);
        });
    }
});
