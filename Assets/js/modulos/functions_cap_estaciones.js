let tableEstaciones;
let rowTable = "";
let divLoading = null;

// Inputs generales estación
let estacion = null;      // #idestacion (hidden)
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
let tabNuevo = null;      // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null;   // span del botón (REGISTRAR / ACTUALIZAR)
let formEstaciones = null;

// Campos sub-ensamble
let bloqueSubensamble = null;
let radiosAgregarSubensamble = null;
let subNombre = null;
let subProceso = null;
let subEstandar = null;
let subTiempo = null;
let subRadiosHerramientas = null;
let subHerramientasFeedback = null;

// Campos de mantenimiento
let tipoMantenimientoSelect = null;
let fechaProgramadaGroup = null;
let fechaProgramadaInput = null;
let fechaInicioInput = null;
let fechaFinInput = null;
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

    // Sub-ensamble
    bloqueSubensamble = document.querySelector('#bloqueSubensamble');
    radiosAgregarSubensamble = document.querySelectorAll('input[name="agregar_subensamble"]');
    subNombre = document.querySelector('#sub-nombre-estacion-input');
    subProceso = document.querySelector('#sub-proceso-estacion-input');
    subEstandar = document.querySelector('#sub-estandar-input');
    subTiempo = document.querySelector('#sub-tiempo-ajuste-input');
    subRadiosHerramientas = document.querySelectorAll('input[name="sub_requiere_herramientas"]');
    subHerramientasFeedback = document.querySelector('#subHerramientasFeedback');

    // Mantenimiento
    tipoMantenimientoSelect = document.querySelector('#tipo_mantenimiento');
    fechaProgramadaGroup = document.querySelector('#grupo-fecha-programada');
    fechaProgramadaInput = document.querySelector('#fecha_programada');
    fechaInicioInput = document.querySelector('#fecha_inicio');
    fechaFinInput = document.querySelector('#fecha_fin');
    comentarios = document.querySelector('#comentarios');

    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formEstaciones) {
        console.warn('formEstaciones no encontrado. JS de estaciones no se inicializa en esta vista.');
        return;
    }

    // --------------------------------------------------------------------
    //  INICIALIZAR SUB-ENSAMBLE
    // --------------------------------------------------------------------
    inicializarSubensamble();

    // --------------------------------------------------------------------
    //  CARGAR PLANTAS POR AJAX
    // --------------------------------------------------------------------
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
    { "data": "subensamble_nombre" },
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
    //  TABS BOOTSTRAP VER QUE EXITAAN
    // --------------------------------------------------------------------
    const primerTabElp = document.querySelector('#nav-tab a[href="#listEstaciones"]');
    const firstTabElp = document.querySelector('#nav-tab a[href="#agregarEstacion"]');

    if (primerTabElp && firstTabElp && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabElp); // LISTA
        firstTab = new bootstrap.Tab(firstTabElp);    // NUEVO / ACTUALIZAR
        tabNuevo = firstTabElp;

        // CLICK EN "NUEVO" → MODO NUEVO
        tabNuevo.addEventListener('click', () => {
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            if (estacion) estacion.value = '';
            formEstaciones.reset();
            if (selectLineas) selectLineas.value = '';
            if (selectPlantas) selectPlantas.value = '';
            resetSubensamble();
        });

        // CLICK EN "LISTA" → RESETE
        primerTabElp.addEventListener('click', () => {
            if (estacion) estacion.value = '';
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';
            formEstaciones.reset();
            if (selectLineas) selectLineas.value = '';
            if (selectPlantas) selectPlantas.value = '';
            resetSubensamble();
        });
    } else {
        console.warn('Tabs de estaciones no encontrados o btnText faltante.');
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM ESTACIONES
    // --------------------------------------------------------------------
    formEstaciones.addEventListener('submit', function (e) {
        e.preventDefault(); // evitar envío por URL

        limpiarValidacionSubensamble();

        const validacionSubensamble = validarSubensamble();
        const validacionGeneral = formEstaciones.checkValidity();

        formEstaciones.classList.add('was-validated');

        if (!validacionGeneral || !validacionSubensamble) {
            Swal.fire("Atención", "Por favor completa los campos obligatorios.", "warning");
            return;
        }

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

                        if (tableEstaciones) tableEstaciones.ajax.reload();

                        formEstaciones.reset();
                        if (selectPlantas) selectPlantas.value = '';
                        if (selectLineas) selectLineas.value = '';
                        if (estado) estado.value = '1';
                        if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                        if (tabNuevo) tabNuevo.textContent = 'NUEVO';
                        resetSubensamble();

                        if (!result.isConfirmed && primerTab) {
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
                        resetSubensamble();
                        if (primerTab) primerTab.show();
                        if (tableEstaciones) tableEstaciones.ajax.reload();
                    });
                }

            } else {
                Swal.fire("Error", objData.msg, "error");
            }
        };
    });

    // --------------------------------------------------------------------
    //  VALIDACIONES DE FECHAS DE MANTENIMIENTO
    // --------------------------------------------------------------------
    function getTodayYMD() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    if (tipoMantenimientoSelect) {
        tipoMantenimientoSelect.addEventListener('change', function () {
            const value = this.value;

            const aplicaFechaProgramada =
                value === 'preventivo' ||
                value === 'calibracion' ||
                value === 'predictivo';

            if (aplicaFechaProgramada) {
                if (fechaProgramadaGroup) fechaProgramadaGroup.classList.remove('d-none');
                if (fechaProgramadaInput) {
                    fechaProgramadaInput.required = true;
                    fechaProgramadaInput.min = getTodayYMD();
                }
            } else {
                if (fechaProgramadaGroup) fechaProgramadaGroup.classList.add('d-none');
                if (fechaProgramadaInput) {
                    fechaProgramadaInput.required = false;
                    fechaProgramadaInput.value = '';
                    fechaProgramadaInput.removeAttribute('min');
                }
                if (fechaInicioInput) fechaInicioInput.removeAttribute('min');
            }
        });
    }

    if (fechaProgramadaInput && fechaInicioInput) {
        fechaProgramadaInput.addEventListener('change', function () {
            const fechaProg = this.value;
            if (!fechaProg) {
                fechaInicioInput.removeAttribute('min');
                return;
            }

            fechaInicioInput.min = fechaProg;

            if (fechaInicioInput.value && fechaInicioInput.value < fechaProg) {
                fechaInicioInput.value = fechaProg;
            }

            if (fechaFinInput && fechaFinInput.value && fechaFinInput.value < fechaProg) {
                fechaFinInput.value = fechaProg;
            }
        });
    }

    if (fechaInicioInput && fechaFinInput) {
        fechaInicioInput.addEventListener('change', function () {
            const fechaIni = this.value;
            if (!fechaIni) {
                fechaFinInput.removeAttribute('min');
                return;
            }

            fechaFinInput.min = fechaIni;

            if (fechaFinInput.value && fechaFinInput.value < fechaIni) {
                fechaFinInput.value = fechaIni;
            }
        });
    }

    // --------------------------------------------------------------------
    //  FORM MANTENIMIENTO (NUEVO / UPDATE)
    // --------------------------------------------------------------------
    let formMantenimiento = document.querySelector("#formMantenimiento");
    if (formMantenimiento) {
        formMantenimiento.onsubmit = function (e) {
            e.preventDefault();

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

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
                        Swal.fire("¡Mantenimiento!", objData.msg, "success");

                    } else {
                        swal("Error", objData.msg, "error");
                    }
                }
                if (divLoading) divLoading.style.display = "none";
                return false;
            }
        }
    }

}, false);

// ------------------------------------------------------------------------
//  FUNCIONES SUB-ENSAMBLE
// ------------------------------------------------------------------------
function inicializarSubensamble() {
    if (radiosAgregarSubensamble && radiosAgregarSubensamble.length > 0) {
        radiosAgregarSubensamble.forEach(radio => {
            radio.addEventListener('change', function () {
                toggleSubensamble(this.value === '1');
            });
        });
    }

    const seleccionado = obtenerValorRadio('agregar_subensamble');
    toggleSubensamble(seleccionado === '1');
}

function toggleSubensamble(mostrar) {
    if (!bloqueSubensamble) return;

    if (mostrar) {
        bloqueSubensamble.classList.remove('d-none');
        setSubensambleRequired(true);
        setSubensambleDisabled(false);
    } else {
        bloqueSubensamble.classList.add('d-none');
        setSubensambleRequired(false);
        setSubensambleDisabled(true);
        limpiarCamposSubensamble();
        limpiarValidacionSubensamble();
    }
}

function setSubensambleRequired(required) {
    if (subNombre) subNombre.required = required;
    if (subProceso) subProceso.required = required;
    if (subEstandar) subEstandar.required = required;
    if (subTiempo) subTiempo.required = required;

    if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
        subRadiosHerramientas.forEach(radio => {
            radio.required = required;
        });
    }
}

function setSubensambleDisabled(disabled) {
    if (subNombre) subNombre.disabled = disabled;
    if (subProceso) subProceso.disabled = disabled;
    if (subEstandar) subEstandar.disabled = disabled;
    if (subTiempo) subTiempo.disabled = disabled;

    if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
        subRadiosHerramientas.forEach(radio => {
            radio.disabled = disabled;
        });
    }
}

function limpiarCamposSubensamble() {
    if (subNombre) subNombre.value = '';
    if (subProceso) subProceso.value = '';
    if (subEstandar) subEstandar.value = '';
    if (subTiempo) subTiempo.value = '';

    if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
        subRadiosHerramientas.forEach(radio => {
            radio.checked = false;
        });
    }
}

function limpiarValidacionSubensamble() {
    if (subNombre) subNombre.classList.remove('is-invalid');
    if (subProceso) subProceso.classList.remove('is-invalid');
    if (subEstandar) subEstandar.classList.remove('is-invalid');
    if (subTiempo) subTiempo.classList.remove('is-invalid');

    if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
        subRadiosHerramientas.forEach(radio => {
            radio.classList.remove('is-invalid');
        });
    }

    if (subHerramientasFeedback) {
        subHerramientasFeedback.classList.add('d-none');
    }
}

function validarSubensamble() {
    const agregarSub = obtenerValorRadio('agregar_subensamble');
    if (agregarSub !== '1') return true;

    let valido = true;

    if (subNombre && !subNombre.value.trim()) {
        subNombre.classList.add('is-invalid');
        valido = false;
    }

    if (subProceso && !subProceso.value.trim()) {
        subProceso.classList.add('is-invalid');
        valido = false;
    }

    if (subEstandar && !subEstandar.value.trim()) {
        subEstandar.classList.add('is-invalid');
        valido = false;
    }

    if (subTiempo && !subTiempo.value.trim()) {
        subTiempo.classList.add('is-invalid');
        valido = false;
    }

    const valorHerramientasSub = obtenerValorRadio('sub_requiere_herramientas');
    if (valorHerramientasSub === null) {
        if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
            subRadiosHerramientas.forEach(radio => radio.classList.add('is-invalid'));
        }
        if (subHerramientasFeedback) {
            subHerramientasFeedback.classList.remove('d-none');
        }
        valido = false;
    }

    return valido;
}

function obtenerValorRadio(name) {
    const radio = document.querySelector(`input[name="${name}"]:checked`);
    return radio ? radio.value : null;
}

function resetSubensamble() {
    const radioNo = document.querySelector('#agregar-subensamble-no');
    if (radioNo) radioNo.checked = true;
    toggleSubensamble(false);
}

// ------------------------------------------------------------------------
// FUNCIÓN EDITAR ESTACION
// ------------------------------------------------------------------------
function fntEditInfo(idestacion) {

    if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
    if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

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

            fntLineas(objData.data.plantaid, objData.data.lineaid);

            if (!estacion) estacion = document.querySelector('#idestacion');
            if (!nombre) nombre = document.querySelector('#nombre-estacion-input');
            if (!proceso) proceso = document.querySelector('#proceso-estacion-input');
            if (!estandar) estandar = document.querySelector('#estandar-input');
            if (!unidaddmedida) unidaddmedida = document.querySelector('#unidad-medida-select');
            if (!tiempo) tiempo = document.querySelector('#tiempo-ajuste-input');
            if (!mx) mx = document.querySelector('#mx-input');
            if (!selectPlantas) selectPlantas = document.querySelector('#listPlantas');
            if (!estado) estado = document.querySelector('#estado-select');
            if (!descripcion) descripcion = document.querySelector('#descripcion-estacion-textarea');

            const radiosHerramientas = document.querySelectorAll('input[name="requiere_herramientas"]');

            if (estacion) estacion.value = objData.data.idestacion;
            if (nombre) nombre.value = objData.data.nombre_estacion;
            if (proceso) proceso.value = objData.data.proceso;
            if (estandar) estandar.value = objData.data.estandar;
            if (unidaddmedida) unidaddmedida.value = objData.data.unidad_medida;
            if (tiempo) tiempo.value = objData.data.tiempo_ajuste;
            if (mx) mx.value = objData.data.mxn;
            if (selectPlantas) selectPlantas.value = objData.data.plantaid;
            if (estado) estado.value = objData.data.estado;
            if (descripcion) descripcion.value = objData.data.descripcion;

            if (radiosHerramientas && radiosHerramientas.length > 0) {
                radiosHerramientas.forEach(radio => {
                    if (radio.value == objData.data.herramientas) {
                        radio.checked = true;
                    }
                });
            }

            // ------------------------------------------------------------
            //  SUB-ENSAMBLE EN EDICIÓN
            //  Estos campos se llenarán si tu backend los retorna
            // ------------------------------------------------------------
            const radioSubSi = document.querySelector('#agregar-subensamble-si');
            const radioSubNo = document.querySelector('#agregar-subensamble-no');

            const tieneSubensamble = (
                objData.data.agregar_subensamble == 1 ||
                objData.data.tiene_subensamble == 1
            );

            if (tieneSubensamble) {
                if (radioSubSi) radioSubSi.checked = true;
                toggleSubensamble(true);

                if (subNombre) subNombre.value = objData.data.sub_nombre_estacion || '';
                if (subProceso) subProceso.value = objData.data.sub_proceso || '';
                if (subEstandar) subEstandar.value = objData.data.sub_estandar || '';
                if (subTiempo) subTiempo.value = objData.data.sub_tiempo_ajuste || '';

                if (subRadiosHerramientas && subRadiosHerramientas.length > 0) {
                    subRadiosHerramientas.forEach(radio => {
                        if (radio.value == objData.data.sub_herramientas) {
                            radio.checked = true;
                        }
                    });
                }
            } else {
                if (radioSubNo) radioSubNo.checked = true;
                toggleSubensamble(false);
            }

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
//  VER EL DETALLE DE LA ESTACION (PLANTA?)
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

                let estadoEstacion = objData.data.estado == 2
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';

                let herramientasEstacion = objData.data.herramientas == 1
                    ? '<span class="badge bg-info">Sí</span>'
                    : '<span class="badge bg-secondary">No</span>';

                let tieneSubensamble = objData.data.tiene_subensamble == 1
                    ? '<span class="badge bg-success">Sí</span>'
                    : '<span class="badge bg-secondary">No</span>';

                let subHerramientas = objData.data.sub_herramientas == 1
                    ? '<span class="badge bg-info">Sí</span>'
                    : '<span class="badge bg-secondary">No</span>';

                let subEstado = '-';
                if (objData.data.sub_estado != null) {
                    subEstado = objData.data.sub_estado == 2
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>';
                }

                document.querySelector("#celClave").innerHTML = objData.data.cve_estacion || '-';
                document.querySelector("#celNombre").innerHTML = objData.data.nombre_estacion || '-';
                document.querySelector("#celProceso").innerHTML = objData.data.proceso || '-';
                document.querySelector("#celEstandar").innerHTML = objData.data.estandar || '-';
                document.querySelector("#celUnidad").innerHTML = objData.data.unidad_medida || '-';
                document.querySelector("#celTiempo").innerHTML = objData.data.tiempo_ajuste || '-';
                document.querySelector("#celMx").innerHTML = objData.data.mxn || '-';
                document.querySelector("#celLinea").innerHTML = objData.data.nombre_linea || '-';
                document.querySelector("#celHerramientas").innerHTML = herramientasEstacion;
                document.querySelector("#celTieneSubensamble").innerHTML = tieneSubensamble;
                document.querySelector("#celEstado").innerHTML = estadoEstacion;
                document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion || '-';
                document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion || '-';

                console.log(objData.data.sub_nombre_estacion);

                // SUB-ENSAMBLE
                document.querySelector("#celSubNombre").innerHTML = objData.data.sub_nombre_estacion || '<span class="text-muted fst-italic">Sin sub-ensamble registrado</span>';
                document.querySelector("#celSubProceso").innerHTML = objData.data.sub_proceso || '<span class="text-muted fst-italic">Sin información</span>';
                document.querySelector("#celSubEstandar").innerHTML = objData.data.sub_estandar || '<span class="text-muted fst-italic">Sin información</span>';
                document.querySelector("#celSubTiempo").innerHTML = objData.data.sub_tiempo_ajuste || '<span class="text-muted fst-italic">Sin información</span>';
                document.querySelector("#celSubHerramientas").innerHTML = objData.data.sub_nombre_estacion ? subHerramientas : '<span class="text-muted fst-italic">No aplica</span>';
                document.querySelector("#celSubEstado").innerHTML = objData.data.sub_nombre_estacion ? subEstado : '<span class="text-muted fst-italic">No aplica</span>';

                $('#modalViewEstacion').modal('show');
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
    const selectPlantasLocal = document.querySelector('#listPlantas');
    if (selectPlantasLocal) {
        let ajaxUrl = base_url + '/Cap_plantas/getSelectPlantas';
        let request = (window.XMLHttpRequest) ?
            new XMLHttpRequest() :
            new ActiveXObject('Microsoft.XMLHTTP');

        request.open("GET", ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                selectPlantasLocal.innerHTML = request.responseText;

                if (selectedValue !== "") {
                    selectPlantasLocal.value = selectedValue;
                }

                if (selectPlantasLocal.value !== "") {
                    fntLineas(selectPlantasLocal.value);
                }
            }
        }

        selectPlantasLocal.addEventListener('change', function () {
            const idPlanta = this.value;
            fntLineas(idPlanta);
        });
    }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE LÍNEAS
// ------------------------------------------------------------------------
function fntLineas(idPlanta, selectedLinea = "") {
    const selectLineasLocal = document.querySelector('#listLineas');

    if (!selectLineasLocal) return;

    if (!idPlanta) {
        selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
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
            selectLineasLocal.innerHTML = request.responseText;

            if (selectedLinea !== "") {
                selectLineasLocal.value = selectedLinea;
            }
        }
    }
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA ESTACION
// ------------------------------------------------------------------------
function fntViewLineaold(idestacion) {
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

    const formMantenimiento = document.querySelector("#formMantenimiento");
    if (formMantenimiento) formMantenimiento.reset();

    const modal = document.getElementById('modalMantenimiento');
    const inputidEstacions = modal ? modal.querySelector('#idestacionmto') : null;
    if (inputidEstacions) inputidEstacions.value = idestacion || '';

    if (tipoMantenimientoSelect) {
        tipoMantenimientoSelect.dispatchEvent(new Event('change'));
    }

    cargarHistoricoMantenimientos(idestacion);

    activarTabAgregar();
    $('#modalMantenimiento').modal('show');
}

// ------------------------------------------------------------------------
//  EDITAR MANTENIMIENTO
// ------------------------------------------------------------------------
function fnteditMantenimiento(idmantenimiento) {

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
                const strresponsable = document.querySelector('#responsable-mantenimiento-input');
                const inputIdmantenimiento = document.querySelector('#idmantenimiento');
                const tipoMantenimientoSelectLocal = document.querySelector('#tipo_mantenimiento');
                const fechaProgramadaInputLocal = document.querySelector('#fecha_programada');
                const fechaInicioInputLocal = document.querySelector('#fecha_inicio');
                const fechaFinInputLocal = document.querySelector('#fecha_fin');
                const estadoSelect = document.querySelector('#estado-mantenimiento-select');
                const comentariosInput = document.querySelector('#comentarios');

                if (inputIdEstacion) inputIdEstacion.value = objData.data.estacionid;
                if (strresponsable) strresponsable.value = objData.data.responsable;
                if (inputIdmantenimiento) inputIdmantenimiento.value = objData.data.idmantenimiento;
                if (tipoMantenimientoSelectLocal) tipoMantenimientoSelectLocal.value = objData.data.tipo;
                if (fechaProgramadaInputLocal) fechaProgramadaInputLocal.value = objData.data.fecha_programada;
                if (fechaInicioInputLocal) fechaInicioInputLocal.value = objData.data.fecha_inicio;
                if (fechaFinInputLocal) fechaFinInputLocal.value = objData.data.fecha_fin;
                if (estadoSelect) estadoSelect.value = objData.data.mantenimiento;
                if (comentariosInput) comentariosInput.value = objData.data.comentarios;

                if (tipoMantenimientoSelectLocal) {
                    tipoMantenimientoSelectLocal.dispatchEvent(new Event('change'));
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

// ------------------------------------------------------------------------
//  CARGAR HISTÓRICO DE MANTENIMIENTOS
// ------------------------------------------------------------------------
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

                    let badgeEstado = "";
                    switch (String(mto.mantenimiento)) {
                        case "2":
                            badgeEstado = '<span class="badge bg-warning">Pendiente</span>';
                            break;
                        case "3":
                            badgeEstado = '<span class="badge bg-info">En proceso</span>';
                            break;
                        case "4":
                            badgeEstado = '<span class="badge bg-success">Finalizado</span>';
                            break;
                        case "5":
                            badgeEstado = '<span class="badge bg-danger">Cancelado</span>';
                            break;
                        default:
                            badgeEstado = '<span class="badge bg-secondary">Sin estatus</span>';
                            break;
                    }

                    html += `
                        <tr>
                            <td>${mto.idmantenimiento}</td>
                            <td>${mto.tipo}</td>
                            <td>${mto.fecha_inicio ?? ''}</td>
                            <td>${mto.fecha_fin ?? ''}</td>
                            <td>${mto.comentarios ?? ''}</td>
                            <td>${mto.responsable ?? ''}</td>
                            <td>${badgeEstado}</td>
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

// ------------------------------------------------------------------------
//  ACTIVAR TAB AGREGAR MANTENIMIENTO
// ------------------------------------------------------------------------
function activarTabAgregar() {
    const paneAgregar = document.getElementById("pane-agregar-mto");
    const tabAgregar = document.getElementById("tab-agregar-mto");

    if (paneAgregar && tabAgregar) {
        document.querySelectorAll('#nav-tab-mto button').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        document.querySelectorAll('#nav-tabContent-mto .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        tabAgregar.classList.add('active');
        tabAgregar.setAttribute('aria-selected', 'true');

        paneAgregar.classList.add('show', 'active');
    }
}