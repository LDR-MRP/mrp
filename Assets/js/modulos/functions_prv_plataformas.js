function fntSwitchView(view) {
    const secGrid = document.querySelector("#view-index-plataformas");
    const secForm = document.querySelector("#view-form-plataformas");

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
        lbl.textContent = val || '4';
    }
}

let tablePlataformas;

document.addEventListener('DOMContentLoaded', function() {
    tablePlataformas = $('#tablePlataformas').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_plataformas/getPlataformas",
            "dataSrc": function(json) {
                let data = json || [];
                
                // Actualizar KPIs de Plataformas de forma dinámica
                let total = data.length;
                let asignadas = data.filter(d => d.chofer_actual && d.chofer_actual.trim() !== '').length;
                let sinChofer = total - asignadas;
                let capacidadTotal = data.reduce((acc, curr) => acc + (parseInt(curr.capacidad_vehiculos) || 0), 0);

                let kpiTotal = document.querySelector('#kpi-total-plataformas');
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
            { "data": "id_plataforma" },
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

    // ── FORM PLATAFORMA ──────────────────────────────────────────────
    let formPlataforma = document.querySelector("#formPlataforma");
    if (formPlataforma) {
        formPlataforma.addEventListener("submit", function(e) {
            e.preventDefault();
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/prv_plataformas/store';
            let formData = new FormData(formPlataforma);
            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState !== 4) return;
                if (request.status === 200 || request.status === 201) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        formPlataforma.reset();
                        tablePlataformas.ajax.reload();
                        Swal.fire({
                            title: "Plataformas",
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
            let ajaxUrl = base_url + '/prv_plataformas/asignarChofer';
            let formData = new FormData(formAsignarChofer);
            request.open("POST", ajaxUrl, true);
            request.send(formData);
            request.onreadystatechange = function() {
                if (request.readyState !== 4) return;
                if (request.status === 200 || request.status === 201) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        tablePlataformas.ajax.reload();
                        Swal.fire({
                            title: "Asignación de Chofer",
                            text: objData.message || "Asignación exitosa",
                            icon: "success",
                            confirmButtonText: "OK",
                            confirmButtonColor: "#28a745"
                        }).then(() => {
                            let idPlataforma = document.querySelector("#historial_id_plataforma").value;
                            fntHistorialPlataforma(idPlataforma);
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
function fntNewPlataforma() {
    document.querySelector('#id_plataforma').value = "";
    document.querySelector('#form-plataforma-title').textContent = "Registrar Nueva Plataforma";
    document.querySelector('#breadcrumb-form-plataforma').textContent = "Nueva Plataforma";
    document.querySelector('#btnText').textContent = "Guardar Plataforma";
    document.querySelector("#formPlataforma").reset();
    document.querySelector('#capacidad_vehiculos').value = 4;
    updateCapacidadDisplay(4);
    fntSwitchView('form');
}

// ── CANCELAR ──────────────────────────────────────────────────────
function cancelForm() {
    document.querySelector("#formPlataforma").reset();
    fntSwitchView('grid');
}

// ── EDITAR ────────────────────────────────────────────────────────
function fntEditPlataforma(id) {
    document.querySelector('#form-plataforma-title').textContent = "Actualizar Plataforma";
    document.querySelector('#breadcrumb-form-plataforma').textContent = "Editar Plataforma";
    document.querySelector('#btnText').textContent = "Actualizar Plataforma";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_plataformas/getPlataforma/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let d = objData.data;
            document.querySelector("#id_plataforma").value          = d.id_plataforma;
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
function fntHistorialPlataforma(id) {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_plataformas/getHistorial/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState !== 4 || request.status !== 200) return;
        let objData = JSON.parse(request.responseText);
        if (objData.status === "success") {
            let plataforma   = objData.data.plataforma;
            let historial = objData.data.historial;

            document.querySelector("#historial_id_plataforma").value   = plataforma.id_plataforma;
            document.querySelector("#historial_id_proveedor").value = plataforma.id_proveedor;
            document.querySelector("#subTitlePlataforma").innerHTML =
                "Eco: <b>" + plataforma.numero_economico + "</b> | Placas: <b>" + plataforma.placas +
                "</b> | Trasladista: <b>" + plataforma.trasladista + "</b>";

            fntCargarChoferesCombo(plataforma.id_proveedor);

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
            $('#modalHistorialPlataforma').modal('show');
        } else {
            Swal.fire("Error", objData.message, "error");
        }
    };
}

// ── CARGAR COMBO CHOFERES ─────────────────────────────────────────
function fntCargarChoferesCombo(idProveedor) {
    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/prv_plataformas/getChoferesPorProveedor/' + idProveedor;
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
function fntDelPlataforma(id) {
    Swal.fire({
        title: "¿Eliminar Plataforma?",
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
            let ajaxUrl = base_url + '/prv_plataformas/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState !== 4 || request.status !== 200) return;
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    Swal.fire("Eliminado", objData.message, "success");
                    tablePlataformas.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message, "error");
                }
            };
        }
    });
}
