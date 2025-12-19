let tablePrecios;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_precio = document.querySelector('#clave-precio-input');
const estado = document.querySelector('#estado-select');
const descripcion = document.querySelector('#descripcion-precio-textarea');
const impuesto = document.querySelector('#impuesto-select');

// Mis referencias globales 
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formPrecios = null;

document.addEventListener('DOMContentLoaded', function () {

    formPrecios = document.querySelector("#formPrecios");
    spanBtnText = document.querySelector('#btnText');

    tablePrecios = $('#tablePrecios').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": base_url + "/Inv_precios/getPrecios",
            "dataSrc": ""
        },
        "columns": [
            { "data": "cve_precio" },
            { "data": "descripcion" },
            { "data": "con_impuesto" },
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

    const primerTabEl = document.querySelector('#nav-tab a[href="#listprecios"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarPrecio"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl);
        firstTab  = new bootstrap.Tab(firstTabEl);
        tabNuevo  = firstTabEl;

        tabNuevo.addEventListener('click', () => {
            spanBtnText.textContent = 'REGISTRAR';
            formPrecios.reset();
            document.querySelector("#idprecio").value = 0;
        });
    }

    formPrecios.addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(formPrecios);
        let url = base_url + "/Inv_precios/setPrecio";

        fetch(url,{
            method:"POST",
            body:formData
        })
        .then(res => res.json())
        .then(objData => {
            if(objData.status){
                $('#tablePrecios').DataTable().ajax.reload();
                primerTab.show();
                Swal.fire("Correcto", objData.msg, "success");
                formPrecios.reset();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        });
    });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewPrecio(id){
    fetch(base_url + "/Inv_precios/getPrecio/" + id)
    .then(res => res.json())
    .then(objData => {
        if(objData.status){
            document.querySelector("#celClave").innerHTML = objData.data.cve_precio;
            document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
            document.querySelector("#celImpuesto").innerHTML = objData.data.impuesto == 2 ? "NO" : "SI";
            document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
            document.querySelector("#celEstado").innerHTML = objData.data.estado == 2 ? "Activo" : "Inactivo";

            $("#modalViewPrecio").modal("show");
        }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditPrecio(id){
    fetch(base_url + "/Inv_precios/getPrecio/" + id)
    .then(res => res.json())
    .then(objData => {

        if(objData.status){
            document.querySelector("#idprecio").value = objData.data.idprecio;
            cve_precio.value = objData.data.cve_precio;
            descripcion.value = objData.data.descripcion;
            impuesto.value = objData.data.impuesto;
            estado.value = objData.data.estado;

            spanBtnText.textContent = "ACTUALIZAR";
            firstTab.show();
        }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UN PRECIO
// ------------------------------------------------------------------------
function fntDelInfo(idprecio) {

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
        let ajaxUrl = base_url + '/Inv_precios/delPrecio';
        let strData = "idprecio=" + idprecio;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {

            if (request.readyState === 4 && request.status === 200) {

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    Swal.fire("Correcto", objData.msg, "success");
                    $('#tablePrecios').DataTable().ajax.reload();

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            }
        }
    });
}
