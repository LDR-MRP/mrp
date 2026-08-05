let tablePlaneaciones;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Planeaciones
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
            { "data": "folio" },
            { "data": "descripcion" },
            { "data": "total_rutas" },
            { "data": "km_total" },
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
                        case 1: badge = '<span class="badge bg-secondary">Borrador</span>'; break;
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
                    return `<div class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onClick="fntViewPlan(${data})" title="Ver Detalle">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>`;
                }
            }
        ],
        "respose": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // Evento de Check All para la tabla del modal
    document.getElementById('checkAllEnvios').addEventListener('change', function(e) {
        let checkboxes = document.querySelectorAll('.chk-envio');
        checkboxes.forEach(chk => chk.checked = e.target.checked);
        calcularTotalesPlan();
    });
});

function openModalPlan() {
    document.querySelector("#formPlaneacion").reset();
    document.getElementById('bodyEnviosDisponibles').innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando envíos...</td></tr>';
    document.getElementById('checkAllEnvios').checked = false;
    resetTotales();
    
    $('#modalFormPlaneacion').modal('show');
    
    cargarEnviosDisponibles();
}

function cargarEnviosDisponibles() {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_planeaciones/getEnviosDisponibles';
    
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                objData.data.forEach(envio => {
                    htmlBody += `
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input chk-envio shadow-none" type="checkbox" value="${envio.id_envio}" data-km="${envio.km_total}" data-costo="${envio.costo_total}" data-vins="${envio.total_vins}" onchange="calcularTotalesPlan();">
                            </td>
                            <td class="fw-bold">${envio.folio}</td>
                            <td>${envio.origen}</td>
                            <td>${envio.trasladista}</td>
                            <td>${envio.total_vins}</td>
                            <td>${parseFloat(envio.km_total).toFixed(2)}</td>
                            <td>$${parseFloat(envio.costo_total).toFixed(2)}</td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="ri-error-warning-line fs-20"></i><br>No hay envíos en Estado "Creado" listos para planear.</td></tr>';
            }
            
            document.getElementById('bodyEnviosDisponibles').innerHTML = htmlBody;
        }
    }
}

function calcularTotalesPlan() {
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    let totalKm = 0.0;
    let totalCosto = 0.0;
    let totalVins = 0;
    
    checkboxes.forEach(chk => {
        totalKm += parseFloat(chk.getAttribute('data-km')) || 0;
        totalCosto += parseFloat(chk.getAttribute('data-costo')) || 0;
        totalVins += parseInt(chk.getAttribute('data-vins')) || 0;
    });
    
    document.getElementById('lblTotalVins').innerText = totalVins;
    document.getElementById('lblTotalKm').innerText = totalKm.toFixed(2);
    document.getElementById('lblTotalCosto').innerText = '$' + totalCosto.toFixed(2);
}

function resetTotales() {
    document.getElementById('lblTotalVins').innerText = '0';
    document.getElementById('lblTotalKm').innerText = '0.00';
    document.getElementById('lblTotalCosto').innerText = '$0.00';
}

function savePlaneacion() {
    // 1. Recolectar IDs seleccionados
    let checkboxes = document.querySelectorAll('.chk-envio:checked');
    if (checkboxes.length === 0) {
        Swal.fire("Atención", "Debes seleccionar al menos un envío para crear la planeación.", "warning");
        return;
    }
    
    let idsArr = [];
    checkboxes.forEach(chk => {
        idsArr.push(chk.value);
    });
    
    document.getElementById('envios_ids').value = idsArr.join(',');
    
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_planeaciones/store';
    let formData = new FormData(document.querySelector("#formPlaneacion"));

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
                $('#modalFormPlaneacion').modal("hide");
                Swal.fire("¡Planeación Creada!", objData.msg, "success");
                tablePlaneaciones.ajax.reload();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

function fntViewPlan(idPlaneacion) {
    Swal.fire('Detalle', 'El detalle de la planeación ' + idPlaneacion + ' se mostrará aquí.', 'info');
}
