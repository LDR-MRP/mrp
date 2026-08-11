function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-choferes");
    const secForm = document.querySelector("#view-form-choferes");

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

function updateLicenciaDisplay(tipo) {
    const lbl = document.querySelector('#lbl-tipo-licencia-display');
    if (!lbl) return;

    switch ((tipo || '').toUpperCase()) {
        case 'A':
            lbl.textContent = "Licencia Tipo A (Particular)";
            break;
        case 'B':
            lbl.textContent = "Licencia Tipo B (Carga)";
            break;
        case 'C':
            lbl.textContent = "Licencia Tipo C (Pesado)";
            break;
        case 'E':
            lbl.textContent = "Licencia Tipo E (Federal)";
            break;
        default:
            lbl.textContent = "Licencia Tipo " + (tipo || 'B');
    }
}

let tableChoferes;

document.addEventListener('DOMContentLoaded', function() {
    tableChoferes = $('#tableChoferes').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_choferes/getChoferes",
            "dataSrc": function(json) {
                let data = json || [];

                // Actualizar KPIs de Choferes
                let total = data.length;
                let activos = data.filter(d => d.estatus_operativo == 1).length;
                let inactivos = total - activos;
                let licencias = data.filter(d => ['B', 'C', 'E'].includes((d.tipo_licencia || '').toUpperCase())).length;

                let kpiTotal = document.querySelector('#kpi-total-choferes');
                let kpiActivos = document.querySelector('#kpi-activos');
                let kpiInactivos = document.querySelector('#kpi-inactivos');
                let kpiLicencias = document.querySelector('#kpi-licencias');

                if (kpiTotal) kpiTotal.textContent = total;
                if (kpiActivos) kpiActivos.textContent = activos;
                if (kpiInactivos) kpiInactivos.textContent = inactivos;
                if (kpiLicencias) kpiLicencias.textContent = licencias;

                return data;
            }
        },
        "columns": [
            { "data": "id_chofer" },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-body">' + (data || '-') + '</span>';
                }
            },
            { 
                "data": "nombre_completo",
                "render": function(data) {
                    return '<span class="fw-bold text-dark">' + (data || '-') + '</span>';
                }
            },
            { 
                "data": "num_licencia",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || '-') + '</span>';
                }
            },
            {
                "data": "tipo_licencia",
                "render": function(data) {
                    return '<span class="badge bg-soft-info text-info fs-12">Lic. Tipo ' + (data || 'N/A') + '</span>';
                }
            },
            {
                "data": "vigencia_licencia",
                "render": function(data) {
                    return data ? '<span class="fs-13"><i class="ri-calendar-line me-1 text-muted"></i>' + data + '</span>' : '<span class="text-muted fs-12">Sin fecha</span>';
                }
            },
            { 
                "data": "telefono",
                "render": function(data) {
                    return data ? '<span class="fs-13"><i class="ri-phone-line me-1 text-muted"></i>' + data + '</span>' : '-';
                }
            },
            {
                "data": "estatus_operativo",
                "render": function(data) {
                    return data == 1
                        ? '<span class="badge bg-soft-success text-success fs-12">Activo</span>'
                        : '<span class="badge bg-soft-danger text-danger fs-12">Inactivo</span>';
                }
            },
            { "data": "options" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // ── FORM CHOFER ───────────────────────────────────────────────
    let formChofer = document.querySelector("#formChofer");
    if (formChofer) {
        formChofer.addEventListener("submit", function(e) {
            e.preventDefault();
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_choferes/store';
            let formData = new FormData(formChofer);
            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState !== 4) return;
                if (request.status === 200 || request.status === 201) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        formChofer.reset();
                        tableChoferes.ajax.reload();
                        Swal.fire({
                            title: "Choferes",
                            text: objData.message || "Guardado exitosamente",
                            icon: "success",
                            confirmButtonText: "OK",
                            confirmButtonColor: "#28a745"
                        }).then(() => {
                            fntSwitchView('grid');
                        });
                    } else {
                        Swal.fire("Error", objData.message || "Error al procesar", "error");
                    }
                } else {
                    try {
                        let objData = JSON.parse(request.responseText);
                        Swal.fire("Error", objData.message || "Error en la petición", "error");
                    } catch(err) {
                        Swal.fire("Error", "Error al procesar la solicitud (" + request.status + ")", "error");
                    }
                }
            };
        });
    }
});

// ── NUEVO ─────────────────────────────────────────────────────────
function fntNewChofer() {
    document.querySelector('#id_chofer').value = "";
    document.querySelector('#form-chofer-title').textContent = "Registrar Nuevo Chofer";
    document.querySelector('#breadcrumb-form-chofer').textContent = "Nuevo Chofer";
    document.querySelector('#btnText').textContent = "Guardar Chofer";
    document.querySelector("#formChofer").reset();
    updateLicenciaDisplay('B');
    fntSwitchView('form');
}

// ── CANCELAR ──────────────────────────────────────────────────────
function cancelForm() {
    document.querySelector("#formChofer").reset();
    fntSwitchView('grid');
}

// ── EDITAR ────────────────────────────────────────────────────────
function fntEditChofer(id) {
    document.querySelector('#form-chofer-title').textContent = "Actualizar Chofer";
    document.querySelector('#breadcrumb-form-chofer').textContent = "Editar Chofer";
    document.querySelector('#btnText').textContent = "Actualizar Chofer";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_choferes/getChofer/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let d = objData.data;
            document.querySelector("#id_chofer").value         = d.id_chofer;
            document.querySelector("#id_proveedor").value      = d.id_proveedor;
            document.querySelector("#nombre").value            = d.nombre;
            document.querySelector("#apellidos").value         = d.apellidos;
            document.querySelector("#num_licencia").value      = d.num_licencia;
            document.querySelector("#tipo_licencia").value     = d.tipo_licencia;
            document.querySelector("#vigencia_licencia").value = d.vigencia_licencia;
            document.querySelector("#telefono").value          = d.telefono;
            updateLicenciaDisplay(d.tipo_licencia);
            fntSwitchView('form');
        } else {
            Swal.fire("Error", objData.message, "error");
        }
    };
}

// ── ELIMINAR ──────────────────────────────────────────────────────
function fntDelChofer(id) {
    Swal.fire({
        title: "¿Eliminar Chofer?",
        text: "¿Realmente deseas eliminar este chofer? Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d"
    }).then((result) => {
        if (result.isConfirmed) {
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_choferes/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState !== 4 || request.status !== 200) return;
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    Swal.fire("Eliminado", objData.message, "success");
                    tableChoferes.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message, "error");
                }
            };
        }
    });
}
