let tableAprobaciones;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Aprobaciones
    tableAprobaciones = $('#tableAprobaciones').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_aprobaciones/getPlaneacionesAprobacion",
            "dataSrc": ""
        },
        "columns": [
            { "data": "folio" },
            { "data": "creador" },
            { "data": "total_rutas" },
            { 
                "data": "costo_total",
                "render": function (data, type, row) {
                    if (data == null) return '$0.00';
                    return '$' + parseFloat(data).toFixed(2);
                }
            },
            { "data": "created_at" },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 2: badge = '<span class="badge bg-warning text-dark">Pendiente Aprobación</span>'; break;
                        case 3: badge = '<span class="badge bg-danger">Rechazada</span>'; break;
                        case 5: badge = '<span class="badge bg-success">Aprobada</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_planeacion",
                "render": function (data, type, row) {
                    // Solo si está en estado 2 (Pendiente) se puede evaluar.
                    // Si está en otro estado, solo se ve el detalle.
                    let btnAction = '';
                    if (row.id_estado == 2) {
                        btnAction = `<button class="btn btn-sm btn-primary" onClick="fntEvaluarPlan(${data}, '${row.folio}', ${row.costo_total}, ${row.km_total}, '${row.obs_operador}')" title="Evaluar Plan">
                                        <i class="ri-search-eye-line"></i> Evaluar
                                     </button>`;
                    } else {
                        btnAction = `<button class="btn btn-sm btn-outline-secondary" onClick="fntViewDetalle(${data})" title="Ver Detalle (Solo Lectura)">
                                        <i class="ri-eye-line"></i> Detalle
                                     </button>`;
                    }

                    return `<div class="text-center">${btnAction}</div>`;
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
    if (document.getElementById('cardMontoPendiente')) document.getElementById('cardMontoPendiente').innerText = '$' + montoPendiente.toFixed(2);
    if (document.getElementById('cardAprobAutorizadas')) document.getElementById('cardAprobAutorizadas').innerText = countAutorizadas;
    if (document.getElementById('cardMontoAutorizado')) document.getElementById('cardMontoAutorizado').innerText = '$' + montoAutorizado.toFixed(2);
}

function fntEvaluarPlan(idPlaneacion, folio, costo, km, obs) {
    // 1. Limpiar Modal
    document.querySelector("#formAprobacion").reset();
    document.getElementById('id_planeacion').value = idPlaneacion;
    document.getElementById('lblFolioModal').innerText = folio;
    document.getElementById('lblCostoModal').innerText = '$' + parseFloat(costo).toFixed(2);
    document.getElementById('lblKmModal').innerText = parseFloat(km).toFixed(2);
    document.getElementById('lblObsOperador').innerText = (obs == 'null' || obs == '') ? 'Sin observaciones del operador.' : obs;
    
    document.getElementById('bodyDetalleRutas').innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando rutas...</td></tr>';
    
    $('#modalEvaluarPlan').modal('show');
    
    // 2. Cargar detalle de rutas (envíos)
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_aprobaciones/getDetallePlan/' + idPlaneacion;
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                objData.data.forEach(ruta => {
                    htmlBody += `
                        <tr>
                            <td class="fw-bold text-primary">${ruta.folio}</td>
                            <td><span class="badge bg-light text-dark border">${ruta.tipo_traslado || 'N/A'}</span></td>
                            <td>${ruta.origen}</td>
                            <td>${ruta.trasladista}</td>
                            <td class="text-center">${ruta.total_vins}</td>
                            <td class="fw-bold">$${parseFloat(ruta.costo_total).toFixed(2)}</td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="6" class="text-center text-muted">No se encontraron rutas asociadas.</td></tr>';
            }
            
            document.getElementById('bodyDetalleRutas').innerHTML = htmlBody;
        }
    }
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
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status) {
                $('#modalEvaluarPlan').modal("hide");
                Swal.fire("Resolución Exitosa", objData.msg, "success");
                tableAprobaciones.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

function fntViewDetalle(idPlaneacion) {
    Swal.fire('Detalle', 'Aquí se mostraría la vista de solo lectura del plan ya resuelto.', 'info');
}
