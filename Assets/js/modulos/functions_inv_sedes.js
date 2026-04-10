let tableSedes;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_sede = document.querySelector('#clave-sede-input');
const estado = document.querySelector('#estado-select');
const descripcion = document.querySelector('#descripcion-sede-textarea');

// Mis referencias globales 
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formSedes = null;

document.addEventListener('DOMContentLoaded', function () {

    formSedes = document.querySelector("#formSedes");
    spanBtnText = document.querySelector('#btnText');

    tableSedes = $('#tableSedes').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": base_url + "/Inv_sedes/getSedes",
            "dataSrc": ""
        },
        "columns": [
    { "data": "cve_sede" },
    { "data": "descripcion" },
    { "data": "fecha_creacion" },
    { "data": "estado" },
    { "data": "options" } 
        ],
        "dom": "lBfrtip",
        "buttons": [],
        "resonsieve": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    const primerTabEl = document.querySelector('#nav-tab a[href="#listsedes"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarSede"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl);
        firstTab  = new bootstrap.Tab(firstTabEl);
        tabNuevo  = firstTabEl;

        tabNuevo.addEventListener('click', () => {
            spanBtnText.textContent = 'REGISTRAR';
            formSedes.reset();
            document.querySelector("#idsede").value = 0;
        });
    }

    formSedes.addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(formSedes);
        let url = base_url + "/Inv_sedes/setSede";

        fetch(url,{
            method:"POST",
            body:formData
        })
        .then(res => res.json())
        .then(objData => {
            if(objData.status){
                $('#tableSedes').DataTable().ajax.reload();
                primerTab.show();
                Swal.fire("Correcto", objData.msg, "success");
                formSedes.reset();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        });
    });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewSede(id){
    fetch(base_url + "/Inv_sedes/getSede/" + id)
    .then(res => res.json())
    .then(objData => {
        if(objData.status){
            document.querySelector("#celClave").innerHTML = objData.data.cve_sede;
            document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
            document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
            document.querySelector("#celEstado").innerHTML = objData.data.estado == 2 ? "Activo" : "Inactivo";

            $("#modalViewSede").modal("show");
        }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditSede(id){
    fetch(base_url + "/Inv_sedes/getSede/" + id)
    .then(res => res.json())
    .then(objData => {

        if(objData.status){
            document.querySelector("#idsede").value = objData.data.idsede;
            cve_sede.value = objData.data.cve_sede;
            descripcion.value = objData.data.descripcion;
            estado.value = objData.data.estado;

            spanBtnText.textContent = "ACTUALIZAR";
            firstTab.show();
        }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UNA SEDE
// ------------------------------------------------------------------------
function fntDelInfo(idsede) {

    Swal.fire({
        html: `
        <div class="mt-3">
            <lord-icon 
                src="https://cdn.lordicon.com/gsqxdxog.json" 
                trigger="loop" 
                colors="primary:#f7b84b,secondary:#f06548" 
                style="width:100px;height:100px">
            </lord-icon>

            <div class="mt-4 pt-2 fs-15 mx-5">
                <h4>Confirmar eliminación</h4>
                <p class="text-muted mx-4 mb-0">
                    ¿Estás seguro de eliminar este registro?
                    Esta acción no se puede deshacer.
                </p>
            </div>
        </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        customClass: {
            confirmButton: "btn btn-primary w-xs me-2 mb-1",
            cancelButton: "btn btn-danger w-xs mb-1"
        },
        buttonsStyling: false,
        showCloseButton: true
    }).then((result) => {

        if (!result.isConfirmed) return;

        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/Inv_sedes/delSede';
        let strData = "idsede=" + idsede;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {

            if (request.readyState === 4 && request.status === 200) {

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    Swal.fire("Correcto", objData.msg, "success");
                    $('#tableSedes').DataTable().ajax.reload();

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            }
        }
    });
}
