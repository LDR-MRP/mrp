let tableLineas;
let rowTable = "";
let divLoading = null;

// Inputs del formulario (los llenamos en DOMContentLoaded)
let planta = null;  // #idlinea (hidden)
let nombre = null;  // #nombre-linea-input
let estado = null;  // #estado-select

// Referencias globales para tabs y botón
let primerTab   = null; // instancia de bootstrap.Tab (lista)
let firstTab    = null; // instancia de bootstrap.Tab (nuevo/actualizar)
let tabNuevo    = null; // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null; // span del botón (REGISTRAR / ACTUALIZAR)
let selectPlantas = null;
let formLineas = null; 

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading    = document.querySelector("#divLoading");
    formLineas    = document.querySelector("#formLineas");
    spanBtnText   = document.querySelector('#btnText');
    selectPlantas = document.querySelector('#listPlantas');

    linea = document.querySelector('#idlinea');
    nombre = document.querySelector('#nombre-linea-input');
    estado = document.querySelector('#estado-select');

    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formLineas) {
        console.warn('formLineas no encontrado. JS de lineas no se inicializa en esta vista.');
        return;
    }

    // --------------------------------------------------------------------
    //  CARGAR PLANTAS POR AJAX
    // --------------------------------------------------------------------
    fntPlantas(); // solo llena el select

    // --------------------------------------------------------------------
    //  DATATABLE LINEAS
    // --------------------------------------------------------------------
    tableLineas = $('#tableLineas').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": base_url + "/cap_lineasdtrabajo/getLineas",
            "dataSrc": ""
        },
        "columns": [
            { "data": "cve_linea" },
            { "data": "nombre_linea" },
            { "data": "nombre_planta" },
            { "data": "fecha_creacion" },
            { "data": "estado" },
            { "data": "options" }
        ],
        'dom': 'lBfrtip',
        'buttons': [],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP (solo si existen)
    // --------------------------------------------------------------------
    const primerTabElp = document.querySelector('#nav-tab a[href="#listlineas"]');
    const firstTabElp  = document.querySelector('#nav-tab a[href="#agregarlinea"]');

    if (primerTabElp && firstTabElp && spanBtnText) {
        // ⚠️ IMPORTANTE: NO usar "let" aquí, usamos las globales
        primerTab = new bootstrap.Tab(primerTabElp); // LISTA
        firstTab  = new bootstrap.Tab(firstTabElp);  // NUEVO / ACTUALIZAR
        tabNuevo  = firstTabElp;                     // elemento del tab

        // CLICK EN "NUEVO" → MODO NUEVO
        tabNuevo.addEventListener('click', () => {
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            linea.value = '';
            formLineas.reset();
            if (selectPlantas) selectPlantas.value = '';
            
        });

        // CLICK EN "LISTA" → RESET
        primerTabElp.addEventListener('click', () => {
            linea.value = '';
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            formLineas.reset();
            if (selectPlantas) selectPlantas.value = '';
            
        });
    } else {
        console.warn('Tabs de lineas no encontrados o btnText faltante.');
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM → SOLO AJAX
    // --------------------------------------------------------------------
    formLineas.addEventListener('submit', function (e) {
        e.preventDefault(); // evitar envío por URL

   

        // Validar planta si aplica
        // if (selectPlantas && selectPlantas.value === '') {
        //     Swal.fire("Aviso", "Debes seleccionar una planta.", "warning");
        //     return;
        // }

        if (divLoading) divLoading.style.display = "flex";

        let request = (window.XMLHttpRequest)
            ? new XMLHttpRequest()
            : new ActiveXObject('Microsoft.XMLHTTP');

        let ajaxUrl  = base_url + '/cap_lineasdtrabajo/setLinea';
        let formData = new FormData(formLineas);

        request.open("POST", ajaxUrl, true);
        request.send(formData);

        request.onreadystatechange = function () {
            if (request.readyState !== 4) return;

            if (divLoading) divLoading.style.display = "none";

            if (request.status !== 200) {
                Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
                return;
            }

            let objData = JSON.parse(request.responseText);

            if (objData.status) {

                if (objData.tipo == 'insert') {

                    Swal.fire({
                        title: objData.msg,
                        text: '¿Deseas ingresar un nuevo registro?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Sí',
                        cancelButtonText: 'No',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {

                        // Siempre recargamos el DataTable
                        if (tableLineas) tableLineas.ajax.reload();

                        // Modo NUEVO nuevamente
                        formLineas.reset();
                        if (selectPlantas) selectPlantas.value = '';
                        if (estado) estado.value = '2';
                        if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                        if (tabNuevo) tabNuevo.textContent = 'NUEVO';

                        if (!result.isConfirmed && primerTab) {
                            // Regresar al listado
                            primerTab.show();
                        }

                    });
                } else {
                    // UPDATE
                    Swal.fire({
                        title: objData.msg,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        formLineas.reset();
                        if (selectPlantas) selectPlantas.value = '';
                        if (estado) estado.value = '2';
                        if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                        if (tabNuevo) tabNuevo.textContent = 'NUEVO';
                        if (primerTab) primerTab.show();
                        if (tableLineas) tableLineas.ajax.reload();
                    });
                }

            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        };
    });

}, false);

// ------------------------------------------------------------------------
// FUNCIÓN EDITAR LINEA → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(idlinea) {

    // Cambiar textos a modo ACTUALIZAR
    if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
    if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

    // Opcional: limpiar antes de llenar
    if (formLineas) formLineas.reset();

    let request = (window.XMLHttpRequest) 
        ? new XMLHttpRequest() 
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/cap_lineasdtrabajo/getLinea/' + idlinea;

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState != 4) return;
        if (request.status != 200) {
            Swal.fire("Error", "Error al consultar la línea.", "error");
            return;
        }

        let objData = JSON.parse(request.responseText);

        if (objData.status) {

            // Asegurarnos de tener las referencias por si el DOM cambió
            if (!planta)  planta  = document.querySelector('#idlinea');
            if (!nombre)  nombre  = document.querySelector('#nombre-linea-input');
            if (!estado)  estado  = document.querySelector('#estado-select');
            if (!selectPlantas) selectPlantas = document.querySelector('#listPlantas');

            if (planta)  planta.value  = objData.data.idlinea;        // id hidden
            if (nombre)  nombre.value  = objData.data.nombre_linea;   // ajusta al nombre exacto del JSON
            if (estado)  estado.value  = objData.data.estado;
            if (selectPlantas && objData.data.plantaid  ) {
                selectPlantas.value = objData.data.plantaid ;
            }

            // Cambiar al tab de captura
            if (firstTab) firstTab.show();

        } else {
            Swal.fire("Error", objData.msg, "error");
        }
    }
}

// ------------------------------------------------------------------------
//  ELIMINAR UN REGISTRO DEL LISTADO
// ------------------------------------------------------------------------
function fntDelInfo(idlinea) {
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
                    ¿Estás seguro de que deseas eliminar este registro? 
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

        if (!result.isConfirmed) {
            return;
        }

        let request = (window.XMLHttpRequest) 
            ? new XMLHttpRequest() 
            : new ActiveXObject('Microsoft.XMLHTTP');

        let ajaxUrl = base_url + '/cap_lineasdtrabajo/delLinea';
        let strData = "idlinea=" + idlinea;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    if (tableLineas) tableLineas.ajax.reload();
                } else {
                    Swal.fire("Atención!", objData.msg, "error");
                }
            }
        }
    });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA PLANTA (SI AÚN LO USAS)
// ------------------------------------------------------------------------
function fntViewPlanta(idlinea) {
    let request = (window.XMLHttpRequest) 
        ? new XMLHttpRequest() 
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/cap_lineasdtrabajo/getLinea/' + idlinea;
    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

            if (objData.status) {
                let estadoUsuario = objData.data.estado == 1 ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>';

                document.querySelector("#celClave").innerHTML  = objData.data.cve_planta;
                document.querySelector("#celNombre").innerHTML = objData.data.nombre_planta;
                document.querySelector("#celFecha").innerHTML  = objData.data.fecha_creacion;
                document.querySelector("#celEstado").innerHTML = estadoUsuario;

                $('#modalViewPlanta').modal('show');
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PLANTAS
// ------------------------------------------------------------------------
function fntPlantas(selectedValue = ""){
    if(document.querySelector('#listPlantas')){
        let ajaxUrl = base_url+'/cap_plantas/getSelectPlantas';
        let request = (window.XMLHttpRequest) ? 
                    new XMLHttpRequest() : 
                    new ActiveXObject('Microsoft.XMLHTTP');
        request.open("GET",ajaxUrl,true);
        request.send();
        request.onreadystatechange = function(){
            if(request.readyState == 4 && request.status == 200){
                document.querySelector('#listPlantas').innerHTML = request.responseText;

                      if (selectedValue !== "") {
                    select.value = selectedValue;
                }
            }
        }
    }
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA LA LINEA
// ------------------------------------------------------------------------
function fntViewLinea(idlinea) {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/cap_lineasdtrabajo/getLinea/' + idlinea;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

            if (objData.status) {
                let estadoUsuario = objData.data.estado == 2 ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>';

                document.querySelector("#celClave").innerHTML = objData.data.cve_linea;
                document.querySelector("#celNombre").innerHTML = objData.data.nombre_linea;
                document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
                document.querySelector("#celEstado").innerHTML = estadoUsuario;

                $('#modalViewLinea').modal('show');
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}
