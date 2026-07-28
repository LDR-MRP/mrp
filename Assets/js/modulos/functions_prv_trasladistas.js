let tableTrasladistas;

document.addEventListener('DOMContentLoaded', function() {
    tableTrasladistas = $('#tableTrasladistas').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/prv_trasladistas/getTrasladistas",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_proveedor" },
            { "data": "rfc" },
            { "data": "razon_social" },
            { "data": "nombre_comercial" },
            { 
                "data": "total_madrinas",
                "render": function(data) {
                    return '<span class="badge bg-info">' + data + ' Madrinas</span>';
                }
            },
            { 
                "data": "total_choferes",
                "render": function(data) {
                    return '<span class="badge bg-success">' + data + ' Choferes</span>';
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

    let formTrasladista = document.querySelector("#formTrasladista");
    formTrasladista.onsubmit = function(e) {
        e.preventDefault();
        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/prv_trasladistas/store';
        let formData = new FormData(formTrasladista);
        request.open("POST", ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function() {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status === "success") {
                    $('#modalFormTrasladista').modal("hide");
                    formTrasladista.reset();
                    swal("Trasladistas", objData.message, "success");
                    tableTrasladistas.ajax.reload();
                } else {
                    swal("Error", objData.message || "Error al procesar", "error");
                }
            }
        }
    }
});

function openModal() {
    document.querySelector('#id_proveedor').value = "";
    document.querySelector('.modal-header').classList.replace("headerUpdate", "headerRegister");
    document.querySelector('#btnActionForm').classList.replace("btn-info", "btn-primary");
    document.querySelector('#btnText').innerHTML = "Guardar";
    document.querySelector('#titleModal').innerHTML = "Nuevo Trasladista";
    document.querySelector("#formTrasladista").reset();
    $('#modalFormTrasladista').modal('show');
}

function fntEditTrasladista(id) {
    document.querySelector('#titleModal').innerHTML = "Actualizar Trasladista";
    document.querySelector('.modal-header').classList.replace("headerRegister", "headerUpdate");
    document.querySelector('#btnActionForm').classList.replace("btn-primary", "btn-info");
    document.querySelector('#btnText').innerHTML = "Actualizar";

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/prv_trasladistas/getTrasladista/' + id;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function() {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            if (objData.status === "success") {
                let data = objData.data;
                document.querySelector("#id_proveedor").value = data.id_proveedor;
                document.querySelector("#rfc").value = data.rfc;
                document.querySelector("#razon_social").value = data.razon_social;
                document.querySelector("#nombre_comercial").value = data.nombre_comercial;
                document.querySelector("#id_tipo_persona").value = data.id_tipo_persona;
                document.querySelector("#tipo").value = data.tipo;
                document.querySelector("#origen").value = data.origen;
                $('#modalFormTrasladista').modal('show');
            } else {
                swal("Error", objData.message, "error");
            }
        }
    }
}

function fntDelTrasladista(id) {
    swal({
        title: "Eliminar Trasladista",
        text: "¿Realmente desea eliminar este trasladista?",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
            let ajaxUrl = base_url + '/prv_trasladistas/delete/' + id;
            request.open("POST", ajaxUrl, true);
            request.send();
            request.onreadystatechange = function() {
                if (request.readyState == 4 && request.status == 200) {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === "success") {
                        swal("Eliminado!", objData.message, "success");
                        tableTrasladistas.ajax.reload();
                    } else {
                        swal("Error", objData.message, "error");
                    }
                }
            }
        }
    });
}
