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
                    if (data == 1) {
                        return '<span class="badge bg-success">Activo</span>';
                    } else {
                        return '<span class="badge bg-danger">Inactivo</span>';
                    }
                }
            },
            { "data": "options" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    let formChofer = document.querySelector("#formChofer");
    formChofer.onsubmit = function(e) {
        e.preventDefault();
        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/prv_choferes/store';
        let formData = new FormData(formChofer);
        request.open("POST", ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function() {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    $('#modalFormChofer').modal("hide");
                    formChofer.reset();
                    swal("Choferes", objData.message, "success");
                    tableChoferes.ajax.reload();
                } else {
                    swal("Error", objData.message || "Error al procesar", "error");
                }
            }
        }
    }
});

function openModal() {
    document.querySelector('#id_chofer').value = "";
    document.querySelector('.modal-header').classList.replace("headerUpdate", "headerRegister");
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML = "Guardar";
    document.querySelector('#titleModal').innerHTML = "Nuevo Chofer";
    document.querySelector("#formChofer").reset();
    $('#modalFormChofer').modal('show');
}

function fntEditChofer(id) {
    document.querySelector('#titleModal').innerHTML = "Actualizar Chofer";
    document.querySelector('.modal-header').classList.replace("headerRegister", "headerUpdate");
    document.querySelector('#btnActionForm').classList.replace("btn-primary", "btn-info");
    document.querySelector('#btnText').innerHTML = "Actualizar";

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/prv_choferes/getChofer/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status === "success") {
                let data = objData.data;
                document.querySelector("#id_chofer").value = data.id_chofer;
                document.querySelector("#id_proveedor").value = data.id_proveedor;
                document.querySelector("#nombre").value = data.nombre;
                document.querySelector("#apellidos").value = data.apellidos;
                document.querySelector("#num_licencia").value = data.num_licencia;
                document.querySelector("#tipo_licencia").value = data.tipo_licencia;
                document.querySelector("#vigencia_licencia").value = data.vigencia_licencia;
                document.querySelector("#telefono").value = data.telefono;
                $('#modalFormChofer').modal('show');
            } else {
                swal("Error", objData.message, "error");
            }
        }
    }
}

function fntDelChofer(id) {
    swal({
        title: "Eliminar Chofer",
        text: "¿Realmente desea eliminar este chofer?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/prv_choferes/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        swal("Eliminado!", objData.message, "success");
                        tableChoferes.ajax.reload();
                    } else {
                        swal("Error", objData.message, "error");
                    }
                }
            }
        }
    });
}
