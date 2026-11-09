let tableAprobaciones;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Aprobaciones
    tableAprobaciones = $('#tableAprobaciones').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_aprobaciones/getPlaneacionesAprobacion",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_planeacion" },
            { "data": "folio" },
            { 
                "data": "descripcion",
                "render": function(data) {
                    return data ? data : '<span class="text-muted">-</span>';
                }
            },
            { 
                "data": "total_rutas",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-12">' + (data || 0) + ' Envío(s)</span>';
                }
            },
            { 
                "data": "km_total",
                "render": function(data) {
                    return (parseFloat(data) || 0).toFixed(1) + ' km';
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<strong class="text-success">$' + parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>Pendiente Aprobación</span>'; break;
                        case 3:
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>Rechazada</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-checkbox-circle-line me-1"></i>Aprobada</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_planeacion",
                "render": function (data, type, row) {
                    let btnAction = '';
                    let obsEsc = (row.obs_operador || '').replace(/'/g, "\\'");
                    if (row.id_estado == 2) {
                        btnAction = `<button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-sm" onClick="fntEvaluarPlan(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${obsEsc}')" title="Evaluar Planeación">
                                        <i class="ri-search-eye-line me-1"></i> Evaluar
                                     </button>`;
                    } else {
                        btnAction = `<button class="btn btn-sm btn-soft-primary rounded-pill px-3 fw-semibold" onClick="fntViewDetalle(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${obsEsc}')" title="Ver Detalle">
                                        <i class="ri-eye-line me-1"></i> Detalle
                                     </button>`;
                    }
                    return `<div class="text-end pe-3">${btnAction}</div>`;
                }
            }
        ],
        "respose": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasAprobaciones(settings.json || []);
        }
    });

    // Control de ajuste de DataTables al cambiar entre pestañas
    var tabGastosBtn = document.getElementById('tab-gastos-btn');
    if (tabGastosBtn) {
        tabGastosBtn.addEventListener('shown.bs.tab', function () {
            if (typeof tableGastos !== 'undefined' && tableGastos) {
                tableGastos.columns.adjust();
            }
        });
    }

    var tabPlaneacionesBtn = document.getElementById('tab-planeaciones-btn');
    if (tabPlaneacionesBtn) {
        tabPlaneacionesBtn.addEventListener('shown.bs.tab', function () {
            if (typeof tableAprobaciones !== 'undefined' && tableAprobaciones) {
                tableAprobaciones.columns.adjust();
            }
        });
    }
});

function actualizarMetricasAprobaciones(data) {
    if (!Array.isArray(data)) return;
    
    let pendientes = data.filter(p => parseInt(p.id_estado) === 2);
    let autorizadas = data.filter(p => parseInt(p.id_estado) === 5);

    let countPendientes = pendientes.length;
    let countAutorizadas = autorizadas.length;

    let montoPendiente = pendientes.reduce((acc, p) => acc + (parseFloat(p.costo_total) || 0), 0);
    let montoAutorizado = autorizadas.reduce((acc, p) => acc + (parseFloat(p.costo_total) || 0), 0);

    if (document.getElementById('cardAprobPendientes')) document.getElementById('cardAprobPendientes').innerText = countPendientes;
    if (document.getElementById('cardMontoPendiente')) document.getElementById('cardMontoPendiente').innerText = '$' + montoPendiente.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (document.getElementById('cardAprobAutorizadas')) document.getElementById('cardAprobAutorizadas').innerText = countAutorizadas;
    if (document.getElementById('cardMontoAutorizado')) document.getElementById('cardMontoAutorizado').innerText = '$' + montoAutorizado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Actualizar badge en la pestaña de Planeaciones
    let badgePlaneaciones = document.getElementById('badgePlaneacionesPendientes');
    if (badgePlaneaciones) {
        badgePlaneaciones.innerText = countPendientes;
    }
}

/**
 * Filtro por buscador de texto
 */
function filtrarTablaAprobaciones(term) {
    if (typeof tableAprobaciones !== "undefined" && tableAprobaciones) {
        tableAprobaciones.search(term).draw();
    }
}

/**
 * Filtra el DataTable por estado usando los chips de acceso rápido
 */
function filtrarAprobPorChip(filtro, btn) {
    document.querySelectorAll('.chip-aprob-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.replace('btn-secondary', 'btn-outline-secondary');
        b.classList.replace('btn-warning', 'btn-outline-warning');
        b.classList.replace('btn-success', 'btn-outline-success');
        b.classList.replace('btn-danger', 'btn-outline-danger');
    });
    
    if (btn) {
        btn.classList.add('active');
        if (btn.classList.contains('btn-outline-secondary')) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        if (btn.classList.contains('btn-outline-warning')) btn.classList.replace('btn-outline-warning', 'btn-warning');
        if (btn.classList.contains('btn-outline-success')) btn.classList.replace('btn-outline-success', 'btn-success');
        if (btn.classList.contains('btn-outline-danger')) btn.classList.replace('btn-outline-danger', 'btn-danger');
    }
    
    if (typeof tableAprobaciones !== "undefined" && tableAprobaciones) {
        tableAprobaciones.search(filtro).draw();
    }
}

/**
 * Permite filtrar la tabla haciendo clic en las cards de métricas
 */
function filtrarAprobPorCard(filtro, chipId) {
    const chipBtn = document.getElementById(chipId);
    filtrarAprobPorChip(filtro, chipBtn);
    
    const tableEl = document.getElementById('tableAprobaciones');
    if (tableEl) {
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function fntEvaluarPlan(idPlaneacion, folio, costo, km, obs) {
    // 1. Limpiar Modal
    document.querySelector("#formAprobacion").reset();
    document.getElementById('id_planeacion').value = idPlaneacion;
    document.getElementById('lblFolioModal').innerText = folio || ('PL-' + idPlaneacion);
    document.getElementById('lblCostoModal').innerText = '$' + (parseFloat(costo) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('lblKmModal').innerText = (parseFloat(km) || 0).toFixed(2);
    document.getElementById('lblObsOperador').innerText = (obs == 'null' || !obs) ? 'Sin observaciones del operador.' : obs;
    
    const contEnvios = document.getElementById('vdp_contenedor_envios');
    if (contEnvios) contEnvios.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> <span class="ms-2">Cargando desglose de envíos...</span></div>';
    
    const btnApprove = document.getElementById('btnAprobarModal');
    const btnReject = document.getElementById('btnRechazarModal');
    if (btnApprove) btnApprove.style.display = '';
    if (btnReject) btnReject.style.display = '';
    
    let modalEl = document.getElementById('modalEvaluarPlan');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            m.show();
        } else if (typeof $ !== 'undefined') {
            $('#modalEvaluarPlan').modal('show');
        }
    }
    
    // 2. Cargar detalle de rutas (envíos)
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_aprobaciones/getDetallePlan/' + idPlaneacion;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    const contEnvios = document.getElementById('vdp_contenedor_envios');
                    if (contEnvios) contEnvios.innerHTML = '';
            
            if (objData.status && objData.data && objData.data.length > 0) {
                let calcCosto = 0;
                let calcKm = 0;

                objData.data.forEach(env => {
                    calcCosto += parseFloat(env.costo_total) || 0;
                    calcKm += parseFloat(env.km_total) || 0;

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
                                    <td class="text-end fw-bold text-success">$${(parseFloat(v.costo_unidad) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rowsVins = '<tr><td colspan="6" class="text-center text-muted py-3">Sin unidades asignadas a este envío.</td></tr>';
                    }

                    const cardEnvHtml = `
                        <div class="card border shadow-none rounded-3 mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary fs-12 fw-bold">${env.folio}</span>
                                    <span class="badge bg-soft-info text-info border">${env.tipo_traslado || 'Madrina'}</span>
                                    <span class="fs-12 text-dark fw-medium"><i class="ri-map-pin-range-line text-muted me-1"></i>${env.origen}</span>
                                    <span class="fs-12 text-muted">| ${env.trasladista || 'Sin Trasladista'}</span>
                                    ${parseInt(env.total_incidencias || 0) > 0 ? `<a href="${base_url}/Lgs_incidencias?envio=${env.id_envio}" target="_blank" class="badge bg-soft-danger text-danger border border-danger-subtle fs-11 text-decoration-none ms-1" title="Ver ${env.total_incidencias} incidencia(s)"><i class="ri-alarm-warning-line me-1"></i>${env.total_incidencias} Inc.</a>` : ''}
                                    ${parseFloat(env.total_gastos || 0) > 0 ? `<a href="${base_url}/Lgs_gastosadicionales?envio=${env.id_envio}" target="_blank" class="badge bg-soft-warning text-dark border border-warning-subtle fs-11 text-decoration-none ms-1" title="Gastos Adicionales"><i class="ri-money-dollar-circle-line me-1"></i>+$${parseFloat(env.total_gastos).toLocaleString('en-US', { minimumFractionDigits: 2 })}</a>` : ''}
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted"><i class="ri-car-line me-1"></i>${env.total_vins} unidad(es)</span>
                                    <strong class="fs-14 text-success">$${(parseFloat(env.costo_total) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
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

                    if (contEnvios) contEnvios.innerHTML += cardEnvHtml;
                });

                // Si no venía costo o km en cabecera, actualizar con la suma de las rutas
                if (!costo || parseFloat(costo) === 0) {
                    document.getElementById('lblCostoModal').innerText = '$' + calcCosto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                if (!km || parseFloat(km) === 0) {
                    document.getElementById('lblKmModal').innerText = calcKm.toFixed(2);
                }
            } else {
                if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-soft-secondary text-center py-3">No hay envíos vinculados a esta planeación.</div>';
            }
        } catch (e) {
            const contEnvios = document.getElementById('vdp_contenedor_envios');
            if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-danger text-center py-3">Error al procesar la respuesta del servidor.</div>';
        }
    } else {
        const contEnvios = document.getElementById('vdp_contenedor_envios');
        if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-danger text-center py-3"><i class="ri-error-warning-line me-1"></i> No se pudo obtener el desglose de envíos del servidor.</div>';
    }
}
};
}

function fntViewDetalle(idPlaneacion, folio, costo, km, obs) {
    fntEvaluarPlan(idPlaneacion, folio, costo, km, obs);
    const btnApprove = document.getElementById('btnAprobarModal');
    const btnReject = document.getElementById('btnRechazarModal');
    if (btnApprove) btnApprove.style.display = 'none';
    if (btnReject) btnReject.style.display = 'none';
}

function enviarDecision(decision) {
    const obs = document.getElementById('obs_aprobador').value.trim();
    
    if (decision === 'rechazar' && obs === '') {
        Swal.fire("Atención", "Para rechazar la planeación es obligatorio ingresar el motivo.", "warning");
        return;
    }

    let textoConfirm = decision === 'aprobar' ? "¿Confirma APROBAR este presupuesto logístico?" : "¿Confirma RECHAZAR esta planeación?";
    let iconConfirm = decision === 'aprobar' ? "question" : "warning";
    let btnColor = decision === 'aprobar' ? "#198754" : "#dc3545";

    Swal.fire({
        title: 'Confirmar Decisión',
        text: textoConfirm,
        icon: iconConfirm,
        showCancelButton: true,
        confirmButtonColor: btnColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('decision').value = decision;
            ejecutarAjaxDecision();
        }
    });
}

function ejecutarAjaxDecision() {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_aprobaciones/resolver';
    let formData = new FormData(document.querySelector("#formAprobacion"));

    Swal.fire({
        title: 'Procesando...',
        text: 'Registrando respuesta.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    request.open("POST", ajaxUrl, true);
    request.send(formData);
    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            Swal.close();
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status === true || objData.status === 'success') {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        let modalEl = document.getElementById('modalEvaluarPlan');
                        if (modalEl) {
                            let m = bootstrap.Modal.getInstance(modalEl);
                            if (m) m.hide();
                        }
                    } else if (typeof $ !== 'undefined') {
                        $('#modalEvaluarPlan').modal("hide");
                    }
                    Swal.fire("Resolución Exitosa", objData.msg || objData.message || "Operación realizada", "success");
                    if (typeof tableAprobaciones !== 'undefined' && tableAprobaciones) {
                        tableAprobaciones.ajax.reload();
                    }
                } else {
                    let msg = (objData && (objData.msg || objData.message)) ? (objData.msg || objData.message) : 'No se pudo procesar la resolución.';
                    Swal.fire("Atención", msg, "warning");
                }
            } catch (e) {
                console.error(e);
                Swal.fire("Error", "Error al procesar la respuesta del servidor.", "error");
            }
        }
    };
}
