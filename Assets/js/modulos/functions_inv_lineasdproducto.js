let tableLineasProducto;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario (los dejamos globales, pero se asignan cuando exista el DOM)
let cve_linea_producto = null;
let estado = null;
let descripcion = null;

// Mis referencias globales
let primerTab= null;
let firstTab= null;
let tabNuevo= null;
let spanBtnText = null;
let formLineasProducto = null;

document.addEventListener('DOMContentLoaded', function () {

    // ------------------------------------------------------------
    //  1) REFERENCIAS (NO ASUMAS QUE EXISTE EL FORM EN ESTA VISTA)
    // ------------------------------------------------------------
    formLineasProducto = document.querySelector("#formLineasProducto");
    spanBtnText   = document.querySelector('#btnText');
  

    // Inputs (solo si existen)
    cve_linea_producto = document.querySelector('#clave-linea-producto-input');
    estado = document.querySelector('#estado-select');
    descripcion = document.querySelector('#descripcion-linea-producto-textarea');

    // ------------------------------------------------------------
    //  2) DATATABLE (ESTO SÍ DEBE CORRER AUNQUE NO EXISTA EL FORM)
    // ------------------------------------------------------------
    if (document.querySelector('#tableLineasProducto')) {

        // evita doble inicialización si tu vista se carga más de una vez
        if (!$.fn.DataTable.isDataTable('#tableLineasProducto')) {
            tableLineasProducto = $('#tableLineasProducto').DataTable({
                aProcessing: true,
                aServerSide: true,
                ajax: {
                    url: base_url + "/Inv_lineasdproducto/getLineasProductos",
                    dataSrc: ""
                },
                columns: [
                    { data: "cve_linea_producto" },
                    { data: "descripcion" },
                    { data: "fecha_creacion" },
                    { data: "estado" },
                    { data: "options" }
                ],
                dom: "lBfrtip",
                buttons: [],
                responsive: true,   
                bDestroy: true,
                iDisplayLength: 10,
                order: [[0, "desc"]]
            });
        }
    }

    // ------------------------------------------------------------
    //  3) TABS BOOTSTRAP (SETEA SOLO SI EXISTEN)
    // ------------------------------------------------------------
    const primerTabEl = document.querySelector('#nav-tab a[href="#listlineasproductos"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarlineasproducto"]');
    

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl);
        firstTab  = new bootstrap.Tab(firstTabEl);
        tabNuevo  = firstTabEl;

        // OJO: solo si existe el form (si no, no tiene sentido resetear)
        if (formLineasProducto) {
            tabNuevo.addEventListener('click', () => {
                spanBtnText.textContent = 'REGISTRAR';
                formLineasProducto.reset();
                const idHidden = document.querySelector("#idlineaproducto");
                if (idHidden) idHidden.value = 0;
            });
        }
    } else {
        console.warn('Tabs de lineas no encontrados o btnText faltante.');
    }

    // ------------------------------------------------------------
    //  4) SUBMIT FORM (AQUÍ ESTABA TU ERROR EN SERVIDOR)
    //     SOLO AGREGAMOS EL LISTENER SI EL FORM EXISTE
    // ------------------------------------------------------------
    if (formLineasProducto) {

        formLineasProducto.addEventListener('submit', function(e){
            e.preventDefault();

            let formData = new FormData(formLineasProducto);
            let url = base_url + "/Inv_lineasdproducto/setLineaProducto";

            fetch(url, {
                method:"POST",
                body: formData
            })
            .then(res => res.json())
            .then(objData => {
                if (objData.status) {
                    if ($.fn.DataTable.isDataTable('#tableLineasProducto')) {
                        $('#tableLineasProducto').DataTable().ajax.reload();
                    }
                    if (primerTab) primerTab.show();

                    Swal.fire("Correcto", objData.msg, "success");
                    formLineasProducto.reset();

                    const idHidden = document.querySelector("#idlineaproducto");
                    if (idHidden) idHidden.value = 0;

                    if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire("Error", "Error de red o servidor.", "error");
            });
        });

    } else {
        // Esto explica EXACTO tu caso de servidor: el JS corre pero el form aún no está en DOM
        console.warn('formLineasProducto no encontrado en este render. (Si tu vista se carga por AJAX, esto es normal).');
    }

}, false);

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

            // por si el DOM aún no tenía referencias:
            const idHidden = document.querySelector("#idlineaproducto");
            const inpClave = document.querySelector('#clave-linea-producto-input');
            const inpDesc  = document.querySelector('#descripcion-linea-producto-textarea');
            const selEstado= document.querySelector('#estado-select');
            const btnText  = document.querySelector('#btnText');

            if (idHidden) idHidden.value = objData.data.idlineaproducto;
            if (inpClave) inpClave.value = objData.data.cve_linea_producto;
            if (inpDesc)  inpDesc.value  = objData.data.descripcion;
            if (selEstado)selEstado.value= objData.data.estado;

            if (btnText) btnText.textContent = "ACTUALIZAR";
            if (firstTab) firstTab.show();
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
                    if ($.fn.DataTable.isDataTable('#tableLineasProducto')) {
                        $('#tableLineasProducto').DataTable().ajax.reload();
                    }
                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            }
        }
    });
}
