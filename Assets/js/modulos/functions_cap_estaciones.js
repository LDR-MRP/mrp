let tableEstaciones;
let rowTable = "";
let divLoading = null;


let estacion = null;  // #idestacion (hidden)
let nombre = null;
let proceso = null;
let estandar = null;
let unidaddmedida = null;
let tiempo = null;
let mx = null;
let selectLineas = null;
let estado = null;
let descripcion = null;


// Referencias globales para tabs y botón
let primerTab = null; 
let firstTab = null; 
let tabNuevo = null; // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null; // span del botón (REGISTRAR / ACTUALIZAR)
let formEstaciones = null;

document.addEventListener('DOMContentLoaded', function () {


    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading = document.querySelector("#divLoading");
    formEstaciones = document.querySelector("#formEstaciones");
    spanBtnText = document.querySelector('#btnText');


    estacion = document.querySelector('#idestacion');
    nombre = document.querySelector('#nombre-estacion-input');
    proceso = document.querySelector('#proceso-estacion-input');
    estandar = document.querySelector('#estandar-input');
    unidaddmedida = document.querySelector('#unidad-medida-select');
    tiempo = document.querySelector('#tiempo-ajuste-input');
    mx = document.querySelector('#mx-input');
    selectLineas = document.querySelector('#listLineas');
    estado = document.querySelector('#estado-select');
    descripcion = document.querySelector('#descripcion-estacion-textarea');








    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formEstaciones) {
        console.warn('formEstaciones no encontrado. JS de lineas no se inicializa en esta vista.');
        return;
    }

    // --------------------------------------------------------------------
    //  CARGAR LÍNEAS POR AJAX
    // --------------------------------------------------------------------
    fntLineas(); 
    // --------------------------------------------------------------------
    //  DATATABLE ESTACIONES
    // --------------------------------------------------------------------
    const tableEl = document.querySelector('#tableEstaciones');

    if (!tableEl) {
        console.warn('tableEstaciones no encontrada en el DOM. No se inicializa DataTable.');
    } else {
        tableEstaciones = $(tableEl).DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "ajax": {
                "url": base_url + "/Cap_estaciones/getEstaciones",
                "dataSrc": ""
            },
            "columns": [
                { "data": "cve_estacion" },
                { "data": "nombre_estacion" },
                { "data": "nombre_linea" },
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
    }


    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP (solo si existen)
    // --------------------------------------------------------------------
    const primerTabElp = document.querySelector('#nav-tab a[href="#listEstaciones"]');
    const firstTabElp = document.querySelector('#nav-tab a[href="#agregarEstacion"]');

    if (primerTabElp && firstTabElp && spanBtnText) {
        // IMPORTANTE: NO usar "let" aquí, usamos las globales
        primerTab = new bootstrap.Tab(primerTabElp); // LISTA
        firstTab = new bootstrap.Tab(firstTabElp);  // NUEVO / ACTUALIZAR
        tabNuevo = firstTabElp;                     // elemento del tab

        // CLICK EN "NUEVO" → MODO NUEVO
        tabNuevo.addEventListener('click', () => {
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            estacion.value = '';
            formEstaciones.reset();
            if (selectLineas) selectLineas.value = '';

        });

        // CLICK EN "LISTA" → RESET
        primerTabElp.addEventListener('click', () => {
            estacion.value = '';
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            formEstaciones.reset();
            if (selectLineas) selectLineas.value = '';

        });
    } else {
        console.warn('Tabs de lineas no encontrados o btnText faltante.');
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM 
    // --------------------------------------------------------------------
    formEstaciones.addEventListener('submit', function (e) {
        e.preventDefault(); // evitar envío por URL



        // Validar planta si aplica
        // if (selectLineas && selectLineas.value === '') {
        //     Swal.fire("Aviso", "Debes seleccionar una planta.", "warning");
        //     return;
        // }

        if (divLoading) divLoading.style.display = "flex";

        let request = (window.XMLHttpRequest)
            ? new XMLHttpRequest()
            : new ActiveXObject('Microsoft.XMLHTTP');

        let ajaxUrl = base_url + '/Cap_estaciones/setEstacion';
        let formData = new FormData(formEstaciones);

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
                        if (tableEstaciones) tableEstaciones.ajax.reload();

                        // Modo NUEVO nuevamente
                        formEstaciones.reset();
                        if (selectLineas) selectLineas.value = '';
                        if (estado) estado.value = '1';
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
                        formEstaciones.reset();
                        if (selectLineas) selectLineas.value = '';
                        if (estado) estado.value = '1';
                        if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                        if (tabNuevo) tabNuevo.textContent = 'NUEVO';
                        if (primerTab) primerTab.show();
                        if (tableEstaciones) tableEstaciones.ajax.reload();
                    });
                }

            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        };
    });

}, false);


// ------------------------------------------------------------------------
// FUNCIÓN EDITAR ESTACION 
// ------------------------------------------------------------------------
function fntEditInfo(idestacion) {

    // Cambiar textos a modo ACTUALIZAR
    if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
    if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

    // Opcional: limpiar antes de llenar
    if (formEstaciones) formEstaciones.reset();

    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/Cap_estaciones/getEstacion/' + idestacion;

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
            if (!estacion) estacion = document.querySelector('#idestacion');
            if (!nombre) nombre = document.querySelector('#nombre-estacion-input');

            if (!proceso) proceso = document.querySelector('#proceso-estacion-input');
            if (!estandar) estandar = document.querySelector('#estandar-input');
            if (!unidaddmedida) unidaddmedida = document.querySelector('#unidad-medida-select');
            if (!tiempo) tiempo = document.querySelector('#tiempo-ajuste-input');
            if (!mx) mx = document.querySelector('#mx-input');

            if (!selectLineas) selectLineas = document.querySelector('#listLineas');
            if (!estado) estado = document.querySelector('#estado-select');
            if (!descripcion) descripcion = document.querySelector('#descripcion-estacion-textarea');




            if (estacion) estacion.value = objData.data.idestacion;        // id hidden
            if (nombre) nombre.value = objData.data.nombre_estacion;

            if (proceso) proceso.value = objData.data.proceso;
            if (estandar) estandar.value = objData.data.estandar;
            if (unidaddmedida) unidaddmedida.value = objData.data.unidad_medida;
            if (tiempo) tiempo.value = objData.data.tiempo_ajuste;
            if (mx) mx.value = objData.data.mxn;
            if (selectLineas) selectLineas.value = objData.data.lineaid;
            if (estado) estado.value = objData.data.estado;
            if (descripcion) descripcion.value = objData.data.descripcion;

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
function fntDelInfo(idestacion) {
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

        let ajaxUrl = base_url + '/Cap_estaciones/delEstacion';
        let strData = "idestacion=" + idestacion;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    if (tableEstaciones) tableEstaciones.ajax.reload();
                } else {
                    Swal.fire("Atención!", objData.msg, "error");
                }
            }
        }
    });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA ESTACION 
// ------------------------------------------------------------------------
function fntViewPlanta(idestacion) {
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/Cap_estaciones/getEstacion/' + idestacion;
    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

            if (objData.status) {
                let estadoUsuario = objData.data.estado == 1 ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>';

                document.querySelector("#celClave").innerHTML = objData.data.cve_planta;
                document.querySelector("#celNombre").innerHTML = objData.data.nombre_planta;
                document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
                document.querySelector("#celEstado").innerHTML = estadoUsuario;

                $('#modalViewPlanta').modal('show');
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE LÍNEAS
// ------------------------------------------------------------------------
function fntLineas(selectedValue = "") {
    if (document.querySelector('#listLineas')) {
        let ajaxUrl = base_url + '/cap_lineasdtrabajo/getSelectLineas';
        let request = (window.XMLHttpRequest) ?
            new XMLHttpRequest() :
            new ActiveXObject('Microsoft.XMLHTTP');
        request.open("GET", ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                document.querySelector('#listLineas').innerHTML = request.responseText;

                if (selectedValue !== "") {
                    select.value = selectedValue;
                }
            }
        }
    }
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA LA ESTACION
// ------------------------------------------------------------------------
function fntViewLinea(idestacion) {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/cap_estaciones/getEstacion/' + idestacion;
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

            if (objData.status) {
                let estadoEstacion = objData.data.estado == 2 ?
                    '<span class="badge bg-success">Activo</span>' :
                    '<span class="badge bg-danger">Inactivo</span>';

                document.querySelector("#celClave").innerHTML = objData.data.cve_estacion;
                document.querySelector("#celNombre").innerHTML = objData.data.nombre_estacion;
                document.querySelector("#celProceso").innerHTML = objData.data.proceso;
                document.querySelector("#celEstandar").innerHTML = objData.data.estandar;
                document.querySelector("#celUnidad").innerHTML = objData.data.unidad_medida;
                document.querySelector("#celTiempo").innerHTML = objData.data.tiempo_ajuste;
                document.querySelector("#celMx").innerHTML = objData.data.mxn;
                document.querySelector("#celLinea").innerHTML = objData.data.nombre_linea;
                document.querySelector("#celEstado").innerHTML = estadoEstacion;
                document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
                document.querySelector("#celFecha").innerHTML = objData.data.	fecha_creacion;

                $('#modalViewEstacion').modal('show');
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}
