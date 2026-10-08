/**
 * functions_lgs_buscador_incidencias.js
 * Buscador Avanzado de Incidencias Operativas para Asignación de Gastos Adicionales
 */

let catalogoIncidenciasBuscador = [];

/**
 * Abre el modal de búsqueda avanzada de incidencias
 */
function abrirBuscadorIncidencias() {
    const modalEl = document.getElementById('modalBuscadorIncidencias');
    if (!modalEl) return;

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInstance.show();

    // Enfocar input de búsqueda al abrir
    setTimeout(() => {
        const input = document.getElementById('inputBuscarIncidenciaModal');
        if (input) {
            input.focus();
            input.select();
        }
    }, 400);

    // Cargar del servidor si aún no hay catálogo
    if (catalogoIncidenciasBuscador.length === 0) {
        recargarIncidenciasServidor();
    } else {
        filtrarIncidenciasEnMemoria();
    }
}

/**
 * Consulta incidencias en el servidor con filtros multi-parámetro
 */
function recargarIncidenciasServidor() {
    const spinner = document.getElementById('spinnerCargandoIncidencias');
    if (spinner) spinner.classList.remove('d-none');

    const q = (document.getElementById('inputBuscarIncidenciaModal')?.value || '').trim();
    const idTipo = document.getElementById('modalFiltroTipoIncidencia')?.value || '';
    const idAbsorcion = document.getElementById('modalFiltroAbsorcionIncidencia')?.value || '';
    const idProveedor = document.getElementById('modalFiltroProveedorIncidencia')?.value || '';
    const folioPlaneacion = (document.getElementById('modalFiltroPlaneacionIncidencia')?.value || '').trim();

    // Actualizar badge de filtros activos
    actualizarBadgeFiltrosIncidencias(idTipo, idAbsorcion, idProveedor, folioPlaneacion);

    const params = new URLSearchParams();
    if (q) params.append('q', q);
    if (idTipo) params.append('id_tipo_incidencia', idTipo);
    if (idAbsorcion) params.append('id_absorcion', idAbsorcion);
    if (idProveedor) params.append('id_proveedor', idProveedor);
    if (folioPlaneacion) params.append('folio_planeacion', folioPlaneacion);

    fetch(`${base_url}/Lgs_gastosadicionales/buscarIncidencias?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (spinner) spinner.classList.add('d-none');
            if (res.status && Array.isArray(res.data)) {
                catalogoIncidenciasBuscador = res.data;
                filtrarIncidenciasEnMemoria();
            } else {
                catalogoIncidenciasBuscador = [];
                renderizarTablaIncidenciasModal([]);
            }
        })
        .catch(err => {
            console.error('Error al consultar incidencias:', err);
            if (spinner) spinner.classList.add('d-none');
            catalogoIncidenciasBuscador = [];
            renderizarTablaIncidenciasModal([]);
        });
}

function actualizarBadgeFiltrosIncidencias(tipo, abs, prov, plan) {
    let activos = 0;
    if (tipo) activos++;
    if (abs) activos++;
    if (prov) activos++;
    if (plan) activos++;

    const badge = document.getElementById('badgeFiltrosIncidenciasActivos');
    if (!badge) return;

    if (activos > 0) {
        badge.textContent = activos;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

function limpiarBuscadorIncidenciasRapido() {
    const input = document.getElementById('inputBuscarIncidenciaModal');
    if (input) {
        input.value = '';
        input.focus();
    }
    filtrarIncidenciasEnMemoria();
}

function limpiarTodosFiltrosIncidenciasModal() {
    const ids = ['inputBuscarIncidenciaModal', 'modalFiltroTipoIncidencia', 'modalFiltroAbsorcionIncidencia', 'modalFiltroProveedorIncidencia', 'modalFiltroPlaneacionIncidencia'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    recargarIncidenciasServidor();
}

/**
 * Filtra en memoria las incidencias según el término de búsqueda rápida
 */
function filtrarIncidenciasEnMemoria() {
    const q = (document.getElementById('inputBuscarIncidenciaModal')?.value || '').toLowerCase().trim();

    let resultado = catalogoIncidenciasBuscador;
    if (q) {
        resultado = catalogoIncidenciasBuscador.filter(inc => {
            const folio = (inc.folio || '').toLowerCase();
            const folioEnvio = (inc.folio_envio || '').toLowerCase();
            const folioPlan = (inc.folio_planeacion || '').toLowerCase();
            const tipo = (inc.tipo_incidencia || '').toLowerCase();
            const trasladista = (inc.trasladista || '').toLowerCase();
            const descripcion = (inc.descripcion || '').toLowerCase();
            const vins = (inc.vins_afectados_str || '').toLowerCase();
            const absorcion = (inc.absorcion_nombre || '').toLowerCase();

            return folio.includes(q) ||
                   folioEnvio.includes(q) ||
                   folioPlan.includes(q) ||
                   tipo.includes(q) ||
                   trasladista.includes(q) ||
                   descripcion.includes(q) ||
                   vins.includes(q) ||
                   absorcion.includes(q);
        });
    }

    const lbl = document.getElementById('lblCountIncidenciasModal');
    if (lbl) lbl.textContent = resultado.length;

    renderizarTablaIncidenciasModal(resultado);
}

/**
 * Renderiza los registros en la tabla del modal
 */
function renderizarTablaIncidenciasModal(lista) {
    const tbody = document.getElementById('tbodyBuscadorIncidenciasModal');
    const msgVacio = document.getElementById('msgSinResultadosIncidencias');
    if (!tbody) return;

    if (!lista || lista.length === 0) {
        tbody.innerHTML = '';
        if (msgVacio) msgVacio.classList.remove('d-none');
        return;
    }

    if (msgVacio) msgVacio.classList.add('d-none');

    let html = '';
    lista.forEach(inc => {
        let vinsCount = parseInt(inc.total_vins_afectados) || 0;
        let vinsBadge = `<span class="badge bg-light text-dark border">${vinsCount} VIN${vinsCount === 1 ? '' : 's'}</span>`;
        if (inc.vins_afectados_str) {
            vinsBadge += `<div class="fs-11 text-muted text-truncate mt-1" style="max-width: 180px;" title="${escapeHtml(inc.vins_afectados_str)}">${escapeHtml(inc.vins_afectados_str)}</div>`;
        }

        let dictamenHtml = '<span class="badge bg-soft-secondary text-secondary">Pendiente</span>';
        if (inc.absorcion_nombre) {
            dictamenHtml = `<span class="badge bg-soft-primary text-primary">${escapeHtml(inc.absorcion_nombre)}</span>`;
            if (parseFloat(inc.porcentaje_proveedor) > 0) {
                dictamenHtml += `<div class="fs-10 text-muted mt-1">${parseFloat(inc.porcentaje_proveedor)}% Prov.</div>`;
            }
        }

        html += `
            <tr>
                <td>
                    <strong class="text-danger fs-13 d-block">${escapeHtml(inc.folio)}</strong>
                    <small class="text-muted fs-11">${escapeHtml(inc.fecha_incidente || 'Sin fecha')}</small>
                </td>
                <td>
                    <span class="badge bg-soft-danger text-danger border fs-12 mb-1">${escapeHtml(inc.tipo_incidencia || 'Incidencia')}</span>
                    <div class="fs-11 text-muted text-truncate" style="max-width: 220px;" title="${escapeHtml(inc.descripcion || '')}">
                        ${escapeHtml(inc.descripcion || 'Sin descripción')}
                    </div>
                </td>
                <td>
                    <span class="badge bg-primary fs-11">${escapeHtml(inc.folio_envio || 'N/A')}</span>
                    <div class="mt-1">
                        <span class="badge bg-soft-secondary text-secondary fs-11">
                            <i class="ri-calendar-todo-line me-1"></i>${escapeHtml(inc.folio_planeacion || 'Sin Plan')}
                        </span>
                    </div>
                </td>
                <td>
                    <span class="text-truncate d-inline-block fw-medium text-dark" style="max-width: 150px;" title="${escapeHtml(inc.trasladista || '')}">
                        ${escapeHtml(inc.trasladista || 'N/A')}
                    </span>
                </td>
                <td>${vinsBadge}</td>
                <td>${dictamenHtml}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="seleccionarIncidenciaDesdeModal(${inc.id_incidencia});">
                        <i class="ri-check-line me-1"></i> Seleccionar
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

/**
 * Selecciona una incidencia desde el modal buscador y la asigna al formulario de gasto
 */
function seleccionarIncidenciaDesdeModal(idIncidencia) {
    const inc = catalogoIncidenciasBuscador.find(i => parseInt(i.id_incidencia) === parseInt(idIncidencia));
    if (!inc) {
        console.error('Incidencia no encontrada en catálogo:', idIncidencia);
        return;
    }

    aplicarIncidenciaSeleccionada(inc);

    // Cerrar modal de búsqueda
    const modalEl = document.getElementById('modalBuscadorIncidencias');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();
    }
}

/**
 * Llena la ficha de incidencia seleccionada en el formulario de gasto
 */
function aplicarIncidenciaSeleccionada(inc) {
    $('#id_incidencia').val(inc.id_incidencia);
    $('#id_incidencia').data('envio', inc.id_envio);
    $('#id_envio_incidencia').val(inc.id_envio);

    // Actualizar datos de la ficha
    $('#txtIncFolio').text(inc.folio);
    $('#txtIncTipo').text(inc.tipo_incidencia);
    $('#txtIncEnvioFolio').html(`<i class="ri-truck-line me-1"></i>${escapeHtml(inc.folio_envio || 'N/A')}`);
    $('#txtIncPlaneacionFolio').html(`<i class="ri-calendar-todo-line me-1"></i>${escapeHtml(inc.folio_planeacion || 'Sin Plan')}`);
    $('#txtIncDescripcion').text(inc.descripcion || 'Sin descripción');
    $('#txtIncTrasladista').text(inc.trasladista || 'N/A');

    let vinsCount = parseInt(inc.total_vins_afectados) || 0;
    let vinsStr = `<span class="badge bg-light text-dark border">${vinsCount} unidades</span>`;
    if (inc.vins_afectados_str) {
        vinsStr += ` <span class="text-muted fs-11">(${escapeHtml(inc.vins_afectados_str)})</span>`;
    }
    $('#txtIncVinsAfectados').html(vinsStr);

    if (inc.absorcion_nombre) {
        let dictamen = `<span class="badge bg-soft-primary text-primary">${escapeHtml(inc.absorcion_nombre)}</span>`;
        if (parseFloat(inc.porcentaje_proveedor) > 0) {
            dictamen += ` <small class="text-muted">(${parseFloat(inc.porcentaje_proveedor)}% cargo al proveedor)</small>`;
        }
        $('#txtIncDictamen').html(dictamen);
    } else {
        $('#txtIncDictamen').html('<span class="badge bg-soft-secondary text-secondary">Pendiente de Dictamen</span>');
    }

    // Mostrar ficha y ocultar estado vacío
    $('#boxIncidenciaVacia').addClass('d-none');
    $('#infoIncidenciaSeleccionada').removeClass('d-none');

    // Auto-sugerir tipo de gasto si aplica
    if (inc.id_tipo_gasto_sugerido && parseInt(inc.id_tipo_gasto_sugerido) > 0) {
        $('#id_tipo_gasto').val(inc.id_tipo_gasto_sugerido);
        if (typeof onTipoGastoChange === 'function') {
            onTipoGastoChange();
        }
    }

    // Cargar VINs del envío correspondiente
    if (typeof cargarVinsParaGasto === 'function') {
        cargarVinsParaGasto(parseInt(inc.id_envio), parseInt(inc.id_incidencia));
    }
}

/**
 * Permite preseleccionar una incidencia por folio (por ejemplo al venir de /Lgs_gastosadicionales?incidencia=INC-...)
 */
function cargarYSeleccionarIncidenciaPorFolio(folio) {
    if (!folio) return;
    fetch(`${base_url}/Lgs_gastosadicionales/buscarIncidencias?q=${encodeURIComponent(folio)}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && Array.isArray(res.data) && res.data.length > 0) {
                const inc = res.data.find(i => i.folio === folio) || res.data[0];
                aplicarIncidenciaSeleccionada(inc);
            }
        })
        .catch(err => console.error('Error preseleccionando incidencia por folio:', err));
}
