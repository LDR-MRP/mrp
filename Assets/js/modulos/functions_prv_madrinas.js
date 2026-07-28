let tableMadrinas;

document.addEventListener('DOMContentLoaded', function() {
    tableMadrinas = $('#tableMadrinas').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_madrinas/getMadrinas",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_madrina" },
            { "data": "trasladista" },
            { "data": "numero_economico" },
            { "data": "placas" },
            { 
                "data": null,
                "render": function(data) {
                    return (data.marca || '') + ' ' + (data.modelo || '');
                }
            },
            { 
                "data": "capacidad_vehiculos",
                "render": function(data) {
                    return '<span class="badge bg-primary">' + data + ' vehículos</span>';
                }
            },
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

    let formMadrina = document.querySelector("#formMadrina");
    formMadrina.onsubmit = function(e) {
        e.preventDefault();
        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/prv_madrinas/store';
        let formData = new FormData(formMadrina);
        request.open("POST", ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function() {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    $('#modalFormMadrina').modal("hide");
                    formMadrina.reset();
                    swal("Madrinas", objData.message, "success");
                    tableMadrinas.ajax.reload();
                } else {
                    swal("Error", objData.message || "Error al procesar", "error");
                }
            }
        }
    }
});

function openModal() {
    document.querySelector('#id_madrina').value = "";
    document.querySelector('.modal-header').classList.replace("headerUpdate", "headerRegister");
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML = "Guardar";
    document.querySelector('#titleModal').innerHTML = "Nueva Madrina";
    document.querySelector("#formMadrina").reset();
    $('#modalFormMadrina').modal('show');
}

function fntEditMadrina(id) {
    document.querySelector('#titleModal').innerHTML = "Actualizar Madrina";
    document.querySelector('.modal-header').classList.replace("headerRegister", "headerUpdate");
    document.querySelector('#btnActionForm').classList.replace("btn-primary", "btn-info");
    document.querySelector('#btnText').innerHTML = "Actualizar";

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/prv_madrinas/getMadrina/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status === "success") {
                let data = objData.data;
                document.querySelector("#id_madrina").value = data.id_madrina;
                document.querySelector("#id_proveedor").value = data.id_proveedor;
                document.querySelector("#numero_economico").value = data.numero_economico;
                document.querySelector("#placas").value = data.placas;
                document.querySelector("#marca").value = data.marca;
                document.querySelector("#modelo").value = data.modelo;
                document.querySelector("#capacidad_vehiculos").value = data.capacidad_vehiculos;
                $('#modalFormMadrina').modal('show');
            } else {
                swal("Error", objData.message, "error");
            }
        }
    }
}

function fntDelMadrina(id) {
    swal({
        title: "Eliminar Madrina",
        text: "¿Realmente desea eliminar esta unidad?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/prv_madrinas/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        swal("Eliminado!", objData.message, "success");
                        tableMadrinas.ajax.reload();
                    } else {
                        swal("Error", objData.message, "error");
                    }
                }
            }
        }
    });
}
