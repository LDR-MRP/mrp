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
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
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
                "render": function (data) {
                    return `<div class="text-end">
                                <button class="btn btn-sm btn-soft-primary" onClick="fntViewPlan(${data})" title="Ver Detalle">
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
    Swal.fire('Detalle', 'El detalle de la planeación ' + idPlaneacion + ' se mostrará en el visor de planeación.', 'info');
}
