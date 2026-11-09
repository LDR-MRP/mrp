/**
 * functions_lgs_preplaneaciones.js
 * Módulo de Preplaneación para Planta y Confirmación Preventiva de Unidades — Logística MRP
 */

let tablePreplaneaciones;
let catalogoVinsDisponibles = [];
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
 * Abre el modal para crear una nueva preplaneación
 */
function abrirModalNuevaPreplaneacion() {
    const form = document.getElementById('formNuevaPreplan');
    if (form) form.reset();

    const tbody = document.getElementById('tbodyVinsSeleccionPreplan');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Seleccione una Planta arriba para cargar las unidades disponibles.</td></tr>`;
    }

    const lbl = document.getElementById('lblCountVinsSeleccionados');
    if (lbl) lbl.textContent = "0 seleccionados";

    const modalEl = document.getElementById('modalNuevaPreplan');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

/**
 * Consulta los VINs disponibles para la planta seleccionada
 */
function cargarVinsDisponiblesParaPreplan() {
    const idPlanta = document.getElementById('nueva_id_planta')?.value || 0;
    const tbody = document.getElementById('tbodyVinsSeleccionPreplan');
    if (!tbody) return;

    if (!idPlanta) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Seleccione una Planta arriba para cargar las unidades disponibles.</td></tr>`;
        return;
    }

    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando unidades disponibles...</td></tr>`;

    fetch(`${base_url}/Lgs_preplaneaciones/getVinsDisponibles?id_planta=${idPlanta}`)
        .then(res => res.json())
        .then(res => {
            if (res.status && Array.isArray(res.data) && res.data.length > 0) {
                catalogoVinsDisponibles = res.data;
                renderizarTablaSeleccionVins(catalogoVinsDisponibles);
            } else {
                catalogoVinsDisponibles = [];
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No hay unidades pendientes o terminadas disponibles en esta planta.</td></tr>`;
            }
        })
        .catch(() => {
            catalogoVinsDisponibles = [];
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Error al cargar unidades. Intente nuevamente.</td></tr>`;
        });
}

function renderizarTablaSeleccionVins(lista) {
    const tbody = document.getElementById('tbodyVinsSeleccionPreplan');
    if (!tbody) return;

    if (lista.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No se encontraron unidades con ese filtro.</td></tr>`;
        return;
    }

    let html = '';
    lista.forEach(u => {
        html += `
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input check-vin-preplan" value="${u.id_unidad}" 
                           data-vin="${escapeHtmlPre(u.vin)}" data-modelo="${escapeHtmlPre(u.modelo)}" 
                           data-destino="${escapeHtmlPre(u.destino_sugerido)}" onchange="actualizarConteoVinsSeleccionados();">
                </td>
                <td><strong class="text-dark font-monospace">${escapeHtmlPre(u.vin)}</strong></td>
                <td><span class="text-secondary">${escapeHtmlPre(u.modelo)}</span></td>
                <td><small class="text-muted">${escapeHtmlPre(u.num_serie || '-')}</small></td>
                <td><span class="badge bg-light text-dark border fs-11">${escapeHtmlPre(u.destino_sugerido || 'Por Asignar')}</span></td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    actualizarConteoVinsSeleccionados();
}

function filtrarTablaVinsPreplan() {
    const term = (document.getElementById('buscarVinPreplan')?.value || '').trim().toLowerCase();
    if (!term) {
        renderizarTablaSeleccionVins(catalogoVinsDisponibles);
        return;
    }

    const filtrados = catalogoVinsDisponibles.filter(u => {
        if (u.vin && u.vin.toLowerCase().includes(term)) return true;
        if (u.modelo && u.modelo.toLowerCase().includes(term)) return true;
        if (u.num_serie && u.num_serie.toLowerCase().includes(term)) return true;
        return false;
    });

    renderizarTablaSeleccionVins(filtrados);
}

function toggleSeleccionarTodosVins(checked) {
    document.querySelectorAll('.check-vin-preplan').forEach(cb => {
        cb.checked = checked;
    });
    actualizarConteoVinsSeleccionados();
}

function actualizarConteoVinsSeleccionados() {
    const marcados = document.querySelectorAll('.check-vin-preplan:checked').length;
    const lbl = document.getElementById('lblCountVinsSeleccionados');
    if (lbl) lbl.textContent = `${marcados} seleccionado(s)`;
}

/**
 * Guarda la nueva preplaneación
 */
function guardarNuevaPreplaneacion() {
    const idPlanta = document.getElementById('nueva_id_planta')?.value;
    const fecha = document.getElementById('nueva_fecha_tentativa')?.value;

    const checkboxes = document.querySelectorAll('.check-vin-preplan:checked');
    if (checkboxes.length === 0) {
        Swal.fire("Atención", "Debe seleccionar al menos un VIN para emitir el aviso de preplaneación.", "warning");
        return;
    }

    const vins = [];
    checkboxes.forEach(cb => {
        vins.push({
            id_unidad: cb.value,
            vin: cb.getAttribute('data-vin'),
            modelo: cb.getAttribute('data-modelo'),
            destino_sugerido: cb.getAttribute('data-destino')
        });
    });

    const formData = new FormData();
    formData.append('id_planta', idPlanta);
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
            Swal.fire("Preplaneación Registrada", res.message || "Aviso generado exitosamente.", "success");
            const modalEl = document.getElementById('modalNuevaPreplan');
            if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
            if (tablePreplaneaciones) tablePreplaneaciones.ajax.reload();
        } else {
            Swal.fire("Error", res.message || "No se pudo registrar la preplaneación.", "error");
        }
    })
    .catch(() => {
        Swal.fire("Error", "Error de comunicación con el servidor.", "error");
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
                document.getElementById('subFolioPreplan').textContent = `Folio: ${pre.folio} · Planta: ${pre.planta_nombre}`;
                document.getElementById('conf_contacto_planta').value = pre.contacto_planta || '';
                document.getElementById('conf_notas_comunicacion').value = pre.notas_comunicacion || '';

                const tbody = document.getElementById('tbodyVinsConfirmacion');
                let html = '';

                vins.forEach((v, idx) => {
                    let esDisp = (v.disponible_planta === null || parseInt(v.disponible_planta) === 1);
                    html += `
                        <tr class="fila-confirmar-vin" data-id="${v.id}" data-vin="${escapeHtmlPre(v.vin)}">
                            <td>
                                <strong class="text-dark font-monospace">${escapeHtmlPre(v.vin)}</strong>
                                ${v.destino_sugerido ? `<small class="text-muted d-block">Destino: ${escapeHtmlPre(v.destino_sugerido)}</small>` : ''}
                            </td>
                            <td><span class="text-secondary">${escapeHtmlPre(v.modelo)}</span></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input switch-disp-vin" type="checkbox" role="switch" id="disp_${idx}" 
                                           ${esDisp ? 'checked' : ''} onchange="toggleMotivoRechazoVin(${idx}, this.checked);">
                                    <label class="form-check-label fs-11 fw-bold text-${esDisp ? 'success' : 'danger'}" id="lbl_disp_${idx}">
                                        ${esDisp ? 'Sí Llega' : 'No Llega'}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <select class="form-select form-select-sm select-motivo-vin" id="motivo_${idx}" style="${esDisp ? 'display: none;' : ''}">
                                    <option value="Falta de componente / Pieza faltante" ${v.motivo_no_disponible === 'Falta de componente / Pieza faltante' ? 'selected' : ''}>Falta de componente / Pieza</option>
                                    <option value="En línea de ensamble / Proceso" ${v.motivo_no_disponible === 'En línea de ensamble / Proceso' ? 'selected' : ''}>En línea de ensamble</option>
                                    <option value="Bloqueo de Calidad / PDI pendiente" ${v.motivo_no_disponible === 'Bloqueo de Calidad / PDI pendiente' ? 'selected' : ''}>Calidad / PDI pendiente</option>
                                    <option value="Retrabajo en patio" ${v.motivo_no_disponible === 'Retrabajo en patio' ? 'selected' : ''}>Retrabajo en patio</option>
                                    <option value="Otro motivo" ${v.motivo_no_disponible === 'Otro motivo' ? 'selected' : ''}>Otro motivo</option>
                                </select>
                            </td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;

                const modalEl = document.getElementById('modalConfirmarPlanta');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            } else {
                Swal.fire("Error", "No se pudo cargar la información de la preplaneación.", "error");
            }
        })
        .catch(() => {
            Swal.fire("Error", "Error al comunicarse con el servidor.", "error");
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
                document.getElementById('detPlantaFecha').textContent = `Planta: ${pre.planta_nombre} · Fecha Tentativa: ${pre.fecha_tentativa_recoleccion}`;

                let rowsHtml = '';
                let confirmados = 0;
                let rechazados = 0;

                vins.forEach(v => {
                    let badgeDisp = '';
                    if (v.disponible_planta === null) {
                        badgeDisp = '<span class="badge bg-warning-subtle text-warning fs-11">Pendiente de Revisión</span>';
                    } else if (parseInt(v.disponible_planta) === 1) {
                        badgeDisp = '<span class="badge bg-success-subtle text-success fs-11 fw-bold"><i class="ri-check-line me-1"></i>Sí Llega</span>';
                        confirmados++;
                    } else {
                        badgeDisp = `<span class="badge bg-danger-subtle text-danger fs-11"><i class="ri-close-line me-1"></i>No Llega</span>
                                     <small class="text-danger d-block fs-10 mt-1">${escapeHtmlPre(v.motivo_no_disponible || 'No disponible')}</small>`;
                        rechazados++;
                    }

                    rowsHtml += `
                        <tr>
                            <td><strong class="font-monospace text-dark fs-12">${escapeHtmlPre(v.vin)}</strong></td>
                            <td><span class="text-secondary fs-12">${escapeHtmlPre(v.modelo)}</span></td>
                            <td><span class="badge bg-light text-dark border fs-11">${escapeHtmlPre(v.destino_sugerido || 'Por Asignar')}</span></td>
                            <td>${badgeDisp}</td>
                        </tr>
                    `;
                });

                let content = `
                    <div class="row g-2 mb-3 fs-12 text-muted">
                        <div class="col-md-4">
                            <strong>Estado:</strong> ${obtenerBadgeEstadoPreplan(pre.id_estado)}
                        </div>
                        <div class="col-md-4">
                            <strong>Contacto Planta:</strong> <span class="text-dark">${escapeHtmlPre(pre.contacto_planta || 'No registrado aún')}</span>
                        </div>
                        <div class="col-md-4">
                            <strong>Creado por:</strong> <span class="text-dark">${escapeHtmlPre(pre.usuario_creador || 'Logística')}</span>
                        </div>
                    </div>
                    ${pre.notas_comunicacion ? `<div class="p-2 mb-3 bg-light rounded-3 fs-12 text-dark"><strong>Notas de Comunicación:</strong> ${escapeHtmlPre(pre.notas_comunicacion)}</div>` : ''}

                    <div class="border rounded-3 overflow-hidden">
                        <table class="table table-sm table-hover align-middle mb-0 fs-12">
                            <thead class="table-light">
                                <tr>
                                    <th>VIN</th>
                                    <th>Modelo</th>
                                    <th>Destino Proyectado</th>
                                    <th>Disponibilidad</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>
                `;

                document.getElementById('contentDetallePreplan').innerHTML = content;

                const btnAccion = document.getElementById('btnDetalleAccionConvertir');
                if (btnAccion) {
                    if (parseInt(pre.id_estado) === 2 && confirmados > 0) {
                        btnAccion.innerHTML = `<button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background-color: #0284C7; border-color: #0284C7;" onclick="fntConvertirAPlaneacion(${pre.id_preplaneacion}, '${escapeHtmlPre(pre.folio)}');">
                                                   <i class="ri-magic-line me-1"></i> Generar Planeación Definitiva (${confirmados} VINs)
                                               </button>`;
                    } else if (parseInt(pre.id_estado) === 3) {
                        btnAccion.innerHTML = `<span class="badge fs-12 text-white p-2" style="background-color: #C46623;"><i class="ri-check-double-line me-1"></i>Convertida a ${escapeHtmlPre(pre.folio_planeacion_generada || 'Planeación')}</span>`;
                    } else {
                        btnAccion.innerHTML = '';
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

