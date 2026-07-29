function fntSwitchTab(tabId) {
    let tabEl = document.querySelector(tabId);
    if (tabEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab && typeof bootstrap.Tab.getOrCreateInstance === 'function') {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        } else {
            tabEl.click();
        }
    }
}

let tableChoferes;

document.addEventListener('DOMContentLoaded', function() {
    tableChoferes = $('#tableChoferes').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_choferes/getChoferes",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_chofer" },
            { "data": "trasladista" },
            { "data": "nombre_completo" },
            { "data": "num_licencia" },
            {
                "data": "tipo_licencia",
                "render": function(data) {
                    return '<span class="badge bg-secondary">Lic. Tipo ' + (data || 'N/A') + '</span>';
                }
            },
            {
                "data": "vigencia_licencia",
                "render": function(data) {
                    return data ? data : 'Sin fecha';
                }
            },
            { "data": "telefono" },
            {
                "data": "estatus_operativo",
                "render": function(data) {
                    return data == 1
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
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
                        fntSwitchTab('#tabList');
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
});

// ── NUEVO ─────────────────────────────────────────────────────────
function fntNewChofer() {
    document.querySelector('#id_chofer').value = "";
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML = "Guardar";
    document.querySelector('#tabForm').innerHTML = "NUEVO CHOFER";
    document.querySelector("#formChofer").reset();
}

// ── CANCELAR ──────────────────────────────────────────────────────
function cancelForm() {
    document.querySelector("#formChofer").reset();
    fntSwitchTab('#tabList');
}

// ── EDITAR ────────────────────────────────────────────────────────
function fntEditChofer(id) {
    document.querySelector('#tabForm').innerHTML = "EDITAR CHOFER";
    document.querySelector('#btnActionForm').classList.replace("btn-primary", "btn-info");
    document.querySelector('#btnText').innerHTML = "Actualizar";

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
            fntSwitchTab('#tabForm');
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
