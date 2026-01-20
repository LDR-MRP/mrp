let tableMonedas;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_moneda = document.querySelector('#clave-moneda-input');
const cambio_moneda = document.querySelector('#cambio-moneda-input');
const simbolo_moneda = document.querySelector('#simbolo-moneda-input');
const estado = document.querySelector('#estado-select');
const descripcion = document.querySelector('#descripcion-moneda-textarea');

// Mis referencias globales 
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formMonedas = null;

document.addEventListener('DOMContentLoaded', function () {

    formMonedas = document.querySelector("#formMonedas");
    spanBtnText = document.querySelector('#btnText');

    tableMonedas = $('#tableMonedas').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": base_url + "/Inv_moneda/getMonedas",
            "dataSrc": ""
        },
        "columns": [
    { "data": "cve_moneda" },
    { "data": "descripcion" },
    { "data": "simbolo" },
    { "data": "tipo_cambio" },
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

    const primerTabEl = document.querySelector('#nav-tab a[href="#listmonedas"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarMoneda"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl);
        firstTab  = new bootstrap.Tab(firstTabEl);
        tabNuevo  = firstTabEl;

        tabNuevo.addEventListener('click', () => {
            spanBtnText.textContent = 'REGISTRAR';
            formMonedas.reset();
            document.querySelector("#idmoneda").value = 0;
        });
    }

    formMonedas.addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(formMonedas);
        let url = base_url + "/Inv_moneda/setMoneda";

        fetch(url,{
            method:"POST",
            body:formData
        })
        .then(res => res.json())
        .then(objData => {
            if(objData.status){
                $('#tableMonedas').DataTable().ajax.reload();
                primerTab.show();
                Swal.fire("Correcto", objData.msg, "success");
                formMonedas.reset();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        });
    });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewMoneda(id){
    fetch(base_url + "/Inv_moneda/getMoneda/" + id)
    .then(res => res.json())
    .then(objData => {
        if(objData.status){
            document.querySelector("#celClave").innerHTML = objData.data.cve_moneda;
            document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
            document.querySelector("#celCambio").innerHTML = objData.data.tipo_cambio; 
            document.querySelector("#celSimbolo").innerHTML = objData.data.simbolo; 
            document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
            document.querySelector("#celEstado").innerHTML = objData.data.estado == 2 ? "Activo" : "Inactivo";

            $("#modalViewMoneda").modal("show");
        }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditMoneda(id){
    fetch(base_url + "/Inv_moneda/getMoneda/" + id)
    .then(res => res.json())
    .then(objData => {

        if(objData.status){
            document.querySelector("#idmoneda").value = objData.data.idmoneda;
            cve_moneda.value = objData.data.cve_moneda;
            descripcion.value = objData.data.descripcion;
            simbolo_moneda.value = objData.data.simbolo;
            cambio_moneda.value = objData.data.tipo_cambio;
            estado.value = objData.data.estado;

            spanBtnText.textContent = "ACTUALIZAR";
            firstTab.show();
        }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UN PRECIO
// ------------------------------------------------------------------------
function fntDelInfo(idmoneda) {

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
        let ajaxUrl = base_url + '/Inv_moneda/delMoneda';
        let strData = "idmoneda=" + idmoneda;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {

            if (request.readyState === 4 && request.status === 200) {

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    Swal.fire("Correcto", objData.msg, "success");
                    $('#tableMonedas').DataTable().ajax.reload();

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            }
        }
    });
}
