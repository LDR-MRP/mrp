function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-planeaciones");
    const secForm = document.querySelector("#view-form-planeaciones");

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

function cancelFormPlan() {
    let form = document.querySelector("#formPlan");
    if (form) form.reset();
    fntSwitchView('grid');
}

let tablePlaneaciones;

document.addEventListener('DOMContentLoaded', function () {
    tablePlaneaciones = $('#tablePlaneaciones').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_planeaciones/getPlaneaciones",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_planeacion" },
            { 
                "data": "folio",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || 'S/F') + '</span>';
                }
            },
            { 
                "data": "descripcion",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || '-') + '</span>';
                }
            },
            { 
                "data": "total_rutas",
                "render": function(data) {
                    return '<span class="badge bg-primary fs-12">' + (data || 0) + ' Envíos</span>';
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<span class="fw-bold text-success">$' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { "data": "created_at" },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12">Borrador</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12">Pendiente Aprobación</span>'; break;
                        case 3: badge = '<span class="badge bg-soft-danger text-danger fs-12">Rechazada</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-success text-success fs-12">Aprobada</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_planeacion",
                "render": function (data, type, row) {
                    let btnActionExtra = '';
                    if (parseInt(row.id_estado) === 1) {
                        btnActionExtra = `<button class="btn btn-sm btn-soft-success rounded-pill px-3 fw-semibold me-1" onClick="fntEnviarAprobacionPlan(${data})" title="Enviar a Aprobación">
                                            <i class="ri-send-plane-fill me-1"></i> Enviar
                                          </button>`;
                    } else if (parseInt(row.id_estado) === 3 || parseInt(row.id_estado) === 2) {
                        btnActionExtra = `<button class="btn btn-sm btn-soft-warning rounded-pill px-3 fw-semibold me-1" onClick="fntReabrirPlan(${data})" title="Reabrir y Desbloquear Planeación">
                                            <i class="ri-restart-line me-1"></i> Reabrir
                                          </button>`;
                    }
                    return `<div class="text-end">
                                ${btnActionExtra}
                                <button class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold" onClick="fntViewPlan(${data})" title="Ver Detalle">
                                    <i class="ri-eye-line me-1"></i> Detalle
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
            actualizarMetricasPlaneaciones(settings.json || []);
        }
    });
});

function actualizarMetricasPlaneaciones(data) {
    if (!Array.isArray(data)) return;
    
    let total = data.length;
    let pendientes = data.filter(p => parseInt(p.id_estado) === 2).length;
    let aprobadas = data.filter(p => parseInt(p.id_estado) === 5).length;
    let montoTotal = data.reduce((acc, p) => acc + (parseFloat(p.costo_total) || 0), 0);

    if (document.getElementById('cardTotalPlaneaciones')) document.getElementById('cardTotalPlaneaciones').innerText = total;
    if (document.getElementById('cardPlanPendientes')) document.getElementById('cardPlanPendientes').innerText = pendientes;
    if (document.getElementById('cardPlanAprobadas')) document.getElementById('cardPlanAprobadas').innerText = aprobadas;
    if (document.getElementById('cardPlanMontoTotal')) document.getElementById('cardPlanMontoTotal').innerText = '$' + montoTotal.toFixed(2);
}

function openModalPlan() {
    let form = document.querySelector("#formPlan");
    if (form) form.reset();
    let container = document.getElementById('containerEnviosDisponibles');
    if (container) {
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando envíos...</div>';
    }
    fntSwitchView('form');
    cargarEnviosDisponibles();
}

function cargarEnviosDisponibles() {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_planeaciones/getEnviosDisponibles';
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                htmlBody = '<div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="bg-light"><tr><th width="40"><input class="form-check-input" type="checkbox" id="checkAllEnvios" onchange="toggleAllEnvios(this);"></th><th>Folio</th><th>Origen</th><th>Trasladista</th><th>VINs</th><th>Costo Est.</th></tr></thead><tbody>';
                objData.data.forEach(envio => {
                    htmlBody += `
                        <tr>
                            <td>
                                <input class="form-check-input chk-envio" type="checkbox" value="${envio.id_envio}" data-costo="${envio.costo_total}" onchange="calcularTotalesPlan();">
                            </td>
                            <td class="fw-bold">${envio.folio}</td>
                            <td>${envio.origen}</td>
                            <td>${envio.trasladista}</td>
                            <td><span class="badge bg-soft-info text-info">${envio.total_vins} VINs</span></td>
                            <td class="fw-bold text-success">$${parseFloat(envio.costo_total).toFixed(2)}</td>
                        </tr>
                    `;
                });
                htmlBody += '</tbody></table></div>';
            } else {
                htmlBody = '<div class="text-center text-muted py-4"><i class="ri-error-warning-line fs-20 me-1"></i>No hay envíos en estado "Creado" pendientes de agrupar.</div>';
            }
            
            let container = document.getElementById('containerEnviosDisponibles');
            if (container) container.innerHTML = htmlBody;
        }
    }
}

function toggleAllEnvios(masterChk) {
    let checkboxes = document.querySelectorAll('.chk-envio');
    checkboxes.forEach(chk => chk.checked = masterChk.checked);
    calcularTotalesPlan();
}

function calcularTotalesPlan() {
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    let totalCosto = 0.0;
    
    checkboxes.forEach(chk => {
        totalCosto += parseFloat(chk.getAttribute('data-costo')) || 0;
    });
    
    let lbl = document.getElementById('lbl-monto-plan-display');
    if (lbl) lbl.innerText = '$' + totalCosto.toFixed(2);
}

function savePlaneacion() {
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    if (checkboxes.length === 0) {
        Swal.fire("Atención", "Debes seleccionar al menos un envío para crear la planeación.", "warning");
        return;
    }
    
    let idsArr = [];
    checkboxes.forEach(chk => idsArr.push(chk.value));

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_planeaciones/store';
    let formData = new FormData(document.querySelector("#formPlan"));
    formData.append('envios_ids', idsArr.join(','));

    Swal.fire({
        title: 'Enviando a Aprobación...',
        text: 'Por favor espere.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                Swal.fire("¡Planeación Creada!", objData.msg || "Planeación enviada a aprobación correctamente", "success");
                tablePlaneaciones.ajax.reload();
                fntSwitchView('grid');
            } else {
                Swal.fire("Error", objData.msg || "Error al guardar la planeación", "error");
            }
        }
    }
}

function fntViewPlan(idPlaneacion) {
    Swal.fire({
        title: 'Cargando Detalle...',
        text: 'Consultando envíos y unidades asociadas',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_planeaciones/getDetalleCompletoPlan/' + idPlaneacion;

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            Swal.close();
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status && objData.data) {
                    const plan = objData.data;

                    // 1. Cabecera y KPIs
                    document.getElementById('vdp_folio').innerText = plan.folio || ('PL-' + plan.id_planeacion);
                    document.getElementById('vdp_costo_total').innerText = '$' + (parseFloat(plan.costo_total) || 0).toFixed(2);
                    document.getElementById('vdp_km_total').innerText = (parseFloat(plan.km_total) || 0).toFixed(1);
                    document.getElementById('vdp_total_envios').innerText = (plan.envios || []).length;
                    document.getElementById('vdp_creador').innerText = plan.creador || 'Operador de Logística';
                    document.getElementById('vdp_fecha').innerText = plan.created_at || '-';
                    document.getElementById('vdp_obs_operador').innerText = plan.obs_operador || 'Sin observaciones registradas.';
                    document.getElementById('vdp_obs_aprobador').innerText = plan.obs_aprobador || 'Pendiente de dictamen por gerencia.';

                    let badgeEstado = '';
                    switch(parseInt(plan.id_estado)) {
                        case 1: badgeEstado = '<span class="badge bg-soft-secondary text-secondary fs-12"><i class="ri-draft-line me-1"></i>Borrador</span>'; break;
                        case 2: badgeEstado = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>Pendiente Aprobación</span>'; break;
                        case 3: badgeEstado = '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>Rechazada</span>'; break;
                        case 5: badgeEstado = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-checkbox-circle-line me-1"></i>Aprobada</span>'; break;
                        default: badgeEstado = '<span class="badge bg-light text-dark fs-12">Estado ' + plan.id_estado + '</span>'; break;
                    }
                    document.getElementById('vdp_estado_badge').innerHTML = badgeEstado;

                    // 2. Renderizar Envíos y Unidades
                    const contEnvios = document.getElementById('vdp_contenedor_envios');
                    contEnvios.innerHTML = '';

                    if (!plan.envios || plan.envios.length === 0) {
                        contEnvios.innerHTML = '<div class="alert alert-soft-secondary text-center py-3">No hay envíos vinculados a esta planeación.</div>';
                    } else {
                        plan.envios.forEach((env, idx) => {
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
                                            <td class="text-end fw-bold text-success">$${(parseFloat(v.costo_unidad) || 0).toFixed(2)}</td>
                                        </tr>
                                    `;
                                });
                            } else {
                                rowsVins = '<tr><td colspan="6" class="text-center text-muted py-3">Sin unidades asignadas a este envío.</td></tr>';
                            }

                            const cardEnvHtml = `
                                <div class="card border shadow-none rounded-3 mb-0">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary fs-12 fw-bold">${env.folio}</span>
                                            <span class="badge bg-soft-info text-info border">${env.tipo_traslado || 'Madrina'}</span>
                                            <span class="fs-12 text-dark fw-medium"><i class="ri-map-pin-range-line text-muted me-1"></i>${env.origen}</span>
                                            <span class="fs-12 text-muted">| ${env.trasladista || 'Sin Trasladista'}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fs-12 text-muted"><i class="ri-car-line me-1"></i>${env.total_vins} unidad(es)</span>
                                            <strong class="fs-14 text-success">$${(parseFloat(env.costo_total) || 0).toFixed(2)}</strong>
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

                            contEnvios.innerHTML += cardEnvHtml;
                        });
                    }

                    // 3. Botones dinámicos del Footer del Modal
                    const footerActions = document.getElementById('vdp_modal_footer_actions');
                    if (footerActions) {
                        const st = parseInt(plan.id_estado);
                        if (st === 1) {
                            footerActions.innerHTML = `
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold shadow-sm" onclick="fntEnviarAprobacionPlan(${plan.id_planeacion});">
                                    <i class="ri-send-plane-fill me-1"></i> Enviar a Aprobación
                                </button>
                            `;
                        } else if (st === 2) {
                            footerActions.innerHTML = `
                                <a href="${base_url}/Lgs_aprobaciones" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                                    <i class="ri-shield-check-line me-1"></i> Ir al Panel de Aprobaciones
                                </a>
                            `;
                        } else if (st === 3) {
                            footerActions.innerHTML = `
                                <button type="button" class="btn btn-sm btn-soft-warning rounded-pill px-4 fw-semibold shadow-sm" onclick="$('#modalViewDetallePlan').modal('hide'); fntReabrirPlan(${plan.id_planeacion});">
                                    <i class="ri-restart-line me-1"></i> Reabrir Planeación
                                </button>
                            `;
                        } else {
                            footerActions.innerHTML = '';
                        }
                    }

                    // 4. Abrir Modal
                    $('#modalViewDetallePlan').modal('show');

                } else {
                    Swal.fire("Error", objData.msg || "No se pudo obtener el detalle de la planeación", "error");
                }
            } catch (e) {
                console.error(e);
                Swal.fire("Error", "Error al procesar los datos de la planeación", "error");
            }
        }
    };
}

function fntReabrirPlan(idPlaneacion) {
    Swal.fire({
        title: '¿Reabrir Planeación?',
        text: 'La planeación y sus envíos regresarán a estado editable (Borrador) para que puedas realizar modificaciones.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-restart-line me-1"></i> Sí, reabrir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reabriendo...',
                text: 'Por favor espere.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() }
            });

            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_planeaciones/reabrir';
            let formData = new FormData();
            formData.append('id_planeacion', idPlaneacion);

            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        Swal.fire("¡Planeación Reabierta!", objData.msg, "success");
                        tablePlaneaciones.ajax.reload();
                    } else {
                        Swal.fire("Error", objData.msg || "No se pudo reabrir la planeación", "error");
                    }
                }
            };
        }
    });
}

function fntEnviarAprobacionPlan(idPlaneacion) {
    Swal.fire({
        title: '¿Enviar a Aprobación?',
        text: 'La planeación pasará a estado Pendiente de Aprobación para que el gerente la revise y autorice.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0ab39c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-send-plane-fill me-1"></i> Sí, enviar a aprobación',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Enviando...',
                text: 'Por favor espere.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading() }
            });

            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_planeaciones/enviarAprobacion';
            let formData = new FormData();
            formData.append('id_planeacion', idPlaneacion);

            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status) {
                        $('#modalVerDetallePlan').modal('hide');
                        Swal.fire("¡Enviada con Éxito!", objData.msg, "success");
                        tablePlaneaciones.ajax.reload();
                    } else {
                        Swal.fire("Error", objData.msg || "No se pudo enviar a aprobación", "error");
                    }
                }
            };
        }
    });
}
