let tablePlantas;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const planta = document.querySelector('#idplanta');
const nombre = document.querySelector('#nombre-planta-input');
const estado = document.querySelector('#estado-select');

// Mis referencias globales
let primerTab;   // Tab LISTA
let firstTab;    // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText;

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector('#nav-tab a[href="#listplantas"]');
    const firstTabEl = document.querySelector('#nav-tab a[href="#agregarplanta"]');

    primerTab = new bootstrap.Tab(primerTabEl); // LISTA
    firstTab = new bootstrap.Tab(firstTabEl);  // NUEVO / ACTUALIZAR
    tabNuevo = firstTabEl;
    spanBtnText = document.querySelector('#btnText');

    // --------------------------------------------------------------------
    //  DATATABLE PLANTAS
    // --------------------------------------------------------------------
    tablePlantas = $('#tablePlantas').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
            "url": " " + base_url + "/cap_plantas/getPlantas",
            "dataSrc": ""
        },
        "columns": [
            { "data": "cve_planta" },
            { "data": "nombre_planta" },
            { "data": "fecha_creacion" },
            { "data": "estado" },
            { "data": "options" }
        ],
        'dom': 'lBfrtip',
        'buttons': [],
        "resonsieve": "true",
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    const formPlantas = document.querySelector("#formPlantas");

    // --------------------------------------------------------------------
    //  CLICK EN "NUEVO" → MODO NUEVO
    // --------------------------------------------------------------------
    tabNuevo.addEventListener('click', () => {
        tabNuevo.textContent = 'NUEVO';
        spanBtnText.textContent = 'REGISTRAR';

        // Limpiar formulario
        formPlantas.reset();
        planta.value = '';
        estado.value = '1';
    });

    // --------------------------------------------------------------------
    // CLICK EN "PLANTAS" → RESETEAR NAV A NUEVO
    // --------------------------------------------------------------------
    primerTabEl.addEventListener('click', () => {
        tabNuevo.textContent = 'NUEVO';
        spanBtnText.textContent = 'REGISTRAR';
        planta.value = '';
        estado.value = '1';
        formPlantas.reset();
    });

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR PLANTA
    // --------------------------------------------------------------------
    formPlantas.addEventListener('submit', function (e) {
        e.preventDefault();

        divLoading.style.display = "flex";

        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/cap_plantas/setPlanta';
        let formData = new FormData(formPlantas);

        request.open("POST", ajaxUrl, true);
        request.send(formData);

        request.onreadystatechange = function () {

            if (request.readyState !== 4) return;

            divLoading.style.display = "none";

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

                        if (result.isConfirmed) {
                            // Seguir en modo NUEVO
                            formPlantas.reset();
                            planta.value = '';
                            estado.value = '1';
                            tabNuevo.textContent = 'NUEVO';
                            spanBtnText.textContent = 'REGISTRAR';
                            tablePlantas.api().ajax.reload();

                        } else {
                            // Regresar al listado
                            formPlantas.reset();
                            planta.value = '';
                            estado.value = '1';
                            tabNuevo.textContent = 'NUEVO';
                            spanBtnText.textContent = 'REGISTRAR';
                            primerTab.show();
                            tablePlantas.api().ajax.reload();
                        }

                    });
                } else {

                    Swal.fire({
                        title: objData.msg,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // Acción final después de OK (opcional)
                        formPlantas.reset();
                        planta.value = '';
                        estado.value = '1';
                        tabNuevo.textContent = 'NUEVO';
                        spanBtnText.textContent = 'REGISTRAR';
                        primerTab.show();
                        tablePlantas.api().ajax.reload();
                    });

                }

            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        };
    });

}, false);

// ------------------------------------------------------------------------
// FUNCIÓN EDITAR PLANTA → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(idplanta) {

    // Cambiar textos a modo ACTUALIZAR
    if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
    if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/cap_plantas/getPlanta/' + idplanta;

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {

            let objData = JSON.parse(request.responseText);

            if (objData.status) {

                planta.value = objData.data.idplanta;
                nombre.value = objData.data.nombre_planta;
                estado.value = objData.data.estado;

                // Cambiar al tab de captura
                if (firstTab) firstTab.show();

            } else {
                swal("Error", objData.msg, "error");
            }
        }
    }
}

// ------------------------------------------------------------------------
//  ELIMINAR UN REGISTRO DEL LISTADO
// ------------------------------------------------------------------------

function fntDelInfo(idplanta) {
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


        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/cap_plantas/delPlanta';
        let strData = "idPlanta=" + idplanta;
        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    tablePlantas.api().ajax.reload();
                } else {
                    Swal.fire("Atención!", objData.msg, "error");
                }
            }
        }


    });

}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA PLANTA
// ------------------------------------------------------------------------
function fntViewPlanta(idplanta) {
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/cap_plantas/getPlanta/' + idplanta;
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

