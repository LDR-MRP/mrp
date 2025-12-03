let tableAlmacenes;
let tableDocumentos;
let divLoading = null;

// Inputs / elementos del formulario
let estacion = null;          // #idcomponente (hidden, id del componente)
let idcomponentedoc = null;   // #idcomponentedoc (hidden para docs)

// Referencias globales para tabs y botón
let primerTab = null;         // instancia bootstrap.Tab (LISTA)
let tabNuevo = null;          // <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null;       // span del botón (REGISTRAR / ACTUALIZAR)
let formBom = null;           // formulario de componentes
let formDocumentacion = null; // formulario de documentación

// NAVS INFERIORES 
let btnInfoGeneral = null;
let btnDocumentacion = null;
let btnProcesos = null;
let btnFinalizado = null;

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading        = document.querySelector("#divLoading");
    formBom           = document.querySelector("#formBomComponentes");
    formDocumentacion = document.querySelector("#formDocumentacion");
    spanBtnText       = document.querySelector('#btnText');

    estacion        = document.querySelector('#idcomponente');
    idcomponentedoc = document.querySelector('#idcomponentedoc');

    // DECLARACIÓN DE NAVS INFERIORES
    btnInfoGeneral   = document.getElementById('informacion_general');
    btnDocumentacion = document.getElementById('documentacion');
    btnProcesos      = document.getElementById('procesos');
    btnFinalizado    = document.getElementById('pills-finish-tab');

    // Estado inicial de las tabs inferiores (según si ya hay id componente)
    refreshLowerTabs();

    // --------------------------------------------------------------------
    //  CARGAR SELECTS (PRODUCTOS / LÍNEAS)
    // --------------------------------------------------------------------
    fntInventarios();      
    fntLineasProducto();    

    // --------------------------------------------------------------------
    //  DATATABLE DOCUMENTOS
    // --------------------------------------------------------------------
    const tableDocEl = document.querySelector('#tableDocumentos');

    if (!tableDocEl) {
        console.warn('tableDocumentos no encontrada en el DOM. No se inicializa DataTable de documentos.');
    } else {
tableDocumentos = $(tableDocEl).DataTable({
    "aProcessing": true,
    "aServerSide": true,
    "ajax": {
        "url": base_url + "/Plan_bomcomponentes/getDocumentos",
        "type": "POST", // o "GET", según como lo manejes en tu controlador
        "data": function (d) {
            // Aquí agregas el id del componente que está en el input oculto
            d.idcomponentedoc = idcomponentedoc ? idcomponentedoc.value : '';
        },
        "dataSrc": ""
    },
    "columns": [
        { "data": "descripcion" },
        { "data": "documento" },
        { "data": "fecha_creacion" },
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
    //  DATATABLE COMPONENTES
    // --------------------------------------------------------------------
    const tableCompEl = document.querySelector('#tableComponentes');

    if (!tableCompEl) {
        console.warn('tableComponentes no encontrada en el DOM. No se inicializa DataTable de componentes.');
    } else {
        tableAlmacenes = $(tableCompEl).DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "ajax": {
                "url": base_url + "/Plan_bomcomponentes/getComponentes",
                "dataSrc": ""
            },
            "columns": [
                { "data": "cve_componente" },
                { "data": "cve_art" },
                { "data": "descripcion_producto" },
                { "data": "cve_linea" },
                { "data": "descripcion_linea" },
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


    if (btnDocumentacion) {
    btnDocumentacion.addEventListener('click', function () {
        if (tableDocumentos) {
            tableDocumentos.ajax.reload();
        }
    });
}

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP (LISTA / NUEVO)
    //  OJO: en el HTML el tab es #agregarProducto
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector('#nav-tab a[href="#listComponentes"]');
    const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarProducto"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl); // LISTA
        tabNuevo  = firstTabEl;                     // NUEVO / ACTUALIZAR

 
// CLICK EN "NUEVO" → modo NUEVO
tabNuevo.addEventListener('click', () => {
    tabNuevo.textContent    = 'NUEVO';
    spanBtnText.textContent = 'REGISTRAR';

    if (estacion) estacion.value = '';
    if (idcomponentedoc) idcomponentedoc.value = '';

    if (formBom) formBom.reset();

    const selectProductos       = document.querySelector('#listProductos');
    const selectLineasProductos = document.querySelector('#listLineasProductos');

    if (selectProductos)       selectProductos.value = '';
    if (selectLineasProductos) selectLineasProductos.value = '';

    // Nuevo producto → bloquear pasos inferiores otra vez
    refreshLowerTabs();

    // 🔹 Siempre arrancar en Información General
    setInfoGeneralActive();
});


        // CLICK EN "LISTA" → reset form
        primerTabEl.addEventListener('click', () => {
            if (estacion) estacion.value = '';
            if (idcomponentedoc) idcomponentedoc.value = '';

            tabNuevo.textContent    = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';

            if (formBom) formBom.reset();

            const selectProductos       = document.querySelector('#listProductos');
            const selectLineasProductos = document.querySelector('#listLineasProductos');

            if (selectProductos)       selectProductos.value = '';
            if (selectLineasProductos) selectLineasProductos.value = '';

            // Regresamos a listado → bloquear pasos inferiores
            refreshLowerTabs();
        });
    } else {
        console.warn('Tabs de componentes o btnText no encontrados.');
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM PARA AGREGAR / ACTUALIZAR COMPONENTES
    // --------------------------------------------------------------------
    if (formBom) {
        formBom.addEventListener('submit', function (e) {
            e.preventDefault(); // evitar envío por URL

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

            let ajaxUrl  = base_url + '/Plan_bomcomponentes/setComponente';
            let formData = new FormData(formBom);

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

                    if (objData.tipo === 'insert') {

                        // Guardamos el id del componente en ambos inputs
                        if (estacion)        estacion.value        = objData.idcomponente;
                        if (idcomponentedoc) idcomponentedoc.value = objData.idcomponente;

                        // Ya hay id de componente → habilitar tabs inferiores
                        refreshLowerTabs();

                        Swal.fire({
                            title: objData.msg,
                            text: 'Ahora avanzaremos a la carga de la documentación del producto.',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {

                            if (tableAlmacenes) tableAlmacenes.ajax.reload();

                            // Cambiamos directamente a la tab DOCUMENTACIÓN
                            if (btnDocumentacion) {
                                const tabDoc = new bootstrap.Tab(btnDocumentacion);
                                tabDoc.show();
                            }

                            formBom.reset();

                            const selectProductos       = document.querySelector('#listProductos');
                            const selectLineasProductos = document.querySelector('#listLineasProductos');

                            if (selectProductos)       selectProductos.value = '';
                            if (selectLineasProductos) selectLineasProductos.value = '';

                            if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                            if (tabNuevo)    tabNuevo.textContent    = 'NUEVO';

                            // Si NO confirma (por si pones otro botón a futuro), regresa a la lista
                            if (!result.isConfirmed && primerTab) {
                                primerTab.show();
                            }
                        });

                    } else {
                        // caso UPDATE si lo necesitas
                    }

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            };
        });
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM PARA GUARDAR LOS DOCUMENTOS
    // --------------------------------------------------------------------
    if (formDocumentacion) {
        formDocumentacion.addEventListener('submit', function (e) {
            e.preventDefault(); // evitar envío por URL

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

            let ajaxUrl  = base_url + '/Plan_bomcomponentes/setDocumentacion';
            let formData = new FormData(formDocumentacion);

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

                    if (objData.tipo === 'insert') {

                        if (tableDocumentos) tableDocumentos.ajax.reload();

                        // Aseguramos que siga teniéndo el id del componente

                       

                        // Por si quisieras en un futuro habilitar más pasos en base a docs:
                        refreshLowerTabs();

                        Swal.fire({
                            title:'¡Operación exitosa!',
                            text:  objData.msg,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {

                            formDocumentacion.reset();
                             if (idcomponentedoc) idcomponentedoc.value = objData.idcomponente;

                            const selectProductos       = document.querySelector('#listProductos');
                            const selectLineasProductos = document.querySelector('#listLineasProductos');

                            if (selectProductos)       selectProductos.value = '';
                            if (selectLineasProductos) selectLineasProductos.value = '';

                            if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                            if (tabNuevo)    tabNuevo.textContent    = 'NUEVO';

                            if (!result.isConfirmed && primerTab) {
                                primerTab.show();
                            }
                        });

                    } else {
                        // caso UPDATE docs
                    }

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            };
        });
    }

}, false);

// ------------------------------------------------------------------------
//  CONTROL DE HABILITAR / DESHABILITAR TABS INFERIORES
// ------------------------------------------------------------------------
function refreshLowerTabs() {
    // Regla: solo cuando idcomponentedoc tenga un valor
    const hasComponente = idcomponentedoc && idcomponentedoc.value.trim() !== '';

    if (btnInfoGeneral)   btnInfoGeneral.disabled   = false; // siempre disponible
    if (btnDocumentacion) btnDocumentacion.disabled = !hasComponente;
    if (btnProcesos)      btnProcesos.disabled      = !hasComponente;
    if (btnFinalizado)    btnFinalizado.disabled    = !hasComponente;
}

// ------------------------------------------------------------------------
//  VER EL CATÁLOGO DE PRODUCTOS
// ------------------------------------------------------------------------
function fntInventarios(selectedValue = "") {
    const selectProductos = document.querySelector('#listProductos');
    if (selectProductos) {
        let ajaxUrl = base_url + '/Plan_bomcomponentes/getSelectProductos';
        let request = (window.XMLHttpRequest)
            ? new XMLHttpRequest()
            : new ActiveXObject('Microsoft.XMLHTTP');

        request.open("GET", ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                selectProductos.innerHTML = request.responseText;

                if (selectedValue !== "") {
                    selectProductos.value = selectedValue;
                }
            }
        };

        // cambio de producto → cargar detalle
        selectProductos.addEventListener('change', function () {
            const idProducto = this.value;
            if (idProducto !== "") {
                fntInventarioDetalle(idProducto);
            }
        });
    }
}

// ------------------------------------------------------------------------
//  DETALLE DEL INVENTARIO SELECCIONADO
// ------------------------------------------------------------------------
function fntInventarioDetalle(idInventario) {
    let ajaxUrl = base_url + '/Plan_bomcomponentes/getSelectInventario/' + idInventario;
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData       = JSON.parse(request.responseText);
            let descripcion   = objData.descripcion;
            let lineaproducto = objData.lineaproductoid;

            let inputDescripcion      = document.getElementById("txtDescripcion");
            let selectLineasProductos = document.getElementById("listLineasProductos");

            if (inputDescripcion)      inputDescripcion.value      = descripcion;
            if (selectLineasProductos) selectLineasProductos.value = lineaproducto;
        }
    };
}

// ------------------------------------------------------------------------
//  VER EL CATÁLOGO DE LÍNEAS DE PRODUCTO
// ------------------------------------------------------------------------
function fntLineasProducto(selectedValue = "") {
    const selectLineasProductos = document.querySelector('#listLineasProductos');
    if (selectLineasProductos) {
        let ajaxUrl = base_url + '/Plan_bomcomponentes/getSelectLineasProductos';
        let request = (window.XMLHttpRequest)
            ? new XMLHttpRequest()
            : new ActiveXObject('Microsoft.XMLHTTP');

        request.open("GET", ajaxUrl, true);
        request.send();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                selectLineasProductos.innerHTML = request.responseText;

                if (selectedValue !== "") {
                    selectLineasProductos.value = selectedValue;
                }
            }
        }; 
    }
}

// ------------------------------------------------------------------------
//  ELIMINAR UN DOCUMENTO POR COMPONENTE
// ------------------------------------------------------------------------

function fntDelDocumento(iddocumento){

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
        let ajaxUrl = base_url + '/Plan_bomcomponentes/delDocumento';
        let strData = "iddocumento=" + iddocumento;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    if (tableDocumentos) tableDocumentos.ajax.reload();
                } else {
                    Swal.fire("Atención!", objData.msg, "error");
                }
            }
        }

    });

}


// ------------------------------------------------------------------------
//  EDITAR COMPONENTE
// ------------------------------------------------------------------------

function fntEditComponente(idcomponente) {
    if (!idcomponente) return;

    if (divLoading) divLoading.style.display = "flex";

    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/Plan_bomcomponentes/getComponente/' + idcomponente;

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState != 4) return;

        if (divLoading) divLoading.style.display = "none";

        if (request.status != 200) {
            Swal.fire("Error", "Error al consultar el componente.", "error");
            return;
        }

        let objData = JSON.parse(request.responseText);

        if (!objData.status) {
            Swal.fire("Aviso", objData.msg || "No se encontró la información del componente.", "warning");
            return;
        }

        // Datos reales del componente
        let data = objData.data || objData;

        // -----------------------------------------------------------------
        // 1) Abrir la pestaña "NUEVO" (agregarProducto)
        // -----------------------------------------------------------------
        const tabAgregarEl = document.querySelector('#nav-tab a[href="#agregarProducto"]');
        if (tabAgregarEl) {
            const tab = new bootstrap.Tab(tabAgregarEl);
            tab.show();
            tabNuevo = tabAgregarEl; // actualizamos la referencia global por si acaso
        }

        // -----------------------------------------------------------------
        // 2) Cambiar textos a ACTUALIZAR
        // -----------------------------------------------------------------
        if (tabNuevo)    tabNuevo.textContent    = 'ACTUALIZAR';
        if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';

        // -----------------------------------------------------------------
        // 3) Setear los IDs ocultos
        // -----------------------------------------------------------------
        if (estacion)        estacion.value        = data.idcomponente;
        if (idcomponentedoc) idcomponentedoc.value = data.idcomponente;

        // Habilitar tabs inferiores porque ya hay componente
        refreshLowerTabs();

        // -----------------------------------------------------------------
        // 4) Llenar formulario formBomComponentes
        // -----------------------------------------------------------------
        const selectProductos       = document.querySelector('#listProductos');
        const selectLineasProductos = document.querySelector('#listLineasProductos');
        const inputDescripcion      = document.querySelector('#txtDescripcion');

        if (selectProductos && data.inventarioid) {
            selectProductos.value = data.inventarioid;
        }

        if (inputDescripcion && data.descripcion) {
            inputDescripcion.value = data.descripcion;
        }

        if (selectLineasProductos && data.lineaproductoid) {
            selectLineasProductos.value = data.lineaproductoid;
        }
        setInfoGeneralActive();

        // Aquí podrías llenar más campos si los agregas al formulario
        // por ejemplo estado, observaciones, etc.
    };
}

function setInfoGeneralActive() {
    if (!btnInfoGeneral) return;

    // Usamos el Tab de Bootstrap para activar ese botón/pestaña
    const tabInfo = new bootstrap.Tab(btnInfoGeneral);
    tabInfo.show();
}


