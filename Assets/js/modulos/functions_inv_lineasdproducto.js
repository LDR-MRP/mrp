let tableLineasProducto;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_linea_producto = document.querySelector('#clave-linea-producto-input');
const estado = document.querySelector('#estado-select');
const descripcion = document.querySelector('#descripcion-linea-producto-textarea');

// Mis referencias globales 
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formLineasProducto = null;

document.addEventListener('DOMContentLoaded', function () {

    formLineasProducto = document.querySelector("#formLineasProducto");
    spanBtnText = document.querySelector('#btnText');

        if (!formLineasProducto) {
        console.warn('formLineasProducto no encontrado. JS de lineas no se inicializa en esta vista.');
        return;
    }

    tableLineasProducto = $('#tableLineasProducto').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": base_url + "/Inv_lineasdproducto/getLineasProductos",
            "dataSrc": ""
        },
        "columns": [
            { "data": "cve_linea_producto" },
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

    const primerTabEl = document.querySelector('#nav-tab a[href="#listlineasproductos"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarlineasproducto"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl);
        firstTab  = new bootstrap.Tab(firstTabEl);
        tabNuevo  = firstTabEl;

        tabNuevo.addEventListener('click', () => {
            spanBtnText.textContent = 'REGISTRAR';
            formLineasProducto.reset();
            document.querySelector("#idlineaproducto").value = 0;
        });
    }else {
        console.warn('Tabs de lineas no encontrados o btnText faltante.');
    }

    formLineasProducto.addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(formLineasProducto);
        let url = base_url + "/Inv_lineasdproducto/setLineaProducto";

        fetch(url,{
            method:"POST",
            body:formData
        })
        .then(res => res.json())
        .then(objData => {
            if(objData.status){
                $('#tableLineasProducto').DataTable().ajax.reload();
                primerTab.show();
                Swal.fire("Correcto", objData.msg, "success");
                formLineasProducto.reset();
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        });
    });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewLineaProducto(id){
    fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then(res => res.json())
    .then(objData => {
        if(objData.status){
            document.querySelector("#celClave").innerHTML = objData.data.cve_linea_producto;
            document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
            document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
            document.querySelector("#celEstado").innerHTML = objData.data.estado == 2 ? "Activo" : "Inactivo";

            $("#modalViewLineaProducto").modal("show");
        }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditLineaProducto(id){
    fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then(res => res.json())
    .then(objData => {

        if(objData.status){
            document.querySelector("#idlineaproducto").value = objData.data.idlineaproducto;
            cve_linea_producto.value = objData.data.cve_linea_producto;
            descripcion.value = objData.data.descripcion;
            estado.value = objData.data.estado;

            spanBtnText.textContent = "ACTUALIZAR";
            firstTab.show();
        }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UNA LINEA DE PRODUCTO
// ------------------------------------------------------------------------
function fntDelInfo(idlineaproducto) {

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
        let ajaxUrl = base_url + '/Inv_lineasdproducto/delLineaProducto';
        let strData = "idlineaproducto=" + idlineaproducto;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {

            if (request.readyState === 4 && request.status === 200) {

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    Swal.fire("Correcto", objData.msg, "success");
                    $('#tableLineasProducto').DataTable().ajax.reload();

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            }
        }
    });
}
