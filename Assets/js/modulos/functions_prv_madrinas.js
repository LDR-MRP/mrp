function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-madrinas");
    const secForm = document.querySelector("#view-form-madrinas");

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

function updateCapacidadDisplay(val) {
    const lbl = document.querySelector('#lbl-capacidad-display');
    if (lbl) {
        lbl.textContent = val || '0';
    }
}

let tableMadrinas;

document.addEventListener('DOMContentLoaded', function() {
    tableMadrinas = $('#tableMadrinas').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_madrinas/getMadrinas",
            "dataSrc": function(json) {
                let data = json || [];
                
                // Actualizar KPIs de Madrinas de forma dinámica
                let total = data.length;
                let asignadas = data.filter(d => d.chofer_actual && d.chofer_actual.trim() !== '').length;
                let sinChofer = total - asignadas;
                let capacidadTotal = data.reduce((acc, curr) => acc + (parseInt(curr.capacidad_vehiculos) || 0), 0);

                let kpiTotal = document.querySelector('#kpi-total-madrinas');
                let kpiAsignadas = document.querySelector('#kpi-asignadas');
                let kpiSinChofer = document.querySelector('#kpi-sin-chofer');
                let kpiCapacidad = document.querySelector('#kpi-capacidad-total');

                if (kpiTotal) kpiTotal.textContent = total;
                if (kpiAsignadas) kpiAsignadas.textContent = asignadas;
                if (kpiSinChofer) kpiSinChofer.textContent = sinChofer;
                if (kpiCapacidad) kpiCapacidad.textContent = capacidadTotal;

                return data;
            }
        },
        "columns": [
            { "data": "id_madrina" },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-body">' + (data || '-') + '</span>';
                }
            },
            { 
                "data": "numero_economico",
                "render": function(data) {
                    return '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || '-') + '</span>';
                }
            },
            {
                "data": null,
                "render": function(data) {
                    let tracto = data.placas || 'S/P';
                    let caja = data.placa_caja ? ' <small class="text-muted">(Caja: ' + data.placa_caja + ')</small>' : '';
                    return '<span class="fw-bold">' + tracto + '</span>' + caja;
                }
            },
            {
                "data": null,
                "render": function(data) {
                    let desc = ((data.marca || '') + ' ' + (data.modelo || '')).trim();
                    let anio = data.anio ? ' (' + data.anio + ')' : '';
                    return desc + anio || '-';
                }
            },
            {
                "data": "chofer_actual",
                "render": function(data) {
                    if (data) {
                        return '<span class="badge bg-soft-success text-success fs-12"><i class="ri-steering-fill me-1"></i>' + data + '</span>';
                    }
                    return '<span class="badge bg-soft-warning text-warning fs-12">Sin asignar</span>';
                }
            },
            {
                "data": "capacidad_vehiculos",
                "render": function(data) {
                    return '<span class="badge bg-primary fs-12">' + data + ' vehs.</span>';
                }
            },
            { "data": "options" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // ── FORM MADRINA ──────────────────────────────────────────────
    let formMadrina = document.querySelector("#formMadrina");
    if (formMadrina) {
        formMadrina.addEventListener("submit", function(e) {
            e.preventDefault();
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_madrinas/store';
            let formData = new FormData(formMadrina);
            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState !== 4) return;
                if (request.status === 200 || request.status === 201) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        formMadrina.reset();
                        tableMadrinas.ajax.reload();
                        Swal.fire({
                            title: "Madrinas",
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

    // ── FORM ASIGNAR CHOFER ───────────────────────────────────────
    let formAsignarChofer = document.querySelector("#formAsignarChofer");
    if (formAsignarChofer) {
        formAsignarChofer.addEventListener("submit", function(e) {
            e.preventDefault();
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_madrinas/asignarChofer';
            let formData = new FormData(formAsignarChofer);
            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState !== 4) return;
                if (request.status === 200 || request.status === 201) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        tableMadrinas.ajax.reload();
                        Swal.fire({
                            title: "Asignación de Chofer",
                            text: objData.message || "Asignación exitosa",
                            icon: "success",
                            confirmButtonText: "OK",
                            confirmButtonColor: "#28a745"
                        }).then(() => {
                            let idMadrina = document.querySelector("#historial_id_madrina").value;
                            fntHistorialMadrina(idMadrina);
                        });
                    } else {
                        Swal.fire("Error", objData.message || "Error al asignar chofer", "error");
                    }
                } else {
                    try {
                        let objData = JSON.parse(request.responseText);
                        Swal.fire("Error", objData.message || "Error en la petición", "error");
                    } catch(err) {
                        Swal.fire("Error", "Error al asignar chofer (" + request.status + ")", "error");
                    }
                }
            };
        });
    }
});

// ── NUEVO ─────────────────────────────────────────────────────────
function fntNewMadrina() {
    document.querySelector('#id_madrina').value = "";
    document.querySelector('#form-madrina-title').textContent = "Registrar Nueva Madrina";
    document.querySelector('#breadcrumb-form-madrina').textContent = "Nueva Madrina";
    document.querySelector('#btnText').textContent = "Guardar Madrina";
    document.querySelector("#formMadrina").reset();
    updateCapacidadDisplay(8);
    fntSwitchView('form');
}

// ── CANCELAR ──────────────────────────────────────────────────────
function cancelForm() {
    document.querySelector("#formMadrina").reset();
    fntSwitchView('grid');
}

// ── EDITAR ────────────────────────────────────────────────────────
function fntEditMadrina(id) {
    document.querySelector('#form-madrina-title').textContent = "Actualizar Madrina";
    document.querySelector('#breadcrumb-form-madrina').textContent = "Editar Madrina";
    document.querySelector('#btnText').textContent = "Actualizar Madrina";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_madrinas/getMadrina/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let d = objData.data;
            document.querySelector("#id_madrina").value          = d.id_madrina;
            document.querySelector("#id_proveedor").value        = d.id_proveedor;
            document.querySelector("#numero_economico").value    = d.numero_economico;
            document.querySelector("#placas").value              = d.placas;
            document.querySelector("#placa_caja").value          = d.placa_caja || '';
            document.querySelector("#num_serie_vin").value       = d.num_serie_vin || '';
            document.querySelector("#marca").value               = d.marca || '';
            document.querySelector("#modelo").value              = d.modelo || '';
            document.querySelector("#anio").value                = d.anio || '';
            document.querySelector("#color").value               = d.color || '';
            document.querySelector("#capacidad_vehiculos").value = d.capacidad_vehiculos;
            updateCapacidadDisplay(d.capacidad_vehiculos);
            fntSwitchView('form');
        } else {
            Swal.fire("Error", objData.message, "error");
        }
    };
}

// ── HISTORIAL ─────────────────────────────────────────────────────
function fntHistorialMadrina(id) {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_madrinas/getHistorial/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let madrina   = objData.data.madrina;
            let historial = objData.data.historial;

            document.querySelector("#historial_id_madrina").value   = madrina.id_madrina;
            document.querySelector("#historial_id_proveedor").value = madrina.id_proveedor;
            document.querySelector("#subTitleMadrina").innerHTML =
                "Eco: <b>" + madrina.numero_economico + "</b> | Placas: <b>" + madrina.placas +
                "</b> | Trasladista: <b>" + madrina.trasladista + "</b>";

            fntCargarChoferesCombo(madrina.id_proveedor);

            let html = "";
            if (historial.length > 0) {
                historial.forEach(function(row) {
                    let badge = row.activo == 1
                        ? '<span class="badge bg-success">ACTIVO</span>'
                        : '<span class="badge bg-secondary">HISTÓRICO</span>';
                    html += '<tr>' +
                        '<td><b>' + row.chofer_nombre + '</b></td>' +
                        '<td>' + (row.num_licencia || 'S/N') + '</td>' +
                        '<td>' + (row.telefono || '-') + '</td>' +
                        '<td>' + row.fecha_inicio + '</td>' +
                        '<td>' + (row.fecha_fin || '-') + '</td>' +
                        '<td>' + badge + '</td>' +
                        '<td>' + (row.observaciones || '-') + '</td>' +
                    '</tr>';
                });
            } else {
                html = '<tr><td colspan="7" class="text-center text-muted">Sin conductores asignados aún.</td></tr>';
            }
            document.querySelector("#tbodyHistorialChoferes").innerHTML = html;
            $('#modalHistorialMadrina').modal('show');
        } else {
            Swal.fire("Error", objData.message, "error");
        }
    };
}

// ── CARGAR COMBO CHOFERES ─────────────────────────────────────────
function fntCargarChoferesCombo(idProveedor) {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_madrinas/getChoferesPorProveedor/' + idProveedor;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let choferes = objData.data;
            let html = '<option value="">-- Seleccionar Chofer --</option>';
            choferes.forEach(function(c) {
                html += '<option value="' + c.id_chofer + '">' + c.nombre_completo + ' (Lic: ' + c.num_licencia + ')</option>';
            });
            document.querySelector("#selectChoferAsignar").innerHTML = html;
        }
    };
}

// ── ELIMINAR ──────────────────────────────────────────────────────
function fntDelMadrina(id) {
    Swal.fire({
        title: "¿Eliminar Madrina?",
        text: "¿Realmente deseas eliminar esta unidad? Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d"
    }).then((result) => {
        if (result.isConfirmed) {
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_madrinas/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState !== 4 || request.status !== 200) return;
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    Swal.fire("Eliminado", objData.message, "success");
                    tableMadrinas.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message, "error");
                }
            };
        }
    });
}
