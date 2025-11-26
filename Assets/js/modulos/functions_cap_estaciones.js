let tableEstaciones;
let rowTable = "";
let divLoading = null;


let estacion = null;  // #idestacion (hidden)
let idestacion = null;
let idmantenimiento = null;
let nombre = null;
let proceso = null;
let estandar = null;
let unidaddmedida = null;
let tiempo = null;
let mx = null;
let selectPlantas = null;
let selectLineas = null;
let estado = null;
let descripcion = null;


// Referencias globales para tabs y botón
let primerTab = null;
let firstTab = null;
let tabNuevo = null; // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null; // span del botón (REGISTRAR / ACTUALIZAR)
let formEstaciones = null;


let tipoMantenimientoSelect = null;
let fechaProgramadaGroup = null;
let fechaProgramadaInput = null;
let fechaInicioInput = null;
let fechaFinInput = null;
let descripcionTxt = null;
let comentarios = null;

document.addEventListener('DOMContentLoaded', function () {


    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading = document.querySelector("#divLoading");
    formEstaciones = document.querySelector("#formEstaciones");
    spanBtnText = document.querySelector('#btnText');


    estacion = document.querySelector('#idestacion');
    idestacion = document.querySelector('#idestacion');
    nombre = document.querySelector('#nombre-estacion-input');
    proceso = document.querySelector('#proceso-estacion-input');
    estandar = document.querySelector('#estandar-input');
    unidaddmedida = document.querySelector('#unidad-medida-select');
    tiempo = document.querySelector('#tiempo-ajuste-input');
    mx = document.querySelector('#mx-input');
    selectPlantas = document.querySelector('#listPlantas');
    selectLineas = document.querySelector('#listLineas');
    estado = document.querySelector('#estado-select');
    descripcion = document.querySelector('#descripcion-estacion-textarea');

    tipoMantenimientoSelect = document.querySelector('#tipo_mantenimiento');
    fechaProgramadaGroup = document.querySelector('#grupo-fecha-programada');


    fechaProgramadaInput = document.querySelector('#fecha_programada');
    fechaInicioInput = document.querySelector('#fecha_inicio');
    fechaFinInput = document.querySelector('#fecha_fin');
    comentarios = document.querySelector('#comentarios');


    let tiposConFecha = ['preventivo', 'calibracion', 'predictivo'];









    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formEstaciones) {
        console.warn('formEstaciones no encontrado. JS de lineas no se inicializa en esta vista.');
        return;
    }

    // --------------------------------------------------------------------
    //  CARGAR PLANTAS  POR AJAX
    // --------------------------------------------------------------------
    // fntLineas(); 
    fntPlantas();
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
                { "data": "estacion_mantenimiento" },
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
            if (selectPlantas) selectPlantas.value = '';

        });

        // CLICK EN "LISTA" → RESET
        primerTabElp.addEventListener('click', () => {
            estacion.value = '';
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            formEstaciones.reset();
            if (selectLineas) selectLineas.value = '';
            if (selectPlantas) selectPlantas.value = '';

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
                        if (selectPlantas) selectPlantas.value = '';
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
                        if (selectPlantas) selectPlantas.value = '';
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




    tipoMantenimientoSelect.addEventListener('change', function () {
        const mostrar = this.value === 'preventivo'
            || this.value === 'calibracion'
            || this.value === 'predictivo';

        if (mostrar) {
            fechaProgramadaGroup.classList.remove('d-none');
            fechaProgramadaInput.required = true;   // lo hace obligatorio
        } else {
            fechaProgramadaGroup.classList.add('d-none');
            fechaProgramadaInput.required = false;  // deja de ser obligatorio
            fechaProgramadaInput.value = '';        // limpia el valor
        }
    });





    //NUEVA MANTENIMEINTO
    let formMantenimiento = document.querySelector("#formMantenimiento");
    formMantenimiento.onsubmit = function (e) {
        e.preventDefault();

        divLoading.style.display = "flex";
        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/Cap_estaciones/setMantenimiento';
        let formData = new FormData(formMantenimiento);
        request.open("POST", ajaxUrl, true);
        request.send(formData);
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {

                let objData = JSON.parse(request.responseText);
                if (objData.status) {

                    if (tableEstaciones) tableEstaciones.ajax.reload();
                    $('#modalMantenimiento').modal("hide");
                    formMantenimiento.reset();

                } else {
                    swal("Error", objData.msg, "error");
                }
            }
            divLoading.style.display = "none";
            return false;
        }
    }





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


          const radiosHerramientas  = document.querySelectorAll('input[name="requiere_herramientas"]');





            if (estacion) estacion.value = objData.data.idestacion;
            if (nombre) nombre.value = objData.data.nombre_estacion;

            if (proceso) proceso.value = objData.data.proceso;
            if (estandar) estandar.value = objData.data.estandar;
            if (unidaddmedida) unidaddmedida.value = objData.data.unidad_medida;
            if (tiempo) tiempo.value = objData.data.tiempo_ajuste;
            if (mx) mx.value = objData.data.mxn;
            if (selectPlantas) selectPlantas.value = objData.data.plantaid;
            if (selectLineas) selectLineas.value = objData.data.lineaid;
            if (estado) estado.value = objData.data.estado;
            if (descripcion) descripcion.value = objData.data.descripcion;

          if (radiosHerramientas && radiosHerramientas.length > 0) {
    radiosHerramientas.forEach(radio => {
        if (radio.value == objData.data.herramientas) {
            radio.checked = true;
        }
    });
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
//  VER EL CATALOGO DE PLANTAS
// ------------------------------------------------------------------------
function fntPlantas(selectedValue = "") {
    const selectPlantas = document.querySelector('#listPlantas');
    if (selectPlantas) {
        let ajaxUrl = base_url + '/Cap_plantas/getSelectPlantas';
        let request = (window.XMLHttpRequest) ?
            new XMLHttpRequest() :
            new ActiveXObject('Microsoft.XMLHTTP');

        request.open("GET", ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                // Rellenamos el select de plantas
                selectPlantas.innerHTML = request.responseText;

                // Si viene un valor seleccionado, lo marcamos
                if (selectedValue !== "") {
                    selectPlantas.value = selectedValue;
                }

                // Cargar líneas de la planta seleccionada al inicio
                if (selectPlantas.value !== "") {
                    fntLineas(selectPlantas.value);
                }
            }
        }

        // AQUÍ APLICAMOS EL onchange
        selectPlantas.addEventListener('change', function () {
            const idPlanta = this.value;
            fntLineas(idPlanta); // Cargamos las líneas de esa planta
        });
    }
}


// ------------------------------------------------------------------------
//  VER EL CATALOGO DE LÍNEAS
// ------------------------------------------------------------------------
function fntLineas(idPlanta, selectedLinea = "") {
    const selectLineas = document.querySelector('#listLineas');

    if (!selectLineas) return;

    // Si no hay planta seleccionada, limpiamos las líneas
    if (!idPlanta) {
        selectLineas.innerHTML = '<option value="">--Seleccione--</option>';
        return;
    }

    let ajaxUrl = base_url + '/Cap_lineasdtrabajo/getSelectLineas/' + idPlanta;
    let request = (window.XMLHttpRequest) ?
        new XMLHttpRequest() :
        new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            // El controlador debe regresar los <option> ya listos
            selectLineas.innerHTML = request.responseText;

            // Si quieres preseleccionar alguna línea
            if (selectedLinea !== "") {
                selectLineas.value = selectedLinea;
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
                document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;

                $('#modalViewEstacion').modal('show');
            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        }
    }
}



// ------------------------------------------------------------------------
//  MOSTRAR MODAL DE MANTENIMIENTO
// ------------------------------------------------------------------------
function fntAddMantenimiento(idestacion) {

    const titleModal = document.querySelector('#titleModalMto');
    const btnText = document.querySelector('#btnTextMto');

    if (titleModal) titleModal.innerHTML = "Registrar Mantenimiento";
    if (btnText) btnText.innerHTML = "Registrar";

    document.querySelector("#formMantenimiento").reset();
    const modal = document.getElementById('modalMantenimiento');
    const inputidEstacions = modal.querySelector('#idestacionmto');
    if (inputidEstacions) inputidEstacions.value = idestacion || '';


    cargarHistoricoMantenimientos(idestacion);



    activarTabAgregar();
    $('#modalMantenimiento').modal('show');
}


function fnteditMantenimiento(idmantenimiento) {

    // Título y texto del botón del modal
    const titleModal = document.querySelector('#titleModalMto');
    const btnText = document.querySelector('#btnTextMto');

    if (titleModal) titleModal.innerHTML = "Actualizar Mantenimiento";
    if (btnText) btnText.innerHTML = "Actualizar";

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Cap_estaciones/getMantenimiento/' + idmantenimiento;
    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

            if (objData.status) {


                const inputIdEstacion = document.querySelector('#idestacionmto');
                const inputIdmantenimiento = document.querySelector('#idmantenimiento');
                const tipoMantenimientoSelect = document.querySelector('#tipo_mantenimiento');
                const fechaProgramadaInput = document.querySelector('#fecha_programada');
                const fechaInicioInput = document.querySelector('#fecha_inicio');
                const fechaFinInput = document.querySelector('#fecha_fin');
                const estadoSelect = document.querySelector('#estado-mantenimiento-select');
                const comentariosInput = document.querySelector('#comentarios');


                if (inputIdEstacion) inputIdEstacion.value = objData.data.estacionid;
                if (inputIdmantenimiento) inputIdmantenimiento.value = objData.data.idmantenimiento;
                if (tipoMantenimientoSelect) tipoMantenimientoSelect.value = objData.data.tipo;
                if (fechaProgramadaInput) fechaProgramadaInput.value = objData.data.fecha_programada;
                if (fechaInicioInput) fechaInicioInput.value = objData.data.fecha_inicio;
                if (fechaFinInput) fechaFinInput.value = objData.data.fecha_fin;
                if (estadoSelect) estadoSelect.value = objData.data.mantenimiento;
                if (comentariosInput) comentariosInput.value = objData.data.comentarios;


                if (tipoMantenimientoSelect) {
                    tipoMantenimientoSelect.dispatchEvent(new Event('change'));
                }

                if (objData.data.estacionid) {
                    cargarHistoricoMantenimientos(objData.data.estacionid);
                }
                activarTabAgregar();
                $('#modalMantenimiento').modal('show');

            } else {
                swal("Error", objData.msg, "error");
            }
        }
    }
}


function cargarHistoricoMantenimientos(idEstacion) {
    const tbody = document.querySelector('#tableHistoricoMantenimiento tbody');

    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center">Cargando historial...</td>
        </tr>
    `;

    let requestHist = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrlHist = base_url + '/Cap_estaciones/getMantenimientosByEstacion/' + idEstacion;
    requestHist.open("GET", ajaxUrlHist, true);
    requestHist.send();

    requestHist.onreadystatechange = function () {
        if (requestHist.readyState == 4 && requestHist.status == 200) {
            let objData = JSON.parse(requestHist.responseText);

            if (objData.status && Array.isArray(objData.data) && objData.data.length > 0) {

                let html = "";

                objData.data.forEach(function (mto, index) {
                    html += `
                        <tr>
                            <td>${mto.idmantenimiento}</td>
                            <td>${mto.tipo}</td>
                            <td>${mto.fecha_inicio ?? ''}</td>
                            <td>${mto.fecha_fin ?? ''}</td>
                            <td>${mto.comentarios ?? ''}</td>
                            <td>${mto.estado_texto ?? ''}</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;

            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">Sin mantenimientos registrados para esta estación.</td>
                    </tr>
                `;
            }
        }
    }
}


function activarTabAgregar() {
    // Contenido del tab
    const paneAgregar = document.getElementById("pane-agregar-mto");
    // Botón del tab
    const tabAgregar = document.getElementById("tab-agregar-mto");

    if (paneAgregar && tabAgregar) {
        // Quitar clases activas a todos los tabs
        document.querySelectorAll('#nav-tab-mto button').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        // Quitar clases activas a todos los panes
        document.querySelectorAll('#nav-tabContent-mto .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        // Activar el tab AGREGAR
        tabAgregar.classList.add('active');
        tabAgregar.setAttribute('aria-selected', 'true');

        // Activar el pane AGREGAR
        paneAgregar.classList.add('show', 'active');
    }
}



